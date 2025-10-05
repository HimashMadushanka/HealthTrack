<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$uid = $_SESSION['user_id'];




$todayReminders = $conn->query("
    SELECT * FROM reminders 
    WHERE user_id='$uid' 
    ORDER BY reminder_time ASC
");


$conn->query("UPDATE goals g
SET g.current_value = (
    SELECT weight FROM health_records h
    WHERE h.user_id = g.user_id AND g.type='weight'
    ORDER BY h.date DESC LIMIT 1
)
WHERE g.user_id='$uid' AND g.type='weight'");

$conn->query("UPDATE goals g
SET g.current_value = (
    SELECT SUM(steps) FROM health_records h
    WHERE h.user_id = g.user_id AND g.type='steps' AND DATE(h.date) = CURDATE()
)
WHERE g.user_id='$uid' AND g.type='steps'");

$conn->query("UPDATE goals g
SET g.current_value = (
    SELECT SUM(calories) FROM health_records h
    WHERE h.user_id = g.user_id AND g.type='calories' AND DATE(h.date) = CURDATE()
)
WHERE g.user_id='$uid' AND g.type='calories'");


// Get user info
$userQuery = $conn->query("SELECT name FROM users WHERE id='$uid'");
$user = $userQuery->fetch_assoc();

// Get statistics
$statsQuery = $conn->query("SELECT COUNT(*) as total_records FROM health_records WHERE user_id='$uid'");
$stats = $statsQuery->fetch_assoc();

// Get latest record
$latestQuery = $conn->query("SELECT * FROM health_records WHERE user_id='$uid' ORDER BY date DESC LIMIT 1");
$latestRecord = $latestQuery->fetch_assoc();

// Prepare data for charts
$dates = [];
$weights = [];
$steps = [];
$calories = [];

// Fetch all records in ascending order for chart display
$chartQuery = $conn->query("SELECT * FROM health_records WHERE user_id='$uid' ORDER BY date ASC");
while ($row = $chartQuery->fetch_assoc()) {
    $dates[] = $row['date'];
    $weights[] = (float)$row['weight'];
    $steps[] = (int)$row['steps'];
    $calories[] = (int)$row['calories'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HealthTrack Dashboard</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f7fa;
            min-height: 100vh;
        }



        .navbar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .navbar h1 { font-size: 24px; }

        .navbar .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .logout-btn {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            border: 2px solid white;
            padding: 8px 20px;
            border-radius: 10px;
            cursor: pointer;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s;
        }

        .logout-btn:hover {
            background: white;
            color: #667eea;
        }

        .container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 20px;
        }

        .welcome-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 50px 40px;
            border-radius: 10px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
            text-align: center;
        }

        .welcome-section h2 { font-size: 36px; margin-bottom: 10px; }
        .welcome-section p { font-size: 18px; opacity: 0.9; }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            text-align: center;
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
        }

        .stat-icon { font-size: 48px; margin-bottom: 15px; }
        .stat-value { font-size: 32px; font-weight: bold; color: #667eea; }
        .stat-label { color: #666; font-size: 14px; text-transform: uppercase; }

        .action-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .action-card {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            text-align: center;
        }

        .action-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
        }

        .action-icon { font-size: 64px; margin-bottom: 20px; }
        .action-btn {
            display: inline-block;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 12px 30px;
            border-radius: 5px;
            text-decoration: none;
            font-weight: 600;
            transition: 0.2s;
        }

        .action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }

        .chart-container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            margin-bottom: 30px;
        }

        .chart-wrapper {
            background: linear-gradient(135deg, #f8f9ff 0%, #ffffff 100%);
            padding: 25px;
            border-radius: 12px;
            margin-bottom: 30px;
            border: 1px solid rgba(102, 126, 234, 0.1);
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.08);
        }

        .chart-title {
            font-size: 18px;
            font-weight: 600;
            color: #333;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .chart-icon {
            font-size: 24px;
        }

        .chart-container canvas {
            width: 100%;
            max-height: 350px;
        }

        .latest-record {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        .record-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }

        .record-item {
            padding: 15px;
            background: #f8f9ff;
            border-radius: 8px;
            border-left: 4px solid #667eea;
        }




       /* Reminders Card */
.reminders-card {
    background: white;
    padding: 25px 30px;
    border-radius: 12px;
    box-shadow: 0 2px 15px rgba(0, 0, 0, 0.05);
    margin-bottom: 30px;
}

.reminders-card h3 {
    font-size: 22px;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
    color: #ff9800; /* Use a vibrant color for the clock icon */
}

.reminders-card ul {
    list-style: none;
    padding: 0;
}

.reminders-card li {
    display: flex;
    justify-content: space-between;
    background: #f8f9ff;
    padding: 12px 15px;
    margin-bottom: 12px;
    border-left: 5px solid #ff9800; /* Highlight color */
    border-radius: 8px;
    font-size: 16px;
    font-weight: 500;
    color: #333;
    transition: transform 0.2s, box-shadow 0.2s;
}

.reminders-card li:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(255, 152, 0, 0.2);
}

