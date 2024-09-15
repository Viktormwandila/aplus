<?php
session_start();
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $exam_id = intval($_POST['exam_id']);
    $user_id = intval($_POST['user_id']);
    $question_id = intval($_POST['question_id']);
    $response_text = trim($_POST['response_text']);
    $score = intval($_POST['score']);  // Ensure score is an integer

    $is_submitted = intval($_POST['is_submitted']);
    $created_at = date('Y-m-d H:i:s');

    // Validate inputs
    if (empty($response_text)) {
        echo json_encode(["status" => "error", "message" => "Response text cannot be empty."]);
        exit();
    }

    // Validate that the question_id exists in the questions table
    $question_check_query = "SELECT * FROM questions WHERE question_id = ?";
    $question_check_stmt = $mysqli->prepare($question_check_query);
    $question_check_stmt->bind_param('i', $question_id);
    $question_check_stmt->execute();
    $question_check_result = $question_check_stmt->get_result();

    if ($question_check_result->num_rows === 0) {
        echo json_encode(["status" => "error", "message" => "Invalid question ID. The question does not exist."]);
        $question_check_stmt->close();
        exit();
    }
    $question_check_stmt->close();

    // Check if the response already exists
    $check_query = "SELECT * FROM user_responses WHERE exam_id = ? AND user_id = ? AND question_id = ?";
    $stmt = $mysqli->prepare($check_query);
    if ($stmt) {
        $stmt->bind_param('iii', $exam_id, $user_id, $question_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            // Response exists, update it
            $update_query = "UPDATE user_responses SET response_text = ?, is_submitted = ?, created_at = ?, score = ? 
            WHERE exam_id = ? AND user_id = ? AND question_id = ?";
            $update_stmt = $mysqli->prepare($update_query);
            if ($update_stmt) {
                // Correct the bind_param types: 'sisiiii' to match the correct types
                $update_stmt->bind_param('sisiiii', $response_text, $is_submitted, $created_at, $score, $exam_id, $user_id, $question_id);
                if ($update_stmt->execute()) {
                    echo json_encode(["status" => "success", "message" => "Response updated successfully."]);
                } else {
                    echo json_encode(["status" => "error", "message" => "Database error: " . $update_stmt->error]);
                }
                $update_stmt->close();
            } else {
                echo json_encode(["status" => "error", "message" => "Database preparation error: " . $mysqli->error]);
            }
        } else {
            // Response doesn't exist, insert a new one
            $insert_query = "INSERT INTO user_responses (exam_id, user_id, question_id, response_text, is_submitted, created_at, score) 
                             VALUES (?, ?, ?, ?, ?, ?, ?)";
            $insert_stmt = $mysqli->prepare($insert_query);
            if ($insert_stmt) {
                // Correct the bind_param types: 'iiisisi' to match the correct order and types
                $insert_stmt->bind_param('iiisisi', $exam_id, $user_id, $question_id, $response_text, $is_submitted, $created_at, $score);
                if ($insert_stmt->execute()) {
                    echo json_encode(["status" => "success", "message" => "Response submitted successfully."]);
                } else {
                    echo json_encode(["status" => "error", "message" => "Database error: " . $insert_stmt->error]);
                }
                $insert_stmt->close();
            } else {
                echo json_encode(["status" => "error", "message" => "Database preparation error: " . $mysqli->error]);
            }
        }
        $stmt->close();
    } else {
        echo json_encode(["status" => "error", "message" => "Database preparation error: " . $mysqli->error]);
    }

    $mysqli->close();
} else {
    header('Location: error_page.php');
    exit();
}
?>
