
<?php
//TODO: put a brute-force stopper somwhere here too
if(!empty($_POST['captcha']))  die("mep.");

require dirname(__DIR__) .'/connection.php';
require dirname(__DIR__) .'/utilities.php';
include dirname(__DIR__) .'/version_number.php';

$invalidLogin = "";
if(!empty($_POST['loginName']) && !empty($_POST['password']) && isset($_POST['loginButton'])) {
    $result = $conn->query("SELECT id, privateKey, login_pw FROM external_users WHERE login_mail = '" . test_input($_POST['loginName']) . "' "); echo $conn->error;
    if(($row = $result->fetch_assoc()) && crypt($_POST['password'], $row['login_pw']) == $row['login_pw']) {
        session_start();
        $_SESSION['external_id'] = $row['id'];
        $_SESSION['external_timeToUTC'] = intval($_POST['funZone']);
        $_SESSION['external_private'] = simple_decryption($row['privateKey'], $_POST['password']);
        redirect("home");
    } else {
        $invalidLogin = "Invalid Username/ Password!";
    }
}
?>

<!DOCTYPE html>
<html>
<meta http-equiv="Cache-Control" content="max-age=600, must-revalidate">
<link href="../plugins/homeMenu/loginMenu.css" rel="stylesheet" />
<head>
    <title>Externer Login</title>
</head>
<body>
    <div id="footer">
        <form method="POST" style="display:inline-block">
            <label for="in">E-Mail: </label>  <input id="in" type="text" name="loginName" value="" autofocus /><br>
            <label for="pw">Password: </label> <input id="pw" type="password" name="password" value="" /><br>
            <input type="submit" name="cancelButton" value="Cancel" /> <input type="submit" name="loginButton" value="Submit" /><br>
            <input type="text" readonly name="invalidLogin" style="border:0; background:0; color:white; text-align:right;" value="<?php echo $invalidLogin; ?>" />
            <div class="robot-control"><input type="number" id="funZone" name="funZone" readonly><input type="text" name="captcha" value="" /></div>
        </form>
    </div>
    <script>
    var today = new Date();
    var timeZone = today.getTimezoneOffset() /(-60);
    if(today.dst){timeZone--;}
    document.getElementById("funZone").value = timeZone;
    Date.prototype.stdTimezoneOffset = function() {
        var jan = new Date(this.getFullYear(), 0, 1);
        var jul = new Date(this.getFullYear(), 6, 1);
        return Math.max(jan.getTimezoneOffset(), jul.getTimezoneOffset());
    }
    Date.prototype.dst = function() {
        return this.getTimezoneOffset() < this.stdTimezoneOffset();
    }
    </script>

    <div style="position: absolute; bottom: 5px;">
        <a href=http://www.eitea.at target='_blank'>EI-TEA Partner GmbH - <?php echo $VERSION_TEXT; ?></a>
    </div>
</body>
</html>
