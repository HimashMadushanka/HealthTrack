<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$uid = $_SESSION['user_id'];

// Fetch today's reminders
$todayReminders = $conn->query("
    SELECT * FROM reminders 
    WHERE user_id='$uid' 
    ORDER BY reminder_time ASC
");

// Update goals based on latest health records
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

// Get weekly average
$weeklyQuery = $conn->query("SELECT AVG(weight) as avg_weight, AVG(steps) as avg_steps, AVG(calories) as avg_calories 
                             FROM health_records 
                             WHERE user_id='$uid' AND date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)");
$weeklyAvg = $weeklyQuery->fetch_assoc();

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
    <title>HealthTrack Dashboard - Your Health Journey</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        * { 
            margin: 0; 
            padding: 0; 
            box-sizing: border-box; 
        }

        :root {
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --secondary-gradient: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            --success-gradient: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            --warning-gradient: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
            --info-gradient: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%);
            --primary-color: #667eea;
            --secondary-color: #764ba2;
            --text-dark: #2d3748;
            --text-light: #718096;
            --bg-light: #f7fafc;
            --card-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            --card-shadow-hover: 0 8px 30px rgba(102, 126, 234, 0.15);
            --border-radius: 16px;
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            color: var(--text-dark);
            line-height: 1.6;
            overflow-x: hidden;
        }

        /* Animated Background */
        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: 
                radial-gradient(circle at 20% 30%, rgba(102, 126, 234, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 80% 70%, rgba(118, 75, 162, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 50% 50%, rgba(79, 172, 254, 0.05) 0%, transparent 50%);
            z-index: -1;
            pointer-events: none;
            animation: backgroundFloat 20s ease infinite;
        }

        @keyframes backgroundFloat {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.8; }
        }

        /* Navbar */
        .navbar {
            background: var(--primary-gradient);
            color: white;
            padding: 20px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 20px rgba(102, 126, 234, 0.3);
            position: sticky;
            top: 0;
            z-index: 1000;
            backdrop-filter: blur(10px);
        }

        .navbar h1 { 
            font-size: 28px; 
            font-weight: 800;
            letter-spacing: -0.5px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .navbar h1::before {
            content: '💚';
            font-size: 32px;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.15); }
        }

        .navbar .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .navbar .user-info span {
            font-weight: 500;
            font-size: 15px;
            padding: 8px 16px;
            background: rgba(255, 255, 255, 0.15);
            border-radius: 20px;
            backdrop-filter: blur(10px);
        }

        .logout-btn, .profile-btn {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            border: 2px solid rgba(255, 255, 255, 0.8);
            padding: 10px 24px;
            border-radius: 25px;
            cursor: pointer;
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .logout-btn:hover, .profile-btn:hover {
            background: white;
            color: var(--primary-color);
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(255, 255, 255, 0.3);
        }

        /* Container */
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 30px 20px;
        }

        /* Welcome Section */
        .welcome-section {
            background: var(--primary-gradient);
            color: white;
            padding: 60px 50px;
            border-radius: var(--border-radius);
            box-shadow: 0 10px 40px rgba(102, 126, 234, 0.3);
            margin-bottom: 40px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .welcome-section::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -10%;
            width: 300px;
            height: 300px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            animation: float 6s ease-in-out infinite;
        }

        .welcome-section::after {
            content: '';
            position: absolute;
            bottom: -30%;
            left: -5%;
            width: 200px;
            height: 200px;
            background: rgba(255, 255, 255, 0.08);
            border-radius: 50%;
            animation: float 8s ease-in-out infinite reverse;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
        }

        .welcome-section h2 { 
            font-size: 42px; 
            margin-bottom: 15px; 
            font-weight: 800;
            position: relative;
            z-index: 1;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .welcome-section p { 
            font-size: 18px; 
            opacity: 0.95;
            position: relative;
            z-index: 1;
            font-weight: 400;
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
        }

        .stat-card {
            background: white;
            padding: 35px 30px;
            border-radius: var(--border-radius);
            box-shadow: var(--card-shadow);
            text-align: center;
            transition: var(--transition);
            position: relative;
            overflow: hidden;
            border: 1px solid rgba(102, 126, 234, 0.1);
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: var(--primary-gradient);
        }

        .stat-card:hover {
            transform: translateY(-8px);
            box-shadow: var(--card-shadow-hover);
            border-color: var(--primary-color);
        }

        .stat-icon { 
            font-size: 56px; 
            margin-bottom: 20px;
            display: inline-block;
            animation: bounce 2s infinite;
        }

        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }

        .stat-value { 
            font-size: 36px; 
            font-weight: 800; 
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 8px;
        }

        .stat-label { 
            color: var(--text-light); 
            font-size: 13px; 
            text-transform: uppercase; 
            font-weight: 600;
            letter-spacing: 1px;
        }

        /* Action Cards */
        .action-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
        }

        .action-card {
            background: white;
            padding: 40px 35px;
            border-radius: var(--border-radius);
            box-shadow: var(--card-shadow);
            text-align: center;
            transition: var(--transition);
            border: 1px solid rgba(102, 126, 234, 0.1);
            position: relative;
            overflow: hidden;
        }

        .action-card::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: var(--primary-gradient);
            opacity: 0;
            transform: rotate(45deg);
            transition: var(--transition);
        }

        .action-card:hover::before {
            opacity: 0.03;
        }

        .action-card:hover {
            transform: translateY(-8px);
            box-shadow: var(--card-shadow-hover);
            border-color: var(--primary-color);
        }

        .action-icon { 
            font-size: 72px; 
            margin-bottom: 25px;
            display: inline-block;
            filter: drop-shadow(0 4px 8px rgba(102, 126, 234, 0.2));
        }

        .action-card h3 {
            font-size: 22px;
            margin-bottom: 15px;
            color: var(--text-dark);
            font-weight: 700;
        }

        .action-card p {
            color: var(--text-light);
            margin-bottom: 25px;
            line-height: 1.7;
            font-size: 15px;
        }

        .action-btn {
            display: inline-block;
            background: var(--primary-gradient);
            color: white;
            padding: 14px 32px;
            border-radius: 30px;
            text-decoration: none;
            font-weight: 600;
            font-size: 15px;
            transition: var(--transition);
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
            border: none;
            cursor: pointer;
            margin: 5px;
            position: relative;
            overflow: hidden;
        }

        .action-btn::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.3);
            transform: translate(-50%, -50%);
            transition: width 0.6s, height 0.6s;
        }

        .action-btn:hover::before {
            width: 300px;
            height: 300px;
        }

        .action-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
        }

        .action-btn.success {
            background: var(--success-gradient);
            box-shadow: 0 4px 15px rgba(79, 172, 254, 0.3);
        }

        .action-btn.success:hover {
            box-shadow: 0 8px 25px rgba(79, 172, 254, 0.4);
        }

        .action-btn.danger {
            background: var(--secondary-gradient);
            box-shadow: 0 4px 15px rgba(245, 87, 108, 0.3);
        }

        .action-btn.danger:hover {
            box-shadow: 0 8px 25px rgba(245, 87, 108, 0.4);
        }

        /* Reminders Card */
        .reminders-card {
            background: white;
            padding: 35px 40px;
            border-radius: var(--border-radius);
            box-shadow: var(--card-shadow);
            margin-bottom: 40px;
            border: 1px solid rgba(255, 152, 0, 0.1);
            transition: var(--transition);
        }

        .reminders-card:hover {
            box-shadow: var(--card-shadow-hover);
        }

        .reminders-card h3 {
            font-size: 24px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 12px;
            color: var(--text-dark);
            font-weight: 700;
        }

        .reminders-card h3::before {
            content: '⏰';
            font-size: 28px;
        }

        .reminders-card ul {
            list-style: none;
            padding: 0;
        }

        .reminders-card li {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: linear-gradient(135deg, #fff5e6 0%, #ffe8cc 100%);
            padding: 18px 22px;
            margin-bottom: 15px;
            border-left: 5px solid #ff9800;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 500;
            color: var(--text-dark);
            transition: var(--transition);
            box-shadow: 0 2px 8px rgba(255, 152, 0, 0.1);
        }

        .reminders-card li:hover {
            transform: translateX(5px);
            box-shadow: 0 4px 15px rgba(255, 152, 0, 0.2);
        }

        .reminders-card li span:last-child {
            background: #ff9800;
            color: white;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
        }

        .reminders-card a {
            color: var(--primary-color);
            font-weight: 600;
            text-decoration: none;
            transition: var(--transition);
        }

        .reminders-card a:hover {
            color: var(--secondary-color);
            text-decoration: underline;
        }

        /* Goals Progress */
        .progress-bar {
            background: #e8eaf6;
            height: 30px;
            border-radius: 15px;
            overflow: hidden;
            margin: 12px 0 20px;
            box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.1);
            position: relative;
        }

        .progress-fill {
            height: 100%;
            background: var(--success-gradient);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 13px;
            transition: width 1s ease;
            box-shadow: 0 2px 8px rgba(79, 172, 254, 0.3);
            position: relative;
            overflow: hidden;
        }

        .progress-fill::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
            animation: shimmer 2s infinite;
        }

        @keyframes shimmer {
            0% { left: -100%; }
            100% { left: 100%; }
        }

        /* Chart Container */
        .chart-container {
            background: white;
            padding: 40px;
            border-radius: var(--border-radius);
            box-shadow: var(--card-shadow);
            margin-bottom: 40px;
            border: 1px solid rgba(102, 126, 234, 0.1);
        }

        .chart-container h3 {
            margin-bottom: 35px;
            color: var(--text-dark);
            font-size: 28px;
            font-weight: 800;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .chart-wrapper {
            background: linear-gradient(135deg, #f8f9ff 0%, #ffffff 100%);
            padding: 30px;
            border-radius: 16px;
            margin-bottom: 35px;
            border: 1px solid rgba(102, 126, 234, 0.1);
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.08);
            transition: var(--transition);
        }

        .chart-wrapper:hover {
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.12);
            transform: translateY(-2px);
        }

        .chart-title {
            font-size: 20px;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .chart-icon {
            font-size: 28px;
        }

        .chart-container canvas {
            width: 100% !important;
            max-height: 380px !important;
        }

        /* Latest Record */
        .latest-record {
            background: white;
            padding: 40px;
            border-radius: var(--border-radius);
            box-shadow: var(--card-shadow);
            border: 1px solid rgba(102, 126, 234, 0.1);
        }

        .latest-record h3 {
            font-size: 24px;
            margin-bottom: 30px;
            color: var(--text-dark);
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .record-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
        }

        .record-item {
            padding: 25px;
            background: linear-gradient(135deg, #f8f9ff 0%, #ffffff 100%);
            border-radius: 12px;
            border-left: 4px solid var(--primary-color);
            transition: var(--transition);
            box-shadow: 0 2px 8px rgba(102, 126, 234, 0.08);
        }

        .record-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.15);
        }

        .record-item .label { 
            font-size: 12px; 
            color: var(--text-light); 
            text-transform: uppercase; 
            font-weight: 600;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
        }

        .record-item .value { 
            font-size: 24px; 
            font-weight: 800; 
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .no-records { 
            text-align: center; 
            padding: 60px 40px; 
            color: var(--text-light); 
        }

        .no-records h3 {
            font-size: 24px;
            margin: 20px 0;
            color: var(--text-dark);
        }

        .no-records p {
            margin-bottom: 25px;
            font-size: 16px;
        }

        /* Footer */
        .footer {
            text-align: center;
            padding: 30px 20px;
            color: var(--text-light);
            font-size: 14px;
            margin-top: 40px;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .navbar {
                padding: 15px 20px;
                flex-direction: column;
                gap: 15px;
            }

            .navbar h1 {
                font-size: 22px;
            }

            .navbar .user-info {
                flex-wrap: wrap;
                justify-content: center;
            }

            .welcome-section {
                padding: 40px 30px;
            }

            .welcome-section h2 {
                font-size: 32px;
            }

            .stats-grid, 
            .action-cards {
                grid-template-columns: 1fr;
            }

            .container {
                padding: 20px 15px;
            }

            .chart-container,
            .latest-record,
            .reminders-card {
                padding: 25px 20px;
            }

            .action-btn {
                padding: 12px 24px;
                font-size: 14px;
            }
        }

        /* Scrollbar Styling */
        ::-webkit-scrollbar {
            width: 12px;
        }

        ::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }

        ::-webkit-scrollbar-thumb {
            background: var(--primary-gradient);
            border-radius: 10px;
            border: 2px solid #f1f1f1;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: var(--secondary-color);
        }

        /* Loading Animation */
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .fade-in {
            animation: fadeIn 0.6s ease-out;
        }
    </style>
