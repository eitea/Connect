<div class="row">
  <?php
  $filterCompany = empty($filterings['company']) ? 0 : $filterings['company'];
  $filterClient = empty($filterings['client']) ? 0 : $filterings['client'];
  $result_fc = mysqli_query($conn, "SELECT * FROM companyData WHERE id IN (".implode(', ', $available_companies).")");
  if($result_fc && $result_fc->num_rows > 1){
    echo '<div class="col-sm-6"><select class="js-example-basic-single" name="filterCompany" onchange="select_client.showClients(this.value, '.$filterClient.');" >';
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
  <div class="col-sm-6">
    <select id="clientHint" class="js-example-basic-single" name="filterClient">
    </select>
  </div>
</div>
<script>
var select_client = {
  showClients: function(company, client){
    if(company != ""){
      $.ajax({
        url:'ajaxQuery/AJAX_getClient.php',
        data:{companyID:company, clientID:client},
        type: 'get',
        success : function(resp){
          $("#clientHint").html(resp);
        },
        error : function(resp){}
      });
    }
  }
};
</script>

<?php
if($filterCompany){
  echo '<script>';
  echo "select_client.showClients($filterCompany, $filterClient)";
  echo '</script>';
}
?>
