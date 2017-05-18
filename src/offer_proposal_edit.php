<?php require 'header.php'; enableToERP($userID); ?>
<?php
$filterCompany = $filterClient = $filterProposal = 0;
$meta_curDate = $meta_deliveryDate = $meta_yourSign = $meta_yourOrder = $meta_ourSign = $meta_ourMessage = $meta_daysNetto = '';
$meta_skonto1 = $meta_skonto2 = $meta_skonto1Days = $meta_skonto2Days = $meta_paymentMethod = $meta_shipmentType = $meta_representative = $meta_porto = '';

//new proposal
$result = $conn->query("SELECT COUNT(*) as num FROM proposals");
$row = $result->fetch_assoc();
$id_num = 'ANG' . sprintf('%07d', $row['num'] +1);

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
  if(isset($_POST['meta_curDate']) && test_Date($_POST['meta_curDate'])){
    $meta_curDate = test_input($_POST['meta_curDate']);
  }
  if(isset($_POST['meta_deliveryDate']) && test_Date($_POST['meta_deliveryDate'])){
    $meta_deliveryDate = test_input($_POST['meta_deliveryDate']);
  }
  if(isset($_POST['meta_yourSign'])){
    $meta_yourSign = test_input($_POST['meta_yourSign']);
  }
  if(isset($_POST['meta_yourOrder'])){
    $meta_yourOrder = test_input($_POST['meta_yourOrder']);
  }
  if(isset($_POST['meta_ourSign'])){
    $meta_ourSign = test_input($_POST['meta_ourSign']);
  }
  if(isset($_POST['meta_ourMessage'])){
    $meta_ourMessage = test_input($_POST['meta_ourMessage']);
  }
  if(isset($_POST['meta_daysNetto'])){
    $meta_daysNetto = intval($_POST['meta_daysNetto']);
  }
  if(isset($_POST['meta_skonto1'])){
    $meta_skonto1 = floatval($_POST['meta_skonto1']);
  }
  if(isset($_POST['meta_skonto2'])){
    $meta_skonto2 = floatval($_POST['meta_skonto2']);
  }
  if(isset($_POST['meta_skonto1Days'])){
    $meta_skonto1Days = intval($_POST['meta_skonto1Days']);
  }
  if(isset($_POST['meta_skonto2Days'])){
    $meta_skonto2Days = intval($_POST['meta_skonto2Days']);
  }
  if(isset($_POST['meta_paymentMethod'])){
    $meta_paymentMethod = test_input($_POST['meta_paymentMethod']);
  }
  if(isset($_POST['meta_shipmentType'])){
    $meta_shipmentType = test_input($_POST['meta_shipmentType']);
  }
  if(isset($_POST['meta_representative'])){
    $meta_representative = test_input($_POST['meta_representative']);
  }
  if(isset($_POST['meta_porto'])){
    $meta_porto = floatval($_POST['meta_porto']);
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
      if(!$filterProposal){ //new proposal: create proposal first
        $conn->query("INSERT INTO proposals (id_number, clientID, status,
          curDate, deliveryDate, yourSign, yourOrder, ourSign, ourMessage, daysNetto, skonto1, skonto2, skonto1Days, skonto2Days, paymentMethod, shipmentType, representative, porto)
        VALUES ('$id_num', $filterClient, '0', '$meta_curDate', '$meta_deliveryDate', '$meta_yourSign', '$meta_yourOrder', '$meta_ourSign', '$meta_ourMessage', '$meta_daysNetto',
          '$meta_skonto1', '$meta_skonto2', '$meta_skonto1Days', '$meta_skonto2Days', '$meta_paymentMethod', '$meta_shipmentType', '$meta_representative', '$meta_porto')");
        $filterProposal = mysqli_insert_id($conn);
      }
      $conn->query("INSERT INTO products (proposalID, name, price, quantity, description) VALUES($filterProposal, '$product_name', '$product_price', '$product_quantity', '$product_description')");
      if(mysqli_error($conn)){
        echo $conn->error;
      } else {
        echo '<div class="alert alert-success fade in">';
        echo '<a href="userProjecting.php" class="close" data-dismiss="alert" aria-label="close">&times;</a>';
        echo '<strong>O.K.: </strong>'.$lang['OK_CREATE'];
        echo '</div>';
      }
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
  if(isset($_POST['meta_save'])){
    if($_POST['meta_save']){ //existing
      $conn->query("UPDATE proposals SET curDate = '$meta_curDate', deliveryDate = '$meta_deliveryDate', yourSign = '$meta_yourSign', yourOrder = '$meta_yourOrder', ourSign = '$meta_ourSign',
        ourMessage = '$meta_ourMessage', daysNetto = '$meta_daysNetto', skonto1 = '$meta_skonto1', skonto2 = '$meta_skonto2', skonto1Days = '$meta_skonto1Days', skonto2Days = '$meta_skonto2Days',
        paymentMethod = '$meta_paymentMethod', shipmentType = '$meta_shipmentType', representative = '$meta_representative', porto = '$meta_porto'
        WHERE id = " . intval($_POST['meta_save']));
    } else { //new proposal
      $conn->query("INSERT INTO proposals (id_number, clientID, status, curDate, deliveryDate, yourSign, yourOrder, ourSign,
        ourMessage, daysNetto, skonto1, skonto2, skonto1Days, skonto2Days, paymentMethod, shipmentType, representative, porto)
      VALUES ('$id_num', $filterClient, '0', '$meta_curDate', '$meta_deliveryDate', '$meta_yourSign', '$meta_yourOrder', '$meta_ourSign', '$meta_ourMessage', '$meta_daysNetto',
        '$meta_skonto1', '$meta_skonto2', '$meta_skonto1Days', '$meta_skonto2Days', '$meta_paymentMethod', '$meta_shipmentType', '$meta_representative', '$meta_porto')");
      $filterProposal = mysqli_insert_id($conn);
    }
    if(mysqli_error($conn)){
      echo $conn->error;
    } else {
      echo '<div class="alert alert-success fade in">';
      echo '<a href="userProjecting.php" class="close" data-dismiss="alert" aria-label="close">&times;</a>';
      echo '<strong>O.K.: </strong>'.$lang['OK_SAVE'];
      echo '</div>';
    }
  }
}

if($filterProposal){
  $result = $conn->query("SELECT * FROM proposals WHERE id = $filterProposal");
  $row = $result->fetch_assoc();
  $id_num = $row['id_number'];
} elseif($filterClient) {
  $result = $conn->query("SELECT * FROM clientInfoData WHERE clientId = $filterClient");
  $row = $result->fetch_assoc();
  $row['curDate'] = $row['deliveryDate'] = $row['yourSign'] = $row['ourSign'] = $row['yourOrder'] = $row['ourMessage'] = $row['porto'] = '';
} else {
  $id_num = '-';
  $result_c = $conn->query("SELECT * FROM $clientTable WHERE companyID IN (".implode(', ', $available_companies).")");
  if(!$result_c || $result_c->num_rows <= 0){
    echo '<br><div class="alert alert-info">'.$lang['WARNING_NO_CLIENTS'].'<br><br>';
    include "new_client.php";
    echo '</div>';
  } else {
    echo '<br><div class="alert alert-info">'.$lang['INFO_SELECT_CLIENT'].'</div>';
  }
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
        $result_fc = mysqli_query($conn, "SELECT * FROM companyData WHERE id IN (".implode(', ', $available_companies).")");
        if($result_fc && $result_fc->num_rows > 1) {
          echo '<option value="0">Select Company...</option>';
        } else {
          $filterCompany = $available_companies[1];
        }
        while($result && ($row_fc = $result_fc->fetch_assoc())){
          $checked = '';
          if($filterCompany == $row_fc['id']) {
            $checked = 'selected';
          }
          echo "<option $checked value='".$row_fc['id']."' >".$row_fc['name']."</option>";
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
      while($result && ($prod_row = $result->fetch_assoc())){
        echo '<tr>';
        echo '<td><input type="checkbox" name="delete_product[]" value="'.$row['id'].'" /></td>';
        echo '<td>'.$prod_row['name'].'</td>';
        echo '<td>'.$prod_row['description'].'</td>';
        echo '<td>'.$prod_row['price'].'</td>';
        echo '<td>'.$prod_row['quantity'].'</td>';
        echo '</tr>';
      }
      ?>
    </tbody>
  </table>
  <br><br>
  <div class="container-fluid">
    <div class="col-xs-6">
      <button type="submit" class="btn btn-warning" name="delete_selection"><?php echo $lang['DELETE']; ?></button>
    </div>
    <?php if($filterProposal): ?>
      <div class="col-xs-6 text-right">
        <a href="download_proposal.php?propID=<?php echo $filterProposal; ?>" target="_blank" class="btn btn-warning" >PDF Download</a>
      </div>
    <?php endif; ?>
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
          while($result && ($prod_row = $result->fetch_assoc())){
            echo "<option value='".$prod_row['id']."'>".$prod_row['name']."</option>";
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

    <br><hr>
    <h5>META: </h5>
    <hr><br>

    <div class="container-fluid">
      <div class="col-md-2"><?php echo $lang['DATE']; ?>:</div>
      <div class="col-md-6">
        <input type="text" class="form-control" name="meta_curDate" value="<?php echo $row['curDate']; ?>"/>
      </div>
    </div>
    <br>
    <div class="container-fluid">
      <div class="col-md-2"><?php echo $lang['DATE_DELIVERY']; ?>:</div>
      <div class="col-md-6">
        <input type="text" class="form-control" name="meta_deliveryDate" value="<?php echo $row['deliveryDate']; ?>" />
      </div>
    </div>
    <br>
    <div class="container-fluid">
      <div class="col-md-2"><?php echo $lang['PROP_YOUR_SIGN']; ?>:</div>
      <div class="col-md-8">
        <input type="text" class="form-control" name="meta_yourSign" value="<?php echo $row['yourSign']; ?>"/>
      </div>
    </div>
    <br>
    <div class="container-fluid">
      <div class="col-md-2"><?php echo $lang['PROP_YOUR_ORDER']; ?>:</div>
      <div class="col-md-8">
        <input type="text" class="form-control" name="meta_yourOrder" value="<?php echo $row['yourOrder']; ?>"/>
      </div>
    </div>
    <br>
    <div class="container-fluid">
      <div class="col-md-2"><?php echo $lang['PROP_OUR_SIGN']; ?>:</div>
      <div class="col-md-8">
        <input type="text" class="form-control" name="meta_ourSign" value="<?php echo $row['ourSign']; ?>"/>
      </div>
    </div>
    <br>
    <div class="container-fluid">
      <div class="col-md-2"><?php echo $lang['PROP_OUR_MESSAGE']; ?>:</div>
      <div class="col-md-8">
        <input type="text" class="form-control" name="meta_ourMessage" value="<?php echo $row['ourMessage']; ?>" />
      </div>
    </div>
    <hr>
    <h5>Zahlungsdaten</h5>
    <hr>
    <div class="container-fluid">
      <div class="col-xs-2">
        Tage Netto
      </div>
      <div class="col-xs-8">
        <input type="text" class="form-control" name="meta_daysNetto" value="<?php echo $row['daysNetto']; ?>" />
      </div>
    </div>
    <br>
    <div class="container-fluid">
      <div class="col-xs-2">
        Skonto 1
      </div>
      <div class="col-xs-3">
        <input type="text" class="form-control" name="meta_skonto1" value="<?php echo $row['skonto1']; ?>" />
      </div>
      <div class="col-xs-2 text-center">
        % Innerhalb von
      </div>
      <div class="col-xs-3">
        <input type="text" class="form-control" name="meta_skonto1Days" value="<?php echo $row['skonto1Days']; ?>" />
      </div>
      <div class="col-xs-1">
        Tagen
      </div>
    </div>
    <br>
    <div class="container-fluid">
      <div class="col-xs-2">
        Skonto 2
      </div>
      <div class="col-xs-3">
        <input type="text" class="form-control" name="meta_skonto2" value="<?php echo $row['skonto2']; ?>" />
      </div>
      <div class="col-xs-2 text-center">
        % Innerhalb von
      </div>
      <div class="col-xs-3">
        <input type="text" class="form-control" name="meta_skonto2Days" value="<?php echo $row['skonto2Days']; ?>" />
      </div>
      <div class="col-xs-1">
        Tagen
      </div>
    </div>
    <br>
    <div class="container-fluid">
      <div class="col-xs-2">
        Zahlungsweise
      </div>
      <div class="col-xs-9">
        <input type="text" class="form-control" name="meta_paymentMethod" value="<?php echo $row['paymentMethod']; ?>" />
      </div>
    </div>
    <br>
    <div class="container-fluid">
      <div class="col-xs-2">
        Versandart
      </div>
      <div class="col-xs-9">
        <input type="text" class="form-control" name="meta_shipmentType" value="<?php echo $row['shipmentType']; ?>" />
      </div>
    </div>
    <br>
    <div class="container-fluid">
      <div class="col-xs-2">
        Vertreter
      </div>
      <div class="col-xs-9">
        <input type="text" class="form-control" name="meta_representative" value="<?php echo $row['representative']; ?>" />
      </div>
    </div>
    <br>
    <div class="container-fluid">
      <div class="col-xs-2">
        Porto
      </div>
      <div class="col-xs-9">
        <input type="text" class="form-control" name="meta_porto" value="<?php echo $row['porto']; ?>" />
      </div>
    </div>
    <br><br>
    <div class="container-fluid">
      <div class="col-md-12">
        <button type="submit" class="btn btn-warning" name="meta_save" value="<?php echo $filterProposal;?>"><?php echo $lang['SAVE'];?></button>
      </div>
    </div>
  <?php endif; ?>
</form>

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
