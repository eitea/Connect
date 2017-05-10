<?php require 'header.php'; enableToERP($userID); ?>

<?php

 ?>
<div class="page-header">
  <h3><?php echo $lang['CHOOSE_PROCESS']; ?></h3>
</div>

<div class="container text-center">
<a href="offer_proposal_edit.php" class="btn btn-default"><?php echo $lang['NEW_PROPOSAL']; ?></a>
<br><br>
<?php echo $lang['NEW_RECEIPT']; ?>
</div>
<?php include 'footer.php'; ?>
