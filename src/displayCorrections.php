<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <link href="../bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="../plugins/homeMenu/homeMenu.css" rel="stylesheet">

  <script src="../plugins/jQuery/jquery-3.1.0.min.js"></script>
  <script src="../bootstrap/js/bootstrap.min.js"></script>
</head>

<?php
include 'connection.php';
include 'language.php';

if(isset($_GET['userID'])){
  $curID = test_input($_GET['userID']);
} else {
  $curID = $userID;
}

function test_input($data) {
  $data = trim($data);
  $data = stripslashes($data);
  $data = htmlspecialchars($data);
  return $data;
}
?>

<div class="row">
  <div class="col-xs-6">
    <h3><?php echo $lang['ADJUSTMENTS']; ?></h3>
  </div>
  <div class="col-xs-6 text-right">
    <a href="tableSummary.php?userID=<?php echo $curID; ?>" class="btn btn-warning"> Return </a>
  </div>
</div>
<hr>
<br><br>
<table class="table table-hover">
  <thead>
    <th>Info</th>
    <th><?php echo $lang['CORRECTION'].' '. $lang['HOURS']; ?></th>
  </thead>
  <tbody>
    <?php
    $result = $conn->query("SELECT * FROM $correctionTable WHERE $correctionTable.userID = $curID");
    echo mysqli_error($conn);
    while($result && ($row = $result->fetch_assoc())){
      $hours = $row['hours'] * $row['addOrSub'];
      echo '<tr>';
      echo '<td>'.$row['infoText'].'</td>';
      echo '<td>'.sprintf("%+.2f",$hours).'</td>';
      echo '</tr>';
    }
    ?>
  </tbody>
</table>

<?php include 'footer.php'; ?>
