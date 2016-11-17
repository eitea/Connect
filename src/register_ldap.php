<?php include 'header.php'; ?>
<?php include 'validate.php'; enableToCore($userID);?>
<!-- BODY -->

<div class="page-header">
<h3>LDAP Query</h3>
</div>

  <form method="post">
    <?php
    require 'connectionLDAP.php';
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

              echo "<br><a href=register_optionals.php?gn=" . $entries[$x]['givenname'][0] . "&sn=" . $entries[$x]['sn'][0] . "&mail=" . $entries[$x]['mail'][0] . "> $eOut </a><br>";

            }
          }
        }
      }
    }
    ldap_unbind($ldap_connection); // Clean up after ourselves.

    ?>
    <br>
  </form>

  <!-- /BODY -->
  <?php include 'footer.php'; ?>
