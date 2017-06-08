<?php require 'header.php'; enableToCore($userID); ?>
<?php
if($_SERVER['REQUEST_METHOD'] == 'POST'){
  if(isset($_POST['save'])){
    for($i = 0; $i < count($_POST['ids']); $i++){
      $x = $_POST['ids'][$i];
      $nam = test_input($_POST['name'][$i]);
      $un = test_input($_POST['unit'][$i]);
      if($nam && $un){
        $conn->query("UPDATE units SET name = '$nam', unit = '$un' WHERE id = $x");
      } else {
        echo '<div class="alert alert-danger"><a class="close" data-dismiss="alert">&times;</a>'.$lang['ERROR_INVALID_DATA'].'</div>';
      }
    }
    if($conn->error){ echo $conn->error; } else { echo '<div class="alert alert-success"><a href="#" data-dismiss="alert">&times;</a>'.$lang['OK_SAVE'].'</div>'; }
  } elseif(isset($_POST['add']) && !empty($_POST['add_name']) && !empty($_POST['add_unit'])){
    $nam = test_input($_POST['add_name']);
    $un = test_input($_POST['add_unit']);
    $conn->query("INSERT INTO units (name, unit) VALUES ('$nam', '$un')");
    if($conn->error){ echo $conn->error; } else { echo '<div class="alert alert-success"><a href="#" data-dismiss="alert">&times;</a>'.$lang['OK_ADD'].'</div>'; }
  } elseif(isset($_POST['delete']) && !empty($_POST['deleteIDs'])){
    foreach($_POST['deleteIDs'] as $x){
      $conn->query("DELETE FROM units WHERE id = $x");
    }
  }
}
?>
<div class="page-header">
  <h3><?php echo $lang['UNITS']; ?></h3>
</div>
<form method="POST">
  <div class="container-fluid">
    <div class="col-md-6">
      <table class="table table-hover">
        <thead>
          <th><?php echo $lang['DELETE']; ?></th>
          <th>Name</th>
          <th><?php echo $lang['UNIT']; ?></th>
          <th></th>
        </thead>
        <tbody>
          <?php
          $result = $conn->query("SELECT * FROM units");
          while ($result && ($row = $result->fetch_assoc())) {
            echo '<tr>';
            echo '<td><input type="checkbox" name="deleteIDs[]" value="'.$row['id'].'" /></td>';
            echo '<td><input type="text" name="name[]" value="'.$row['name'].'" class="form-control" maxlength="20" /></td>';
            echo '<td><input type="text" name="unit[]" value="'.$row['unit'].'" class="form-control" maxlength="10" /></td>';
            echo '<td><input type="hidden" name="ids[]" value="'.$row['id'].'" /></td>';
            echo '</tr>';
          }
          ?>
        </tbody>
      </table><br>
      <button type="submit" name="delete" class="btn btn-danger"><?php echo $lang['DELETE']; ?></button>
      <button type="submit" name="save" class="btn btn-warning"><?php echo $lang['SAVE']; ?></button><br>
    </div>
    <div class="col-md-5 col-md-offset-1"><br>
      <label><?php echo $lang['ADD']; ?>: </label>
      <input type="text" name="add_name" class="form-control" maxlength="20" placeholder="Name" /><br>
      <input type="text" name="add_unit" class="form-control" maxlength="20" placeholder="<?php echo $lang['UNIT']; ?>" /><br>
      <button type="submit" name="add" class="btn btn-warning"><?php echo $lang['ADD']; ?></button>
    </div>
  </div>
</form>
<?php include 'footer.php'; ?>
