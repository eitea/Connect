<?php include 'header.php'; ?>
<?php enableToTemplate($userID); ?>

<div class="page-header">
  <h3><?php echo $lang['TEMPLATES']; ?></h3>
</div>

<?php
$templatePreview = "Click on a template above to preview it, or create a new one.";
if(isset($_POST['prevTemplate'])){
  $tempelID = intval($_POST['prevTemplate']);
  $templatePreview = "<iframe src='templatePreview.php?prevTemplate=$tempelID' style='width:100%; border:none;' scrolling='no' onload='resizeIframe(this)'></iframe>";
}
if(isset($_POST['removeTemplate'])){
  $tempelID = $_POST['removeTemplate'];
  $conn->query("DELETE FROM $pdfTemplateTable WHERE id = $tempelID");
}

if(isset($_POST['addRecipient'])){
  $reportID = $_POST['addRecipient'];
  if(!empty($_POST['recipientMail'.$reportID]) && filter_var($_POST['recipientMail'.$reportID], FILTER_VALIDATE_EMAIL)){
    $mail = test_input($_POST['recipientMail'.$reportID]);
    $conn->query("INSERT INTO $mailReportsRecipientsTable(reportID, email) VALUES($reportID, '$mail')");
    echo mysqli_error($conn);
  } else {
    echo '<div class="alert alert-danger fade in"><a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>Invalid E-Mail Address.</div>';
  }
} elseif(isset($_POST['removeRecipient'])){
  $recipID = $_POST['removeRecipient'];
  $conn->query("DELETE FROM $mailReportsRecipientsTable WHERE id = $recipID");
} elseif(isset($_POST['saveChanges'])){
  $conn->query("UPDATE $pdfTemplateTable SET repeatCount = ''");
  echo mysqli_error($conn);
  if(isset($_POST['activeReport'])){
    foreach($_POST['activeReport'] as $x){
      $conn->query("UPDATE $pdfTemplateTable SET repeatCount = 'TRUE' WHERE id = $x");
    }
  }
}
?>

<form method="POST">
<table class="table table-hover">
  <thead>
    <th><?php echo $lang['OPTIONS']; ?></th>
    <th>Name</th>
    <th>Activate</th>
    <th><?php echo $lang['RECIPIENTS']; ?></th>
    <th style="width:20%">Add E-Mail</th>
    <th></th>
  </thead>
  <tbody>
    <?php
    $result = $conn->query("SELECT * FROM $pdfTemplateTable"); //CARE: changes to this query must also be applied to modal query!
    while($result && ($row = $result->fetch_assoc())){
      $templID = $row['id'];

      $checked = "";
      if(!empty($row['repeatCount'])){$checked = "checked";}
      $result2 = $conn->query("SELECT * FROM $mailReportsRecipientsTable WHERE reportID = $templID");
      $recipients = "";
      while($result2 && ($row2 = $result2->fetch_assoc())){
        $recipients .= '<button type="submit" style="background:none;border:none" name="removeRecipient" value="'.$row2['id'].'"><img width="10px" height="10px" src="../images/minus_circle.png"></button>'.$row2['email'] . '<br>';
      }

      echo '<tr>';
      echo '<td>';
      echo "<button type='submit' class='btn btn-default' name='removeTemplate' value='$templID' title='Delete'> <i class='fa fa-trash-o'></i></button> ";
      echo "<a href='templateDownload.php?id=$templID' target='_blank' class='btn btn-default' title='Export'> <i class='fa fa-download'></i></a> ";
      echo "<a href='templateEdit.php?id=$templID' class='btn btn-default' title='Edit'> <i class='fa fa-pencil'></i></a> ";
      echo "<button type='submit' value='$templID' name='prevTemplate' class='btn btn-default' title='Preview'> <i class='fa fa-search'></i></button> ";
      echo "<button type='button' class='btn btn-default' data-toggle='modal' data-target='#addUserModal$templID' title='Select Userdata'> <i class='fa fa-user-plus'></i></button>";
      echo '</td>';
      echo "<td>" . $row['name'] . "</td>";

      echo "<td style='width:100px'><input type='checkbox' name='activeReport[]' value='$templID' $checked /></td>";
      echo "<td>$recipients</td>";
      echo "<td><input type='text' name='recipientMail$templID' class='form-control'/></td>";
      echo "<td><button type='submit' class='btn btn-warning btn-small' name='addRecipient' value='$templID'>+</button></td>";
      echo '</tr>';
    }
    ?>
  </tbody>
