<?php
session_start();
$userID = $_SESSION['userid'] or die("no user signed in");
require dirname(__DIR__) . DIRECTORY_SEPARATOR . "connection.php";
require dirname(__DIR__) . DIRECTORY_SEPARATOR . "language.php";

/*

This page is accessible when clicking 'search' in the navbar or using the following keybard combinations: F1, CTRL SHIFT SPACE, CTRL SHIFT F

*/

$result = $conn->query("SELECT isCoreAdmin FROM $roleTable WHERE userID = $userID AND isCoreAdmin = 'TRUE'");
$enableToAdvancedSearch = !($userID != 1 && (!$result || $result->num_rows <= 0));

if (isset($_REQUEST["modal"])) {
    $german = $english = "";
    if (isset($_SESSION['language']) && $_SESSION['language'] == 'ENG') {
        $english = "checked";
    } elseif (!isset($_SESSION['language']) || $_SESSION['language'] == 'GER') {
        $german = "checked";
    }
    ?>
        <script src="plugins/jsCookie/src/js.cookie.js"></script>
        <div class="modal fade">
            <div class="modal-dialog modal-content modal-md">
                <div class="modal-header">
                    <form id="searchForm" autocomplete="off">
                        <div class="input-group">
                            <input autofocus type="text" name="search" value="" class="form-control" id="searchQuery">
                            <span class="input-group-btn">
                                <button class="btn btn-primary" type="submit"><i class="fa fa-search"></i></button>
                                <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><?php echo $lang['SETTINGS']; ?> <span class="caret"></span></button>
                                <div class="dropdown-menu">
                                    <div class="row">
                                        <div class="col-md-12"><label><input type="checkbox" id="germanSearchCheckbox" <?php echo $german; ?> > <?php echo $lang['GERMAN'] ?></label></div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-12"><label><input type="checkbox" id="englishSearchCheckbox" <?php echo $english; ?> > <?php echo $lang['ENGLISH'] ?></label></div>
                                    </div>
                                    <?php if ($enableToAdvancedSearch): ?>
                                    <div role="separator" class="divider"></div>
                                    <div class="row">
                                        <div class="col-md-12"><label><input type="checkbox" id="advancedSearchCheckbox" > <?php echo $lang['ADVANCED_SEARCH']; ?></label></div>
                                    </div>
                                    <?php endif;?>
                                </div>
                            </span>
                        </div>
                    </form>
                </div>
                <div class="modal-body">
                    <div id="searchResult"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo $lang['CLOSE']; ?></button>
                </div>
            </div>
        </div>
        <script>
            fetchSearchResults = _.debounce (function () {
                Cookies.set("search-german",$("#germanSearchCheckbox").prop("checked"), { expires: 30 /*days*/ });
                Cookies.set("search-english",$("#englishSearchCheckbox").prop("checked"), { expires: 30 /*days*/ });
                Cookies.set("search-advanced",$("#advancedSearchCheckbox").prop("checked"), { expires: 30 /*days*/ });
                
                $("#searchResult").html("<div style='width:100%;height:200px;'><div class='searchLoader'></div></div>");
                $.ajax({
                    url: 'ajaxQuery/AJAX_getSearch.php',
                    data: {
                        query: $("#searchQuery").val(),
                        german: $("#germanSearchCheckbox").prop("checked"),
                        english: $("#englishSearchCheckbox").prop("checked"),
                        advanced: $("#advancedSearchCheckbox").prop("checked")
                    },
                    type: 'post',
                    success: function (resp) {
                        $("#searchResult").html(resp)
                    },
                    error: function (resp) {
                        $("#searchResult").html(resp)
                    }
                });
            }, 600, { leading: false, trailing: true });
            $("#searchForm").submit(function(event){
                event.preventDefault();
                fetchSearchResults();
                return false;
            })
            $("#searchForm input[type=checkbox]").change(function(event){
                fetchSearchResults();
            })
            $("#searchQuery").keyup(function(event){
                fetchSearchResults();
            })
            if( Cookies.get("search-german") && Cookies.get("search-english") && Cookies.get("search-advanced")){
                $("#germanSearchCheckbox").prop("checked", Cookies.get("search-german") == "true")
                $("#englishSearchCheckbox").prop("checked", Cookies.get("search-english") == "true")
                $("#advancedSearchCheckbox").prop("checked", Cookies.get("search-advanced") == "true")
            }
        </script>
    <?php
die();
}
isset($_POST["query"]) or die("not a valid query");
$result = $conn->query("SELECT DISTINCT companyID, name FROM $companyToUserRelationshipTable inner join companyData on companyData.id = $companyToUserRelationshipTable.companyID WHERE userID = $userID OR $userID = 1");
$available_companies = array();
while ($result && ($row = $result->fetch_assoc())) {
    $available_companies[] = array("id" => $row['companyID'], "name" => $row["name"]);
}
$german = $english = $advanced = false;
if (isset($_POST["german"])) {
    $german = $_POST["german"] == "true";
}

