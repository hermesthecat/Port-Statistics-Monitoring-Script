<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Port Statistics Monitoring Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #3498db;
            --success-color: #27ae60;
            --warning-color: #f39c12;
            --danger-color: #e74c3c;
            --light-bg: #f8f9fa;
        }

        body {
            background-color: var(--light-bg);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .navbar {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
        }

        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }

        .card:hover {
            transform: translateY(-5px);
        }

        .stats-card {
            text-align: center;
            padding: 2rem;
        }

        .stats-card .icon {
            font-size: 3rem;
            margin-bottom: 1rem;
        }

        .stats-card .number {
            font-size: 2.5rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }

        .table-responsive {
            border-radius: 15px;
            overflow: hidden;
        }

        .status-indicator {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 8px;
        }

        .status-online {
            background-color: var(--success-color);
        }

        .status-warning {
            background-color: var(--warning-color);
        }

        .status-error {
            background-color: var(--danger-color);
        }

        .refresh-btn {
            position: fixed;
            bottom: 30px;
            right: 30px;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: var(--secondary-color);
            border: none;
            color: white;
            font-size: 1.5rem;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
            transition: all 0.3s ease;
        }

        .refresh-btn:hover {
            background: var(--primary-color);
            transform: rotate(180deg);
        }

        .chart-container {
            position: relative;
            height: 300px;
        }

        .last-updated {
            font-size: 0.9rem;
            color: #6c757d;
            text-align: center;
            margin-top: 1rem;
        }
    </style>
</head>

