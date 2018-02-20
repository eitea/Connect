<?php include dirname(__DIR__) . '/header.php'; enableToClients($userID); ?>
<?php require dirname(__DIR__) . "/misc/helpcenter.php"; ?>
<?php
if($_SERVER['REQUEST_METHOD'] == 'POST'){
  if(isset($_POST['add']) && !empty($_POST['add_name'])){
    $nam = $netto = $skonto1 = $skonto2 = $skonto1Days = $skonto2Days = 0;
    $nam = test_input($_POST['add_name']);
    $skonto1 = $_POST['add_skonto1'];
    $skonto2 = $_POST['add_skonto2'];
    $skonto1Days = $_POST['add_skonto1Days'];
    $skonto2Days = $_POST['add_skonto2Days'];
    $stmt = $conn->prepare("INSERT INTO paymentMethods (name, daysNetto, skonto1, skonto2, skonto1Days, skonto2Days) VALUES (?, ?, ?, ?, ?, ?) ");
    $stmt->bind_param("siddii", $nam, $netto, $skonto1, $skonto2, $skonto1Days, $skonto2Days);
    $stmt->execute();
    $stmt->close();
    if($conn->error){
      echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$conn->error.'</div>';
    } else {
      echo '<div class="alert alert-success"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$lang['OK_ADD'].'</div>';
    }
  } elseif(isset($_POST['delete'])){
    $x = intval($_POST['delete']);
    $conn->query("DELETE FROM paymentMethods WHERE id = $x");
    if($conn->error){
      echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$conn->error.'</div>';
    } else {
      echo '<div class="alert alert-success"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$lang['OK_DELETE'].'</div>';
    }
  } else {
    echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$lang['ERROR_MISSING_FIELDS'].'</div>';
  }
}
?>
<form method="POST">
  <div class="page-header">
    <h3><?php echo $lang['PAYMENT_METHODS']; ?>
      <a role="button" data-toggle="collapse" href="#erp_payment_info"><i class="fa fa-info-circle"></i></a>
      <div class="page-header-button-group">
        <button type="button" class="btn btn-default" title="<?php echo $lang['ADD']; ?>" data-toggle="modal" data-target=".add_unit" ><i class="fa fa-plus"></i></button>
      </div>
    </h3>
  </div>

  <div class="collapse" id="erp_payment_info">
    <div class="well">
      Zahlungsarten sind pro Auftrag und Kunde individuell einstellbar. <br>
      Die Liste der verfügbaren Zahlungsarten wird hier gelistet. Wird eine Zahlungsart ohne Daten erstellt, wird der Name selbst als Zahlungsbedingung aufgelistet. <br>
      Sind konkretere Zahlungsbedingungen vorhanden, werden diese anstelle des Namen angezeigt. <br>
      Eine Zahlungsart die bereits von einem Kunden oder einem Auftrag referenziert wurde, kann erst dann gelöscht werden wenn diese Referenz aufgelöst wurde.
    </div>
  </div>

  <table class="table table-hover">
    <thead>
      <th>Name</th>
      <th><?php echo $lang['DAYS']; ?> Netto</th>
      <th>Skonto1</th>
      <th>Skonto2</th>
      <th></th>
    </thead>
    <tbody>
      <?php
      $result = $conn->query("SELECT * FROM paymentMethods");
      while ($result && ($row = $result->fetch_assoc())) {
        if($row['skonto1']) $skonto1 = $row['skonto1'].'% '.$lang['WITHIN'].' '.$row['skonto1Days'].' '.$lang['DAYS']; else $skonto1 = '';
        if($row['skonto2']) $skonto2 = $row['skonto2'].'% '.$lang['WITHIN'].' '.$row['skonto2Days'].' '.$lang['DAYS']; else $skonto2 = '';
        $b = '<button type="submit" class="btn btn-default" name="delete" value="'.$row['id'].'" ><i class="fa fa-trash-o"></i></button>';
        $del_res = $conn->query("SELECT id FROM proposals WHERE paymentMethod = '".$row['name']."' ");
        if($del_res && $del_res->num_rows > 0) $b = '';
        $del_res = $conn->query("SELECT id FROM clientInfoData WHERE paymentMethod = '".$row['name']."' ");
        if($del_res && $del_res->num_rows > 0) $b = '';
        echo '<tr>';
        echo '<td>'.$row['name'].'</td>';
        echo '<td>'.$row['daysNetto'].'</td>';
        echo '<td>'.$skonto1.'</td>';
        echo '<td>'.$skonto2.'</td>';
        echo '<td>'.$b.'</td>';
        echo '</tr>';
      }
      ?>
    </tbody>
  </table>
</form>
<?php include dirname(__DIR__) . '/footer.php'; ?>

<form method="POST">
  <div class="modal fade add_unit">
    <div class="modal-dialog modal-content modal-lg">
      <div class="modal-header">
        <h4><?php echo $lang['ADD']; ?></h4>
      </div>
      <div class="modal-body">
        <div class="container-fluid">
        <div class="row">
          <div class="col-xs-2">Name:</div>
          <div class="col-xs-8"><input type="text" name="add_name" class="form-control" maxlength="100" placeholder="z.B.: 3% Skonto 8 Tage, 30 Tage Netto" /><br></div>
        </div>
        <div class="row">
          <div class="col-xs-2">Netto:</div>
          <div class="col-xs-3"><input type="number" class="form-control" name="add_daysNetto" placeholder="0" /></div>
          <div class="col-xs-2">Tage</div>
        </div>
        <br>
        <div class="row">
          <div class="col-xs-2">Skonto 1:</div>
          <div class="col-xs-3"><input type="number" step="0.01" class="form-control" name="add_skonto1" placeholder="0" /></div>
          <div class="col-xs-2 text-center">% Innerhalb von</div>
          <div class="col-xs-3"><input type="number" class="form-control" name="add_skonto1Days" placeholder="0" /></div>
          <div class="col-xs-1">Tagen</div>
        </div>
        <br>
        <div class="row">
          <div class="col-xs-2">Skonto 2:</div>
          <div class="col-xs-3"><input type="number" step="0.01" class="form-control" name="add_skonto2" placeholder="0" /></div>
          <div class="col-xs-2 text-center">% Innerhalb von</div>
          <div class="col-xs-3"><input type="number" class="form-control" name="add_skonto2Days" placeholder="0" /></div>
          <div class="col-xs-1">Tagen</div>
        </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button"  class="btn btn-default" data-dismiss="modal" ><?php echo $lang['CANCEL']; ?></button>
        <button type="submit" name="add" class="btn btn-warning"><?php echo $lang['ADD']; ?></button>
      </div>
    </div>
  </div>
</form>
<?php include dirname(__DIR__) . '/footer.php'; ?>
