<?php require 'header.php'; ?>

<div class="page-header">
  <h3><?php echo $lang['RECEIPTS']; ?></h3>
</div>

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
      if(substr($row['id_number'], 0, 3) != 'ANG'){ //its something else now
        $id_name = substr($row['history'], strpos($row['history'], 'ANG'), 10);
        $status = $trans_lans[preg_replace('/\d/', '', $row['id_number'])];
      } else {
        $id_name = $row['id_number'];
        $status = $lang['OFFERSTATUS_TOSTRING'][$row['status']];
      }
      $i = $row['id'];
      echo '<tr>';
      echo '<td>'.$id_name.'</td>';
      echo '<td>'.$row['clientName'].'</td>';
      echo '<td>'.$status.'</td>';
      echo '<td>'.$row['ourSign'].'</td>';
      echo '<td>'.$row['ourMessage'].'</td>';
      echo '<td>';
      echo '<form method="POST" style="display:inline" action="offer_proposal_edit.php"><button type="submit" class="btn btn-default" name="filterProposal" value="'.$row['id'].'"><i class="fa fa-pencil"></i></button></form> ';
      echo '<form method="POST" style="display:inline" action="download_proposal.php" target="_blank">'."<button type='submit' class='btn btn-default' value='$i' name='download_proposal'><i class='fa fa-download'></i></button></form> ";
      echo '<form method="POST" style="display:inline"><button type="submit" class="btn btn-danger" title="Delete: Deleting this will also delete EVERY transition!" name="delete_proposal" value="'.$row['id'].'"><i class="fa fa-trash-o"></i></button></form> ';
      echo '</td>';
      echo '<td style="text-align:right;"><a data-target=".choose-transition-'.$i.'" data-toggle="modal" class="btn btn-info"><i class="fa fa-arrow-right"></i></a></td>';
      echo '</tr>';
    }
    echo mysqli_error($conn);
    ?>
  </tbody>
</table>

<?php include 'footer.php'; ?>