if (isset($_POST["english"])) {
    $english = $_POST["english"] == "true";
}

if (isset($_POST["advanced"])) {
    $advanced = $_POST["advanced"] == "true";
}

$routes = array();
$routesENG = array();
$routesGER = array();
$routesENG[] = array("name" => "Home", "url" => "../user/home", "tags" => array("Overview"));
$routesGER[] = array("name" => "Übersicht", "url" => "../user/home", "tags" => array("Home"));
$routesENG[] = array("name" => "My Times", "url" => "../user/time", "tags" => array("Monthly Report"));
$routesGER[] = array("name" => "Meine Zeiten", "url" => "../user/time", "tags" => array("Monatsbericht"));
$routesENG[] = array("name" => "Request", "url" => "../user/request");
$routesGER[] = array("name" => "Anträge", "url" => "../user/request", "tags" => array("Antrag stellen"));
//TODO: Add Tags
$routesENG[] = array("name"=>"Request", "url"=>"../social/post");
$routesGER[] = array("name"=>"Anträge", "url"=>"../social/post");
$routesENG[] = array("name" => "Book Projects", "url" => "../user/book", "tags" => array("Booking"));
$routesGER[] = array("name" => "Projekte buchen", "url" => "../user/book", "tags" => array("Buchungen"));
$routesENG[] = array("name" => "Suppliers", "url" => "../erp/suppliers", "tags" => array("Supplier List"));
$routesGER[] = array("name" => "Lieferanten", "url" => "../erp/suppliers", "tags" => array("Lieferantenliste"));
$routesENG[] = array("name" => "Edit Users", "url" => "../system/users", "tags" => array("User List", "Users"));
$routesGER[] = array("name" => "Benutzer editieren", "url" => "../system/users", "tags" => array("Benutzer", "Benutzerliste"));
$routesENG[] = array("name" => "User Saldo", "url" => "../system/saldo", "tags" => array("User List", "Users"));
$routesGER[] = array("name" => "Benutzer Saldo", "url" => "../system/saldo", "tags" => array("Benutzerliste", "Benutzer"));
$routesENG[] = array("name" => "Add User", "url" => "../system/register", "tags" => array("New User", "Create User"));
$routesGER[] = array("name" => "Benutzer hinzufügen", "url" => "../system/register", "tags" => array("Registrieren", "Neuer Benutzer"));
$routesENG[] = array("name" => "Deactivated Users", "url" => "../system/deactivated", "tags" => array("Deleted Users", "Removed Users"));
$routesGER[] = array("name" => "Deaktivierte Benutzer", "url" => "../system/deactivated", "tags" => array("Gelöschte Benutzer"));
$routesENG[] = array("name" => "Checkin Logs", "url" => "../system/checkinLogs", "tags" => array("Checkins", "User Checkin"));
$routesGER[] = array("name" => "Checkin Logs", "url" => "../system/checkinLogs", "tags" => array("Checkins", "Benutzer Checkin"));
$routesENG[] = array("name" => "Clients", "url" => "../system/clients", "tags" => array("Client List"));
$routesGER[] = array("name" => "Kunden", "url" => "../system/clients", "tags" => array("Kundenliste"));
$routesENG[] = array("name" => "Teams", "url" => "../system/teams", "tags" => array("Team List"));
$routesGER[] = array("name" => "Teams", "url" => "../system/teams", "tags" => array("Team Liste"));
$routesENG[] = array("name" => "Holidays", "url" => "../system/holidays", "tags" => array("Holiday Settings"));
$routesGER[] = array("name" => "Feiertage", "url" => "../system/holidays", "tags" => array("Feiertage Einstellungen"));
$routesENG[] = array("name" => "Advanced Options", "url" => "../system/advanced", "tags" => array("Advanced Settings", "GitHub Settings", "Buffer Settings", "Display Settings", "Self Registration Settings"));
$routesGER[] = array("name" => "Erweiterte Optionen", "url" => "../system/advanced", "tags" => array("Erweiterte Einstellungen", "GitHub Einstellungen", "Buffers", "Display Einstellungen", "Selbstregistrationseinstellungen"));
$routesENG[] = array("name" => "Password Options", "url" => "../system/password", "tags" => array("Password Settings", "Password Expiration Date", "Master Password", "Encryption"));
$routesGER[] = array("name" => "Passwort Optionen", "url" => "../system/password", "tags" => array("Passwort Einstellungen", "Passwort Gültikgkeitsdatum", "Master Passwort", "Verschlüsselung"));
$routesENG[] = array("name" => "Email Options", "url" => "../system/email", "tags" => array("Email Settings", "SMTP", "Feedback Options"));
$routesGER[] = array("name" => "Email Optionen", "url" => "../system/email", "tags" => array("Email Einstellungen", "SMTP", "Feedback Einstellungen"));
$routesENG[] = array("name" => "Archive Options", "url" => "../system/archive", "tags" => array("Archive Settings"));
$routesGER[] = array("name" => "Archiv Optionen", "url" => "../system/archive", "tags" => array("Archiv Einstellungen"));
$routesENG[] = array("name" => "Task Scheduler", "url" => "../system/tasks", "tags" => array("Task Scheduling", "Tasks", "Email Report", "Restic Database Backup", "Invalid Lunchbreaks", "Automatic Task Builder"));
$routesGER[] = array("name" => "Taskplaner", "url" => "../system/tasks", "tags" => array("Tasks", "Email Report", "Restic Backup", "Ungültige Pausen", "Automatischer Task Builder"));
$routesENG[] = array("name" => "SQL Backup", "url" => "../system/backup", "tags" => array("Download Database Backup", "SQL Backup", "ZIP"));
$routesGER[] = array("name" => "SQL Backup", "url" => "../system/backup", "tags" => array("Download Datenbank Backup", "SQL Backup", "ZIP"));
$routesENG[] = array("name" => "SQL Restore", "url" => "../system/restore", "tags" => array("Restore Database Backup", "SQL Restore", "ZIP"));
$routesGER[] = array("name" => "SQL Wiederherstellung", "url" => "../system/restore", "tags" => array("Datenbank Backup wiederherstellen", "SQL Widerherstellung", "ZIP"));
$routesENG[] = array("name" => "Update", "url" => "../system/update", "tags" => array("GIT Update", "New Version"));
$routesGER[] = array("name" => "Update", "url" => "../system/update", "tags" => array("GIT Update", "Neue Version"));
$routesENG[] = array("name" => "Restic Backup", "url" => "../system/restic", "tags" => array("Restic Restore", "Restic Settings"));
$routesGER[] = array("name" => "Restic Backup", "url" => "../system/restic", "tags" => array("Restic Widerherstellung", "Restic Einstellungen"));
$routesENG[] = array("name" => "Time Overview", "url" => "../time/view", "tags" => array("View Time"));
$routesGER[] = array("name" => "Zeit - Übersicht", "url" => "../time/view", "tags" => array("Zeit Übersicht", "Zeit ansehen"));
$routesENG[] = array("name" => "Correction Hours", "url" => "../time/corrections", "tags" => array("Time Corrections", "Adjustments"));
$routesGER[] = array("name" => "Zeit Anpassungen", "url" => "../time/corrections", "tags" => array("Korrekturstunden", "Stundenanpassung"));
$routesENG[] = array("name" => "Traveling Expenses", "url" => "../time/travels", "tags" => array("Travels"));
$routesGER[] = array("name" => "Reisekostenabrechnung", "url" => "../time/travels", "tags" => array("Reisen"));
$routesENG[] = array("name" => "Vacation", "url" => "../time/vacations", "tags" => array("Days available"));
$routesGER[] = array("name" => "Urlaub", "url" => "../time/vacations", "tags" => array("Tage verfügbar"));
$routesENG[] = array("name" => "Checklist", "url" => "../time/check", "tags" => array("Check", "Invalid Timestamps", "Invalid Lunchbreaks", "Autocorrect"));
$routesGER[] = array("name" => "Checkliste", "url" => "../time/check", "tags" => array("Check", "Ungültige Zeitstempel", "Ungültige Pausen", "Autocorrect"));
$routesENG[] = array("name" => "Static Projects", "url" => "../project/view");
$routesGER[] = array("name" => "Statische Projekte", "url" => "../project/view");
$routesENG[] = array("name" => "Project Logs", "url" => "../project/log");
$routesGER[] = array("name" => "Buchungsprotokoll", "url" => "../project/log", "tags" => array("Projektbuchungen","Projekt Logs"));
$routesENG[] = array("name" => "Tasks", "url" => "../dynamic-projects/view", "tags" => array("Dynamic Projects", "Todos"));
$routesGER[] = array("name" => "Tasks", "url" => "../dynamic-projects/view", "tags" => array("Dynamische Projekte", "Todos"));
$routesENG[] = array("name" => "Project Options", "url" => "../project/options");
$routesGER[] = array("name" => "Projekt Optionen", "url" => "../project/options");
$routesENG[] = array("name" => "Productivity", "url" => "../report/productivity", "tags" => array("Report"));
$routesGER[] = array("name" => "Produktivität", "url" => "../report/productivity", "tags" => array("Berichte"));
$routesENG[] = array("name" => "Report Designer", "url" => "../system/designer", "tags" => array("Reports", "Templates"));
$routesGER[] = array("name" => "Report Designer", "url" => "../system/designer", "tags" => array("Reports", "Vorlagen"));
$routesENG[] = array("name" => "Processes", "url" => "../erp/view");
$routesGER[] = array("name" => "Vorgänge", "url" => "../erp/view");
$routesENG[] = array("name" => "Receipt Book", "url" => "../erp/receipts", "tags" => array("Receipts"));
$routesGER[] = array("name" => "Wareneingangsbuch", "url" => "../erp/receipts", "tags" => array("Eingänge"));
$routesENG[] = array("name" => "Tax Rates", "url" => "../erp/taxes", "tags" => array("ERP Taxes"));
$routesGER[] = array("name" => "Steuersätze", "url" => "../erp/taxes", "tags" => array("ERP Steuern"));
$routesENG[] = array("name" => "Units", "url" => "../erp/units", "tags" => array("ERP Units"));
$routesGER[] = array("name" => "Einheiten", "url" => "../erp/units", "tags" => array("ERP Einheiten"));
$routesENG[] = array("name" => "Payment Methods", "url" => "../erp/payment", "tags" => array("ERP Payments"));
$routesGER[] = array("name" => "Zahlungsarten", "url" => "../erp/payment", "tags" => array("ERP Zahlungen"));
$routesENG[] = array("name" => "Shipping Methods", "url" => "../erp/shipping", "tags" => array("ERP Shipping"));
$routesGER[] = array("name" => "Versandarten", "url" => "../erp/shipping", "tags" => array("ERP Versand"));
$routesENG[] = array("name" => "Representative", "url" => "../erp/representatives", "tags" => array("ERP Representatives"));
$routesGER[] = array("name" => "Vertreter", "url" => "../erp/representatives", "tags" => array("ERP Vertreter"));
foreach ($available_companies as $company) {
    $companyID = $company["id"];
    $name = $company["name"];
    $routesENG[] = array("name" => "Clients ($name)", "url" => "../system/clients?t=$companyID", "tags" => array("Client List"));
    $routesGER[] = array("name" => "Kundenliste ($name)", "url" => "../system/clients?t=$companyID", "tags" => array("Kunden"));
    $routesENG[] = array("name" => "Agreements ($name)", "url" => "../dsgvo/documents?n=$companyID", "tags" => array("DSGVO Documents"));
    $routesGER[] = array("name" => "Vereinbarungen ($name)", "url" => "../dsgvo/documents?n=$companyID", "tags" => array("DSGVO Vereinbarungen"));
    $routesENG[] = array("name" => "Procedure Directory ($name)", "url" => "../dsgvo/vv?n=$companyID", "tags" => array("DSGVO Procedures"));
    $routesGER[] = array("name" => "Verfahrensverzeichnis ($name)", "url" => "../dsgvo/vv?n=$companyID", "tags" => array("DSGVO Verfahren", "VV"));
    $routesENG[] = array("name" => "Email Templates ($name)", "url" => "../dsgvo/templates?n=$companyID", "tags" => array("DSGVO Emails"));
    $routesGER[] = array("name" => "Email Vorlagen ($name)", "url" => "../dsgvo/templates?n=$companyID", "tags" => array("DSGVO Emails", "DSGVO Vorlagen"));
    $routesENG[] = array("name" => "Data Matrix ($name)", "url" => "../dsgvo/data-matrix?n=$companyID", "tags" => array("DSGVO Data Matrix", "DSGVO Templates" ));
    $routesGER[] = array("name" => "Datenmatrix ($name)", "url" => "../dsgvo/data-matrix?n=$companyID", "tags" => array("DSGVO Datenmatrix", "DSGVO Vorlagen"));
    $routesENG[] = array("name" => "Procedure Directory - Templates ($name)", "url" => "../dsgvo/vtemplates?n=$companyID", "tags" => array("DSGVO Procedure Templates"));
    $routesGER[] = array("name" => "Verfahrensverzeichnis - Templates ($name)", "url" => "../dsgvo/vtemplates?n=$companyID", "tags" => array("DSGVO Verfahrensverzeichnis Vorlagen"));
    $routesENG[] = array("name" => "Trainings ($name)", "url" => "../dsgvo/training?n=$companyID", "tags" => array("DSGVO Trainings"));
    $routesGER[] = array("name" => "Schulungen ($name)", "url" => "../dsgvo/training?n=$companyID", "tags" => array("DSGVO Schulungen"));
    $routesENG[] = array("name" => "Edit Company ($name)", "url" => "../system/company?cmp=$companyID", "tags" => array("Companies"));
    $routesGER[] = array("name" => "Mandant Bearbeiten ($name)", "url" => "../system/company?cmp=$companyID", "tags" => array("Mandanten"));
    $routesENG[] = array("name" => "Account Plan ($name)", "url" => "../finance/plan?n=$companyID", "tags" => array("Finances"));
    $routesGER[] = array("name" => "Kontenplan ($name)", "url" => "../finance/plan?n=$companyID", "tags" => array("Finanzen"));
    $routesENG[] = array("name" => "Accounting Journal ($name)", "url" => "../finance/journal?n=$companyID", "tags" => array("Finances"));
    $routesGER[] = array("name" => "Buchungsjournal ($name)", "url" => "../finance/journal?n=$companyID", "tags" => array("Finanzen", "Buchungen"));
    $routesENG[] = array("name" => "Articles ($name)", "url" => "../erp/articles?cmp=$companyID", "tags" => array("ERP Articles"));
    $routesGER[] = array("name" => "Artikel ($name)", "url" => "../erp/articles?cmp=$companyID", "tags" => array("ERP Artikel"));
    $routesENG[] = array("name" => "DSGVO Logs ($name)", "url" => "../dsgvo/log?n=$companyID", "tags" => array("Training Logs", "Procedure Directory Logs"));
    $routesGER[] = array("name" => "DSGVO Logs ($name)", "url" => "../dsgvo/log?n=$companyID", "tags" => array("Schulung Logs", "Verfahrensverzeichnis Logs"));
}