</head>
<body>

<!-- Navigation Bar -->
<div class="navbar">
    <h1>HealthTrack</h1>
    <div class="user-info">
        <span>👋 <?php echo htmlspecialchars($user['name']); ?></span>
        <a href="profile.php" class="profile-btn">👤 Profile</a>
        <a href="logout.php" class="logout-btn">🚪 Logout</a>
    </div>
</div>

<!-- Main Container -->
<div class="container">
    <!-- Welcome Section -->
    <div class="welcome-section fade-in">
        <h2>Welcome to Your Health Dashboard</h2>
        <p>Track your health metrics and stay on top of your wellness journey</p>
    </div>

    <!-- Statistics Grid -->
    <div class="stats-grid">
        <div class="stat-card fade-in">
            <div class="stat-icon">📊</div>
            <div class="stat-value"><?php echo $stats['total_records']; ?></div>
            <div class="stat-label">Total Records</div>
        </div>
        <div class="stat-card fade-in" style="animation-delay: 0.1s;">
            <div class="stat-icon">📅</div>
            <div class="stat-value"><?php echo date('F'); ?></div>
            <div class="stat-label">Current Month</div>
        </div>
        <div class="stat-card fade-in" style="animation-delay: 0.2s;">
            <div class="stat-icon">⚖️</div>
            <div class="stat-value"><?php echo $weeklyAvg['avg_weight'] ? number_format($weeklyAvg['avg_weight'], 1) : 'N/A'; ?></div>
            <div class="stat-label">Avg Weekly Weight (kg)</div>
        </div>
        <div class="stat-card fade-in" style="animation-delay: 0.3s;">
            <div class="stat-icon">💪</div>
            <div class="stat-value">Active</div>
            <div class="stat-label">Health Status</div>
        </div>
    </div>

    <!-- Action Cards -->
    <div class="action-cards">
        <div class="action-card fade-in">
            <div class="action-icon">➕</div>
            <h3>Add Health Record</h3>
            <p>Log your daily health metrics including weight, calories, steps, and blood pressure</p>
            <a href="add_record.php" class="action-btn">Add New Record</a>
        </div>

        <div class="action-card fade-in" style="animation-delay: 0.1s;">
            <div class="action-icon">📋</div>
            <h3>View All Records</h3>
            <p>Browse through your complete health history and track your progress over time</p>
            <a href="view_records.php" class="action-btn">View Records</a>
            <div style="margin-top: 15px;">
                <a href="export_csv.php" class="action-btn success">🧾 Export CSV</a>
                <a href="export_pdf.php" class="action-btn danger">📄 Export PDF</a>
            </div>
        </div>

        <div class="action-card fade-in" style="animation-delay: 0.2s;">
            <div class="action-icon">🧮</div>
            <h3>BMI Calculator</h3>
            <p>Calculate and record your Body Mass Index to track your health metrics effectively</p>
            <a href="bmi.php" class="action-btn">Calculate BMI</a>
        </div>
    </div>

    <!-- Reminders Section -->
    <div class="reminders-card fade-in">
        <h3>Today's Reminders</h3>
        <?php if($todayReminders->num_rows > 0): ?>
            <ul>
            <?php while($r = $todayReminders->fetch_assoc()): ?>
                <li>
                    <span><?php echo htmlspecialchars($r['reminder_text']); ?></span>
                    <span><?php echo date('g:i A', strtotime($r['reminder_time'])); ?></span>
                </li>
            <?php endwhile; ?>
            </ul>
        <?php else: ?>
            <p style="text-align: center; padding: 20px; color: var(--text-light);">
                No reminders for today. <a href="reminders.php">Add your first reminder</a> to stay on track!
            </p>
        <?php endif; ?>
    </div>

    <!-- Goals Section -->
    <div class="action-card fade-in">
        <div class="action-icon">🎯</div>
        <h3>Your Health Goals</h3>
        <?php
        $goals = $conn->query("SELECT * FROM goals WHERE user_id='$uid'");
        if($goals->num_rows > 0){
            while($g = $goals->fetch_assoc()){
                $progress = ($g['target_value'] > 0) ? ($g['current_value'] / $g['target_value'] * 100) : 0;
                if($progress > 100) $progress = 100;
                echo "<p style='text-align: left; font-weight: 600; margin-top: 20px; color: var(--text-dark);'>";
                echo ucfirst(htmlspecialchars($g['type'])) . ": " . number_format($g['current_value'], 1) . " / " . number_format($g['target_value'], 1);
                echo "</p>";
                echo "<div class='progress-bar'><div class='progress-fill' style='width:".round($progress)."%;'>".round($progress)."%</div></div>";
            }
        } else {
            echo "<p style='color: var(--text-light); margin: 20px 0;'>No goals set yet.</p>";
            echo "<a href='goals.php' class='action-btn'>Set Your Goals</a>";
        }
        ?>
    </div>

    <!-- Charts Section -->
    <div class="chart-container fade-in">
        <h3>📊 Health Progress Overview</h3>
        
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

    <!-- Latest Record Section -->
    <?php if ($latestRecord): ?>
        <div class="latest-record fade-in">
            <h3>📈 Latest Health Record</h3>
            <div class="record-grid">
                <div class="record-item">
                    <div class="label">Date</div>
                    <div class="value"><?php echo date('M d, Y', strtotime($latestRecord['date'])); ?></div>
                </div>
                <div class="record-item">
                    <div class="label">Weight</div>
                    <div class="value"><?php echo htmlspecialchars($latestRecord['weight']); ?> kg</div>
                </div>
                <div class="record-item">
                    <div class="label">Calories</div>
                    <div class="value"><?php echo number_format($latestRecord['calories']); ?> kcal</div>
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
        <div class="latest-record fade-in">
            <div class="no-records">
                <div style="font-size: 80px; margin-bottom: 20px;">📊</div>
                <h3>No Records Yet</h3>
                <p>Start your health journey by adding your first record!</p>
                <a href="add_record.php" class="action-btn">Add Your First Record</a>
            </div>
        </div>
    <?php endif; ?>

    <!-- Footer -->
    <div class="footer">
        <p>© 2025 HealthTrack. Your health, your journey. 💚</p>
    </div>
