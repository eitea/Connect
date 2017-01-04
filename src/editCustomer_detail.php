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
  <li><a data-toggle="tab" href="#menuBank">Account</a></li>
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
    <h3>Billing</h3>
    paymentMethod VARCHAR(100),
    shipmentType VARCHAR(100),
    creditLimit DECIMAL(10,2),
    eBill ENUM('true', 'false') DEFAULT 'false',
    lastFaktura DATETIME,
  </div>

  <div id="menuPayment" class="tab-pane fade">
    <h3>Payment</h3>
    daysNetto INT(4),
    skonto1 DECIMAL(6,2),
    skonto2 DECIMAL(6,2),
    skonto1Days INT(4),
    skonto2Days INT(4),
    warningEnabled ENUM('true', 'false') DEFAULT 'true',
    karenztage INT(4),
    lastWarning DATETIME,
    warning1 DECIMAL(10,2),
    warning2 DECIMAL(10,2),
    warning3 DECIMAL(10,2),
    calculateInterest ENUM('true', 'false'),
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
