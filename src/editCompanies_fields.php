<?php include 'header.php'; ?>
<?php enableToCore($userID);?>
<!-- BODY -->

<div class="page-header">
  <h3>Weitere Projektfelder</h3>
</div>

<?php
/* -the howDoesThisMagicWorkBeforeIforgetHowItWorks comment-
* each company can define up to three fields, all with reserved IDs, meaning ID no. 2 will always have id 4,5,6 assigned to in other table (where fields are stored)
* this way, we ALWAYS know which project CAN have which fields assigned to, it is a 1:3 mapping
* if a company field is activated, only then can the corresponding field variable in projects table be set to true also (double acitvation!)
* user enters additional info to projectbookingdata directly, whereas the corresponding field in projectTable says if it is active or not (cannot be active if deactivated on companyExtrafieldsTable)
* This is possible by our unique id to id mapping.
*/
$cmpID = intval($_GET['cmp']);
//linear mapping of f(x) = x*3 -2; f1(x) = f(x) + 1; f2(x) = f1(x)+1;
$id_1 = $cmpID * 3 - 2;
$id_2 = $cmpID * 3 - 1;
$id_3 = $cmpID * 3;

$result = $conn->query("SELECT * FROM $companyExtraFieldsTable WHERE companyID = $cmpID"); //selects up to three rows (or at least it should)
if(isset($_POST['save'])){
  if(!empty($_POST['name_1']) && !empty($_POST['description_1'])){
    $name = test_input($_POST['name_1']);
    $description = test_input($_POST['description_1']);
    $active = !empty($_POST['active_1']) ? 'TRUE' : 'FALSE';
    $required = !empty($_POST['required_1']) ? 'TRUE' : 'FALSE';
    $forall = !empty($_POST['forall_1']) ? 'TRUE' : 'FALSE';

    if($active == 'FALSE'){
      $forall = 'FALSE';
    }
    if($result->num_rows > 0){ //first row
      $row = $result->fetch_assoc();
      $forall_old = $row['isForAllProjects'];
      $conn->query("UPDATE $companyExtraFieldsTable SET name='$name', isActive = '$active' , isRequired = '$required', isForAllProjects = '$forall', description = '$description' WHERE id = $id_1");
    } else {
      $forall_old = 'FALSE';
      $conn->query("INSERT INTO $companyExtraFieldsTable (id, companyID, name, isActive, isRequired, isForAllProjects, description) VALUES ($id_1, $cmpID, '$name', '$active', '$required', '$forall', '$description')");
    }

    if($forall_old == 'FALSE' && $forall == 'TRUE'){
      $conn->query("UPDATE $projectTable, $clientTable SET $projectTable.field_1 = 'TRUE' WHERE $clientTable.companyID = $cmpID AND $clientTable.id = $projectTable.clientID");
    } elseif($forall_old == 'TRUE' && $forall == 'FALSE'){
      $conn->query("UPDATE $projectTable, $clientTable SET $projectTable.field_1 = 'FALSE' WHERE $clientTable.companyID = $cmpID AND $clientTable.id = $projectTable.clientID");
    }
  }
  if(!empty($_POST['name_2']) && !empty($_POST['description_2'])){
    $name = test_input($_POST['name_2']);
    $description = test_input($_POST['description_2']);
    $active = !empty($_POST['active_2']) ? 'TRUE' : 'FALSE';
    $required = !empty($_POST['required_2']) ? 'TRUE' : 'FALSE';
    $forall = !empty($_POST['forall_2']) ? 'TRUE' : 'FALSE';

    if($active == 'FALSE'){
      $forall = 'FALSE';
    }
    if($result->num_rows > 1){ //2nd row
      $row = $result->fetch_assoc();
      $forall_old = $row['isForAllProjects'];
      $conn->query("UPDATE $companyExtraFieldsTable SET name='$name', isActive = '$active' , isRequired = '$required', isForAllProjects = '$forall', description = '$description' WHERE id = $id_2");
    } else {
      $forall_old = 'FALSE';
      $conn->query("INSERT INTO $companyExtraFieldsTable (id, companyID, name, isActive, isRequired, isForAllProjects, description) VALUES ($id_2, $cmpID, '$name', '$active', '$required', '$forall', '$description')");
    }
    if($forall_old == 'FALSE' && $forall == 'TRUE'){
      $conn->query("UPDATE $projectTable, $clientTable SET $projectTable.field_2 = 'TRUE' WHERE $clientTable.companyID = $cmpID AND $clientTable.id = $projectTable.clientID");
    } elseif($forall_old == 'TRUE' && $forall == 'FALSE'){
      $conn->query("UPDATE $projectTable, $clientTable SET $projectTable.field_2 = 'FALSE' WHERE $clientTable.companyID = $cmpID AND $clientTable.id = $projectTable.clientID");
    }
  }
  if(!empty($_POST['name_3']) && !empty($_POST['description_3'])){
    $name = test_input($_POST['name_3']);
    $description = test_input($_POST['description_3']);
    $active = !empty($_POST['active_3']) ? 'TRUE' : 'FALSE';
    $required = !empty($_POST['required_3']) ? 'TRUE' : 'FALSE';
    $forall = !empty($_POST['forall_3']) ? 'TRUE' : 'FALSE';

    if($active == 'FALSE'){
      $forall = 'FALSE';
    }
    if($result->num_rows > 2){ //3rd row
      $row = $result->fetch_assoc();
      $forall_old = $row['isForAllProjects'];
      $conn->query("UPDATE $companyExtraFieldsTable SET name='$name', isActive = '$active' , isRequired = '$required', isForAllProjects = '$forall', description = '$description' WHERE id = $id_3");
    } else {
      $forall_old = 'FALSE';
      $conn->query("INSERT INTO $companyExtraFieldsTable (id, companyID, name, isActive, isRequired, isForAllProjects, description) VALUES ($id_3, $cmpID, '$name', '$active', '$required', '$forall', '$description')");
    }
    if($forall_old == 'FALSE' && $forall == 'TRUE'){
      $conn->query("UPDATE $projectTable, $clientTable SET $projectTable.field_3 = 'TRUE' WHERE $clientTable.companyID = $cmpID AND $clientTable.id = $projectTable.clientID");
    } elseif($forall_old == 'TRUE' && $forall == 'FALSE'){
      $conn->query("UPDATE $projectTable, $clientTable SET $projectTable.field_3 = 'FALSE' WHERE $clientTable.companyID = $cmpID AND $clientTable.id = $projectTable.clientID");
    }
  }
}
echo mysqli_error($conn);
$result = $conn->query("SELECT * FROM $companyExtraFieldsTable WHERE companyID = $cmpID");
?>

