<!DOCTYPE html>
<?php
if(isset($_POST['GERMAN']) || isset($_POST['ENGLISH']))
{
header("location:userHome.php?link=userSummary.php"); // your current page
}
?>

<html>
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">

  <link rel="stylesheet" href="../bootstrap/css/bootstrap.min.css">
  <link rel="stylesheet" href="../css/font-awesome/css/font-awesome.min.css">
  <link rel="stylesheet" href="../css/homeMenu.css">
  <link rel="stylesheet" type="text/css" href="../css/submitFlags.css">
  <link rel="stylesheet" type="text/css" href="../css/stampingButt.css">

  <script src="../plugins/jQuery/jquery-3.1.0.min.js"></script>
  <script src="../bootstrap/js/bootstrap.min.js"></script>

  <style>
  iframe {
    width:100%;
    min-height:800px;
  }
  .popover{
      width: 400px; /* Max Width of the popover (depending on the container!) */
      font-size:11px;
  }
  </style>
  <script>
    function resizeIframe(obj) {
      obj.style.height = obj.contentWindow.document.body.scrollHeight + 'px';
    }
  </script>

<?php
session_start();
if (!isset($_SESSION['userid'])) {
  die('Please <a href="login.php">login</a> first');
}
$URL = "userSummary.php";
if($_SERVER["REQUEST_METHOD"] == "GET"){
  if(isset($_GET['link'])){
    $URL = $_GET['link'];
  }
}
$userID = $_SESSION['userid'];
require "connection.php";
require "language.php";
require 'createTimestamps.php';
require 'ckInOut.php'; //needs createTimestamps redecleared beforehand

?>
<title>Home</title>

</head>

<body class="hold-transition sidebar-mini">
  <div class="wrapper">
    <header class="main-header">
      <!-- Logo -->
      <a href="userHome.php?link=userSummary.php" class="logo">
        <!-- mini logo for sidebar mini 50x50 pixels -->
        <span class="logo-mini"><b>H</b></span>
        <!-- logo for regular state and mobile devices -->
        <span class="logo-lg"><b>Home</b></span>
      </a>

      <!-- Header Navbar -->
      <nav class="navbar navbar-static-top" role="navigation">
        <!-- Sidebar toggle button-->
        <a href="userHome.php?link=userSummary.php" class="sidebar-toggle" data-toggle="offcanvas" role="button">
          <span class="sr-only">Toggle navigation</span>
        </a>

        <div class="navbar-custom-menu">
          <ul class="nav navbar-nav">
            <li>
              <a href="#" data-trigger="focus" title='Information' data-placement="left" data-toggle="popover" data-content="<a href='http://www.eitea.at'>EI-TEA Partner GmbH</a> <br> The Licensor does not warrant that commencing upon the date of delivery or installation, that when operated in accordance with the documentation or other instructions provided by the Licensor, the Software will perform substantially in accordance with the functional specifications set forth in the documentation.">
              <i class="fa fa-info"></i></a>
            </li>
            <li>
              <a href="#" data-toggle="control-sidebar" title="Options"><i class="fa fa-gears"></i></a>
            </li>
            <li>
              <a href="logout.php" title="Logout"><i class="fa fa-sign-out"></i></a>
            </li>
          </ul>
        </div>

      </nav>
    </header>

    <script>
    $(document).ready(function(){
        $('[data-toggle="popover"]').popover({html:true});
    });
    </script>

  <aside class="main-sidebar">
    <section class="sidebar">
