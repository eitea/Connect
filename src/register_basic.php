<?php include 'header.php'; ?>
<?php include 'validate.php'; enableToCore($userID)?>
<!-- BODY -->

<div class="page-header">
<h3>Register</h3>
</div>

  <?php
  $accept = TRUE;
  $firstname = $lastname = $email = "";
  $firstnameErr = $lastnameErr = $emailErr = "";

  if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (empty($_POST["firstname"])) {
      $firstnameErr = "*Name is required";
      $accept = FALSE;
    } else {
      $firstname = test_input($_POST["firstname"]);
    }

    if (empty($_POST["lastname"])) {
      $lastnameErr = "*Last name is required";
      $accept = FALSE;
    } else {
      $lastname = test_input($_POST['lastname']);
    }

    if (!empty($_POST["email"]) && filter_var(test_input($_POST["email"]), FILTER_VALIDATE_EMAIL)) {
      $email = test_input($_POST["email"]);
      $result = $conn->query("SELECT * FROM $userTable WHERE email = '$email'");
      if($result && $result->num_rows > 0){
        $accept = FALSE;
        $emailErr = "*Email already in Use";
      } else {
        echo mysqli_error($conn);
      }
    } else {
      $emailErr = "*Email invalid";
      $accept = FALSE;
    }

    if ($accept) {
      redirect("register_optionals.php?gn=$firstname&sn=$lastname&mail=$email" );
    }
  }

  ?>

<form method="post">
  <div class=container>
  <div class="col-md-8">
    <div class="row form-group">
      <div class="input-group">
        <span class="input-group-addon" style=min-width:150px><?php echo $lang['FIRSTNAME'] ?></span>
        <input type="text" class="form-control" name="firstname" value="<?php echo $firstname; ?>">
      </div>
    </div>
    <div class="row form-group">
      <div class="input-group">
        <span class="input-group-addon" style=min-width:150px><?php echo $lang['LASTNAME'] ?></span>
        <input type="text" class="form-control" name="lastname" value="<?php echo $lastname; ?>">
      </div>
    </div>
    <div class="row form-group">
      <div class="input-group">
        <span class="input-group-addon" style=min-width:150px>E-Mail</span>
        <input type="email" class="form-control" name="email" value="<?php echo $email; ?>">
      </div>
    </div>
    <div class="row text-right">
      <button type="submit" class="btn btn-warning" name="createUser">Next</button>
    </div>
  </div>
</div>

</form>

<!-- /BODY -->
<?php include 'footer.php'; ?>
