<?php include dirname(__DIR__) . '/header.php';
if($_SERVER['REQUEST_METHOD'] == 'POST'){
    if(isset($_POST['delete'])){
        $id = ($_POST['delete']);
        preg_match_all('!\d+!', $_POST['delete'], $id);
        $conn->query("DELETE FROM emailprojects WHERE id = ".$id[0][0]);
        if ($conn->error) {
            echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>' . $conn->error . '</div>';
        }else{
            echo '<div class="alert alert-success"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$lang['OK_DELETE'].'</div>';
        }
    }elseif (isset($_POST['addAccount']) && !empty($_POST['server'])&& !empty($_POST['service'])&& !empty($_POST['port'])&& !empty($_POST['username'])&& !empty($_POST['password'])) {
        $server = test_input($_POST['server']);
        $port = test_input($_POST['port']);
        $security = test_input($_POST['security']) == "none" ? "null" : test_input($_POST['security']);
        $service = test_input($_POST['service']);
        $username = test_input($_POST['username']);
        $password = test_input($_POST['password']);
        $logging = $_POST['logging']=="on" ? 'TRUE' : 'FALSE';
        $conn->query("INSERT INTO emailprojects(server,port,service,smtpSecure,username,password,logEnabled) VALUES('$server','$port','$service','$security','$username','$password','$logging') ");
        if ($conn->error) {
            echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>' . $conn->error . '</div>';
        }else{
            echo '<div class="alert alert-success"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$lang['OK_SAVE'].'</div>';
        }
    }elseif(isset($_POST['editAccount']) && !empty($_POST['edit_server'])&& !empty($_POST['edit_service'])&& !empty($_POST['edit_port'])&& !empty($_POST['edit_username'])&& !empty($_POST['edit_password'])){
        $server = test_input($_POST['edit_server']);
        $port = test_input($_POST['edit_port']);
        $security = test_input($_POST['edit_security']) == "none" ? "null" : test_input($_POST['edit_security']);
        $service = test_input($_POST['edit_service']);
        $username = test_input($_POST['edit_username']);
        $password = test_input($_POST['edit_password']);
        $logging = $_POST['edit_logging']=="on" ? 'TRUE' : 'FALSE';
        $id = $_POST['edit_id'];
        preg_match_all('!\d+!', $id, $id);
        $conn->query("UPDATE emailprojects SET server='$server',port='$port',service='$service',smtpSecure='$security',username='$username',password='$password',logEnabled='$logging' WHERE id = ".$id[0][0]);
        if ($conn->error) {
            echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>' . $conn->error . '</div>';
        }else{
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
    <th><button type="button" title="Test" onClick="test()" class="btn btn-default" ><i class="fa fa-plus"></i></button></th>
  </tr></thead>
  <tbody>
  <?php
    $result = $conn->query("SELECT * FROM emailprojects");
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



<form method="POST">
  <div class="modal fade" id="new-account">
    <div class="modal-dialog modal-content modal-md">
      <div class="modal-header h4"><?php echo $lang['ADD']; ?></div>
      <div class="modal-body">
        <label>Server</label>
        <input type="text" class="form-control" name="server" id="server"/>
        <label>Port</label>
        <input type="number" class="form-control" name="port" id="port"/>
        <label>Service</label>
        <select class="form-control" name="service" id="service">
            <option value="imap">IMAP</option>
            <option value="pop3">POP3</option>
        </select>
        <label>Security</label>
        <select class="form-control" name="security" id="security">
            <option value="none">none</option>
            <option value="tls">tls</option>
            <option value="ssl">ssl</option>
        </select>
        <label>Username</label>
        <input type="email" class="form-control" name="username" id="username"/>
        <label>Password</label>
        <input type="password" class="form-control" name="password" id="password"/>
        <label>Log</label>
        <input type="checkbox" class="form-control" name="logging" id="logging" />
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
        <label>Server</label>
        <input type="text" class="form-control" id="edit_server" name="edit_server" />
        <label>Port</label>
        <input type="number" class="form-control" id="edit_port" name="edit_port" />
        <label>Service</label>
        <select class="form-control" id="edit_service" name="edit_service">
            <option value="imap">IMAP</option>
            <option value="pop3">POP3</option>
        </select>
        <label>Security</label>
        <select class="form-control" id="edit_security" name="edit_security">
            <option value="none">none</option>
            <option value="tls">tls</option>
            <option value="ssl">ssl</option>
        </select>
        <label>Username</label>
        <input type="email" class="form-control" id="edit_username" name="edit_username" />
        <label>Password</label>
        <input type="password" class="form-control" id="edit_password" name="edit_password" />
        <label>Log</label>
        <input type="checkbox" class="form-control" id="edit_logging" name="edit_logging" />
        <input type="number" value="-1" style="visibility: hidden" name="edit_id" id="edit_id"/>
      </div>
      <div class="modal-footer">
        <button style="float:left" type="button" class="btn btn-default" onblur="this.setAttribute('style','float:left');" onClick="edit_checkEmail(this)">Check</button>
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
            <th>Employees</th>
            <th>Opt. Employees</th>
            <th>Task-Leader</th>
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
    <div class="modal-dialog modal-content modal-md">
      <div class="modal-header h4"><?php echo $lang['ADD']; ?></div>
      <div class="modal-body">
        <label>Identifier</label>
        <input id="Identifier" class="form-control" type="text" />
        <label>Company</label>
        <input id="Company" class="form-control" type="text" />
        <label>Client</label>
        <input id="Client" class="form-control" type="text" />
        <label>Color</label>
        <input id="Color" class="form-control" type="text" />
        <label>Status</label>
        <input id="Status" class="form-control" type="text" />
        <label>Priority</label>
        <input id="Priority" class="form-control" type="text" />
        <label>Parent</label>
        <input id="Parent" class="form-control" type="text" />
        <label>Owner</label>
        <input id="Owner" class="form-control" type="text" />
        <label>Employees</label>
        <input id="Employees" class="form-control" type="text" />
        <label>Opt. Employees</label>
        <input id="Opt. Employees" class="form-control" type="text" />
        <label>Task-Leader</label>
        <input id="Task-Leader" class="form-control" type="text" />
        <input id="emailId" class="form-control" type="number" style="visibility: hidden"/>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-warning" data-dismiss="modal" onclick="addRule()" name="addRule"><?php echo $lang['ADD']; ?></button>
      </div>
    </div>
  </div>



<script>
    function editAccount(event,e_id){
        var id = document.getElementById("edit_id");
        var server = document.getElementById("edit_server");
        var port = document.getElementById("edit_port");
        var security = document.getElementById("edit_security");
        var service = document.getElementById("edit_service");
        var username = document.getElementById("edit_username");
        var password = document.getElementById("edit_password");
        var logging = document.getElementById("edit_logging");
        var row = event.target.parentNode.parentNode.parentNode.childNodes;
        server.setAttribute("value",row[0].textContent)
        port.setAttribute("value",row[1].textContent)
        username.setAttribute("value",row[4].textContent)
        service.selectedIndex = row[2].textContent == "IMAP" ? 0 : 1;
        security.selectedIndex = row[3].textContent == "none" ? 0 : row[0].textContent == "tls" ? 1 : 2;
        logging.checked = row[4].textContent == "FALSE" ? false : true;
        id.setAttribute("value",e_id);
        //TODO: Hier forstsetzten mit setzen der Werte
    }
    function changeIdForRule(id){
        document.getElementById("emailId").setAttribute("value", id);
    }
    function addRule(){
        $.post("../misc/newrule",{
            Identifier: document.getElementById("Identifier").value,
            Company: document.getElementById("Company").value,
            Client: document.getElementById("Client").value,
            Color: document.getElementById("Color").value,
            Status: document.getElementById("Status").value,
            Priority: document.getElementById("Priority").value,
            Parent: document.getElementById("Parent").value,
            Owner: document.getElementById("Owner").value,
            Employees: document.getElementById("Employees").value,
            OEmployees: document.getElementById("Opt. Employees").value,
            Leader: document.getElementById("Task-Leader").value,
            id: document.getElementById("emailId").value,
        }, function(data){
            console.log(data);
            editRules(null,data);
        });
    }
    function editRules(event,id){
        var body = document.getElementById("rulesBody");
        document.getElementById("addRule").setAttribute("onClick","changeIdForRule("+id+")");
        while(body.firstChild){
            body.removeChild(body.firstChild);
        }
        $.post("../misc/getrules",{
            id: id
        } , function(data){
            //console.log(data);
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
                var parent = document.createElement("tr");
                client.innerHTML = rulesets[i]['client'];
                color.innerHTML = rulesets[i]['color'];
                company.innerHTML = rulesets[i]['company'];
                employees.innerHTML = rulesets[i]['employees'];
                identifier.innerHTML = rulesets[i]['identifier'];
                optionalemployees.innerHTML = rulesets[i]['optionalemployees'];
                leader.innerHTML = rulesets[i]['leader'];
                owner.innerHTML = rulesets[i]['owner'];
                parenttask.innerHTML = rulesets[i]['parent'];
                priority.innerHTML = rulesets[i]['priority'];
                status.innerHTML = rulesets[i]['status'];
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
                body.appendChild(parent);
            }
            //console.log(rulesets);
        })
    }
    function test(){
      $.get("../misc/taskemails", function(data){
          console.log(data);
          //alert(JSON.parse(data));
      });
    }
    function checkEmail(element){
        $.post("../misc/checkemail",{
            server: document.getElementById("server").value,
            port: document.getElementById("port").value,
            security: document.getElementById("security").selectedOptions[0].value,
            service: document.getElementById("service").selectedOptions[0].value,
            username: document.getElementById("username").value,
            password: document.getElementById("password").value
        }, function(data){
          console.log(data==1);
          data==1 ? element.setAttribute("style","background-color: lime; float: left") : element.setAttribute("style","background-color: red; float: left");
      });
    }
    function edit_checkEmail(element){
        $.post("../misc/checkemail",{
            server: document.getElementById("edit_server").value,
            port: document.getElementById("edit_port").value,
            security: document.getElementById("edit_security").selectedOptions[0].value,
            service: document.getElementById("edit_service").selectedOptions[0].value,
            username: document.getElementById("edit_username").value,
            password: document.getElementById("edit_password").value
        }, function(data){
            console.log(data==1);
            data==1 ? element.setAttribute("style","background-color: lime; float: left") : element.setAttribute("style","background-color: red; float: left");
      });
    }
</script>
<?php include dirname(__DIR__) . '/footer.php';?>