if ($advanced && $enableToAdvancedSearch) { // todo: search should be done in the database itself in future versions
    $users = array();
    $teams = array();
    $clients = array();
    $trainings = array();
    $tasks = array();
    $result = $conn->query("SELECT id, firstname, lastname FROM UserData GROUP BY id");
    while ($result && ($row = $result->fetch_assoc())) {
        $users[] = array("id" => $row['id'], "name" => $row["firstname"] . " " . $row["lastname"]);
    }
    $result = $conn->query("SELECT id, name FROM $teamTable");
    while ($result && ($row = $result->fetch_assoc())) {$teams[] = array("id" => $row['id'], "name" => $row["name"]);}
    $result = $conn->query("SELECT id, name, isSupplier FROM clientData");
    while ($result && ($row = $result->fetch_assoc())) {$clients[] = array("id" => $row['id'], "name" => $row["name"], "supplier" => $row["isSupplier"] == 'TRUE');}
    $result = $conn->query("SELECT id, name,companyID FROM dsgvo_training");
    while ($result && ($row = $result->fetch_assoc())) {$trainings[] = array("id" => $row['id'], "name" => $row["name"], "company" => $row["companyID"]);}
    $result = $conn->query("SELECT projectid, projectname, projectdescription FROM dynamicprojects");
    while ($result && ($row = $result->fetch_assoc())) {$tasks[] = array("id" => $row['projectid'], "name" => $row["projectname"], "description" => $row["projectdescription"]);}
    foreach ($users as $user) {
        $name = $user["name"];
        $id = $user["id"];
        $routesENG[] = array("name" => "Edit User ($name)", "url" => "../system/users?ACT=$id");
        $routesGER[] = array("name" => "Benutzer Bearbeiten ($name)", "url" => "../system/users?ACT=$id");
    }
    foreach ($teams as $team) {
        $name = $team["name"];
        $id = $team["id"];
        $routesENG[] = array("name" => "Edit Team ($name)", "url" => "../system/teams?id=$id");
        $routesGER[] = array("name" => "Team Bearbeiten ($name)", "url" => "../system/teams?id=$id");
    }
    foreach ($clients as $client) {
        $name = $client["name"];
        $id = $client["id"];
        if ($client["supplier"]) {
            $routesENG[] = array("name" => "Edit Supplier ($name)", "url" => "../system/clientDetail?supID=$id");
            $routesGER[] = array("name" => "Lieferant bearbeiten ($name)", "url" => "../system/clientDetail?supID=$id");
        } else {
            $routesENG[] = array("name" => "Edit Clients ($name)", "url" => "../system/clientDetail?custID=$id");
            $routesGER[] = array("name" => "Kunde bearbeiten ($name)", "url" => "../system/clientDetail?custID=$id");
        }
    }
    foreach ($trainings as $training) {
        $name = $training["name"];
        $id = $training["id"];
        $companyID = $training["company"];
        $routesENG[] = array("name" => "Trainings ($name)", "url" => "../dsgvo/training?n=$companyID&trainingid=$id");
        $routesGER[] = array("name" => "Schulungen ($name)", "url" => "../dsgvo/training?n=$companyID&trainingid=$id");
    }
    foreach ($tasks as $task) {
        $name = $task["name"];
        $id = $task["id"];
        $description = strip_tags($task["description"]);
        $routesENG[] = array("name" => "Task ($name)", "url" => "../dynamic-projects/view?id=$id", "tags" => array($description));
        $routesGER[] = array("name" => "Task ($name)", "url" => "../dynamic-projects/view?id=$id", "tags" => array($description));
    }
}

