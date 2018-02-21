<?php
if(isset($_POST['lockAccountings'])){
    $conn->query("DELETE FROM accountingLocks");
    $stmt = $conn->prepare("INSERT INTO accountingLocks (id, companyID, lockDate) VALUES(?, $cmpID ,?)");
    $stmt->bind_param('is', $i, $val);
    if(isset($_POST['lockdown_month'])){
        for($i = 1; $i <= count($_POST['lockdown_month']); $i++){
            $val = $_POST['lockdown_month'][$i -1];
            $stmt->execute();
        }
    }
    $stmt->close();
}
$result = $conn->query("SELECT lockDate FROM accountingLocks WHERE companyID = $cmpID");
$result = array_column($result->fetch_all(), 0);
?>
<div id="lockAccountingDropdown" class="dropdown" style="display:inline">
    <button type="button" class="btn btn-default" data-toggle="dropdown" ><i class="fa fa-calendar-minus-o"></i><?php echo $lang['LOCK_BOOKING_MONTH']; ?></button>
    <div class="dropdown-menu" style="width:300px">
        <form method="POST">
            <div class="container-fluid"><br>
                <div class="col-xs-6" style="text-align:center">
                    <ul class="nav nav-pills nav-stacked">
                    <?php
                    $tabContent = '';
                    for($i = 2017; $i < 2030; $i++){
                        echo "<li><a href='#$i' role='tab' data-toggle='tab' >$i</a></li>";
                        $tabContent.= "<div class='tab-pane' id ='$i'>";
                        for($j = 1; $j < 13; $j++){
                            $date = $i.'-'.sprintf("%02d", $j).'-01';
                            $checked = '';
                            if(in_array($date, $result)) $checked = 'checked';
                            $tabContent.= '<label><input type="checkbox" name="lockdown_month[]" value="'.$date.'" '.$checked.' />'.$lang['MONTH_TOSTRING'][$j].'</label><br>';
                        }
                        $tabContent.= "</div>";
                    }
                    ?>
                    </ul>
                    <br>
                </div>
                <div class="col-xs-6"><div class="tab-content"><?php echo $tabContent; ?></div></div>
                <div class="col-xs-12" style="text-align:center"><button type="submit" class="btn btn-warning" name="lockAccountings"><?php echo $lang['SAVE']; ?></button><br><br></div>
            </div>
        </form>
    </div>
</div>