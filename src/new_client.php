<div style="display:hidden">
  <form method="post" id="filterCompany_form">
    <input type="hidden" name="filterCompanyID" value="<?php echo $filterCompanyID; ?>" />
  </form>
</div>

<?php
if($_SERVER["REQUEST_METHOD"] == "POST"){
  if(isset($_POST['create_client']) && !empty($_POST['create_client_name']) && $_POST['create_client_company'] != 0){
    $name = test_input($_POST['create_client_name']);
    $filterCompanyID = $companyID = intval($_POST['create_client_company']);

    $sql = "INSERT INTO $clientTable (name, companyID, clientNumber) VALUES('$name', $companyID, '".$_POST['clientNumber']."')";
    if($conn->query($sql)){ //if ok, give him default projects
      $id = $conn->insert_id;
      $sql = "INSERT INTO $projectTable (clientID, name, status, hours, field_1, field_2, field_3)
      SELECT '$id', name, status, hours, field_1, field_2, field_3 FROM $companyDefaultProjectTable WHERE companyID = $companyID";
      $conn->query($sql);
      //and his details
      $conn->query("INSERT INTO $clientDetailTable (clientID) VALUES($id)");
    }
    if(mysqli_error($conn)){
      echo mysqli_error($conn);
    } else {
      echo '<script>document.getElementById("filterCompany_form").submit();</script>';
    }
  } elseif(isset($_POST['create_client'])){
    echo '<div class="alert alert-danger fade in">';
    echo '<a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>';
    echo '<strong>Error: </strong>'.$lang['ERROR_MISSING_FIELDS'];
    echo '</div>';
  }
}
?>

<a class="btn btn-warning" data-toggle="modal" data-target="#create_client"><?php echo $lang['NEW_CLIENT_CREATE']; ?></a>

<div class="modal fade" id="create_client" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
  <div class="modal-dialog modal-content" role="document">
    <form method="post">
      <div class="modal-header">
        <h4><?php echo $lang['NEW_CLIENT_CREATE']; ?></h4>
      </div>
      <div class="modal-body">
        <br>
        <input type="text" class="form-control required-field" name="create_client_name" placeholder="Name..." onkeydown="if (event.keyCode == 13) return false;">
        <br>
        <div class="row">
          <div class="col-md-6">
            <select name="create_client_company" class="js-example-basic-single" onchange="showClients(this.value)" style="width:200px">
              <?php
              $result_cc = $conn->query("SELECT * FROM $companyTable WHERE id IN (".implode(', ', $available_companies).")");
              while ($result_cc && ($row_cc = $result_cc->fetch_assoc())) {
                $cmpnyID = $row_cc['id'];
                $cmpnyName = $row_cc['name'];
                if(isset($filterCompanyID) && $filterCompanyID == $cmpnyID){
                  echo "<option selected name='cmp' value='$cmpnyID'>$cmpnyName</option>";
                } else {
                  echo "<option name='cmp' value='$cmpnyID'>$cmpnyName</option>";
                }
              }
              ?>
            </select>
          </div>
          <div class="col-md-6">
            <input type="text" class="form-control" name="clientNumber" placeholder="#" >
            <small> &nbsp Kundennummer - Optional</small>
          </div>
        </div>
        <input type="hidden" name="filterCompanyID" value="<?php echo $filterCompanyID; ?>" />
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
        <button type="submit" class="btn btn-warning " name="create_client"> <?php echo $lang['ADD']; ?></button>
      </div>
    </form>
  </div>
</div>
