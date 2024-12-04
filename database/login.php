<html>
    <body>

        <!-- START SESSION -->
        <?php
            require "db.php";
            session_start();
        ?>

        <!-- LOGIN -->
        <?php
            if (isset($_POST["login"])){
                if (authenticate($_POST["username"], $_POST["password"]) == 1){
                    $_SESSION["username"] = $_POST["username"];
                    header("LOCATION:main.php");
                }
                else {
                    echo '<p style="color:red">incorrect username or password</p>';
                }
            }
        ?>
        <form action="login.php" method="post">

            <label for="username">username:</label>
            <input type="text" id="username" name="username"><br>

            <label for="password">password:</label>
            <input type="text" id="password" name="password"><br>

            <input type="submit" name="login" value="login">
            
        </form>

    </body>
</html>