<?php
//this will only be called on the setup for persons with private hosting
require 'connection_config.php';
require "validate.php"; denyToCloud();

$conn = new mysqli($servername, $username, $password);
if ($conn->connect_error) {
  echo "<br>Connection Error: Could not Connect.<a href='setup_getInput.php'>Click here to return to previous page.</a><br>";
  die();
}

// Create database
$sql = "CREATE DATABASE IF NOT EXISTS $dbName";
if ($conn->query($sql)) {
  echo "Database was created. <br>";
} else {
    echo "<br>Invalid Database name: Could not instantiate a database.<a href='setup_getInput.php'>Return</a><br>";
  die();
}
$conn->close();

require 'connection.php';

if(isset($_GET)){
  $psw = $_GET['psw'];
  $companyName = test_input(rawurldecode($_GET['companyName']));
  $companyType = test_input(rawurldecode($_GET['companyType']));
  $firstname = test_input(rawurldecode($_GET['first']));
  $lastname = test_input(rawurldecode($_GET['last']));
  $loginname = test_input(rawurldecode($_GET['login']));
}

function test_input($data) {
  $data = trim($data);
  $data = stripslashes($data);
  $data = htmlspecialchars($data);
  return $data;
}

echo "<br><br><br> Your Login E-Mail: $loginname <br><br><br>";

require "setup_inc.php"; //creates all tables

//define
$holidayFile = '../Feiertage.txt';
$travellingFile = fopen("../Laender.txt", "r");
require_once "version_number.php";
require "setup_ins.php"; //makes all necessary inserts

//------------------------------------------------------------------------------

$repositoryPath = dirname(dirname(realpath("setup.php")));

//git init
$command = 'git -C ' .$repositoryPath. ' init 2>&1';
exec($command, $output, $returnValue);

//sslyverify false
$command = 'git -C ' .$repositoryPath. ' config http.sslVerify "false" 2>&1';
exec($command, $output, $returnValue);

//remote add
$command = "git -C $repositoryPath remote add -t master origin https://github.com/eitea/T-Time.git 2>&1";
exec($command, $output, $returnValue);

$command = "git -C $repositoryPath fetch --force 2>&1";
exec($command, $output, $returnValue);

$command = "git -C $repositoryPath reset --hard origin/master 2>&1";
exec($command, $output, $returnValue);

echo implode('<br>', $output);

//------------------------------------------------------------------------------
die ('<br><br> Setup Finished. Click Next after writing down your Login E-Mail: <a href="login.php">Next</a>');