</table>

<div class="container text-right">
  <button type="submit" name="saveChanges" class="btn btn-default"><?php echo $lang['SAVE']; ?></button>
</div>
</form>

<?php
if(isset($_POST['modifyUserIDs'])){
  if(empty($_POST['modifiedUser'])){
    $userIDs = '';
  } else {
    $userIDs = implode(",",$_POST['modifiedUser']);
  }
  $conn->query("UPDATE $pdfTemplateTable SET userIDs = '$userIDs' WHERE id = ".$_POST['modifyUserIDs']);
  echo $conn->error;
}
$result = $conn->query("SELECT * FROM $pdfTemplateTable"); //CARE: changes to this query must also be applied to above query!
while($result && ($row = $result->fetch_assoc())):  //create a modal for every table above. So we don't mess it up.
?>
<!-- Modal -->
<div class="modal fade" id="addUserModal<?php echo $row['id']; ?>" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title" id="myModalLabel">Select Users</h4>
      </div>
      <form method="post">
        <div class="modal-body">
          Select all users who's data should be included in your report. <br>Leave empty to include all. <br><br>
          <div class="container checkbox">
            <?php
            $users = explode(",", $row['userIDs']);
            $resultUser = $conn->query("SELECT id, firstname, lastname FROM $userTable");
            while($resultUser && ($rowUser = $resultUser->fetch_assoc())){
              if(in_array($rowUser['id'], $users)){
                $selected = 'checked';
              } else {
                $selected = '';
              }
              echo "<input type='checkbox' $selected name='modifiedUser[]' value='".$rowUser['id']."'/>" . $rowUser['firstname'].' '.$rowUser['lastname'];
              echo '<br><br>';
            }
            ?>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
          <button type="submit" name="modifyUserIDs" value="<?php echo $row['id'];?>" class="btn btn-primary">Save changes</button>
        </div>
      </form>
    </div>
  </div>
</div>
<?php endwhile; ?>

<br><br><br>
<div class="row">
  <div class="col-sm-4">
    <a href="templateEdit.php" class="btn btn-warning"><?php echo $lang['NEW_TEMPLATE']; ?></a>
    <button class="btn btn-warning" type="button" data-toggle="collapse" data-target="#importTemplate" aria-expanded="false" aria-controls="collapseExample">
      <i class='fa fa-upload'></i> <i class="fa fa-caret-down"></i>
    </button>
  </div>
  <div  class="col-sm-8">
    <div class="collapse" id="importTemplate">
        <form action="templateUpload.php" method="post" enctype="multipart/form-data">
          <div class="col-md-8">
            <input type="file" name="fileToUpload" id="fileToUpload">
          </div>
          <div class="col-md-4">
            <button type="submit" class="btn btn-warning" name="templateUpload">Upload</button>
          </div>
        </form>
    </div>
  </div>
</div>
<br><br><br>

<hr><h4 style="color:grey; font-weight:bold;"><?php echo $lang['PREVIEW']; ?>:</h4><hr>

<div class="container text-center">
  <?php  echo $templatePreview;  ?>
</div>

<script>
  function resizeIframe(obj) {
    obj.style.height = obj.contentWindow.document.body.scrollHeight + 'px';
  }
</script>

<?php include 'footer.php'; ?>
