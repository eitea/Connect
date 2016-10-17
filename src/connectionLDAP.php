<?php
require "connection.php";
$query = "SELECT * FROM $adminLDAPTable WHERE adminID = 1";
$result = mysqli_query($conn, $query);
if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $ldapConnect = $row['ldapConnect'];
    $ldap_password = $row['ldapPassword'];
    $ldap_username = $row['ldapUsername'];
} else {
  $ldapConnect = "";
  $ldap_password = "";
  $ldap_username = "";
}
