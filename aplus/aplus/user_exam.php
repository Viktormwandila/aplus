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
$emoji ="";

// Fetch exam details for the exam
$query = "SELECT * FROM exams WHERE exam_id = ?";
$stmt = $mysqli->prepare($query);
$stmt->bind_param('i', $exam_id);
$stmt->execute();
$exam_results = $stmt->get_result();
while ($row = $exam_results->fetch_assoc()) {
    $exam_name = htmlspecialchars($row['exam_name']);
    $paper_code = htmlspecialchars($row['paper_code']);
    $date = htmlspecialchars($row['date']);
    $emoji = htmlspecialchars($row['emoji']);
}
$stmt->close();



// Fetch questions for the exam
$query = "SELECT * FROM questions WHERE exam_id = ?";
$stmt = $mysqli->prepare($query);
$stmt->bind_param('i', $exam_id);
$stmt->execute();
$result = $stmt->get_result();
$questions = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$total_questions = count($questions);

// Fetch user responses to determine which questions are submitted
$response_query = "SELECT question_id, is_submitted FROM user_responses WHERE exam_id = ? AND user_id = ?";
$response_stmt = $mysqli->prepare($response_query);
$response_stmt->bind_param('ii', $exam_id, $user_id);
$response_stmt->execute();
$response_result = $response_stmt->get_result();
$user_responses = [];
while ($row = $response_result->fetch_assoc()) {
    $user_responses[$row['question_id']] = $row['is_submitted'];
}
$response_stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Exam - Swipe Navigation</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/swiper/swiper-bundle.min.css" />
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://unpkg.com/swiper/swiper-bundle.min.js"></script>
    <style>
      /* General styles for body and container */
body, html {
    margin: 0;
    padding: 0;
    height: 100%;
    display: flex;
    flex-direction: column;
    overflow: hidden;
}
        
p {
    margin-top: 0;
    margin-bottom: 1rem;
    background-color: #d8f3f3;
    padding: 10px;
    color: #425858;
    border-radius: 20px;
    font-weight: bold;
}

        
.h3, h3 {
    font-size: 17px;
    background: #daeae5;
    width: 100%;
    padding: 10px;
    border-radius: 10px;
    color: #425858;
}

.swiper-container {
    width: 100%;
    flex: 1; /* Allow swiper to take available height */
    overflow: hidden; /* Prevent scrollbars on the swiper container */
}

.swiper-slide {
    display: flex;
    flex-direction: column;
    justify-content: flex-start; /* Align items to start for better layout */
    align-items: start;
    padding-top: 10px;
    padding-left: 38px;
    padding-bottom: 38px;
    padding-right: 38px;
    box-sizing: border-box;
    background-color: #f8f9fa;
    overflow-y: auto; /* Allows content to scroll if it overflows */
}

/* Input group styles */
.input-group {
    position: sticky;
    margin-top: 10px;
    bottom: 0;
    left: 0;
    right: 0;
    padding: 10px;
    background: #f7f7f7;
    border-top: 1px solid #ddd;
    display: flex;
    align-items: center;
    justify-content: space-between;
    z-index: 1030; /* Ensures it's above other elements */
    box-shadow: 0 -1px 5px rgba(0, 0, 0, 0.1); /* Optional shadow for visual separation */
    transform: translateZ(0); /* Force hardware acceleration for rendering */
}
        
.btn-group-sm>.btn, .btn-sm {
    padding: .25rem .5rem;
    font-size: .875rem;
    line-height: 1.5;
    border-radius: 1.2rem;
    margin: 3px;
}
        
.input-group-append{
  margin-top: 5px;
}
        
.btn-primary:hover {
    color: #ffeb7b;
    background-color: #3991cd;
    border-color: #3991cd;
}
        
.btn-primary{
    color: #ffeb7b;
    background-color: #3991cd;
    border-color: #3991cd;
    font-weight:  700;
}
        
/* Textarea styles */
textarea {
    resize: none;
    max-height: 120px;
    overflow-y: auto;
    width: 100%;
    padding-right: 30px;
    box-sizing: border-box; /* Include padding in height calculation */
}


