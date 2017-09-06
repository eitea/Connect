<?php
/*
Update: This file is no longer maintained. Substitution of variables into hardcoded strings
*/
$adminLDAPTable = "ldapConfigTab";
$adminGitHubTable = "gitHubConfigTab";
$auditLogsTable = "auditLogs";

$bookingTable = "bookingData"; //contains weekly schedule for each user

$configTable = "configurationData";
$clientTable = "clientData"; //customers
$clientDetailTable = "clientInfoData";
$clientDetailNotesTable = "clientInfoNotes";
$clientDetailBankTable = "clientInfoBank";
$companyTable = "companyData";
$companyExtraFieldsTable = "additionalFields";
$companyToUserRelationshipTable = "relationship_company_client"; //which user works for which companies (N:N)
$companyDefaultProjectTable = "companyDefaultProjects";
$correctionTable = "correctionData";

//deactivated users disappear from the program like deleted ones, but these keep their data.
$deactivatedUserTable = "DeactivatedUsers";
$deactivatedUserDataTable = "DeactivatedUserData"; //intervals 
$deactivatedUserLogs = "DeactivatedUserLogData";
$deactivatedUserProjects = "DeactivatedUserProjectData";
$deactivatedUserTravels = "DeactivatedUserTravelData";

$feedbackTable = "feedbacks";
$holidayTable = "holidays";
$logTable = "logs"; //all loggings of user activity (checkin, vacation, sick..)

$mailLogsTable = "mailLogs";
$mailOptionsTable = "mailingOptions";
$mailReportsTable = 'mailReports';
$mailReportsRecipientsTable = 'mailRecipients';
$moduleTable = "modules"; //module enable/disable options set by systematic admin

$negative_logTable = "unlogs"; //deleted

$pdfTemplateTable = "templateData";
$projectTable = "projectData"; //each customer can have projects
$projectBookingTable = "projectBookingData"; //bookings made for each project
$piConnTable = "piConnectionData"; //contains connection data for the raspberry pi terminal
$policyTable = "policyData";

$userTable = "UserData"; //users
$userRequests = "userRequestsData"; //user requests for vacation
$roleTable = "roles"; //all roles (admin/canBook)

$taskTable ='taskData';
$teamTable = 'teamData'; //users can be divided into teams
$teamRelationshipTable = 'teamRelationshipData'; //team to users is a n:m relation
$travelTable = "travelBookings";
$travelCountryTable = "travelCountryData"; //for calculation of travel expenses

$intervalTable = "intervalData"; //holy grail of grails

$vacationTable = "vacationData"; //deleted
