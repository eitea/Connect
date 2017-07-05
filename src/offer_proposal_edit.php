<?php require 'header.php'; enableToERP($userID); ?>
<?php
$meta_curDate = $meta_deliveryDate = $meta_yourSign = $meta_yourOrder = $meta_ourSign = $meta_ourMessage = $meta_daysNetto = '';
$meta_skonto1 = $meta_skonto1Days = $meta_paymentMethod = $meta_shipmentType = $meta_representative = $meta_porto = $meta_porto_percentage = '';

$filterings = array('savePage' => $this_page, 'proposal' => 0, 'client' => 0, 'number' => getNextERP('ANG'));

if(!empty($_SESSION['filterings']['savePage']) && $_SESSION['filterings']['savePage'] != $this_page){
  $_SESSION['filterings'] = array(); //clear filterings if they come from another page
}

if(!empty($_POST['proposalID'])){
  $filterings['proposal'] = intval($_POST['proposalID']);
} elseif(!empty($_POST['nERP']) && array_key_exists($_POST['nERP'], $lang['PROPOSAL_TOSTRING']) && !empty($_POST['filterClient'])) {
  $filterings['number'] = getNextERP($_POST['nERP']);
  $filterings['client'] = intval($_POST['filterClient']);
} else {
  $filterings = $_SESSION['filterings'];
}

