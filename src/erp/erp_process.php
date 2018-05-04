<?php include dirname(__DIR__) . '/header.php'; enableToERP($userID); ?>
<script src="../plugins/jQuery/jquery-ui/jquery-ui.min.js"></script>
<?php
$meta_curDate = $meta_deliveryDate = getCurrentTimestamp();
$meta_porto = $meta_porto_percentage = 0;
$meta_paymentMethod = $meta_shipmentType = $meta_representative = $meta_header = '';

if(!empty($_GET['val'])){
  $historyID = intval($_GET['val']);
} else {
  redirect('view?err=1');
}

$result = $conn->query("SELECT proposals.*, companyID, id_number FROM proposals, processHistory, clientData
WHERE proposals.clientID = clientData.id AND proposals.id = processHistory.processID AND processHistory.id = $historyID");
if($result && $result->num_rows > 0){
  $proposal_row = $result->fetch_assoc();
} else {
  $conn->query("UPDATE UserData SET strikeCount = strikeCount + 1 WHERE id = $userID");
  redirect('view?err=2'); //STRIKE
}
if(!in_array($proposal_row['companyID'], $available_companies)){
  $conn->query("UPDATE UserData SET strikeCount = strikeCount + 1 WHERE id = $userID");
  redirect('view?err=2'); //STRIKE
}

if($_SERVER['REQUEST_METHOD'] == 'POST'){
  if(isset($_POST['meta_curDate']) && test_Date($_POST['meta_curDate'].' 12:00:00')){
    $meta_curDate = test_input($_POST['meta_curDate'].' 12:00:00');
    $proposal_row['curDate'] = $meta_curDate;
  }
  if(isset($_POST['meta_deliveryDate']) && test_Date($_POST['meta_deliveryDate'].' 12:00:00')){
    $meta_deliveryDate = test_input($_POST['meta_deliveryDate'].' 12:00:00');
    $proposal_row['deliveryDate'] =  $meta_deliveryDate;
  }
  if(isset($_POST['meta_paymentMethod'])){
    $meta_paymentMethod = test_input($_POST['meta_paymentMethod']);
    $proposal_row['paymentMethod'] =  $meta_paymentMethod;
  }
  if(isset($_POST['meta_shipmentType'])){
    $meta_shipmentType = test_input($_POST['meta_shipmentType']);
    $proposal_row['shipmentType'] =  $meta_shipmentType;
  }
  if(isset($_POST['meta_representative'])){
    $meta_representative = test_input($_POST['meta_representative']);
    $proposal_row['representative'] =  $meta_representative;
  }
  if(isset($_POST['meta_porto'])){
    $meta_porto = floatval($_POST['meta_porto']);
    $proposal_row['porto'] =  $meta_porto;
  }
  if(isset($_POST['meta_header'])){
    $meta_header = test_input($_POST['meta_header']);
    $proposal_row['header'] =  $meta_header;
  }
  if(isset($_POST['meta_porto_percentage'])){
    $meta_porto_percentage = intval($_POST['meta_porto_percentage']);
    $proposal_row['portoRate'] =  $meta_porto_percentage;
  }
  if(isset($_POST['translate'])){
    if(!empty($_POST['copy_transition'])){
      $transit = $_POST['copy_transition'];
      $prod_list = array();
      //step1 - get all products (id quantity and origin) from current history
      $res = $conn->query("SELECT * FROM products WHERE historyID = $historyID");
      while($row = $res->fetch_assoc()){
        if($row['origin']){
          $prod_list[$row['origin']] = $row;
        } else {  //origins can be empty... what to do then?
          $prod_list[$row['id']] = $row;
        }
      }
      //step2 - reduce by all products which are same as transit
      $res = $conn->query("SELECT quantity, origin FROM products INNER JOIN processHistory ON products.historyID = processHistory.id
      WHERE processHistory.processID = ".$proposal_row['id']." AND id_number LIKE '$transit%'"); echo $conn->error;
      while($row = $res->fetch_assoc()){
        if(isset($prod_list[$row['origin']])){
          $prod_list[$row['origin']]['quantity'] -= $row['quantity'];
        }
      }
      $res->free();
      //step3 - create new history and enter products with quantities
      $num = getNextERP($transit, $proposal_row['companyID']);
      $conn->query("INSERT INTO processHistory (id_number, processID) VALUES('$num', ".$proposal_row['id'].")"); echo $conn->error;
      $historyID = $conn->insert_id;
      $stmt = $conn->prepare("INSERT INTO products (historyID, origin, position, name, price, quantity, description, taxID, cash, unit, purchase)
      VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"); echo $conn->error;
      $stmt->bind_param("isisddsissd", $historyID, $origin, $pos, $name, $price, $quantity, $desc, $taxID, $cash, $unit, $purchase);
      foreach($prod_list as $p){
        if($p['quantity'] > 0 || !$p['origin']){
          $origin = $p['origin'];
          $pos = $p['position'];
          $name = $p['name'];
          $price = $p['price'];
          $quantity = $p['quantity'];
          $desc = $p['description'];
          $taxID = $p['taxID'];
          $cash = $p['cash'];
          $unit = $p['unit'];
          $purchase = $p['purchase'];
          $stmt->execute();
        }
        if($stmt->error) echo $stmt->error;
      }
      echo $stmt->error;
      $stmt->close();
      redirect("edit?val=$historyID");
    } else {
      echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$lang['ERROR_MISSING_DATA'].'</div>';
    }
  } elseif(isset($_POST['add_position_sum'])){
    $LAST_POSITION = intval($_POST['add_position_sum']) +1;
    $conn->query("INSERT INTO products (historyID, position, name) VALUES($historyID, $LAST_POSITION, '".secure_data('ERP', 'PARTIAL_SUM', 'encrypt', $userID, $privateKey)."')");
    if($conn->error){ echo $conn->error;} else {echo '<div class="alert alert-success"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$lang['OK_ADD'].'</div>';}
  } elseif(isset($_POST['add_position_text']) && !empty($_POST['add_position_text_text'])){
    $LAST_POSITION = intval($_POST['add_position_text']) +1;
    $txt = test_input($_POST['add_position_text_text']);
    $conn->query("INSERT INTO products (historyID, position, name, description) VALUES($historyID, $LAST_POSITION, '".secure_data('ERP', 'CLEAR_TEXT', 'encrypt', $userID, $privateKey)."', '$txt')");
    if($conn->error){ echo $conn->error;} else {echo '<div class="alert alert-success"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$lang['OK_ADD'].'</div>';}
  } elseif(isset($_POST['add_position_page'])){
    $LAST_POSITION = intval($_POST['add_position_page']) +1;
    $conn->query("INSERT INTO products (historyID, position, name) VALUES($historyID, $LAST_POSITION, '".secure_data('ERP', 'NEW_PAGE', 'encrypt', $userID, $privateKey)."')");
    if($conn->error){ echo $conn->error;} else {echo '<div class="alert alert-success"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$lang['OK_ADD'].'</div>';}
  } elseif(isset($_POST['add_product'])){
    $LAST_POSITION = intval($_POST['add_product']) +1;
    if(!empty($_POST['add_product_name']) && !empty($_POST['add_product_quantity']) && !empty($_POST['add_product_price'])){
      $product_name = secure_data('ERP', test_input($_POST['add_product_name']), 'encrypt', $userID, $privateKey);
      $product_description = secure_data('ERP', test_input($_POST['add_product_description']));
      $product_quantity = floatval($_POST['add_product_quantity']);
      $product_price = floatval($_POST['add_product_price']);
      $product_tax_id = intval($_POST['add_product_taxes']);
      $product_unit = test_input($_POST['add_product_unit']);
      $product_purchase = floatval($_POST['add_product_purchase']);
      $product_is_cash = 'FALSE';
      if(!empty($_POST['add_product_as_bar'])){
        $product_is_cash = 'TRUE';
      }
      $product_origin = randomPassword(16);
      $conn->query("INSERT INTO products (historyID, origin, position, name, price, quantity, description, taxID, cash, unit, purchase)
      VALUES($historyID, '$product_origin', $LAST_POSITION, '$product_name', '$product_price', '$product_quantity', '$product_description', '$product_tax_id', '$product_is_cash', '$product_unit', '$product_purchase')");
      if($conn->error){ echo $conn->error;} else {echo '<div class="alert alert-success"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$lang['OK_ADD'].'</div>';}
    } else {
      echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$lang['ERROR_MISSING_FIELDS'].'</div>';
    }
  } elseif(!empty($_POST['delete_product'])){
    $i = intval($_POST['delete_product']);
    $conn->query("DELETE FROM products WHERE id = $i");
    if($conn->error){ echo $conn->error; } else { echo '<div class="alert alert-success"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$lang['OK_DELETE'].'</div>'; }
  } elseif(isset($_POST['update_product'])){
    $x = intval($_POST['update_product']);
    $check_result = $conn->query("SELECT name FROM products WHERE id = $x");
    if($check_row = $check_result->fetch_assoc()){
      if(secure_data('ERP', $check_row['name'], 'decrypt', $userID, $privateKey) == 'CLEAR_TEXT'){
        $product_description = secure_data('ERP', test_input($_POST['update_description_'.$x]));
        $conn->query("UPDATE products SET description='$product_description' WHERE id = $x");
        if($conn->error){ echo $conn->error;} else {echo '<div class="alert alert-success"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$lang['OK_SAVE'].'</div>';}
      } elseif(!empty($_POST['update_name_'.$x]) && !empty($_POST['update_price_'.$x]) && !empty($_POST['update_quantity_'.$x])){
        $product_name = secure_data('ERP', test_input($_POST['update_name_'.$x]));
        $product_description = secure_data('ERP', test_input($_POST['update_description_'.$x]));
        $product_quantity = floatval($_POST['update_quantity_'.$x]);
        $product_purchase = floatval($_POST['update_purchase_'.$x]);
        $product_price = floatval($_POST['update_price_'.$x]);
        $product_tax_id = intval($_POST['update_tax_'.$x]);
        $product_unit = test_input($_POST['update_unit_'.$x]);
        $product_bar = 'FALSE';
        if(isset($_POST['update_bar_'.$x])) $product_bar = 'TRUE';
        $conn->query("UPDATE products SET name='$product_name', cash='$product_bar', description='$product_description', quantity='$product_quantity', price='$product_price', purchase='$product_purchase', taxID=$product_tax_id, unit='$product_unit' WHERE id = $x");
        if($conn->error){ echo $conn->error;} else {echo '<div class="alert alert-success"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$lang['OK_SAVE'].'</div>';}
      } else {
        echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$lang['ERROR_MISSING_FIELDS'].'</div>';
      }
    }
  } elseif(isset($_POST['save_positions']) && !empty($_POST['positions']) && !empty($_POST['positions_id'])){
    if(count($_POST['positions']) == count($_POST['positions_id'])){
      $conn->query("UPDATE products SET position = NULL WHERE historyID = $historyID");
      $stmt = $conn->prepare("UPDATE products SET position = ? WHERE id = ?");
      $stmt->bind_param("ii", $position, $id);
      for($i = 0; $i < count($_POST['positions']); $i++){
        $position = intval($_POST['positions'][$i]);
        $id = intval($_POST['positions_id'][$i]);
        $stmt->execute();
      }
      if($conn->error){ echo $conn->error;} else {echo '<div class="alert alert-success"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$lang['OK_SAVE'].'</div>';}
    }
  } elseif(isset($_POST['update_articles'])){
    $conn->query("UPDATE products p, articles a SET p.description = a.description, p.price = a.price, p.unit = a.unit, p.taxID = a.taxID, p.purchase = a.purchase, p.cash = a.cash
    WHERE a.name = p.name AND p.historyID = $historyID");
    if($conn->error){ echo $conn->error;} else {echo '<div class="alert alert-success"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$lang['OK_SAVE'].'</div>';}
  } elseif(isset($_POST['update_clientData'])){
    $conn->query("UPDATE proposals p, clientInfoData c
      SET p.paymentMethod = c.paymentMethod, p.shipmentType = c.shipmentType, p.representative = c.representative
      WHERE p.clientID = c.clientID AND p.id = ".$proposal_row['id']);
    if($conn->error){ echo $conn->error;} else {echo '<div class="alert alert-success"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$lang['OK_SAVE'].'</div>';}
  }
  if(isset($_POST['meta_save'])){
    $conn->query("UPDATE proposals SET curDate = '$meta_curDate', deliveryDate = '$meta_deliveryDate', paymentMethod = '$meta_paymentMethod', shipmentType = '$meta_shipmentType',
    representative = '$meta_representative', porto = '$meta_porto', portoRate = '$meta_porto_percentage', header = '$meta_header' WHERE id = ".$proposal_row['id']);
    if($conn->error){ echo $conn->error;} else {echo '<div class="alert alert-success"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$lang['OK_SAVE'].'</div>';}
  }
} //END POST
?>
<div class="page-header-fixed">
    <div class="page-header">
        <h3><?php echo $lang['PROCESS'] .' - '. $lang['EDIT'].' <small>'.$proposal_row['id_number'].'</small>'; ?>
            <div class="page-header-button-group">
                <button type="button" class="btn btn-default" data-toggle="modal" data-target=".proposal_details" title="Auftragsdaten bearbeiten"><i class="fa fa-cog"></i></button>
                <a href="../system/clientDetail?custID=<?php echo $proposal_row['clientID']; ?>" class="btn btn-default" title="<?php echo $lang['CLIENT'] .' - Details'; ?>"><i class="fa fa-briefcase"></i></a>
                <div class="btn-group">
                    <a class="btn btn-default dropdown-toggle" data-toggle="dropdown" title="Daten erneuern"><i class="fa fa-refresh"></i></a>
                    <ul class="dropdown-menu">
                        <li><form method="POST"><button type="submit" class="btn btn-link" name="update_clientData"><?php echo $lang['CLIENTS']; ?> Info</button></form></li>
                        <li><form method="POST"><button type="submit" class="btn btn-link" name="update_articles"><?php echo $lang['ARTICLE']; ?></button></form></li>
                    </ul>
                </div>
                <button data-target=".product-summary" data-toggle="modal" class="btn btn-default" title="<?php echo $lang['OVERVIEW']; ?>"><i class="fa fa-list-alt"></i></button>
                <button type="submit" form="positionForm" class="btn btn-default blinking" name="save_positions" title="<?php echo $lang['SAVE']; ?>"><i class="fa fa-floppy-o"></i></button>
                <a href="download?proc=<?php echo $historyID; ?>" target="_blank" class="btn btn-default" title="Download PDF"><i class="fa fa-download"></i></a>
                <a style="margin-left: 20px" data-target=".choose-transition" data-toggle="modal" class="btn btn-default" title=""><i class="fa fa-arrow-right"></i></a>
            </div>
        </h3>
    </div>
</div>
<div class="page-content-fixed-150">
<form id="positionForm" method="POST">
  <table id="sort" class="table table-hover">
    <thead>
      <th>#</th>
      <th>Name</th>
      <th><?php echo $lang['DESCRIPTION']; ?></th>
      <th><?php echo $lang['PRICE_STK']; ?></th>
      <th><?php echo $lang['QUANTITY']; ?></th>
      <th><?php echo $lang['TAXES']; ?></th>
      <th>Option</th>
    </thead>
    <tbody>
      <?php
      $LAST_POSITION = 0;
      $result = $conn->query("SELECT products.*, percentage FROM products LEFT JOIN taxRates ON taxRates.id = taxID WHERE historyID = $historyID ORDER BY position ASC");
      while($result && ($prod_row = $result->fetch_assoc())){
        $prod_row["name"] = $prod_row["name"];
        $prod_row["description"] = $prod_row["description"];
        echo '<tr>';
        echo '<td><input type="text" readonly class="index" name="positions[]" value="'.$prod_row['position'].'" style="border:0;background:0;" size="4" /><input type="hidden" value="'.$prod_row['id'].'" name="positions_id[]"/></td>';
        echo '<td>'.mc_status('ERP').' '.secure_data('ERP', $prod_row['name'], 'decrypt', $userID, $privateKey).'</td>';
        echo '<td style="max-width:500px;">'.mc_status('ERP').' '.secure_data('ERP', $prod_row['description'], 'decrypt').'</td>';
        echo '<td>'.$prod_row['price'].'</td>';
        echo '<td>'.$prod_row['quantity'].' '.$prod_row['unit'].'</td>';
        echo '<td>'.intval($prod_row['percentage']).'%</td>';
        echo '<td style="min-width:120px;">';
        if($prod_row['name'] != 'PARTIAL_SUM' && $prod_row['name'] != 'NEW_PAGE')
          echo '<a class="btn btn-default" data-toggle="modal" data-target=".modal_edit_product_'.$prod_row['id'].'" ><i class="fa fa-pencil"></i></a> ';
        echo '<button type="submit" class="btn btn-default" name="delete_product" value="'.$prod_row['id'].'" title="'.$lang['DELETE'].'"><i class="fa fa-trash-o"></i></button>';
        echo '</td>';
        echo '</tr>';
        $LAST_POSITION = $prod_row['position'];
      }
      echo $conn->error;
      ?>
    </tbody>
  </table>
</form>
<script>
var fixHelperModified = function(e, tr) {
  var $originals = tr.children();
  var $helper = tr.clone();
  $helper.children().each(function(index) {
    $(this).width($originals.eq(index).width())
  });
  return $helper;
},
updateIndex = function(e, ui) {
  $('input.index', ui.item.parent()).each(function (i) {
    $(this).val(i + 1);
    $(this).keyup();
  });
};
$("#sort tbody").sortable({
  helper: fixHelperModified,
  stop: updateIndex
}).disableSelection();
</script>

<div class="modal fade product-summary">
  <div class="modal-dialog modal-content modal-md">
    <div class="modal-header"><h4><?php echo $lang['OVERVIEW']; ?></h4></div>
    <div class="modal-body">
      <table class="table table-hover">
        <thead>
          <th>Position #</th>
          <th>Einkauf</th>
          <th>Verkauf</th>
          <th>Bilanz</th>
        </thead>
        <tbody>
          <?php
          if($result){  $result->data_seek(0); } //TODO: remove this
          $sum_purchase = $sum_sell = $partial_sum_purchase = $partial_sum_sell = 0;
          while($result && ($prod_row = $result->fetch_assoc())){
            $prod_row["name"] = $prod_row["name"];
            $prod_row["description"] = $prod_row["description"];
            if($prod_row['name'] != 'CLEAR_TEXT' && $prod_row['name'] != 'NEW_PAGE' && $prod_row['name'] != 'PARTIAL_SUM'){
              $purchase = $prod_row['purchase']*$prod_row['quantity'];
              $sell = $prod_row['price']*$prod_row['quantity'];
              if($sell > $purchase){$style = 'color:#65c948';} else {$style = 'color:#e08e21';}
              if($purchase > 0){$percentage = sprintf('%+.2f', ($sell / $purchase -1)*100);} else {$percentage = 'xx';}
              echo '<tr>';
              echo '<td>#'.$prod_row['position'].'</td>';
              echo '<td>'.$purchase.' EUR</td>';
              echo '<td>'.$sell.' EUR</td>';
              echo "<td style='$style'>".sprintf('%+.2f',$sell - $purchase)." EUR ($percentage %)</td>";
              echo '</tr>';
              $sum_purchase += $purchase;
              $sum_sell += $sell;
            } elseif($prod_row['name'] == 'PARTIAL_SUM'){
              $partial_sum_purchase = $sum_purchase - $partial_sum_purchase;
              $partial_sum_sell = $sum_sell - $partial_sum_sell;
              if($partial_sum_purchase > 0){$percentage = sprintf('%+.2f', ($partial_sum_sell / $partial_sum_purchase - 1)*100);} else {$percentage = 'xx';}
              echo "<tr><td><strong>".$lang['PARTIAL_SUM'].":</strong></td><td>$partial_sum_purchase EUR</td><td>$partial_sum_sell EUR</td><td>".sprintf('%+.2f',$partial_sum_sell - $partial_sum_purchase)." EUR ($percentage %)</td></tr>";
            }
          }
          if($sum_purchase > 0){$percentage = sprintf('%+.2f', ($sum_sell / $sum_purchase -1)*100);} else {$percentage = 'xx';}
          echo "<tr><td><strong>Endsumme:</strong></td><td>$sum_purchase EUR</td><td>$sum_sell EUR</td><td>".sprintf('%+.2f',$sum_sell - $sum_purchase)." EUR ($percentage %)</td></tr>";
          ?>
        </tbody>
      </table>
    </div>
    <div class="modal-footer"><button type="button" class="btn btn-default" data-dismiss="modal">Ok</button></div>
  </div>
</div>

<form method="POST">
  <div class="container-fluid">
    <div class="col-xs-12 text-right">
      <div class="btn-group">
        <button type="button" class="btn btn-warning dropdown-toggle" data-toggle="dropdown"><i class="fa fa-plus"></i> <?php echo $lang['ADD']; ?></button>
        <ul class="dropdown-menu">
          <li><button type="button" class="btn btn-link" data-toggle="modal" data-target=".add_product"><?php echo $lang['FREE_TEXT']; ?></button></li>
          <li><button type="button" class="btn btn-link" data-toggle="modal" data-target=".add_product_text">Text</button></li>
          <li><button type="submit" class="btn btn-link" value="<?php echo $LAST_POSITION; ?>" name="add_position_sum" ><?php echo $lang['PARTIAL_SUM']; ?></button></li>
          <li><button type="submit" class="btn btn-link" value="<?php echo $LAST_POSITION; ?>" name="add_position_page" ><?php echo $lang['NEW_PAGE']; ?></button></li>
          <?php
          $article_res = $conn->query("SELECT id FROM articles");
          if($article_res && $article_res->num_rows > 0){ echo '<li><button type="button" class="btn btn-link" data-toggle="modal" data-target=".add_article">'.$lang['ARTICLE'].'</button></li>'; }
          ?>
        </ul>
      </div>
    </div>
  </div>
<?php
$unit_select = '';
$unit_result = $conn->query("SELECT * FROM units");
while($unit_result && ($unit_row = $unit_result->fetch_assoc())){
  $unit_select .= '<option value="'.$unit_row['unit'].'" >'.$unit_row['name'].'</option>';
}

if($result){  $result->data_seek(0); }
while($result && ($prod_row = $result->fetch_assoc())):
  $prod_row["name"] = secure_data('ERP', $prod_row["name"], 'decrypt');
  $prod_row["description"] = $prod_row["description"];
  if($prod_row['name'] != 'NEW_PAGE' && $prod_row['name'] != 'PARTIAL_SUM'):
$x = $prod_row['id'];
 ?>
  <div class="modal fade modal_edit_product_<?php echo $x ?>">
    <div class="modal-dialog modal-md modal-content" role="document">
      <div class="modal-header"><h4 class="modal-title"><?php echo $prod_row['name']; ?></h4></div>
      <div class="modal-body">
        <?php if($prod_row['name'] == 'CLEAR_TEXT'): ?>
          <label><?php echo mc_status('ERP'); ?>Text</label>
          <textarea type="text" class="form-control" maxlength="300" name="update_description_<?php echo $x ?>" ><?php echo $prod_row['description']; ?></textarea>
        <?php else: ?>
          <label><?php echo mc_status('ERP'); ?>Name</label>
          <input type="text" class="form-control" name="update_name_<?php echo $x ?>" value="<?php echo $prod_row['name']; ?>"/>
          <br>
            <label><?php echo mc_status('ERP'); ?><?php echo $lang['DESCRIPTION']; ?></label>
            <textarea class="form-control" rows="3" name="update_description_<?php echo $x ?>"><?php echo $prod_row['description']; ?></textarea>
          <br>
          <div class="row">
            <div class="col-md-4">
              <label><?php echo $lang['PURCHASE_PRICE']; ?></label>
              <input type="number" step='0.01' class="form-control money" name="update_purchase_<?php echo $x ?>" value="<?php echo $prod_row['purchase']; ?>"/>
            </div>
            <div class="col-md-4"></div>
            <div class="col-md-4">
              <label><?php echo $lang['PRICE_STK']; ?> (Netto)</label>
              <input type="text" class="form-control money" name="update_price_<?php echo $x ?>" value="<?php echo $prod_row['price']; ?>"/>
            </div>
          </div>
          <br>
          <div class="row">
            <div class="col-md-6">
              <label><?php echo $lang['QUANTITY']; ?></label>
              <input type="text" class="form-control" name="update_quantity_<?php echo $x ?>" value="<?php echo $prod_row['quantity']; ?>"/>
            </div>
            <div class="col-md-6">
              <label><?php echo $lang['UNIT']; ?></label>
              <select class="js-example-basic-single" name="update_unit_<?php echo $x ?>">
                <?php echo str_replace('value="'.$prod_row['unit'], 'selected value="'.$prod_row['unit'], $unit_select); ?>
              </select>
            </div>
          </div>
          <br>
          <div class="row">
            <div class="col-md-6">
              <label><?php echo $lang['TAXES']; ?></label><br>
              <select class="js-example-basic-single" name="update_tax_<?php echo $x ?>">
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
            <div class="col-md-6 checkbox">
              <label><input type="checkbox" <?php if($prod_row['cash'] == 'TRUE') echo 'checked'; ?> name="update_bar_<?php echo $x ?>" value="TRUE" /><?php echo $lang['CASH_EXPENSE']; ?></label>
            </div>
          </div>
        <?php endif; ?>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-warning" name="update_product" value="<?php echo $x ?>"><?php echo $lang['SAVE'] ?></button>
      </div>
    </div>
  </div>
<?php endif; endwhile;?>

<div class="modal fade add_product">
  <div class="modal-dialog modal-md modal-content">
    <div class="modal-header">
      <h4 class="modal-title"><?php echo $lang['ADD']; ?>: Position</h4>
    </div>
    <div class="modal-body">
      <label><?php echo mc_status('ERP'); ?>Name</label>
      <input type="text" class="form-control required-field" name="add_product_name" maxlength="48"/>
      <br>
      <label><?php echo mc_status('ERP'); ?><?php echo $lang['DESCRIPTION']; ?></label>
      <textarea class="form-control" style='resize:none;overflow:hidden' rows="3" name="add_product_description" maxlength="350"></textarea>
      <br>
      <div class="row">
        <div class="col-md-3">
          <label><?php echo $lang['PURCHASE_PRICE']; ?></label>
          <input id="product_purchase" type="number" step='0.01' class="form-control money" name="add_product_purchase" placeholder="EUR" />
        </div>
        <div class="col-md-1"><label>+</label></div>
        <div class="col-md-3">
          <label><?php echo $lang['ADDITION']; ?> %</label>
          <input id="salePercent" type="number" step='1' class="form-control" name="add_product_addition" placeholder="zzgl %">
        </div>
        <div class="col-md-1"><label>=</label></div>
        <div class="col-md-4">
          <label><?php echo $lang['PRICE_STK']; ?> <small>(Netto)</small></label>
          <input id="product_price" type="number" step="0.01" class="form-control required-field money" name="add_product_price" placeholder="EUR" />
        </div>
      </div>
      <br>
      <div class="row">
        <div class="col-md-6">
          <label><?php echo $lang['QUANTITY']; ?></label>
          <input type="number" step="0.01" class="form-control required-field" name="add_product_quantity" value="1" />
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
          <label><?php echo $lang['TAXES']; ?></label>
          <select class="js-example-basic-single btn-block" name="add_product_taxes">
            <?php
            $tax_result = $conn->query("SELECT id, description, percentage FROM taxRates");
            while($tax_result && ($tax_row = $tax_result->fetch_assoc())){
              $selected = '';
              if($tax_row['id'] == 3) $selected = 'selected';
              echo '<option '.$selected.' value="'.$tax_row['id'].'" >'.$tax_row['description'].' - '.$tax_row['percentage'].'% </option>';
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
      <button type="submit" class="btn btn-warning" value="<?php echo $LAST_POSITION; ?>" name="add_product"><?php echo $lang['ADD']; ?></button>
    </div>
  </div>
</div>

<div class="modal fade add_article">
  <div class="modal-dialog modal-sm modal-content">
    <div class="modal-header">
      <h4 class="modal-title"><?php echo $lang['CHOOSE_ARTICLE']; ?><?php echo mc_status('ERP'); ?></h4>
    </div>
    <div class="modal-body">
      <select class="js-example-basic-single" name="select_new_product_true" style="min-width:200px" onchange="displayArticle(this.value);">
        <option value="0"><?php echo $lang['ARTICLE']; ?> ...</option>
        <?php
        $result = $conn->query("SELECT id, name FROM articles");
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

<div class="modal fade add_product_text">
  <div class="modal-dialog modal-md modal-content">
    <div class="modal-header">
      <h4 class="modal-title">Text</h4>
    </div>
    <div class="modal-body">
      <label>Text</label>
      <textarea type="text" class="form-control" maxlength="300" name="add_position_text_text" placeholder="Text" ></textarea>
    </div>
    <div class="modal-footer">
      <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
      <button type="submit" class="btn btn-warning" name="add_position_text" value="<?php echo $LAST_POSITION; ?>"><?php echo $lang['ADD']; ?></button>
    </div>
  </div>
</div>

<div class="modal fade proposal_details">
  <div class="modal-dialog modal-lg modal-content" role="document">
    <div class="modal-header"><h5>META</h5></div>
    <div class="modal-body">
      <div class="container-fluid">
        <div class="col-md-2"><?php echo $lang['DATE']; ?>:</div>
        <div class="col-md-4"><input type="text" class="form-control datepicker" name="meta_curDate" value="<?php echo substr($proposal_row['curDate'],0,10); ?>"/></div>
        <div class="col-md-2 text-center"><?php echo $lang['EXPIRATION_DATE']; ?>:</div>
        <div class="col-md-4"><input type="text" class="form-control datepicker" name="meta_deliveryDate" value="<?php echo substr($proposal_row['deliveryDate'],0,10); ?>" /></div>
      </div>
      <br>
      <div class="container-fluid">
        <div class="col-md-2">Kopftext</div>
        <div class="col-md-10"><textarea rows="4" class="form-control" maxlength="400" style="resize: none;" name="meta_header"><?php echo $proposal_row['header']; ?></textarea></div>
      </div>
    </div>
    <div class="modal-header"><h5>Zahlungsdaten</h5></div>
    <div class="modal-body">
      <div class="container-fluid">
        <div class="col-xs-2">Zahlungsweise:</div>
        <div class="col-xs-6">
          <select class="js-example-basic-single" name="meta_paymentMethod">
            <option value="">...</option>
            <?php
            $tax_result = $conn->query("SELECT name FROM paymentMethods");
            while($tax_result && ($tax_row = $tax_result->fetch_assoc())){
              $selected = $tax_row['name'] == $proposal_row['paymentMethod'] ? 'selected' : '';
              echo '<option '.$selected.' value="'.$tax_row['name'].'" >'.$tax_row['name'].'</option>';
            }
            ?>
          </select>
        </div>
        <div class="col-xs-4"><a href="payment" class="btn btn-block btn-warning">Zahlungsarten verwalten</a></div>
      </div>
      <hr>
      <div class="container-fluid">
        <div class="col-xs-2">Versandart:</div>
        <div class="col-xs-4">
          <select class="js-example-basic-single" name="meta_shipmentType">
            <option value="">...</option>
            <?php
            $tax_result = $conn->query("SELECT name FROM shippingMethods");
            while($tax_result && ($tax_row = $tax_result->fetch_assoc())){
              $selected = $tax_row['name'] == $proposal_row['shipmentType'] ? 'selected' : '';
              echo '<option '.$selected.' value="'.$tax_row['name'].'" >'.$tax_row['name'].'</option>';
            }
            ?>
          </select>
        </div>
        <div class="col-xs-2 text-center">Vertreter:</div>
        <div class="col-xs-4">
          <select class="js-example-basic-single" name="meta_representative">
            <option value="">...</option>
            <?php
            $tax_result = $conn->query("SELECT name FROM representatives");
            while($tax_result && ($tax_row = $tax_result->fetch_assoc())){
              $selected = $tax_row['name'] == $proposal_row['representative'] ? 'selected' : '';
              echo '<option '.$selected.' value="'.$tax_row['name'].'" >'.$tax_row['name'].'</option>';
            }
            ?>
          </select>
        </div>
      </div>
      <br>
      <div class="container-fluid">
        <div class="col-xs-2">Porto: (EUR)</div>
        <div class="col-xs-4">
          <input type="number" step="0.01" class="form-control money" name="meta_porto" value="<?php echo $proposal_row['porto']; ?>" />
        </div>
        <div class="col-xs-2 text-center">Porto Steuer: (%)</div>
        <div class="col-xs-4">
          <select class="js-example-basic-single" name="meta_porto_percentage">
            <option value="">...</option>
            <?php
            $tax_result = $conn->query("SELECT DISTINCT percentage FROM taxRates WHERE percentage IS NOT NULL");
            while($tax_result && ($tax_row = $tax_result->fetch_assoc())){
              $selected = $tax_row['percentage'] == $proposal_row['portoRate'] ? 'selected' : '';
              echo '<option '.$selected.' value="'.$tax_row['percentage'].'" >'.$tax_row['percentage'].'% </option>';
            }
            ?>
          </select>
        </div>
      </div>
    </div>
    <div class="modal-footer">
      <button type="submit" class="btn btn-default" data-dismiss="modal">Cancel</button>
      <button type="submit" class="btn btn-warning" name="meta_save"><?php echo $lang['SAVE'];?></button>
    </div>
  </div>
</div>
</form>

<form method="POST">
  <div class="modal fade choose-transition">
    <div class="modal-dialog modal-sm modal-content">
      <div class="modal-header">
        <h3><?php echo $lang['TRANSITION']; ?></h3>
      </div>
      <div class="modal-body">
        <div class="radio">
          <?php
          $transitable = false;
          $current_transition = preg_replace('/\d/', '', $proposal_row['id_number']);
          if($current_transition == 'ANG') {$transitable = true; $available_transitions = array('AUB', 'RE', 'STN');}
          if($current_transition == 'AUB') {$transitable = true; $available_transitions = array('RE', 'LFS', 'STN');}
          if($current_transition == 'RE') {$transitable = true; $available_transitions = array('LFS', 'GUT');}

          foreach($available_transitions as $t){
            echo '<div class="row"><div class="col-xs-6"><label><input type="radio" name="copy_transition" value="'.$t.'" />'.getNextERP($t, $proposal_row['companyID']).'</label></div><div class="col-xs-6">'.$lang['PROPOSAL_TOSTRING'][$t].'</div></div>';
        }
          ?>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" data-dismiss="modal" class="btn btn-default">Cancel</button>
        <button type="submit" class="btn btn-warning" name="translate">OK</button>
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
        console.log(resp);
        var res = resp.split("; ");
        $("[name='add_product_name']").val(res[0]);
        $("[name='add_product_description']").val(res[1]);
        $("[name='add_product_price']").val(res[2]);
        $("[name='add_product_unit']").val(res[3]).trigger('change');
        $("[name='add_product_taxes']").val(res[4]).trigger('change');
        if(res[6] == 'TRUE'){
          $("[name='add_product_as_bar']").prop('checked', true);
        } else {
          $("[name='add_product_as_bar']").prop('checked', false);
        }
        $("[name='add_product_purchase']").val(res[5]);
        $("[name='add_product_addition']").val((res[2]/(res[5]/100))-100);
      },
      error : function(resp){}
    });
  }
}

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
</div>
<?php include dirname(__DIR__) . '/footer.php';?>
