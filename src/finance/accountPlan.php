<?php include dirname(__DIR__) . '/header.php';
enableToFinance($userID); ?>
<?php require dirname(__DIR__) . "/misc/helpcenter.php"; ?>

<div class="page-header"><h3><?php echo $lang['ACCOUNT_PLAN']; ?><div class="page-header-button-group">
            <button type="button" class="btn btn-default" data-toggle="modal" data-target=".add-finance-account" title="<?php echo $lang['ADD']; ?>" ><i class="fa fa-plus"></i></button>
        </div></h3></div>

<?php
if (isset($_GET['n']) && in_array($_GET['n'], $available_companies)) {
    $cmpID = $_GET['n'];
} else {
    echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>Zugriff verweigert.</div>';
    include dirname(__DIR__) . '/footer.php';
    die();
}

if (isset($_POST['addFinanceAccount'])) {
    if (!empty($_POST['addFinance_name']) && $_POST['addFinance_num'] < 9999 && $_POST['addFinance_num'] >= 0 && $_POST['addFinance_type'] < 5) {
        $name = test_input($_POST['addFinance_name']);
        $num = intval($_POST['addFinance_num']);
        $type = intval($_POST['addFinance_type']);
        $opt = 'STAT';
        if (isset($_POST['addOption']))
            $opt = 'CONT';
        $conn->query("INSERT INTO accounts (companyID, num, name, type, options) VALUES('$cmpID', $num, '$name', '$type', '$opt')");
        if ($conn->error) {
            echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>' . $lang['ERROR_DUPLICATE'] . $conn->error . '</div>';
        } else {
            echo '<div class="alert alert-success"><a href="#" data-dismiss="alert" class="close">&times;</a>' . $lang['OK_ADD'] . '</div>';
        }
    } else {
        echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>' . $lang['ERROR_INVALID_DATA'] . '</div>';
    }
} elseif (isset($_POST['delete'])) {
    $id = intval($_POST['delete']);
    $result = $conn->query("SELECT * FROM account_balance WHERE accountID = $id");
    echo $conn->error;
    if (!$result || $result->num_rows < 1) {
        $conn->query("DELETE FROM accounts WHERE id = $id AND companyID = $cmpID ");
        echo $conn->error;
        echo '<div class="alert alert-success"><a href="#" data-dismiss="alert" class="close">&times;</a>' . $lang['OK_DELETE'] . '</div>';
    } else {
        echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>' . $lang['ERROR_DELETE_ACCOUNT'] . '</div>';
    }
} elseif (!empty($_POST['saveNameChange']) && !empty("changeName")) {
    $val = test_input($_POST['changeName']);
    $id = intval($_POST['saveNameChange']);
    $opt = 'STAT';
    if (isset($_POST['changeOpt']))
        $opt = 'CONT';
    $conn->query("UPDATE accounts SET name = '$val', options = '$opt' WHERE id = $id AND companyID = $cmpID");
    if ($conn->error) {
        echo '<div class="alert alert-success"><a href="#" data-dismiss="alert" class="close">&times;</a>' . $conn->error . '</div>';
    } else {
        echo '<div class="alert alert-success"><a href="#" data-dismiss="alert" class="close">&times;</a>' . $lang['OK_SAVE'] . '</div>';
    }
}
?>
<br>
<table class="table table-hover">
    <thead><tr>
            <th>Nr.</th>
            <th>Name</th>
            <th><?php echo $lang['TYPE']; ?></th>
            <th>Forlaufende Nr</th>
            <th></th>
        </tr></thead>
    <tbody>
        <?php
        $modals = '';
        $result = $conn->query("SELECT * FROM accounts WHERE companyID = $cmpID ");
        while ($result && ($row = $result->fetch_assoc())) {
            echo '<tr>';
            echo '<td>' . $row['num'] . '</td>';
            echo '<td>' . $row['name'] . '</td>';
            echo '<td>' . $lang['ACCOUNT_TOSTRING'][$row['type']] . '</td>';
            if ($row['options'] == 'CONT') {
                echo '<td>' . $lang['YES'] . '</td>';
                $checked = 'checked';
            } else {
                echo '<td>' . $lang['NO'] . '</td>';
                $checked = '';
            }
            echo '<td>';
            echo '<form method="POST" style="display:inline"><button type="submit" name="delete" value="' . $row['id'] . '" title="' . $lang['DELETE'] . '" class="btn btn-default"><i class="fa fa-trash-o"></i></button></form>';
            echo '<a href="account?v=' . $row['id'] . '" class="btn btn-default" title="Zum Konto" ><i class="fa fa-arrow-right"></i></a>';
            echo '<button type="button" class="btn btn-default" data-toggle="modal" data-target=".editName-' . $row['id'] . '" title="' . $lang['EDIT'] . '" ><i class="fa fa-pencil"></i></button>';
            echo '</td>';
            echo '</tr>';

            $modals .= '<div class="modal fade editName-' . $row['id'] . '"><div class="modal-dialog modal-content modal-md"><form method="POST">
                    <div class="modal-header"><h3>' . $lang['EDIT'] . '</h3></div>
                    <div class="modal-body"><label>Name</label><br><input type="text" class="form-control" name="changeName" value="' . $row['name'] . '" maxlength="20" ><br>
                    <label><input type="checkbox" name="changeOpt" ' . $checked . ' value="true" /> Fortlaufende Nr. </label></div>
                    <div class="modal-footer"><button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>                    
                    <button type="submit" class="btn btn-warning" name="saveNameChange" value="' . $row['id'] . '">' . $lang['SAVE'] . '</button></div></form></div></div>';
        }
        ?>
    </tbody>
