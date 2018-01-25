<?php include dirname(dirname(__DIR__)) . '/header.php'; denyToContainer(); ?>
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

    $repositoryPath =  dirname(dirname(dirname(__DIR__)));

    if(!$result || $result->num_rows <= 0){ //sslVerify is False -> disable
      $command = 'git -C ' .$repositoryPath. ' config http.sslVerify "false" 2>&1';
      exec($command, $output, $returnValue);
      echo implode('<br>', $output) .'<br><br>';
    }

    $command = "git -C $repositoryPath pull 2>&1";
    exec($command, $output, $returnValue);

    echo implode('<br>', $output);
    session_destroy();
    echo "<br><br><a href='../login/auth' class='btn btn-warning'>O.K & Continue</a></form>";

    die($lang['LOGOUT_MESSAGE']);
  }
  echo $lang['DO_YOU_REALLY_WANT_TO_UPDATE'] .'<br><br>';
  echo $lang['MAY_TAKE_A_WHILE'] .'<br><br>';
  ?>

  <input type="submit" name="imtotallyFineWithThisOK" class="btn btn-warning" value="<?php echo $lang['YES_I_WILL']?>" />
</form>

<!-- /BODY -->
<?php include dirname(dirname(__DIR__)) . '/footer.php'; ?>