.reminders-card a {
    color: #667eea;
    font-weight: 600;
    text-decoration: none;
}

.reminders-card a:hover {
    text-decoration: underline;
}













        .record-item .label { font-size: 12px; color: #666; text-transform: uppercase; }
        .record-item .value { font-size: 20px; font-weight: bold; color: #333; }

        .no-records { text-align: center; padding: 40px; color: #999; }

        @media (max-width: 768px) {
            .stats-grid, .action-cards { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

<div class="navbar">
    <h1>HealthTrack</h1>
    

    <div class="user-info">
        <span>Welcome, <?php echo htmlspecialchars($user['name']); ?></span>
        <a href="logout.php" class="logout-btn">Logout</a>
       <a href="profile.php" class="logout-btn">Profile</a>


    </div>
</div>

<div class="container">
    <div class="welcome-section">
        <h2>Welcome to Your Health Dashboard</h2>
        <p>Track your health metrics and stay on top of your wellness journey</p>
    </div>

    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon">📊</div>
            <div class="stat-value"><?php echo $stats['total_records']; ?></div>
            <div class="stat-label">Total Records</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">🎯</div>
            <div class="stat-value"><?php echo date('F Y'); ?></div>
            <div class="stat-label">Current Month</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">💪</div>
            <div class="stat-value">Active</div>
            <div class="stat-label">Status</div>
        </div>
    </div>

    <div class="action-cards">
        <div class="action-card">
            <div class="action-icon">➕</div>
            <h3>Add Health Record</h3>
            <p>Log your daily health metrics including weight, calories, steps, and blood pressure</p><br/>
            <a href="add_record.php" class="action-btn">Add Record</a>
        </div>
        <div class="action-card">
            <div class="action-icon">📋</div>
            <h3>View Records</h3>
            <p>Browse through your complete health history and track your progress over time</p><br/>
            <a href="view_records.php" class="action-btn">View All Records</a><br/>
        <div>
            <a href="export_csv.php" class="action-btn" style="background:#4CAF50; ">🧾 Export CSV</a>
            <a href="export_pdf.php" class="action-btn" style="background:#E53935;">📄 Export PDF</a>
         </div>
        </div>
    </div>

        <div class="action-card">
            <div class="action-icon">🧮</div>
            <h3>BMI Calculater </h3>
            <p>Calculate and record your Body Mass Index by bmi Calculator</p>
            <br/>
             <a href="bmi.php" class="action-btn"> Go to Calculate </a>
</div>


<div class="reminders-card">
    <h3>⏰ Today's Reminders</h3>
    <?php if($todayReminders->num_rows > 0): ?>
        <ul>
        <?php while($r = $todayReminders->fetch_assoc()): ?>
            <li>
                <span><?php echo htmlspecialchars($r['reminder_text']); ?></span>
                <span><?php echo $r['reminder_time']; ?></span>
            </li>
        <?php endwhile; ?>
        </ul>
    <?php else: ?>
        <p>No reminders for today. Add one <a href="reminders.php">here</a>.</p>
    <?php endif; ?>
</div>

<div class="action-card">
    <div class="action-icon">🎯</div>
    <h3>Your Goals</h3>
    <?php
    $goals = $conn->query("SELECT * FROM goals WHERE user_id='$uid'");
    if($goals->num_rows > 0){
        while($g = $goals->fetch_assoc()){
            $progress = $g['current_value'] / $g['target_value'] * 100;
            if($progress > 100) $progress = 100;
            echo "<p>{$g['type']}: {$g['current_value']} / {$g['target_value']}</p>";
            echo "<div class='progress-bar'><div class='progress-fill' style='width:{$progress}%; background:#42a5f5;'>{$progress}%</div></div>";
        }
    } else {
        echo "<p>No goals set. <a href='goals.php'>Set Now</a></p>";
    }
    ?>
</div>


    </div>

    <!-- 📈 Health Progress Graphs -->
    <div class="chart-container">
        <h3 style="margin-bottom:30px; color:#333; font-size: 24px;">📊 Health Progress Overview</h3>
        
        <div class="chart-wrapper">
            <div class="chart-title">
                <span class="chart-icon">⚖️</span>
                <span>Weight Tracking</span>
            </div>
            <canvas id="weightChart"></canvas>
        </div>

        <div class="chart-wrapper">
            <div class="chart-title">
                <span class="chart-icon">🚶</span>
                <span>Daily Steps</span>
            </div>
            <canvas id="stepsChart"></canvas>
        </div>

        <div class="chart-wrapper">
            <div class="chart-title">
                <span class="chart-icon">🔥</span>
                <span>Calorie Intake</span>
            </div>
            <canvas id="caloriesChart"></canvas>
        </div>
    </div>

    <?php if ($latestRecord): ?>
        <div class="latest-record">
            <h3>📈 Latest Health Record</h3>
            <div class="record-grid">
                <div class="record-item">
                    <div class="label">Date</div>
                    <div class="value"><?php echo htmlspecialchars($latestRecord['date']); ?></div>
                </div>
                <div class="record-item">
                    <div class="label">Weight</div>
                    <div class="value"><?php echo htmlspecialchars($latestRecord['weight']); ?> kg</div>
                </div>
                <div class="record-item">
                    <div class="label">Calories</div>
                    <div class="value"><?php echo htmlspecialchars($latestRecord['calories']); ?> kcal</div>
                </div>
                <div class="record-item">
                    <div class="label">Steps</div>
                    <div class="value"><?php echo number_format($latestRecord['steps']); ?></div>
                </div>
                <div class="record-item">
                    <div class="label">Blood Pressure</div>
                    <div class="value"><?php echo htmlspecialchars($latestRecord['bp']); ?></div>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="latest-record">
            <div class="no-records">
                <div style="font-size: 64px; margin-bottom: 20px;">📊</div>
                <h3>No Records Yet</h3>
                <p>Start your health journey by adding your first record!</p>
                <a href="add_record.php" class="action-btn">Add Your First Record</a>
            </div>
        </div>
    <?php endif; ?>
</div>


<script>
const labels = <?php echo json_encode($dates); ?>;

// Enhanced chart options with gradients
const createGradient = (ctx, color1, color2) => {
    const gradient = ctx.createLinearGradient(0, 0, 0, 400);
    gradient.addColorStop(0, color1);
    gradient.addColorStop(1, color2);
    return gradient;
};

// Weight Chart with Gradient
const weightCtx = document.getElementById('weightChart').getContext('2d');
const weightGradient = createGradient(weightCtx, 'rgba(102, 126, 234, 0.4)', 'rgba(102, 126, 234, 0.01)');

new Chart(weightCtx, {
    type: 'line',
    data: {
        labels: labels,
        datasets: [{
            label: 'Weight (kg)',
            data: <?php echo json_encode($weights); ?>,
            borderColor: '#667eea',
            backgroundColor: weightGradient,
            borderWidth: 4,
            tension: 0.4,
            fill: true,
            pointRadius: 6,
            pointHoverRadius: 9,
            pointBackgroundColor: '#667eea',
            pointBorderColor: '#fff',
            pointBorderWidth: 3,
            pointHoverBackgroundColor: '#fff',
            pointHoverBorderColor: '#667eea',
            pointHoverBorderWidth: 4,
            pointShadowOffsetX: 3,
            pointShadowOffsetY: 3,
            pointShadowBlur: 10,
            pointShadowColor: 'rgba(102, 126, 234, 0.5)'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        interaction: {
            mode: 'index',
            intersect: false
        },
        plugins: {
            legend: {
                display: true,
                position: 'top',
                labels: {
                    font: { size: 14, weight: 'bold' },
                    padding: 20,
                    usePointStyle: true,
                    pointStyle: 'circle'
                }
            },
            tooltip: {
                backgroundColor: 'rgba(102, 126, 234, 0.95)',
                padding: 16,
                titleFont: { size: 15, weight: 'bold' },
                bodyFont: { size: 14 },
                borderColor: '#667eea',
                borderWidth: 2,
                cornerRadius: 8,
                displayColors: true,
                callbacks: {
                    label: function(context) {
                        return ' ' + context.parsed.y + ' kg';
                    }
                }
            }
        },
        scales: {
            y: {
                beginAtZero: false,
                grid: {
                    color: 'rgba(102, 126, 234, 0.08)',
                    drawBorder: false
                },
                ticks: {
                    font: { size: 13, weight: '500' },
                    color: '#667eea',
                    padding: 10,
                    callback: function(value) {
                        return value + ' kg';
                    }
                }
            },
            x: {
                grid: {
                    display: false,
                    drawBorder: false
                },
                ticks: {
                    font: { size: 12, weight: '500' },
                    color: '#666',
                    maxRotation: 45,
                    minRotation: 0
                }
            }
        },
        animation: {
            duration: 2000,
            easing: 'easeInOutQuart'
        }
    }
});

// Steps Chart with Gradient Bars
const stepsCtx = document.getElementById('stepsChart').getContext('2d');
const stepsGradient = createGradient(stepsCtx, 'rgba(66, 165, 245, 1)', 'rgba(66, 165, 245, 0.5)');

new Chart(stepsCtx, {
    type: 'bar',
    data: {
        labels: labels,
        datasets: [{
            label: 'Steps Count',
            data: <?php echo json_encode($steps); ?>,
            backgroundColor: stepsGradient,
            borderColor: '#42a5f5',
            borderWidth: 2,
            borderRadius: 10,
            borderSkipped: false,
            hoverBackgroundColor: '#1e88e5',
            hoverBorderColor: '#1565c0',
            hoverBorderWidth: 3
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        interaction: {
            mode: 'index',
            intersect: false
        },
        plugins: {
            legend: {
                display: true,
                position: 'top',
                labels: {
                    font: { size: 14, weight: 'bold' },
                    padding: 20,
                    usePointStyle: true,
                    pointStyle: 'rect'
                }
            },
            tooltip: {
                backgroundColor: 'rgba(66, 165, 245, 0.95)',
                padding: 16,
                titleFont: { size: 15, weight: 'bold' },
                bodyFont: { size: 14 },
                borderColor: '#42a5f5',
                borderWidth: 2,
                cornerRadius: 8,
                displayColors: true,
                callbacks: {
                    label: function(context) {
                        return ' ' + context.parsed.y.toLocaleString() + ' steps';
                    }
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                grid: {
                    color: 'rgba(66, 165, 245, 0.08)',
                    drawBorder: false
                },
                ticks: {
                    font: { size: 13, weight: '500' },
                    color: '#42a5f5',
                    padding: 10,
                    callback: function(value) {
                        return value.toLocaleString();
                    }
                }
            },
            x: {
                grid: {
                    display: false,
                    drawBorder: false
                },
                ticks: {
                    font: { size: 12, weight: '500' },
                    color: '#666',
                    maxRotation: 45,
                    minRotation: 0
                }
            }
        },
        animation: {
            duration: 2000,
            easing: 'easeInOutQuart'
        }
    }
});






const reminders = <?php
$remindersArr = [];
$todayReminders = $conn->query("SELECT * FROM reminders WHERE user_id='$uid'");
while($row = $todayReminders->fetch_assoc()) {
    $remindersArr[] = ['text'=>$row['reminder_text'], 'time'=>$row['reminder_time']];
}
echo json_encode($remindersArr);
?>;

function checkReminders() {
    const now = new Date();
    reminders.forEach(r => {
        const [h, m] = r.time.split(':');
        if(now.getHours() === parseInt(h) && now.getMinutes() === parseInt(m)) {
            if(Notification.permission === "granted") {
                new Notification("HealthTrack Reminder", { body: r.text });
            }
        }
    });
}

// Ask for notification permission
if(Notification.permission !== "granted") {
    Notification.requestPermission();
}

// Check every minute
setInterval(checkReminders, 60000);





// Calories Chart with Gradient
const caloriesCtx = document.getElementById('caloriesChart').getContext('2d');
const caloriesGradient = createGradient(caloriesCtx, 'rgba(255, 152, 0, 0.4)', 'rgba(255, 152, 0, 0.01)');

new Chart(caloriesCtx, {
    type: 'line',
    data: {
        labels: labels,
        datasets: [{
            label: 'Calories (kcal)',
            data: <?php echo json_encode($calories); ?>,
            borderColor: '#ff9800',
            backgroundColor: caloriesGradient,
            borderWidth: 4,
            tension: 0.4,
            fill: true,
            pointRadius: 6,
            pointHoverRadius: 9,
            pointBackgroundColor: '#ff9800',
            pointBorderColor: '#fff',
            pointBorderWidth: 3,
            pointHoverBackgroundColor: '#fff',
            pointHoverBorderColor: '#ff9800',
            pointHoverBorderWidth: 4
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        interaction: {
            mode: 'index',
            intersect: false
        },
        plugins: {
            legend: {
                display: true,
                position: 'top',
                labels: {
                    font: { size: 14, weight: 'bold' },
                    padding: 20,
                    usePointStyle: true,
                    pointStyle: 'circle'
                }
            },
            tooltip: {
                backgroundColor: 'rgba(255, 152, 0, 0.95)',
                padding: 16,
                titleFont: { size: 15, weight: 'bold' },
                bodyFont: { size: 14 },
                borderColor: '#ff9800',
                borderWidth: 2,
                cornerRadius: 8,
                displayColors: true,
                callbacks: {
                    label: function(context) {
                        return ' ' + context.parsed.y.toLocaleString() + ' kcal';
                    }
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                grid: {
                    color: 'rgba(255, 152, 0, 0.08)',
                    drawBorder: false
                },
                ticks: {
                    font: { size: 13, weight: '500' },
                    color: '#ff9800',
                    padding: 10,
                    callback: function(value) {
                        return value.toLocaleString() + ' kcal';
                    }
                }
            },
            x: {
                grid: {
                    display: false,
                    drawBorder: false
                },
                ticks: {
                    font: { size: 12, weight: '500' },
                    color: '#666',
                    maxRotation: 45,
                    minRotation: 0
                }
            }
        },
        animation: {
            duration: 2000,
            easing: 'easeInOutQuart'
        }
    }
});










</script>

</body>
</html>