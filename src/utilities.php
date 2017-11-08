<?php
function timeDiff_Hours($from, $to) {
  $timeBegin = strtotime($from);
  $timeEnd = strtotime($to);
  return ($timeEnd - $timeBegin)/3600;
}

function getCurrentTimestamp(){
  ini_set('date.timezone', 'UTC');
  $t = localtime(time(), true);
  return ($t["tm_year"] + 1900 . "-" . sprintf("%02d", ($t["tm_mon"]+1)) . "-". sprintf("%02d", $t["tm_mday"]) . " " . sprintf("%02d", $t["tm_hour"]) . ":" . sprintf("%02d", $t["tm_min"]) . ":" . sprintf("%02d", $t["tm_sec"]));
}

function carryOverAdder_Hours($a, $b){
  $b = round($b);
  if($a == '0000-00-00 00:00:00'){
    return $a;
  }
  $date = new DateTime($a);
  if($b < 0){
    $b *= -1;
    $date->sub(new DateInterval("PT".$b."H"));
  } else {
    $date->add(new DateInterval("PT".$b."H"));
  }
  return $date->format('Y-m-d H:i:s');
}

function carryOverAdder_Minutes($a, $b){
  $b = round($b);
  if($a == '0000-00-00 00:00:00'){
    return $a;
  }
  $date = new DateTime($a);
  if($b < 0){
    $b *= -1;
    $date->sub(new DateInterval("PT".$b."M"));
  } else {
    $date->add(new DateInterval("PT".$b."M"));
  }
  return $date->format('Y-m-d H:i:s');
}

function isHoliday($ts){
  require "connection.php";
  $result = $conn->query("SELECT * FROM holidays WHERE begin LIKE '". substr($ts, 0, 10)."%'"); //the sql § comparison aint working
  return($result && ($row = $result->fetch_assoc()) && strpos($row['name'], '(§)'));
}

/*
function isHoliday($ts){|
  require "connection.php";
  $sql = "SELECT * FROM $holidayTable WHERE begin LIKE '". substr($ts, 0, 10)."%'";
  $result = mysqli_query($conn, $sql);
  return($result && $result->num_rows>0);
}
*/

function test_input($data){
  $data = preg_replace("~[^A-Za-z0-9\-?!=:.,/@€§$%()+*öäüÖÄÜß_ ]~", "", $data);
  $data = trim($data);
  return $data;
}

function test_Date($date, $format = "Y-m-d H:i:s"){
  $dt = DateTime::createFromFormat($format, $date);
  return $dt && $dt->format($format) === $date;
}

function test_Time($time){
  return preg_match("/^([01][0-9]|2[0-3]):([0-5][0-9])$/", $time);
}

//$hours is a float
function displayAsHoursMins($hour){
  $hours = round($hour, 2); //i know params are passed by value if not specified otherwise, but still.. I got trust issues with this language
  $s = '';
  if($hours < 0){
    $s = '-';
    $hours = $hours * -1;
  }
  if($hours >= 1){
    $s .= intval($hours) . 'h ';
    $hours = $hours - intval($hours);
  }
  $s .= round($hours * 60) .'min';
  return $s;
}

function redirect($url){
  if (!headers_sent()) {
    header('Location: '.$url);
    exit;
  } else {
    echo '<script type="text/javascript">';
    echo 'window.location.href="'.$url.'";';
    echo '</script>';
    echo '<noscript>';
    echo '<meta http-equiv="refresh" content="0;url='.$url.'" />';
    echo '</noscript>'; exit;
  }
}

/*see if password matches policy, returns true or false.
* writes error message in optional output
* low - at least x characters (x from policy table)
* medium - at least one capital letter and one number
* high - at least one special character
*/
function match_passwordpolicy($p, &$out = ''){
  require "connection.php";
  $result = $conn->query("SELECT * FROM $policyTable");
  $row = $result->fetch_assoc();

  if(strlen($p) < $row['passwordLength']){
    $out = "Password must be at least " . $row['passwordLength'] . " Characters long.";
    return false;
  }
  if($row['complexity'] === '0'){ //whatever
    return true;
  } elseif($row['complexity'] === '1'){
    if(!preg_match('/[A-Z]/', $p) || !preg_match('/[0-9]/', $p)){
      $out = "Password must contain at least one captial letter and one number";
      return false;
    }
  } elseif($row['complexity'] === '2'){
    if(!preg_match('/[A-Z]/', $p) || !preg_match('/[0-9]/', $p) || !preg_match('/[~\!@#\$%&\*_\-\+\.\?]/', $p)){
      $out = "Password must contain at least one captial letter, one number and one special character (~ ! @ # $ % & * _ - + . ?)";
      return false;
    }
  }
  return true;
}

