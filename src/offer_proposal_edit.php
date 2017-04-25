<?php require 'header.php'; enableToERP($userID); ?>
<?php
$clientID = $proposalID = 0;
if(isset($_POST['new_proposal'])){
  $clientID = intval($_POST['new_proposal']);
}

if($proposalID){
  $result = $conn->query("SELECT * FROM proposals WHERE id = $proposalID");
  $row = $result->fetch_assoc();
  $id = $row['id'];
  $id_num = $row['id_number'];
} else {
  $id = 0;
  $id_num = 'AT'.substr(strtotime(getCurrentTimestamp()), 2, 8);
}
?>
<div class="page-header">
  <h3><?php echo $lang['OFFER'] .' - '. $lang['EDIT']." <small>$id_num</small>"; ?></h3>
</div>

<form method="POST">
  <div class="container-fluid">
    <div class="col-md-2">
      <?php echo $lang['CLIENT']; ?>:
    </div>
    <div class="col-md-10">
      <select style='width:200px' name="filterCompany" class="js-example-basic-single" onchange="showClients(this.value);">
        <?php
        $sql = "SELECT * FROM companyData WHERE id IN (".implode(', ', $available_companies).")";
        $result = mysqli_query($conn, $sql);
        if($result && $result->num_rows > 1) {
          echo '<option value="0">Select Company...</option>';
        } else {
          $filterCompany = $available_companies[0];
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
      <select id="clientHint" style='width:200px' class="js-example-basic-single" name="filterClient" onchange="showProposals(this.value);">
      </select>
      <select id="proposalHint" style='width:200px' class="js-example-basic-single" name="filterProposal">
      </select>
    </div>
  </div>
  <div class="container-fluid">
    <div class="col-md-2">
      <?php echo $lang['PRODUCTS']; ?>:
    </div>
  </div>

  <br><br>
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
      $result = $conn->query("SELECT * FROM products WHERE proposalID = $proposalID");
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

  <br><hr><br>
  <div class="container-fluid">
    <div class="col-md-4">
      <input type="text" class="form-control" name="add_product_name" placeholder="New Product Name"/>
    </div>
    <div class="col-md-2">
      <input type="number" class="form-control" name="add_product_quantity" placeholder="Quantity" />
    </div>
    <div class="col-md-2">
      <input type="number" step="any" class="form-control" name="add_product_price" placeholder="Price" />
    </div>
    <div class="col-md-1">
      <button type="submit" class="btn btn-warning" name="add_product">+</button>
    </div>
  </div>
</form>


<script>
function showClients(cmpID, clientID){
  if (cmpID != "") {
    if(window.XMLHttpRequest){
      // code for IE7+, Firefox, Chrome, Opera, Safari
      xmlhttp = new XMLHttpRequest();
    } else {
      // code for IE6, IE5
      xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
    }
    xmlhttp.onreadystatechange = function(){
      if (xmlhttp.readyState == 4 && xmlhttp.status == 200) {
        document.getElementById("clientHint").innerHTML = xmlhttp.responseText;
      }
    };
    xmlhttp.open("GET","ajaxQuery/AJAX_getClient.php?company="+cmpID+"&p="+clientID,true);
    xmlhttp.send();
  }
}
function showProposals(clientID, proposalID){
  if(clientID != ""){
    if(window.XMLHttpRequest){
      xmlhttp = new XMLHttpRequest();//IE7+, Firefox, Chrome, Opera, Safari
    } else {
      xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");//IE6, IE5
    }
    xmlhttp.onreadystatechange = function(){
      if (xmlhttp.readyState == 4 && xmlhttp.status == 200) {
        document.getElementById("proposalHint").innerHTML = xmlhttp.responseText;
      }
    };
    xmlhttp.open("GET","ajaxQuery/AJAX_getProposals.php?client="+clientID+"&p="+proposalID,true);
    xmlhttp.send();
  }
}
</script>
<?php require 'footer.php';?>
