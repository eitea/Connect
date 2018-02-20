<?php
include dirname(dirname(__DIR__)) . '/header.php';?>
<?php require dirname(dirname(__DIR__)) . "/misc/helpcenter.php"; ?>
<?php
enableToClients($userID);

if (isset($_GET['custID']) && is_numeric($_GET['custID'])) {
    $filterClient = intval($_GET['custID']);
} elseif (isset($_GET['supID']) && is_numeric($_GET['supID'])) {
    $filterClient = intval($_GET['supID']);
} else { // STRIKE
    $conn->query("UPDATE userdata SET strikeCount = strikecount + 1 WHERE id = $userID");
    echo '<script>alert("<strong>Invalid Access.</strong> '.$lang['ERROR_STRIKE'].'")</script>';
    redirect("../user/logout");
}

// get corresponding id from detailTable
$result = $conn->query("SELECT * FROM $clientDetailTable WHERE clientId = $filterClient");
if ($result && ($row = $result->fetch_assoc())) {
    $detailID = $row['id'];
} else { // no detailTable found -> create one
    $conn->query("INSERT INTO $clientDetailTable (clientID) VALUES($filterClient)");
    $detailID = $conn->insert_id;
    echo mysqli_error($conn);
}

$activeTab = 'home';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!empty($_POST['saveAll'])) {
        $activeTab = $_POST['saveAll'];
    }
    //always update
    $val = isset($_POST['contactType']) ? test_input($_POST['contactType']) : '';
    $conn->query("UPDATE $clientDetailTable SET contactType = '$val' WHERE id = $detailID");
    if (!empty($_POST['edit_name'])) {
        $name = test_input($_POST['edit_name']);
        $companyID = intval($_POST['edit_company']);
        $number = test_input($_POST['edit_clientNumber']);
        $conn->query("UPDATE $clientTable SET name = '$name', companyID = $companyID, clientNumber = '$number' WHERE id = $filterClient");
    }
    if (isset($_POST['gender'])) {
        $val = $_POST['gender'];
        $conn->query("UPDATE $clientDetailTable SET gender = '$val' WHERE id = $detailID");
    }
    if (isset($_POST['title'])) {
        $val = test_input($_POST['title']);
        $conn->query("UPDATE $clientDetailTable SET title = '$val' WHERE id = $detailID");
    }
    if (isset($_POST['name'])) {
        $val = test_input($_POST['name']);
        $conn->query("UPDATE $clientDetailTable SET name = '$val' WHERE id = $detailID");
    }
    if (isset($_POST['firstname'])) {
        $val = test_input($_POST['firstname']);
        $conn->query("UPDATE $clientDetailTable SET firstname = '$val' WHERE id = $detailID");
    }
    if (isset($_POST['nameAddition'])) {
        $val = test_input($_POST['nameAddition']);
        $conn->query("UPDATE $clientDetailTable SET nameAddition = '$val' WHERE id = $detailID");
    }
    if (isset($_POST['address_Street'])) {
        $val = test_input($_POST['address_Street']);
        $conn->query("UPDATE $clientDetailTable SET address_Street = '$val' WHERE id = $detailID");
    }
    if (isset($_POST['address_Country'])) {
        $val = test_input($_POST['address_Country']);
        $conn->query("UPDATE $clientDetailTable SET address_Country = '$val' WHERE id = $detailID");
    }
    if (isset($_POST['address_Country_City'])) {
        $val = test_input($_POST['address_Country_City']);
        $conn->query("UPDATE $clientDetailTable SET address_Country_City = '$val' WHERE id = $detailID");
    }
    if (isset($_POST['address_Country_Postal'])) {
        $val = test_input($_POST['address_Country_Postal']);
        $conn->query("UPDATE $clientDetailTable SET address_Country_Postal = '$val' WHERE id = $detailID");
    }
    if (isset($_POST['address_Addition'])) {
        $val = test_input($_POST['address_Addition']);
        $conn->query("UPDATE $clientDetailTable SET address_Addition = '$val' WHERE id = $detailID");
    }
    if (isset($_POST['phone'])) {
        $val = test_input($_POST['phone']);
        $conn->query("UPDATE $clientDetailTable SET phone = '$val' WHERE id = $detailID");
    }
    if (isset($_POST['fax_number'])) {
        $val = test_input($_POST['fax_number']);
        $conn->query("UPDATE $clientDetailTable SET fax_number = '$val' WHERE id = $detailID");
    }
    if (isset($_POST['homepage'])) {
        $val = test_input($_POST['homepage']);
        $conn->query("UPDATE $clientDetailTable SET homepage = '$val' WHERE id = $detailID");
    }
    if (isset($_POST['mail']) && filter_var($_POST['mail'], FILTER_VALIDATE_EMAIL)) {
        $val = test_input($_POST['mail']);
        $conn->query("UPDATE $clientDetailTable SET mail = '$val' WHERE id = $detailID");
    }
    if (isset($_POST['debitNumber'])) {
        $val = intval($_POST['debitNumber']);
        $conn->query("UPDATE $clientDetailTable SET debitNumber = '$val' WHERE id = $detailID");
    }
    if (isset($_POST['datev'])) {
        $val = intval($_POST['datev']);
        $conn->query("UPDATE $clientDetailTable SET datev = '$val' WHERE id = $detailID");
    }
    if (isset($_POST['accountName'])) {
        $val = test_input($_POST['accountName']);
        $conn->query("UPDATE $clientDetailTable SET accountName = '$val' WHERE id = $detailID");
    }
    if (isset($_POST['taxnumber'])) {
        $val = test_input($_POST['taxnumber']);
        $conn->query("UPDATE $clientDetailTable SET taxnumber = '$val' WHERE id = $detailID");
    }
    if (isset($_POST['vatnumber'])) {
        $val = test_input($_POST['vatnumber']);
        $conn->query("UPDATE $clientDetailTable SET vatnumber = '$val' WHERE id = $detailID");
    }
    if (isset($_POST['taxArea'])) {
        $val = test_input($_POST['taxArea']);
        $conn->query("UPDATE $clientDetailTable SET taxArea = '$val' WHERE id = $detailID");
    }
    if (isset($_POST['customerGroup'])) {
        $val = test_input($_POST['customerGroup']);
        $conn->query("UPDATE $clientDetailTable SET customerGroup = '$val' WHERE id = $detailID");
    }
    if (isset($_POST['representative'])) {
        $val = test_input($_POST['representative']);
        $conn->query("UPDATE $clientDetailTable SET representative = '$val' WHERE id = $detailID");
    }
    if (isset($_POST['blockDelivery'])) {
        $conn->query("UPDATE $clientDetailTable SET blockDelivery = 'true' WHERE id = $detailID");
    } else {
        $conn->query("UPDATE $clientDetailTable SET blockDelivery = 'false' WHERE id = $detailID");
    }
    if (isset($_POST['paymentMethod'])) {
        $val = test_input($_POST['paymentMethod']);
        $conn->query("UPDATE $clientDetailTable SET paymentMethod = '$val' WHERE id = $detailID");
    }
    if (isset($_POST['shipmentType'])) {
        $val = test_input($_POST['shipmentType']);
        $conn->query("UPDATE $clientDetailTable SET shipmentType = '$val' WHERE id = $detailID");
    }
    if (isset($_POST['creditLimit'])) {
        $val = floatval($_POST['creditLimit']);
        $conn->query("UPDATE $clientDetailTable SET creditLimit = '$val' WHERE id = $detailID");
    }
    if (isset($_POST['eBill'])) {
        $conn->query("UPDATE $clientDetailTable SET eBill = 'true' WHERE id = $detailID");
    } else {
        $conn->query("UPDATE $clientDetailTable SET eBill = 'false' WHERE id = $detailID");
    }
    if (isset($_POST['lastFaktura']) && test_Date($_POST['lastFaktura'] . ':00')) {
        $val = $_POST['lastFaktura'] . ':00';
        $conn->query("UPDATE $clientDetailTable SET lastFaktura = '$val' WHERE id = $detailID");
    }
    if (isset($_POST['billingMailAddress']) && filter_var($_POST['billingMailAddress'], FILTER_VALIDATE_EMAIL)) {
        $val = test_input($_POST['billingMailAddress']);
        $conn->query("UPDATE $clientDetailTable SET billingMailAddress = '$val' WHERE id = $detailID");
    }
    if (isset($_POST['billDelivery'])) {
        $val = test_input($_POST['billDelivery']);
        $conn->query("UPDATE $clientDetailTable SET billDelivery = '$val' WHERE id = $detailID");
    }
    if (isset($_POST['warningEnabled'])) {
        $conn->query("UPDATE $clientDetailTable SET warningEnabled = 'true' WHERE id = $detailID");
    } else {
        $conn->query("UPDATE $clientDetailTable SET warningEnabled = 'false' WHERE id = $detailID");
    }
    if (isset($_POST['karenztage'])) {
        $val = intval($_POST['karenztage']);
        $conn->query("UPDATE $clientDetailTable SET karenztage = '$val' WHERE id = $detailID");
    }
    if (isset($_POST['lastWarning']) && test_Date($_POST['lastWarning'] . ':00')) {
        $val = $_POST['lastWarning'] . ':00';
        $conn->query("UPDATE $clientDetailTable SET lastFaktura = '$val' WHERE id = $detailID");
    }
    if (isset($_POST['warning1'])) {
        $val = floatval($_POST['warning1']);
        $conn->query("UPDATE $clientDetailTable SET warning1 = '$val' WHERE id = $detailID");
    }
    if (isset($_POST['warning2'])) {
        $val = floatval($_POST['warning2']);
        $conn->query("UPDATE $clientDetailTable SET warning2 = '$val' WHERE id = $detailID");
    }
    if (isset($_POST['warning3'])) {
        $val = floatval($_POST['warning3']);
        $conn->query("UPDATE $clientDetailTable SET warning3 = '$val' WHERE id = $detailID");
    }
    if (isset($_POST['calculateInterest'])) {
        $conn->query("UPDATE $clientDetailTable SET calculateInterest = 'true' WHERE id = $detailID");
    } else {
        $conn->query("UPDATE $clientDetailTable SET calculateInterest = 'false' WHERE id = $detailID");
    }
    if ($conn->error) {
        echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>' . $conn->error . '</div>';
    } else {
        echo '<div class="alert alert-success"><a href="#" data-dismiss="alert" class="close">&times;</a>' . $lang['OK_SAVE'] . '</div>';
    }
    if (isset($_POST['addNotes']) && !empty($_POST['infoText'])) {
        $activeTab = 'notes';
        $val = test_input($_POST['infoText']);
        $conn->query("INSERT INTO $clientDetailNotesTable (infoText, createDate, parentID) VALUES ('$val', CURRENT_TIMESTAMP, $detailID)");
        if ($conn->error) {
            echo $conn->error;
        } else {
            echo '<div class="alert alert-success"><a href="#" data-dismiss="alert" class="close">&times;</a>' . $lang['OK_ADD'] . '</div>';
        }
    } elseif (isset($_POST['deleteNotes']) && !empty($_POST['noteIndeces'])) {
        $activeTab = 'notes';
        foreach ($_POST['noteIndeces'] as $i) {
            $conn->query("DELETE FROM $clientDetailNotesTable WHERE id = $i");
        }
        if ($conn->error) {
            echo $conn->error;
        } else {
            echo '<div class="alert alert-success"><a href="#" data-dismiss="alert" class="close">&times;</a>' . $lang['OK_DELETE'] . '</div>';
        }
    } elseif (isset($_POST['addBankingDetail']) && !empty($_POST['bankName']) && !empty($_POST['iban']) && !empty($_POST['bic'])) {
        $activeTab = 'banking';
        $mc = new MasterCrypt($_SESSION["masterpassword"]);
        $bankName = $mc->encrypt($_POST['bankName']);
        $ibanVal = $mc->encrypt($_POST['iban']);
        $bicVal = $mc->encrypt($_POST['bic']);
        $iv = $mc->iv;
        $iv2 = $mc->iv2;
        $stmt = $conn->prepare("INSERT INTO clientInfoBank (bankName, iban, bic, iv, iv2, parentID) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param('sssssi', $bankName, $ibanVal, $bicVal, $iv, $iv2, $detailID);
        $stmt->execute();
        $stmt->close();
        if ($conn->error) {
            echo $conn->error;
        } else {
            echo '<div class="alert alert-success"><a href="#" data-dismiss="alert" class="close">&times;</a>' . $lang['OK_ADD'] . '</div>';
        }
    } elseif (!empty($_POST['removeBank'])) {
        $activeTab = 'banking';
        $val = intval($_POST['removeBank']);
        $conn->query("DELETE FROM clientInfoBank WHERE id = $val");
        if ($conn->error) {
            echo $conn->error;
        } else {
            echo '<div class="alert alert-success"><a href="#" data-dismiss="alert" class="close">&times;</a>' . $lang['OK_DELETE'] . '</div>';
        }
    } elseif (!empty($_POST['editBankingDetail'])) {
        $activeTab = 'banking';
        $mc = new MasterCrypt($_SESSION["masterpassword"]);
        $bankName = $mc->encrypt($_POST['edit_bankName']);
        $ibanVal = $mc->encrypt($_POST['edit_iban']);
        $bicVal = $mc->encrypt($_POST['edit_bic']);
        $iv = $mc->iv;
        $iv2 = $mc->iv2;
        $val = intval($_POST['editBankingDetail']);
        $stmt = $conn->prepare("UPDATE clientInfoBank SET bankName = ?, iban = ?, bic = ?, iv = ?, iv2 = ? WHERE id = ?");
        $stmt->bind_param('sssssi', $bankName, $ibanVal, $bicVal, $iv, $iv2, $val);
        $stmt->execute();
        $stmt->close();
        if ($conn->error) {
            echo $conn->error;
        } else {
            echo '<div class="alert alert-success"><a href="#" data-dismiss="alert" class="close">&times;</a>' . $lang['OK_SAVE'] . '</div>';
        }
    } elseif (isset($_POST['delete_projects']) && !empty($_POST['delete_projects_index'])) {
        $activeTab = 'project';
        foreach ($_POST['delete_projects_index'] as $x) {
            $x = intval($x);
            $conn->query("DELETE FROM $projectTable WHERE id = $x;");
        }
        if ($conn->error) {
            echo $conn->error;
        } else {
            echo '<div class="alert alert-success"><a href="#" data-dismiss="alert" class="close">&times;</a>' . $lang['OK_DELETE'] . '</div>';
        }
    } elseif (isset($_POST['add']) && !empty($_POST['name'])) {
        $activeTab = 'project';
        $name = test_input($_POST['name']);
        $status = "";
        if (isset($_POST['status'])) {
            $status = "checked";
        }
        $hourlyPrice = floatval(test_input($_POST['hourlyPrice']));
        $hours = floatval(test_input($_POST['hours']));
        if (isset($_POST['createField_1'])) {
            $field_1 = 'TRUE';
        } else {
            $field_1 = 'FALSE';
        }
        if (isset($_POST['createField_2'])) {
            $field_2 = 'TRUE';
        } else {
            $field_2 = 'FALSE';
        }
        if (isset($_POST['createField_3'])) {
            $field_3 = 'TRUE';
        } else {
            $field_3 = 'FALSE';
        }
        $conn->query("INSERT INTO $projectTable (clientID, name, status, hours, hourlyPrice, field_1, field_2, field_3) VALUES($filterClient, '$name', '$status', '$hours', '$hourlyPrice', '$field_1', '$field_2', '$field_3')");
        if ($conn->error) {
            echo $conn->error;
        } else {
            echo '<div class="alert alert-success"><a href="#" data-dismiss="alert" class="close">&times;</a>' . $lang['OK_ADD'] . '</div>';
        }
    } elseif (isset($_POST['save'])) {
        $activeTab = 'project';
        $projectID = intval($_POST['save']);
        $hours = floatval(test_input($_POST['boughtHours']));
        $hourlyPrice = floatval(test_input($_POST['pricedHours']));
        $status = isset($_POST['productive']) ? 'checked' : '';
        // checkboxes are not set at all if they're not checked
        if (isset($_POST['addField_1_' . $projectID])) {
            $field_1 = 'TRUE';
        } else {
            $field_1 = 'FALSE';
        }
        if (isset($_POST['addField_2_' . $projectID])) {
            $field_2 = 'TRUE';
        } else {
            $field_2 = 'FALSE';
        }
        if (isset($_POST['addField_3_' . $projectID])) {
            $field_3 = 'TRUE';
        } else {
            $field_3 = 'FALSE';
        }

        $conn->query("UPDATE $projectTable SET hours = '$hours', hourlyPrice = '$hourlyPrice', status='$status', field_1 = '$field_1', field_2 = '$field_2', field_3 = '$field_3' WHERE id = $projectID");
        if ($conn->error) {
            echo $conn->error;
        } else {
            echo '<div class="alert alert-success"><a href="#" data-dismiss="alert" class="close">&times;</a>' . $lang['OK_SAVE'] . '</div>';
        }
    } elseif (!empty($_POST['deleteContact'])) {
        $val = intval($_POST['deleteContact']);
        $conn->query("DELETE FROM contactPersons WHERE id = $val");
        if ($conn->error) {
            echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>' . $conn->error . '</div>';
        } else {
            echo '<div class="alert alert-success"><a href="#" data-dismiss="alert" class="close">&times;</a>' . $lang['OK_DELETE'] . '</div>';
        }
    } elseif (isset($_POST['addContact']) && !empty($_POST['contacts_firstname']) && !empty($_POST['contacts_lastname']) && !empty($_POST['contacts_gender']) && !empty($_POST['contacts_email'])) {
        $firstname = test_input($_POST['contacts_firstname']);
        $lastname = test_input($_POST['contacts_lastname']);
        $mail = test_input($_POST['contacts_email']);
        $position = intval($_POST['contacts_position']);
        $resp = test_input($_POST['contacts_responsibility']);
        $dial = test_input($_POST['contacts_dial']);
        $faxDial = test_input($_POST['contacts_faxDial']);
        $phone = test_input($_POST['contacts_phone']);
        $gender = test_input($_POST['contacts_gender']);
        $title = test_input($_POST['contacts_titel']);
        $pgp = trim($_POST['contacts_pgp']);
        $conn->query("INSERT INTO contactPersons (clientID, firstname, lastname, email, position, responsibility, dial, faxDial, phone, gender, title, pgpKey)
        VALUES ($filterClient, '$firstname', '$lastname', '$mail', $position, '$resp', '$dial', '$faxDial', '$phone', '$gender', '$title', '$pgp')");
        if ($conn->error) {
            echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>' . $conn->error . '</div>';
        } else {
            echo '<div class="alert alert-success"><a href="#" data-dismiss="alert" class="close">&times;</a>' . $lang['OK_ADD'] . '</div>';
        }
    } elseif (isset($_POST['editContact']) && !empty($_POST['edit_contacts_firstname']) && !empty($_POST['edit_contacts_lastname']) && !empty($_POST['edit_contacts_gender']) && !empty($_POST['edit_contacts_email'])) {
        $id = intval($_POST['editContact']);
        $firstname = test_input($_POST['edit_contacts_firstname']);
        $lastname = test_input($_POST['edit_contacts_lastname']);
        $mail = test_input($_POST['edit_contacts_email']);
        $position = intval($_POST['edit_contacts_position']);
        $resp = test_input($_POST['edit_contacts_responsibility']);
        $dial = test_input($_POST['edit_contacts_dial']);
        $faxDial = test_input($_POST['edit_contacts_faxDial']);
        $phone = test_input($_POST['edit_contacts_phone']);
        $gender = test_input($_POST['edit_contacts_gender']);
        $title = test_input($_POST['edit_contacts_titel']);
        $pgp = trim($_POST['edit_contacts_pgp']);
        $conn->query("UPDATE contactPersons SET firstname = '$firstname', lastname = '$lastname', email = '$mail', position = $position, responsibility = '$resp', dial = '$dial',
            faxDial = '$faxDial', phone = '$phone', gender = '$gender', title = '$title', pgpKey = '$pgp' WHERE id = $id AND clientID = $filterClient");
        if ($conn->error) {
            echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>' . $conn->error . '</div>';
        } else {
            echo '<div class="alert alert-success"><a href="#" data-dismiss="alert" class="close">&times;</a>' . $lang['OK_SAVE'] . '</div>';
        }
    }
}

$result = $conn->query("SELECT name, clientNumber, companyID FROM clientData WHERE id = $filterClient");
$rowClient = $result->fetch_assoc();

$result = $conn->query("SELECT * FROM clientInfoData WHERE id = $detailID");
$row = $result->fetch_assoc();

$resultNotes = $conn->query("SELECT * FROM clientInfoNotes WHERE parentID = $detailID");
$resultBank = $conn->query("SELECT * FROM clientInfoBank WHERE parentID = $detailID");
$resultContacts = $conn->query("SELECT contactPersons.*, position.name AS positionName FROM contactPersons LEFT JOIN position ON position.id = position WHERE clientID = $filterClient");
?>

<div class="page-header">
	<h3><?php if (isset($_GET['supID'])) {echo $lang['SUPPLIER'];} else {echo $lang['CLIENT'];} echo ' - ' . $rowClient['name']; ?>
		<div class="page-header-button-group">
			<button id="sav" type="submit" class="btn btn-default blinking" name="saveAll" value="home" title="<?php echo $lang['SAVE']; ?>" form="mainForm"><i class="fa fa-floppy-o"></i></button>
		</div>
	</h3>
</div>

<ul class="nav nav-tabs">
	<li <?php if ($activeTab == 'project') {echo 'class="active"';}?>><a data-toggle="tab" href="#project" onclick="$('#sav').val('project');"><?php echo $lang['VIEW_PROJECTS']; ?></a></li>
	<li <?php if ($activeTab == 'home') {echo 'class="active"';}?>><a data-toggle="tab" href="#home" onclick="$('#sav').val('home');"><?php echo $lang['RECORD']; ?></a></li>
	<li <?php if ($activeTab == 'taxes') {echo 'class="active"';}?>><a data-toggle="tab" href="#menuTaxes" onclick="$('#sav').val('taxes');"><?php echo $lang['TAXES']; ?></a></li>
	<li <?php if ($activeTab == 'banking') {echo 'class="active"';}?>><a data-toggle="tab" href="#menuBank" onclick="$('#sav').val('banking');">Banking</a></li>
	<li <?php if ($activeTab == 'billing') {echo 'class="active"';}?>><a data-toggle="tab" href="#menuBilling" onclick="$('#sav').val('billing');"><?php echo $lang['BILLING']; ?></a></li>
	<li <?php if ($activeTab == 'payment') {echo 'class="active"';}?>><a data-toggle="tab" href="#menuPayment" onclick="$('#sav').val('payment');"><?php echo $lang['PAYMENT']; ?></a></li>
	<li <?php if ($activeTab == 'notes') {echo 'class="active"';}?>><a data-toggle="tab" href="#menuContact" onclick="$('#sav').val('notes');"><?php echo $lang['NOTES']; ?></a></li>
	<li <?php if ($activeTab == 'docs') {echo 'class="active"';}?>><a data-toggle="tab" href="#menuDocs" onclick="$('#sav').val('docs');"><?php echo $lang['DOCUMENTS']; ?></a></li>
</ul>

<form id="mainForm" method="POST">
  <div class="tab-content">
    <div id="project" class="tab-pane fade <?php if ($activeTab == 'project') {echo 'in active';}?>"> <h3><?php echo $lang['VIEW_PROJECTS']; ?>
        <div class="page-header-button-group">
          <button type="submit" class="btn btn-default" name='delete_projects' title="<?php echo $lang['DELETE']; ?>" ><i class="fa fa-trash-o"></i></button>
          <button type="button" class="btn btn-default" title="<?php echo $lang['ADD']; ?>" data-toggle="modal" data-target=".add-project"><i class="fa fa-plus"></i></button>
        </div></h3>
      <hr>
      <table class="table table-hover">
		<thead>
			<th><?php echo $lang['DELETE']; ?></th>
			<th></th>
			<th>Name</th>
			<th><?php echo $lang['ADDITIONAL_FIELDS']; ?></th>
			<th><?php echo $lang['HOURS']; ?></th>
			<th><?php echo $lang['HOURLY_RATE']; ?></th>
			<th></th>
        </thead>
        <tbody>
            <?php
            $result_p = $conn->query("SELECT * FROM $projectTable WHERE clientID = $filterClient");
            while ($row_p = $result_p->fetch_assoc()) {
                $productive = $row_p['status'] ? '<i class="fa fa-tags"></i>' : '';
                echo '<tr>';
                echo '<td><input type="checkbox" name="delete_projects_index[]" value=' . $row_p['id'] . ' /></td>';
                echo '<td>' . $productive . '</td>';
                echo '<td>' . $row_p['name'] . '</td>';
                echo '<td>';
                $resF = $conn->query("SELECT * FROM $companyExtraFieldsTable WHERE companyID = " . $rowClient['companyID'] . " ORDER BY id ASC");
                if ($resF->num_rows > 0) {
                    $rowF = $resF->fetch_assoc();
                    if ($rowF['isActive'] == 'TRUE' && $row_p['field_1'] == 'TRUE') {
                        echo $rowF['name'];
                    }
                }
                if ($resF->num_rows > 1) {
                    $rowF = $resF->fetch_assoc();
                    if ($rowF['isActive'] == 'TRUE' && $row_p['field_2'] == 'TRUE') {
                        echo $rowF['name'];
                    }
                }
                if ($resF->num_rows > 2) {
                    $rowF = $resF->fetch_assoc();
                    if ($rowF['isActive'] == 'TRUE' && $row_p['field_3'] == 'TRUE') {
                        echo $rowF['name'];
                    }
                }
                echo '</td>';
                echo '<td>' . $row_p['hours'] . '</td>';
                echo '<td>' . $row_p['hourlyPrice'] . '</td>';
                echo '<td><button type="button" class="btn btn-default" data-toggle="modal" data-target=".editingProjectsModal-' . $row_p['id'] . '"><i class="fa fa-pencil"></i></td>';
                echo '</tr>';
            }
            ?>
        </tbody>
    </table>
</div>

	<div id="home" class="tab-pane fade <?php if ($activeTab == 'home') {echo 'in active';}?>">
	<h3><?php echo $lang['GENERAL_INFORMATION']; ?></h3>
	<hr>
		<div class="row">
			<div class="col-md-4 col-md-offset-1">
				<label>Kundenname</label><input type="text" class="form-control" name="edit_name" placeholder="Kundenname" value="<?php echo $rowClient['name']; ?>" />
			</div>
			<div class="col-md-3">
				<label>Kundennummer</label><input type="text" class="form-control" maxlength="12" placeholder="z.B. #KD-123" value="<?php echo $rowClient['clientNumber']; ?>" name="edit_clientNumber" />
			</div>
			<div class="col-md-4">
				<label><?php echo $lang['COMPANY']; ?></label>
                <select class="js-example-basic-single" name="edit_company">
                    <?php
                    $res_cmp = $conn->query("SELECT id, name FROM companyData WHERE id IN (" . implode(', ', $available_companies) . ")");
                    while ($row_cmp = $res_cmp->fetch_assoc()) {
                        $selected = ($rowClient['companyID'] == $row_cmp['id']) ? 'selected' : '';
                        echo '<option ' . $selected . ' value="' . $row_cmp['id'] . '">' . $row_cmp['name'] . '</option>';
                    }
                    ?>
                </select>
			</div>
		</div>
		<hr>
    	<div class="row checkbox">
			<div class="col-xs-2 text-right"><?php echo $lang['ADDRESS_FORM']; ?></div>
			<div class="col-sm-2">
				<label><input type="radio" value="male" name="gender" <?php if ($row['gender'] == 'male') {echo 'checked';}?> /> <?php echo $lang['GENDER_TOSTRING']['male']; ?></label>
			</div>
			<div class="col-sm-2">
				<label><input type="radio" value="female" name="gender" <?php if ($row['gender'] == 'female') {echo 'checked';}?> /> <?php echo $lang['GENDER_TOSTRING']['female']; ?></label>
			</div>
			<div class="col-sm-3">
				<label><input type="checkbox" value="company" name="contactType" <?php if ($row['contactType'] == 'company') {echo 'checked';}?> /> <?php echo $lang['COMPANY_2']; ?></label>
			</div>
		</div>
		<br>
		<div class="row form-group">
			<div class="col-xs-2 text-right">Name</div>
			<div class="col-sm-2">
				<input type="text" class="form-control" name="title" value="<?php echo $row['title']; ?>" placeholder="Title"/>
			</div>
			<div class="col-sm-3">
				<input type="text" class="form-control" name="firstname" value="<?php echo $row['firstname']; ?>" placeholder="<?php echo $lang['FIRSTNAME']; ?>" />
			</div>
			<div class="col-sm-3">
				<input type="text" class="form-control" name="name" value="<?php echo $row['name']; ?>" placeholder="<?php echo $lang['LASTNAME']; ?>" />
				</div>
			<div class="col-sm-2">
				<input type="text" class="form-control" name="nameAddition" value="<?php echo $row['nameAddition']; ?>" placeholder="Addition <?php if (!isset($_SESSION['language']) || $_SESSION['language'] == 'GER') echo "/Zusatz"; ?>"/>
			</div>
		</div>
		<div class="row form-group">
			<div class="col-xs-2 text-right">Anschrift</div>
			<div class="col-sm-5">
			<input type="text" class="form-control" name="address_Street" value="<?php echo $row['address_Street']; ?>" placeholder="<?php echo $lang['STREET']; ?>"/>
		</div>
			<div class="col-sm-5">
			<input type="text" class="form-control" name="address_Country" value="<?php echo $row['address_Country']; ?>" placeholder="<?php echo $lang['COUNTRY']; ?>"/>
			</div>
		</div>
		<div class="row form-group">
			<div class="col-sm-2"></div>
			<div class="col-sm-3">
				<input type="text" class="form-control" name="address_Country_City" value="<?php echo $row['address_Country_City']; ?>" placeholder="<?php echo $lang['CITY']; ?>" />
			</div>
			<div class="col-sm-2">
			<input type="text" class="form-control" name="address_Country_Postal" value="<?php echo $row['address_Country_Postal']; ?>" placeholder="<?php echo $lang['PLZ']; ?>" />
		</div>
		<div class="col-sm-5">
			<input type="text" class="form-control" name="address_Addition" value="<?php echo $row['address_Addition']; ?>" placeholder="<?php echo $lang['ADDITION']; ?>"/>
		</div>
		</div>
		<div class="row form-group">
			<div class="col-xs-2 text-right">Kontakt</div>
			<div class="col-sm-5">
				<input type="text" class="form-control" name="phone" value="<?php echo $row['phone']; ?>" placeholder="<?php echo $lang['PHONE_NUMBER']; ?>" />
			</div>
			<div class="col-sm-5">
				<input type="text" class="form-control" name="fax_number" value="<?php echo $row['fax_number']; ?>"  placeholder="Fax" />
			</div>
		</div>
		<div class="row form-group">
			<div class="col-xs-2 text-right">Webseite</div>
			<div class="col-sm-5">
				<input type="text" class="form-control" name="homepage" value="<?php echo $row['homepage']; ?>" placeholder="Homepage" />
			</div>
		</div>
		<div class="row form-group">
			<div class="col-xs-2 text-right">E-Mail</div>
			<div class="col-sm-5">
				<input type="email" class="form-control" name="mail" value="<?php echo $row['mail']; ?>" placeholder="E-Mail" />
			</div>
		</div>
		<br><br>
		<div class="row form-group">
            <div class="col-xs-2 text-right">Ansprechpartner</div>
            <div class="col-sm-10">
                <table class="table">
                    <thead><tr>
                        <th><?php echo $lang['FORM_OF_ADDRESS'] ?></th>
                        <th>Titel</th>
                        <th>Name</th>
                        <th>E-Mail</th>
                        <th>Position</th>
                        <th>Verantwortung</th>
                        <th>Durchwahl</th>
                        <th>Faxdurchwahl</th>
                        <th>Mobiltelefon</th>
                        <th></th>
                    </tr></thead>
                    <tbody>
                        <?php
                        $editmodals = '';
                        while ($contactRow = $resultContacts->fetch_assoc()) {
                            echo '<tr>';
                            echo '<td>' . $lang['GENDER_TOSTRING'][$contactRow['gender']]. '</td>';
                            echo '<td>' . $contactRow['title'] . '</td>';
                            echo '<td>' . $contactRow['firstname'] . ' ' . $contactRow['lastname'] . '</td>';
                            echo '<td>' . $contactRow['email'] . '</td>';
                            echo '<td>' . $contactRow['positionName'] . '</td>';
                            echo '<td>' . $contactRow['responsibility'] . '</td>';
                            echo '<td>' . $contactRow['dial'] . '</td>';
                            echo '<td>' . $contactRow['faxDial'] . '</td>';
                            echo '<td>' . $contactRow['phone'] . '</td>';
                            echo '<td><button type="submit" name="deleteContact" value="' . $contactRow['id'] . '" class="btn btn-default"><i class="fa fa-trash-o"></i></button>';
                            echo '<button type="button" class="btn btn-default" data-toggle="modal" data-target="#edit-contact-' . $contactRow['id'] . '" class="btn btn-default"><i class="fa fa-pencil"></i></button></td>';
                            echo '</tr>';
                        }
                        ?>
					</tbody>
				</table>
				<button type="button" class="btn btn-default" data-toggle="modal" data-target="#add-contact-person" title="<?php echo $lang['ADD']; ?>" ><i class="fa fa-plus"></i></button>
			</div>
		</div>
	</div>
	<div id="menuTaxes" class="tab-pane fade <?php if ($activeTab == 'taxes') {echo 'in active';}?>">
		<div class="row checkbox">
			<div class="col-sm-9"> <h3>Steuerinformationen</h3> </div>
			<br>
			<div class="col-sm-3">
			<input type="checkbox" name="blockDelivery" <?php if ($row['blockDelivery'] == 'true') {echo 'checked';}?> /> Liefersperre
		</div>
	</div>
		<hr>
		<div class="row form-group">
			<div class="col-xs-2 text-right"><?php if (isset($_GET['supID'])) {echo "Credit Nr.";} else {echo "Debit Nr.";}?></div>
			<div class="col-sm-4"><input type="number" class="form-control" name="debitNumber" value="<?php echo $row['debitNumber']; ?>" /></div>
			<div class="col-xs-2 text-center">
				DATEV
			</div>
			<div class="col-sm-4"><input type="number" class="form-control" name="datev" value="<?php echo $row['datev']; ?>" /></div>
		</div>
		<div class="row form-group">
			<div class="col-xs-2 text-right">
				Kontobezeichnung
			</div>
			<div class="col-sm-10">
				<input type="text" class="form-control" name="accountName" value="<?php echo $row['accountName']; ?>" />
			</div>
		</div>
		<hr>
		<div class="row form-group">
			<div class="col-xs-2 text-right">
				Steuernummer
			</div>
			<div class="col-sm-4">
				<input type="text" class="form-control" name="taxnumber" value="<?php echo $row['taxnumber']; ?>" />
			</div>
			<div class="col-xs-2 text-center">
				UID
			</div>
			<div class="col-sm-3">
				<div class="input-group">
					<input type="text" id="uidNumber" class="form-control" name="vatnumber" value="<?php echo $row['vatnumber']; ?>" />
					<span class="input-group-btn">
						<button id="uidCheck" class="btn btn-default" type="button">Überprüfen</button>
					</span>
				</div>
			</div>
			<div class="col-sm-1">
				<span id="uidValidate" ></span>
			</div>
		</div>
		<div class="row form-group">
			<div class="col-xs-2 text-right">
				Steuergebiet
			</div>
			<div class="col-sm-4">
				<input type="text" class="form-control" name="taxArea" value="<?php echo $row['taxArea']; ?>" />
			</div>
			<div class="col-xs-2 text-center">
				Kundengruppe
			</div>
			<div class="col-sm-4">
				<input type="text" class="form-control" name="customerGroup" value="<?php echo $row['customerGroup']; ?>" />
			</div>
		</div>
		<div class="row form-group">
			<div class="col-xs-2 text-right">
				Vertreter
			</div>
			<div class="col-sm-4">
                <select class="js-example-basic-single" name="representative">
                    <option value="0">...</option>
                    <?php
                    $result_con = $conn->query("SELECT * FROM representatives");
                    while ($row_con = $result_con->fetch_assoc()) {
                        $selected = ($row['representative'] == $row_con['name']) ? 'selected' : '';
                        echo '<option ' . $selected . ' value="' . $row_con['name'] . '">' . $row_con['name'] . '</option>';
                    }
                    $result_con = $conn->query("SELECT firstname, lastname FROM UserData WHERE id IN (" . implode(', ', $available_users) . ")");
                    while ($row_con = $result_con->fetch_assoc()) {
                        $selected = ($row['representative'] == $row_con['firstname'] . ' ' . $row_con['lastname']) ? 'selected' : '';
                        echo '<option ' . $selected . ' value="' . $row_con['firstname'] . ' ' . $row_con['lastname'] . '">' . $row_con['firstname'] . ' ' . $row_con['lastname'] . '</option>';
                    }
                    ?>
                </select>
			</div>
		</div>
	</div>

	<div id="menuBank" class="tab-pane fade <?php if ($activeTab == 'banking') {echo 'in active';}?>">
		<div class="row form-group">
			<h3><div class="col-sm-9">Bankdaten</div></h3>
		</div>
		<hr>
		<table class="table table-hover">
			<thead>
				<th>Bankname</th>
				<th>Iban</th>
				<th>BIC</th>
				<th></th>
			</thead>
			<tbody>
                <?php
                $modals = '';
                while ($resultBank && ($rowBank = $resultBank->fetch_assoc())) {
                    $mc = new MasterCrypt($_SESSION["masterpassword"], $rowBank['iv'], $rowBank['iv2']);
                    echo '<tr>';
                    echo '<td>' . $mc->getStatus() . $mc->decrypt($rowBank['bankName']) . '</td>';
                    echo '<td>' . $mc->getStatus() . $mc->decrypt($rowBank['iban']) . '</td>';
                    echo '<td>' . $mc->getStatus() . $mc->decrypt($rowBank['bic']) . '</td>';
                    echo '<td><button type="submit" class="btn btn-default" name="removeBank" value="' . $rowBank['id'] . '" title="' . $lang['DELETE'] . '" ><i class="fa fa-trash-o"></i></button>
                    <button type="button" class="btn btn-default" data-toggle="modal" data-target=".edit-bank-' . $rowBank['id'] . '" title="' . $lang['EDIT'] . '" ><i class="fa fa-pencil"></i></button></td>';
                    echo '</tr>';

                    $modals .= '<div class="modal fade edit-bank-' . $rowBank['id'] . '"><div class="modal-dialog modal-content modal-sm">
                    <div class="modal-header h4">' . $lang['EDIT'] . '</div><div class="modal-body"><div class="container-fluid">
                    <label><label>Bankname ' . mc_status() . '</label></label><input type="text" class="form-control" name="edit_bankName" value="' . $mc->decrypt($rowBank['bankName']) . '" /><br>
                    <label><label>IBAN ' . mc_status() . '</label></label><input type="text" class="form-control" name="edit_iban" value="' . $mc->decrypt($rowBank['iban']) . '" /><br>
                    <label><label>BIC ' . mc_status() . '</label></label><input type="text" class="form-control" name="edit_bic" value="' . $mc->decrypt($rowBank['bic']) . '" /><br>
                    </div></div><div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning" name="editBankingDetail" value="' . $rowBank['id'] . '" >' . $lang['SAVE'] . '</button>
                    </div></div></div>';
                }
                ?>
			</tbody>
		</table>
		<?php echo $modals; ?>
		<br><br><br>
		<div class="container">
			<div class="col-md-3">
				<label>Bankname <?php echo mc_status(); ?></label>
				<input type="text" class="form-control" name="bankName" placeholder="Name der Bank" />
			</div>
			<div class="col-md-4">
				<label>IBAN <?php echo mc_status(); ?></label>
				<input type="text" class="form-control" name="iban" placeholder="Iban" />
			</div>
			<div class="col-md-3">
				<label>BIC <?php echo mc_status(); ?></label>
				<input type="text" class="form-control" name="bic" placeholder="BIC" />
			</div>
			<div class="col-md-2">
				<label style="color:transparent">+</label>
				<button type="submit" class="btn btn-warning btn-block" name="addBankingDetail"><?php echo $lang['ADD']; ?></button>
			</div>
		</div>
	</div>

	<div id="menuBilling" class="tab-pane fade <?php if ($activeTab == 'billing') {echo 'in active';}?>">
		<div class="row checkbox">
			<div class="col-sm-9">
				<h3>Rechnungsdaten</h3>
			</div>
			<br>
			<div class="col-sm-3">
				<input type="checkbox" name="eBill" <?php if ($row['eBill'] == 'true') {echo 'checked';}?> />
				E-Rechnung
			</div>
		</div>
		<hr>
		<div class="row form-group">
			<div class="col-xs-2 text-right">
				Kreditlimit
			</div>
			<div class="col-sm-3">
				<input type="number" step="any" class="form-control" name="creditLimit" value="<?php echo $row['creditLimit']; ?>" />
			</div>
			<div class="col-xs-3 text-center">
				Letzte Faktura Buchung
			</div>
			<div class="col-sm-3">
				<input type="text" class="form-control datetimepicker" name="lastFaktura" value="<?php echo $row['lastFaktura']; ?>" />
			</div>
		</div>
		<div class="row form-group">
			<div class="col-xs-2 text-right">
				Versandart
			</div>
			<div class="col-sm-3">
                <select class="js-example-basic-single" name="shipmentType">
                    <option value="0">...</option>
                    <?php
                    $result_con = $conn->query("SELECT * FROM shippingMethods");
                    while ($row_con = $result_con->fetch_assoc()) {
                        $selected = '';
                        if ($row['shipmentType'] == $row_con['name']) {
                            $selected = 'selected';
                        }
                        echo '<option ' . $selected . ' value="' . $row_con['name'] . '">' . $row_con['name'] . '</option>';
                    }
                    ?>
                </select>
			</div>
		</div>
	<br><hr><br>
		<div class="row form-group">
			<div class="col-xs-2 text-right">
				Rechnungsadresse (E-Mail)
			</div>
			<div class="col-sm-3">
				<input type="email" name="billingMailAddress" class="form-control" value="<?php echo $row['billingMailAddress']; ?>" />
			</div>
			<div class="col-xs-2 text-center">
				Rechnungsversand
			</div>
            <div class="col-sm-3">
                <select name="billDelivery" class="js-example-basic-single">
                    <option <?php if ($row['billDelivery'] == 'Fax') { echo 'selected'; } ?> value="Fax">Fax</option>
                    <option <?php if ($row['billDelivery'] == 'Post') { echo 'selected'; } ?> value="Post">Post</option>
                    <option <?php if ($row['billDelivery'] == 'E-Mail') { echo 'selected'; } ?> value="E-Mail">E-Mail</option>
				</select>
			</div>
		</div>
	</div>

	<div id="menuPayment" class="tab-pane fade <?php if ($activeTab == 'payment') {echo 'in active';}?>">
		<h3>Zahlungsdaten</h3>
		<hr>
		<div class="row form-group">
			<div class="col-xs-2 text-right">
				Zahlungsweise
			</div>
			<div class="col-sm-5">
				<select class="js-example-basic-single" name="paymentMethod">
					<option value="0">...</option>
                    <?php
                    $result_con = $conn->query("SELECT * FROM paymentMethods");
                    while ($row_con = $result_con->fetch_assoc()) {
                        $selected = '';
                        if ($row['paymentMethod'] == $row_con['name']) {
                            $selected = 'selected';
                        }
                        echo '<option ' . $selected . ' value="' . $row_con['name'] . '">' . $row_con['name'] . '</option>';
                    }
                    ?>
				</select>
			</div>
			<div class="col-sm-3"><a href="../erp/payment" class="btn btn-warning">Zahlungsarten verwalten</a></div>
		</div>
		<hr>
		<div class="row form-group">
			<div class="col-xs-2 text-right">
				Mahnungen erlaubt
			</div>
			<div class="col-sm-9">
				<input type="checkbox" name="warningEnabled" <?php if ($row['warningEnabled'] == 'true') {echo 'checked';}?> />
			</div>
		</div>
		<br>
		<div class="row form-group">
			<div class="col-xs-2 text-right">
				Karenztage
			</div>
			<div class="col-sm-3">
				<input type="number" class="form-control" name="karenztage" value="<?php echo $row['karenztage']; ?>" />
			</div>
			<div class="col-xs-2 text-center">
				Letzte Mahnung:
			</div>
			<div class="col-sm-3">
				<?php echo $row['lastWarning'] ? $row['lastWarning'] : '-- --'; ?>
			</div>
		</div>
		<div class="row form-group">
			<div class="col-xs-2 text-right">
				Mahnung 1
			</div>
			<div class="col-sm-3">
				<input type="number" step="0.01" class="form-control" name="warning1" placeholder="Mahn-Betrag in EUR" value="<?php echo $row['warning1']; ?>" />
			</div>
		</div>
		<div class="row form-group">
			<div class="col-xs-2 text-right">
				Mahnung 2
			</div>
			<div class="col-sm-3">
				<input type="number" step="0.01" class="form-control" name="warning2" placeholder="Mahn-Betrag in EUR" value="<?php echo $row['warning2']; ?>" />
			</div>
		</div>
		<div class="row form-group">
			<div class="col-xs-2 text-right">
				Mahnung 3
			</div>
			<div class="col-sm-3">
				<input type="number" step="0.01" class="form-control" name="warning3" placeholder="Mahn-Betrag in EUR" value="<?php echo $row['warning3']; ?>" />
			</div>
		</div>
		<hr>
		<div class="row form-group">
			<div class="col-xs-2 text-right">
				Verzugszinsberechnung
			</div>
			<div class="col-md-1">
				<input type="checkbox" name="calculateInterest" <?php if ($row['calculateInterest'] == 'true') {echo 'checked';}?>/>
			</div>
		</div>
	</div>

	<div id="menuContact" class="tab-pane fade <?php if ($activeTab == 'notes') {echo 'in active';}?>">
		<h3>Bemerkungen</h3>
		<hr>
		<table class="table table-hover">
			<thead>
				<th>Löschen</th>
				<th>Datum</th>
				<th style="width:75%">Info</th>
			</thead>
            <tbody>
                <?php
                while ($resultNotes && ($rowNotes = $resultNotes->fetch_assoc())) {
                    echo "<tr><td><input type='checkbox' name='noteIndeces[]' /></td>";
                    echo "<td>" . $rowNotes['createDate'] . "</td>";
                    echo "<td>" . $rowNotes['infoText'] . "</td></tr>";
                }
                ?>
            </tbody>
		</table>
		<div class="container-fluid">
			<br><br> Neue Notiz Hinzufügen: <br><br>
			<textarea class="form-control" rows="3" name="infoText" placeholder="Info..."></textarea>
		</div>
		<br>
		<div class="container-fluid text-right">
			<button type="submit" class="btn btn-warning" name="addNotes">Hinzufügen</button> <button type="submit" class="btn btn-danger" name="deleteNotes">Löschen</button>
		</div>
	</div>

	<div id="menuDocs" class="tab-pane fade <?php if ($activeTab == 'docs') {echo 'in active';}?>">
		<h3>Vertrauliche Dokumente</h3>
		<hr>
		<table class="table table-hover">
		<thead><tr>
		<th>Ansprechpartner</th>
		<th>Dokument</th>
		<th>Passwort</th>
		<th></th>
		<th></th>
		</tr></thead>
        <tbody>
            <?php
            $resultProc = $conn->query("SELECT name, password, firstname, lastname, documentProcess.id FROM documents, documentProcess, contactPersons
                WHERE clientID = $filterClient AND personID = contactPersons.id AND documentProcess.docID = documents.id");
                echo $conn->error;
                while ($rowProc = $resultProc->fetch_assoc()) {
                    echo '<tr style="background-color:#dedede">';
                    echo '<td>' . $rowProc['firstname'] . ' ' . $rowProc['lastname'] . '</td>';
                    echo '<td>' . $rowProc['name'] . '</td>';
                    echo $rowProc['password'] ? '<td>Ja</td>' : '<td>Nein</td>';
                    echo '<td></td>';
                    echo '<td></td>';
                    echo '</tr>';

                    $processHistory = array();
                    $result = $conn->query("SELECT activity, info, userAgent, logDate FROM documentProcessHistory WHERE processID = '" . $rowProc['id'] . "'");
                    while ($row = $result->fetch_assoc()) {
                        if (isset($processHistory[$row['activity']])) {
                            $processHistory[$row['activity']]['count'] += 1;
                        } else {
                            $processHistory[$row['activity']]['info'] = $row['info'];
                            $processHistory[$row['activity']]['userAgent'] = $row['userAgent'];
                            $processHistory[$row['activity']]['logDate'] = $row['logDate'];
                            $processHistory[$row['activity']]['count'] = 1;
                        }
                    }
                    if (isset($processHistory['ENABLE_READ'])) {
                        $val_stat = 'Ausstehend';
                        $val_date = $val_head = '';
                        if (isset($processHistory['action_read'])) {
                            $val_stat = 'Ja';
                            $val_date = $processHistory['ENABLE_READ']['logDate'];
                            $val_head = $processHistory['ENABLE_READ']['userAgent'];
                        }
                        echo '<tr><td></td> <td>Als Gelesen markiert</td> <td>' . $val_stat . '</td> <td>' . $val_date . '</td> <td>' . $val_head . '</td></tr>';
                    }
                    if (isset($processHistory['ENABLE_SIGN'])) {
                        $val_stat = 'Ausstehend';
                        $val_date = $val_head = '';
                        if (isset($processHistory['action_sign'])) {
                            $val_stat = $processHistory['action_sign']['info'];
                            $val_date = $processHistory['action_sign']['logDate'];
                            $val_head = $processHistory['action_sign']['userAgent'];
                        }
                        echo '<tr><td></td> <td>Unterschrieben</td> <td>' . $val_stat . '</td> <td>' . $val_date . '</td> <td>' . $val_head . '</td></tr>';
                    }
                    if (isset($processHistory['ENABLE_ACCEPT'])) {
                        $val_stat = 'Ausstehend';
                        $val_date = $val_head = '';
                        if (isset($processHistory['action_accept'])) {
                            $val_stat = $processHistory['action_accept'] == 'DECLINED' ? 'Nein' : 'Ja';
                            $val_date = $processHistory['action_accept']['logDate'];
                            $val_head = $processHistory['action_accept']['userAgent'];
                        }
                        echo '<tr><td></td> <td>Akzeptiert</td> <td>' . $val_stat . '</td> <td>' . $val_date . '</td> <td>' . $val_head . '</td></tr>';
                    }
                    if ($rowProc['password'] && isset($processHistory['password_denied'])) {
                        $val_stat = $processHistory['password_denied']['count'];
                        $val_date = $processHistory['password_denied']['logDate'];
                        $val_head = $processHistory['password_denied']['userAgent'];

                        echo '<tr><td></td> <td>Passwort Falscheingaben</td> <td>' . intval($val_stat) . 'x Mal</td> <td>' . $val_date . '</td> <td>' . $val_head . '</td></tr>';
                    }
                }
                ?>
            </tbody>
		</table>
    </div>
</div>

<!-- ADD CONTACT PERSON -->
<div id="add-contact-person" class="modal fade">
    <div class="modal-dialog modal-content modal-md">
        <div class="modal-header h4">Ansprechpartner Hinzufügen</div>
        <div class="modal-body">
            <div class="row form-group">
                <div class="col-md-6"><label>Vorname</label><input type="text" name="contacts_firstname" placeholder="Vorname" class="form-control required-field"/></div>
                <div class="col-md-6"><label>Nachname</label><input type="text" name="contacts_lastname" placeholder="Nachname" class="form-control required-field"/></div>
            </div>
            <div class="row form-group">
                <div class="col-md-6"><label><?php echo $lang['FORM_OF_ADDRESS'] ?></label><select name="contacts_gender" class="js-example-basic-single required-field">
                    <option value="male" >Herr</option>
                    <option value="female" >Frau</option>
                </select></div>
                <div class="col-md-6"><label>Titel</label><input type="text" name="contacts_titel" class="form-control "/></div>
            </div>
            <div class="row form-group">
                <div class="col-md-4"><label>E-Mail</label><input type="email" name="contacts_email" placeholder="E-Mail" class="form-control required-field"/></div>
                <div class="col-md-4"><label>Position</label><select type="text" name="contacts_position" placeholder="Position" class="js-example-basic-single select2-position">
                    <?php
                    $result = $conn->query("SELECT * FROM position ORDER BY name");
                    while($row = $result->fetch_assoc()){
                        echo '<option value="'.$row['id'].'" >'.$row['name'].'</option>';
                    }
                    echo '<option value="-1" >+ Neu...</option>';
                    ?>
                </select></div>
                <div class="col-md-4"><label>Verantwortung</label><input type="text" name="contacts_responsibility" placeholder="Verantwortung" class="form-control"/></div>
            </div>
            <div class="row form-group">
                <div class="col-md-4"><label>Durchwahl</label><input type="text" name="contacts_dial" placeholder="Direct Dial" class="form-control"/></div>
                <div class="col-md-4"><label>Faxdurchwahl</label><input type="text" name="contacts_faxDial" placeholder="Direct Fax Dial" class="form-control"/></div>
                <div class="col-md-4"><label>Mobiltelefon</label><input type="text" name="contacts_phone" placeholder="Mobile Phone" class="form-control"/></div>
            </div>
            <div class="row form-group">
                <div class="col-md-12"><label>PGP-Key</label><textarea class="form-control" name="contacts_pgp" placeholder="Put PGP Key here..." ></textarea></div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
            <button type="submit" name="addContact" class="btn btn-warning"><?php echo $lang['SAVE']; ?></button>
            <button id="newPositionModal" type="button" data-target="new-position" data-toggle="modal" style="visibility:hidden; width:1px; height:1px;" ></button>
        </div>
    </div>
</div>
<!-- ADD PROJECT -->
<div class="modal fade add-project">
    <div class="modal-dialog modal-content modal-md">
        <div class="modal-header"><h4><?php echo $lang['ADD']; ?></h4></div>
        <div class="modal-body">
            <label>Name</label>
            <input type=text class="form-control required-field" name='name' placeholder='Name'>
            <br>
            <div class="row">
                <div class="col-md-6">
                    <label><?php echo $lang['HOURS']; ?></label>
                    <input type="number" class="form-control" name='hours' step="any">
                </div>
                <div class="col-md-6">
                    <label><?php echo $lang['HOURLY_RATE']; ?></label>
                    <input type="number" class="form-control" name='hourlyPrice' step="any">
                </div>
            </div>
            <br>
            <div class="container-fluid">
                <div class="col-md-6 checkbox">
                    <input type="checkbox" name="status" value="checked" checked> <i class="fa fa-tags"></i> <?php echo $lang['PRODUCTIVE']; ?>
                </div>
                <div class="col-md-6">
                    <div class="checkbox">
                        <?php
                        $resF = $conn->query("SELECT * FROM $companyExtraFieldsTable WHERE companyID = " . $rowClient['companyID'] . " ORDER BY id ASC");
                        if ($resF->num_rows > 0) {
                            $rowF = $resF->fetch_assoc();
                            if ($rowF['isActive'] == 'TRUE') {
                                $checked = $rowF['isForAllProjects'] == 'TRUE' ? 'checked' : '';
                                echo '<input type="checkbox" ' . $checked . ' name="createField_1"/>' . $rowF['name'];
                            }
                        }
                        if ($resF->num_rows > 1) {
                            $rowF = $resF->fetch_assoc();
                            if ($rowF['isActive'] == 'TRUE') {
                                $checked = $rowF['isForAllProjects'] == 'TRUE' ? 'checked' : '';
                                echo '<br><input type="checkbox" ' . $checked . ' name="createField_2" />' . $rowF['name'];
                            }
                        }
                        if ($resF->num_rows > 2) {
                            $rowF = $resF->fetch_assoc();
                            if ($rowF['isActive'] == 'TRUE') {
                                $checked = $rowF['isForAllProjects'] == 'TRUE' ? 'checked' : '';
                                echo '<br><input type="checkbox" ' . $checked . ' name="createField_3" />' . $rowF['name'];
                            }
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-warning" name='add'> <?php echo $lang['ADD']; ?> </button>
        </div>
    </div>
</div>
</form>

<?php
mysqli_data_seek($result_p, 0);
while ($row = $result_p->fetch_assoc()):
    $x = $row['id'];
    ?>
	  <!-- Edit projects (time only) -->
	  <form method="post">
	    <div class="modal fade editingProjectsModal-<?php echo $x ?>">
	      <div class="modal-dialog modal-md modal-content" role="document">
	        <div class="modal-header">
	          <h4><?php echo $row['name']; ?></h4>
	        </div>
	        <div class="modal-body">
	          <div class="row">
	            <div class="col-md-6">
	              <label><?php echo $lang['HOURS']; ?></label>
	              <input type="number" class="form-control" step="any" name="boughtHours" value="<?php echo $row['hours']; ?>">
	            </div>
	            <div class="col-md-6">
	              <label><?php echo $lang['HOURLY_RATE']; ?></label>
	              <input type="number" class="form-control" step="any" name="pricedHours" value="<?php echo $row['hourlyPrice']; ?>">
	            </div>
	          </div>
	          <div class="row checkbox">
	            <div class="col-md-6">
	              <label><input type="checkbox" name="productive" <?php echo $row['status']; ?> /><?php echo $lang['PRODUCTIVE']; ?></label>
	            </div>
                <div class="col-md-6"><br>
                    <?php
                    $resF = $conn->query("SELECT * FROM $companyExtraFieldsTable WHERE companyID = " . $rowClient['companyID'] . " ORDER BY id ASC");
                    if ($resF->num_rows > 0) {
                        $rowF = $resF->fetch_assoc();
                        if ($rowF['isActive'] == 'TRUE') {
                            $checked = $row['field_1'] == 'TRUE' ? 'checked' : '';
                            echo '<label><input type="checkbox" ' . $checked . ' name="addField_1_' . $row['id'] . '"/> ' . $rowF['name'] . '</label>';
                        }
                    }
                    if ($resF->num_rows > 1) {
                        $rowF = $resF->fetch_assoc();
                        if ($rowF['isActive'] == 'TRUE') {
                            $checked = $row['field_2'] == 'TRUE' ? 'checked' : '';
                            echo '<br><label><input type="checkbox" ' . $checked . ' name="addField_2_' . $row['id'] . '" /> ' . $rowF['name'] . '</label>';
                        }
                    }
                    if ($resF->num_rows > 2) {
                        $rowF = $resF->fetch_assoc();
                        if ($rowF['isActive'] == 'TRUE') {
                            $checked = $row['field_3'] == 'TRUE' ? 'checked' : '';
                            echo '<br><label><input type="checkbox" ' . $checked . ' name="addField_3_' . $row['id'] . '" /> ' . $rowF['name'] . '</label>';
                        }
                    }
                    ?>
                </div>
	          </div>
	        </div>
	        <div class="modal-footer">
	          <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
	          <button type="submit" class="btn btn-warning" name="save" value="<?php echo $x ?>"><?php echo $lang['SAVE']; ?></button>
	        </div>
	      </div>
	    </div>
	  </form>
	<?php endwhile;?>

<div id="new-position" class="modal fade">
    <div class="modal-dialog modal-content modal-sm">
        <div class="modal-header h4">Position Hinzufügen</div>
        <div class="modal-body">
            <label>Name</label>
            <input type="text" id="positionName" class="form-control" />
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
            <button type="button" onClick="addPosition()" class="btn btn-warning"><?php echo $lang['SAVE']; ?></button>
        </div>
    </div>
</div>

<script>
$('#uidCheck').click(function(e){
	$.ajax({
    url: 'ajaxQuery/AJAX_vies.php',
    data: { vatNumber : $('#uidNumber').val() },
    type: 'get',
    success : function(resp){
		if(resp){
			$("#uidValidate").html('<img width="25px" height="25px" src="images/okay.png" /> ' + resp);
		} else {
			$("#uidValidate").html('<img width="25px" height="25px" src="images/not_okay.png" />');
		} //of equal weight. save them,
    },
    error : function(resp){}
  });
});
$(".select2-position").on("select2:select", function (e) {
    if(e.params.data.id == -1){
        $("#new-position").modal("show");
    }
});
function addPosition(){
    var name = $("#positionName").val();
    if(name!=""){
        $.post("ajaxQuery/AJAX_db_utility.php",{
            name: name,
            function: "addPosition",
        },function(data){
            data = JSON.parse(data);
            var newOption = new Option(data.name,data.id,false,true);
            $(".select2-position").append(newOption).trigger('change');
            $("#new-position").modal("hide");
        });
    } else {
        $("#positionName").focus();
    }
}
</script>
<?php include dirname(dirname(__DIR__)) . '/footer.php';?>
