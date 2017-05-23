<?php require 'header.php'; enableToERP($userID); ?>


<div class="page-header">
  <h3><?php echo $lang['CHOOSE_PROCESS']; ?></h3>
</div>

<div class="container text-center">
<a class="btn btn-default" data-toggle="modal" data-target=".select_client"><?php echo $lang['NEW_PROPOSAL']; ?></a>
<br><br>
<?php echo $lang['NEW_RECEIPT']; ?>
</div>

<form method="POST" action="offer_proposal_edit.php">
  <div class="modal fade select_client">
    <div class="modal-dialog modal-md modal-content"  role="document">
      <div class="modal-header">
        <h4><?php echo $lang['INFO_SELECT_CLIENT']; ?></h4>
      </div>
      <div class="modal-body">
        <?php include 'misc/select_client.php'; ?>
      </div>
      <div class="modal-footer">
        <button type="submit" class="btn btn-warning">OK</button>
      </div>
    </div>
  </div>
</form>
<?php include 'footer.php'; ?>
