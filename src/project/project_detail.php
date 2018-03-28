<?php require dirname(__DIR__).DIRECTORY_SEPARATOR.'header.php'; ?>

<?php
if(!isset($_GET['p'])) die('Invalid access');
$projectID = intval($_GET['p']);
$result = $conn->query("SELECT p.*, c.name AS clientName FROM projectData p LEFT JOIN clientData c ON p.clientID = c.id WHERE p.id = $projectID");
$projectRow = $result->fetch_assoc();

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
}
?>

<div class="page-header">
    <h3><?php echo $projectRow['clientName'].' - '.$projectRow['name']; ?>
        <div class="page-header-button-group">
            <button type="button" class="btn btn-default" title="<?php echo $lang['SAVE']; ?>" ><i class="fa fa-floppy-o"></i></button>
        </div>
    </h3>
</div>

<h4>Allgemein <div class="page-header-button-group">
    <button type="button" class="btn btn-default" data-toggle="modal" data-target=".add-member" title="<?php echo $lang['ADD']; ?>" ><i class="fa fa-plus"></i></button>
</div></h4>

<div class="row">
    <div class="col-lg-4">
        <label>Public Key</label><br>
        <?php echo $projectRow['publicKey']; ?>
    </div>
    <div class="col-sm-4">
        <form method="POST">
        <?php if(!empty($_POST['unlockProjectKeyDownload']) && crypt($_POST['unlockProjectKeyDownload'], $userPasswordHash) == $userPasswordHash): ?>
            <input type="hidden" name="personal" value="<?php echo $projectRow['symmetricKey']."\n".$projectRow['publicKey']; ?>" /><br>
            <button type="submit" class="btn btn-warning" formaction="../setup/keys" formtarget="_blank" name="">Keypair Download</button>
        <?php else: ?>
            <label><?php echo $lang['PASSWORD_CURRENT'] ?></label><br>
            <small>Zum entsperren des Schlüsselpaar Downloads</small>
            <div class="input-group">
                <input type="password" name="unlockProjectKeyDownload" class="form-control" autocomplete="new-password">
                <span class="input-group-btn">
                    <button type="submit" class="btn btn-warning">Entsperren</button>
                </span>
            </div>
            <?php if(isset($_POST['unlockProjectKeyDownload'])) echo '<small style="color:red">Falsches Passwort</small>'; ?>
        <?php endif; ?>
        </form>
    </div>
</div>


<br><hr>
<h4>Benutzer <div class="page-header-button-group">
    <button type="button" class="btn btn-default" data-toggle="modal" data-target=".add-member" title="<?php echo $lang['ADD']; ?>" ><i class="fa fa-plus"></i></button>
</div></h4>

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
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning" name="hire"><?php echo $lang['ADD']; ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<br><hr>
<h4>Dateifreigabe <div class="page-header-button-group">
    <button type="button" class="btn btn-default" data-toggle="modal" data-target=".add-archive" title="<?php echo $lang['ADD']; ?>" ><i class="fa fa-plus"></i></button>
</div></h4>
<?php require dirname(__DIR__).DIRECTORY_SEPARATOR.'footer.php'; ?>
