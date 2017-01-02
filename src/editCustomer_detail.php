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
    <h3>Address Data</h3>
    <div class="container">
      <div class="col-md-3">
      </div>
      <div class="col-md-9">
        <input type="radio" value="person" name="contactType"> Person
        <input type="radio" value="company" name="contactType"> Company
      </div>

      <div class="col-md-3">
        Adressing/Anrede
      </div>
      <div class="col-md-9">
      </div>

      <div class="col-md-3">
        Title
      </div>
      <div class="col-md-9">
      </div>

      <div class="col-md-3">
        Name
      </div>
      <div class="col-md-9">
      </div>

      <div class="col-md-3">
        Addition/Zusatz
      </div>
      <div class="col-md-9">
      </div>

      <div class="col-md-3">
        Straße
      </div>
      <div class="col-md-9">
      </div>

      <div class="col-md-3">

      </div>
      Land/PLZ/ORT
      <div class="col-md-9">
      </div>

      <div class="col-md-3">
        Phone
      </div>
      <div class="col-md-9">
      </div>
    </div>
  </div>

  <div id="menuTaxes" class="tab-pane fade">
    <h3>Taxing Information</h3>
    debitNumber INT(10),
    datev INT(10),
    accountName VARCHAR(100),
    taxnumber INT(50),
    taxArea VARCHAR(50),
    customerGroup VARCHAR(50),
    representative VARCHAR(50),
    blockDelivery ENUM('true', 'false') DEFAULT 'false',
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
