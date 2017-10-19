<?php require 'header.php'; enableToCore($userID);?>
<?php
if($_SERVER['REQUEST_METHOD'] == 'POST'){
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
  $conn->query("UPDATE $policyTable SET passwordLength = $length, complexity = '$compl', expiration = '$exp', expirationDuration = $dur, expirationType = '$type'");
  echo mysqli_error($conn);
    
  //masterpassword
  if(isset($_POST['masterPass_deactivate']) && !empty($_SESSION['masterpassword'])){ //he may only deactivate if he knows the current password
    mc_update_values($_SESSION['masterpassword'], '', 'removed');    
    $conn->query("UPDATE $configTable SET masterPassword = ''");
  } elseif(isset($_POST['masterPass_deactivate'])){
    //STRIKE
    echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$lang['ERROR_UNEXPECTED'].'</div>';
  } elseif(!empty($_POST['masterPass_new']) && !empty($_POST['masterPass_newConfirm']) && (isset($_POST['masterPass_current'])||!$masterPassword)){
    $current = '';
    if($masterPassword){ $current = $_POST['masterPass_current']; }
    if($_POST['masterPass_new'] == $_POST['masterPass_newConfirm']  && (!$masterPassword || crypt($_POST['masterPass_current'], $masterPassword) == $masterPassword )){
      mc_update_values($current, $_POST['masterPass_new'], 'changed'); 
      $_SESSION['masterpassword'] = $_POST['masterPass_new'];
      $new_master = password_hash($_POST['masterPass_new'], PASSWORD_BCRYPT);
      $conn->query("UPDATE $configTable SET masterPassword = '$new_master'");
      echo $conn->error;
    } else {
      echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a><strong>Error: </strong>Ungültige Passwort Eingaben</div>';
    }
  }
}

$result = $conn->query("SELECT * FROM $policyTable");
$row = $result->fetch_assoc();
?>


<form method="POST" id="formPasswordOptions">
  <div class="page-header">
    <h3><?php echo $lang['PASSWORD'].' '.$lang['OPTIONS']; ?>
      <div class="page-header-button-group">
        <button type="submit" class="btn btn-default blinking" name="saveButton" title="Save"><i class="fa fa-floppy-o"></i></button>
      </div>
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
  <div class="container-fluid">
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
      <input type="radio" name="enableTimechange_type" value="ALERT" <?php if($row['expirationType'] == 'ALERT'){echo 'checked';} ?>/> Optional
    </div>
    <div class="col-md-4">
      <input type="radio" name="enableTimechange_type" value="FORCE" <?php if($row['expirationType'] == 'FORCE'){echo 'checked';} ?>/> Zwingend
    </div>
  </div>

  <br><hr><br>

  <h4><?php echo mc_status(); ?><?php echo $lang["MASTER_PASSWORD"]; ?> <small><?php if($masterPassword): echo $lang["ENCRYPTION_ACTIVE"]; else: echo $lang["ENCRYPTION_DEACTIVATED"]; endif;?></small><a role="button" data-toggle="collapse" href="#password_info_master"><i class="fa fa-info-circle"></i></a></h4>
  <br>
  <div class="collapse" id="password_info_master">
    <div class="well">
      Das Masterpasswort wird zum verschlüsseln sensibler Daten verwendet, die nur unter eingabe des Passworts wieder entschlüsselt werden können. Es sind insgesamt <b><?php echo mc_total_row_count(); ?></b> Einträge betroffen.
    </div>
  </div>
  <br>
  <div class="container-fluid">
    <?php if($masterPassword): ?>
      <div class="col-md-4">
      <?php echo $lang["MASTER_PASSWORD_CURRENT"]; ?>:
      </div>
      <div class="col-md-8">
        <input type="password" class="form-control" name="masterPass_current" value=""/>
      </div>
      <br><br>
    <?php endif; ?>
    <div class="col-md-4">
    <?php echo $lang["MASTER_PASSWORD_NEW"]; ?>:
    </div>
    <div class="col-md-8">
      <input type="password" class="form-control" name="masterPass_new" value=""/>
    </div>
    <br><br>
    <div class="col-md-4">
    <?php echo $lang["MASTER_PASSWORD_CONFIRM"]; ?>:
    </div>
    <div class="col-md-8">
      <input type="password" class="form-control" name="masterPass_newConfirm" value=""/>
    </div>
    <br><br>
    <?php if(!empty($_SESSION['masterpassword'])): ?>
      <div class="col-md-4">
      <?php echo $lang["ENCRYPTION_DEACTIVATE"]; ?>:
      </div>
      <div class="col-md-8"><button type="submit" class="btn btn-warning" name="masterPass_deactivate" value="true"><?php echo $lang["ENCRYPTION_DEACTIVATE"]; ?></button></div>
      <br><br><br>
    <?php endif; ?>
    <div class="col-md-4">
      <a href="../system/cryptlog" target="_blank"><?php echo $lang["ENCRYPTION_LOG"]; ?> </a>
    </div>
    <br><br><br>
  </div>
</form>
<script>
  $("#formPasswordOptions").submit(function(event){
    if($("input[name='masterPass_deactivate']").is(":checked")){
      alert("<?php echo mc_list_changes(); ?>");
      if (confirm("<?php echo $lang['PASSWORD_REMOVE_PROMPT'];?>") == true) {
        return true;
      } else {
        event.preventDefault();
        return false;
      }
    } else if ($("input[name='masterPass_new']").val() == $("input[name='masterPass_newConfirm']").val()){
      alert("<?php echo mc_list_changes(); ?>");
      if (confirm("<?php echo $lang['PASSWORD_CHANGE_PROMPT'];?>") == true) {
        return true;
      } else {
        event.preventDefault();
        return false;
      }
    }
  });
</script>

<?php require 'footer.php'; ?>