function test_input($data)
{
    $data = preg_replace("~[^A-Za-z0-9\-@.+/öäüÖÄÜß_ ]~", "", $data);
    $data = trim($data);
    return $data;
}
function formatList($list)
{
    $output = "";
    $output .= '<ul class="list-group">';
    foreach ($list as $idx => $item) {
        $output .= '<li class="list-group-item">';
        $name = $item["name"];
        $url = $item["url"];
        $output .= "<a href='$url'>$name</a>";
        $tags = array();
        if (isset($item["tags"])) {
            $tags = $item["tags"];
            $output .= "<br>";
        }
        foreach ($tags as $tag) {
            $output .= "<span class='label label-default' style='display:inline-block;text-overflow:ellipsis;overflow:hidden;max-width:150px;margin:4px'>$tag</span> ";
        }
        $output .= '</li>';
    }
    $output .= '</ul>';
    return $output;
}
$query = strtolower(test_input($_POST["query"]));
if ($german) {
    $routes = array_merge($routes, $routesGER);
}

if ($english) {
    $routes = array_merge($routes, $routesENG);
}

function queryFunction($item)
{
    global $query;
    $name = strtolower($item["name"]);
    $url = $item["url"];
    $tags = array();
    if (isset($item["tags"])) {
        $tags = $item["tags"];
    }
    if (strlen($query) > 0) {
        if (strpos($name, $query) !== false) {
            return true;
        }
        foreach ($tags as $tag) {
            $tag = strtolower($tag);
            if (strpos($tag, $query) !== false) {
                return true;
            }
        }
        return false;
    }
    return true;
}

echo formatList(array_filter($routes, "queryFunction"));
?>