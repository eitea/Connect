<?php
require dirname(__DIR__) . '/header.php'; enableToWorkflow($userID);
include dirname(__DIR__) . "/misc/helpcenter.php";
if($_SERVER['REQUEST_METHOD'] == 'POST'){
    if(isset($_POST['deleteWorkflow'])){
        $val = intval($_POST['deleteWorkflow']);
        $conn->query("DELETE FROM emailprojects WHERE id = $val");
        if ($conn->error) {
            echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>' . $conn->error . '</div>';
        } else {
            echo '<div class="alert alert-success"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$lang['OK_DELETE'].'</div>';
        }
    } elseif(!empty($_POST['deleteRule'])){
		$val = intval($_POST['deleteRule']);
		$conn->query("DELETE FROM workflowRules WHERE id = $val");
		if ($conn->error) {
            echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>' . $conn->error . '</div>';
        } else {
            echo '<div class="alert alert-success"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$lang['OK_DELETE'].'</div>';
        }
	} elseif (isset($_POST['addAccount']) && !empty($_POST['server']) && !empty($_POST['service']) && !empty($_POST['port']) && !empty($_POST['username']) && !empty($_POST['password'])) {
        $server = test_input($_POST['server']);
        $port = test_input($_POST['port']);
        $security = test_input($_POST['security']) == "none" ? null : test_input($_POST['security']);
        $service = test_input($_POST['service']);
        $username = test_input($_POST['username']);
        $password = test_input($_POST['password']);
        $logging = isset($_POST['logging']) ? 'TRUE' : 'FALSE';
		if(!empty($_POST['addAccount'])){
			$val = intval($_POST['addAccount']);
			$conn->query("UPDATE emailprojects SET server='$server',port='$port',service='$service',
				smtpSecure='$security', username='$username',password='$password',logEnabled='$logging' WHERE id = $val");
		} else {
			$conn->query("INSERT INTO emailprojects(server,port,service,smtpSecure,username,password,logEnabled)
			VALUES('$server','$port','$service','$security','$username','$password','$logging') ");
		}
        if ($conn->error) {
            echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>' . $conn->error . '</div>';
        } else {
            echo '<div class="alert alert-success"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$lang['OK_ADD'].'</div>';
        }
    } elseif(!empty($_POST['add_rule'])){
		$workflowID = intval($_POST['add_rule']);
		$subject = test_input($_POST['rule_subject']);
		$senders = test_input($_POST['rule_senders']);
		$receiver = test_input($_POST['rule_receiver']);
		$response = trim(test_input($_POST['rule_autoResponse'])); //5b20ad39615f9
		if(!empty($_POST['rule_template'])){
			$templateID = test_input($_POST['rule_template']);
		} elseif(!empty($_POST['name']) && !empty($_POST['owner']) && !empty($_POST['employees'])) {
			$templateID = uniqid();
			$name = asymmetric_encryption('TASK', test_input($_POST["name"]), $userID, $privateKey);
			$v2Key = ($name == test_input($_POST["name"])) ? '' : $publicKey;
			$company = $_POST["filterCompany"] ?? $available_companies[1];
			$client = isset($_POST['filterClient']) ? intval($_POST['filterClient']) : '';
			$project = isset($_POST['filterProject']) ? intval($_POST['filterProject']) : '';
			$color = $_POST["color"] ? test_input($_POST['color']) : '#FFFFFF';
			$start = $_POST["start"];
			$end = '0000-00-00'; //temp fix for invalid end value (probs NULL)
			$status = $_POST["status"];
			$priority = intval($_POST["priority"]); //1-5
			$owner = $_POST['owner'] ? intval($_POST["owner"]) : $userID;
			$leader = isset($_POST['leader']) ? intval($_POST['leader']) : '';
			$percentage = intval($_POST['completed']);
			$estimate = test_input($_POST['estimatedHours']);
			if($isDynamicProjectsAdmin == 'TRUE'){
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
			$conn->query("INSERT INTO dynamicprojects(projectid, projectname, projectdescription, companyid, clientid, clientprojectid, projectcolor, projectstart, projectend, projectstatus,
			projectpriority, projectparent, projectowner, projectleader, projectpercentage, estimatedHours, level, projecttags, isTemplate, v2) VALUES (
			'$templateID', '$name', '', $company, $client, $project, '$color', '$start', '$end', '$status', $priority, '$parent', $owner, $leader, $percentage, '$estimate',
			$skill, '$tags', 'TRUE', '$v2Key' )");
			if(!$conn->error){
				//EMPLOYEES
				$stmt = $conn->prepare("INSERT INTO dynamicprojectsemployees (projectid, userid, position) VALUES ('$templateID', ?, ?)"); echo $conn->error;
				$stmt->bind_param("is", $employee, $position);
				$position = 'normal';
				foreach ($_POST["employees"] as $employee) {
					$emp_array = explode(";", $employee);
					if ($emp_array[0] == "user") {
						$employee = intval($emp_array[1]);
						$stmt->execute();
					} else {
						$team = intval($emp_array[1]);
						$conn->query("INSERT INTO dynamicprojectsteams (projectid, teamid) VALUES ('$templateID',$team)");
					}
				}
				if(isset($_POST['optionalemployees']) && !empty($_POST['optionalemployees'])){
					$position = 'optional';
					foreach ($_POST['optionalemployees'] as $optional_employee) {
						$employee = intval($optional_employee);
						$stmt->execute();
					}
				}
			} else {
				$templateID = false;
				showError($conn->error);
			}
		}
		if($templateID){
			if(empty($_POST['edit_rule'])){
				$conn->query("INSERT INTO workflowRules(workflowID, templateID, subject, fromAddress, toAddress, autoResponse)
					VALUES ($workflowID, '$templateID', '$subject', '$senders', '$receiver', '$response')");
			} else {
				$val = intval($_POST['edit_rule']);
				$conn->query("UPDATE workflowRules SET templateID = '$templateID', subject = '$subject', fromAddress = '$senders', toAddress = '$receiver',
					autoResponse = '$response' WHERE id = $val");
			}
			if($conn->error){
				showError($conn->error);
			} else {
				showSuccess($lang['OK_SAVE']);
			}
		} else {
			showError("Kein Template");
		}
	} elseif(!empty($_POST['positions'])){
		//$positions = array();
		foreach($_POST['positions'] as $val){
			$arr = explode('_', $val);
			$conn->query("UPDATE workflowRules SET position = '".$arr[1]."' WHERE id =".$arr[0]);
		}
	}
}
?>
<div class="page-header"><h3>Workflow
	<div class="page-header-button-group">
		<a data-toggle="modal" href="#new-account" class="btn btn-default" title="New..."><i class="fa fa-plus"></i></a>
		<button type="button" class="btn btn-default" data-toggle="modal" data-target="#taskTemplateEditor-modal">Task Templates</button>
	</div>
</h3></div>
<?php include __DIR__.'/taskTemplateEditor.php'; ?>

<div class="row bold">
	<div class="col-xs-2">Server</div>
	<div class="col-xs-1">Port</div>
	<div class="col-xs-1">Service</div>
	<div class="col-xs-1">Security</div>
	<div class="col-xs-2">Username</div>
	<div class="col-xs-1">Log</div>
	<div class="col-xs-4"></div>
</div>
<?php
$stmt = $conn->prepare("SELECT id, subject, fromAddress, toAddress, projectname, v2, templateID, autoResponse
	FROM workflowRules w LEFT JOIN dynamicprojects d ON d.projectid = w.templateID WHERE workflowID = ? ORDER BY w.position ASC");
echo $conn->error;
$stmt->bind_param('i', $id);
$result = $conn->query("SELECT id, server, port, service, smtpSecure, username, password, logEnabled FROM emailprojects");
while ($row = $result->fetch_assoc()) {
	echo '<div class="row" style="background-color:#eaeaea">';
	echo '<div class="col-xs-2">' . $row['server'] . '</div>';
	echo '<div class="col-xs-1">' . $row['port'] . '</div>';
	echo '<div class="col-xs-1">' . strtoupper($row['service']) . '</div>';
	echo '<div class="col-xs-1">' . ($row['smtpSecure']=='null' ? 'none' : $row['smtpSecure']). '</div>';
	echo '<div class="col-xs-2">' . $row['username'] . '</div>';
	echo '<div class="col-xs-1">' . ($row['logEnabled'] == 'TRUE' ? '<i style="color:green" class="fa fa-check"></i>' : '<i style="color:red" class="fa fa-times"></i>') .'</div>';
	echo '<div class="col-xs-4"><form method="POST">';
	echo '<a data-toggle="modal" href="#new-account" class="btn btn-default" title="Bearbeiten" data-valid="'.$row['id'].'" data-server="'.$row['server'].'"
	data-port="'.$row['port'].'" data-security="'.$row['smtpSecure'].'" data-service="'.$row['service'].'" data-user="'.$row['username'].'"
	data-pass="'.$row['password'].'" data-log="'.$row['logEnabled'].'"><i class="fa fa-pencil"></i></a> ';
	echo '<button type="submit" name="deleteWorkflow" value="'.$row['id'].'" title="Löschen" class="btn btn-default" ><i class="fa fa-trash-o"></i></button> ';
	echo '</form></div></div>';
	echo '<div class="row"><div class="col-xs-11 col-xs-offset-1 h4"><form method="POST">Regelsets
	<div class="page-header-button-group"><a data-toggle="modal" href="#add-rule" class="btn btn-default btn-sm" data-valid="'.$row['id'].'"><i class="fa fa-plus"></i></a>
	<button type="submit" name="savePositions" value="'.$row['id'].'" class="btn btn-default btn-sm"><i class="fa fa-floppy-o"></i></button></div></div></div>';
	$id = $row['id'];
	$stmt->execute();
	$res = $stmt->get_result();
	if($res->num_rows < 1){
		echo '<div class="row"><div class="col-xs-11 col-xs-offset-1">Es wurden noch keine Regeln definiert. Es werden keine E-Mails abgeholt oder verarbeitet.</div></div>';
	} else {
		echo '<div style="margin-left:150px">';
		echo '<div class="row bold">
			<div class="col-xs-2">Betreff</div>
			<div class="col-xs-3">Adressen</div>
			<div class="col-xs-2">Response</div>
			<div class="col-xs-2">Template</div>
			<div class="col-xs-1">Position</div>
			<div class="col-xs-1"></div>
		</div>';
		$options = '';

		$j = 1;
		while($rule = $res->fetch_assoc()){
			echo '<div class="row" style="border-top:1px solid lightgrey">';
			echo '<div class="col-xs-2">' . $rule['subject'].'</div>';
			echo '<div class="col-xs-3">';
			if($rule['fromAddress']) echo '<b>Absender: </b>', $rule['fromAddress'];
			if($rule['toAddress']) echo '<b>Empfänger: </b>', $rule['toAddress'];
			echo '</div>';
			echo '<div class="col-xs-2">';
			if($rule['autoResponse']) echo substr($rule['autoResponse'], 0, 20), '...'; //5b20ad39615f9
			echo '</div>';
			echo '<div class="col-xs-2">' . asymmetric_encryption('TASK', $rule['projectname'], $userID, $privateKey, $rule['v2']) . '</div>';
			echo '<div class="col-xs-1"><select name="positions[]" class="form-control">';
			for($i=1;$i<=$res->num_rows;$i++){
				$selected = ($j == $i) ? 'selected' : '';
				echo "<option $selected value='{$rule['id']}_$i'>$i</option>";
			}
			echo '</select></div>';
			echo '<div class="col-xs-2"><button type="submit" name="deleteRule" value="'.$rule['id'].'" class="btn btn-default"><i class="fa fa-trash-o"></i></button>
			<a data-toggle="modal" data-resp="'.$rule['autoResponse'].'" data-subject="'.$rule['subject'].'" data-from="'.$rule['fromAddress'].'" data-to="'.$rule['toAddress'].'"
			data-valid="'.$row['id'].'" data-workid="'.$rule['id'].'" data-templateid="'.$rule['templateID'].'" class="btn btn-default" href="#add-rule"><i class="fa fa-pencil"></i></a></div>';
			echo '</div>';
			$j++;
		}
		echo '</div>';
	}

	//TODO: templates editieren
	echo '</form>';
}
?>

<div class="modal fade" id="new-account">
	<div class="modal-dialog modal-content modal-md">
		<form method="POST" autocomplete="off">
			<div class="modal-header h4"><?php echo $lang['ADD']; ?></div>
			<div class="modal-body">
				<div class="row">
					<div class="col-md-10"><label>Server</label>
						<input type="text" class="form-control" name="server" id="server"/>
					</div>
					<div class="col-md-2"><label>Log</label>
						<input type="checkbox" class="form-control" name="logging" id="logging" />
					</div>
				</div>
				<div class="row">
					<div class="col-md-4"><label>Port</label>
						<input type="number" class="form-control" name="port" id="port"/>
					</div>
					<div class="col-md-4"><label>Service</label>
						<select class="form-control" name="service" id="service">
							<option value="imap">IMAP</option>
							<option value="pop3">POP3</option>
						</select>
					</div>
					<div class="col-md-4"><label>Security</label>
						<select class="form-control" name="security" id="security">
							<option value="none">none</option>
							<option value="tls">tls</option>
							<option value="ssl">ssl</option>
						</select>
					</div>
				</div>
				<div class="row">
					<div class="col-md-6"><label>Username</label>
						<input type="email" autocomplete="new-email" class="form-control" name="username" id="username"/>
					</div>
					<div class="col-md-6"><label>Password</label>
						<input type="password" autocomplete="new-password" class="form-control password" name="password" id="password"/>
					</div>
				</div>
			</div>
			<div class="modal-footer">
				<button style="float:left" type="button" class="btn btn-default emailChecker">Check</button>
				<button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
				<button type="submit" class="btn btn-warning" name="addAccount" id="addAccount"><?php echo $lang['ADD']; ?></button>
			</div>
		</form>
	</div>
</div>

<div class="modal fade" id="add-rule">
    <div class="modal-dialog modal-content modal-md">
		<form method="POST">
            <div class="modal-header h4"><?php echo $lang['ADD']; ?></div>
            <div class="modal-body">
				<ul class="nav nav-tabs">
                    <li class="active"><a data-toggle="tab" href="#add-rule-basic">Task und Filter</a></li>
                    <li><a data-toggle="tab" href="#add-rule-new-template">Neues Template</a></li>
					<li><a data-toggle="tab" href="#add-rule-response">Auto Response </a></li>
				</ul>
				<div class="tab-content">
                    <div id="add-rule-basic" class="tab-pane fade in active"><br>
						<div class="row">
							<div class="col-sm-12"> <label>Betreff</label>
								<input type="text" name="rule_subject" id="rule_subject" class="form-control" placeholder="Genaues Suchwort"/>
								<small>Ist dieser Begriff im Betreff enthalten, wird das Suchwort aus dem Betreff herausgeschnitten
									 und der Rest des Betreffs wird zum Titel des Tasks. Achten Sie auf Groß- und Kleinschreibung.</small><br>
							</div>
						</div>
						<div class="row">
							<div class="col-sm-12"> <label>Absender</label>
								<input type="text" name="rule_senders" id="rule_senders" class="form-control" maxlength="100">
								<small>Es können mehrere Absender angegeben werden. Trennung durch Leerzeichen.</small>
							</div>
						</div>
						<div class="row">
							<div class="col-sm-12"> <label>Emfpänger</label>
								<input type="email" name="rule_receiver" id="rule_receiver" class="form-control" maxlength="100"/>
							</div>
						</div>
						<div class="row">
							<div class="col-md-12"> <label>Task Template</label>
								<select class="js-example-basic-single" name="rule_template" id="rule_template">
									<option value=""> Kein Template </option>
									<?php
									$result = $conn->query("SELECT projectid, projectname, v2 FROM dynamicprojects
										WHERE isTemplate = 'TRUE' AND companyid IN (".implode(', ', $available_companies).")");
										echo $conn->error;
									while($row = $result->fetch_assoc()){
										echo '<option value="'.$row['projectid'].'">'.asymmetric_encryption('TASK', $row['projectname'], $userID, $privateKey, $row['v2']).'</option>';
									}
									?>
								</select>
								<small>Wählen Sie ein bereits bestehendes Task Template aus, oder erstellen Sie ein neues Template im nächsten Tab.
								Betreff, Absender und Empfänger werden UND verknüpft.</small>
							</div>
						</div>
					</div>
					<div id="add-rule-new-template" class="tab-pane fade"><br>
						<?php
						$x = false;
						$isTempalte = true;
						include dirname(__DIR__).'/misc/dynamicproject_gen.php';
						?>
					</div>
					<div id="add-rule-response" class="tab-pane fade"><br>
						<div class="col-md-12">
							<label>Automatische Antwort</label><br>
							Beim erstellen eines Tasks kann automatisch eine Antwort zurück gesendet werden
							<textarea id="rule_autoResponse" name="rule_autoResponse" rows="8" class="form-control">
								Herzlichen Dank für Ihre Nachricht!

Ihr Anliegen werden wir so schnell wie möglich beantworten.

Im Betreff finden sie auch gleich die zugewiesene Ticket Nummer.


%Mandatennamen%
Höchste Qualität. Garantiert.
---------------------------
---------------------------

Thank you very much for your message!

We will answer your request as soon as possible.

In the subject you will also find the assigned ticket number.


%Mandatennamen%
Highest Quality. Guaranteed.
---------------------------
---------------------------
							</textarea>
						</div>
					</div>
				</div>
            </div><!-- /modal-body -->
            <div class="modal-footer">
				<input type="hidden" id="edit_rule" name="edit_rule" value="">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
				<button type="submit" class="btn btn-warning" name="add_rule" id="add_rule"><?php echo $lang['ADD']; ?></button>
            </div>
		</form>
    </div>
</div>

<script type="text/javascript">
$('.emailChecker').click(function(){
	checkEmail();
});
$('#new-account').on('show.bs.modal', function (event) {
	var button = $(event.relatedTarget);
	$('#addAccount').val(button.data('valid'));
	$('#server').val(button.data('server'));
	$("#port").val(button.data('port'));
	$("#security").val(button.data('security')).trigger('change');
	$("#service").val(button.data('service')).trigger('change');
	$("#username").val(button.data('user'));
	$("#password").val(button.data('pass'));
});
$('#add-rule').on('show.bs.modal', function (event) {
	var button = $(event.relatedTarget);
	$('#add_rule').val(button.data('valid'));
	$('#edit_rule').val(button.data('workid'));
	$('#rule_template').val(button.data('templateid')).trigger('change');
	$('#rule_receiver').val(button.data('to'));
	$('#rule_senders').val(button.data('from'));
	$('#rule_subject').val(button.data('subject'));
	$('#rule_autoResponse').val(button.data('resp')); //5b20ad39615f9
});
function checkEmail(){
	$.post("ajaxQuery/AJAX_checkEmailAvailability.php",{
		server: $("#server").val(),
		port: $("#port").val(),
		security: $("#security").val(),
		service: $("#service").val(),
		username: $("#username").val(),
		password: $("#password").val()
	}, function(data){
		alert(data);
	});
}

function formatState (state) {
    if (!state.id) { return state.text; }
    var $state = $(
        '<span><i class="fa fa-' + state.element.dataset.icon + '"></i> ' + state.text + '</span>'
    );
    return $state;
};
$(".select2-team-icons").select2({
	templateResult: formatState,
	templateSelection: formatState
});
$(".js-example-tokenizer").select2({
	tags: true,
	tokenSeparators: [',', ' ']
});
</script>
<?php include dirname(__DIR__) . '/footer.php';?>
