<?php

require_once __DIR__ . '/includes/config.php';
 require_once __DIR__ . '/style.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Redirect patients to patient dashboard
if ($_SESSION['user']['role'] === 'patient') {
    header("Location: patient_dashboard.php");
    exit();
}

// Initialize variables
$patient = null;
$health_records = [];
$chart_data = [];
$filtered_records = [];
$error = null;

//Get
$user_id = $_SESSION['user_id'];
$patient_id = $_GET['patient_id'] ?? null;
$period = $_GET['period'] ?? 'weekly';
$vitals_filter = $_GET['vitals'] ?? 'all'; // all, heart_rate, spo2, temperature, blood_pressure
$status_filter = $_GET['status'] ?? 'all'; // all, normal, warning, critical



try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Get patient info if patient_id is provided
    if ($patient_id) {
        $patient_stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND is_admin = false");
        $patient_stmt->execute([$patient_id]);
        $patient = $patient_stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Get all patients for sidebar
    $patients_stmt = $pdo->prepare("
        SELECT u.*, 
               (SELECT COUNT(*) FROM health_records WHERE user_id = u.id) as reading_count,
               (SELECT recorded_at FROM health_records WHERE user_id = u.id ORDER BY recorded_at DESC LIMIT 1) as last_reading
        FROM users u 
        WHERE u.is_admin = false 
        ORDER BY u.full_name
    ");
    $patients_stmt->execute();
    $patients = $patients_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get health records based on selected period and patient
    $target_user_id = $patient_id ?: $user_id;
    
    switch ($period) {
        case 'daily':
            $date_filter = "AND recorded_at >= CURDATE()";
            break;
        case 'weekly':
            $date_filter = "AND recorded_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
            break;
        case 'monthly':
            $date_filter = "AND recorded_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
            break;
        case 'yearly':
            $date_filter = "AND recorded_at >= DATE_SUB(CURDATE(), INTERVAL 1 YEAR)";
            break;
        default:
            $date_filter = "AND recorded_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
    }

    $stmt = $pdo->prepare("
        SELECT * FROM health_records 
        WHERE user_id = ? $date_filter
        ORDER BY recorded_at DESC
    ");
    $stmt->execute([$target_user_id]);
    $health_records = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Apply additional filters
    $filtered_records = $health_records;
    
    // Filter by vitals type
    if ($vitals_filter !== 'all') {
        $filtered_records = array_filter($filtered_records, function($record) use ($vitals_filter) {
            switch ($vitals_filter) {
                case 'heart_rate':
                    return $record['heart_rate'] !== null;
                case 'spo2':
                    return $record['spo2'] !== null;
                case 'temperature':
                    return $record['body_temperature'] !== null;
                case 'blood_pressure':
                    return $record['blood_pressure'] !== null;
                default:
                    return true;
            }
        });
    }
    
    // Filter by status
    if ($status_filter !== 'all') {
        $filtered_records = array_filter($filtered_records, function($record) use ($status_filter) {
            $warnings = [];
            if ($record['heart_rate'] < 60 || $record['heart_rate'] > 100) $warnings[] = 'heart_rate';
            if ($record['spo2'] < 95) $warnings[] = 'spo2';
            if ($record['body_temperature'] < 36.1 || $record['body_temperature'] > 37.2) $warnings[] = 'temperature';
            
            $has_warnings = !empty($warnings);
            $is_critical = ($record['heart_rate'] < 50 || $record['heart_rate'] > 130 || $record['spo2'] < 90 || $record['body_temperature'] < 35 || $record['body_temperature'] > 39);
            
            switch ($status_filter) {
                case 'normal':
                    return !$has_warnings;
                case 'warning':
                    return $has_warnings && !$is_critical;
                case 'critical':
                    return $is_critical;
                default:
                    return true;
            }
        });
    }

    // Get data for charts based on filtered records
    $chart_data = [];
    $grouped_data = [];
    
    foreach ($filtered_records as $record) {
        $date = date('Y-m-d', strtotime($record['recorded_at']));
        if (!isset($grouped_data[$date])) {
            $grouped_data[$date] = [
                'date' => $date,
                'temps' => [],
                'heart_rates' => [],
                'spo2s' => [],
                'count' => 0
            ];
        }
        
        if ($record['body_temperature']) {
            $grouped_data[$date]['temps'][] = $record['body_temperature'];
        }
        if ($record['heart_rate']) {
            $grouped_data[$date]['heart_rates'][] = $record['heart_rate'];
        }
        if ($record['spo2']) {
            $grouped_data[$date]['spo2s'][] = $record['spo2'];
        }
        $grouped_data[$date]['count']++;
    }
    
    foreach ($grouped_data as $date => $data) {
        $chart_data[] = [
            'date' => $date,
            'avg_temp' => !empty($data['temps']) ? round(array_sum($data['temps']) / count($data['temps']), 1) : null,
            'avg_heart_rate' => !empty($data['heart_rates']) ? round(array_sum($data['heart_rates']) / count($data['heart_rates'])) : null,
            'avg_spo2' => !empty($data['spo2s']) ? round(array_sum($data['spo2s']) / count($data['spo2s'])) : null,
            'readings_count' => $data['count']
        ];
    }
    
    // Sort chart data by date
    usort($chart_data, function($a, $b) {
        return strtotime($a['date']) - strtotime($b['date']);
    });

} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}

// Calculate statistics based on filtered records
$stats = [
    'total_readings' => count($filtered_records),
    'avg_heart_rate' => 0,
    'avg_temperature' => 0,
    'avg_spo2' => 0,
    'critical_readings' => 0,
    'warning_readings' => 0,
    'normal_readings' => 0
];

if (count($filtered_records) > 0) {
    $heart_rates = array_column($filtered_records, 'heart_rate');
    $temps = array_column($filtered_records, 'body_temperature');
    $spo2s = array_column($filtered_records, 'spo2');
    
    $stats['avg_heart_rate'] = round(array_sum($heart_rates) / count($heart_rates));
    $stats['avg_temperature'] = round(array_sum($temps) / count($temps), 1);
    $stats['avg_spo2'] = round(array_sum($spo2s) / count($spo2s));
    
    // Count readings by status
    foreach ($filtered_records as $record) {
        $warnings = [];
        if ($record['heart_rate'] < 60 || $record['heart_rate'] > 100) $warnings[] = 'heart_rate';
        if ($record['spo2'] < 95) $warnings[] = 'spo2';
        if ($record['body_temperature'] < 36.1 || $record['body_temperature'] > 37.2) $warnings[] = 'temperature';
        
        $has_warnings = !empty($warnings);
        $is_critical = ($record['heart_rate'] < 50 || $record['heart_rate'] > 130 || $record['spo2'] < 90 || $record['body_temperature'] < 35 || $record['body_temperature'] > 39);
        
        if ($is_critical) {
            $stats['critical_readings']++;
        } elseif ($has_warnings) {
            $stats['warning_readings']++;
        } else {
            $stats['normal_readings']++;
        }
    }
}

$current_user = $_SESSION['user'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Health Statistics - Health Aid</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .stats-container {
            display: grid;
            grid-template-columns: <?php echo $current_user['role'] === 'doctor' ? '350px 1fr' : '1fr'; ?>;
            gap: 30px;
            padding: 30px 0;
            min-height: calc(100vh - 80px);
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <?php include 'navbar.php'; ?>

    <!-- Main Content -->
    <div class="container">
        <div class="stats-container">
            <?php if ($current_user['role'] === 'doctor'): ?>
            <!-- Patients Sidebar -->
            <div class="patients-sidebar">
                <div class="patients-header">
                    <h2 style="color: var(--text-primary);">Patients</h2>
                    <span style="color: var(--text-secondary); font-size: 0.9rem;"><?php echo count($patients); ?> patients</span>
                </div>
                
                <?php if (empty($patients)): ?>
                    <div style="text-align: center; padding: 40px 20px; color: var(--text-secondary);">
                        <i class="fas fa-users" style="font-size: 3rem; margin-bottom: 15px; opacity: 0.5;"></i>
                        <p>No patients assigned</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($patients as $p): ?>
                        <div class="patient-card <?php echo $patient_id == $p['id'] ? 'active' : ''; ?>" 
                             onclick="window.location.href='statistics.php?patient_id=<?php echo $p['id']; ?>&period=<?php echo $period; ?>&vitals=<?php echo $vitals_filter; ?>&status=<?php echo $status_filter; ?>'">
                            <div class="patient-name">
                                <?php echo htmlspecialchars($p['full_name']); ?>
                            </div>
                            <div class="patient-info">
                                <span><?php echo $p['reading_count']; ?> readings</span>
                                <span>
                                    <?php if ($p['last_reading']): ?>
                                        <?php echo date('M j, g:i A', strtotime($p['last_reading'])); ?>
                                    <?php else: ?>
                                        No data
                                    <?php endif; ?>
                                </span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <!-- Statistics Content -->
            <div class="stats-content">
                <?php if ($patient_id && $patient): ?>
                    <div class="content-header">
                        <h1 class="page-title">
                            Statistics: <?php echo htmlspecialchars($patient['full_name']); ?>
                        </h1>
                    </div>

                    <!-- Advanced Filters -->
                    <div class="advanced-filters">
                        <form method="GET" action="statistics.php" class="filter-form">
                            <input type="hidden" name="patient_id" value="<?php echo $patient_id; ?>">
                            
                            <div class="filter-row">
                                <div class="filter-group">
                                    <label class="filter-label">Time Period</label>
                                    <select name="period" class="filter-select">
                                        <option value="daily" <?php echo $period === 'daily' ? 'selected' : ''; ?>>Daily</option>
                                        <option value="weekly" <?php echo $period === 'weekly' ? 'selected' : ''; ?>>Weekly</option>
                                        <option value="monthly" <?php echo $period === 'monthly' ? 'selected' : ''; ?>>Monthly</option>
                                        <option value="yearly" <?php echo $period === 'yearly' ? 'selected' : ''; ?>>Yearly</option>
                                    </select>
                                </div>
                                
                                <div class="filter-group">
                                    <label class="filter-label">Vitals Type</label>
                                    <select name="vitals" class="filter-select">
                                        <option value="all" <?php echo $vitals_filter === 'all' ? 'selected' : ''; ?>>All Vitals</option>
                                        <option value="heart_rate" <?php echo $vitals_filter === 'heart_rate' ? 'selected' : ''; ?>>Heart Rate</option>
                                        <option value="spo2" <?php echo $vitals_filter === 'spo2' ? 'selected' : ''; ?>>SpO2</option>
                                        <option value="temperature" <?php echo $vitals_filter === 'temperature' ? 'selected' : ''; ?>>Temperature</option>
                                        <option value="blood_pressure" <?php echo $vitals_filter === 'blood_pressure' ? 'selected' : ''; ?>>Blood Pressure</option>
                                    </select>
                                </div>
                                
                                <div class="filter-group">
                                    <label class="filter-label">Health Status</label>
                                    <select name="status" class="filter-select">
                                        <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>All Status</option>
                                        <option value="normal" <?php echo $status_filter === 'normal' ? 'selected' : ''; ?>>Normal</option>
                                        <option value="warning" <?php echo $status_filter === 'warning' ? 'selected' : ''; ?>>Warning</option>
                                        <option value="critical" <?php echo $status_filter === 'critical' ? 'selected' : ''; ?>>Critical</option>
                                    </select>
                                </div>
                                
                                <button type="submit" class="apply-filters-btn">
                                    <i class="fas fa-filter"></i> Apply Filters
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Quick Stats Overview -->
                    <div class="stats-overview">
                        <div class="stat-mini-card">
                            <div class="stat-mini-value"><?php echo $stats['total_readings']; ?></div>
                            <div class="stat-mini-label">Total Readings</div>
                        </div>
                        <div class="stat-mini-card">
                            <div class="stat-mini-value"><?php echo $stats['normal_readings']; ?></div>
                            <div class="stat-mini-label">
                                <span class="status-badge status-normal">Normal</span>
                            </div>
                        </div>
                        <div class="stat-mini-card">
                            <div class="stat-mini-value"><?php echo $stats['warning_readings']; ?></div>
                            <div class="stat-mini-label">
                                <span class="status-badge status-warning">Warning</span>
                            </div>
                        </div>
                        <div class="stat-mini-card">
                            <div class="stat-mini-value"><?php echo $stats['critical_readings']; ?></div>
                            <div class="stat-mini-label">
                                <span class="status-badge status-critical">Critical</span>
                            </div>
                        </div>
                    </div>

                    <!-- Statistics Summary -->
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-label">Total Readings</div>
                            <div class="stat-value"><?php echo $stats['total_readings']; ?></div>
                            <div class="stat-label">in <?php echo ucfirst($period); ?> period</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-label">Avg Heart Rate</div>
                            <div class="stat-value"><?php echo $stats['avg_heart_rate']; ?> bpm</div>
                            <div class="stat-label">
                                <?php 
                                if ($stats['avg_heart_rate'] >= 60 && $stats['avg_heart_rate'] <= 100) {
                                    echo '<span class="status-badge status-normal">Normal</span>';
                                } elseif ($stats['avg_heart_rate'] > 100) {
                                    echo '<span class="status-badge status-warning">High</span>';
                                } else {
                                    echo '<span class="status-badge status-critical">Low</span>';
                                }
                                ?>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-label">Avg Temperature</div>
                            <div class="stat-value"><?php echo $stats['avg_temperature']; ?>°C</div>
                            <div class="stat-label">
                                <?php 
                                if ($stats['avg_temperature'] >= 36.1 && $stats['avg_temperature'] <= 37.2) {
                                    echo '<span class="status-badge status-normal">Normal</span>';
                                } elseif ($stats['avg_temperature'] > 37.2) {
                                    echo '<span class="status-badge status-warning">High</span>';
                                } else {
                                    echo '<span class="status-badge status-critical">Low</span>';
                                }
                                ?>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-label">Avg SpO2</div>
                            <div class="stat-value"><?php echo $stats['avg_spo2']; ?>%</div>
                            <div class="stat-label">
                                <?php 
                                if ($stats['avg_spo2'] >= 95) {
                                    echo '<span class="status-badge status-normal">Normal</span>';
                                } else {
                                    echo '<span class="status-badge status-critical">Low</span>';
                                }
                                ?>
                            </div>
                        </div>
                    </div>

                    <!-- Charts -->
                    <div class="charts-container">
                        <div class="chart-card">
                            <h3>Vitals Overview</h3>
                            <div class="chart-container">
                                <canvas id="vitalsChart"></canvas>
                            </div>
                        </div>
                        <div class="chart-card">
                            <h3>Readings per Day</h3>
                            <div class="chart-container">
                                <canvas id="readingsChart"></canvas>
                            </div>
                        </div>
                    </div>

                    <!-- Health History Table -->
                    <div class="history-table">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                            <h3 style="margin: 0; color: #8B4E91;"><i class="fas fa-history"></i> Health History</h3>
                            <div class="last-updated">
                                Last updated: <span id="lastUpdateTime"><?php echo date('Y-m-d H:i:s'); ?></span>
                                <button onclick="refreshData()" class="btn" style="margin-left: 10px;">
                                    <i class="fas fa-sync-alt"></i> Refresh
                                </button>
                            </div>
                        </div>
                        
                        <div style="overflow-x: auto;">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Date & Time</th>
                                        <th>Temperature</th>
                                        <th>Heart Rate</th>
                                        <th>SpO2</th>
                                        <th>Blood Pressure</th>
                                        <th>Source</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($filtered_records as $record): ?>
                                    <tr>
                                        <td><?php echo date('M j, Y H:i', strtotime($record['recorded_at'])); ?></td>
                                        <td><?php echo $record['body_temperature']; ?>°C</td>
                                        <td>
                                            <?php echo $record['heart_rate']; ?> bpm
                                            <?php 
                                            if ($record['heart_rate'] < 60) echo '<span class="stat-critical">⚠</span>';
                                            elseif ($record['heart_rate'] > 100) echo '<span class="stat-warning">⚠</span>';
                                            else echo '<span class="stat-normal">✓</span>';
                                            ?>
                                        </td>
                                        <td>
                                            <?php echo $record['spo2']; ?>%
                                            <?php 
                                            if ($record['spo2'] < 95) echo '<span class="stat-critical">⚠</span>';
                                            else echo '<span class="stat-normal">✓</span>';
                                            ?>
                                        </td>
                                        <td><?php echo $record['blood_pressure'] ?: '--'; ?></td>
                                        <td>
                                            <span style="text-transform: capitalize;"><?php echo $record['source']; ?></span>
                                            <?php if ($record['location_lat']): ?>
                                                <br><small>Location tracked</small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php
                                            $warnings = [];
                                            if ($record['heart_rate'] < 60 || $record['heart_rate'] > 100) $warnings[] = 'Heart rate';
                                            if ($record['spo2'] < 95) $warnings[] = 'SpO2';
                                            if ($record['body_temperature'] < 36.1 || $record['body_temperature'] > 37.2) $warnings[] = 'Temperature';
                                            
                                            if (empty($warnings)) {
                                                echo '<span class="stat-normal">Normal</span>';
                                            } else {
                                                echo '<span class="stat-critical">' . implode(', ', $warnings) . '</span>';
                                            }
                                            ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                    
                                    <?php if (empty($filtered_records)): ?>
                                    <tr>
                                        <td colspan="7" style="text-align: center; padding: 40px; color: var(--text-secondary);">
                                            No health records found for the selected filters.
                                        </td>
                                    </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php else: ?>
                    <div style="text-align: center; padding: 60px 20px; color: var(--text-secondary);">
                        <i class="fas fa-chart-bar" style="font-size: 4rem; margin-bottom: 20px; opacity: 0.5;"></i>
                        <h2>Select a Patient</h2>
                        <p>Choose a patient from the sidebar to view their health statistics and trends.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        // Theme Toggle
        const themeToggle = document.getElementById('themeToggle');
        const currentTheme = localStorage.getItem('theme') || 'light';
        
        document.documentElement.setAttribute('data-theme', currentTheme);
        updateThemeIcon(currentTheme);
        
        themeToggle.addEventListener('click', function() {
            const currentTheme = document.documentElement.getAttribute('data-theme');
            const newTheme = currentTheme === 'light' ? 'dark' : 'light';
            
            document.documentElement.setAttribute('data-theme', newTheme);
            localStorage.setItem('theme', newTheme);
            updateThemeIcon(newTheme);
        });
        
        function updateThemeIcon(theme) {
            const icon = themeToggle.querySelector('i');
            icon.className = theme === 'light' ? 'fas fa-moon' : 'fas fa-sun';
        }

        // Chart data from PHP
        const chartData = <?php echo json_encode($chart_data); ?>;
        
        // Initialize charts
        function initializeCharts() {
            if (chartData.length === 0) {
                // Show message if no chart data
                document.querySelectorAll('.chart-card .chart-container').forEach(container => {
                    container.innerHTML = '<div style="text-align: center; padding: 40px; color: var(--text-secondary);"><i class="fas fa-chart-line" style="font-size: 2rem; margin-bottom: 10px; opacity: 0.5;"></i><p>No data available</p></div>';
                });
                return;
            }

            // Combined Vitals Chart
            new Chart(document.getElementById('vitalsChart'), {
                type: 'line',
                data: {
                    labels: chartData.map(item => new Date(item.date).toLocaleDateString()),
                    datasets: [
                        {
                            label: 'Heart Rate (bpm)',
                            data: chartData.map(item => item.avg_heart_rate),
                            borderColor: '#6A2C70',
                            backgroundColor: 'rgba(106, 44, 112, 0.1)',
                            borderWidth: 2,
                            tension: 0.4,
                            fill: false,
                            yAxisID: 'y'
                        },
                        {
                            label: 'SpO2 (%)',
                            data: chartData.map(item => item.avg_spo2),
                            borderColor: '#3498db',
                            backgroundColor: 'rgba(52, 152, 219, 0.1)',
                            borderWidth: 2,
                            tension: 0.4,
                            fill: false,
                            yAxisID: 'y1'
                        },
                        {
                            label: 'Temperature (°C)',
                            data: chartData.map(item => item.avg_temp),
                            borderColor: '#e74c3c',
                            backgroundColor: 'rgba(231, 76, 60, 0.1)',
                            borderWidth: 2,
                            tension: 0.4,
                            fill: false,
                            yAxisID: 'y2'
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: {
                        mode: 'index',
                        intersect: false,
                    },
                    stacked: false,
                    scales: {
                        y: {
                            type: 'linear',
                            display: true,
                            position: 'left',
                            title: {
                                display: true,
                                text: 'Heart Rate (bpm)'
                            },
                            suggestedMin: 50,
                            suggestedMax: 120
                        },
                        y1: {
                            type: 'linear',
                            display: true,
                            position: 'right',
                            title: {
                                display: true,
                                text: 'SpO2 (%)'
                            },
                            min: 85,
                            max: 100,
                            grid: {
                                drawOnChartArea: false,
                            },
                        },
                        y2: {
                            type: 'linear',
                            display: false,
                            position: 'right',
                            title: {
                                display: true,
                                text: 'Temperature (°C)'
                            },
                            suggestedMin: 35,
                            suggestedMax: 39,
                            grid: {
                                drawOnChartArea: false,
                            },
                        }
                    }
                }
            });

            // Readings Chart
            new Chart(document.getElementById('readingsChart'), {
                type: 'bar',
                data: {
                    labels: chartData.map(item => new Date(item.date).toLocaleDateString()),
                    datasets: [{
                        label: 'Readings per Day',
                        data: chartData.map(item => item.readings_count),
                        backgroundColor: '#27ae60',
                        borderColor: '#219652',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
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

        // Refresh data
        function refreshData() {
            const refreshBtn = event.target;
            refreshBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Refreshing...';
            refreshBtn.disabled = true;

            setTimeout(() => {
                window.location.reload();
            }, 1000);
        }

        // Auto-refresh every 30 seconds
        setInterval(() => {
            document.getElementById('lastUpdateTime').textContent = new Date().toLocaleString();
        }, 30000);

        // Initialize charts when page loads
        document.addEventListener('DOMContentLoaded', initializeCharts);
    </script>
</body>
</html>