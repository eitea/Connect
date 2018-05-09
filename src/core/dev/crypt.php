<?php
require dirname(dirname(__DIR__)).DIRECTORY_SEPARATOR.'utilities.php';

$private = $_POST['privateKey'];
$password = $_POST['password'];

echo simple_decryption($private, $password);
 ?>

<form method="POST">
<input type="text" name="privateKey" placeholder="Message in base64">
<input type="text" name="password" placeholder="Password">
<button type="submit">Submit</button>
</form>
