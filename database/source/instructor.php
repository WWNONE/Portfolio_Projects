<?php
    require "database.php";
    session_start();
    if (!isset($_SESSION["username"])) {
        header("LOCATION:common.php"); // redirect to the login page if the user is not logged in
        exit();
    }

    $instructor_username = $_SESSION["username"];
    $_SESSION["courses"] = courses_taught($instructor_username);
    $courses = $_SESSION["courses"];

    if (isset($_POST["logout"])){
        session_destroy();
        header("LOCATION:common.php");
        exit();
    }
?>

<html>
    <body>
        <form action="instructor.php" method="post">
            <input type="submit" name="logout" value="Logout">
        </form>

        <h1>Welcome, <?php echo $instructor_username; ?>!</h1>

        <h2>Courses you are teaching:</h2>
        <ul>
            <?php foreach ($courses as $course): ?>
                <li>
                    <?php echo $course["course_id"]; ?>
                    <ul>
                        <?php
                            $survey_questions = get_course_survey_questions($course["course_id"]);
                            foreach ($survey_questions as $question): 
                        ?>
                        <li><?php echo $question["question_text"]; ?></li>
                        <?php 
                            endforeach; 
                        ?>
                    </ul>
                </li>
            <?php endforeach; ?>
        </ul>

        <form action="reviewSurvey.php" method="post">
            <label for="course_id">Select a course to review survey problems:</label>
                <select name="course_id" id="course_id">
                    <?php foreach ($courses as $course): ?>
                        <option value="<?php echo $course["course_id"]; ?>"><?php echo $course["course_id"]; ?></option>
                    <?php endforeach; ?>
                </select>
            <input type="submit" value="Review Survey Problems">
        </form>

        <form action="surveyResults.php" method="post">
            <label for="course_id">Select a course to review survey results:</label>
                <select name="course_id" id="course_id">
                    <?php foreach ($courses as $course): ?>
                        <option value="<?php echo $course["course_id"]; ?>"><?php echo $course["course_id"]; ?></option>
                    <?php endforeach; ?>
                </select>
            <input type="submit" value="Check Survey Results">
        </form>
    </body>
</html>