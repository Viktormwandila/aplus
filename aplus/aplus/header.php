<?php
// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include database configuration file
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Fetch user's first and last name
$user_id = $_SESSION['user_id'];
$query = "SELECT first_name, last_name FROM users WHERE user_id = ?";
$stmt = $mysqli->prepare($query);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$stmt->bind_result($first_name, $last_name);
$stmt->fetch();
$stmt->close();

// Calculate user's initials
$initials = strtoupper($first_name[0] . $last_name[0]);

// Fetch the number of questions answered by the user (default 0)
$query = "SELECT COUNT(*) FROM user_responses WHERE user_id = ?";
$stmt = $mysqli->prepare($query);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$stmt->bind_result($questions_answered);
$stmt->fetch();
$stmt->close();
$questions_answered = $questions_answered ?? 0;  // Default to 0 if null

// Fetch the number of completed exams by the user (default 0)
$query = "SELECT COUNT(*) FROM user_exams WHERE user_id = ? AND status = 'completed'";
$stmt = $mysqli->prepare($query);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$stmt->bind_result($completed_exams);
$stmt->fetch();
$stmt->close();
$completed_exams = $completed_exams ?? 0;  // Default to 0 if null
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <!-- Bootstrap CSS -->
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <style>
        #header-area {
            flex-shrink: 0;
            background-color: #3991cd;
            color: white;
            /*padding: 10px;*/
            text-align: center;
            /*position: fixed;*/
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
        }
        .navbar {
            background-color: #3991cd;
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 20px;
            position:sticky;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1030;
        }
        .navbar a {
            color: white;
        }
        .navbar-brand {
            text-decoration-color: gold;
            color: white;
            font-weight: 700;
        }
        .navbar-brand:hover {
            color: white;
        }
        .header-info {
            color: white;
            display: flex;
            align-items:center;
        }
        .header-info .user-name {
            margin-right: 15px;
            font-weight: 600;
            display: none;
        }
        .header-info span {
            margin-right: 15px;
            font-weight: 600;
        }
        .initials {
            background-color: white;
            color: #3991cd;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            display: flex;
            justify-content: center;
            align-items: center;
            font-weight: 700;
            margin-right: 10px;
        }
        @media (min-width: 768px) {
            .header-info .user-name {
                display: inline;
            }
            .header-info span.qa, .header-info span.ec {
                position: relative;
            }
            .header-info span.qa:hover::after, .header-info span.ec:hover::after {
                content: attr(data-title);
                position: absolute;
                bottom: -25px;
                left: 0;
                background-color: #fff;
                color: #3991cd;
                border-radius: 5px;
                padding: 5px;
                font-size: 12px;
                box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.1);
                white-space: nowrap;
            }
            
            .header-logout {
                margin-right: 15px;
                font-weight: 600;
                align-self: right;
                color: #fff;
            }
        }
        @media (max-width: 767px) {
            .header-info {
                font-size: 14px;
            }
            .header-info span {
                margin-right: 8px;
            }
            .initials {
                width: 30px;
                height: 30px;
                font-size: 14px;
            }
            .header-info .user-name {
                display: none;
            }
            .header-logout {
                color: white;
            }
        }
    </style>
</head>
<body> 
    <div id="header-area">
       <nav class="navbar navbar-expand-lg navbar-dark">
        <a class="navbar-brand" href="index.php"><img src="diagrams/alpha_logo.png" width="45px" height="auto">A-plus</a>
           
        <div class="header-info">
            <div class="initials"><?php echo htmlspecialchars($initials); ?></div>
            <span class="user-name"><?php echo htmlspecialchars($first_name . ' ' . $last_name); ?></span>
            <span class="qa" data-title="Questions Answered">QA: <?php echo $questions_answered; ?></span>
            <span class="ec" data-title="Exams Completed">EC: <?php echo $completed_exams; ?></span>
        </div>
           <a class="header-logout" href="logout.php">Logout</a>
    </nav>
    <!-- Optional: Include popper.js, Bootstrap JS -->
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</div>

</body>
</html>
