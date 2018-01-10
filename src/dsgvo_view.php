<?php require 'header.php'; enableToDSGVO($userID); ?>
<div class="page-header"><h3><?php echo $lang['DOCUMENTS']; ?><div class="page-header-button-group">
  <button type="button" data-toggle="modal" data-target="#new-document" class="btn btn-default" title="New..."><i class="fa fa-plus"></i></button>
  <button type="button" data-toggle="modal" data-target="#zip-upload" class="btn btn-default" title="Upload Zip File"><i class="fa fa-upload"></i></button>
</div></h3></div>

<?php 
use PHPMailer\PHPMailer\PHPMailer;
if(empty($_GET['n']) || !in_array($_GET['n'], $available_companies)){ //eventually STRIKE
    echo "Invalid Access.";
    include 'footer.php';
    die();
}

$cmpID = intval($_GET['n']);
if($_SERVER['REQUEST_METHOD'] == 'POST'){
  if(isset($_POST['addDocument']) && !empty($_POST['add_docName'])){
    $val = test_input($_POST['add_docName']);
    $conn->query("INSERT INTO documents(name, txt, companyID, version) VALUES('$val', ' ', $cmpID, '1.0') ");
    if($conn->error){
      echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$conn->error.'</div>';
    } else {
      redirect("edit?d=".$conn->insert_id);
    }
  }
  if(isset($_FILES['uploadZip']) && !empty($_FILES['uploadZip']['name'])) {
    $filename = $_FILES["uploadZip"]["name"];
    $source = $_FILES["uploadZip"]["tmp_name"];

    $accepted_types = array('application/zip', 'application/x-zip-compressed', 'multipart/x-zip', 'application/x-compressed');
    if($_FILES['uploadZip']["size"] < 8000008 && in_array($_FILES["uploadZip"]["type"], $accepted_types) && substr_compare($filename, 'zip', -3 ) === 0){
      $zip = new ZipArchive();      
      if($zip->open($_FILES['uploadZip']["tmp_name"]) === TRUE){
        $conts = $zip->getFromName('Vereinbarung.txt');
        echo $conts;
        $zip->close();
      } else {
        echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>ZIP File konnte nicht geöffnet werden.</div>';
      }
    } else {
      echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>Ungültig: File zu groß oder kein gültiges ZIP.</div>';
    }
  }
  if(isset($_POST['sendAccess']) && !empty($_POST['send_contact']) && !empty($_POST['send_document'])){
    $processID = uniqid();
    $docID = intval($_POST['send_document']);
    $contactID = intval($_POST['send_contact']);
    $pass = '';
    if(!empty($_POST['send_andPassword'])){
      $pass = password_hash($_POST['send_andPassword'], PASSWORD_BCRYPT);
    }
    //create process and history
    $conn->query("INSERT INTO documentProcess(id, docID, personID, password) VALUES('$processID', $docID, $contactID, '$pass')"); echo $conn->error;
    $stmt = $conn->prepare("INSERT INTO documentProcessHistory(processID, activity) VALUES('$processID', ?)");
    $stmt->bind_param("s", $activity);
    if(isset($_POST['send_andRead'])){
      $activity = 'ENABLE_READ';
      $stmt->execute();
    }
    if(isset($_POST['send_andSign'])){
      $activity = 'ENABLE_SIGN';
      $stmt->execute();
    }
    if(isset($_POST['send_andAccept'])){
      $activity = 'ENABLE_ACCEPT';
      $stmt->execute();
    }
    $stmt->close();

    //contactPerson
    $result = $conn->query("SELECT email, firstname, lastname FROM contactPersons WHERE id = $contactID");
    $contact_row = $result->fetch_assoc();
    
    //build the content
    if($_POST['send_template']){
      $val = intval($_POST['send_template']);
      $res = $conn->query("SELECT htmlCode FROM templateData WHERE id = $val AND type='document' AND userIDs = $cmpID ");
      $content = $res->fetch_assoc()['htmlCode'];
    } else {
      $content = "<p>Guten Tag,</p><p>&nbsp;</p><p>Soeben wurde&nbsp;folgendes Dokument an&nbsp;[FIRSTNAME]&nbsp;[LASTNAME] versendet. Es ist unter folgendem Link einsehbar:</p>".
      "<p>[LINK]</p><p>&nbsp;</p><p>Zu beachten sind:</p><ul><li>Alle T&auml;tigkeiten auf dieser&nbsp;Seite werden mitprotokolliert und sind f&uuml;r den&nbsp;Absender dieses Dokuments einsehbar.&nbsp;</li>".
      "<li>Jede Option kann nur einmal abgespeichert werden und ist im Nachhinein nicht mehr &auml;nderbar.</li><li>Falsche assw&ouml;rter werden gespeichert.&nbsp;</li></ul><p>&nbsp;</p><p>Danke.</p>";
    }

    //build link
    $link_id = '';
    if(getenv('IS_CONTAINER') || isset($_SERVER['IS_CONTAINER'])){
      $link_id = '/'.substr($servername,0,8);
    }
    $link = "https://".$_SERVER['HTTP_HOST'].$link_id .$_SERVER['REQUEST_URI'];
    $link = explode('/', $link);
    array_pop($link);
    $link = implode('/', $link) . "/access?n=$processID";

    $content = str_replace("[LINK]", $link, $content);
    $content = str_replace('[FIRSTNAME]', $contact_row['firstname'], $content);
    $content = str_replace('[LASTNAME]', $contact_row['lastname'], $content);

    //send mail
    require dirname(__DIR__).'/plugins/phpMailer/autoload.php';
    $mail = new PHPMailer();
    $mail->CharSet = 'UTF-8';
    $mail->Encoding = "base64";
    $mail->IsSMTP();
    
    $result = $conn->query("SELECT * FROM $mailOptionsTable");
    $row = $result->fetch_assoc();
    if(!empty($row['username']) && !empty($row['password'])){
      $mail->SMTPAuth   = true;
      $mail->Username   = $row['username'];
      $mail->Password   = $row['password'];
    } else {
      $mail->SMTPAuth   = false;
    }
    if(empty($row['smptSecure'])){
      $mail->SMTPSecure = $row['smtpSecure'];
    }

    $mail->Host       = $row['host'];
    $mail->Port       = $row['port'];
    $mail->setFrom($row['sender']);

    $mail->addAddress($contact_row['email'], $contact_row['firstname'].' '.$contact_row['lastname']);

    $mail->isHTML(true);                       // Set email format to HTML
    $mail->Subject = 'Connect - Dokumentenversand';
    $mail->Body    = $content;
    $mail->AltBody = "Your e-mail provider does not support HTML. To apply formatting, use an html viewer." . $content;
    if(!$mail->send()){
      $errorInfo = $mail->ErrorInfo;
      $conn->query("INSERT INTO $mailLogsTable(sentTo, messageLog) VALUES('$recipients', '$errorInfo')");
      echo $errorInfo;
    }

    if($conn->error){
      echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$conn->error.'</div>';
    } else {
      echo '<div class="alert alert-success"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$lang['OK_ADD'].'</div>';
    }
  }
}
?>

