<?php include 'header.php'; ?>
<!-- BODY -->

<div class="page-header">
  <h3><?php echo $lang['CREATE_NEW_COMPANY']; ?></h3>
</div>

<?php
if($_SERVER["REQUEST_METHOD"] == "POST"){
  if(isset($_POST['compaCreate']) && !empty($_POST['compaName']) && $_POST['compaType'] != "0"){
    $compaName = test_input($_POST['compaName']);
    $type = test_input($_POST['compaType']);
    $conn->query("INSERT INTO $companyTable (name, companyType) VALUES('$compaName', '$type')");
    if(!mysqli_error($conn)){
      echo '<div class="alert alert-success fade in">';
      echo '<a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>';
      echo '<strong>O.K.: </strong>'.$lang['OK_CREATE'];
      echo '</div>';
    }
  } else {
    echo '<div class="alert alert-warning fade in">';
    echo '<a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>';
    echo '<strong>Cannot create new instance: </strong>Name or Type were not well defined.</div>';
  }
}
?>
<form method="post">
  <div class="container-fluid">
  <div class="col-md-6">
    <input type="text" class="form-control" name="compaName" placeholder="Name...">
  </div>
  <div class="col-md-4">
    <select name="compaType" class="js-example-basic-single btn-block">
      <option selected value="0">Typ...</option>
      <option value="GmbH">GmbH</option>
      <option value="AG">AG</option>
      <option value="OG">OG</option>
      <option value="KG">KG</option>
      <option value="EU">EU</option>
      <option value="-">Sonstiges</option>
    </select>
  </div>
  <div class="col-md-2 text-right">
    <button type="submit" class="btn btn-warning " name="compaCreate">Hinzufügen</button>
  </div>
</div>
</form>

<!-- /BODY -->
<?php include 'footer.php'; ?>
