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
    }elseif (isset($_POST['addAccount']) && !empty($_POST['server'])&& !empty($_POST['port'])&& !empty($_POST['username'])&& !empty($_POST['password'])) {
        $server = test_input($_POST['server']);
        $port = test_input($_POST['port']);
        $security = test_input($_POST['security']);
        $username = test_input($_POST['username']);
        $password = test_input($_POST['password']);
        $logging = $_POST['logging']=="on" ? 'TRUE' : 'FALSE';
        $conn->query("INSERT INTO emailprojects(server,port,smtpSecure,username,password,logEnabled) VALUES('$server','$port','$security','$username','$password','$logging') ");
        if ($conn->error) {
            echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>' . $conn->error . '</div>';
        }else{
            echo '<div class="alert alert-success"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$lang['OK_SAVE'].'</div>';
        }
    }elseif(isset($_POST['editAccount']) && !empty($_POST['server'])&& !empty($_POST['port'])&& !empty($_POST['username'])&& !empty($_POST['password'])){
        $server = test_input($_POST['server']);
        $port = test_input($_POST['port']);
        $security = test_input($_POST['security']);
        $username = test_input($_POST['username']);
        $password = test_input($_POST['password']);
        $logging = $_POST['logging']=="on" ? 'TRUE' : 'FALSE';
        $id = $_POST['id'];
        preg_match_all('!\d+!', $id, $id);
        $conn->query("UPDATE emailprojects SET server='$server',port='$port',smtpSecure='$security',username='$username',password='$password',logEnabled='$logging' WHERE id = ".$id[0][0]);
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
    <th>Security</th>
    <th>Username</th>
    <th>Log</th>
    <th></th>
  </tr></thead>
  <tbody>
  <?php
    $result = $conn->query("SELECT * FROM emailprojects");
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo '<td>' . $row['server'] . '</td>';
        echo '<td>' . $row['port'] . '</td>';
        echo '<td>' . ($row['smtpSecure']=='' ? 'none' : $row['smtpSecure']). '</td>';
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
        <input type="text" class="form-control" name="server" />
        <label>Port</label>
        <input type="number" class="form-control" name="port" />
        <label>Security</label>
        <select class="form-control" name="security">
            <option value="none">none</option>
            <option value="tls">tls</option>
            <option value="ssl">ssl</option>
        </select>
        <label>Username</label>
        <input type="text" class="form-control" name="username" />
        <label>Password</label>
        <input type="password" class="form-control" name="password" />
        <label>Log</label>
        <input type="checkbox" class="form-control" name="logging" />
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-warning" name="addAccount"><?php echo $lang['ADD']; ?></button>
      </div>
    </div>
  </div>
  <div class="modal fade" id="edit-account">
    <div class="modal-dialog modal-content modal-md">
      <div class="modal-header h4"><?php echo $lang['ADD']; ?></div>
      <div class="modal-body">
        <label>Server</label>
        <input type="text" class="form-control" id="edit_server" name="server" />
        <label>Port</label>
        <input type="number" class="form-control" id="edit_port" name="port" />
        <label>Security</label>
        <select class="form-control" id="edit_security" name="security">
            <option value="none">none</option>
            <option value="tls">tls</option>
            <option value="ssl">ssl</option>
        </select>
        <label>Username</label>
        <input type="text" class="form-control" id="edit_username" name="username" />
        <label>Password</label>
        <input type="password" class="form-control" id="edit_password" name="password" />
        <label>Log</label>
        <input type="checkbox" class="form-control" id="edit_logging" name="logging" />
        <inpu type="number" value="-1" style="visibility: hidden" name="id" id="edit_id"/>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-warning" name="editAccount"><?php echo $lang['EDIT']; ?></button>
      </div>
    </div>
  </div>
</form>
<script>
    function editAccount(event,id){
        console.log(event,id);
        var edit_id = document.getElementById("edit_id");
        var server = document.getElementById("edit_server");
        var port = document.getElementById("edit_port");
        var security = document.getElementById("edit_security");
        var username = document.getElementById("edit_username");
        var password = document.getElementById("edit_password");
        var logging = document.getElementById("edit_logging");
        edit_id.setAttribute("value",id);
        //TODO: Hier forstsetzten mit setzen der Werte
    }
</script>
<?php include dirname(__DIR__) . '/footer.php';?>