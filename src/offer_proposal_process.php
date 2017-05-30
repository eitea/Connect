<?php require 'header.php'; enableToERP($userID); ?>

<div class="page-header">
  <h3><?php echo $lang['CHOOSE_PROCESS']; ?></h3>
</div>

<?php if(!empty($_POST['filterClient'])): ?>
    <h4>Step 2 - Vorgang auswählen</h4>
    <br><br>
    <div class="container text-center">
      <form method="POST" action="offer_proposal_edit.php">
        <button class="btn btn-default" name="filterClient" value="<?php echo $_POST['filterClient']; ?>"><?php echo $lang['NEW_PROPOSAL']; ?></button>
      </form>
      <br>
      <form method="POST" action="offer_proposal_edit.php?nERP=AUB">
        <button class="btn btn-default" name="filterClient" value="<?php echo $_POST['filterClient']; ?>"><?php echo $lang['NEW_CONFIRMATION']; ?></button>
      </form><br>
      <form method="POST" action="offer_proposal_edit.php?nERP=RE">
        <button class="btn btn-default" name="filterClient" value="<?php echo $_POST['filterClient']; ?>"><?php echo $lang['NEW_RECEIPT']; ?></button>
      </form>
    </div>
<?php else: ?>
  <form method="POST">
   <div class="container-fluid">
     <h4>Step 1 - Kunden auswählen</h4>
     <br><br>
     <?php
     $result = $conn->query("SELECT * FROM $clientTable WHERE companyID IN (".implode(', ', $available_companies).")");
     if(!$result || $result->num_rows <= 0){
       echo '<div class="alert alert-info">'.$lang['WARNING_NO_CLIENTS'].'<br><br>';
       include "new_client.php";
       echo '</div>';
     } else {
       ?>
       <div style="padding-left:20%">
         <?php include 'misc/select_client.php'; ?>
         <button type="submit" class="btn btn-warning" >OK</button>
       </div>
       <?php
     }
     ?>
   </div>
 </form>
<?php endif; ?>

<?php include 'footer.php'; ?>
