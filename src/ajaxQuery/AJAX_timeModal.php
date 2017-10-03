<?php
require dirname(__DIR__)."/connection.php";
require dirname(__DIR__)."/createTimestamps.php";
require dirname(__DIR__)."/language.php";

$i = intval($_GET['timestampID']);

$A = $B = $_GET['date'].' 08:00';
$row['status'] = $row['timeToUTC'] = 0;
if($i > 0){
    $result = $conn->query("SELECT * from logs WHERE indexIM = $i");
    $row = $result->fetch_assoc();
    $A = carryOverAdder_Hours($row['time'], $row['timeToUTC']);
    if($row['timeEnd'] && $row['timeEnd'] != '0000-00-00 00:00:00'){
        $B = carryOverAdder_Hours($row['timeEnd'], $row['timeToUTC']);
    }
}
?>

<form method="POST">
    <div class="modal fade editingModal-<?php echo $_GET['index']; ?>" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-md">
            <div class="modal-content">
                <div class="modal-header"><h4 class="modal-title"><?php echo substr($A, 0, 10); ?></h4></div>
                <div class="modal-body">
                    <div class="row">
                    <?php          
                    echo '<div class="col-md-3"><label>'.$lang['ACTIVITY'].'</label>';
                    echo "<select name='newActivity' class='js-example-basic-single'>";
                    for($j = 0; $j < 7; $j++){
                        if($row['status'] == $j){
                        echo "<option value='$j' selected>". $lang['ACTIVITY_TOSTRING'][$j] ."</option>";
                        } else {
                        echo "<option value='$j'>". $lang['ACTIVITY_TOSTRING'][$j] ."</option>";
                        }
                    }
                    echo '</select></div><div class="col-md-3">';
                    if($i < 1){ //existing timestamps cant have timeToUTC edited
                        echo '<label>'.$lang['TIMEZONE'].'</label><select name="creatTimeZone" class="js-example-basic-single">';
                        for($i_utc = -12; $i_utc <= 12; $i_utc++){
                        if($i_utc == $timeToUTC){
                            echo "<option name='ttz' value='$i_utc' selected>UTC " . sprintf("%+03d", $i_utc) . "</option>";
                        } else {
                            echo "<option name='ttz' value='$i_utc'>UTC " . sprintf("%+03d", $i_utc) . "</option>";
                        }
                        }
                        echo "</select>";
                    }
                    echo '</div>';
                    echo '<div class="col-md-6">';
                    echo '<label>'.$lang['LUNCHBREAK'].' ('.$lang['HOURS'].')</label>';
                    if($i < 1){
                        echo '<input type="number" step="0.01" class="form-control" name="newBreakValues" value="0.0" style="width:100px" />';
                    } else {
                        echo ': '.$lang['ADDITION']."<input type='number' step='0.01' name='addBreakValues' value='0.0' class='form-control' style='width:100px' />";
                    }
                    echo '<small>0,5h = 30min</small></div>';
                    ?>
                    </div>
                    <br><br>
                    <div class="row">
                    <div class="col-md-6">
                        <label><?php echo $lang['BEGIN']; ?></label>
                        <div class="checkbox">
                        <input class='form-control datetimepicker' onkeydown="return event.keyCode != 13;" name="timesFrom" value="<?php echo substr($A,0,16); ?>"/>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label><?php echo $lang['END']; ?></label>
                        <div class="checkbox">
                        <label>
                            <input type="radio" name="is_open" value="0" checked="checked" />
                            <input style="display:inline;max-width:80%;" class='form-control datetimepicker' onkeydown="return event.keyCode != 13;" name="timesTo" value="<?php echo substr($B,0,16) ?>"/>
                        </label>
                        <br>
                        <label><input type="radio" name="is_open" value="1" /><?php echo $lang['OPEN']; ?></label>
                        </div>
                    </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                    <button type="submit" name="saveChanges" class="btn btn-warning" title="Save" value="<?php echo $i; ?>"><?php echo $lang['SAVE']; ?></button>
                </div>
            </div>
        </div>
    </div>
</form>