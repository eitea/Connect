<?php
function mc_encrypt($encrypt, $key){
  $encrypt = serialize($encrypt);
  $iv = mcrypt_create_iv(mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_CBC), MCRYPT_DEV_URANDOM);
  $key = pack('H*', $key);
  $mac = hash_hmac('sha256', $encrypt, substr(bin2hex($key), -32));
  $passcrypt = mcrypt_encrypt(MCRYPT_RIJNDAEL_256, $key, $encrypt.$mac, MCRYPT_MODE_CBC, $iv);
  $encoded = base64_encode($passcrypt).'|'.base64_encode($iv);
  return $encoded;
}

function mc_decrypt($decrypt, $key){
  $decrypt = explode('|', $decrypt.'|');
  $decoded = base64_decode($decrypt[0]);
  $iv = base64_decode($decrypt[1]);
  if(strlen($iv)!==mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_CBC)){ return false; }
  $key = pack('H*', $key);
  $decrypted = trim(mcrypt_decrypt(MCRYPT_RIJNDAEL_256, $key, $decoded, MCRYPT_MODE_CBC, $iv));
  $mac = substr($decrypted, -64);
  $decrypted = substr($decrypted, 0, -64);
  $calcmac = hash_hmac('sha256', $decrypted, substr(bin2hex($key), -32));
  if($calcmac!==$mac){ return false; }
  $decrypted = unserialize($decrypted);
  return $decrypted;
}

/*
* requires createTimestamps.php
* requires Calculator/LogCalculator
*
* query must contain WHERE clause
*/
function getFilledOutTemplate($templateID, $bookingQuery = ""){
  require "connection.php";
  require "language.php";

  $t = localtime(time(), true);
  $today = $t["tm_year"] + 1900 . "-" . sprintf("%02d", ($t["tm_mon"]+1)) . "-". sprintf("%02d", $t["tm_mday"]);

  //grab template
  $result = $conn->query("SELECT htmlCode, userIDs FROM $pdfTemplateTable WHERE id = $templateID");
  if($result && ($row = $result->fetch_assoc())){
    $html = $row['htmlCode'];
    $userIDs = $row['userIDs'];
  } else {
    die("Could not fetch template. Please make sure it exists. Contact support for further issues."); //We dont actually have a support.
  }

  if(empty($userIDs)){ //a template can define the user data it wants to display
    $userIDs_query = "";
  } else {
    $userIDs_query = "WHERE id IN ($userIDs)";
  }

  if(strpos($html, "[TIMESTAMPS]") !== false){ //0 = false, but 0 is valid position
    $html_bookings = "<h3>Anwesenheit:</h3><table><tr><th>Name</th><th>Status</th><th>Von</th><th>Bis</th><th>Differenz</th><th>Saldo (Stunden)</th></tr>";
    //select all users and select log from today if exists else log = null
    $result = $conn->query("SELECT * FROM $userTable LEFT JOIN $logTable ON $logTable.userID = $userTable.id AND $logTable.time LIKE '$today %' $userIDs_query");
    echo mysqli_error($conn);
    while($result && ($row = $result->fetch_assoc())){
      $html_bookings .= "<tr><td>".$row['firstname'].' '.$row['lastname']."</td>";
      //did he check out?
      if(!empty($row['timeEnd']) && $row['timeEnd'] != '0000-00-00 00:00:00'){
        $timeEnd_Cell = '<td>'.substr(carryOverAdder_Hours($row['timeEnd'], $row['timeToUTC']),11,5).'</td>';
        $diff = displayAsHoursMins(timeDiff_Hours($row['time'], $row['timeEnd']));
      } else {
        $timeEnd_Cell = '<td style="color:gold;">00:00</td>';
        $diff = ' - ';
      }
      //if a user did not check in at all, mark him as absent.
      if(empty($row['time'])){
        $row['status'] = '-1';
      } else {
        $time_Cell = '<td>'.substr(carryOverAdder_Hours($row['time'], $row['timeToUTC']),11,5).'</td>';
      }
      //if a user did not >work< dont display times (no correct core times available)
      if($row['status'] != 0){
        $time_Cell = '<td> - </td>';
        $timeEnd_Cell = '<td> - </td>';
      }

      if($diff > 10 && $diff != ' - '){ //user was checked in for over 10 hours
        $diff_Cell = '<td style="color:red;">'.$diff.'</td>';
      } else {
        $diff_Cell = "<td>$diff</td>";
      }

      //SALDO calculation:
      $curID = $row['id'];
      $logSums = new LogCalculator($curID);
      $saldo = sprintf('%.2f', $logSums->saldo);
      if($saldo > 20 || $saldo < -5){
        $saldo_Cell = "<td style=\"color:red;\">$saldo</td>";
      } else {
        $saldo_Cell = "<td>$saldo</td>";
      }

      $html_bookings .= '<td>'.$lang['ACTIVITY_TOSTRING'][$row['status']].'</td>'."$time_Cell $timeEnd_Cell $diff_Cell $saldo_Cell</tr>";
    }
    $html_bookings .= "</table>";
    //replace
    $html = str_replace("[TIMESTAMPS]", $html_bookings, $html);
  }

  if(strpos($html, "[BOOKINGS]") !== false){
    if(empty($bookingQuery)){
      $bookingQuery = "WHERE $projectBookingTable.start LIKE '$today %'";
    }
    if(empty($userIDs)){ //a template can define the user data it wants to display
      $userIDs_query = "";
    } else {
      $userIDs_query = "AND $userTable.id IN ($userIDs)";
    }

    $html_bookings = "<h3>Buchungen</h3>";
    //grab projectbookings
    $sql="SELECT $projectTable.id AS projectID,
    $clientTable.id AS clientID,
    $clientTable.name AS clientName,
    $projectTable.name AS projectName,
    $projectBookingTable.*,
    $projectBookingTable.id AS projectBookingID,
    $logTable.timeToUTC,
    $userTable.firstname, $userTable.lastname,
    $projectTable.hours,
    $projectTable.hourlyPrice,
    $projectTable.status
    FROM $projectBookingTable
    INNER JOIN $logTable ON  $projectBookingTable.timeStampID = $logTable.indexIM
    INNER JOIN $userTable ON $logTable.userID = $userTable.id
    LEFT JOIN $projectTable ON $projectBookingTable.projectID = $projectTable.id
    LEFT JOIN $clientTable ON $projectTable.clientID = $clientTable.id
    LEFT JOIN $companyTable ON $clientTable.companyID = $companyTable.id
    $bookingQuery
    $userIDs_query

    ORDER BY $userTable.firstname, $projectBookingTable.end ASC";

    $result = $conn->query($sql);
    $prevName = "";
    //for each booking
    while($result && ($row = $result->fetch_assoc())){
      if($prevName != $row['firstname']){
        if($prevName != ""){ //cant close a table if this is the first.
          $html_bookings .= '</table>';
        }
        $html_bookings .= '<h4>'.$row['firstname'].'</h4><table><tr><th>Kunde</th><th>Projekt</th><th>Datum</th><th>Von</th><th>Bis</th><th>Infotext</th></tr>';
      }

      $start = carryOverAdder_Hours($row['start'], $row['timeToUTC']);
      $end = carryOverAdder_Hours($row['end'], $row['timeToUTC']);

      $html_bookings .= '<tr><td>'.$row['clientName'].'</td>';
      $html_bookings .= '<td>'.$row['projectName'].'</td>';
      $html_bookings .= '<td>'.substr($start,0,10).'</td>';
      $html_bookings .= '<td>'.substr($start,11,5).'</td><td>'.substr($end,11,5).'</td>';
      $html_bookings .= '<td>'.$row['infoText'].'</td></tr>';

      $prevName = $row['firstname'];
    } //end while
    $html_bookings .= '</table>';
    //replace
    $html = str_replace("[BOOKINGS]", $html_bookings, $html);
  }
  return $html;
}


