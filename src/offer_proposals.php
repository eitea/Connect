<?php require 'header.php'; enableToERP($userID); ?>
<?php
$transitions = array('ANG', 'AUB', 'RE', 'LFS', 'GUT', 'STN');
$trans_lans = array('ANG' => $lang['OFFERS'], 'AUB' => $lang['ORDER_CONFIRMATION'], 'RE' => $lang['RECEIPT'], 'LFS' => $lang['DELIVERY_NOTE'], 'GUT' => $lang['CREDIT'], 'STN' => $lang['CANCELLATION']);

$filterCompany = $filterClient = 0;
$filterStatus = -1;
$filterProcess = array();
if($_SERVER['REQUEST_METHOD'] == 'POST'){
  if(isset($_POST['filterCompany'])){
    $filterCompany = $_POST['filterCompany'];
  }
  if(isset($_POST['filterClient'])){
    $filterClient = $_POST['filterClient'];
  }
  if(isset($_POST['filterProcess'])){
    $filterProcess = $_POST['filterProcess'];
  }
  if(isset($_POST['filterStatus'])){
    $filterStatus = $_POST['filterStatus'];
  } else {
    $filterStatus = 0; //waiting
  }
  if(isset($_POST['translate']) && !empty($_POST['transit'])){
    $proposalID = intval($_POST['translate']);
    $transition = test_input($_POST['transit']);
    $transitionID = getNextERP($transition);
    $conn->query("UPDATE proposals SET history = CONCAT_WS(' ', history , id_number), id_number = '$transitionID' WHERE id = $proposalID");
    echo mysqli_error($conn);
  }
}

$CURRENT_TRANSITIONS = empty($filterProcess) ? $transitions : $filterProcess;

$result = $conn->query("SELECT * FROM $clientTable WHERE companyID IN (".implode(', ', $available_companies).")");
if(!$result || $result->num_rows <= 0){
  echo '<div class="alert alert-info">'.$lang['WARNING_NO_CLIENTS'].'<br><br>';
  include "new_client.php";
  echo '</div>';
}
?>

<div class="page-header">
  <h3><?php echo $lang['PROCESSES']; ?></h3>
</div>

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
  <button type="submit" class="btn btn-warning ">Filter</button>
  <br><br>
  <select class="js-example-basic-single" style='width:400px' name="filterProcess[]" multiple="multiple">
    <?php
    for($i=0; $i < count($transitions); $i++){
      $selected = '';
      if(in_array($i, $filterProcess)){
        $selected = 'selected';
      }
      echo "<option $selected value='$i'>".$trans_lans[$transitions[$i]].'</option>';
    }
    ?>
  </select>
  <select class="js-example-basic-single" style='width:150px'  name="filterStatus">
    <option value="-1"><?php echo $lang['DISPLAY_ALL']; ?></option>
    <?php
    for($i=0; $i < 3; $i++){
      $selected = '';
      if($i == $filterStatus){
        $selected = 'selected';
      }
      echo '<option value="2" '.$selected.' >'.$lang['OFFERSTATUS_TOSTRING'][$i].'</option>';
    }
    ?>
  </select>
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
      <th><?php echo $lang['PREVIOUS']; ?></th>
      <th><?php echo $lang['PROP_OUR_SIGN']; ?></th>
      <th><?php echo $lang['PROP_OUR_MESSAGE']; ?></th>
      <th>Option</th>
      <th style="text-align:right;"><?php echo $lang['TRANSITION']; ?></th>
    </thead>
    <tbody>
      <?php
      while($result && ($row = $result->fetch_assoc())){
        foreach($CURRENT_TRANSITIONS as $currentProcess){
          $transitable = false;
          $transited_from = $transited_into = '';
          $current_transition = preg_replace('/\d/', '', $row['id_number']);
          $lineColor = '';
          if($current_transition == $currentProcess){
            $id_name = $row['id_number'];
            $status = $lang['OFFERSTATUS_TOSTRING'][$row['status']];
            $transitable = true;
            $transited_from = substr($row['history'],-10);
            if($row['status'] == 2){
              $lineColor = '#c7c6c6';
            }
          } else {
            $p = strpos($row['history'], $currentProcess);
            if($p !== false){ //it may also! have used to be it
              $id_name = substr($row['history'], $p, 10);
              $status = $lang['FORWARDED'].': '.$row['id_number'];
              if($p > 8){
                $transited_from = substr($row['history'], $p-11, 10);
              }
              if(strlen($row['history']) > $p + 15){
                $status = $lang['FORWARDED'].': '.substr($row['history'], $p+11, 10);
              }
              $lineColor = '#6fcf2c';
            } else {
              continue;
            }
          }

          $i = $row['id'];
          echo "<tr style='color:$lineColor'>";
          echo '<td>'.$id_name.'</td>';
          echo '<td>'.$row['clientName'].'</td>';
          echo '<td>'.$status.'</td>';
          echo '<td>'.$transited_from.'</td>';
          echo '<td>'.$row['ourSign'].'</td>';
          echo '<td>'.$row['ourMessage'].'</td>';
          echo '<td>';
          echo '<form method="POST" style="display:inline" action="download_proposal.php" target="_blank">'."<button type='submit' class='btn btn-default' value='$i' name='download_proposal'><i class='fa fa-download'></i></button></form> ";
          if($transitable){
            echo '<form method="POST" style="display:inline" action="offer_proposal_edit.php"><button type="submit" class="btn btn-default" name="filterProposal" value="'.$row['id'].'"><i class="fa fa-pencil"></i></button></form> ';
            echo '<form method="POST" style="display:inline"><button type="submit" class="btn btn-danger" title="Deleting will also delete EVERY transition!" name="delete_proposal" value="'.$row['id'].'"><i class="fa fa-trash-o"></i></button></form> ';
          }
          echo '</td>';
          echo '<td style="text-align:right;">';
          if($transitable){
            echo '<a data-target=".choose-transition-'.$i.'" data-toggle="modal" class="btn btn-info"><i class="fa fa-arrow-right"></i></a>';
          }
          echo '</td>';
          echo '</tr>';
        }
      }
      echo mysqli_error($conn);
      ?>
    </tbody>
  </table>

<?php
mysqli_data_seek($result,0);
while($result && ($row = $result->fetch_assoc())):
  $i = $row['id'];
  //TODO: Backward transitions are not possible, as are transitions into same state
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
