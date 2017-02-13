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
* and LogCalculator
*/
function getFilledOutTemplate($templateID, $bookingQuery = ""){ //query must contain WHERE clause
  require "connection.php";
  require "language.php";

  $t = localtime(time(), true);
  $today = $t["tm_year"] + 1900 . "-" . sprintf("%02d", ($t["tm_mon"]+1)) . "-". sprintf("%02d", $t["tm_mday"]);

  //grab template
  $result = $conn->query("SELECT htmlCode FROM $pdfTemplateTable WHERE id = $templateID");
  if($result && ($row = $result->fetch_assoc())){
    $html = $row['htmlCode'];
  } else {
    die("Could not fetch template. Please make sure it exists. Contact support for further issues."); //We dont actually have a support.
  }

  if(strpos($html, "[TIMESTAMPS]") !== false){ //0 = false, but 0 is valid position
    $html_bookings = "<h3>Anwesenheit:</h3><table><tr><th>Name</th><th>Status</th><th>Von</th><th>Bis</th><th>Saldo (Stunden)</th></tr>";
    //select all users and select log from today if exists else log = null
    $result = $conn->query("SELECT * FROM $userTable LEFT JOIN $logTable ON $logTable.userID = $userTable.id AND $logTable.time LIKE '$today %'");
    echo mysqli_error($conn);
    while($result && ($row = $result->fetch_assoc())){
      $beginDate = $row['beginningDate'];
      $exitDate = ($row['exitDate'] == '0000-00-00 00:00:00') ? '5000-12-30 23:59:59' : $row['exitDate'];

      $html_bookings .= "<tr><p><td>".$row['firstname'].' '.$row['lastname']."</td>";
      //if a user did not check in, mark him as absent.
      if(empty($row['time'])){
        $row['status'] = '-1';
        $row['time'] = ' - ';
        $row['timeEnd'] = ' - ';
      } elseif($row['timeEnd'] != '0000-00-00 00:00:00'){ //if he hasnt checked out yet, just display his UTC time (dont bother...)
        $row['time'] = carryOverAdder_Hours($row['time'], $row['timeToUTC']);
        $row['timeEnd'] = carryOverAdder_Hours($row['timeEnd'], $row['timeToUTC']);
      }

      $curID = $row['id'];
      //SALDO calculation:
      $logSums = new LogCalculator($curID);
      $saldo = $logSums->saldo;

      $saldo = sprintf('%.2f', $saldo);
      $html_bookings .= '<td>'.$lang_activityToString[$row['status']].'</td><td>'.substr($row['time'],11,5).'</td><td>'.substr($row['timeEnd'],11,5). "</td><td>$saldo</td></p></tr>";
    }
    $html_bookings .= "</table>";
    //replace
    $html = str_replace("[TIMESTAMPS]", $html_bookings, $html);
  }
  if(strpos($html, "[BOOKINGS]") !== false){
    if(empty($bookingQuery)){
      $bookingQuery = "WHERE $projectBookingTable.start LIKE '$today %'";
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
