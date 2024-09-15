<?php
session_start();
require_once 'config.php';
require_once 'header.php';

if (!isset($_GET['exam_id']) || !isset($_SESSION['user_id'])) {
    header('Location: error_page.php');
    exit();
}

$exam_id = intval($_GET['exam_id']);
$user_id = intval($_SESSION['user_id']);

// Fetch questions for the exam
$query = "SELECT * FROM questions WHERE exam_id = ?";
$stmt = $mysqli->prepare($query);
$stmt->bind_param('i', $exam_id);
$stmt->execute();
$result = $stmt->get_result();
    
$questions = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$total_questions = count($questions);

$current_question_index = isset($_GET['question']) ? intval($_GET['question']) : 0;
if ($current_question_index >= $total_questions) {
    $current_question_index = $total_questions - 1;
} elseif ($current_question_index < 0) {
    $current_question_index = 0;
}

// Ensure current_question is valid and has an 'id'
$current_question = isset($questions[$current_question_index]) ? $questions[$current_question_index] : null;
$current_question_id = isset($current_question['question_id']) ? $current_question['question_id'] : 0;

$current_question_points = isset($current_question['points']) ? $current_question['points'] : 0;

$user_response_text = '';
$question_answer_text = '';
$question_explanation_text = '';

if ($current_question_id > 0) {
    // Fetch user response for the current question
    $response_query = "SELECT response_text FROM user_responses WHERE exam_id = ? AND question_id = ? AND user_id = ?";
    $response_stmt = $mysqli->prepare($response_query);
    $response_stmt->bind_param('iii', $exam_id, $current_question_id, $user_id);
    $response_stmt->execute();
    $response_result = $response_stmt->get_result();

    $user_response = $response_result->fetch_assoc();
    $response_stmt->close();
    
    ////Collect the answer to the question from here
    
    $query = "SELECT question_text, correct_answer, explanation_text FROM questions WHERE exam_id = ? AND question_id = ?";
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param('ii', $exam_id, $current_question_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $question_data = $result->fetch_assoc();
    $stmt->close();

    // Check if a response exists and assign the response text
    if ($user_response) {
        $user_response_text = htmlspecialchars($user_response['response_text']);
        $question_answer_text = htmlspecialchars($question_data['correct_answer']);
        $question_explanation_text = htmlspecialchars($question_data['explanation_text']);
        $question_text = htmlspecialchars($question_data['question_text']);
        
    }
}

?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Exam - Chat Interface</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        /* Styles */
        body {
            margin: 0;
            padding: 0;
            height: 100vh;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        #question-area {
            flex-shrink: 0;
            padding: 60px 10px 10px 10px;
            background-color: #f8f9fa;
            border-bottom: 1px solid #ddd;
            /*position: fixed;*/
            top: 50px;
            left: 0;
            right: 0;
            z-index: 999;
        }

        .chat-container {
            flex-shrink: 1;
            /*position: fixed;*/
            top: 250px;
            bottom: 60px;
            left: 0;
            right: 0;
            overflow-y: auto;
            background-color: white;
            padding: 10px;
            border-top: 1px solid #ddd;
            border-bottom: 1px solid #ddd;
        }

        #message-area {
            max-height: 100%;
            overflow-y: auto;
            padding: 10px;
        }

        .message {
           
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 5px;
            margin-bottom: 10px;
            border-bottom: 1px solid #e9ecef;
        }

        .message-content {
            flex-grow: 1;
            margin-right: 10px;
            word-wrap: break-word;
            overflow: hidden;
        }
        
        .answer {
           
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 5px;
            margin-bottom: 10px;
            border-bottom: 1px solid #e9ecef;
        }
        
          .explanation {
           
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 5px;
            margin-bottom: 10px;
            border-bottom: 1px solid #e9ecef;
        }

        .message-options button {
            margin-left: 5px;
        }

        .submitted {
            color: green;
            font-weight: bold;
        }

        .input-group {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            padding: 10px;
            background: #f7f7f7;
            border-top: 1px solid #ddd;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        textarea {
            resize: none;
            max-height: 120px;
            overflow-y: auto;
            width: 100%;
            padding-right: 30px;
        }

        #cancel-edit {
            display: none;
            position: absolute;
            top: 5px;
            right: 45px;
            cursor: pointer;
            font-size: 25px;
            color: #007bff;
            z-index: 1000;
        }

        #cancel-edit:hover {
            color: #ff0000;
        }

        .question-navigation {
            margin-top: 20px;
            display: flex;
            justify-content: space-between;
        }

        .question-navigation button {
            width: 48%;
        }
    </style>
</head>

