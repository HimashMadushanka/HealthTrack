<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $weight = $_POST['weight'];
    $calories = $_POST['calories'];
    $steps = $_POST['steps'];
    $bp = $_POST['bp'];
    $date = $_POST['date'];
    $uid = $_SESSION['user_id'];

    $sql = "INSERT INTO health_records (user_id, weight, calories, steps, bp, date) 
            VALUES ('$uid', '$weight', '$calories', '$steps', '$bp', '$date')";
    if ($conn->query($sql)) {
        $success = "Record added successfully!";
    } else {
        $error = "Error: " . $conn->error;
    }
}

// Get user info
$userQuery = $conn->query("SELECT name FROM users WHERE id='{$_SESSION['user_id']}'");
$user = $userQuery->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Health Record</title>
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

        .back-btn {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            border: 2px solid white;
            padding: 8px 20px;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s;
            display: inline-block;
        }

        .back-btn:hover {
            background: white;
            color: #667eea;
        }

        .container {
            max-width: 600px;
            margin: 30px auto;
            padding: 0 20px;
        }

        .form-container {
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        .form-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .form-header h2 {
            color: #333;
            font-size: 28px;
            margin-bottom: 10px;
        }

        .form-header p {
            color: #666;
            font-size: 14px;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group.full-width {
            grid-column: 1 / -1;
        }

        .form-group label {
            display: block;
            color: #333;
            font-weight: 500;
            margin-bottom: 8px;
            font-size: 14px;
        }

        .form-group label .icon {
            margin-right: 5px;
        }

        .form-group input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 5px;
            font-size: 14px;
            transition: border-color 0.3s;
        }

        .form-group input:focus {
            outline: none;
            border-color: #667eea;
        }

        .input-hint {
            font-size: 12px;
            color: #999;
            margin-top: 5px;
        }

        .error-message {
            background: #fee;
            color: #c33;
            padding: 12px;
            border-radius: 5px;
            margin-bottom: 20px;
            font-size: 14px;
            border-left: 4px solid #c33;
        }

        .success-message {
            background: #efe;
            color: #2a7;
            padding: 12px;
            border-radius: 5px;
            margin-bottom: 20px;
            font-size: 14px;
            border-left: 4px solid #2a7;
            text-align: center;
        }

        .success-message a {
            color: #2a7;
            font-weight: 600;
            text-decoration: none;
            margin-left: 5px;
        }

        .success-message a:hover {
            text-decoration: underline;
        }

        .submit-btn {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }

        .submit-btn:active {
            transform: translateY(0);
        }

        .form-footer {
            text-align: center;
            margin-top: 20px;
        }

        .form-footer a {
            color: #667eea;
            text-decoration: none;
            font-weight: 500;
        }

        .form-footer a:hover {
            text-decoration: underline;
        }

        @media (max-width: 768px) {
            .navbar {
                flex-direction: column;
                gap: 10px;
            }

            .form-container {
                padding: 30px 20px;
            }

            .form-row {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="navbar">
        <h1>Health Tracker</h1>
        <div class="user-info">
            <span>Welcome, <?php echo htmlspecialchars($user['name']); ?></span>
            <a href="dashboard.php" class="back-btn">← Back to Dashboard</a>
        </div>
    </div>

    <div class="container">
        <div class="form-container">
            <div class="form-header">
                <h2>Add Health Record</h2>
                <p>Track your daily health metrics</p>
            </div>

            <?php if ($error): ?>
                <div class="error-message">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="success-message">
                    <?php echo htmlspecialchars($success); ?>
                    <a href="dashboard.php">View Dashboard</a>
                </div>
            <?php endif; ?>

            <form method="post">
                <div class="form-row">
                    <div class="form-group">
                        <label for="weight">
                            <span class="icon">⚖️</span> Weight
                        </label>
                        <input type="number" step="0.1" id="weight" name="weight" placeholder="70.5" required>
                        <div class="input-hint">in kilograms</div>
                    </div>

                    <div class="form-group">
                        <label for="calories">
                            <span class="icon">🔥</span> Calories
                        </label>
                        <input type="number" id="calories" name="calories" placeholder="2000" required>
                        <div class="input-hint">kcal consumed</div>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="steps">
                            <span class="icon">👟</span> Steps
                        </label>
                        <input type="number" id="steps" name="steps" placeholder="10000" required>
                        <div class="input-hint">steps walked</div>
                    </div>

                    <div class="form-group">
                        <label for="bp">
                            <span class="icon">❤️</span> Blood Pressure
                        </label>
                        <input type="text" id="bp" name="bp" placeholder="120/80" pattern="[0-9]{2,3}/[0-9]{2,3}" required>
                        <div class="input-hint">format: 120/80</div>
                    </div>
                </div>

                <div class="form-group full-width">
                    <label for="date">
                        <span class="icon">📅</span> Date
                    </label>
                    <input type="date" id="date" name="date" value="<?php echo date('Y-m-d'); ?>" required>
                </div>

                <button type="submit" class="submit-btn">Save Health Record</button>
            </form>

            <div class="form-footer">
                <a href="dashboard.php">← Cancel and go back</a>
            </div>
        </div>
    </div>
</body>
</html>