<?php require 'header.php'; enableToERP($userID); ?>
<?php
$clientID = $proposalID = 0;
if(isset($_POST['new_proposal'])){
  $clientID = intval($_POST['new_proposal']);
}

if($proposalID){
  $result = $conn->query("SELECT * FROM proposals WHERE id = $proposalID");
  $row = $result->fetch_assoc();
  $id = $row['id'];
  $id_num = $row['id_number'];
} else {
  $id = 0;
  $id_num = substr(date('Ymd', strtotime(getCurrentTimestamp())), 2, 6);

}
?>
<div class="page-header">
  <h3><?php echo $lang['OFFER'] .' - '. $lang['EDIT'].": <small>$id_num</small>"; ?></h3>
</div>
<form method="POST">
</form>
<?php require 'footer.php';?>
