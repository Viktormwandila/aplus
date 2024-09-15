<?php
session_start();
require_once 'config.php';  // Include your database connection
require_once 'header.php';  // Include the header

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Fetch the user's ID
$user_id = $_SESSION['user_id'];

// Query to fetch exams with user progress
$query = "
    SELECT exams.exam_id, exams.emoji, exams.exam_name, exams.paper_code, exams.date,
           COUNT(questions.question_id) AS total_questions,
           SUM(IF(user_responses.user_id IS NOT NULL, 1, 0)) AS attempted_questions
    FROM exams
    LEFT JOIN questions ON exams.exam_id = questions.exam_id
    LEFT JOIN user_responses ON questions.question_id = user_responses.question_id AND user_responses.user_id = ?
    GROUP BY exams.exam_id
    ORDER BY IFNULL(MAX(user_responses.created_at), exams.date) DESC, exams.date DESC";

$stmt = $mysqli->prepare($query);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Home</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f7f7f7;
            margin: 0;
            padding: 0px;
        }
        .exam-list {
            display: flex;
            flex-wrap: wrap;
            justify-content: left;
        }
        .exam-item {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            margin: 10px;
            padding: 20px;
            width: 300px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            transition: box-shadow 0.3s;
        }
        .exam-item:hover {
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }
        .exam-details {
            flex-grow: 1;
        }
        .exam-item h2 {
            font-size: 1.2em;
            margin-bottom: 10px;
        }
        .exam-item p {
            margin: 5px 0;
            color: #555;
        }
        .exam-item a {
            text-decoration: none;
            color: #007bff;
            font-weight: bold;
            display: inline-block;
            margin-top: 10px;
        }
        .exam-item a:hover {
            text-decoration: underline;
        }
        .progress-ring {
            position: relative;
            width: 60px;
            height: 60px;
        }
        .progress-ring circle {
            fill: transparent;
            stroke-width: 6;
            transform: rotate(-90deg);
            transform-origin: 50% 50%;
        }
        .progress-ring-text {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            font-size: 12px;
            font-weight: bold;
            text-align: center;
        }
        @media (max-width: 600px) {
            .exam-item {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="container mt-4">
        <div class="exam-list">
            <?php while ($row = $result->fetch_assoc()) {
                $total_questions = $row['total_questions'];
                $attempted_questions = $row['attempted_questions'] ?: 0;
                $progress_percentage = ($total_questions > 0) ? ($attempted_questions / $total_questions) * 100 : 0;
                ?>
                <div class="exam-item">
                    <div class="exam-details">
                        <h2><?php echo htmlspecialchars($row['emoji']).' '.htmlspecialchars($row['exam_name']); ?></h2>
                        <p>Paper Code: <?php echo htmlspecialchars($row['paper_code']); ?></p>
                        <p>Date: <?php echo htmlspecialchars($row['date']); ?></p>
                        <a href="user_exam.php?exam_id=<?php echo $row['exam_id']; ?>">
                            <?php echo ($attempted_questions == $total_questions) ? 'Retake Exam' : 'Open Exam'; ?>
                        </a>
                    </div>
                    <div class="progress-ring">
                        <svg width="60" height="60">
                            <circle cx="30" cy="30" r="25" stroke="#e6e6e6"/>
                            <circle cx="30" cy="30" r="25" stroke="#007bff"
                                    stroke-dasharray="<?php echo 2 * pi() * 25; ?>"
                                    stroke-dashoffset="<?php echo (1 - $progress_percentage / 100) * 2 * pi() * 25; ?>"/>
                        </svg>
                        <div class="progress-ring-text"><?php echo $attempted_questions . '/' . $total_questions; ?></div>
                    </div>
                </div>
            <?php } ?>
        </div>
    </div>
</body>
</html>

<?php
$stmt->close();
$mysqli->close();
?>
