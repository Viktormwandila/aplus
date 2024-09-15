<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'User not logged in']);
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch the number of questions answered by the user
$query = "SELECT COUNT(*) FROM user_responses WHERE user_id = ?";
$stmt = $mysqli->prepare($query);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$stmt->bind_result($questions_answered);
$stmt->fetch();
$stmt->close();

// Return the count as JSON
echo json_encode(['status' => 'success', 'questions_answered' => $questions_answered]);
?>
