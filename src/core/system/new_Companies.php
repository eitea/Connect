<?php include dirname(dirname(__DIR__)) . '/header.php'; ?>
<?php require dirname(dirname(__DIR__)) . "/misc/helpcenter.php"; ?>
<!-- BODY -->

<div class="page-header">
  <h3><?php echo $lang['CREATE_NEW_COMPANY']; ?></h3>
</div>

<?php
if($_SERVER["REQUEST_METHOD"] == "POST"){
  if(isset($_POST['compaCreate']) && !empty($_POST['compaName']) && $_POST['compaType'] != "0"){
    $accept = '';
    $compaName = test_input($_POST['compaName']);
    $type = test_input($_POST['compaType']);
    $conn->query("INSERT INTO $companyTable (name, companyType) VALUES('$compaName', '$type')");
    $ins_id = mysqli_insert_id($conn);
    $accept .= $conn->error;
    $conn->query("INSERT INTO $companyToUserRelationshipTable (companyID, userID) VALUES($ins_id, $userID)");
    $accept .= $conn->error;
    $conn->query("INSERT INTO erp_settings (companyID, clientNum, clientStep, supplierNum, supplierStep) VALUES($ins_id, '1000',1,'1000',1)");
    $accept .= $conn->error;

    $file = fopen((dirname(__DIR__)).'/setup/Kontoplan.csv', 'r');
    if($file){
      $stmt = $conn->prepare("INSERT INTO accounts (companyID, num, name, type) VALUES($ins_id, ?, ?, ?)");
      $stmt->bind_param("iss", $num, $name, $type);
      while(($line= fgetcsv($file, 300, ';')) !== false){
        $num = $line[0];
        $name = trim(iconv(mb_detect_encoding($line[1], mb_detect_order(), true), "UTF-8", $line[1]));
        if(!$name) $name = trim(iconv('MS-ANSI', "UTF-8", $line[1]));
        if(!$name) $name = $line[1];
        $type = trim($line[2]);
        $stmt->execute();
      }
      $stmt->close();
    } else {
      $accept .= "<br>Error Opening csv File. Kontoplan konnte nicht erstellt werden";
    }
    $accept .= $conn->error;

    $conn->query("UPDATE accounts SET manualBooking = 'TRUE' WHERE companyID = $ins_id AND (name = 'Bank' OR name = 'Kassa')");
    $accept .= $conn->error;

	$keyPair = sodium_crypto_box_keypair();
	$private = sodium_crypto_box_secretkey($keyPair);
	$public = sodium_crypto_box_publickey($keyPair);
	$symmetric = random_bytes(SODIUM_CRYPTO_SECRETBOX_KEYBYTES);
	$nonce = random_bytes(24);
	$symmetric_encrypted = $nonce . sodium_crypto_box($symmetric, $nonce, $private.$public);
	$conn->query("INSERT INTO security_company (companyID, publicKey, symmetricKey) VALUES ($ins_id, '".base64_encode($public)."', '".base64_encode($symmetric_encrypted)."') ");
	if($conn->error) $accept .= 'Error: Mandantschlüssel '.$conn->error;
	$nonce = random_bytes(24);
	$encrypted = $nonce . sodium_crypto_box($private, $nonce, $private.base64_decode($publicKey));
	$conn->query("INSERT INTO security_access(userID, module, optionalID, privateKey) VALUES ($userID, 'COMPANY', $ins_id , '".base64_encode($encrypted)."')");
	if($conn->error) $accept .= 'Error: Zugriff '.$conn->error;


    if($accept){ echo $accept; } else { redirect("company?cmp=$ins_id"); }

  } else {
    echo '<div class="alert alert-warning fade in">';
    echo '<a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>';
    echo '<strong>Cannot create new instance: </strong>Name or Type were not well defined.</div>';
  }
}
?>
<form method="post">
  <div class="container-fluid">
    <div class="col-md-6">
      <input type="text" class="form-control" name="compaName" placeholder="Name...">
    </div>
    <div class="col-md-4">
      <select name="compaType" class="js-example-basic-single btn-block">
        <option selected value="0">Typ...</option>
        <option value="GmbH">GmbH</option>
        <option value="AG">AG</option>
        <option value="OG">OG</option>
        <option value="KG">KG</option>
        <option value="EU">EU</option>
        <option value="-">Sonstiges</option>
      </select>
    </div>
    <div class="col-md-2 text-right">
      <button type="submit" class="btn btn-warning " name="compaCreate">Hinzufügen</button>
    </div>
  </div>
</form>

<!-- /BODY -->
<?php include dirname(dirname(__DIR__)) . '/footer.php'; ?>
