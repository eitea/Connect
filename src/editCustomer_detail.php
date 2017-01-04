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
<div class="tab-content">
  <div id="home" class="tab-pane fade in active">

    <div class="row radio">
      <div class="col-xs-3">
        <h3>Address Data</h3>
      </div>
        <br>
        <div class="col-xs-2 col-xs-offset-5">
          <input type="radio" value="person" name="contactType" /> Person
        </div>
        <div class="col-xs-2">
          <input type="radio" value="company" name="contactType" /> Company
        </div>
    </div>

    <hr>

    <div class="container">
      <div class="row radio">
        <div class="col-xs-3">
          Anrede
        </div>
          <div class="col-xs-2">
            <input type="radio" value="male" name="gender" /> Herr
          </div>
          <div class="col-xs-2">
            <input type="radio" value="female" name="gender" /> Frau
          </div>
      </div>
      <br>

      <div class="row form-group">
        <div class="col-xs-3">
          Title
        </div>
        <div class="col-xs-3">
          <input type="text" class="form-control" name="title" />
        </div>
      </div>

      <div class="row form-group">
        <div class="col-xs-3">
          Name
        </div>
        <div class="col-xs-9">
          <input type="text" class="form-control" name="name" />
        </div>
      </div>

      <div class="row form-group">
        <div class="col-xs-3">
          Addition/Zusatz
        </div>
        <div class="col-xs-9">
          <input type="text" class="form-control" name="nameAdditive" />
        </div>
      </div>

      <div class="row form-group">
        <div class="col-xs-3">
          Straße
        </div>
        <div class="col-xs-9">
          <input type="text" class="form-control" name="street" />
        </div>
      </div>

      <div class="row form-group">
        <div class="col-xs-3">
          Land/PLZ/Ort
        </div>
        <div class="col-xs-9">
          <input type="text" class="form-control" name="address" />
        </div>
      </div>

      <div class="row form-group">
        <div class="col-xs-3">
          Phone
        </div>
        <div class="col-xs-9">
          <input type="text" class="form-control" name="phone" />
        </div>
      </div>
    </div>
  </div>

  <div id="menuTaxes" class="tab-pane fade">
    <div class="row checkbox">
      <div class="col-xs-9">
      <h3>Taxing Information</h3>
    </div>
    <br>
    <div class="col-xs-3">
      <input type="checkbox" name="blockDelivery" />
      Liefersperre
    </div>
    </div>
    <hr>
    <div class="row form-group">
      <div class="col-xs-3">
        Debit Nr.
      </div>
      <div class="col-xs-4">
        <input type="number" class="form-control" name="debit" />
      </div>
    </div>
    <div class="row form-group">
      <div class="col-xs-3">
        DATEV
      </div>
      <div class="col-xs-4">
        <input type="number" class="form-control" name="datev" />
      </div>
    </div>

    <div class="row form-group">
      <div class="col-xs-3">
        Kontobezeichnung
      </div>
      <div class="col-xs-9">
        <input type="text" class="form-control" name="accountName" />
      </div>
    </div>

    <hr>

    <div class="row form-group">
      <div class="col-xs-3">
        Steuernummer
      </div>
      <div class="col-xs-3">
        <input type="text" class="form-control" name="taxNumber" />
      </div>
    </div>

    <div class="row form-group">
      <div class="col-xs-3">
        Steuergebiet
      </div>
      <div class="col-xs-9">
        <input type="text" class="form-control" name="taxArea" />
      </div>
    </div>

    <div class="row form-group">
      <div class="col-xs-3">
        Kundengruppe
      </div>
      <div class="col-xs-9">
        <input type="text" class="form-control" name="customerGroup" />
      </div>
    </div>

    <div class="row form-group">
      <div class="col-xs-3">
        Vertreter
      </div>
      <div class="col-xs-9">
        <input type="text" class="form-control" name="representative" />
      </div>
    </div>
  </div>

  <div id="menuBank" class="tab-pane fade">
    <h3>Bank Account Info</h3>
    bic VARCHAR(20),
    iban VARCHAR(50),
    bankName VARCHAR(100),
    parentID  INT(6) UNSIGNED,
  </div>

  <div id="menuBilling" class="tab-pane fade">
    <div class="row checkbox">
      <div class="col-xs-9">
        <h3>Billing</h3>
      </div>
      <br>
      <div class="col-xs-3">
        <input type="checkbox" name="eBill" />
        E-Rechnung
      </div>
    </div>
    <hr>

    <div class="row form-group">
      <div class="col-xs-3">
        Kreditlimit
      </div>
      <div class="col-xs-3">
        <input type="number" step="any" class="form-control" name="creditLimit" />
      </div>
    </div>

    <div class="row form-group">
      <div class="col-xs-3">
        Letzte Faktura Buchung
      </div>
      <div class="col-xs-3">
        <input type="text" class="form-control" name="lastFaktura" />
      </div>
    </div>

    <div class="row form-group">
      <div class="col-xs-3">
        Zahlungsweise
      </div>
      <div class="col-xs-9">
        <input type="text" class="form-control" name="paymentmethod" />
      </div>
    </div>

    <div class="row form-group">
      <div class="col-xs-3">
        Versandart
      </div>
      <div class="col-xs-9">
        <input type="text" class="form-control" name="shipmentType" />
      </div>
    </div>
  </div>

  <div id="menuPayment" class="tab-pane fade">
    <h3>Payment</h3>
    <hr>
    <div class="row form-group">
      <div class="col-xs-3">
        Tage Netto
      </div>
      <div class="col-xs-6">
        <input type="text" class="form-control" name="daysNetto" />
      </div>
    </div>

    <div class="row form-group">
      <div class="col-xs-3">
        Skonto 1
      </div>
      <div class="col-xs-3">
        <input type="text" class="form-control" name="skonto1" />
      </div>
      <div class="col-xs-2">
        % Innerhalb von
      </div>
      <div class="col-xs-3">
        <input type="text" class="form-control" name="skonto1days" />
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
        <input type="text" class="form-control" name="skonto2" />
      </div>
      <div class="col-xs-2">
        % Innerhalb von
      </div>
      <div class="col-xs-3">
        <input type="text" class="form-control" name="skonto2days" />
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
        <input type="checkbox" name="warningEnabled" />
      </div>
    </div>
    <br>
    <div class="row form-group">
      <div class="col-xs-3">
        Karenztage
      </div>
      <div class="col-xs-9">
        <input type="text" class="form-control" name="karenztage" />
      </div>
    </div>

    <div class="row form-group">
      <div class="col-xs-3">
        Letzte Mahnung am
      </div>
      <div class="col-xs-9">
        <input type="text" class="form-control" name="lastWarning" />
      </div>
    </div>

    <div class="row form-group">
      <div class="col-xs-3">
        Mahnung 1
      </div>
      <div class="col-xs-9">
        <input type="text" class="form-control" name="warning1" />
      </div>
    </div>

    <div class="row form-group">
      <div class="col-xs-3">
        Mahnung 2
      </div>
      <div class="col-xs-9">
        <input type="text" class="form-control" name="warning2" />
      </div>
    </div>

    <div class="row form-group">
      <div class="col-xs-3">
        Mahnung 3
      </div>
      <div class="col-xs-9">
        <input type="text" class="form-control" name="warning3" />
      </div>
    </div>

    <hr>
    <div class="row form-group">
      <div class="col-xs-3">
        Verzugszinsberechnung
      </div>
      <div class="col-xs-9">
        <input type="checkbox" name="calculateInterest" />
      </div>
    </div>

  </div>

  <div id="menuContact" class="tab-pane fade">
    <h3>Notes</h3>
    infoText VARCHAR(800),
    createDate DATETIME,
    parentID INT(6) UNSIGNED,
  </div>
</div>


<?php require "footer.php"; ?>

</div>


<?php require "footer.php"; ?>
