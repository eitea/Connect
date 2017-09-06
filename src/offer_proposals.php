<?php require 'header.php'; enableToERP($userID); ?>
<?php
$transitions = array('ANG', 'AUB', 'RE', 'LFS', 'GUT', 'STN');
$filterings = array('savePage' => $this_page, 'procedures' => array(array(), 0, 'checked'), 'company' => 0, 'client' => 0);

if($_SERVER['REQUEST_METHOD'] == 'POST'){
  if(isset($_POST['delete_proposal'])){
    $conn->query("DELETE FROM proposals WHERE id = ".intval($_POST['delete_proposal']));
    if($conn->error){ echo $conn->error;} else {echo '<div class="alert alert-success"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$lang['OK_DELETE'].'</div>';}
  } elseif(isset($_POST['save_wait'])){
    $conn->query("UPDATE proposals SET status = 0 WHERE id = ".intval($_POST['save_wait']));
    if($conn->error){ echo $conn->error;} else {echo '<div class="alert alert-success"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$lang['OK_SAVE'].'</div>';}
  } elseif(isset($_POST['save_complete'])){
    $conn->query("UPDATE proposals SET status = 1 WHERE id = ".intval($_POST['save_complete']));
    if($conn->error){ echo $conn->error;} else {echo '<div class="alert alert-success"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$lang['OK_SAVE'].'</div>';}
  } elseif(isset($_POST['save_cancel'])){
    $conn->query("UPDATE proposals SET status = 2 WHERE id = ".intval($_POST['save_cancel']));
    if($conn->error){ echo $conn->error;} else {echo '<div class="alert alert-success"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$lang['OK_SAVE'].'</div>';}
  } elseif(isset($_POST['turnBalanceOff'])) {
    $conn->query("UPDATE UserData SET erpOption = 'FALSE' WHERE id = $userID");
  } elseif(isset($_POST['turnBalanceOn'])) {
    $conn->query("UPDATE UserData SET erpOption = 'TRUE' WHERE id = $userID");
  }
}
if(isset($_GET['err']) && $_GET['err'] == 1){echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$lang['ERROR_MISSING_SELECTION'].'</div>';}
$result = $conn->query("SELECT * FROM $clientTable WHERE companyID IN (".implode(', ', $available_companies).")");
if(!$result || $result->num_rows <= 0){
  echo '<div class="alert alert-info">'.$lang['WARNING_NO_CLIENTS'].'<br><br>';
  include "misc/new_client.php";
  echo '</div>';
}
$result = $conn->query("SELECT erpOption FROM UserData WHERE id = $userID");
if($result && ($row = $result->fetch_assoc())){ $showBalance = $row['erpOption'];} else { $showBalance = 'FALSE'; }
?>

<div class="page-header">
  <h3><?php echo $lang['PROCESSES']; ?>
    <div class="page-header-button-group">
      <?php include 'misc/set_filter.php'; ?>
      <button type="button" class="btn btn-default" data-toggle="modal" data-target=".add_process" title="<?php echo $lang['NEW_PROCESS']; ?>"><i class="fa fa-plus"></i></button>
      <form method="post" style="display:inline-block">
        <?php
        if($showBalance == 'TRUE'){
          echo '<button type="submit" name="turnBalanceOff" class="btn btn-warning" title="Bilanz deaktivieren"><i class="fa fa-check"></i> Bilanz</button>';
        } else {
          echo '<button type="submit" name="turnBalanceOn" class="btn btn-default" title="Bilanz aktivieren"><i class="fa fa-times"></i> Bilanz</button>';
        }
        ?>
      </form>
    </div>
  </h3>
</div>

<?php
$CURRENT_TRANSITIONS = empty($filterings['procedures'][0]) ? $transitions : $filterings['procedures'][0];
$filterCompany_query = $filterings['company'] ?  'AND clientData.companyID = '.$filterings['company'] : "";
$filterClient_query = $filterings['client'] ?  'AND clientData.id = '.$filterings['client'] : "";
$filterStatus_query = ($filterings['procedures'][1] >= 0) ? 'AND status = '.$filterings['procedures'][1] : "";

$result = $conn->query("SELECT proposals.*, clientData.name as clientName, companyData.name as companyName
FROM proposals INNER JOIN clientData ON proposals.clientID = clientData.id INNER JOIN companyData ON clientData.companyID = companyData.id
WHERE companyID IN (".implode(', ', $available_companies).") $filterCompany_query $filterClient_query $filterStatus_query");
?>

<table class="table table-hover">
  <thead>
    <th>ID</th>
    <?php if(count($available_companies) > 2){ echo '<th>'.$lang['COMPANY'].'</th>';} ?>
    <th><?php echo $lang['CLIENT']; ?></th>
    <th>Status</th>
    <th><?php echo $lang['PREVIOUS']; ?></th>
    <th><?php echo $lang['PROP_OUR_SIGN']; ?></th>
    <th><?php echo $lang['PROP_OUR_MESSAGE']; ?></th>
    <?php if($showBalance == 'TRUE') echo '<th>Bilanz</th>'; ?>
    <th>Option</th>
  </thead>
  <tbody>
    <?php //my gosh what is this
    while($result && ($row = $result->fetch_assoc())){
      foreach($CURRENT_TRANSITIONS as $currentProcess){
        $transitable = false;
        $transited_from = $transited_into = $lineColor = '';
        $current_transition = preg_replace('/\d/', '', $row['id_number']); //remove all numbers
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

        if(!$transitable && $filterings['procedures'][2]){ continue; }

        $i = $row['id'];
        echo "<tr style='color:$lineColor'>";
        echo '<td>'.$id_name.'</td>';
        if(count($available_companies) > 2){ echo '<td>'.$row['companyName'].'</td>';}
        echo '<td>'.$row['clientName'].'</td>';
        $balance = 0;
        if($transitable){
          echo '<td><form method="POST"><div class="dropdown"><a href="#" class="btn btn-default dropdown-toggle" data-toggle="dropdown">'.$lang['OFFERSTATUS_TOSTRING'][$row['status']].'<i class="fa fa-caret-down"></i></a><ul class="dropdown-menu">';
          echo '<li><button type="submit" name="save_wait" class="btn btn-link" value="'.$i.'">'.$lang['OFFERSTATUS_TOSTRING'][0].'</button></li>';
          echo '<li><button type="submit" name="save_complete" class="btn btn-link" value="'.$i.'">'.$lang['OFFERSTATUS_TOSTRING'][1].'</button></li>';
          echo '<li><button type="submit" name="save_cancel" class="btn btn-link" value="'.$i.'">'.$lang['OFFERSTATUS_TOSTRING'][2].'</button></li>';
          echo '</ul></div></form></td>';
          $result_b = $conn->query("SELECT * FROM products WHERE proposalID = $i");
          while($rowB = $result_b->fetch_assoc()){
            $balance += $rowB['quantity'] * ($rowB['price'] - $rowB['purchase']);
          }
        } else {
          echo "<td>$status</td>";
        }
        echo '<td>'.$transited_from.'</td>';
        echo '<td>'.$row['ourSign'].'</td>';
        echo '<td>'.$row['ourMessage'].'</td>';
        $style = $balance > 0 ? "style='color:#6fcf2c'" : "style='color:#facf1e'";
        if($showBalance == 'TRUE') echo "<td $style>".sprintf('%+.2f',$balance).' EUR</td>';
        echo '<td>';
        echo "<a href='download?num=$id_name' class='btn btn-default' target='_blank'><i class='fa fa-download'></i></a> ";
        if($transitable){
          echo '<form method="POST" action="edit" style="display:inline-block;"><button type="submit" class="btn btn-default" title="'.$lang['EDIT'].'" name="proposalID" value="'.$row['id'].'"><i class="fa fa-pencil"></i></button></form> ';
          if($currentProcess != 'RE') echo '<button type="button" class="btn btn-default" title="'.$lang['WARNING_DELETE_TRANSITION'].'" data-toggle="modal" data-target=".confirm-delete-'.$row['id'].'"><i class="fa fa-trash-o"></i></button> ';
          echo '<a data-target=".choose-transition-'.$i.'" data-toggle="modal" class="btn btn-warning" title="'.$lang['TRANSITION'].'"><i class="fa fa-arrow-right"></i></a>';
        }
        echo '</td>';
        echo '</tr>';
      }
    }
    echo mysqli_error($conn);
    ?>
  </tbody>
</table>
<!-- TRANSITIONS -->
<?php
mysqli_data_seek($result,0);
while($result && ($row = $result->fetch_assoc())):
  $i = $row['id'];
  $current_transition = preg_replace('/\d/', '', $row['id_number']);
  //Backward transitions are not possible, as are transitions into same state
  $pos = array_search($current_transition, $transitions);
  $bad = array_slice($transitions, 0, $pos);
  $bad[] = $transitions[$pos];
  ?>
  <form method="POST" action="edit">
    <div class="modal fade choose-transition-<?php echo $i; ?>">
      <div class="modal-dialog modal-sm modal-content">
        <div class="modal-header">
          <h3><?php echo $lang['TRANSITION']; ?></h3>
        </div>
        <div class="modal-body">
          <div class="radio">
            <?php
            $checked = '';
            foreach($transitions as $t){
              $disabled = '';
              if(in_array($t, $bad)){
                $disabled = 'disabled';
              }
              echo "<label><input type='radio' $disabled $checked value='$t' name='transit' /> ".$lang['PROPOSAL_TOSTRING'][$t]."</label><br>";
              if($current_transition == $t){
                $checked = 'checked'; //enable the next transition
              } else {
                $checked = '';
              }
            }
            ?>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" data-dismiss="modal" class="btn btn-default">Cancel</button>
          <button type="submit" class="btn btn-warning" name="translate" value="<?php echo $i; ?>">OK</button>
        </div>
      </div>
    </div>
  </form>

  <form method="POST">
    <div class="modal fade confirm-delete-<?php echo $i; ?>">
      <div class="modal-dialog modal-sm modal-content">
        <div class="modal-header"><h4 class="modal-title"><?php echo sprintf($lang['ASK_DELETE'], $row['id_number']); ?></h4></div>
        <div class="modal-body">
          <?php echo $lang['WARNING_DELETE_TRANSITION']; ?>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo $lang['CONFIRM_CANCEL']; ?></button>
          <button type="submit" name='delete_proposal' class="btn btn-warning" value="<?php echo $i; ?>"><?php echo $lang['CONFIRM']; ?></button>
        </div>
      </div>
    </div>
  </form>
<?php endwhile; ?>

<form method="POST" action="edit">
  <div class="modal fade add_process">
    <div class="modal-dialog modal-md modal-content">
      <div class="modal-header"><h4><?php echo $lang['NEW_PROCESS']; ?></h4></div>
      <div class="modal-body">
        <div class="container-fluid">
          <div class="col-sm-12">
            <?php include 'misc/select_client.php'; ?>
          </div>
          <div class="col-sm-6"><br>
            <label><?php echo $lang['CHOOSE_PROCESS']; ?></label>
            <select class="js-example-basic-single" name="nERP">
              <option value="ANG"><?php echo $lang['PROPOSAL_TOSTRING']['ANG']; ?></option>
              <option value="AUB"><?php echo $lang['PROPOSAL_TOSTRING']['AUB']; ?></option>
              <option value="RE"><?php echo $lang['PROPOSAL_TOSTRING']['RE']; ?></option>
            </select>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" data-dismiss="modal" class="btn btn-default">Cancel</button>
        <button type="submit" class="btn btn-warning" name="add_new_process" value="<?php echo $i; ?>"><?php echo $lang['CONTINUE']; ?></button>
      </div>
    </div>
  </div>
</form>

<script type="text/javascript">
$('.table').DataTable({
  ordering: false,
  responsive: true,
  autoWidth: false,
  dom: 'f',
  paginate: false,
  language: {
    <?php echo $lang['DATATABLES_LANG_OPTIONS']; ?>
  }
});
</script>

<?php include 'footer.php'; ?>
