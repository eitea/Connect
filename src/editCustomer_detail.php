<?php include "header.php"; ?>

<div class="page-header">
  <h3><?php echo $lang['CLIENT'] .' - Details'; ?></h3>
</div>

<?php
if(isset($_GET['custID']) && is_numeric($_GET['custID'])){
  $filterClient = test_input($_GET['custID']);
} else {
  redirect("editCustomer.php");
}

//get corresponding id from detailTable
$result = $conn->query("SELECT * FROM $clientDetailTable WHERE clientId = $filterClient");
if($result && ($row = $result->fetch_assoc())){
  $detailID = $row['id'];
} else { //no detailTable found -> create one
  $conn->query("INSERT INTO $clientDetailTable (clientID) VALUES($$filterClient)");
  $detailID = $conn->insert_id;
  echo mysqli_error($conn);
}

$activeTab = 'home';

if(isset($_POST['saveAll'])){
  $activeTab = $_POST['saveAll'];

  if(isset($_POST['contactType'])){
    $val = $_POST['contactType'];
    $conn->query("UPDATE $clientDetailTable SET contactType = '$val' WHERE id = $detailID");
  }

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

  if(isset($_POST['phone'])){
    $val = test_input($_POST['phone']);
    $conn->query("UPDATE $clientDetailTable SET phone = '$val' WHERE id = $detailID");
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
    $val = intval($_POST['taxnumber']);
    $conn->query("UPDATE $clientDetailTable SET taxnumber = '$val' WHERE id = $detailID");
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
}

if($_SERVER['REQUEST_METHOD'] == 'POST'){
  if(isset($_POST['addNotes']) && !empty($_POST['infoText'])){
    $activeTab = 'notes';
    $val = test_input($_POST['infoText']);
    $conn->query("INSERT INTO $clientDetailNotesTable (infoText, createDate, parentID) VALUES ('$val', CURRENT_TIMESTAMP, $detailID)");
  }
  if(isset($_POST['deleteNotes']) && !empty($_POST['noteIndeces'])){
    $activeTab = 'notes';
    foreach($_POST['noteIndeces'] as $i){
      $conn->query("DELETE FROM $clientDetailNotesTable WHERE id = $i");
    }
  }
  if(isset($_POST['addBankingDetail']) && !empty($_POST['bankName']) && !empty($_POST['iban']) && !empty($_POST['bic'])){
    //TODO: encrypt.

  }

  $unlockBanking = false;
  if(isset($_POST['displayBank']) && isset($_POST['displayBankingDetailPass'])){ //TODO: after enabling master password, add this in here
    $result = $conn->query("SELECT masterPassword FROM $configTable");
    $row = $result->fetch_assoc();
    if(crypt($_POST['displayBank'], $row['masterPassword']) == $row['masterPassword'] && !empty($row['masterPassword'])){ //unlock
      $_SESSION['unlock'] = $_POST['displayBank'];
    }
  }
}

$result = $conn->query("SELECT * FROM $clientDetailTable WHERE id = $detailID");
$row = $result->fetch_assoc();

$resultNotes = $conn->query("SELECT * FROM $clientDetailNotesTable WHERE parentID = $detailID");
$resultBank = $conn->query("SELECT * FROM $clientDetailBankTable WHERE parentID = $detailID");
?>

<div class="text-right">
  <a href="editCustomers.php?custID=<?php echo $filterClient; ?>" class="btn btn-info"><i class="fa fa-arrow-left"></i> Return</a><br>
</div>

<ul class="nav nav-tabs">
  <li <?php if($activeTab == 'home'){echo 'class="active"';}?>><a data-toggle="tab" href="#home"><?php echo $lang['DATA']; ?></a></li>
  <li <?php if($activeTab == 'taxes'){echo 'class="active"';}?>><a data-toggle="tab" href="#menuTaxes"><?php echo $lang['TAXES']; ?></a></li>
  <li <?php if($activeTab == 'banking'){echo 'class="active"';}?>><a data-toggle="tab" href="#menuBank">Banking</a></li>
  <li <?php if($activeTab == 'billing'){echo 'class="active"';}?>><a data-toggle="tab" href="#menuBilling"><?php echo $lang['BILLING']; ?></a></li>
  <li <?php if($activeTab == 'payment'){echo 'class="active"';}?>><a data-toggle="tab" href="#menuPayment"><?php echo $lang['PAYMENT']; ?></a></li>
  <li <?php if($activeTab == 'notes'){echo 'class="active"';}?>><a data-toggle="tab" href="#menuContact"><?php echo $lang['NOTES']; ?></a></li>
</ul>

<form method="post">
  <div class="tab-content">
    <div id="home" class="tab-pane fade <?php if($activeTab == 'home'){echo 'in active';}?>">
      <div class="row radio">
        <div class="col-xs-8">
          <h3>Allgemeine Informationen</h3>
        </div>
        <br>
        <div class="col-xs-2">
          <input type="radio" value="person" name="contactType" <?php if($row['contactType'] == 'person'){echo 'checked';} ?> /> Person
        </div>
        <div class="col-xs-2">
          <input type="radio" value="company" name="contactType" <?php if($row['contactType'] == 'company'){echo 'checked';} ?> /> Company
        </div>
      </div>
      <hr>
      <div class="container">
        <div class="row radio">
          <div class="col-xs-3">
            Anrede
          </div>
          <div class="col-xs-2">
            <input type="radio" value="male" name="gender" <?php if($row['gender'] == 'male'){echo 'checked';} ?> /> Herr
          </div>
          <div class="col-xs-2">
            <input type="radio" value="female" name="gender" <?php if($row['gender'] == 'female'){echo 'checked';} ?> /> Frau
          </div>
        </div>
        <br>
        <div class="row form-group">
          <div class="col-xs-3">
            Title
          </div>
          <div class="col-xs-3">
            <input type="text" class="form-control" name="title" value="<?php echo $row['title']; ?>" />
          </div>
        </div>
        <div class="row form-group">
          <div class="col-xs-3">
            Name
          </div>
          <div class="col-xs-9">
            <input type="text" class="form-control" name="name" value="<?php echo $row['name']; ?>" />
          </div>
        </div>
        <div class="row form-group">
          <div class="col-xs-3">
            Addition/Zusatz
          </div>
          <div class="col-xs-9">
            <input type="text" class="form-control" name="nameAddition" value="<?php echo $row['nameAddition']; ?>" />
          </div>
        </div>
        <div class="row form-group">
          <div class="col-xs-3">
            Straße
          </div>
          <div class="col-xs-9">
            <input type="text" class="form-control" name="address_Street" value="<?php echo $row['address_Street']; ?>" />
          </div>
        </div>
        <div class="row form-group">
          <div class="col-xs-3">
            Land/PLZ/Ort
          </div>
          <div class="col-xs-9">
            <input type="text" class="form-control" name="address_Country" value="<?php echo $row['address_Country']; ?>" />
          </div>
        </div>
        <div class="row form-group">
          <div class="col-xs-3">
            Handy
          </div>
          <div class="col-xs-9">
            <input type="text" class="form-control" name="phone" value="<?php echo $row['phone']; ?>" />
          </div>
        </div>
      </div>

      <br><hr><br>
      <button type="submit" class="btn btn-warning" name="saveAll" value="home">Speichern</button>
    </div>

    <div id="menuTaxes" class="tab-pane fade <?php if($activeTab == 'taxes'){echo 'in active';}?>">
      <div class="row checkbox">
        <div class="col-xs-9">
          <h3>Steuerinformationen</h3>
        </div>
        <br>
        <div class="col-xs-3">
          <input type="checkbox" name="blockDelivery" <?php if($row['blockDelivery'] == 'true'){echo 'checked';} ?> />
          Liefersperre
        </div>
      </div>
      <hr>
      <div class="row form-group">
        <div class="col-xs-3">
          Debit Nr.
        </div>
        <div class="col-xs-4">
          <input type="number" class="form-control" name="debitNumber" value="<?php echo $row['debitNumber']; ?>" />
        </div>
      </div>
      <div class="row form-group">
        <div class="col-xs-3">
          DATEV
        </div>
        <div class="col-xs-4">
          <input type="number" class="form-control" name="datev" value="<?php echo $row['datev']; ?>" />
        </div>
      </div>
      <div class="row form-group">
        <div class="col-xs-3">
          Kontobezeichnung
        </div>
        <div class="col-xs-9">
          <input type="text" class="form-control" name="accountName" value="<?php echo $row['accountName']; ?>" />
        </div>
      </div>
      <hr>
      <div class="row form-group">
        <div class="col-xs-3">
          Steuernummer
        </div>
        <div class="col-xs-3">
          <input type="text" class="form-control" name="taxnumber" value="<?php echo $row['taxnumber']; ?>" />
        </div>
      </div>
      <div class="row form-group">
        <div class="col-xs-3">
          Steuergebiet
        </div>
        <div class="col-xs-9">
          <input type="text" class="form-control" name="taxArea" value="<?php echo $row['taxArea']; ?>" />
        </div>
      </div>
      <div class="row form-group">
        <div class="col-xs-3">
          Kundengruppe
        </div>
        <div class="col-xs-9">
          <input type="text" class="form-control" name="customerGroup" value="<?php echo $row['customerGroup']; ?>" />
        </div>
      </div>
      <div class="row form-group">
        <div class="col-xs-3">
          Vertreter
        </div>
        <div class="col-xs-9">
          <input type="text" class="form-control" name="representative" value="<?php echo $row['representative']; ?>" />
        </div>
      </div>
      <br><hr><br>
      <button type="submit" class="btn btn-warning" name="saveAll" value="taxes">Speichern</button>
    </div>

    <div id="menuBank" class="tab-pane fade <?php if($activeTab == 'banking'){echo 'in active';}?>">
      <div class="row form-group">
        <h3>
        <div class="col-xs-9">
          Bankdaten
        </div>
        <div class="col-xs-3">
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

      <?php
      $result = $conn->query("SELECT * FROM $clientDetailBankTable");
       ?>

      <table class="table table-hover">
        <thead>
          <th>Name der Bank</th>
          <th>Iban</th>
          <th>BIC</th>
        </thead>
        <tbody>
          <?php
            while($resultBank && ($rowBank = $resultBank->fetch_assoc())){
              if($unlockBanking){ //If this is set, decrypt banking detail

              } else { // **** it.
                echo $row['bankName'];
                echo '<td>**** **** **** ****</td>';
                echo '<td>******** ***</td>';
              }
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
      <!--
      bic VARCHAR(20),
      iban VARCHAR(50),
      bankName VARCHAR(100),
      parentID  INT(6) UNSIGNED,
      -->

      <br><hr><br>
      <button type="submit" class="btn btn-warning" name="saveAll" value="billing">Speichern</button>
    </div>

    <div id="menuBilling" class="tab-pane fade <?php if($activeTab == 'billing'){echo 'in active';}?>">
      <div class="row checkbox">
        <div class="col-xs-9">
          <h3>Rechnungsdaten</h3>
        </div>
        <br>
        <div class="col-xs-3">
          <input type="checkbox" name="eBill" <?php if($row['eBill'] == 'true'){echo 'checked';} ?> />
          E-Rechnung
        </div>
      </div>
      <hr>
      <div class="row form-group">
        <div class="col-xs-3">
          Kreditlimit
        </div>
        <div class="col-xs-3">
          <input type="number" step="any" class="form-control" name="creditLimit" value="<?php echo $row['creditLimit']; ?>" />
        </div>
      </div>
      <div class="row form-group">
        <div class="col-xs-3">
          Letzte Faktura Buchung
        </div>
        <div class="col-xs-3">
          <input type="datetime-local" class="form-control" name="lastFaktura" value="<?php echo $row['lastFaktura']; ?>" />
        </div>
      </div>
      <div class="row form-group">
        <div class="col-xs-3">
          Zahlungsweise
        </div>
        <div class="col-xs-9">
          <input type="text" class="form-control" name="paymentMethod" value="<?php echo $row['paymentMethod']; ?>" />
        </div>
      </div>
      <div class="row form-group">
        <div class="col-xs-3">
          Versandart
        </div>
        <div class="col-xs-9">
          <input type="text" class="form-control" name="shipmentType" value="<?php echo $row['shipmentType']; ?>" />
        </div>
      </div>
      <br><hr><br>
      <button type="submit" class="btn btn-warning" name="saveAll" value="billing">Speichern</button>
    </div>


    <div id="menuPayment" class="tab-pane fade <?php if($activeTab == 'payment'){echo 'in active';}?>">
      <h3>Zahlungsdaten</h3>
      <hr>
      <div class="row form-group">
        <div class="col-xs-3">
          Tage Netto
        </div>
        <div class="col-xs-6">
          <input type="text" class="form-control" name="daysNetto" value="<?php echo $row['daysNetto']; ?>" />
        </div>
      </div>
      <div class="row form-group">
        <div class="col-xs-3">
          Skonto 1
        </div>
        <div class="col-xs-3">
          <input type="text" class="form-control" name="skonto1" value="<?php echo $row['skonto1']; ?>" />
        </div>
        <div class="col-xs-2">
          % Innerhalb von
        </div>
        <div class="col-xs-3">
          <input type="text" class="form-control" name="skonto1Days" value="<?php echo $row['skonto1Days']; ?>" />
        </div>
        <div class="col-xs-1">
          Tagen
        </div>
      </div>
      <div class="row form-group">
        <div class="col-xs-3">
          Skonto 2
        </div>
        <div class="col-xs-3">
          <input type="text" class="form-control" name="skonto2" value="<?php echo $row['skonto2']; ?>" />
        </div>
        <div class="col-xs-2">
          % Innerhalb von
        </div>
        <div class="col-xs-3">
          <input type="text" class="form-control" name="skonto2Days" value="<?php echo $row['skonto2Days']; ?>" />
        </div>
        <div class="col-xs-1">
          Tagen
        </div>
      </div>
      <hr>
      <div class="row form-group">
        <div class="col-xs-3">
          Mahnungen erlaubt
        </div>
        <div class="col-xs-9">
          <input type="checkbox" name="warningEnabled" <?php if($row['warningEnabled'] == 'true'){echo 'checked';} ?> />
        </div>
      </div>
      <br>
      <div class="row form-group">
        <div class="col-xs-3">
          Karenztage
        </div>
        <div class="col-xs-9">
          <input type="text" class="form-control" name="karenztage" value="<?php echo $row['karenztage']; ?>" />
        </div>
      </div>
      <div class="row form-group">
        <div class="col-xs-3">
          Letzte Mahnung am
        </div>
        <div class="col-xs-9">
          <input type="datetime-local" class="form-control" name="lastWarning" value="<?php echo $row['lastWarning']; ?>" />
        </div>
      </div>
      <div class="row form-group">
        <div class="col-xs-3">
          Mahnung 1
        </div>
        <div class="col-xs-9">
          <input type="text" class="form-control" name="warning1" value="<?php echo $row['warning1']; ?>" />
        </div>
      </div>
      <div class="row form-group">
        <div class="col-xs-3">
          Mahnung 2
        </div>
        <div class="col-xs-9">
          <input type="text" class="form-control" name="warning2" value="<?php echo $row['warning2']; ?>" />
        </div>
      </div>
      <div class="row form-group">
        <div class="col-xs-3">
          Mahnung 3
        </div>
        <div class="col-xs-9">
          <input type="text" class="form-control" name="warning3" value="<?php echo $row['warning3']; ?>" />
        </div>
      </div>
      <hr>
      <div class="row form-group">
        <div class="col-xs-3">
          Verzugszinsberechnung
        </div>
        <div class="col-xs-9">
          <input type="checkbox" name="calculateInterest" <?php if($row['calculateInterest'] == 'true'){echo 'checked';} ?> />
        </div>
      </div>
      <br><hr><br>
      <button type="submit" class="btn btn-warning" name="saveAll" value="payment">Speichern</button>
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
      <div class="container">
        <br><br> Neue Notiz Hinzufügen: <br><br>
        <textarea class="form-control" rows="3" name="infoText" placeholder="Info..."></textarea>
      </div>
      <br>
      <div class="container text-right">
        <button type="submit" class="btn btn-warning" name="addNotes">Hinzufügen</button> <button type="submit" class="btn btn-danger" name="deleteNotes">Löschen</button>
      </div>

    </div>
  </div><br><br>
</form>

<?php require "footer.php"; ?>
