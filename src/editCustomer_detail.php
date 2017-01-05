<?php include "header.php"; ?>
<?php include "validate.php"; ?>

<div class="page-header">
  <h3><?php echo $lang['CLIENT'] .' - Details'; ?></h3>
</div>

<?php
if(isset($_GET['custID']) && is_numeric($_GET['custID'])){
  $filterClient = test_input($_GET['custID']);
} else {
  $filterClient = 0;
}


$result = $conn->query("SELECT * FROM $clientDetailTable WHERE clientId = $filterClient");
if($result && ($row = $result->fetch_assoc())){
  $detailID = $row['id'];
} else {
  $detailID = 0;
}

?>

<div class="text-right">
  <a href="editCustomers.php?custID=<?php echo $filterClient; ?>" class="btn btn-info"><i class="fa fa-arrow-left"></i> Return</a><br>
</div>

<ul class="nav nav-tabs">
  <li class="active"><a data-toggle="tab" href="#home">Data</a></li>
  <li><a data-toggle="tab" href="#menuTaxes">Taxes</a></li>
  <li><a data-toggle="tab" href="#menuBank">Banking</a></li>
  <li><a data-toggle="tab" href="#menuBilling">Billing</a></li>
  <li><a data-toggle="tab" href="#menuPayment">Payment</a></li>
  <li><a data-toggle="tab" href="#menuContact">Notes</a></li>
</ul>

<form method="post">
<div class="tab-content">
  <div id="home" class="tab-pane fade in active">

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
  </div>

  <div id="menuTaxes" class="tab-pane fade">
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
  </div>

  <div id="menuBank" class="tab-pane fade">
    <h3>Bankdaten</h3>
    <hr>
    bic VARCHAR(20),
    iban VARCHAR(50),
    bankName VARCHAR(100),
    parentID  INT(6) UNSIGNED,
  </div>

  <div id="menuBilling" class="tab-pane fade">
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
        <input type="text" class="form-control" name="lastFaktura" value="<?php echo $row['lastFaktura']; ?>" />
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
  </div>

  <div id="menuPayment" class="tab-pane fade">
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
        <input type="text" class="form-control" name="lastWarning" value="<?php echo $row['lastWarning']; ?>" />
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
  </div>

  <div id="menuContact" class="tab-pane fade">
    <h3>Bemerkungen</h3>
    <hr>
    <table class="table table-hover">
      <thead>
        <th>Löschen</th>
        <th>Datum</th>
        <th>Info</th>
      </thead>
      <tbody>
        <?php
        $result = $conn->query("SELECT infoText, createDate FROM $clientDetailNotesTable WHERE parentID = $detailID");
        while($result && ($row = $result->fetch_assoc())){
          echo "<tr><td><input type='checkbox' name='deleteNotes[]' /></td>";
          echo "<td>".$row['createDate']."</td>";
          echo "<td>".$row['infoText']."</td></tr>";
        }
        ?>
      </tbody>
    </table>
  </div>
</div>
<br>
<hr>
<br>
<button type="submit" class="btn btn-warning" disabled >Speichern</button>
</form>

<?php require "footer.php"; ?>
