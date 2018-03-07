<?php
require dirname(__DIR__) . '/header.php';
include dirname(__DIR__) . "/misc/helpcenter.php";
if($_SERVER['REQUEST_METHOD'] == 'POST'){
    if(isset($_POST['delete'])){
        $id = ($_POST['delete']);
        preg_match_all('!\d+!', $_POST['delete'], $id);
        $conn->query("DELETE FROM emailprojects WHERE id = ".$id[0][0]);
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
        $conn->query("INSERT INTO emailprojects(server,port,service,smtpSecure,username,password,logEnabled) VALUES('$server','$port','$service','$security','$username','$password','$logging') ");
        if ($conn->error) {
            echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>' . $conn->error . '</div>';
        } else {
            echo '<div class="alert alert-success"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$lang['OK_ADD'].'</div>';
        }
    } elseif(isset($_POST['editAccount']) && !empty($_POST['edit_server'])&& !empty($_POST['edit_service'])&& !empty($_POST['edit_port'])&& !empty($_POST['edit_username'])&& !empty($_POST['edit_password'])){
        $server = test_input($_POST['edit_server']);
        $port = test_input($_POST['edit_port']);
        $security = test_input($_POST['edit_security']) == "none" ? null : test_input($_POST['edit_security']);
        $service = test_input($_POST['edit_service']);
        $username = test_input($_POST['edit_username']);
        $password = test_input($_POST['edit_password']);
        $logging = isset($_POST['edit_logging']) ? 'TRUE' : 'FALSE';
        $id = $_POST['edit_id'];
        preg_match_all('!\d+!', $id, $id);
        $conn->query("UPDATE emailprojects SET server='$server',port='$port',service='$service',smtpSecure='$security',username='$username',password='$password',logEnabled='$logging' WHERE id = ".$id[0][0]);
        if ($conn->error) {
            echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>' . $conn->error . '</div>';
        } else {
            echo '<div class="alert alert-success"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$lang['OK_SAVE'].'</div>';
        }
    }
}
?>
<div class="page-header"><h3><?php echo $lang['PROJECT_OPTIONS']; ?>
  <div class="page-header-button-group">
    <button type="button" data-toggle="modal" data-target="#new-account" class="btn btn-default" title="New..."><i class="fa fa-plus"></i></button>
  </div>
</h3></div>

<table class="table">
  <thead><tr>
    <th>Server</th>
    <th>Service</th>
    <th>Security</th>
    <th>Username</th>
    <th><?php echo $lang['RULES'] ?></th>
    <th><button type="button" onclick="retrieveEmails()" class="btn btn-default" title="Check Emails" ><i class="fa fa-refresh"></i></button></th>
  </tr></thead>
  <tbody>
  <?php
    $result = $conn->query("SELECT id, server, service, smtpSecure, username FROM emailprojects");
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo '<td>' . $row['server'] . '</td>';
        echo '<td>' . strtoupper($row['service']) . '</td>';
        echo '<td>' . ($row['smtpSecure']=='null' ? 'none' : $row['smtpSecure']). '</td>';
        echo '<td>' . $row['username'] . '</td>';
        echo '<td><button type="button" data-toggle="modal" data-target="#edit-rules" onClick="editRules(event,'.$row['id'].')" class="btn btn-default" title="'.$lang['RULES'].'"><i class="fa fa-cog"></i></button></td>';
        echo '<td><form method="POST">';
        echo '<button type="button" data-toggle="modal" data-target="#edit-account" onClick="editAccount(event,'.$row['id'].')" class="btn btn-default" title="Bearbeiten"><i class="fa fa-pencil"></i></button> ';
        echo '<button type="submit" name="delete" value="' . $row['id'] . '" title="Löschen" class="btn btn-default" ><i class="fa fa-trash-o"></i></button> ';
        echo '</form></td>';
        echo '</tr>';
    }
?>
  </tbody>
</table>

