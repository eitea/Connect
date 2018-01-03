<div class="row">
  <?php
  $filterCompany = empty($filterings['company']) ? 0 : $filterings['company'];
  $result_fc = mysqli_query($conn, "SELECT * FROM companyData WHERE id IN (".implode(', ', $available_companies).")");
  if($result_fc && $result_fc->num_rows > 1){
    echo '<div class="col-sm-12"><label>'.$lang['COMPANY'].'</label><select class="js-example-basic-single" name="filterCompany" ;" >';
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
  
</div>
