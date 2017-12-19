<?php require 'header.php'; enableToDSGVO($userID); ?>

<div class="page-header"><h3>Template <?php echo $lang['EDIT']; ?></h3></div>

<?php
if(empty($_GET['t'])){
    echo "Invalid Access.";
    include 'footer.php';
    die();
}
$tmpID = intval($_GET['t']);
$result = $conn->query("SELECT name, type, companyID FROM dsgvo_vv_templates WHERE id = $tmpID");
if(!$result || !($row = $result->fetch_assoc()) || !in_array($row['companyID'], $available_companies)){
    echo "Invalid Access.";
    include 'footer.php';
    die();
}

$template_name = $row['name'];
$template_type = $row['type'];

function getSettings($like){
    $result = $conn->query("SELECT opt_name, opt_descr FROM dsgvo_vv_template_settings WHERE templateID = $tmpID AND opt_name LIKE '$like'");
    echo $conn->error;
    return $result;
}

?>

<form method="POST">
<div class="row">
    <label>Name</label>
    <input type="text" name="template_name" value="<?php echo $template_name; ?>" class="form-control" />
</div>

<?php
$i = 1;
$result = getSettings('GEN_%');
while($row = $result->fetch_assoc()){
    $num = ltrim($row['opt_name'], 'GEN_');
    if($num == $i) $i++;

    
}

?>

</form>
<?php require 'footer.php'; ?>