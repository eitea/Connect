<?php require dirname(__DIR__).DIRECTORY_SEPARATOR.'header.php'; enableToProject($userID); ?>

<?php
if(!isset($_GET['p'])) die('Invalid access');
$projectID = intval($_GET['p']);

if($_SERVER['REQUEST_METHOD'] == 'POST'){
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
        $conn->query("DELETE FROM relationship_project_user WHERE userID = $x AND projectID = $projectID");
        if($conn->error){
            echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$conn->error.'</div>';
        } else {
            echo '<div class="alert alert-success"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$lang['OK_DELETE'].'</div>';
        }
    }
    if(!empty($_POST['removeExtern'])){
        $x = intval($_POST['removeExtern']);
        $conn->query("DELETE FROM relationship_project_extern WHERE userID = $x AND projectID = $projectID");
        if($conn->error){
            echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$conn->error.'</div>';
        } else {
            echo '<div class="alert alert-success"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$lang['OK_DELETE'].'</div>';
        }
    }
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
    }
} //endif POST

$result = $conn->query("SELECT p.*, c.companyID, c.name AS clientName FROM projectData p LEFT JOIN clientData c ON p.clientID = c.id WHERE p.id = $projectID");
$projectRow = $result->fetch_assoc();
?>

<form method="POST">
    <div class="page-header">
        <h3><?php echo $projectRow['clientName'].' - '.$projectRow['name']; ?>
            <div class="page-header-button-group">
                <button type="submit" name="saveGeneral" class="btn btn-default blinking" title="<?php echo $lang['SAVE']; ?>" ><i class="fa fa-floppy-o"></i></button>
            </div>
        </h3>
    </div>

    <h4>Allgemein</h4>

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

<?php if($projectRow['publicKey']): ?>
<form method="POST" action="../setup/keys" target="_blank">
    <div class="row form-group">
        <div class="col-sm-2">
            Public Key
        </div>
        <div class="col-sm-6">
            <?php echo $projectRow['publicKey']; ?>
        </div>
        <div class="col-sm-4">
            <?php
            $result = $conn->query("SELECT privateKey FROM security_projects WHERE userID = $userID AND projectID = $projectID AND outDated = 'FALSE' LIMIT 1");
            if($result && ($row = $result->fetch_assoc())):
                $keypair = base64_decode($privateKey).base64_decode($projectRow['publicKey']);
                //echo mb_strlen($privateKey).'--<br>';
                //echo mb_strlen(base64_decode($projectRow['publicKey'])).'--<br>';
                $cipher = base64_decode($row['privateKey']);
                $nonce = mb_substr($cipher, 0, 24, '8bit');
                $encrypted = mb_substr($cipher, 24, null, '8bit');
                try{
                    $decrypted = sodium_crypto_box_open($encrypted, $nonce, $keypair);
                } catch(Exception $e){
                    echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$e.'</div>';
                    $decrypted = '';
                }
            ?>
            <input type="hidden" name="personal" value="<?php echo $decrypted."\n".$projectRow['publicKey']; ?>" />
            <button type="submit" class="btn btn-warning" name="">Schlüsselpaar Downloaden</button>
            <?php endif; ?>
        </div>
    </div>
</form>
<?php endif; ?>

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
                <div class="col-xs-6 h4">Interne Benutzer</div>
                <div class="col-xs-3 h4">Zugriff</div>
                <div class="col-xs-3 h4">Ablaufdatum</div>
                <?php
                $access_select = '<option value="WRITE">Vollzugriff</option><option value="READ">Halbzugriff</option>';
                $res_addmem = $conn->query("SELECT id FROM UserData WHERE id NOT IN (SELECT DISTINCT userID FROM relationship_project_user WHERE projectID = $projectID)");
                while ($res_addmem && ($row_addmem = $res_addmem->fetch_assoc())) {
                    echo '<div class="row">';
                    echo '<div class="col-sm-6"><label><input type="checkbox" name="userID[]" value="'.$row_addmem['id'].'" >'.$userID_toName[$row_addmem['id']].'</label></div>';
                    echo '<div class="col-sm-3"><select name="userAccess[]" class="form-control">'.$access_select.'</select></div>';
                    echo '<div class="col-sm-3"><input type="text" name="userExpiration[]" class="form-control datepicker" /></div>';
                    echo '</div>';
                }
                ?>
                <hr>
                <div class="col-xs-6 h4">Externe Benutzer</div>
                <div class="col-xs-3 h4">Zugriff</div>
                <div class="col-xs-3 h4">Ablaufdatum</div>
                <?php
                $res_addmem = $conn->query("SELECT e.id, firstname, lastname FROM external_users e INNER JOIN contactPersons c ON c.id = e.contactID WHERE c.clientID = "
                .$projectRow['clientID']." AND e.id NOT IN (SELECT DISTINCT userID FROM relationship_project_extern WHERE projectID = $projectID)"); echo $conn->error;
                while ($res_addmem && ($row_addmem = $res_addmem->fetch_assoc())) {
                    echo '<div class="row">';
                    echo '<div class="col-sm-6"><label><input type="checkbox" name="externID[]" value="'.$row_addmem['id'].'" >'.$row_addmem['firstname'].' '.$row_addmem['lastname'].'</label><br></div>';
                    echo '<div class="col-sm-3"><select name="externAccess[]" class="form-control">'.$access_select.'</select></div>';
                    echo '<div class="col-sm-3"><input type="text" name="externExpiration[]" class="form-control datepicker" placeholder="Ablaufdatum" /></div>';
                    echo '</div>';
                }
                ?>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-warning" name="hire"><?php echo $lang['ADD']; ?></button>
            </div>
        </form>
    </div>
</div>

<br><hr>
<h4>Dateifreigabe <div class="page-header-button-group">
    <div class="btn-group">
        <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" title="<?php echo $lang['ADD']; ?>" ><i class="fa fa-plus"></i></button>
        <ul class="dropdown-menu">
            <li><a href="#">Entfernt bis S3 Verfügbar</a></li>
        </ul>
    </div>
</div></h4>

<table class="table">
    <thead>
        <tr>
            <td><input type="checkbox" class="form-control" id="allCheck" /></td>
            <td></td>
            <td><label>Name</label></td>
            <td><label>Upload Datum</label></td>
            <td><label>File Size</label></td>
            <td></td>
        </tr>
    </thead>
    <tbody>
        <?php
        function drawTree($parent_structure){
            global $conn;
            global $projectID;
            $html = '';
            $stmt = $conn->prepare("SELECT name, type, parent_directory FROM project_archive WHERE projectID = $projectID AND parent_directory = ? "); echo $conn->error;
            $stmt->bind_param("s", $parent_structure);
            $stmt->execute();
            $result = $stmt->get_result();
            while($result && $row = $result->fetch_assoc()){
                //text, file, s3File, s3Text, folder
                if($row['type'] == 'folder'){
                    return drawTree($row['parent_directory']);
                } elseif($row['type'] == 'text'){
                  $html .= '<tr></tr>';
                }
            }
            return $html;
        }

        echo drawTree('ROOT');
        ?>
    </tbody>
</table>
<?php require dirname(__DIR__).DIRECTORY_SEPARATOR.'footer.php'; ?>
