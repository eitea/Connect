<?php include dirname(__DIR__) . '/header.php'; enableToDSGVO($userID); ?>
<?php require dirname(__DIR__) . "/misc/helpcenter.php"; ?>
<?php
if (empty($_GET['n']) || !in_array($_GET['n'], $available_companies)) { //eventually STRIKE
  $conn->query("UPDATE UserData SET strikeCount = strikeCount + 1 WHERE id = $userID");
  echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a><strong>Invalid Access.</strong> '.$lang['ERROR_STRIKE'].'</div>';
  include dirname(__DIR__) . '/footer.php';
  die();
}?>
<div class="page-header"><h3><?php echo $lang['DOCUMENTS']; ?>
  <div class="page-header-button-group">
    <button type="button" data-toggle="modal" data-target="#new-document" class="btn btn-default" title="New..."><i class="fa fa-plus"></i></button>
    <button type="button" data-toggle="modal" data-target="#zip-upload" class="btn btn-default" title="Upload Zip File"><i class="fa fa-upload"></i></button>
  </div>
  <span style="float:right" ><a href="https://consulio.at/dokumente" class="btn btn-sm btn-warning" target="_blank">Neueste Dokumente von Consulio laden</a> </span>
</h3></div>

<?php
use PHPMailer\PHPMailer\PHPMailer;

