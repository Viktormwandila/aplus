<?php
require_once 'config.php';

$question_id = $_POST['question_id'];
$user_id = $_POST['user_id'];
$exam_id = $_POST['exam_id'];

$query = "DELETE FROM user_responses WHERE question_id = ? AND user_id = ? AND exam_id = ?";
$stmt = $mysqli->prepare($query);
$stmt->bind_param('iii', $question_id, $user_id, $exam_id);
$stmt->execute();
$stmt->close();

echo json_encode(['success' => true]);
?>
