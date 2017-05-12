<?php include 'header.php'; ?>
<!-- BODY -->

<div class="page-header">
<h3><?php echo $lang['VACATION']; ?></h3>
</div>

<?php
if($_SERVER['REQUEST_METHOD'] == 'POST'){
  if(isset($_POST['okay'])){
    $requestID = $_POST['okay'];
    $result = $conn->query("SELECT *, $intervalTable.id AS intervalID FROM $userRequests INNER JOIN $intervalTable ON $intervalTable.userID = $userRequests.userID
    WHERE status = '0' AND $userRequests.id = $requestID AND $intervalTable.endDate IS NULL");

    if($result && ($row = $result->fetch_assoc())){
      $i = $row['fromDate'];
      $days = (timeDiff_Hours($i, $row['toDate'])/24) + 1; //days

      for($j = 0; $j < $days; $j++){
        if(isHoliday($i)){
          $expected = 0;
        } else {
          $expected = $row[strtolower(date('D', strtotime($i)))];
        }

        //only insert if expectedHours != 0
        if($expected != 0){
          $expectedHours = floor($expected);
          $expectedMinutes = ($expected * 60) % 60;
          $i2 = carryOverAdder_Hours($i, $expectedHours);
          $i2 = carryOverAdder_Minutes($i2, $expectedMinutes);

          $sql = "INSERT INTO $logTable (time, timeEnd, userID, timeToUTC, status) VALUES('$i', '$i2', ".$row['userID'].", '0', '1')";
          $conn->query($sql);
          echo mysqli_error($conn);
        }
        $i = carryOverAdder_Hours($i, 24);
      }

      $answerText = test_input($_POST['answerText'. $requestID]); //inputs are always set
      $conn->query("UPDATE $userRequests SET status = '2', answerText = '$answerText' WHERE id = $requestID");
    } else {
      echo $conn->error;
    }
  } elseif(isset($_POST['nokay'])){
    $requestID = $_POST['nokay'];
    $answerText = $_POST['answerText'. $requestID];
    $conn->query("UPDATE $userRequests SET status = '1',answerText = '$answerText' WHERE id = $requestID");
  }
}
?>
<body>
<form method="POST">
  <table class="table table-hover">
    <th>Name</th>
    <th><?php echo $lang['TIME']?></th>
    <th>Reason</th>
    <th class=text-center><?php echo $lang['REPLY_TEXT']; ?></th>
    <tbody>
    <?php
    $sql = "SELECT $userTable.firstname, $userTable.lastname, $userRequests.id, $userRequests.fromDate, $userRequests.toDate, $userRequests.requestText FROM $userRequests INNER JOIN $userTable ON $userTable.id = $userRequests.userID WHERE status = '0'";
    $result = $conn->query($sql);
    if($result && $result->num_rows > 0){
      while($row = $result->fetch_assoc()){
        echo '<tr>';
        echo '<td>'. $row['firstname']. ' ' .$row['lastname'] . '</td>';
        echo '<td>'. substr($row['fromDate'],0,10) . ' - ' . substr($row['toDate'],0,10) . '</td>';
        echo '<td>'. $row['requestText'].'</td>';
        echo '<td>'; ?>
          <div class="form-group">
            <div class="input-group">
              <input type=text class="form-control" name="answerText<?php echo $row['id']; ?>" placeholder="Reply... (Optional)" />
              <span class="input-group-btn">
                <button type="submit" class="btn btn-default" name="okay" value="<?php echo $row['id']; ?>" > <img width=18px height=18px src="../images/okay.png"> </button>
                <button type="submit" class="btn btn-default" name="nokay" value="<?php echo $row['id']; ?>"> <img width=18px height=18px src="../images/not_okay.png"> </button>
              </span>
            </div><!-- /input-group -->
          </div>
        <?php echo '</td>';
        echo '</tr>';
      }
    }
     ?>
   </tbody>
  </table>
</form>

<form action="getTimestamps.php#" method="POST">
  <div class="text-right">
    <button type="submit" class="btn btn-warning" name="filterStatus" value="1">Urlaub bearbeiten <i class="fa fa-arrow-right"></i></button>
  </div>
</form>

<!-- /BODY -->
<?php include 'footer.php'; ?>
