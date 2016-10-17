<!DOCTYPE html>
<head>
  <link rel="stylesheet" type="text/css" href="../css/table.css">
  <link rel="stylesheet" type="text/css" href="../css/submitButt.css">
  <link rel="stylesheet" href="../css/homeMenu.css">

<style>
.createEntry{
  display:block;
  padding:10px;
  background:#E8E8F1;
  padding-left: 20px;
}
input[type="text"]{
  color:black;
  font-family: monospace;
  border-style: hidden;
  border: none;
  display:inline-block;
  border-radius:6px;
  padding:5px 10px;
}
</style>
</head>
<body>
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
?>

<h1><?php echo $lang['COMPANY']; ?></h1>
<br>

<?php
if($_SERVER["REQUEST_METHOD"] == "POST"){
  if (isset($_POST['delete']) && isset($_POST['index'])) {
    $x = $_POST["index"][0];
    $sql = "DELETE FROM $companyTable WHERE id=$x;";
    if (!$conn->query($sql)) {
      echo mysqli_error($conn);
    }

  } elseif(isset($_POST['create']) && !empty($_POST['name'])){
    $name = test_input($_POST['name']);
    $sql = "INSERT INTO $companyTable (name) VALUES('$name')";
    $conn->query($sql);
  } elseif(isset($_POST['createProject']) && isset($_POST['index'])){
    $name = test_input($_POST['nameProject']);
    $status = test_input($_POST['statusProject']);
    $hours = test_input($_POST['hoursProject']);
    $companyID = $_POST['index'][0];
    if(isset($_POST['hourlyPriceProject']) && is_numeric($_POST['hourlyPriceProject'])){
      $price = $_POST['hourlyPriceProject'];
    } else {
      $price = 0.0;
    }
    if(empty($hours)){$hours = 0;}

    $sql = "INSERT INTO $companyDefaultProjectTable (name, companyID, hours, status, hourlyPrice) VALUES('$name', $companyID, $hours, '$status', $price)";
    if($conn->query($sql)){ //add default project to all clients with the company ID;
      $sql = "INSERT INTO $projectTable (clientID, name, status, hours, hourlyPrice) SELECT id,'$name', '$status', '$hours', '$price' FROM $clientTable WHERE companyID = $companyID";
      $conn->query($sql);
      echo mysqli_error($conn);
    }
  } elseif(isset($_POST['deleteProject']) && isset($_POST['indexProject'])){
    $index = $_POST["indexProject"];
    foreach ($index as $x) {
      $sql = "DELETE FROM $companyDefaultProjectTable WHERE id = $x";
      $conn->query($sql);
    }
  } elseif(isset($_POST['createUser']) && isset($_POST['index'])){
    $userID = $_POST['selectUser'];
    $companyID = $_POST['index'][0];
    $sql = "INSERT INTO $companyToUserRelationshipTable(companyID, userID) VALUES($companyID, $userID)";
    $conn->query($sql);
  } elseif(isset($_POST['deleteUser']) && isset($_POST['indexUser']) && isset($_POST['index'])){
    $companyID = $_POST['index'][0];
    $index = $_POST["indexUser"];
    foreach ($index as $x) {
      $sql = "DELETE FROM $companyToUserRelationshipTable WHERE userID = $x AND companyID = $companyID";
      $conn->query($sql);
    }
  }
}
?>

<form method="post">
  <table>
    <tr>
      <th style="width:10%;"> <?php echo $lang['DELETE']; ?> </th>
      <th> Name </th>
    </tr>

<?php
$query = "SELECT * FROM $companyTable";
$result = mysqli_query($conn, $query);
if ($result && $result->num_rows > 0) {
  while ($row = $result->fetch_assoc()) {
    $i = $row['id'];
    $postDataChecked = "";
    if(isset($_POST['index']) && $_POST['index'][0] == $i){
      $postDataChecked = "checked";
    }
    echo "<tr><td><input type='radio' name='index[]' $postDataChecked value=$i></td>";
    echo "<td>".$row['name']."</td></tr>";
  }
}
?>