<form method="POST" autocomplete="off">
    <div class="modal fade" id="new-account">
        <div class="modal-dialog modal-content modal-md">
            <div class="modal-header h4"><?php echo $lang['ADD']; ?></div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-10">
                        <label>Server</label>
                        <input type="text" class="form-control" name="server" id="server"/>
                    </div>
                    <div class="col-md-2">
                        <label>Log</label>
                        <input type="checkbox" class="form-control" name="logging" id="logging" />
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4">
                        <label>Port</label>
                        <input type="number" class="form-control" name="port" id="port"/>
                    </div>
                    <div class="col-md-4">
                        <label>Service</label>
                        <select class="form-control" name="service" id="service">
                            <option value="imap">IMAP</option>
                            <option value="pop3">POP3</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label>Security</label>
                        <select class="form-control" name="security" id="security">
                            <option value="none">none</option>
                            <option value="tls">tls</option>
                            <option value="ssl">ssl</option>
                        </select>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <label>Username</label>
                        <input type="email" autocomplete="new-email" class="form-control" name="username" id="username"/>
                    </div>
                    <div class="col-md-6">
                        <label>Password</label>
                        <input type="text" autocomplete="new-password" class="form-control password" name="password" id="password"/>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button style="float:left" type="button" class="btn btn-default" onblur="this.setAttribute('style','float:left');" onClick="checkEmail(this)">Check</button>
                <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-warning" name="addAccount"><?php echo $lang['ADD']; ?></button>
            </div>
        </div>
    </div>
</form>
<form method="POST">
    <div class="modal fade" id="edit-account">
        <div class="modal-dialog modal-content modal-md">
            <div class="modal-header h4"><?php echo $lang['ADD']; ?></div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-10">
                        <label>Server</label>
                        <input type="text" class="form-control" id="edit_server" name="edit_server" />
                    </div>
                    <div class="col-md-2">
                        <label>Log</label>
                        <input type="checkbox" class="form-control" id="edit_logging" name="edit_logging" />
                        <input type="hidden" value="-1" name="edit_id" id="edit_id"/>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4">
                        <label>Port</label>
                        <input type="number" class="form-control" id="edit_port" name="edit_port" />
                    </div>
                    <div class="col-md-4">
                        <label>Service</label>
                        <select class="form-control" id="edit_service" name="edit_service">
                            <option value="imap">IMAP</option>
                            <option value="pop3">POP3</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label>Security</label>
                        <select class="form-control" id="edit_security" name="edit_security">
                            <option value="none">none</option>
                            <option value="tls">tls</option>
                            <option value="ssl">ssl</option>
                        </select>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <label>Username</label>
                        <input type="email" autocomplete="new-email" class="form-control" id="edit_username" name="edit_username" />
                    </div>
                    <div class="col-md-6">
                        <label>Password</label>
                        <input type="password" autocomplete="new-password" class="form-control required-field" id="edit_password" name="edit_password" />
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button id="checkEmailBtn" style="float:left" type="button" class="btn btn-default" onClick="edit_checkEmail(this)">Check</button>
                <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-warning" name="editAccount"><?php echo $lang['EDIT']; ?></button>
            </div>
        </div>
    </div>
</form>

<div class="modal fade" id="edit-rules">
    <div class="modal-dialog modal-content modal-lg">
      <div class="modal-header h4"><?php echo $lang['ADD']; ?>
        <button id="addRule" style="float: right" type="button" class="btn btn-warning" data-toggle="modal" data-target="#add-rule"><i class="fa fa-plus"></i></button>
      </div>
      <div class="modal-body">
      <table class="table">
        <thead><tr>
            <th>Identification</th>
            <th>Company</th>
            <th>Client</th>
            <th>Color</th>
            <th>Status</th>
            <th>Priority</th>
            <th>Parent</th>
            <th>Owner</th>
            <th>Employees/Teams</th>
            <th>Opt. Employees</th>
            <th>Task-Leader</th>
            <th></th>
        </tr></thead>
        <tbody id="rulesBody">
        </tbody>
        </table>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
      </div>
    </div>
</div>

