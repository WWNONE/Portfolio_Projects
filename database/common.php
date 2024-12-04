<?php
    require "database.php";
    session_start();

    if (isset($_POST["login"])){
        if (authenticate($_POST["username"], $_POST["password"]) == 1){
            $_SESSION["username"] = $_POST["username"];
            $_SESSION["account_type"] = get_account_type($_POST["username"]);
            $_SESSION["password_reset"] = check_password_reset($_POST["username"]); 
        
            if ($_SESSION["password_reset"] == true) {
                if ($_SESSION["account_type"] == 'instructor') {
                    header("LOCATION:instructor.php");
                    exit();
                } elseif ($_SESSION["account_type"] == 'student') {
                    header("LOCATION:student.php");
                    exit();
                }
            } else {
                echo '<p style="color:blue">Please reset your password</p>';
            }
        } else {
            echo '<p style="color:red">Error: incorrect username or password</p>';
        }
    }
    
    if (isset($_POST["reset_password"])) {
        $new_password = $_POST["new_password"];
        $confirm_password = $_POST["confirm_password"];
    
        if ($new_password === $confirm_password) {
            update_password($_SESSION["username"], $new_password);
            $_SESSION["password_reset"] = true;
            echo '<p style="color:green">Password reset successful!</p>';
        } else {
            echo '<p style="color:red">Error: passwords do not match, please try again.</p>';
        }
    }

?>

<html>
    <body>
        <div class="login-container">
            <!-- DEFAULT LOGIN -->
            <form action="common.php" method="post">
                <label for="username">Username:</label>
                <input type="text" id="username" name="username">
                <label for="password">Password:</label>
                <input type="password" id="password" name="password">
                <input type="submit" name="login" value="Login">
            </form>

            <?php if (isset($_SESSION["password_reset"]) && $_SESSION["password_reset"] == false): ?>
                <!-- PASSWORD RESET -->
                <form action="common.php" method="post">
                    <label for="new_password">New Password:</label>
                    <input type="password" id="new_password" name="new_password" required>
                    <label for="confirm_password">Confirm Password:</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                    <input type="submit" name="reset_password" value="Reset Password">
                </form>
            <?php endif; ?>
        </div>
    </body>
</html>