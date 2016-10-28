<?php
if(isset($_POST['GERMAN']) || isset($_POST['ENGLISH'])) {
  header("location:adminHome.php");
}
?>
<head>
  <title>Home</title>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">

  <link rel="stylesheet" href="../bootstrap/css/bootstrap.min.css">
  <link rel="stylesheet" href="../css/font-awesome/css/font-awesome.min.css">

  <link rel="stylesheet" href="../css/homeMenu.css">
  <link rel="stylesheet" type="text/css" href="../css/submitFlags.css">

  <script src="../plugins/jQuery/jquery-3.1.0.min.js"></script>
  <script src="../bootstrap/js/bootstrap.min.js"></script>
<!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
<!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
<!--[if lt IE 9]>
<script src="https://oss.maxcdn.com/html5shiv/3.7.3/html5shiv.min.js"></script>
<script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
<![endif]-->

<style>
iframe {
  width:100%;
  min-height:450px;
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
</head>

<?php
session_start();
if (!isset($_SESSION['userid'])) {
  die('Please <a href="login.php">login</a> first.');
}
if ($_SESSION['userid'] != 1) {
  die('Access denied. <a href="logout.php"> return</a>');
}
require "connection.php";
require "createTimestamps.php";
require "language.php";

$URL = "adminTodos.php";
if($_SERVER["REQUEST_METHOD"] == "GET"){
  if(isset($_GET['link'])){
    $URL = $_GET['link'];
  }
}
?>
<div class="wrapper">
  <header class="main-header">
    <!-- Logo -->
    <a href="adminHome.php?link=adminTodos.php" class="logo">
      <!-- mini logo for sidebar mini 50x50 pixels -->
      <span class="logo-mini"><b>A</b></span>
      <!-- logo for regular state and mobile devices -->
      <span class="logo-lg"><b>Admin</b></span>
    </a>

    <!-- Header Navbar -->
    <nav class="navbar navbar-static-top" role="navigation">
      <!-- Sidebar toggle button-->
      <a href="#" class="sidebar-toggle" data-toggle="offcanvas" role="button">
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
      <!-- Sidebar Menu -->
      <ul class="sidebar-menu">
        <br>
        <!-- Optionally, you can add icons to the links -->
        <li class="active"><a href="adminHome.php?link=getTimestamps.php"><i class="fa fa-clock-o"></i>
          <span><?php echo $lang['VIEW_TIMESTAMPS']; ?></span></a></li>


          <li><a href="adminHome.php?link=getProjects.php"><i class="fa fa-history"></i>
            <span><?php echo $lang['VIEW_PROJECTS']; ?></span></a></li>


            <li><a href="adminHome.php?link=editCustomers.php"><i class="fa fa-book"></i>
              <span><?php echo $lang['CLIENTS']; ?></span></a></li>

            <!-- Multilevel -->
            <li class="treeview">
              <a href="#"><i class="fa fa-database"></i> <span><?php echo $lang['PROJECT_INFORMATION']; ?></span>
                <span class="pull-right-container">
                  <i class="fa fa-angle-left pull-right"></i>
                </span>
              </a>
              <ul class="treeview-menu">
                <li><a href="adminHome.php?link=dailyReport.php"><?php echo $lang['DAILY_USER_PROJECT']; ?></a></li>
                <li><a href="adminHome.php?link=monthlyReport.php"><?php echo $lang['MONTHLY_REPORT']; ?></a></li>
              </ul>
            </li>

            <li class="treeview">
              <a href="#"><i class="fa fa-users"></i> <span><?php echo $lang['VIEW_USER']; ?></span>
                <span class="pull-right-container">
                  <i class="fa fa-angle-left pull-right"></i>
                </span>
              </a>
              <ul class="treeview-menu">

              <li><a href="adminHome.php?link=editUsers.php"><?php echo $lang['VIEW_USER']; ?></a></li>
              <li><a href="adminHome.php?link=register_choice.php"><?php echo $lang['REGISTER_NEW_USER']; ?></a></li>
              <li><a href="adminHome.php?link=allowVacations.php"><?php echo $lang['VACATION']; ?></a></li>
              <li><a href="adminHome.php?link=readyPlan.php"><?php echo $lang['READY_STATUS']; ?></a></li>
            </ul>
          </li>

          <li><a href="adminHome.php?link=calendar.php"><i class="fa fa-calendar"></i>
            <span>Calendar</span></a></li>

          <li class="treeview">
            <a href="#"><i class="fa fa-gear"></i> <span><?php echo $lang['SETTINGS']; ?></span>
              <span class="pull-right-container">
                <i class="fa fa-angle-left pull-right"></i>
              </span>
            </a>
            <ul class="treeview-menu">
              <li><a href="adminHome.php?link=editCompanies.php"><?php echo $lang['COMPANIES']; ?></a></li>
              <li><a href="adminHome.php?link=configureLDAP.php"><span>LDAP</span></a></li>
              <li><a href="adminHome.php?link=editHolidays.php"><span><?php echo $lang['HOLIDAYS']; ?></span></a></li>
              <li><a href="adminHome.php?link=advancedOptions.php"><span><?php echo $lang['ADVANCED_OPTIONS']; ?></span></a></li>
            </ul>
          </li>

          <li class="treeview">
            <a href="#"><i class="fa fa-wrench"></i> <span><?php echo $lang['FUNCTIONS']; ?></span>
              <span class="pull-right-container">
                <i class="fa fa-angle-left pull-right"></i>
              </span>
            </a>
            <ul class="treeview-menu">
              <li><a href="pullGitRepo.php" target='_blank'><span>Update</span></a></li>
              <li><a href="fixLunchbreak.php" target='_blank'><span><?php echo $lang['LUNCHBREAK_REPAIR']; ?></span></a></li>
              <li><a href="fixVacations.php" target='_blank'><span><?php echo $lang['VACATION_REPAIR']; ?></span></a></li>
              <li><a href="fixAccumulatedVacation.php" target='_blank'><span><?php echo $lang['RECALCULATE_VACATION']; ?></span></a></li>
              <li><a href="fixAbsentLogTable.php" target='_blank'><span>Repair Expected Hours</span></a></li>
            </ul>
          </li>

          </ul>
        </section>
      </aside>

      <div class="content-wrapper">
        <section class="content-header">
          <!-- header -->
        </section>

        <!-- Main content -->
        <section class="content">
          <!-- Your Page Content Here -->
          <?php
          if ($_SERVER["REQUEST_METHOD"] == "POST") {
            if(isset($_POST['password']) && isset($_POST['passwordConfirm'])){
              $password = $_POST['password'];
              $passwordConfirm = $_POST['passwordConfirm'];
              if (strcmp($password, $passwordConfirm) == 0) {
                $psw = password_hash($password, PASSWORD_BCRYPT);
                $sql = "UPDATE $userTable SET psw = '$psw' WHERE id = 1;";
                $conn->query($sql);
                echo '<div class="alert alert-success fade in">';
                echo '<a href="adminHome.php" class="close" data-dismiss="alert" aria-label="close">&times;</a>';
                echo '<strong>Success! </strong>Passwords were changed.';
                echo '</div>';
              } else {
                echo '<div class="alert alert-danger fade in">';
                echo '<a href="adminHome.php" class="close" data-dismiss="alert" aria-label="close">&times;</a>';
                echo '<strong>Failure: </strong>Passwords did not match.';
                echo '</div>';
              }
            }
            if(!empty($_POST['email']) && filter_var(test_input($_POST['email']), FILTER_VALIDATE_EMAIL)){
                $email = test_input($_POST['email']);
                $sql = "UPDATE $userTable SET email = '$email' WHERE id = 1;";
                $conn->query($sql);
            } else {$emailErr = "Invalid E-Mail Address";}
          }

            $sql = "SELECT * FROM  $userTable  WHERE id = 1;";
            $result = mysqli_query($conn, $sql);
            $row = $result->fetch_assoc();
            $firstName = $row['firstname'];
            $lastName = $row['lastname'];
            $email = $row['email'];

          ?>

          <iframe id="myFrame" frameborder="0" scrolling="no" onload="resizeIframe(this)"  src= <?php echo $URL?>></iframe>

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
          <li><a href="#control-sidebar-home-tab" data-toggle="tab"><i class="fa fa-language"></i></a></li>
          <li><a href="#control-sidebar-settings-tab" data-toggle="tab"><i class="fa fa-gears"></i></a></li>
        </ul>
        <!-- Tab panes -->
        <div class="tab-content">
          <!-- Home tab content -->
          <div class="tab-pane active" id="control-sidebar-home-tab">
            <h3 class="control-sidebar-heading">Language</h3>
            <ul class="control-sidebar-menu">
              <li>
                <a href="javascript:void(0)">
                  <form method="post" style="text-align:center">
                    <button type="submit" name="GERMAN"><img width="30px" height="20px" src="../images/ger.png"></button>
                    <button type="submit" name="ENGLISH"><img width="30px" height="20px" src="../images/eng.png"></button>
                  </form>
                </a>
              </li>
            </ul>

          </div>

          <div class="tab-pane" id="control-sidebar-settings-tab">
            <form method="post">

              <h3 class="control-sidebar-heading"><?php echo $lang['SETTINGS']?>:</h3>

              <div class="form-group">
                <label class="control-sidebar-subheading">
                  E-Mail:
                </label>
                <input type="text" name="email" value=<?php echo $email; ?> />
                <br><br>

                <label class="control-sidebar-subheading">
                <?php echo $lang['NEW_PASSWORD']?>:
                </label>
                <input type="password" name="password"/>
                <br><br>

                <label class="control-sidebar-subheading">
                <?php echo $lang['NEW_PASSWORD_CONFIRM']?>:
                </label>
                <input type="password" name="passwordConfirm"/>
              </div>

              <input type="submit" formtarget="_top" value="Submit">
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
