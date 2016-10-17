<!DOCTYPE html>
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">

  <link rel="stylesheet" href="../css/homeMenu.css">
  <link rel="stylesheet" type="text/css" href="../css/submitButt.css">
  <link rel="stylesheet" type="text/css" href="../css/spanBlockInput.css">
  <link rel="stylesheet" type="text/css" href="../css/inputTypeText.css">
  <link rel="stylesheet" href="../bootstrap/css/bootstrap.min.css">
  <link rel="stylesheet" type="text/css" href="../css/table.css">

  <link rel="stylesheet" type="text/css" href="../plugins/datatables/css/dataTables.bootstrap.min.css">
  <script src="../plugins/datatables/js/jquery.js"></script>
  <script src="../plugins/datatables/js/jquery.dataTables.min.js"></script>
  <script src="../plugins/datatables/js/dataTables.bootstrap.min.js"></script>


  <style>
  input[type="number"]{
    color:darkblue;
    font-family: monospace;
    border-style: hidden;
    width:55px;
    padding:2px;
    border-radius:5px;
    min-width:90px;
  }
  .columnDiv {
    -webkit-column-count: 3; /* Chrome, Safari, Opera */
    -moz-column-count: 3; /* Firefox */
    column-count: 3;
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

  echo "<h1>". $lang['CLIENT'] ."</h1>";

$filterCompanyID = 0;
  if($_SERVER["REQUEST_METHOD"] == "POST"){
    $filterCompanyID = $_POST['filterCompany'];
    if (isset($_POST['delete']) && isset($_POST['index'])) {
      $index = $_POST["index"];
      foreach ($index as $x) {
        $sql = "DELETE FROM $clientTable WHERE id = $x;";
        if (!$conn->query($sql)) {
          echo mysqli_error($conn);
        }
      }
    } elseif(isset($_POST['create']) && !empty($_POST['name']) && $_POST['filterCompany'] != 0){
      $name = test_input($_POST['name']);
      $companyID = $_POST['filterCompany'];

      $sql = "INSERT INTO $clientTable (name, companyID, clientNumber) VALUES('$name', $companyID, '".$_POST['clientNumber']."')";
      if($conn->query($sql)){
        $id = $conn->insert_id;
        $sql = "INSERT INTO $projectTable (clientID, name, status, hours)
        SELECT '$id', name, status, hours FROM $companyDefaultProjectTable WHERE companyID = $companyID";
        $conn->query($sql);
      }
      echo mysqli_error($conn);
    }
  }


  ?>
  <br>
  <form method="post"><br>
    <select name="filterCompany">
      <option value=0>Select Company..</option>
      <?php
      $filterCompanyQuery = "";
      $query = "SELECT * FROM $companyTable";
      $result = mysqli_query($conn, $query);
      if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
          $i = $row['id'];
          $checked = "";
          if($filterCompanyID == $i){
            $checked = "selected";
            $filterCompanyQuery = "WHERE companyID = ".$i;
          } elseif(isset($_POST['selectCompany']) && $_POST['selectCompany'] == $row['id']){
            $checked = "selected";
          }
          echo "<option $checked value= $i> ".$row['name']."</option>";
        }
      }
      ?>
    </select>

    <input type="submit" value="Filter" /><br>

    <br>
    <table id="clientTable" class="table table-striped table-bordered" cellspacing="0" width="100%">
      <thead>
      <tr>
        <th style="width:10%;"> Delete </th>
        <th>Name </th>
        <th><?php echo $lang['NUMBER']; ?></th>
      </tr>
    </thead>
 <tbody>
      <?php
      $query = "SELECT * FROM $clientTable $filterCompanyQuery ORDER BY name ASC";
      $result = mysqli_query($conn, $query);
      if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
          $i = $row['id'];
          $checked = "";
          if(isset($_POST['index']) && $_POST['index'][0] == $i){
            $checked = "checked";
          }
          echo '<tr>';
          echo "<td><input type='checkbox' $checked name='index[]' value= $i></td>";
          echo "<td><a href='editProjects.php?customerID=$i'>" .$row['name'] ."</a></td>";
          echo "<td>" .$row['clientNumber']."</td>";
          echo '</tr>';
        }
      }
      ?>
 </tbody>
    </table>

    <?php if(isset($_POST['filterCompany']) && $_POST['filterCompany'] != 0):  ?>

    <br><br>
    <div class="blockInput">
      Options:
      <input type="submit" class="button" name="delete" value="Delete">
    <br><br>
      Create:
      <input type="text" name="name" placeholder="name" onkeydown="if (event.keyCode == 13) return false;" />
      <input type="text" name="clientNumber" placeholder="#" style="width:100px;" />
      <input type="submit" class="button" name="create" value="+" />
    </div>
    
  <?php endif; ?>

  </form>
</body>

<script>
$(document).ready(function() {
  $('#clientTable').DataTable({
    "order": [[ 1, "asc" ]]
  });
} );
</script>
