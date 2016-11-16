<?php include 'header.php'; ?>
<?php include 'validate.php'; ?>
<!-- BODY -->

<div class="page-header">
  <h3><?php echo $lang['COMPANY']; ?></h3>
</div>

<br>

<?php
if($_SERVER["REQUEST_METHOD"] == "POST"){
  //deleteSelectionX deleteCompanyX indexProjectX indexUserX
  $sql = "SELECT id FROM $userTable;";
  $result = $conn->query($sql);
  while ($row = $result->fetch_assoc()) {
    $x = $row['id'];
    if (isset($_POST['deleteCompany'.$x])) {
      $sql = "DELETE FROM $companyTable WHERE id=$x;";
      $conn->query($sql);
      echo mysqli_error($conn);
    }
    if (isset($_POST['deleteSelection'.$x])) {
      if(isset($_POST['indexProject'.$x])){
        foreach ($_POST['indexProject'.$x] as $i) {
          $sql = "DELETE FROM $companyDefaultProjectTable WHERE id = $i";
          $conn->query($sql);
        }
      }
      if(isset($_POST['indexUser'.$x])){
        foreach ($_POST['indexUser'.$x] as $x) {
          $sql = "DELETE FROM $companyToUserRelationshipTable WHERE userID = $x AND companyID = $companyID";
          $conn->query($sql);
        }
      }
    }

  }


  if (isset($_POST['delete']) && isset($_POST['index'])) {
    $x = $_POST["index"][0];


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
  }
  } elseif(isset($_POST['createUser']) && isset($_POST['index'])){
    $userID = $_POST['selectUser'];
    $companyID = $_POST['index'][0];
    $sql = "INSERT INTO $companyToUserRelationshipTable(companyID, userID) VALUES($companyID, $userID)";
    $conn->query($sql);
  }

?>
<div class="panel-group" id="accordion" role="tablist" aria-multiselectable="true">
  <?php
  $query = "SELECT * FROM $companyTable";
  $result = mysqli_query($conn, $query);
  if ($result && $result->num_rows > 0):
    while ($row = $result->fetch_assoc()):
      $x = $row['id'];
      ?>

      <div class="panel panel-default">
        <div class="panel-heading" role="tab" id="headingMenu<?php echo $x; ?>">
          <h4 class="panel-title">
            <a class="collapsed" role="button" data-toggle="collapse" data-parent="#accordion" href="#collapseMenu<?php echo $x; ?>" aria-expanded="false" aria-controls="collapseTwo">
              <?php echo $row['name']; ?>
            </a>
          </h4>
        </div>
        <div id="collapseMenu<?php echo $x; ?>" class="panel-collapse collapse" role="tabpanel" aria-labelledby="headingMenu<?php echo $x; ?>">
          <div class="panel-body">
            <form method="POST">
              <!-- #########  CONTENT ######## -->

              <p><?php echo $lang['DEFAULT'] . " " . $lang['PROJECT']; ?>: </p>
              <table class="table table-hover table-condensed">
                <thead>
                  <tr>
                    <th><?php echo $lang['DELETE']; ?></th>
                    <th>Name</th>
                    <th>Status</th>
                  </tr>
                </thead>
                <tbody>
                  <?php
                  $query = "SELECT * FROM $companyDefaultProjectTable WHERE companyID = $x";
                  $projectResult = mysqli_query($conn, $query);
                  if ($projectResult && $projectResult->num_rows > 0) {
                    while ($projectRow = $projectResult->fetch_assoc()) {
                      $i = $projectRow['id'];
                      echo "<tr><td><input type='checkbox' name='indexProject".$x."[]' value='$i'></td>";
                      echo "<td>".$projectRow['name']."</td>";
                      echo "<td>".$projectRow['status']."</td></tr>";
                    }
                  }
                  ?>
                </tbody>
              </table>

              <br><br>
              <p> <?php echo $lang['ASSIGNED'] . " " . $lang['USERS']; ?>: </p>
              <table class="table table-hover table-condensed" >
                  <tr>
                    <th><?php echo $lang['DELETE']; ?></th>
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

              <div class="container-fluid text-right">
              <div class="btn-group" role="group">
                  <div class="btn-group" role="group">
                    <div class="dropup">
                      <button class="btn btn-warning dropdown-toggle" id="dropOptions" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        Option
                        <span class="caret"></span>
                      </button>
                      <ul class="dropdown-menu">
                        <li><a href="editCompanies_projects?cmp=<?php echo $x; ?>">Add Default Project</a></li>
                        <li><a href="editCompanies_users?cmp=<?php echo $x; ?>">Hire Users</a></li>
                        <li role="separator" class="divider"></li>
                        <li><button type="submit" class="btn btn-link" name="deleteSelection<?php echo $x; ?>">Delete Selection</button></li>
                      </ul>
                  </div>
                </div>
                    <button class="btn btn-danger" type='submit' name='deleteCompany<?php echo $x; ?>'><?php echo $lang['DELETE_COMPANY']; ?></button>

              </div>
            </div>


              <!-- #########  /CONTENT ######## -->
            </form>
          </div>
        </div>
      </div>

    <?php endwhile; endif; ?>
  </div>

  <!-- /BODY -->
  <?php include 'footer.php'; ?>
