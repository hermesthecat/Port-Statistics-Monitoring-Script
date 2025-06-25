<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Device Details - Port Statistics Monitoring</title>
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
            margin-bottom: 2rem;
        }

        .device-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 2rem;
            border-radius: 15px 15px 0 0;
        }

        .port-card {
            border-left: 4px solid var(--secondary-color);
            transition: all 0.3s ease;
        }

        .port-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.1);
        }

        .port-card.error {
            border-left-color: var(--danger-color);
        }

        .port-card.warning {
            border-left-color: var(--warning-color);
        }

        .port-card.success {
            border-left-color: var(--success-color);
        }

        .metric-value {
            font-size: 2rem;
            font-weight: bold;
            color: var(--primary-color);
        }

        .metric-label {
            font-size: 0.9rem;
            color: #6c757d;
            text-transform: uppercase;
        }

        .status-badge {
            font-size: 0.8rem;
            padding: 0.4rem 0.8rem;
        }

        .chart-container {
            position: relative;
            height: 300px;
            margin: 1rem 0;
        }

        .back-btn {
            background: var(--secondary-color);
            border: none;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 25px;
            transition: all 0.3s ease;
        }

        .back-btn:hover {
            background: var(--primary-color);
            color: white;
        }
    </style>
</head>

<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">
                <i class="fas fa-network-wired me-2"></i>
                Port Statistics Monitor
            </a>
            <div class="navbar-nav ms-auto">
                <a class="btn back-btn" href="dashboard.php">
                    <i class="fas fa-arrow-left me-1"></i>Back to Dashboard
                </a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <?php
        require_once 'config.php';

        $deviceId = $_GET['id'] ?? null;
        if (!$deviceId) {
            echo '<div class="alert alert-danger">Device ID is required</div>';
            exit;
        }

        try {
            $pdo = getDatabaseConnection();

            // Get device details
            $stmt = $pdo->prepare("SELECT * FROM devices WHERE deviceid = ?");
            $stmt->execute([$deviceId]);
            $device = $stmt->fetch();

            if (!$device) {
                echo '<div class="alert alert-danger">Device not found</div>';
                exit;
            }

            // Get device statistics
            $stmt = $pdo->prepare("
                SELECT 
                    COUNT(DISTINCT p.id) as total_ports,
                    COUNT(DISTINCT s.id) as total_records,
                    MAX(s.time) as last_update,
                    SUM(CASE WHEN s.interfaceerror > 0 THEN 1 ELSE 0 END) as error_records,
                    AVG(CAST(s.interfaceerror AS UNSIGNED)) as avg_errors
                FROM devices d
                LEFT JOIN ports p ON d.deviceid = p.deviceid
                LEFT JOIN statistics s ON p.id = s.portid
                WHERE d.deviceid = ?
            ");
            $stmt->execute([$deviceId]);
            $deviceStats = $stmt->fetch();
        } catch (PDOException $e) {
            echo '<div class="alert alert-danger">Database error: ' . htmlspecialchars($e->getMessage()) . '</div>';
            exit;
        }
        ?>

        <!-- Device Header -->
        <div class="card">
            <div class="device-header">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h1 class="mb-2">
                            <i class="fas fa-server me-3"></i>
                            <?php echo htmlspecialchars($device['device'] ?? 'Device ' . $device['deviceid']); ?>
                        </h1>
                        <p class="mb-1"><i class="fas fa-network-wired me-2"></i>IP: <?php echo htmlspecialchars($device['ipaddress']); ?></p>
                        <p class="mb-0"><i class="fas fa-tag me-2"></i>Type: <?php echo htmlspecialchars($device['type'] ?? 'Unknown'); ?></p>
                    </div>
                    <div class="col-md-4 text-end">
                        <div class="row text-center">
                            <div class="col-6">
                                <div class="metric-value text-white"><?php echo $deviceStats['total_ports']; ?></div>
                                <div class="metric-label text-white-50">Total Ports</div>
                            </div>
                            <div class="col-6">
                                <div class="metric-value text-white"><?php echo number_format($deviceStats['avg_errors'], 2); ?></div>
                                <div class="metric-label text-white-50">Avg Errors</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Device Statistics -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <i class="fas fa-ethernet text-primary mb-3" style="font-size: 2rem;"></i>
                        <div class="metric-value"><?php echo $deviceStats['total_ports']; ?></div>
                        <div class="metric-label">Total Ports</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <i class="fas fa-chart-bar text-info mb-3" style="font-size: 2rem;"></i>
                        <div class="metric-value"><?php echo number_format($deviceStats['total_records']); ?></div>
                        <div class="metric-label">Total Records</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <i class="fas fa-exclamation-triangle text-warning mb-3" style="font-size: 2rem;"></i>
                        <div class="metric-value"><?php echo $deviceStats['error_records']; ?></div>
                        <div class="metric-label">Error Records</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <i class="fas fa-clock text-success mb-3" style="font-size: 2rem;"></i>
                        <div class="metric-value" style="font-size: 1.2rem;">
                            <?php echo $deviceStats['last_update'] ? date('H:i', strtotime($deviceStats['last_update'])) : 'Never'; ?>
                        </div>
                        <div class="metric-label">Last Update</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts -->
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0"><i class="fas fa-chart-line me-2"></i>Error Trend (24h)</h5>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="errorTrendChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0"><i class="fas fa-chart-pie me-2"></i>Port Status Distribution</h5>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="portStatusChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Port Details -->
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0"><i class="fas fa-list me-2"></i>Port Details</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <?php
                    try {
                        $stmt = $pdo->prepare("
                            SELECT p.*, 
                                   COUNT(s.id) as stat_count,
                                   MAX(s.time) as last_update,
                                   SUM(CASE WHEN s.interfaceerror > 0 THEN 1 ELSE 0 END) as error_count,
                                   AVG(CAST(s.interfaceerror AS UNSIGNED)) as avg_errors,
                                   MAX(CAST(s.ifhighspeed AS UNSIGNED)) as max_speed,
                                   s2.interfaceerror as current_errors,
                                   s2.ifhighspeed as current_speed
                            FROM ports p 
                            LEFT JOIN statistics s ON p.id = s.portid 
                            LEFT JOIN statistics s2 ON p.id = s2.portid AND s2.time = (
                                SELECT MAX(time) FROM statistics WHERE portid = p.id
                            )
                            WHERE p.deviceid = ? 
                            GROUP BY p.id
                            ORDER BY p.interfacename
                        ");
                        $stmt->execute([$deviceId]);

                        while ($port = $stmt->fetch()) {
                            $cardClass = 'port-card';
                            $statusClass = 'bg-success';
                            $statusText = 'OK';

                            if ($port['current_errors'] > 0) {
                                $cardClass .= ' error';
                                $statusClass = 'bg-danger';
                                $statusText = 'Errors';
                            } elseif (!$port['last_update']) {
                                $cardClass .= ' warning';
                                $statusClass = 'bg-warning';
                                $statusText = 'No Data';
                            }

                            echo '<div class="col-md-6 col-lg-4 mb-3">';
                            echo '<div class="card ' . $cardClass . '">';
                            echo '<div class="card-body">';
                            echo '<div class="d-flex justify-content-between align-items-start mb-3">';
                            echo '<h6 class="card-title mb-0">' . htmlspecialchars($port['interfacename']) . '</h6>';
                            echo '<span class="badge ' . $statusClass . ' status-badge">' . $statusText . '</span>';
                            echo '</div>';

                            echo '<div class="row text-center">';
                            echo '<div class="col-6">';
                            echo '<div class="metric-value" style="font-size: 1.5rem;">' . ($port['current_errors'] ?? 0) . '</div>';
                            echo '<div class="metric-label">Current Errors</div>';
                            echo '</div>';
                            echo '<div class="col-6">';
                            echo '<div class="metric-value" style="font-size: 1.5rem;">' . ($port['current_speed'] ?? 0) . '</div>';
                            echo '<div class="metric-label">Speed (Mbps)</div>';
                            echo '</div>';
                            echo '</div>';

                            echo '<hr>';
                            echo '<small class="text-muted">';
                            echo '<i class="fas fa-chart-bar me-1"></i>' . $port['stat_count'] . ' records<br>';
                            echo '<i class="fas fa-clock me-1"></i>Last: ' . ($port['last_update'] ? date('Y-m-d H:i', strtotime($port['last_update'])) : 'Never');
                            echo '</small>';

                            echo '</div>';
                            echo '</div>';
                            echo '</div>';
                        }
                    } catch (PDOException $e) {
                        echo '<div class="col-12"><div class="alert alert-danger">Error loading ports: ' . htmlspecialchars($e->getMessage()) . '</div></div>';
                    }
                    ?>
                </div>
            </div>
        </div>

        <!-- Recent Statistics Table -->
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0"><i class="fas fa-history me-2"></i>Recent Statistics</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-dark">
                            <tr>
                                <th>Time</th>
                                <th>Interface</th>
                                <th>Errors</th>
                                <th>Speed (Mbps)</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            try {
                                $stmt = $pdo->prepare("
                                    SELECT s.*, p.interfacename
                                    FROM statistics s
                                    JOIN ports p ON s.portid = p.id
                                    WHERE p.deviceid = ?
                                    ORDER BY s.time DESC
                                    LIMIT 20
                                ");
                                $stmt->execute([$deviceId]);

                                while ($stat = $stmt->fetch()) {
                                    $statusClass = $stat['interfaceerror'] > 0 ? 'bg-danger' : 'bg-success';
                                    $statusText = $stat['interfaceerror'] > 0 ? 'Error' : 'OK';

                                    echo '<tr>';
                                    echo '<td>' . date('Y-m-d H:i:s', strtotime($stat['time'])) . '</td>';
                                    echo '<td>' . htmlspecialchars($stat['interfacename']) . '</td>';
                                    echo '<td class="' . ($stat['interfaceerror'] > 0 ? 'text-danger' : '') . '">';
                                    echo '<strong>' . $stat['interfaceerror'] . '</strong></td>';
                                    echo '<td>' . $stat['ifhighspeed'] . '</td>';
                                    echo '<td><span class="badge ' . $statusClass . '">' . $statusText . '</span></td>';
                                    echo '</tr>';
                                }
                            } catch (PDOException $e) {
                                echo '<tr><td colspan="5" class="text-center text-danger">Error loading statistics: ' . htmlspecialchars($e->getMessage()) . '</td></tr>';
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Chart configurations
        const chartOptions = {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        };

        // Load chart data via API
        async function loadChartData() {
            try {
                const deviceId = <?php echo json_encode($deviceId); ?>;

                // Error trend chart
                const errorResponse = await fetch(`api.php?action=error_trends&device_id=${deviceId}&hours=24`);
                const errorData = await errorResponse.json();

                if (errorData.success) {
                    const labels = errorData.data.map(item => new Date(item.hour).toLocaleTimeString([], {
                        hour: '2-digit',
                        minute: '2-digit'
                    }));
                    const errors = errorData.data.map(item => item.error_records);

                    const errorTrendCtx = document.getElementById('errorTrendChart').getContext('2d');
                    new Chart(errorTrendCtx, {
                        type: 'line',
                        data: {
                            labels: labels,
                            datasets: [{
                                label: 'Error Records',
                                data: errors,
                                borderColor: '#e74c3c',
                                backgroundColor: 'rgba(231, 76, 60, 0.1)',
                                tension: 0.4,
                                fill: true
                            }]
                        },
                        options: {
                            ...chartOptions,
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    ticks: {
                                        stepSize: 1
                                    }
                                }
                            }
                        }
                    });
                }

                // Port status chart
                const portsResponse = await fetch(`api.php?action=port_statistics&device_id=${deviceId}`);
                const portsData = await portsResponse.json();

                if (portsData.success) {
                    const portsWithErrors = portsData.data.filter(port => port.error_count > 0).length;
                    const portsWithoutErrors = portsData.data.length - portsWithErrors;

                    const portStatusCtx = document.getElementById('portStatusChart').getContext('2d');
                    new Chart(portStatusCtx, {
                        type: 'doughnut',
                        data: {
                            labels: ['Healthy Ports', 'Ports with Errors', 'No Data'],
                            datasets: [{
                                data: [portsWithoutErrors, portsWithErrors, 0],
                                backgroundColor: ['#27ae60', '#e74c3c', '#95a5a6'],
                                borderWidth: 0
                            }]
                        },
                        options: chartOptions
                    });
                }

            } catch (error) {
                console.error('Error loading chart data:', error);
            }
        }

        // Load charts when page is ready
        document.addEventListener('DOMContentLoaded', loadChartData);
    </script>
</body>

</html>