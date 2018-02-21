<?php
if($_SERVER["REQUEST_METHOD"] == "POST"){
    $username = $_POST['username'];
    $password = $_POST['password'];
<<<<<<< HEAD
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
                'debug' => ImapConnect::DEBUG,
            ),
            'mailbox' => array(
                'remote_system_name' => $mailbox,
                'port' => $port
            ),
            'connect' => array(
                'username' => $username,
                'password' => $password,
                'n_retries' => 1
            )
        ));
        echo json_encode($imap->isConnected());
    }catch(Exception $e){
        echo $e;
=======
    $security = empty($_POST['security']) ? '' : '/'.$_POST['security'];
    $mailbox = '{'.$_POST['server'] .':'. $_POST['port']. '/'.$_POST['service'] . $security.'/novalidate-cert}'.'INBOX'; //{imap.gmail.com:993/imap/ssl}INBOX ; {localhost:993/imap/ssl/novalidate-cert}
    if(!function_exists('imap_open')){
        echo 'Imap not Installed'; //imap extension not installed
    } else {
        try{
            $imap = imap_open($mailbox, $username, $password, OP_READONLY, 5);
            if($imap && ($headers = imap_headers($imap))){ //imap_check will turn mails to "read" if not OP_READONLY
                echo "Connection Successful!\n";
                var_dump($headers);
                imap_close($imap);
            } elseif($imap) {
                var_dump($imap);
                imap_close($imap);
                echo "Error while retriving\n";
            } else {
                echo "Could not connect\n";
            }
        } catch(Exception $e) {
            echo 'ERROR DETECTED: ';
            var_dump($e);
        }
>>>>>>> master
    }
    var_dump(imap_errors());
}
?>
