<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">

  <link rel="stylesheet" href="../bootstrap/css/bootstrap.min.css">


</head>

<body>
  <div style=text-align:center>
   <br><br>
  <?php
  require "connection.php";
  session_start();
  if (!isset($_SESSION['userid'])) {
    die('Please <a href="login.php">login</a> first.');
  }
  if ($_SESSION['userid'] != 1) {
    die('Access denied. <a href="logout.php"> return</a>');
  }

  require 'connectionLDAP.php';
  require 'language.php';

  if($ldapConnect != ""): ?>
  <a href="register_ldap.php"><?php echo $lang['REGISTER_FROM_ACTIVE_DIR'] . ' [Detail]'; ?></a> <br><br>
  <a href="adminHome.php?link=ldapGet.php"><?php echo $lang['REGISTER_USERS'] . ' [Quick]'; ?></a> <br><br>
  <?php else: header("refresh:0;url=register_basic.php" ); endif; ?>

  <a href="register_basic.php"><?php echo $lang['REGISTER_FROM_FORM']; ?></a><br>



</div>
</body>
