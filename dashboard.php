<?php
session_start();
include 'db.php';

// Redirect if user not logged in
if(!isset($_SESSION['user_id'])){
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Functions for BMI category and advice
function getBMICategory($bmi){
    if($bmi < 18.5) return "Underweight";
    if($bmi < 25) return "Normal weight";
    if($bmi < 30) return "Overweight";
    return "Obese";
}

function getHealthAdvice($bmi){
    if($bmi < 18.5) return "Eat more nutritious food to gain weight.";
    if($bmi < 25) return "Keep up the good work!";
    if($bmi < 30) return "Exercise more and watch your diet.";
    return "Consult a doctor for proper guidance.";
}

// Save new health data
if(isset($_POST['save'])){
    $weight = $_POST['weight'];
    $height_cm = $_POST['height'];
    $height_m = $height_cm / 100;
    $calories = $_POST['calories'];
    $water = $_POST['water'];
    $steps = $_POST['steps'];

    $bmi = $weight / ($height_m * $height_m);

    $sql = "INSERT INTO health_data (user_id, date, weight, height, bmi, calories, water, steps)
            VALUES ('$user_id', CURDATE(), '$weight', '$height_cm', '$bmi', '$calories', '$water', '$steps')";
    $conn->query($sql);
}

// Delete record
if(isset($_GET['delete'])){
    $id = $_GET['delete'];
    $conn->query("DELETE FROM health_data WHERE id='$id' AND user_id='$user_id'");
    header("Location: dashboard.php");
    exit();
}

// Fetch all records for table
$records = $conn->query("SELECT * FROM health_data WHERE user_id='$user_id' ORDER BY date DESC");

// Fetch data for charts
$chartData = $conn->query("SELECT date, weight, bmi, calories, water, steps FROM health_data WHERE user_id='$user_id' ORDER BY date ASC");
$dates = $weights = $bmis = $caloriesData = $waterData = $stepsData = [];
while($row = $chartData->fetch_assoc()){
    $dates[] = $row['date'];
    $weights[] = $row['weight'];
    $bmis[] = round($row['bmi'],2);
    $caloriesData[] = $row['calories'];
    $waterData[] = $row['water'];
    $stepsData[] = $row['steps'];
}

// Weekly summary
$summary = $conn->query("SELECT AVG(weight) AS avg_weight, AVG(bmi) AS avg_bmi, 
                        AVG(calories) AS avg_calories, AVG(water) AS avg_water, 
                        AVG(steps) AS avg_steps 
                        FROM health_data WHERE user_id='$user_id'");
$sum = $summary->fetch_assoc();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Health Tracker Dashboard</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        input, button { padding: 8px; margin: 5px 0; width: 100%; max-width: 200px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ccc; padding: 8px; text-align: center; }
        th { background-color: #f0f0f0; }
        .charts { display: flex; flex-wrap: wrap; gap: 20px; margin-top: 20px; }
        canvas { background: #fff; border: 1px solid #ccc; padding: 10px; }
        .summary { margin-top: 20px; }
        a.delete { color: red; text-decoration: none; }
        a.delete:hover { text-decoration: underline; }
    </style>
</head>
<body>

<a href="logout.php">Logout</a>
<h2>Health Tracker Dashboard</h2>

<form method="post">
    <input type="number" name="weight" placeholder="Weight (kg)" step="0.1" required>
    <input type="number" name="height" placeholder="Height (cm)" step="0.1" required>
    <input type="number" name="calories" placeholder="Calories" required>
    <input type="number" name="water" placeholder="Water (ml)" required>
    <input type="number" name="steps" placeholder="Steps" required>
    <button type="submit" name="save">Save Data</button>
</form>

<div class="summary">
    <h3>Weekly Summary (Average)</h3>
    <p>Weight: <?= round($sum['avg_weight'],2) ?> kg | BMI: <?= round($sum['avg_bmi'],2) ?> | Calories: <?= round($sum['avg_calories'],0) ?> | Water: <?= round($sum['avg_water'],0) ?> ml | Steps: <?= round($sum['avg_steps'],0) ?></p>
</div>

<h3>Your Records</h3>
<table>
    <tr>
        <th>Date</th>
        <th>Weight</th>
        <th>Height</th>
        <th>BMI</th>
        <th>Category</th>
        <th>Advice</th>
        <th>Calories</th>
        <th>Water</th>
        <th>Steps</th>
        <th>Action</th>
    </tr>
    <?php while($row = $records->fetch_assoc()): ?>
        <tr>
            <td><?= $row['date'] ?></td>
            <td><?= $row['weight'] ?></td>
            <td><?= $row['height'] ?></td>
            <td><?= round($row['bmi'],2) ?></td>
            <td><?= getBMICategory($row['bmi']) ?></td>
            <td><?= getHealthAdvice($row['bmi']) ?></td>
            <td><?= $row['calories'] ?></td>
            <td><?= $row['water'] ?></td>
            <td><?= $row['steps'] ?></td>
            <td><a class="delete" href="?delete=<?= $row['id'] ?>" onclick="return confirm('Delete this record?')">Delete</a></td>
        </tr>
    <?php endwhile; ?>
</table>

<h3>Progress Charts</h3>
<div class="charts">
    <canvas id="weightChart" width="400" height="200"></canvas>
    <canvas id="caloriesChart" width="400" height="200"></canvas>
    <canvas id="waterChart" width="400" height="200"></canvas>
    <canvas id="stepsChart" width="400" height="200"></canvas>
</div>

<script>
const dates = <?= json_encode($dates) ?>;
const weights = <?= json_encode($weights) ?>;
const bmis = <?= json_encode($bmis) ?>;
const caloriesData = <?= json_encode($caloriesData) ?>;
const waterData = <?= json_encode($waterData) ?>;
const stepsData = <?= json_encode($stepsData) ?>;

new Chart(document.getElementById('weightChart').getContext('2d'), {
    type: 'line',
    data: { labels: dates, datasets: [
        { label: 'Weight (kg)', data: weights, borderColor: 'blue', fill: false, tension: 0.1 },
        { label: 'BMI', data: bmis, borderColor: 'green', fill: false, tension: 0.1 }
    ]},
    options: { responsive: true }
});

new Chart(document.getElementById('caloriesChart').getContext('2d'), {
    type: 'line',
    data: { labels: dates, datasets: [
        { label: 'Calories', data: caloriesData, borderColor: 'orange', fill: false, tension: 0.1 }
    ]},
    options: { responsive: true }
});

new Chart(document.getElementById('waterChart').getContext('2d'), {
    type: 'line',
    data: { labels: dates, datasets: [
        { label: 'Water (ml)', data: waterData, borderColor: 'cyan', fill: false, tension: 0.1 }
    ]},
    options: { responsive: true }
});

new Chart(document.getElementById('stepsChart').getContext('2d'), {
    type: 'line',
    data: { labels: dates, datasets: [
        { label: 'Steps', data: stepsData, borderColor: 'purple', fill: false, tension: 0.1 }
    ]},
    options: { responsive: true }
});
</script>



</body>
</html>
