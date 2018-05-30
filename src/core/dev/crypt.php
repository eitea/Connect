<?php
require dirname(dirname(__DIR__)).DIRECTORY_SEPARATOR.'utilities.php';

if($_SERVER['REQUEST_METHOD'] == 'POST'){
	if(!empty($_POST['boxKeyprivate'])){
		$private = base64_decode($_POST['boxKeyprivate']);
		$public = base64_decode($_POST['boxKeypublic']);

		$cipher = $_POST['privateKey'];
		$nonce = mb_substr($cipher, 0, 24, '8bit');
		$cipherText = mb_substr($cipher, 24, null, '8bit');
		$decrypted = sodium_crypto_box_open($cipherText, $nonce, $private.$public);

		echo ($decrypted);
	}
	if(!empty($_POST['privateKey'])){
		$private = $_POST['privateKey'];
		$password = $_POST['password'];
		echo simple_decryption($private, $password);

	}
}

 ?>

<form method="POST">
<input type="text" name="privateKey" placeholder="Message in base64">
<input type="text" name="password" placeholder="Password">
<button type="submit">Submit</button>
</form>


<form method="POST">
<input type="text" name="privateKey" placeholder="Message in base64">
<input type="text" name="boxKeyprivate" placeholder="Private 64">
<input type="text" name="boxKeypublic" placeholder="Public 64">
<button type="submit">Submit</button>
</form>
