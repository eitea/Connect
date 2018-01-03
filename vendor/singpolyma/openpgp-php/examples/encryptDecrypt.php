<?php

require 'vendor/autoload.php';

$key = OpenPGP_Message::parse(file_get_contents('vendor/singpolyma/openpgp-php/tests/data/helloKey.gpg'));
$data = new OpenPGP_LiteralDataPacket('This is text.', array('format' => 'u', 'filename' => 'stuff.txt'));
$encrypted = OpenPGP_Crypt_Symmetric::encrypt($key, new OpenPGP_Message(array($data)));

// Now decrypt it with the same key
$decryptor = new OpenPGP_Crypt_RSA($key);
$decrypted = $decryptor->decrypt($encrypted);

var_dump($decrypted);
