<?php include 'header.php'; ?>
<?php enableToCore($userID);?>
<!-- BODY -->

<?php
if(empty($_GET['cmp']) || !in_array($_GET['cmp'], $available_companies)){die("Invalid Access");}
$cmpID = intval($_GET['cmp']);

if($_SERVER["REQUEST_METHOD"] == "POST"){
  if(isset($_POST['deleteCompany']) && $cmpID != 1){
    $sql = "DELETE FROM companyData WHERE id = $cmpID;";
    $conn->query($sql);
    echo mysqli_error($conn);
  } elseif(isset($_POST['deleteCompany']) && $cmpID == 1){
    echo '<div class="alert alert-danger fade in">';
    echo '<a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>';
    echo '<strong>Error: </strong>Cannot delete first Company.';
    echo '</div>';
  }

  if(isset($_POST['deleteSelection'])){
    if(isset($_POST['indexProject'])){
      foreach ($_POST['indexProject'] as $i) {
        $sql = "DELETE FROM $companyDefaultProjectTable WHERE id = $i";
        $conn->query($sql);
      }
    }
    if(isset($_POST['indexUser'])){
      foreach ($_POST['indexUser'] as $i) {
        $sql = "DELETE FROM $companyToUserRelationshipTable WHERE userID = $i AND companyID = $cmpID";
        $conn->query($sql);
      }
    }
  }

  if(isset($_POST['hire']) && isset($_POST['hiring_userIDs'])){
    foreach($_POST['hiring_userIDs'] as $i){
      $sql = "INSERT INTO $companyToUserRelationshipTable (companyID, userID) VALUES ($cmpID, $i)";
      $conn->query($sql);
    }
  }

  if(isset($_POST['createNewProject']) && !empty($_POST['name'])){
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
      $sql = "INSERT IGNORE INTO projectData (clientID, name, status, hours, hourlyPrice, field_1, field_2, field_3) SELECT id,'$name', '$status', '$hours', '$hourlyPrice', '$field_1', '$field_2', '$field_3' FROM $clientTable WHERE companyID = $cmpID";
      if($conn->query($sql)){
        echo '<div class="alert alert-success fade in">';
        echo '<a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>';
        echo '<strong>O.K.: </strong>' .$lang['OK_CREATE'];
        echo '</div>';
      } else {
        echo mysqli_error($conn);
      }
    }
    echo mysqli_error($conn);
  }

if(isset($_POST['save_defaultProjects']) && isset($_POST['default_projectNames'])){
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
  if(!mysqli_error($conn)){
    echo '<div class="alert alert-success fade in">';
    echo '<a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>';
    echo $lang['OK_SAVE'];
    echo '</div>';
  } else {
    echo mysqli_error($conn);
  }
}

if(isset($_POST['general_save'])){
  $address = test_input($_POST['general_address']);
  $phone = test_input($_POST['general_phone']);
  $mail = test_input($_POST['general_mail']);
  $homepage = test_input($_POST['general_homepage']);
  $erpText = test_input($_POST['general_erpText']);
  $conn->query("UPDATE companyData SET address = '$address', phone = '$phone', mail = '$mail', homepage = '$homepage', erpText = '$erpText' WHERE id = $cmpID");
  echo mysqli_error($conn);
}

