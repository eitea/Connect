<?php
require dirname(dirname(__DIR__))."/plugins/imap-client/autoload.php";
require dirname(__DIR__)."/connection.php";

use SSilence\ImapClient\ImapClientException;
use SSilence\ImapClient\ImapConnect;
use SSilence\ImapClient\ImapClient;
if($_SERVER["REQUEST_METHOD"] == "POST"){
    $mailbox = $_POST['server'];
    $username = $_POST['username'];
    $password = $_POST['password'];
    $service = strtoupper($_POST['service'])=="IMAP" ? ImapConnect::SERVICE_IMAP : ImapConnect::SERVICE_POP3;
    if($_POST['security']=='none'){
        $encryption = null;
    }else{
        $encryption = $_POST['security']=="tls" ? ImapClient::ENCRYPT_TLS : ImapClient::ENCRYPT_SSL;
    }
    $port = $_POST['port'];
    $validation = ImapConnect::VALIDATE_CERT;
    try{
        $imap = new ImapClient(array(
            'flags' => array(
                'service' => $service,
                'encrypt' => $encryption,
                'validateCertificates' => $validation,
            ),
            'mailbox' => array(
                'remote_system_name' => $mailbox,
                'port' => $port
            ),
            'connect' => array(
                'username' => $username,
                'password' => $password
            )
        ));
        $imap->selectFolder("INBOX");
        echo true;
    }catch(Exception $e){
        echo false;
    }
}
return;
?>