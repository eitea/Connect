<?php include "header.php"; require_once 'utilities.php'; enableToClients($userID); ?>
<?php
if(isset($_GET['custID']) && is_numeric($_GET['custID'])){
  $filterClient = intval($_GET['custID']);
} else {
  redirect("editCustomer.php");
}
//get corresponding id from detailTable
$result = $conn->query("SELECT * FROM $clientDetailTable WHERE clientId = $filterClient");
if($result && ($row = $result->fetch_assoc())){
  $detailID = $row['id'];
} else { //no detailTable found -> create one
  $conn->query("INSERT INTO $clientDetailTable (clientID) VALUES($filterClient)");
  $detailID = $conn->insert_id;
  echo mysqli_error($conn);
}

$activeTab = 'home';

if(!empty($_POST['saveAll'])){
  $activeTab = $_POST['saveAll'];
  if(isset($_POST['contactType'])){
    $val = $_POST['contactType'];
  } else { $val = ''; }
  $conn->query("UPDATE $clientDetailTable SET contactType = '$val' WHERE id = $detailID");

  if(isset($_POST['gender'])){
    $val = $_POST['gender'];
    $conn->query("UPDATE $clientDetailTable SET gender = '$val' WHERE id = $detailID");
  }
  if(isset($_POST['title'])){
    $val = test_input($_POST['title']);
    $conn->query("UPDATE $clientDetailTable SET title = '$val' WHERE id = $detailID");
  }
  if(isset($_POST['name'])){
    $val = test_input($_POST['name']);
    $conn->query("UPDATE $clientDetailTable SET name = '$val' WHERE id = $detailID");
  }
  if(isset($_POST['firstname'])){
    $val = test_input($_POST['firstname']);
    $conn->query("UPDATE $clientDetailTable SET firstname = '$val' WHERE id = $detailID");
  }
  if(isset($_POST['nameAddition'])){
    $val = test_input($_POST['nameAddition']);
    $conn->query("UPDATE $clientDetailTable SET nameAddition = '$val' WHERE id = $detailID");
  }
  if(isset($_POST['address_Street'])){
    $val = test_input($_POST['address_Street']);
    $conn->query("UPDATE $clientDetailTable SET address_Street = '$val' WHERE id = $detailID");
  }
  if(isset($_POST['address_Country'])){
    $val = test_input($_POST['address_Country']);
    $conn->query("UPDATE $clientDetailTable SET address_Country = '$val' WHERE id = $detailID");
  }
  if(isset($_POST['address_Country_City'])){
    $val = test_input($_POST['address_Country_City']);
    $conn->query("UPDATE $clientDetailTable SET address_Country_City = '$val' WHERE id = $detailID");
  }
  if(isset($_POST['address_Country_Postal'])){
    $val = test_input($_POST['address_Country_Postal']);
    $conn->query("UPDATE $clientDetailTable SET address_Country_Postal = '$val' WHERE id = $detailID");
  }
  if(isset($_POST['phone'])){
    $val = test_input($_POST['phone']);
    $conn->query("UPDATE $clientDetailTable SET phone = '$val' WHERE id = $detailID");
  }
  if(isset($_POST['fax_number'])){
    $val = test_input($_POST['fax_number']);
    $conn->query("UPDATE $clientDetailTable SET fax_number = '$val' WHERE id = $detailID");
  }
  if(isset($_POST['debitNumber'])){
    $val = intval($_POST['debitNumber']);
    $conn->query("UPDATE $clientDetailTable SET debitNumber = '$val' WHERE id = $detailID");
  }
  if(isset($_POST['datev'])){
    $val = intval($_POST['datev']);
    $conn->query("UPDATE $clientDetailTable SET datev = '$val' WHERE id = $detailID");
  }
  if(isset($_POST['accountName'])){
    $val = test_input($_POST['accountName']);
    $conn->query("UPDATE $clientDetailTable SET accountName = '$val' WHERE id = $detailID");
  }
  if(isset($_POST['taxnumber'])){
    $val = test_input($_POST['taxnumber']);
    $conn->query("UPDATE $clientDetailTable SET taxnumber = '$val' WHERE id = $detailID");
  }
  if(isset($_POST['vatnumber'])){
    $val = test_input($_POST['vatnumber']);
    $conn->query("UPDATE $clientDetailTable SET vatnumber = '$val' WHERE id = $detailID");
  }
  if(isset($_POST['taxArea'])){
    $val = test_input($_POST['taxArea']);
    $conn->query("UPDATE $clientDetailTable SET taxArea = '$val' WHERE id = $detailID");
  }
  if(isset($_POST['customerGroup'])){
    $val = test_input($_POST['customerGroup']);
    $conn->query("UPDATE $clientDetailTable SET customerGroup = '$val' WHERE id = $detailID");
  }
  if(isset($_POST['representative'])){
    $val = test_input($_POST['representative']);
    $conn->query("UPDATE $clientDetailTable SET representative = '$val' WHERE id = $detailID");
  }
  if(isset($_POST['blockDelivery'])){
    $conn->query("UPDATE $clientDetailTable SET blockDelivery = 'true' WHERE id = $detailID");
  } else {
    $conn->query("UPDATE $clientDetailTable SET blockDelivery = 'false' WHERE id = $detailID");
  }
  if(isset($_POST['paymentMethod'])){
    $val = test_input($_POST['paymentMethod']);
    $conn->query("UPDATE $clientDetailTable SET paymentMethod = '$val' WHERE id = $detailID");
  }
  if(isset($_POST['shipmentType'])){
    $val = test_input($_POST['shipmentType']);
    $conn->query("UPDATE $clientDetailTable SET shipmentType = '$val' WHERE id = $detailID");
  }
  if(isset($_POST['creditLimit'])){
    $val = floatval($_POST['creditLimit']);
    $conn->query("UPDATE $clientDetailTable SET creditLimit = '$val' WHERE id = $detailID");
  }
  if(isset($_POST['eBill'])){
    $conn->query("UPDATE $clientDetailTable SET eBill = 'true' WHERE id = $detailID");
  } else {
    $conn->query("UPDATE $clientDetailTable SET eBill = 'false' WHERE id = $detailID");
  }
  if(isset($_POST['lastFaktura']) && test_Date($_POST['lastFaktura'].':00')){
    $val = $_POST['lastFaktura'].':00';
    $conn->query("UPDATE $clientDetailTable SET lastFaktura = '$val' WHERE id = $detailID");
  }
  if(isset($_POST['daysNetto'])){
    $val = intval($_POST['daysNetto']);
    $conn->query("UPDATE $clientDetailTable SET daysNetto = '$val' WHERE id = $detailID");
  }
  if(isset($_POST['skonto1'])){
    $val = floatval($_POST['skonto1']);
    $conn->query("UPDATE $clientDetailTable SET skonto1 = '$val' WHERE id = $detailID");
  }
  if(isset($_POST['skonto2'])){
    $val = floatval($_POST['skonto2']);
    $conn->query("UPDATE $clientDetailTable SET skonto2 = '$val' WHERE id = $detailID");
  }
  if(isset($_POST['skonto1Days'])){
    $val = intval($_POST['skonto1Days']);
    $conn->query("UPDATE $clientDetailTable SET skonto1Days = '$val' WHERE id = $detailID");
  }
  if(isset($_POST['skonto2Days'])){
    $val = intval($_POST['skonto2Days']);
    $conn->query("UPDATE $clientDetailTable SET skonto2Days = '$val' WHERE id = $detailID");
  }
  if(isset($_POST['warningEnabled'])){
    $conn->query("UPDATE $clientDetailTable SET warningEnabled = 'true' WHERE id = $detailID");
  } else {
    $conn->query("UPDATE $clientDetailTable SET warningEnabled = 'false' WHERE id = $detailID");
  }
  if(isset($_POST['karenztage'])){
    $val = intval($_POST['karenztage']);
    $conn->query("UPDATE $clientDetailTable SET karenztage = '$val' WHERE id = $detailID");
  }
  if(isset($_POST['lastWarning']) && test_Date($_POST['lastWarning'].':00')){
    $val = $_POST['lastWarning'].':00';
    $conn->query("UPDATE $clientDetailTable SET lastFaktura = '$val' WHERE id = $detailID");
  }
  if(isset($_POST['warning1'])){
    $val = floatval($_POST['warning1']);
    $conn->query("UPDATE $clientDetailTable SET warning1 = '$val' WHERE id = $detailID");
  }
  if(isset($_POST['warning2'])){
    $val = floatval($_POST['warning2']);
    $conn->query("UPDATE $clientDetailTable SET warning2 = '$val' WHERE id = $detailID");
  }
  if(isset($_POST['warning3'])){
    $val = floatval($_POST['warning3']);
    $conn->query("UPDATE $clientDetailTable SET warning3 = '$val' WHERE id = $detailID");
  }
  if(isset($_POST['calculateInterest'])){
    $conn->query("UPDATE $clientDetailTable SET calculateInterest = 'true' WHERE id = $detailID");
  } else {
    $conn->query("UPDATE $clientDetailTable SET calculateInterest = 'false' WHERE id = $detailID");
  }
  if($conn->error){
    echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$conn->error.'</div>';
  } else {
    echo '<div class="alert alert-success"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$lang['OK_SAVE'].'</div>';
  }
} elseif($_SERVER['REQUEST_METHOD'] == 'POST'){
  if(isset($_POST['addNotes']) && !empty($_POST['infoText'])){
    $activeTab = 'notes';
    $val = test_input($_POST['infoText']);
    $conn->query("INSERT INTO $clientDetailNotesTable (infoText, createDate, parentID) VALUES ('$val', CURRENT_TIMESTAMP, $detailID)");
    if($conn->error){ echo $conn->error; } else { echo '<div class="alert alert-success"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$lang['OK_ADD'].'</div>'; }
  } elseif(isset($_POST['deleteNotes']) && !empty($_POST['noteIndeces'])){
    $activeTab = 'notes';
    foreach($_POST['noteIndeces'] as $i){
      $conn->query("DELETE FROM $clientDetailNotesTable WHERE id = $i");
    }
    if($conn->error){ echo $conn->error; } else { echo '<div class="alert alert-success"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$lang['OK_DELETE'].'</div>'; }
  } elseif(isset($_POST['addBankingDetail']) && !empty($_POST['bankName']) && !empty($_POST['iban']) && !empty($_POST['bic'])){
    $activeTab = 'banking';
    $mc = mc();
    $bankName = test_input($_POST['bankName']);
    $ibanVal = $mc->encrypt(test_input($_POST['iban']));
    $bicVal = $mc->encrypt(test_input($_POST['bic']));
    $iv = $mc->iv;
    $iv2 = $mc->iv2;
    $conn->query("INSERT INTO $clientDetailBankTable (bankName, iban, bic, iv, iv2, parentID) VALUES ('$bankName', '$ibanVal', '$bicVal', '$iv', '$iv2', $detailID)");
    if($conn->error){ echo $conn->error; } else { echo '<div class="alert alert-success"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$lang['OK_ADD'].'</div>'; }
  } elseif(isset($_POST['displayBank']) && isset($_POST['displayBankingDetailPass'])){
    $activeTab = 'banking';
    $result = $conn->query("SELECT masterPassword FROM $configTable");
    $row = $result->fetch_assoc();
    if(crypt($_POST['displayBankingDetailPass'], $row['masterPassword']) == $row['masterPassword'] && !empty($row['masterPassword'])){ //unlock
      $_SESSION['unlock'] = $_POST['displayBankingDetailPass']; //TODO: this is no good.
    } else {
      unset($_SESSION['unlock']);
    }
  } elseif(isset($_POST['delete_projects']) && !empty($_POST['delete_projects_index'])){
    $activeTab = 'project';
    foreach($_POST['delete_projects_index'] as $x){
      $x = intval($x);
      $conn->query("DELETE FROM $projectTable WHERE id = $x;");
    }
    if($conn->error){ echo $conn->error; } else { echo '<div class="alert alert-success"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$lang['OK_DELETE'].'</div>'; }
  } elseif(isset($_POST['add']) && !empty($_POST['name'])){
    $activeTab = 'project';
    $name = test_input($_POST['name']);
    $status = "";
    if(isset($_POST['status'])){
      $status = "checked";
    }
    $hourlyPrice = floatval(test_input($_POST['hourlyPrice']));
    $hours = floatval(test_input($_POST['hours']));
    if(isset($_POST['createField_1'])){ $field_1 = 'TRUE'; } else { $field_1 = 'FALSE'; }
    if(isset($_POST['createField_2'])){ $field_2 = 'TRUE'; } else { $field_2 = 'FALSE'; }
    if(isset($_POST['createField_3'])){ $field_3 = 'TRUE'; } else { $field_3 = 'FALSE'; }
    $conn->query("INSERT INTO $projectTable (clientID, name, status, hours, hourlyPrice, field_1, field_2, field_3) VALUES($filterClient, '$name', '$status', '$hours', '$hourlyPrice', '$field_1', '$field_2', '$field_3')");
    if($conn->error){ echo $conn->error; } else { echo '<div class="alert alert-success"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$lang['OK_ADD'].'</div>'; }
  } elseif(isset($_POST['save'])){
    $activeTab = 'project';
    $projectID = intval($_POST['save']);
    $hours = floatval(test_input($_POST['boughtHours']));
    $hourlyPrice = floatval(test_input($_POST['pricedHours']));
    $status = isset($_POST['productive']) ? 'checked' : '';
    //checkboxes are not set at all if they're not checked
    if(isset($_POST['addField_1_'.$projectID])){ $field_1 = 'TRUE'; } else { $field_1 = 'FALSE'; }
    if(isset($_POST['addField_2_'.$projectID])){ $field_2 = 'TRUE'; } else { $field_2 = 'FALSE'; }
    if(isset($_POST['addField_3_'.$projectID])){ $field_3 = 'TRUE'; } else { $field_3 = 'FALSE'; }

    $conn->query("UPDATE $projectTable SET hours = '$hours', hourlyPrice = '$hourlyPrice', status='$status', field_1 = '$field_1', field_2 = '$field_2', field_3 = '$field_3' WHERE id = $projectID");
    if($conn->error){ echo $conn->error; } else { echo '<div class="alert alert-success"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$lang['OK_SAVE'].'</div>'; }
  }
}

$result = $conn->query("SELECT name, companyID FROM $clientTable WHERE id = $filterClient");
$rowClient = $result->fetch_assoc();

$result = $conn->query("SELECT * FROM $clientDetailTable WHERE id = $detailID");
$row = $result->fetch_assoc();

$resultNotes = $conn->query("SELECT * FROM $clientDetailNotesTable WHERE parentID = $detailID");
$resultBank = $conn->query("SELECT * FROM $clientDetailBankTable WHERE parentID = $detailID");
?>

<div class="page-header">
  <h3><?php echo $lang['CLIENT'] .' - '.$rowClient['name']; ?>
    <div class="page-header-button-group">
      <button id="sav" type="submit" class="btn btn-default blinking" name="saveAll" value="home" title="<?php echo $lang['SAVE']; ?>" form="mainForm"><i class="fa fa-floppy-o"></i></button>
      <a href="editCustomers.php?custID=<?php echo $filterClient; ?>" title="<?php echo $lang['CLIENT']; ?>" class="btn btn-default"><i class="fa fa-briefcase"></i></a><br>
    </div>
  </h3>
</div>

<ul class="nav nav-tabs">
  <li <?php if($activeTab == 'project'){echo 'class="active"';}?>><a data-toggle="tab" href="#project" onclick="$('#sav').val('project');"><?php echo $lang['VIEW_PROJECTS']; ?></a></li>
  <li <?php if($activeTab == 'home'){echo 'class="active"';}?>><a data-toggle="tab" href="#home" onclick="$('#sav').val('home');"><?php echo $lang['DATA']; ?></a></li>
  <li <?php if($activeTab == 'taxes'){echo 'class="active"';}?>><a data-toggle="tab" href="#menuTaxes" onclick="$('#sav').val('taxes');"><?php echo $lang['TAXES']; ?></a></li>
  <li <?php if($activeTab == 'banking'){echo 'class="active"';}?>><a data-toggle="tab" href="#menuBank" onclick="$('#sav').val('banking');">Banking</a></li>
  <li <?php if($activeTab == 'billing'){echo 'class="active"';}?>><a data-toggle="tab" href="#menuBilling" onclick="$('#sav').val('billing');"><?php echo $lang['BILLING']; ?></a></li>
  <li <?php if($activeTab == 'payment'){echo 'class="active"';}?>><a data-toggle="tab" href="#menuPayment" onclick="$('#sav').val('payment');"><?php echo $lang['PAYMENT']; ?></a></li>
  <li <?php if($activeTab == 'notes'){echo 'class="active"';}?>><a data-toggle="tab" href="#menuContact" onclick="$('#sav').val('notes');"><?php echo $lang['NOTES']; ?></a></li>
</ul>
<form id="mainForm" method="POST">
  <div class="tab-content">
    <div id="project" class="tab-pane fade <?php if($activeTab == 'project'){echo 'in active';} ?>">
      <h3><?php echo $lang['VIEW_PROJECTS']; ?>
        <div class="page-header-button-group">
          <button type="submit" class="btn btn-default" name='delete_projects' title="<?php echo $lang['DELETE']; ?>" ><i class="fa fa-trash-o"></i></button>
          <button type="button" class="btn btn-default" title="<?php echo $lang['ADD']; ?>" data-toggle="modal" data-target=".add-project"><i class="fa fa-plus"></i></button>
        </div>
      </h3>
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
          while($row_p = $result_p->fetch_assoc()){
            $productive = $row_p['status'] ? '<i class="fa fa-tags"></i>' : '';
            echo '<tr>';
            echo '<td><input type="checkbox" name="delete_projects_index[]" value='. $row_p['id'].' /></td>';
            echo '<td>'.$productive.'</td>';
            echo '<td>'. $row_p['name'] .'</td>';
            echo '<td>';
            $resF = $conn->query("SELECT * FROM $companyExtraFieldsTable WHERE companyID = ".$rowClient['companyID']." ORDER BY id ASC");
            if($resF->num_rows > 0){
              $rowF = $resF->fetch_assoc();
              if($rowF['isActive'] == 'TRUE' && $row_p['field_1'] == 'TRUE'){
                echo $rowF['name'];
              }
            }
            if($resF->num_rows > 1){
              $rowF = $resF->fetch_assoc();
              if($rowF['isActive'] == 'TRUE' && $row_p['field_2'] == 'TRUE'){
                echo $rowF['name'];
              }
            }
            if($resF->num_rows > 2){
              $rowF = $resF->fetch_assoc();
              if($rowF['isActive'] == 'TRUE' && $row_p['field_3'] == 'TRUE'){
                echo $rowF['name'];
              }
            }
            echo '</td>';
            echo '<td>'. $row_p['hours'] .'</td>';
            echo '<td>'. $row_p['hourlyPrice'] .'</td>';
            echo '<td><button type="button" class="btn btn-default" data-toggle="modal" data-target=".editingProjectsModal-'.$row_p['id'].'"><i class="fa fa-pencil"></i></td>';
            echo '</tr>';
          }
          ?>
        </tbody>
      </table>
    </div>

    <div id="home" class="tab-pane fade <?php if($activeTab == 'home'){echo 'in active';}?>">
      <h3><?php echo $lang['GENERAL_INFORMATION']; ?></h3>
      <hr>
      <div class="row checkbox">
        <div class="col-xs-2 text-right"><?php echo $lang['ADDRESS_FORM']; ?></div>
        <div class="col-sm-2">
          <label><input type="radio" value="male" name="gender" <?php if($row['gender'] == 'male'){echo 'checked';} ?> /> <?php echo $lang['GENDER_TOSTRING']['male']; ?></label>
        </div>
        <div class="col-sm-2">
          <label><input type="radio" value="female" name="gender" <?php if($row['gender'] == 'female'){echo 'checked';} ?> /> <?php echo $lang['GENDER_TOSTRING']['female']; ?></label>
        </div>
        <div class="col-sm-3">
          <label><input type="checkbox" value="company" name="contactType" <?php if($row['contactType'] == 'company'){echo 'checked';} ?> /> <?php echo $lang['COMPANY_2']; ?></label>
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
          <input type="text" class="form-control" name="nameAddition" value="<?php echo $row['nameAddition']; ?>" placeholder="Addition <?php if(!isset($_SESSION['language']) || $_SESSION['language'] == 'GER') echo "/Zusatz"; ?>"/>
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
        <div class="col-sm-5">
          <input type="text" class="form-control" name="address_Country_City" value="<?php echo $row['address_Country_City']; ?>" placeholder="<?php echo $lang['CITY']; ?>" />
        </div>
        <div class="col-sm-5">
          <input type="text" class="form-control" name="address_Country_Postal" value="<?php echo $row['address_Country_Postal']; ?>" placeholder="<?php echo $lang['PLZ']; ?>" />
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
    </div>

    <div id="menuTaxes" class="tab-pane fade <?php if($activeTab == 'taxes'){echo 'in active';}?>">
      <div class="row checkbox">
        <div class="col-sm-9">
          <h3>Steuerinformationen</h3>
        </div>
        <br>
        <div class="col-sm-3">
          <input type="checkbox" name="blockDelivery" <?php if($row['blockDelivery'] == 'true'){echo 'checked';} ?> />
          Liefersperre
        </div>
      </div>
      <hr>
      <div class="row form-group">
        <div class="col-xs-2 text-right">
          Debit Nr.
        </div>
        <div class="col-sm-4">
          <input type="number" class="form-control" name="debitNumber" value="<?php echo $row['debitNumber']; ?>" />
        </div>
        <div class="col-xs-2 text-center">
          DATEV
        </div>
        <div class="col-sm-4">
          <input type="number" class="form-control" name="datev" value="<?php echo $row['datev']; ?>" />
        </div>
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
          USt. Ident-Nr.
        </div>
        <div class="col-sm-4">
          <input type="text" class="form-control" name="vatnumber" value="<?php echo $row['vatnumber']; ?>" />
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
            while($row_con = $result_con->fetch_assoc()){
              $selected = ($row['representative'] == $row_con['name']) ? 'selected' : '';
              echo '<option '.$selected.' value="'.$row_con['name'].'">'.$row_con['name'].'</option>';
            }
             ?>
          </select>
        </div>
      </div>
    </div>

    <div id="menuBank" class="tab-pane fade <?php if($activeTab == 'banking'){echo 'in active';}?>">
      <div class="row form-group">
        <h3>
          <div class="col-sm-9">
            Bankdaten
          </div>
          <div class="col-sm-3">
            <div class="input-group">
              <input type="password" class="form-control" name="displayBankingDetailPass" value="" />
              <span class="input-group-btn">
                <button type="submit" class="btn btn-warning" name="displayBank">Unlock</button>
              </span>
            </div>
          </div>
        </h3>
      </div>
      <hr>
      <table class="table table-hover">
        <thead>
          <th>Name der Bank</th>
          <th>Iban</th>
          <th>BIC</th>
        </thead>
        <tbody>
          <?php
          while($resultBank && ($rowBank = $resultBank->fetch_assoc())){
            echo '<tr>';
            echo '<td>' . $rowBank['bankName'] . '</td>';
            if(isset($_SESSION['unlock'])){ //If this is set, decrypt banking detail
              $mc = mc($rowBank["iv"],$rowBank["iv2"]);
              echo '<td>'.$mc->decrypt($rowBank['iban']). '</td>';
              echo '<td>'.$mc->decrypt($rowBank['bic']). '</td>';
            } else { // **** it.
              echo '<td>**** **** **** ****</td>';
              echo '<td>******** ***</td>';
            }
            echo '</tr>';
          }
          ?>
        </tbody>
      </table>
      <br><br><br>
      <?php if(isset($_SESSION['unlock'])): ?>
        <div class="container">
          <div class="col-md-3">
            <input type="text" class="form-control" name="bankName" placeholder="Name der Bank" />
          </div>
          <div class="col-md-5">
            <input type="text" class="form-control" name="iban" placeholder="Iban" />
          </div>
          <div class="col-md-3">
            <input type="text" class="form-control" name="bic" placeholder="BIC" />
          </div>
          <button type="submit" class="btn btn-warning" name="addBankingDetail">+</button>
        </div>
      <?php endif; ?>
    </div>

    <div id="menuBilling" class="tab-pane fade <?php if($activeTab == 'billing'){echo 'in active';}?>">
      <div class="row checkbox">
        <div class="col-sm-9">
          <h3>Rechnungsdaten</h3>
        </div>
        <br>
        <div class="col-sm-3">
          <input type="checkbox" name="eBill" <?php if($row['eBill'] == 'true'){echo 'checked';} ?> />
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
          <input type="datetime-local" class="form-control" name="lastFaktura" value="<?php echo $row['lastFaktura']; ?>" />
        </div>
      </div>
      <div class="row form-group">
        <div class="col-xs-2 text-right">
          Zahlungsweise
        </div>
        <div class="col-sm-3">
          <select class="js-example-basic-single" name="paymentMethod">
            <option value="0">...</option>
            <?php
            $result_con = $conn->query("SELECT * FROM paymentMethods");
            while($row_con = $result_con->fetch_assoc()){
              $selected = '';
              if($row['paymentMethod'] == $row_con['name']){$selected = 'selected';}
              echo '<option '.$selected.' value="'.$row_con['name'].'">'.$row_con['name'].'</option>';
            }
            ?>
          </select>
        </div>
        <div class="col-xs-3 text-center">
          Versandart
        </div>
        <div class="col-sm-3">
          <select class="js-example-basic-single" name="shipmentType">
            <option value="0">...</option>
            <?php
            $result_con = $conn->query("SELECT * FROM shippingMethods");
            while($row_con = $result_con->fetch_assoc()){
              $selected = '';
              if($row['shipmentType'] == $row_con['name']){$selected = 'selected';}
              echo '<option '.$selected.' value="'.$row_con['name'].'">'.$row_con['name'].'</option>';
            }
            ?>
          </select>
        </div>
      </div>
    </div>

    <div id="menuPayment" class="tab-pane fade <?php if($activeTab == 'payment'){echo 'in active';}?>">
      <h3>Zahlungsdaten</h3>
      <hr>
      <div class="row form-group">
        <div class="col-xs-2 text-right">
          Tage Netto
        </div>
        <div class="col-sm-8">
          <input type="number" class="form-control" name="daysNetto" value="<?php echo $row['daysNetto']; ?>" />
        </div>
      </div>
      <div class="row form-group">
        <div class="col-xs-2 text-right">
          Skonto 1
        </div>
        <div class="col-sm-3">
          <input type="number" step="0.01" class="form-control" name="skonto1" value="<?php echo $row['skonto1']; ?>" />
        </div>
        <div class="col-xs-2 text-center">
          % Innerhalb von
        </div>
        <div class="col-sm-3">
          <input type="number" class="form-control" name="skonto1Days" value="<?php echo $row['skonto1Days']; ?>" />
        </div>
        <div class="col-sm-1">
          Tagen
        </div>
      </div>
      <div class="row form-group">
        <div class="col-xs-2 text-right">
          Skonto 2
        </div>
        <div class="col-sm-3">
          <input type="number" step="0.01" class="form-control" name="skonto2" value="<?php echo $row['skonto2']; ?>" />
        </div>
        <div class="col-xs-2 text-center">
          % Innerhalb von
        </div>
        <div class="col-sm-3">
          <input type="number" class="form-control" name="skonto2Days" value="<?php echo $row['skonto2Days']; ?>" />
        </div>
        <div class="col-sm-1">
          Tagen
        </div>
      </div>
      <hr>
      <div class="row form-group">
        <div class="col-xs-2 text-right">
          Mahnungen erlaubt
        </div>
        <div class="col-sm-9">
          <input type="checkbox" name="warningEnabled" <?php if($row['warningEnabled'] == 'true'){echo 'checked';} ?> />
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
          Letzte Mahnung am
        </div>
        <div class="col-sm-3">
          <input type="datetime-local" class="form-control" name="lastWarning" value="<?php echo $row['lastWarning']; ?>" />
        </div>
      </div>
      <div class="row form-group">
        <div class="col-xs-2 text-right">
          Mahnung 1
        </div>
        <div class="col-sm-3">
          <input type="number" step="0.01" class="form-control" name="warning1" value="<?php echo $row['warning1']; ?>" />
        </div>
      </div>
      <div class="row form-group">
        <div class="col-xs-2 text-right">
          Mahnung 2
        </div>
        <div class="col-sm-3">
          <input type="number" step="0.01" class="form-control" name="warning2" value="<?php echo $row['warning2']; ?>" />
        </div>
      </div>
      <div class="row form-group">
        <div class="col-xs-2 text-right">
          Mahnung 3
        </div>
        <div class="col-sm-3">
          <input type="number" step="0.01" class="form-control" name="warning3" value="<?php echo $row['warning3']; ?>" />
        </div>
      </div>
      <hr>
      <div class="row form-group">
        <div class="col-xs-2 text-right">
          Verzugszinsberechnung
        </div>
        <div class="col-md-1">
          <input type="checkbox" name="calculateInterest" <?php if($row['calculateInterest'] == 'true'){echo 'checked';} ?>/>
        </div>
      </div>
    </div>

    <div id="menuContact" class="tab-pane fade <?php if($activeTab == 'notes'){echo 'in active';}?>">
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
          while($resultNotes && ($rowNotes = $resultNotes->fetch_assoc())){
            echo "<tr><td><input type='checkbox' name='noteIndeces[]' /></td>";
            echo "<td>".$rowNotes['createDate']."</td>";
            echo "<td>".$rowNotes['infoText']."</td></tr>";
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
  </div>
</form>

<?php
mysqli_data_seek($result_p,0);
while($row = $result_p->fetch_assoc()):
  $x = $row['id'];
  ?>
  <!-- Edit bookings (time only) -->
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
            <div class="col-md-6">
              <br>
              <?php
              $resF = $conn->query("SELECT * FROM $companyExtraFieldsTable WHERE companyID = ".$rowClient['companyID']." ORDER BY id ASC");
              if($resF->num_rows > 0){
                $rowF = $resF->fetch_assoc();
                if($rowF['isActive'] == 'TRUE'){
                  $checked = $row['field_1'] == 'TRUE' ? 'checked': '';
                  echo '<label><input type="checkbox" '.$checked.' name="addField_1_'.$row['id'].'"/> '.$rowF['name'].'</label>';
                }
              }
              if($resF->num_rows > 1){
                $rowF = $resF->fetch_assoc();
                if($rowF['isActive'] == 'TRUE'){
                  $checked = $row['field_2'] == 'TRUE' ? 'checked': '';
                  echo '<br><label><input type="checkbox" '.$checked.' name="addField_2_'.$row['id'].'" /> '.$rowF['name'].'</label>';
                }
              }
              if($resF->num_rows > 2){
                $rowF = $resF->fetch_assoc();
                if($rowF['isActive'] == 'TRUE'){
                  $checked = $row['field_3'] == 'TRUE' ? 'checked': '';
                  echo '<br><label><input type="checkbox" '.$checked.' name="addField_3_'.$row['id'].'" /> '.$rowF['name'].'</label>';
                }
              }
              ?>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-warning" name="save" value="<?php echo $x; ?>"><?php echo $lang['SAVE']; ?></button>
        </div>
      </div>
    </div>
  </form>
<?php endwhile; ?>

<!-- ADD PROJECT -->
<form method="POST">
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
            <input type=number class="form-control" name='hours' step="any">
          </div>
          <div class="col-md-6">
            <label><?php echo $lang['HOURLY_RATE']; ?></label>
            <input type=number class="form-control" name='hourlyPrice' step="any">
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
              $resF = $conn->query("SELECT * FROM $companyExtraFieldsTable WHERE companyID = ".$rowClient['companyID']." ORDER BY id ASC");
              if($resF->num_rows > 0){
                $rowF = $resF->fetch_assoc();
                if($rowF['isActive'] == 'TRUE'){
                  $checked = $rowF['isForAllProjects'] == 'TRUE' ? 'checked': '';
                  echo '<input type="checkbox" '.$checked.' name="createField_1"/>'. $rowF['name'];
                }
              }
              if($resF->num_rows > 1){
                $rowF = $resF->fetch_assoc();
                if($rowF['isActive'] == 'TRUE'){
                  $checked = $rowF['isForAllProjects'] == 'TRUE' ? 'checked': '';
                  echo '<br><input type="checkbox" '.$checked.' name="createField_2" />'. $rowF['name'];
                }
              }
              if($resF->num_rows > 2){
                $rowF = $resF->fetch_assoc();
                if($rowF['isActive'] == 'TRUE'){
                  $checked = $rowF['isForAllProjects'] == 'TRUE' ? 'checked': '';
                  echo '<br><input type="checkbox" '.$checked.' name="createField_3" />'. $rowF['name'];
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
<?php require "footer.php"; ?>
