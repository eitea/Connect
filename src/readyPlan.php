<?php include 'header.php'; ?>
<?php include 'validate.php'; ?>
<!-- BODY -->

<div class="page-header">
  <h3><?php echo $lang['READY_STATUS']; ?></h3>
</div>

<table class="table table-hover">
  <thead>
    <th>Name</th>
    <th>Checkin</th>
  </thead>
  <tbody>
    <?php
    $today = substr(getCurrentTimestamp(),0,10);
    $sql = "SELECT * FROM $logTable INNER JOIN $userTable ON $userTable.id = $logTable.userID WHERE time LIKE '$today %' AND timeEND = '0000-00-00 00:00:00'";
    $result = $conn->query($sql);
    if($result && $result->num_rows > 0){
      while($row = $result->fetch_assoc()){
        echo '<tr><td>' . $row['firstname'] .' '. $row['lastname'] .'</td>';
        echo '<td>'. substr(carryOverAdder_Hours($row['time'], $row['timeToUTC']), 11, 5) . '</td></tr>';
      }
    } else {
      echo mysqli_error($conn);
    }
    ?>
  </tbody>
</table>

<!-- /BODY -->
<?php include 'footer.php'; ?>
