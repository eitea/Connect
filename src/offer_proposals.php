<?php require 'header.php'; enableToERP($userID);
$transitions = array('ANG', 'AUB', 'RE', 'LFS', 'GUT', 'STN');
$filterings = array('procedures' => array(array(), 0, 'checked'), 'company' => 0, 'client' => 0);

if(isset($_GET['t'])){
  $filterings['procedures'][0] = array(strtoupper($_GET['t']));
}

if($_SERVER['REQUEST_METHOD'] == 'POST'){
  if(isset($_POST['delete_proposal'])){
    $conn->query("DELETE FROM processHistory WHERE id = ".intval($_POST['delete_proposal']));
    if($conn->error){ echo $conn->error;} else {echo '<div class="alert alert-success"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$lang['OK_DELETE'].'</div>';}
    $conn->query("DELETE p1 FROM proposals p1 WHERE p1.id NOT IN(SELECT processID FROM processHistory WHERE processID = p1.id)"); echo $conn->error;
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
  } elseif(!empty($_POST['copy_process'])) {
    $val = intval($_POST['copy_process']);
    $result = $conn->query("SELECT processID, id_number FROM processHistory WHERE id = $val"); echo $conn->error;
    $row = $result->fetch_assoc();
    $processID = $row['processID'];
    //copy process
    $conn->query("INSERT INTO proposals(clientID, status, deliveryDate, paymentMethod, shipmentType, representative, porto, portoRate, header, referenceNumrow)
    SELECT clientID, status, deliveryDate, paymentMethod, shipmentType, representative, porto, portoRate, header, referenceNumrow FROM proposals WHERE id = $processID"); echo $conn->error;
    //insert history
    $processID = $conn->insert_id;
    $conn->query("INSERT INTO processHistory(id_number, processID) VALUES('".$row['id_number']."', $processID)"); echo $conn->error;
    //insert products
    $historyID = $conn->insert_id;
    $origin = randomPassword(16);
    $conn->query("INSERT INTO products(name, description, price, quantity, unit, taxID, cash, purchase, position, iv, iv2, historyID, origin)
    SELECT name, description, price, quantity, unit, taxID, cash, purchase, position, iv, iv2, $historyID, '$origin' FROM products WHERE historyID = $val");
    if($conn->error){
      echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$conn->error.'</div>';
    } else {
      echo '<div class="alert alert-success"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$lang['OK_ADD'].'</div>';
    }
  } elseif(isset($_POST['add_new_process']) && !empty($_POST['filterClient']) && !empty($_POST['nERP'])){
    $val = intval($_POST['filterClient']);
    $result = $conn->query("SELECT representative, paymentMethod, shipmentType FROM clientInfoData WHERE clientID = $val");
    if($result && $row = $result->fetch_assoc()){
      $meta_paymentMethod = $row['paymentMethod'];
      $meta_shipmentType = $row['shipmentType'];
      $meta_representative = $row['representative'];
    } else {
      echo $conn->error;
      $meta_paymentMethod = $meta_shipmentType = $meta_representative = '';
    }
    $date = getCurrentTimestamp();
    if(isset($_POST['filterCompany'])){
      $num = getNextERP(test_input($_POST['nERP']), $_POST['filterCompany']); 
    } else {
      $num = getNextERP(test_input($_POST['nERP']), $available_companies[1]);
    }
    $conn->query("INSERT INTO proposals (clientID, status, curDate, deliveryDate, paymentMethod, shipmentType, representative)
    VALUES ($val, '0', '$date', '$date', '$meta_paymentMethod', '$meta_shipmentType', '$meta_representative')");
    $val = $conn->insert_id;
    $conn->query("INSERT INTO processHistory (id_number, processID) VALUES('$num', $val)");
    $val = $conn->insert_id;
    if(!$conn->error){
      redirect("edit?val=$val");
    } else {
      echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$conn->error.'</div>';
    }
  }
}

if(isset($_GET['err'])){
  echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>';
  $val = $_GET['err'];
  if($val == 1){
    echo $lang['ERROR_MISSING_SELECTION'];
  } elseif($val == 2){
    echo $lang['ERROR_UNEXPECTED'];
  } else {
    echo 'Unknown error';
  }
  echo '</div>';
}
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
$filtered_transitions = empty($filterings['procedures'][0]) ? $transitions : $filterings['procedures'][0];
$filterCompany_query = $filterings['company'] ?  'AND clientData.companyID = '.$filterings['company'] : "";
$filterClient_query = $filterings['client'] ?  'AND clientData.id = '.$filterings['client'] : "";
$filterStatus_query = ($filterings['procedures'][1] >= 0) ? 'AND status = '.$filterings['procedures'][1] : "";

$result = $conn->query("SELECT proposals.*, companyID, clientData.name as clientName, companyData.name as companyName
FROM proposals INNER JOIN clientData ON proposals.clientID = clientData.id INNER JOIN companyData ON clientData.companyID = companyData.id
WHERE companyID IN (".implode(', ', $available_companies).") $filterCompany_query $filterClient_query $filterStatus_query");
?>

<table class="table table-hover">
  <thead>
    <?php if(count($available_companies) > 2){ echo '<th>'.$lang['COMPANY'].'</th>';} ?>
    <th><?php echo $lang['CLIENT']; ?></th>
    <th>ID</th>
    <th>Status</th>
    <?php if($showBalance == 'TRUE') echo '<th>Bilanz</th>'; ?>
    <th>Option</th>
  </thead>
  <tbody>
    <?php
    $modals = '';
      //each process splits into a history where we need to check 
      while($result && ($row = $result->fetch_assoc())){
        $result_process = $conn->query("SELECT * FROM processHistory WHERE processID = ".$row['id']);
        $product_placements = array_fill_keys($transitions, array());
        echo '<tr style="background-color:#e4e4e4">';
        if(count($available_companies) > 2){ echo '<td>'.$row['companyName'].'</td>';}
        echo '<td>'.$row['clientName'].'</td><td></td>';
        echo '<td><form method="POST"><div class="dropdown"><a href="#" class="btn btn-default dropdown-toggle" data-toggle="dropdown">'.$lang['OFFERSTATUS_TOSTRING'][$row['status']].'<i class="fa fa-caret-down"></i></a><ul class="dropdown-menu">';
        echo '<li><button type="submit" name="save_wait" class="btn btn-link" value="'.$row['id'].'">'.$lang['OFFERSTATUS_TOSTRING'][0].'</button></li>';
        echo '<li><button type="submit" name="save_complete" class="btn btn-link" value="'.$row['id'].'">'.$lang['OFFERSTATUS_TOSTRING'][1].'</button></li>';
        echo '<li><button type="submit" name="save_cancel" class="btn btn-link" value="'.$row['id'].'">'.$lang['OFFERSTATUS_TOSTRING'][2].'</button></li>';
        echo '</ul></div></form></td>';
        if($showBalance == 'TRUE') echo '<td></td>';
        echo '<td></td>';
        echo '</tr>';
        while($row_history = $result_process->fetch_assoc()){
          $i = $row_history['id'];
          $current_transition = preg_replace('/\d/', '', $row_history['id_number']);
          if(!in_array($current_transition, $filtered_transitions)) continue;

          $balance = 0;
          $result_b = $conn->query("SELECT quantity, price, purchase, origin FROM products WHERE historyID = $i AND origin IS NOT NULL");
          while($rowB = $result_b->fetch_assoc()){
            if(empty($product_placements[$current_transition][$rowB['origin']])) $product_placements[$current_transition][$rowB['origin']] = 0;
            $product_placements[$current_transition][$rowB['origin']] += $rowB['quantity'];
            $balance += $rowB['quantity'] * ($rowB['price'] - $rowB['purchase']);
          }

          $transitable = $isYoungest = false;
          if($current_transition == 'ANG') {$transitable = true; $available_transitions = array('AUB', 'RE', 'STN');}
          if($current_transition == 'AUB') {$transitable = true; $available_transitions = array('RE', 'LFS', 'STN');}
          if($current_transition == 'RE') {$transitable = true; $available_transitions = array('LFS', 'GUT');}

          echo "<tr>";
          if(count($available_companies) > 2){ echo '<td></td>';}
          echo '<td></td>';
          echo '<td>'.$row_history['id_number'].'</td>';

          if($isYoungest){
            //TODO: are all of the products transited? 
          } else {
            echo "<td>-TBC-</td>";
          }
          
          $style = $balance > 0 ? "style='color:#6fcf2c;font-weight:bold;'" : "style='color:#facf1e;font-weight:bold;'";
          if($showBalance == 'TRUE') echo "<td $style>".number_format($balance, 2, ',', '.').' EUR</td>';
          echo '<td>';
          echo "<a href='download?proc=$i' class='btn btn-default' target='_blank'><i class='fa fa-download'></i></a> ";
          echo '<form method="POST" style="display:inline"><button type="submit" class="btn btn-default" name="copy_process" title="'.$lang['COPY'].'" value="'.$i.'"><i class="fa fa-files-o"></i></button></form> ';
          echo '<a href="edit?val='.$i.'" title="'.$lang['EDIT'].'" class="btn btn-default"><i class="fa fa-pencil"></i></a> ';
          
          if($transitable){ //if open positions
            if($current_transition != 'RE'){ echo '<button type="button" class="btn btn-default" title="'.$lang['DELETE'].'" data-toggle="modal" data-target=".confirm-delete-'.$i.'"><i class="fa fa-trash-o"></i></button> '; }
            echo '<a data-target=".choose-transition-'.$i.'" data-toggle="modal" class="btn btn-warning" title="'.$lang['TRANSITION'].'"><i class="fa fa-arrow-right"></i></a>';

            $modal_transits = '';
            foreach($available_transitions as $t){
              $modal_transits .= '<div class="row"><div class="col-xs-6"><label><input type="radio" name="copy_transition" value="'.$t.'" />'.getNextERP($t, $row['companyID']).'</label></div><div class="col-xs-6">'.$lang['PROPOSAL_TOSTRING'][$t].'</div></div>';
            }
            $modals .= '<form method="POST" action="edit?val='.$i.'">
            <div class="modal fade choose-transition-'.$i.'">
              <div class="modal-dialog modal-sm modal-content">
                <div class="modal-header"><h3>'.$lang['TRANSITION'].'</h3></div>
                <div class="modal-body"><div class="radio">'.$modal_transits.'</div></div>
                <div class="modal-footer">
                  <button type="button" data-dismiss="modal" class="btn btn-default">Cancel</button>
                  <button type="submit" class="btn btn-warning" name="translate">OK</button>
                </div>
              </div>
            </div></form><form method="POST">
            <div class="modal fade confirm-delete-'.$i.'">
              <div class="modal-dialog modal-sm modal-content">
                <div class="modal-header"><h4 class="modal-title">'.sprintf($lang['ASK_DELETE'], $row_history['id_number']).'</h4></div>
                <div class="modal-body">#'.$lang['WARNING_DELETE_TRANSITION'].'</div>
                <div class="modal-footer">
                  <button type="button" class="btn btn-default" data-dismiss="modal">'.$lang['CONFIRM_CANCEL'].'</button>
                  <button type="submit" name="delete_proposal" class="btn btn-warning" value="'.$i.'">'.$lang['CONFIRM'].'</button>
                </div>
              </div>
            </div>
          </form>';
          } //endif transitable
          
          echo '</td>';
          echo '</tr>';
        } //endwhile each history
        //TODO: i can only evaluate in here whether a transition is the youngest or if it still has open positions or not 
        //solve this either by a second iteration over questionable entries or... magic

      } //endwhile each process
      echo $conn->error;
    ?>

  </tbody>
</table>
<?php echo $modals; ?>
<form method="POST">
  <div class="modal fade add_process">
    <div class="modal-dialog modal-md modal-content">
      <div class="modal-header"><h4><?php echo $lang['NEW_PROCESS']; ?></h4></div>
      <div class="modal-body">
        <div class="container-fluid">
          <div class="col-sm-12"><?php include 'misc/select_client.php'; ?></div>
          <div class="col-sm-6"><br>
            <label><?php echo $lang['CHOOSE_PROCESS']; ?></label>
            <select class="js-example-basic-single" name="nERP">
              <option value="ANG"><?php echo $lang['PROPOSAL_TOSTRING']['ANG']; ?></option>
              <option <?php if($filterings['procedures'][0] == 'sub') echo "selected"; ?> value="AUB"><?php echo $lang['PROPOSAL_TOSTRING']['AUB']; ?></option>
              <option <?php if($filterings['procedures'][0] == 're') echo "selected"; ?> value="RE"><?php echo $lang['PROPOSAL_TOSTRING']['RE']; ?></option>
              <option <?php if($filterings['procedures'][0] == 'lfs') echo "selected"; ?> value="LFS"><?php echo $lang['PROPOSAL_TOSTRING']['LFS']; ?></option>
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
  order: [],
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
