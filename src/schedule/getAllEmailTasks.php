<?php
require dirname(dirname(__DIR__))."/plugins/imap-client/autoload.php";
require dirname(__DIR__)."/connection.php";

use SSilence\ImapClient\ImapClientException;
use SSilence\ImapClient\ImapConnect;
use SSilence\ImapClient\ImapClient;

$result = $conn->query("SELECT * FROM emailprojects");
if($result){
    while($row = $result->fetch_assoc()){
        $mailbox = $row['server'];
        $username = $row['username'];
        $password = $row['password'];
        $service = strtoupper($row['service'])=="IMAP" ? ImapConnect::SERVICE_IMAP : ImapConnect::SERVICE_POP3;
        if($row['smtpSecure']=='null'){
            $encryption = null;
        }else{
            $encryption = $row['smtpSecure']=="tls" ? ImapClient::ENCRYPT_TLS : ImapClient::ENCRYPT_SSL;
        }
        $port = $row['port'];
        $validation = ImapConnect::VALIDATE_CERT;
        //echo json_encode($row);
        try{
        $imap = new ImapClient(array(
                'flags' => array(
                    'service' => $service,
                    'encrypt' => $encryption,
                    'validateCertificates' => $validation,
                    'debug' => ImapConnect::DEBUG
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
            //TODO Get Them juicy Tasks out of those e-mails
            //Derweilen Hard Coded
            $messages = $imap->getMessages();
            for($i = 0;$i<count($messages);$i++){
                if(strstr($messages[$i]->header->subject,"#Task#")){
                    if(insertTask($messages[$i],$conn)) $imap->deleteMessage($messages[$i]->header->uid);
                }
            }
            
        }catch(Exception $e){
            echo $e;
        }
        
    }
}else{
    echo json_encode("NÖ");
}
return;



function insertTask($message,$conn){
    $allowedTags = "<div><p><b><img><a><br><em><hr><i><li><ol><s><span><table><tr><td><u><ul>";

    $id = uniqid();
    $null = null;
    $name = str_replace("#Task#","",$message->header->subject);
    $description = strip_tags($message->message->html,$allowedTags);
    for($i=0;$i<count($message->attachments);$i++){
        if($message->attachments[$i]->info->structure->disposition == "inline"){
            $description = str_replace("cid:".trim($message->attachments[$i]->info->structure->id,'<>'),"data:image/".$message->attachments[$i]->info->structure->subtype.";base64,".base64_encode($message->attachments[$i]->body),$description);
        }
    }
    $company = 1;
    $client = '';
    $project = '';
    $color = '#FFFFFF';
    $start = date('Y-m-d');
    $end = '';
    $status = 'ACTIVE';
    $priority = 3; //1-5
    $parent = ''; //dynamproject id
    $owner = 1;
    $percentage = 0;
    $series = null;

                // PROJECT
                $stmt = $conn->prepare("INSERT INTO dynamicprojects(projectid, projectname, projectdescription, companyid, clientid, clientprojectid, projectcolor, projectstart, projectend, projectstatus,
                    projectpriority, projectparent, projectowner, projectnextdate, projectseries, projectpercentage) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("ssbiiissssisisbi", $id, $name, $null, $company, $client, $project, $color, $start, $end, $status, $priority, $parent, $owner, $nextDate, $null, $percentage);
                $stmt->send_long_data(2, $description);
                $stmt->send_long_data(12, $series);
                $stmt->execute();

                if(!$stmt->error){
                    $stmt->close();
                    //EMPLOYEES
                    $stmt = $conn->prepare("INSERT INTO dynamicprojectsemployees (projectid, userid, position) VALUES ('$id', ?, ?)"); echo $conn->error;
                    $stmt->bind_param("is", $employee, $position);
                    $position = 'normal';
     
                    $employee = 2;
                    $stmt->execute();
                }
                $stmt->close();
                return true;
}
?>