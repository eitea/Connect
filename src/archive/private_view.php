<?php
include dirname(__DIR__) . '/header.php';
require dirname(__DIR__) . "/misc/helpcenter.php";

$bucket = $identifier.'-archive';
if($_SERVER['REQUEST_METHOD'] == 'POST'){
	$s3 = getS3Object($bucket);
	if(!empty($_POST['delete-file'])){
		$x = test_input($_POST['delete-file']);
		try{
			$conn->query("DELETE FROM archive WHERE category = 'PERSONAL' AND categoryID = '$userID' AND uniqID = '$x'");
			if($conn->error){
				showError($conn->error);
			} else {
				$s3->deleteObject(['Bucket' => $bucket, 'Key' => $x]);
				showSuccess($lang['OK_DELETE']);
			}
		} catch(Exception $e){
			echo $e->getMessage();
		}
	} elseif(!empty($_POST['delete-folder'])){
		$x = test_input($_POST['delete-folder']);
		$conn->query("DELETE FROM archive WHERE category = 'PERSONAL' AND categoryID = '$userID AND id = '$x' AND type = 'folder'");
		if($conn->error){ showError($conn->error); } else { showSuccess($lang['OK_DELETE']); }
	} elseif(!empty($_POST['add-new-folder'])){
        $parent = test_input($_POST['add-new-folder']);
        if(!empty($_POST['new-folder-name'])){
            $name = test_input($_POST['new-folder-name']);
            $conn->query("INSERT INTO archive (category, categoryID, name, parent_directory, type, uploadUser) VALUES ('PERSONAL', '$userID', '$name', '$parent', 'folder', $userID)");
            if($conn->error){
                showError($conn->error);
            } else {
                showSuccess($lang['OK_ADD']);
            }
        } else {
            showError($lang['ERROR_MISSING_FIELDS']);
        }
    } elseif($privateKey && $publicKey && !empty($_POST['add-new-file']) && isset($_FILES['new-file-upload'])){
		$file_info = pathinfo($_FILES['new-file-upload']['name']);
		$ext = strtolower($file_info['extension']);
		$filetype = $_FILES['new-file-upload']['type'];
		if (!validate_file($err, $ext, $_FILES['new-file-upload']['size'])){
			showError($err);
		} elseif (empty($s3)) {
			showError("Es konnte keine S3 Verbindung hergestellt werden. Stellen Sie sicher, dass unter den Archiv Optionen eine gültige Verbindung gespeichert wurde.");
		} else {
			$parent = test_input($_POST['add-new-file']);
			try{
				$hashkey = uniqid('', true); //23 chars
				$keypair = base64_decode($privateKey).base64_decode($publicKey);
				$nonce = random_bytes(24);
				$file_encrypt = base64_encode($nonce . sodium_crypto_box(file_get_contents($_FILES['new-file-upload']['tmp_name']), $nonce, $keypair));
				$s3->putObject(array(
					'Bucket' => $bucket,
					'Key' => $hashkey,
					'Body' => $file_encrypt
				));
				$filename = test_input($file_info['filename']);
				$conn->query("INSERT INTO archive (category, categoryID, name, parent_directory, type, uniqID, uploadUser)
				VALUES ('PERSONAL', '$userID', '$filename', '$parent', '$ext', '$hashkey', $userID)");
				if($conn->error){ showError($conn->error); } else { showSuccess($lang['OK_UPLOAD']); }
			} catch(Exception $e){
				echo $e->getTraceAsString();
				echo '<br><hr><br>';
				echo $e->getMessage();
			}
		}
	} elseif(!empty($_POST['add-new-file'])){
		showError('No File Selected '.$_FILES['new-file-upload']['error']);
	}
}
?>
<div class="page-header"><h3>Persönliches Archiv</h3></div>

<?php
$upload_viewer = [
'accessKey' => 'ARCHIVE',
'category' => 'PERSONAL',
'categoryID' => $userID
];
require dirname(__DIR__).'/misc/upload_viewer.php';
?>

<?php include dirname(__DIR__) . '/footer.php'; ?>