$cmpID = intval($_GET['n']);
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  if (!empty($_POST['delete'])){
    $val = intval($_POST['delete']);
    $conn->query("DELETE FROM documents WHERE id = $val AND companyID = $cmpID;");
    if($conn->error){
      echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$conn->error.'</div>';
    } else {
      echo '<div class="alert alert-success"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$lang['OK_DELETE'].'</div>';
    }
  } elseif (!empty($_POST['clone'])){
    $val = intval($_POST['clone']);
    $conn->query("INSERT INTO documents (companyID, docID, name, txt, version) SELECT companyID, docID, name, txt, version FROM documents WHERE id = $val AND companyID = $cmpID");
    //TODO: cloning a BASE has to result in merging the freetext INTO the document

    if($conn->error){
      echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$conn->error.'</div>';
    } else {
      echo '<div class="alert alert-success"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$lang['OK_CREATE'].'</div>';
    }
}
    if (isset($_POST['addDocument']) && !empty($_POST['add_docName'])) {
        $val = secure_data('DSGVO', test_input($_POST['add_docName']), 'encrypt', $userID, $privateKey);
        $conn->query("INSERT INTO documents(name, txt, companyID, version) VALUES('$val', ' ', $cmpID, '1.0') ");
        if ($conn->error) {
            echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>' . $conn->error . '</div>';
        } else {
            redirect("edit?d=" . $conn->insert_id);
        }
    }
    if (isset($_FILES['uploadZip']) && !empty($_FILES['uploadZip']['name'])) {
        $filename = $_FILES["uploadZip"]["name"];
        $source = $_FILES["uploadZip"]["tmp_name"];

        $accepted_types = array('application/zip', 'application/x-zip-compressed', 'multipart/x-zip', 'application/x-compressed');
        if ($_FILES['uploadZip']["size"] < 8000008 && in_array($_FILES["uploadZip"]["type"], $accepted_types) && substr_compare($filename, '.zip', -4) === 0) {
            $zip = new ZipArchive();
            if ($zip->open($_FILES['uploadZip']["tmp_name"]) === true) {
                $stmt = $conn->prepare("INSERT INTO documents(name, txt, companyID, version, docID, isBase) VALUES(?, ?, $cmpID, ?, ?, 'TRUE')");
                $stmt->bind_param('ssss', $doc_name, $doc_txt, $doc_ver, $doc_id);
                $stmt_up = $conn->prepare("UPDATE documents SET txt = ?, name = ?, version = ? WHERE id = ?");
                $stmt_up->bind_param('sssi', $doc_name, $doc_txt, $doc_ver, $id);
                for ($i = 0; $i < $zip->numFiles; $i++) {
                    $filename = explode('.', $zip->getNameIndex($i));
                    if (count($filename) == 2 && $filename[1] == 'txt' && ($meta = $zip->getFromName($filename[0] . '.xml'))) {
                        $meta = simplexml_load_string($meta);
                        if($meta->template_name && $meta->template_version && $meta->template_ID){
                          $doc_name = $meta->template_name;
                          $doc_ver = $meta->template_version;
                          $doc_id = $meta->template_ID;
                          $doc_txt = convToUTF8(nl2br($zip->getFromIndex($i)));
                          //upload exists: update
                          $result = $conn->query("SELECT id FROM documents WHERE companyID = $cmpID AND docID = '$doc_id'");
                          if($result && $result->num_rows > 0){
                            $row = $result->fetch_assoc();
                            $result->free();
                            $id = $row['id'];
                            $stmt_up->execute();
                            if($conn->error){
                                echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$conn->error.'</div>';
                            }
                          } else {  //insert as new document
                            $stmt->execute();
                            if($conn->error){
                                echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$conn->error.'</div>';
                            }
                          }
                        } else {
                          echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>Fehler in '.$filename[0].'.xml: Fehlerhafte Tags.</div>';
                          continue;
                        }
                    }
                }
                $stmt->close();
                $stmt_up->close();
                $zip->close();
            } else {
                echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>ZIP File konnte nicht geöffnet werden.</div>';
            }
        } else {
            echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>Ungültig: File zu groß oder kein gültiges ZIP.</div>';
        }
    }
    if (isset($_POST['sendAccess']) && !empty($_POST['send_contact']) && !empty($_POST['send_document'])) {
        $accept = true;
        $processID = uniqid();
        $docID = intval($_POST['send_document']);
        $contactID = intval($_POST['send_contact']);
        $pass = '';
        if (!empty($_POST['send_andPassword'])) {
            $pass = password_hash($_POST['send_andPassword'], PASSWORD_BCRYPT);
        }
        //contactPerson
        $result = $conn->query("SELECT email, firstname, lastname FROM contactPersons WHERE id = $contactID");
        if(!$contact_row = $result->fetch_assoc()) $accept = false;

        //build link
        $link_id = '';
        if (getenv('IS_CONTAINER') || isset($_SERVER['IS_CONTAINER'])) {
            $link_id = '/' . substr($servername, 0, 8);
        }
        $link = "https://" . $_SERVER['HTTP_HOST'] . $link_id . $_SERVER['REQUEST_URI'];
        $link = explode('/', $link);
        array_pop($link);
        $link = implode('/', $link) . "/access?n=$processID";

        //prepare document
        $result = $conn->query("SELECT docID, name, txt, version FROM documents WHERE id = $docID AND companyID = $cmpID");
        if($row = $result->fetch_assoc()){
          $doc_cont = test_input($row['txt']);
          $doc_head = $row['name'];
          $doc_ver = $row['version'];
          $doc_ident = $row['docID'];
          $result->free();
        } else {
          echo $conn->error;
          $accept = false;
        }
        $result = $conn->query("SELECT p.firstname, p.lastname, e.name, c.address_Street, c.address_Country_Postal, c.address_Country_City  FROM contactPersons p
        INNER JOIN clientData e ON p.clientID = e.id INNER JOIN clientInfoData c ON e.id = c.clientID WHERE p.id = $contactID");
        if($accept && ($row = $result->fetch_assoc())){
          $doc_cont = str_replace('[LINK]', $link, $doc_cont);
          $doc_cont = str_replace('[FIRSTNAME]', $contact_row['firstname'], $doc_cont);
          $doc_cont = str_replace('[LASTNAME]', $contact_row['lastname'], $doc_cont);
          $doc_cont = str_replace('[Companyname]', $row['name'], $doc_cont);
          $doc_cont = str_replace('[Companystreet]', $row['address_Street'], $doc_cont);
          $doc_cont = str_replace('[Companyplace]', $row['address_Country_City'], $doc_cont);
          $doc_cont = str_replace('[Companypostcode]', $row['address_Country_Postal'], $doc_cont);
          $result->free();
          if(preg_match_all("/\[CUSTOMTEXT_\d+\]/", $doc_cont, $matches) && $matches){
            $result = $conn->query("SELECT id, identifier, content FROM document_customs WHERE doc_id = '$doc_ident' AND companyID = $cmpID ");
            $result = $result->fetch_all(MYSQLI_ASSOC);
            $result = array_combine(array_column($result, 'identifier'), array_column($result, 'content'));
            foreach($matches[0] as $match){
              $doc_cont = str_replace($match, $result[substr($match,1,-1)], $doc_cont);
            }
          }
        }

        if($accept){
          //create process and history
          $stmt = $conn->prepare("INSERT INTO documentProcess(id, docID, personID, password, document_text, document_headline, document_version) VALUES(?, ?, ?, ?, ?, ?, ?)");
          $stmt->bind_param('siissss', $processID, $docID, $contactID, $pass, $doc_cont, $doc_head, $doc_ver);
          $stmt->execute();
          $stmt->close();
          $stmt = $conn->prepare("INSERT INTO documentProcessHistory(processID, activity) VALUES('$processID', ?)");
          $stmt->bind_param("s", $activity);
          if (isset($_POST['send_andRead'])) {
              $activity = 'ENABLE_READ';
              $stmt->execute();
          }
          if (isset($_POST['send_andSign'])) {
              $activity = 'ENABLE_SIGN';
              $stmt->execute();
          }
          if (isset($_POST['send_andAccept'])) {
              $activity = 'ENABLE_ACCEPT';
              $stmt->execute();
          }
          $stmt->close();

          //build email content
          if ($_POST['send_template']) {
            $val = intval($_POST['send_template']);
            $res = $conn->query("SELECT htmlCode FROM templateData WHERE id = $val AND type='document' AND userIDs = $cmpID ");
            $content = $res->fetch_assoc()['htmlCode'];
          } else {
            $content = "<p>Guten Tag,</p><p>&nbsp;</p><p>Soeben wurde&nbsp;folgendes Dokument an&nbsp;[FIRSTNAME]&nbsp;[LASTNAME] versendet. Es ist unter folgendem Link einsehbar:</p>" .
                "<p>[LINK]</p><p>&nbsp;</p><p>Zu beachten sind:</p><ul><li>Alle T&auml;tigkeiten auf dieser&nbsp;Seite werden mitprotokolliert und sind f&uuml;r den&nbsp;Absender dieses Dokuments einsehbar.&nbsp;</li>" .
                "<li>Jede Option kann nur einmal abgespeichert werden und ist im Nachhinein nicht mehr &auml;nderbar.</li><li>Falsch eingegebene Passw&ouml;rter werden gespeichert.&nbsp;</li></ul><p>&nbsp;</p><p>Danke.</p>";
          }

          $content = str_replace("[LINK]", $link, $content);
          $content = str_replace('[FIRSTNAME]', $contact_row['firstname'], $content);
          $content = str_replace('[LASTNAME]', $contact_row['lastname'], $content);

          //send mail
          require dirname(dirname(__DIR__)) . '/plugins/phpMailer/autoload.php';
          $mail = new PHPMailer();
          $mail->CharSet = 'UTF-8';
          $mail->Encoding = "base64";
          $mail->IsSMTP();

          $result = $conn->query("SELECT * FROM $mailOptionsTable");
          $row = $result->fetch_assoc();
          if (!empty($row['username']) && !empty($row['password'])) {
              $mail->SMTPAuth = true;
              $mail->Username = $row['username'];
              $mail->Password = $row['password'];
          } else {
              $mail->SMTPAuth = false;
          }
          if (empty($row['smptSecure'])) {
              $mail->SMTPSecure = $row['smtpSecure'];
          }

          $mail->Host = $row['host'];
          $mail->Port = $row['port'];
          $mail->setFrom($row['sender']);

          $mail->addAddress($contact_row['email'], $contact_row['firstname'] . ' ' . $contact_row['lastname']);

          $mail->isHTML(true); // Set email format to HTML
          $mail->Subject = 'Connect - Dokumentenversand';
          $mail->Body = $content;
          $mail->AltBody = "Your e-mail provider does not support HTML. To apply formatting, use an html viewer." . $content;
          if (!$mail->send()) {
              $errorInfo = $mail->ErrorInfo;
              $conn->query("INSERT INTO $mailLogsTable(sentTo, messageLog) VALUES('".$contact_row['email']."', '$errorInfo')");
              echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>' . $errorInfo . '</div>';
          } elseif ($conn->error) {
              echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>' . $conn->error . '</div>';
          } else {
              echo '<div class="alert alert-success"><a href="#" data-dismiss="alert" class="close">&times;</a>' . $lang['OK_CREATE'] . '</div>';
          }
        } else {
          echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>Dokument oder Kontakperson unzulässig.</div>';
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
while ($row = $result->fetch_assoc()) {
    $style = '';
    if($row['isBase'] == 'TRUE'){
      $style = 'style="background-color:#efefef"';
      $row['version'] .= ' <small>(Basis)</small>';
    }
    echo "<tr $style>";
    echo '<td>' . $row['name'] . '</td>';
    echo '<td>' . $row['version'] . '</td>';
    echo '<td><form method="POST">';
    echo '<a href="edit?d=' . $row['id'] . '" title="Bearbeiten" class="btn btn-default"><i class="fa fa-pencil"></i></a> ';
    echo '<button type="submit" name="clone" value="' . $row['id'] . '" title="Klonen" class="btn btn-default" ><i class="fa fa-files-o"></i></button> ';
    echo '<button type="submit" name="delete" value="' . $row['id'] . '" title="Löschen" class="btn btn-default" ><i class="fa fa-trash-o"></i></button> ';
    echo '<button type="button" name="setSelect" value="' . $row['id'] . '" data-toggle="modal" data-target="#send-as-mail" class="btn btn-default" title="Senden.."><i class="fa fa-envelope-o"></i></button>';
    echo '</form></td>';
    echo '</tr>';
    $doc_selects .= '<option value="' . $row['id'] . '" >' . $row['name'] .' - '. $row['version']. '</option>';
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
if ($res && $res->num_rows > 1) {echo '<option value="0">...</option>';}
while ($res && ($row_fc = $res->fetch_assoc())) {
    echo "<option value='" . $row_fc['id'] . "' >" . $row_fc['name'] . "</option>";
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
                <option value="0"><?php echo $lang['DEFAULT']; ?></option>
                <?php
$res = $conn->query("SELECT * FROM templateData WHERE type='document' AND userIDs = $cmpID");
while ($res && ($row_fc = $res->fetch_assoc())) {
    echo "<option value='" . $row_fc['id'] . "' >" . $row_fc['name'] . "</option>";
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
  $("#send-select-doc").val($(this).val()).trigger('change');
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
if (isset($filterClient)) {
    echo '<script>';
    echo "showContacts($filterClient)";
    echo '</script>';
}
?>
<?php include dirname(__DIR__) . '/footer.php';?>