<table class="table">
  <thead><tr>
    <th>Name</th>
    <th>Version</th>
    <th></th>
  </tr></thead>
  <tbody>
  <?php
  $doc_selects = '';
  $result = $conn->query("SELECT * FROM documents WHERE companyID = $cmpID");
  while($row = $result->fetch_assoc()){
    echo '<tr>';
    echo '<td>'.$row['name'].'</td>';
    echo '<td>'.$row['version'].'</td>';
    echo '<td>';
    echo '<a href="edit?d='.$row['id'].'" title="Bearbeiten" class="btn btn-default"><i class="fa fa-pencil"></i></a> ';
    echo '<button type="button" name="setSelect" value="'.$row['id'].'" data-toggle="modal" data-target="#send-as-mail" class="btn btn-default" title="Senden.."><i class="fa fa-envelope-o"></i></button>';
    echo '</td>';
    echo '</tr>';
    $doc_selects .= '<option value="'.$row['id'].'" >'.$row['name'].'</option>';
  }
  ?>
  </tbody>
</table>

<form method="POST">
  <div id="send-as-mail" class="modal fade">
    <div class="modal-dialog modal-content modal-md"><div class="modal-header h4">Dokument Senden</div>
      <div class="modal-body">
        <div class="container-fluid">
          <label><?php echo $lang['DOCUMENTS']; ?></label>
          <select id="send-select-doc" class="js-example-basic-single" name="send_document"><?php echo $doc_selects; ?></select>
          <br><br>
          <div class="row form-group">
            <div class="col-sm-4">
              <label><?php echo $lang['CLIENT']; ?></label>
              <select class="js-example-basic-single" onchange="showContacts(this.value);">
              <option value="">...</option>
              <?php             
              $res = $conn->query("SELECT id, name FROM clientData WHERE companyID = $cmpID");
              if($res && $res->num_rows > 1){ echo '<option value="0">...</option>'; }
              while($res && ($row_fc = $res->fetch_assoc())){
                echo "<option value='".$row_fc['id']."' >".$row_fc['name']."</option>";
                $filterClient = $row['id'];
              }
              ?>
              </select>
            </div>
            <div class="col-sm-4">
              <label><?php echo $lang['CONTACT_PERSON']; ?></label>
              <select id="contactHint" class="js-example-basic-single" name="send_contact"></select>
            </div>
          </div>
          <div class="row form-group checkbox">
            <div class="col-sm-4"><label><input type="checkbox" name="send_andRead" /> + Lesen</label></div>
            <div class="col-sm-4"><label><input type="checkbox" name="send_andAccept" /> + Akzeptieren</label></div>
            <div class="col-sm-4"><label><input type="checkbox" name="send_andSign" /> + Unterschreiben</label></div>            
          </div>
          <br>
          <div class="row">
            <div class="col-sm-6"><label>Zugang mit Passwort schützen</label><input type="text" name="send_andPassword" placeholder="Password" class="form-control" /></div>
            <div class="col-sm-6">
              <label>E-Mail Vorlage</label>
              <select class="js-example-basic-single" name="send_template">
                <option value="0"><?php echo  $lang['DEFAULT']; ?></option>
                <?php
                $res = $conn->query("SELECT * FROM templateData WHERE type='document' AND userIDs = $cmpID");
                while($res && ($row_fc = $res->fetch_assoc())){
                  echo "<option value='".$row_fc['id']."' >".$row_fc['name']."</option>";
                }
                ?>
              </select>
            </div>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-warning" name="sendAccess">Dokument Senden</button>
      </div>
    </div>
  </div>
