<?php
require "../connection.php";
$q = intval($_GET['q']);
$sql="SELECT * FROM $bookingTable WHERE userID = $q";
$result = mysqli_query($conn,$sql);

if($result && $result->num_rows >0){
  $row = mysqli_fetch_array($result);
  echo "<table class='table table-striped table-bordered' >";
  echo '<tr>';
  echo '<th>Mon</th>';
  echo '<th>Tue</th>';
  echo '<th>Wed</th>';
  echo '<th>Thu</th>';
  echo '<th>Fri</th>';
  echo '<th>Sat</th>';
  echo '<th>Sun</th>';
  echo '</tr>';
  echo '<tr>';
  echo '<td>' .$row['mon']. '</td>';
  echo '<td>' .$row['tue']. '</td>';
  echo '<td>' .$row['wed']. '</td>';
  echo '<td>' .$row['thu']. '</td>';
  echo '<td>' .$row['fri']. '</td>';
  echo '<td>' .$row['sat']. '</td>';
  echo '<td>' .$row['sun']. '</td>';
  echo '</tr>';
  echo '</table>';
}
?>
