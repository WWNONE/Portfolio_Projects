<html>
    <body>

        <?php
            session_start();
        ?>

        <form action="quiz.php" method="post">

            <!-- QUESTION 1 -->
            <h4>Q1: The pace of this course</h4>

            <label for="option1">
                <input type="radio" name="question1" id="option1" value="A">
                A: is too slow
            </label><br>
            <label for="option2">
                <input type="radio" na  me="question1" id="option2" value="B">
                B: is just right
            </label><br>
            <label for="option3">
                <input type="radio" name="question1" id="option3" value="C">
                C: is too fast
            </label><br>
            <label for="option4">
                <input type="radio" name="question1" id="option4" value="D">
                D: I don't know
            </label><br>

            <!-- QUESTION 2 -->
            <h4>Q2: The feedback from homework assignment grading</h4>

            <label for="option1">
                <input type="radio" name="question2" id="option1" value="A">
                A: too few
            </label><br>
            <label for="option2">
                <input type="radio" name="question2" id="option2" value="B">
                B: sufficient
            </label><br>
            <label for="option3">
                <input type="radio" name="question2" id="option3" value="C">
                C: I don't know
            </label><br>

            <!-- QUESTION 3 -->
            <h4>Q3: Anything you like about the teaching of this course?</h4>
            
            <label for="question3">
                <textarea id="question3" name="question3" rows="3" cols="50"></textarea>
            </label><br>
            <input type="submit" name="submit" value="Submit">

        </form>

        <?php
            if (isset($_POST["submit"])){
                echo "Your answers are: <br>";
                foreach (array_keys($_POST) as $x){
                    if ($x!='submit')
                        echo $x .": ". $_POST[$x]. "<br>";
                }
                return;
            }
        ?>

        <?php
        /*
            echo "<pre>";
            echo "Session:";
            print_r($_SESSION);
            echo "Post:";
            print_r($_POST);
            echo "</pre>";
        */
        ?>
        
    </body>
</html>