/* Styles for answer and explanation sections */
.answer{
    background: #f1f1f1;
    color: #555857 !important;
    border-radius: 20px 20px 20px 3px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 5px;
    margin-top: 10px;
    margin-bottom: 10px;
    padding: 7px 13px 7px 13px;
    border-bottom: 1px solid #e9ecef;
    display: none; /* Hide by default */
}
        
.explanation {
    font-style: italic;
    background: #f1f1f1;
    color: #8b9091 !important;
    border-radius: 20px 20px 20px 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 5px;
    margin-bottom: 10px;
    padding: 7px 13px 7px 13px;
    border-bottom: 1px solid #e9ecef;
    display: none; /* Hide by default */
}
        
.points{
    color:#5991c1;
}

/* Message styles */
.user-response {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 5px;
    margin-bottom: 10px;
    border-bottom: 1px solid #e9ecef;
    /*display: none; /* Hide by default */
}

.message {
    display: none; /* Hide by default */
    margin-bottom: 10px;
}
        
.message-content {
    background: #42a5f5;
    color: #fff !important;
    border-radius: 20px 20px 3px 20px;
    padding: 7px 13px 7px 13px;
    margin-bottom: 2px;
    border-bottom: 1px solid #e9ecef;
}

/* Question indicators */
.question-indicator {
    display: flex;
    flex-wrap: wrap;
    padding: 10px;
    background-color: #fff;
    justify-content: start;
    gap: 5px;
}

.question-indicator span {
    width: 35px;
    height: 35px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    font-size: 14px;
    color: #fff;
    cursor: pointer;
}

.question-indicator .submitted {
    background-color: #ddd; /* Green for submitted */
}

.question-indicator .not-submitted {
    background-color: #aed6f1; /* Red for not submitted */
}

/* Responsive styles */
@media (max-width: 768px) {
    .swiper-slide {
        padding-top: 10px;
        padding-left: 38px;
        padding-bottom: 38px;
        padding-right: 38px;
        justify-content: flex-start; /* Align items to start for smaller screens */
    }

    .input-group {
        padding: 10px 5px;
        
    }

    textarea {
        max-height: 100px; /* Slightly reduce max height for mobile */
    }

    .question-indicator {
        gap: 3px;
    }

    .question-indicator span {
        width: 20px;
        height: 20px;
        font-size: 12px; /* Adjust size for better fit on mobile */
    }
}
        
    .alpha{
        /*display: flex;*/
        /*flex-direction:row;*/
        margin-top: 10px;
        align-self: end;
        margin-bottom: -10px;
        }  
        .alpha img {
           
        }
    .ask-alpha {
        margin-bottom: 110px;
        background-color: #3991cd;
        padding: 6px;
        color: #ffeb7b;
        border-radius: 20px 20px 0px 20px;
        white-space: nowrap;
        /*overflow: hidden;*/
        border-right: 2px 2px 2px 2px solid;
        }

    .numbering  {
            border-radius: 3px 3px 3px 3px;
            color: #ffeb7b;
            background-color:#328cca;
            border-style: 20px solid;
            padding: 3px;
        }
        
@media (max-width: 480px) {
    .swiper-slide {
        padding-top: 10px;
        padding-left: 38px;
        padding-bottom: 38px;
        padding-right: 38px;
    }

    .input-group {
        padding: 5px;
    }

    .question-indicator span {
        width: 18px;
        height: 18px;
        font-size: 8px; /* Further reduce size for very small screens */
    }
}

/* General styles for swiper arrows */
.swiper-button-next,
.swiper-button-prev {
    width: 30px; /* Default width */
    height: 21%; /* Default height */
    color: #aed6f1;
}

/* Adjust arrow size for screens smaller than 768px */
@media (max-width: 768px) {
    .swiper-button-next,
    .swiper-button-prev {
        width: 20px; /* Reduced width for mobile */
        height: 20px; /* Reduced height for mobile */
    }
}

/* Further adjust arrow size for very small screens (like small phones) */
@media (max-width: 480px) {
    .swiper-button-next,
    .swiper-button-prev {
        width: 10px; /* Further reduced width */
        height: 0px; /* Further reduced height */
    }
}

    

    </style>
