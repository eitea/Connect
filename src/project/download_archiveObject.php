<?php
require dirname(dirname(__DIR__)) . '/plugins/aws/autoload.php';
require dirname(__DIR__).DIRECTORY_SEPARATOR.'connection.php';
require dirname(__DIR__).DIRECTORY_SEPARATOR.'utilities.php';

session_start();
if(empty($_SESSION['userid'])) die();

$fileKey = test_input($_POST['download-file']);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
$arr = explode('_', test_input($_POST['keyReference']));

$userID = $_SESSION['userid'];
$privateKey = $_SESSION['privateKey'];
$publicKey = $_SESSION['publicKey'];

$result = $conn->query("SELECT id FROM identification LIMIT 1");
if($row = $result->fetch_assoc()){
	$identifier = $row['id'];
} else {
	die('No identifier found '.$conn->error);
}

$bucket = $identifier .'-uploads';
$module = $arr[0];

if($module == 'PROJECT') $module = ['PRIVATE_PROJECT', $arr[1]];
if($module == 'PERSONAL') $bucket = $identifier .'-archive';
if($module == 'TASK') $bucket = $identifier .'-tasks';
try{
    $s3 = getS3Object($bucket);
    $object = $s3->getObject(array(
        'Bucket' => $bucket,
        'Key' => $fileKey,
    ));
} catch(Exception $e){
    echo $e;
}

$result = $conn->query("SELECT name, type FROM archive WHERE uniqID = '$fileKey' LIMIT 1");
$row = $result->fetch_assoc();

if(isset($object)){
    header( "Cache-Control: public" );
    header( "Content-Description: File Transfer" );
	if($row['type'] == 'pdf'){
		header( "Content-Type: application/pdf" );
		header( "Content-Disposition: inline; filename=" . $row['name'] . "." . $row['type'] );
	} else {
		header( "Content-Type: {$object['ContentType']}" );
		header( "Content-Disposition: attachment; filename=" . $row['name'] . "." . $row['type'] );
	}
	if($module == 'TASK' || $module == 'CHAT'){
		if($module == 'TASK') $result = $conn->query("SELECT v2 FROM dynamicprojects WHERE projectid = '{$arr[1]}'");
		if($module == 'CHAT') $result = $conn->query("SELECT vKey AS v2 FROM messenger_messages WHERE id = {$arr[1]}");
		echo $conn->error;
		$row = $result->fetch_assoc();
		echo asymmetric_encryption($module, $object[ 'Body' ], $userID, $privateKey, $row['v2'], $err);
		echo $err;
	} elseif($module == 'PERSONAL'){
		$body = base64_decode($object['Body']);
		$nonce = mb_substr($body, 0, 24, '8bit');
		$encrypted = mb_substr($body, 24, null, '8bit');
		echo sodium_crypto_box_open($encrypted, $nonce, base64_decode($privateKey).base64_decode($publicKey));
	} else {
		echo secure_data($module, $object[ 'Body' ], 'decrypt', $userID, $privateKey);
	}
}
?>
