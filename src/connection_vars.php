<?php

$adminLDAPTable = "ldapConfigTab";
$adminGitHubTable = "gitHubConfigTab";

$bookingTable = "bookingData"; //contains weekly schedule for each user

$configTable = "configurationData";
$companyToUserRelationshipTable = "companyToClientRelationshipData"; //which user works for which companies (N:N)
$companyDefaultProjectTable = "companyDefaultProjects";
$clientTable = "clientData"; //customers
$clientDetailTable = "clientInfoData";
$clientDetailNotesTable = "clientInfoNotes";
$clientDetailBankTable = "clientInfoBank";
$companyTable = "companyData";

//deactivated users disappear from the program like deleted ones, but keeps their data.
$deactivatedUserTable = "DeactivatedUsers";
$deactivatedUserDataTable = "DeactivatedUserData"; //bookingtable, vacationtable
$deactivatedUserLogs = "DeactivatedUserLogData";
$deactivatedUserUnLogs = "DeactivatedUserUnLogData";
$deactivatedUserProjects = "DeactivatedUserProjectData";
$deactivatedUserTravels = "DeactivatedUserTravelData";

$feedbackTable = "feedbacks";
$holidayTable = "holidays";
$logTable = "logs"; //all loggings of user activity (checkin, vacation, sick..)
$moduleTable = "modules"; //module enable/disable options set by systematic admin
$negative_logTable = "unlogs"; //absent table

$pdfTemplateTable = "templateData";
$projectTable = "projectData"; //each customer can have projects
$projectBookingTable = "projectBookingData"; //bookings made for each project
$piConnTable = "piConnectionData"; //contains connection data for the raspberry pi terminal
$policyTable = "policyData";

$userTable = "UserData"; //users
$userRequests = "userRequestsData"; //user requests for vacation
$roleTable = "roles"; //all roles (admin/canBook)

$travelTable = "travelBookings";
$travelCountryTable = "travelCountryData"; //for calculation of travel expenses

$vacationTable = "vacationData"; //each users info on his vacation data
