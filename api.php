<?php

/**
 * API Endpoint for Port Statistics Monitoring Dashboard
 * Provides JSON data for AJAX requests and real-time updates
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once 'config.php';

// Get the requested action
$action = $_GET['action'] ?? 'dashboard_stats';

try {
    $pdo = getDatabaseConnection();
    $response = ['success' => true, 'data' => null];

    switch ($action) {
        case 'dashboard_stats':
            $response['data'] = getDashboardStats($pdo);
            break;

        case 'device_list':
            $response['data'] = getDeviceList($pdo);
            break;

        case 'device_details':
            $deviceId = $_GET['device_id'] ?? null;
            if ($deviceId) {
                $response['data'] = getDeviceDetails($pdo, $deviceId);
            } else {
                throw new Exception('Device ID is required');
            }
            break;

        case 'recent_statistics':
            $limit = $_GET['limit'] ?? 20;
            $response['data'] = getRecentStatistics($pdo, $limit);
            break;

        case 'error_trends':
            $hours = $_GET['hours'] ?? 24;
            $response['data'] = getErrorTrends($pdo, $hours);
            break;

        case 'port_statistics':
            $deviceId = $_GET['device_id'] ?? null;
            if ($deviceId) {
                $response['data'] = getPortStatistics($pdo, $deviceId);
            } else {
                throw new Exception('Device ID is required');
            }
            break;

        case 'system_health':
            $response['data'] = getSystemHealth($pdo);
            break;

        default:
            throw new Exception('Invalid action');
    }
} catch (Exception $e) {
    $response = [
        'success' => false,
        'error' => $e->getMessage()
    ];
    logError("API Error: " . $e->getMessage());
}

echo json_encode($response, JSON_PRETTY_PRINT);

/**
 * Get dashboard statistics
 */