function uploadFile($file_field = null, $check_image = true, $random_name = false) {
  //Config
  $path = '/images/ups/'; //with trailing slash
  $max_size = 5000000; //in bytes
  //Set default file extension whitelist
  $whitelist_ext = array('jpeg','jpg','png');
  //Set default file type whitelist
  $whitelist_type = array('image/jpeg', 'image/jpg', 'image/png');

  //Validation
  $out = array('error'=>null);

  //Make sure that there is a file
  if((!empty($_FILES[$file_field])) && ($_FILES[$file_field]['error'] == 0)) {
    // Get filename
    $file_info = pathinfo($_FILES[$file_field]['name']);
    $name = $file_info['filename'];
    $ext = strtolower($file_info['extension']);

    //Check file has the right extension
    if(!in_array($ext, $whitelist_ext)) {
      $out['error'][] = "Invalid file Extension";
    }

    //Check that the file is of the right type
    if(!in_array($_FILES[$file_field]["type"], $whitelist_type)) {
      $out['error'][] = "Invalid file Type";
    }

    //Check that the file is not too big
    if($_FILES[$file_field]["size"] > $max_size) {
      $out['error'][] = "File is too big";
    }

    //If $check image is set as true
    if($check_image) {
      if (!getimagesize($_FILES[$file_field]['tmp_name'])) {
        $out['error'][] = "Uploaded file is not a valid image";
      }
    }

    if(count($out['error'])>0) {
      return $out;
    }

    //turn interlacing off
    $im = file_get_contents($_FILES[$file_field]['tmp_name']);
    $im = imagecreatefromstring($im);
    imageinterlace($im, 0);
    if($_FILES[$file_field]["type"] == $whitelist_type[0] || $_FILES[$file_field]["type"] == $whitelist_type[1]){
      imagejpeg($im, $_FILES[$file_field]['tmp_name']);
    } else {
      imagepng($im, $_FILES[$file_field]['tmp_name']);
    }
    if(count($out['error']) > 0) {
      return $out;
    } else {
      return file_get_contents($_FILES[$file_field]['tmp_name']);
    }

  } else {
    $out['error'][] = "No file uploaded";
    return $out;
  }
}
