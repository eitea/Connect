<?php
require dirname(__DIR__).DIRECTORY_SEPARATOR.'header.php';
require dirname(dirname(__DIR__)) . "/plugins/aws/autoload.php";
enableToProject($userID);

if(!isset($_GET['p'])){ include dirname(__DIR__).DIRECTORY_SEPARATOR.'footer.php'; die('Invalid access'); }
$projectID = intval($_GET['p']);

function insert_access_user($userID, $privateKey, $external = false){
    global $conn;
    global $projectID;
    if($external) {
        $result = $conn->query("SELECT publicPGPKey FROM UserData WHERE id = $userID");
    } else {
        $result = $conn->query("SELECT publicPGPKey FROM external_users WHERE id = $userID");
    }
    if($result && ($row = $result->fetch_assoc())){
        $user_public = base64_decode($row['publicPGPKey']);
        $nonce = random_bytes(24);
        $private_encrypt = $nonce . sodium_crypto_box($privateKey, $nonce, $privateKey.$user_public);
        if($external){
            $conn->query("INSERT INTO security_external_access(externalID, module, privateKey, optionalID) VALUES ($userID, 'PRIVATE_PROJECT', '".base64_encode($private_encrypt)."', '$projectID')");
        } else {
            $conn->query("INSERT INTO security_access(userID, module, privateKey, optionalID) VALUES ($userID, 'PRIVATE_PROJECT', '".base64_encode($private_encrypt)."', '$projectID')");
        }
        if($conn->error){
            echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$conn->error.'</div>';
        }
    } else {
        echo $conn->error;
    }
}

if($_SERVER['REQUEST_METHOD'] == 'POST'){
    if(isset($_POST['saveGeneral'])){
        $hours = floatval(test_input($_POST['project_hours']));
        $hourlyPrice = floatval(test_input($_POST['project_hourlyPrice']));
        $status = isset($_POST['project_productive']) ? 'checked' : '';
        $field_1 = $field_2 = $field_3 = 'FALSE';
        if(isset($_POST['project_field_1'])){ $field_1 = 'TRUE'; }
        if(isset($_POST['project_field_2'])){ $field_2 = 'TRUE'; }
        if(isset($_POST['project_field_3'])){ $field_3 = 'TRUE'; }

        $conn->query("UPDATE projectData SET hours = '$hours', hourlyPrice = '$hourlyPrice', status='$status', field_1 = '$field_1', field_2 = '$field_2', field_3 = '$field_3' WHERE id = $projectID");
        if($conn->error){
            echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$conn->error.'</div>';
        } else {
            echo '<div class="alert alert-success"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$lang['OK_SAVE'].'</div>';
        }
    } elseif(isset($_POST['reKey'])){
        $keyPair = sodium_crypto_box_keypair();
        $new_private = sodium_crypto_box_secretkey($keyPair);
        $new_public = sodium_crypto_box_publickey($keyPair);
        //outdate and insert
        $conn->query("UPDATE security_access SET outDated = 'TRUE' WHERE module = 'PRIVATE_PROJECT' AND optionalID = '$projectID'"); echo $conn->error;
        $result = $conn->query("SELECT userID, publicPGPKey FROM relationship_project_user r LEFT JOIN UserData ON r.userID = UserData.id WHERE projectID = $projectID");
        while($result && ($row = $result->fetch_assoc())){
            $user_public = base64_decode($row['publicPGPKey']);
            $nonce = random_bytes(24);
            $private_encrypt = $nonce . sodium_crypto_box($new_private, $nonce, $new_private.$new_public);
            $conn->query("INSERT INTO security_access(userID, module, privateKey, optionalID) VALUES ($userID, 'PRIVATE_PROJECT', '".base64_encode($private_encrypt)."', '$projectID')");
            echo $conn->error .' - access error<br>';
        }
        $result = $conn->query("SELECT id, symmetricKey, publicKey FROM security_projects WHERE projectID = $projectID AND outDated = 'FALSE' LIMIT 1"); echo $conn->error;
        if($row = $result->fetch_assoc()){
            $symmetric_cipher = base64_decode($row['symmetricKey']);
            //TODO: decrypt old symmetric key or re-encrypt all old data.
        } else {
            $symmetric = random_bytes(SODIUM_CRYPTO_SECRETBOX_KEYBYTES);
        }
        //outdate and insert
        $conn->query("UPDATE security_projects SET outDated = 'TRUE' WHERE projectID = $projectID"); echo $conn->error;
        $symmetric_encrypted = base64_encode($nonce . sodium_crypto_box($symmetric, $nonce, $private.$public));
        $conn->query("INSERT INTO security_projects (projectID, publicKey, symmetricKey) VALUES ($projectID, $new_public, $symmetric_encrypted)");
    }
    if(isset($_POST['hire'])){
        if(!empty($_POST['userID'])){
            $stmt = $conn->prepare("INSERT INTO relationship_project_user (projectID, userID, access, expirationDate) VALUES($projectID, ?, ?, ?)"); echo $conn->error;
            $stmt->bind_param('iss', $x, $access, $date);
            for($i = 0; $i < count($_POST['userID']); $i++){
                $x = intval($_POST['userID'][$i]);
                $access = test_input($_POST['userAccess'][$i], 1);
                $date = test_Date($_POST['userExpiration'][$i], 'Y-m-d') ? $_POST['userExpiration'][$i] : '0000-00-00';
                $stmt->execute();
                echo $stmt->error;
            }
            $stmt->close();
        }
        if(!empty($_POST['externID'])){
            $stmt = $conn->prepare("INSERT INTO relationship_project_extern (projectID, userID, access, expirationDate) VALUES($projectID, ?, ?, ?)"); echo $conn->error;
            $stmt->bind_param('iss', $x, $access, $date);
            for($i = 0; $i < count($_POST['externID']); $i++){
                $x = intval($_POST['externID'][$i]);
                $access = test_input($_POST['externAccess'][$i], 1);
                $date = test_Date($_POST['externExpiration'][$i], 'Y-m-d') ? $_POST['externExpiration'][$i] : '0000-00-00';
                $stmt->execute();
                echo $stmt->error;
            }
            $stmt->close();
        }
    }

    if(!empty($_POST['removeUser'])){
        $x = intval($_POST['removeUser']);
        $conn->query("UPDATE security_access SET outDated = 'TRUE' WHERE module = 'PRIVATE_PROJECT' AND optionalID = '$projectID' AND userID = $x");
        $conn->query("DELETE FROM relationship_project_user WHERE userID = $x AND projectID = $projectID");
        if($conn->error){
            echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$conn->error.'</div>';
        } else {
            echo '<div class="alert alert-success"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$lang['OK_DELETE'].'</div>';
        }
    } elseif(!empty($_POST['removeExtern'])){
        $x = intval($_POST['removeExtern']);
        $conn->query("DELETE FROM relationship_project_extern WHERE userID = $x AND projectID = $projectID");
        if($conn->error){
            echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$conn->error.'</div>';
        } else {
            echo '<div class="alert alert-success"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$lang['OK_DELETE'].'</div>';
        }
    }
} //endif POST

