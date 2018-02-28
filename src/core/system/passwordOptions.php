<?php include dirname(dirname(__DIR__)) . '/header.php'; enableToCore($userID);?>
<?php require dirname(dirname(__DIR__)) . "/misc/helpcenter.php"; ?>
<?php
if(isset($_POST['saveButton'])){
    //general
    $length = intval($_POST['passwordLength']);
    $compl = intval($_POST['passwordComplexity']);

    //expiration
    $exp = 'FALSE';
    $dur = 0;
    if(isset($_POST['enableTimechange'])){
        if(isset($_POST['enableTimechange_months'])){
            $exp = 'TRUE';
            $dur = intval($_POST['enableTimechange_months']);
        } else {
            echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a><strong>Error: </strong>Please define months when activating password expiration .</div>';
        }
    }
    $type = test_input($_POST['enableTimechange_type']);
    $conn->query("UPDATE policyData SET passwordLength = $length, complexity = '$compl', expiration = '$exp', expirationDuration = $dur, expirationType = '$type'");
    echo mysqli_error($conn);
}

$result = $conn->query("SELECT * FROM policyData");
$row = $result->fetch_assoc();
?>

<form method="POST">
    <div class="page-header">
        <h3><?php echo $lang['PASSWORD'].' '.$lang['OPTIONS']; ?>
            <div class="page-header-button-group"><button type="submit" class="btn btn-default blinking" name="saveButton" title="Save"><i class="fa fa-floppy-o"></i></button></div>
        </h3>
    </div>
    <h4><?php echo $lang['ADMIN_CORE_OPTIONS']; ?> <a role="button" data-toggle="collapse" href="#password_info_general"> <i class="fa fa-info-circle"> </i> </a></h4>
    <br>
    <div class="collapse" id="password_info_general">
        <div class="well"><?php echo $lang['INFO_PASSWORD_GENERAL']; ?></div>
    </div>
    <br>
    <div class="container-fluid">
        <div class="col-md-4">
            <?php echo $lang['PASSWORD_MINLENGTH'] ?>:
        </div>
        <div class="col-md-8">
            <input type="number" class="form-control" name="passwordLength" value="<?php echo $row['passwordLength']; ?>" />
        </div>
        <br><br><br>
        <div class="col-md-4">
            <?php echo $lang['COMPLEXITY']; ?>:
        </div>
        <div class="col-md-4">
            <select class="form-control" name="passwordComplexity">
                <option value="0" <?php if($row['complexity'] === '0'){echo 'selected';} ?>><?php echo $lang['COMPLEXITY_TOSTRING']['SIMPLE']; ?></option>
                <option value="1" <?php if($row['complexity'] === '1'){echo 'selected';} ?>><?php echo $lang['COMPLEXITY_TOSTRING']['MEDIUM']; ?></option>
                <option value="2" <?php if($row['complexity'] === '2'){echo 'selected';} ?>><?php echo $lang['COMPLEXITY_TOSTRING']['STRONG']; ?></option>
            </select>
        </div>
    </div>

    <br><hr><br>

    <div class="row">
        <div class="col-xs-8">
            <h4><?php echo $lang['EXPIRATION_DATE']; ?> <a role="button" data-toggle="collapse" href="#password_info_expiry"><i class="fa fa-info-circle"></i></a></h4>
        </div>
        <div class="col-xs-2 checkbox">
            <input type="checkbox" value="person" name="enableTimechange"  <?php if($row['expiration'] == 'TRUE'){echo 'checked';} ?> /> Aktiv
        </div>
    </div>
    <br>
    <div class="collapse" id="password_info_expiry">
        <div class="well">
            <?php echo $lang['INFO_EXPIRATION']; ?>
        </div>
    </div>
    <br>
    <div class="row">
        <div class="col-md-4">
            Änderung nach Monaten:
        </div>
        <div class="col-md-8">
            <input type="number" class="form-control" name="enableTimechange_months" value="<?php echo $row['expirationDuration']; ?>"/>
        </div>
        <br><br><br>
        <div class="col-md-4">
            Art:
        </div>
        <div class="col-md-4">
            <input type="radio" name="enableTimechange_type" value="ALERT" <?php if($row['expirationType'] != 'FORCE'){echo 'checked';} ?>/> Optional
        </div>
        <div class="col-md-4">
            <input type="radio" name="enableTimechange_type" value="FORCE" <?php if($row['expirationType'] == 'FORCE'){echo 'checked';} ?>/> Zwingend
        </div>
    </div>

    <br><hr><br>
</form>

<form method="POST">
    <?php
    $result = $conn->query("SELECT activeEncryption FROM configurationData");
    if($result && ($row = $result->fetch_assoc())){
        if($row['activeEncryption'] == 'TRUE'){
            echo '<button type="submit" name="deactive_encryption">Verschlüsselung DEAKTIVIEREN</button>';
        } else {
            echo '<div class="row">
            <div class="col-md-4">
            <label>Neues Passwort</label>
            <input type="password" name="encryption_pass" class="form-control" />
            </div>
            <div class="col-md-4">
            <label>Neues Passwort Bestätigen</label>
            <input type="password" name="encryption_pass_confirm" class="form-control" />
            </div>
            <div class="col-md-4">
            <label>OK</label><br>
            <button type="submit" name="active_encryption" class="btn btn-warning" >Verschlüsselung Aktivieren</button>
            </div>
            </div>';
        }
    }
    ?>
</form>

<?php include dirname(dirname(__DIR__)) . '/footer.php'; ?>
