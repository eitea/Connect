<?php include 'header.php'; ?>
<?php include 'validate.php'; ?>
<!-- BODY -->

<div class="page-header">
<h3><?php echo $lang['VACATION']; ?></h3>
</div>

<?php

if($_SERVER['REQUEST_METHOD'] == 'POST'){
  $sql = "SELECT $userRequests.*, $bookingTable.* FROM $userRequests INNER JOIN $userTable ON $userTable.id = $userRequests.userID INNER JOIN $bookingTable ON $bookingTable.userID = $userTable.id WHERE status = '0'";
  $result = $conn->query($sql);
  if($result && $result->num_rows > 0){
    while($row = $result->fetch_assoc()){
      if(isset($_POST['okay'. $row['id']])){
        $answerText = test_input($_POST['answerText'. $row['id']]);
        $sql = "UPDATE $userRequests SET status = '2', answerText = '$answerText' WHERE id = " .$row['id'];
        $conn->query($sql);
        //vacation only exists in days at max.
        $i = $row['fromDate'];
        $days = (timeDiff_Hours($i, $row['toDate'])/24) + 1; //days

        for($j = 0; $j < $days; $j++){
          $expected = $row[strtolower(date('D', strtotime($i)))];
          $expectedHours = floor($expected);
          $expectedMinutes = ($expected * 60) % 60;
          $i2 = carryOverAdder_Hours($i, $expectedHours);
          $i2 = carryOverAdder_Minutes($i2, $expectedMinutes);

          //only insert if expectedHours != 0
          if($expected != 0){
            $sql = "INSERT INTO $logTable (time, timeEnd, userID, timeToUTC, status, expectedHours, breakCredit) VALUES('$i', '$i2', ".$row['userID'].", '0', '1', '$expected', 0)";
            $conn->query($sql);
            echo mysqli_error($conn);
          }
          $i = carryOverAdder_Hours($i, 24);
        }

        echo '<div class="alert alert-success fade in">';
        echo '<a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>';
        echo '<strong>Approval: </strong> Created Vacation Timestamps.';
        echo '</div>';
        break;
      } elseif(isset($_POST['nokay'. $row['id']])){
        $answerText = $_POST['answerText'. $row['id']];
        $sql = "UPDATE $userRequests SET status = '1',answerText = '$answerText' WHERE id = " .$row['id'];
        $conn->query($sql);
        break;
      }
    }
  } else {
    echo mysqli_error($conn);
  }
}
?>
<body>
<form method=post>

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
                <input type=text class="form-control" name="answerText<?php echo $row['id']; ?>" placeholder="Reply... (Optional)">

                <span class="input-group-btn">
                  <button type=submit class="btn btn-default" name="okay<?php echo $row['id']; ?>" > <img width=18px height=18px src="../images/okay.png"> </button>
                  <button type=submit class="btn btn-default" name="nokay<?php echo $row['id']; ?>" > <img width=18px height=18px src="../images/not_okay.png"> </button>
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

<!-- /BODY -->
<?php include 'footer.php'; ?>
