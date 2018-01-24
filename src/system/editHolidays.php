<?php include dirname(__DIR__) . '/header.php'; enableToCore($userID)?>

<div class="page-header">
  <h3>
    <?php echo $lang['HOLIDAYS']; ?>
    <div class="page-header-button-group">
      <button class="btn btn-default" type="button" data-toggle="modal" data-target=".addHolidays" title="<?php echo $lang['ADD']; ?>"><i class="fa fa-plus"></i></button>
      <button type="submit" form="holidays" class="btn btn-default" name="holidayDelete" title="<?php echo $lang['DELETE_SELECTION']; ?>"><i class="fa fa-trash-o"></i></button>
    </div>
  </h3>
</div>

<?php
if(isset($_POST['holidayDelete']) && isset($_POST['checkingIndeces'])) {
  $index = $_POST["checkingIndeces"];
  foreach ($index as $x) {
    $sql = "DELETE FROM " . $holidayTable . " WHERE begin='$x';";
    if (!$conn->query($sql)) {
      echo mysqli_error($conn);
    }
  }
} elseif(isset($_POST['holidayAdd'])){
  if(!empty($_POST['holidayName']) && isset($_POST['holidayDate'])) {
    $holidayName = test_input($_POST['holidayName']);
    $holidayStart = $_POST['holidayDate'] .' 08:00:00';
    $sql = "INSERT INTO $holidayTable (name, begin, end) VALUES('$holidayName', '$holidayStart', '$holidayStart')";
    $conn->query($sql);
    echo mysqli_error($conn);
  }
}
?>

<form id="holidays" method="POST">
  <table class="table table-hover" cellspacing="0" width="100%">
    <thead>
      <tr>
        <th><?php echo $lang['DELETE']; ?></th>
        <th><?php echo $lang['DESCRIPTION']; ?></th>
        <th><?php echo $lang['DATE']; ?></th>
      </tr>
    </thead>
    <tbody>
      <?php
      $query = "SELECT * FROM $holidayTable";
      $result = mysqli_query($conn, $query);
      while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td><input type=checkbox name='checkingIndeces[]' value='".$row['begin']."' /></td>";
        echo "<td>".$row['name']."</td><td> ". substr($row['begin'],0,10)."</td>";
        echo "</tr>";
      }
      ?>
    </tbody>
  </table>
</form>
<form method="POST">
  <div class="modal fade addHolidays">
    <div class="modal-dialog modal-content modal-sm">
      <div class="modal-header">
        <h4><?php echo $lang['ADD']; ?></h4>
      </div>
      <div class="modal-body">
        <label>Date</label>
        <input type="text" class="form-control datepicker" name="holidayDate" placeholder="YYYY-MM-DD"/>
        <br>
        <label>Name</label>
        <input type="text" class="form-control" name="holidayName" />
        <small><?php echo $lang['INFO_HOLIDAY']; ?></small>
      </div>
      <div class="modal-footer">
        <button type="button"  class="btn btn-default" data-dismiss="modal" ><?php echo $lang['CANCEL']; ?></button>
        <button type="submit"  class="btn btn-warning" name="holidayAdd" ><?php echo $lang['ADD']; ?></button>
      </div>
    </div>
  </div>
</form>

<script>
$('.table').DataTable({
  order: [[ 2, "asc" ]],
  columns: [{orderable: false}, null, null],
  responsive: true,
  colReorder: true,
  language: {
    <?php echo $lang['DATATABLES_LANG_OPTIONS']; ?>
  },
  dom: '<"top"i>rt<"bottom"flp><"clear">'
});
</script>

<!-- /BODY -->
<?php include dirname(__DIR__) . '/footer.php'; ?>
