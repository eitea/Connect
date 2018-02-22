<?php
require_once dirname(__DIR__)."/connection.php";
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

        $result = $conn->query("SELECT * FROM taskemailrules WHERE emailaccount = ".$row['id']); echo $conn->error;
        while($rule = $result->fetch_assoc()){
            foreach($mailsIds as $mail_number){
                $mail = $imap->getMail($mail_number);
                if($subject = strstr($mail->subject, $rule['identifier'])){
                    echo $subject .' - found<br>';
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
}
?>
