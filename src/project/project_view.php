<?php include dirname(__DIR__) . '/header.php'; enableToProject($userID);  ?>
<?php require dirname(__DIR__) . "/misc/helpcenter.php"; ?>
<?php
$filterings = array("savePage" => $this_page, "company" => 0, "client" => 0, "project" => 0); //set_filter requirement

if(isset($_GET['custID']) && is_numeric($_GET['custID'])){
  $filterings['client'] = test_input($_GET['custID']);
}
if(isset($_POST['filterClient'])){
  $filterings['client'] = intval($_POST['filterClient']);
}
if(isset($_POST['add']) && !empty($_POST['name']) && !empty($_POST['filterClient'])){
  $client_id = intval($_POST['filterClient']);
  $name = test_input($_POST['name']);
  $status = "";
  if(isset($_POST['status'])){
    $status = "checked";
  }
  $hourlyPrice = floatval(test_input($_POST['hourlyPrice']));
  $hours = floatval(test_input($_POST['hours']));
  if(isset($_POST['createField_1'])){ $field_1 = 'TRUE'; } else { $field_1 = 'FALSE'; }
  if(isset($_POST['createField_2'])){ $field_2 = 'TRUE'; } else { $field_2 = 'FALSE'; }
  if(isset($_POST['createField_3'])){ $field_3 = 'TRUE'; } else { $field_3 = 'FALSE'; }
  $conn->query("INSERT INTO $projectTable (clientID, name, status, hours, hourlyPrice, field_1, field_2, field_3) VALUES($client_id, '$name', '$status', '$hours', '$hourlyPrice', '$field_1', '$field_2', '$field_3')");
  if($conn->error){ echo $conn->error; } else { echo '<div class="alert alert-success"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$lang['OK_ADD'].'</div>'; }
} elseif(isset($_POST['save'])){
  $projectID = intval($_POST['save']);
  $hours = floatval(test_input($_POST['boughtHours']));
  $hourlyPrice = floatval(test_input($_POST['pricedHours']));
  $status = isset($_POST['productive']) ? 'checked' : '';
  //checkboxes are not set at all if they're not checked
  if(isset($_POST['addField_1_'.$projectID])){ $field_1 = 'TRUE'; } else { $field_1 = 'FALSE'; }
  if(isset($_POST['addField_2_'.$projectID])){ $field_2 = 'TRUE'; } else { $field_2 = 'FALSE'; }
  if(isset($_POST['addField_3_'.$projectID])){ $field_3 = 'TRUE'; } else { $field_3 = 'FALSE'; }

  $conn->query("UPDATE $projectTable SET hours = '$hours', hourlyPrice = '$hourlyPrice', status='$status', field_1 = '$field_1', field_2 = '$field_2', field_3 = '$field_3' WHERE id = $projectID");
  if($conn->error){ echo $conn->error; } else { echo '<div class="alert alert-success"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$lang['OK_SAVE'].'</div>'; }
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
?>
<div class="page-header-fixed">
    <div class="page-header">
        <h3><?php echo $lang['PROJECTS']; ?>
            <div class="page-header-button-group">
                <?php include dirname(__DIR__) . '/misc/set_filter.php'; ?>
                <button type="button" class="btn btn-default" data-toggle="modal" data-target=".add-project" title="<?php echo $lang['ADD']; ?>" ><i class="fa fa-plus"></i></button>
                <button type="submit" class="btn btn-default" name='delete' title="<?php echo $lang['DELETE']; ?>" form="mainForm"><i class="fa fa-trash-o"></i></button>
                <a href="../system/clients?<?php echo 'cmp='.$filterings['company'].'&custID='.$filterings['client']; ?>" class="btn btn-default" title="<?php echo $lang['CLIENT']; ?>"><i class="fa fa-briefcase"></i></a>
            </div>
        </h3>
    </div>
</div>
<div class="page-content-fixed-130">
<br>
<?php
$result = $conn->query("SELECT * FROM $clientTable WHERE companyID IN (".implode(', ', $available_companies).")");
if(!$result || $result->num_rows <= 0){
  echo '<div class="alert alert-info">'.$lang['WARNING_NO_CLIENTS'].'<br><br>';
  echo '<a class="btn btn-warning" data-toggle="modal" data-target="#create_client">'.$lang['NEW_CLIENT_CREATE'].'</a>';
  echo '</div>';
  include dirname(__DIR__) . "/misc/new_client_buttonless.php";
}
?>
<form id="mainForm" method="post">
    <table class="table table-hover">
        <thead>
            <th><?php echo $lang['DELETE']; ?></th>
            <th></th>
            <th><?php echo $lang['COMPANY']; ?></th>
            <th><?php echo $lang['CLIENT']; ?></th>
            <th>Name</th>
            <th><?php echo $lang['ADDITIONAL_FIELDS']; ?></th>
            <th><?php echo $lang['HOURS']; ?></th>
            <th><?php echo $lang['HOURLY_RATE']; ?></th>
            <th></th>
        </thead>
        <tbody>
            <?php
            $query = '';
            if($filterings['company']){$query .= " AND companyData.id = ".$filterings['company']; }
            if($filterings['client']){$query .= " AND clientData.id = ".$filterings['client']; }
            if($filterings['project']){$query .= " AND projectData.id = ".$filterings['project']; }

            $stmt_extra = $conn->prepare("SELECT isActive, name FROM $companyExtraFieldsTable WHERE companyID = ? ORDER BY id ASC");
            $stmt_extra->bind_param("i", $companyID);

            $result = $conn->query("SELECT projectData.*, clientData.companyID, clientData.name AS clientName, companyData.name AS companyName
            FROM projectData INNER JOIN clientData ON clientData.id = projectData.clientID INNER JOIN companyData ON companyData.id = clientData.companyID WHERE 1 $query");
            while($row = $result->fetch_assoc()){
                $companyID = $row['companyID'];
                $productive = $row['status'] ? '<i class="fa fa-tags"></i>' : '';
                echo '<tr>';
                echo '<td><input type="checkbox" name="index[]" value='. $row['id'].' /></td>';
                echo '<td>'.$productive.'</td>';
                echo '<td>'.$row['companyName'] .'</td>';
                echo '<td>'. $row['clientName'] .'</td>';
                echo '<td>'. $row['name'] .'</td>';
                echo '<td>';
                $stmt_extra->execute();
                $resF = $stmt_extra->get_result();
                if($resF->num_rows > 0){
                    $rowF = $resF->fetch_assoc();
                    if($rowF['isActive'] == 'TRUE' && $row['field_1'] == 'TRUE'){
                        echo $rowF['name'];
                    }
                }
                if($resF->num_rows > 1){
                    $rowF = $resF->fetch_assoc();
                    if($rowF['isActive'] == 'TRUE' && $row['field_2'] == 'TRUE'){
                        echo $rowF['name'];
                    }
                }
                if($resF->num_rows > 2){
                    $rowF = $resF->fetch_assoc();
                    if($rowF['isActive'] == 'TRUE' && $row['field_3'] == 'TRUE'){
                        echo $rowF['name'];
                    }
                }
                echo '</td>';
                echo '<td>'. $row['hours'] .'</td>';
                echo '<td>'. $row['hourlyPrice'] .'</td>';
                echo '<td><a type="button" class="btn btn-default" href="detail/'.$row['id'].'"><i class="fa fa-pencil"></i></a></td>';
                echo '</tr>';
            }
            $stmt_extra->close();
            ?>
        </tbody>
    </table>
</form>

<!-- ADD PROJECT -->
<form method="POST">
    <div class="modal fade add-project">
        <div class="modal-dialog modal-content modal-md">
            <div class="modal-header"><h4><?php echo $lang['ADD']; ?></h4></div>
            <div class="modal-body">
                <?php include dirname(__DIR__) . '/misc/select_client.php'; ?>
                <br>
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
                <div class="container-fluid">
                    <div class="col-md-6">
                        <div class="checkbox"><input type="checkbox" name="status" value="checked" checked> <i class="fa fa-tags"></i> <?php echo $lang['PRODUCTIVE']; ?></div>
                    </div>
                    <div class="col-md-6">
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
$('.table').DataTable({
  order: [[ 2, "asc" ]],
  columns: [{orderable: false}, {orderable: false}, null, null, null, null, null, null, {orderable: false}],
  deferRender: true,
  responsive: true,
  colReorder: true,
  autoWidth: false,
  language: {
    <?php echo $lang['DATATABLES_LANG_OPTIONS']; ?>
  }
});
</script>
</div>
<?php include dirname(__DIR__) . '/footer.php'; ?>