</table><br><br>

<div class="createEntry">
  <input type="submit" class="button" name="delete" value="<?php echo $lang['DELETE']; ?>">
  <input type="submit" class="button" name="showProjects" value="<?php echo $lang['DISPLAY_INFORMATION']; ?>">

  Create:
  <input type="text" name="name" placeholder="name" onkeydown='if (event.keyCode == 13) return false;'>
  <input type="submit" class="button" name="create" value="+"/><br><br>

</div><br><br>

<?php if(isset($_POST['index'])): ?>

<p><?php echo $lang['DEFAULT'] . " " . $lang['PROJECT']; ?>: </p>

<br><br>
  <table>
    <tr>
      <th><?php echo $lang['DELETE']; ?></th>
      <th>Name</th>
      <th>Status</th>
    </tr>

  <?php
  if(isset($_POST['index'])){
    $query = "SELECT * FROM $companyDefaultProjectTable WHERE companyID =" .$_POST['index'][0];
    $result = mysqli_query($conn, $query);
    if ($result && $result->num_rows > 0) {
      while ($row = $result->fetch_assoc()) {
        $i = $row['id'];
        echo "<tr><td><input type='checkbox' name='indexProject[]' value= ".$i."></td>";
        echo "<td>".$row['name']."</td>";
        echo "<td>".$row['status']."</td></tr>";
      }
    }
  }
  ?>
  </table><br><br>

<div class="createEntry">
  Options:
    <input type="submit" class="button" name="deleteProject" value="<?php echo $lang['DELETE']; ?>">
<br><br>
    Create:
    <input type="text" name="nameProject" placeholder="name" onkeydown='if (event.keyCode == 13) return false;'>
    <input type="text" name="statusProject" placeholder = "status" onkeydown='if (event.keyCode == 13) return false;' style="width:100px;">
    <input type="text" name="hoursProject" placeholder = "hours" onkeydown='if (event.keyCode == 13) return false;' style="width:40px;">
    <input type="text" name="hourlyPriceProject" placeholder = "€" style="width:40px;">
    <input type="submit" class="button" name="createProject" value="+"/>
</div>
<br><br>

<p> <?php echo $lang['ASSIGNED'] . " " . $lang['VIEW_USER']; ?>: </p>

<br><br>
  <table>
    <tr>
      <th><?php echo $lang['DELETE']; ?> (id)</th>
      <th>Name</th>
    </tr>

  <?php
  if(isset($_POST['index'])){
    $query = "SELECT DISTINCT * FROM $userTable INNER JOIN $companyToUserRelationshipTable ON $userTable.id = $companyToUserRelationshipTable.userID WHERE $companyToUserRelationshipTable.companyID = " .$_POST['index'][0];
    $result = mysqli_query($conn, $query);
    if ($result && $result->num_rows > 0) {
      while ($row = $result->fetch_assoc()) {
        $i = $row['id'];
        echo "<tr><td><input type='checkbox' name='indexUser[]' value= $i> ($i)</td>";
        echo "<td>".$row['firstname']." ".$row['lastname']."</td></tr>";
      }
    }
  }
  ?>
  </table><br><br>

  <div class="createEntry">
    Options:
    <input type="submit" class="button" name="deleteUser" value="Fire">
<br><br>
    Hire:
    <select name="selectUser">
      <?php
      $sql = "SELECT * FROM $userTable";
      $result = mysqli_query($conn, $sql);
      if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
          $id = $row['id'];
          $name = $row['firstname'] . " " . $row['lastname'];
          echo "<option name='opt' value=$id>$id - $name</option>";
        }
      }
      ?>
    </select>
    <input type="submit" class="button" name="createUser" value="+"/>
  </div><br>

<?php endif; ?>

</form>
</body>
