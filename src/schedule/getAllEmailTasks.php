<?php
require_once dirname(__DIR__)."/connection.php";
<<<<<<< HEAD
$result = $conn->query("SELECT server, username, password FROM emailprojects");
//echo $result->num_rows;
if($result){
    while($row = $result->fetch_assoc()){
        $mailbox = $row['server'];
        $username = $row['username'];
        $password = $row['password'];
        //$conn->query("INSERT INTO emailprojectlogs VALUES(null,CURRENT_TIMESTAMP,'$mailbox')");
        $service = strtoupper($row['service'])=="IMAP" ? ImapConnect::SERVICE_IMAP : ImapConnect::SERVICE_POP3;
        if($row['smtpSecure']=='null'){
            $encryption = null;
        } else {
            $encryption = $row['smtpSecure']=="tls" ? ImapClient::ENCRYPT_TLS : ImapClient::ENCRYPT_SSL;
        }
        $port = $row['port'];
        $validation = ImapConnect::VALIDATE_CERT;
        echo "INSERT INTO emailprojectlogs VALUES(null,CURRENT_TIMESTAMP,'$mailbox ; $username ; $password ; $service ; $encryption ; $port')";
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
            echo $imap->isConnected();
            if($imap->isConnected()){
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
            }else{
                throw new Exception($imap->getError());
            }
        }catch(Exception $e){
            $conn->query("INSERT INTO emailprojectlogs VALUES(null,CURRENT_TIMESTAMP,'".substr($e,0,100)."')");
        }
    }
}else{
    $conn->query("INSERT INTO emailprojectlogs VALUES(null,CURRENT_TIMESTAMP,'ERROR')");
}
return;
function insertTask($imap,$messages,$conn,$ruleset){
    $message = $messages->message;
    //$conn->query("INSERT INTO emailprojectlogs VALUES(null,CURRENT_TIMESTAMP,'$message->html')");
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
=======
require_once dirname(__DIR__)."/utilities.php";
require_once dirname(dirname(__DIR__)).'/plugins/imap/autoload.php';

$result = $conn->query("SELECT * FROM emailprojects");
if($result){
    while($row = $result->fetch_assoc()){
        $security = empty($row['security']) ? '' : '/'.$row['security'];
        $mailbox = '{'.$row['server'] .':'. $row['port']. '/'.$row['service'] . $security.'/novalidate-cert}'.'INBOX';

        $conn->query("INSERT INTO emailprojectlogs VALUES(null,CURRENT_TIMESTAMP,'$mailbox')");
        $imap = new PhpImap\Mailbox($mailbox, $row['username'], $row['password'], __DIR__ ); //modified so nothing will be saved to disk
        $mailsIds = $imap->searchMailbox('ALL');

        $result = $conn->query("SELECT * FROM taskemailrules WHERE emailaccount = ".$row['id']);
        while($rule = $result->fetch_assoc()){
            foreach($mailsIds as $mail_number){
                $mail = $imap->getMail($mail_number);
                if($subject = strstr($mail->subject, $rule['identifier'])){
                    $id = uniqid();
                    $null = null;
                    $name = str_replace($rule['identifier'],"",$subject);
                    $description = convToUTF8($mail->textHtml);

                    $attachments = $mail->getAttachments();
                    foreach($attachments as $attach){ //easy custom rawData
                        $description = str_replace("cid:".$attach->contentId, "data:image/jpeg;base64,".base64_encode($attach->rawData), $description);
                    }

                    $company = $rule['company'];
                    $client = $rule['client'];
                    $project = $rule['clientproject'];
                    $color = $rule['color'];
                    $start = date('Y-m-d');
                    $end = '';
                    $status = $rule['status'];
                    $priority = $rule['priority']; //1-5
                    $parent = $rule['parent']; //dynamproject id
                    $owner = $rule['owner'];
                    $percentage = 0;
                    $series = null;
                    $projectleader = $rule['leader'];
                    // PROJECT
                    $stmt = $conn->prepare("INSERT INTO dynamicprojects(projectid, projectname, projectdescription, companyid, clientid, clientprojectid, projectcolor, projectstart, projectend, projectstatus,
                        projectpriority, projectparent, projectowner, projectnextdate, projectseries, projectpercentage, projectleader) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                        $stmt->bind_param("ssbiiissssisisbii", $id, $name, $null, $company, $client, $project, $color, $start, $end, $status, $priority, $parent, $owner, $nextDate, $series, $percentage, $projectleader);
                        $stmt->send_long_data(2, $description);
                        $stmt->execute();
                        if(!$stmt->error){
                            $stmt->close();
                            //EMPLOYEES
                            $stmt = $conn->prepare("INSERT INTO dynamicprojectsemployees (projectid, userid, position) VALUES ('$id', ?, ?)"); echo $conn->error;
                            $stmt->bind_param("is", $employee, $position);
                            $position = 'normal';
                            $employees = explode(",", $rule['employees']);
                            foreach($employees as $employee){
                                $stmt->execute();
                            }
                            if(!empty($rule['optionalemployees'])){
                                $position = 'optional';
                                $employees = explode(",",$rule['optionalemployees']);
                                foreach ($employees as $optional_employee) {
                                    $employee = intval($optional_employee);
                                    $stmt->execute();
                                }
                            }
                        } else {
                            echo $stmt->error;
                        }
                        $stmt->close();

                    //$imap->deleteMail($mail_number);
                }
            }
        }
    }
} else {
    $conn->query("INSERT INTO emailprojectlogs VALUES(null,CURRENT_TIMESTAMP,'ERROR')");
>>>>>>> master
}
?>