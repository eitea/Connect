<?php
if(file_exists(dirname(__DIR__) . '/connection_config.php')){
  header("Location: ../login/auth");
}
require_once dirname(__DIR__) . "/createTimestamps.php";
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta http-equiv="Cache-Control" content="max-age=600, must-revalidate">

  <script src="plugins/jQuery/jquery-3.2.1.min.js"></script>

  <link href="plugins/bootstrap/css/bootstrap.min.css" rel="stylesheet"/>
  <script src="plugins/bootstrap/js/bootstrap.min.js"></script>

  <link rel="stylesheet" type="text/css" href="plugins/select2/css/select2.min.css">
  <script src='plugins/select2/js/select2.min.js'></script>

  <link href="plugins/homeMenu/homeMenu.css" rel="stylesheet" />
  <title>Setup Connect</title>
  <script>
  document.onreadystatechange = function() {
    var state = document.readyState
    if(state == 'complete') {
      document.getElementById("loader").style.display = "none";
      document.getElementById("bodyContent").style.display = "block";
    }
  }
  $(document).ready(function() {
    if($(".js-example-basic-single")[0]){
      $(".js-example-basic-single").select2();
    }
  });
  </script>
</head>
<body id="body_container" class="is-table-row">
  <div id="loader"></div>
  <!-- navbar -->
  <nav id="fixed-navbar-header" class="navbar navbar-default navbar-fixed-top">
    <div class="container-fluid">
      <div class="navbar-header hidden-xs">
        <a class="navbar-brand" >Connect</a>
      </div>
      <div class="navbar-right">
        <a class="btn navbar-btn navbar-link" data-toggle="collapse" href="#infoDiv_collapse"><strong>info</strong></a>
      </div>
    </div>
  </nav>
  <div class="collapse" id="infoDiv_collapse">
    <div class="well">
      <a href='http://www.eitea.at'> EI-TEA Partner GmbH </a> - <?php include dirname(__DIR__).'/version_number.php'; echo $VERSION_TEXT; ?><br>
      The Licensor does not warrant that commencing upon the date of delivery or installation, that when operated in accordance with the documentation or other instructions provided by the Licensor,
      the Software will perform substantially in accordance with the functional specifications set forth in the documentation. The software is provided "as is", without warranty of any kind, express or implied.
    </div>
  </div>
  <!-- /navbar -->
  <?php
  function clean($string) {
    return preg_replace('/[^\.A-Za-z0-9\-]/', '', $string);
  }
  function match_passwordpolicy_setup($p, &$out = ''){
    if(strlen($p) < 6){
      $out = "Password must be at least 6 Characters long.";
      return false;
    }
    if(!preg_match('/[A-Z]/', $p) || !preg_match('/[0-9]/', $p)){
      $out = "Password must contain at least one captial letter and one number";
      return false;
    }
    return true;
  }
  function icsToArray($paramUrl) {
    $icsFile = file_get_contents($paramUrl);
    $icsData = explode("BEGIN:", $icsFile);
    foreach ($icsData as $key => $value) {
      $icsDatesMeta[$key] = explode("\n", $value);
    }
    foreach ($icsDatesMeta as $key => $value) {
      foreach ($value as $subKey => $subValue) {
        if ($subValue != "") {
          if ($key != 0 && $subKey == 0) {
            $icsDates[$key]["BEGIN"] = $subValue;
          } else {
            $subValueArr = explode(":", $subValue, 2);
            $icsDates[$key][$subValueArr[0]] = $subValueArr[1];
          }
        }
      }
    }
    return $icsDates;
  }
  ?>
  <div id="bodyContent" style="display:none;" >
    <div class="affix-content">
      <div class="container-fluid">

        <?php
        if(!function_exists('mysqli_init') && !extension_loaded('mysqli')) {
          die('Mysqli not available.');
        }
        $firstname = $lastname = $companyName = $companyType = $localPart = $domainpart = $out = "";

        if($_SERVER['REQUEST_METHOD'] == 'POST'){
          if(!empty($_POST['companyName']) && !empty($_POST['adminPass']) && !empty($_POST['firstname']) && !empty($_POST['type']) && !empty($_POST['localPart']) && !empty($_POST['domainPart'])){
            $psw = $_POST['adminPass'];
            $companyName = test_input($_POST['companyName']);
            $companyType = test_input($_POST['type']);
            $firstname = test_input($_POST['firstname']);
            $lastname = test_input($_POST['lastname']);
            $domainname = clean($_POST['domainPart']);
            $loginname = clean($_POST['localPart']) .'@'.$domainname;

            if(match_passwordpolicy_setup(test_input($_POST['adminPass']), $out)){
              $psw = password_hash($_POST['adminPass'], PASSWORD_BCRYPT);
              //create connection file
              $myfile = fopen(dirname(__DIR__) .'/connection_config.php', 'w');
              $txt = '<?php
              $servername = "'.test_input($_POST['serverName']).'";
              $username = "'.test_input($_POST['mysqlUsername']).'";
              $password = "'.test_input($_POST['pass']).'";
              $dbName = "'.test_input($_POST['dbName']).'";';
              fwrite($myfile, $txt);
              fclose($myfile);
              if(!file_exists(dirname(__DIR__) .'/connection_config.php')){
                echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>Fatal Error: Please grant PHP permission to create files first. Click Next to proceed. <a href="/setup/run">Next</a></div>';
              }
              require dirname(__DIR__) .'/connection_config.php';
              //establish connection
              if(!($conn = new mysqli($servername, $username, $password))){
                echo $conn->connect_error;
                unlink(dirname(__DIR__) .'/connection_config.php');
                die("<br>Connection Error: Could not Connect.<br>");
              }

              if($conn->query("CREATE DATABASE IF NOT EXISTS $dbName")){
                echo "Database was created. <br>";
              } else {
                echo mysqli_error($conn);
                unlink(dirname(__DIR__) .'/connection_config.php');
                die("<br>Invalid Database name: Could not instantiate a database.<a href='run'>Return</a><br>");
              }

      //reconnect to database
      $conn->close();
      $conn = new mysqli($servername, $username, $password, $dbName);

      $conn->query("SET NAMES 'utf8';");
      $conn->query("SET CHARACTER SET 'utf8';");

      echo "<br><br><br> Your Login E-Mail: $loginname <br><br><br>";

      //create all tables
      set_time_limit(0); //fix for slow computers
      require __DIR__ . "/setup_inc.php";
      create_tables($conn);

      require_once dirname(__DIR__) . "/version_number.php";
      //------------------------------ INSERTS ---------------------------------------

      //insert identification
      $identifier = str_replace('.', '0', randomPassword().uniqid('', true).randomPassword().uniqid('').randomPassword()); //60 characters;
      $conn->query("INSERT INTO identification (id) VALUES ('$identifier')");

      //insert main company
      $sql = "INSERT INTO companyData (name, companyType) VALUES ('$companyName', '$companyType')";
      $conn->query($sql);
      //insert password policy
      $conn->query("INSERT INTO policyData (passwordLength) VALUES (6)");
      //insert module en/disable
      $conn->query("INSERT INTO modules (enableTime, enableProject, enableSocialMedia, enableDynamicProjects) VALUES('TRUE', 'TRUE', 'TRUE', 'FALSE')");

      //insert ADMIN
      $sql = "INSERT INTO UserData (firstname, lastname, email, psw) VALUES ('', 'Admin', 'Admin@$domainname', '$2y$10$98/h.UxzMiwux5OSlprx0.Cp/2/83nGi905JoK/0ud1VUWisgUIzK');";
      $conn->query($sql);
      //interval
      $sql = "INSERT INTO intervalData (userID) VALUES (1);";
      $conn->query($sql);
      //role
      $sql = "INSERT INTO roles (userID, isCoreAdmin, canStamp, canBook, canUseSocialMedia, isDynamicProjectsAdmin) VALUES(1, 'TRUE', 'TRUE', 'TRUE', 'TRUE', 'TRUE');";
      $conn->query($sql);
      //insert company-client relationship
      $sql = "INSERT INTO relationship_company_client(companyID, userID) VALUES(1,1)";
      $conn->query($sql);
      //socialprofile
      $sql = "INSERT INTO socialprofile (userID, isAvailable, status) VALUES(1, 'TRUE', '-');";
      $conn->query($sql);

      //insert core user
      $sql = "INSERT INTO UserData (firstname, lastname, email, psw) VALUES ('$firstname', '$lastname', '$loginname', '$psw');";
      $conn->query($sql);
      //insert intervaltable
      $sql = "INSERT INTO intervalData (userID) VALUES (2);";
      $conn->query($sql);
      //insert roletable
      $sql = "INSERT INTO roles (userID, isCoreAdmin, isTimeAdmin, isProjectAdmin, isReportAdmin, isERPAdmin, canStamp, canBook, canUseSocialMedia, isDynamicProjectsAdmin) VALUES(2, 'TRUE', 'TRUE', 'TRUE','TRUE', 'TRUE', 'TRUE','TRUE', 'TRUE', 'TRUE');";
      $conn->query($sql);
      //insert company-client relationship
      $sql = "INSERT INTO relationship_company_client(companyID, userID) VALUES(1,2)";
      $conn->query($sql);
      //socialprofile
      $sql = "INSERT INTO socialprofile (userID, isAvailable, status) VALUES(2, 'TRUE', '-');";
      $conn->query($sql);

      //insert configs
      $sql = "INSERT INTO configurationData (bookingTimeBuffer, cooldownTimer, masterPassword) VALUES (5, 2,'')";
      $conn->query($sql);
      //insert ldap config
      $sql = "INSERT INTO ldapConfigTab (adminID, version) VALUES (1, $VERSION_NUMBER)";
      $conn->query($sql);
      //insert ERP numbers
      $conn->query("INSERT INTO erpNumbers (erp_ang, erp_aub, erp_re, erp_lfs, erp_gut, erp_stn, companyID) VALUES (1, 1, 1, 1, 1, 1, 1)");
      //insert mail options
      $conn->query("INSERT INTO mailingOptions (host, port) VALUES('localhost', '80')");
      //insert restic backup configuration
      $conn->query("INSERT INTO resticconfiguration () VALUES ()");

      //insert holidays
      $holidayFile = __DIR__ . '/Feiertage.txt';
      $holidayFile = icsToArray($holidayFile);
      for($i = 1; $i < count($holidayFile); $i++){
        if(trim($holidayFile[$i]['BEGIN']) == 'VEVENT'){
          $start = substr($holidayFile[$i]['DTSTART;VALUE=DATE'], 0, 4) ."-" . substr($holidayFile[$i]['DTSTART;VALUE=DATE'], 4, 2) . "-" . substr($holidayFile[$i]['DTSTART;VALUE=DATE'], 6, 2) . " 00:00:00";
          $end = substr($holidayFile[$i]['DTEND;VALUE=DATE'], 0, 4) ."-" . substr($holidayFile[$i]['DTEND;VALUE=DATE'], 4, 2) . "-" . substr($holidayFile[$i]['DTEND;VALUE=DATE'], 6, 2) . " 20:00:00";
          $n = $holidayFile[$i]['SUMMARY'];
          $conn->query("INSERT INTO holidays(begin, end, name) VALUES ('$start', '$end', '$n');");
        }
      }
      echo mysqli_error($conn);

              //insert github options
              $sql = "INSERT INTO gitHubConfigTab (sslVerify) VALUES('FALSE')";
              if (!$conn->query($sql)) {
                echo mysqli_error($conn);
              }

              //insert travelling expenses
              $travellingFile = fopen(__DIR__ . "/Laender.txt", "r");
              if ($travellingFile) {
                while (($line = fgets($travellingFile)) !== false) {
                  $line = iconv('UTF-8', 'windows-1252', $line);
                  $thisLineIsNotOK = true;
                  while($thisLineIsNotOK){
                    $data = preg_split('/\s+/', $line);
                    array_pop($data);
                    if(count($data) == 4){
                      $short = test_input($data[0]);
                      $name = test_input($data[1]);
                      $dayPay = floatval($data[2]);
                      $nightPay = floatval($data[3]);
                      $conn->query("INSERT INTO travelCountryData(identifier, countryName, dayPay, nightPay) VALUES('$short', '$name', '$dayPay' , '$nightPay')");
                      $thisLineIsNotOK = false;
                    } elseif(count($data) > 4) {
                      $line = substr_replace($line, '_', strlen($data[0].' '.$data[1]), 1);
                    } else {
                      echo 'Ups! Something went wrong with that file. <br>';
                      print_r ($data);
                      die();
                    }
                  }
                }
                fclose($travellingFile);
              } else {
                echo "File with Country Data not found!";
              }
              echo mysqli_error($conn);

              //insert main report
              $exampleTemplate = "<h1>Main Report</h1> \n [TIMESTAMPS] \n <br> [BOOKINGS] ";
              $conn->query("INSERT INTO templateData(name, htmlCode, repeatCount) VALUES('Example_Report', '$exampleTemplate', 'TRUE')");

              //insert taxRates
              $conn->query("INSERT INTO taxRates(description, percentage) VALUES('Normalsatz', 20)");
              $conn->query("INSERT INTO taxRates(description, percentage) VALUES('Ermäßigter Satz', 10)");
              $conn->query("INSERT INTO taxRates(description, percentage) VALUES('Innergemeinschaftlicher Erwerb Normalsatz', 20)");
              $conn->query("INSERT INTO taxRates(description, percentage) VALUES('Innergemeinschaftlicher Erwerb Ermäßigter Satz', 10)");
              $conn->query("INSERT INTO taxRates(description, percentage) VALUES('Innergemeinschaftlicher Erwerb steuerfrei', NULL)");
              $conn->query("INSERT INTO taxRates(description, percentage) VALUES('Reverse Charge Normalsatz', 20)");
              $conn->query("INSERT INTO taxRates(description, percentage) VALUES('Reverse Charge Ermäßigter Satz', 10)");
              $conn->query("INSERT INTO taxRates(description, percentage) VALUES('Bewirtung', 20)");
              $conn->query("INSERT INTO taxRates(description, percentage) VALUES('Bewirtung', 10)");
              $conn->query("INSERT INTO taxRates(description, percentage) VALUES('Innergemeinschaftliche Leistungen', NULL)");
              $conn->query("INSERT INTO taxRates(description, percentage) VALUES('Innergemeinschatliche Lieferungen steuerfrei', NULL)");
              $conn->query("INSERT INTO taxRates(description, percentage) VALUES('Ermäßigter Satz', 13)");
              $conn->query("INSERT INTO taxRates(description, percentage) VALUES('Sonder Ermäßigter Satz', 12)");
              $conn->query("INSERT INTO taxRates(description, percentage) VALUES('Zollausschulssgebiet', NULL)");
              $conn->query("INSERT INTO taxRates(description, percentage) VALUES('Zusatzsteuer LuF', 10)");
              $conn->query("INSERT INTO taxRates(description, percentage) VALUES('Zusatzsteuer LuF', 8)");
              $conn->query("INSERT INTO taxRates(description, percentage) VALUES('KFZ Normalsatz', 20)");
              $conn->query("INSERT INTO taxRates(description, percentage) VALUES('UStBBKV', 20)");
              $conn->query("INSERT INTO taxRates(description, percentage) VALUES('Keine Steuer', NULL)");
              $conn->query("INSERT INTO taxRates(description, percentage) VALUES('Steuerfrei', 0)");

              //insert sum units
              $conn->query("INSERT INTO units (name, unit) VALUES('Stück', 'Stk')");
              $conn->query("INSERT INTO units (name, unit) VALUES('Packungen', 'Pkg')");
              $conn->query("INSERT INTO units (name, unit) VALUES('Stunden', 'h')");
              $conn->query("INSERT INTO units (name, unit) VALUES('Gramm', 'g')");
              $conn->query("INSERT INTO units (name, unit) VALUES('Kilogramm', 'kg')");
              $conn->query("INSERT INTO units (name, unit) VALUES('Meter', 'm')");
              $conn->query("INSERT INTO units (name, unit) VALUES('Kilometer', 'km')");
              $conn->query("INSERT INTO units (name, unit) VALUES('Quadratmeter', 'm2')");
              $conn->query("INSERT INTO units (name, unit) VALUES('Kubikmeter', 'm3')");

              //insert payment method
              $sql = "INSERT INTO paymentMethods (name) VALUES ('Überweisung')";
              $conn->query($sql);
              //insert shippign method
              $sql = "INSERT INTO shippingMethods (name) VALUES ('Abholer')";
              $conn->query($sql);


              //-------------------------------- GIT -----------------------------------------

              $repositoryPath = dirname(dirname(realpath("setup.php")));

              //git init
              $command = 'git -C ' .$repositoryPath. ' init 2>&1';
              exec($command, $output, $returnValue);

              //sslyverify false
              $command = 'git -C ' .$repositoryPath. ' config http.sslVerify "false" 2>&1';
              exec($command, $output, $returnValue);

              //remote add
              $command = "git -C $repositoryPath remote add -t master origin https://github.com/eitea/Connect.git 2>&1";
              exec($command, $output, $returnValue);

              $command = "git -C $repositoryPath fetch --force 2>&1";
              exec($command, $output, $returnValue);

              $command = "git -C $repositoryPath reset --hard origin/master 2>&1";
              exec($command, $output, $returnValue);

              //------------------------------------------------------------------------------
              die('<br><br> Setup Finished. Click Next after writing down your Login E-Mail: <a href="../login/auth">Next</a>');

            } else {
              echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$out.'</div>';
            }
          } else {
            echo 'Missing Fields. <br><br>';
          }
        }
        ?>

        <form method='post'>
          <h1>Login Data</h1><br><br>
          <div class="row">
            <div class="col-sm-8 col-lg-4">
              <div class="form-group">
                <div class="input-group">
                  <span class="input-group-addon" style="min-width:150px">Firstname</span>
                  <input type="text" class="form-control" name="firstname" placeholder="Firstname.." value="<?php echo $firstname; ?>" />
                </div>
              </div>
            </div>
            <div class="col-sm-8 col-lg-4">
              <div class="form-group">
                <div class="input-group">
                  <span class="input-group-addon" style="min-width:150px">Lastname</span>
                  <input type="text" class="form-control" name="lastname" placeholder="Lastname.." value="<?php echo $lastname ?>" />
                </div>
              </div>
            </div>
          </div>

          <div class="row">
            <div class="col-sm-8">
              <div class="form-group">
                <div class="input-group">
                  <span class="input-group-addon text-warning" style=min-width:150px>Login Password</span>
                  <input type='password' class="form-control" name='adminPass' value="" placeholder="****" />
                </div>
              </div>
            </div>
          </div>
          <div class="row">
            <div class="col-sm-6">
              <div class="form-group">
                <div class="input-group">
                  <span class="input-group-addon text-warning" style="min-width:150px">Company Name</span>
                  <input type='text' class="form-control" name='companyName' placeholder='Company Name' value="<?php echo $companyName ?>" />
                </div>
              </div>
            </div>
            <div class="col-sm-2">
              <div class="form-group">
                <select name="type" class="js-example-basic-single btn-block">
                  <option selected>...</option>
                  <option <?php if($companyType == "GmbH") echo "selected"; ?> value="GmbH">GmbH</option>
                  <option <?php if($companyType == "AG") echo "selected"; ?> value="AG">AG</option>
                  <option <?php if($companyType == "OG") echo "selected"; ?> value="OG">OG</option>
                  <option <?php if($companyType == "KG") echo "selected"; ?> value="KG">KG</option>
                  <option <?php if($companyType == "EU") echo "selected"; ?> value="EU">EU</option>
                  <option <?php if($companyType == "-") echo "selected"; ?> value="-">Sonstiges</option>
                </select>
              </div>
            </div>
          </div>
          <br><br>
          <div class="row">
            <div class="col-sm-8">
              <label>Your Login E-Mail</label>
              <div class="form-group">
                <div class="input-group">
                  <input type='text' class="form-control" name='localPart' placeholder='name' value="<?php echo $localPart ?>" />
                  <span class="input-group-addon text-warning"> @ </span>
                  <input type='text' class="form-control" name='domainPart' placeholder="domain.com" value="<?php echo $domainPart ?>" />
                </div>
              </div>
              <small> * The Domain will be used for every login adress that will be created. Cannot be changed afterwards.<br><b> May not contain any special characters! </b></small>
            </div>
          </div>
          <br><hr><br>

          <?php if(!getenv('IS_CONTAINER') && !isset($_SERVER['IS_CONTAINER'])): ?>
            <h1>MySQL Database Connection</h1><br><br>

            <div class="row">
              <div class="col-sm-8">
                <div class="form-group">
                  <div class="input-group">
                    <span class="input-group-addon" style="min-width:150px">
                      Server Address
                    </span>
                    <input type="text" class="form-control" name="serverName" value = "localhost" />
                  </div>
                </div>
              </div>
            </div>
            <div class="row">
              <div class="col-sm-8">
                <div class="form-group">
                  <div class="input-group">
                    <span class="input-group-addon" style="min-width:150px">
                      Username
                    </span>
                    <input type="text" class="form-control" name='mysqlUsername' value = 'root' />
                  </div>
                </div>
              </div>
            </div>
            <div class="row">
              <div class="col-sm-8">
                <div class="form-group">
                  <div class="input-group">
                    <span class="input-group-addon" style="min-width:150px">
                      Password
                    </span>
                    <input type="text" class="form-control" name='pass' value = '' />
                  </div>
                </div>
              </div>
            </div>
            <div class="row">
              <div class="col-sm-8">
                <div class="form-group">
                  <div class="input-group">
                    <span class="input-group-addon" style="min-width:150px">
                      DB Name
                    </span>
                    <input type="text" class="form-control" name='dbName' value = 'Zeit1' />
                  </div>
                </div>
              </div>
            </div>
            <br><hr><br>
          <?php else: ?>
            <input type="hidden" name='serverName' value = "<?php echo getenv('MYSQL_SERVICE', true); ?>">
            <input type="hidden" name='mysqlUsername' value = 'connect' />
            <input type="hidden" name='pass' value = 'Uforonudi499' />
            <input type="hidden" name='dbName' value = 'connect' />
          <?php endif; ?>

          <div class="container-fluid text-right">
            <button id="continueButton" type='submit' name'submitInput' class="btn btn-warning">Continue</button>
          </div>
        </form>

      </div>
    </div>
  </div>
</body>
</html>
