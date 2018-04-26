<?php
$result = $conn->query("SELECT p.*, c.companyID, s.publicKey, s.symmetricKey, c.name AS clientName FROM projectData p INNER JOIN clientData c ON p.clientID = c.id
INNER JOIN security_projects s ON s.projectID = p.id AND s.outDated = 'FALSE' WHERE p.id = $projectID LIMIT 1");

if(!$result){ include dirname(__DIR__).DIRECTORY_SEPARATOR.'footer.php'; die($conn->error); }
$projectRow = $result->fetch_assoc();
if(!$projectRow) { include dirname(__DIR__).DIRECTORY_SEPARATOR.'footer.php'; die($lang['ERROR_UNEXPECTED']); }

if($projectRow['publicKey']){
    $result = $conn->query("SELECT privateKey FROM security_access WHERE module = 'PRIVATE_PROJECT' AND optionalID = '$projectID' AND userID = $userID AND outDated = 'FALSE' LIMIT 1");
    if($result && ($row = $result->fetch_assoc())){
        $keypair = base64_decode($privateKey).base64_decode($projectRow['publicKey']);
        $cipher = base64_decode($row['privateKey']);
        $nonce = mb_substr($cipher, 0, 24, '8bit');
        $encrypted = mb_substr($cipher, 24, null, '8bit');
        try {
            $project_private = sodium_crypto_box_open($encrypted, $nonce, $keypair);
            $cipher_symmetric = base64_decode($projectRow['symmetricKey']);
            $nonce = mb_substr($cipher_symmetric, 0, 24, '8bit');
            $project_symmetric = sodium_crypto_box_open(mb_substr($cipher_symmetric, 24, null, '8bit'), $nonce, $project_private.base64_decode($projectRow['publicKey']));
        } catch(Exception $e){
            echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$e.'</div>';
        }
    } else {
        if($conn->error){
            echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$conn->error.__LINE__.'</div>';
        } else {
            $result = $conn->query("SELECT privateKey FROM security_access WHERE module = 'PRIVATE_PROJECT' AND optionalID = '$projectID'");
            if($result->num_rows > 0){
                echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>Sie besitzen keinen Zugriff auf dieses Projekt.Nur der Projektersteller kann Ihnen diesen Zugriff gewähren.</div><hr>';
            } elseif($isProjectAdmin == 'TRUE') {
                //no one has access to this, but a keypair exists. re-key it.
                $keyPair = sodium_crypto_box_keypair();
                $new_private = sodium_crypto_box_secretkey($keyPair);
                $new_public = sodium_crypto_box_publickey($keyPair);
                $symmetric = random_bytes(SODIUM_CRYPTO_SECRETBOX_KEYBYTES);

                $projectRow['publicKey'] = base64_encode($new_public);
                $project_private = $new_private;
                $project_symmetric = $symmetric;
                $nonce = random_bytes(24);
                $symmetric_encrypted = base64_encode($nonce . sodium_crypto_box($symmetric, $nonce, $new_private.$new_public));

                $conn->query("UPDATE security_projects SET outDated = 'TRUE' WHERE projectID = $projectID"); echo $conn->error;
                $conn->query("INSERT INTO security_projects (projectID, publicKey, symmetricKey) VALUES ('$projectID', '".base64_encode($new_public)."', '$symmetric_encrypted')"); echo $conn->error;

                insert_access_user($userID, $new_private);
				showSuccess("Automatisierung: Neues access keypair wurde zugewiesen, da kein Benutzer mit Access vorhanden war.");
            } else {
				showError($lang['ERROR_UNEXPECTED']);
			}
        }
    }
    //if there is a public key, there is an access, there is an upload:
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
        } catch(Exception $e){
            echo $e->getMessage();
        }
    }
}

if($_SERVER['REQUEST_METHOD'] == 'POST'){
    if(isset($_POST['reKey_'.$projectID])){
        $keyPair = sodium_crypto_box_keypair();
        $new_private = sodium_crypto_box_secretkey($keyPair);
        $new_public = sodium_crypto_box_publickey($keyPair);
        if($projectRow['publicKey']){
            if(isset($project_symmetric)){
                $symmetric = $project_symmetric;
            } else {
                echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>Erneutes verschlüsseln ohne Zugriff nicht möglich.</div>';
            }
        } else {
            $symmetric = random_bytes(SODIUM_CRYPTO_SECRETBOX_KEYBYTES);
        }
        if($symmetric){
            $projectRow['publicKey'] = base64_encode($new_public);
            $project_private = $new_private;
            $project_symmetric = $symmetric;

            $nonce = random_bytes(24);
            $symmetric_encrypted = base64_encode($nonce . sodium_crypto_box($symmetric, $nonce, $new_private.$new_public));
            //outdate and insert
            $conn->query("UPDATE security_projects SET outDated = 'TRUE' WHERE projectID = $projectID"); echo $conn->error;
            $conn->query("INSERT INTO security_projects (projectID, publicKey, symmetricKey) VALUES ('$projectID', '".base64_encode($new_public)."', '$symmetric_encrypted')"); echo $conn->error;
            $conn->query("UPDATE security_access SET outDated = 'TRUE' WHERE module = 'PRIVATE_PROJECT' AND optionalID = '$projectID'"); echo $conn->error;
            $result = $conn->query("SELECT userID FROM relationship_project_user WHERE projectID = $projectID AND userID != $userID"); echo $conn->error;
            while($result && ($row = $result->fetch_assoc())){
                insert_access_user($row['userID'], $new_private);
            }

            $result = $conn->query("SELECT userID FROM relationship_project_extern WHERE projectID = $projectID AND userID != $userID"); echo $conn->error;
            while($result && ($row = $result->fetch_assoc())){
                insert_access_user($row['userID'], $new_private, 1);
            }
            insert_access_user($userID, $new_private);

            if(!$projectRow['creator']){
                $conn->query("UPDATE projectData SET creator = $userID WHERE id = $projectID");
                showInfo("Sie wurden als Projektersteller hinzugefügt");
            }
        }
    }


} //endif POST

?>
