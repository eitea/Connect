<?php include 'header.php'; ?>
<?php include 'validate.php'; ?>
<!-- BODY -->

<div class="page-header">
<h3>Navigation</h3>
</div>

<form method='post'>
<?php

if(isset($_GET['customerID'])){
  $customerID = $_GET['customerID'];
} else {
  $customerID = 0;
}

if(isset($_POST['add']) && !empty($_POST['name']) && $customerID != 0){
  $name = test_input($_POST['name']);
  $hours = (empty($_POST['hours']))?0:$_POST['hours'];
  $price = (empty($_POST['price']))?0:$_POST['price'];

  $sql="INSERT INTO $projectTable (clientID, name, hours, hourlyPrice) VALUES($customerID, '$name', '$hours', '$price')";
  $conn->query($sql);

}

if (isset($_POST['delete']) && isset($_POST['index'])) {
  $index = $_POST["index"];
  foreach ($index as $x) {
    $sql = "DELETE FROM $projectTable WHERE id = $x;";
    if (!$conn->query($sql)) {
      echo mysqli_error($conn);
    }
  }
}

//?companyID=$customerID vlavla..
echo "<h1>"."<a href=editCustomers.php><img src='../images/return.png' alt='return' style='width:35px;height:35px;border:0;margin-bottom:5px'></a>" . $lang['PROJECT'] ."</h1><br><br>";
?>

<table>
  <tr>
    <th>Select</th>
    <th>Name</th>
    <th><?php echo $lang['HOURS']?></th>
    <th><?php echo $lang['HOURLY_RATE']?></th>
  </tr>

  <?php
  $customerQuery = ($customerID==0)?'':"WHERE clientID = $customerID";
  $sql = "SELECT * FROM $projectTable $customerQuery";
  // $sql = "SELECT $projectTable.name AS projectName, $projectTable.id AS projectID, $projectTable.hours, $projectTable.hourlyPrice, $clientTable.name  FROM $projectTable INNER JOIN $clientTable ON $projectTable.clientID = $clientTable.id $customerQuery";
  $result = $conn->query($sql);

  echo mysqli_error($conn);
  while($row = $result->fetch_assoc()){
    echo '<tr>';
    echo '<td><input type=checkbox name=index[] value='. $row['id'].'> </td>';
    echo '<td>'. $row['name'] .'</td>';
    echo '<td>'. $row['hours'] .'</td>';
    echo '<td>'. $row['hourlyPrice'] .'</td>';
    echo '</tr>';
  }
  ?>

</table>

<br><br>
<div class='blockInput'>
  <input type=submit name='delete' value='Delete'>
  <input type=submit name='add' value='+'>
  <input type=text name='name' placeholder='projectname'>
  <input type=number name='hours' placeholder='hours' step="any">
  <input type=number name='price' placeholder='price' step="any">
</div>
</form>

<!-- /BODY -->
<?php include 'footer.php'; ?>
