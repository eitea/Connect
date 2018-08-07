<?php
if($_SERVER['REQUEST_METHOD'] == 'POST'){
    if(!empty($_POST['play']) || !empty($_POSt['play-take'])){ //5b03f7a9d151a
        $ckIn_disabled = 'disabled';
    }
}
include dirname(__DIR__) . '/header.php';
require dirname(__DIR__) . "/misc/helpcenter.php";
require dirname(__DIR__) . "/Calculators/dynamicProjects_ProjectSeries.php";

//$referenceTime is the time where the progress bar overall length hits 100% (it can't go over 100%)
function generate_progress_bar($current, $estimate, Array $options = ['referenceTime' => 8]){
    $allHours = 0;
    $times = explode(' ', $estimate);
    foreach($times as $t){
        if(is_numeric($t) || substr($t, -1) == 'h'){
            $allHours += floatval($t); //5ac62fddd5ccc
        } elseif(substr($t, -1) == 'M'){
            $allHours += intval($t) * 730.5;
        } elseif(substr($t, -1) == 'w'){
            $allHours += intval($t) * 168;
        } elseif(substr($t, -1) == 't'){
            $allHours += intval($t) * 24;
        } elseif(substr($t, -1) == 'm'){
            $allHours += intval($t) / 60;
        }
    }
    if($current < $allHours){
        $yellowBar = $current/($allHours+0.0001);
        $greenBar = 1-$yellowBar;
        $timeLeft = $allHours - $current;
        $redBar = 0;
        $timeOver = 0;
    } else {
        $timeOver = $current - $allHours;
        $timeLeft = 0;
        $greenBar = 0;
        $yellowBar = ($allHours)/($timeLeft + $timeOver + $current + 0.0001);
        $redBar = 1-$yellowBar;
        $current = $allHours;
    }
    $greenBar *= 100;
    $yellowBar *= 100;
    $redBar *= 100;
	if(empty($options['referenceTime'])) $options['referenceTime'] = 8;
    $progressLength = min(($timeLeft + $timeOver + $current)/$options['referenceTime'], 1);
	$greenID = $yellowID = $redID = '';
	if(!empty($options['animate']) && $options['animate']){
		$greenID = 'id="progress-bar-green"';
		$yellowID = 'id="progress-bar-yellow"';
		$redID = 'id="progress-bar-red"';
	}
	//TODO: change title
    return '<div style="height:5px;margin-bottom:2px;width:'.($progressLength * 100).'%" class="progress">'
    .'<div '.$yellowID.' data-toggle="tooltip" title="'.round($current, 2).' Stunden" class="progress-bar progress-bar-warning" style="height:10px;width:'.$yellowBar.'%"></div>'
    .'<div '.$greenID.' data-toggle="tooltip" title="'.round($timeLeft,2).' Stunden" class="progress-bar progress-bar-success" style="height:10px;width:'.$greenBar.'%"></div>'
    .'<div '.$redID.' data-toggle="tooltip" title="'.round($timeOver,2).' Stunden" class="progress-bar progress-bar-danger" style="height:10px;width:'.$redBar.'%"></div></div>';
}
$available_teams = array();
$filterings = array("savePage" => $this_page, "company" => 0, "client" => 0, "project" => 0, 'tasks' => 'ACTIVE', 'taskview' => 'default', "priority" => 0, 'employees' => []);
//$filterings = array("savePage" => $this_page, "company" => 0, "client" => 0, "project" => 0, 'tasks' => 'ACTIVE', "priority" => 0, 'employees' => ["user;".$userID]); //set_filter requirement
$result = $conn->query("SELECT teamID FROM relationship_team_user WHERE userID = $userID");
while($result && ( $row = $result->fetch_assoc())){
	$available_teams[] = $row['teamID'];
}
$templateResult = $conn->query("SELECT projectname,projectid,v2 FROM dynamicprojects WHERE isTemplate = 'TRUE'");
?>
<div class="page-header-fixed">
	<div class="page-header"><h3>Tasks<div class="page-header-button-group">
	    <?php include dirname(__DIR__) . '/misc/set_filter.php';
		if($user_roles['isDynamicProjectsAdmin'] == 'TRUE'|| $user_roles['canCreateTasks'] == 'TRUE'):
			if($templateResult->num_rows > 0): ?>
	        <div class="dropdown" style="display:inline;">
	            <button class="btn btn-default dropdown-toggle" data-toggle="dropdown" type="button"><i class="fa fa-plus"></i></button>
				<ul class="dropdown-menu" >
	                <div class="container-fluid">
	                    <li ><button class="btn btn-default btn-block" data-toggle="modal" data-target="#editingModal-" >Neu</button></li>
	                    <li class="divider"></li>
	                    <li ><button class="btn btn-default btn-block" data-toggle="modal" data-target="#template-list-modal" >Template</button></li>
	                </div>
	            </ul>
	        </div>
			<?php else : ?>
				<button class="btn btn-default" data-toggle="modal" data-target="#editingModal-" ><i class="fa fa-plus"></i></button>
			<?php endif; ?>
			<button type="button" class="btn btn-default" data-toggle="modal" data-target="#taskTemplateEditor-modal">Task Templates</button>
		<?php endif; ?>
	</div></h3></div>
</div>

