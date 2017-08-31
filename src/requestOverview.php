<?php require 'header.php'; enableToTime($userID); ?>

<div class="page-header">
  <?php $filterings = array('user' => 0, 'date' => array('2016-06-01', substr(getCurrentTimestamp(), 0, 10)), 'acceptance' => -1, 'requestType' => ''); ?>
  <h3><?php echo $lang['REQUESTS'].' '.$lang['OVERVIEW']; ?> <div class="page-header-button-group"><?php include 'misc/set_filter.php'; ?></div></h3>
</div>

<table class="table table-hover">
  <thead><tr>
    <th>Name</th>
    <th>Typ</th>
    <th><?php echo $lang['DATE']; ?></th>
    <th>Status</th>
  </tr></thead>
  <tbody>
    <?php
    $statusQuery = $typeQuery = $userQuery = '';
    if($filterings['acceptance'] > -1){ $statusQuery = "AND userRequestsData.status = '".$filterings['acceptance']."'"; }
    if($filterings['requestType']){ $typeQuery = "AND requestType = '".$filterings['requestType'] ."'" ; }
    if($filterings['user']){ $userQuery = "AND userID = ".$filterings['user']; }
    $result = $conn->query("SELECT * FROM userRequestsData INNER JOIN UserData ON userRequestsData.userID = UserData.id
    WHERE DATE(fromDate) > DATE('{$filterings['date'][0]}') AND DATE(fromDate) < DATE('{$filterings['date'][1]}') $statusQuery $typeQuery $userQuery");
    echo $conn->error;
    while($row = $result->fetch_assoc()){
      echo '<tr>';
      echo '<td>'.$row['firstname'].' '.$row['lastname'].'</td>';
      echo '<td>'.$lang['REQUEST_TOSTRING'][$row['requestType']].'</td>';
      echo '<td>'.$row['fromDate'].' - '.$row['toDate'].'</td>';
      echo '<td>'.$lang['REQUESTSTATUS_TOSTRING'][$row['status']].'</td>';
      echo '</tr>';
    }
    ?>
  </tbody>
</table>
<?php include 'footer.php'; ?>
