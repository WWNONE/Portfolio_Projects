<?php
    require "database.php";
    session_start();
    if (!isset($_SESSION["username"])) {
        header("LOCATION:common.php"); // redirect to the login page if the user is not logged in
        exit();
    }

    $student_username = $_SESSION["username"];
    $courses = courses_enrolled($student_username);
    $_SESSION["student_id"] = get_student_id($student_username);
    $available_courses = get_available_courses($_SESSION["student_id"]);

    if (isset($_POST["logout"])){
        session_destroy();
        header("LOCATION:common.php");
        exit();
    }

    if (isset($_POST["enroll_course_id"])){
        enroll($student_username, $_POST["enroll_course_id"]);
        header("LOCATION:student.php");
    }
?>

<html>
    <body>
        <form action="student.php" method="post">
            <input type="submit" name="logout" value="Logout">
        </form>

        <h1>Welcome, <?php echo $student_username; ?>!</h1>

        <h2>Classes:</h2>
        <ul>
            <?php foreach ($courses as $course): ?>
                <li>
                    <?php 
                        echo $course["course_id"], ": ", $course["name"];
                        $completion_time = get_completion_time($_SESSION["student_id"], $course["course_id"]);

                        if($completion_time != null){ // Completion time not null, display completion time
                            echo "<br> - Completed: ", $completion_time;
                        } else { // Completion time null, show button to take survey
                            ?>
                                    <form action="takeSurvey.php" method="post">
                                        <input type="hidden" name="course_id" value="<?php echo $course["course_id"]; ?>">
                                        <input type="submit" value="Take Survey">
                                    </form>
                            <?php
                                }
                            ?>
                </li>
            <?php endforeach; ?>
        </ul>

        <h2>Register New Courses</h2>
        <form action="student.php" method="post">
            <label for="enroll_course_id">Course ID:</label>
            <select name="enroll_course_id" id="enroll_course_id">
                <?php foreach ($available_courses as $course): ?>
                    <option value="<?php echo $course["course_id"]; ?>"><?php echo $course["course_id"]; ?></option>
                <?php endforeach; ?>
            </select>
            <input type="submit" value="Register">
        </form>

    </body>
</html>
