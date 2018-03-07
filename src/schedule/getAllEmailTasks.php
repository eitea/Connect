<?php
require_once dirname(__DIR__)."/connection.php";
require_once dirname(__DIR__)."/utilities.php";
require_once dirname(dirname(__DIR__)).'/plugins/imap/autoload.php'; //TODO: remove
$trash = 'Connect_Tasks';
$result = $conn->query("SELECT * FROM emailprojects");
if($result){
    while($row = $result->fetch_assoc()){
        $security = empty($row['security']) ? '' : '/'.$row['security'];
        $mailbox = '{'.$row['server'] .':'. $row['port']. '/'.$row['service'] . $security.'/novalidate-cert}';
        //$conn->query("INSERT INTO emailprojectlogs VALUES(null,CURRENT_TIMESTAMP,'$mailbox')");
        $imap = new PhpImap\Mailbox($mailbox, $row['username'], $row['password'], __DIR__ ); //modified so nothing will be saved to disk
        if(!$imap->getMailboxes($trash)) $imap->createMailbox($trash);
        $imap->switchMailbox($mailbox.'INBOX');
        $mailsIds = $imap->searchMailbox('ALL');
        $result = $conn->query("SELECT * FROM taskemailrules WHERE emailaccount = ".$row['id']); echo $conn->error;
        while($rule = $result->fetch_assoc()){
            foreach($mailsIds as $mail_number){
                $mail = $imap->getMail($mail_number);
                $pos = strpos($mail->subject, $rule['identifier']);
                if($pos !== false){
                    //echo $mail->subject .' - found<br>';
                    $id = uniqid();
                    $null = null;
                    $doc = new DOMDocument();
                    //$doc->encoding = 'iso-8859-1';
                    @$doc->loadHTML($mail->textHtml);
                    $doc_body = new DOMDocument();
                    $body = $doc->getElementsByTagName('body')->item(0);
                    foreach ($body->childNodes as $child){
                        $doc_body->appendChild($doc_body->importNode($child, true));
                    }
                    $description = $doc_body->saveHTML();
                    //TODO: should replace this with dom viewer attr also, since descr can be very large
                    //$images = $doc->getElementsByTagName('img');
                    $attachments = $mail->getAttachments();
                    foreach($attachments as $attach){ //easy custom rawData
                        if(strpos($description, $attach->contentId)){
                            $description = str_replace("cid:".$attach->contentId, "data:image/jpeg;base64,".base64_encode($attach->rawData), $description);
                        } else {
                            $description .= '<img style="width:80%;" src="data:image/jpeg;base64,'.base64_encode($attach->rawData).'" />';
                        }
                    }
                    $name = substr_replace($mail->subject, '', $pos, strlen($rule['identifier']));
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
                    $estimated = $rule['estimatedHours'];
                    // PROJECT
                    $stmt = $conn->prepare("INSERT INTO dynamicprojects(projectid, projectname, projectdescription, companyid, clientid, clientprojectid, projectcolor, projectstart, projectend, projectstatus,
                        projectpriority, projectparent, projectowner, projectnextdate, projectseries, projectpercentage, projectleader, estimatedHours) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->bind_param("ssbiiissssisisbiis", $id, $name, $null, $company, $client, $project, $color, $start, $end, $status, $priority, $parent, $owner, $nextDate, $series, $percentage, $projectleader, $estimated);
                    $stmt->send_long_data(2, $description);
                    $stmt->execute();
                    if(!$stmt->error){
                        $stmt->close();
                        $conn->query("INSERT INTO dynamicprojectslogs (projectid, activity, userID) VALUES ('$id', 'CREATED', 1)");
                        //EMPLOYEES TEAMS
                        $stmt_emp = $conn->prepare("INSERT INTO dynamicprojectsemployees (projectid, userid, position) VALUES ('$id', ?, ?)"); echo $conn->error;
                        $stmt_emp->bind_param("is", $employee, $position);
                        $stmt_team = $conn->prepare("INSERT INTO dynamicprojectsteams (projectid, teamid) VALUES ('$id', ?)"); echo $conn->error;
                        $stmt_team->bind_param("i", $team);
                        $position = 'normal';
                        $employees = explode(",", $rule['employees']);
                        foreach($employees as $entry){
                            $entries = explode(";", $entry);
                            if($entries[0] == 'user'){
                                $employee = $entries[1];
                                $stmt_emp->execute();
                            } elseif($entries[0] == 'team'){
                                $team = $entries[1];
                                $stmt_team->execute();
                            }
                        }
                        if(!empty($rule['optionalemployees'])){
                            $position = 'optional';
                            $employees = explode(",", $rule['optionalemployees']);
                            foreach ($employees as $entry) {
                                $entries = explode(";", $entry);
                                if($entries[0] == 'user'){
                                    $employee = $entries[1];
                                    $stmt_emp->execute();
                                }
                            }
                        }
                        echo $stmt_emp->error;
                        echo $stmt_team->error;
                        $stmt_emp->close();
                        $stmt_team->close();
                        $imap->moveMail($mail_number, $trash);
                    } else {
                        echo $stmt->error;
                    }
                    break;
                }
            } //end foreach mail
        }
    }
} else {
    $conn->query("INSERT INTO emailprojectlogs VALUES(null,CURRENT_TIMESTAMP,'ERROR')");
}
?>