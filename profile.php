<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$uid = $_SESSION['user_id'];

// Fetch user info
$userQuery = $conn->query("SELECT * FROM users WHERE id='$uid'");
$user = $userQuery->fetch_assoc();

$msg = "";

// Handle profile picture removal
if (isset($_GET['remove_pic']) && $_GET['remove_pic'] == '1') {
    // Delete old profile picture file
    if (!empty($user['profile_pic']) && file_exists("uploads/" . $user['profile_pic'])) {
        unlink("uploads/" . $user['profile_pic']);
    }
    
    // Update database
    $removeQuery = "UPDATE users SET profile_pic=NULL WHERE id='$uid'";
    if ($conn->query($removeQuery)) {
        $msg = "✅ Profile picture removed successfully!";
        $user['profile_pic'] = null;
    } else {
        $msg = "❌ Error removing profile picture.";
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $conn->real_escape_string($_POST['name']);
    $email = $conn->real_escape_string($_POST['email']);

    // Handle profile picture
    if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] == 0) {
        $targetDir = "uploads/";
        
        // Create uploads directory if it doesn't exist
        if (!file_exists($targetDir)) {
            mkdir($targetDir, 0777, true);
        }
        
        $fileName = time() . '_' . $_FILES['profile_pic']['name'];
        $targetPath = $targetDir . $fileName;
        
        if (move_uploaded_file($_FILES['profile_pic']['tmp_name'], $targetPath)) {
            $profilePicSQL = ", profile_pic='$fileName'";
        } else {
            $msg = "❌ Error uploading file.";
            $profilePicSQL = "";
        }
    } else {
        $profilePicSQL = "";
    }

    // Update database
    if (!isset($msg)) {
        $updateQuery = "UPDATE users SET name='$name', email='$email' $profilePicSQL WHERE id='$uid'";
        if ($conn->query($updateQuery)) {
            $msg = "✅ Profile updated successfully!";
            $user['name'] = $name;
            $user['email'] = $email;
            if (isset($fileName)) {
                $user['profile_pic'] = $fileName;
            }
        } else {
            $msg = "❌ Error updating profile.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile Settings - HealthTrack</title>
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
            padding: 20px;
        }

        .navbar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            border-radius: 10px;
            margin-bottom: 30px;
        }

        .navbar h1 {
            font-size: 24px;
        }

        .back-btn {
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

        .back-btn:hover {
            background: white;
            color: #667eea;
        }

        .container {
            max-width: 600px;
            margin: 0 auto;
        }

        .profile-card {
            background: white;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
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

        .profile-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .profile-header h2 {
            color: #667eea;
            font-size: 28px;
            margin-bottom: 10px;
        }

        .profile-header p {
            color: #666;
            font-size: 14px;
        }

        .profile-pic-container {
            text-align: center;
            margin-bottom: 30px;
        }

        .profile-pic {
            display: inline-block;
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 5px solid #667eea;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
            transition: all 0.3s ease;
        }

        .profile-pic:hover {
            transform: scale(1.05);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
        }

        .default-avatar {
            display: inline-flex;
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            font-size: 48px;
            align-items: center;
            justify-content: center;
            border: 5px solid #667eea;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }

        .remove-pic-btn {
            display: inline-block;
            margin-top: 15px;
            background: #ff4444;
            color: white;
            padding: 8px 20px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 2px 10px rgba(255, 68, 68, 0.3);
        }

        .remove-pic-btn:hover {
            background: #cc0000;
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(255, 68, 68, 0.5);
        }

        .pic-actions {
            margin-top: 15px;
        }

        form {
            display: flex;
            flex-direction: column;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            font-weight: 600;
            color: #333;
            font-size: 14px;
            margin-bottom: 8px;
        }

        input[type=text],
        input[type=email] {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            font-size: 16px;
            transition: all 0.3s ease;
            background: #f8f9ff;
        }

        input[type=text]:focus,
        input[type=email]:focus {
            outline: none;
            border-color: #667eea;
            background: white;
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
        }

        input[type=text]:hover,
        input[type=email]:hover {
            border-color: #667eea;
        }

        input[type=file] {
            width: 100%;
            padding: 12px;
            border: 2px dashed #e0e0e0;
            border-radius: 12px;
            font-size: 14px;
            background: #f8f9ff;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        input[type=file]:hover {
            border-color: #667eea;
            background: white;
        }

        button {
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
            margin-top: 10px;
        }

        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.6);
        }

        button:active {
            transform: translateY(0);
        }

        .message {
            margin-bottom: 20px;
            text-align: center;
            color: #333;
            font-weight: 600;
            font-size: 15px;
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

        .info-section {
            background: linear-gradient(135deg, #f8f9ff 0%, #e8ecff 100%);
            padding: 20px;
            border-radius: 12px;
            margin-top: 20px;
            border-left: 4px solid #667eea;
        }

        .info-section h4 {
            color: #667eea;
            margin-bottom: 10px;
            font-size: 16px;
        }

        .info-section p {
            font-size: 13px;
            color: #666;
            line-height: 1.6;
        }

        .user-stats {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-top: 20px;
        }

        .stat-box {
            background: white;
            padding: 15px;
            border-radius: 10px;
            text-align: center;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        .stat-box .icon {
            font-size: 24px;
            margin-bottom: 8px;
        }

        .stat-box .label {
            font-size: 12px;
            color: #666;
            text-transform: uppercase;
        }

        .stat-box .value {
            font-size: 18px;
            font-weight: bold;
            color: #667eea;
        }

        @media (max-width: 768px) {
            .profile-card {
                padding: 25px;
            }

            .user-stats {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>

<div class="navbar">
    <h1>⚙️ Profile Settings</h1>
    <a href="dashboard.php" class="back-btn">⬅ Back to Dashboard</a>
</div>

<div class="container">
    <div class="profile-card">
        <div class="profile-header">
            <h2>Edit Your Profile</h2>
            <p>Update your personal information and profile picture</p>
        </div>

        <?php if ($msg): ?>
            <div class="message"><?php echo $msg; ?></div>
        <?php endif; ?>

        <div class="profile-pic-container">
            <?php if (!empty($user['profile_pic'])): ?>
                <img src="uploads/<?php echo htmlspecialchars($user['profile_pic']); ?>" class="profile-pic" alt="Profile Picture">
                <div class="pic-actions">
                    <a href="?remove_pic=1" class="remove-pic-btn" onclick="return confirm('Are you sure you want to remove your profile picture?')">🗑️ Remove Picture</a>
                </div>
            <?php else: ?>
                <div class="default-avatar">👤</div>
            <?php endif; ?>
        </div>

        <form action="" method="post" enctype="multipart/form-data">
            <div class="form-group">
                <label for="name">Full Name</label>
                <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required>
            </div>
            
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
            </div>
            
            <div class="form-group">
                <label for="profile_pic">Profile Picture (Optional)</label>
                <input type="file" id="profile_pic" name="profile_pic" accept="image/*">
            </div>
            
            <button type="submit">💾 Update Profile</button>
        </form>

        <div class="info-section">
            <h4>📝 Profile Information</h4>
            <div class="user-stats">
                <div class="stat-box">
                    <div class="icon">📧</div>
                    <div class="label">Email</div>
                    <div class="value"><?php echo htmlspecialchars($user['email']); ?></div>
                </div>
                <div class="stat-box">
                    <div class="icon">👤</div>
                    <div class="label">Name</div>
                    <div class="value"><?php echo htmlspecialchars($user['name']); ?></div>
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>