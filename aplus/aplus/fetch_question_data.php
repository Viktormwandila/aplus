<?php
session_start();
require_once 'config.php';

if (!isset($_POST['exam_id']) || !isset($_POST['question_id']) || !isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
    exit();
}

$exam_id = intval($_POST['exam_id']);
$question_id = intval($_POST['question_id']);
$user_id = intval($_SESSION['user_id']);

// Fetch user response for the current question
$response_query = "SELECT response_text FROM user_responses WHERE exam_id = ? AND question_id = ? AND user_id = ?";
$response_stmt = $mysqli->prepare($response_query);
$response_stmt->bind_param('iii', $exam_id, $question_id, $user_id);
$response_stmt->execute();
$response_result = $response_stmt->get_result();
$user_response = $response_result->fetch_assoc();
$response_stmt->close();

// Fetch question details
$query = "SELECT question_text, correct_answer, explanation_text FROM questions WHERE exam_id = ? AND question_id = ?";
$stmt = $mysqli->prepare($query);
$stmt->bind_param('ii', $exam_id, $question_id);
$stmt->execute();
$result = $stmt->get_result();
$question_data = $result->fetch_assoc();
$stmt->close();

// Return data as JSON
echo json_encode([
    'status' => 'success',
    'user_response_text' => $user_response ? htmlspecialchars($user_response['response_text']) : '',
    'question_text' => htmlspecialchars($question_data['question_text']),
    'question_answer_text' => htmlspecialchars($question_data['correct_answer']),
    'question_explanation_text' => htmlspecialchars($question_data['explanation_text']),
]);
exit();
?>
