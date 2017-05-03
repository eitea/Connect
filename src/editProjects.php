<?php include 'header.php'; ?>
<!-- BODY -->

<div class="page-header">
  <h3><?php echo $lang['PROJECT']; ?></h3>
</div>

<?php
$filterCompany = 0;
$filterClient = 0;

if(isset($_GET['custID']) && is_numeric($_GET['custID'])){
  $filterClient = test_input($_GET['custID']);
  $result = $conn->query("SELECT companyID FROM $clientTable WHERE id = $filterClient");
  $row = $result->fetch_assoc();
  $filterCompany = $row['companyID'];
} else {
  $filterClient = 0;
}
if(isset($_POST['filterCompany'])){
  $filterCompany = $_POST['filterCompany'];
}
if(isset($_POST['filterClient'])){
  $filterClient = $_POST['filterClient'];
}
if(isset($_POST['add']) && !empty($_POST['name']) && $filterClient != 0){
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

  $conn->query("INSERT INTO $projectTable (clientID, name, status, hours, hourlyPrice, field_1, field_2, field_3) VALUES($filterClient, '$name', '$status', '$hours', '$hourlyPrice', '$field_1', '$field_2', '$field_3')");
}
if(isset($_POST['delete']) && isset($_POST['index'])) {
  $index = $_POST["index"];
  foreach ($index as $x) {
    $sql = "DELETE FROM $projectTable WHERE id = $x;";
    if (!$conn->query($sql)) {
      echo mysqli_error($conn);
    }
  }
}
if(isset($_POST['save']) && isset($_POST['projectIndeces'])){
  if(empty($_POST['statii'])){
    $_POST['statii'] = array();
  }
  for($i = 0; $i < count($_POST['projectIndeces']); $i++){
    $projectID = test_input( $_POST['projectIndeces'][$i]);
    $hours = floatval(test_input($_POST['boughtHours'][$i]));
    $hourlyPrice = floatval(test_input($_POST['pricedHours'][$i]));
    if(in_array($projectID, $_POST['statii'])){
      $status = "checked";
    } else {
      $status = "";
    }
    $sql = "UPDATE $projectTable SET hours = '$hours', hourlyPrice = '$hourlyPrice', status='$status' WHERE id = $projectID";
    $conn->query($sql);
    echo mysqli_error($conn);

    //checkboxes are not set at all if they're not checked. so we gotta remove this!!
    if(isset($_POST['addField_1_'.$projectID])){ $field_1 = 'TRUE'; } else { $field_1 = 'FALSE'; }
    if(isset($_POST['addField_2_'.$projectID])){ $field_2 = 'TRUE'; } else { $field_2 = 'FALSE'; }
    if(isset($_POST['addField_3_'.$projectID])){ $field_3 = 'TRUE'; } else { $field_3 = 'FALSE'; }
    $conn->query("UPDATE $projectTable SET field_1 = '$field_1', field_2 = '$field_2', field_3 = '$field_3' WHERE id = $projectID");
  }
}
?>

