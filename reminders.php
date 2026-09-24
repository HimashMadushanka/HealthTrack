<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$uid = $_SESSION['user_id'];
$msg = "";
$msg_type = "";

if(isset($_POST['add_reminder'])) {
    $text = $_POST['reminder_text'];
    $time = $_POST['reminder_time'];

    if(!empty($text) && !empty($time)) {
        $stmt = $conn->prepare("INSERT INTO reminders (user_id, reminder_text, reminder_time) VALUES (?, ?, ?)");
        $stmt->bind_param("iss", $uid, $text, $time);
        $stmt->execute();
        $msg = "Reminder added successfully!";
        $msg_type = "success";
    } else {
        $msg = "Please fill all fields.";
        $msg_type = "error";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reminders - HealthTrack</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .reminders-container {
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
            width: 100%;
            max-width: 500px;
        }

        .reminders-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .reminders-header h2 {
            color: #333;
            font-size: 28px;
            margin-bottom: 10px;
        }

        .reminders-header p {
            color: #666;
            font-size: 14px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            color: #333;
            font-weight: 500;
            margin-bottom: 8px;
            font-size: 14px;
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

        .success-message {
            background: #d4edda;
            color: #155724;
            padding: 12px;
            border-radius: 5px;
            margin-bottom: 20px;
            font-size: 14px;
            border-left: 4px solid #28a745;
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

        .divider {
            height: 1px;
            background: #e0e0e0;
            margin: 30px 0;
        }

        .reminders-list {
            list-style: none;
        }

        .list-header {
            color: #333;
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 15px;
        }

        .reminder-item {
            padding: 15px;
            background: #f8f9fa;
            border-radius: 5px;
            margin-bottom: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .reminder-text {
            color: #333;
            font-size: 14px;
        }

        .reminder-time {
            color: #667eea;
            font-size: 14px;
            font-weight: 600;
        }

        .no-reminders {
            text-align: center;
            color: #999;
            font-size: 14px;
            padding: 20px 0;
        }

        .form-footer {
            text-align: center;
            margin-top: 20px;
            color: #666;
            font-size: 14px;
        }

        .form-footer a {
            color: #667eea;
            text-decoration: none;
            font-weight: 500;
        }

        .form-footer a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="reminders-container">
        <div class="reminders-header">
            <h2>Your Reminders</h2>
            <p>Stay on track with your health goals</p>
        </div>

        <?php if($msg && $msg_type == "success"): ?>
            <div class="success-message">
                <?php echo htmlspecialchars($msg); ?>
            </div>
        <?php endif; ?>

        <?php if($msg && $msg_type == "error"): ?>
            <div class="error-message">
                <?php echo htmlspecialchars($msg); ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label for="reminder_text">Reminder</label>
                <input type="text" id="reminder_text" name="reminder_text" placeholder="e.g., Drink water, Take medicine" required>
            </div>

            <div class="form-group">
                <label for="reminder_time">Time</label>
                <input type="time" id="reminder_time" name="reminder_time" required>
            </div>

            <button type="submit" name="add_reminder" class="submit-btn">Add Reminder</button>
        </form>

        <div class="divider"></div>

        <div class="list-header">Scheduled Reminders</div>
        <ul class="reminders-list">
            <?php
            $result = $conn->query("SELECT * FROM reminders WHERE user_id='$uid' ORDER BY reminder_time ASC");
            if($result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {
                    echo "<li class='reminder-item'>";
                    echo "<span class='reminder-text'>" . htmlspecialchars($row['reminder_text']) . "</span>";
                    echo "<span class='reminder-time'>" . htmlspecialchars($row['reminder_time']) . "</span>";
                    echo "</li>";
                }
            } else {
                echo "<p class='no-reminders'>No reminders yet</p>";
            }
            ?>
        </ul>

        <div class="form-footer">
            <a href="dashboard.php">← Back to Dashboard</a>
        </div>
    </div>
</body>
</html>