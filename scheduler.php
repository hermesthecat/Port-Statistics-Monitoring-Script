<?php

/**
 * SNMP Data Collection Scheduler
 * Command-line script for automated data collection via cron jobs
 * 
 * Usage:
 * php scheduler.php [options]
 * 
 * Options:
 * --device=ID     Collect data for specific device only
 * --force         Force collection even if recent data exists
 * --verbose       Enable verbose output
 * --dry-run       Show what would be done without executing
 * --help          Show this help message
 */

// Ensure this script runs only from command line
if (php_sapi_name() !== 'cli') {
    die('This script can only be run from the command line.');
}

require_once 'config.php';

class SNMPScheduler
{
    private $pdo;
    private $options;
    private $stats;

    public function __construct($options = [])
    {
        $this->options = array_merge([
            'device' => null,
            'force' => false,
            'verbose' => false,
            'dry-run' => false,
            'help' => false
        ], $options);

        $this->stats = [
            'devices_processed' => 0,
            'ports_updated' => 0,
            'statistics_collected' => 0,
            'errors' => 0,
            'start_time' => time()
        ];

        try {
            $this->pdo = getDatabaseConnection();
        } catch (PDOException $e) {
            $this->logError("Database connection failed: " . $e->getMessage());
            exit(1);
        }
    }

    public function run()
    {
        if ($this->options['help']) {
            $this->showHelp();
            return;
        }

        $this->log("Starting SNMP data collection scheduler...");

        if ($this->options['dry-run']) {
            $this->log("DRY RUN MODE - No actual data will be collected");
        }

        try {
            $devices = $this->getDevicesToProcess();

            if (empty($devices)) {
                $this->log("No devices to process.");
                return;
            }

            $this->log("Found " . count($devices) . " device(s) to process");

            foreach ($devices as $device) {
                $this->processDevice($device);
            }

            $this->printSummary();
        } catch (Exception $e) {
            $this->logError("Scheduler error: " . $e->getMessage());
            exit(1);
        }
    }

    private function getDevicesToProcess()
    {
        $sql = "SELECT * FROM devices";
        $params = [];

        if ($this->options['device']) {
            $sql .= " WHERE deviceid = ?";
            $params[] = $this->options['device'];
        } else if (!$this->options['force']) {
            // Only process devices that haven't been updated in the last hour
            $sql .= " WHERE deviceid NOT IN (
                SELECT DISTINCT d.deviceid 
                FROM devices d 
                JOIN ports p ON d.deviceid = p.deviceid 
                JOIN statistics s ON p.id = s.portid 
                WHERE s.time >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
            )";
        }

        $sql .= " ORDER BY deviceid";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    private function processDevice($device)
    {
        $this->log("Processing device: " . $device['ipaddress'] . " (ID: " . $device['deviceid'] . ")");

        if ($this->options['dry-run']) {
            $this->log("  [DRY RUN] Would collect SNMP data from " . $device['ipaddress']);
            $this->stats['devices_processed']++;
            return;
        }

        try {
            // Test SNMP connectivity first
            if (!$this->testSNMPConnectivity($device['ipaddress'])) {
                $this->logError("  SNMP connectivity test failed for " . $device['ipaddress']);
                $this->stats['errors']++;
                return;
            }

            // Collect interface information
            $interfaces = $this->collectInterfaceData($device);

            if (!empty($interfaces)) {
                $this->updatePortsData($device['deviceid'], $interfaces);
                $this->collectStatistics($device['deviceid'], $interfaces);
                $this->stats['devices_processed']++;
                $this->log("  Successfully processed " . count($interfaces) . " interfaces");
            } else {
                $this->log("  No interfaces found for device " . $device['ipaddress']);
            }
        } catch (Exception $e) {
            $this->logError("  Error processing device " . $device['ipaddress'] . ": " . $e->getMessage());
            $this->stats['errors']++;
        }
    }