function getNextERP($identifier, $companyID, $offset = 0){
  require "connection.php";
  if(!$companyID){$companyID = $available_companies[1]; }
  $result = $conn->query("SELECT * FROM erpNumbers WHERE companyID = $companyID");
  if($row = $result->fetch_assoc()){
    $offset = $row['erp_'.strtolower($identifier)];
    $offset--;
    if($offset < 0) $offset = 0;
  }
  $vals = array($offset);

  //get all the little shits which contain my shit
  $result = $conn->query("SELECT id_number, history FROM proposals, clientData WHERE clientID = clientData.id AND companyID = $companyID AND (id_number LIKE '$identifier%' OR history LIKE '%$identifier%')");
  while($result && ($row = $result->fetch_assoc())){
    $history = explode(' ', $row['history']);
    $history[] = $row['id_number'];
    foreach($history as $h){
      if(substr($h, 0, strlen($identifier)) == $identifier){
        $vals[] = intval(substr($h, strlen($identifier))); //trim 0s
      }
    }
  }
  return $identifier . sprintf('%0'.(10-strlen($identifier)).'d', max($vals) +1);
}

function randomPassword($length = 8){
  $pool = array('abcdefghijklmnopqrstuvwxyz', 'ABCDEFGHIJKLMNOPQRSTUVWXYZ', '1234567890', '!@#$*+?');
  shuffle($pool);
  $psw = array();
  for($i = 0; $i < $length; $i++){
    $psw[] = $pool[$i % 4][rand(0, strlen($pool[$i % 4]) -1)];
    if($i > 3){
      shuffle($pool);
    }
  }
  return implode($psw);
}

/*
echo $test=strtotime('2016-02-3 05:44:21');
echo date('Y-m-d H:i:s', $test);
*/

/** Usage
* Encrypt
* $c = new MasterCrypt();
* $encrypted = $c->encrypt("this is a test");
* $encrypted2 = $c->encrypt("this is a test");
* INSERT INTO table1 (var1,var2,iv,iv2) VALUES ('$encrypted','$encrypted2','$iv','$iv2');
*
* Decrypt
* SELECT var1,var2,iv,iv2 FROM table1;
* $c = new MasterCrypt($iv,$iv2);
* echo $c->decrypt($var1);
* echo $c->decrypt($var2);
*/
class MasterCrypt{
  public $iv;
  public $iv2;
  private $password;

  function __construct($pass, $iv = '', $iv2 = ''){
    $this->password = $pass;
    $this->iv = $iv;
    $this->iv2 = $iv2;
    if($pass && (!$iv || !$iv2)){
      $this->iv2 = bin2hex(openssl_random_pseudo_bytes(8));
      $this->iv = bin2hex(openssl_random_pseudo_bytes(32));
      $this->iv = openssl_encrypt($this->iv, 'aes-256-cbc', $this->password, 0, $this->iv2);
    }
  }

  function encrypt(string $unencrypted){
    if($this->password && $this->iv && $this->iv2){
        $iv = openssl_decrypt($this->iv, 'aes-256-cbc', $this->password, 0, $this->iv2);
        $encrypted = self::mc_encrypt($unencrypted, $iv);
        return $encrypted;
    } else {      
        return $unencrypted;
    }
  }
  function decrypt($encrypted){
    if($this->password){
        $iv = openssl_decrypt($this->iv, 'aes-256-cbc', $this->password, 0, $this->iv2);
        return self::mc_decrypt($encrypted, $iv);
    } else {
      //if values are encrypted, then **** it
      if($this->iv && $this->iv2){
        return '****';
      }
      return $encrypted;
    }
  }

