<!DOCTYPE html>
<head>
  <link rel="stylesheet" href="../css/homeMenu.css">
  <link rel="stylesheet" type="text/css" href="../css/table.css">
  <link rel="stylesheet" type="text/css" href="../css/submitButt.css">
  <link rel="stylesheet" type="text/css" href="../css/spanBlockInput.css">
  <link rel="stylesheet" type="text/css" href="../css/submitFlags.css">

</head>
<?php
session_start();
if (!isset($_SESSION['userid'])) {
  die('Please <a href="login.php">login</a> first.');
}
if ($_SESSION['userid'] != 1) {
  die('Access denied. <a href="logout.php"> return</a>');
}
require "connection.php";
require "createTimestamps.php";
require "language.php";


if($_SERVER['REQUEST_METHOD'] == 'POST'){
  $sql = "SELECT $userRequests.*, $bookingTable.* FROM $userRequests INNER JOIN $userTable ON $userTable.id = $userRequests.userID INNER JOIN $bookingTable ON $bookingTable.userID = $userTable.id WHERE status = '0'";
  $result = $conn->query($sql);
  if($result->num_rows > 0){
    while($row = $result->fetch_assoc()){
      if(isset($_POST['okay'. $row['id']])){
        $answerText = $_POST['answerText'. $row['id']];
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
          if($expected != 0.0){
            $sql = "INSERT INTO $logTable (time, timeEnd, userID, timeToUTC, status, expectedHours) VALUES('$i', '$i2', ".$row['userID'].", '0', '2', '$expectedHours')";
            $conn->query($sql);
            echo mysqli_error($conn);
          }
          $i = carryOverAdder_Hours($i, 24);
        }

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

  <table>
    <th>Name</th>
    <th><?php echo $lang['TIME']?></th>
    <th>Reason</th>
    <th><?php echo $lang['REPLY_TEXT']; ?></th>
    <?php
    $sql = "SELECT $userTable.firstname, $userTable.lastname, $userRequests.id, $userRequests.fromDate, $userRequests.toDate, $userRequests.requestText FROM $userRequests INNER JOIN $userTable ON $userTable.id = $userRequests.userID WHERE status = '0'";
    $result = $conn->query($sql);
    if($result->num_rows > 0){
      while($row = $result->fetch_assoc()){
        echo '<tr>';
        echo '<td>'. $row['firstname']. ' ' .$row['lastname'] . '</td>';
        echo '<td>'. substr($row['fromDate'],0,10) . ' - ' . substr($row['toDate'],0,10) . '</td>';
        echo '<td>'. $row['requestText'].'</td>';
        echo '<td><input type=text name="answerText'. $row['id'] .'" placeholder=Reply </td>';
        echo '<td style=text-align:left><button type=submit name=okay'. $row['id'] .' > <img width=20px height=20px src="../images/okay.png"> </button>';
        echo '<button type=submit name=nokay'. $row['id'] . ' > <img width=20px height=20px src="../images/not_okay.png"> </button></td>';
        echo '</tr>';
      }
    }
     ?>
  </table>

</form>
</body>
