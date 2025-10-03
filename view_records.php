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

// Get health records
$res = $conn->query("SELECT * FROM health_records WHERE user_id='$uid' ORDER BY date DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Health Dashboard</title>
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

        .dashboard-header {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
        }

        .dashboard-header h2 {
            color: #333;
            font-size: 28px;
        }

        .add-record-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .add-record-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }

        .records-container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px;
            text-align: left;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 12px;
            letter-spacing: 0.5px;
        }

        th:first-child {
            border-top-left-radius: 5px;
        }

        th:last-child {
            border-top-right-radius: 5px;
        }

        td {
            padding: 15px;
            border-bottom: 1px solid #f0f0f0;
            color: #555;
        }

        tr:hover {
            background: #f8f9ff;
        }

        tr:last-child td {
            border-bottom: none;
        }

        .no-records {
            text-align: center;
            padding: 40px;
            color: #999;
        }

        .no-records-icon {
            font-size: 48px;
            margin-bottom: 15px;
        }

        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }

        .badge-weight {
            background: #e3f2fd;
            color: #1976d2;
        }

        .badge-calories {
            background: #fff3e0;
            color: #f57c00;
        }

        .badge-steps {
            background: #e8f5e9;
            color: #388e3c;
        }

        .badge-bp {
            background: #fce4ec;
            color: #c2185b;
        }

        @media (max-width: 768px) {
            .navbar {
                flex-direction: column;
                gap: 10px;
            }

            .dashboard-header {
                flex-direction: column;
                align-items: flex-start;
            }

            table {
                font-size: 14px;
            }

            th, td {
                padding: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="navbar">
        <h1>Health Tracker</h1>
        <div class="user-info">
            <span>Welcome, <?php echo htmlspecialchars($user['name']); ?></span>
            <a href="logout.php" class="logout-btn">Logout</a>
        </div>
    </div>

    <div class="container">
        <div class="dashboard-header">
            <h2>Your Health Records</h2>
            <a href="add_record.php" class="add-record-btn">+ Add New Record</a>
        </div>

        <div class="records-container">
            <?php if ($res->num_rows > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Weight</th>
                            <th>Calories</th>
                            <th>Steps</th>
                            <th>Blood Pressure</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $res->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['date']); ?></td>
                            <td>
                                <span class="badge badge-weight">
                                    <?php echo htmlspecialchars($row['weight']); ?> kg
                                </span>
                            </td>
                            <td>
                                <span class="badge badge-calories">
                                    <?php echo htmlspecialchars($row['calories']); ?> kcal
                                </span>
                            </td>
                            <td>
                                <span class="badge badge-steps">
                                    <?php echo number_format($row['steps']); ?> steps
                                </span>
                            </td>
                            <td>
                                <span class="badge badge-bp">
                                    <?php echo htmlspecialchars($row['bp']); ?>
                                </span>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="no-records">
                    <div class="no-records-icon">📊</div>
                    <h3>No Health Records Yet</h3>
                    <p>Start tracking your health by adding your first record!</p>
                    <br>
                    <a href="add_record.php" class="add-record-btn">Add Your First Record</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>