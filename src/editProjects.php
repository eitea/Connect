<?php include 'header.php'; enableToProject($userID);  ?>
<?php
$filterings = array("savePage" => $this_page, "company" => 0, "client" => 0, "project" => array(0, '')); //set_filter requirement
if(isset($_GET['custID']) && is_numeric($_GET['custID'])){
  $filterings['client'] = test_input($_GET['custID']);
}
if(isset($_POST['filterClient'])){
  $filterings['client'] = intval($_POST['filterClient']);
}

if(isset($_POST['add']) && !empty($_POST['name']) && !empty($_POST['client'])){
  $filterClient = intval($_POST['client']);
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
  if($conn->error){ echo $conn->error; } else { echo '<div class="alert alert-success"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$lang['OK_DELETE'].'</div>'; }
}
if(isset($_POST['save']) && !empty($_POST['projectIndeces'])){
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
<br>
<?php
$result = $conn->query("SELECT * FROM $clientTable WHERE companyID IN (".implode(', ', $available_companies).")");
if(!$result || $result->num_rows <= 0){
  echo '<div class="alert alert-info">'.$lang['WARNING_NO_CLIENTS'].'<br><br>';
  include "new_client.php";
  echo '</div>';
}
?>
<form method="post">
  <div class="page-header">
    <h3>
      <?php echo $lang['PROJECT']; ?>
      <div class="page-header-button-group">
        <?php include 'misc/set_filter.php'; ?>
        <button type="button" class="btn btn-default" data-toggle="modal" data-target=".add-project" title="<?php echo $lang['ADD']; ?>" ><i class="fa fa-plus"></i></button>
        <button type="submit" class="btn btn-default" name='delete' title="<?php echo $lang['DELETE']; ?>"><i class="fa fa-trash-o"></i></button>
        <button type="submit" class="btn btn-default" name='save' title="<?php echo $lang['SAVE']; ?>"><i class="fa fa-floppy-o"></i></button>
        <a href="editCustomers.php?custID=<?php echo $filterClient; ?>" class="btn btn-default" title="<?php echo $lang['CLIENT']; ?>"><i class="fa fa-briefcase"></i></a>
      </div>
    </h3>
  </div>
  <br>
  <table class="table table-hover">
    <thead>
        <th><?php echo $lang['DELETE']; ?></th>
        <th><?php echo $lang['COMPANY']; ?></th>
        <th><?php echo $lang['CLIENT']; ?></th>
        <th>Name</th>
        <th><?php echo $lang['PRODUCTIVE']; ?></th>
        <th><?php echo $lang['ADDITIONAL_FIELDS']; ?></th>
        <th><?php echo $lang['HOURS']; ?></th>
        <th><?php echo $lang['HOURLY_RATE']; ?></th>
        <th></th>
    </thead>
    <tbody>
      <?php
      $companyQuery = $clientQuery = $projectQuery = $productiveQuery = "";
      if($filterings['company']){$companyQuery = " AND $companyTable.id = ".$filterings['company']; }
      if($filterings['client']){$clientQuery = " AND $clientTable.id = ".$filterings['client']; }
      if($filterings['project'][0]){$projectQuery = " AND $projectTable.id = ".$filterings['project'][0]; }
      if($filterings['project'][1]){$productiveQuery = " AND $projectTable.status = 'checked'"; }

      $result = $conn->query("SELECT $projectTable.*, $clientTable.companyID, $clientTable.name AS clientName, $companyTable.name AS companyName
        FROM $projectTable INNER JOIN $clientTable ON $clientTable.id = $projectTable.clientID INNER JOIN $companyTable ON $companyTable.id = $clientTable.companyID
        WHERE 1 $companyQuery $clientQuery $projectQuery $productiveQuery");
      echo mysqli_error($conn);
      while($row = $result->fetch_assoc()){
        echo '<tr>';
        echo '<td><input type="checkbox" name="index[]" value='. $row['id'].'> </td>';
        echo '<td>'. $row['companyName'] .'</td>';
        echo '<td>'. $row['clientName'] .'</td>';
        echo '<td>'. $row['name'] .'</td>';
        echo '<td><input type="checkbox" name="statii[]" '. $row['status'] .' value="'.$row['id'].'"> <i class="fa fa-tags"></i></td>';
        echo '<td><small>';
        $resF = $conn->query("SELECT * FROM $companyExtraFieldsTable WHERE companyID = ".$row['companyID']." ORDER BY id ASC");
        if($resF->num_rows > 0){
          $rowF = $resF->fetch_assoc();
          if($rowF['isActive'] == 'TRUE'){
            $checked = $row['field_1'] == 'TRUE' ? 'checked': '';
            echo '<input type="checkbox" '.$checked.' name="addField_1_'.$row['id'].'"/> '.$rowF['name'];
          }
        }
        if($resF->num_rows > 1){
          $rowF = $resF->fetch_assoc();
          if($rowF['isActive'] == 'TRUE'){
            $checked = $row['field_2'] == 'TRUE' ? 'checked': '';
            echo '<br><input type="checkbox" '.$checked.' name="addField_2_'.$row['id'].'" /> '.$rowF['name'];
          }
        }
        if($resF->num_rows > 2){
          $rowF = $resF->fetch_assoc();
          if($rowF['isActive'] == 'TRUE'){
            $checked = $row['field_3'] == 'TRUE' ? 'checked': '';
            echo '<br><input type="checkbox" '.$checked.' name="addField_3_'.$row['id'].'" /> '.$rowF['name'];;
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
</form>

<form method="POST">
  <div class="modal fade add-project">
    <div class="modal-dialog modal-content modal-md">
      <div class="modal-header"><h4><?php echo $lang['ADD']; ?></h4></div>
      <div class="modal-body">
        <div class="row">
            <?php
            $result_fc = mysqli_query($conn, "SELECT * FROM companyData WHERE id IN (".implode(', ', $available_companies).")");
            if($result_fc && $result_fc->num_rows > 1){
              echo '<div class="col-md-6">';
              echo '<select class="js-example-basic-single" name="searchCompany" onchange="showClients(this.value, 0);" >';
              echo '<option value="0">...</option>';
              while($result_fc && ($row_fc = $result_fc->fetch_assoc())){
                $checked = '';
                if($filterings['company'] == $row_fc['id']) {
                  $checked = 'selected';
                }
                echo "<option $checked value='".$row_fc['id']."' >".$row_fc['name']."</option>";
              }
              echo '</select>';
              echo '</div>';
            }
            ?>
          <div class="col-md-6">
            <select class="js-example-basic-single" id="clientHint" name="client">
            </select>
          </div>
        </div>
        <label>Name</label>
        <input type=text class="form-control required-field" name='name' placeholder='Name'>
        <br>
        <div class="row">
          <div class="col-md-6">
            <label><?php echo $lang['HOURS']; ?></label>
            <input type=number class="form-control" name='hours' step="any">
          </div>
          <div class="col-md-6">
            <label><?php echo $lang['HOURLY_RATE']; ?></label>
            <input type=number class="form-control" name='hourlyPrice' step="any">
          </div>
        </div>
        <br>
        <div class="row">
          <?php include 'misc/select_client.php'; ?>
          <div class="col-md-3" style="padding-left:50px;">
            <div class="checkbox"><input type="checkbox" name="status" value="checked" checked> <i class="fa fa-tags"></i> <?php echo $lang['PRODUCTIVE']; ?></div>
          </div>
          <div class="col-md-3" style="padding-left:50px;">
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
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-warning" name='add'> <?php echo $lang['ADD']; ?> </button>
      </div>
    </div>
  </div>
</form>

<script>
var myCalendar = new dhtmlXCalendarObject(["calendar"]);
myCalendar.setSkin("material");
myCalendar.setDateFormat("%Y-%m-%d");
dhx.zim.first = function(){ return 2000 };

$('.table').DataTable({
  order: [[ 1, "asc" ]],
  columns: [{orderable: false}, null, null, null, null, {orderable: false}, {orderable: false}, {orderable: false}, {orderable: false}],
  responsive: true,
  colReorder: true,
  autoWidth: false,
  deferRender: true,
  language: {
    <?php echo $lang['DATATABLES_LANG_OPTIONS']; ?>
  }
});

function showClients(company, client){
  $.ajax({
    url:'ajaxQuery/AJAX_getClient.php',
    data:{companyID:company, clientID:client},
    type: 'get',
    success : function(resp){
      $("#clientHint").html(resp);
    },
    error : function(resp){},
    complete : function(resp){
      showProjects($("#clientHint").val(), 0);
    }
  });
}
</script>

<?php
if($filterings['company']){
  echo '<script>';
  echo "showClients(".$filterings['company'].", 0)";
  echo '</script>';
}
 ?>
<?php include 'footer.php'; ?>
