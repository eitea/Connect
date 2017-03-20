<?php include 'header.php'; ?>
<!-- BODY -->

<div class="page-header">
<h3>Team</h3>
</div>

<?php
if(isset($_POST['createTeam']) && !empty($_POST['createTeam_name'])){
  $name = test_input($_POST['createTeam_name']);
  $conn->query("INSERT INTO $teamTable (name) VALUES('$name')");
  $teamID = mysqli_insert_id($conn);
  foreach($_POST['createTeam_members'] AS $user){
    $conn->query("INSERT INTO $teamRelationshipTable(teamID, userID) VALUES($teamID, $user)");
  }
} elseif(isset($_POST['removeTeam'])){
  $teamID = intval($_POST['removeTeam']);
  $conn->query("DELETE FROM $teamTable WHERE id = $teamID");
} elseif(isset($_POST['removeMember'])){
  $arr = explode(' ', $_POST['removeMember']);
  $teamID = intval($arr[0]);
  $user = intval($arr[1]);
  $conn->query("DELETE FROM $teamRelationshipTable WHERE userID = $user AND teamID = $teamID");
}
echo mysqli_error($conn);
?>

<div class="container-fluid">
  <form method="post">
    <div class="text-right">
      <button type="button" data-toggle="modal" data-target=".bookingModal-newTeam"  class="btn btn-warning">+ New Team</button>
      <br><br>
    </div>
    <?php
    $result = $conn->query("SELECT * FROM $teamTable");
    while($result && ($row = $result->fetch_assoc())):
      $teamID = $row['id'];
    ?>
    <div class="panel panel-default">
      <div class="panel-heading container-fluid">
        <div class="col-xs-6"><a data-toggle="collapse" href="#teamCollapse-<?php echo $teamID; ?>"><?php echo $row['name']; ?></a></div>
        <div class="col-xs-6 text-right"><button type="submit" style="background:none;border:none;color:#d90000;" name="removeTeam" value="<?php echo $teamID; ?>"><i class="fa fa-trash-o"></i></button></div>
      </div>
      <div class="collapse" id="teamCollapse-<?php echo $teamID; ?>">
        <div class="panel-body container-fluid">
          <?php
          $userResult = $conn->query("SELECT id, firstname, lastname FROM $userTable JOIN $teamRelationshipTable ON userID = id WHERE teamID = $teamID");
          while($userResult && ($userRow = $userResult->fetch_assoc())){
            echo '<div class="col-md-4"><button type="submit" style="background:none;border:none" name="removeMember" value="'.$teamID.' '.$userRow['id'].'"><img width="10px" height="10px" src="../images/minus_circle.png"></button>';
            echo $userRow['firstname'].' '.$userRow['lastname'] . '</div>';
          }
          echo mysqli_error($conn);
          ?>
        </div>
      </div>
    </div>
  <?php endwhile; ?>
  </form>
</div>


<form method="post">
<div class="modal fade bookingModal-newTeam" tabindex="-1" role="dialog" aria-labelledby="newTeamModal">
  <div class="modal-dialog modal-md" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h4 class="modal-title">New Team</h4>
      </div>
      <div class="modal-body">
        <label>Name</label>
        <input type="text" class="form-control" name="createTeam_name" placeholder="Name" /><br>
        <label>Benutzer</label>
        <div class="container-fluid checkbox">
          <?php
          $result = $conn->query("SELECT id, firstname, lastname FROM $userTable");
          while($result && ($row = $result->fetch_assoc())){
            echo '<div class="col-xs-6"><input type="checkbox" name="createTeam_members[]" value="'.$row['id'].'" />'.$row['firstname'].' '.$row['lastname'].'</div>';
          }
          ?>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-warning" name="createTeam" value="<?php echo $x; ?>"><?php echo $lang['ADD']; ?></button>
      </div>
    </div>
  </div>
</div>
</form>

<!-- /BODY -->
<?php include 'footer.php'; ?>