  function getStatus($encrypt = false){
    if($encrypt){
      if($this->password) return '<i class="fa fa-lock text-success" aria-hidden="true" title="Encryption Aktiv"></i>';
      return '<i class="fa fa-unlock text-danger" aria-hidden="true" title="Encryption Inaktiv"></i>';
    }
    if($this->iv && $this->iv2) return '<i class="fa fa-lock text-success" aria-hidden="true" title="Encryption Aktiv"></i>';
    return '<i class="fa fa-unlock text-danger" aria-hidden="true" title="Encryption Inaktiv"></i>';
  }

  //TODO: mcrypt is deprecated. replace with openssl (_seal and _open)
  private static function mc_encrypt($encrypt, $key){
    $encrypt = serialize($encrypt);
    $iv = mcrypt_create_iv(mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_CBC), MCRYPT_DEV_URANDOM);
    $key = pack('H*', $key);
    $mac = hash_hmac('sha256', $encrypt, substr(bin2hex($key), -32));
    $passcrypt = mcrypt_encrypt(MCRYPT_RIJNDAEL_256, $key, $encrypt.$mac, MCRYPT_MODE_CBC, $iv);
    $encoded = base64_encode($passcrypt).'|'.base64_encode($iv);
    return $encoded;
  }
  private static function mc_decrypt($decrypt, $key){
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
}
  
function mc_status(){
  if(!empty($_SESSION['masterpassword'])){
      return '<i class="fa fa-lock text-success" aria-hidden="true" title="Encryption Aktiv"></i>';
  } else{
      return '<i class="fa fa-unlock text-danger" aria-hidden="true" title="Encryption Inaktiv"></i>';
  }
}

/**
 * List all changes before user confirms or cancels master password change
 *
 * @return string
 */
function mc_list_changes(){
  require __DIR__."/connection.php";
  $out = "Changes following data: \\n";
  $out .= ($conn->query("SELECT * FROM articles")->num_rows ?? "0")               . " Changes in articles\\n";
  $out .= ($conn->query("SELECT * FROM products")->num_rows ?? "0")               . " Changes in products\\n";
  $out .= ($conn->query("SELECT * FROM $clientDetailBankTable")->num_rows ?? "0") . " Changes in banking data\\n";
  return $out;
}

/**
 * Returns rows affected by master password change
 *
 * @return int
 */
function mc_total_row_count(){
  require __DIR__."/connection.php";
  $total_count = 0;
  $total_count += $conn->query("SELECT * FROM articles")->num_rows ?? 0;
  $total_count += $conn->query("SELECT * FROM products")->num_rows ?? 0;
  $total_count += $conn->query("SELECT * FROM $clientDetailBankTable")->num_rows ?? 0;
  return $total_count;
}

