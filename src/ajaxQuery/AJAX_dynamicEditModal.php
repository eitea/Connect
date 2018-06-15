<?php
require dirname(__DIR__) . "/connection.php";
require dirname(__DIR__) . "/utilities.php";
require dirname(__DIR__) . "/language.php";
if(!$_SERVER['REQUEST_METHOD'] == 'POST'){
    die("0");
}
$x = preg_replace("/[^A-Za-z0-9]/", '', $_POST['projectid']);
$isDynamicProjectsAdmin = $_POST['isDPAdmin'];
session_start();
$userID = $_SESSION["userid"] or die("0");
$privateKey = $_SESSION['privateKey'];

$result = $conn->query("SELECT DISTINCT companyID FROM relationship_company_client WHERE userID = $userID OR $userID = 1");
$available_companies = array('-1'); //care
while ($result && ($row = $result->fetch_assoc())) {
    $available_companies[] = $row['companyID'];
}

$result = $conn->query("SELECT DISTINCT userID FROM relationship_company_client WHERE companyID IN(" . implode(', ', $available_companies) . ") OR $userID = 1");
$available_users = array('-1');
while ($result && ($row = $result->fetch_assoc())) {
    $available_users[] = $row['userID'];
}
?>

<div class="modal fade" id="editingModal-<?php echo $x; ?>">
    <div class="modal-dialog modal-lg" role="form">
        <div class="modal-content">
            <form method="POST" id="projectForm<?php echo $x; ?>" enctype="multipart/form-data">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title">Task editieren</h4>
                </div>
                <div class="modal-body">
                    <?php include dirname(__DIR__).'/misc/dynamicproject_gen.php'; ?>
                </div><!-- /modal-body -->
                <div class="modal-footer">
                    <div class="pull-left"><?php echo $x; ?></div>
                    <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo $lang['CANCEL']; ?></button>
                    <button type="submit" class="btn btn-warning blinking" name="editDynamicProject" value="<?php echo $x; ?>" ><?php echo $lang['SAVE']; ?></button>
                </div>
            </form>
        </div>
    </div>
</div>
