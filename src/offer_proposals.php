<?php require 'header.php'; enableToERP($userID); ?>
<div class="page-header">
  <h3><?php echo $lang['OFFER']; ?></h3>
</div>
<?php
$filterCompany = $filterClient = 0;
$filterStatus = -1;
if($_SERVER['REQUEST_METHOD'] == 'POST'){
  if(isset($_POST['filterCompany'])){
    $filterCompany = $_POST['filterCompany'];
  }
  if(isset($_POST['filterClient'])){
    $filterClient = $_POST['filterClient'];
  }
  if(isset($_POST['filterStatus'])){
    $filterStatus = $_POST['filterStatus'];
  } else {
    $filterStatus = 0;
  }
}
?>

<form method="POST">
  <select style='width:200px' name="filterCompany" class="js-example-basic-single" onchange="showClients(this.value)">
    <?php
    $sql = "SELECT * FROM companyData WHERE id IN (".implode(', ', $available_companies).")";
    $result = mysqli_query($conn, $sql);
    if($result && $result->num_rows > 1) {
      echo '<option value="0">Select Company...</option>';
    } else {
      $filterCompany = $available_companies[1];
    }
    while($result && ($row = $result->fetch_assoc())){
      $checked = '';
      if($filterCompany == $row['id']){
        $checked = 'selected';
      }
      echo "<option $checked value='".$row['id']."' >".$row['name']."</option>";
    }
    echo mysqli_error($conn);
    ?>
  </select>
  <select id="clientHint" style='width:200px' class="js-example-basic-single" name="filterClient">
  </select>

  <select class="js-example-basic-single" style='width:150px'  name="filterStatus">
    <option value="-1" <?php if($filterStatus == -1) echo 'selected'; ?>><?php echo $lang['DISPLAY_ALL']; ?></option>
    <option value="0" <?php if($filterStatus == 0) echo 'selected'; ?>><?php echo $lang['OFFERSTATUS_TOSTRING'][0]; ?></option>
    <option value="1" <?php if($filterStatus == 1) echo 'selected'; ?>><?php echo $lang['OFFERSTATUS_TOSTRING'][1]; ?></option>
    <option value="2" <?php if($filterStatus == 2) echo 'selected'; ?>><?php echo $lang['OFFERSTATUS_TOSTRING'][2]; ?></option>
  </select>
  <button type="submit" class="btn btn-warning btn-sm">Filter</button>
</form>
  <br><hr><br>
  <table class="table table-hover">
    <thead>
      <th>ID</th>
      <th><?php echo $lang['CLIENT']; ?></th>
      <th>Status</th>
      <th><?php echo $lang['PRODUCTS']; ?></th>
      <th>Option</th>
    </thead>
    <tbody>
      <?php
      $filterClient_query = $filterClient ? " AND clientData.id = $filterClient" : "";
      $filterStatus_query = ($filterStatus >= 0) ? " AND status = '$filterStatus'" : "";

      $result = $conn->query("SELECT proposals.*, clientData.name as clientName
      FROM proposals INNER JOIN clientData ON proposals.clientID = clientData.id
      WHERE clientData.companyID = $filterCompany $filterClient_query $filterStatus_query");

      while($result && ($row = $result->fetch_assoc())){
        $i = $row['id'];
        echo '<tr>';
        echo '<td>'.$row['id_number'].'</td>';
        echo '<td>'.$row['clientName'].'</td>';
        echo'<td>'.$lang['OFFERSTATUS_TOSTRING'][$row['status']].'</td>';
        echo '<td><dl>';
        $productRes = $conn->query("SELECT * FROM products WHERE proposalID = $i");
        while($productRes && ($prodrow = $productRes->fetch_assoc())){
          echo '<dt>'.$prodrow['quantity'].'x '.$prodrow['name'].'</dt>';
          echo '<dd style="margin-left:15px;">'.$prodrow['description'].'</dd>';
        }
        echo '</dl></td>';
        echo '<td>';
        echo '<form method="POST" style="display:inline" action="offer_proposal_edit.php"><button type="submit" class="btn btn-default" name="edit_proposal" value="'.$row['id'].'"><i class="fa fa-pencil"></i></button></form> ';
        echo '<form method="POST" style="display:inline" action="download_proposal.php">'."<button type='submit' class='btn btn-default' value='$i' name='download_proposal'><i class='fa fa-download'></i></button></form> ";
        echo '<form method="POST" style="display:inline"><button type="submit" class="btn btn-danger" title="Delete" name="delete_proposal" value="'.$row['id'].'"><i class="fa fa-trash-o"></i></button></form> ';
        echo '</td>';
        echo '</tr>';
      }
      echo mysqli_error($conn);
      ?>
    </tbody>
  </table>
  <br><hr><br>


<script>
function showClients(company, client){
  if(company != ""){
    $.ajax({
      url:'ajaxQuery/AJAX_getClient.php',
      data:{companyID:company, clientID:client},
      type: 'get',
      success : function(resp){
        $(clientHint).html(resp);
      },
      error : function(resp){}
    });
  }
}
</script>
<?php
if($filterCompany){ //i want my filter displayed even if there were no bookings
  echo "<script> showClients($filterCompany, $filterClient); </script>";
}
?>
<?php include 'footer.php'; ?>