$result = $conn->query("SELECT p.*, c.companyID, s.publicKey, s.symmetricKey, c.name AS clientName FROM projectData p LEFT JOIN clientData c ON p.clientID = c.id
LEFT JOIN security_projects s ON s.projectID = p.id AND s.outDated = 'FALSE' WHERE p.id = $projectID LIMIT 1");
if(!$result){ include dirname(__DIR__).DIRECTORY_SEPARATOR.'footer.php'; die($conn->error); }
$projectRow = $result->fetch_assoc();
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
            echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$e->getMessage.'</div>';
        }
        $result = $conn->query("SELECT endpoint, awskey, secret FROM archiveconfig WHERE isActive = 'TRUE' LIMIT 1");
        if($result && ($row = $result->fetch_assoc())){
            $link_id = (getenv('IS_CONTAINER') || isset($_SERVER['IS_CONTAINER'])) ? substr($servername, 0, 8) : $identifier;
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
    } else {
        if($conn->error){
            echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$conn->error.'</div>';
        } else {
            echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>Sie besitzen keinen Zugriff auf dieses Projekt.Nur der Projektersteller kann Ihnen diesen Zugriff gewähren.</div><hr>';
        }
    }
}

if($_SERVER['REQUEST_METHOD'] == 'POST'){
    if(isset($_POST['reKey'])){
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
            $result = $conn->query("SELECT userID FROM relationship_project_user WHERE projectID = $projectID AND userID != $userID");
            echo $conn->error;
            while($result && ($row = $result->fetch_assoc())){
                insert_access_user($row['userID'], $new_private);
            }
            insert_access_user($userID, $new_private);
            if($conn->error){
                echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$conn->error.'</div>';
            }
        }
    } elseif(isset($_POST['hire'])){
        if(!empty($_POST['userID'])){
            $stmt = $conn->prepare("INSERT INTO relationship_project_user (projectID, userID, access, expirationDate) VALUES($projectID, ?, 'READ', '0000-00-00')"); echo $conn->error;
            $stmt->bind_param('i', $x);
            for($i = 0; $i < count($_POST['userID']); $i++){
                $x = intval($_POST['userID'][$i]);
                $stmt->execute();
                echo $stmt->error;
                insert_access_user($x, $project_private);
            }
            $stmt->close();
        }
        if(!empty($_POST['externID'])){
            $stmt = $conn->prepare("INSERT INTO relationship_project_extern (projectID, userID, access, expirationDate) VALUES($projectID, ?, 'READ', '0000-00-00')"); echo $conn->error;
            $stmt->bind_param('i', $x);
            for($i = 0; $i < count($_POST['externID']); $i++){
                $x = intval($_POST['externID'][$i]);
                $stmt->execute();
                echo $stmt->error;
                insert_access_user($x, $project_private, 1);
            }
            $stmt->close();
        }
    } elseif(!empty($_POST['add-new-folder'])){
        $x = test_input($_POST['add-new-folder']);
        if(!empty($_POST['new-folder-name'])){
            $val = test_input($_POST['new-folder-name']);
            $conn->query("INSERT INTO project_archive(projectID, name, parent_directory, type) VALUES ($projectID, '$val', '$x', 'folder')");
            if($conn->error){
                showError($conn->error);
            } else {
                showSuccess($lang['OK_ADD']);
            }
        } else {
            showError($lang['ERROR_MISSING_FIELDS']);
        }
    }

    if(!empty($_POST['delete-file'])){
        $x = test_input($_POST['delete-file']);
        $bucket = $link_id .'_uploads';
        try{
            $s3->deleteObject(['Bucket' => $bucket, 'Key' => $x]);

            $conn->query("DELETE FROM project_archive WHERE projectID = $projectID AND uniqID = '$x'");
            if($conn->error){ showError($conn->error); } else { showSuccess($lang['OK_DELETE']); }
        } catch(Exception $e){
            echo $e->getMessage();
        }
    }
    if(!empty($_POST['add-new-file']) && isset($_FILES['new-file-upload'])){
        $file_info = pathinfo($_FILES['new-file-upload']['name']);
        $ext = strtolower($file_info['extension']);
        $filetype = $_FILES['new-file-upload']['type'];
        $accepted_types = ['application/msword', 'application/vnd.ms-excel', 'application/vnd.ms-powerpoint', 'text/plain', 'application/pdf', 'application/zip',
        'application/x-zip-compressed', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'multipart/x-zip',
        'application/x-compressed', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
        if (!in_array($ext, ['doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'pdf', 'txt', 'zip'])){
            showError('Ungültige Dateiendung: '.$ext);
        } elseif(!in_array($filetype, $accepted_types)) {
            showError('Ungültiger Dateityp: '.$filetype);
        } elseif ($_FILES['new-file-upload']['size'] > 15000000) { //15mb max
            showError('Die maximale Dateigröße wurde überschritten (15 MB)');
        } elseif(empty($s3)) {
            showError("Es konnte keine S3 Verbindung hergestellt werden. Stellen Sie sicher, dass unter den Archiv Optionen eine gültige Verbindung gespeichert wurde.");
        } else {
            $parent = test_input($_POST['add-new-file']);
            try{
                $hashkey = uniqid('', true); //23 chars
                $bucket = $link_id .'_uploads';
                if(!$s3->doesBucketExist($bucket)){
                    $result = $s3->createBucket(['ACL' => 'private', 'Bucket' => $bucket]);
                    if($result) showSuccess("Bucket $bucket Created");
                }
                $file_encrypt = simple_encryption(file_get_contents($_FILES['new-file-upload']['tmp_name']), $project_symmetric);
                //$_FILES['file']['name']
                $s3->putObject(array(
                    'Bucket' => $bucket,
                    'Key' => $hashkey,
                    'Body' => $file_encrypt
                ));

                $filename = test_input($file_info['filename']);
                $conn->query("INSERT INTO project_archive (projectID, name, parent_directory, type, uniqID) VALUES ($projectID, '$filename', '$parent', '$ext', '$hashkey')");
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
} //endif POST #2

?>

<form method="POST">
    <div class="page-header">
        <h3><?php echo $projectRow['clientName'].' - '.$projectRow['name']; ?>
            <div class="page-header-button-group">
                <button type="submit" name="saveGeneral" class="btn btn-default" title="<?php echo $lang['SAVE']; ?>" ><i class="fa fa-floppy-o"></i></button>
                <button type="submit" name="reKey" class="btn btn-default" title="Neues Schlüsselpaar erstellen" ><i class="fa fa-lock"></i></button>
            </div>
        </h3>
    </div>
    <?php if(!$projectRow['publicKey']) echo '<div class="alert alert-warning"><a href="#" data-dismiss="alert" class="close">&times;</a>
    Dieses Projekt besitzt noch kein Schlüsselpaar. Zugriff wurde eingeschränkt. Um das Projekt absichern zu lassen, drücken Sie auf den Schloss-Button</div><hr>'; ?>

    <h4>Allgemein</h4>
    <br>
    <div class="row form-group">
        <div class="col-sm-2">Produktiv</div>
        <div class="col-sm-10"><label><input type="checkbox" name="project_productive" <?php echo $projectRow['status']; ?> value="1" /> <i class="fa fa-tags"></i></label></div>
    </div>
    <div class="row form-group">
        <div class="col-sm-2">Stunden</div>
        <div class="col-sm-4"><label><input type="number" step="any" class="form-control" name="project_hours" value="<?php echo $projectRow['hours']; ?>" /></div>
        <div class="col-sm-2">Stundenrate</div>
        <div class="col-sm-4"><label><input type="number" step="any" class="form-control" name="project_hourlyPrice" value="<?php echo $projectRow['hourlyPrice']; ?>" /></div>
    </div>

    <div class="row form-group">
        <div class="col-sm-2">Optionale Projektfelder</div>
        <?php
        $resF = $conn->query("SELECT isActive, name FROM $companyExtraFieldsTable WHERE companyID = ".$projectRow['companyID']." ORDER BY id ASC"); echo $conn->error;
        if($resF->num_rows > 0){
            $rowF = $resF->fetch_assoc();
            if($rowF['isActive'] == 'TRUE'){
                $checked = $projectRow['field_1'] == 'TRUE' ? 'checked': '';
                echo '<div class="col-sm-3"><label><input type="checkbox" '.$checked.' name="project_field_1" /> '.$rowF['name'].'</label></div>';
            }
        }
        if($resF->num_rows > 1){
            $rowF = $resF->fetch_assoc();
            if($rowF['isActive'] == 'TRUE'){
                $checked = $projectRow['field_2'] == 'TRUE' ? 'checked': '';
                echo '<div class="col-sm-3"><label><input type="checkbox" '.$checked.' name="project_field_2" /> '.$rowF['name'].'</label></div>';
            }
        }
        if($resF->num_rows > 2){
            $rowF = $resF->fetch_assoc();
            if($rowF['isActive'] == 'TRUE'){
                $checked = $projectRow['field_3'] == 'TRUE' ? 'checked': '';
                echo '<div class="col-sm-3"><label><input type="checkbox" '.$checked.' name="project_field_3" /> '.$rowF['name'].'</label></div>';
            }
        }
        $resF->free();
        ?>
    </div>
</form>

<?php if(isset($project_private)): ?>
    <form method="POST" action="../setup/keys" target="_blank">
        <div class="row form-group">
            <div class="col-sm-2">
                Public Key
            </div>
            <div class="col-sm-6">
                <?php echo $projectRow['publicKey']; ?>
            </div>
            <div class="col-sm-4">
                <input type="hidden" name="personal" value="<?php echo base64_encode($project_private)."\n".$projectRow['publicKey']; ?>" />
                <button type="submit" class="btn btn-warning" name="">Schlüsselpaar Downloaden</button>
            </div>
        </div>
    </form>
    <br><hr>
    <h4>Benutzer <div class="page-header-button-group">
        <button type="button" class="btn btn-default" data-toggle="modal" data-target=".add-member" title="<?php echo $lang['ADD']; ?>" ><i class="fa fa-plus"></i></button>
    </div></h4>
    <form method="POST">
        <div class="row">
            <div class="col-xs-6 h5">Intern</div>
            <div class="col-xs-6 h5">Extern</div>
        </div>
        <div class="row">
            <div class="col-md-6">
                <?php
                $result = $conn->query("SELECT userID FROM relationship_project_user WHERE projectID = $projectID"); echo $conn->error;
                while($result && ($row = $result->fetch_assoc())){
                    echo '<button type="submit" name="removeUser" value="'.$row['userID'].'" class="btn btn-empty" title="Entfernen"><i class="fa fa-times" style="color:red"></i></button>';
                    echo $userID_toName[$row['userID']] .'<br>';
                }

                $result = $conn->query("SELECT userID, firstname, lastname FROM relationship_project_extern INNER JOIN external_users e ON userID = e.id
                INNER JOIN contactPersons c ON c.id = e.contactID WHERE projectID = $projectID"); echo $conn->error;
                while($result && ($row = $result->fetch_assoc())){
                    echo '<button type="submit" name="removeExtern" value="'.$row['userID'].'" class="btn btn-empty" title="Entfernen"><i class="fa fa-times" style="color:red"></i></button>';
                    echo $userID_toName[$row['userID']] .'<br>';
                }
                ?>
            </div>
        </div>
    </form>

    <div class="modal fade add-member">
        <div class="modal-dialog modal-content modal-md">
            <form method="POST">
                <div class="modal-header">Benutzer Hinzufügen</div>
                <div class="modal-body">
                    <div class="col-xs-12">
                        <h4>Interne Benutzer</h4>
                        <select class="js-example-basic-single" name="userID[]" multiple>
                            <?php
                            //$access_select = '<option value="WRITE">Vollzugriff</option><option value="READ">Halbzugriff</option>';
                            $res_addmem = $conn->query("SELECT id FROM UserData WHERE id NOT IN (SELECT DISTINCT userID FROM relationship_project_user WHERE projectID = $projectID)");
                            while ($res_addmem && ($row_addmem = $res_addmem->fetch_assoc())) {
                                echo '<option value="'.$row_addmem['id'].'" >'.$userID_toName[$row_addmem['id']].'</option>';
                            }
                            ?>
                        </select>
                        <hr>
                    </div>
                    <div class="col-xs-12">
                        <h4>Externe Benutzer</h4>
                        <select class="js-example-basic-single" name="externID[]" multiple>
                            <?php
                            $res_addmem = $conn->query("SELECT e.id, firstname, lastname FROM external_users e INNER JOIN contactPersons c ON c.id = e.contactID WHERE c.clientID = "
                            .$projectRow['clientID']." AND e.id NOT IN (SELECT DISTINCT userID FROM relationship_project_extern WHERE projectID = $projectID)"); echo $conn->error;
                            while ($res_addmem && ($row_addmem = $res_addmem->fetch_assoc())) {
                                echo '<option value="'.$row_addmem['id'].'" >'.$row_addmem['firstname'].' '.$row_addmem['lastname'].'</option>';
                            }
                            ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning" name="hire"><?php echo $lang['ADD']; ?></button>
                </div>
            </form>
        </div>
    </div>
    <br><hr>

    <?php if(!empty($s3)) : ?>
        <h4>Dateifreigabe
            <div class="page-header-button-group">
                <div class="btn-group"><a class="btn btn-default dropdown-toggle" data-toggle="dropdown" title="Hochladen..."><i class="fa fa-upload"></i></a>
                    <ul class="dropdown-menu">
                        <li><a data-toggle="modal" data-target="#modal-new-folder">Neuer Ordner</a></li>
                        <li><a data-toggle="modal" data-target="#modal-new-file">File</a></li>
                        <!--li><a data-toggle="modal" data-target="#modal-new-text">Text</a></li-->
                    </ul>
                </div>
            </div>
        </h4><br>

        <?php
        $result = $conn->query("SELECT name FROM company_folders WHERE companyID = ".$projectRow['companyID']." AND name NOT IN
            ( SELECT name FROM project_archive WHERE projectID = $projectID AND parent_directory = 'ROOT') ");
        echo $conn->error;
        while($result && ($row = $result->fetch_assoc())){
            $conn->query("INSERT INTO project_archive(projectID, name, parent_directory, type) VALUES($projectID, '".$row['name']."', 'ROOT', 'folder')"); echo $conn->error;
        }
        function drawFolder($parent_structure, $visibility = true){
            global $conn;
            global $projectID;
            global $project_symmetric;
            $html = '<div id="folder-'.$parent_structure.'" >';
            if(!$visibility) $html = substr_replace($html, 'style="display:none"', -1, 0);

            if($parent_structure != 'ROOT') $html .= '<div class="row"><div class="col-xs-1"><i class="fa fa-arrow-left"></i></div>
            <div class="col-xs-3"><button class="btn btn-link tree-node-back" data-parent="'.$parent_structure.'">Zurück</button></div></div>';
            $subfolder = '';
            $result = $conn->query("SELECT id, name, uploadDate, type, uniqID FROM project_archive WHERE projectID = $projectID AND parent_directory = '$parent_structure' ORDER BY type <> 'folder', type ASC ");
            echo $conn->error;
            while($result && ($row = $result->fetch_assoc())){
                $html .= '<div class="row">';
                if($row['type'] == 'folder'){
                    $html .= '<div class="col-xs-1"><i class="fa fa-folder-open-o"></i></div>
                    <div class="col-xs-4"><a class="folder-structure" data-child="'.$row['id'].'" data-parent="'.$parent_structure.'" >'.$row['name'].'</a></div><div class="col-xs-4">'.$row['uploadDate'].'</div>';
                    $subfolder .= drawFolder($row['id'], false);
                } else {
                    $html .= '<div class="col-xs-1"><i class="fa fa-file-o"></i></div>
                    <div class="col-xs-4">'.$row['name'].'</div><div class="col-xs-4">'.$row['uploadDate'].'</div>
                    <div class="col-xs-3">
                    <form method="POST" style="display:inline"><button type="submit" class="btn btn-default" name="delete-file" value="'.$row['uniqID'].'">
                    <i class="fa fa-trash-o"></i></button></form>
                    <form method="POST" style="display:inline" action="detailDownload" target="_blank">
                    <input type="hidden" name="symmetricKey" value="'.base64_encode($project_symmetric).'" />
                    <button type="submit" class="btn btn-default" name="download-file" value="'.$row['uniqID'].'"><i class="fa fa-download"></i></button>
                    </form></div>';
                }
                $html .= '</div>';
            }
            $html .= '</div>';
            $html .= $subfolder;
            return $html;
        }
        echo drawFolder('ROOT');
        ?>

        <div id="modal-new-folder" class="modal fade">
            <div class="modal-dialog modal-content modal-sm">
                <form method="POST">
                    <div class="modal-header h4">Neuer Ordner</div>
                    <div class="modal-body">
                        <label>Name</label>
                        <input type="text" name="new-folder-name" class="form-control" />
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-warning modal-new" name="add-new-folder" value="ROOT"><?php echo $lang['ADD']; ?></button>
                    </div>
                </form>
            </div>
        </div>
        <div id="modal-new-file" class="modal fade">
            <div class="modal-dialog modal-content modal-sm">
                <form method="POST" enctype="multipart/form-data">
                    <div class="modal-header h4">File Hochladen</div>
                    <div class="modal-body">
                        <label class="btn btn-default">
                            Datei Auswählen
                            <input type="file" name="new-file-upload"  accept="application/msword, application/vnd.ms-excel, application/vnd.ms-powerpoint, text/plain, application/pdf,.doc, .docx" style="display:none" >
                        </label>
                        <small>Max. 15MB<br>Text, PDF, .Zip und Office</small>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-warning modal-new" name="add-new-file" value="ROOT"><?php echo $lang['ADD']; ?></button>
                    </div>
                </form>
            </div>
        </div>

        <script>
        var grandParent = ['ROOT'];
        $('.tree-node-back').click(function(){
            var grandPa = grandParent.pop();
            $('#folder-'+ $(this).data('parent')).hide();
            $('#folder-'+ grandPa).fadeIn();
            changeUploadPlace(grandPa);
        });
        $('.folder-structure').click(function(event){
            $('#folder-'+ $(this).data('parent')).hide();
            $('#folder-'+ $(this).data('child')).fadeIn();
            grandParent.push($(this).data('parent'));
            changeUploadPlace($(this).data('child'));
        });
        function changeUploadPlace(place){
            $('.modal-new').val(place);
        }
        </script>
    <?php else: ?>
        <h4>Dateifreigabe</h4>
        <div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>Es konnte keine Verbindung zu einer S3 Schnittstelle hergestellt werden.
        Um den Dateiupload nutzen zu können, überprüfen Sie bitte Ihre Archiv Optionen</div>
    <?php endif; //s3 ?>
<?php endif; //key ?>
<?php require dirname(__DIR__).DIRECTORY_SEPARATOR.'footer.php'; ?>
