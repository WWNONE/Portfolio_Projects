<?php
    // Checks validity
    require "database.php";
    session_start();
    if (!isset($_SESSION["username"])) {
        header("LOCATION:common.php"); // redirect to the login page if the user is not logged in
        exit();
    }
    if (!isset($_POST["course_id"])) {
        header("LOCATION:instructor.php"); // Redirect back to instructor.php if no course_id is set
        exit();
    }

    $instructor_username = $_SESSION["username"];
    $course = $_POST["course_id"];
    $dept = get_department($course);
    $selected_course_id = $_POST["course_id"];
    $survey_questions = get_course_survey_questions($course);

    // Form checks
    if (isset($_POST["go_home"])){
        header("LOCATION:instructor.php");
        exit();
    }
?>

<html>
    <body>
        <!-- LOGOUT/GO_HOME BUTTONS -->
        <form action="instructor.php" method="post">
            <input type="submit" name="logout" value="Logout">
        </form>
        <form action="instructor.php" method="post">
            <input type="submit" name="go_home" value="Go home">
        </form>

        <!-- DISPLAY RESPONSE RATES -->
        <h2><?php echo $course ?> Response Rates</h2>
        <?php $overall_response_rate = get_completion_rate($course); ?>
        <h3>Completion Rate: <?php echo $overall_response_rate["numerator"], "/", $overall_response_rate["denomenator"]; ?><h3>
        <?php
            $categories = array('university' => 'University Questions', 'department' => 'Department Questions', 'course' => 'Course Questions');
            foreach ($categories as $category => $category_title) {
                echo "<h3>$category_title:</h3>";
                echo "<ul>";
                foreach ($survey_questions as $question) {
                    if ($question["question_category"] == $category) {
                        echo "<li><b>", $question["question_display"], ". ", $question["question_text"], "</b>";
                        echo "<ul>";
                        $question_response_rates = get_question_response_rates($question["question_category"], $question["question_display"], $dept, $course);
                        foreach ($question_response_rates as $rate) {
                            echo "<li>", $rate['Response Option'], ": \t", $rate['Frequency'], "\t (", $rate['Percent'], '%)', "</li>";
                        }
                        echo "</ul>";
                        echo "</li>";
                    }
                }
                echo "</ul>";
            }
            echo "<br><br>";
        ?>
        <!-- INDIVIDUAL SURVEY RESPONSES -->
        <h2><?php echo $course ?> Individual Survey Responses</h2>

        <?php
            $count = 0;
            $survey_ids = get_course_survey_ids($dept, $course);

            foreach ($survey_ids as $survey) {
                $count = $count + 1;
                $survey_instance_id = $survey['survey_instance_id'];
                echo "<h2>Survey ", $count, "</h2>";
                
                $categories = array('university' => 'University Questions', 'department' => 'Department Questions', 'course' => 'Course Questions');
                foreach ($categories as $category => $category_title) {
                    echo "<h4>$category_title</h4>";
                    echo "<ul>";
                    
                    $responses = get_completed_survey_responses($survey_instance_id, $dept, $course);
                    foreach ($responses as $response) {
                        if ($response["question_category"] == $category) {
                            echo "<li><b>", $response["question_display"], ": ", $response["question_text"], "</b>";
                            echo "<br>Answer: ", $response["choice_text"], "</li>";
                        }
                    }
                    
                    echo "<br></ul>";
                }
            }
        ?>
    </body>
</html>