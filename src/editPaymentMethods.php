<?php require 'header.php'; ?>
<?php
if($_SERVER['REQUEST_METHOD'] == 'POST'){
  if(isset($_POST['save'])){
    for($i = 0; $i < count($_POST['ids']); $i++){
      $x = $_POST['ids'][$i];
      $nam = test_input($_POST['name'][$i]);
      if($nam){
        $conn->query("UPDATE paymentMethods SET name = '$nam' WHERE id = $x");
      } else {
        echo '<div class="alert alert-danger"><a href="#" class="close" data-dismiss="alert">&times;</a>'.$lang['ERROR_INVALID_DATA'].'</div>';
      }
    }
    if($conn->error){
      echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$conn->error.'</div>';
    } else {
      echo '<div class="alert alert-success"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$lang['OK_SAVE'].'</div>';
    }
  } elseif(isset($_POST['add']) && !empty($_POST['add_name'])){
    $nam = $netto = $skonto1 = $skonto2 = $skonto1Days = $skonto2Days = 0;
    $nam = test_input($_POST['add_name']);
    $skonto1 = $_POST['add_skonto1'];
    $skonto2 = $_POST['add_skonto2'];
    $skonto1Days = $_POST['add_skonto1Days'];
    $skonto2Days = $_POST['add_skonto2Days'];
    $stmt = $conn->prepare("INSERT INTO paymentMethods (name, daysNetto, skonto1, skonto2, skonto1Days, skonto2Days) VALUES (?, ?, ?, ?, ?, ?) ");
    $stmt->bind_param("siddii", $nam, $netto, $skonto1, $skonto2, $skonto1Days, $skonto2Days);
    $stmt->execute();
    $stmt->close();
    if($conn->error){
      echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$conn->error.'</div>';
    } else {
      echo '<div class="alert alert-success"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$lang['OK_ADD'].'</div>';
    }
  } elseif(isset($_POST['delete']) && !empty($_POST['deleteIDs'])){
    foreach($_POST['deleteIDs'] as $x){
      $conn->query("DELETE FROM paymentMethods WHERE id = $x");
    }
    if($conn->error){
      echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$conn->error.'</div>';
    } else {
      echo '<div class="alert alert-success"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$lang['OK_DELETE'].'</div>';
    }
  } else {
    echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$lang['ERROR_MISSING_FIELDS'].'</div>';
  }
}
?>
<form method="POST">
  <div class="page-header">
    <h3><?php echo $lang['PAYMENT_METHODS']; ?>
      <div class="page-header-button-group">
        <button type="button" class="btn btn-default" title="<?php echo $lang['ADD']; ?>" data-toggle="modal" data-target=".add_unit" ><i class="fa fa-plus"></i></button>
        <button type="submit" class="btn btn-default" title="<?php echo $lang['DELETE']; ?>" name="delete"><i class="fa fa-trash-o"></i></button>
        <button type="submit" class="btn btn-default" title="<?php echo $lang['SAVE']; ?>" name="save"><i class="fa fa-floppy-o"></i></button><br>
      </div>
    </h3>
  </div>
  <div class="container-fluid">
    <table class="table table-hover">
      <thead>
        <th><?php echo $lang['DELETE']; ?></th>
        <th>Name</th>
        <th><?php echo $lang['TYPE']; ?></th>
        <th></th>
      </thead>
      <tbody>
        <?php
        $result = $conn->query("SELECT * FROM paymentMethods");
        while ($result && ($row = $result->fetch_assoc())) {
          echo '<tr>';
          echo '<td><input type="checkbox" name="deleteIDs[]" value="'.$row['id'].'" /></td>';
          echo '<td><input type="text" name="name[]" value="'.$row['name'].'" class="form-control" maxlength="100" /></td>';
          echo '<td>'.$row['daysNetto'].' '.$row['skonto1'].' '.$row['skonto1Days'].' '.$row['skonto2'].' '.$row['skonto2Days'].'</td>';
          echo '<td><input type="hidden" name="ids[]" value="'.$row['id'].'" /></td>';
          echo '</tr>';
        }
        ?>
      </tbody>
    </table>
  </div>
</form>
<?php include 'footer.php'; ?>

<form method="POST">
  <div class="modal fade add_unit">
    <div class="modal-dialog modal-content modal-md">
      <div class="modal-header">
        <h4><?php echo $lang['ADD']; ?></h4>
      </div>
      <div class="modal-body">
        <label>Name</label>
        <input type="text" name="add_name" class="form-control" maxlength="100" placeholder="z.B.: 3% Skonto 8 Tage, 30 Tage Netto" /><br>
        <div class="row">
          <div class="col-xs-2">Netto:</div>
          <div class="col-xs-3"><input type="number" class="form-control" name="add_daysNetto" placeholder="0" /></div>
          <div class="col-xs-2">Tage</div>
        </div>
        <br>
        <div class="row">
          <div class="col-xs-2">Skonto 1:</div>
          <div class="col-xs-3"><input type="number" step="0.01" class="form-control" name="add_skonto1" placeholder="0" /></div>
          <div class="col-xs-2 text-center">% Innerhalb von</div>
          <div class="col-xs-3"><input type="number" class="form-control" name="add_skonto1Days" placeholder="0" /></div>
          <div class="col-xs-1">Tagen</div>
        </div>
        <br>
        <div class="row">
          <div class="col-xs-2">Skonto 2:</div>
          <div class="col-xs-3"><input type="number" step="0.01" class="form-control" name="add_skonto2" placeholder="0" /></div>
          <div class="col-xs-2 text-center">% Innerhalb von</div>
          <div class="col-xs-3"><input type="number" class="form-control" name="add_skonto2Days" placeholder="0" /></div>
          <div class="col-xs-1">Tagen</div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button"  class="btn btn-default" data-dismiss="modal" ><?php echo $lang['CANCEL']; ?></button>
        <button type="submit" name="add" class="btn btn-warning"><?php echo $lang['ADD']; ?></button>
      </div>
    </div>
  </div>
</form>
<?php include 'footer.php'; ?>