//just encrypt the values from current to new
//MasterCrypt will handle the masterPass settings
function mc_update_values($current, $new, $statement = ''){
  require __DIR__."/connection.php";
  $logFile = fopen("./cryptlog.txt","a");
  if($statement) fwrite($logFile, "\r\n".getCurrentTimestamp()." (UTC): Master password $statement\r\n");
  $i = 0;
  //articles
  $result = $conn->query("SELECT id, name, description, iv, iv2 FROM articles");
  $stmt = $conn->prepare("UPDATE articles SET name = ?, description = ?, iv = ?, iv2 = ? WHERE id = ?");
  $stmt->bind_param("ssssi", $name, $description, $iv, $iv2, $id);
  fwrite($logFile, "\t".getCurrentTimestamp()." (UTC): altering articles\r\n");
  while($row = $result->fetch_assoc()){
    $i++;
    $mc_old = new MasterCrypt($current, $row['iv'], $row['iv2']);
    $mc_new = new MasterCrypt($new);
    $name = $mc_new->encrypt($mc_old->decrypt($row["name"]));
    $desc = $mc_new->encrypt($mc_old->decrypt($row["description"]));
    $iv = $mc_new->iv;
    $iv2 = $mc_new->iv2;
    $id = $row["id"];
    $stmt->execute();
    if($conn->error) fwrite($logFile, "\t\t".getCurrentTimestamp()." (UTC): Error in row with id $id: ".$conn->error."\r\n");
  }
  $stmt->close();
  //products
  $result = $conn->query("SELECT id, name, description, iv, iv2 FROM products");
  $stmt = $conn->prepare("UPDATE products SET name = ?, description = ?, iv = ?, iv2 = ? WHERE id = ?");
  $stmt->bind_param("ssssi", $name, $description, $iv, $iv2, $id);
  fwrite($logFile, "\t".getCurrentTimestamp()." (UTC): altering products\r\n");
  while($row = $result->fetch_assoc()){
    $i++;
    $mc_old = new MasterCrypt($current, $row['iv'], $row['iv2']);
    $mc_new = new MasterCrypt($new);
    $name = $mc_new->encrypt($mc_old->decrypt($row["name"]));
    $desc = $mc_new->encrypt($mc_old->decrypt($row["description"]));
    $iv = $mc_new->iv;
    $iv2 = $mc_new->iv2;
    $id = $row["id"];
    $stmt->execute();
    if($conn->error) fwrite($logFile, "\t\t".getCurrentTimestamp()." (UTC): Error in row with id $id: ".$conn->error."\r\n");
  }
  $stmt->close();
  //bank data
  $result = $conn->query("SELECT id, iban, bic FROM clientInfoBank");
  $stmt = $conn->prepare("UPDATE articles SET name = ?, description = ?, iv = ?, iv2 = ? WHERE id = ?");
  $stmt->bind_param("ssssi", $name, $description, $iv, $iv2, $id);
  fwrite($logFile, "\t".getCurrentTimestamp()." (UTC): altering articles\r\n");
  while($row = $result->fetch_assoc()){
    $i++;
    $mc_old = new MasterCrypt($current, $row['iv'], $row['iv2']);
    $mc_new = new MasterCrypt($new);
    $name = $mc_new->encrypt($mc_old->decrypt($row["name"]));
    $desc = $mc_new->encrypt($mc_old->decrypt($row["description"]));
    $iv = $mc_new->iv;
    $iv2 = $mc_new->iv2;
    $id = $row["id"];
    $stmt->execute();
    if($conn->error) fwrite($logFile, "\t\t".getCurrentTimestamp()." (UTC): Error in row with id $id: ".$conn->error."\r\n");
  }
  fwrite($logFile,date("y-m-d h:i:s").": Finished\r\n");
  fwrite($logFile,date("y-m-d h:i:s").":  rows affected\r\n");
  $stmt->close();
  fclose($logFile);
}


/*
* requires Calculator/IntervalCalculator
*
* query must contain WHERE clause
*/
function getFilledOutTemplate($templateID, $bookingQuery = ""){
  set_time_limit(60);
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
      $logSums = new Interval_Calculator($curID);
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
    $bookingQuery $userIDs_query
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

function uploadFile($file_field, $check_image = true,$crop_square = false,$resize = false) { //should be named uploadImage
  //bytes
  $max_size = 5000000;
  //whitelist
  $whitelist_ext = array('jpeg','jpg','png');
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

    if($check_image) {
      if (!getimagesize($_FILES[$file_field]['tmp_name'])) {
        $out['error'][] = "Uploaded file is not a valid image";
      }
    }

    if(count($out['error']) > 0) {
      return $out;
    }

    //remove interlacing bit
    $im = file_get_contents($_FILES[$file_field]['tmp_name']);
    $im = imagecreatefromstring($im);
    if(!$im){
      return file_get_contents($_FILES[$file_field]['tmp_name']);
    }
    if($crop_square){
      $size = min(imagesx($im), imagesy($im));
      $middlex = imagesx($im)/2;
      $middley = imagesy($im)/2;
      $im = imagecrop($im, ['x' => floor($middlex-($size/2)), 'y' => floor($middley-($size/2)), 'width' => $size, 'height' => $size]);
    }
    if($resize){
      $aspect_ratio = imagesx($im) / imagesy($im);
      if($aspect_ratio>1){
        $y = 300;
        $x = 300*$aspect_ratio;
      }else{
        $x = 300;
        $y = 300* (imagesy($im) / imagesx($im));
      }
      $im2 = imagecreatetruecolor($x,$y);
      imagecopyresized($im2,$im,0,0,0,0,$x,$y,imagesx($im),imagesy($im));
      $im = $im2;
    }
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
