<?php
include 'db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$message = "";

// When user submits form
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $height = $_POST['height'];
    $weight = $_POST['weight'];
    $user_id = $_SESSION['user_id'];

    // BMI Formula
    $height_m = $height / 100; // convert cm to meters
    $bmi = $weight / ($height_m * $height_m);

    // Determine BMI Category
    if ($bmi < 18.5) {
        $category = "Underweight";
    } elseif ($bmi >= 18.5 && $bmi < 24.9) {
        $category = "Normal weight";
    } elseif ($bmi >= 25 && $bmi < 29.9) {
        $category = "Overweight";
    } else {
        $category = "Obesity";
    }

    // Save data in database
    $sql = "INSERT INTO bmi_records (user_id, height, weight, bmi, category)
            VALUES ('$user_id', '$height', '$weight', '$bmi', '$category')";
    if ($conn->query($sql) === TRUE) {
        $message = "✅ Your BMI is " . round($bmi, 2) . " ($category)";
    } else {
        $message = "❌ Error: " . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>BMI Calculator - HealthTrack</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }

        .bmi-container {
            background: rgba(255, 255, 255, 0.95);
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            width: 100%;
            max-width: 450px;
            backdrop-filter: blur(10px);
            animation: slideIn 0.5s ease-out;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        h2 {
            text-align: center;
            color: #667eea;
            font-size: 32px;
            margin-bottom: 30px;
            font-weight: 700;
        }

        form {
            display: flex;
            flex-direction: column;
        }

        label {
            margin-top: 15px;
            font-weight: 600;
            color: #333;
            font-size: 14px;
            margin-bottom: 8px;
        }

        input {
            padding: 14px 16px;
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            font-size: 16px;
            transition: all 0.3s ease;
            background: #f8f9ff;
        }

        input:focus {
            outline: none;
            border-color: #667eea;
            background: white;
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
        }

        input:hover {
            border-color: #667eea;
        }

        button {
            margin-top: 25px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 16px;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        }

        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.6);
        }

        button:active {
            transform: translateY(0);
        }

        .message {
            margin-top: 20px;
            text-align: center;
            color: #333;
            font-weight: 600;
            font-size: 16px;
            padding: 15px;
            background: linear-gradient(135deg, #f8f9ff 0%, #e8ecff 100%);
            border-radius: 12px;
            border-left: 4px solid #667eea;
            animation: fadeIn 0.5s ease-in;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .back-link {
            display: block;
            margin-top: 20px;
            text-align: center;
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
            font-size: 15px;
            padding: 12px;
            border-radius: 10px;
            transition: all 0.3s ease;
        }

        .back-link:hover {
            background: #f8f9ff;
            transform: translateX(-5px);
        }

        /* Info Card Styles */
        .info-card {
            background: linear-gradient(135deg, #f8f9ff 0%, #e8ecff 100%);
            padding: 20px;
            border-radius: 12px;
            margin-top: 25px;
            border-left: 4px solid #667eea;
        }

        .info-card h4 {
            color: #667eea;
            margin-bottom: 10px;
            font-size: 16px;
        }

        .info-card p {
            font-size: 13px;
            color: #666;
            line-height: 1.6;
        }

        .bmi-ranges {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-top: 10px;
        }

        .bmi-range {
            background: white;
            padding: 10px;
            border-radius: 8px;
            font-size: 12px;
            text-align: center;
        }

        .bmi-range strong {
            display: block;
            margin-bottom: 5px;
            color: #667eea;
        }
    </style>
</head>
<body>
    <div class="bmi-container">
        <h2>🧮 BMI Calculator</h2>
        <form method="POST" action="">
            <label for="height">Height (cm):</label>
            <input type="number" name="height" id="height" required step="0.01">

            <label for="weight">Weight (kg):</label>
            <input type="number" name="weight" id="weight" required step="0.01">

            <button type="submit">Calculate BMI</button>
        </form>
        
        <?php if ($message): ?>
            <div class="message"><?php echo $message; ?></div>
        <?php endif; ?>

        <div class="info-card">
            <h4>📊 BMI Categories</h4>
            <div class="bmi-ranges">
                <div class="bmi-range">
                    <strong>&lt; 18.5</strong>
                    Underweight
                </div>
                <div class="bmi-range">
                    <strong>18.5 - 24.9</strong>
                    Normal
                </div>
                <div class="bmi-range">
                    <strong>25 - 29.9</strong>
                    Overweight
                </div>
                <div class="bmi-range">
                    <strong>≥ 30</strong>
                    Obesity
                </div>
            </div>
        </div>

        <a class="back-link" href="dashboard.php">⬅ Back to Dashboard</a>
    </div>
</body>
</html>