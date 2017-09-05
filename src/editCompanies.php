<?php include 'header.php'; enableToCore($userID);?>
<!-- BODY -->
<?php
if(empty($_GET['cmp']) || !in_array($_GET['cmp'], $available_companies)){die("Invalid Access");}
$cmpID = intval($_GET['cmp']);

if($_SERVER["REQUEST_METHOD"] == "POST"){
  if(isset($_POST['deleteCompany'])){
    if($cmpID == 1){
      echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert">&times;</a>'.$lang['ERROR_DELETE_COMPANY'].'</div>';
    } else {
      $sql = "DELETE FROM companyData WHERE id = $cmpID;";
      $conn->query($sql);
      echo mysqli_error($conn);
    }
  } elseif(isset($_POST['delete_logo'])){
    if(!mysqli_error($conn)){
      $conn->query("UPDATE companyData SET logo = '' WHERE id = $cmpID");
    }
    if($conn->error){ echo $conn->error;} else {echo '<div class="alert alert-success"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$lang['OK_DELETE'].'</div>';}
  } elseif(isset($_POST['save_logo'])){
    require_once __DIR__ . "/utilities.php";
    $logo = uploadFile("fileToUpload", 1); //returns array on false
    if(!is_array($logo)){
      $stmt = $conn->prepare("UPDATE companyData SET logo = ? WHERE id = $cmpID"); 
      $null = NULL;
      $stmt->bind_param("b", $null);
      $stmt->send_long_data(0, $logo);
      $stmt->execute();
      if($stmt->errno){ echo $stmt->error;} else { echo '<div class="alert alert-success"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$lang['OK_SAVE'].'</div>'; }
      $stmt->close();
    } else {
      echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>'.print_r($filename).'</div>';
    }
  } elseif(isset($_POST['general_save'])){
    function max4Lines($str){
      $str = preg_replace("~[^A-Za-z0-9\-?!=:.,/@€$%()+*öäüÖÄÜß\\n ]~", "", $str);
      while(substr_count($str, "\n") > 3){
        $str = substr_replace($str, ' ', strpos($str, "\n"), 1); //remove first occurence
      }
      return $str;
    }
    $descr = test_input($_POST['general_description']);
    $address = test_input($_POST['general_address']);
    $plz = test_input($_POST['general_postal']);
    $city = test_input($_POST['general_city']);
    $uid = test_input($_POST['general_uid']);
    $phone = test_input($_POST['general_phone']);
    $mail = test_input($_POST['general_mail']);
    $homepage = test_input($_POST['general_homepage']);
    $erpText = test_input($_POST['general_erpText']);
    $left = max4Lines($_POST['general_detail_left']);
    $middle = max4Lines($_POST['general_detail_middle']);
    $right = max4Lines($_POST['general_detail_right']);
    $conn->query("UPDATE companyData SET cmpDescription = '$descr',address = '$address', phone = '$phone', mail = '$mail', homepage = '$homepage', erpText = '$erpText',
      detailLeft = '$left', detailMiddle = '$middle', detailRight = '$right', companyPostal = '$plz', uid = '$uid', companyCity = '$city' WHERE id = $cmpID");
    echo mysqli_error($conn);
  } elseif(isset($_POST['createNewProject']) && !empty($_POST['name'])){
   $name = test_input($_POST['name']);
   if(isset($_POST['status'])){
     $status = "checked";
   } else {
     $status = "";
   }
   $hourlyPrice = floatval(test_input($_POST['hourlyPrice']));
   $hours = floatval(test_input($_POST['hours']));

   if(isset($_POST['createField_1'])){ $field_1 = 'TRUE'; } else { $field_1 = 'FALSE'; }
   if(isset($_POST['createField_2'])){ $field_2 = 'TRUE'; } else { $field_2 = 'FALSE'; }
   if(isset($_POST['createField_3'])){ $field_3 = 'TRUE'; } else { $field_3 = 'FALSE'; }

   $sql = "INSERT INTO $companyDefaultProjectTable(companyID, name, status, hourlyPrice, hours, field_1, field_2, field_3) VALUES($cmpID, '$name', '$status', '$hourlyPrice', '$hours', '$field_1', '$field_2', '$field_3')";
   if($conn->query($sql)){ //add default project to all clients with the company. pow.;
     $conn->query("INSERT IGNORE INTO projectData (clientID, name, status, hours, hourlyPrice, field_1, field_2, field_3) SELECT id,'$name', '$status', '$hours', '$hourlyPrice', '$field_1', '$field_2', '$field_3' FROM $clientTable WHERE companyID = $cmpID");
     if($conn->error){ echo $conn->error; } else { echo '<div class="alert alert-success"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$lang['OK_CREATE'].'</div>'; }
   }
   echo mysqli_error($conn);
 } elseif(isset($_POST['save_defaultProjects']) && isset($_POST['default_projectNames'])){
   if(empty($_POST['default_statii'])){
     $_POST['default_statii'] = array();
   }
   for($i = 0; $i < count($_POST['default_projectNames']); $i++){
     $projectName = test_input($_POST['default_projectNames'][$i]);
     $defaultID = intval($_POST['default_projectIDs'][$i]);
     $hours = floatval(test_input($_POST['default_boughtHours'][$i]));
     $hourlyPrice = floatval(test_input($_POST['default_pricedHours'][$i]));
     if(in_array($defaultID, $_POST['default_statii'])){
       $status = "checked";
     } else {
       $status = "";
     }
     //checkboxes are not set at all if they're not checked
     if(isset($_POST['default_addField_1_'.$defaultID])){ $field_1 = 'TRUE'; } else { $field_1 = 'FALSE'; }
     if(isset($_POST['default_addField_2_'.$defaultID])){ $field_2 = 'TRUE'; } else { $field_2 = 'FALSE'; }
     if(isset($_POST['default_addField_3_'.$defaultID])){ $field_3 = 'TRUE'; } else { $field_3 = 'FALSE'; }

     $sql = "UPDATE $companyDefaultProjectTable SET hours = '$hours', status = '$status', hourlyPrice = '$hourlyPrice', field_1 = '$field_1', field_2 = '$field_2', field_3 = '$field_3' WHERE id = $defaultID";
     $conn->query($sql);

     $sql = "UPDATE projectData SET hourlyPrice = '$hourlyPrice', status='$status', field_1 = '$field_1', field_2 = '$field_2', field_3 = '$field_3'
     WHERE name= '$projectName' AND clientID IN (SELECT id FROM $clientTable WHERE companyID = $cmpID)";
     $conn->query($sql);
   }
   if($conn->error){ echo $conn->error; } else { echo '<div class="alert alert-success"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$lang['OK_ADD'].'</div>'; }
 } elseif(isset($_POST['delete_defaultProjects'])){
   if(isset($_POST['indexProject'])){
     foreach ($_POST['indexProject'] as $i) {
       $sql = "DELETE FROM $companyDefaultProjectTable WHERE id = $i";
       $conn->query($sql);
     }
   }
   if($conn->error){ echo $conn->error; } else { echo '<div class="alert alert-success"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$lang['OK_DELETE'].'</div>'; }
 } elseif(isset($_POST['save_erp_numbers'])){
   $erp_ang = abs(intval($_POST['erp_ang']));
   $erp_aub = abs(intval($_POST['erp_aub']));
   $erp_re = abs(intval($_POST['erp_re']));
   $erp_lfs = abs(intval($_POST['erp_lfs']));
   $erp_gut = abs(intval($_POST['erp_gut']));
   $erp_stn = abs(intval($_POST['erp_stn']));
   $conn->query("UPDATE erpNumbers SET erp_ang = $erp_ang, erp_aub = $erp_aub, erp_re = $erp_re, erp_lfs = $erp_lfs, erp_gut = $erp_gut, erp_stn = $erp_stn WHERE companyID = $cmpID");
   if($conn->error){ echo '<div class="alert alert-danger alert-over"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$conn->error.'</div>'; } else {
     echo '<div class="alert alert-success alert-over"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$lang['OK_SAVE'].'</div>';
   }
 }
 if(isset($_POST['hire']) && isset($_POST['hiring_userIDs'])){
   foreach($_POST['hiring_userIDs'] as $i){
     $conn->query("INSERT INTO $companyToUserRelationshipTable (companyID, userID) VALUES ($cmpID, $i)");
     if($conn->error){ echo $conn->error; } else { echo '<div class="alert alert-success"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$lang['OK_ADD'].'</div>'; }
   }
 } elseif(isset($_POST['delete_assigned_users']) && isset($_POST['indexUser'])){
   foreach ($_POST['indexUser'] as $i) {
     $sql = "DELETE FROM $companyToUserRelationshipTable WHERE userID = $i AND companyID = $cmpID";
     $conn->query($sql);
   }
   if($conn->error){ echo $conn->error; } else { echo '<div class="alert alert-success"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$lang['OK_DELETE'].'</div>'; }
 }
 if(isset($_POST['save_additional_fields'])){
   for($i = 1; $i < 4; $i++){
     //linear mapping of f(x) = x*3 -2; f1(x) = f(x) + 1; f2(x) = f1(x)+1;
     $id_save = $cmpID * 3 - 3 + $i;
     if(!empty($_POST['name_'.$i])){
       $name = test_input($_POST['name_'.$i]);
       $description = test_input($_POST['description_'.$i]);
       $active = !empty($_POST['active_'.$i]) ? 'TRUE' : 'FALSE';
       $required = !empty($_POST['required_'.$i]) ? 'TRUE' : 'FALSE';
       $forall = !empty($_POST['forall_'.$i]) ? 'TRUE' : 'FALSE';
       if($active == 'FALSE'){
         $forall = 'FALSE';
       }
       $result = $conn->query("SELECT * FROM $companyExtraFieldsTable WHERE id = $id_save");
       if($result->num_rows > 0){
         $row = $result->fetch_assoc();
         $forall_old = $row['isForAllProjects'];
         $conn->query("UPDATE $companyExtraFieldsTable SET name='$name', isActive = '$active' , isRequired = '$required', isForAllProjects = '$forall', description = '$description' WHERE id = $id_save");
       } else {
         $forall_old = 'FALSE';
         $conn->query("INSERT INTO $companyExtraFieldsTable (id, companyID, name, isActive, isRequired, isForAllProjects, description) VALUES ($id_save, $cmpID, '$name', '$active', '$required', '$forall', '$description')");
       }
       echo mysqli_error($conn);
       if($forall_old == 'FALSE' && $forall == 'TRUE'){
         $conn->query("UPDATE $projectTable, $clientTable SET $projectTable.field_$i = '$active' WHERE $clientTable.companyID = $cmpID AND $clientTable.id = $projectTable.clientID");
       } elseif($forall_old == 'TRUE' && $forall == 'FALSE'){
         $conn->query("UPDATE $projectTable, $clientTable SET $projectTable.field_$i = 'FALSE' WHERE $clientTable.companyID = $cmpID AND $clientTable.id = $projectTable.clientID");
       }
     } elseif(!empty($_POST['active_'.$i])){ //if name is empty, but the active button is not
       echo '<div class="alert alert-danger fade in">';
       echo '<a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>';
       echo "<strong>Could not save Field nr. $i : </strong> Cannot set active field without a name";
       echo '</div>';
     }
   }
 } elseif(isset($_POST['field_add_save']) && !empty($_POST['field_add_name'])){
   $i = intval($_POST['field_add_save']);
   if(0 < $i && $i < 4){
     $id_save = $cmpID * 3 - 3 + $i;
     $name = test_input($_POST['field_add_name']);
     $description = test_input($_POST['field_add_description']);
     $active = !empty($_POST['field_add_active']) ? 'TRUE' : 'FALSE';
     $required = !empty($_POST['field_add_required']) ? 'TRUE' : 'FALSE';
     $forall = !empty($_POST['field_add_forall']) ? 'TRUE' : 'FALSE';
     $conn->query("INSERT INTO $companyExtraFieldsTable (id, companyID, name, isActive, isRequired, isForAllProjects, description) VALUES ($id_save, $cmpID, '$name', '$active', '$required', '$forall', '$description')");
   } else {
     echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert">&times;</a>'.$lang['ERROR_UNEXPECTED'].'</div>';
   }
 }

}
$result = $conn->query("SELECT * FROM $companyTable WHERE id = $cmpID");
if ($result && ($row = $result->fetch_assoc()) && in_array($row['id'], $available_companies)):
  ?>

<div class="page-seperated-body">
<div class="page-header page-seperated-section">
  <h3><?php echo $lang['COMPANY'] .' - '.$row['name']; ?>
    <div class="page-header-button-group">
      <button type="button" class="btn btn-default btn-sm" data-toggle="modal" data-target=".cmp-delete-confirm-modal" title="<?php echo $lang['DELETE']; ?>"><i class="fa fa-trash-o"></i></button>
    </div>
  </h3>
</div>
<form method="POST">
  <div class="modal fade cmp-delete-confirm-modal" tabindex="-1" role="dialog" aria-labelledby="mySmallModalLabel">
    <div class="modal-dialog modal-sm" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h4 class="modal-title"><?php echo sprintf($lang['ASK_DELETE'], $row['name']); ?></h4>
        </div>
        <div class="modal-body">
          <?php echo $lang['WARNING_DELETE_COMPANY']; ?>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo $lang['CONFIRM_CANCEL']; ?></button>
          <button type="submit" name='deleteCompany' class="btn btn-warning"><?php echo $lang['CONFIRM']; ?></button>
        </div>
      </div>
    </div>
  </div>
</form>


<!-- LOGO -->
<form method="post" enctype="multipart/form-data" class="page-seperated-section">
  <div class="container-fluid">
    <div class="row">
      <h4>Logo
        <div class="page-header-button-group">
          <button type="submit" name="delete_logo" class="btn btn-default"><i class="fa fa-trash-o"></i></button>
          <button type="submit" name="save_logo" class="btn btn-default" value="<?php echo $cmpID;?>"><i class="fa fa-floppy-o"></i></button>
        </div>
      </h4>
    </div>
  </div>
  <div class="container-fluid">
    <div class="col-sm-4">
      <?php if($row['logo']){echo '<img style="max-width:350px;max-height:200px;" src="data:image/jpeg;base64,'.base64_encode( $row['logo'] ).'"/>';} ?>
    </div>
    <div class="col-sm-8">
      <input type="file" name="fileToUpload" id="fileToUpload" />
    </div>
  </div>
</form>
<br>

<!-- GENERAL -->
<form method="POST" class="page-seperated-section">
  <div class="container-fluid">
    <div class="row">
      <h4>
        <?php echo $lang['GENERAL_SETTINGS']; ?>
        <div class="page-header-button-group"><button type="submit" name="general_save" class="btn btn-default blinking" value="<?php echo $cmpID;?>" title="<?php echo $lang['SAVE']; ?>"><i class="fa fa-floppy-o"></i></button></div>
      </h4>
    </div>
    <br>
    <div class="row">
      <div class="col-sm-3">
        <label><?php echo $lang['COMPANY_NAME']; ?></label>
      </div>
      <div class="col-sm-9">
        <input type="text" class="form-control" name="general_description" value="<?php echo $row['cmpDescription'];?>" />
      </div>
    </div>
    <div class="row">
      <div class="col-sm-3">
        <label><?php echo $lang['ADDRESS']; ?></label>
      </div>
      <div class="col-sm-9">
        <input type="text" class="form-control" name="general_address" placeholder="Address" value="<?php echo $row['address'];?>" />
      </div>
    </div>
    <div class="row">
      <div class="col-sm-3">
        <label><?php echo $lang['PLZ']; ?></label>
      </div>
      <div class="col-sm-4">
        <input type="text" class="form-control" name="general_postal" value="<?php echo $row['companyPostal'];?>" />
      </div>
      <div class="col-sm-1 text-center">
        <label><?php echo $lang['CITY']; ?></label>
      </div>
      <div class="col-sm-4">
        <input type="text" class="form-control" name="general_city" value="<?php echo $row['companyCity'];?>" />
      </div>
    </div>
    <div class="row">
      <div class="col-sm-3">
        <label>UID</label>
      </div>
      <div class="col-sm-9">
        <input type="text" class="form-control" name="general_uid" value="<?php echo $row['uid'];?>" />
      </div>
    </div>
    <div class="row">
      <div class="col-sm-3">
        <label><?php echo $lang['PHONE_NUMBER']; ?></label>
      </div>
      <div class="col-sm-9">
        <input type="text" class="form-control" name="general_phone" placeholder="Tel nr." value="<?php echo $row['phone'];?>"/>
      </div>
    </div>
    <div class="row">
      <div class="col-sm-3">
        <label>E-Mail</label>
      </div>
      <div class="col-sm-9">
        <input type="mail" class="form-control"  name="general_mail" placeholder="E-Mail" value="<?php echo $row['mail'];?>" />
      </div>
    </div>
    <div class="row">
      <div class="col-sm-3">
        <label>Homepage</label>
      </div>
      <div class="col-sm-9">
        <input type="text" class="form-control"  name="general_homepage" placeholder="Homepage" value="<?php echo $row['homepage'];?>" />
      </div>
    </div>
    <div class="row">
      <div class="col-sm-3">
        <label>ERP Text</label>
      </div>
      <div class="col-sm-9">
        <textarea name="general_erpText" class="form-control" placeholder=""><?php echo $row['erpText'];?></textarea>
      </div>
    </div>
    <div class="row">
      <div class="col-sm-3">
        <label>Detail (<?php echo $lang['FOOT_NOTE']; ?>)</label>
      </div>
      <div class="col-sm-3">
        <textarea name="general_detail_left" class="form-control" placeholder="" maxlength="140" rows="3"><?php echo $row['detailLeft'];?></textarea>
      </div>
      <div class="col-sm-3 text-center">
        <textarea name="general_detail_middle" class="form-control" placeholder="" maxlength="140" rows="3"><?php echo $row['detailMiddle'];?></textarea>
      </div>
      <div class="col-sm-3 text-right">
        <textarea name="general_detail_right" class="form-control" placeholder="" maxlength="140" rows="3"><?php echo $row['detailRight'];?></textarea>
      </div>
    </div>
  </div>
</form>
<br>

<!-- DEFAULT PROJECTS -->
<form method="POST" class="page-seperated-section">
  <div class="container-fluid">
    <div class="row">
      <h4>
        <?php echo $lang['DEFAULT'] . " " . $lang['PROJECT']; ?>
        <div class="page-header-button-group">
          <button type="button" class="btn btn-default" data-toggle="modal" data-target=".cmp-new-project-modal" title="<?php echo $lang['ADD']; ?>"><i class="fa fa-plus"></i></button>
          <button type="submit" class="btn btn-default" name="delete_default_projects" title="<?php echo $lang['DELETE']; ?>"><i class="fa fa-trash-o"></i></button>
          <button type="button" class="btn btn-default" data-toggle="modal" data-target=".cmp-edit-project-modal" title="<?php echo $lang['EDIT']; ?>"><i class="fa fa-pencil"></i></button>
        </div>
      </h4>
    </div>
  </div>
  <table class="table table-hover">
    <thead>
      <th><?php echo $lang['DELETE']; ?></th>
      <th>Name</th>
      <th>Status</th>
      <th><?php echo $lang['ADDITIONAL_FIELDS']; ?></th>
      <th><?php echo $lang['HOURS']; ?></th>
      <th><?php echo $lang['HOURLY_RATE']; ?></th>
    </thead>
    <tbody>
      <?php
      $query = "SELECT * FROM $companyDefaultProjectTable WHERE companyID = $cmpID";
      $projectResult = mysqli_query($conn, $query);
      if($projectResult && $projectResult->num_rows > 0){
        while($projectRow = $projectResult->fetch_assoc()){
          $i = $projectRow['id'];
          $projectRowStatus = (!empty($projectRow['status']))? $lang['PRODUCTIVE']:'';
          echo "<tr><td><input type='checkbox' name='indexProject[]' value='$i'></td>";
          echo "<td>".$projectRow['name']."</td>";
          echo "<td> $projectRowStatus </td>";
          echo '<td>';
          $j = 1;
          $extraResult = $conn->query("SELECT * FROM $companyExtraFieldsTable WHERE companyID = $cmpID AND isActive = 'TRUE' ORDER BY id ASC");
          while($extraResult && ($extraRow = $extraResult->fetch_assoc())){ if($projectRow['field_'.$j] == 'TRUE'){echo $extraRow['name'] .'<br>';} $j++; }
          echo '</td>';
          echo "<td>".$projectRow['hours']."</td>";
          echo "<td>".$projectRow['hourlyPrice']."</td></tr>";
        }
      }
      ?>
    </tbody>
  </table>

  <div class="modal fade cmp-new-project-modal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h4 class="modal-title"><?php echo $lang['ADD']; ?></h4>
        </div>
        <div class="modal-body">
          <input type="text" class="form-control" name="name" placeholder="Name">
          <br>
          <div class="row">
            <div class="col-md-6">
              <input type="number" step="any" class="form-control" name="hours" placeholder="Hours">
            </div>
            <div class="col-md-6">
              <input type="number" step="any" class="form-control" name="hourlyPrice" placeholder="Price/Hour">
            </div>
          </div>
          <br>
          <div class="row">
            <div class="col-md-6" style="padding-left:50px;">
              <div class="checkbox"><input type="checkbox" name="status" value="checked" checked> <i class="fa fa-tags"></i> <?php echo $lang['PRODUCTIVE']; ?></div>
            </div>
            <div class="col-md-6" style="padding-left:50px;">
              <div class="checkbox">
                <?php
                $resF = $conn->query("SELECT * FROM $companyExtraFieldsTable WHERE companyID = $cmpID ORDER BY id ASC");
                if($resF->num_rows > 0){
                  $rowF = $resF->fetch_assoc();
                  if($rowF['isActive'] == 'TRUE'){
                    $checked = $rowF['isForAllProjects'] == 'TRUE' ? 'checked': '';
                    echo '<input type="checkbox" '.$checked.' name="createField_1"/>'. $rowF['name'];
                  }
                }
                if($resF->num_rows > 1){
                  $rowF = $resF->fetch_assoc();
                  if($rowF['isActive'] == 'TRUE'){
                    $checked = $rowF['isForAllProjects'] == 'TRUE' ? 'checked': '';
                    echo '<br><input type="checkbox" '.$checked.' name="createField_2" />'. $rowF['name'];
                  }
                }
                if($resF->num_rows > 2){
                  $rowF = $resF->fetch_assoc();
                  if($rowF['isActive'] == 'TRUE'){
                    $checked = $rowF['isForAllProjects'] == 'TRUE' ? 'checked': '';
                    echo '<br><input type="checkbox" '.$checked.' name="createField_3" />'. $rowF['name'];
                  }
                }
                ?>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-warning" name='createNewProject'> <?php echo $lang['ADD']; ?> </button>
        </div>
      </div>
    </div>
  </div>
  <div class="modal fade cmp-edit-project-modal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg modal-content" role="document">
      <div class="modal-header">
        <h4 class="modal-title"><?php echo $lang['DEFAULT'] .' '.$lang['PROJECT']; ?></h4>
      </div>
      <div class="modal-body">
        <table class="table table-hover">
          <thead>
            <th>Name</th>
            <th><?php echo $lang['PRODUCTIVE']; ?></th>
            <th><?php echo $lang['ADDITIONAL_FIELDS']; ?></th>
            <th><?php echo $lang['HOURS']; ?></th>
            <th><?php echo $lang['HOURLY_RATE']; ?></th>
            <th></th>
          </thead>
          <tbody>
            <?php
            $result = $conn->query("SELECT * FROM $companyDefaultProjectTable WHERE companyID = $cmpID");
            echo mysqli_error($conn);
            while($row = $result->fetch_assoc()){
              echo '<tr>';
              echo '<td>'. $row['name'] .'</td>';
              echo '<td><div class="checkbox text-center"><input type="checkbox" name="default_statii[]" '. $row['status'] .' value="'.$row['id'].'"> <i class="fa fa-tags"></i></div></td>';
              echo '<td><small>';
              $resF = $conn->query("SELECT * FROM $companyExtraFieldsTable WHERE companyID = $cmpID ORDER BY id ASC");
              if($resF->num_rows > 0){
                $rowF = $resF->fetch_assoc();
                if($rowF['isActive'] == 'TRUE'){
                  $checked = $row['field_1'] == 'TRUE' ? 'checked': '';
                  echo '<input type="checkbox" '.$checked.' name="default_addField_1_'.$row['id'].'"/>'. $rowF['name'];
                }
              }
              if($resF->num_rows > 1){
                $rowF = $resF->fetch_assoc();
                if($rowF['isActive'] == 'TRUE'){
                  $checked = $row['field_2'] == 'TRUE' ? 'checked': '';
                  echo '<br><input type="checkbox" '.$checked.' name="default_addField_2_'.$row['id'].'" />'. $rowF['name'];
                }
              }
              if($resF->num_rows > 2){
                $rowF = $resF->fetch_assoc();
                if($rowF['isActive'] == 'TRUE'){
                  $checked = $row['field_3'] == 'TRUE' ? 'checked': '';
                  echo '<br><input type="checkbox" '.$checked.' name="default_addField_3_'.$row['id'].'" />'. $rowF['name'];;
                }
              }
              echo '</small></td>';
              echo '<td><input type="number" class="form-control" step="any" name="default_boughtHours[]" value="'. $row['hours'] .'"></td>';
              echo '<td><input type="number" class="form-control" step="any" name="default_pricedHours[]" value="'. $row['hourlyPrice'] .'"></td>';
              echo '<td><input type="text" class="hidden" name="default_projectNames[]" value="'.$row['name'].'"><input type="text" class="hidden" name="default_projectIDs[]" value="'.$row['id'].'"></td>';
              echo '</tr>';
            }
            ?>
          </tbody>
        </table>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
        <button type=submit class="btn btn-warning" name='save_defaultProjects'> <?php echo $lang['SAVE']; ?> </button>
      </div>
    </div>
  </div>
</form>
<br>

<!-- ASSIGNED USERS -->
<form method="POST" class="page-seperated-section">
  <div class="container-fluid">
    <div class="row">
      <h4><?php echo $lang['ASSIGNED'] . " " . $lang['USERS']; ?>
        <div class="page-header-button-group">
          <button type="button" class="btn btn-default" data-toggle="modal" data-target=".cmp-hire-users-modal" title="<?php echo $lang['ADD']; ?>"><i class="fa fa-plus"></i></button>
          <button type="submit" class="btn btn-default" name="delete_assigned_users" title="<?php echo $lang['DELETE']; ?>"><i class="fa fa-trash-o"></i></button>
        </div>
      </h4>
    </div>
  </div>
  <table class="table table-hover">
    <thead>
      <th><?php echo $lang['DELETE']; ?></th>
      <th>Name</th>
    </thead>
    <tbody>
      <?php
      $query = "SELECT DISTINCT * FROM $userTable
      INNER JOIN $companyToUserRelationshipTable ON $userTable.id = $companyToUserRelationshipTable.userID
      WHERE $companyToUserRelationshipTable.companyID = $cmpID";
      $usersResult = mysqli_query($conn, $query);
      if ($usersResult && $usersResult->num_rows > 0) {
        while ($usersRow = $usersResult->fetch_assoc()) {
          $i = $usersRow['id'];
          echo "<tr><td><input type='checkbox' name='indexUser[]' value= $i></td>";
          echo "<td>".$usersRow['firstname']." ".$usersRow['lastname']."</td></tr>";
        }
      }
      ?>
    </tbody>
  </table>
  <div class="modal fade cmp-hire-users-modal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-md" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h4 class="modal-title"><?php echo $lang['HIRE_USER']; ?></h4>
        </div>
        <div class="modal-body">
          <table class="table table-hover">
            <thead>
              <th>Select</th>
              <th>Name</th>
            </thead>
            <tbody>
              <?php
              $sql = "SELECT * FROM $userTable WHERE id NOT IN (SELECT DISTINCT userID FROM $companyToUserRelationshipTable WHERE companyID = $cmpID) ORDER BY lastname ASC";
              $result = mysqli_query($conn, $sql);
              if($result && $result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {
                  echo '<tr>';
                  echo '<td><input type="checkbox" name="hiring_userIDs[]" value="'.$row['id'].'" ></td>';
                  echo '<td>'.$row['firstname'].' '.$row['lastname'].'</td>';
                  echo '</tr>';
                }
              }
              ?>
            </tbody>
          </table>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-warning" name="hire"><?php echo $lang['HIRE_USER']; ?></button>
        </div>
      </div>
    </div>
  </div>
</form>
<br>


<!-- ADDITIONAL BOOKING FIELDS -->
<?php $fieldResult = $conn->query("SELECT * FROM $companyExtraFieldsTable WHERE companyID = $cmpID"); $i = 1; ?>
<form method="POST" class="page-seperated-section">
  <div class="container-fluid">
    <div class="row">
      <h4><?php echo $lang['ADDITIONAL_FIELDS']; ?>
        <div class="page-header-button-group">
          <?php if($fieldResult->num_rows < 3){ echo '<button type="button" class="btn btn-default" data-toggle="modal" data-target=".cmp-add-fields-modal" title="'.$lang['ADD'].'"><i class="fa fa-plus"></i></button>'; } ?>
          <button type="button" class="btn btn-default" data-toggle="modal" data-target=".cmp-edit-fields-modal" ><i class="fa fa-pencil"></i></button>
        </div>
      </h4>
    </div>
  </div>
  <table class="table table-hover">
    <thead>
      <th><?php echo $lang['ACTIVE']; ?></th>
      <th><?php echo $lang['HEADLINE']; ?></th>
      <th><?php echo $lang['REQUIRED_FIELD']; ?></th>
      <th><?php echo $lang['FOR_ALL_PROJECTS']; ?></th>
      <th><?php echo $lang['DESCRIPTION']; ?></th>
    </thead>
    <tbody>
      <?php
      while ($fieldResult && ($fieldRow = $fieldResult->fetch_assoc())) {
        $i++;
        echo '<tr>';
        echo '<td>'.$fieldRow['isActive'].'</td>';
        echo '<td>'.$fieldRow['name'].'</td>';
        echo '<td>'.$fieldRow['isRequired'].'</td>';
        echo '<td>'.$fieldRow['isForAllProjects'].'</td>';
        echo '<td>'.$fieldRow['description'].'</td>';
        echo '</tr>';
      }
      ?>
    </tbody>
  </table>
  <div class="modal fade cmp-add-fields-modal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h4 class="modal-title"><?php echo $lang['ADD']; ?></h4>
        </div>
        <div class="modal-body">
          <table class="table table-hover">
            <thead>
              <th>Aktiv</th>
              <th width="200px">Überschrift</th>
              <th>Pflichtfeld</th>
              <th width="150px">Für Alle Projekte</th>
              <th>Beschreibung</th>
            </thead>
            <tbody>
              <?php
                echo "<td><input type='checkbox' name='field_add_active' checked /></td>";
                echo "<td><input type='text' class='form-control required-field' name='field_add_name' maxlength='15' placeholder='max. 15 Zeichen' /></td>";
                echo "<td><input type='checkbox' name='field_add_required' /></td>";
                echo "<td><input type='checkbox' name='field_add_forall' /></td>";
                echo "<td><input type='text' class='form-control' name='field_add_description' maxlength='50' placeholder='max. 50 Zeichen' /></td>";
                echo '</tr>';
              ?>
            </tbody>
          </table>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
          <button type="submit" name="field_add_save" class="btn btn-warning" value="<?php echo $i; ?>"><?php echo $lang['SAVE']; ?></button>
        </div>
      </div>
    </div>
  </div>
  <div class="modal fade cmp-edit-fields-modal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h4 class="modal-title"><?php echo $lang['ADDITIONAL_FIELDS']; ?></h4>
        </div>
        <div class="modal-body">
          <table class="table table-hover">
            <thead>
              <th>Aktiv</th>
              <th width="200px">Überschrift</th>
              <th>Pflichtfeld</th>
              <th width="150px">Für Alle Projekte</th>
              <th>Beschreibung</th>
            </thead>
            <tbody>
              <?php
              for($i = 1; $i < 4; $i++){
                $a = $cmpID * 3 - 3 + $i ;
                $result = $conn->query("SELECT * FROM $companyExtraFieldsTable WHERE id = ($a)");
                echo '<tr>';
                if($result->num_rows > 0){
                  $row = $result->fetch_assoc();
                  $active = $row['isActive'] == 'TRUE' ? 'checked' : '';
                  $name = $row['name'];
                  $required = $row['isRequired'] == 'TRUE' ? 'checked' : '';
                  $forAllProjects = $row['isForAllProjects'] == 'TRUE' ? 'checked' : '';
                  $description = $row['description'];
                } else {
                  $row = $active = $name = $required = $forAllProjects = $description = '';
                }
                echo "<td><input type='checkbox' name='active_$i' $active /></td>";
                echo "<td><input type='text' class='form-control' name='name_$i' maxlength='15' placeholder='max. 15 Zeichen' value='$name' /></td>";
                echo "<td><input type='checkbox' name='required_$i' $required /></td>";
                echo "<td><input type='checkbox' name='forall_$i' $forAllProjects /></td>";
                echo "<td><input type='text' class='form-control' name='description_$i' maxlength='50' placeholder='max. 50 Zeichen' value='$description' /></td>";
                echo '</tr>';
              }
              ?>
            </tbody>
          </table>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
          <button type="submit" name="save_additional_fields" class="btn btn-warning" value="<?php echo $cmpID; ?>"><?php echo $lang['SAVE']; ?></button>
        </div>
      </div>
    </div>
  </div>
</form><br>

<!-- ERP NUMBERS -->
<?php
$result = $conn->query("SELECT * FROM erpNumbers WHERE companyID = $cmpID");
$row = $result->fetch_assoc();
?>
<form method="POST" class="page-seperated-section">
  <h4>ERP <?php echo $lang['SETTINGS']; ?><a role="button" data-toggle="collapse" href="#erp_number_info"><i class="fa fa-info-circle"></i></a>
    <div class="page-header-button-group">
      <button type="submit" class="btn btn-default" name="save_erp_numbers"><i class="fa fa-floppy-o"></i></button>
    </div>
  </h4>
  <div class="container-fluid">
    <br>
    <div class="collapse" id="erp_number_info">
      <div class="well">
        Festsetzen der kleinsten Nummer des nächsten Auftrags. <br>
        Aufträge werden fortlaufend nummeriert, beginnend von der höchsten, bereits vorhandenen Zahl, welche zum ausgewählten Vorgang und jeweiligen Mandanten passt. <br>
        Dieser sog. mindest-Offset kann hier zustäzlich eingestellt werden.
      </div>
    </div>
    <div class="col-md-4">
      <label><?php echo $lang['PROPOSAL_TOSTRING']['ANG']; ?></label>
        <input type="number" class="form-control" name="erp_ang" value="<?php echo $row['erp_ang']; ?>" min="1"/>
    </div>
    <div class="col-md-4">
      <label><?php echo $lang['PROPOSAL_TOSTRING']['AUB']; ?></label>
        <input type="number" class="form-control" name="erp_aub" value="<?php echo $row['erp_aub']; ?>" min="1"/>
    </div>
    <div class="col-md-4">
      <label><?php echo $lang['PROPOSAL_TOSTRING']['RE']; ?></label>
        <input type="number" class="form-control" name="erp_re" value="<?php echo $row['erp_re']; ?>" min="1"/>
    </div>
  </div>
  <br>
  <div class="container-fluid">
    <div class="col-md-4">
      <label><?php echo $lang['PROPOSAL_TOSTRING']['LFS']; ?></label>
        <input type="number" class="form-control" name="erp_lfs" value="<?php echo $row['erp_lfs']; ?>" min="1"/>
    </div>
    <div class="col-md-4">
      <label><?php echo $lang['PROPOSAL_TOSTRING']['GUT']; ?></label>
        <input type="number" class="form-control" name="erp_gut" value="<?php echo $row['erp_gut']; ?>" min="1"/>
    </div>
    <div class="col-md-4">
      <label><?php echo $lang['PROPOSAL_TOSTRING']['STN']; ?></label>
        <input type="number" class="form-control" name="erp_stn" value="<?php echo $row['erp_stn']; ?>" min="1"/>
    </div>
  </div>
  <br>
</form>

<?php endif;?>
</div> <!-- /page-seperated-body -->
<?php include 'footer.php'; ?>