<br><br>
        <!-- input form (Optional) -->
      <form action="userHome.php?link=userSummary.php"  method="post" class="sidebar-form">
        <div class="input-group">
        <span>
            <?php
            $activityA = $activityB = "";
            $showProjectBookingLink = 0;

            if ($_SERVER["REQUEST_METHOD"] == "POST") {
              if (isset($_POST['stampIn'])) {
                $activityA = checkIn($userID);
              } elseif (isset($_POST['stampOut'])) {
                $activityB = checkOut($userID);
              }
            }

            $cd = 0;
            $query = "SELECT * FROM $userTable, $configTable WHERE $userTable.id = $userID;";
            $result = mysqli_query($conn, $query);
            if ($result && $result->num_rows > 0) {
              $row = $result->fetch_assoc();
              $cd = $row['cooldownTimer'];
              $enableProjecting = $row['enableProjecting'];
            }

            $query = "SELECT * FROM $logTable WHERE timeEnd = '0000-00-00 00:00:00' AND userID = $userID AND status = '0' ";
            $result = mysqli_query($conn, $query);
            if ($result && $result->num_rows > 0) {
              //both are UTC
              $row = $result->fetch_assoc();
              $now = getCurrentTimestamp();
              if(timeDiff_Hours($row['time'],$now)  > $cd/60){
                $disabled = '';
              } else {
                $disabled = 'disabled';
              }
              echo '<input '.$disabled.' type="submit" class="button" name="stampOut" value="'.$lang['CHECK_OUT'].'"/>';
              $showProjectBookingLink = TRUE;
            } else {
              echo '<input type="submit" class="button" name="stampIn" value= "'.$lang['CHECK_IN'].'"/>';
            }
            ?>

          </span>
    </div>
  </form> <br>

    <!-- Sidebar Menu -->
    <ul class="sidebar-menu">
      <br>

<?php if($enableProjecting == "TRUE" && $showProjectBookingLink): ?>
    <li><a href="userHome.php?link=userProjecting.php"><i class="fa fa-bookmark"></i>
        <span><?php echo $lang['BOOK_PROJECTS']; ?></span></a></li>
<?php endif; ?>

<li><a href="userHome.php?link=timeCalcTable.php"><i class="fa fa-clock-o"></i>
  <span><?php echo $lang['VIEW_TIMESTAMPS']; ?></span></a></li>

<li><a href="userHome.php?link=calendar.php"><i class="fa fa-calendar"></i>
  <span>Calendar</span></a></li>

<li><a href="userHome.php?link=makeRequest.php"><i class="fa fa-calendar-plus-o"></i>
  <span><?php echo $lang['VACATION']?></span></a></li>

    </ul>
  </section>
</aside>

<div class="content-wrapper">
    <section class="content-header">
      <!-- header -->
    </section>

    <!-- Main content -->
    <section class="content">
      <?php
      if ($_SERVER["REQUEST_METHOD"] == "POST") {
        if (!empty($_POST['password']) && !empty($_POST['passwordConfirm'])) {
          $password = $_POST['password'];
          $passwordConfirm = $_POST['passwordConfirm'];
          if (strcmp($password, $passwordConfirm) == 0) {
            $psw = password_hash($password, PASSWORD_BCRYPT);
            $sql = "UPDATE $userTable SET psw = '$psw' WHERE id = '$userID';";
            $conn->query($sql);
            echo '<div class="alert alert-success fade in">';
            echo '<a href="userHome.php?link=userSummary.php" class="close" data-dismiss="alert" aria-label="close">&times;</a>';
            echo '<strong>Success! </strong>Password successfully changed.';
            echo '</div>';
          } else {
            echo '<div class="alert alert-danger fade in">';
            echo '<a href="userHome.php?link=userSummary.php" class="close" data-dismiss="alert" aria-label="close">&times;</a>';
            echo '<strong>Failed! </strong>Passwords did not match.';
            echo '</div>';
          }
        }

        if (isset($_POST['submitPIN'])) {
          if(is_numeric($_POST['pinCode']) && !empty($_POST['pinCode'])){

            $sql = "UPDATE $userTable SET terminalPin = '".$_POST['pinCode']."' WHERE id = '$userID';";
            $conn->query($sql);
            echo '<div class="alert alert-success fade in">';
            echo '<a href="userHome.php?link=userSummary.php" class="close" data-dismiss="alert" aria-label="close">&times;</a>';
            echo '<strong>Success! </strong>Your Pincode was changed.';
            echo '</div>';
          } else {
            echo '<div class="alert alert-danger fade in">';
            echo '<a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>';
            echo '<strong>Failed: </strong>Invalid PIN.';
            echo '</div>';
          }
        }

        if(isset($_POST['stampIn']) || isset($_POST['leaveIn'])){
          echo '<div class="alert alert-info fade in">';
          echo '<a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>';
          echo "<strong>Stamping recognized! </strong> Refresh in $cd minutes.";
          echo '</div>';
        }
      }
      ?>

      <!-- Page Content Here -->
      <iframe frameborder="0" scrolling="no" onload="resizeIframe(this)" src= <?php echo $URL?>></iframe>


    </section>
  </div>

