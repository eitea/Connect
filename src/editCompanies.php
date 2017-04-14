<?php include 'header.php'; ?>
<?php enableToCore($userID);?>
<!-- BODY -->

<?php
if(empty($_GET['cmp']) || !in_array($_GET['cmp'], $available_companies)){die("Invalid Access");}
$cmpID = intval($_GET['cmp']);

if($_SERVER["REQUEST_METHOD"] == "POST"){
  if(isset($_POST['deleteCompany']) && $cmpID != 1){
    $sql = "DELETE FROM $companyTable WHERE id = $cmpID;";
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
      $sql = "INSERT INTO $projectTable (clientID, name, status, hours, hourlyPrice, field_1, field_2, field_3) SELECT id,'$name', '$status', '$hours', '$hourlyPrice', '$field_1', '$field_2', '$field_3' FROM $clientTable WHERE companyID = $cmpID";
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

  $result = $conn->query("SELECT * FROM $companyExtraFieldsTable WHERE companyID = $cmpID"); //selects up to three rows (or at least it should)
  if(isset($_POST['save_additional_fields'])){
    for($i = 1; $i < 4; $i++){
      //linear mapping of f(x) = x*3 -2; f1(x) = f(x) + 1; f2(x) = f1(x)+1;
      $id_save = $cmpID * 3 - 3 + $i;
      if(!empty($_POST['name_'.$i]) && !empty($_POST['description_'.$i])){
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
      }
    }
  }
}
?>

<?php
$result = $conn->query("SELECT * FROM $companyTable WHERE id = $cmpID");
if ($result && ($row = $result->fetch_assoc()) && in_array($row['id'], $available_companies)):
?>
  <div class="page-header">
    <h3><?php echo $lang['COMPANY'] .' - '.$row['name']; ?></h3>
  </div>

  <br>
  <form method="POST">
    <p><?php echo $lang['DEFAULT'] . " " . $lang['PROJECT']; ?>: </p>
    <table class="table table-hover table-condensed">
      <thead>
        <tr>
          <th>Option</th>
          <th>Name</th>
          <th>Status</th>
          <th><?php echo $lang['HOURS']; ?></th>
          <th><?php echo $lang['HOURLY_RATE']; ?></th>
        </tr>
      </thead>
      <tbody>
        <?php
        $query = "SELECT * FROM $companyDefaultProjectTable WHERE companyID = $cmpID";
        $projectResult = mysqli_query($conn, $query);
        if ($projectResult && $projectResult->num_rows > 0) {
          while ($projectRow = $projectResult->fetch_assoc()) {
            $i = $projectRow['id'];

            $projectRowStatus = (!empty($projectRow['status']))? $lang['PRODUCTIVE']:'';
            echo "<tr><td><input type='checkbox' name='indexProject[]' value='$i'></td>";
            echo "<td>".$projectRow['name']."</td>";
            echo "<td> $projectRowStatus </td>";
            echo "<td>".$projectRow['hours']."</td>";
            echo "<td>".$projectRow['hourlyPrice']."</td></tr>";
          }
        }
        ?>
      </tbody>
    </table>
    <br><br>
    <p> <?php echo $lang['ASSIGNED'] . " " . $lang['USERS']; ?>: </p>
    <table class="table table-hover" >
      <tr>
        <th>Option</th>
        <th>Name</th>
      </tr>
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
    <br><br>
    <p><?php echo $lang['ADDITIONAL_FIELDS']; ?>: </p>
    <table class="table table-hover" >
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
    <br><br>
    <div class="container-fluid text-right">
      <div class="btn-group" role="group">
        <div class="dropup">
          <button class="btn btn-warning dropdown-toggle" id="dropOptions" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
            Option
            <span class="caret"></span>
          </button>
          <ul class="dropdown-menu">
            <li><a href="#" data-toggle="modal" data-target=".cmp-new-project-modal">Neues Standardprojekt</a></li>
            <li><a href="#" data-toggle="modal" data-target=".cmp-hire-users-modal" ><?php echo $lang['HIRE_USER']; ?></a></li>
            <li><a href="#" data-toggle="modal" data-target=".cmp-additional-fields"><?php echo $lang['ADDITIONAL_FIELDS']; ?></a></li>
            <li role="separator" class="divider"></li>
            <li><button type="button" class="btn btn-link" data-toggle="modal" data-target=".cmp-delete-confirm-modal"><?php echo $lang['DELETE_COMPANY']; ?></button></li>
          </ul>
        </div>
      </div>
      <button type="submit" class="btn btn-danger" name="deleteSelection">Auswahl Löschen</button>
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
            <button type="submit" name='deleteCompany' class="btn btn-primary">Yes, delete it.</button>
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
                $sql = "SELECT * FROM $userTable WHERE id NOT IN (SELECT DISTINCT userID FROM $companyToUserRelationshipTable WHERE companyID = $cmpID)";
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
    <!-- new project modal -->
  <form  method="POST">
    <div class="modal fade cmp-new-project-modal" tabindex="-1" role="dialog">
      <div class="modal-dialog modal-md" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <h4 class="modal-title"><?php echo $lang['DEFAULT'] .' '.$lang['PROJECT']; ?></h4>
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
            <button type=submit class="btn btn-warning" name='createNewProject'> <?php echo $lang['ADD']; ?> </button>
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
