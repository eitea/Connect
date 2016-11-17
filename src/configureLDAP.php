<?php include 'header.php'; ?>
<?php include 'validate.php'; enableToCore($userID);?>
<!-- BODY -->

<div class="page-header">
<h3>LDAP</h3>
</div>

  <?php
  require 'connectionLDAP.php';
  $query = "SELECT * FROM $configTable;";
  $row = mysqli_query($conn, $query)->fetch_assoc();
  $cd = $row['cooldownTimer'];
  $bufferTime = $row['bookingTimeBuffer'];

  $newConn = $ldapConnect;
  $newUser = $ldap_username;
  $newPass = $ldap_password;

  if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['ldapDomain'])) {
      $newConn = $_POST['ldapDomain'];
    }
    if (isset($_POST['ldap_user'])) {
      $newUser = $_POST['ldap_user'];
    }
    if (isset($_POST['ldap_pass'])) {
      $newPass = $_POST['ldap_pass'];
    }

    $sql = "UPDATE $adminLDAPTable SET ldapConnect = '$newConn', ldapPassword = '$newPass', ldapUsername = '$newUser' WHERE adminID = 1";
    if ($conn->query($sql)) {
      echo '<div class="alert alert-success fade in"> <strong>O.K. - </strong>Change was successful. </div>';
    } else {
      echo mysqli_error($conn);
    }

    if(isset($_POST['cd'])){
      $cd = $_POST['cd'];
      $sql = "UPDATE $configTable SET cooldownTimer = '$cd';";
      $conn->query($sql);
    }
    if(isset($_POST['bufferTime'])){
      $bufferTime = $_POST['bufferTime'];
      $sql = "UPDATE $configTable SET bookingTimeBuffer = '$bufferTime';";
      $conn->query($sql);
    }
  }
  ?>

  <form method="post">

    <div class=container-fluid>
      LDAP Configuration Settings: <br><br>
      <div class=form-group>
        <div class="input-group">
          <span class="input-group-addon" style=min-width:150px>Ldap Domain</span>
          <input type="text" class="form-control" name="ldapDomain" value="<?php echo $newConn; ?>">
        </div>
      </div>
      <div class=form-group>
        <div class="input-group">
          <span class="input-group-addon" style=min-width:150px>Ldap Username</span>
          <input type="text" class="form-control" name="ldap_user" value="<?php echo $newUser; ?>">
        </div>
      </div>
      <div class=form-group>
        <div class="input-group">
          <span class="input-group-addon" style=min-width:150px>Ldap Password</span>
          <input type="password" class="form-control" name="ldap_pass" value="<?php echo $newPass; ?>">
        </div>
      </div>
      <button type="submit" class="btn btn-warning" name="ldapSubmit">Save<br>
    </div>

    <br><br><br>

    <div class=container-fluid>
      <div class=col-md-6>
        Disable-time for In/Out Buttons:
        <input type="number" class="form-control" name="cd" value="<?php echo $cd; ?>">
      </div>
      <div class=col-md-6>
        Project-Time Buffer
        <input type="number" class="form-control" name="bufferTime" value="<?php echo $bufferTime ?>" >
      </div>
    </div>
<br>
    <div class=container-fluid>
      <div class="form-group">
        <button type="submit" class="btn btn-warning" name="ldapSubmit">Save </button>
      </div>
    </div>

  </form>

  <!-- /BODY -->
  <?php include 'footer.php'; ?>
