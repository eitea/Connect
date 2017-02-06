<?php require "header.php"; ?>

<div class="page-header">
  <h3> E-mail <?php echo $lang['REPORTS']; ?></h3>
</div>

<?php
$result = $conn->query("SELECT * FROM $mailOptionsTable");
$row = $result->fetch_assoc();
if(isset($_POST['addRecipient']) && !empty($_POST['recipientMail'])){
  if(filter_var($_POST['recipientMail'], FILTER_VALIDATE_EMAIL)){
    $reportID = $_POST['addRecipient'];
    $mail = test_input($_POST['recipientMail']);
    $conn->query("INSERT INTO $mailReportsRecipientsTable(reportID, email) VALUES($reportID, '$mail')");
    echo mysqli_error($conn);
  } else {
    echo '<div class="alert alert-danger fade in"><a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>Invalid E-Mail Address.</div>';
  }
} elseif (isset($_POST['removeRecipient'])) {
  $recipID = $_POST['removeRecipient'];
  $conn->query("DELETE FROM $mailReportsRecipientsTable WHERE id = $recipID");
}
?>

<div class="text-center container">
  <div class="col-md-6">
    Host: <?php echo $row['host']; ?>
  </div>
  <div class="col-md-6">
    Port:  <?php echo $row['port']; ?>
  </div>
</div>
<hr><br><br>
<form method="POST">
  <table class="table table-hover">
    <thead>
      <th><?php echo $lang['OPTIONS']; ?></th>
      <th>Name</th>
      <th>Time</th>
      <th>Recipients</th>
      <th>Add E-Mail</th>
      <th></th>
    </thead>
    <tbody>
      <?php
      $result = $conn->query("SELECT * FROM $mailReportsTable");

      while ($result && ($row = $result->fetch_assoc())){
        $templID = $row['id'];
        $result2 = $conn->query("SELECT * FROM $mailReportsRecipientsTable WHERE reportID = $templID");
        $recipients = "";
        while($result2 && ($row2 = $result2->fetch_assoc())){
          $recipients .= '<button type="submit" style="background:none;border:none" name="removeRecipient" value="'.$row['id'].'"><img width="10px" height="10px" src="../images/minus_circle.png"></button>'.$row2['email'] . '<br>';
        }
        echo '<tr>';
        echo '<td>';
        echo "<button type='submit' class='btn btn-default' name='deleteReport' value='$templID' title='Delete'> <i class='fa fa-trash-o'></i></button> ";
        echo "<a href='templateEdit_Emails.php?id=$templID' class='btn btn-default' title='Edit'> <i class='fa fa-pencil'></i></a> ";
        echo '</td>';
        echo '<td>' . $row['name'] . '</td>';
        echo "<td></td>";
        echo "<td>$recipients</td>";
        echo '<td><input type="text" name="recipientMail" class="form-control"/></td>';
        echo "<td><button type='submit' class='btn btn-warning btn-small' name='addRecipient' value='$templID'>+</button></td>";
        echo '</tr>';
      }
      ?>
    </tbody>
  </table>
  <br><br><br>
  <a href="templateEdit_Emails.php" class="btn btn-warning" > New Report </a>
</form>

<?php require "footer.php"; ?>
