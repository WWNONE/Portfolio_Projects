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

    // Add question
    if (isset($_POST["add_question"])) {
        $category = $_POST["question_category"];
        $display = $_POST["question_display"];
        $text = $_POST["question_text"];
        $type = "multiple-choice"; // Set default question type
        $dept_name = $dept;
        $course_id = $_POST["course_id"];
    
        add_question($category, $display, $text, $type, $dept_name, $course_id);
        header("Location: reviewSurvey.php?course_id=" . $course_id);
    }

    // Add question choice
    if (isset($_POST["add_choice"])) {
        $category = $_POST["question_category"];
        $display = $_POST["question_display"];
        $choice_display = $_POST["choice_display"];
        $choice_text = $_POST["choice_text"];
        $dept_name = $dept;
        $course_id = $_POST["course_id"];
    
        add_question_choice($category, $display, $choice_display, $choice_text, $dept_name, $course_id);
        header("Location: reviewSurvey.php?course_id=" . $course_id);
    }

    // Delete question
    if (isset($_POST["delete_question"])) {
        $category = $_POST["question_category"];
        $display = $_POST["question_display"];
        $dept_name = $dept;
        $course_id = $_POST["course_id"];
    
        delete_question($category, $display, $dept_name, $course_id);
        header("Location: reviewSurvey.php?course_id=" . $course_id);
    }

    // Delete question choice
    if (isset($_POST["delete_choice"])) {
        $category = $_POST["question_category"];
        $display = $_POST["question_display"];
        $choice_display = $_POST["choice_display"];
        $dept_name = $dept;
        $course_id = $_POST["course_id"];
    
        delete_question_choice($category, $display, $choice_display, $dept_name, $course_id);
        header("Location: reviewSurvey.php?course_id=" . $course_id);
    }
?>

<html>
    <body>
        <!-- CONTROL BUTTONS -->
        <form action="instructor.php" method="post">
            <input type="submit" name="logout" value="Logout">
        </form>
        <form action="instructor.php" method="post">
                <input type="submit" name="go_home" value="Go home">
        </form>

        <!-- DISPLAY QUESTIONS -->
        <h2><?php echo $course ?> Survey</h2>

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
                        echo "<li>", $choice["choice_display"], ". ",  $choice["choice_text"], "</li>";
                    }
                    echo "</ul>";
                    echo "</li>";
                }
            }
            echo "</ul>";
        }
        ?>

        <!-- ADD QUESTION FORM -->
        <h3>Add Question</h3>
        <form action="reviewSurvey.php" method="post">
            <input type="hidden" name="course_id" value="<?php echo $course; ?>">
            <label for="question_category">Category:</label>
            <input type="text" name="question_category" required>
            <label for="question_display">Question:</label>
            <input type="text" name="question_display" required>
            <label for="question_text">Question Text:</label>
            <input type="text" name="question_text" required><br><br>
            <input type="submit" name="add_question" value="Add Question">
        </form>

        <!-- ADD QUESTION CHOICE FORM -->
        <h3>Add Choice</h3>
        <form action="reviewSurvey.php" method="post">
            <input type="hidden" name="course_id" value="<?php echo $course; ?>">
            <label for="question_category">Category:</label>
            <input type="text" name="question_category" required>
            <label for="question_display">Question:</label>
            <input type="text" name="question_display" required>
            <label for="question_text">Option:</label>
            <input type="text" name="choice_display" required>
            <label for="question_text">Option Text:</label>
            <input type="text" name="choice_text" required><br><br>
            <input type="submit" name="add_choice" value="Add Choice">
        </form>

        <!-- DELETE QUESTION FORM -->
        <h3>Delete Question</h3>
        <form action="reviewSurvey.php" method="post">
            <input type="hidden" name="course_id" value="<?php echo $course; ?>">
            <label for="question_category">Category:</label>
            <input type="text" name="question_category" required>
            <label for="question_display">Question:</label>
            <input type="text" name="question_display" required><br><br>
            <input type="submit" name="delete_question" value="Delete Question">
        </form>

        <!-- DELETE QUESTION CHOICE FORM -->
        <h3>Delete Choice</h3>
        <form action="reviewSurvey.php" method="post">
            <input type="hidden" name="course_id" value="<?php echo $course; ?>">
            <label for="question_category">Category:</label>
            <input type="text" name="question_category" required>
            <label for="question_display">Question:</label>
            <input type="text" name="question_display" required>
            <label for="question_text">Option:</label>
            <input type="text" name="choice_display" required><br><br>
            <input type="submit" name="delete_choice" value="Delete Choice">
        </form>
    </body>
</html>