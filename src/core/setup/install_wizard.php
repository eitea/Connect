<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="Cache-Control" content="max-age=600, must-revalidate">

    <script src="plugins/jQuery/jquery.min.js"></script>
    <link rel="stylesheet" href="plugins/font-awesome/css/font-awesome.min.css"/>

    <link href="plugins/bootstrap/css/bootstrap.min.css" rel="stylesheet"/>
    <script src="plugins/bootstrap/js/bootstrap.min.js"></script>

    <link rel="stylesheet" type="text/css" href="plugins/select2/css/select2.min.css">
    <script src='plugins/select2/js/select2.min.js'></script>

    <link href="plugins/homeMenu/homeMenu.css" rel="stylesheet" />
    <title>Setup Connect</title>
</head>
<body>
    <?php
    session_start();
    if (empty($_SESSION['userid'])) {
        die('Please <a href="../login/auth">login</a> first.');
    }
    $userID = $_SESSION['userid'];

    require dirname(dirname(__DIR__)) . '/utilities.php';
    require dirname(dirname(__DIR__)) . '/connection.php';

    echo '<pre>';
    /*
    $aliceKeypair = sodium_crypto_box_keypair();
    $aliceSecretKey = sodium_crypto_box_secretkey($aliceKeypair);
    $alicePublicKey = sodium_crypto_box_publickey($aliceKeypair);

    $bobKeypair = sodium_crypto_box_keypair();
    $bobSecretKey = sodium_crypto_box_secretkey($bobKeypair);
    $bobPublicKey = sodium_crypto_box_publickey($bobKeypair);


    // On Alice's computer:
    $message = 'This comes from Alice.';

    $alice_sign_kp = sodium_crypto_sign_keypair();
    $alice_sign_secretkey = sodium_crypto_sign_secretkey($alice_sign_kp);
    $alice_sign_publickey = sodium_crypto_sign_publickey($alice_sign_kp);
    $message = sodium_crypto_sign($message, $alice_sign_secretkey);

    $aliceToBob = $aliceSecretKey . $bobPublicKey;
    $nonce = random_bytes(24);
    $ciphertext = $nonce . sodium_crypto_box($message, $nonce, $aliceToBob);


    // On Bob's computer:
    $bobToAlice = $bobSecretKey . $alicePublicKey;
    $nonce = mb_substr($ciphertext, 0, 24, '8bit');
    $encrypted = mb_substr($ciphertext, 24, null, '8bit');
    $decrypted = sodium_crypto_box_open($encrypted, $nonce, $bobToAlice);
    echo $decrypted;

    $original_msg = sodium_crypto_sign_open($message, $alicePublicKey);
    if ($original_msg !== false) {
        echo $original_msg;
    }
    */
    echo '</pre>';

    if($_SERVER['REQUEST_METHOD'] == 'POST'){
        if(!empty($_POST['encryption_pass']) && !empty($_POST['encryption_pass_confirm']) && $_POST['encryption_pass'] == $_POST['encryption_pass_confirm']){
            $result = $conn->query("SELECT firstname, lastname, email FROM UserData WHERE id = $userID LIMIT 1");
            if($result && ($row = $result->fetch_assoc())){
                //user PAIR
                $keyPair = sodium_crypto_box_keypair();
                $private = sodium_crypto_box_secretkey($keyPair);
                $public = sodium_crypto_box_publickey($keyPair);
                $hash = password_hash($_POST['encryption_pass'], PASSWORD_BCRYPT);
                $private_encrypt = simple_encryption(base64_encode($private), $_POST['encryption_pass']);
                $conn->query("UPDATE UserData SET psw = '$hash', publicPGPKey = '".base64_encode($public)."', privatePGPKey = '".$private_encrypt."'  WHERE id = $userID");

                //company PAIR
                $keyPair = sodium_crypto_box_keypair();
                $private = sodium_crypto_box_secretkey($keyPair);
                $public = sodium_crypto_box_publickey($keyPair);
                $private_encrypt = simple_encryption(base64_encode($private), $_POST['encryption_pass']);
                $conn->query("UPDATE companyData SET publicPGPKey = '".base64_encode($public)."', privatePGPKey = '".$private_encrypt."'");


            } else {
                echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>Database could not be read. '.$conn->error.'</div>';
            }
        }
    }
    ?>
    <nav id="fixed-navbar-header" class="navbar navbar-default navbar-fixed-top">
        <div class="container-fluid">
            <div class="navbar-header hidden-xs"><a class="navbar-brand" >Connect</a></div>
            <div class="navbar-right">
                <a class="btn navbar-btn navbar-link" data-toggle="collapse" href="#infoDiv_collapse"><strong>info</strong></a>
            </div>
        </div>
    </nav>
    <div class="collapse" id="infoDiv_collapse">
        <div class="well">
            <a href='http://www.eitea.at'> EI-TEA Partner GmbH </a> - <?php include dirname(dirname(__DIR__)).'/version_number.php'; echo $VERSION_TEXT; ?><br>
            The Licensor does not warrant that commencing upon the date of delivery or installation, that when operated in accordance with the documentation or other instructions provided by the Licensor,
            the Software will perform substantially in accordance with the functional specifications set forth in the documentation. The software is provided "as is", without warranty of any kind, express or implied.
        </div>
    </div>

    <div class="container">
        <div class="page-header h3">Einstellungen</div>
        Hallo!<br>
        Ihre Connect Umgebung steht Ihnen in kürze bereit. Sie müssen nur noch ihr gewünschtes Login-Kennwort eingeben und können dann die Einstellungen überprüfen.<br>
        Falls Sie hilfe benötigen, suchen sie einfach nach diesem Symbol <i class="fa fa-question-circle-o"></i> um mehr Informationen zu erhalten.<br>
        Wir wünschen Ihnen viel Erfolg.<br>
        <br><hr><br>
        <div class="row">
            <div class="col-md-4">
                <label>Neues Passwort</label>
                <input type="password" name="encryption_pass" class="form-control" />
            </div>
            <div class="col-md-4">
                <label>Neues Passwort Bestätigen</label>
                <input type="password" name="encryption_pass_confirm" class="form-control" />
            </div>
        </div>
        <br><hr><br>
        <h4>Verschlüsselung</h4>
        <div class="row">
            <div class="col-md-4">
                <label><input type="checkbox" checked name="wizard_encryption" value="1"> Aktivieren</label>
            </div>
        </div>
    </div>
</body>