<form method="POST">
  <div class="container-fluid">
    <table class="table table-hover">
      <thead>
        <th>Aktiv</th>
        <th width="200px">Überschrift</th>
        <th>Pflichtfeld</th>
        <th width="150px">Für Alle Projekte</th>
        <th>Beschreibung</th>
      </thead>
      <tbody>
        <tr>
          <?php
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
          ?>
          <td><input type='checkbox' name="active_1" <?php echo $active; ?> /></td>
          <td><input type="text" class="form-control" name="name_1" maxlength="15" placeholder="max. 15 Zeichen" value="<?php echo $name; ?>"/></td>
          <td><input type='checkbox' name="required_1" <?php echo $required; ?> /></td>
          <td><input type='checkbox' name="forall_1" <?php echo $forAllProjects; ?> /></td>
          <td><input type="text" class="form-control" name="description_1" maxlength="50" placeholder="max. 50 Zeichen" value="<?php echo $description; ?>" /></td>
        </tr>
        <tr>
          <?php
          if($result->num_rows > 1){
            $row = $result->fetch_assoc();
            $active = $row['isActive'] == 'TRUE' ? 'checked' : '';
            $name = $row['name'];
            $required = $row['isRequired'] == 'TRUE' ? 'checked' : '';
            $forAllProjects = $row['isForAllProjects'] == 'TRUE' ? 'checked' : '';
            $description = $row['description'];
          } else {
            $row = $active = $name = $required = $forAllProjects = $description = '';
          }
          ?>
          <td><input type='checkbox' name="active_2" <?php echo $active;?> /></td>
          <td><input type="text" name="name_2" class="form-control" maxlength="15" value="<?php echo $name; ?>" /></td>
          <td><input type='checkbox' name="required_2" <?php echo $required; ?>/></td>
          <td><input type='checkbox' name="forall_2" <?php echo $forAllProjects; ?>/></td>
          <td><input type="text" name="description_2" class="form-control" maxlength="50" value="<?php echo $description; ?>" /></td>
        </tr>
        <tr>
          <?php
          if($result->num_rows > 2){
            $row = $result->fetch_assoc();
            $active = $row['isActive'] == 'TRUE' ? 'checked' : '';
            $name = $row['name'];
            $required = $row['isRequired'] == 'TRUE' ? 'checked' : '';
            $forAllProjects = $row['isForAllProjects'] == 'TRUE' ? 'checked' : '';
            $description = $row['description'];
          } else {
            $row = $active = $name = $required = $forAllProjects = $description = '';
          }
          ?>
          <td><input type='checkbox' name="active_3" <?php echo $active;?> /></td>
          <td><input type="text" name="name_3" class="form-control" maxlength="15" value="<?php echo $name; ?>"/></td>
          <td><input type='checkbox' name="required_3" <?php echo $required; ?>/></td>
          <td><input type='checkbox' name="forall_3" <?php echo $forAllProjects; ?>/></td>
          <td><input type="text" name="description_3" class="form-control" maxlength="50" value="<?php echo $description; ?>" /></td>
        </tr>
      </tbody>
    </table>
  </div>
  <br>
  <div class="text-right">
    <button type="submit" name="save" class="btn btn-warning" value="<?php echo $cmpID; ?>">Speichern</button>
  </div>
</form>
<?php include 'footer.php'; ?>