</form>

<form method="POST">
  <div class="modal fade" id="new-document">
    <div class="modal-dialog modal-content modal-md">
      <div class="modal-header h4"><?php echo $lang['ADD']; ?></div>
      <div class="modal-body">
        <label>Name</label>
        <input type="text" class="form-control" name="add_docName" />
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-warning" name="addDocument"><?php echo $lang['ADD']; ?></button>
      </div>
    </div>
  </div>
</form>

<form method="POST" enctype="multipart/form-data">
  <div class="modal fade" id="zip-upload">
    <div class="modal-dialog modal-content modal-md">
      <div class="modal-header h4">ZIP <?php echo $lang['UPLOAD']; ?></div>
      <div class="modal-body">
        <label class="btn btn-default">
          .zip File <?php echo $lang['UPLOAD']; ?>
          <input type="file" name="uploadZip" style="display:none">
        </label>
        <small>Max. 8MB</small>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-warning"><?php echo $lang['UPLOAD']; ?></button>
      </div>
    </div>
  </div>
</form>

<script>
$('button[name=setSelect]').click(function(){
  $("[name='send_document']").val($(this).val()).trigger('change');
});
function showContacts(client){
  $.ajax({
    url:'ajaxQuery/AJAX_getContacts.php',
    data:{clientID:client},
    type: 'get',
    success : function(resp){
      $('#contactHint').html(resp);
    },
    error : function(resp){}
  });
}
</script>

<?php
if(isset($filterClient)){
  echo '<script>';
  echo "showContacts($filterClient)";
  echo '</script>';
}
?>
<?php require 'footer.php'; ?>