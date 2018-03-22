<?php
isset($canEditSuppliers, $canEditClients) or die ("no permission (you need canEditSuppliers or canEditClients)");
if(!($canEditSuppliers == 'TRUE' || $canEditClients == 'TRUE')){
    echo "no permission (you need canEditSuppliers or canEditClients)";
    include dirname(dirname(__DIR__)) . '/footer.php';
    die();
}
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
        $bankName = $_POST['bankName'];
        $ibanVal = $_POST['iban'];
        $bicVal = $_POST['bic'];
        $stmt = $conn->prepare("INSERT INTO clientInfoBank (bankName, iban, bic, parentID) VALUES (?, ?, ?, ?)");
        $stmt->bind_param('sssi', $bankName, $ibanVal, $bicVal, $detailID);
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
        $bankName = $_POST['edit_bankName'];
        $ibanVal = $_POST['edit_iban'];
        $bicVal = $_POST['edit_bic'];
        $val = intval($_POST['editBankingDetail']);
        $stmt = $conn->prepare("UPDATE clientInfoBank SET bankName = ?, iban = ?, bic = ? WHERE id = ?");
        $stmt->bind_param('sssi', $bankName, $ibanVal, $bicVal, $val);
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
        $title = test_input($_POST['contacts_title']);
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
        $title = test_input($_POST['edit_contacts_title']);
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
?>
