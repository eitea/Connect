<?php include 'header.php'; ?>

<div class="page-header">
  <h3><?php echo $lang['ARTICLE']; ?></h3>
</div>

<?php
if($_SERVER['REQUEST_METHOD'] == 'POST'){
  if(isset($_POST['delete']) && !empty($_POST['delete_article'])){
    foreach($_POST['delete_article'] AS $articleID){
      $conn->query("DELETE FROM articles WHERE id = $articleID");
    }
  } elseif(isset($_POST['add_product']) && !empty($_POST['add_product_name']) && !empty($_POST['add_product_price'])){
    $product_name = test_input($_POST['add_product_name']);
    $product_description = test_input($_POST['add_product_description']);
    $product_price = floatval($_POST['add_product_price']);
    $product_tax_id = intval($_POST['add_product_taxes']);
    $product_unit = test_input($_POST['add_product_unit']);
    $conn->query("INSERT INTO articles (name, description, price, unit, taxPercentage) VALUES('$product_name', '$product_description', '$product_price', '$product_unit', $product_tax_id)");
    if(mysqli_error($conn)){
      echo $conn->error;
    } else {
      echo '<div class="alert alert-success fade in">';
      echo '<a href="userProjecting.php" class="close" data-dismiss="alert" aria-label="close">&times;</a>';
      echo '<strong>O.K.: </strong>'.$lang['OK_CREATE'];
      echo '</div>';
    }
  } elseif (isset($_POST['add_product'])){
    echo '<div class="alert alert-danger fade in">';
    echo '<a href="userProjecting.php" class="close" data-dismiss="alert" aria-label="close">&times;</a>';
    echo '<strong>Error: </strong>'.$lang['ERROR_MISSING_FIELDS'];
    echo '</div>';
  }
}
?>
<form method="post">
  <table class="table table-hover">
    <thead>
      <th></th>
      <th>Name</th>
      <th><?php echo $lang['DESCRIPTION']; ?></th>
      <th><?php echo $lang['PRICE_STK']; ?></th>
      <th><?php echo $lang['UNIT']; ?></th>
      <th><?php echo $lang['TAXES']; ?></th>
    </thead>
    <tbody>
      <?php
      $result = $conn->query("SELECT articles.*, taxRates.percentage, taxRates.description AS taxName FROM articles, taxRates WHERE taxPercentage = taxRates.id");
      while($result && ($row = $result->fetch_assoc())){
        echo '<tr>';
        echo '<td><input type="checkbox" name="delete_article[]" value="'.$row['id'].'" />';
        echo '<td>'.$row['name'].'</td>';
        echo '<td>'.$row['description'].'</td>';
        echo '<td>'.$row['price'].'</td>';
        echo '<td>'.$row['unit'].'</td>';
        echo '<td>'.$row['taxName'].' '.$row['percentage'].'%</td>';
        echo '</tr>';
      }
      ?>
    </tbody>
  </table>
  <br>
  <button type="submit" class="btn btn-danger" name="delete"><?php echo $lang['DELETE']; ?></button>
</form>

<form method="POST">
  <br><hr>
  <h5><?php echo $lang['ADD'] .': '; ?></h5>
  <hr><br>
  <div class="container-fluid">
    <div class="col-md-6">
      <input type="text" class="form-control required-field" name="add_product_name" placeholder="Name" maxlength="48"/>
    </div>
    <div class="col-md-6">
      <input type="text" class="form-control" name="add_product_description" placeholder="<?php echo $lang['DESCRIPTION']; ?>" maxlength="190"/>
    </div>
  </div>
  <br>
  <div class="container-fluid">
    <div class="col-md-3">
      <input type="number" step="any" class="form-control required-field" name="add_product_price" placeholder="<?php echo $lang['PRICE_STK']; ?>" />
    </div>
    <div class="col-md-3">
      <select class="js-example-basic-single" name="add_product_unit">
        <?php
        $unit_result = $conn->query("SELECT * FROM units");
        while($unit_result && ($unit_row = $unit_result->fetch_assoc())){
          echo '<option value="'.$unit_row['unit'].'" >'.$unit_row['name'].'</option>';
        }
        ?>
      </select>
    </div>
    <div class="col-md-4">
      <select class="js-example-basic-single btn-block" name="add_product_taxes">
        <?php
        $tax_result = $conn->query("SELECT * FROM taxRates WHERE percentage IS NOT NULL");
        while($tax_result && ($tax_row = $tax_result->fetch_assoc())){
          echo '<option value="'.$tax_row['id'].'" >'.$tax_row['description'].' - '.$tax_row['percentage'].'% </option>';
        }
        ?>
      </select>
    </div>
    <div class="col-md-2 text-right">
      <button type="submit" class="btn btn-warning" name="add_product"><?php echo $lang['ADD']; ?></button>
    </div>
  </div>
</form>

<?php include 'footer.php'; ?>
