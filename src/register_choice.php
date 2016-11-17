<?php include 'header.php'; ?>
<?php include 'validate.php'; enableToCore($userID); ?>
<!-- BODY -->

<div class="page-header">
<h3>Register</h3>
</div>

  <div style=text-align:center>
   <br><br>
  <?php
  require 'connectionLDAP.php';

  if($ldapConnect != ""): ?>
  <a href="register_ldap.php"><?php echo $lang['REGISTER_FROM_ACTIVE_DIR'] . ' [Detail]'; ?></a> <br><br>
  <a href="ldapGet.php"><?php echo $lang['REGISTER_FROM_ACTIVE_DIR']  . ' [Quick]'; ?></a> <br><br>
  <?php else: header("refresh:0;url=register_basic.php" ); endif; ?>

  <a href="register_basic.php"><?php echo $lang['REGISTER_FROM_FORM']; ?></a><br>
</div>
</body>

<!-- /BODY -->
<?php include 'footer.php'; ?>
