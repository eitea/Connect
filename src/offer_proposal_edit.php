<?php require 'header.php'; enableToERP($userID); ?>
<?php
$filterCompany = $filterClient = $filterProposal = 0;
$id_num = 'AT'.substr(strtotime(getCurrentTimestamp()), 2, 8);
if(isset($_POST['new_proposal'])){
  $clientID = intval($_POST['new_proposal']);
}
if(isset($_POST['edit_proposal'])){
  $filterProposal = intval($_POST['edit_proposal']);
  $result = $conn->query("SELECT clientData.* FROM clientData, proposals WHERE proposals.id = $filterProposal AND proposals.clientId = clientData.id");
  if($row = $result->fetch_assoc()){
    $filterClient = $row['id'];
    $filterCompany = $row['companyID'];
  }
}

if($_SERVER['REQUEST_METHOD'] == 'POST'){
  if(isset($_POST['filterCompany'])){
    $filterCompany = $_POST['filterCompany'];
  }
  if(isset($_POST['filterClient'])){
    $filterClient = $_POST['filterClient'];
  }
  if(isset($_POST['filterProposal'])){
    $filterProposal = $_POST['filterProposal'];
  }
  if(isset($_POST['add_product']) && ($filterClient || $filterProposal) && $_POST['add_product_quantity'] > 0 && $_POST['add_product_price'] > 0){
    $product_quantity = floatval($_POST['add_product_quantity']);
    $product_price = floatval($_POST['add_product_price']);
    if($_POST['select_new_product'] && $_POST['select_new_product_true']){
      $i = intval($_POST['select_new_product_true']);
      $result = $conn->query("SELECT name, description FROM products WHERE id = $i");
      $row = $result->fetch_assoc();
      $product_name = $row['name'];
      $product_description = $row['description'];
    } elseif($_POST['add_product_name']) {
      $product_name = test_input($_POST['add_product_name']);
      $product_description = test_input($_POST['add_product_description']);
    } else {
      $product_name = 0;
      echo '<div class="alert alert-danger fade in">';
      echo '<a href="userProjecting.php" class="close" data-dismiss="alert" aria-label="close">&times;</a>';
      echo '<strong>Could not create entry: </strong>Missing Information. Select a product or enter a valid name';
      echo '</div>';
    }
    if($product_name){
      if(!$filterProposal){ //new proposal
        $conn->query("INSERT INTO proposals (id_number, clientID, status) VALUES ('$id_num', $filterClient, '0')");
        $filterProposal = mysqli_insert_id($conn);
      }
      $conn->query("INSERT INTO products (proposalID, name, price, quantity, description) VALUES($filterProposal, '$product_name', '$product_price', '$product_quantity', '$product_description')");
      echo mysqli_error($conn);
    }
  } elseif(isset($_POST['add_product'])){
    echo '<div class="alert alert-danger fade in">';
    echo '<a href="userProjecting.php" class="close" data-dismiss="alert" aria-label="close">&times;</a>';
    echo '<strong>Could not create entry: </strong>Missing Information. Please fill out every textfield';
    echo '</div>';
  }
  if(!empty($_POST['delete_product']) && isset($_POST['delete_selection'])){
    foreach($_POST['delete_product'] as $i){
      $conn->query("DELETE FROM products WHERE id = $i");
    }
  }
}

if($filterProposal){
  $result = $conn->query("SELECT * FROM proposals WHERE id = $filterProposal");
  $row = $result->fetch_assoc();
  $id = $row['id'];
  $id_num = $row['id_number'];
}
?>
<div class="page-header">
  <h3><?php echo $lang['OFFER'] .' - '. $lang['EDIT']." <small>$id_num</small>"; ?></h3>
</div>

