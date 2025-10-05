<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$uid = $_SESSION['user_id'];
$msg = "";

// Handle form submission
if(isset($_POST['set_goal'])) {
    $type = $_POST['type'];
    $target = $_POST['target'];
    
    // Check if goal already exists
    $check = $conn->query("SELECT * FROM goals WHERE user_id='$uid' AND type='$type'");
    if($check->num_rows > 0){
        $conn->query("UPDATE goals SET target_value='$target' WHERE user_id='$uid' AND type='$type'");
        $msg = "Goal updated successfully!";
    } else {
        $conn->query("INSERT INTO goals (user_id, type, target_value) VALUES ('$uid', '$type', '$target')");
        $msg = "Goal set successfully!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Set Goals - HealthTrack</title>
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

        .goals-container {
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
            width: 100%;
            max-width: 500px;
        }

        .goals-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .goals-header h2 {
            color: #333;
            font-size: 28px;
            margin-bottom: 10px;
        }

        .goals-header p {
            color: #666;
            font-size: 14px;
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

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 5px;
            font-size: 14px;
            transition: border-color 0.3s;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #667eea;
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

        .list-header {
            color: #333;
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 15px;
        }

        .goal-item {
            margin-bottom: 20px;
        }

        .goal-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
        }

        .goal-type {
            color: #333;
            font-size: 14px;
            font-weight: 500;
            text-transform: capitalize;
        }

        .goal-values {
            color: #666;
            font-size: 14px;
        }

        .progress-bar {
            background: #f1f1f1;
            border-radius: 20px;
            height: 20px;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 12px;
            font-weight: 600;
            transition: width 0.3s ease;
        }

        .no-goals {
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
    <div class="goals-container">
        <div class="goals-header">
            <h2>Your Health Goals</h2>
            <p>Set and track your fitness targets</p>
        </div>

        <?php if($msg): ?>
            <div class="success-message">
                <?php echo htmlspecialchars($msg); ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label for="type">Goal Type</label>
                <select name="type" id="type" required>
                    <option value="weight">Weight (kg)</option>
                    <option value="steps">Steps</option>
                    <option value="calories">Calories (kcal)</option>
                </select>
            </div>

            <div class="form-group">
                <label for="target">Target Value</label>
                <input type="number" step="any" name="target" id="target" placeholder="Enter target value" required>
            </div>

            <button type="submit" name="set_goal" class="submit-btn">Set Goal</button>
        </form>

        <div class="divider"></div>

        <div class="list-header">Current Goals</div>
        
        <?php
        $goals = $conn->query("SELECT * FROM goals WHERE user_id='$uid'");
        if($goals->num_rows > 0) {
            while($g = $goals->fetch_assoc()) {
                $progress = ($g['target_value'] > 0) ? ($g['current_value'] / $g['target_value'] * 100) : 0;
                if($progress > 100) $progress = 100;
                $progress = round($progress);
                
                echo "<div class='goal-item'>";
                echo "<div class='goal-info'>";
                echo "<span class='goal-type'>" . htmlspecialchars($g['type']) . "</span>";
                echo "<span class='goal-values'>" . htmlspecialchars($g['current_value']) . " / " . htmlspecialchars($g['target_value']) . "</span>";
                echo "</div>";
                echo "<div class='progress-bar'>";
                echo "<div class='progress-fill' style='width:{$progress}%'>{$progress}%</div>";
                echo "</div>";
                echo "</div>";
            }
        } else {
            echo "<p class='no-goals'>No goals set yet</p>";
        }
        ?>

        <div class="form-footer">
            <a href="dashboard.php">← Back to Dashboard</a>
        </div>
    </div>
</body>
</html>