function getDashboardStats($pdo)
{
    $stats = [];

    // Device count
    $stats['device_count'] = $pdo->query("SELECT COUNT(*) FROM devices")->fetchColumn();

    // Port count
    $stats['port_count'] = $pdo->query("SELECT COUNT(*) FROM ports")->fetchColumn();

    // Error count (ports with errors in last 24h)
    $stats['error_count'] = $pdo->query("
        SELECT COUNT(DISTINCT portid) 
        FROM statistics 
        WHERE interfaceerror > 0 
        AND time >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
    ")->fetchColumn();

    // Total statistics records
    $stats['total_records'] = $pdo->query("SELECT COUNT(*) FROM statistics")->fetchColumn();

    // Active devices (devices with recent statistics)
    $stats['active_devices'] = $pdo->query("
        SELECT COUNT(DISTINCT d.deviceid) 
        FROM devices d 
        JOIN ports p ON d.deviceid = p.deviceid 
        JOIN statistics s ON p.id = s.portid 
        WHERE s.time >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
    ")->fetchColumn();

    return $stats;
}

/**
 * Get device list with status
 */
function getDeviceList($pdo)
{
    $stmt = $pdo->query("
        SELECT d.*, 
               COUNT(p.id) as port_count,
               MAX(s.time) as last_seen,
               AVG(CAST(s.interfaceerror AS UNSIGNED)) as avg_errors,
               SUM(CASE WHEN s.interfaceerror > 0 THEN 1 ELSE 0 END) as error_ports
        FROM devices d 
        LEFT JOIN ports p ON d.deviceid = p.deviceid 
        LEFT JOIN statistics s ON p.id = s.portid 
        GROUP BY d.deviceid 
        ORDER BY d.deviceid
    ");

    $devices = [];
    while ($device = $stmt->fetch()) {
        $device['status'] = determineDeviceStatus($device['last_seen']);
        $devices[] = $device;
    }

    return $devices;
}

/**
 * Get detailed information for a specific device
 */
function getDeviceDetails($pdo, $deviceId)
{
    // Device info
    $stmt = $pdo->prepare("SELECT * FROM devices WHERE deviceid = ?");
    $stmt->execute([$deviceId]);
    $device = $stmt->fetch();

    if (!$device) {
        throw new Exception('Device not found');
    }

    // Port information
    $stmt = $pdo->prepare("
        SELECT p.*, 
               COUNT(s.id) as stat_count,
               MAX(s.time) as last_update,
               AVG(CAST(s.interfaceerror AS UNSIGNED)) as avg_errors,
               MAX(CAST(s.ifhighspeed AS UNSIGNED)) as max_speed
        FROM ports p 
        LEFT JOIN statistics s ON p.id = s.portid 
        WHERE p.deviceid = ? 
        GROUP BY p.id
    ");
    $stmt->execute([$deviceId]);
    $ports = $stmt->fetchAll();

    return [
        'device' => $device,
        'ports' => $ports
    ];
}

/**
 * Get recent statistics
 */
function getRecentStatistics($pdo, $limit = 20)
{
    $stmt = $pdo->prepare("
        SELECT s.*, 
               p.devicename, 
               p.interfacename, 
               d.ipaddress,
               d.device as device_name
        FROM statistics s
        JOIN ports p ON s.portid = p.id
        JOIN devices d ON p.deviceid = d.deviceid
        ORDER BY s.time DESC
        LIMIT ?
    ");
    $stmt->execute([$limit]);

    return $stmt->fetchAll();
}

/**
 * Get error trends for charts
 */
function getErrorTrends($pdo, $hours = 24)
{
    $stmt = $pdo->prepare("
        SELECT 
            DATE_FORMAT(time, '%Y-%m-%d %H:00:00') as hour,
            COUNT(*) as total_records,
            SUM(CASE WHEN interfaceerror > 0 THEN 1 ELSE 0 END) as error_records,
            AVG(CAST(interfaceerror AS UNSIGNED)) as avg_errors
        FROM statistics 
        WHERE time >= DATE_SUB(NOW(), INTERVAL ? HOUR)
        GROUP BY DATE_FORMAT(time, '%Y-%m-%d %H:00:00')
        ORDER BY hour
    ");
    $stmt->execute([$hours]);

    return $stmt->fetchAll();
}

/**
 * Get port statistics for a specific device
 */
function getPortStatistics($pdo, $deviceId)
{
    $stmt = $pdo->prepare("
        SELECT 
            p.interfacename,
            COUNT(s.id) as record_count,
            SUM(CASE WHEN s.interfaceerror > 0 THEN 1 ELSE 0 END) as error_count,
            AVG(CAST(s.interfaceerror AS UNSIGNED)) as avg_errors,
            MAX(CAST(s.ifhighspeed AS UNSIGNED)) as max_speed,
            MIN(s.time) as first_seen,
            MAX(s.time) as last_seen
        FROM ports p
        LEFT JOIN statistics s ON p.id = s.portid
        WHERE p.deviceid = ?
        GROUP BY p.id, p.interfacename
        ORDER BY p.interfacename
    ");
    $stmt->execute([$deviceId]);

    return $stmt->fetchAll();
}

/**
 * Get system health information
 */
function getSystemHealth($pdo)
{
    $health = [];

    // Database connectivity
    $health['database'] = 'connected';

    // Recent activity (records in last hour)
    $recentRecords = $pdo->query("
        SELECT COUNT(*) FROM statistics 
        WHERE time >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
    ")->fetchColumn();

    $health['recent_activity'] = $recentRecords > 0 ? 'active' : 'inactive';

    // Data freshness (most recent record)
    $lastRecord = $pdo->query("SELECT MAX(time) FROM statistics")->fetchColumn();
    $minutesSinceLastRecord = $lastRecord ?
        (time() - strtotime($lastRecord)) / 60 : 9999;

    $health['data_freshness'] = $minutesSinceLastRecord < 60 ? 'fresh' : 'stale';

    // Error rate (percentage of records with errors in last 24h)
    $errorRate = $pdo->query("
        SELECT 
            (SUM(CASE WHEN interfaceerror > 0 THEN 1 ELSE 0 END) / COUNT(*)) * 100 as error_rate
        FROM statistics 
        WHERE time >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
    ")->fetchColumn();

    $health['error_rate'] = round($errorRate, 2);

    return $health;
}

/**
 * Determine device status based on last seen time
 */
function determineDeviceStatus($lastSeen)
{
    if (!$lastSeen) {
        return 'never_seen';
    }

    $lastSeenTime = strtotime($lastSeen);
    $currentTime = time();
    $minutesSinceLastSeen = ($currentTime - $lastSeenTime) / 60;

    if ($minutesSinceLastSeen <= 60) {
        return 'online';
    } elseif ($minutesSinceLastSeen <= 240) {
        return 'warning';
    } else {
        return 'offline';
    }
}
