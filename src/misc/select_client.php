  <?php
  if(empty($filterCompany)){ $filterCompany = 0; }
  if(empty($filterClient)){ $filterClient = 0; }
  $result_fc = mysqli_query($conn, "SELECT * FROM companyData WHERE id IN (".implode(', ', $available_companies).")");
  if($result_fc && $result_fc->num_rows > 1){
    echo '<div class="col-sm-2"><select class="js-example-basic-single" name="filterCompany" onchange="showClients(this.value, '.$filterClient.');" >';
    echo '<option value="0">'.$lang['COMPANY'].'... </option>';
    while($result && ($row_fc = $result_fc->fetch_assoc())){
      $checked = '';
      if($filterCompany == $row_fc['id']) {
        $checked = 'selected';
      }
      echo "<option $checked value='".$row_fc['id']."' >".$row_fc['name']."</option>";
    }
    echo '</select></div>';
  } else {
    $filterCompany = $available_companies[1];
  }
  ?>
<div class="col-sm-2">
  <select id="clientHint" class="js-example-basic-single" name="filterClient">
  </select>
</div>
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
</script>

<?php
if($filterCompany){
  echo '<script>';
  echo "showClients($filterCompany, $filterClient)";
  echo '</script>';
}
?>
