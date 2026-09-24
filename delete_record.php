<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$uid = $_SESSION['user_id'];

if (isset($_GET['id'])) {
    $record_id = intval($_GET['id']);

    // Make sure the record belongs to the logged-in user
    $query = $conn->prepare("DELETE FROM health_records WHERE id=? AND user_id=?");
    $query->bind_param("ii", $record_id, $uid);
    $query->execute();

    header("Location: dashboard.php?msg=deleted");
    exit;
} else {
    header("Location: dashboard.php");
    exit;
}
?>