<body>

    <div id="question-area">
        <h3>Question:</h3>
        <p id="question-text"><?php echo htmlspecialchars($current_question['question_text'], ENT_QUOTES, 'UTF-8'); ?></p>
        <div class="question-navigation">
            <button class="btn btn-secondary" <?php if ($current_question_index == 0) echo 'disabled'; ?>
                onclick="navigateQuestion(<?php echo $current_question_index - 1; ?>)">Previous</button>
            <button class="btn btn-primary" <?php if ($current_question_index == $total_questions - 1) echo 'disabled'; ?>
                onclick="navigateQuestion(<?php echo $current_question_index + 1; ?>)">Next</button>
        </div>
    </div>

    <div class="chat-container">
        <div id="message-area">
            <!-- Chat messages will be dynamically loaded here -->
            <?php if ($user_response_text) { ?>
                <div class="message" data-question-id="<?php echo $current_question_id; ?>" data-exam-id="<?php echo $exam_id; ?>" data-user-id="<?php echo $user_id; ?>">
                    <div class="message-content"><?php echo $user_response_text; ?></div>
                    <div class="message-options">
                        <button class="btn btn-danger btn-sm delete-response-btn">Delete</button>
                    </div>
                     
                </div>
            <div class="answer"><?php echo $question_answer_text; ?></div>
            <div class="explanation"><?php echo $question_explanation_text; ?></div>
            <?php } ?>
        </div>
    </div>


    <div class="input-group">
        <span id="cancel-edit">&#8855;</span> <!-- Cross icon for cancelling edit -->
        <textarea class="form-control" id="user-response" rows="1" placeholder="Type your answer here..."></textarea>
        <div class="input-group-append">
            <button class="btn btn-primary" type="button" id="send-button">Send</button>
        </div>
    </div>
    <script>
        function navigateQuestion(questionIndex) {
            window.location.href = `user_exam_old.php?exam_id=<?php echo $exam_id; ?>&question=${questionIndex}`;
        }
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const userInput = document.getElementById('user-response');
            const messageArea = document.getElementById('message-area');
            const cancelEditButton = document.getElementById('cancel-edit');

            document.getElementById('send-button').addEventListener('click', handleSend);

            function handleSend() {
                const messageContent = userInput.value.trim();
                if (messageContent === '') {
                    alert('Please enter a message before sending.');
                    return;
                }

                userInput.disabled = true;
                document.getElementById('send-button').disabled = true;

                const messageDiv = createMessageElement(messageContent);
                messageArea.appendChild(messageDiv);

                userInput.value = '';
                userInput.style.height = 'auto';
                cancelEditButton.style.display = 'none';

                messageArea.scrollTop = 0; // Scrolls to the top of the message area
            }

            function createMessageElement(content) {
                const messageDiv = document.createElement('div');
                messageDiv.className = 'message';

                const contentDiv = document.createElement('div');
                contentDiv.className = 'message-content';
                contentDiv.innerText = content;

                const optionsDiv = document.createElement('div');
                optionsDiv.className = 'message-options';

                const submitButton = document.createElement('button');
                submitButton.className = 'btn btn-success btn-sm';
                submitButton.innerText = 'Submit';
                submitButton.addEventListener('click', function() {
                    console.log('Submit button clicked with content:', content); // Debugging
                    submitResponse(content, contentDiv, optionsDiv, messageDiv);
                });

                const editButton = document.createElement('button');
                editButton.className = 'btn btn-warning btn-sm';
                editButton.innerText = 'Edit';
                editButton.addEventListener('click', function() {
                    editMessage(content, messageDiv);
                });

                const deleteButton = document.createElement('button');
                deleteButton.className = 'btn btn-danger btn-sm';
                deleteButton.innerText = 'Delete';
                deleteButton.addEventListener('click', function() {
                    messageDiv.remove();
                    enableInput();
                });

                optionsDiv.appendChild(submitButton);
                optionsDiv.appendChild(editButton);
                optionsDiv.appendChild(deleteButton);

                messageDiv.appendChild(contentDiv);
                messageDiv.appendChild(optionsDiv);

                return messageDiv;
            }
            
            //typing out animation function
            function typeOutText(text, element, callback) {
                let index = 0;

                function typeCharacter() {
                    if (index < text.length) {
                        element.innerHTML += text.charAt(index);
                        index++;
                        setTimeout(typeCharacter, 50); // Adjust typing speed here (in milliseconds)
                    } else if (callback) {
                        callback();
                    }
                }

                element.innerHTML = ''; // Clear the element content before typing
                typeCharacter();
            }
            

            function submitResponse(content, contentDiv, optionsDiv, messageDiv) {
                console.log('submitResponse function triggered'); // Debugging
                $.ajax({
                    type: 'POST',
                    url: 'submit_response.php',
                    data: {
                        exam_id: '<?php echo addslashes($exam_id); ?>',
                        user_id: '<?php echo addslashes($user_id); ?>',
                        question_id: '<?php echo addslashes($current_question_id); ?>',
                        response_text: content,
                        is_submitted: 1,
                        score: '<?php echo $current_question_points ?>'
                        
                    },
                    dataType: 'json',
                    success: function(response) {
                        console.log('AJAX request succeeded:', response); // Debugging
                        if (response.status === 'success') {
                            contentDiv.classList.add('submitted');
                            contentDiv.innerHTML = `<span>${content}</span>`;
                            
                            // Fetch updated question data
                            fetchQuestionData('<?php echo $current_question_id; ?>');
                            // Keep the optionsDiv visible after submission
                            optionsDiv.style.display = 'flex'; // Ensure the optionsDiv is visible
                            const submitButton = optionsDiv.querySelector('.btn-success');
                            if (submitButton) {
                                submitButton.disabled = true; // Optionally disable submit button
                            }
                        } else {
                            alert(response.message);
                        }
                        enableInput();
                    },
                    error: function(xhr, status, error) {
                        console.error('AJAX request failed:', status, error); // Debugging
                        alert('An error occurred while submitting your response. Please try again.');
                        enableInput();
                    }
                });
            }
            
            ///////fetching data from DB using Ajax
            function fetchQuestionData(questionId) {
                $.ajax({
                    type: 'POST',
                    url: 'fetch_question_data.php',
                    data: {
                        exam_id: '<?php echo addslashes($exam_id); ?>',
                        question_id: questionId
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.status === 'success') {
                            // Display the question text instantly
                            $('#question-text').text(response.question_text);

                            // Clear the message area and display the user response instantly
                            $('#message-area').html(`<div class="message">${response.user_response_text}</div>`);

                            // Create elements for answer and explanation
                            const answerDiv = $('<div class="answer"></div>');
                            const explanationDiv = $('<div class="explanation"></div>');

                            // Append them to the message area
                            $('#message-area').append(answerDiv);
                            $('#message-area').append(explanationDiv);

                            // Start typing animation for the answer and explanation
                            typeText(answerDiv, response.question_answer_text, 0, function() {
                                typeText(explanationDiv, response.question_explanation_text, 0);
                            });
                            
                            userInput.blur();

                        } else {
                            alert('Failed to fetch question data.');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Failed to fetch question data:', status, error);
                        alert('An error occurred while fetching question data. Please try again.');
                    }
                });
            }
            
            
            ////the typing animation
            // Function to type out text with a typing animation
            function typeText(element, text, index, callback) {
                if (index < text.length) {
                    $(element).append(text.charAt(index));
                    setTimeout(function() {
                        typeText(element, text, index + 1, callback);
                    }, 2.66); // Adjust typing speed here (milliseconds per character)
                } else if (callback) {
                    callback();
                }
            }
            
            function editMessage(content, messageDiv) {
                userInput.value = content;
                adjustTextareaHeight();
                cancelEditButton.style.display = 'inline';
                messageDiv.remove();
                enableInput();
            }

            function enableInput() {
                userInput.disabled = false;
                document.getElementById('send-button').disabled = false;
                userInput.focus();
            }

            cancelEditButton.addEventListener('click', function() {
                userInput.value = '';
                userInput.style.height = 'auto';
                userInput.blur();
                cancelEditButton.style.display = 'none';
            });

            userInput.addEventListener('input', adjustTextareaHeight);

            function adjustTextareaHeight() {
                userInput.style.height = 'auto';
                const maxLines = 5;
                const lineHeight = 24;
                userInput.style.height = Math.min(userInput.scrollHeight, maxLines * lineHeight) + 'px';
            }
        });
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.delete-response-btn').forEach(function(button) {
                button.addEventListener('click', function() {
                    // Get the necessary data attributes
                    const messageElement = this.closest('.message');
                    const questionId = messageElement.getAttribute('data-question-id');
                    const examId = messageElement.getAttribute('data-exam-id');
                    const userId = messageElement.getAttribute('data-user-id');

                    // Confirm delete action
                    if (confirm('Are you sure you want to delete this response?')) {
                        // Perform the AJAX request
                        const xhr = new XMLHttpRequest();
                        xhr.open('POST', 'delete_response.php', true);
                        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                        xhr.onreadystatechange = function() {
                            if (xhr.readyState === 4 && xhr.status === 200) {
                                // Remove the message element from the DOM
                                messageElement.remove();
                            }
                        };
                        // Send the data
                        xhr.send('exam_id=' + examId + '&question_id=' + questionId + '&user_id=' + userId);
                    }
                });
            });
        });
    </script>

</body>

</html>