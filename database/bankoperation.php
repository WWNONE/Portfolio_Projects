<html>
    <body>    

        <style>
        table, th, td {
            border: 1px solid black;
            border-collapse: collapse;
        }
        </style>

        <!-- START SESSION -->
        <?php
            session_start();

            if (!isset($_SESSION["username"])) {
                header("LOCATION:login.php"); // redirect to the login page if the user is not logged in
            }

            require "db.php";
            if (isset($_POST["accounts"])) {
            $accounts = get_accounts($_SESSION["username"]);
        ?>

        <!-- DISPLAY ACCOUNT INFO -->
        <table>
        <tr>
        <th>Account</th>
        <th>Balance</th>
        </tr>
        <?php
            foreach ($accounts as $row) {
            echo "<tr>";
            echo "<td>" . $row[0] . "</td>";
            echo "<td>" . $row[1] . "</td>";
            echo "</tr>";
            }
            echo "<table>";
        }

        ?>

        <!-- CONFIRM TRANSACTION -->
        <?php
        if (isset($_POST["confirm"])) {
            $from = $_POST["from_account"];
            $to = $_POST["to_account"];
            $amount = $_POST["amount"];
            $user = $_SESSION["username"];
            transfer($from, $to, $amount, $user);
        }
        ?>
        <form>
            <form action="bankoperation.php" method="post">

            <label for="from_account">From account:</label>
            <input type="text" id="from_account" name="from_account"><br>

            <label for="to_account">To account:</label>
            <input type="text" id="to_account" name="to_account"><br>

            <label for="amount">Amount:</label>
            <input type="text" id="amount" name="amount"><br>

            <input type="submit" name="confirm" value="Confirm">

        </form>

    </body>
</html>