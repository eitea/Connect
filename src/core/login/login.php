<?php
if(getenv('IS_CONTAINER') || isset($_SERVER['IS_CONTAINER'])) header("Location: /login");

if(file_exists(dirname(dirname(__DIR__)).'/connection_config.php')){
    session_start();
} else {
    header("Location: ../setup/run");
}

//TODO: put a brute-force stopper somwhere here too
if(!empty($_POST['captcha']))  die("mep.");

require dirname(dirname(__DIR__)) .'/connection.php';
require dirname(dirname(__DIR__)) .'/utilities.php';
include dirname(dirname(__DIR__)) .'/version_number.php';

$invalidLogin = "";

if(!empty($_POST['loginName']) && !empty($_POST['password']) && isset($_POST['loginButton'])) {
    $result = $conn->query("SELECT * FROM UserData WHERE email = '" . test_input($_POST['loginName']) . "' ");
    if($result){
        $row = $result->fetch_assoc();
    } else {
        echo $conn->error;
    }
    if($result && crypt($_POST['password'], $row['psw']) == $row['psw']) {
        $redirect = "../user/home";
        $_SESSION['userid'] = $row['id'];
		$_SESSION['version'] = $VERSION_NUMBER;
        $_SESSION['firstname'] = $row['firstname'];
        $_SESSION['language'] = $row['preferredLang'];
        $_SESSION['timeToUTC'] = intval($_POST['funZone']);
        $_SESSION['filterings'] = array();
        $_SESSION['color'] = $row['color'];
        $conn->query("UPDATE UserData SET lastLogin = UTC_TIMESTAMP WHERE id = ".$row['id']); //5ac7126421a8b
        //check key pairs
		$key_res = $conn->query("SELECT privateKey, publicKey FROM security_users WHERE outDated = 'FALSE' AND userID = ".$row['id']);
        if(!$key_res || $key_res->num_rows < 1){
            if(function_exists("sodium_crypto_box_keypair")){
                $keyPair = sodium_crypto_box_keypair();
                $private = base64_encode(sodium_crypto_box_secretkey($keyPair));
                $user_public = base64_encode(sodium_crypto_box_publickey($keyPair));
                $encrypted = simple_encryption($private, $_POST['password']);
                $conn->query("INSERT INTO security_users(userID, publicKey, privateKey) VALUES('".$row['id']."', '$user_public', '$encrypted')"); //5ae9e3e1e84e5
                $_SESSION['privateKey'] = $private;
				$_SESSION['publicKey'] = $user_public;
            }else{
                $_SESSION['privateKey'] = "";
            }
        } else {
			$key_row = $key_res->fetch_assoc();
            $_SESSION['privateKey'] = simple_decryption($key_row['privateKey'], $_POST['password']);
			$_SESSION['publicKey'] = $key_row['publicKey'];
        }
        //if core admin
        $sql = "SELECT userID FROM roles WHERE userID = ".$row['id']." AND isCoreAdmin = 'TRUE'";
        $result = $conn->query($sql);
        if($result && $result->num_rows > 0){
            require dirname(dirname(__DIR__)) ."/language.php";
            //check for updates
            $result = mysqli_query($conn, "SELECT version FROM configurationData;");
            if(!$result || (($row = $result->fetch_assoc()) && $row['version'] < $VERSION_NUMBER)){
                redirect("update");
                die ($lang['UPDATE_REQUIRED']. $lang['AUTOREDIRECT']. '<a href="update">update</a>');
            }
        }
        redirect($redirect);
    } else {
        $invalidLogin = "Invalid Username/ Password!";
    }
}

$rowConfigTable['enableReg'] = FALSE;
$result = $conn->query("SELECT enableReg FROM $configTable");
if($result && $result->num_rows > 0){
    $rowConfigTable = $result->fetch_assoc();
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
            <label for="in">E-Mail: </label>  <input id="in" type="text" name="loginName" value="" autofocus /><br>
            <label for="pw">Password: </label> <input id="pw" type="password" name="password" value="" /><br>
            <input type="submit" name="cancelButton" value="Cancel" /> <input type="submit" name="loginButton" value="Submit" /><br>
            <input type="text" readonly name="invalidLogin" style="border:0; background:0; color:white; text-align:right;" value="<?php echo $invalidLogin; ?>" />
            <div class="robot-control"><input type="number" id="funZone" name="funZone" readonly><input type="text" name="captcha" value="" /></div>
        </form>
        <?php if($rowConfigTable['enableReg'] == 'TRUE'){echo '<a class="register-link" href="register">Register</a>';} ?>
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