</div>

<script>
// Chart Data from PHP
const labels = <?php echo json_encode($dates); ?>;
const weights = <?php echo json_encode($weights); ?>;
const steps = <?php echo json_encode($steps); ?>;
const calories = <?php echo json_encode($calories); ?>;

// Enhanced Chart.js Configuration
Chart.defaults.font.family = "'Inter', sans-serif";
Chart.defaults.font.size = 13;
Chart.defaults.color = '#718096';

// Gradient Creation Helper Function
const createGradient = (ctx, color1, color2) => {
    const gradient = ctx.createLinearGradient(0, 0, 0, 400);
    gradient.addColorStop(0, color1);
    gradient.addColorStop(1, color2);
    return gradient;
};

// ========== WEIGHT CHART ==========
const weightCtx = document.getElementById('weightChart').getContext('2d');
const weightGradient = createGradient(weightCtx, 'rgba(102, 126, 234, 0.5)', 'rgba(102, 126, 234, 0.05)');

new Chart(weightCtx, {
    type: 'line',
    data: {
        labels: labels,
        datasets: [{
            label: 'Weight (kg)',
            data: weights,
            borderColor: '#667eea',
            backgroundColor: weightGradient,
            borderWidth: 4,
            tension: 0.4,
            fill: true,
            pointRadius: 7,
            pointHoverRadius: 10,
            pointBackgroundColor: '#667eea',
            pointBorderColor: '#fff',
            pointBorderWidth: 3,
            pointHoverBackgroundColor: '#fff',
            pointHoverBorderColor: '#667eea',
            pointHoverBorderWidth: 4,
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
                    font: { size: 14, weight: '600' },
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
                cornerRadius: 10,
                displayColors: true,
                callbacks: {
                    label: function(context) {
                        return ' Weight: ' + context.parsed.y + ' kg';
                    }
                }
            }
        },
        scales: {
            y: {
                beginAtZero: false,
                grid: {
                    color: 'rgba(102, 126, 234, 0.1)',
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
                    color: '#718096',
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

// ========== STEPS CHART ==========
const stepsCtx = document.getElementById('stepsChart').getContext('2d');
const stepsGradient = createGradient(stepsCtx, 'rgba(66, 165, 245, 1)', 'rgba(66, 165, 245, 0.4)');

new Chart(stepsCtx, {
    type: 'bar',
    data: {
        labels: labels,
        datasets: [{
            label: 'Steps Count',
            data: steps,
            backgroundColor: stepsGradient,
            borderColor: '#42a5f5',
            borderWidth: 2,
            borderRadius: 12,
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
                    font: { size: 14, weight: '600' },
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
                cornerRadius: 10,
                displayColors: true,
                callbacks: {
                    label: function(context) {
                        return ' Steps: ' + context.parsed.y.toLocaleString();
                    }
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                grid: {
                    color: 'rgba(66, 165, 245, 0.1)',
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
                    color: '#718096',
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

// ========== CALORIES CHART ==========
const caloriesCtx = document.getElementById('caloriesChart').getContext('2d');
const caloriesGradient = createGradient(caloriesCtx, 'rgba(255, 152, 0, 0.5)', 'rgba(255, 152, 0, 0.05)');

new Chart(caloriesCtx, {
    type: 'line',
    data: {
        labels: labels,
        datasets: [{
            label: 'Calories (kcal)',
            data: calories,
            borderColor: '#ff9800',
            backgroundColor: caloriesGradient,
            borderWidth: 4,
            tension: 0.4,
            fill: true,
            pointRadius: 7,
            pointHoverRadius: 10,
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
                    font: { size: 14, weight: '600' },
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
                cornerRadius: 10,
                displayColors: true,
                callbacks: {
                    label: function(context) {
                        return ' Calories: ' + context.parsed.y.toLocaleString() + ' kcal';
                    }
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                grid: {
                    color: 'rgba(255, 152, 0, 0.1)',
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
                    color: '#718096',
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

// ========== REMINDER NOTIFICATION SYSTEM ==========
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
    const currentHour = now.getHours();
    const currentMinute = now.getMinutes();
    
    reminders.forEach(r => {
        const [h, m] = r.time.split(':');
        if(currentHour === parseInt(h) && currentMinute === parseInt(m)) {
            if(Notification.permission === "granted") {
                new Notification("🏥 HealthTrack Reminder", { 
                    body: r.text,
                    icon: '/favicon.ico',
                    badge: '/favicon.ico',
                    requireInteraction: true
                });
            }
        }
    });
}

// Request notification permission on page load
if(Notification.permission !== "granted" && Notification.permission !== "denied") {
    Notification.requestPermission().then(permission => {
        if (permission === "granted") {
            console.log("✅ Notifications enabled for reminders!");
        }
    });
}

// Check reminders every minute
setInterval(checkReminders, 60000);

// ========== SMOOTH SCROLL BEHAVIOR ==========
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
            target.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    });
});

// ========== PAGE LOAD ANIMATIONS ==========
window.addEventListener('load', function() {
    // Animate progress bars
    const progressBars = document.querySelectorAll('.progress-fill');
    progressBars.forEach(bar => {
        const width = bar.style.width;
        bar.style.width = '0%';
        setTimeout(() => {
            bar.style.width = width;
        }, 500);
    });

    // Log welcome message
    console.log('%c🏥 HealthTrack Dashboard', 'color: #667eea; font-size: 24px; font-weight: bold;');
    console.log('%c✨ Tracking your health, one metric at a time!', 'color: #764ba2; font-size: 14px;');
    console.log('%c📊 Version 2.0', 'color: #42a5f5; font-size: 12px;');
});

// ========== BUTTON RIPPLE EFFECT ==========
document.querySelectorAll('.action-btn').forEach(button => {
    button.addEventListener('click', function(e) {
        const ripple = document.createElement('span');
        const rect = this.getBoundingClientRect();
        const size = Math.max(rect.width, rect.height);
        const x = e.clientX - rect.left - size / 2;
        const y = e.clientY - rect.top - size / 2;
        
        ripple.style.width = ripple.style.height = size + 'px';
        ripple.style.left = x + 'px';
        ripple.style.top = y + 'px';
        ripple.classList.add('ripple');
        
        this.appendChild(ripple);
        
        setTimeout(() => ripple.remove(), 600);
    });
});

// ========== REAL-TIME CLOCK ==========
function updateClock() {
    const now = new Date();
    const timeString = now.toLocaleTimeString('en-US', { 
        hour: '2-digit', 
        minute: '2-digit',
        second: '2-digit'
    });
    console.log('⏰ Current Time:', timeString);
}

// Update clock every second (optional - for debugging)
// setInterval(updateClock, 1000);

// ========== KEYBOARD SHORTCUTS ==========
document.addEventListener('keydown', function(e) {
    // Ctrl/Cmd + K for quick navigation
    if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
        e.preventDefault();
        console.log('🔍 Quick navigation shortcut activated!');
        // You can add a quick navigation modal here
    }
});

// ========== PERFORMANCE MONITORING ==========
window.addEventListener('load', function() {
    const loadTime = window.performance.timing.domContentLoadedEventEnd - window.performance.timing.navigationStart;
    console.log(`⚡ Page loaded in ${loadTime}ms`);
});

// ========== DATA REFRESH INDICATOR ==========
let lastDataUpdate = new Date();
console.log('📡 Data last updated:', lastDataUpdate.toLocaleString());

// ========== RESPONSIVE CHART RESIZE ==========
let resizeTimer;
window.addEventListener('resize', function() {
    clearTimeout(resizeTimer);
    resizeTimer = setTimeout(function() {
        console.log('📐 Charts resized for optimal viewing');
    }, 250);
});

</script>

</body>
</html>