
<?php require_once "../plugins/phpMailer/class.phpmailer.php"; ?>
<?php require_once "../plugins/phpMailer/class.smtp.php"; ?>
<?php require_once "connection.php"; require_once "createTimestamps.php"; ?>
<?php require_once "language.php"; ?>
<?php require "../plugins/cssToInlineStyles/autoload.php"; ?>
<?php
$mail = new PHPMailer();
$mail->IsSMTP();
$mail->SMTPAuth   = true;

//for css
use TijsVerkoyen\CssToInlineStyles\CssToInlineStyles;
$cssToInlineStyles = new CssToInlineStyles();
$css = file_get_contents('../plugins/homeMenu/template.css');

//get all mails
$resultContent = $conn->query("SELECT * FROM $pdfTemplateTable WHERE repeatCount != '' AND repeatCount IS NOT NULL "); //<> == !=
while($resultContent && ($rowContent = $resultContent->fetch_assoc())){ //for each report, send a mail
  $reportID = $rowContent['id'];
  $content = $rowContent['htmlCode'];

  //grab positions
  $pos1 = strpos($content, "[REPEAT]");
  $pos2 = strpos($content, "[REPEAT END]");
  //explode my repeat pattern
  $html_head = substr($content, 0, $pos1);
  $html_foot = substr($content, $pos2 + 12);
  $repeat = substr($content, $pos1 + 12 , $pos2 - $pos1 - 12);
  //replace all findings
  $t = localtime(time(), true);
  $today = $t["tm_year"] + 1900 . "-" . sprintf("%02d", ($t["tm_mon"]+1)) . "-". sprintf("%02d", $t["tm_mday"]);
  $today = '2017-01-26';
  //Main Report consists of multiple Parts, first part covers Logs (Name - Checkin - Checkout - Saldo)
  if($rowContent['name'] == 'Main_Report'){
    $html_head .= "<h4>Anwesenheit:</h4><table><tr><th>Name</th><th>Status</th><th>Von</th><th>Bis</th><th>Saldo</th></tr>";
    //select all users and select log from today if exists else log = null
    $result = $conn->query("SELECT * FROM $userTable LEFT JOIN $logTable ON $logTable.userID = $userTable.id AND $logTable.time LIKE '$today %'");
    while($result && ($row = $result->fetch_assoc())){
      $beginDate = $row['beginningDate'];
      $exitDate = ($userRow['exitDate'] == '0000-00-00 00:00:00') ? '5000-12-30 23:59:59' : $row['exitDate'];

      $html_head .= "<tr><p><td>".$row['firstname'].' '.$row['lastname']."</td>";
      //if a user did not check in, mark him as absent.
      if(empty($row['time'])){
        $row['status'] = '-1';
        $row['time'] = ' - ';
        $row['timeEnd'] = ' - ';
      } elseif($row['timeEnd'] != '0000-00-00 00:00:00'){ //if he hasnt checked out yet, just display his UTC time (dont bother...)
        $row['time'] = carryOverAdder_Hours($row['time'], $row['timeToUTC']);
        $row['timeEnd'] = carryOverAdder_Hours($row['timeEnd'], $row['timeToUTC']);
      }

      //SALDO calculation:
      $saldo = 0;
      $resultSaldo = $conn->query("SELECT SUM( ((UNIX_TIMESTAMP(timeEnd) - UNIX_TIMESTAMP(time )) / 3600) - expectedHours - breakCredit) AS total FROM $logTable WHERE timeEnd != '0000-00-00 00:00:00' AND userID = ".$row['id']);
      if(!$resultSaldo || !($rowSaldo = $resultSaldo->fetch_assoc())){
        $rowSaldo['total'] = 'x';
      }
      $saldo = $rowSaldo['total'];
      //extra expectedHours from unlogs:
      $resultSaldo = $conn->query("SELECT * FROM $negative_logTable WHERE time > '$beginDate' AND time < '$exitDate' AND userID = ".$row['id']);
      while($resultSaldo && ($rowSaldo = $resultSaldo->fetch_assoc())){
        if(!isHoliday($rowSaldo['time'])){
          $saldo -= $rowSaldo[strtolower(date('D', strtotime($rowSaldo['time'])))];
        }
      }

      //$html_head .= substr($row['time'],11,5).' '.substr($row['timeEnd'],11,5).' -- '.$row['total']."</p>";
      $html_head .= '<td>'.$lang_activityToString[$row['status']].'</td> <td>'.substr($row['time'],11,5).'</td><td>'.substr($row['timeEnd'],11,5). "</td><td>$saldo</td></p></tr>";
    }
    $html_head .= "</table><br><h4>Buchungen: </h4>";
  }

  //convert only table above to inline css style, or else <ul> will get removed by this little ****
  $html_head = $cssToInlineStyles->convert($html_head, $css);

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
  INNER JOIN $logTable ON $projectBookingTable.timeStampID = $logTable.indexIM
  INNER JOIN $userTable ON $logTable.userID = $userTable.id
  LEFT JOIN $projectTable ON $projectBookingTable.projectID = $projectTable.id
  LEFT JOIN $clientTable ON $projectTable.clientID = $clientTable.id
  LEFT JOIN $companyTable ON $clientTable.companyID = $companyTable.id
  WHERE $projectBookingTable.start LIKE '$today %'
  ORDER BY $userTable.firstname, $projectBookingTable.end ASC";

  $result = $conn->query($sql);
  if($result && ($row = $result->fetch_assoc())){
    $prevName = $row['firstname'];
    do{
      if($prevName != $row['firstname']){
        $html_head .= '<p><hr><br /></p>';
      }
      $prevName = $row['firstname'];
      $start = carryOverAdder_Hours($row['start'], $row['timeToUTC']);
      $end = carryOverAdder_Hours($row['end'], $row['timeToUTC']);

      $appendPattern = str_replace("[NAME]", $row['firstname'] . ' ' . $row['lastname'], $repeat);
      $appendPattern = str_replace("[CLIENT]", $row['clientName'], $appendPattern);
      $appendPattern = str_replace("[PROJECT]", $row['projectName'], $appendPattern);
      $appendPattern = str_replace("[CLIENT]", $row['clientName'], $appendPattern);
      $appendPattern = str_replace("[INFOTEXT]", $row['infoText'], $appendPattern);
      $appendPattern = str_replace("[HOURLY RATE]", $row['hourlyPrice'], $appendPattern);
      $appendPattern = str_replace("[DATE]", substr($start,0,10), $appendPattern);
      $appendPattern = str_replace("[FROM]", substr($start,11,5), $appendPattern);
      $appendPattern = str_replace("[TO]", substr($end,11,5), $appendPattern);

      $html_head .= $appendPattern;
    } while($result && ($row = $result->fetch_assoc()));
  }

  //glue my html back together
  //'<head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"/><link href="" rel="stylesheet" /></head>' .

  $content = $html_head . $html_foot;

  //get mail server options
  $result = $conn->query("SELECT * FROM $mailOptionsTable");
  $row = $result->fetch_assoc();

  $mail->Host       = $row['host'];
  $mail->Username   = $row['username'];
  $mail->Password   =  $row['password'];
  $mail->Port       = $row['port'];
  $mail->SMTPSecure = $row['smtpSecure'];
  $mail->setFrom($row['sender']);

  //add mail recipients
  $result = $conn->query("SELECT * FROM $mailReportsRecipientsTable WHERE reportID = $reportID");
  if(!$result || $result->num_rows <= 0){
    die("Please Define Recipients! ");
  } else {
    echo "<script>window.close();</script>";
  }
  $recipients = "";
  while($result && ($row = $result->fetch_assoc())){
    $mail->addAddress($row['email']);     // Add a recipient, name is optional
    $recipients .= $row['email'] .' ';
  }

  $mail->isHTML(true);                       // Set email format to HTML
  $mail->Subject = $rowContent['name'];
  $mail->Body    = $content;
  $mail->AltBody = "If you can read this, your E-Mail provider does not support HTML." . $content;
  $errorInfo = "";
  if(!$mail->send()){
    $errorInfo = $mail->ErrorInfo;
  }
  $conn->query("INSERT INTO $mailLogsTable(sentTo, messageLog) VALUES('$recipients', '$errorInfo')");

}
?>
