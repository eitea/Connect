<?php include dirname(__DIR__) . '/header.php';

if(empty($_GET['cmp']) || !in_array($_GET['cmp'], $available_companies)){ include dirname(__DIR__) . '/footer.php'; die("Invalid Access");}
$cmpID = intval($_GET['cmp']);
$isUpdate = false;
?>
<div class="page-header">
  <h3><?php echo $lang['ARTICLE']; ?></h3>
</div>

<?php
if($_SERVER['REQUEST_METHOD'] == 'POST'){
  if(isset($_POST['update'])){
    $isUpdate = true;
    $articleID = intval($_POST['update']);
    $update_row = $conn->query("SELECT * FROM articles WHERE id = $articleID");
    $update_row = $update_row->fetch_assoc();
  } elseif(isset($_POST['add_product']) && !empty($_POST['add_product_name']) && !empty($_POST['add_product_price']) && !empty($_POST['isUpdate'])){
    $articleID = $_POST['isUpdate'];
    $product_name = test_input($_POST['add_product_name']);
    $product_description = test_input($_POST['add_product_description']);
    $product_price = floatval($_POST['add_product_price']);
    $product_unit = test_input($_POST['add_product_unit']);
    $product_tax_id = intval($_POST['add_product_taxes']);
    $product_is_cash = empty($_POST['add_product_as_bar']) ? 'FALSE' : 'TRUE';
    $product_purchase = floatval($_POST['add_product_purchase']);
    $product_company = $cmpID;

    $mc = new MasterCrypt($_SESSION["masterpassword"]);
    $iv = $mc->iv;
    $iv2 = $mc->iv2;
    $conn->query("UPDATE articles SET companyID = $product_company, name = '".$mc->encrypt($product_name)."', description = '".$mc->encrypt($product_description)."', price = '$product_price', unit = '$product_unit', taxID =  $product_tax_id, cash = '$product_is_cash', purchase = '$product_purchase', iv = '$iv', iv2 = '$iv2' WHERE id = $articleID");
    if(mysqli_error($conn)){
      echo $conn->error;
    } else {
      echo '<div class="alert alert-success"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$lang['OK_CREATE'].'</div>';
    }
  } elseif(isset($_POST['delete'])){
    $articleID = intval($_POST['delete']);
    $conn->query("DELETE FROM articles WHERE id = $articleID");
    if($conn->error){ echo $conn->error; } else { echo '<div class="alert alert-success"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$lang['OK_DELETE'].'</div>'; }
  } elseif(isset($_POST['add_product']) && !empty($_POST['add_product_name']) && !empty($_POST['add_product_price'])){
    $product_name = test_input($_POST['add_product_name']);
    $product_description = test_input($_POST['add_product_description']);
    $product_price = floatval($_POST['add_product_price']);
    $product_unit = test_input($_POST['add_product_unit']);
    $product_tax_id = intval($_POST['add_product_taxes']);
    $product_is_cash = empty($_POST['add_product_as_bar']) ? 'FALSE' : 'TRUE';
    $product_purchase = floatval($_POST['add_product_purchase']);
    $product_company = $cmpID;

    $mc = new MasterCrypt($_SESSION["masterpassword"]);
    $iv = $mc->iv;
    $iv2 = $mc->iv2;
    $conn->query("INSERT INTO articles (companyID, name, description, price, unit, taxID, cash, purchase, iv, iv2) VALUES($product_company, '".$mc->encrypt($product_name)."', '".$mc->encrypt($product_description)."', '$product_price', '$product_unit', $product_tax_id, '$product_is_cash', '$product_purchase', '$iv', '$iv2')");
    if(mysqli_error($conn)){
      echo $conn->error;
    } else {
      echo '<div class="alert alert-success"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$lang['OK_CREATE'].'</div>';
    }
  } elseif (isset($_POST['add_product'])){
    echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$lang['ERROR_MISSING_FIELDS'].'</div>';
  }
}
?>
<form method="POST">
  <table class="table table-hover">
    <thead>
      <th>Name</th>
      <th><?php echo $lang['DESCRIPTION']; ?></th>
      <th><?php echo $lang['PRICE_STK']; ?></th>
      <th><?php echo $lang['PURCHASE_PRICE']; ?></th>
      <th><?php echo $lang['CASH_EXPENSE']; ?></th>
      <th><?php echo $lang['TAXES']; ?></th>
      <th></th>
    </thead>
    <tbody>
      <?php
      $result = $conn->query("SELECT articles.*, taxRates.percentage, taxRates.description AS taxName FROM articles, taxRates WHERE companyID = $cmpID AND taxID = taxRates.id");
      while($result && ($row = $result->fetch_assoc())){
        $mc = new MasterCrypt($_SESSION["masterpassword"], $row['iv'],$row['iv2']);
        echo '<tr>';
        echo '<td>'.$mc->getStatus().$mc->decrypt($row['name']).'</td>';
        echo '<td>'.$mc->getStatus().$mc->decrypt($row['description']).'</td>';
        echo '<td>'.number_format($row['price'],2,',','.').'</td>';
        echo '<td>'.number_format($row['purchase'],2,',','.').'</td>';
        echo $row['cash'] == 'TRUE' ? '<td>'.$lang['YES'].'</td>' : '<td>'.$lang['NO'].'</td>';
        echo '<td>'.$row['taxName'].' '.$row['percentage'].'%</td>';
        echo '<td><button type="submit" class="btn btn-danger" name="delete" value="'.$row['id'].'" ><i class="fa fa-trash-o"></i></button>';
        echo '<button type="submit" name="update" value="'.$row['id'].'" class="btn btn-default" title="Bearbeiten"><i class="fa fa-cog"></i></button> ';
        echo '</tr>';
      }
      ?>
    </tbody>
  </table>
  <div class="text-right"><br>
    <button id="addArticle" type="button" class="btn btn-warning" data-toggle="modal" data-target=".add_product"><i class="fa fa-plus"></i> <?php echo $lang['ADD']; ?></button>
  </div>
</form>

<form method="POST">
  <div class="modal fade add_product">
    <div class="modal-dialog modal-md modal-content">
      <div class="modal-header">
        <h4 class="modal-title"><?php echo $lang['ADD']; ?></h4>
      </div>
      <div class="modal-body">
        <label>Name<?php echo mc_status(); ?></label>
        <input <?php if($isUpdate) echo 'value="'.$update_row['name'].'" ' ?> type="text" class="form-control required-field" name="add_product_name" placeholder="Name" maxlength="48"/>
        <br>
        <label><?php echo $lang['DESCRIPTION']; ?><?php echo mc_status(); ?></label>
        <textarea class="form-control" style='resize:none;overflow:hidden' rows="3" name="add_product_description" maxlength="350"><?php if($isUpdate) echo $update_row['description'] ?></textarea>
        <br>
        <div class="row">
          <div class="col-md-3">
            <label><?php echo $lang['PURCHASE_PRICE']; ?></label>
            <input <?php if($isUpdate) echo 'value="'.$update_row['purchase'].'" ' ?> id="product_purchase" type="number" step='0.01' class="form-control" name="add_product_purchase" placeholder="EUR" />
          </div>
          <div class="col-md-1"><label>+</label></div>
          <div class="col-md-3">
            <label><?php echo $lang['ADDITION']; ?> %</label>
            <input id="salePercent" type="number" step='1' class="form-control" placeholder="zzgl %">
          </div>
          <div class="col-md-1"><label>=</label></div>
          <div class="col-md-4">
            <label><?php echo $lang['PRICE_STK']; ?></label>
            <input <?php if($isUpdate) echo 'value="'.$update_row['price'].'" ' ?> id="product_price" type="number" step="0.01" class="form-control required-field" name="add_product_price" placeholder="EUR" />
          </div>
        </div>
        <br><br>
        <div class="row">
          <div class="col-md-4">
            <label><?php echo $lang['TAXES']; ?></label>
            <select class="js-example-basic-single btn-block" name="add_product_taxes">
              <?php
              $tax_result = $conn->query("SELECT * FROM taxRates");
              while($tax_result && ($tax_row = $tax_result->fetch_assoc())){
                $selected = '';
                if($tax_row['id'] == 3) $selected = 'selected'; 
                echo '<option '.$selected.' value="'.$tax_row['id'].'" >'.$tax_row['description'].' - '.$tax_row['percentage'].'% </option>';
              }
              ?>
            </select>
          </div>
          <div class="col-md-4">
            <label><?php echo $lang['UNIT']; ?></label>
            <select class="js-example-basic-single" name="add_product_unit">
              <?php
              $unit_result = $conn->query("SELECT * FROM units");
              while($unit_result && ($unit_row = $unit_result->fetch_assoc())){
                echo '<option value="'.$unit_row['unit'].'" >'.$unit_row['name'].'</option>';
              }
              ?>
            </select>
          </div>
          <div class="col-md-4 checkbox">
            <label><input <?php if($isUpdate && $update_row['cash']=="TRUE") echo 'checked' ?> type="checkbox" name="add_product_as_bar" value="TRUE" /><?php echo $lang['CASH_EXPENSE']; ?></label>
          </div>
              <?php if($isUpdate) echo '<input id="isUpdate" type="number" style="visibility:hidden; height: 1px; width: 1px;" value="'.$update_row['id'].'" name="isUpdate" />' ?>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" <?php if($isUpdate) echo 'onClick="window.location = \'../erp/articles?cmp='.$update_row['companyID'].'\'"' ?> data-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-warning" name="add_product"><?php echo $isUpdate ? $lang['EDIT'] : $lang['ADD'] ?></button>
      </div>
    </div>
  </div>
</form>

<script>
$("#product_price").on("keyup", function(){
  var v = parseFloat($("#product_price").val());
  var p = parseInt($("#salePercent").val());
  var e = parseFloat($("#product_purchase").val());
  if(e){ //v and e yield p
    $("#salePercent").val(Math.round((-1 + v/e) * 10000) / 100);
  } else if(p){ //v and b yield e
    $("#product_purchase").val(Math.round(10000 * v / (p + 100)) / 100);
  }
});

$("#salePercent").on("keyup", function(){
  var v = parseFloat($("#product_price").val());
  var p = parseInt($("#salePercent").val());
  var e = parseFloat($("#product_purchase").val());
  if(e){ //yields v
    $("#product_price").val(Math.round(100 * e + e*p) / 100);
  } else if(v){
    $("#product_purchase").val(Math.round(10000 * v / (p + 100)) / 100);
  }
});

$("#product_purchase").on("keyup", function(){
  var v = parseFloat($("#product_price").val());
  var p = parseInt($("#salePercent").val());
  var e = parseFloat($("#product_purchase").val());
  if(p){
    $("#product_price").val(Math.round(100 * e + e*p) / 100);
  }else if(v){
    $("#salePercent").val(Math.round((-1 + v/e) * 10000) / 100);
  }
});

</script>
<?php 
  if($isUpdate){
    echo "<script>document.getElementById('addArticle').click()</script>";
  }

include dirname(__DIR__) . '/footer.php'; ?>
