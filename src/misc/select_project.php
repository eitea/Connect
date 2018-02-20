<div class="row">
  <?php
  $filterCompany = empty($filterings['company']) ? (empty($filterCompany) ? 0 : $filterCompany) : $filterings['company'];
  $filterClient = empty($filterings['client']) ? empty($filterClient) ? 0 : $filterCompany : $filterings['client'];
  $filterProject = empty($filterings['project']) ? empty($filterProject) ? 0 : $filterCompany : $filterings['project'];
  $result_fc = mysqli_query($conn, "SELECT * FROM companyData WHERE id IN (".implode(', ', $available_companies).")");
  if($result_fc && $result_fc->num_rows > 1){
    echo '<div class="col-sm-4"><label>'.$lang['COMPANY'].'</label><select class="js-example-basic-single" name="filterCompany" onchange="select_client.showClients(this.value, '.$filterClient.');" >';
    echo '<option value="0">...</option>';
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
  <div class="col-sm-4">
    <label><?php echo $lang['CLIENT']; ?></label>
    <select id="clientHint" class="js-example-basic-single" name="filterClient" onchange="select_client.showProjects(this.value, '<?php echo $filterProject; ?>');">
    </select>
  </div>
  <div class="col-sm-4">
    <label><?php echo $lang['PROJECT']; ?></label>
    <select id="projectHint" class="js-example-basic-single" name="filterProject">
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
  },
  showProjects: function(client, project){
    if(client != ""){
      $.ajax({
        url:'ajaxQuery/AJAX_getProjects.php',
        data:{clientID:client, projectID:project},
        type: 'get',
        success : function(resp){
          $("#projectHint").html(resp);
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
  echo "select_client.showClients($filterCompany, $filterClient);";
  echo "select_client.showProjects($filterClient, $filterProject);";
  echo '</script>';
}
?>