    private function testSNMPConnectivity($ipAddress)
    {
        try {
            $session = new SNMP(SNMP_VERSION, $ipAddress, SNMP_COMMUNITY);
            $session->valueretrieval = SNMP_VALUE_PLAIN;

            // Try to get system description as a connectivity test
            $result = $session->get(".1.3.6.1.2.1.1.1.0", TRUE);
            $session->close();

            return !empty($result);
        } catch (Exception $e) {
            return false;
        }
    }

    private function collectInterfaceData($device)
    {
        global $snmpOIDs;
        $interfaces = [];

        try {
            $session = new SNMP(SNMP_VERSION, $device['ipaddress'], SNMP_COMMUNITY);
            $session->valueretrieval = SNMP_VALUE_PLAIN;

            // Get interface names
            $ifNames = $session->walk($snmpOIDs['ifName'], TRUE);

            if (!empty($ifNames)) {
                // Get device hostname/description
                $deviceName = $session->get($snmpOIDs['ifDescr'] . ".1", TRUE);

                foreach ($ifNames as $oid => $interfaceName) {
                    $interfaceIndex = str_replace($snmpOIDs['ifName'] . ".", "", $oid);

                    $interfaces[] = [
                        'name' => $interfaceName,
                        'oid' => $interfaceIndex,
                        'device_name' => $deviceName
                    ];
                }
            }

            $session->close();
        } catch (Exception $e) {
            $this->logError("  Failed to collect interface data: " . $e->getMessage());
        }

        return $interfaces;
    }

