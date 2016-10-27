<?php
require 'connection_config.php';

$userTable = "UserData";
$logTable = "logs";
$holidayTable = "holidays";
$vacationTable = "vacationData";
$bookingTable = "bookingData";
$projectTable = "projectData";
$clientTable = "clientData";
$companyTable = "companyData";
$projectBookingTable = "projectBookingData";
$companyToUserRelationshipTable = "companyToClientRelationshipData";
$companyDefaultProjectTable = "companyDefaultProjects";
$negative_logTable = "unlogs";
$userRequests = "userRequestsData";

$adminLDAPTable = "ldapConfigTab";
$adminGitHubTable = "gitHubConfigTab";
$configTable = "configurationData";
$piConnTable = "piConnectionData";


$conn = new mysqli($servername, $username, $password, $dbName);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$conn->query("SET NAMES 'utf8';");
$conn->query("SET CHARACTER SET 'utf8';");
