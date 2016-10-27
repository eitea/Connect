<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">

  <link rel="stylesheet" href="../bootstrap/css/bootstrap.min.css">
</head>
<body>
  <form method="post">
    <h1 style=''>LDAP Query</h1><br>
    <?php
    session_start();
    if (!isset($_SESSION['userid'])) {
      die('Please <a href="login.php">login</a> first.');
    }
    if($_SESSION['userid'] != 1){
      die('Access denied. <a href="logout.php"> return</a>');
    }

    require 'connection.php';
    require 'connectionLDAP.php';

    $conn = new mysqli($servername, $username, $password, $dbName);
    $ldap_connection = ldap_connect($ldapConnect);

    // We have to set this option for the version of Active Directory we are using.
    ldap_set_option($ldap_connection, LDAP_OPT_PROTOCOL_VERSION, 3) or die('Unable to set LDAP protocol version');
    ldap_set_option($ldap_connection, LDAP_OPT_REFERRALS, 0); // We need this for doing an LDAP search.

    if (ldap_bind($ldap_connection, $ldap_username, $ldap_password)) {
      $ldap_base_dn = 'DC=eitea,DC=local';
      $search_filter = '(&(objectCategory=person)(samaccountname=*))';

      $result = ldap_search($ldap_connection, $ldap_base_dn, $search_filter);
      if ($result) {
        $entries = ldap_get_entries($ldap_connection, $result);
        for ($x = 0; $x < $entries['count']; $x++) { //foreach ldap entry
          if (!empty($entries[$x]['givenname'][0]) && //is it a person
          !empty($entries[$x]['mail'][0]) &&
          !empty($entries[$x]['samaccountname'][0]) &&
          !empty($entries[$x]['sn'][0]) &&
          'Shop' != $entries[$x]['sn'][0] &&
          'Account' != $entries[$x]['sn'][0]
          ) {
            $mail = $entries[$x]['mail'][0];
            $sql = "SELECT * FROM $userTable WHERE email = '$mail';";
            $result = mysqli_query($conn, $sql);
            if ($result && $result->num_rows <= 0) { //if email adress not already registered
              $sid = "S-"; //sid is still hexadec
              $sidInHex = str_split(bin2hex($entries[$x]['objectsid'][0]), 2);
              $sid .= hexdec($sidInHex[0]) . "-";
              $sid .= hexdec($sidInHex[6] . $sidInHex[5] . $sidInHex[4] . $sidInHex[3] . $sidInHex[2] . $sidInHex[1]);
              $subauths = hexdec($sidInHex[7]);

              for ($i = 0; $i < $subauths; $i++) {
                $start = 8 + (4 * $i);
                $sid .= "-" . hexdec($sidInHex[$start + 3] . $sidInHex[$start + 2] . $sidInHex[$start + 1] . $sidInHex[$start]);
              }

              $eOut = $entries[$x]['givenname'][0] . "\n" . $entries[$x]['sn'][0];

              echo "<br/><input type='checkbox' name=\"index[]\" value=$x />$eOut<br>";
              if ($_SERVER["REQUEST_METHOD"] == "POST") {
                $exes = $_POST["index"];
                foreach ($exes as $e) {
                  if ($e == $x) {
                    $ranPass = randomPassword();
                    $psw = password_hash($ranPass, PASSWORD_BCRYPT);
                    if ($conn->connect_error) {
                      die("Connection failed: " . $conn->connect_error);
                    }
                    $sql = "INSERT INTO $userTable (firstname, lastname, psw, sid, email) VALUES ('"
                    . $entries[$x]['givenname'][0] . "', '" . $entries[$x]['sn'][0] . "', '" . $psw . "', '" . $sid . "', '" . $entries[$x]['mail'][0] . "')";
                    if ($conn->query($sql)) {
                      echo $ranPass;
                    } else {
                      echo '<font color="red">Connection error</font>';
                    }

                    $sql = "SELECT id FROM $userTable WHERE email = '" . $entries[$x]['mail'][0] . "';";
                    $result = mysqli_query($conn, $sql);
                    if ($result && $result->num_rows > 0) {
                      $row = $result->fetch_assoc();
                      $id = $row['id'];
                      $sql = "INSERT INTO $bookingTable(userID) VALUES($id)";
                      $conn->query($sql);

                      $sql = "INSERT INTO $vacationTable(userID) VALUES($id)";
                      $conn->query($sql);
                      echo mysqli_error($conn);

                    }
                  }
                }
              }
            }
          }
        }
      }
    }
    ldap_unbind($ldap_connection); // Clean up after ourselves.

    function randomPassword(){
      $alphabet = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890';
      $pass = array(); //remember to declare $pass as an array
      $alphaLength = strlen($alphabet) - 1; //put the length -1 in cache
      for ($i = 0; $i < 8; $i++) {
        $n = rand(0, $alphaLength);
        $pass[] = $alphabet[$n];
      }
      return implode($pass); //turn the array into a string
    }

    ?>
    <br>
    <input type="submit" value="Submit"><br><br>
  </form>
</body>
</html>
