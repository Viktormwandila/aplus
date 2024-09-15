<?php
session_start();
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $exam_id = intval($_POST['exam_id']);
    $question_id = intval($_POST['question_id']);
    $response_text = $_POST['message'];
    $is_submitted = intval($_POST['is_submitted']); // 1 if submitted, 0 if just posted

    // Prepare and execute the insert statement
    $stmt = $mysqli->prepare("INSERT INTO user_responses (exam_id, user_id, question_id, response_text, is_submitted, created_at) VALUES (?, ?, ?, ?, ?, NOW()) ON DUPLICATE KEY UPDATE response_text = VALUES(response_text), is_submitted = VALUES(is_submitted), created_at = VALUES(created_at)");
    $stmt->bind_param('iiisi', $exam_id, $user_id, $question_id, $response_text, $is_submitted);

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => $stmt->error]);
    }

    $stmt->close();
}

?>