<script>
function showClients(company, client){
  $.ajax({
    url:'ajaxQuery/AJAX_client.php',
    data:{companyID:company, clientID:client},
    type: 'post',
    success : function(resp){
      $("#filterClient").html(resp);
    },
    error : function(resp){}
  });
  showProjects(client, 0);
};
</script>
<br>
<form method="post">
  <!-- SELECT COMPANY -->
  <div class="row">
    <div class="col-md-6">
      <select style='width:200px' id="filterCompany" name="filterCompany" onchange='showClients(this.value, 0);' class="js-example-basic-single">
        <option value="0">Select Company...</option>
        <?php
        $sql = "SELECT * FROM $companyTable WHERE id IN (".implode(', ', $available_companies).")";
        $result = mysqli_query($conn, $sql);
        if($result && $result->num_rows > 0) {
          if($result && $result->num_rows > 1) {
            echo '<option value="0">'.$lang['COMPANY'].'...</option>';
          } else {
            $filterCompany = $available_companies[1];
          }
          $row = $result->fetch_assoc();
          do {
            $checked = '';
            if($filterCompany == $row['id']) {
              $checked = 'selected';
            }
            echo "<option $checked value='".$row['id']."' >".$row['name']."</option>";

          } while($row = $result->fetch_assoc());
        }
        ?>
      </select>
      <select id="filterClient" name="filterClient" class="js-example-basic-single" style='width:200px' >
      </select>
      <button type="submit" class="btn btn-warning " name="filter">Filter</button><br><br>
    </div>
    <div class="col-md-6 text-right">
      <a href="editCustomers.php?custID=<?php echo $filterClient; ?>" class="btn btn-info">Return <i class="fa fa-arrow-right"></i></a>
    </div>
  </div>

  <br><br>
  <table class="table table-hover">
    <thead>
      <tr>
        <th><?php echo $lang['DELETE']; ?></th>
        <th>Name</th>
        <th><?php echo $lang['PRODUCTIVE']; ?></th>
        <th><?php echo $lang['ADDITIONAL_FIELDS']; ?></th>
        <th><?php echo $lang['HOURS']; ?></th>
        <th><?php echo $lang['HOURLY_RATE']; ?></th>
        <th></th>
      </tr>
    </thead>
    <tbody>
      <?php
      $result = $conn->query("SELECT * FROM $projectTable WHERE clientID = $filterClient");
      echo mysqli_error($conn);
      while($row = $result->fetch_assoc()){
        echo '<tr>';
        echo '<td><input type="checkbox" name="index[]" value='. $row['id'].'> </td>';
        echo '<td>'. $row['name'] .'</td>';
        echo '<td><div class="checkbox text-center"><input type="checkbox" name="statii[]" '. $row['status'] .' value="'.$row['id'].'"> <i class="fa fa-tags"></i></div></td>';
        echo '<td><small>';
        $resF = $conn->query("SELECT * FROM $companyExtraFieldsTable WHERE companyID = $filterCompany ORDER BY id ASC");
        if($resF->num_rows > 0){
          $rowF = $resF->fetch_assoc();
          if($rowF['isActive'] == 'TRUE'){
            $checked = $row['field_1'] == 'TRUE' ? 'checked': '';
            echo '<input type="checkbox" '.$checked.' name="addField_1_'.$row['id'].'"/>'. $rowF['name'];
          }
        }
        if($resF->num_rows > 1){
          $rowF = $resF->fetch_assoc();
          if($rowF['isActive'] == 'TRUE'){
            $checked = $row['field_2'] == 'TRUE' ? 'checked': '';
            echo '<br><input type="checkbox" '.$checked.' name="addField_2_'.$row['id'].'" />'. $rowF['name'];
          }
        }
        if($resF->num_rows > 2){
          $rowF = $resF->fetch_assoc();
          if($rowF['isActive'] == 'TRUE'){
            $checked = $row['field_3'] == 'TRUE' ? 'checked': '';
            echo '<br><input type="checkbox" '.$checked.' name="addField_3_'.$row['id'].'" />'. $rowF['name'];;
          }
        }
        echo '</small></td>';
        echo '<td><input type="number" class="form-control" step="any" name="boughtHours[]" value="'. $row['hours'] .'"></td>';
        echo '<td><input type="number" class="form-control" step="any" name="pricedHours[]" value="'. $row['hourlyPrice'] .'"></td>';
        echo '<td><input type="text" class="hidden" name="projectIndeces[]" value="'.$row['id'].'"></td>';
        echo '</tr>';
      }
      ?>
    </tbody>
  </table>
  <br>
  <div class="container-fluid">
    <div class="col-md-4">
      <?php if($filterClient): ?>
      <button class="btn btn-warning" type="button" data-toggle="collapse" data-target="#newProjectDrop" aria-expanded="false" aria-controls="collapseExample">
        New Project <i class="fa fa-caret-down"></i>
      </button>
    <?php endif; ?>
    </div>
    <div class="text-right">
      <button type="submit" class="btn btn-danger" name='delete'>Delete</button>
      <button type="submit" class="btn btn-warning" name='save'>Save Changes</button>
    </div>
  </div>

  <br><br>
  <?php if($filterClient): ?>
  <div class="collapse col-md-5 well" id="newProjectDrop">
    <br>
    <input type=text class="form-control" name='name' placeholder='Name'>
    <br>
    <div class="row">
      <div class="col-md-6">
        <input type=number class="form-control" name='hours' placeholder='Hours' step="any">
      </div>
      <div class="col-md-6">
        <input type=number class="form-control" name='hourlyPrice' placeholder='Price per Hour' step="any">
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
          $resF = $conn->query("SELECT * FROM $companyExtraFieldsTable WHERE companyID = $filterCompany ORDER BY id ASC");
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
    <br>
    <div class="text-right">
      <button type=submit class="btn btn-warning" name='add'> <?php echo $lang['ADD']; ?> </button>
    </div>
  </div>
<?php endif;?>

  <script>
  showClients(<?php echo $filterCompany; ?>, <?php echo $filterClient; ?>);
  </script>
</form>
<!-- /BODY -->
<?php include 'footer.php'; ?>