if(isset($_POST['logoUpload'])){
  require "utilities.php";
  $filename = uploadFile("fileToUpload", 1, 1);
  if(!is_array($filename)){
    //delete old Logo if exists
    $result = $conn->query("SELECT logo from companyData WHERE id = $cmpID");
    if($result && ($row = $result->fetch_assoc())){
      unlink($row['logo']);
    }
    $conn->query("UPDATE companyData SET logo = '$filename' WHERE id = $cmpID");
    echo mysqli_error($conn);
  } else {
    echo '<div class="alert alert-danger fade in">';
    echo '<a href="userProjecting.php" class="close" data-dismiss="alert" aria-label="close">&times;</a>';
    echo print_r($filename);
    echo '</div>';
  }
}

  $result = $conn->query("SELECT * FROM $companyExtraFieldsTable WHERE companyID = $cmpID"); //selects up to three rows (or at least it should)
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
        if($result->num_rows > ($i -1)){ //first row
          $row = $result->fetch_assoc();
          $forall_old = $row['isForAllProjects'];
          $conn->query("UPDATE $companyExtraFieldsTable SET name='$name', isActive = '$active' , isRequired = '$required', isForAllProjects = '$forall', description = '$description' WHERE id = $id_save");
        } else {
          $forall_old = 'FALSE';
          $conn->query("INSERT INTO $companyExtraFieldsTable (id, companyID, name, isActive, isRequired, isForAllProjects, description) VALUES ($id_save, $cmpID, '$name', '$active', '$required', '$forall', '$description')");
        }

        if($forall_old == 'FALSE' && $forall == 'TRUE'){
          $conn->query("UPDATE $projectTable, $clientTable SET $projectTable.field_$i = 'TRUE' WHERE $clientTable.companyID = $cmpID AND $clientTable.id = $projectTable.clientID");
        } elseif($forall_old == 'TRUE' && $forall == 'FALSE'){
          $conn->query("UPDATE $projectTable, $clientTable SET $projectTable.field_$i = 'FALSE' WHERE $clientTable.companyID = $cmpID AND $clientTable.id = $projectTable.clientID");
        }
      } elseif(!empty($_POST['active_'.$i])){//if name is empty, but the active button is not
        echo '<div class="alert alert-danger fade in">';
        echo '<a href="userProjecting.php" class="close" data-dismiss="alert" aria-label="close">&times;</a>';
        echo "<strong>Could not save Field nr. $i : </strong> Cannot set active field without a name";
        echo '</div>';
      }
    }
  }
}