if($_SERVER['REQUEST_METHOD'] == 'POST'){
  if(isset($_POST['meta_curDate']) && test_Date($_POST['meta_curDate'].' 12:00:00')){
    $meta_curDate = test_input($_POST['meta_curDate'].' 12:00:00');
  }
  if(isset($_POST['meta_deliveryDate']) && test_Date($_POST['meta_deliveryDate'].' 12:00:00')){
    $meta_deliveryDate = test_input($_POST['meta_deliveryDate'].' 12:00:00');
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
  if(isset($_POST['meta_skonto1Days'])){
    $meta_skonto1Days = intval($_POST['meta_skonto1Days']);
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
  if(isset($_POST['meta_porto_percentage'])){
    $meta_porto_percentage = intval($_POST['meta_porto_percentage']);
  }
  if(isset($_POST['add_product']) && ($filterings['proposal'] || $filterings['client'])){
    if(!empty($_POST['add_product_name']) && !empty($_POST['add_product_quantity']) && !empty($_POST['add_product_price'])){
      $product_name = test_input($_POST['add_product_name']);
      $product_description = test_input($_POST['add_product_description']);
      $product_quantity = floatval($_POST['add_product_quantity']);
      $product_price = floatval($_POST['add_product_price']);
      $product_tax_id = intval($_POST['add_product_taxes']);
      $product_unit = test_input($_POST['add_product_unit']);
      $product_purchase = floatval($_POST['add_product_purchase']);
      $product_is_cash = 'FALSE';
      if(!empty($_POST['add_product_as_bar'])){
        $product_is_cash = 'TRUE';
      }
      if(!$filterings['proposal']){ //new proposal: create proposal first
        $conn->query("INSERT INTO proposals (id_number, clientID, status, curDate, deliveryDate, yourSign, yourOrder, ourSign, ourMessage, daysNetto, skonto1, skonto1Days, paymentMethod, shipmentType, representative, porto, portoRate)
        VALUES ('".$filterings['number']."', ".$filterings['client'].", '0', '$meta_curDate', '$meta_deliveryDate', '$meta_yourSign', '$meta_yourOrder', '$meta_ourSign', '$meta_ourMessage', '$meta_daysNetto',
        '$meta_skonto1', '$meta_skonto1Days', '$meta_paymentMethod', '$meta_shipmentType', '$meta_representative', '$meta_porto', '$meta_porto_percentage')");
        $filterings['proposal'] = mysqli_insert_id($conn);
        echo $conn->error;
      }
      $result_tax = $conn->query("SELECT percentage FROM taxRates WHERE id = $product_tax_id");
      $row_tax = $result_tax->fetch_assoc();
      $conn->query("INSERT INTO products (proposalID, name, price, quantity, description, taxPercentage, cash, unit, purchase)
      VALUES(".$filterings['proposal'].", '$product_name', '$product_price', '$product_quantity', '$product_description', '".$row_tax['percentage']."', '$product_is_cash', '$product_unit', '$product_purchase')");
      if($conn->error){ echo $conn->error;} else {echo '<div class="alert alert-success"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$lang['OK_CREATE'].'</div>';}
    } else {
      echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$lang['ERROR_MISSING_FIELDS'].'</div>';
    }
  } elseif(isset($_POST['add_product'])){
    echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$lang['ERROR_UNEXPECTED'].'</div>';
  }
  if(!empty($_POST['delete_product'])){
    $i = intval($_POST['delete_product']);
    $conn->query("DELETE FROM products WHERE id = $i");
    if($conn->error){ echo $conn->error; } else { echo '<div class="alert alert-success"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$lang['OK_DELETE'].'</div>'; }
  }
  if(isset($_POST['update_product'])){
    $x = intval($_POST['update_product']);
    if(!empty($_POST['update_name_'.$x]) && !empty($_POST['update_price_'.$x]) && !empty($_POST['update_quantity_'.$x])){
      $product_name = test_input($_POST['update_name_'.$x]);
      $product_description = test_input($_POST['update_description_'.$x]);
      $product_quantity = floatval($_POST['update_quantity_'.$x]);
      $product_price = floatval($_POST['update_price_'.$x]);
      $product_tax_id = intval($_POST['update_tax_'.$x]);
      $product_unit = test_input($_POST['update_unit_'.$x]);
      $conn->query("UPDATE products SET name='$product_name', description='$product_description', quantity='$product_quantity', price='$product_price', taxID=$product_tax_id, unit='$product_unit' WHERE id = $x");
      if($conn->error){ echo $conn->error;} else {echo '<div class="alert alert-success"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$lang['OK_SAVE'].'</div>';}
    } else {
      echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$lang['ERROR_MISSING_FIELDS'].'</div>';
    }
  }
  if(isset($_POST['meta_save'])){
    if($_POST['meta_save']){ //existing
      $conn->query("UPDATE proposals SET curDate = '$meta_curDate', deliveryDate = '$meta_deliveryDate', yourSign = '$meta_yourSign', yourOrder = '$meta_yourOrder', ourSign = '$meta_ourSign',
        ourMessage = '$meta_ourMessage', daysNetto = '$meta_daysNetto', skonto1 = '$meta_skonto1', skonto1Days = '$meta_skonto1Days',
        paymentMethod = '$meta_paymentMethod', shipmentType = '$meta_shipmentType', representative = '$meta_representative', porto = '$meta_porto', portoRate = '$meta_porto_percentage'
        WHERE id =".$filterings['proposal']);
    } else { //new proposal
      $conn->query("INSERT INTO proposals (id_number, clientID, status, curDate, deliveryDate, yourSign, yourOrder, ourSign,
        ourMessage, daysNetto, skonto1, skonto1Days, paymentMethod, shipmentType, representative, porto, portoRate)
      VALUES ('".$filterings['number']."', ".$filterings['client'].", '0', '$meta_curDate', '$meta_deliveryDate', '$meta_yourSign', '$meta_yourOrder', '$meta_ourSign', '$meta_ourMessage', '$meta_daysNetto',
        '$meta_skonto1', '$meta_skonto1Days', '$meta_paymentMethod', '$meta_shipmentType', '$meta_representative', '$meta_porto', '$meta_porto_percentage')");
      $filterings['proposal'] = mysqli_insert_id($conn);
    }
    if($conn->error){ echo $conn->error;} else {echo '<div class="alert alert-success"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$lang['OK_ADD'].'</div>';}
  }
  if(!empty($_POST['translate']) && !empty($_POST['transit'])){
    $filterings['proposal'] = intval($_POST['translate']);
    $filterings['number'] = getNextERP(test_input($_POST['transit']));
    $conn->query("UPDATE proposals SET history = CONCAT_WS(' ', history , id_number), id_number = '".$filterings['number']."' WHERE id = ".$filterings['proposal']);
  } elseif(isset($_POST['translate'])){
    echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$lang['ERROR_MISSING_DATA'].'</div>';
  }
} //END POST

if($filterings['proposal']){
  $result = $conn->query("SELECT * FROM proposals WHERE id = ".$filterings['proposal']);
  $row = $result->fetch_assoc();
  $filterings['number'] = $row['id_number'];
  $filterings['client'] = $row['clientID'];
} elseif ($filterings['client'] && $filterings['number']) {
  $result = $conn->query("SELECT * FROM clientInfoData WHERE clientId = ".$filterings['client']);
  $row = $result->fetch_assoc();
  $row['yourSign'] = $row['ourSign'] = $row['yourOrder'] = $row['ourMessage'] = $row['porto'] = '';
  $row['curDate'] = $row['deliveryDate'] = getCurrentTimestamp();
} else {
  redirect('offer_proposals.php?err=1');
}

$_SESSION['filterings'] = $filterings; //save your filterings
?>

<div class="page-header">
  <h3><?php echo $lang['PROCESS'] .' - '. $lang['EDIT'].' <small>'.$filterings['number'].'</small>'; ?>
    <div class="page-header-button-group">
      <button type="button" class="btn btn-default" data-toggle="modal" data-target=".proposal_details" title="Auftragsdaten bearbeiten"><i class="fa fa-cog"></i></button>
      <a href="download_proposal.php?propID=<?php echo $filterings['proposal']; ?>" target="_blank" class="btn btn-default" title="Download PDF"><i class="fa fa-download"></i></a>
      <a href="editCustomer_detail?custID=<?php echo $filterings['client']; ?>" class="btn btn-default" title="<?php echo $lang['CLIENT'] .' - Details'; ?>"><i class="fa fa-briefcase"></i></a>
      <a data-target=".choose-transition" data-toggle="modal" class="btn btn-default" title="<?php echo $lang['TRANSITION']; ?>"><i class="fa fa-arrow-right"></i></a>
    </div>
  </h3>
</div>

<form method="POST">
  <table class="table">
    <thead>
      <th>Name</th>
      <th><?php echo $lang['DESCRIPTION']; ?></th>
      <th><?php echo $lang['PRICE_STK']; ?></th>
      <th><?php echo $lang['QUANTITY']; ?></th>
      <th><?php echo $lang['TAXES']; ?></th>
      <th>Option</th>
    </thead>
    <tbody>
      <?php
      $result = $conn->query("SELECT * FROM products WHERE proposalID = ".$filterings['proposal']);
      while($result && ($prod_row = $result->fetch_assoc())){
        echo '<tr>';
        echo '<td>'.$prod_row['name'].'</td>';
        echo '<td style="max-width:500px;">'.$prod_row['description'].'</td>';
        echo '<td>'.$prod_row['price'].'</td>';
        echo '<td>'.$prod_row['quantity'].' '.$prod_row['unit'].'</td>';
        echo '<td>'.$prod_row['taxPercentage'].'%</td>';
        echo '<td style="min-width:120px;">';
        echo '<a class="btn btn-default" data-toggle="modal" data-target=".modal_edit_product_'.$prod_row['id'].'" ><i class="fa fa-pencil"></i></a> ';
        echo '<button type="submit" class="btn btn-default" name="delete_product" value="'.$prod_row['id'].'" title="'.$lang['DELETE'].'"><i class="fa fa-trash-o"></i></button';
        echo '</td>';
        echo '</tr>';
      }
      ?>
    </tbody>
  </table>

  <div class="container-fluid">
    <div class="col-xs-12 text-right">
      <?php
      $article_res = $conn->query("SELECT id FROM articles");
      if($article_res && $article_res->num_rows > 0): ?>
      <div class="btn-group">
        <button type="button" class="btn btn-warning dropdown-toggle" data-toggle="dropdown"><i class="fa fa-plus"></i> <?php echo $lang['ADD']; ?></button>
        <ul class="dropdown-menu">
          <li><a href="#" data-toggle="modal" data-target=".add_product"><?php echo $lang['FREE_TEXT']; ?></a></li>
          <li><a href="#" data-toggle="modal" data-target=".add_article"><?php echo $lang['ARTICLE']; ?></a></li>
        </ul>
      </div>
    <?php else: ?>
      <button type="button" class="btn btn-warning" data-toggle="modal" data-target=".add_product"><i class="fa fa-plus"></i> <?php echo $lang['ADD']; ?></button>
    <?php endif; ?>
  </div>
</div>
<?php
if($result){
  mysqli_data_seek($result,0);
}
while($result && ($prod_row = $result->fetch_assoc())):
$x = $prod_row['id'];
 ?>
  <div class="modal fade modal_edit_product_<?php echo $x ?>">
    <div class="modal-dialog modal-lg modal-content" role="document">
      <div class="modal-header">
        <h4 class="modal-title"><?php echo $prod_row['name']; ?></h4>
      </div>
      <div class="modal-body">
        <div class="container-fluid">
          <div class="col-md-6">
            <label>Name</label>
            <input type="text" class="form-control" name="update_name_<?php echo $x ?>" value="<?php echo $prod_row['name']; ?>"/>
          </div>
          <div class="col-md-6">
            <label><?php echo $lang['TAXES']; ?></label><br>
            <select class="js-example-basic-single" name="update_tax_<?php echo $x ?>" style="width:100%;">
              <?php
              $tax_result = $conn->query("SELECT * FROM taxRates WHERE percentage IS NOT NULL");
              while($tax_result && ($tax_row = $tax_result->fetch_assoc())){
                $selected = '';
                if($tax_row['id'] == $prod_row['taxID']) { $selected = 'selected';}
                echo '<option '.$selected.' value="'.$tax_row['id'].'" >'.$tax_row['description'].' - '.$tax_row['percentage'].'% </option>';
              }
              ?>
            </select>
          </div>
        </div>
        <br>
        <div class="container-fluid">
          <div class="col-md-4">
            <label><?php echo $lang['QUANTITY']; ?></label>
            <input type="text" class="form-control" name="update_quantity_<?php echo $x ?>" value="<?php echo $prod_row['quantity']; ?>"/>
          </div>
          <div class="col-md-4">
            <label><?php echo $lang['PRICE_STK']; ?></label>
            <input type="text" class="form-control" name="update_price_<?php echo $x ?>" value="<?php echo $prod_row['price']; ?>"/>
          </div>
          <div class="col-md-4">
            <label><?php echo $lang['UNIT']; ?></label>
            <select class="js-example-basic-single" name="update_unit_<?php echo $x ?>">
              <?php
              $unit_result = $conn->query("SELECT * FROM units");
              while($unit_result && ($unit_row = $unit_result->fetch_assoc())){
                echo '<option value="'.$unit_row['unit'].'" >'.$unit_row['name'].'</option>';
              }
              ?>
            </select>
          </div>
        </div>
        <br>
        <div class="container-fluid">
          <div class="col-md-12">
            <label><?php echo $lang['DESCRIPTION']; ?></label>
            <input type="text" class="form-control" name="update_description_<?php echo $x ?>" value="<?php echo $prod_row['description']; ?>"/>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-warning" name="update_product" value="<?php echo $x ?>"><?php echo $lang['SAVE'] ?></button>
      </div>
    </div>
  </div>
<?php endwhile;?>

<div class="modal fade add_product">
  <div class="modal-dialog modal-md modal-content">
    <div class="modal-header">
      <h4 class="modal-title"><?php echo $lang['ADD']; ?></h4>
    </div>
    <div class="modal-body">
      <label>Name</label>
      <input type="text" class="form-control required-field" name="add_product_name" maxlength="48"/>
      <br>
      <label><?php echo $lang['DESCRIPTION']; ?></label>
      <input type="text" class="form-control" name="add_product_description" maxlength="190"/>
      <br>
      <div class="row">
        <div class="col-md-3">
          <label><?php echo $lang['PURCHASE_PRICE']; ?></label>
          <input id="product_purchase" type="number" step='0.01' class="form-control" name="add_product_purchase" placeholder="EUR" />
        </div>
        <div class="col-md-1"><label>+</label></div>
        <div class="col-md-3">
          <label><?php echo $lang['ADDITION']; ?> %</label>
          <input id="salePercent" type="number" step='1' class="form-control" placeholder="zzgl %">
        </div>
        <div class="col-md-1"><label>=</label></div>
        <div class="col-md-4">
          <label><?php echo $lang['PRICE_STK']; ?></label>
          <input id="product_price" type="number" step="0.01" class="form-control required-field" name="add_product_price" placeholder="EUR" />
        </div>
      </div>
      <br>
      <div class="row">
        <div class="col-md-6">
          <label><?php echo $lang['QUANTITY']; ?></label>
          <input type="number" class="form-control required-field" name="add_product_quantity" value="1" />
        </div>
        <div class="col-md-6">
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
      </div>
      <br>
      <div class="row">
        <div class="col-md-6">
          <?php echo $lang['TAXES']; ?>
          <select class="js-example-basic-single btn-block" name="add_product_taxes">
            <?php
            $tax_result = $conn->query("SELECT * FROM taxRates WHERE percentage IS NOT NULL");
            while($tax_result && ($tax_row = $tax_result->fetch_assoc())){
              echo '<option value="'.$tax_row['id'].'" >'.$tax_row['description'].' - '.$tax_row['percentage'].'% </option>';
            }
            ?>
          </select>
        </div>
        <div class="col-md-6 checkbox">
          <label><input type="checkbox" name="add_product_as_bar" value="TRUE" /><?php echo $lang['CASH_EXPENSE']; ?></label>
        </div>
      </div>
    </div>
    <div class="modal-footer">
      <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
      <button type="submit" class="btn btn-warning" name="add_product"><?php echo $lang['ADD']; ?></button>
    </div>
  </div>
</div>

<div class="modal fade add_article">
  <div class="modal-dialog modal-sm modal-content">
    <div class="modal-header">
      <h4 class="modal-title"><?php echo $lang['CHOOSE_ARTICLE']; ?></h4>
    </div>
    <div class="modal-body">
      <select class="js-example-basic-single" name="select_new_product_true" style="min-width:200px" onchange="displayArticle(this.value);">
        <option value="0"><?php echo $lang['ARTICLE']; ?> ...</option>
        <?php
        $result = $conn->query("SELECT * FROM articles");
        while($result && ($prod_row = $result->fetch_assoc())){
          echo "<option value='".$prod_row['id']."'>".$prod_row['name']."</option>";
        }
        ?>
      </select>
    </div>
    <div class="modal-footer">
      <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
      <button type="button" data-dismiss="modal" class="btn btn-warning" data-toggle="modal" data-target=".add_product"><?php echo $lang['CONTINUE']; ?></button>
    </div>
  </div>
</div>

<div class="modal fade proposal_details">
  <div class="modal-dialog modal-lg modal-content" role="document">
    <div class="modal-header">
      <h5>META: </h5>
    </div>
    <div class="modal-body">
      <div class="container-fluid">
        <div class="col-md-2"><?php echo $lang['DATE']; ?>:</div>
        <div class="col-md-6">
          <input type="date" class="form-control" name="meta_curDate" value="<?php echo substr($row['curDate'],0,10); ?>"/>
        </div>
      </div>
      <br>
      <div class="container-fluid">
        <div class="col-md-2"><?php echo $lang['DATE_DELIVERY']; ?>:</div>
        <div class="col-md-6">
          <input type="date" class="form-control" name="meta_deliveryDate" value="<?php echo substr($row['deliveryDate'],0,10); ?>" />
        </div>
      </div>
      <br>
      <div class="container-fluid">
        <div class="col-md-2"><?php echo $lang['PROP_YOUR_SIGN']; ?>:</div>
        <div class="col-md-8">
          <input type="text" maxlength="25" class="form-control" name="meta_yourSign" value="<?php echo $row['yourSign']; ?>"/>
        </div>
      </div>
      <br>
      <div class="container-fluid">
        <div class="col-md-2"><?php echo $lang['PROP_YOUR_ORDER']; ?>:</div>
        <div class="col-md-8">
          <input type="text" maxlength="25" class="form-control" name="meta_yourOrder" value="<?php echo $row['yourOrder']; ?>"/>
        </div>
      </div>
      <br>
      <div class="container-fluid">
        <div class="col-md-2"><?php echo $lang['PROP_OUR_SIGN']; ?>:</div>
        <div class="col-md-8">
          <input type="text" maxlength="25" class="form-control" name="meta_ourSign" value="<?php echo $row['ourSign']; ?>"/>
        </div>
      </div>
      <br>
      <div class="container-fluid">
        <div class="col-md-2"><?php echo $lang['PROP_OUR_MESSAGE']; ?>:</div>
        <div class="col-md-8">
          <input type="text" maxlength="25" class="form-control" name="meta_ourMessage" value="<?php echo $row['ourMessage']; ?>" />
        </div>
      </div>
    </div>
    <div class="modal-header">
      <h5>Zahlungsdaten</h5>
    </div>
    <div class="modal-body">
      <div class="container-fluid">
        <div class="col-xs-2">
          Tage Netto
        </div>
        <div class="col-xs-8">
          <input type="number" class="form-control" name="meta_daysNetto" value="<?php echo $row['daysNetto']; ?>" />
        </div>
      </div>
      <br>
      <div class="container-fluid">
        <div class="col-xs-2">
          Skonto 1
        </div>
        <div class="col-xs-3">
          <input type="number" step="0.01" class="form-control" name="meta_skonto1" value="<?php echo $row['skonto1']; ?>" />
        </div>
        <div class="col-xs-2 text-center">
          % Innerhalb von
        </div>
        <div class="col-xs-3">
          <input type="number" class="form-control" name="meta_skonto1Days" value="<?php echo $row['skonto1Days']; ?>" />
        </div>
        <div class="col-xs-2">
          Tagen
        </div>
      </div>
      <br>
      <div class="container-fluid">
        <div class="col-xs-2">
          Zahlungsweise
        </div>
        <div class="col-xs-6">
          <input id="meta_paymentMethod" type="text" class="form-control" name="meta_paymentMethod" value="<?php echo $row['paymentMethod']; ?>" maxlength="100"/>
        </div>
        <div class="col-xs-4">
          <select class="js-example-basic-single" onchange="$('#meta_paymentMethod').val(this.value);">
            <option value="">...</option>
            <?php
            $tax_result = $conn->query("SELECT * FROM paymentMethods");
            while($tax_result && ($tax_row = $tax_result->fetch_assoc())){
              echo '<option '.$selected.' value="'.$tax_row['name'].'" >'.$tax_row['name'].'</option>';
            }
            ?>
          </select>
        </div>
      </div>
      <br>
      <div class="container-fluid">
        <div class="col-xs-2">
          Versandart
        </div>
        <div class="col-xs-6">
          <input id="meta_shipmentType" type="text" class="form-control" name="meta_shipmentType" value="<?php echo $row['shipmentType']; ?>" maxlength="100" />
        </div>
        <div class="col-xs-4">
          <select class="js-example-basic-single" onchange="$('#meta_shipmentType').val(this.value);">
            <option value="">...</option>
            <?php
            $tax_result = $conn->query("SELECT * FROM shippingMethods");
            while($tax_result && ($tax_row = $tax_result->fetch_assoc())){
              echo '<option '.$selected.' value="'.$tax_row['name'].'" >'.$tax_row['name'].'</option>';
            }
            ?>
          </select>
        </div>
      </div>
      <br>
      <div class="container-fluid">
        <div class="col-xs-2">
          Vertreter
        </div>
        <div class="col-xs-6">
          <input id="meta_representative" type="text" class="form-control" name="meta_representative" value="<?php echo $row['representative']; ?>" maxlength="50" />
        </div>
        <div class="col-xs-4">
          <select class="js-example-basic-single" onchange="$('#meta_representative').val(this.value);">
            <option value="">...</option>
            <?php
            $tax_result = $conn->query("SELECT * FROM representatives");
            while($tax_result && ($tax_row = $tax_result->fetch_assoc())){
              echo '<option '.$selected.' value="'.$tax_row['name'].'" >'.$tax_row['name'].'</option>';
            }
            ?>
          </select>
        </div>
      </div>
      <br>
      <div class="container-fluid">
        <div class="col-xs-2">
          Porto
        </div>
        <div class="col-xs-3">
          <input type="number" step="0.01" class="form-control" name="meta_porto" value="<?php echo $row['porto']; ?>" />
        </div>
        <div class="col-xs-3">
          <input id="meta_porto_percentage" type="number" step="0.01" class="form-control" name="meta_porto_percentage" value="<?php echo $row['portoRate']; ?>" />
        </div>
        <div class="col-xs-4">
          <select class="js-example-basic-single" onchange="$('#meta_porto_percentage').val(this.value);">
            <option value="">...</option>
            <?php
            $tax_result = $conn->query("SELECT * FROM taxRates WHERE percentage IS NOT NULL");
            while($tax_result && ($tax_row = $tax_result->fetch_assoc())){
              echo '<option value="'.$tax_row['percentage'].'" >'.$tax_row['description'].' - '.$tax_row['percentage'].'% </option>';
            }
            ?>
          </select>
        </div>
      </div>
    </div>
    <div class="modal-footer">
      <button type="submit" class="btn btn-default" data-dismiss="modal">Cancel</button>
      <button type="submit" class="btn btn-warning" name="meta_save" value="<?php echo $filterings['proposal'];?>"><?php echo $lang['SAVE'];?></button>
    </div>
  </div>
</div>
</form>

<?php
$current_transition = preg_replace('/\d/', '', $filterings['number']);
//Backward transitions are not possible, as are transitions into same state
$transitions = array('ANG', 'AUB', 'RE', 'LFS', 'GUT', 'STN');
$pos = array_search($current_transition, $transitions);
$bad = array_slice($transitions, 0, $pos);
$bad[] = $transitions[$pos];
?>
<form method="POST" action="offer_proposals.php">
  <div class="modal fade choose-transition">
    <div class="modal-dialog modal-sm modal-content">
      <div class="modal-header">
        <h3><?php echo $lang['TRANSITION']; ?></h3>
      </div>
      <div class="modal-body">
        <div class="radio">
          <?php
          $checked = '';
          foreach($transitions as $t){
            $disabled = '';
            if(in_array($t, $bad)){
              $disabled = 'disabled';
            }
            echo "<label><input type='radio' $disabled $checked value='$t' name='transit' /> ".$lang['PROPOSAL_TOSTRING'][$t]."</label><br>";
            if($current_transition == $t){
              $checked = 'checked'; //enable the next transition
            } else {
              $checked = '';
            }
          }
          ?>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" data-dismiss="modal" class="btn btn-default">Cancel</button>
        <button type="submit" class="btn btn-warning" name="translate" value="<?php echo $filterings['proposal']; ?>">OK</button>
      </div>
    </div>
  </div>
</form>

<script type="text/javascript">

function displayArticle(i){
  if(i != ""){
    $.ajax({
      url:'ajaxQuery/AJAX_getArticles.php',
      data:{articleID: i},
      type: 'get',
      success : function(resp){
        var res = resp.split("; ");
        $("[name='add_product_name']").val(res[1]);
        $("[name='add_product_description']").val(res[2]);
        $("[name='add_product_price']").val(res[3]);
        $("[name='add_product_unit']").val(res[4]).trigger('change');
        $("[name='add_product_taxes']").val(res[5]).trigger('change');
        if(res[6] == 'TRUE'){
          $("[name='add_product_as_bar']").iCheck('check');
        } else {
          $("[name='add_product_as_bar']").iCheck('uncheck');
        }
        $("[name='add_product_purchase']").val(res[7]);
      },
      error : function(resp){}
    });
  }
}

$("#product_price").on("keyup", function(){
  var v = parseInt($("#product_price").val());
  var p = parseFloat($("#salePercent").val());
  var e = parseInt($("#product_purchase").val());
  if(e){ //v and e yield p
    $("#salePercent").val(Math.round((-1 + v/e) * 10000) / 100);
  } else if(p){ //v and b yield e
    $("#product_purchase").val(Math.round(10000 * v / (p + 100)) / 100);
  }
});

$("#salePercent").on("keyup", function(){
  var v = parseInt($("#product_price").val());
  var p = parseFloat($("#salePercent").val());
  var e = parseInt($("#product_purchase").val());
  if(e){ //yields v
    $("#product_price").val(Math.round(100 * e + e*p) / 100);
  } else if(v){
    $("#product_purchase").val(Math.round(10000 * v / (p + 100)) / 100);
  }
});

$("#product_purchase").on("keyup", function(){
  var v = parseInt($("#product_price").val());
  var p = parseFloat($("#salePercent").val());
  var e = parseInt($("#product_purchase").val());
  if(p){
    $("#product_price").val(Math.round(100 * e + e*p) / 100);
  }else if(v){
    $("#salePercent").val(Math.round((-1 + v/e) * 10000) / 100);
  }
});

</script>

<?php require 'footer.php';?>
