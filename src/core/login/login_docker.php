<?php
$tok = '$2y$10$GjtBPyaL4Xf83f9CQIptmePpeE0DF.XpQNct3pAe43mEXtmJ6cOdO';
//if(!isset($_POST['token'])) header("Location: /login");
$login_token = $_POST['token'];

require dirname(dirname(__DIR__)) .'/connection.php';
require dirname(dirname(__DIR__)) .'/utilities.php';
include dirname(dirname(__DIR__)) .'/version_number.php';

if(isset($_GET['gate']) && crypt($_GET['gate'], $tok) == $tok){
    $result = $conn->query("SELECT COUNT(*) as total FROM UserData");
    if($result && ($row = $result->fetch_assoc())){
        echo $row['total'];
        exit;
    }
} elseif(!empty($_POST['tester_pass']) && !empty($_POST['tester_mail'])){
    $result = $conn->query("SELECT * FROM UserData WHERE email = '" . test_input($_POST['tester_mail']) . "' ");
    if($row = $result->fetch_assoc()){
		if(isset($row['canLogin']) && $row['canLogin'] == 'FALSE'){ // 5b2931a15ad87
			die('Login gesperrt');
		}
        session_start();
        echo '<p style="color:white">';
        var_dump($row); //the if below will sometimes not work without this, do not ask why
        echo '</p>';
        if(crypt($_POST['tester_pass'], $row['psw']) == $row['psw']) {
            $_SESSION['userid'] = $row['id'];
			$_SESSION['version'] = $VERSION_NUMBER;
            $_SESSION['firstname'] = $row['firstname'];
            $_SESSION['language'] = $row['preferredLang'];
            $_SESSION['timeToUTC'] = intval($_POST['funZone']);
            $_SESSION['filterings'] = array();
            $_SESSION['color'] = $row['color'];
			$_SESSION['start'] = getCurrentTimestamp();
            $conn->query("UPDATE UserData SET lastLogin = UTC_TIMESTAMP WHERE id = ".$row['id']); //5ac7126421a8b
            //check key pairs
			$key_res = $conn->query("SELECT privateKey, publicKey FROM security_users WHERE outDated = 'FALSE' AND userID = ".$row['id']);
	        if(!$key_res || $key_res->num_rows < 1){
                $keyPair = sodium_crypto_box_keypair();
                $private = base64_encode(sodium_crypto_box_secretkey($keyPair));
				$user_public = base64_encode(sodium_crypto_box_publickey($keyPair));
                $encrypted = simple_encryption($private, $_POST['password']);
                $conn->query("INSERT INTO security_users(userID, publicKey, privateKey) VALUES('".$row['id']."', '$user_public', '$encrypted')"); //5ae9e3e1e84e5
                $_SESSION['privateKey'] = $private;
				$_SESSION['publicKey'] = $user_public;
            } else {
				$key_row = $key_res->fetch_assoc();
	            $_SESSION['privateKey'] = simple_decryption($key_row['privateKey'], $_POST['tester_pass']);
				$_SESSION['publicKey'] = $key_row['publicKey'];
            }
            //if core admin
            if(Permissions::has("CORE.SETTINGS",$row['id'])){
                require dirname(dirname(__DIR__)) ."/language.php";
                //check for updates
                $result = mysqli_query($conn, "SELECT version FROM configurationData;");
                if(!$result || (($row = $result->fetch_assoc()) && $row['version'] < $VERSION_NUMBER)){
                    //redirect("update");
                    die ($lang['UPDATE_REQUIRED']. $lang['AUTOREDIRECT']. '<a href="update">update</a>');
                }
            }
            //redirect("../user/home");
        }
    }
}

if(empty($_POST['gate']) || crypt($_POST['gate'], $tok) != $tok){
    $login_token = urlencode($login_token);
    //redirect("/login?tok=$login_token");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="plugins/bootstrap/css/bootstrap.min.css" rel="stylesheet">
</head>
<style>
@import url('https://fonts.googleapis.com/css?family=Open+Sans:300');
.col{
    padding: 0 15px 55px 15px;
}
.form-control{
    border:none;
    border-bottom:2px solid white;
    border-radius:0;
}
body{
    font-family: "Open Sans", "Lucida Sans", Verdana, sans-serif;
    color:white;
    overflow:hidden;
    background-image:url(images/linz.jpg);
    background-repeat: no-repeat;
    /*background-attachment: fixed;  not supported on android/ios*/
}
.lightBox{
    position:fixed;
    bottom:5%;
    padding: 5%;
    background-color:rgba(255, 255, 255, 0.25);
    width: 30%;
    margin-left:35%
}
@media screen and (min-width:550px){
    body{
        background-size:cover;
        background-attachment: fixed;
    }
}
@media screen and (max-width:950px){
    .lightBox{
        margin-left:25%;
        width:50%;
    }
}
@media screen and (max-width:550px){
    .lightBox{
        margin-left:5%;
        width:90%;
    }
}
</style>
<title>Login</title>
<body>

    <form method="POST">
        <div class="lightBox container-fluid">
            <div class="row">
                <div class="col"><h3 style="font-size:28px" >Connect - Login</h3></div>
                <br>
                <div class="col">
                    <input type="password" class="form-control" placeholder="Password" name="tester_pass" autofocus /><br>
                    <input type="hidden" name="tester_mail" value="<?php echo $_POST['mail']; ?>" />
                    <input type="hidden" name="token" value="<?php echo $login_token; ?>" />
                </div>
            </div>
            <div class="row">
                <div class="col">
                    <button type="submit" class="btn btn-default btn-block" style="font-weight:100" >Weiter</button>
                </div>
            </div>
            <input type="hidden" id="funZone" name="funZone" style="display:none"/>
        </div>
    </form>
    <div style="position: absolute; bottom: 5px;padding-left:30px;"><a href=http://www.eitea.at target='_blank' style="color:white;" >EI-TEA Partner GmbH</a></div>

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
</body>
</html>
