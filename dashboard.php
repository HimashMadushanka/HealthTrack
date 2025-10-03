<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$uid = $_SESSION['user_id'];

// Get user info
$userQuery = $conn->query("SELECT name FROM users WHERE id='$uid'");
$user = $userQuery->fetch_assoc();

// Get statistics
$statsQuery = $conn->query("SELECT COUNT(*) as total_records FROM health_records WHERE user_id='$uid'");
$stats = $statsQuery->fetch_assoc();

// Get latest record
$latestQuery = $conn->query("SELECT * FROM health_records WHERE user_id='$uid' ORDER BY date DESC LIMIT 1");
$latestRecord = $latestQuery->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HealthTrack Dashboard</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

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

        .navbar h1 {
            font-size: 24px;
        }

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
            border-radius: 5px;
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

        .welcome-section h2 {
            font-size: 36px;
            margin-bottom: 10px;
        }

        .welcome-section p {
            font-size: 18px;
            opacity: 0.9;
        }

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

        .stat-icon {
            font-size: 48px;
            margin-bottom: 15px;
        }

        .stat-value {
            font-size: 32px;
            font-weight: bold;
            color: #667eea;
            margin-bottom: 5px;
        }

        .stat-label {
            color: #666;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

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
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .action-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
        }

        .action-icon {
            font-size: 64px;
            margin-bottom: 20px;
        }

        .action-card h3 {
            color: #333;
            font-size: 22px;
            margin-bottom: 10px;
        }

        .action-card p {
            color: #666;
            font-size: 14px;
            margin-bottom: 20px;
            line-height: 1.6;
        }

        .action-btn {
            display: inline-block;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 12px 30px;
            border-radius: 5px;
            text-decoration: none;
            font-weight: 600;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }

        .latest-record {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        .latest-record h3 {
            color: #333;
            font-size: 22px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
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

        .record-item .label {
            font-size: 12px;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 5px;
        }

        .record-item .value {
            font-size: 20px;
            font-weight: bold;
            color: #333;
        }

        .no-records {
            text-align: center;
            padding: 40px;
            color: #999;
        }

        @media (max-width: 768px) {
            .navbar {
                flex-direction: column;
                gap: 10px;
            }

            .welcome-section {
                padding: 30px 20px;
            }

            .welcome-section h2 {
                font-size: 28px;
            }

            .stats-grid,
            .action-cards {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="navbar">
        <h1>HealthTrack</h1>
        <div class="user-info">
            <span>Welcome, <?php echo htmlspecialchars($user['name']); ?></span>
            <a href="logout.php" class="logout-btn">Logout</a>
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
                <p>Log your daily health metrics including weight, calories, steps, and blood pressure</p>
                <a href="add_record.php" class="action-btn">Add Record</a>
            </div>

            <div class="action-card">
                <div class="action-icon">📋</div>
                <h3>View Records</h3>
                <p>Browse through your complete health history and track your progress over time</p>
                <a href="view_records.php" class="action-btn">View All Records</a>
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
                <br>
                <a href="add_record.php" class="action-btn">Add Your First Record</a>
            </div>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>