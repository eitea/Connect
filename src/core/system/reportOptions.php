<?php include dirname(dirname(__DIR__)) . '/header.php'; enableToCore($userID);?>
<?php require dirname(dirname(__DIR__)) . "/misc/helpcenter.php"; ?>
<script>
  document.onreadystatechange = () => {
    if (document.readyState === "complete"){
      if(document.getElementById("defaultCheck").hasAttribute("checked")){
        var container = document.getElementById("emailContainer");
        var inputs = container.getElementsByClassName("form-control");
        for(i = 0;i<inputs.length;i++){
          inputs[i].setAttribute("disabled","disabled");
        }
        document.getElementById("smtpDropDown").setAttribute("disabled","disabled");
      }
   }

  }
</script>
<?php
if(isset($_POST['saveButton'])){
    if((getenv('IS_CONTAINER') || isset($_SERVER['IS_CONTAINER'])) && isset($_POST['defaultOptions'])){
        $conn->query("UPDATE $mailOptionsTable SET host = 'adminmail', port = 25, username = 'admin', password = 'admin', smtpSecure = 'SSL', sender = 'noreply@eitea.at', sendername = 'Connect im Auftrag von ', isDefault = 1");
    } else {
        if(!empty($_POST['smtp_host'])){
            $val = test_input($_POST['smtp_host']);
            $conn->query("UPDATE $mailOptionsTable SET host = '$val'");
        }
        if(!empty($_POST['smtp_port'])){
            $val = intval($_POST['smtp_port']);
            $conn->query("UPDATE $mailOptionsTable SET port = '$val'");
        }
        if(isset($_POST['mail_username'])){
            $val = test_input($_POST['mail_username']);
            $conn->query("UPDATE $mailOptionsTable SET username = '$val'");
        }
        if(isset($_POST['mail_password'])){
            $val = test_input($_POST['mail_password']);
            $conn->query("UPDATE $mailOptionsTable SET password = '$val'");
        }
        if(isset($_POST['smtp_secure'])){
            $val = test_input($_POST['smtp_secure']);
            $conn->query("UPDATE $mailOptionsTable SET smtpSecure = '$val'");
        }
        if(!empty($_POST['mail_sender'])){
            $val = test_input($_POST['mail_sender']);
            $conn->query("UPDATE $mailOptionsTable SET sender = '$val'");
        }
        if(!empty($_POST['mail_sender_name'])){
            $val = test_input($_POST['mail_sender_name']);
            $conn->query("UPDATE $mailOptionsTable SET sendername = '$val'");
        }
        if(!empty($_POST['feedback_mail_recipient'])){
            $val = test_input($_POST['feedback_mail_recipient']);
            $conn->query("UPDATE $mailOptionsTable SET feedbackRecipient = '$val'");
        }
        $conn->query("UPDATE $mailOptionsTable SET isDefault = 0");
    }
    echo mysqli_error($conn);
}

$result = $conn->query("SELECT * FROM $mailOptionsTable");
$row = $result->fetch_assoc();
?>

<form method="post">
    <div class="page-header">
        <h3>E-mail <?php echo $lang['OPTIONS']; ?>
            <div class="page-header-button-group"><button type="submit" class="btn btn-default blinking" title="<?php echo $lang['SAVE']; ?>" name="saveButton"><i class="fa fa-floppy-o"></i></button></div>
            <div style="display:inline;float:right;"><a role="button" data-toggle="collapse" href="#info_emailserver"><i class="fa fa-info-circle"></i></a></div>
        </h3>
    </div>

    <div class="collapse" id="info_emailserver"><div class="well">
        <?php
        if(isset($_SESSION['language']) && $_SESSION['language'] == 'ENG'){
            echo 'To send reports and login informations via email, an external e-mail server is required. <br>
            When attempting to send an email, Connect will always work with the entered information below.';
        } elseif(!isset($_SESSION['language']) || $_SESSION['language'] == 'GER'){
            echo 'Um Reports oder Login Informationen abzuschicken wird ein externer E-Mail Server benötigt. <br>
            Sobald Informationen als E-Mail abgeschickt werden sollen, wird Connect die unten stehenden Daten verwenden.';
        }
        ?></div>
    </div>

    <div class="col-md-4">
        <h4>SMTP Einstellungen</h4>
    </div>
    <div class="col-md-8" >
        <?php
        if(getenv('IS_CONTAINER') || isset($_SERVER['IS_CONTAINER'])){
            $string = '<input style="float: right" id="defaultCheck" type="checkbox" name="defaultOptions" ';

            if($row['isDefault']){
                $string = $string . 'checked ';
            }
            $string = $string . '><label style="float: right;position: relative;min-height: 1px;padding-right: 10px;padding-left: 15px;padding-top: 5px;">Default</label></input>';
            echo $string;
        }
        ?>
    </div>
    <br><br>
    <div id="emailContainer" class="container-fluid">

        <br>
        <div class="col-md-4">SMTP Security</div>

        <div class="col-md-8">
            <select id = "smtpDropDown" class="js-example-basic-single" name="smtp_secure" style="width:200px">
                <option value="" <?php if($row['smtpSecure'] == ''){echo "selected";} ?>> - </option>
                <option value="tls" <?php if($row['smtpSecure'] == 'tls'){echo "selected";} ?>> TLS </option>
                <option value="ssl" <?php if($row['smtpSecure'] == 'ssl'){echo "selected";} ?>> SSL </option>
            </select>
        </div>
        <br><br>
        <div class="col-md-4"> Absender-Adresse </div>
        <div class="col-md-8"><input type="text" class="form-control" name="mail_sender"  value="<?php echo $row['sender']; ?>" /></div>
        <br><br>
        <div class="col-md-4"> Absender-Name </div>
        <div class="col-md-8"><input type="text" class="form-control" name="mail_sender_name"  value="<?php echo $row['senderName']; if(isset($_POST['defaultOptions'])) echo "<Kunde>";?>" /></div>
            <br><br><br>
            <div class="col-md-4">Host</div>
            <div class="col-md-8"><input type="text" class="form-control" name="smtp_host" value="<?php echo $row['host']; ?>" /></div>
            <br><br>
            <div class="col-md-4">Port</div>
            <div class="col-md-8"><input type="number" class="form-control" name="smtp_port"  value="<?php echo $row['port']; ?>" /></div>
            <br><br><br>
            <div class="col-md-4">Username</div>
            <div class="col-md-8"><input type="text" class="form-control" name="mail_username" value="<?php echo $row['username']; ?>" /></div>
            <br><br>
            <div class="col-md-4">Passwort</div>
            <div class="col-md-8"><input type="text" class="form-control password" name="mail_password" /></div>
            <br>
        </div>
        <div class="col-md-4">
            <h4>Feedback Einstellungen</h4>
        </div>
        <br/>
        <br/>
        <div class="container-fluid">
            <br/>
            <div class="col-md-4"> Feedback-Empfänger </div>
            <div class="col-md-8"><input type="text" class="form-control" name="feedback_mail_recipient"  value="<?php echo $row['feedbackRecipient']; ?>" /></div>
            <br><br>
        </div>
    </form>

<!-- /BODY -->
<?php include dirname(dirname(__DIR__)) . '/footer.php'; ?>
