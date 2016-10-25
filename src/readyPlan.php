<!DOCTYPE html>
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">

  <link rel="stylesheet" href="../css/homeMenu.css">
  <link rel="stylesheet" href="../css/table.css">
</head>
<body>
<form method=post>

  <?php
  session_start();
  if (!isset($_SESSION['userid'])) {
    die('Please <a href="login.php">login</a> first.');
  }
  if ($_SESSION['userid'] != 1) {
    die('Access denied. <a href="logout.php"> return</a>');
  }
  require 'connection.php';
  require 'createTimestamps.php';
  ?>

  <table>
    <tr>
    <th>Name</th>
    <th>Checkin</th>
    </tr>
    <?php
    $today = substr(getCurrentTimestamp(),0,10);
    $sql = "SELECT * FROM $logTable INNER JOIN $userTable ON $userTable.id = $logTable.userID WHERE time LIKE '$today %' AND timeEND = '0000-00-00 00:00:00'";
    $result = $conn->query($sql);
    if($result && $result->num_rows > 0){
      while($row = $result->fetch_assoc()){
        echo '<tr><td>' . $row['firstname'] .' '. $row['lastname'] .'</td>';
        echo '<td>'. substr($row['time'], 11, 5) . '</td></tr>';
      }
    } else {
      echo mysqli_error($conn);
    }
    ?>
  </table>

</form>
</body>
