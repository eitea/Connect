<?php include 'header.php'; ?>
<?php include 'validate.php'; ?>
<!-- BODY -->

<div class="page-header">
<h3><?php echo $lang['CLIENT']; ?></h3>
</div>

  <?php

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


<script>
$(document).ready(function() {
  $('#clientTable').DataTable({
    "order": [[ 1, "asc" ]]
  });
} );
</script>


<!-- /BODY -->
<?php include 'footer.php'; ?>
