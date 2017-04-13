<?php include 'header.php'; ?>
<?php enableToCore($userID);?>
<!-- BODY -->

<?php
if(empty($_GET['cmp'])){die("Invalid Access");}
$x = $cmpID = intval($_GET['cmp']);

if($_SERVER["REQUEST_METHOD"] == "POST"){ //a long lost remain of what used to be here... (a lazy developer says never change a running system)
  if(isset($_POST['deleteCompany'.$x]) && $x != 1){
    $sql = "DELETE FROM $companyTable WHERE id=$x;";
    $conn->query($sql);
    echo mysqli_error($conn);
  } elseif(isset($_POST['deleteCompany'.$x]) && $x == 1){
    echo '<div class="alert alert-danger fade in">';
    echo '<a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>';
    echo '<strong>Error: </strong>Cannot delete first Company.';
    echo '</div>';
  }
  if(isset($_POST['deleteSelection'.$x])){
    if(isset($_POST['indexProject'.$x])){
      foreach ($_POST['indexProject'.$x] as $i) {
        $sql = "DELETE FROM $companyDefaultProjectTable WHERE id = $i";
        $conn->query($sql);
      }
    }
    if(isset($_POST['indexUser'.$x])){
      foreach ($_POST['indexUser'.$x] as $i) {
        $sql = "DELETE FROM $companyToUserRelationshipTable WHERE userID = $i AND companyID = $x";
        $conn->query($sql);
      }
    }
  }



}
?>

<?php
$result = $conn->query("SELECT * FROM $companyTable WHERE id = $cmpID");
if ($result && ($row = $result->fetch_assoc()) && in_array($row['id'], $available_companies)):
  $x = $row['id'];
?>
  <div class="page-header">
    <h3><?php echo $lang['COMPANY'] .' - '.$row['name']; ?></h3>
  </div>

  <br>
  <form method="POST">
    <p><?php echo $lang['DEFAULT'] . " " . $lang['PROJECT']; ?>: </p>
    <table class="table table-hover table-condensed">
      <thead>
        <tr>
          <th>Option</th>
          <th>Name</th>
          <th>Status</th>
          <th><?php echo $lang['HOURS']; ?></th>
          <th><?php echo $lang['HOURLY_RATE']; ?></th>
        </tr>
      </thead>
      <tbody>
        <?php
        $query = "SELECT * FROM $companyDefaultProjectTable WHERE companyID = $x";
        $projectResult = mysqli_query($conn, $query);
        if ($projectResult && $projectResult->num_rows > 0) {
          while ($projectRow = $projectResult->fetch_assoc()) {
            $i = $projectRow['id'];

            $projectRowStatus = (!empty($projectRow['status']))? $lang['PRODUCTIVE']:'';
            echo "<tr><td><input type='checkbox' name='indexProject".$x."[]' value='$i'></td>";
            echo "<td>".$projectRow['name']."</td>";
            echo "<td> $projectRowStatus </td>";
            echo "<td>".$projectRow['hours']."</td>";
            echo "<td>".$projectRow['hourlyPrice']."</td></tr>";
          }
        }
        ?>
      </tbody>
    </table>
    <br><br>
    <p> <?php echo $lang['ASSIGNED'] . " " . $lang['USERS']; ?>: </p>
    <table class="table table-hover" >
      <tr>
        <th>Option</th>
        <th>Name</th>
      </tr>
      <tbody>
        <?php
        $query = "SELECT DISTINCT * FROM $userTable
        INNER JOIN $companyToUserRelationshipTable ON $userTable.id = $companyToUserRelationshipTable.userID
        WHERE $companyToUserRelationshipTable.companyID = $x";
        $usersResult = mysqli_query($conn, $query);
        if ($usersResult && $usersResult->num_rows > 0) {
          while ($usersRow = $usersResult->fetch_assoc()) {
            $i = $usersRow['id'];
            echo "<tr><td><input type='checkbox' name='indexUser".$x."[]' value= $i></td>";
            echo "<td>".$usersRow['firstname']." ".$usersRow['lastname']."</td></tr>";
          }
        }
        ?>
      </tbody>
    </table>
    <br><br>
    <p><?php echo $lang['ADDITIONAL_FIELDS']; ?>: </p>
    <table class="table table-hover" >
      <thead>
        <th><?php echo $lang['ACTIVE']; ?></th>
        <th><?php echo $lang['HEADLINE']; ?></th>
        <th><?php echo $lang['REQUIRED_FIELD']; ?></th>
        <th><?php echo $lang['FOR_ALL_PROJECTS']; ?></th>
        <th><?php echo $lang['DESCRIPTION']; ?></th>
      </thead>
      <tbody>
        <?php
        $fieldResult = $conn->query("SELECT * FROM $companyExtraFieldsTable WHERE companyID = $x");
        while ($fieldResult && ($fieldRow = $fieldResult->fetch_assoc())) {
          echo '<tr>';
          echo '<td>'.$fieldRow['isActive'].'</td>';
          echo '<td>'.$fieldRow['name'].'</td>';
          echo '<td>'.$fieldRow['isRequired'].'</td>';
          echo '<td>'.$fieldRow['isForAllProjects'].'</td>';
          echo '<td>'.$fieldRow['description'].'</td>';
          echo '</tr>';
        }
        ?>
      </tbody>
    </table>

    <br><br>
    <div class="container-fluid text-right">
      <div class="btn-group" role="group">
        <div class="dropup">
          <button class="btn btn-warning dropdown-toggle" id="dropOptions" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
            Option
            <span class="caret"></span>
          </button>
          <ul class="dropdown-menu">
            <li><a href="editCompanies_projects.php?cmp=<?php echo $x; ?>">Neues Standardprojekt</a></li>
            <li><a href="editCompanies_users.php?cmp=<?php echo $x; ?>">Benutzer einstellen</a></li>
            <li><a href="editCompanies_fields.php?cmp=<?php echo $x; ?>">Weitere Projektfelder</a></li>
            <li role="separator" class="divider"></li>
            <li><button type="button" class="btn btn-link text-warning" data-toggle="modal" data-target=".bs-example-modal-sm<?php echo $x; ?>"><?php echo $lang['DELETE_COMPANY']; ?></button></li>
          </ul>
        </div>
      </div>
      <button type="submit" class="btn btn-danger" name="deleteSelection<?php echo $x; ?>">Auswahl Löschen</button>
    </div>

    <!-- Small modal -->
    <div class="modal fade bs-example-modal-sm<?php echo $x; ?>" tabindex="-1" role="dialog" aria-labelledby="mySmallModalLabel">
      <div class="modal-dialog modal-sm" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <h4 class="modal-title">Do you really wish to delete <?php echo $row['name']; ?> ?</h4>
          </div>
          <div class="modal-body">
            All Clients, Projects and Bookings belonging to this Company will be lost forever. Do you still wish to proceed?
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-default" data-dismiss="modal">No, I'm sorry.</button>
            <button type="submit" name='deleteCompany<?php echo $x; ?>' class="btn btn-primary">Yes, delete it.</button>
          </div>
        </div>
      </div>
    </div>
  </form>
<?php endif;?>

<!-- /BODY -->
<?php include 'footer.php'; ?>
