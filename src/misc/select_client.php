<?php
$filterCompany = 0;
if(empty($filterClient)){ $filterClient = 0; }
$result_fc = mysqli_query($conn, "SELECT * FROM companyData WHERE id IN (".implode(', ', $available_companies).")");
if($result_fc && $result_fc->num_rows > 1){
  echo '<select "style=min-width:200px "name="filterCompany" class="js-example-basic-single" onchange="showClients(this.value, '.$filterClient.');" >';
  echo '<option value="0">'.$lang['COMPANY'].'... </option>';
  while($result && ($row_fc = $result_fc->fetch_assoc())){
    $checked = '';
    if($filterCompany == $row_fc['id']) {
      $checked = 'selected';
    }
    echo "<option $checked value='".$row_fc['id']."' >".$row_fc['name']."</option>";
  }
  echo '</select>';
} else {
  $filterCompany = $available_companies[1];
}
?>

<select id="clientHint" style='width:200px' class="js-example-basic-single" name="filterClient">
</select>

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
  echo "showClients($filterCompany, 0)";
  echo '</script>';
}
?>