<form method="POST">
  <div class="container-fluid">
    <div class="col-md-8">
      <select style='width:200px' name="filterCompany" class="js-example-basic-single" onchange="showClients(this.value, '<?php echo $filterClient; ?>');">
        <?php
        $sql = "SELECT * FROM companyData WHERE id IN (".implode(', ', $available_companies).")";
        $result = mysqli_query($conn, $sql);
        if($result && $result->num_rows > 1) {
          echo '<option value="0">Select Company...</option>';
        } else {
          $filterCompany = $available_companies[1];
        }
        while($result && ($row = $result->fetch_assoc())){
          $checked = '';
          if($filterCompany == $row['id']) {
            $checked = 'selected';
          }
          echo "<option $checked value='".$row['id']."' >".$row['name']."</option>";
        }
        ?>
      </select>
      <select id="clientHint" style='width:200px' class="js-example-basic-single" name="filterClient" onchange="showProposals(this.value,'<?php echo $filterProposal; ?>');">
      </select>
      <select id="proposalHint" style='width:200px' class="js-example-basic-single" name="filterProposal">
      </select>
      <button type="submit" class="btn btn-warning " name="apply_filter">Filter</button>
    </div>
  </div>

  <br><br><br>
  <table class="table">
    <thead>
      <th><?php echo $lang['DELETE']; ?></th>
      <th>Name</th>
      <th><?php echo $lang['DESCRIPTION']; ?></th>
      <th><?php echo $lang['PRICE_STK']; ?></th>
      <th><?php echo $lang['QUANTITY']; ?></th>
    </thead>
    <tbody>
      <?php
      $result = $conn->query("SELECT * FROM products WHERE proposalID = $filterProposal");
      while($result && ($row = $result->fetch_assoc())){
        echo '<tr>';
        echo '<td><input type="checkbox" name="delete_product[]" value="'.$row['id'].'" /></td>';
        echo '<td>'.$row['name'].'</td>';
        echo '<td>'.$row['description'].'</td>';
        echo '<td>'.$row['price'].'</td>';
        echo '<td>'.$row['quantity'].'</td>';
        echo '</tr>';
      }
      ?>
    </tbody>
  </table>
  <br>
  <div class="container-fluid">
    <button type="submit" class="btn btn-warning" name="delete_selection"><?php echo $lang['DELETE']; ?></button>
  </div>

  <?php if($filterClient): ?>
    <br><hr>
    <h5><?php echo $lang['ADD'] .': '; ?></h5>
    <hr><br>
    <div class="container-fluid">
      <div class="col-md-1"><label><?php echo $lang['EXISTING']; ?>:</label></div>
      <div class="col-md-1"><input type="radio" name="select_new_product" value="1"></div>
      <div class="col-md-3">
        <select class="js-example-basic-single" name="select_new_product_true" style="width:200px">
          <option value="0"><?php echo $lang['PRODUCTS']; ?> ...</option>
          <?php
          $result = $conn->query("SELECT products.* FROM products, clientData WHERE clientData.companyID = $filterCompany GROUP BY(name)");
          while($result && ($row = $result->fetch_assoc())){
            echo "<option value='".$row['id']."'>".$row['name']."</option>";
          }
          ?>
        </select>
      </div>
    </div>
    <br>
    <div class="container-fluid">
      <div class="col-md-1"><label><?php echo $lang['NEW']; ?>:</label></div>
      <div class="col-md-1"><input type="radio" name="select_new_product" value="1" checked></div>
      <div class="col-md-4">
        <input type="text" class="form-control" name="add_product_name" placeholder="New Product Name" maxlength="48"/>
      </div>
      <div class="col-md-6">
        <input type="text" class="form-control" name="add_product_description" placeholder="Product Description" maxlength="190"/>
      </div>
    </div>
    <br><br>
    <div class="container-fluid">
      <div class="col-md-2 col-md-offset-2">
        <input type="number" class="form-control required-field" name="add_product_quantity" placeholder="Quantity" />
      </div>
      <div class="col-md-2">
        <input type="number" step="any" class="form-control required-field" name="add_product_price" placeholder="Unit Price" />
      </div>
      <div class="col-md-1">
        <button type="submit" class="btn btn-warning" name="add_product">+</button>
      </div>
    </div>
  <?php endif; ?>
</form>

<br><hr><br>
<?php if($filterProposal): ?>
  <div class="container-fluid text-right">
    <form method="POST" action="download_proposal.php">
      <button class="btn btn-warning" value='<?php echo $filterProposal; ?>' name='download_proposal'>PDF Download</button>
    </form>
  </div>
<?php endif; ?>

<script>
function showClients(company, client){
  if(company != ""){
    $.ajax({
      url:'ajaxQuery/AJAX_getClient.php',
      data:{companyID:company, clientID:client},
      type: 'get',
      success : function(resp){
        $(clientHint).html(resp);
      },
      error : function(resp){}
    });
  }
}
function showProposals(clientID, proposalID){
  if(clientID != ""){
    $.ajax({
      url:'ajaxQuery/AJAX_getProposals.php',
      data:{client:clientID, p:proposalID},
      type: 'get',
      success : function(resp){
        $(proposalHint).html(resp);
      },
      error : function(resp){}
    });
  }
}
</script>
<?php
if($filterCompany){
  echo '<script>';
  echo "showClients('$filterCompany', '$filterClient');";
  echo "showProposals('$filterClient', '$filterProposal');";
  echo '</script>';
}
?>
<?php require 'footer.php';?>