<footer>
</footer>

  <?php
  if(isset($_POST["GERMAN"])){
    $sql="UPDATE $userTable SET preferredLang='GER' WHERE id = 1";
    $conn->query($sql);
    $_SESSION['language'] = 'GER';
  } elseif(isset($_POST['ENGLISH'])){
    $sql="UPDATE $userTable SET preferredLang='ENG' WHERE id = 1";
    $conn->query($sql);
    $_SESSION['language'] = 'ENG';
  }
  echo mysqli_error($conn);
  ?>

<!-- Control Sidebar -->
<aside class="control-sidebar control-sidebar-light">
  <!-- Create the tabs -->
  <ul class="nav nav-tabs nav-justified control-sidebar-tabs">
    <li class="active"><a href="#control-sidebar-home-tab" data-toggle="tab"><i class="fa fa-language"></i></a></li>
    <li><a href="#control-sidebar-settings-tab" data-toggle="tab"><i class="fa fa-gear"></i></a></li>
  </ul>
  <!-- Tab panes -->
  <div class="tab-content">
    <!-- Home tab content -->
    <div class="tab-pane active" id="control-sidebar-home-tab">
      <h3 class="control-sidebar-heading">Language</h3>
      <ul class="control-sidebar-menu">
        <li>
          <form action="userHome.php?link=userSummary.php" method="post" style="text-align:center" >
            <button type="submit" name="GERMAN"><img width="30px" height="20px" src="../images/ger.png"></button>
            <button type="submit" name="ENGLISH"><img width="30px" height="20px" src="../images/eng.png"></button>
          </form>
        </li>
      </ul>

    </div>
    <!-- /.tab-pane -->
    <!-- Stats tab content -->
    <div class="tab-pane" id="control-sidebar-stats-tab">Stats Tab Content</div>
    <!-- /.tab-pane -->
    <!-- Settings tab content -->
    <div class="tab-pane" id="control-sidebar-settings-tab">
      <form action="userHome.php?link=userSummary.php" method="post">
        <h3 class="control-sidebar-heading">Settings</h3>

        <div class="form-group">
          <label class="control-sidebar-subheading">
            <form action="userHome.php?link=userSummary.php" method="post">
              <?php echo $lang['NEW_PASSWORD']?>: <br>
              <input type="password" name="password" /> <br><br>

              <?php echo $lang['NEW_PASSWORD_CONFIRM']?>: <br>
              <input type="password" name="passwordConfirm" /><br><br>
              <input type="submit" name="submitPassword" value="Change">
            </form>
        </div>

        <div class="form-group">
          <label class="control-sidebar-subheading">
            <form action="userHome.php?link=userSummary.php" method="post">
              New PIN: <br>
              <input type="number" name="pinCode" /> <br><br>
              <input type="submit" name="submitPIN" value="Change">
            </form>
        </div>
        <!-- /.form-group -->
      </form>
    </div>
    <!-- /.tab-pane -->
  </div>
</aside>
<div class="control-sidebar-bg"></div>

</div>
<script src="../plugins/fastclick/fastclick.js"></script>
<script src="../js/app.min.js"></script>
<script src="../plugins/sparkline/jquery.sparkline.min.js"></script>
<script src="../plugins/jvectormap/jquery-jvectormap-1.2.2.min.js"></script>
<script src="../plugins/jvectormap/jquery-jvectormap-world-mill-en.js"></script>
<script src="../plugins/slimScroll/jquery.slimscroll.min.js"></script>
<script src="../plugins/chartjs/Chart.min.js"></script>
</body>
</html>
