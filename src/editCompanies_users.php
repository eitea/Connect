<?php include 'header.php'; ?>
<?php include 'validate.php'; enableToCore($userID);?>
<!-- BODY -->

<div class="page-header">
  <h3><?php echo $lang['USERS']; ?></h3>
</div>

<?php
$companyID = intval($_GET['cmp']);

if(isset($_POST['hire']) && isset($_POST['userIDs'])){
  foreach($_POST['userIDs'] as $x){
    $sql = "INSERT INTO $companyToUserRelationshipTable (companyID, userID) VALUES ($companyID, $x)";
    $conn->query($sql);
  }
}
 ?>
<form method="post">
<table class="table table-hover">
  <thead>
    <th>Select</th>
    <th>Name</th>
  </thead>
  <tbody>
    <?php
    $sql = "SELECT * FROM $userTable WHERE id NOT IN (SELECT DISTINCT userID FROM $companyToUserRelationshipTable WHERE companyID = $companyID)";
    $result = mysqli_query($conn, $sql);
    if ($result && $result->num_rows > 0) {
      while ($row = $result->fetch_assoc()) {
        echo '<tr>';
        echo '<td><input type="checkbox" name="userIDs[]" value="'.$row['id'].'" ></td>';
        echo '<td>'.$row['firstname'].' '. $row['lastname'] .'</td>';
        echo '</tr>';
      }
    }
     ?>
  </tbody>
</table>
<br><br>
<div class="text-right">
<button type="submit" class="btn btn-warning" name="hire">Benutzer einstellen</button>
</div>
</form>
<?php include 'footer.php'; ?>
