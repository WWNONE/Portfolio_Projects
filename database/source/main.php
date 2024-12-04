<html>
    <body>

        <!-- START SESSION -->
        <?php
            session_start();

            if (!isset($_SESSION["username"])) {
                header("LOCATION:login.php"); // redirect to the login page if the user is not logged in
            }
            else {
                echo '<p align="right"> Welcome '. $_SESSION["username"].'</p>';
            }
        ?>

        <!-- LOGOUT USER -->
        <?php 
            if (isset($_POST["logout"])){
                session_destroy();
                header("LOCATION:login.php");
            }
        ?>
        <form action='main.php' method="post">
            <p align="right">
                <input type="submit" value="logout" name="logout">
            </p>
        </form>

        <!-- DISPLAY -->
        <p>Welcome to our online minibank <?php echo $_SESSION["username"] ?>!<br><br>
            We can help you to transfer the money or display your accounts.<br><br>
            What would you like to do? Please click one of the buttons.<br><br>
        </p>
        <form action="bankoperation.php" method="post">
            <input type="submit" name="transfer" value="Transfer">
            <input type="submit" name="accounts" value="Accounts">   
        </form>

    </body>
</html>
