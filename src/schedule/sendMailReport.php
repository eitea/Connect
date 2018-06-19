<?php use PHPMailer\PHPMailer\PHPMailer; require_once dirname(dirname(__DIR__)) ."/plugins/phpMailer/autoload.php"; ?>
<?php require_once dirname(__DIR__) . "/connection.php"; ?>
<?php require_once dirname(dirname(__DIR__)) ."/plugins/cssToInlineStyles/autoload.php"; ?>
<?php require_once dirname(__DIR__) .'/utilities.php'; ?>
<?php require_once dirname(__DIR__) ."/Calculators/IntervalCalculator.php"; ?>
<?php
//for css
set_time_limit(120);
use TijsVerkoyen\CssToInlineStyles\CssToInlineStyles;
$cssToInlineStyles = new CssToInlineStyles();
try {
  $css = file_get_contents(dirname(dirname(__DIR__)) . '/plugins/homeMenu/compactMail.css');
} catch (Exception $e) {
  $css = 'body {
    font-size: 12px;
    font-weight:lighter;
  }
  table, td, th {
    border: 1px solid #ddd;
  }
  table {
    border-collapse: collapse;
    width: 100%;
  }
  th, td {
    padding: 5px;
  }';
}

//get all mails
$resultContent = $conn->query("SELECT id, name FROM $pdfTemplateTable WHERE repeatCount != '' AND repeatCount IS NOT NULL "); //i think the repeatCount stands for active or inactive..
while($resultContent && ($rowContent = $resultContent->fetch_assoc())){
  //for each active report, send a NEW mail
  $mail = new PHPMailer();
  $mail->CharSet = 'UTF-8';
  $mail->Encoding = "base64";
  $mail->IsSMTP();

  $reportID = $rowContent['id'];
  $content = getFilledOutTemplate($reportID); //utilities.php

  //convert to inline css style
  $content = $cssToInlineStyles->convert($content, $css);

  //get mail server options
  $result = $conn->query("SELECT * FROM $mailOptionsTable");
  $row = $result->fetch_assoc();

  if(!empty($row['username']) && !empty($row['password'])){
    $mail->SMTPAuth   = true;
    $mail->Username   = $row['username'];
    $mail->Password   = $row['password'];
  } else {
    $mail->SMTPAuth   = false;
  }

  if(empty($row['smptSecure'])){
    $mail->SMTPSecure = $row['smtpSecure'];
  }

  $mail->Host       = $row['host'];
  $mail->Port       = $row['port'];
  if(!empty($row['sendername'])&&$row['isDefault']==1){
    $result = $conn->query("SELECT name FROM companydata WHERE id = $cmpID");
    $sendTo = $result->fetch_assoc();
    $mail->setFrom($row['sender'],$row['sendername'].$sendTo['name']);
  }elseif(!empty($row['sendername'])){
    $mail->setFrom($row['sender'],$row['sendername']);
  }else{
    $mail->setFrom($row['sender']);
  }

  //check if mail has recipients
  $result = $conn->query("SELECT * FROM $mailReportsRecipientsTable WHERE reportID = $reportID");
  if(!$result || $result->num_rows <= 0){
    die("Please Define Recipients! ");
  }
  $recipients = "";
  while($result && ($row = $result->fetch_assoc())){
    $mail->addAddress($row['email']);        // Add a recipient, name is optional
    $recipients .= $row['email'] .' ';
  }

  $mail->isHTML(true);                       // Set email format to HTML
  $mail->Subject = $rowContent['name'];
  $mail->Body    = $content;
  $mail->AltBody = "Your e-mail provider does not support HTML. To apply formatting, use an html viewer." . $content;
  if(!$mail->send()){
    $errorInfo = $mail->ErrorInfo;
    $conn->query("INSERT INTO mailLogs(sentTo, messageLog) VALUES('$recipients', '$errorInfo')");
    echo $errorInfo;
  } else {
    //echo "<script>window.close();</script>";
  }
}
?>
