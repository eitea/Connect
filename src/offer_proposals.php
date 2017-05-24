<?php require 'header.php'; enableToERP($userID); ?>
<div class="page-header">
  <h3><?php echo $lang['OFFERS']; ?></h3>
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

$result = $conn->query("SELECT * FROM $clientTable WHERE companyID IN (".implode(', ', $available_companies).")");
if(!$result || $result->num_rows <= 0){
  echo '<div class="alert alert-info">'.$lang['WARNING_NO_CLIENTS'].'<br><br>';
  include "new_client.php";
  echo '</div>';
}

if($_SERVER['REQUEST_METHOD'] == 'POST'){
  if(isset($_POST['translate']) && !empty($_POST['transit'])){
    $proposalID = intval($_POST['translate']);
    $transition = test_input($_POST['transit']);
    //get new name: to have ever existed
    $result = $conn->query("SELECT COUNT(*) as num FROM proposals WHERE id_number LIKE '$transition%' OR history LIKE '% $transition%'");
    $row = $result->fetch_assoc();
    $transitionID = $transition . sprintf('%07d', $row['num'] +1);

    $conn->query("UPDATE proposals SET history = CONCAT_WS(' ', history , id_number), id_number = '$transitionID' WHERE id = $proposalID");
    echo mysqli_error($conn);
    //TODO: filter proposals to be contained inside id_number
  }
}
?>

<form method="POST">
  <select style='width:200px' name="filterCompany" class="js-example-basic-single" onchange="showClients(this.value)">
    <?php
    $result = $conn->query("SELECT * FROM companyData WHERE id IN (".implode(', ', $available_companies).")");
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
  <button type="submit" class="btn btn-warning ">Filter</button>
</form>
  <br><hr><br>
  <?php
  $filterClient_query = $filterClient ? " AND clientData.id = $filterClient" : "";
  $filterStatus_query = ($filterStatus >= 0) ? " AND status = '$filterStatus'" : "";

  $result = $conn->query("SELECT proposals.*, clientData.name as clientName
  FROM proposals INNER JOIN clientData ON proposals.clientID = clientData.id
  WHERE clientData.companyID = $filterCompany $filterClient_query $filterStatus_query");
  ?>
  <table class="table table-hover">
    <thead>
      <th>ID</th>
      <th><?php echo $lang['CLIENT']; ?></th>
      <th>Status</th>
      <th><?php echo $lang['PROP_OUR_SIGN']; ?></th>
      <th><?php echo $lang['PROP_OUR_MESSAGE']; ?></th>
      <th>Option</th>
      <th style="text-align:right;"><?php echo $lang['TRANSITION']; ?></th>
    </thead>
    <tbody>
      <?php
      while($result && ($row = $result->fetch_assoc())){
        $i = $row['id'];
        echo '<tr>';
        echo '<td>'.$row['id_number'].'</td>';
        echo '<td>'.$row['clientName'].'</td>';
        echo '<td>'.$lang['OFFERSTATUS_TOSTRING'][$row['status']].'</td>';
        echo '<td>'.$row['ourSign'].'</td>';
        echo '<td>'.$row['ourMessage'].'</td>';
        echo '<td>';
        echo '<form method="POST" style="display:inline" action="offer_proposal_edit.php"><button type="submit" class="btn btn-default" name="filterProposal" value="'.$row['id'].'"><i class="fa fa-pencil"></i></button></form> ';
        echo '<form method="POST" style="display:inline" action="download_proposal.php" target="_blank">'."<button type='submit' class='btn btn-default' value='$i' name='download_proposal'><i class='fa fa-download'></i></button></form> ";
        echo '<form method="POST" style="display:inline"><button type="submit" class="btn btn-danger" title="Delete" name="delete_proposal" value="'.$row['id'].'"><i class="fa fa-trash-o"></i></button></form> ';
        echo '</td>';
        echo '<td style="text-align:right;"><a data-target=".choose-transition-'.$i.'" data-toggle="modal" class="btn btn-info"><i class="fa fa-arrow-right"></i></a></td>';
        echo '</tr>';
      }
      echo mysqli_error($conn);
      ?>
    </tbody>
  </table>
  <br><hr><br>
<?php
mysqli_data_seek($result,0);
while($result && ($row = $result->fetch_assoc())):
  $i = $row['id'];
  //TODO: Backward transitions are not possible, as are transitions into same state (bill to bill, for example)
?>
<form method="post">
  <div class="modal fade choose-transition-<?php echo $i; ?>">
    <div class="modal-dialog modal-sm modal-content">
      <div class="modal-header">
        <h3><?php echo $lang['TRANSITION']; ?></h3>
      </div>
      <div class="modal-body">
        <div class="radio">
          <label class="btn btn-link"><input type="radio" checked value="AUB" name="transit"/><?php echo $lang['ORDER_CONFIRMATION']; ?></label><br>
          <label class="btn btn-link"><input type="radio" value="RE" name="transit" /><?php echo $lang['RECEIPT']; ?></label><br>
          <label class="btn btn-link"><input type="radio" disabled value="LFS" name="transit" /><?php echo $lang['DELIVERY_NOTE']; ?></label><br>
          <label class="btn btn-link"><input type="radio" disabled value="GUT" name="transit" /><?php echo $lang['CREDIT']; ?></label><br>
          <label class="btn btn-link"><input type="radio" disabled value="STN" name="transit" /><?php echo $lang['CANCELLATION']; ?></label><br>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" data-dismiss="modal" class="btn btn-default">Cancel</button>
        <button type="submit" class="btn btn-warning" name="translate" value="<?php echo $i; ?>">OK</button>
      </div>
    </div>
  </div>
</form>
<?php endwhile; ?>

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
<?php if($filterCompany){ echo "<script> showClients($filterCompany, $filterClient); </script>"; } ?>
<?php include 'footer.php'; ?>
