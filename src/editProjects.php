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

  $sql="INSERT INTO $projectTable (clientID, name, status, hours, hourlyPrice) VALUES($filterClient, '$name', '$status', '$hours', '$hourlyPrice')";
  $conn->query($sql);
}

if (isset($_POST['delete']) && isset($_POST['index'])) {
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
        $sql = "SELECT * FROM $companyTable";
        $result = mysqli_query($conn, $sql);
        if($result && $result->num_rows > 0) {
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
      <button type="submit" class="btn btn-warning btn-sm" name="filter">Filter</button><br><br>
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
        <th><?php echo $lang['HOURS']; ?></th>
        <th><?php echo $lang['HOURLY_RATE']; ?></th>
      </tr>
    </thead>
    <tbody>
      <?php
      $sql = "SELECT * FROM $projectTable WHERE clientID = $filterClient";
      $result = $conn->query($sql);

      echo mysqli_error($conn);
      while($row = $result->fetch_assoc()){
        echo '<tr>';
        echo '<td><input type=checkbox name=index[] value='. $row['id'].'> </td>';
        echo '<td>'. $row['name'] .'</td>';
        echo '<td><div class="checkbox text-center"><input type="checkbox" name="statii[]" '. $row['status'] .' value="'.$row['id'].'"> <i class="fa fa-tags"></i></div></td>';
        echo '<td><input type="number" class="form-control" step="any" name="boughtHours[]" value="'. $row['hours'] .'"></td>';
        echo '<td><input type="number" class="form-control" step="any" name="pricedHours[]" value="'. $row['hourlyPrice'] .'"></td>';
        echo '<td><input type="text" class="hidden" name="projectIndeces[]" value="'.$row['id'].'"></td>';
        echo '</tr>';
      }
      ?>
    </tbody>
  </table>
  <br>
  <div class="row">
    <div class="col-md-4">
      <button class="btn btn-warning" type="button" data-toggle="collapse" data-target="#newProjectDrop" aria-expanded="false" aria-controls="collapseExample">
        New Project <i class="fa fa-caret-down"></i>
      </button>
    </div>
    <div class="text-right">
      <button type="submit" class="btn btn-danger" name='delete'>Delete</button>
      <button type="submit" class="btn btn-warning" name='save'>Save Changes</button>
    </div>
  </div>

  <br><br>
  <div class="collapse col-md-5 well" id="newProjectDrop">
    <form method="post">
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
      <div style="margin-left:25px">
        <div class="checkbox"><input type="checkbox" name="status" value="checked"> <i class="fa fa-tags"></i> <?php echo $lang['PRODUCTIVE']; ?></div>
      </div>
      <br>
      <div class="text-right">
        <button type=submit class="btn btn-warning" name='add'> <?php echo $lang['ADD']; ?> </button>
      </div>
    </form>
  </div>

  <script>
  showClients(<?php echo $filterCompany; ?>, <?php echo $filterClient; ?>);
  </script>

  </form>
  <!-- /BODY -->
  <?php include 'footer.php'; ?>
