<?php session_start() ?>
<?php use PHPMailer\PHPMailer\PHPMailer; require_once dirname(dirname(__DIR__)) ."/plugins/phpMailer/autoload.php"; ?>
<?php require_once dirname(__DIR__) . "/connection.php"; ?>
<?php require_once dirname(dirname(__DIR__)) ."/plugins/cssToInlineStyles/autoload.php"; ?>
<?php require_once dirname(__DIR__) .'/utilities.php'; ?>
<?php require_once dirname(__DIR__) ."/Calculators/IntervalCalculator.php"; ?>
<?php
#region css
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
#endregion

#region init_mailer
$mail = new PHPMailer();
$mail->CharSet = 'UTF-8';
$mail->Encoding = "base64";
$mail->IsSMTP();
#endregion

#region generate_content
$user_location = test_input($_POST["location"]);
$userID = $_SESSION['userid'];
$userName = $conn->query("SELECT id, CONCAT(firstname,' ', lastname) as name FROM UserData WHERE id = $userID")->fetch_assoc()["name"];
$connectID = $conn->query("SELECT id FROM identification")->fetch_assoc()["id"];
$message = test_input($_POST["message"]);
$type = test_input($_POST["type"]);
$content = "<h1>Feedback</h1>";
$content .= "<p><b>Type: </b>$type</p>";
$content .= "<p><b>User: </b>$userName ($userID)</p>";
$content .= "<p><b>Location: </b>$user_location</p>";
$content .= "<p><b>Connect ID: </b>$connectID</p>";
$content .= "<p><b>Message: </b>$message</p>";
//convert to inline css style
$content = $cssToInlineStyles->convert($content, $css);

#endregion

#region mail_options
//get mail server options
$result = $conn->query("SELECT * FROM $mailOptionsTable");
$row = $result->fetch_assoc();
$recipient = $row["feedbackRecipient"];

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

$mail->Host = $row['host'];
$mail->Port = $row['port'];
if(!empty($row['sendername'])&&$row['isDefault']==1){
    $result = $conn->query("SELECT name FROM companydata WHERE id = $cmpID");
    $sendTo = $result->fetch_assoc();
    $mail->setFrom($row['sender'],$row['sendername'].$sendTo['name']);
}elseif(!empty($row['sendername'])){
    $mail->setFrom($row['sender'],$row['sendername']);
}else{
    $mail->setFrom($row['sender']);
}
$mail->addAddress($recipient); // Add recipient
#endregion

#region mail_content
$mail->isHTML(true); // Set email format to HTML
$mail->Subject = "Feedback Feedback / $type";
$mail->Body    = $content;
$mail->AltBody = "Your e-mail provider does not support HTML. To apply formatting, use an html viewer." . $content;

if(isset($_POST['screenshot']))
 {
    $screenshot = $_POST['screenshot'];
    $uri = substr($screenshot,strpos($screenshot,",")+1);
    $mail->addStringAttachment(base64_decode($uri), 'screenshot.png');
}
#endregion

if(!$mail->send()){
    $errorInfo = $mail->ErrorInfo;
    $conn->query("INSERT INTO $mailLogsTable(sentTo, messageLog) VALUES('$recipient', '$errorInfo')");
    echo $errorInfo; //users see this in alert
}else{
    echo "Success!"; //users see this in alert
}
?>
