<?php require 'header.php'; enableToERP($userID); ?>
<?php
$transitions = array('ANG', 'AUB', 'RE', 'LFS', 'GUT', 'STN');
$filterings = array('savePage' => $this_page, 'procedures' => array(array(), -1, 'checked'), 'company' => 0, 'client' => 0);

if($_SERVER['REQUEST_METHOD'] == 'POST'){
  if(isset($_POST['translate']) && !empty($_POST['transit'])){
    $proposalID = intval($_POST['translate']);
    $transition = test_input($_POST['transit']);
    $transitionID = getNextERP($transition);
    $conn->query("UPDATE proposals SET history = CONCAT_WS(' ', history , id_number), id_number = '$transitionID' WHERE id = $proposalID");
    redirect('offer_proposal_edit.php?num='.$proposalID);
  } elseif(isset($_POST['delete_proposal'])){
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
  }
}

$result = $conn->query("SELECT * FROM $clientTable WHERE companyID IN (".implode(', ', $available_companies).")");
if(!$result || $result->num_rows <= 0){
  echo '<div class="alert alert-info">'.$lang['WARNING_NO_CLIENTS'].'<br><br>';
  include "new_client.php";
  echo '</div>';
}
?>

<div class="page-header">
  <h3><?php echo $lang['PROCESSES']; ?><div class="page-header-button-group"><?php include 'misc/set_filter.php'; ?></div></h3>
</div>

<form method="POST">
  <?php
  $CURRENT_TRANSITIONS = empty($filterings['procedures'][0]) ? $transitions : $filterings['procedures'][0];
  $filterCompany_query = $filterings['company'] ?  'AND clientData.companyID = '.$filterings['company'] : "";
  $filterClient_query = $filterings['client'] ?  'AND clientData.id = '.$filterings['client'] : "";
  $filterStatus_query = ($filterings['procedures'][1] >= 0) ? 'AND status = '.$filterings['procedures'][1] : "";

  $result = $conn->query("SELECT proposals.*, clientData.name as clientName FROM proposals INNER JOIN clientData ON proposals.clientID = clientData.id
  WHERE 1 $filterCompany_query $filterClient_query $filterStatus_query");
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
    </thead>
    <tbody>
      <?php //my gosh what is this
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

          if(!$transitable && $filterings['procedures'][2]){ continue; }

          $i = $row['id'];
          echo "<tr style='color:$lineColor'>";
          echo '<td>'.$id_name.'</td>';
          echo '<td>'.$row['clientName'].'</td>';
          if($transitable){
            echo '<td><div class="dropdown"><a href="#" class="btn btn-default dropdown-toggle" data-toggle="dropdown">'.$lang['OFFERSTATUS_TOSTRING'][$row['status']].'<i class="fa fa-caret-down"></i></a><ul class="dropdown-menu">';
            echo '<li><button type="submit" name="save_wait" class="btn btn-link" value="'.$i.'">'.$lang['OFFERSTATUS_TOSTRING'][0].'</button></li>';
            echo '<li><button type="submit" name="save_complete" class="btn btn-link" value="'.$i.'">'.$lang['OFFERSTATUS_TOSTRING'][1].'</button></li>';
            echo '<li><button type="submit" name="save_cancel" class="btn btn-link" value="'.$i.'">'.$lang['OFFERSTATUS_TOSTRING'][2].'</button></li>';
            echo '</ul></div></td>';
          } else {
            echo "<td>$status</td>";
          }
          echo '<td>'.$transited_from.'</td>';
          echo '<td>'.$row['ourSign'].'</td>';
          echo '<td>'.$row['ourMessage'].'</td>';
          echo '<td>';
          echo "<a href='download_proposal.php?num=$id_name' class='btn btn-default' target='_blank'><i class='fa fa-download'></i></a> ";
          if($transitable){
            echo '<a href="offer_proposal_edit.php?num='.$row['id'].'" class="btn btn-default" title="'.$lang['EDIT'].'" name="filterProposal" value="'.$row['id'].'"><i class="fa fa-pencil"></i></a> ';
            if($currentProcess != 'RE') echo '<button type="submit" class="btn btn-default" title="'.$lang['WARNING_DELETE_TRANSITION'].'" name="delete_proposal" value="'.$row['id'].'" "><i class="fa fa-trash-o"></i></button> ';
            echo '<a data-target=".choose-transition-'.$i.'" data-toggle="modal" class="btn btn-info" title="'.$lang['TRANSITION'].'"><i class="fa fa-arrow-right"></i></a>';
          }
          echo '</td>';
          echo '</tr>';
        }
      }
      echo mysqli_error($conn);
      ?>
    </tbody>
  </table>
  </form>

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
    <form method="POST">
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
  <?php endwhile; ?>
<?php include 'footer.php'; ?>
