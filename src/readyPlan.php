<?php include 'header.php'; ?>
<!-- BODY -->
<?php
$result = $conn->query("SELECT enableReadyCheck FROM $configTable");
$row = $result->fetch_assoc();
$isAdmin = $conn->query("SELECT * FROM $roleTable WHERE userID = $userID AND isCoreAdmin = 'TRUE'");
if(!$row['enableReadyCheck'] && !$isAdmin){
  die("Access restricted, only a CORE Admin can view this page and enable it for others.");
}
?>
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
    $sql = "SELECT * FROM $logTable INNER JOIN $userTable ON $userTable.id = $logTable.userID WHERE time LIKE '$today %' AND timeEnd = '0000-00-00 00:00:00' ORDER BY firstname DESC";
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