<?php include __DIR__.'/taskTemplateEditor.php'; ?>
<div class="page-content-fixed-100">
<?php
if($_SERVER['REQUEST_METHOD'] == 'POST'){
	include dirname(__DIR__).'/social/chatwindow_backend.php';
	if(!empty($_POST['play']) || !empty($_POST['play-take'])){
        if(!empty($_POST['play'])){
            $x = test_input($_POST['play'], true);
        } else {
            $x = test_input($_POST['play-take'], true);
            $conn->query("UPDATE dynamicprojectsemployees SET status='leader' WHERE userid = $userID AND status != 'owner' AND projectid = '$x'");
            $conn->query("INSERT INTO dynamicprojectslogs (projectid, activity, userID) VALUES ('$x', 'DUTY', $userID)");
        }
        $result = $conn->query("SELECT id FROM projectBookingData WHERE `end` = '0000-00-00 00:00:00' AND dynamicID = '$x'");
        if($result->num_rows < 1){
            $result = mysqli_query($conn, "SELECT indexIM FROM logs WHERE userID = $userID AND timeEnd = '0000-00-00 00:00:00' LIMIT 1");
            if($result && ($row = $result->fetch_assoc())){
                $indexIM = $row['indexIM'];
                //TODO: make sure play does not overlap an existing booking and user does not have a lücke in the previous buchung
                //$result = $conn->query("SELECT start, end FROM projectBookingData WHERE timestampID = $indexIM");
                $conn->query("INSERT INTO projectBookingData (start, end, timestampID, infoText, bookingType, dynamicID)
                VALUES(UTC_TIMESTAMP, '0000-00-00 00:00:00', $indexIM, 'Begin of Task $x' , 'project', '$x')");
                if($conn->error){
                    showError($conn->error);
                } else {
                    showSuccess('Task wurde gestartet. Solange dieser läuft, ist Ausstempeln nicht möglich.');
                }
            } else {
                showError('<strong>Bitte einstempeln.</strong> Tasks können nur angenommen werden, sofern man eingestempelt ist.');
            }
        } else {
            showError('<strong>Task besetzt.</strong> Dieser Task gehört schon jemandem.');
        }
    } elseif(!empty($_POST['task-plan-date']) && !empty($_POST['task-plan'])){
		$x = test_input($_POST['task-plan'], 1);
        $conn->query("UPDATE dynamicprojectsemployees SET position='leader' WHERE userid = $userID AND projectid = '$x' AND status != 'owner'");

        if(test_Date($_POST['task-plan-date'].':00')){
            $date = carryOverAdder_Hours($_POST['task-plan-date'].':00', $timeToUTC * -1);
            $conn->query("UPDATE dynamicprojects SET projectstart = '$date' WHERE projectid = '$x'");
            if($conn->error){
                showError($conn->error);
            } else {
                showSuccess($lang['OK_SAVE']);
				$conn->query("INSERT INTO dynamicprojectslogs (projectid, activity, userID, extra1) VALUES('$x', 'DELAY', $userID, '$date')");
            }
        } else {
            showError('Datum ungültig. Format YYYY-MM-DD HH:mm');
        }
    } elseif(!empty($_POST['add-note']) && !empty($_POST['add-note-text'])){
		$x = test_input($_POST['add-note'], true);
		$val = test_input($_POST['add-note-text']);
		$conn->query("INSERT INTO dynamicprojectsnotes(taskID, userID, notetext) VALUES('$x', '$userID', '$val')");
		if($conn->error){
			showError($conn->error.__LINE__);
		} else {
			showSuccess($lang['OK_ADD']);
		}
	} elseif(!empty($_POST['take_task'])){
        $x = test_input($_POST['take_task'], true);
        $conn->query("INSERT INTO dynamicprojectsemployees (position, userid, projectid) VALUES('leader', $userID, '$x')
			ON DUPLICATE KEY UPDATE position = IF(position = 'owner', 'owner', 'leader')");
        if($conn->error){
            showError($conn->error);
        } else {
			$conn->query("INSERT INTO dynamicprojectslogs (projectid, activity, userID) VALUES ('$x', 'TAKEOVER', $userID)");
            showSuccess($lang['OK_ADD']);
        }
    } elseif(!empty($_POST['send_new_message']) && !empty($_POST['new_message_body'])){
		$dynamicID = test_input($_POST['send_new_message']);
		$message = asymmetric_encryption('CHAT', test_input($_POST['new_message_body']), $userID, $privateKey);
		//conversation
		$result = $conn->query("SELECT id FROM messenger_conversations WHERE category = 'task' AND categoryID = '$dynamicID'");
		if($result->num_rows > 0 && ($row = $result->fetch_assoc())){
			$conversationID = $row['id'];
		} else {
			$conn->query("INSERT INTO messenger_conversations (identifier, subject, category, categoryID) VALUES ('".uniqid()."', 'TASK: $dynamicID', 'task', '$dynamicID')"); echo $conn->error;
			$conversationID = $conn->insert_id;
			$conn->query("INSERT INTO relationship_conversation_participant (conversationID, partType, partID, status)
			SELECT $conversationID, 'USER', userID, 'open' FROM dynamicprojectsemployees WHERE projectid = '$dynamicID' AND position = 'owner'");
			if($conn->error){
				showError($conn->error);
			} else {
				showSuccess($lang['OK_CREATE']);
			}
		}
		//participant
		$result = $conn->query("SELECT id FROM relationship_conversation_participant WHERE partType = 'USER' AND partID = '$userID' and conversationID = $conversationID");
		if($result->num_rows >  0 && ($row = $result->fetch_assoc())){
			$participantID = $row['id'];
		} else {
			$conn->query("INSERT INTO relationship_conversation_participant (conversationID, partType, partID, status) VALUES ($conversationID, 'USER', '$userID', 'open')");
			$participantID = $conn->insert_id;
		}
		//message
		$conn->query("INSERT INTO messenger_messages (message, participantID, type, vKey) VALUES('$message', $participantID, 'text', '$publicKey')");
		if($conn->error){
			showError($conn->error);
		} else {
			showSuccess($lang['OK_SEND']);
		}
	}
    if(!empty($_POST['createForgottenBooking']) && !empty($_POST['description']) && isset($_POST["time-range"])){
        $timeRange =  explode(";",$_POST["time-range"]); //format date;start;end;timeToUTC;indexIM
        $date = $timeRange[0];
        $start = $timeRange[1];
        $end = $timeRange[2];
        $timeToUTC = intval($timeRange[3]);
        $indexIM = intval($timeRange[4]);
        $x = $dynamicID = test_input($_POST["createForgottenBooking"], true);
        $result = $conn->query("SELECT clientprojectid, needsreview FROM dynamicprojects WHERE projectid = '$x'");
        if(!test_Time($end) || !test_Time($start) || !$result){
            echo "invalid time format or project id";
        } else {
            $row = $result->fetch_assoc();
            $projectID = $row['clientprojectid'] ? $row['clientprojectid'] : intval($_POST['bookDynamicProjectID']);
            $description = test_input($_POST['description']);
            $startDate = $date." ".$start;
            $startDate = carryOverAdder_Hours($startDate, $timeToUTC * -1);
            $endDate = $date." ".$end;
            $endDate = carryOverAdder_Hours($endDate, $timeToUTC * -1);
            if(timeDiff_Hours($startDate, $endDate) < 0){
                $endDate = carryOverAdder_Hours($endDate, 24);
                $date = substr($endDate, 0, 10);
            }
            if(timeDiff_Hours($startDate, $endDate) > 0 && timeDiff_Hours($startDate, $endDate) < 12){
                $percentage = intval($_POST['bookCompleted']);
                if($percentage == 100 || isset($_POST['bookCompletedCheckbox'])){
                    $percentage = 100; //safety
                    if($row['needsreview'] == 'TRUE'){
                        $conn->query("UPDATE dynamicprojects SET projectstatus = 'REVIEW' WHERE projectid = '$dynamicID'");
						if(!$conn->error){
							$result = $conn->query("SELECT userid FROM dynamicprojectsemployees WHERE position = 'owner' AND projectid = '$dynamicID'");
							if($result && ($emp_row = $result->fetch_assoc())){
								sendNotification($emp_row['userid'], "Review Task $dynamicID", "Task $dynamicID wurde soeben abgeschlossen und ist bereit zur Überprüfung!");
							} else {
								echo $conn->error;
							}
						} else {
							showError($conn->error);
						}
                    } else {
                        $conn->query("UPDATE dynamicprojects SET projectstatus = 'COMPLETED' WHERE projectid = '$dynamicID'");
                    }
                }
                $conn->query("UPDATE dynamicprojects SET projectpercentage = $percentage WHERE projectid = '$dynamicID'");
                //normal booking
                $sql = "INSERT INTO projectBookingData (start, end, projectID, timestampID, infoText, internInfo, bookingType, dynamicID)
                VALUES('$startDate', '$endDate', $projectID, $indexIM, '$description', '$percentage% Abgeschlossen', 'project', '$dynamicID')";
                $conn->query($sql);
                echo $conn->error;
            } else {
                echo "invalid time";
            }
        }
    }
    if(!empty($_POST['createBooking']) && (!empty($_POST['completeWithoutBooking']) || !empty($_POST['description']))){
        $bookingID = test_input($_POST['createBooking']);
        $result = $conn->query("SELECT dynamicID, clientprojectid, needsreview FROM projectBookingData p, dynamicprojects d WHERE id = $bookingID AND d.projectid = p.dynamicID");
        if($row = $result->fetch_assoc()){
            $dynamicID = $row['dynamicID'];
            $projectID = $row['clientprojectid'];
            if(!$projectID && !empty($_POST['bookDynamicProjectID'])) $projectID = intval($_POST['bookDynamicProjectID']); //5acaff282ced0
            if($projectID || !empty($_POST['completeWithoutBooking'])){
                $result->free();
                $percentage = intval($_POST['bookCompleted']);
                if($percentage == 100 || isset($_POST['bookCompletedCheckbox'])){
                    $percentage = 100; //safety
                    if($row['needsreview'] == 'TRUE'){
                        $conn->query("UPDATE dynamicprojects SET projectstatus = 'REVIEW' WHERE projectid = '$dynamicID'");
						if(!$conn->error){
							$result = $conn->query("SELECT userid FROM dynamicprojectsemployees WHERE position = 'owner' AND projectid = '$dynamicID'");
							if($result && ($emp_row = $result->fetch_assoc())){
								sendNotification($emp_row['userid'], "Review Task $dynamicID", "Task $dynamicID wurde soeben abgeschlossen und ist bereit zur Überprüfung!");
							}
						} else {
							showError($conn->error);
						}
                    } else {
                        $conn->query("UPDATE dynamicprojects SET projectstatus = 'COMPLETED' WHERE projectid = '$dynamicID'");
                    }
                }
                $conn->query("UPDATE dynamicprojects SET projectpercentage = $percentage WHERE projectid = '$dynamicID'");
                $microtasks = $conn->query("SELECT microtaskid, ischecked FROM microtasks WHERE projectid='$dynamicID'");
                if($microtasks){
                    while($microrow = $microtasks->fetch_assoc()){
                        if($microrow['ischecked']=='FALSE'){
                            if(isset($_POST["mtask".$microrow['microtaskid']])){ // IS ALLWAYS SET?
                                $conn->query("UPDATE microtasks SET ischecked='TRUE', finisher = $userID, completed = CURRENT_TIMESTAMP WHERE microtaskid = '".$microrow['microtaskid']."'");
                            }
                        }
                    }
                }
                if(!empty($_POST['completeWithoutBooking'])){
                    $conn->query("DELETE FROM projectBookingData WHERE id = $bookingID");
                } else {
                    $description = 'TASK: '. $dynamicID.' - '.test_input($_POST['description']); //5ac9e5167c616, 5aeb2a8256b5f
                    $conn->query("UPDATE projectBookingData SET end = UTC_TIMESTAMP, infoText = '$description', projectID = '$projectID', internInfo = '$percentage% von $dynamicID Abgeschlossen' WHERE id = $bookingID");
                }
                if($conn->error){
                    showError($conn->error);
                }
            } else {
                showError($lang['ERROR_MISSING_SELECTION'].' (Projekt)');
            }
        } else { //STRIKE
            $conn->query("UPDATE UserData SET strikeCount = strikecount + 1 WHERE id = $userID");
            showError('<strong>Project not Available.</strong> '.$lang['ERROR_STRIKE']);
        }
    }
	if(!empty($_POST['change-status']) && !empty($_POST['change-status-assigned-user']) && !empty($_POST['change-status-status']) && !empty($_POST['change-status-note'])){
		$val = test_input($_POST['change-status']);
		$note = test_input($_POST['change-status-note']);
		$newUser = intval($_POST['change-status-assigned-user']);
		$status = test_input($_POST['change-status-status'], 1);

		$conn->query("DELETE FROM dynamicprojectsemployees WHERE position = 'leader' AND projectid = '$val'");
		$err = $conn->error;
		$conn->query("INSERT INTO dynamicprojectsemployees (position, userid, projectid) VALUES('leader', $newUser, '$val')
			ON DUPLICATE KEY UPDATE position = IF(position = 'owner', 'owner', 'leader')");
		$err .= $conn->error;
		$conn->query("INSERT INTO dynamicprojectslogs (projectid, activity, userID, extra1, extra2) VALUES('$val', 'STATECHANGE', $userID, '".$userID_toName[$newUser]."', '$note')");
		$err .= $conn->error;
		if($status == 'COMPLETED'){
			$conn->query("UPDATE dynamicprojects SET projectstatus = 'COMPLETED' AND projectpercentage = '100' WHERE projectid = '$val'");
		} else {
			$conn->query("UPDATE dynamicprojects SET projectstatus = '$status' WHERE projectid = '$val'");
		}
		$err .= $conn->error;
		if($err){
			showError($err);
		} else {
			showSuccess('Status wurde auf '.$userID_toName[$newUser].' Übertragen');
		}
	} elseif(isset($_POST['change-status'])){
		showError($lang['ERROR_MISSING_FIELDS']);
	}
    if($user_roles['isDynamicProjectsAdmin'] == 'TRUE' || $user_roles['canCreateTasks'] == 'TRUE'){
        if(!empty($_POST['deleteProject'])){
            $val = test_input($_POST['deleteProject']);
            $conn->query("DELETE FROM dynamicprojectslogs WHERE projectid = '$val'");
            $conn->query("DELETE FROM dynamicprojects WHERE projectid = '$val'");
			$conn->query("DELETE FROM archive WHERE category = 'TASK' AND categoryID = '$val'"); //corpses, everyone, corpses!
            if($conn->error){
                showError($conn->error);
            } else {
                showSuccess($lang['OK_DELETE']);
            }
        }

        if(isset($_POST['editDynamicProject'])){ //new projects
			$setEdit = false;
			if(isset($available_companies[1]) && !empty($_POST['name']) && !empty($_POST['employees']) && !empty($_POST['description'])){
				$id = uniqid();
				if(!empty($_POST['editDynamicProject'])){ //existing
					$id =  test_input($_POST['editDynamicProject']);
					$conn->query("DELETE FROM dynamicprojects WHERE projectid = '$id'"); echo $conn->error; //fk does the rest
					$conn->query("INSERT INTO dynamicprojectslogs (projectid, activity, userID) VALUES ('$id', 'EDITED', $userID)");
				} else { //new
					$conn->query("INSERT INTO dynamicprojectslogs (projectid, activity, userID) VALUES ('$id', 'CREATED', $userID)");
				}
				$null = null;
				$name = asymmetric_seal('TASK', test_input($_POST["name"]));
				$description = $_POST["description"];
				if(preg_match_all("/\[([^\]]*)\]\s*\{([^\[]*)\}/m",$description,$matches) && count($matches[0])>0){
					for($i = 0;$i<count($matches[0]);$i++){
						$mname = strip_tags($matches[1][$i]);
						$info = strip_tags($matches[2][$i]);
						$mid = uniqid();
						$conn->query("INSERT INTO microtasks VALUES('$id','$mid','$mname','FALSE',null,null)");
						$checkbox = "<input type='checkbox' id='$mid' disabled title=''><b>".$mname."</b><br>".$info."</input>";
						$mname = preg_quote($mname);
						$description = preg_replace("/\[($mname)\]\s*\{([^\[]*)\}/m",$checkbox,$description,1);
						if($conn->error){
							showError($conn->error);
						}
					}
				}
				$description = asymmetric_seal('TASK', $description);
				$company = $_POST["filterCompany"] ?? $available_companies[1];
				$client = isset($_POST['filterClient']) ? intval($_POST['filterClient']) : '';
				$project = isset($_POST['filterProject']) ? intval($_POST['filterProject']) : '';
				$color = $_POST["color"] ? test_input($_POST['color']) : '#FFFFFF';
				$start = $_POST["start"];
				$end = '0000-00-00'; //temp fix for invalid end value (probs NULL)
				$status = $_POST["status"];
				$priority = intval($_POST["priority"]); //1-5
				$percentage = intval($_POST['completed']);
				$estimate = test_input($_POST['estimatedHours']);
				$isTemplate = isset($_POST['isTemplate']) ? 'TRUE' : 'FALSE';
				if($user_roles['isDynamicProjectsAdmin'] == 'TRUE'){
					$skill = intval($_POST['projectskill']);
					$parent = test_input($_POST["parent"]); //dynamproject id
				} else {
					$skill = 0;
					$parent = null;
				}
				if($status == 'COMPLETED') $percentage = 100;
				if(!empty($_POST['projecttags'])){
					$tags = implode(',', array_map( function($data){ return preg_replace("/[^A-Za-z0-9]/", '', $data); }, $_POST['projecttags'])); //strictly map and implode the tags
				} else {
					$tags = '';
				}
				// PROJECT
				$stmt = $conn->prepare("INSERT INTO dynamicprojects(projectid, projectname, projectdescription, companyid, clientid, clientprojectid, projectcolor, projectstart,
					projectend, projectstatus, projectpriority, projectparent, projectpercentage, estimatedHours, level, projecttags, isTemplate)
					VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"); echo $conn->error;
				$stmt->bind_param("ssbiiissssissisis", $id, $name, $null, $company, $client, $project, $color, $start, $end, $status, $priority, $parent, $percentage, $estimate, $skill, $tags, $isTemplate);
				$stmt->send_long_data(2, $description);
				$stmt->execute();
				if(!$stmt->error){
					$stmt->close();
					$setEdit = true;
					//EMPLOYEES
					$stmt = $conn->prepare("INSERT INTO dynamicprojectsemployees (projectid, userid, position) VALUES ('$id', ?, ?)"); echo $conn->error;
					$stmt->bind_param("is", $employee, $position);

					$position = 'owner';
					$employee = $userID;
					$stmt->execute();
					if(isset($_POST['leader'])){
						$position = 'leader';
						$employee = intval($_POST['leader']);
						$stmt->execute();
					}

					$position = 'normal';
					foreach ($_POST["employees"] as $employee) {
						$emp_array = explode(";", $employee);
						if ($emp_array[0] == "user") {
							$employee = intval($emp_array[1]);
							$stmt->execute();
						} else {
							$team = intval($emp_array[1]);
							$conn->query("INSERT INTO dynamicprojectsteams (projectid, teamid) VALUES ('$id',$team)");
						}
					}
					if(isset($_POST['optionalemployees']) && !empty($_POST['optionalemployees'])){
						$position = 'optional';
						foreach ($_POST['optionalemployees'] as $optional_employee) {
							$employee = intval($optional_employee);
							$stmt->execute();
						}
					}
					//FILES
					$bucket = $identifier.'-tasks';
					$s3 = getS3Object($bucket);
					if($s3 && !empty($_POST['deleteTaskFile'])){
						foreach($_POST['deleteTaskFile'] as $val){
							$conn->query("DELETE FROM archive WHERE uniqID = '$val'");
							if((!$conn->error)) $s3->deleteObject(['Bucket' => $bucket, 'Key' => $val]);
						}
					}
					if(isset($_FILES['newTaskFiles'])){
						for ($i = 0; $i < count($_FILES['newTaskFiles']['name']); $i++) {
							if($s3 && file_exists($_FILES['newTaskFiles']['tmp_name'][$i]) && is_uploaded_file($_FILES['newTaskFiles']['tmp_name'][$i])){
								$file_info = pathinfo($_FILES['newTaskFiles']['name'][$i]);
								$ext = test_input(strtolower($file_info['extension']));
								if (!validate_file($err, $ext, $_FILES['newTaskFiles']['size'][$i])){
									showError($err);
								} else {
									$hashkey = uniqid('', true); //23 chars
									$file_encrypt = asymmetric_seal('TASK', file_get_contents($_FILES['newTaskFiles']['tmp_name'][$i]));
									$s3->putObject(array(
										'Bucket' => $bucket,
										'Key' => $hashkey,
										'Body' => $file_encrypt
									));
									$filename = test_input($file_info['filename']);
									$conn->query("INSERT INTO archive (category, categoryID, name, parent_directory, type, uniqID, uploadUser)
									VALUES ('TASK', '$id', '$filename', 'ROOT', '$ext', '$hashkey', $userID)");
								}
							}
						}
					}
					$stmt->close();
					$stmt = $conn->prepare("INSERT INTO dynamicprojectsemployees (projectid, userid, position) VALUES ('$id', ?, ?)"); echo $conn->error;
					$stmt->bind_param("is", $employee, $position);
					showSuccess($lang['OK_ADD']);
				} else {
					showError($stmt->error);
				}
				$stmt->close();
			} else {
				showError($lang['ERROR_MISSING_FIELDS']);
				if(empty($_POST['owner'])) showError('Fehlend: Projektbesitzer');
				if(empty($_POST['employees'])) showError('Fehlend: Mitarbeiter');
			}
        }
    } // end if dynamic Admin
} //end if POST
$completed_tasks = file_get_contents('task_changelog.txt', true);
?>
<form method="POST">
<?php
if($filterings['tasks'] == 'ACTIVE_PLANNED'){
    echo '<div class="text-right"><button type="submit" formaction="../tasks/icalDownload" formtarget="_blank" class="btn btn-warning">Als .ical Downloaden</button></div>';
}
?>
<table class="table table-hover">
    <thead>
        <tr>
            <th style="width:250px;"><?php echo $lang["DYNAMIC_PROJECTS_PROJECT_NAME"]; ?></th>
            <th><?php echo $lang["DESCRIPTION"]; ?></th>
            <th><?php echo $lang["COMPANY"].' - '.$lang["CLIENT"].' - '.$lang["PROJECT"]; ?></th>
            <th><?php echo $lang["BEGIN"]; ?></th>
            <th><?php echo $lang["END"]; ?></th>
            <th>Status</th>
            <th><?php echo $lang["DYNAMIC_PROJECTS_PROJECT_PRIORITY"]; ?></th>
            <th><?php echo $lang["EMPLOYEE"]; ?></th>
            <th>Review</th>
            <th></th>
        </tr>
    </thead>
    <tbody>
        <?php
        $occupation = $query_filter = '';
        $priority_color = ['', '#2a5da1', '#0c95d9', '#9d9c9c', '#ff7600', '#ff0000'];
        if($filterings['company']){ $query_filter .= "AND d.companyid = ".intval($filterings['company']); }
        if($filterings['client']){ $query_filter .= " AND d.clientid = ".intval($filterings['client']); }
        if($filterings['project']){ $query_filter .= " AND d.clientprojectid = ".intval($filterings['project']); }
        if($filterings['tasks']){
            if($filterings['tasks'] != 'ACTIVE_PLANNED'){
                $query_filter .= " AND d.projectstart <= UTC_TIMESTAMP ";
            }
            if($filterings['tasks'] == 'ACTIVE_PLANNED'){
                $query_filter .= " AND d.projectstart > UTC_TIMESTAMP ";
            } elseif($filterings['tasks'] == 'REVIEW_1'){
                $query_filter .= " AND d.projectstatus = 'REVIEW' AND needsreview = 'TRUE' ";
            } elseif($filterings['tasks'] == 'REVIEW_2'){
                $query_filter .= " AND d.projectstatus = 'REVIEW' AND needsreview = 'FALSE' ";
            } else {
                $query_filter .= " AND d.projectstatus = '".test_input($filterings['tasks'])."' ";
            }
        }
        if($filterings['priority'] > 0){ $query_filter .= " AND d.projectpriority = ".$filterings['priority']; }

		$filter_emps = $filter_team = '0';
        foreach($filterings['employees'] as $val){
            $arr = explode(';', $val);
            if($arr[0] == 'user') $filter_emps .= " OR conemployees LIKE '% {$arr[1]} %'";
            if($arr[0] == 'team') $filter_team .= " OR conteamsids LIKE '% {$arr[1]} %'";
        }

        if($filter_emps || $filter_team) $query_filter .= " AND ($filter_emps OR $filter_team)";

	    $nonAdminQuery = '';
        if($user_roles['isDynamicProjectsAdmin'] == 'FALSE'){
			foreach($available_teams as $val) $nonAdminQuery .= " OR conteamsids LIKE '% $val %' ";
			$nonAdminQuery = "AND (conemployees LIKE '% $userID %' $nonAdminQuery)";
        }
		$stmt_employees = $conn->prepare("SELECT userid, position FROM dynamicprojectsemployees WHERE projectid = ?");
		$stmt_employees->bind_param('s', $x);

		$sql = ("SELECT d.projectid, projectname, projectdescription, projectcolor, projectstart, projectend, projectseries, projectstatus,
			projectpriority, projectpercentage, projecttags, v2, d.companyid, d.clientid, d.clientprojectid, companyData.name AS companyName,
			clientData.name AS clientName, projectData.name AS projectDataName, needsreview, estimatedHours, tbl2.conteams, tbl2.conteamsids, tbl3.currentHours,
			tbl5.userID AS workingUser, tbl5.start AS workStart, tbl5.id AS workingID, dpl.activity
			FROM dynamicprojects d
			LEFT JOIN ( SELECT projectid, CONCAT(' ',GROUP_CONCAT(userid SEPARATOR ' '),' ') AS conemployees FROM dynamicprojectsemployees GROUP BY projectid ) tbl ON tbl.projectid = d.projectid
			LEFT JOIN companyData ON companyData.id = d.companyid LEFT JOIN clientData ON clientData.id = clientid LEFT JOIN projectData ON projectData.id = clientprojectid
			LEFT JOIN ( SELECT t.projectid, GROUP_CONCAT(teamData.name SEPARATOR ',<br>') AS conteams, CONCAT(' ', GROUP_CONCAT(teamData.id SEPARATOR ' '), ' ') AS conteamsids FROM dynamicprojectsteams t
				LEFT JOIN teamData ON teamData.id = t.teamid GROUP BY t.projectid ) tbl2 ON tbl2.projectid = d.projectid
			LEFT JOIN ( SELECT p.dynamicID, SUM(IFNULL(TIMESTAMPDIFF(SECOND, p.start, p.end)/3600,TIMESTAMPDIFF(SECOND, p.start, UTC_TIMESTAMP)/3600)) AS currentHours
				FROM projectBookingData p GROUP BY dynamicID) tbl3 ON tbl3.dynamicID = d.projectid
			LEFT JOIN ( SELECT userID, dynamicID, p.id, p.start FROM projectBookingData p, logs WHERE p.timestampID = logs.indexIM AND `end` = '0000-00-00 00:00:00') tbl5
				ON tbl5.dynamicID = d.projectid
			LEFT JOIN dynamicprojectslogs dpl ON dpl.id = ( SELECT id FROM dynamicprojectslogs dpl2
				WHERE dpl2.projectid = d.projectid AND ((activity = 'VIEWED' AND userID = $userID) OR ((activity = 'CREATED' OR activity = 'EDITED') AND userID != $userID))
				ORDER BY id DESC LIMIT 1)
			WHERE d.isTemplate = 'FALSE' AND d.companyid IN (0, ".implode(', ', $available_companies).") $nonAdminQuery $query_filter
			ORDER BY workingUser DESC, projectpriority DESC, projectstatus, projectstart");
		$result = $conn->query($sql);
		//echo $sql;
        echo $conn->error;
        while($result && ($row = $result->fetch_assoc())){
            $x = $row['projectid'];
			$emps = $leader = $owner = '';
			$stmt_employees->execute();
			$res_emps = $stmt_employees->get_result();
			while($row_emps = $res_emps->fetch_assoc()){
				if($row_emps['position'] == 'owner'){
					$owner = $row_emps['userid'];
				} elseif($row_emps['position'] == 'leader') {
					$leader = $row_emps['userid'];
				} else {
					if(isset($userID_toName[$row_emps['userid']])) $emps .= $userID_toName[$row_emps['userid']].',<br>';
				}
			}
			if($filterings['taskview'] == 'default' && $leader && $leader != $userID) continue;
			if(!$owner){ //has to always exist
				$conn->query("INSERT INTO dynamicprojectsemployees (projectid, userid, position) VALUES('$x', 1, 'owner') ON DUPLICATE KEY UPDATE position = 'owner'");
				$owner = 1;
			}

            $rowStyle = (isset($row['activity']) && $row['activity'] != 'VIEWED') ? 'style="color:#1689e7; font-weight:bold;"' : '';
            echo "<tr $rowStyle>";
            echo '<td>';
            // echo $row['activity'];
            if($row['estimatedHours'] || $row['currentHours']) echo generate_progress_bar($row['currentHours'], $row['estimatedHours'], ['animate' => ($row['workingUser'] == $userID)]);
            echo '<i style="color:'.$row['projectcolor'].'" class="fa fa-circle"></i> '.mc_status('TASK');
			if($row['v2'] || strtotime($row['projectstart']) < strtotime('2018-08-07')) {
				echo asymmetric_encryption('TASK', $row['projectname'],$userID, $privateKey, $row['v2']);
			} else {
				echo asymmetric_seal('TASK', $row['projectname'], 'decrypt', $userID, $privateKey);
			}
            foreach(explode(',', $row['projecttags']) as $tag){
                if($tag) echo '<span class="badge">'.$tag.'</span> ';
            }
            echo '</div></td>';
            echo '<td><button type="button" class="btn btn-default view-modal-open" value="'.$x.'" >View</button></td>';
            echo '<td>'.$row['companyName'].'<br>'.$row['clientName'].'<br>'.$row['projectDataName'].'</td>';
            $A = substr(carryOverAdder_Hours($row['projectstart'], $timeToUTC),0,10);
            $B = $row['projectend'] == '0000-00-00' ? '' : substr($row['projectend'],0,10);
            echo '<td>'.$A.'</td>';
            echo '<td>'.$B.'</td>';
            echo '<td>';
            if($row['workingUser']){
				echo 'WORKING<br><small>'.$userID_toName[$row['workingUser']].'</small>';
			} elseif($row['projectstatus'] == 'COMPLETED') {
				echo 'FINISHED';
			} elseif(!$leader && !$row['projectpercentage']){
				echo 'UNASSIGNED';
			} elseif($leader == $userID) {
				echo 'ACTIVE';
			} elseif($row['projectstatus'] == 'ACTIVE') {
				echo 'TAKEN';
			} else {
				echo $row['projectstatus'];
			}
            if($row['projectstatus'] != 'COMPLETED'){ echo ' ('.$row['projectpercentage'].'%)'; }
            echo '<br><small style="color:transparent;">'.$x.'</small>';
            echo '</td>';
            echo '<td style="color:white;"><span class="badge" style="background-color:'.$priority_color[$row['projectpriority']].'" title="'.$lang['PRIORITY_TOSTRING'][$row['projectpriority']].'">'.$row['projectpriority'].'</span></td>';
            echo '<td>';
			//employees
            if(!$leader){
                echo '<u title="Besitzer und Verantwortlicher Mitarbeiter"><b>'. $userID_toName[$owner].'</b></u>,<br>';
            } else {
                echo '<b title="Besitzer">'. $userID_toName[$owner].'</b>,<br>';
                echo '<u title="Verantwortlicher Mitarbeiter">'.$userID_toName[$leader].'</u>,<br>';
            }
            echo $emps, $row['conteams'];
            echo '</td>';
            echo '<td>';
            if(($user_roles['isDynamicProjectsAdmin'] == 'TRUE' || $owner == $userID)){
                $checked = $row['needsreview'] == 'TRUE' ? 'checked' : '';
                echo '<input type="checkbox" onchange="reviewChange(event,\''.$x.'\')" '.$checked.'/>';
            }
            if(strpos($completed_tasks, $x) !== false) echo '<i class="fa fa-check" style="color:#00cf65" title="In aktueller Version vorhanden"></i>';
            echo '</td>';
            echo '<td>';
			if($row['workingUser'] == $userID) {
				$occupation = array('bookingID' => $row['workingID'], 'dynamicID' => $x, 'companyid' => $row['companyid'], 'clientid' => $row['clientid'],
				'projectid' => $row['clientprojectid'], 'percentage' => $row['projectpercentage'], 'noBooking' => ((time() - strtotime($row['workStart'])) < 60));
				echo '<button class="btn btn-default" type="button" value="" data-toggle="modal" data-target="#dynamic-booking-modal" name="pauseBtn"><i class="fa fa-pause"></i></button> ';
			} elseif(strtotime($A) < time() && $row['projectstatus'] == 'ACTIVE' && !$row['workingUser'] && !$hasActiveBooking){
				if(!$leader && $owner != $userID){
					echo "<button class='btn btn-default' type='button' title='Task starten' data-toggle='modal' data-valid='$x' data-target='#play-take'><i class='fa fa-play'></i></button> ";
				} else {
					echo "<button class='btn btn-default' type='submit' title='Task starten' name='play' value='$x'><i class='fa fa-play'></i></button> ";
				}
			}
			echo '<div class="dropdown" style="display:inline">
			<button class="btn btn-link dropdown-toggle" type="button" id="task-dropdown-'.$x.'" data-toggle="dropdown"> <i class="fa fa-ellipsis-v"></i> </button>
			<ul class="dropdown-menu dropdown-menu-right" aria-labelledby="task-dropdown-'.$x.'">';
            if(!$row['workingUser']){ //5acdfb19c0e84
                //echo " <button type='button' class='btn btn-default' title='Task Planen' data-toggle='modal' data-valid='$x' data-target='#task-plan'><i class='fa fa-clock-o'></i></button> ";
                if($user_roles['isDynamicProjectsAdmin'] == 'TRUE' || $owner == $userID) { //don't show edit tools for trainings
                    echo '<li><button type="submit" name="deleteProject" value="'.$x.'" class="btn btn-link"><i class="fa fa-trash-o"></i> Löschen</button></li>';
                    echo '<li><button type="button" name="editModal" value="'.$x.'" class="btn btn-link"><i class="fa fa-pencil"></i> Bearbeiten</button></li>';
                }
            }
			if(!$leader && $owner != $userID) echo "<li><button type='submit' class='btn btn-link' name='take_task' value='$x'><i class='fa fa-address-card'></i> Task übernehmen</button></li>";
			// always show the messages button (5ac63505c0ecd)
			echo "<li><button type='button' data-toggle='modal' data-valid='$x' data-target='#task-plan' class='btn btn-link'><i class='fa fa-clock-o'></i> Task Planen</button></li>";
			echo "<li><button type='button' data-toggle='modal' data-chatid='$x' data-target='#new-message-modal' class='btn btn-link'><i class='fa fa-commenting-o'></i> Nachricht Senden</button></li>";
			//5b45d4ae6b4cc
			echo "<li><button type='button' data-toggle='modal' data-chatid='$x' data-target='#add-note-modal' class='btn btn-link'><i class='fa fa-sticky-note-o'></i> Notiz anheften</button></li>";
			//5b45cfa8a0bd0
			echo "<li><button type='button' data-leader='$leader' data-status='{$row['projectstatus']}' data-toggle='modal' data-chatid='$x' data-target='#change-status-modal' class='btn btn-link'><i class='fa fa-random'></i> Status Ändern</button></li>";
			echo '</ul></div>';

			if($filterings['tasks'] == 'ACTIVE_PLANNED') echo '<label><input type="checkbox" name="icalID[]" value="'.$x.'" checked /> .ical</label>';
			echo '</td>';
			echo '</tr>';
        }
        ?>
        <!--training-->
        <?php if($userHasUnansweredSurveys): ?>
            <tr>
                <td><i style="color: #efefef" class="fa fa-circle"></i>Unbeantwortete Schulung<div></div></td>
                <td><a type="button" class="btn btn-default openSurvey">View</a></td>
                <td>-</td>
                <td><?=date("Y-m-d")?></td>
                <td></td>
                <td><i class="fa fa-times" style="color:red" title="Keine Routine"></i></td>
                <td>ACTIVE</td>
                <td style="color:white;"><span class="badge" style="background-color:<?=$priority_color[1]?>" title="<?=$lang['PRIORITY_TOSTRING'][1]?>">1</span></td>
                <td>-</td>
                <td>-</td>
                <td><a type="button" class="btn btn-default openSurvey"><i class="fa fa-question-circle"></i></a></td>
            </tr>
        <?php endif;
        if($userHasSurveys): ?>
            <tr>
                <td><i style="color: #efefef" class="fa fa-circle"></i>Bereits beantwortete Schulungen erneut ansehen<div></div></td>
                <td><a type="button" class="btn btn-default openDoneSurvey">View</a></td>
                <td>-</td>
                <td><?=date("Y-m-d")?></td>
                <td></td>
                <td>ACTIVE</td>
                <td style="color:white;"><span class="badge" style="background-color:<?=$priority_color[1]?>" title="<?=$lang['PRIORITY_TOSTRING'][1]?>">1</span></td>
                <td>-</td>
                <td>-</td>
                <td><a type="button" class="btn btn-default openDoneSurvey"><i class="fa fa-question-circle"></i></a></td>
            </tr>
        <?php endif; ?>
        <!--/training-->
    </tbody>
</table>
</form>
<div id="new-message-modal" class="modal fade" tabindex="-1" role="dialog">
	<form method="post">
		<div class="modal-dialog modal-content modal-md">
			<div class="modal-header h4">Neue Nachricht</div>
			<div class="modal-body">
				<div class="col-md-12">
					<label><?php echo mc_status(); ?> Nachricht</label>
					<textarea name="new_message_body" rows="8" style="resize:none" class="form-control"></textarea>
					<hr><h4>Empfänger</h4>
					Alle Teilnehmer können diese Nachricht lesen. Der Taskbestizer wird direkt benachrichtigt.
				</div>
				<br>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
				<button type="submit" class="btn btn-warning" name="send_new_message">Senden</button>
			</div>
		</div>
	</form>
</div>

<div id="add-note-modal" class="modal fade" tabindex="-1" role="dialog">
	<form method="post">
		<div class="modal-dialog modal-content modal-md">
			<div class="modal-header h4">Notiz hinzufügen</div>
			<div class="modal-body">
				<textarea name="add-note-text" rows="8" style="resize:none" class="form-control"></textarea>
				<br>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
				<button type="submit" class="btn btn-warning" name="add-note">Senden</button>
			</div>
		</div>
	</form>
</div>

<div id="change-status-modal" class="modal fade" tabindex="-1" role="dialog">
	<form method="post">
		<div class="modal-dialog modal-content modal-md">
			<div class="modal-header h4">Statuswechsel</div>
			<div class="modal-body">
				<label>Status</label>
				<select class="js-example-basic-single" name="change-status-status">
					<option value="ACTIVE">Assigned</option>
					<option value="REVIEW">Review</option>
					<option value="DEACTIVATED">Delay</option>
					<option value="COMPLETED">Finished</option>
				</select><br>
				<br>
				<div id="change-status-assigned-user">
					<label>Zugewiesener Benutzer</label>
		 			<select class="js-example-basic-single" name="change-status-assigned-user">
		 				<?php
						foreach($userID_toName as $id => $name){
							echo '<option value="',$id,'">',$name,'</option>';
						}
						?>
		 			</select>
				</div><br>
				<label>Notiz</label>
				<textarea name="change-status-note" rows="3" class="form-control"></textarea>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
				<button type="submit" class="btn btn-warning" name="change-status">Senden</button>
			</div>
		</div>
	</form>
</div>

<div id="editingModalDiv">
	<div id="play-take" class="modal fade" style="z-index:1500;">
		<div class="modal-dialog modal-content modal-sm">
			<div class="modal-header h4">Task Übernehmen</div>
			<div class="modal-body">Wollen Sie den Task dabei auch gleichzeitig als verantwortlichen Mitarbeiter übernehmen?</div>
			<div class="modal-footer">
				<form method="POST">
					<button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
					<button class="btn btn-default" type="submit" title="Task normal starten" name="play" value="">Nein</button>
					<button class="btn btn-warning" type="submit" title="Task als Verantwortlicher starten" name="play-take">Ja</button>
				</form>
			</div>
		</div>
	</div>
	<div id="task-plan" class="modal fade" style="z-index:1500;">
		<div class="modal-dialog modal-content modal-sm"><form method="POST">
			<div class="modal-header h4">Task Planen</div>
			<div class="modal-body"> Wollen Sie diesen Task auf ein anderes Datum verschieben? <br>
				Geplante Tasks werden automatisch übernommen und kehren mit dem eingestellten Datum automatisch wieder.<br><br>
				<input type="text" class="form-control datetimepicker" name="task-plan-date" value="" placeholder="z.B. 2018-12-24 10:30"/><br>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
				<button class="btn btn-warning" type="submit" title="Task Verschieben" name="task-plan">Verschieben</button>
			</div>
		</form>
	</div>
</div>

    <div class="modal fade" id="template-list-modal">
        <div class="modal-dialog modal-content modal-md">
            <div class="modal-header h4"><button type="button" class="close"><span>&times;</span></button><?php echo "Templates" ?></div>
            <div class="modal-body">
                <div class="col-md-12">
                    <label>Template auswählen</label>
                </div>
                <div class="col-md-9">
                    <select id="template-select" class="js-example-basic-single" >
						<?php
						while($templateResult && ($template = $templateResult->fetch_assoc())){
							echo '<option value="'.$template['projectid'].'" >'.asymmetric_encryption('TASK', $template['projectname'], $userID, $privateKey, $template['v2']).'</option>';
						}
                        ?>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-warning" id="templateActivate" ><?php echo $lang['APPLY']; ?></button>
            </div>
        </div>
    </div>
    <?php if($occupation): ?>
    <div class="modal fade" id="dynamic-booking-modal">
        <div class="modal-dialog modal-content modal-md">
            <form method="POST">
                <div class="modal-header h4"><button type="button" class="close"><span>&times;</span></button><?php echo $lang["DYNAMIC_PROJECTS_BOOKING_PROMPT"]; ?></div>
                <div class="modal-body">
					<? if(!$occupation['noBooking']): ?>
	                    <div class="row" id="occupation_booking_fields">
	                        <?php if(!$occupation['companyid'] && count($available_companies) > 2): ?>
	                            <div class="col-md-4">
	                                <label><?php echo $lang['COMPANY']; ?></label>
	                                <select class="js-example-basic-single" onchange="showClients(this.value, <?php echo intval($occupation['clientid']); ?>, 'book-dynamic-clientHint')">
	                                    <?php
	                                    $result = $conn->query("SELECT id, name FROM companyData WHERE id IN (".implode(', ', $available_companies).") ");
	                                    echo '<option value="0"> ... </option>';
	                                    while($row = $result->fetch_assoc()){
	                                        echo '<option value="'.$row['id'].'">'.$row['name'].'</option>';
	                                    }
	                                    ?>
	                                </select><br><br>
	                            </div>
	                        <?php endif; if(!$occupation['clientid']): ?>
	                            <div class="col-md-4">
	                                <label><?php echo $lang['CLIENT']; ?></label>
	                                <select id="book-dynamic-clientHint" class="js-example-basic-single" onchange="showProjects(this.value, '<?php echo intval($occupation['projectid']); ?>', 'book-dynamic-projectHint')" >
	                                    <option value="0"> ... </option>
	                                <?php
	                                $result = $conn->query("SELECT id, name FROM clientData WHERE isSupplier = 'FALSE' AND companyID IN (".implode(', ', $available_companies).")");
	                                if ($occupation['companyid']){
	                                    $result = $conn->query("SELECT id, name FROM clientData WHERE isSupplier = 'FALSE' AND companyID = ".$occupation['companyid']);
	                                }
	                                while ($row = $result->fetch_assoc()){
	                                    echo '<option value="'.$row['id'].'">'.$row['name'].'</option>';
	                                }
	                                ?>
	                                </select><br><br>
	                            </div>
	                        <?php endif; if(!$occupation['projectid']): ?>
	                            <div class="col-md-4">
	                                <label><?php echo $lang['PROJECT']; ?></label>
	                                <select id="book-dynamic-projectHint" class="js-example-basic-single" name="bookDynamicProjectID">
	                                    <?php if($occupation['clientid']){
	                                        $result = $conn->query("SELECT id, name FROM projectData WHERE clientID = ".$occupation['clientid']);
	                                        echo '<option value="0"> ... </option>';
	                                        while($row = $result->fetch_assoc()){
	                                            echo '<option value="'.$row['id'].'">'.$row['name'].'</option>';
	                                        }
	                                    } ?>
	                                </select><br><br>
	                            </div>
	                        <?php endif; ?>
	                        <div class="col-md-12">
	                            <textarea name="description" rows="4" class="form-control" style="max-width:100%; min-width:100%" placeholder="Info..."></textarea><br>
	                        </div>
	                    </div>
					<?php  else: echo 'Unter 60 Sekunden wird keine Buchung erstellt<hr>'; endif; //5b47049682f6e ?>
                    <div class="col-md-12">
                        <label><input type="checkbox" <? if($occupation['noBooking']) echo 'checked  onclick="return false;"'; ?> name="completeWithoutBooking" value="1" id="occupation_booking_fields_toggle">Task ohne Buchung abschließen</label><br><br>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <input id="bookRanger" step="25" type="range" min="1" max="100" value="<?php echo $occupation['percentage']; ?>" oninput="document.getElementById('bookCompleted').value = this.value;"><br>
                        </div>
                        <div class="col-md-3">
                            <div class="input-group">
                                <input type="number" class="form-control" name="bookCompleted" id="bookCompleted" min="0" max="100" value="<?php echo $occupation['percentage']; ?>" />
                                <span class="input-group-addon">%</span>
                            </div><br>
                        </div>
                        <div class="col-md-3">
                            <label><input type="checkbox" name="bookCompletedCheckbox" id="bookCompletedCheckbox"> Task erledigt</label><br>
                        </div>
                    </div>
                    <?php
                    $microtasks = $conn->query("SELECT microtaskid, title, ischecked FROM microtasks WHERE projectid = '".$occupation['dynamicID']."' WHERE ischecked = 'FALSE'");
                    if($microtasks && $microtasks->num_rows > 0):
                        ?>
                        <div class="row">
                            <div class="col-md-12">
                                <table id="microlist" class="dataTable table">
                                    <thead><tr>
                                        <th>Completed</th>
                                        <th>Micro Task</th>
                                    </tr></thead>
                                    <tbody>
                                        <?php
                                        while($mtask = $microtasks->fetch_assoc()){
                                            $mid = $mtask['microtaskid'];
                                            $title = $mtask['title'];
                                            echo '<tr>';
                                            echo '<td><input type="checkbox" name="mtask'.$mid.'" title="'.$title.'"></input></td>';
                                            echo '<td><label>'.$title.'</label></td>';
                                            echo '</tr>';
                                        }
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="modal-footer">
                    <div class="pull-left"><?php echo $occupation['dynamicID']; ?></div>
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning" name="createBooking" value="<?php echo $occupation['bookingID']; ?>"><?php echo $lang['SAVE']; ?></button>
                </div>
            </form>
        </div>
    </div>
<?php endif; //endif occupation ?>
</div>

<?php if(isset($setEdit) && !$setEdit && isset($_POST['description'])): //5ae9e4ee9e35f ?>
	<div id="hiddenSetEdit" style="display:none"><?php echo $_POST['description']; ?></div>
<?php endif; ?>

<script src="plugins/rtfConverter/rtf.js-master/samples/cptable.full.js"></script>
<script src="plugins/rtfConverter/rtf.js-master/samples/symboltable.js"></script>
<script src="plugins/rtfConverter/rtf.js-master/rtf.js"></script>
<script src='../plugins/tinymce/tinymce.min.js'></script>
<script>
$('button[name="deleteProject"]').click(function(){
    return confirm("Wollen Sie diesen Task wirklich löschen?");
});
$("#bookCompletedCheckbox").change(function(){
    $("#bookCompleted").attr('readonly', this.checked);
    $("#bookRanger").attr('disabled', this.checked);
    if(this.checked){
        $("#bookRanger").val(100);
        $("#bookCompleted").val(100);
    }
});
$('#occupation_booking_fields_toggle').change(function(){
    if(this.checked){
        $("#occupation_booking_fields").hide();
    } else {
        $("#occupation_booking_fields").show();
    }
});
$('#templateActivate').on('click', function(){
	var id = $('#template-select').val();
	$("#template-list-modal").modal('hide');
    if(existingModals.indexOf(id) == -1){
        appendModal(id);
    } else {
        $('#editingModal-'+id).modal('show');
    }
});
$('button[name="take_task"]').on('click', function(){
	return confirm("Wollen Sie diesen Task übernehmen?");
});
function formatState (state) {
    if (!state.id) { return state.text; }
    return $('<span><i class="fa fa-' + state.element.dataset.icon + '"></i> ' + state.text + '</span>');
};
function dynamicOnLoad(modID){
    if(typeof modID === 'undefined') modID = '';
    $(".select2-team-icons").select2({
        templateResult: formatState,
        templateSelection: formatState
    });
    $(".js-example-tokenizer").select2({
        tags: true,
        tokenSeparators: [',', ' ']
    });
    $('[data-toggle="tooltip"]').tooltip();
    tinymce.init({
        selector: '.projectDescriptionEditor',
        plugins: 'image code paste emoticons table',
        relative_urls: false,
        paste_data_images: true,
        menubar: false,
        statusbar: false,
        height: 300,
        browser_spellcheck: true,
        toolbar: 'undo redo | cut copy paste | styleselect | link image file media | code table | InsertMicroTask | emoticons',
        setup: function(editor){
            editor.addButton("InsertMicroTask",{
                tooltip: "Insert MicroTask",
                icon: "template",
                onclick: function(){
                    var html = "<p>[<label style='color: red;font-weight:bold'>MicroTaskName</label>] { </p><p> MicrotaskDescription here </p><p> }</p>";
                    editor.insertContent(html);
                },
            });
        },

        // enable title field in the Image dialog
        image_title: true,
        // enable automatic uploads of images represented by blob or data URIs
        automatic_uploads: true,
        // URL of our upload handler (for more details check: https://www.tinymce.com/docs/configure/file-image-upload/#images_upload_url)
        // images_upload_url: 'postAcceptor.php',
        // here we add custom filepicker only to Image dialog
        file_picker_types: 'file image media',
        // init_instance_callback: function (editor) { //5b28df25a2b93
        //     editor.on('paste', function (e) {
        //         //console.log(e.clipboardData.types.includes("text/rtf"));
        //         if(e.clipboardData.types.includes("text/rtf")){
        //             var clipboardData, pastedData;
        //         // Stop data actually being pasted into div
        //         e.preventDefault();
        //         // Get pasted data via clipboard API
        //         clipboardData = e.clipboardData || window.clipboardData;
        //         pastedData = clipboardData.getData('text/rtf');
        //         var stringToBinaryArray = function(txt) {
        //             var buffer = new ArrayBuffer(txt.length);
        //             var bufferView = new Uint8Array(buffer);
        //             for (var i = 0; i < txt.length; i++) {
        //                 bufferView[i] = txt.charCodeAt(i);
        //             }
        //             return buffer;
        //         }
        //         var settings = {};
        //         var doc = new RTFJS.Document(stringToBinaryArray(pastedData), settings);
        //         var part = doc.render();
        //         //console.log(part);
        //         for(i=0;i<part.length;i++){
        //             part[i][0].innerHTML = part[i][0].innerHTML.replace("[Unsupported image format]","");
        //             this.execCommand("mceInsertContent",false,part[i][0].innerHTML);
        //         }
        //         }
        //     });
        // },
        // and here's our custom image picker
        file_picker_callback: function(cb, value, meta) {
            var input = document.createElement('input');
            input.setAttribute('type', 'file');
            input.setAttribute('accept', '*');
            input.onchange = function() {
                var file = this.files[0];
                var reader = new FileReader();
                reader.onload = function () {
                    // Note: Now we need to register the blob in TinyMCEs image blob
                    // registry. In the next release this part hopefully won't be
                    // necessary, as we are looking to handle it internally.
                    var id = 'blobid' + (new Date()).getTime();
                    var blobCache =  tinymce.activeEditor.editorUpload.blobCache;
                    //console.log(reader.result.split(";")[0].split(":")[1]) //mime type
                    var base64 = reader.result.split(',')[1];
                    alert("Base64 size: "+base64.length+" chars")
                    var blobInfo = blobCache.create(id, file, base64);
                    blobCache.add(blobInfo);
                    // call the callback and populate the Title field with the file name
                    cb(blobInfo.blobUri(), { title: file.name, text:file.name,alt:file.name,source:"images/Question_Circle.jpg",poster:"images/Question_Circle.jpg" });
                };
                reader.readAsDataURL(file);
            };
            input.click();
        }
    });
} //end dynamicOnLoad()
function appendModal(index){
	$.ajax({
		url:'ajaxQuery/AJAX_dynamicEditModal.php',
		data:{projectid: index,isDPAdmin: "<?php echo $user_roles['isDynamicProjectsAdmin']; ?>"},
		type: 'post',
		success : function(resp){
			$("#editingModalDiv").append(resp);
			existingModals.push(index);
			onPageLoad();
			dynamicOnLoad(index);
		},
		error : function(resp){alert(resp);},
		complete: function(resp){
			if(index){
				$('#editingModal-'+index).modal('show');
			} else {
				<?php if(isset($setEdit) && !$setEdit && isset($_POST['description'])): //5ae9e4ee9e35f ?>
				setTimeout(function(){
					$("#editingModal-").modal("show");
					tinyMCE.activeEditor.setContent($('#hiddenSetEdit').html());
					$("#editingModal-").find('input[name="name"]').val("<?php echo $_POST['name']; ?>");
				}, 1500);
				<?php endif; ?>
			}
		}
	});
}
var existingModals = new Array();
appendModal('');

var existingModals_info = new Array();
function openViewModal(index /*projectid*/){
  if(existingModals_info.indexOf(index) == -1){
      $.ajax({
      url:'ajaxQuery/AJAX_dynamicInfo.php',
      data:{projectid: index},
      type: 'get',
      success : function(resp){
        $("#editingModalDiv").append(resp);
        existingModals_info.push(index);
        onPageLoad();
        dynamicOnLoad(index);
      },
      error : function(resp){},
      complete: function(resp){
          if(index){
              $('#infoModal-'+index).modal('show');
          }
      }
     });
  } else {
    $('#infoModal-'+index).modal('show');
  }
};
<?php if(isset($_POST['open'])): // 5b47043bac66a ?>
$(document).ready(function(){
   setTimeout(function(){ openViewModal("<?php echo $_POST['open']?>")},500);
});
<?php endif; ?>
$('.view-modal-open').click(function(){
    openViewModal($(this).val())
     $(this).parent().parent().removeAttr('style');
});
function showClients(company, client, place){
    if(company != ""){
        $.ajax({
            url:'ajaxQuery/AJAX_getClient.php',
            data:{companyID:company, clientID:client},
            type: 'get',
            success : function(resp){
                $("#"+place).html(resp);
            },
            error : function(resp){}
        });
    }
}
function showProjects(client, project, place){
    if(client != ""){
        $.ajax({
            url:'ajaxQuery/AJAX_getProjects.php',
            data:{clientID:client, projectID:project},
            type: 'get',
            success : function(resp){
                $("#"+place).html(resp);
            },
            error : function(resp){}
        });
    }
}

$('.table').on('click', 'button[name=editModal]', function(){
    var index = $(this).val();
  if(existingModals.indexOf(index) == -1){
      appendModal(index);
  } else {
    $('#editingModal-'+index).modal('show');
  }
});
function reviewChange(event,id){
    projectid = id;
    needsReview = event.target.checked ? 'TRUE' : 'FALSE';
    $.post("ajaxQuery/AJAX_db_utility.php",{
        needsReview: needsReview,
        function: "changeReview",
        projectid: projectid
    }, function(data){
        //console.log(data);
    });
}
$(".openDoneSurvey").click(function(){ // answer already done surveys/trainings again
    $.ajax({
        url:'ajaxQuery/ajax_dsgvo_training_user_generate.php',
        data:{done:true},
        type: 'get',
        success : function(resp){
            $("#currentSurveyModal").html(resp);
        },
        error : function(resp){console.error(resp)},
        complete: function(resp){
            $("#currentSurveyModal .survey-modal").modal("show");
        }
   });
});
$(document).ready(function() {
	$('#new-message-modal').on('show.bs.modal', function (event) {
	  var button = $(event.relatedTarget);
	  $(this).find('button[name=send_new_message]').val(button.data('chatid'));
	});
	$('#play-take').on('show.bs.modal', function (event) {
	  var index = $(event.relatedTarget).data('valid');
	  $(this).find("button[name='play']").val(index);
	  $(this).find('button[name="play-take"]').val(index);
	});
	$('#task-plan').on('show.bs.modal', function (event) {
	  var index = $(event.relatedTarget).data('valid');
	  $(this).find('button[name=task-plan]').val(index);
	});
	$('#change-status-modal').on('show.bs.modal', function (event) {
	  var button = $(event.relatedTarget);
	  $(this).find('button[name=change-status]').val(button.data('chatid'));
	  $(this).find('select[name=change-status-assigned-user]').val(button.data('leader')).trigger('change');
	  $(this).find('select[name=change-status-status]').val(button.data('status')).trigger('change');
	});
	$('#add-note-modal').on('show.bs.modal', function (event) {
	  var index = $(event.relatedTarget).data('chatid');
	  $(this).find('button[name=add-note]').val(index);
	});
	// setInterval(function(){
	// 	if($('#progress-bar-green').width() > 0){
	// 		$('#progress-bar-green').width($('#progress-bar-green').width()-1);
	// 		$('#progress-bar-yellow').width($('#progress-bar-yellow').width()+1);
	// 	} elseif($('#progress-bar-red').width() < 250) {
	// 		$('#progress-bar-red').width($('#progress-bar-red').width()+1);
	// 	}
	// }, 1000);
    dynamicOnLoad();
    $('.table').DataTable({
        ordering:false,
        language: {
            <?php echo $lang['DATATABLES_LANG_OPTIONS']; ?>
        },
        responsive: true,
        autoWidth: false,
        fixedHeader: {
            header: true,
            headerOffset: 150,
            zTop: 1
        },
        paging: true
    });
    setTimeout(function(){
        window.dispatchEvent(new Event('resize'));
        $('.table').trigger('column-reorder.dt');
    }, 500);
});
</script>
</div>
<?php include dirname(__DIR__) . '/footer.php'; ?>
