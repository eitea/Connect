
<?php require_once "../plugins/phpMailer/class.phpmailer.php"; ?>
<?php require_once "../plugins/phpMailer/class.smtp.php"; ?>
<?php require_once "connection.php"; require_once "createTimestamps.php"; ?>
<?php require "../plugins/cssToInlineStyles/autoload.php"; ?>
<?php include 'utilities.php'; ?>
<?php require "Calculators/LogCalculator.php"; ?>

<?php
//for css
use TijsVerkoyen\CssToInlineStyles\CssToInlineStyles;
$cssToInlineStyles = new CssToInlineStyles();
$css = file_get_contents('../plugins/homeMenu/template.css');

//get all mails
$resultContent = $conn->query("SELECT id, name FROM $pdfTemplateTable WHERE repeatCount != '' AND repeatCount IS NOT NULL "); //<> == !=
while($resultContent && ($rowContent = $resultContent->fetch_assoc())){
  //for each report, send a NEW mail
  $mail = new PHPMailer();


  $mail->SMTPDebug = 2;


  $mail->IsSMTP();
  $mail->SMTPAuth   = true;

  $reportID = $rowContent['id'];
  $content = getFilledOutTemplate($reportID);

  //convert to inline css style
  $content = $cssToInlineStyles->convert($content, $css);

  //get mail server options
  $result = $conn->query("SELECT * FROM $mailOptionsTable");
  $row = $result->fetch_assoc();

  $mail->Host       = $row['host'];
  $mail->Username   = $row['username'];
  $mail->Password   = $row['password'];
  $mail->Port       = $row['port'];
  $mail->SMTPSecure = $row['smtpSecure'];
  $mail->setFrom($row['sender']);

  //check if mail has recipients
  $result = $conn->query("SELECT * FROM $mailReportsRecipientsTable WHERE reportID = $reportID");
  if(!$result || $result->num_rows <= 0){
    die("Please Define Recipients! ");
  } else {
    //echo "<script>window.close();</script>";
  }
  $recipients = "";
  while($result && ($row = $result->fetch_assoc())){
    $mail->addAddress($row['email']);        // Add a recipient, name is optional
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
