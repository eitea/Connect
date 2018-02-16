<?php
require_once dirname(dirname(__DIR__))."/plugins/imap-client/ssilence/php-imap-client/autoload.php";
require_once dirname(__DIR__)."/connection.php";

use SSilence\ImapClient\ImapClientException;
use SSilence\ImapClient\ImapConnect;
use SSilence\ImapClient\ImapClient;

$result = $conn->query("SELECT * FROM emailprojects");
if($result){
    while($row = $result->fetch_assoc()){
        $mailbox = $row['server'];
        $username = $row['username'];
        $password = $row['password'];
        //$conn->query("INSERT INTO emailprojectlogs VALUES(null,CURRENT_TIMESTAMP,'$mailbox')");
        $service = strtoupper($row['service'])=="IMAP" ? ImapConnect::SERVICE_IMAP : ImapConnect::SERVICE_POP3;
        if($row['smtpSecure']=='null'){
            $encryption = null;
        }else{
            $encryption = $row['smtpSecure']=="tls" ? ImapClient::ENCRYPT_TLS : ImapClient::ENCRYPT_SSL;
        }
        $port = $row['port'];
        $validation = ImapConnect::VALIDATE_CERT;
        //echo json_encode($row);
        $conn->query("INSERT INTO emailprojectlogs VALUES(null,CURRENT_TIMESTAMP,'$mailbox ; $username ; $password ; $service ; $encryption ; $port')");
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
            $messages = $imap->getMessages();
            $rulesets = $conn->query("SELECT * FROM taskemailrules WHERE emailaccount = ".$row['id']);
            while($rule = $rulesets->fetch_assoc()){
                for($i = 0;$i<count($messages);$i++){
                    if(strstr($messages[$i]->header->subject,$rule['identifier'])){ //Identifies how to handle this email
                        insertTask($imap,$messages[$i],$conn,$rule);
                    }
                } 
            }
            
        }catch(Exception $e){
            $conn->query("INSERT INTO emailprojectlogs VALUES(null,CURRENT_TIMESTAMP,'$e')");
        }
        
    }
}else{
    $conn->query("INSERT INTO emailprojectlogs VALUES(null,CURRENT_TIMESTAMP,'ERROR')");
}
return;



function insertTask($imap,$messages,$conn,$ruleset){
    $message = $messages->message;
    $conn->query("INSERT INTO emailprojectlogs VALUES(null,CURRENT_TIMESTAMP,'$message')");
    $allowedTags = "<div><p><b><img><a><br><em><hr><i><li><ol><s><span><table><tr><td><u><ul>";
try{
    $id = uniqid();
    $null = null;
    $name = str_replace($ruleset['identifier'],"",$messages->header->subject);
    $description = strip_tags($messages->message->html,$allowedTags);
    for($i=0;$i<count($messages->attachments);$i++){
        if($messages->attachments[$i]->info->structure->disposition == "inline"){
            $description = str_replace("cid:".trim($messages->attachments[$i]->info->structure->id,'<>'),"data:image/".$messages->attachments[$i]->info->structure->subtype.";base64,".base64_encode($messages->attachments[$i]->body),$description);
        }
    }
    $company = $ruleset['company'];
    $client = $ruleset['client'];
    $project = $ruleset['clientproject'];
    $color = $ruleset['color'];
    $start = date('Y-m-d');
    $end = '';
    $status = $ruleset['status'];
    $priority = $ruleset['priority']; //1-5
    $parent = $ruleset['parent']; //dynamproject id
    $owner = $ruleset['owner'];
    $percentage = 0;
    $series = null;
    $projectleader = $ruleset['leader'];

    // PROJECT
    $stmt = $conn->prepare("INSERT INTO dynamicprojects(projectid, projectname, projectdescription, companyid, clientid, clientprojectid, projectcolor, projectstart, projectend, projectstatus,
        projectpriority, projectparent, projectowner, projectnextdate, projectseries, projectpercentage, projectleader) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssbiiissssisisbii", $id, $name, $null, $company, $client, $project, $color, $start, $end, $status, $priority, $parent, $owner, $nextDate, $null, $percentage, $projectleader);
    $stmt->send_long_data(2, $description);
    $stmt->send_long_data(12, $series);
    $stmt->execute();

    if(!$stmt->error){
        $stmt->close();
        //EMPLOYEES
        $stmt = $conn->prepare("INSERT INTO dynamicprojectsemployees (projectid, userid, position) VALUES ('$id', ?, ?)"); echo $conn->error;
        $stmt->bind_param("is", $employee, $position);
        $position = 'normal';
        
        $employees = explode(",",$ruleset['employees']);
        foreach($employees as $employee){
                $stmt->execute();
        }
        if(!empty($ruleset['optionalemployees'])){
            $position = 'optional';
            $employees = explode(",",$ruleset['optionalemployees']);
            foreach ($employees as $optional_employee) {
                $employee = intval($optional_employee);
                $stmt->execute();
            }
        }
    }

    $stmt->close();
    $imap->deleteMessage($messages->header->uid);
}catch(Exception $e){
    $conn->query("INSERT INTO emailprojectlogs VALUES(null,CURRENT_TIMESTAMP,'$e')");
}
}
?>