<!DOCTYPE html>
<head>

  <link rel="stylesheet" type="text/css" href="../bootstrap/css/bootstrap.min.css">
  <link rel="stylesheet" href="../css/homeMenu.css">
  <link rel="stylesheet" href="../css/submitButt.css">

  <link rel="stylesheet" type="text/css" href="../plugins/datatables/css/dataTables.bootstrap.min.css">
  <script src="../plugins/datatables/js/jquery.js"></script>
  <script src="../plugins/datatables/js/jquery.dataTables.min.js"></script>
  <script src="../plugins/datatables/js/dataTables.bootstrap.min.js"></script>

<style>
.createEntry{
  display:block;
  padding:10px;
  background:#f4f4f4;
  padding-left: 20px;
  border:none;
}
</style>

</head>
<body>

  <?php

  require "connection.php";
  if(isset($_POST['holidayDelete']) && isset($_POST['checkingIndeces'])) {
    $index = $_POST["checkingIndeces"];
    foreach ($index as $x) {
      $sql = "DELETE FROM " . $holidayTable . " WHERE begin='$x';";
      if (!$conn->query($sql)) {
        echo mysqli_error($conn);
      }
    }
  } elseif(isset($_POST['holidayAdd'])){
      if(!empty($_POST['holidayName']) && isset($_POST['holidayStart'])) {
        $holidayName = test_input($_POST['holidayName']);
        $holidayStart = $_POST['holidayStart'];
        $sql = "INSERT INTO $holidayTable (name, begin, end) VALUES('$holidayName', '$holidayStart', '$holidayStart')";
        $conn->query($sql);
        echo mysqli_error($conn);
      }
  }

  function test_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);

    return $data;
  }

   ?>

<form method="post">
  <table id="holidayTable" class="table table-striped table-bordered" cellspacing="0" width="100%">
    <thead>
      <tr>
        <th>Delete</th>
        <th>Occasion</th>
        <th>Date</th>
      </tr>
    </thead>
    <?php
    $query = "SELECT * FROM $holidayTable";
    $result = mysqli_query($conn, $query);
    while ($row = $result->fetch_assoc()) {
      echo "<tr>";
      echo "<td><input type=checkbox name='checkingIndeces[]' value='".$row['begin']."' /></td>";
      echo "<td> ". $row['name']."</td><td> ". substr($row['begin'],0,10)."</td>";
      echo "</tr>";
    }
    ?>

  </table><br>

  <span class="createEntry">
  <input type="submit" name="holidayDelete" value="Delete" style="margin-bottom:5px;"/><br>
  <input type="text" name="holidayName" placeholder="name" /> <input type="date" name="holidayStart" /> <input type="submit" value="+" name="holidayAdd" />
  </span>
<form>
</body>

<script>
$(document).ready(function() {
  $('#holidayTable').DataTable({
    "order": [[ 2, "asc" ]]
  });
});
</script>
