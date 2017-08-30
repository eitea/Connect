<?php require 'header.php'; enableToTime($userID); ?>
<?php $filterings = array('user' => 0, 'date' => array('2016-06-01', substr(getCurrentTimestamp(), 0, 10)), ) ?>
<div class="page-header">
  <h3><?php echo $lang['REQUESTS'].' '.$lang['OVERVIEW']; ?> <div class="page-header-button-group"><?php include 'misc/set_filter.php'; ?></div></h3>
</div>

<table class="table table-hover">
  <thead><tr>
    <th>Name</th>
    <th>Typ</th>
    <th><?php echo $lang['DATE']; ?></th>
    <th><?php echo $lang['Status']; ?></th>
  </tr></thead>
  <tbody>
    <?php
    $result = $conn->query("SELECT * FROM userRequestsData INNER JOIN UserData ON userRequestsData.userID = UserData.id");
    while($row = $result->fetch_assoc()){
      echo '<tr>';
      echo '<td></td>';
      echo '</tr>';
    }
    ?>
  </tbody>
</table>
<?php include 'footer.php'; ?>
