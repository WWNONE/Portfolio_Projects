<?php
    // Checks validity
    require "database.php";
    session_start();
    if (!isset($_SESSION["username"])) {
        header("LOCATION:common.php"); // redirect to the login page if the user is not logged in
        exit();
    }
    if (!isset($_POST["course_id"])) {
        header("LOCATION:student.php"); // Redirect back to instructor.php if no course_id is set
        exit();
    }

    $student_username = $_SESSION["username"];
    $course = $_POST["course_id"];
    $dept = get_department($course);
    $selected_course_id = $_POST["course_id"];
    $survey_questions = get_course_survey_questions($course);

    // Form checks
    if (isset($_POST["go_home"])){
        header("LOCATION:student.php");
        exit();
    }

    if (isset($_POST["submit_survey"])) {
        $survey_instance_id = generate_survey_instance($_SESSION["student_id"], $_POST["course_id"]);
    
        foreach ($_POST as $key => $value) {
            if ($key !== "course_id" && $key !== "submit_survey") {
                list($question_category, $question_display) = explode('_', $key);
                $question = get_question_by_display($question_category, $question_display, $dept, $_POST["course_id"]);
                insert_response($survey_instance_id, $question["question_category"], $question["question_display"], $value, $dept, $_POST["course_id"]);
            }
        }
        set_completion_time($_SESSION["student_id"], $_POST["course_id"]);
        header("LOCATION:student.php");
    }
?>

<html>
    <body>
        <!-- CONTROL BUTTONS -->
        <form action="student.php" method="post">
            <input type="submit" name="logout" value="Logout">
        </form>
        <form action="student.php" method="post">
                <input type="submit" name="go_home" value="Go home">
        </form>

        <!-- QUESTIONS TO ANSWER -->
        <form action="takeSurvey.php" method="post">
            <input type="hidden" name="course_id" value="<?php echo $course; ?>">
            <?php
            $categories = array('university' => 'University Questions', 'department' => 'Department Questions', 'course' => 'Course Questions');
            foreach ($categories as $category => $category_title) {
                echo "<h3>$category_title:</h3>";
                echo "<ul>";
                foreach ($survey_questions as $question) {
                    if ($question["question_category"] == $category) {
                        echo "<li><b>", $question["question_display"], ". ", $question["question_text"], "</b>";
                        echo "<ul>";
                        $question_choices = get_question_choices($question["question_category"], $question["question_display"], $dept, $course);
                        foreach ($question_choices as $choice) {
                            $choice_id = $choice["choice_display"];
                            echo "<li>";
                            echo "<input type='radio' id='{$choice_id}' name='{$question["question_category"]}_{$question["question_display"]}' value='{$choice_id}'>";
                            echo "<label for='{$choice_id}'>", $choice["choice_display"], ". ", $choice["choice_text"], "</label>";
                            echo "</li>";
                        }
                        echo "</ul>";
                        echo "</li>";
                    }
                }
                echo "</ul>";
            }
            ?>
            <input type="submit" name="submit_survey" value="Submit">
        </form>
    </body>
</html>