<?php include dirname(__DIR__) . '/header.php'; enableToCore($userID);?>
<!-- BODY -->

<?php
if(empty($_GET['cmp']) || !in_array($_GET['cmp'], $available_companies)){ include dirname(__DIR__) . '/footer.php'; die("Invalid Access");}
$cmpID = intval($_GET['cmp']);

if($_SERVER["REQUEST_METHOD"] == "POST"){
  if(isset($_POST['deleteCompany'])){
    if($cmpID == 1){
      echo '<div class="alert alert-over alert-danger"><a href="#" data-dismiss="alert">&times;</a>'.$lang['ERROR_DELETE_COMPANY'].'</div>';
    } else {
      $sql = "DELETE FROM companyData WHERE id = $cmpID;";
      $conn->query($sql);
      echo mysqli_error($conn);
    }
  } elseif(isset($_POST['delete_logo'])){
    if(!mysqli_error($conn)){
      $conn->query("UPDATE companyData SET logo = '' WHERE id = $cmpID");
    }
    if($conn->error){ echo $conn->error;} else {echo '<div class="alert alert-over alert-success"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$lang['OK_DELETE'].'</div>';}
  } elseif(isset($_POST['save_logo'])){
    require_once dirname(__DIR__) . "/utilities.php";
    $logo = uploadImage("fileToUpload", 0, 1); //returns array on false
    if(!is_array($logo)){
      $stmt = $conn->prepare("UPDATE companyData SET logo = ? WHERE id = $cmpID");
      $null = NULL;
      $stmt->bind_param("b", $null);
      $stmt->send_long_data(0, $logo);
      $stmt->execute();
      if($stmt->errno){ echo $stmt->error;} else { echo '<div class="alert alert-over alert-success"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$lang['OK_SAVE'].'</div>'; }
      $stmt->close();
    } else {
      echo '<div class="alert alert-over alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>'.print_r($logo).'</div>';
    }
  } elseif(isset($_POST['general_save'])){
    function max4Lines($str){
      $str = preg_replace("~[^A-Za-z0-9\-?!=:.,/@€$%()+*öäüÖÄÜß\\n ]~", "", $str);
      while(substr_count($str, "\n") > 3){
        $str = substr_replace($str, ' ', strpos($str, "\n"), 1); //remove first occurence
      }
      return $str;
    }
    $descr = test_input($_POST['general_description']);
    $address = test_input($_POST['general_address']);
    $plz = test_input($_POST['general_postal']);
    $city = test_input($_POST['general_city']);
    $uid = test_input($_POST['general_uid']);
    $phone = test_input($_POST['general_phone']);
    $mail = test_input($_POST['general_mail']);
    $homepage = test_input($_POST['general_homepage']);
    $erpText = test_input($_POST['general_erpText']);
    $left = max4Lines($_POST['general_detail_left']);
    $middle = max4Lines($_POST['general_detail_middle']);
    $right = max4Lines($_POST['general_detail_right']);
    $conn->query("UPDATE companyData SET cmpDescription = '$descr',address = '$address', phone = '$phone', mail = '$mail', homepage = '$homepage', erpText = '$erpText',
      detailLeft = '$left', detailMiddle = '$middle', detailRight = '$right', companyPostal = '$plz', uid = '$uid', companyCity = '$city' WHERE id = $cmpID");
    echo mysqli_error($conn);
  } elseif(isset($_POST['createNewProject']) && !empty($_POST['name'])){
   $name = test_input($_POST['name']);
   if(isset($_POST['status'])){
     $status = "checked";
   } else {
     $status = "";
   }
   $hourlyPrice = floatval(test_input($_POST['hourlyPrice']));
   $hours = floatval(test_input($_POST['hours']));

   if(isset($_POST['createField_1'])){ $field_1 = 'TRUE'; } else { $field_1 = 'FALSE'; }
   if(isset($_POST['createField_2'])){ $field_2 = 'TRUE'; } else { $field_2 = 'FALSE'; }
   if(isset($_POST['createField_3'])){ $field_3 = 'TRUE'; } else { $field_3 = 'FALSE'; }

   $sql = "INSERT INTO $companyDefaultProjectTable(companyID, name, status, hourlyPrice, hours, field_1, field_2, field_3) VALUES($cmpID, '$name', '$status', '$hourlyPrice', '$hours', '$field_1', '$field_2', '$field_3')";
   if($conn->query($sql)){ //add default project to all clients with the company. pow.;
     $conn->query("INSERT IGNORE INTO projectData (clientID, name, status, hours, hourlyPrice, field_1, field_2, field_3) SELECT id,'$name', '$status', '$hours', '$hourlyPrice', '$field_1', '$field_2', '$field_3' FROM $clientTable WHERE companyID = $cmpID");
     if($conn->error){ echo $conn->error; } else { echo '<div class="alert alert-success"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$lang['OK_CREATE'].'</div>'; }
   }
   echo mysqli_error($conn);
 } elseif(isset($_POST['save_defaultProjects']) && isset($_POST['default_projectNames'])){
   if(empty($_POST['default_statii'])){
     $_POST['default_statii'] = array();
   }
   for($i = 0; $i < count($_POST['default_projectNames']); $i++){
     $projectName = test_input($_POST['default_projectNames'][$i]);
     $defaultID = intval($_POST['default_projectIDs'][$i]);
     $hours = floatval(test_input($_POST['default_boughtHours'][$i]));
     $hourlyPrice = floatval(test_input($_POST['default_pricedHours'][$i]));
     if(in_array($defaultID, $_POST['default_statii'])){
       $status = "checked";
     } else {
       $status = "";
     }
     //checkboxes are not set at all if they're not checked
     if(isset($_POST['default_addField_1_'.$defaultID])){ $field_1 = 'TRUE'; } else { $field_1 = 'FALSE'; }
     if(isset($_POST['default_addField_2_'.$defaultID])){ $field_2 = 'TRUE'; } else { $field_2 = 'FALSE'; }
     if(isset($_POST['default_addField_3_'.$defaultID])){ $field_3 = 'TRUE'; } else { $field_3 = 'FALSE'; }

     $sql = "UPDATE $companyDefaultProjectTable SET hours = '$hours', status = '$status', hourlyPrice = '$hourlyPrice', field_1 = '$field_1', field_2 = '$field_2', field_3 = '$field_3' WHERE id = $defaultID";
     $conn->query($sql);

     $sql = "UPDATE projectData SET hourlyPrice = '$hourlyPrice', status='$status', field_1 = '$field_1', field_2 = '$field_2', field_3 = '$field_3'
     WHERE name= '$projectName' AND clientID IN (SELECT id FROM $clientTable WHERE companyID = $cmpID)";
     $conn->query($sql);
   }
   if($conn->error){ echo $conn->error; } else { echo '<div class="alert alert-success"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$lang['OK_ADD'].'</div>'; }
 } elseif(isset($_POST['delete_defaultProjects'])){
   if(isset($_POST['indexProject'])){
     foreach ($_POST['indexProject'] as $i) {
       $sql = "DELETE FROM $companyDefaultProjectTable WHERE id = $i";
       $conn->query($sql);
     }
   }
   if($conn->error){ echo $conn->error; } else { echo '<div class="alert alert-success"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$lang['OK_DELETE'].'</div>'; }
 } elseif(isset($_POST['save_erp_numbers'])){
   $erp_ang = abs(intval($_POST['erp_ang']));
   $erp_aub = abs(intval($_POST['erp_aub']));
   $erp_re = abs(intval($_POST['erp_re']));
   $erp_lfs = abs(intval($_POST['erp_lfs']));
   $erp_gut = abs(intval($_POST['erp_gut']));
   $erp_stn = abs(intval($_POST['erp_stn']));
   $clientNum = test_input($_POST['erp_clientNum']);
   $clientStep = abs(intval($_POST['erp_clientStep']));
   $supplierNum = test_input($_POST['erp_supplierNum']);
   $supplierStep = abs(intval($_POST['erp_supplierStep']));

   $conn->query("UPDATE erp_settings SET erp_ang = $erp_ang, erp_aub = $erp_aub, erp_re = $erp_re, erp_lfs = $erp_lfs, erp_gut = $erp_gut, erp_stn = $erp_stn,
    clientNum = '$clientNum', clientStep = $clientStep, supplierNum = '$supplierNum', supplierStep = $supplierStep WHERE companyID = $cmpID");
   if($conn->error){ echo '<div class="alert alert-danger alert-over"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$conn->error.'</div>'; } else {
     echo '<div class="alert alert-success alert-over"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$lang['OK_SAVE'].'</div>';
   }
 } elseif(isset($_POST['save_erp_reference_numrow'])){
    $ourSign = test_input($_POST['erp_ourSign']);
    $ourMessage = test_input($_POST['erp_ourMessage']);
    $yourSign = test_input($_POST['erp_yourSign']);
    $yourOrder = test_input($_POST['erp_yourOrder']);
    $stmt = $conn->prepare("UPDATE erp_settings SET ourSign = ?, ourMessage = ?, yourSign = ?, yourOrder = ? WHERE companyID = $cmpID");
    echo $conn->error;
    $stmt->bind_param("ssss", $ourSign, $ourMessage, $yourSign, $yourOrder);
    $stmt->execute();
    $stmt->close();
 }

 if(isset($_POST['hire']) && isset($_POST['hiring_userIDs'])){
   foreach($_POST['hiring_userIDs'] as $i){
     $conn->query("INSERT INTO $companyToUserRelationshipTable (companyID, userID) VALUES ($cmpID, $i)");
     if($conn->error){ echo $conn->error; } else { echo '<div class="alert alert-over alert-success"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$lang['OK_ADD'].'</div>'; }
   }
 } elseif(isset($_POST['delete_assigned_users']) && isset($_POST['indexUser'])){
   foreach ($_POST['indexUser'] as $i) {
     $sql = "DELETE FROM $companyToUserRelationshipTable WHERE userID = $i AND companyID = $cmpID";
     $conn->query($sql);
   }
   if($conn->error){ echo $conn->error; } else { echo '<div class="alert alert-over alert-success"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$lang['OK_DELETE'].'</div>'; }
 } elseif(isset($_POST['save_additional_fields'])){
   for($i = 1; $i < 4; $i++){
     //linear mapping of f(x) = x*3 -2; f1(x) = f(x) + 1; f2(x) = f1(x)+1;
     $id_save = $cmpID * 3 - 3 + $i;
     if(!empty($_POST['name_'.$i])){
       $name = test_input($_POST['name_'.$i]);
       $description = test_input($_POST['description_'.$i]);
       $active = !empty($_POST['active_'.$i]) ? 'TRUE' : 'FALSE';
       $required = !empty($_POST['required_'.$i]) ? 'TRUE' : 'FALSE';
       $forall = !empty($_POST['forall_'.$i]) ? 'TRUE' : 'FALSE';
       if($active == 'FALSE'){
         $forall = 'FALSE';
       }
       $result = $conn->query("SELECT * FROM $companyExtraFieldsTable WHERE id = $id_save");
       if($result->num_rows > 0){
         $row = $result->fetch_assoc();
         $forall_old = $row['isForAllProjects'];
         $conn->query("UPDATE $companyExtraFieldsTable SET name='$name', isActive = '$active' , isRequired = '$required', isForAllProjects = '$forall', description = '$description' WHERE id = $id_save");
       } else {
         $forall_old = 'FALSE';
         $conn->query("INSERT INTO $companyExtraFieldsTable (id, companyID, name, isActive, isRequired, isForAllProjects, description) VALUES ($id_save, $cmpID, '$name', '$active', '$required', '$forall', '$description')");
       }
       echo mysqli_error($conn);
       if($forall_old == 'FALSE' && $forall == 'TRUE'){
         $conn->query("UPDATE $projectTable, $clientTable SET $projectTable.field_$i = '$active' WHERE $clientTable.companyID = $cmpID AND $clientTable.id = $projectTable.clientID");
       } elseif($forall_old == 'TRUE' && $forall == 'FALSE'){
         $conn->query("UPDATE $projectTable, $clientTable SET $projectTable.field_$i = 'FALSE' WHERE $clientTable.companyID = $cmpID AND $clientTable.id = $projectTable.clientID");
       }
     } elseif(!empty($_POST['active_'.$i])){ //if name is empty, but the active button is not
        echo '<div class="alert alert-danger alert-over"><a href="#" data-dismiss="alert" class="close">&times;</a><strong>Could not save Field nr. $i : </strong> Cannot set active field without a name</div>';
     }
   }
 } elseif(isset($_POST['field_add_save']) && !empty($_POST['field_add_name'])){
   $i = intval($_POST['field_add_save']);
   if(0 < $i && $i < 4){
     $id_save = $cmpID * 3 - 3 + $i;
     $name = test_input($_POST['field_add_name']);
     $description = test_input($_POST['field_add_description']);
     $active = !empty($_POST['field_add_active']) ? 'TRUE' : 'FALSE';
     $required = !empty($_POST['field_add_required']) ? 'TRUE' : 'FALSE';
     $forall = !empty($_POST['field_add_forall']) ? 'TRUE' : 'FALSE';
     $conn->query("INSERT INTO $companyExtraFieldsTable (id, companyID, name, isActive, isRequired, isForAllProjects, description) VALUES ($id_save, $cmpID, '$name', '$active', '$required', '$forall', '$description')");
   } else {
     echo '<div class="alert alert-over alert-danger"><a href="#" data-dismiss="alert">&times;</a>'.$lang['ERROR_UNEXPECTED'].'</div>';
   }
 } elseif(isset($_POST['addFinanceAccount'])){
    if(!empty($_POST['addFinance_name']) && !empty($_POST['addFinance_num']) && $_POST['addFinance_num'] < 2999 && $_POST['addFinance_num'] > 2000){
      $name = test_input($_POST['addFinance_name']);
      $num = intval($_POST['addFinance_num']);
      $ggk = 'FALSE';
      if(isset($_POST['addFinance_booking'])) $ggk = 'TRUE';
      $opt = 'STAT';
      if(isset($_POST['addOption'])) $opt = 'CONT';
      $conn->query("INSERT INTO accounts (companyID, num, name, manualBooking, options) VALUES('$cmpID', $num, '$name', '$ggk', '$opt')");
      if($conn->error){
        echo '<div class="alert alert-over alert-success"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$lang['ERROR_DUPLICATE'].$conn->error.'</div>';
      } else {
        echo '<div class="alert alert-over alert-success"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$lang['OK_ADD'].'</div>';
      }
    } else {
      echo '<div class="alert alert-over alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$lang['ERROR_INVALID_DATA'].'</div>';
    }
  } elseif(isset($_POST['saveFinances']) && isset($_POST['finance_istVersteuerer'])){
    $val = 'FALSE';
    if($_POST['finance_istVersteuerer']) $val = 'TRUE';
    $conn->query("UPDATE companyData SET istVersteuerer = '$val' WHERE id = $cmpID");
    if($conn->error){
      echo '<div class="alert alert-over alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$conn->error.'</div>';
    } else {
      echo '<div class="alert alert-over alert-success"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$lang['OK_SAVE'].'</div>';
    }
  } elseif(!empty($_POST['turnBookingOn'])){
    $val = intval($_POST['turnBookingOn']);
    $conn->query("UPDATE accounts SET manualBooking = 'TRUE' WHERE id = $val AND companyID IN (".implode(', ', $available_companies).")");
    if($conn->error){
      echo '<div class="alert alert-over alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$conn->error.'</div>';
    } else {
      echo '<div class="alert alert-over alert-success"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$lang['OK_SAVE'].'</div>';
    }
  } elseif(!empty($_POST['turnBookingOff'])){
    $val = intval($_POST['turnBookingOff']);
    $conn->query("UPDATE accounts SET manualBooking = 'FALSE' WHERE id = $val AND companyID IN (".implode(', ', $available_companies).")");
    if($conn->error){
      echo '<div class="alert alert-over alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$conn->error.'</div>';
    } else {
      echo '<div class="alert alert-over alert-success"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$lang['OK_SAVE'].'</div>';
    }
  }

  if(isset($_FILES['csvUpload']) && !$_FILES['csvUpload']['error']){
    //validate uploaded file
    function convSet($s){
      $s = preg_replace("~[^A-Za-z0-9\-!:.,$()+öäüÖÄÜß ]~", "", $s);
      return trim(iconv(mb_detect_encoding($s), "UTF-8", $s));
    }
    $error = '';
    $file_info = pathinfo($_FILES['csvUpload']['name']);
    $ext = strtolower($file_info['extension']);
    if(strtolower($file_info['extension']) != 'csv'){ $error = "Invalid file Extension"; }
    if(!$error){
      $file = fopen($_FILES['csvUpload']['tmp_name'], "r");
      if(!$file) {$error = 'Could not open file'; }
    }
    if(!$error){
      $i = 0;
      if(isset($_POST['uploadClients']) || isset($_POST['uploadSuppliers'])){
        $isSupplier = 'TRUE';
        if(isset($_POST['uploadClients'])) $isSupplier = 'FALSE';
        $stmt_client = $conn->prepare("INSERT INTO clientData (name, companyID, clientNumber, isSupplier) VALUES (?, $cmpID, ?, '$isSupplier')");
        $stmt_client_detail = $conn->prepare("INSERT INTO clientInfoData (clientID, name, firstname, title, nameAddition, gender, address_Street, address_Country, address_Country_Postal,
        address_Country_City, address_Addition, phone, fax_number, debitNumber, datev, accountName, taxnumber, taxArea, vatnumber, customerGroup, creditLimit, lastFaktura, karenztage,
        warning1, warning2, warning3) VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) ");
        if(!$conn->error && !$stmt_client->error && !$stmt_client_detail->error){
          $stmt_client->bind_param("ss", $name, $num);
          $stmt_client_detail->bind_param("issssssssssssiisssssdsiddd", $clientID, $lastname, $firstname, $title, $nameAddition, $gender, $street, $country, $postal, $city, $addressAddition, $phone, $fax, $debit, $datev, $accountName, $taxNum, $taxArea, $uid, $customerGroup, $creditLimit, $faktura, $karenztage, $warn1, $warn2, $warn3);
        } else {
          echo $conn->error .' err1<br>';
          echo $stmt_client->error .' err2<br>';
          echo $stmt_client_detail->error .' err3<br>';
        }
        fgetcsv($file); //skip 1st line
        while(($line = fgetcsv($file, 0, ";")) !== false){
          if(empty($line)) continue;
          if(empty($line[0])) continue;
          $name = convSet($line[0]);
          $num = $line[1];
          $res = $conn->query("SELECT id FROM clientData WHERE clientNumber = '$num' AND isSupplier = '$isSupplier' "); echo $conn->error;
          if($res && $res->num_rows > 0){
            $row = $res->fetch_assoc();
            $clientID = $row['id'];
            $conn->query("UPDATE clientData SET name = '$name' WHERE id = $clientID"); echo $conn->error;
            $conn->query("DELETE FROM clientInfoData WHERE clientID = $clientID"); echo $conn->error;
          } else {
            $stmt_client->execute();
            $clientID = $conn->insert_id;
          }
          $firstname = convSet($line[2]);
          $lastname = convSet($line[3]);
          $title = convSet($line[4]);
          $nameAddition = convSet($line[5]);
          $gender = in_array($line[6], array('male', 'female')) ? $line[6] : 'male';
          $street = convSet($line[7]);
          $postal = $line[8];
          $city = convSet($line[9]);
          $country = convSet($line[10]);
          $addressAddition = convSet($line[11]);
          $phone = $line[12];
          $fax = $line[13];
          $debit = intval($line[14]);
          $datev = intval($line[15]);
          $accountName = convSet($line[16]);
          $taxNum = $line[17];
          $taxArea = convSet($line[18]);
          $uid = $line[19];
          $customerGroup = $line[20];
          $creditLimit = floatval($line[21]);
          $faktura = empty(trim($line[22])) ? '0000-00-00 00:00:00' : $line[22];
          $karenztage = intval($line[23]);
          $warn1 = floatval($line[24]);
          $warn2 = floatval($line[25]);
          $warn3 = floatval($line[26]);
          $stmt_client_detail->execute();
          $i++;
        }
        $stmt_client->close();
        $stmt_client_detail->close();
      } elseif(isset($_POST['uploadArticles'])){
        $stmt = $conn->prepare("INSERT INTO articles(name, description, price, unit, cash, purchase, taxID) VALUES(?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssdssdi", $name, $descr, $price, $unit, $cash, $purchase, $taxID);
        fgetcsv($file); //skip 1st line
        while(($line= fgetcsv($file, 0, ';')) !== false){
          if(empty($line)) continue;
          $name = convSet($line[0]);
          $descr = convSet($line[1]);
          $price = floatval($line[2]);
          $unit = convSet($line[3]);
          $cash = $line[4];
          $purchase = floatval($line[5]);
          $taxID = intval($line[6]);
          if($name && $price){
            $i++;
            $stmt->execute();
          }
        }
        $stmt->close();
      } else {
        echo '<div class="alert alert-over alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$lang['ERROR_UNEXPECTED'].'</div>';
      }
      if($conn->error){
        echo '<div class="alert alert-over alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$conn->error.'</div>';
      } else {
        echo '<div class="alert alert-over alert-success"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$i.' '.$lang['OK_SAVE'].'</div>';
      }
    } else {
      echo '<div class="alert alert-over alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$error.'</div>';
    }
  }

} //end POST

$result = $conn->query("SELECT * FROM companyData WHERE id = $cmpID");
if ($result && ($companyRow = $result->fetch_assoc()) && in_array($companyRow['id'], $available_companies)):
?>

<div class="page-seperated-body">
<div class="page-header page-seperated-section">
  <h3><?php echo $lang['COMPANY'] .' - '.$companyRow['name']; ?>
    <div class="page-header-button-group">
      <button type="button" class="btn btn-default btn-sm" data-toggle="modal" data-target=".cmp-delete-confirm-modal" title="<?php echo $lang['DELETE']; ?>"><i class="fa fa-trash-o"></i></button>
    </div>
  </h3>
</div>

<form method="POST">
  <div class="modal fade cmp-delete-confirm-modal" tabindex="-1" role="dialog" aria-labelledby="mySmallModalLabel">
    <div class="modal-dialog modal-sm" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h4 class="modal-title"><?php echo sprintf($lang['ASK_DELETE'], $companyRow['name']); ?></h4>
        </div>
        <div class="modal-body">
          <?php echo $lang['WARNING_DELETE_COMPANY']; ?>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo $lang['CONFIRM_CANCEL']; ?></button>
          <button type="submit" name='deleteCompany' class="btn btn-warning"><?php echo $lang['CONFIRM']; ?></button>
        </div>
      </div>
    </div>
  </div>
</form>

<!-- LOGO -->
<form method="post" enctype="multipart/form-data" class="page-seperated-section">
  <div class="container-fluid">
    <div class="row">
      <h4> Logo
        <div class="page-header-button-group">
          <button type="submit" name="delete_logo" class="btn btn-default"><i class="fa fa-trash-o"></i></button>
          <button type="submit" name="save_logo" class="btn btn-default" value="<?php echo $cmpID;?>"><i class="fa fa-floppy-o"></i></button>
        </div>
      </h4>
    </div>
  </div>
  <div class="container-fluid">
    <div class="col-sm-4">
      <?php if($companyRow['logo']){echo '<img style="max-width:350px;max-height:200px;" src="data:image/jpeg;base64,'.base64_encode( $companyRow['logo'] ).'"/>';} ?>
    </div>
    <div class="col-sm-8">
      <input type="file" name="fileToUpload"/>
      <small>Empfohlen 350x200px; Max. 5MB</small>
    </div>
  </div>
</form>
<br>

<!-- GENERAL -->
<form method="POST" class="page-seperated-section">
  <div class="container-fluid">
    <div class="row">
      <h4>
        <?php echo $lang['GENERAL_SETTINGS']; ?>
        <div class="page-header-button-group"><button type="submit" name="general_save" class="btn btn-default blinking" value="<?php echo $cmpID;?>" title="<?php echo $lang['SAVE']; ?>"><i class="fa fa-floppy-o"></i></button></div>
      </h4>
    </div>
    <br>
    <div class="row">
      <div class="col-sm-3"><label><?php echo $lang['COMPANY_NAME']; ?></label></div>
      <div class="col-sm-9">
        <input type="text" class="form-control" name="general_description" value="<?php echo $companyRow['cmpDescription'];?>" />
      </div>
    </div>
    <div class="row">
      <div class="col-sm-3"><label><?php echo $lang['ADDRESS']; ?></label></div>
      <div class="col-sm-9">
        <input type="text" class="form-control" name="general_address" placeholder="Address" value="<?php echo $companyRow['address'];?>" />
      </div>
    </div>
    <div class="row">
      <div class="col-sm-3"><label><?php echo $lang['PLZ']; ?></label></div>
      <div class="col-sm-4">
        <input type="text" class="form-control" name="general_postal" value="<?php echo $companyRow['companyPostal'];?>" />
      </div>
      <div class="col-sm-1 text-center"><label><?php echo $lang['CITY']; ?></label></div>
      <div class="col-sm-4">
        <input type="text" maxlength="54" class="form-control" name="general_city" value="<?php echo $companyRow['companyCity'];?>" />
      </div>
    </div>
    <div class="row">
      <div class="col-sm-3"><label>UID</label></div>
      <div class="col-sm-9">
        <input type="text" class="form-control" name="general_uid" value="<?php echo $companyRow['uid'];?>" />
      </div>
    </div>
    <div class="row">
      <div class="col-sm-3"><label><?php echo $lang['PHONE_NUMBER']; ?></label></div>
      <div class="col-sm-9">
        <input type="text" class="form-control" name="general_phone" placeholder="Tel nr." value="<?php echo $companyRow['phone'];?>"/>
      </div>
    </div>
    <div class="row">
      <div class="col-sm-3"><label>E-Mail</label></div>
      <div class="col-sm-9">
        <input type="mail" class="form-control"  name="general_mail" placeholder="E-Mail" value="<?php echo $companyRow['mail'];?>" />
      </div>
    </div>
    <div class="row">
      <div class="col-sm-3"><label>Homepage</label></div>
      <div class="col-sm-9">
        <input type="text" class="form-control"  name="general_homepage" placeholder="Homepage" value="<?php echo $companyRow['homepage'];?>" />
      </div>
    </div>
    <div class="row">
      <div class="col-sm-3"><label>ERP Text</label></div>
      <div class="col-sm-9">
        <textarea name="general_erpText" class="form-control" placeholder=""><?php echo $companyRow['erpText'];?></textarea>
      </div>
    </div>
    <div class="row">
      <div class="col-sm-3"><label>Detail (<?php echo $lang['FOOT_NOTE']; ?>)</label></div>
      <div class="col-sm-3">
        <textarea name="general_detail_left" class="form-control" placeholder="" maxlength="140" rows="3"><?php echo $companyRow['detailLeft'];?></textarea>
      </div>
      <div class="col-sm-3 text-center">
        <textarea name="general_detail_middle" class="form-control" placeholder="" maxlength="140" rows="3"><?php echo $companyRow['detailMiddle'];?></textarea>
      </div>
      <div class="col-sm-3 text-right">
        <textarea name="general_detail_right" class="form-control" placeholder="" maxlength="140" rows="3"><?php echo $companyRow['detailRight'];?></textarea>
      </div>
    </div>
  </div>
</form>
<br>

<!-- DEFAULT PROJECTS -->
<form method="POST" class="page-seperated-section">
  <div class="container-fluid">
    <div class="row">
      <h4>
        <?php echo $lang['DEFAULT'] . " " . $lang['PROJECT']; ?>
        <div class="page-header-button-group">
          <button type="button" class="btn btn-default" data-toggle="modal" data-target=".cmp-new-project-modal" title="<?php echo $lang['ADD']; ?>"><i class="fa fa-plus"></i></button>
          <button type="submit" class="btn btn-default" name="delete_default_projects" title="<?php echo $lang['DELETE']; ?>"><i class="fa fa-trash-o"></i></button>
          <button type="button" class="btn btn-default" data-toggle="modal" data-target=".cmp-edit-project-modal" title="<?php echo $lang['EDIT']; ?>"><i class="fa fa-pencil"></i></button>
        </div>
      </h4>
    </div>
  </div>
  <table class="table table-hover">
    <thead>
      <th><?php echo $lang['DELETE']; ?></th>
      <th>Name</th>
      <th>Status</th>
      <th><?php echo $lang['ADDITIONAL_FIELDS']; ?></th>
      <th><?php echo $lang['HOURS']; ?></th>
      <th><?php echo $lang['HOURLY_RATE']; ?></th>
    </thead>
    <tbody>
      <?php
      $query = "SELECT * FROM $companyDefaultProjectTable WHERE companyID = $cmpID";
      $projectResult = mysqli_query($conn, $query);
      if($projectResult && $projectResult->num_rows > 0){
        while($projectRow = $projectResult->fetch_assoc()){
          $i = $projectRow['id'];
          $projectRowStatus = (!empty($projectRow['status']))? $lang['PRODUCTIVE']:'';
          echo "<tr><td><input type='checkbox' name='indexProject[]' value='$i'></td>";
          echo "<td>".$projectRow['name']."</td>";
          echo "<td> $projectRowStatus </td>";
          echo '<td>';
          $j = 1;
          $extraResult = $conn->query("SELECT * FROM $companyExtraFieldsTable WHERE companyID = $cmpID AND isActive = 'TRUE' ORDER BY id ASC");
          while($extraResult && ($extraRow = $extraResult->fetch_assoc())){ if($projectRow['field_'.$j] == 'TRUE'){echo $extraRow['name'] .'<br>';} $j++; }
          echo '</td>';
          echo "<td>".$projectRow['hours']."</td>";
          echo "<td>".$projectRow['hourlyPrice']."</td></tr>";
        }
      }
      ?>
    </tbody>
  </table>

  <div class="modal fade cmp-new-project-modal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h4 class="modal-title"><?php echo $lang['ADD']; ?></h4>
        </div>
        <div class="modal-body">
          <input type="text" class="form-control" name="name" placeholder="Name">
          <br>
          <div class="row">
            <div class="col-md-6">
              <input type="number" step="any" class="form-control" name="hours" placeholder="Hours">
            </div>
            <div class="col-md-6">
              <input type="number" step="any" class="form-control" name="hourlyPrice" placeholder="Price/Hour">
            </div>
          </div>
          <br>
          <div class="row">
            <div class="col-md-6" style="padding-left:50px;">
              <div class="checkbox"><input type="checkbox" name="status" value="checked" checked> <i class="fa fa-tags"></i> <?php echo $lang['PRODUCTIVE']; ?></div>
            </div>
            <div class="col-md-6" style="padding-left:50px;">
              <div class="checkbox">
                <?php
                $resF = $conn->query("SELECT * FROM $companyExtraFieldsTable WHERE companyID = $cmpID ORDER BY id ASC");
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
          <button type="submit" class="btn btn-warning" name='createNewProject'> <?php echo $lang['ADD']; ?> </button>
        </div>
      </div>
    </div>
  </div>
  <div class="modal fade cmp-edit-project-modal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg modal-content" role="document">
      <div class="modal-header">
        <h4 class="modal-title"><?php echo $lang['DEFAULT'] .' '.$lang['PROJECT']; ?></h4>
      </div>
      <div class="modal-body">
        <table class="table table-hover">
          <thead>
            <th>Name</th>
            <th><?php echo $lang['PRODUCTIVE']; ?></th>
            <th><?php echo $lang['ADDITIONAL_FIELDS']; ?></th>
            <th><?php echo $lang['HOURS']; ?></th>
            <th><?php echo $lang['HOURLY_RATE']; ?></th>
            <th></th>
          </thead>
          <tbody>
            <?php
            $result = $conn->query("SELECT * FROM $companyDefaultProjectTable WHERE companyID = $cmpID");
            echo mysqli_error($conn);
            while($row = $result->fetch_assoc()){
              echo '<tr>';
              echo '<td>'. $row['name'] .'</td>';
              echo '<td><div class="checkbox text-center"><input type="checkbox" name="default_statii[]" '. $row['status'] .' value="'.$row['id'].'"> <i class="fa fa-tags"></i></div></td>';
              echo '<td><small>';
              $resF = $conn->query("SELECT * FROM $companyExtraFieldsTable WHERE companyID = $cmpID ORDER BY id ASC");
              if($resF->num_rows > 0){
                $rowF = $resF->fetch_assoc();
                if($rowF['isActive'] == 'TRUE'){
                  $checked = $row['field_1'] == 'TRUE' ? 'checked': '';
                  echo '<input type="checkbox" '.$checked.' name="default_addField_1_'.$row['id'].'"/>'. $rowF['name'];
                }
              }
              if($resF->num_rows > 1){
                $rowF = $resF->fetch_assoc();
                if($rowF['isActive'] == 'TRUE'){
                  $checked = $row['field_2'] == 'TRUE' ? 'checked': '';
                  echo '<br><input type="checkbox" '.$checked.' name="default_addField_2_'.$row['id'].'" />'. $rowF['name'];
                }
              }
              if($resF->num_rows > 2){
                $rowF = $resF->fetch_assoc();
                if($rowF['isActive'] == 'TRUE'){
                  $checked = $row['field_3'] == 'TRUE' ? 'checked': '';
                  echo '<br><input type="checkbox" '.$checked.' name="default_addField_3_'.$row['id'].'" />'. $rowF['name'];;
                }
              }
              echo '</small></td>';
              echo '<td><input type="number" class="form-control" step="any" name="default_boughtHours[]" value="'. $row['hours'] .'"></td>';
              echo '<td><input type="number" class="form-control" step="any" name="default_pricedHours[]" value="'. $row['hourlyPrice'] .'"></td>';
              echo '<td><input type="text" class="hidden" name="default_projectNames[]" value="'.$row['name'].'"><input type="text" class="hidden" name="default_projectIDs[]" value="'.$row['id'].'"></td>';
              echo '</tr>';
            }
            ?>
          </tbody>
        </table>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
        <button type=submit class="btn btn-warning" name='save_defaultProjects'> <?php echo $lang['SAVE']; ?> </button>
      </div>
    </div>
  </div>
</form>
<br>

<!-- ASSIGNED USERS -->
<form method="POST" class="page-seperated-section">
  <div class="container-fluid">
    <div class="row">
      <h4><?php echo $lang['ASSIGNED'] . " " . $lang['USERS']; ?>
        <div class="page-header-button-group">
          <button type="button" class="btn btn-default" data-toggle="modal" data-target=".cmp-hire-users-modal" title="<?php echo $lang['ADD']; ?>"><i class="fa fa-plus"></i></button>
          <button type="submit" class="btn btn-default" name="delete_assigned_users" title="<?php echo $lang['DELETE']; ?>"><i class="fa fa-trash-o"></i></button>
        </div>
      </h4>
    </div>
  </div>
  <table class="table table-hover">
    <thead>
      <th><?php echo $lang['DELETE']; ?></th>
      <th>Name</th>
    </thead>
    <tbody>
      <?php
      $query = "SELECT DISTINCT * FROM $userTable
      INNER JOIN $companyToUserRelationshipTable ON $userTable.id = $companyToUserRelationshipTable.userID
      WHERE $companyToUserRelationshipTable.companyID = $cmpID";
      $usersResult = mysqli_query($conn, $query);
      if ($usersResult && $usersResult->num_rows > 0) {
        while ($usersRow = $usersResult->fetch_assoc()) {
          $i = $usersRow['id'];
          echo "<tr><td><input type='checkbox' name='indexUser[]' value= $i></td>";
          echo "<td>".$usersRow['firstname']." ".$usersRow['lastname']."</td></tr>";
        }
      }
      ?>
    </tbody>
  </table>
  <div class="modal fade cmp-hire-users-modal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-md" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h4 class="modal-title"><?php echo $lang['HIRE_USER']; ?></h4>
        </div>
        <div class="modal-body">
          <table class="table table-hover">
            <thead>
              <th>Select</th>
              <th>Name</th>
            </thead>
            <tbody>
              <?php
              $sql = "SELECT * FROM $userTable WHERE id NOT IN (SELECT DISTINCT userID FROM $companyToUserRelationshipTable WHERE companyID = $cmpID) ORDER BY lastname ASC";
              $result = mysqli_query($conn, $sql);
              if($result && $result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {
                  echo '<tr>';
                  echo '<td><input type="checkbox" name="hiring_userIDs[]" value="'.$row['id'].'" ></td>';
                  echo '<td>'.$row['firstname'].' '.$row['lastname'].'</td>';
                  echo '</tr>';
                }
              }
              ?>
            </tbody>
          </table>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-warning" name="hire"><?php echo $lang['HIRE_USER']; ?></button>
        </div>
      </div>
    </div>
  </div>
</form>
<br>

<!-- ADDITIONAL BOOKING FIELDS -->
<?php $fieldResult = $conn->query("SELECT * FROM $companyExtraFieldsTable WHERE companyID = $cmpID"); $i = 1; ?>
<form method="POST" class="page-seperated-section">
  <div class="container-fluid">
    <div class="row">
      <h4><?php echo $lang['ADDITIONAL_FIELDS']; ?>
        <div class="page-header-button-group">
          <?php if($fieldResult->num_rows < 3){ echo '<button type="button" class="btn btn-default" data-toggle="modal" data-target=".cmp-add-fields-modal" title="'.$lang['ADD'].'"><i class="fa fa-plus"></i></button>'; } ?>
          <button type="button" class="btn btn-default" data-toggle="modal" data-target=".cmp-edit-fields-modal" ><i class="fa fa-pencil"></i></button>
        </div>
      </h4>
    </div>
  </div>
  <table class="table table-hover">
    <thead>
      <th><?php echo $lang['ACTIVE']; ?></th>
      <th><?php echo $lang['HEADLINE']; ?></th>
      <th><?php echo $lang['REQUIRED_FIELD']; ?></th>
      <th><?php echo $lang['FOR_ALL_PROJECTS']; ?></th>
      <th><?php echo $lang['DESCRIPTION']; ?></th>
    </thead>
    <tbody>
      <?php
      while ($fieldResult && ($fieldRow = $fieldResult->fetch_assoc())) {
        $i++;
        echo '<tr>';
        echo '<td>'.$fieldRow['isActive'].'</td>';
        echo '<td>'.$fieldRow['name'].'</td>';
        echo '<td>'.$fieldRow['isRequired'].'</td>';
        echo '<td>'.$fieldRow['isForAllProjects'].'</td>';
        echo '<td>'.$fieldRow['description'].'</td>';
        echo '</tr>';
      }
      ?>
    </tbody>
  </table>
  <div class="modal fade cmp-add-fields-modal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h4 class="modal-title"><?php echo $lang['ADD']; ?></h4>
        </div>
        <div class="modal-body">
          <table class="table table-hover">
            <thead>
              <th>Aktiv</th>
              <th width="200px">Überschrift</th>
              <th>Pflichtfeld</th>
              <th width="150px">Für Alle Projekte</th>
              <th>Beschreibung</th>
            </thead>
            <tbody>
              <?php
                echo "<td><input type='checkbox' name='field_add_active' checked /></td>";
                echo "<td><input type='text' class='form-control required-field' name='field_add_name' maxlength='15' placeholder='max. 15 Zeichen' /></td>";
                echo "<td><input type='checkbox' name='field_add_required' /></td>";
                echo "<td><input type='checkbox' name='field_add_forall' /></td>";
                echo "<td><input type='text' class='form-control' name='field_add_description' maxlength='50' placeholder='max. 50 Zeichen' /></td>";
                echo '</tr>';
              ?>
            </tbody>
          </table>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
          <button type="submit" name="field_add_save" class="btn btn-warning" value="<?php echo $i; ?>"><?php echo $lang['SAVE']; ?></button>
        </div>
      </div>
    </div>
  </div>
  <div class="modal fade cmp-edit-fields-modal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h4 class="modal-title"><?php echo $lang['ADDITIONAL_FIELDS']; ?></h4>
        </div>
        <div class="modal-body">
          <table class="table table-hover">
            <thead>
              <th>Aktiv</th>
              <th width="200px">Überschrift</th>
              <th>Pflichtfeld</th>
              <th width="150px">Für Alle Projekte</th>
              <th>Beschreibung</th>
            </thead>
            <tbody>
              <?php
              for($i = 1; $i < 4; $i++){
                $a = $cmpID * 3 - 3 + $i ;
                $result = $conn->query("SELECT * FROM $companyExtraFieldsTable WHERE id = ($a)");
                echo '<tr>';
                if($result->num_rows > 0){
                  $row = $result->fetch_assoc();
                  $active = $row['isActive'] == 'TRUE' ? 'checked' : '';
                  $name = $row['name'];
                  $required = $row['isRequired'] == 'TRUE' ? 'checked' : '';
                  $forAllProjects = $row['isForAllProjects'] == 'TRUE' ? 'checked' : '';
                  $description = $row['description'];
                } else {
                  $row = $active = $name = $required = $forAllProjects = $description = '';
                }
                echo "<td><input type='checkbox' name='active_$i' $active /></td>";
                echo "<td><input type='text' class='form-control' name='name_$i' maxlength='15' placeholder='max. 15 Zeichen' value='$name' /></td>";
                echo "<td><input type='checkbox' name='required_$i' $required /></td>";
                echo "<td><input type='checkbox' name='forall_$i' $forAllProjects /></td>";
                echo "<td><input type='text' class='form-control' name='description_$i' maxlength='50' placeholder='max. 50 Zeichen' value='$description' /></td>";
                echo '</tr>';
              }
              ?>
            </tbody>
          </table>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
          <button type="submit" name="save_additional_fields" class="btn btn-warning" value="<?php echo $cmpID; ?>"><?php echo $lang['SAVE']; ?></button>
        </div>
      </div>
    </div>
  </div>
</form><br>

<!-- ERP NUMBERS -->
<?php
$result = $conn->query("SELECT * FROM erp_settings WHERE companyID = $cmpID");
$row = $result->fetch_assoc();
?>
<form method="POST" class="page-seperated-section">
  <h4>ERP <?php echo $lang['SETTINGS']; ?><a role="button" data-toggle="collapse" href="#erp_number_info"><i class="fa fa-info-circle"></i></a>
    <div class="page-header-button-group">
      <button type="submit" class="btn btn-default blinking" name="save_erp_numbers"><i class="fa fa-floppy-o"></i></button>
    </div>
  </h4>
  <div class="container-fluid">
    <br>
    <div class="collapse" id="erp_number_info">
      <div class="well">
        Festsetzen der kleinsten Nummer des nächsten Auftrags. <br>
        Aufträge werden fortlaufend nummeriert, beginnend von der höchsten, bereits vorhandenen Zahl, welche zum ausgewählten Vorgang und jeweiligen Mandanten passt. <br>
        Dieser sog. mindest-Offset kann hier zustäzlich eingestellt werden. Ist die eingetragene Zahl die größte vorhandene Zahl, wird diese als nächste Nummer festgelegt.
      </div>
    </div>
    <div class="col-md-4">
      <label><?php echo $lang['PROPOSAL_TOSTRING']['ANG']; ?></label>
      <input type="number" class="form-control" name="erp_ang" value="<?php echo $row['erp_ang']; ?>" min="1"/>
    </div>
    <div class="col-md-4">
      <label><?php echo $lang['PROPOSAL_TOSTRING']['AUB']; ?></label>
      <input type="number" class="form-control" name="erp_aub" value="<?php echo $row['erp_aub']; ?>" min="1"/>
    </div>
    <div class="col-md-4">
      <label><?php echo $lang['PROPOSAL_TOSTRING']['RE']; ?></label>
      <input type="number" class="form-control" name="erp_re" value="<?php echo $row['erp_re']; ?>" min="1"/>
    </div>
  </div>
  <br>
  <div class="container-fluid">
    <div class="col-md-4">
      <label><?php echo $lang['PROPOSAL_TOSTRING']['LFS']; ?></label>
      <input type="number" class="form-control" name="erp_lfs" value="<?php echo $row['erp_lfs']; ?>" min="1"/>
    </div>
    <div class="col-md-4">
      <label><?php echo $lang['PROPOSAL_TOSTRING']['GUT']; ?></label>
      <input type="number" class="form-control" name="erp_gut" value="<?php echo $row['erp_gut']; ?>" min="1"/>
    </div>
    <div class="col-md-4">
      <label><?php echo $lang['PROPOSAL_TOSTRING']['STN']; ?></label>
      <input type="number" class="form-control" name="erp_stn" value="<?php echo $row['erp_stn']; ?>" min="1"/>
    </div>
  </div><br>
  <br>
  <div class="container-fluid">
    <div class="col-md-4"><label>Kundennummernkreis</label><input type="text" name="erp_clientNum" class="form-control" placeholder="Anfangswert" value="<?php echo $row['clientNum']; ?>"/></div>
    <div class="col-md-3"><label>Schrittweite</label><input type="number" name="erp_clientStep" class="form-control" value="<?php echo $row['clientStep']; ?>"/></div>
  </div>
  <br>
  <div class="container-fluid">
    <div class="col-md-4"><label>Lieferantennummernkreis</label><input type="text" name="erp_supplierNum" class="form-control" placeholder="Anfangswert" value="<?php echo $row['supplierNum']; ?>"/></div>
    <div class="col-md-3"><label>Schrittweite</label><input type="number" name="erp_supplierStep" class="form-control" value="<?php echo $row['supplierStep']; ?>"/></div>
    <small><i class="fa fa-arrow-right"></i> Buchstaben werden immmer an den Anfang gestellt</small>
  </div>
</form><br>

<!-- ERP REFERENCE ROW -->
<?php
$result = $conn->query("SELECT * FROM erp_settings WHERE companyID = $cmpID");
$row = $result->fetch_assoc();
?>

<form method="POST" class="page-seperated-section">
  <h4>ERP <?php echo $lang['REFERENCE_NUMERAL_ROW']; ?><a role="button" data-toggle="collapse" href="#erp_reference_info"><i class="fa fa-info-circle"></i></a>
    <div class="page-header-button-group">
      <button type="submit" class="btn btn-default blinking" name="save_erp_reference_numrow"><i class="fa fa-floppy-o"></i></button>
    </div>
  </h4>
  <div class="container-fluid">
    <br>
    <div class="collapse" id="erp_reference_info">
      <div class="well">
        Die Bezugszeichenzeile wird in Vorgängen gedruckt. Ändern sich die Werte hier, ändern Sie sich auch in allen Vorgängen.
      </div>
    </div>
    <div class="col-md-6">
      <label><?php echo $lang['PROP_OUR_SIGN']; ?></label>
        <input type="text" class="form-control" name="erp_ourSign" value="<?php echo $row['ourSign']; ?>" maxlength="25"/>
    </div>
    <div class="col-md-6">
      <label><?php echo $lang['PROP_OUR_MESSAGE']; ?></label>
        <input type="text" class="form-control" name="erp_ourMessage" value="<?php echo $row['ourMessage']; ?>" maxlength="25"/>
    </div>
  </div>
  <br>
  <div class="container-fluid">
    <div class="col-md-6">
      <label><?php echo $lang['PROP_YOUR_SIGN']; ?></label>
        <input type="text" class="form-control" name="erp_yourSign" value="<?php echo $row['yourSign']; ?>" maxlength="25"/>
    </div>
    <div class="col-md-6">
      <label><?php echo $lang['PROP_YOUR_ORDER']; ?></label>
        <input type="text" class="form-control" name="erp_yourOrder" value="<?php echo $row['yourOrder']; ?>" maxlength="25"/>
    </div>
  </div><br>
</form><br>

<!-- FINANCES -->
<form method="POST" class="page-seperated-section">
  <h4><?php echo $lang['FINANCES']; ?>
    <div class="page-header-button-group">
      <button type="button" class="btn btn-default" data-toggle="modal" data-target=".add-finance-account" title="<?php echo $lang['ADD']; ?>" ><i class="fa fa-plus"></i></button>
      <button id="blinker" type="submit" class="btn btn-default" name="saveFinances" title="<?php echo $lang['SAVE']; ?>"><i class="fa fa-floppy-o"></i></button>
    </div>
  </h4>
  <div class="container-fluid">
    <br><br>
    <div class="row">
      <div class="col-sm-2 radio"><label><strong><?php echo $lang['VAT']; ?>:</strong></label></div>
      <div class="col-sm-8 radio">
        <label><input onchange="startBlinker();" type="radio" name="finance_istVersteuerer" value="1" <?php if($companyRow['istVersteuerer'] == 'TRUE') echo 'checked'; ?>> Ist-Versteuerer</label>
        <br>
        <label><input onchange="startBlinker();" type="radio" name="finance_istVersteuerer" value="0" <?php if($companyRow['istVersteuerer'] != 'TRUE') echo 'checked' ?>> Soll-Versteuerer</label>
      </div>
    </div><br>
    <table class="table table-hover">
    <thead><tr>
      <th>Nr.</th>
      <th>Name</th>
      <th>Menübar</th>
    </tr></thead>
    <tbody>
      <?php
        $result = $conn->query("SELECT * FROM accounts WHERE companyID = $cmpID AND num >= 2000 AND num < 3000");
        while($result && ($row = $result->fetch_assoc())){
          echo '<tr>';
          echo '<td>'.$row['num'].'</td>';
          echo '<td>'.$row['name'].'</td>';
          echo '<td>';
          if($row['manualBooking'] == 'TRUE'){
            echo '<button type="submit" name="turnBookingOff" class="btn btn-warning" title="Buchung deaktivieren" value="'.$row['id'].'"><i class="fa fa-check"></i> Gegenkonto</button>';
          } else {
            echo '<button type="submit" name="turnBookingOn" class="btn btn-default" title="Buchung aktivieren" value="'.$row['id'].'"><i class="fa fa-times"></i> Gegenkonto</button>';
          }
          echo '</td>';
          echo '</tr>';
        }
      ?>
    </tbody>
    </table>
  </div>
</form><br>
<div class="modal fade add-finance-account">
  <div class="modal-dialog modal-content modal-sm">
    <div class="modal-header"><h4><?php echo $lang['ADD']; ?></h4></div>
    <div class="modal-body container-fluid">
      <div class="col-md-8">
        <label for="account2">Nr.</label>
        <input id="account2" name="addFinance_num" type="number" class="form-control" maxlength="4" min="2000" max="2999" placeholder="2000"/>
      </div>
      <div class="col-md-12 checkbox">
        <label><input type="checkbox" name="addFinance_booking" value="1"> Gegenkonto</label>
      </div>
      <div class="col-md-12">
        <label>Name</label>
        <input type="text" name="addFinance_name" class="form-control" maxlength="20" placeholder="Name"/>
      </div>
      <div class="col-md-12">
        <br><label><input type="checkbox" name="addOption" value="true" />Forlaufende Nr.</label>
      </div>
    </div>
    <div class="modal-footer">
      <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
      <button type="submit" name="addFinanceAccount" class="btn btn-warning"><?php echo $lang['SAVE']; ?></button>
    </div>
  </div>
</div>

<!-- IMPORT -->
<div class="page-seperated-section">
  <?php
  $csv_clients = iconv('UTF-8','windows-1252',"NAME;nr;vorname;nachname;titel;zusatz;gender;straße;plz;stadt;land;adressZusatz;tel;fax;debitNr;datev;kontobez;steuernr;steuergeb;uid;kundenGrp;kreditLimit;letzteFaktura;karenztage;mahnung1;mahnung2;mahnung3");
  $csv_suppliers = iconv('UTF-8','windows-1252',"NAME;nr;vorname;nachname;titel;zusatz;gender;straße;plz;stadt;land;adressZusatz;tel;fax;creditNr;datev;kontobez;steuernr;steuergeb;uid;kundenGrp;kreditLimit;letzteFaktura;karenztage;mahnung1;mahnung2;mahnung3");
  $csv_articles = iconv('UTF-8','windows-1252', "NAME;beschreibung;PREIS;einheit;barauslage;einkaufspreis;steuerID");
  ?>
  <h4>Import</h4>
  <div class="container-fluid">
    <div class="col-sm-4">
      <h5><?php echo $lang['CLIENTS']; ?></h5>
      <form method="POST" enctype="multipart/form-data">
        <label class="btn btn-default">
            CSV <?php echo $lang['UPLOAD']; ?> <input type="file" name="csvUpload" style="display:none">
        </label>
        <button type="submit" name="uploadClients" class="btn btn-warning" value="<?php echo $cmpID;?>"><?php echo $lang['SAVE']; ?></button>
      </form>
      <form method="POST" target="_blank" action="../project/csvDownload?name=Kunden_Muster">
        <input type="hidden" name='csv' value="<?php echo rawurlencode($csv_clients."\nMusterfirma;#123;Max;Mustermann;Dr.;123;male;Musterstraße 44;4020;Musterort;Musterland;Beispieldaten;123 456;123 456;1234;258;Beispiel Kontobezeichnung;Steuernummer 123;Österreich;AT123456;Gruppe123;100;2017-23-11 05:00:00;5;100;150;300"); ?>" />
        <button type="submit" title="Musterdownload" class="btn btn-link">Musterdownload</button>
      </form>
      <br>
    </div>
    <div class="col-sm-4">
      <h5><?php echo $lang['SUPPLIERS']; ?></h5>
      <form method="POST" enctype="multipart/form-data">
        <label class="btn btn-default">
            CSV <?php echo $lang['UPLOAD']; ?> <input type="file" name="csvUpload" style="display:none">
        </label>
        <button type="submit" name="uploadSuppliers" class="btn btn-warning" value="<?php echo $cmpID;?>"><?php echo $lang['SAVE']; ?></button>
      </form>
      <form method="POST" target="_blank" action="../project/csvDownload?name=Lieferanten_Muster">
        <input type="hidden" name='csv' value="<?php echo rawurlencode($csv_suppliers."\nMusterfirma;#123;Max;Mustermann;Dr.;123;male;Musterstraße 44;4020;Musterort;Musterland;Beispieldaten;123 456;123 456;1234;258;Beispiel Kontobezeichnung;Steuernummer 123;Österreich;AT123456;Gruppe123;100;2017-23-11 05:00:00;5;100;150;300"); ?>" />
        <button type="submit" title="Musterdownload" class="btn btn-link">Musterdownload</button>
      </form>
      <br>
    </div>
    <div class="col-sm-4">
      <h5><?php echo $lang['ARTICLE']; ?></h5>
      <form method="POST" enctype="multipart/form-data">
        <label class="btn btn-default">
            CSV <?php echo $lang['UPLOAD']; ?> <input type="file" name="csvUpload" style="display:none">
        </label>
        <button type="submit" name="uploadArticles" class="btn btn-warning" value="<?php echo $cmpID;?>"><?php echo $lang['SAVE']; ?></button>
      </form>
      <form method="POST" target="_blank" action="../project/csvDownload?name=Artikel_Muster">
        <input type="hidden" name='csv' value="<?php echo rawurlencode($csv_articles."\nApfel;Eine Beschreibung von Apfel;100;Pkg;FALSE;80;3"); ?>" />
        <button type="submit" title="Musterdownload" class="btn btn-link">Musterdownload</button>
      </form>
      <br>
    </div>
    <br>
    <small>*Die 1. Zeile wird ignoriert. Beim befüllen bitte auf die Reihenfolge achten. Pflichtfelder sind in Großbuchstaben angeführt.</small>
  </div>
</div>
<br>
<div class="page-seperated-section">
  <h4>Export</h4>
  <div class="container-fluid">
    <div class="col-sm-4">
      <form method="POST" target="_blank" action="../project/csvDownload?name=Kunden_Export">
        <?php
        $csv = $csv_clients . "\n";
        $result = $conn->query("SELECT *, clientData.name AS clientName FROM clientData LEFT JOIN clientInfoData ON clientID = clientData.id WHERE companyID = $cmpID AND isSupplier = 'FALSE'"); echo $conn->error;
        while($row = $result->fetch_assoc()){
          $line = '';
          $line.= $row['clientName'] .';';
          $line.= $row['clientNumber'] .';';
          $line.= $row['firstname'] .';';
          $line.= $row['name'] .';';
          $line.= $row['title'] .';';
          $line.= $row['nameAddition'] .';';
          $line.= $row['gender'] .';';
          $line.= $row['address_Street'] .';';
          $line.= $row['address_Country_Postal'] .';';
          $line.= $row['address_Country_City'] .';';
          $line.= $row['address_Country'] .';';
          $line.= $row['address_Addition'] .';';
          $line.= $row['phone'] .';';
          $line.= $row['fax_number'] .';';
          $line.= $row['debitNumber'] .';';
          $line.= $row['datev'] .';';
          $line.= $row['accountName'] .';';
          $line.= $row['taxnumber'] .';';
          $line.= $row['taxArea'] .';';
          $line.= $row['vatnumber'] .';';
          $line.= $row['customerGroup'] .';';
          $line.= $row['creditLimit'] .';';
          $line.= $row['lastFaktura'] .';';
          $line.= $row['karenztage'] .';';
          $line.= $row['warning1'] .';';
          $line.= $row['warning2'] .';';
          $line.= $row['warning3'] .';';
          $line.= "\n";
          $csv .= iconv(mb_detect_encoding($line), 'UTF-16LE', $line);
        }
        ?>
        <button type="submit" class="btn btn-default" title="CSV Download">Kunden Export</button>
        <input type="hidden" name='csv' value="<?php echo rawurlencode($csv); ?>" />
      </form>
    </div>
    <div class="col-sm-4">
      <form method="POST" target="_blank" action="../project/csvDownload?name=Lieferanten_Export">
        <?php
        $csv = $csv_suppliers . "\n";
        $result = $conn->query("SELECT *, clientData.name AS clientName FROM clientData LEFT JOIN clientInfoData ON clientID = clientData.id WHERE companyID = $cmpID AND isSupplier = 'TRUE'"); echo $conn->error;
        while($row = $result->fetch_assoc()){
          $line = '';
          $line.= $row['clientName'] .';';
          $line.= $row['clientNumber'] .';';
          $line.= $row['firstname'] .';';
          $line.= $row['name'] .';';
          $line.= $row['title'] .';';
          $line.= $row['nameAddition'] .';';
          $line.= $row['gender'] .';';
          $line.= $row['address_Street'] .';';
          $line.= $row['address_Country_Postal'] .';';
          $line.= $row['address_Country_City'] .';';
          $line.= $row['address_Country'] .';';
          $line.= $row['address_Addition'] .';';
          $line.= $row['phone'] .';';
          $line.= $row['fax_number'] .';';
          $line.= $row['debitNumber'] .';';
          $line.= $row['datev'] .';';
          $line.= $row['accountName'] .';';
          $line.= $row['taxnumber'] .';';
          $line.= $row['taxArea'] .';';
          $line.= $row['vatnumber'] .';';
          $line.= $row['customerGroup'] .';';
          $line.= $row['creditLimit'] .';';
          $line.= $row['lastFaktura'] .';';
          $line.= $row['karenztage'] .';';
          $line.= $row['warning1'] .';';
          $line.= $row['warning2'] .';';
          $line.= $row['warning3'] .';';
          $line.= "\n";
          $csv .= iconv(mb_detect_encoding($line), 'UTF-16LE', $line);
        }
        ?>
        <button type="submit" class="btn btn-default" title="CSV Download">Lieferanten Export</button>
        <input type="hidden" name='csv' value="<?php echo rawurlencode($csv); ?>" />
      </form>
    </div>
    <div class="col-sm-4">
      <form method="POST" target="_blank" action="../project/csvDownload?name=Artikel_Export">
        <?php
        $csv = $csv_articles . "\n";
        $result = $conn->query("SELECT * FROM articles"); echo $conn->error;
        while($row = $result->fetch_assoc()){
          $line = '';
          $line.= $row['name'] .';';
          $line.= $row['description'] .';';
          $line.= $row['price'] .';';
          $line.= $row['unit'] .';';
          $line.= $row['cash'] .';';
          $line.= $row['purchase'] .';';
          $line.= $row['taxID'] .';';
          $line.= "\n";
          $csv .= iconv(mb_detect_encoding($line), 'UTF-16LE', $line);
        }
        ?>
        <button type="submit" class="btn btn-default" title="CSV Download">Artikel Export</button>
        <input type="hidden" name='csv' value="<?php echo rawurlencode($csv); ?>" />
      </form>
    </div>
  </div>
  <br>
</div>

<br><br>
<script>
$('#account2').mask("0000");
function startBlinker(){
  var blink = $('#blinker');
  blink.attr('class', 'btn btn-warning blinking');
  setInterval(function() {
    blink.fadeOut(500, function() {
      blink.fadeIn(500);
    });
  }, 1000);
}

$(document).ready(function(){
  $('.table').DataTable({
    order: [],
    ordering: false,
    language: {
      <?php echo $lang['DATATABLES_LANG_OPTIONS']; ?>
    },
    responsive: true,
    autoWidth: false
  });
});
</script>

<?php endif;?>
</div> <!-- /page-seperated-body -->
<?php include dirname(__DIR__) . '/footer.php'; ?>