<div class="modal fade" id="add-rule">
    <form id="resetForm" >
        <div class="modal-dialog modal-content modal-lg">
            <div class="modal-header h4"><?php echo $lang['ADD']; ?></div>
            <div class="modal-body">
                <ul class="nav nav-tabs">
                    <li class="active"><a data-toggle="tab" href="#projectBasics">Basic*</a></li>
                    <li><a data-toggle="tab" href="#projectAdvanced">Erweiterte Optionen</a></li>
                </ul>
                <div class="tab-content">
                    <div id="projectBasics" class="tab-pane fade in active"><br>
                        <div class="col-md-12"><label>Subject Filter*</label><input id="Identifier" class="form-control required-field" type="text" name="name" placeholder="<?php echo $lang['FILTER_PLACEHOLDER'] ?>" /><small style="margin-bottom:50px;" ><?php echo $lang['FILTER_HELP'] ?><br></small><br></div>
                        <div class="row">
                            <?php
                            $result_fc = mysqli_query($conn, "SELECT id, name FROM companyData WHERE id IN (".implode(', ', $available_companies).")");
                            echo '<div class="col-sm-4"><label>'.$lang['COMPANY'].'</label><select class="js-example-basic-single" id="Company" name="filterCompany" onchange="showClients(this.value, \''.$userID.'\', \'clientHint\');" >';
                            echo '<option value="0">...</option>';
                            while($result && ($row_fc = $result_fc->fetch_assoc())){
                                echo "<option value='".$row_fc['id']."' >".$row_fc['name']."</option>";
                            }
                            echo '</select></div>';
                            ?>
                            <div class="col-sm-4">
                                <label><?php echo $lang['CLIENT']; ?></label>
                                <select id="clientHint" class="js-example-basic-single" name="filterClient" onchange="showProjects(this.value, 'projectHint');">
                                    <option value="0">...</option>;
                                </select>
                            </div>
                            <div class="col-sm-4">
                                <label><?php echo $lang['PROJECT']; ?></label>
                                <select id="projectHint" class="js-example-basic-single" name="filterProject">
                                </select>
                            </div>
                        </div>

                        <div class="col-md-12"><small>*Auswahl ist Optional. Falls kein Projekt angegeben, entscheidet der Benutzer.</small><br><br></div>

                        <?php
                        $modal_options = '';
                        $result = $conn->query("SELECT id, firstname, lastname FROM UserData WHERE id IN (".implode(', ', $available_users).")");
                        while ($row = $result->fetch_assoc()){ $modal_options .= '<option value="'.$row['id'].'" data-icon="user">'.$row['firstname'] .' '. $row['lastname'].'</option>'; }
                        ?>
                        <div class="col-md-4">
                            <label><?php echo $lang["OWNER"]; ?>*</label>
                            <select id="Owner" class="select2-team-icons required-field" name="owner">
                                <?php echo $modal_options; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label><?php echo $lang["LEADER"]; ?>*</label>
                            <select id="Task-Leader" class="select2-team-icons required-field" name="leader">
                                <?php echo $modal_options; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label><?php echo $lang["EMPLOYEE"]; ?>*</label>
                            <select id="Employees" class="select2-team-icons required-field" name="employees[]" multiple="multiple">
                                <?php
                                $result = str_replace('<option value="', '<option value="user;', $modal_options); //append 'user;' before every value
                                echo $result;
                                $result = $conn->query("SELECT id, name FROM $teamTable");
                                while ($row = $result->fetch_assoc()) {
                                    echo '<option value="team;'.$row['id'].'" data-icon="group" >'.$row['name'].'</option>';
                                }
                                ?>
                            </select>
                        </div>
                    </div>

                    <div id="projectAdvanced" class="tab-pane fade"><br>
                        <div class="col-md-6">
                            <label><?php echo $lang["DYNAMIC_PROJECTS_PROJECT_PRIORITY"]; ?>*</label>
                            <select id="Priority" class="form-control js-example-basic-single" name="priority">
                                <?php
                                for($i = 1; $i < 6; $i++){
                                    echo '<option value="'.$i.'">'.$lang['PRIORITY_TOSTRING'][$i].'</option>';
                                }
                                ?>
                            </select><br>
                        </div>
                        <div class="col-md-2">
                            <label>Status*</label>
                            <div class="input-group">
                                <select id="Status" class="form-control" name="status" >
                                    <option value="DEACTIVATED" >Deaktiviert</option>
                                    <option value="ACTIVE" >Aktiv</option>
                                    <option value="DRAFT" >Entwurf</option>
                                    <option value="COMPLETED" >Abgeschlossen</option>
                                </select>
                            </div><br>
                        </div>
                         <div class="col-md-4">
                            <label>Geschätzte Zeit <a data-toggle="collapse" href="#estimateCollapse-"><i class="fa fa-question-circle-o"></i></a></label>
                            <input type="text" class="form-control" value="" id="estimatedHours" /><br>
                        </div>
                        <div class="row">
                                <div class="col-md-12">
                                    <div class="collapse" id="estimateCollapse-">
                                        <div class="well">
                                            Die <strong>Geschätzte Zeit</strong> wird per default in Stunden angegeben. D.h. 120 = 120 Stunden. <br>
                                            Mit "m", "t", "w" oder "M" können genauere Angaben gemacht werden: z.B. 2M für 2 Monate, 7m = 7 Minuten, 4t = 4 Tage und 6w = 6 Wochen.<br>
                                            Konkret: "2M 3w 50" würde also für 2 Monate, 3 Wochen und 50 Stunden stehen. (Alle anderen Angaben werden gespeichert, aber vom Programm ignoriert)
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <div class="col-md-4">
                            <label><?php echo $lang["DYNAMIC_PROJECTS_PROJECT_COLOR"]; ?></label>
                            <input id="Color" type="color" class="form-control" name="color"><br>
                        </div>
                        <div class="col-md-4">
                            <label><?php echo $lang["DYNAMIC_PROJECTS_PROJECT_PARENT"]; ?>:</label>
                            <select id="Parent" class="form-control js-example-basic-single" name="parent">
                                <option value=''>Keines</option>
                                <?php
                                $result = $conn->query("SELECT projectid, projectname FROM dynamicprojects");
                                while ($row = $result->fetch_assoc()) {
                                    echo '<option value="'.$row["projectid"].'" >'.$row["projectname"].'</option>';
                                }
                                ?>
                            </select><br>
                        </div>
                        <div class="col-md-4">
                            <label><?php echo $lang["DYNAMIC_PROJECTS_PROJECT_OPTIONAL_EMPLOYEES"]; ?></label>
                            <select id="OptEmployees" class="select2-team-icons" name="optionalemployees[]" multiple="multiple">
                                <?php
                                echo str_replace('<option value="', '<option value="user;', $modal_options);
                                ?>
                            </select>
                        </div>
                        <input id="emailId" class="form-control" type="hidden"/>
                    </div>
                </div><!-- /tab-content -->
            </div><!-- /modal-body -->
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-warning" onclick="addRuleFunc()" id="addRuleBtn" name="addRule"><?php echo $lang['ADD']; ?></button>
            </div>
        </div>
    </form>
