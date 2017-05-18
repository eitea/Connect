<?php include 'header.php'; ?>
<?php enableToCore($userID); ?>
<!-- BODY -->

<div class="page-header">
  <h3>Register</h3>
</div>

<div style=text-align:center>
  <br><br>
  <?php redirect("register_basic.php" ); ?>
  <a href="register_basic.php"><?php echo $lang['REGISTER_FROM_FORM']; ?></a><br>
</div>

<!-- /BODY -->
<?php include 'footer.php'; ?>
