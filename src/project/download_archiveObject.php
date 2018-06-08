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
$symmetricOptional = '';
if($module == 'PROJECT'){
	$module = 'PRIVATE_PROJECT';
	$symmetricOptional = "AND optionalID = '{$arr[1]}'";
	$symmetricTableQuery = "LEFT JOIN security_projects s ON (s.projectID = optionalID AND s.outDated = 'FALSE')";
} else {
	$symmetricTableQuery = "LEFT JOIN security_modules s ON (s.module = a.module AND s.outDated = 'FALSE')";
}

$sql = "SELECT privateKey, s.publicKey, s.symmetricKey FROM security_access a $symmetricTableQuery
	WHERE a.module = '$module' $symmetricOptional AND a.userID = $userID AND a.outDated = 'FALSE'  LIMIT 1";
$result = $conn->query($sql);

if($result && ($row = $result->fetch_assoc()) && $row['publicKey'] && $row['privateKey']){
	$keypair = base64_decode($privateKey).base64_decode($row['publicKey']);
	$cipher = base64_decode($row['privateKey']);
	$nonce = mb_substr($cipher, 0, 24, '8bit');
	$encrypted = mb_substr($cipher, 24, null, '8bit');
	try {
		$project_private = sodium_crypto_box_open($encrypted, $nonce, $keypair);
		if($module == 'TASK'){
			$bucket = $identifier .'-tasks';
		} else {
			$cipher_symmetric = base64_decode($row['symmetricKey']);
			$nonce = mb_substr($cipher_symmetric, 0, 24, '8bit');
			$symmetricKey = sodium_crypto_box_open(mb_substr($cipher_symmetric, 24, null, '8bit'), $nonce, $project_private.base64_decode($row['publicKey']));
		}
	} catch(Exception $e){
		echo $e;
	}
} else {
	echo $conn->error;
}

$result = $conn->query("SELECT endpoint, awskey, secret FROM archiveconfig WHERE isActive = 'TRUE' LIMIT 1");
if($result && ($row = $result->fetch_assoc())){
    try{
        $s3 = new Aws\S3\S3Client(array(
            'version' => 'latest',
            'region' => '',
            'endpoint' => $row['endpoint'],
            'use_path_style_endpoint' => true,
            'credentials' => array('key' => $row['awskey'], 'secret' => $row['secret'])
        ));
        $object = $s3->getObject(array(
            'Bucket' => $bucket,
            'Key' => $fileKey,
        ));
    } catch(Exception $e){
        echo $e;
    }
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

	if(isset($symmetricKey)){
		echo simple_decryption($object[ 'Body' ], $symmetricKey);
	} elseif($module == 'TASK'){
		$result = $conn->query("SELECT v2 FROM dynamicprojects WHERE projectid = '{$arr[1]}'");
		$row = $result->fetch_assoc();
		echo asymmetric_encryption('TASK', $object[ 'Body' ], $userID, $privateKey, $row['v2']);
	} else {
		echo $object['Body'];
	}
}
?>
