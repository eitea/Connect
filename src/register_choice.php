<?php include 'header.php'; ?>
<?php enableToCore($userID); ?>
<!-- BODY -->

<div class="page-header">
<h3>Register</h3>
</div>

  <div style=text-align:center>
   <br><br>
  <?php
  require 'connectionLDAP.php';

  if($ldapConnect != "" && $ldap_username != "" && $ldap_password != ""): ?>
  <a href="register_ldap.php"><?php echo $lang['REGISTER_FROM_ACTIVE_DIR']; ?></a> <br><br>
<?php else: redirect("register_basic.php" ); endif; ?>

  <a href="register_basic.php"><?php echo $lang['REGISTER_FROM_FORM']; ?></a><br>
</div>
</body>

<!-- /BODY -->
<?php include 'footer.php'; ?>
