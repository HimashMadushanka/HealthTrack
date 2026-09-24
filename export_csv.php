<?php
include 'db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch user data
$sql = "SELECT date, weight, calories, steps, bp FROM health_records WHERE user_id='$user_id' ORDER BY date DESC";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="health_records.csv"');

    $output = fopen("php://output", "w");
    fputcsv($output, ['Date', 'Weight (kg)', 'Calories (kcal)', 'Steps', 'Blood Pressure']);

    while ($row = $result->fetch_assoc()) {
        fputcsv($output, $row);
    }
    fclose($output);
} else {
    echo "No data found!";
}
exit();
?>
