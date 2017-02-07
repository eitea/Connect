
<?php require_once "../plugins/PHPMailer/class.phpmailer.php"; ?>
<?php require_once "../plugins/PHPMailer/class.smtp.php"; ?>

<?php
//send mail
require_once "connection.php";
$result = $conn->query("SELECT * FROM $mailOptionsTable");
$row = $result->fetch_assoc();

$mail = new PHPMailer();

$mail->IsSMTP();
$mail->Host     = $row['host'];
$mail->SMTPAuth = true;
$mail->Username = $row['username'];
$mail->Password = $row['password'];
$mail->SMTPSecure = 'tls';
$mail->Port     = 587;

$mail->setFrom('mailFrom@com', 'Name1');
$mail->addAddress('mailTo@com', 'Name2');     // Add a recipient, name is optional
$mail->isHTML(true);                       // Set email format to HTML

$mail->Subject = '';
$mail->Body    = "";
$mail->AltBody = "";

$mail->send();
?>