    private function updatePortsData($deviceId, $interfaces)
    {
        foreach ($interfaces as $interface) {
            try {
                $stmt = $this->pdo->prepare("
                    INSERT INTO ports (devicename, interfacename, interfaceoid, deviceid) 
                    VALUES (:devicename, :interfacename, :interfaceoid, :deviceid)
                    ON DUPLICATE KEY UPDATE 
                        devicename = :devicename_update,
                        interfacename = :interfacename_update
                ");

                $stmt->execute([
                    ':devicename' => $interface['device_name'],
                    ':interfacename' => $interface['name'],
                    ':interfaceoid' => $interface['oid'],
                    ':deviceid' => $deviceId,
                    ':devicename_update' => $interface['device_name'],
                    ':interfacename_update' => $interface['name']
                ]);

                $this->stats['ports_updated']++;
            } catch (PDOException $e) {
                $this->logError("  Error updating port data: " . $e->getMessage());
                $this->stats['errors']++;
            }
        }
    }

    private function collectStatistics($deviceId, $interfaces)
    {
        global $snmpOIDs;

        try {
            // Get all ports for this device
            $stmt = $this->pdo->prepare("SELECT id, interfaceoid FROM ports WHERE deviceid = ?");
            $stmt->execute([$deviceId]);
            $ports = $stmt->fetchAll();

            if (empty($ports)) {
                return;
            }

            // Get device IP for SNMP connection
            $stmt = $this->pdo->prepare("SELECT ipaddress FROM devices WHERE deviceid = ?");
            $stmt->execute([$deviceId]);
            $device = $stmt->fetch();

            $session = new SNMP(SNMP_VERSION, $device['ipaddress'], SNMP_COMMUNITY);
            $session->valueretrieval = SNMP_VALUE_PLAIN;

            foreach ($ports as $port) {
                try {
                    $interfaceOid = $port['interfaceoid'];

                    // Collect error statistics
                    $errorOid = $snmpOIDs['ifInErrors'] . "." . $interfaceOid;
                    $errors = $session->get($errorOid, TRUE);

                    // Collect speed information
                    $speedOid = $snmpOIDs['ifHighSpeed'] . "." . $interfaceOid;
                    $speed = $session->get($speedOid, TRUE);

                    // Insert statistics record
                    $stmt = $this->pdo->prepare("
                        INSERT INTO statistics (erroroid, interfaceerror, highspeedoid, ifhighspeed, time, portid) 
                        VALUES (:erroroid, :interfaceerror, :highspeedoid, :ifhighspeed, NOW(), :portid)
                    ");

                    $stmt->execute([
                        ':erroroid' => $errorOid,
                        ':interfaceerror' => $errors ?: 0,
                        ':highspeedoid' => $speedOid,
                        ':ifhighspeed' => $speed ?: 0,
                        ':portid' => $port['id']
                    ]);

                    $this->stats['statistics_collected']++;
                } catch (Exception $e) {
                    $this->logError("  Error collecting statistics for port " . $port['interfaceoid'] . ": " . $e->getMessage());
                    $this->stats['errors']++;
                }
            }

            $session->close();
        } catch (Exception $e) {
            $this->logError("  Error in statistics collection: " . $e->getMessage());
            $this->stats['errors']++;
        }
    }

    private function log($message)
    {
        if ($this->options['verbose'] || $this->options['dry-run']) {
            $timestamp = date('Y-m-d H:i:s');
            echo "[$timestamp] $message" . PHP_EOL;
        }

        // Always log to file
        logError($message, 'INFO');
    }

    private function logError($message)
    {
        $timestamp = date('Y-m-d H:i:s');
        echo "[$timestamp] ERROR: $message" . PHP_EOL;
        logError($message, 'ERROR');
    }

    private function printSummary()
    {
        $duration = time() - $this->stats['start_time'];

        echo PHP_EOL . "=== COLLECTION SUMMARY ===" . PHP_EOL;
        echo "Duration: {$duration} seconds" . PHP_EOL;
        echo "Devices processed: {$this->stats['devices_processed']}" . PHP_EOL;
        echo "Ports updated: {$this->stats['ports_updated']}" . PHP_EOL;
        echo "Statistics collected: {$this->stats['statistics_collected']}" . PHP_EOL;
        echo "Errors encountered: {$this->stats['errors']}" . PHP_EOL;
        echo "=========================" . PHP_EOL;

        // Log summary to database
        $this->logCollectionRun();
    }

    private function logCollectionRun()
    {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO collection_logs (
                    start_time, duration, devices_processed, ports_updated, 
                    statistics_collected, errors, options
                ) VALUES (
                    FROM_UNIXTIME(?), ?, ?, ?, ?, ?, ?
                )
            ");

            $stmt->execute([
                $this->stats['start_time'],
                time() - $this->stats['start_time'],
                $this->stats['devices_processed'],
                $this->stats['ports_updated'],
                $this->stats['statistics_collected'],
                $this->stats['errors'],
                json_encode($this->options)
            ]);
        } catch (PDOException $e) {
            // Don't fail if logging doesn't work
            $this->logError("Failed to log collection run: " . $e->getMessage());
        }
    }

    private function showHelp()
    {
        echo "SNMP Data Collection Scheduler" . PHP_EOL . PHP_EOL;
        echo "Usage: php scheduler.php [options]" . PHP_EOL . PHP_EOL;
        echo "Options:" . PHP_EOL;
        echo "  --device=ID     Collect data for specific device only" . PHP_EOL;
        echo "  --force         Force collection even if recent data exists" . PHP_EOL;
        echo "  --verbose       Enable verbose output" . PHP_EOL;
        echo "  --dry-run       Show what would be done without executing" . PHP_EOL;
        echo "  --help          Show this help message" . PHP_EOL . PHP_EOL;
        echo "Examples:" . PHP_EOL;
        echo "  php scheduler.php --verbose" . PHP_EOL;
        echo "  php scheduler.php --device=123 --force" . PHP_EOL;
        echo "  php scheduler.php --dry-run" . PHP_EOL;
    }
}

// Parse command line arguments
function parseOptions($argv)
{
    $options = [];

    for ($i = 1; $i < count($argv); $i++) {
        $arg = $argv[$i];

        if (strpos($arg, '--') === 0) {
            $arg = substr($arg, 2);

            if (strpos($arg, '=') !== false) {
                list($key, $value) = explode('=', $arg, 2);
                $options[$key] = $value;
            } else {
                $options[$arg] = true;
            }
        }
    }

    return $options;
}

// Main execution
if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    $options = parseOptions($argv);
    $scheduler = new SNMPScheduler($options);
    $scheduler->run();
}
