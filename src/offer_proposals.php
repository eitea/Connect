<?php require 'header.php'; enableToERP($userID); ?>
<div class="page-header">
  <h3><?php echo $lang['OFFER']; ?></h3>
</div>
<?php
$filterCompany = $filterClient = $filterProposal = 0;
?>

<form method="POST">
  <select style='width:200px' name="filterCompany" class="js-example-basic-single" onchange="showClients(this.value)">
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
  <select id="clientHint" style='width:200px' class="js-example-basic-single" name="filterClient">
  </select>
  <button type="submit" class="btn btn-warning btn-sm">Filter</button>

  <br><hr><br>
  <table class="table table-hover">
    <thead>
      <th>ID</th>
      <th><?php echo $lang['CLIENT']; ?></th>
      <th><?php echo $lang['PRODUCTS']; ?></th>
      <th>Option</th>
    </thead>
    <tbody>
      <?php
      $filterClient_query = $filterClient ? " AND clientData.id = $filterClient" : "";

      $result = $conn->query("SELECT proposals.*, clientData.name as clientName
      FROM proposals INNER JOIN clientData ON proposals.clientID = clientData.id
      WHERE clientData.companyID = $filterCompany $filterClient_query");

      while($result && ($row = $result->fetch_assoc())){
        $i = $row['id'];
        echo '<tr>';
        echo '<td>'.$row['id_number'].'</td>';
        echo '<td>'.$row['clientName'].'</td>';
        echo '<td><dl>';
        $productRes = $conn->query("SELECT * FROM products WHERE proposalID = $i");
        while($productRes && ($prodrow = $productRes->fetch_assoc())){
          echo '<dt>'.$prodrow['quantity'].'x '.$prodrow['name'].'</dt>';
          echo '<dd>'.$prodrow['description'].'</dd>';
        }
        echo '</dl></td>';
        echo '</tr>';
      }
      ?>
    </tbody>
  </table>
  <br><hr><br>
</form>

<form method="POST" action="offer_proposal_edit.php">
  <button type="submit" class="btn btn-warning" name="new_proposal" value="<?php echo $filterClient; ?>"><?php echo $lang['NEW_OFFER']; ?></button>
</form>
<?php
if($filterCompany){ //i want my filter displayed even if there were no bookings
  echo "<script> showClients($filterCompany, $filterClient); </script>";
}
?>
<script>
function showClients(cmpID, clientID) {
  if (cmpID != "") {
    if (window.XMLHttpRequest) {
      // code for IE7+, Firefox, Chrome, Opera, Safari
      xmlhttp = new XMLHttpRequest();
    } else {
      // code for IE6, IE5
      xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
    }
    xmlhttp.onreadystatechange = function() {
      if (xmlhttp.readyState == 4 && xmlhttp.status == 200) {
        document.getElementById("clientHint").innerHTML = xmlhttp.responseText;
      }
    };
    xmlhttp.open("GET","ajaxQuery/AJAX_getClient.php?company="+cmpID+"&p="+clientID,true);
    xmlhttp.send();
  }
}
</script>
<?php include 'footer.php'; ?>
