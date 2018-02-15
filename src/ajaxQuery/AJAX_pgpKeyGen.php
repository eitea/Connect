<?php

require dirname(dirname(__DIR__)) . "/plugins/pgp/autoload.php";
require dirname(__DIR__)."/connection.php";

if($_SERVER["REQUEST_METHOD"] == "POST"){
    if(!empty($_POST['userID'])){
        $userID = $_POST['userID'];
        $rsa = new \phpseclib\Crypt\RSA();
        $k = $rsa->createKey(512);
        $rsa->loadKey($k['privatekey']);

        $nkey = new OpenPGP_SecretKeyPacket(array(
            'n' => $rsa->modulus->toBytes(),
            'e' => $rsa->publicExponent->toBytes(),
            'd' => $rsa->exponent->toBytes(),
            'p' => $rsa->primes[2]->toBytes(),
            'q' => $rsa->primes[1]->toBytes(),
            'u' => $rsa->coefficients[2]->toBytes()
        ));

        $result = $conn->query("SELECT firstname, lastname, email FROM UserData WHERE id = $userID");
        if($result && ($row = $result->fetch_assoc())){
            $uid = new OpenPGP_UserIDPacket($row['firstname'].' '.$row['lastname']. ' <'.$row['email'].'>');
            //$ttl = new OpenPGP_SignaturePacket_SignatureExpirationTimePacket(time()+(3*365*24*60*60));
            $wkey = new OpenPGP_Crypt_RSA($nkey);
            $m = $wkey->sign_key_userid(array($nkey, $uid));
            //$m = $wkey->sign($ttl);
            // Serialize private key
            $private_bytes = $m->to_bytes();

            // Serialize public key message
            $pubm = clone($m);
            $pubm[0] = new OpenPGP_PublicKeyPacket($pubm[0]);

            $public_bytes = $pubm->to_bytes();
            $keys = array();
            $keys[0] = OpenPGP::enarmor($private_bytes, "PGP PRIVATE KEY BLOCK");
            $keys[1] = OpenPGP::enarmor($public_bytes, "PGP PUBLIC KEY BLOCK");
            echo json_encode($keys);
        } else {
            echo $conn->error;
        }
    }
}

?>
