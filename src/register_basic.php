<?php include 'header.php'; ?>
<?php enableToCore($userID)?>
<!-- BODY -->

<div class="page-header">
<h3>Register</h3>
</div>

<?php
$accept = TRUE;
$firstname = $lastname = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
  if (empty($_POST["firstname"])) {
    $accept = FALSE;
  } else {
    $firstname = test_input($_POST["firstname"]);
  }

  if (empty($_POST["lastname"])) {
    $accept = FALSE;
  } else {
    $lastname = test_input($_POST['lastname']);
  }

  if ($accept) {
    redirect("register_optionals.php?gn=$firstname&sn=$lastname" );
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
    <div class="row text-right">
      <button type="submit" class="btn btn-warning" name="createUser"><?php echo $lang['CONTINUE']; ?></button>
    </div>
  </div>
</div>
</form>

<!-- /BODY -->
<?php include 'footer.php'; ?>
