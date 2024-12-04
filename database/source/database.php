<?php
    // connects to database
    function connectDB()
    {
        $config = parse_ini_file("/local/my_web_files/tpneal/classdb/database.ini");
        $dbh = new PDO($config['dsn'], $config['username'], $config['password']);
        $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $dbh;
    }

    // returns number of rows matching the given user and passwd.
    function authenticate($user, $passwd) 
    {
        try {
            $dbh = connectDB();
            $statement = $dbh->prepare("SELECT count(*) FROM mtu_account "."where username = :username and password = sha2(:passwd,256) ");
            $statement->bindParam(":username", $user);
            $statement->bindParam(":passwd", $passwd);
            $result = $statement->execute();
            $row=$statement->fetch();
            $dbh=null;
            return $row[0];
        }catch (PDOException $e) {
            print "Error: could not connect to database" . $e->getMessage() . "<br/>";
        die();
        }
    }

    // returns account_type associated with user
    function get_account_type($user)
    {
        try {
            $dbh = connectDB();
            $statement = $dbh->prepare("SELECT account_type FROM mtu_account WHERE username = :username");
            $statement->bindParam(":username", $user);
            $result = $statement->execute();
            $row = $statement->fetch();
            $dbh = null;
            return $row['account_type'];
        } catch (PDOException $e) {
            print "Error: could not retrieve account_type" . $e->getMessage() . "<br/>";
            die();
        }
    }

    // returns account_type associated with user
    function get_student_id($username)
    {
        try {
            $dbh = connectDB();
            $statement = $dbh->prepare("SELECT student_id FROM mtu_account WHERE username = :username");
            $statement->bindParam(":username", $username);
            $result = $statement->execute();
            $id = $statement->fetch();
            $dbh = null;
            return $id['student_id'];
        } catch (PDOException $e) {
            print "Error: could not retrieve student id" . $e->getMessage() . "<br/>";
            die();
        }
    }

    // returns if password has been reset
    function check_password_reset($user)
    {
        try {
            $dbh = connectDB();
            $statement = $dbh->prepare("SELECT password_reset FROM mtu_account WHERE username = :username");
            $statement->bindParam(":username", $user);
            $result = $statement->execute();
            $row = $statement->fetch();
            $dbh = null;
            return $row['password_reset'];
        } catch (PDOException $e) {
            print "Error: could not retrieve password_reset" . $e->getMessage() . "<br/>";
            die();
        }
    }

    function update_password($user, $new_password)
    {
        try {
            $dbh = connectDB();
            $statement = $dbh->prepare("UPDATE mtu_account SET password = sha2(:new_password, 256), password_reset = 1 WHERE username = :username");
            $statement->bindParam(":username", $user);
            $statement->bindParam(":new_password", $new_password);
            $result = $statement->execute();
            $dbh = null;
            return $result;
        } catch (PDOException $e) {
            print "Error: could not update password" . $e->getMessage() . "<br/>";
            die();
        }
    }

    function courses_taught($instructor_username)
    {
        try{
            $dbh = connectDB();
            $statement = $dbh->prepare("SELECT course_id FROM teaches t JOIN mtu_account a ON t.instructor_id = a.instructor_id WHERE a.username = :username");
            $statement->bindParam(":username", $instructor_username);
            $statement->execute();
            $courses = $statement->fetchAll();
            $dbh = null;
            return $courses;
        } catch (PDOException $e) {
            print "Error: could not retireve courses taught" . $e->getMessage() . "<br/>";
            die();
        }
    }

    function enroll($student_username, $course_id)
    {
        try {
            $dbh = connectDB();
            $dbh->beginTransaction(); // Start the transaction
    
            // Prepare and execute the statement to retrieve student_id
            $id_stmt = $dbh->prepare("SELECT student_id FROM mtu_account WHERE username = :username");
            $id_stmt->bindParam(":username", $student_username);
            $id_stmt->execute();
            $student_id = $id_stmt->fetch();
    
            // Prepare and execute the statement to enroll student  
            $statement = $dbh->prepare("CALL enroll_student(:student_id, :course_id)");
            $statement->bindParam(":course_id", $course_id);
            $statement->bindParam(":student_id", $student_id["student_id"]);
            $statement->execute();
            $questions = $statement->fetchAll();
    
            $dbh->commit(); // Commit the transaction
            $dbh = null;
            return $questions;
        } catch (PDOException $e) {
            $dbh->rollBack(); // Rollback the transaction in case of an error
            print "Error: could not enroll in course" . $e->getMessage() . "<br/>";
            die();
        }
    }

    function courses_enrolled($instructor_username)
    {
        try{
            $dbh = connectDB();
            $statement = $dbh->prepare("SELECT * FROM takes t JOIN mtu_account a ON t.student_id = a.student_id JOIN course c ON c.course_id = t.course_id WHERE a.username = :username");
            $statement->bindParam(":username", $instructor_username);
            $statement->execute();
            $courses = $statement->fetchAll();
            $dbh = null;
            return $courses;
        } catch (PDOException $e) {
            print "Error: could not retireve courses enrolled" . $e->getMessage() . "<br/>";
            die();
        }
    }

    function get_completion_time($student_id, $course_id)
    {
        try{
            $dbh = connectDB();
            $statement = $dbh->prepare("SELECT survey_completion_time FROM takes t JOIN mtu_account a ON t.student_id = a.student_id WHERE t.student_id = :student_id AND t.course_id = :course_id");
            $statement->bindParam(":student_id", $student_id);
            $statement->bindParam(":course_id", $course_id);
            $statement->execute();
            $completion_time = $statement->fetch();
            $dbh = null;
            return $completion_time['survey_completion_time'];
        } catch (PDOException $e) {
            print "Error: could not retireve survey completion time" . $e->getMessage() . "<br/>";
            die();
        }
    }

    function set_completion_time($student_id, $course_id)
    {
        try{
            $dbh = connectDB();
            $statement = $dbh->prepare("UPDATE takes SET survey_completion_time = current_timestamp() WHERE student_id = :student_id AND course_id = :course_id;");
            $statement->bindParam(":student_id", $student_id);
            $statement->bindParam(":course_id", $course_id);
            $statement->execute();
            $dbh = null;
            return;
        } catch (PDOException $e) {
            print "Error: could not set survey completion time" . $e->getMessage() . "<br/>";
            die();
        }
    }

    function get_department($course_id)
    {
        try{
            $dbh = connectDB();
            $statement = $dbh->prepare("SELECT dept_name FROM course WHERE course_id = :course_id");
            $statement->bindParam(":course_id", $course_id);
            $result = $statement->execute();
            $dept_name = $statement->fetch();
            $dbh = null;
            return $dept_name['dept_name'];
        } catch (PDOException $e) {
            print "Error: could not retrieve department name" . $e->getMessage() . "<br/>";
            die();
        }
    }

    function get_available_courses($student_id)
    {
        try{
            $dbh = connectDB();
            $statement = $dbh->prepare("SELECT distinct course_id FROM takes WHERE course_id not in (SELECT course_id FROM takes WHERE student_id = :student_id)");
            $statement->bindParam(":student_id", $student_id);
            $result = $statement->execute();
            $available_courses = $statement->fetchAll();
            $dbh = null;
            return $available_courses;
        } catch (PDOException $e) {
            print "Error: could not retrieve available courses" . $e->getMessage() . "<br/>";
            die();
        }
    }

    function insert_response($survey_instance_id, $question_category, $question_display, $choice_display, $dept_name, $course_id)
    {
        try{
            $dbh = connectDB();
            $key_check_off = $dbh->prepare("SET foreign_key_checks = 0");
            $key_check_off->execute();
            $statement = $dbh->prepare("INSERT INTO response (
                survey_instance_id,
                question_category,
                question_display,
                choice_display,
                dept_name,
                course_id
            ) VALUES (
                :survey_instance_id,
                :question_category,
                :question_display,
                :choice_display,
                :dept_name,
                :course_id
            );");
            $statement->bindParam(":survey_instance_id", $survey_instance_id);
            $statement->bindParam(":question_category", $question_category);
            $statement->bindParam(":question_display", $question_display);
            $statement->bindParam(":choice_display", $choice_display);
            $statement->bindParam(":course_id", $course_id);
            $statement->bindParam(":dept_name", $dept_name);
            $statement->execute();
            $key_check_on = $dbh->prepare("SET foreign_key_checks = 1");
            $key_check_on->execute();

            $dbh = null;
            return;
        } catch (PDOException $e) {
            print "Error: could not insert response" . $e->getMessage() . "<br/>";
            die();
        }
    }

    function generate_survey_instance()
    {
        try{
            $dbh = connectDB();
            $dbh->beginTransaction(); // Start the transaction

            // GENERATE SURVEY NUMBER
            $statement = $dbh->prepare("SELECT (MAX(survey_instance_id)+1) as id FROM survey_instance;");
            $statement->execute();
            $instance_id = $statement->fetch();

            // INSERT SURVEY INSTANCE INTO TABLE
            $statement2 = $dbh->prepare("INSERT into survey_instance(survey_instance_id) VALUES (:id)");
            $statement2->bindParam(":id", $instance_id["id"]);
            $statement2->execute();
            
            $dbh->commit(); // Commit the transaction
            $dbh = null;
            return $instance_id["id"];
        } catch (PDOException $e) {
            $dbh->rollBack(); // Rollback the transaction in case of an error
            print "Error: could not retireve survey instance" . $e->getMessage() . "<br/>";
            die();
        }
    }

    function get_course_survey_questions($course_id)
    {
    try {
        $dbh = connectDB();
        $dbh->beginTransaction(); // Start the transaction

        // Prepare and execute the statement to retrieve dept_name
        $dept_stmt = $dbh->prepare("SELECT dept_name FROM course WHERE course_id = :course_id");
        $dept_stmt->bindParam(":course_id", $course_id);
        $dept_stmt->execute();
        $dept_row = $dept_stmt->fetch();
        $dept_name = $dept_row['dept_name'];

        // Prepare and execute the statement to retrieve course survey questions
        $statement = $dbh->prepare("SELECT * FROM question m WHERE (dept_name is null AND course_id is null) OR dept_name = :dept_name OR course_id = :course_id");
        $statement->bindParam(":course_id", $course_id);
        $statement->bindParam(":dept_name", $dept_name);
        $statement->execute();
        $questions = $statement->fetchAll();

        $dbh->commit(); // Commit the transaction
        $dbh = null;
        return $questions;
    } catch (PDOException $e) {
        $dbh->rollBack(); // Rollback the transaction in case of an error
        print "Error: could not retrieve course survey questions" . $e->getMessage() . "<br/>";
        die();
    }
    }

    function get_question_choices($question_category, $question_display, $dept_name, $course_id)
    {
        try {
            $dbh = connectDB();
            $statement = $dbh->prepare
            ("SELECT * 
            FROM multiple_choice 
            WHERE question_category = :question_category 
                AND question_display = :question_display
                AND ((dept_name is null AND course_id is null) OR dept_name = :dept_name OR course_id = :course_id)");
            $statement->bindParam(":question_category", $question_category);
            $statement->bindParam(":question_display", $question_display);
            $statement->bindParam(":course_id", $course_id);
            $statement->bindParam(":dept_name", $dept_name);
            $statement->execute();
            $choices = $statement->fetchAll();
            $dbh = null;
            return $choices;
        } catch (PDOException $e) {
            print "Error: could not retireve question choices" . $e->getMessage() . "<br/>";
            die();
        }
    }

    function get_question_by_display($question_category, $question_display, $dept_name, $course_id) {
        {
            try {
                $dbh = connectDB();
                $statement = $dbh->prepare
                ("SELECT * 
                FROM question 
                WHERE question_category = :question_category 
                    AND question_display = :question_display
                    AND ((dept_name is null AND course_id is null) OR dept_name = :dept_name OR course_id = :course_id)");
                $statement->bindParam(":question_category", $question_category);
                $statement->bindParam(":question_display", $question_display);
                $statement->bindParam(":course_id", $course_id);
                $statement->bindParam(":dept_name", $dept_name);
                $statement->execute();
                $question = $statement->fetch();
                $dbh = null;
                return $question;
            } catch (PDOException $e) {
                print "Error: could not retireve question" . $e->getMessage() . "<br/>";
                die();
            }
        }
    }

    function get_question_response_rates($question_category, $question_display, $dept_name, $course_id)
    {
        try{
            $dbh = connectDB();
            $statement = $dbh->prepare("SELECT
                m.choice_text AS 'Response Option',
                COUNT(r.choice_display) AS 'Frequency',
                ROUND(COUNT(r.choice_display) / COALESCE((SELECT COUNT(*) FROM takes WHERE course_id = :course_id), 1) * 100, 2) AS 'Percent'
            FROM
                multiple_choice m
            LEFT JOIN
                response r
                ON r.question_category = m.question_category
                AND r.question_display = m.question_display
                AND r.choice_display = m.choice_display
                AND r.dept_name = :dept_name
                AND r.course_id = :course_id
                AND r.survey_instance_id IS NOT NULL
            WHERE
                m.question_category = :question_category
                AND m.question_display = :question_display
                AND ((m.dept_name is null AND m.course_id is null) OR m.dept_name = :dept_name OR m.course_id = :course_id)
            GROUP BY m.choice_text;");
            $statement->bindParam(":question_category", $question_category);
            $statement->bindParam(":question_display", $question_display);
            $statement->bindParam(":course_id", $course_id);
            $statement->bindParam(":dept_name", $dept_name);
            $statement->execute();
            $response_rates = $statement->fetchAll();
            $dbh = null;
            return $response_rates;
        } catch (PDOException $e) {
            print "Error: could not retireve question response rates" . $e->getMessage() . "<br/>";
            die();
        }
    }

    function get_course_survey_ids($dept_name, $course_id)
    {
        try{
            $dbh = connectDB();
            $statement = $dbh->prepare("SELECT distinct survey_instance_id FROM response WHERE dept_name = :dept_name AND course_id = :course_id;");
            $statement->bindParam(":course_id", $course_id);
            $statement->bindParam(":dept_name", $dept_name);
            $statement->execute();
            $survey_ids = $statement->fetchAll();
            $dbh = null;
            return $survey_ids;
        } catch (PDOException $e) {
            print "Error: could not retireve course survey ids" . $e->getMessage() . "<br/>";
            die();
        }
    }


    function get_completion_rate($course_id){
        try{
            $dbh = connectDB();
            $statement = $dbh->prepare("SELECT (SELECT count(*) FROM takes t JOIN mtu_account a ON t.student_id = a.student_id WHERE course_id = :course_id AND survey_completion_time is not null) as numerator,
            (SELECT count(*) FROM takes t JOIN mtu_account a ON t.student_id = a.student_id WHERE course_id = :course_id) as denomenator");
            $statement->bindParam(":course_id", $course_id);
            $statement->execute();
            $completion_rate = $statement->fetch();
            $dbh = null;
            return $completion_rate;
        } catch (PDOException $e) {
            print "Error: could not retireve course completion rate" . $e->getMessage() . "<br/>";
            die();
        }
    }

    function get_completed_survey_responses($survey_instance_id, $dept_name, $course_id)
    {
        try{
            $dbh = connectDB();
            $statement = $dbh->prepare("SELECT  
            r.response_id, r.survey_instance_id, r.question_category, 
            r.question_display, q.question_text, r.choice_display, 
            m.choice_text, r.response_text, r.dept_name, r.course_id
            FROM question q
            LEFT JOIN
                multiple_choice m
                ON q.question_category = m.question_category
                AND q.question_display = m.question_display
                AND (q.course_id = m.course_id OR q.dept_name = m.dept_name OR (q.course_id is null AND q.dept_name is null))
            LEFT JOIN
                response r
                ON r.question_category = m.question_category
                AND r.question_display = m.question_display
                AND r.choice_display = m.choice_display
                AND r.dept_name = :dept_name
                AND r.course_id = :course_id
                AND r.survey_instance_id IS NOT NULL
                AND (m.dept_name = :dept_name OR m.course_id = :course_id OR (m.dept_name is null AND m.course_id is null))
            WHERE
                r.survey_instance_id = :survey_instance_id
            ORDER BY survey_instance_id, m.question_category desc");
            $statement->bindParam(":course_id", $course_id);
            $statement->bindParam(":dept_name", $dept_name);
            $statement->bindParam(":survey_instance_id", $survey_instance_id);
            $statement->execute();
            $responses = $statement->fetchAll();
            $dbh = null;
            return $responses;
        } catch (PDOException $e) {
            print "Error: could not retireve survey responses" . $e->getMessage() . "<br/>";
            die();
        }
    }

    function add_question($category, $question_display, $text, $type, $dept_name, $course_id)
    {
        try {
            $dbh = connectDB();
            $statement = $dbh->prepare("CALL add_question(:category, :question_display, :text, :type, :dept_name, :course_id)");
            $statement->bindParam(":category", $category);
            $statement->bindParam(":question_display", $question_display);
            $statement->bindParam(":text", $text);
            $statement->bindParam(":type", $type);
            $statement->bindParam(":dept_name", $dept_name);
            $statement->bindParam(":course_id", $course_id);
            $statement->execute();
            $dbh = null;
        } catch (PDOException $e) {
            print "Error: could not add question" . $e->getMessage() . "<br/>";
            die();
        }
    }

    function add_question_choice($category, $question_display, $choice_display, $choice_text, $dept_name, $course_id)
    {
        try {
            $dbh = connectDB();
            $statement = $dbh->prepare("CALL add_question_choice(:category, :question_display, :choice_display, :choice_text, :dept_name, :course_id)");
            $statement->bindParam(":category", $category);
            $statement->bindParam(":question_display", $question_display);
            $statement->bindParam(":choice_display", $choice_display);
            $statement->bindParam(":choice_text", $choice_text);
            $statement->bindParam(":dept_name", $dept_name);
            $statement->bindParam(":course_id", $course_id);
            $statement->execute();
            $dbh = null;
        } catch (PDOException $e) {
            print "Error: could not add question choice" . $e->getMessage() . "<br/>";
            die();
        }
    }

    function delete_question($category, $question_display, $dept_name, $course_id)
    {
        try {
            $dbh = connectDB();
            $statement = $dbh->prepare("DELETE FROM question WHERE question_category = :category AND question_display = :question_display AND ((dept_name is null AND course_id is null) OR dept_name = :dept_name OR course_id = :course_id)");
            $statement->bindParam(":category", $category);
            $statement->bindParam(":question_display", $question_display);
            $statement->bindParam(":dept_name", $dept_name);
            $statement->bindParam(":course_id", $course_id);
            $statement->execute();
            $dbh = null;
        } catch (PDOException $e) {
            print "Error: could not delete question" . $e->getMessage() . "<br/>";
            die();
        }
    }

    function delete_question_choice($category, $question_display, $choice_display, $dept_name, $course_id)
    {
        try {
            $dbh = connectDB();
            $statement = $dbh->prepare("DELETE FROM multiple_choice WHERE question_category = :category AND question_display = :question_display AND choice_display = :choice_display AND ((dept_name is null AND course_id is null) OR dept_name = :dept_name OR course_id = :course_id)");
            $statement->bindParam(":category", $category);
            $statement->bindParam(":question_display", $question_display);
            $statement->bindParam(":choice_display", $choice_display);
            $statement->bindParam(":dept_name", $dept_name);
            $statement->bindParam(":course_id", $course_id);
            $statement->execute();
            $dbh = null;
        } catch (PDOException $e) {
            print "Error: could not delete question choice" . $e->getMessage() . "<br/>";
            die();
        }
    }
?>