</div>

<script>
    function clearInputs(){
        document.getElementById("resetForm").reset();
        $(".select2-team-icons").val(null).trigger('change');
        $(".js-example-basic-single").val(null).trigger('change');
        $("#Priority").val(1).trigger('change');
    }
    function editAccount(event,e_id){
        var id = document.getElementById("edit_id");
        var server = document.getElementById("edit_server");
        var port = document.getElementById("edit_port");
        var security = document.getElementById("edit_security");
        var service = document.getElementById("edit_service");
        var username = document.getElementById("edit_username");
        var password = document.getElementById("edit_password");
        var logging = document.getElementById("edit_logging");
        var row = null;
        $.post("ajaxQuery/AJAX_getEmailAccountInfo.php",{id: e_id},function(data){
            row = JSON.parse(data);
            //console.log(row);
            server.setAttribute("value",row['server']);
            port.setAttribute("value",row['port']);
            //console.log(port);
            username.setAttribute("value",row['username']);
            service.selectedIndex = row['service'].toUpperCase() == "IMAP" ? 0 : 1;
            security.selectedIndex = row['smtpSecure'] == null ? 0 : row['smtpSecure'] == "tls" ? 1 : 2;
            logging.checked = row['logEnabled'] == "FALSE" ? false : true;
            id.setAttribute("value",e_id);
            //TODO: Hier forstsetzten mit setzen der Werte
        });
    }
    function changeIdForRule(id){
        document.getElementById("emailId").setAttribute("value", id);
    }
    function addRuleFunc(){
        if(!document.getElementById("Identifier").value) return false;
        var Employees = [];
        var OEmployees = [];
        for(i=0;i<document.getElementById("Employees").selectedOptions.length;i++){
            Employees.push(document.getElementById("Employees").selectedOptions[i].value);
        }
        for(i=0;i<document.getElementById("OptEmployees").selectedOptions.length;i++){
            OEmployees.push(document.getElementById("OptEmployees").selectedOptions[i].value);
        }
        var Company = document.getElementById("Company").selectedOptions.length>0 ? document.getElementById("Company").selectedOptions[0].value : null;
        var Client = document.getElementById("clientHint").selectedOptions.length>0 ? document.getElementById("clientHint").selectedOptions[0].value : null;
        var ClientProject = document.getElementById("projectHint").selectedOptions.length>0 ? document.getElementById("projectHint").selectedOptions[0].value : null;
        $.post("ajaxQuery/AJAX_newRule.php",{
            Identifier: document.getElementById("Identifier").value,
            Company: Company,
            Client: Client,
            ClientProject: ClientProject,
            Color: document.getElementById("Color").value,
            Status: document.getElementById("Status").selectedOptions[0].value,
            Priority: document.getElementById("Priority").selectedOptions[0].value,
            Parent: document.getElementById("Parent").selectedOptions[0].value,
            Owner: document.getElementById("Owner").selectedOptions[0].value,
            Employees: Employees,
            OEmployees: OEmployees,
            Leader: document.getElementById("Task-Leader").selectedOptions[0].value,
            eid: document.getElementById("emailId").value,
            rid: document.getElementById("addRuleBtn").value,
            estimated: document.getElementById("estimatedHours").value,
        }, function(data){
            //console.log(data);
            editRules(null,data);
            $("#add-rule").modal("hide");
        });
    }
    function editRules(event,id){
        var body = document.getElementById("rulesBody");
        var priority_color = ['', '#2a5da1', '#0c95d9', '#6b6b6b', '#ff7600', '#ff0000'];
        document.getElementById("addRule").setAttribute("onClick","changeIdForRule("+id+")");
        while(body.firstChild){
            body.removeChild(body.firstChild);
        }
        $.post("ajaxQuery/AJAX_getRules.php",{
            id: id
        } , function(data){
            var rulesets = JSON.parse(data);
            for(i = 0;i<rulesets.length;i++){
                var client = document.createElement("td");
                var color = document.createElement("td");
                var company = document.createElement("td");
                var employees = document.createElement("td");
                var identifier = document.createElement("td");
                var leader = document.createElement("td");
                var optionalemployees = document.createElement("td");
                var owner = document.createElement("td");
                var parenttask = document.createElement("td");
                var priority = document.createElement("td");
                var status = document.createElement("td");
                var buttons = document.createElement("td");
                var parent = document.createElement("tr");
                client.innerHTML = rulesets[i]['client'];
                color.innerHTML = '<i style="color:'+rulesets[i]['color']+'" class="fa fa-circle"></i>';
                company.innerHTML = rulesets[i]['company'];
                employees.innerHTML = rulesets[i]['employees'];
                identifier.innerHTML = rulesets[i]['identifier'];
                optionalemployees.innerHTML = rulesets[i]['optionalemployees'];
                leader.innerHTML = rulesets[i]['leader'];
                owner.innerHTML = rulesets[i]['owner'];
                parenttask.innerHTML = rulesets[i]['parent'];
                priority.innerHTML = '<span class="badge" style="background-color:' +priority_color[rulesets[i]['priority']] + '">' + rulesets[i]['priority'] + ' </span>';
                status.innerHTML = rulesets[i]['status'];
                buttons.innerHTML = '<button type="button" name="delete" onclick="deleteRule('+ rulesets[i]['id'] +','+ rulesets[i]['emailaccount'] +')" title="Löschen" class="btn btn-default" ><i class="fa fa-trash-o"></i></button> <button type="button" name="edit" onclick="changeRule('+ rulesets[i]['id'] +')" title="Editieren" class="btn btn-default" ><i class="fa fa-pencil"></i></button> ';
                parent.appendChild(identifier);
                parent.appendChild(company);
                parent.appendChild(client);
                parent.appendChild(color);
                parent.appendChild(status);
                parent.appendChild(priority);
                parent.appendChild(parenttask);
                parent.appendChild(owner);
                parent.appendChild(employees);
                parent.appendChild(optionalemployees);
                parent.appendChild(leader);
                parent.appendChild(buttons);
                body.appendChild(parent);
            }
            //console.log(rulesets);
        })
    }
    function deleteRule(ruleID,emailID){
        $.post("ajaxQuery/AJAX_deleteRule.php",{
            emailid: emailID,
            ruleid: ruleID
        }, function (info){
            editRules(null,info);
        });
    }
    function checkEmail(element){
        $.post("ajaxQuery/AJAX_checkEmailAvailability.php",{
            server: document.getElementById("server").value,
            port: document.getElementById("port").value,
            security: document.getElementById("security").selectedOptions[0].value,
            service: document.getElementById("service").selectedOptions[0].value,
            username: document.getElementById("username").value,
            password: document.getElementById("password").value
        }, function(data){
          alert(data);
      });
    }
    function edit_checkEmail(element){
        $.post("ajaxQuery/AJAX_checkEmailAvailability.php",{
            server: document.getElementById("edit_server").value,
            port: document.getElementById("edit_port").value,
            security: document.getElementById("edit_security").selectedOptions[0].value,
            service: document.getElementById("edit_service").selectedOptions[0].value,
            username: document.getElementById("edit_username").value,
            password: document.getElementById("edit_password").value
        }, function(data){
            alert(data);
      });
  }
    function showClients(company, client, place){
        if(company != ""){
      $.ajax({
        url:'ajaxQuery/AJAX_getClient.php',
        data:{companyID:company, clientID:client},
        type: 'get',
        success : function(resp){
          $("#"+place).html(resp).trigger('change');
        },
        error : function(resp){}
        });
        }
    }
    function showProjects(client, place){
        if(client != ""){
      $.ajax({
        url:'ajaxQuery/AJAX_getProjects.php',
        data:{clientID:client},
        type: 'get',
        success : function(resp){
          $("#"+place).html(resp).trigger('change');
        },
        error : function(resp){}
      });
        }
    }
    $(document).ready(function() {
        $(".select2-team-icons").select2({
        templateResult: formatState,
        templateSelection: formatState
        });
        document.getElementById("addRule").addEventListener("click", clearInputs);
        $("#edit-account input, #edit-account select, #new-account input, #new-account select").on("change",function(){
        document.getElementById("checkEmailBtn").setAttribute('style','float:left');
        });
    });
    function formatState (state) {
        if (!state.id) { return state.text; }
        var $state = $(
        '<span><i class="fa fa-' + state.element.dataset.icon + '"></i> ' + state.text + '</span>'
        );
        return $state;
    };
    function retrieveEmails(){
        $.post("../report/tasks",{},function(data){
            console.log(data);
        });
    }
    function changeRule(id){
        $.post("ajaxQuery/AJAX_getRule.php",{
            id: id,
        }, function(data){
            console.log(JSON.parse(data));
            var ruleData = JSON.parse(data);
            document.getElementById("Identifier").value = ruleData.identifier;
            document.getElementById("estimatedHours").value = ruleData.estimatedHours;
            document.getElementById("Color").value = ruleData.color;
            document.getElementById("addRuleBtn").value = ruleData.id;
            $("#Company").val(ruleData.company).trigger('change');
            $("#Owner").val(ruleData.owner).trigger('change');
            $("#Task-Leader").val(ruleData.leader).trigger('change');
            $("#Employees").val((ruleData.employees.substring(0,ruleData.employees.length-1)).split(',')).trigger('change');
            $("#clientHint").val(ruleData.client).trigger('change');
            $("#Priority").val(ruleData.priority).trigger('change');
            $("#Parent").val(ruleData.parent).trigger('change');
            $("#OptEmployees").val((ruleData.optionalemployees.substring(0,ruleData.optionalemployees.length-1)).split(',')).trigger('change');
            $("#Status").val(ruleData.status).trigger('change');
            $("#projectHint").val(ruleData.clientproject).trigger('change');
            changeIdForRule(ruleData.emailaccount);
            $("#add-rule").modal('show');
        });
    }
</script>
<?php include dirname(__DIR__) . '/footer.php';?>
