<?php

/**
 * Example Configuration file for Port Statistics Monitoring Script
 * 
 * Copy this file to config.php and update the values according to your environment.
 * This example file contains default/placeholder values.
 */

// Database Configuration - UPDATE THESE VALUES
define('DB_HOST', 'localhost');
define('DB_NAME', 'your_database_name');
define('DB_USER', 'your_username');
define('DB_PASS', 'your_password');
define('DB_CHARSET', 'utf8');

// PDO Configuration Options
$pdoOptions = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

// SNMP Configuration - UPDATE COMMUNITY STRING
define('SNMP_VERSION', SNMP::VERSION_2c);
define('SNMP_COMMUNITY', 'your_snmp_community_string');
define('SNMP_TIMEOUT', 5000000); // 5 seconds in microseconds
define('SNMP_RETRIES', 3);

// SNMP OID Configuration
$snmpOIDs = [
    'ifDescr' => '.iso.org.dod.internet.mgmt.mib-2.interfaces.ifTable.ifEntry.ifDescr',
    'ifName' => '.iso.org.dod.internet.mgmt.mib-2.ifMIB.ifMIBObjects.ifXTable.ifXEntry.ifName',
    'ifInErrors' => '.iso.org.dod.internet.mgmt.mib-2.interfaces.ifTable.ifEntry.ifInErrors',
    'ifHighSpeed' => '.iso.org.dod.internet.mgmt.mib-2.ifMIB.ifMIBObjects.ifXTable.ifXEntry.ifHighSpeed'
];

// Application Configuration
define('DEBUG_MODE', false); // Set to true for development
define('LOG_ERRORS', true);
define('LOG_FILE', 'monitoring.log');

// Error Messages
$errorMessages = [
    'db_connection_failed' => 'Unable to connect to database',
    'no_devices_found' => 'No devices present.',
    'snmp_connection_failed' => 'Failed to connect to device via SNMP',
    'interface_select_error' => 'Selecting interfaceoid went wrong',
    'statistics_insert_error' => 'Something went wrong while inserting statistics',
    'ports_insert_error' => 'Something went wrong while inserting port data'
];

// Timezone Configuration
date_default_timezone_set('UTC'); // Change to your timezone

// Security Configuration
define('ALLOWED_IPS', []); // Add allowed IP addresses: ['192.168.1.100', '10.0.0.50']
define('REQUIRE_AUTH', false); // Set to true to enable IP-based access control

/**
 * Function to get database connection
 * @return PDO
 * @throws PDOException
 */
function getDatabaseConnection()
{
    global $pdoOptions;

    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;

    try {
        return new PDO($dsn, DB_USER, DB_PASS, $pdoOptions);
    } catch (PDOException $e) {
        if (DEBUG_MODE) {
            throw $e;
        } else {
            throw new PDOException("Database connection failed");
        }
    }
}

/**
 * Function to log errors
 * @param string $message
 * @param string $level
 */
function logError($message, $level = 'ERROR')
{
    if (LOG_ERRORS) {
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[$timestamp] [$level] $message" . PHP_EOL;
        file_put_contents(LOG_FILE, $logMessage, FILE_APPEND | LOCK_EX);
    }
}

/**
 * Function to check if IP is allowed
 * @param string $ip
 * @return bool
 */
function isIpAllowed($ip)
{
    if (empty(ALLOWED_IPS)) {
        return true;
    }
    return in_array($ip, ALLOWED_IPS);
}
