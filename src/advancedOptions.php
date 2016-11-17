<?php include 'header.php'; ?>
<?php include 'validate.php'; enableToCore($userID);?>
<!-- BODY -->

<div class="page-header">
  <h3><?php echo $lang['ADVANCED_OPTIONS']; ?></h3>
</div>

<form method=post>
  <?php

  if(isset($_POST['gitSubmit'])){
    if(isset($_POST['ssl'])){
      $status = 'TRUE';
    } else {
      $status = 'FALSE';
    }
    $sql = "UPDATE $adminGitHubTable SET sslVerify = '$status'";
    $conn->query($sql);
  }

  if(isset($_POST['terminalSubmit'])){
    if(isset($_POST['userAgent'])){
      $status = $_POST['userAgent'];
      $sql = "UPDATE $piConnTable SET header = '$status'";
      $conn->query($sql);
    }
  }
  ?>

  <h4>GitHub</h4>
  <div class="container">
      <br>
      <?php
      $sql = "SELECT * FROM $adminGitHubTable";
      $result = $conn->query($sql);
      $row = $result->fetch_assoc();
      $checked = $row['sslVerify']=='TRUE'?'checked':'';
      ?>

      <div class="input-group">
        <span class="input-group-addon">
        <input <?php echo $checked; ?> type='checkbox' name='ssl' value='TRUE'>
        </span>
          <input type="text" class="form-control" aria-label="..." readonly value="SSL Certificate Validation">

      </div>

      <br>

    <button type="submit" class="btn btn-warning" name= "gitSubmit">Save</button><br>
  </div>

<br><br>

<h4>Terminal</h4>

<div class="container">
  <br>
  <?php
  $sql = "SELECT * FROM $piConnTable";
  $result = $conn->query($sql);
  $row = $result->fetch_assoc();
  $myAgent = $row['header'];
  ?>
  <div class="input-group">
    <span class="input-group-addon"> User-Agent </span>
    <input type=text class="form-control" name=userAgent value="<?php echo $myAgent; ?>" >

  </div>
  <br>
  <button type="submit" class="btn btn-warning" name="terminalSubmit">Save</button><br>
</div>

</form>

<!-- /BODY -->
<?php include 'footer.php'; ?>
