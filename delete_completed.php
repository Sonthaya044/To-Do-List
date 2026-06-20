<?php
session_start();
include "includes/config.php";

if (!isset($_SESSION['username'])) {
    header('Location: auth.php');
    exit;
}

$username = $_SESSION['username'];

$stmt = $conn->prepare("DELETE FROM notes WHERE username = ? AND is_completed = 1");
$stmt->bind_param("s", $username);

if ($stmt->execute()) {
    header("Location: today.php");
} else {
    echo "Error deleting records: " . $conn->error;
}

$stmt->close();
$conn->close();
?>