</head>

<body>
    <!-- Question Indicators -->
    <div class="question-indicator"  >
        <!-- <h3>/*//<? //echo $exam_name.' Year: '.$date.' Paper Code: '.$paper_code;?>*/</h3> -->
        <h3><? echo $emoji.' '. $exam_name.' '.$date;?></h3>
        <?php foreach ($questions as $index => $question): 
            $question_id = $question['question_id'];
            $is_submitted = isset($user_responses[$question_id]) && $user_responses[$question_id] == 1;
            $status_class = $is_submitted ? 'submitted' : 'not-submitted';
        ?>
            <span class="<?php echo $status_class; ?>" data-slide-index="<?php echo $index; ?>">
                Q<?php echo $index + 1; ?>
            </span>
        <?php endforeach; ?>
    </div>

    <div class="swiper-container">
        <div class="swiper-wrapper">
            <?php foreach ($questions as $index => $question): ?>
                <div class="swiper-slide" data-question-id="<?php echo $question['question_id']; ?>">
                    
                    
                    <?php
                        $current_question_id = $question['question_id'];
                        $current_question_points = $question['points'];
                        $user_response_text = '';
                        $question_answer_text = htmlspecialchars($question['correct_answer']);
                        $question_explanation_text = htmlspecialchars($question['explanation_text']);

                        // Fetch user response for the current question
                        $response_query = "SELECT response_text, is_submitted FROM user_responses WHERE exam_id = ? AND question_id = ? AND user_id = ?";
                        $response_stmt = $mysqli->prepare($response_query);
                        $response_stmt->bind_param('iii', $exam_id, $current_question_id, $user_id);
                        $response_stmt->execute();
                        $response_result = $response_stmt->get_result();
                        $user_response = $response_result->fetch_assoc();
                        $response_stmt->close();

                        $is_submitted = $user_response['is_submitted'] ?? 0;
                        if ($user_response) {
                            $user_response_text = htmlspecialchars($user_response['response_text']);
                        }
                    ?>
                    <p><span class="numbering"><?php echo $index + 1; ?>.</span> <?php echo htmlspecialchars($question['question_text'], ENT_QUOTES, 'UTF-8'); echo ' <span class="points">[ <i>' . $current_question_points .' Points</i> ]</span>';?>
                        
                    <!--This is alphas place where he renders -->
                    <div class="alpha"><span class="ask-alpha">Ask <b>Alpha.</b></span> <img height="100px" src="diagrams/alpha.png" alt="Ask Alpha!"></div>
                    <!-- Display user response, answer, and explanation only if response exists -->
                
                    <div class="message" data-question-id="<?php echo $current_question_id; ?>" data-exam-id="<?php echo $exam_id; ?>" data-user-id="<?php echo $user_id; ?>" 
                         style="<?php echo $user_response ? 'display: block;' : 'display: none;'; ?>">
                        <div class="message-content"><?php echo $user_response_text; ?></div>
                        <div class="message-options" style="<?php echo $is_submitted ? 'display: none;' : 'display: flex;'; ?>">
                            <button class="btn btn-success btn-sm submit-response-btn">Submit</button>
                            <button class="btn btn-warning btn-sm edit-response-btn">Edit</button>
                            <button class="btn btn-danger btn-sm delete-response-btn">Delete</button>
                        </div>
                    </div>
                  
                    <div class="answer" style="<?php echo $is_submitted ? 'display: block;' : 'display: none;'; ?>"><?php echo $question_answer_text; ?></div>
                    <div class="explanation" style="<?php echo $is_submitted ? 'display: block;' : 'display: none;'; ?>"><?php echo $question_explanation_text; ?></div>

                    <div class="input-group" style="<?php echo $is_submitted ? 'display: none;' : 'display: flex; flex-direction: column; align-items: flex-start;'; ?>">
                        <textarea class="form-control user-response" rows="1" style = "height:auto; width:100%;" placeholder="Type your answer here..."><?php echo $is_submitted ? '' : $user_response_text; ?></textarea>
                       <div class="input-group-append">
                            <button class="btn btn-primary send-button" type="button">Answer</button>
                        </div>
                    </div>
                    
                </div>
            <?php endforeach; ?>
        </div>
        <!-- Add Arrows -->
       <div class="swiper-button-prev"></div>
       <div class="swiper-button-next"></div>
        
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const swiper = new Swiper('.swiper-container', {
                direction: 'horizontal',
                loop: false,
                navigation: {
                    nextEl: '.swiper-button-next',
                    prevEl: '.swiper-button-prev',
                },
                pagination: {
                    el: '.swiper-pagination',
                    clickable: true,
                },
                spaceBetween: 20,
                mousewheel: true, // Enable mouse wheel control
                touchRatio: 1, // Enable touch swiping
                touchAngle: 45, // Adjust the angle to make horizontal swipes easier
            });

            // Handle text area height adjustment
            const textareas = document.querySelectorAll('textarea');
            textareas.forEach(function (textarea) {
                textarea.addEventListener('input', function () {
                    adjustTextareaHeight(textarea);
                });
            });

            // Handle click on question indicators to navigate to the respective slide
            document.querySelectorAll('.question-indicator span').forEach(function(indicator) {
                indicator.addEventListener('click', function() {
                    const slideIndex = parseInt(this.getAttribute('data-slide-index'));
                    swiper.slideTo(slideIndex);
                });
            });

            // Adjust textarea height function
            function adjustTextareaHeight(textarea) {
                textarea.style.height = 'auto'; // Reset height
                const maxLines = 5;
                const lineHeight = 24;
                textarea.style.height = Math.min(textarea.scrollHeight, maxLines * lineHeight) + 'px';
            }

            function updateQuestionsAnsweredCount() {
                $.ajax({
                    type: 'GET',
                    url: 'update_question_count.php',
                    dataType: 'json',
                    success: function(response) {
                        if (response.status === 'success') {
                            // Update the Questions Answered count in the header
                            document.querySelector('.qa').textContent = `QA: ${response.questions_answered}`;
                        } else {
                            console.error('Failed to update questions answered count:', response.message);
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Error fetching updated question count:', status, error);
                    }
                });
            }

            // Handle Send button click
            document.querySelectorAll('.send-button').forEach(function (button) {
                button.addEventListener('click', function () {
                    const inputGroup = this.closest('.input-group');
                    const textarea = inputGroup.querySelector('.user-response');
                    const responseText = textarea.value.trim();
                    const swiperSlide = this.closest('.swiper-slide');
                    const questionId = swiperSlide.getAttribute('data-question-id');
                    textarea.style.height = 'auto'; // Reset height
                   
                    if (responseText === '') {
                        alert('Please enter a response before pressing Answer.');
                        return;
                    }
                    
                    
                    const messageArea = swiperSlide.querySelector('.message-content');
                    const answerDiv = swiperSlide.querySelector('.answer');

                    // Show response
                    messageArea.parentElement.style.display = 'block';
                    answerDiv.style.display = 'none';
                    messageArea.innerHTML = responseText;
                    textarea.value = '';
                    
                    const submitButton = swiperSlide.querySelector('.submit-response-btn');
                    if (submitButton) {
                        submitButton.style.display = 'inline-block'; // Show the submit button
                    }
                    const messageOptions = swiperSlide.querySelector('.message-options');
                    messageOptions.style.display = 'flex'; // Show all buttons
                    
                    // Set is_submitted to 0 for non-final submission
                    $.ajax({
                        type: 'POST',
                        url: 'submit_response.php',
                        data: {
                            exam_id: '<?php echo $exam_id; ?>',
                            user_id: '<?php echo $user_id; ?>',
                            question_id: questionId,
                            response_text: responseText,
                            is_submitted: 0,
                            score: '<?php echo $current_question_points; ?>'
                        },
                        dataType: 'json',
                        success: function (response) {
                            if (response.status === 'success') {
                                console.log('Response saved as draft.');

                                //fetchQuestionData(questionId, swiperSlide); // Pass swiperSlide to target correct slide

                                // Update the question indicator color
                                document.querySelector(`.question-indicator span[data-slide-index="${parseInt(swiperSlide.dataset.questionId, 10) - 1}"]`).classList.remove('not-submitted');
                                document.querySelector(`.question-indicator span[data-slide-index="${parseInt(swiperSlide.dataset.questionId, 10) - 1}"]`).classList.add('submitted');
                            } else {
                                alert(response.message);
                            }
                        },
                        error: function (xhr, status, error) {
                            console.error('Failed to save response:', status, error);
                            alert('An error occurred while saving your response. Please try again.');
                        }
                    });
                });
            });

            
            
              // Handle alpha click
            document.querySelectorAll('.ask-alpha').forEach(function (button) {
                button.addEventListener('click', function () {
                    const swiperSlide = this.closest('.swiper-slide');
                    const messageArea = swiperSlide.querySelector('.message-content');
                    const responseText = messageArea.innerHTML;
                    const questionId = swiperSlide.getAttribute('data-question-id');

                    
                    if (!confirm('You are about to see the answer from Alpha.')) {
                        return;
                    }
                    
                    
                    //hide the option buttons
                    const messageOptions = swiperSlide.querySelector('.message-options');
                    messageOptions.style.display = 'none';
                    
                    //we will fetch the answer and explnation from db
                    fetchQuestionData(questionId, swiperSlide);
                    //hide the input text area
                    const inputGroupNone = swiperSlide.querySelector('.input-group');
                    inputGroupNone.style.display = 'none';
                    ///empty the textarea
                    //textarea.value = '';
                    
                    
                    messageArea.scrollTop = messageArea.scrollHeight; 
                });
            });
            
            // Handle Submit button click
            document.querySelectorAll('.submit-response-btn').forEach(function (button) {
                button.addEventListener('click', function () {
                    const swiperSlide = this.closest('.swiper-slide');
                    const messageArea = swiperSlide.querySelector('.message-content');
                    const responseText = messageArea.innerHTML;
                    const questionId = swiperSlide.getAttribute('data-question-id');

                    if (responseText === '') {
                        alert('Please enter a response before submitting.');
                        return;
                    }

                    const textarea = swiperSlide.querySelector('.user-response');
                    if (!textarea) {
                        console.error('No .user-response textarea found in the .swiper-slide.');
                        return;
                    }

                    // Final submission
                    $.ajax({
                        type: 'POST',
                        url: 'submit_response.php',
                        data: {
                            exam_id: '<?php echo $exam_id; ?>',
                            user_id: '<?php echo $user_id; ?>',
                            question_id: questionId,
                            response_text: responseText,
                            is_submitted: 1,
                            score: '<?php echo $current_question_points; ?>'
                        },
                        dataType: 'json',
                        success: function (response) {
                            if (response.status === 'success') {
                                // Hide buttons after submission
                                const messageOptions = swiperSlide.querySelector('.message-options');
                                messageOptions.style.display = 'none';

                                 const inputGroupNone = swiperSlide.querySelector('.input-group');
                                inputGroupNone.style.display = 'none';

                                textarea.value = '';
                                //alert('Response submitted successfully!');
                                fetchQuestionData(questionId, swiperSlide); // Pass swiperSlide to target correct slide
                                updateQuestionsAnsweredCount(); // Update the count after response is submitted

                                // Update the question indicator color
                                document.querySelector(`.question-indicator span[data-slide-index="${parseInt(swiperSlide.dataset.questionId, 10) - 1}"]`).classList.remove('not-submitted');
                                document.querySelector(`.question-indicator span[data-slide-index="${parseInt(swiperSlide.dataset.questionId, 10) - 1}"]`).classList.add('submitted');

                            } else {
                                alert(response.message);
                            }
                        },
                        error: function (xhr, status, error) {
                            console.error('Failed to submit response:', status, error);
                            alert('An error occurred while submitting your response. Please try again.');
                        }
                    });
                    messageArea.scrollTop = messageArea.scrollHeight; 
                });
            });

            // Fetch question data and display it with typing animation
            function fetchQuestionData(questionId, swiperSlide) {
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
                            // Target the specific slide's message area
                            const messageArea = swiperSlide.querySelector('.message-content');
                            const answerDiv = swiperSlide.querySelector('.answer');
                            const explanationDiv = swiperSlide.querySelector('.explanation');

                            // Show and update content with typing animation
                            messageArea.parentElement.style.display = 'block';
                            answerDiv.style.display = 'block';
                            explanationDiv.style.display = 'block';
                            //messageArea.scrollTop = 0; // Scrolls to the top of the message area
                            messageArea.scrollBottom = messageArea.scrollHeight;
                            messageArea.innerHTML = response.user_response_text;
                            answerDiv.innerHTML = '';
                            explanationDiv.innerHTML = '';
                            //typeText($(messageArea), response.user_response_text, 0, function() {
                               
                                typeText($(answerDiv), response.question_answer_text, 0, function() {
                                    typeText($(explanationDiv), response.question_explanation_text, 0, function() {
                                        // Set timeout to redirect to the next question after 2 seconds
                                        /*
                                        setTimeout(function() {
                                            // Check if it's not the last question
                                            if (swiper.activeIndex < swiper.slides.length - 1) {
                                                swiper.slideNext();
                                            } else {
                                                alert('You have reached the last question.');
                                            }
                                        }, 2000); // 2000 milliseconds = 2 seconds
                                        */
                                        
                                    });
                                });
                            
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

            // Function to type out text with a typing animation
            function typeText(element, text, index, callback) {
                if (index < text.length) {
                    $(element).append(text.charAt(index));
                    setTimeout(function() {
                        typeText(element, text, index + 1, callback);
                    }, 3); // Adjust typing speed here (milliseconds per character)
                } else if (callback) {
                    callback();
                }
            }

            // Handle Edit button click
            document.querySelectorAll('.edit-response-btn').forEach(function (button) {
                button.addEventListener('click', function () {
                    const swiperSlide = this.closest('.swiper-slide');
                    const messageArea = swiperSlide.querySelector('.message-content');
                    const textarea = swiperSlide.querySelector('.user-response');

                    textarea.value = messageArea.innerHTML; // Fill textarea with existing response
                    adjustTextareaHeight(textarea);
                });
            });

            // Handle Delete button click
            document.querySelectorAll('.delete-response-btn').forEach(function (button) {
                button.addEventListener('click', function () {
                    const swiperSlide = this.closest('.swiper-slide');
                    const messageElement = this.closest('.message');
                    const questionId = messageElement.getAttribute('data-question-id');
                    const examId = messageElement.getAttribute('data-exam-id');
                    const userId = messageElement.getAttribute('data-user-id');

                    if (confirm('Are you sure you want to delete this response?')) {
                        $.ajax({
                            type: 'POST',
                            url: 'delete_response.php',
                            data: {
                                exam_id: examId,
                                question_id: questionId,
                                user_id: userId
                            },
                            dataType: 'json',
                            success: function (response) {
                                if (response.success) {
                                    messageElement.style.display = 'none';
                                    swiperSlide.querySelector('.answer').style.display = 'none';
                                    swiperSlide.querySelector('.explanation').style.display = 'none';
                                    swiperSlide.querySelector('.user-response').value = ''; // Clear input box
                                    //alert('Response deleted successfully!');
                                    updateQuestionsAnsweredCount(); // Update count after deletion

                                    // Update question indicator
                                    document.querySelector(`.question-indicator span[data-slide-index="${parseInt(swiperSlide.dataset.questionId, 10) - 1}"]`)
                                        .classList.remove('submitted');
                                    document.querySelector(`.question-indicator span[data-slide-index="${parseInt(swiperSlide.dataset.questionId, 10) - 1}"]`)
                                        .classList.add('not-submitted');
                                } else {
                                    alert('Failed to delete response.');
                                }
                            },
                            error: function (xhr, status, error) {
                                console.error('Failed to delete response:', status, error);
                                alert('An error occurred while deleting the response. Please try again.');
                            }
                        });
                    }
                });
            });
        });
    </script>
</body>
</html>
