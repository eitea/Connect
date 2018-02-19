<?php
session_start();
require dirname(dirname(__DIR__)) . '/connection.php';
require dirname(dirname(__DIR__)) . '/utilities.php';
require dirname(dirname(__DIR__)) . '/language.php';
include dirname(dirname(__DIR__)) . '/version_number.php';

if (!empty($_POST['password']) && !empty($_POST['passwordConfirm'])) {
    $password = $_POST['password'];
    $passwordConfirm = $_POST['passwordConfirm'];
    if (strcmp($password, $passwordConfirm) == 0 && match_passwordpolicy($password)) {
        $psw = password_hash($password, PASSWORD_BCRYPT);
        $sql = "UPDATE $userTable SET psw = '$psw',forcedPwdChange = NULL WHERE id = '" . $_SESSION['userid'] . "';";
        $conn->query($sql);
        if ($conn->error) {
            echo '<div class="alert alert-danger fade in">';
            echo '<a href="" class="close" data-dismiss="alert" aria-label="close">&times;</a>';
            echo '<strong>Could not change Passwords! </strong>Passwords did not match or were invalid. Password must be at least 8 characters long and contain at least one Capital Letter, one number and one special character.';
            echo '</div>';
        } else {
            $conn->query("UPDATE $userTable SET lastPswChange = CURRENT_TIMESTAMP WHERE id = '" . $_SESSION['userid'] . "';");
            redirect("../user/home");
        }
    } else {
        echo '<div class="alert alert-danger fade in">';
        echo '<a href="" class="close" data-dismiss="alert" aria-label="close">&times;</a>';
        echo '<strong>Could not change Passwords! </strong>Passwords did not match or were invalid. Password must be at least 8 characters long and contain at least one Capital Letter, one number and one special character.';
        echo '</div>';
    }
}
?>
<!DOCTYPE html>
<html>
    <meta http-equiv="Cache-Control" content="max-age=600, must-revalidate">
    <link href="../plugins/homeMenu/loginMenu.css" rel="stylesheet" />
    <head>
        <title>Login</title>
    </head>
    <body>
        <div id="footer">
            <form method="POST" style="display:inline-block">
                <label for="in">New Password: </label>  <input id="pw" type="text" name="password" value="" autofocus /><br>
                <label for="pw">Confirm New Password: </label> <input id="pc" type="text" name="passwordConfirm" value="" /><br>
                <input type="submit" name="cancelButton" value="Cancel" /> <input type="submit" name="loginButton" value="Submit" /><br><br>
                <div class="robot-control"><input type="number" id="funZone" name="funZone" readonly><input type="text" name="captcha" value="" /></div>
            </form>

        </div>
        <script>
            var today = new Date();
            var timeZone = today.getTimezoneOffset() / (-60);
            if (today.dst) {
                timeZone--;
            }
            document.getElementById("funZone").value = timeZone;
            Date.prototype.stdTimezoneOffset = function () {
                var jan = new Date(this.getFullYear(), 0, 1);
                var jul = new Date(this.getFullYear(), 6, 1);
                return Math.max(jan.getTimezoneOffset(), jul.getTimezoneOffset());
            }
            Date.prototype.dst = function () {
                return this.getTimezoneOffset() < this.stdTimezoneOffset();
            }
            alert("Please change your Password!");
        </script>

        <div style="position: absolute; bottom: 5px;">
            <a href=http://www.eitea.at target='_blank'>EI-TEA Partner GmbH - <?php echo $VERSION_TEXT; ?></a>
        </div>
    </body>
</html>
