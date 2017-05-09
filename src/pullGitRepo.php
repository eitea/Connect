<?php include 'header.php'; ?>
<?php enableToCore($userID);?>
<!-- BODY -->

<div class="page-header">
  <h3>Update</h3>
</div>

<form method="post">
  <?php
  if(isset($_POST['imtotallyFineWithThisOK'])){
    $output = '';
    $sql = "SELECT * FROM $adminGitHubTable WHERE sslVerify = 'TRUE'";
    $result = $conn->query($sql);

    $repositoryPath = dirname(dirname(realpath("pullGitRepo.php")));

    if(!$result || $result->num_rows <= 0){ //sslVerify is False -> disable, else do nothing
      $command = 'git -C ' .$repositoryPath. ' config http.sslVerify "false" 2>&1';
      exec($command, $output, $returnValue);
      echo implode('<br>', $output) .'<br><br>';
    }

    $command = "git -C $repositoryPath fetch --all 2>&1";
    exec($command, $output, $returnValue);

    $command = "git -C $repositoryPath reset --hard origin/master 2>&1";
    exec($command, $output, $returnValue);

    echo implode('<br>', $output);

    session_destroy();
    echo "<br><br><input type='submit' name='okey' value='O.K & Continue' /></form>";

    die($lang['LOGOUT_MESSAGE']);
  }
  echo $lang['DO_YOU_REALLY_WANT_TO_UPDATE'] .'<br><br>';
  echo $lang['MAY_TAKE_A_WHILE'] .'<br><br>';
  ?>

  <input type="submit" name="imtotallyFineWithThisOK" class="btn btn-warning" value="<?php echo $lang['YES_I_WILL']?>" />
</form>

<!-- /BODY -->
<?php include 'footer.php'; ?>
