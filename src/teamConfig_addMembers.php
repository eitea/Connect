<?php include 'header.php'; enableToCore($userID); ?>
<!-- BODY -->

<div class="page-header">
<h3>Team</h3>
</div>

<?php
$teamID = intval($_GET['tm']);
?>

<form method="post" action="teamConfig.php">
<table class="table table-hover">
  <thead>
    <th>Select</th>
    <th>Name</th>
  </thead>
  <tbody>
    <?php
    $sql = "SELECT id, firstname, lastname FROM $userTable WHERE id NOT IN (SELECT DISTINCT userID FROM $teamRelationshipTable WHERE teamID = $teamID)";
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
<a href="teamConfig.php" class="btn btn-info"><i class="fa fa-arrow-left"></i> Return</a>
<br><br>
<div class="text-right">
<button type="submit" class="btn btn-warning" name="hire" value="<?php echo $teamID; ?>">Benutzer einstellen</button>
</div>
</form>
<!-- /BODY -->
<?php include 'footer.php'; ?>
