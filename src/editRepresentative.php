<?php require 'header.php'; ?>
<?php
if($_SERVER['REQUEST_METHOD'] == 'POST'){
  if(isset($_POST['save'])){
    for($i = 0; $i < count($_POST['ids']); $i++){
      $x = $_POST['ids'][$i];
      $nam = test_input($_POST['name'][$i]);
      if($nam){
        $conn->query("UPDATE representatives SET name = '$nam' WHERE id = $x");
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
    $nam = test_input($_POST['add_name']);
    $conn->query("INSERT INTO representatives (name) VALUES ('$nam')");
    if($conn->error){
      echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$conn->error.'</div>';
    } else {
      echo '<div class="alert alert-success"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$lang['OK_ADD'].'</div>';
    }
  } elseif(isset($_POST['delete']) && !empty($_POST['deleteIDs'])){
    foreach($_POST['deleteIDs'] as $x){
      $conn->query("DELETE FROM representatives WHERE id = $x");
    }
  }
}
?>
<form method="POST">
  <div class="page-header">
    <h3><?php echo $lang['REPRESENTATIVE']; ?>
      <div class="page-header-button-group">
        <button type="button" class="btn btn-default" title="<?php echo $lang['ADD']; ?>" data-toggle="modal" data-target=".add_unit" ><i class="fa fa-plus"></i></button>
        <button type="submit" class="btn btn-default" title="<?php echo $lang['DELETE']; ?>" name="delete"><i class="fa fa-trash-o"></i></button>
        <button type="submit" class="btn btn-default" title="<?php echo $lang['SAVE']; ?>" name="save"><i class="fa fa-floppy-o"></i></button><br>
      </div>
    </h3>
  </div>
  <div class="container-fluid">
    <div class="col-md-6">
      <table class="table table-hover">
        <thead>
          <th><?php echo $lang['DELETE']; ?></th>
          <th>Name</th>
          <th></th>
        </thead>
        <tbody>
          <?php
          $result = $conn->query("SELECT * FROM representatives");
          while ($result && ($row = $result->fetch_assoc())) {
            echo '<tr>';
            echo '<td><input type="checkbox" name="deleteIDs[]" value="'.$row['id'].'" /></td>';
            echo '<td><input type="text" name="name[]" value="'.$row['name'].'" class="form-control" maxlength="100" /></td>';
            echo '<td><input type="hidden" name="ids[]" value="'.$row['id'].'" /></td>';
            echo '</tr>';
          }
          ?>
        </tbody>
      </table>
    </div>
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
        <input type="text" name="add_name" class="form-control" maxlength="50" placeholder="Name" /><br>
      </div>
      <div class="modal-footer">
        <button type="button"  class="btn btn-default" data-dismiss="modal" ><?php echo $lang['CANCEL']; ?></button>
        <button type="submit" name="add" class="btn btn-warning"><?php echo $lang['ADD']; ?></button>
      </div>
    </div>
  </div>
</form>
<?php include 'footer.php'; ?>