</table>
<?php echo $modals; ?>

<div class="modal fade add-finance-account">
    <div class="modal-dialog modal-content modal-md">
        <form method="POST">
            <div class="modal-header"><h4><?php echo $lang['ADD']; ?></h4></div>
            <div class="modal-body">
                <div class="container-fluid">
                    <div class="col-md-6"><label>Nr.</label>
                        <input id="account2" name="addFinance_num" type="number" class="form-control" max="9999"/><br>
                    </div>
                    <div class="col-md-4">
                        <br><label><input type="checkbox" name="addOption" value="true" />Forlaufende Nr.</label>
                    </div>
                </div>
                <div class="container-fluid">
                    <div class="col-md-6"><label>Name</label>
                        <input type="text" name="addFinance_name" class="form-control" maxlength="20" placeholder="Name"/>
                    </div>
                    <div class="col-md-6"><label><?php echo $lang['TYPE']; ?></label>
                        <select class="js-example-basic-single" name="addFinance_type">
                            <option value="1"><?php echo $lang['ACCOUNT_TOSTRING'][1]; ?></option>
                            <option value="2"><?php echo $lang['ACCOUNT_TOSTRING'][2]; ?></option>
                            <option value="3"><?php echo $lang['ACCOUNT_TOSTRING'][3]; ?></option>
                            <option value="4"><?php echo $lang['ACCOUNT_TOSTRING'][4]; ?></option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                <button type="submit" name="addFinanceAccount" class="btn btn-warning"><?php echo $lang['SAVE']; ?></button>
            </div>
        </form>
    </div>
</div>

<script>
    $('.table').DataTable({
        order: [[0, "asc"]],
        deferRender: true,
        responsive: true,
        autoWidth: false,
        language: {<?php echo $lang['DATATABLES_LANG_OPTIONS']; ?>}
    });
    setTimeout(function () {
        window.dispatchEvent(new Event('resize'));
        $('.table').trigger('column-reorder.dt');
    }, 500);

    $('.add-finance-account').on('shown.bs.modal', function () {
        $('#account2').focus();
    });
</script>
<?php include dirname(__DIR__) . '/footer.php'; ?>