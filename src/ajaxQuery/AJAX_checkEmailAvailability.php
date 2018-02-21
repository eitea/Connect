<?php
if($_SERVER["REQUEST_METHOD"] == "POST"){
    $username = $_POST['username'];
    $password = $_POST['password'];
    $security = empty($_POST['security']) ? '' : '/'.$_POST['security'];
    $mailbox = '{'.$_POST['server'] .':'. $_POST['port']. '/'.$_POST['service'] . $security .'}'.'INBOX'; //{imap.gmail.com:993/imap/ssl}INBOX ; {localhost:993/imap/ssl/novalidate-cert}

    if(!function_exists('imap_open')){
        echo -1; //imap extension not installed
    } else {
        try{
            $imap = imap_open($mailbox, $username, $password, OP_READONLY, 5);
            if($imap && ($obj = imap_check($imap))){ //imap_check will turn mails to "read" if not OP_READONLY
                var_dump($obj);
                imap_close($imap);
            } else {
                echo 'Imap: ';
                var_dump($imap);
            }
        } catch(Exception $e) {
            echo 'ERROR DETECTED: ';
            var_dump($e);
        }
    }
}
?>