$result = $conn->query("SELECT * FROM $companyTable WHERE id = $cmpID");
if ($result && ($row = $result->fetch_assoc()) && in_array($row['id'], $available_companies)):
  ?>

  <div class="page-header">
    <h3><?php echo $lang['COMPANY'] .' - '.$row['name']; ?></h3>
  </div>

  <br><br>
  <form method="post" enctype="multipart/form-data">
    <div class="container-fluid">
      <div class="row"><h4>Logo</h4></div>
      <div class="row text-right"><button type="submit" name="logoUpload" class="btn btn-default" value="<?php echo $cmpID;?>"><i class="fa fa-floppy-o"></i> <?php echo $lang['SAVE']; ?></button></div>
    </div>
    <div class="container-fluid">
      <div class="col-sm-4">
        <img src="<?php echo $row['logo'];?>"></img>
      </div>
      <div class="col-sm-8">
        <input type="file" name="fileToUpload" id="fileToUpload" />
      </div>
    </div>
  </form>
    <form method="POST">
    <br><hr><br>
    <div class="container-fluid">
      <div class="row"><h4><?php echo $lang['GENERAL_SETTINGS']; ?></h4></div>
      <div class="row text-right"><button class="btn btn-default" name="general_save"><i class="fa fa-floppy-o"></i> <?php echo $lang['SAVE']; ?></button></div>
      <br>
      <div class="col-sm-3">
        <label><?php echo $lang['ADDRESS']; ?></label>
      </div>
      <div class="col-sm-9">
        <input type="text" class="form-control" name="general_address" placeholder="Address" value="<?php echo $row['address'];?>" />
      </div>
      <br><br>
      <div class="col-sm-3">
        <label><?php echo $lang['PHONE_NUMBER']; ?></label>
      </div>
      <div class="col-sm-9">
        <input type="text" class="form-control" name="general_phone" placeholder="Tel nr." value="<?php echo $row['phone'];?>"/>
      </div>
      <br><br>
      <div class="col-sm-3">
        <label>E-Mail</label>
      </div>
      <div class="col-sm-9">
        <input type="mail" class="form-control"  name="general_mail" placeholder="E-Mail" value="<?php echo $row['mail'];?>" />
      </div>
      <br><br>
      <div class="col-sm-3">
        <label>Homepage</label>
      </div>
      <div class="col-sm-9">
        <input type="text" class="form-control"  name="general_homepage" placeholder="Homepage" value="<?php echo $row['homepage'];?>" />
      </div>
      <br><br>
      <div class="col-sm-3">
        <label>ERP Text</label>
      </div>
      <div class="col-sm-9">
        <textarea name="general_erpText" class="form-control" placeholder=""><?php echo $row['erpText'];?></textarea>
      </div>
    </div>
    <br><hr><br>
    <div class="container-fluid">
      <div class="row"><h4><?php echo $lang['DEFAULT'] . " " . $lang['PROJECT']; ?></h4></div>
      <div class="row text-right"><a href="#" class="btn btn-default" data-toggle="modal" data-target=".cmp-new-project-modal"><i class="fa fa-pencil"></i> <?php echo $lang['EDIT']; ?></a></div>
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
    <br><hr><br>
    <div class="container-fluid">
      <div class="row"><h4><?php echo $lang['ASSIGNED'] . " " . $lang['USERS']; ?></h4></div>
      <div class="row text-right"><a href="#" class="btn btn-default" data-toggle="modal" data-target=".cmp-hire-users-modal" ><i class="fa fa-pencil"></i> <?php echo $lang['EDIT']; ?></a></div>
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
    <br><hr><br>
    <div class="container-fluid">
      <div class="row"><h4><?php echo $lang['ADDITIONAL_FIELDS']; ?></h4></div>
      <div class="row text-right"><a href="#" class="btn btn-default" data-toggle="modal" data-target=".cmp-additional-fields"><i class="fa fa-pencil"></i> <?php echo $lang['EDIT']; ?></a></div>
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
        $fieldResult = $conn->query("SELECT * FROM $companyExtraFieldsTable WHERE companyID = $cmpID");
        while ($fieldResult && ($fieldRow = $fieldResult->fetch_assoc())) {
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
    <br><hr><br>
    <div class="container-fluid text-right">
      <button type="submit" class="btn btn-warning" name="deleteSelection">Auswahl Löschen</button>
      <button type="button" class="btn btn-danger" data-toggle="modal" data-target=".cmp-delete-confirm-modal"><?php echo $lang['DELETE_COMPANY']; ?>
      </div>

      <!-- Delete confirm modal -->
      <div class="modal fade cmp-delete-confirm-modal" tabindex="-1" role="dialog" aria-labelledby="mySmallModalLabel">
        <div class="modal-dialog modal-sm" role="document">
          <div class="modal-content">
            <div class="modal-header">
              <h4 class="modal-title">Do you really wish to delete <?php echo $row['name']; ?> ?</h4>
            </div>
            <div class="modal-body">
              All Clients, Projects and Bookings belonging to this Company will be lost forever. Do you still wish to proceed?
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-default" data-dismiss="modal">No, I'm sorry.</button>
              <button type="submit" name='deleteCompany' class="btn btn-warning">Yes, delete it.</button>
            </div>
          </div>
        </div>
      </div>
    </form>
    <!-- hire users modal -->
    <form method="POST">
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
                  if ($result && $result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                      echo '<tr>';
                      echo '<td><input type="checkbox" name="hiring_userIDs[]" value="'.$row['id'].'" ></td>';
                      echo '<td>'.$row['firstname'].' '. $row['lastname'] .'</td>';
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
    <!-- edit default projects modal -->
    <form  method="POST">
      <div class="modal fade cmp-new-project-modal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
          <div class="modal-content">
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
                  <div class="checkbox"><input type="checkbox" name="status" value="checked"> <i class="fa fa-tags"></i> <?php echo $lang['PRODUCTIVE']; ?></div>
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
    </form>

    <!-- edit projectfields modal -->
    <form method="POST">
      <div class="modal fade cmp-additional-fields" tabindex="-1" role="dialog">
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
                  $result = $conn->query("SELECT * FROM $companyExtraFieldsTable WHERE companyID = $cmpID");
                  for($i = 1; $i < 4; $i++){
                    echo '<tr>';
                    if($result->num_rows > ($i-1)){
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
    </form>
  <?php endif;?>

  <!-- /BODY -->
  <?php include 'footer.php'; ?>
