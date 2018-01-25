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
    <th>Port</th>
    <th>Service</th>
    <th>Security</th>
    <th>Username</th>
    <th>Log</th>
    <th><button type="button" title="Test" onClick="test()" class="btn btn-default" ><i class="fa fa-plus"></i></button></th>
  </tr></thead>
  <tbody>
  <?php
    $result = $conn->query("SELECT * FROM emailprojects");
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo '<td>' . $row['server'] . '</td>';
        echo '<td>' . $row['port'] . '</td>';
        echo '<td>' . strtoupper($row['service']) . '</td>';
        echo '<td>' . ($row['smtpSecure']=='null' ? 'none' : $row['smtpSecure']). '</td>';
        echo '<td>' . $row['username'] . '</td>';
        echo '<td>' . $row['logEnabled'] . '</td>';
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