<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="#">
                <i class="fas fa-network-wired me-2"></i>
                Port Statistics Monitor
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="#"><i class="fas fa-tachometer-alt me-1"></i>Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#devices"><i class="fas fa-server me-1"></i>Devices</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#statistics"><i class="fas fa-chart-line me-1"></i>Statistics</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <?php
        require_once 'config.php';

        try {
            $pdo = getDatabaseConnection();

            // Get statistics
            $deviceCount = $pdo->query("SELECT COUNT(*) FROM devices")->fetchColumn();
            $portCount = $pdo->query("SELECT COUNT(*) FROM ports")->fetchColumn();
            $errorCount = $pdo->query("SELECT COUNT(*) FROM statistics WHERE interfaceerror > 0")->fetchColumn();
            $totalStats = $pdo->query("SELECT COUNT(*) FROM statistics")->fetchColumn();
        } catch (PDOException $e) {
            $deviceCount = $portCount = $errorCount = $totalStats = 0;
            $error_message = "Database connection failed: " . $e->getMessage();
        }
        ?>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-3 mb-3">
                <div class="card stats-card bg-primary text-white">
                    <div class="icon">
                        <i class="fas fa-server"></i>
                    </div>
                    <div class="number"><?php echo $deviceCount; ?></div>
                    <div>Total Devices</div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card stats-card bg-info text-white">
                    <div class="icon">
                        <i class="fas fa-ethernet"></i>
                    </div>
                    <div class="number"><?php echo $portCount; ?></div>
                    <div>Total Ports</div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card stats-card bg-warning text-white">
                    <div class="icon">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <div class="number"><?php echo $errorCount; ?></div>
                    <div>Ports with Errors</div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card stats-card bg-success text-white">
                    <div class="icon">
                        <i class="fas fa-chart-bar"></i>
                    </div>
                    <div class="number"><?php echo $totalStats; ?></div>
                    <div>Total Records</div>
                </div>
            </div>
        </div>

        <!-- Error Alert -->
        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i>
                <?php echo htmlspecialchars($error_message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Charts Row -->
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0"><i class="fas fa-chart-pie me-2"></i>Device Status Overview</h5>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="deviceStatusChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0"><i class="fas fa-chart-line me-2"></i>Error Trends (Last 24h)</h5>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="errorTrendChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Devices Table -->
        <div class="row" id="devices">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0"><i class="fas fa-server me-2"></i>Network Devices</h5>
                        <button class="btn btn-primary btn-sm" onclick="refreshData()">
                            <i class="fas fa-sync-alt me-1"></i>Refresh
                        </button>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Status</th>
                                        <th>Device ID</th>
                                        <th>Device Name</th>
                                        <th>IP Address</th>
                                        <th>Type</th>
                                        <th>Ports</th>
                                        <th>Last Seen</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    try {
                                        $stmt = $pdo->query("
                                            SELECT d.*, 
                                                   COUNT(p.id) as port_count,
                                                   MAX(s.time) as last_seen
                                            FROM devices d 
                                            LEFT JOIN ports p ON d.deviceid = p.deviceid 
                                            LEFT JOIN statistics s ON p.id = s.portid 
                                            GROUP BY d.deviceid 
                                            ORDER BY d.deviceid
                                        ");

                                        while ($device = $stmt->fetch()) {
                                            $status_class = 'status-online';
                                            $status_text = 'Online';

                                            if (!$device['last_seen']) {
                                                $status_class = 'status-error';
                                                $status_text = 'Never seen';
                                            } elseif (strtotime($device['last_seen']) < strtotime('-1 hour')) {
                                                $status_class = 'status-warning';
                                                $status_text = 'Stale';
                                            }

                                            echo "<tr>";
                                            echo "<td><span class='status-indicator $status_class'></span>$status_text</td>";
                                            echo "<td>" . htmlspecialchars($device['deviceid']) . "</td>";
                                            echo "<td>" . htmlspecialchars($device['device'] ?? 'N/A') . "</td>";
                                            echo "<td>" . htmlspecialchars($device['ipaddress']) . "</td>";
                                            echo "<td>" . htmlspecialchars($device['type'] ?? 'Unknown') . "</td>";
                                            echo "<td><span class='badge bg-info'>" . $device['port_count'] . "</span></td>";
                                            echo "<td>" . ($device['last_seen'] ? date('Y-m-d H:i:s', strtotime($device['last_seen'])) : 'Never') . "</td>";
                                            echo "<td>";
                                            echo "<button class='btn btn-sm btn-outline-primary me-1' onclick='viewDevice(" . $device['deviceid'] . ")'>";
                                            echo "<i class='fas fa-eye'></i>";
                                            echo "</button>";
                                            echo "<button class='btn btn-sm btn-outline-secondary' onclick='testConnection(\"" . $device['ipaddress'] . "\")'>";
                                            echo "<i class='fas fa-network-wired'></i>";
                                            echo "</button>";
                                            echo "</td>";
                                            echo "</tr>";
                                        }
                                    } catch (PDOException $e) {
                                        echo "<tr><td colspan='8' class='text-center text-danger'>Error loading devices: " . htmlspecialchars($e->getMessage()) . "</td></tr>";
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Statistics -->
        <div class="row mt-4" id="statistics">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0"><i class="fas fa-chart-line me-2"></i>Recent Statistics</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Time</th>
                                        <th>Device</th>
                                        <th>Interface</th>
                                        <th>Errors</th>
                                        <th>Speed (Mbps)</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    try {
                                        $stmt = $pdo->query("
                                            SELECT s.*, p.devicename, p.interfacename, d.ipaddress
                                            FROM statistics s
                                            JOIN ports p ON s.portid = p.id
                                            JOIN devices d ON p.deviceid = d.deviceid
                                            ORDER BY s.time DESC
                                            LIMIT 20
                                        ");

                                        while ($stat = $stmt->fetch()) {
                                            $error_class = $stat['interfaceerror'] > 0 ? 'text-danger' : 'text-success';
                                            $status_badge = $stat['interfaceerror'] > 0 ? 'bg-danger' : 'bg-success';
                                            $status_text = $stat['interfaceerror'] > 0 ? 'Errors' : 'OK';

                                            echo "<tr>";
                                            echo "<td>" . date('Y-m-d H:i:s', strtotime($stat['time'])) . "</td>";
                                            echo "<td>" . htmlspecialchars($stat['devicename']) . "<br><small class='text-muted'>" . htmlspecialchars($stat['ipaddress']) . "</small></td>";
                                            echo "<td>" . htmlspecialchars($stat['interfacename']) . "</td>";
                                            echo "<td class='$error_class'><strong>" . $stat['interfaceerror'] . "</strong></td>";
                                            echo "<td>" . $stat['ifhighspeed'] . "</td>";
                                            echo "<td><span class='badge $status_badge'>$status_text</span></td>";
                                            echo "</tr>";
                                        }
                                    } catch (PDOException $e) {
                                        echo "<tr><td colspan='6' class='text-center text-danger'>Error loading statistics: " . htmlspecialchars($e->getMessage()) . "</td></tr>";
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="last-updated">
            Last updated: <?php echo date('Y-m-d H:i:s'); ?>
        </div>
    </div>

    <!-- Floating Refresh Button -->
    <button class="refresh-btn" onclick="refreshData()" title="Refresh Data">
        <i class="fas fa-sync-alt"></i>
    </button>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="dashboard.js"></script>
</body>

</html>