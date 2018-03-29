<?php
/*
* Callees: installation_wizard, header, securitySettings
*/
$content_personal = isset($_POST['personal']) ? $_POST['personal'] : '';
$content_company = isset($_POST['company']) ? $_POST['company'] : '';

$zip = new ZipArchive();
$zip_name = 'keys.zip';
if ($zip->open($zip_name, ZIPARCHIVE::CREATE)) {
    if($content_personal) $zip->addFromString('personal.txt', $content_personal);
    if(is_array($content_company)){
        for($i = 0; $i < count($content_company); $i++)
        $zip->addFromString("company_$i.txt", $content_company[$i]);
    } elseif($content_company) {
        $zip->addFromString('company.txt', $content_company);
    }
    $zip->close();
    header('Content-Type: application/octet-stream');
    header("Content-Transfer-Encoding: Binary");
    header("Content-Disposition: attachment; filename=\"".$zip_name."\"");
    clearstatcache();
    header("Content-Length: ".filesize($zip_name));
    readfile($zip_name);
    unlink($zip_name);
} else {
    header("Cache-Control: public");
    header("Content-Description: File Transfer");
    header("Content-Disposition: attachment; filename=keys.txt");
    header("Content-Type: text/plain");
    if(is_array($content_company)) $content_company = implode("\n", $content_company);
    echo $content_personal."\n".$content_company;
}

/*
echo '<pre>';
$aliceKeypair = sodium_crypto_box_keypair();
$aliceSecretKey = sodium_crypto_box_secretkey($aliceKeypair);
$alicePublicKey = sodium_crypto_box_publickey($aliceKeypair);

$bobKeypair = sodium_crypto_box_keypair();
$bobSecretKey = sodium_crypto_box_secretkey($bobKeypair);
$bobPublicKey = sodium_crypto_box_publickey($bobKeypair);

// On Alice's computer:
$message = base64_encode(random_bytes(SODIUM_CRYPTO_SECRETBOX_KEYBYTES));
$aliceToBob = $aliceSecretKey . $alicePublicKey;
$nonce = random_bytes(24);
$ciphertext = $nonce . sodium_crypto_box($message, $nonce, $aliceToBob);

// $alice_sign_kp = sodium_crypto_sign_keypair();
// $alice_sign_secretkey = sodium_crypto_sign_secretkey($alice_sign_kp);
// $alice_sign_publickey = sodium_crypto_sign_publickey($alice_sign_kp);
// $message = sodium_crypto_sign($message, $alice_sign_secretkey);

// Alice can decrypt her own message too:
$nonce = mb_substr($ciphertext, 0, 24, '8bit');
$encrypted = mb_substr($ciphertext, 24, null, '8bit');
$decrypted = sodium_crypto_box_open($encrypted, $nonce, $aliceToBob);
echo $decrypted;

echo "\n linebreak \n";

// On Bob's computer:
$bobToAlice = $bobSecretKey . $alicePublicKey;
$nonce = mb_substr($ciphertext, 0, 24, '8bit');
$encrypted = mb_substr($ciphertext, 24, null, '8bit');
$decrypted = sodium_crypto_box_open($encrypted, $nonce, $bobToAlice);

echo $decrypted;
// $original_msg = sodium_crypto_sign_open($decrypted, $alice_sign_publickey);
// if ($original_msg === false) {
//     echo "This is not from Alice";
// } else {
//     echo $original_msg;
// }
echo '</pre>';
*/
?>
