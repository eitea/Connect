<?php
require dirname(dirname(__DIR__)) . '/plugins/aws/autoload.php';
require dirname(__DIR__).DIRECTORY_SEPARATOR.'connection.php';
require dirname(__DIR__).DIRECTORY_SEPARATOR.'utilities.php';

$fileKey = test_input($_POST['download-file']);
$symmetricKey = base64_decode($_POST['symmetricKey']);

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
            'Bucket' => $identifier .'-uploads',
            'Key' => $fileKey,
        ));
    } catch(Exception $e){
        echo $e->getMessage();
    }
}

$result = $conn->query("SELECT name, type FROM project_archive WHERE uniqID = '$fileKey' LIMIT 1");
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
    echo simple_decryption($object[ 'Body' ], $symmetricKey);
}
?>
