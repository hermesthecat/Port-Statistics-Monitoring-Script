<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Scheduler Logs - Port Statistics Monitoring</title>
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

        .log-row.error {
            background-color: rgba(231, 76, 60, 0.1);
        }

        .log-row.warning {
            background-color: rgba(243, 156, 18, 0.1);
        }

        .chart-container {
            position: relative;
            height: 300px;
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

        try {
            $pdo = getDatabaseConnection();

            // Check if collection_logs table exists
            $tableExists = $pdo->query("SHOW TABLES LIKE 'collection_logs'")->fetch();

            if (!$tableExists) {
                echo '<div class="alert alert-warning">';
                echo '<h4><i class="fas fa-exclamation-triangle me-2"></i>Scheduler Table Not Found</h4>';
                echo '<p>The collection_logs table does not exist. Please run the following SQL to create it:</p>';
                echo '<pre>mysql -u username -p database_name < db_scheduler.sql</pre>';
                echo '</div>';
                exit;
            }

            // Get summary statistics
            $totalRuns = $pdo->query("SELECT COUNT(*) FROM collection_logs")->fetchColumn();
            $totalDevicesProcessed = $pdo->query("SELECT SUM(devices_processed) FROM collection_logs")->fetchColumn();
            $totalStatisticsCollected = $pdo->query("SELECT SUM(statistics_collected) FROM collection_logs")->fetchColumn();
            $totalErrors = $pdo->query("SELECT SUM(errors) FROM collection_logs")->fetchColumn();
            $avgDuration = $pdo->query("SELECT AVG(duration) FROM collection_logs")->fetchColumn();
            $lastRun = $pdo->query("SELECT MAX(start_time) FROM collection_logs")->fetchColumn();
        } catch (PDOException $e) {
            echo '<div class="alert alert-danger">Database error: ' . htmlspecialchars($e->getMessage()) . '</div>';
            exit;
        }
        ?>

        <!-- Page Header -->
        <div class="row mb-4">
            <div class="col-12">
                <h1><i class="fas fa-clock me-3"></i>Scheduler Logs & Statistics</h1>
                <p class="text-muted">Monitor automated SNMP data collection performance and history</p>
            </div>
        </div>

        <!-- Summary Statistics -->
        <div class="row mb-4">
            <div class="col-md-2">
                <div class="card stats-card bg-primary text-white">
                    <div class="icon">
                        <i class="fas fa-play-circle"></i>
                    </div>
                    <div class="number"><?php echo $totalRuns ?: 0; ?></div>
                    <div>Total Runs</div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card stats-card bg-info text-white">
                    <div class="icon">
                        <i class="fas fa-server"></i>
                    </div>
                    <div class="number"><?php echo $totalDevicesProcessed ?: 0; ?></div>
                    <div>Devices Processed</div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card stats-card bg-success text-white">
                    <div class="icon">
                        <i class="fas fa-chart-bar"></i>
                    </div>
                    <div class="number"><?php echo number_format($totalStatisticsCollected ?: 0); ?></div>
                    <div>Statistics Collected</div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card stats-card bg-warning text-white">
                    <div class="icon">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <div class="number"><?php echo $totalErrors ?: 0; ?></div>
                    <div>Total Errors</div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card stats-card bg-secondary text-white">
                    <div class="icon">
                        <i class="fas fa-stopwatch"></i>
                    </div>
                    <div class="number"><?php echo round($avgDuration ?: 0); ?>s</div>
                    <div>Avg Duration</div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card stats-card bg-dark text-white">
                    <div class="icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="number" style="font-size: 1.2rem;">
                        <?php echo $lastRun ? date('H:i', strtotime($lastRun)) : 'Never'; ?>
                    </div>
                    <div>Last Run</div>
                </div>
            </div>
        </div>

        <!-- Charts -->
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0"><i class="fas fa-chart-line me-2"></i>Collection Trends (Last 7 Days)</h5>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="collectionTrendChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0"><i class="fas fa-chart-pie me-2"></i>Success vs Error Rate</h5>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="successRateChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Collection Runs -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0"><i class="fas fa-list me-2"></i>Recent Collection Runs</h5>
                <button class="btn btn-primary btn-sm" onclick="location.reload()">
                    <i class="fas fa-sync-alt me-1"></i>Refresh
                </button>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-dark">
                            <tr>
                                <th>Start Time</th>
                                <th>Duration</th>
                                <th>Devices</th>
                                <th>Ports Updated</th>
                                <th>Statistics</th>
                                <th>Errors</th>
                                <th>Options</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            try {
                                $stmt = $pdo->query("
                                    SELECT * FROM collection_logs 
                                    ORDER BY start_time DESC 
                                    LIMIT 50
                                ");

                                while ($log = $stmt->fetch()) {
                                    $statusClass = 'success';
                                    $statusText = 'Success';
                                    $rowClass = '';

                                    if ($log['errors'] > 0) {
                                        $statusClass = 'danger';
                                        $statusText = 'Errors';
                                        $rowClass = 'log-row error';
                                    } elseif ($log['devices_processed'] == 0) {
                                        $statusClass = 'warning';
                                        $statusText = 'No Data';
                                        $rowClass = 'log-row warning';
                                    }

                                    $options = json_decode($log['options'], true);
                                    $optionsText = '';
                                    if ($options) {
                                        $optionStrings = [];
                                        foreach ($options as $key => $value) {
                                            if ($value === true) {
                                                $optionStrings[] = "--$key";
                                            } elseif ($value !== false && $value !== null) {
                                                $optionStrings[] = "--$key=$value";
                                            }
                                        }
                                        $optionsText = implode(' ', $optionStrings);
                                    }

                                    echo "<tr class='$rowClass'>";
                                    echo "<td>" . date('Y-m-d H:i:s', strtotime($log['start_time'])) . "</td>";
                                    echo "<td>" . $log['duration'] . "s</td>";
                                    echo "<td>" . $log['devices_processed'] . "</td>";
                                    echo "<td>" . $log['ports_updated'] . "</td>";
                                    echo "<td>" . number_format($log['statistics_collected']) . "</td>";
                                    echo "<td class='" . ($log['errors'] > 0 ? 'text-danger' : '') . "'>" . $log['errors'] . "</td>";
                                    echo "<td><small>" . htmlspecialchars($optionsText ?: 'default') . "</small></td>";
                                    echo "<td><span class='badge bg-$statusClass'>$statusText</span></td>";
                                    echo "</tr>";
                                }
                            } catch (PDOException $e) {
                                echo "<tr><td colspan='8' class='text-center text-danger'>Error loading logs: " . htmlspecialchars($e->getMessage()) . "</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Daily Statistics -->
        <div class="card mt-4">
            <div class="card-header">
                <h5 class="card-title mb-0"><i class="fas fa-calendar me-2"></i>Daily Collection Statistics</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead class="table-dark">
                            <tr>
                                <th>Date</th>
                                <th>Total Runs</th>
                                <th>Devices Processed</th>
                                <th>Statistics Collected</th>
                                <th>Total Errors</th>
                                <th>Avg Duration</th>
                                <th>Success Rate</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            try {
                                $stmt = $pdo->query("
                                    SELECT 
                                        DATE(start_time) as collection_date,
                                        COUNT(*) as total_runs,
                                        SUM(devices_processed) as total_devices_processed,
                                        SUM(statistics_collected) as total_statistics_collected,
                                        SUM(errors) as total_errors,
                                        ROUND(AVG(duration), 1) as avg_duration,
                                        ROUND((SUM(CASE WHEN errors = 0 THEN 1 ELSE 0 END) / COUNT(*)) * 100, 1) as success_rate
                                    FROM collection_logs 
                                    WHERE start_time >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                                    GROUP BY DATE(start_time)
                                    ORDER BY collection_date DESC
                                ");

                                while ($stat = $stmt->fetch()) {
                                    $successRate = $stat['success_rate'];
                                    $rateClass = $successRate >= 95 ? 'success' : ($successRate >= 80 ? 'warning' : 'danger');

                                    echo "<tr>";
                                    echo "<td>" . $stat['collection_date'] . "</td>";
                                    echo "<td>" . $stat['total_runs'] . "</td>";
                                    echo "<td>" . number_format($stat['total_devices_processed']) . "</td>";
                                    echo "<td>" . number_format($stat['total_statistics_collected']) . "</td>";
                                    echo "<td class='" . ($stat['total_errors'] > 0 ? 'text-danger' : '') . "'>" . $stat['total_errors'] . "</td>";
                                    echo "<td>" . $stat['avg_duration'] . "s</td>";
                                    echo "<td><span class='badge bg-$rateClass'>" . $successRate . "%</span></td>";
                                    echo "</tr>";
                                }
                            } catch (PDOException $e) {
                                echo "<tr><td colspan='7' class='text-center text-danger'>Error loading daily statistics: " . htmlspecialchars($e->getMessage()) . "</td></tr>";
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
        // Load chart data
        async function loadCharts() {
            try {
                // Collection trend chart
                const response = await fetch('api.php?action=scheduler_trends&days=7');
                const data = await response.json();

                if (data.success) {
                    renderCollectionTrendChart(data.data);
                }

                // Success rate chart
                renderSuccessRateChart();

            } catch (error) {
                console.error('Error loading chart data:', error);
            }
        }

        function renderCollectionTrendChart(trendData) {
            const ctx = document.getElementById('collectionTrendChart').getContext('2d');

            // Sample data if no API data available
            const defaultData = [{
                    date: '2024-01-01',
                    runs: 48,
                    statistics: 1200
                },
                {
                    date: '2024-01-02',
                    runs: 96,
                    statistics: 2400
                },
                {
                    date: '2024-01-03',
                    runs: 72,
                    statistics: 1800
                },
                {
                    date: '2024-01-04',
                    runs: 48,
                    statistics: 1200
                },
                {
                    date: '2024-01-05',
                    runs: 96,
                    statistics: 2400
                },
                {
                    date: '2024-01-06',
                    runs: 84,
                    statistics: 2100
                },
                {
                    date: '2024-01-07',
                    runs: 60,
                    statistics: 1500
                }
            ];

            const chartData = trendData && trendData.length > 0 ? trendData : defaultData;

            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: chartData.map(item => new Date(item.date).toLocaleDateString()),
                    datasets: [{
                        label: 'Collection Runs',
                        data: chartData.map(item => item.runs || item.total_runs),
                        borderColor: '#3498db',
                        backgroundColor: 'rgba(52, 152, 219, 0.1)',
                        tension: 0.4,
                        yAxisID: 'y'
                    }, {
                        label: 'Statistics Collected',
                        data: chartData.map(item => item.statistics || item.total_statistics),
                        borderColor: '#27ae60',
                        backgroundColor: 'rgba(39, 174, 96, 0.1)',
                        tension: 0.4,
                        yAxisID: 'y1'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            type: 'linear',
                            display: true,
                            position: 'left',
                            title: {
                                display: true,
                                text: 'Collection Runs'
                            }
                        },
                        y1: {
                            type: 'linear',
                            display: true,
                            position: 'right',
                            title: {
                                display: true,
                                text: 'Statistics Collected'
                            },
                            grid: {
                                drawOnChartArea: false
                            }
                        }
                    }
                }
            });
        }

        function renderSuccessRateChart() {
            const ctx = document.getElementById('successRateChart').getContext('2d');

            // Calculate success rate from displayed data
            const successfulRuns = <?php echo $totalRuns - $totalErrors; ?>;
            const failedRuns = <?php echo $totalErrors; ?>;

            new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: ['Successful Runs', 'Runs with Errors'],
                    datasets: [{
                        data: [successfulRuns, failedRuns],
                        backgroundColor: ['#27ae60', '#e74c3c'],
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });
        }

        // Load charts when page is ready
        document.addEventListener('DOMContentLoaded', loadCharts);
    </script>
</body>

</html>