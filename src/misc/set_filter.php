<?php
/**
* Company [id]
* Client [id]
* Project [id, productive]
* Users [id]
* Bookings [charged, break, drive]
* Logs [activity, hideAll]
* Dates [date]
* Procedures [transitions[id], status, hideAll]
**/
if(!empty($_SESSION['filterings']['savePage']) && $_SESSION['filterings']['savePage'] != $this_page){
  $_SESSION['filterings'] = array();
}
if(isset($_POST['set_filter_apply'])){ //NONE of these if's may have an else! (THINK)
  if(isset($_POST['searchCompany'])){
    $filterings['company'] = intval($_POST['searchCompany']);
  }
  if(isset($_POST['searchClient'])){
    $filterings['client'] = intval($_POST['searchClient']);
  }
  if(isset($_POST['searchProject'])){
    $filterings['project'][0] = intval($_POST['searchProject']);
  }
  if(isset($filterings['project'][1])){
    if(isset($_POST['searchProductive'])){
      $filterings['project'][1] = 'checked';
    } else {
      $filterings['project'][1] = '';
    }
  }
  if(isset($_POST['searchProductive'])){
    $filterings['project'][1] = 'checked';
  }
  if(isset($_POST['searchUser'])){
    $filterings['user'] = intval($_POST['searchUser']);
  }
  if(isset($_POST['searchCharged'])){
    $filterings['bookings'][0] = intval($_POST['searchCharged']);
  }
  if(isset($filterings['bookings'][1])){
    if(isset($_POST['searchBreaks'])){
      $filterings['bookings'][1] = 'checked';
    } else {
      $filterings['bookings'][1] = '';
    }
  }
  if(isset($filterings['bookings'][2])){
    if(isset($_POST['searchDrives'])){
      $filterings['bookings'][2] = 'checked';
    } else {
      $filterings['bookings'][2] = '';
    }
  }
  if(isset($_POST['searchActivity'])){
    $filterings['logs'][0] = intval($_POST['searchActivity']);
  }
  if(isset($filterings['logs'][1])){
    if(isset($_POST['searchAllTimestamps'])){
      $filterings['logs'][1] = 'checked';
    } else {
      $filterings['logs'][1] = '';
    }
  }
  if(!empty($_POST['searchYear'])){
    $filterings['date'] = intval($_POST['searchYear']);
  }
  if(isset($_POST['searchMonth'])){
    if(!empty($_POST['searchMonth'])){
      $filterings['date'] .= '-' . sprintf('%02d', intval($_POST['searchMonth']));
    } else {
      $filterings['date'] .= '-' . '__';
    }
  }
  if(isset($_POST['searchDay'])){
    if(!empty($_POST['searchDay'])){
      $filterings['date'] .= '-' . sprintf('%02d', intval($_POST['searchDay']));
    } else {
      $filterings['date'] .= '-' . '__';
    }
  }
  if(isset($_POST['searchTransitions'])){
    $filterings['procedures'][0] = array_map("test_input", $_POST['searchTransitions']);
  }
  if(isset($_POST['searchProcessStatus'])){
    $filterings['procedures'][1] = intval($_POST['searchProcessStatus']);
  }
  if(isset($filterings['procedures'][2])){
    if(isset($_POST['searchAllProcesses'])){
      $filterings['procedures'][2] = 'checked';
    } else {
      $filterings['procedures'][2] = '';
    }
  }

  if(isset($filterings['savePage'])){
    $_SESSION['filterings'] = $filterings;
  } else {
    $_SESSION['filterings'] = array();
  }
}

//read saved filters
if(!empty($_SESSION['filterings']['savePage']) && $_SESSION['filterings']['savePage'] == $this_page){
  $filterings = $_SESSION['filterings'];
}

$scale = 0;
if(isset($filterings['date'])){$scale++;}
if(isset($filterings['user'])){$scale++;}
if(isset($filterings['company'])){$scale++;}
if(isset($filterings['procedures'])){$scale++;}
$styles = array(20, 90);
if($scale > 1){ //2 columns
  $styles = array(40, 45);
}
if($scale > 2){ //3 columns
  $styles = array(60, 32);
}
?>
<style>
.filter_column{
  width:<?php echo $styles[1]; ?>%;
  display:inline;
  float:left;
  padding-left:20px;
}
</style>
<div id="filterings_dropdown" class="dropdown" style="display:inline">
  <button id="set_filter_search" type="button" class="btn btn-default" data-toggle="dropdown" title="<?php echo $lang['SEARCH_OPTIONS']; ?>"><i class="fa fa-search"></i></button>
  <div class="dropdown-menu" style="width:<?php echo $styles[0]; ?>vw">
    <form method="POST">
      <div class="container-fluid"><br>
        <div class="filter_column">
          <?php
          if(isset($filterings['company'])){
            $result_fc = mysqli_query($conn, "SELECT * FROM companyData WHERE id IN (".implode(', ', $available_companies).")");
            if($result_fc && $result_fc->num_rows > 1){
              echo '<label>'.$lang['COMPANY'].'</label>';
              if(isset($filterings['client'])){
                echo '<select class="js-example-basic-single" name="searchCompany" onchange="set_filter.showClients(this.value, \''.$filterings['client'].'\');" >';
              } else {
                echo '<select class="js-example-basic-single" name="searchCompany">';
              }
              echo '<option value="0">...</option>';
              while($result_fc && ($row_fc = $result_fc->fetch_assoc())){
                $checked = '';
                if($filterings['company'] == $row_fc['id']) {
                  $checked = 'selected';
                }
                echo "<option $checked value='".$row_fc['id']."' >".$row_fc['name']."</option>";
              }
              echo '</select><br><br>';
            }
          }
          if(isset($filterings['client'])){
            echo '<label>'.$lang['CLIENT'].'</label>';
            if(isset($filterings['project'])){
              echo '<select id="searchClientHint" class="js-example-basic-single" name="searchClient" onchange="set_filter.showProjects(this.value, \''.$filterings['project'][0].'\');" >';
            } else {
              echo '<select id="searchClientHint" class="js-example-basic-single" name="searchClient">';
            }
            $result_fc = mysqli_query($conn, "SELECT * FROM clientData WHERE companyID IN (".implode(', ', $available_companies).")");
            echo '<option value="0">...</option>';
            while($result_fc && ($row_fc = $result_fc->fetch_assoc())){
              $checked = '';
              if($filterings['client'] == $row_fc['id']) {
                $checked = 'selected';
              }
              echo "<option $checked value='".$row_fc['id']."' >".$row_fc['name']."</option>";
            }
            echo '</select><br><br>';
          }
          if(isset($filterings['project'])): ?>
            <label><?php echo $lang['PROJECT']; ?></label>
            <select id="searchProjectHint" class="js-example-basic-single" name="searchProject" >
            </select>
            <div class="checkbox"><label><input <?php echo $filterings['project'][1]; ?> type="checkbox" name="searchProductive" /><?php echo $lang['PRODUCTIVE']; ?></label></div>
          <?php endif; ?>
        </div>

        <div class="filter_column">
          <?php if(isset($filterings['user'])){
            echo '<label>'.$lang['USERS'].'</label>';
            echo '<select class="js-example-basic-single" name="searchUser" >';
            echo '<option value="0">...</option>';
            $result_fc = mysqli_query($conn, "SELECT * FROM $userTable WHERE id IN (".implode(', ', $available_users).")");
            while($result_fc && ($row_fc = $result_fc->fetch_assoc())){
              $checked = '';
              if($filterings['user'] == $row_fc['id']) { $checked = 'selected'; }
              echo "<option $checked value='".$row_fc['id']."' >".$row_fc['firstname'].' '.$row_fc['lastname']."</option>";
            }
            echo '</select><br><br>';
          }
          ?>
          <?php if(isset($filterings['bookings'])): ?>
            <label><?php echo $lang['BOOKINGS']; ?></label>
            <select name="searchCharged" class="js-example-basic-single">
              <option value='0' <?php if($filterings['bookings'][0] == '0'){echo 'selected';}?> >...</option>
              <option value='1' <?php if($filterings['bookings'][0] == '1'){echo 'selected';}?> ><?php echo $lang['NOT_CHARGED']; ?></option>
              <option value='2' <?php if($filterings['bookings'][0] == '2'){echo 'selected';}?> ><?php echo $lang['CHARGED']; ?></option>
            </select>
            <?php if($filterings['bookings'][0] != 1){ echo '<small>*'.$lang['INFO_CHARGED'].'</small>'; } ?>
            <div class="checkbox">
              <label><input type="checkbox" name="searchBreaks" <?php echo $filterings['bookings'][1]; ?> /><?php echo $lang['BREAKS']; ?></label>
              <label><input type="checkbox" name="searchDrives" <?php echo $filterings['bookings'][2]; ?> /><?php echo $lang['DRIVES']; ?></label>
            </div>
            <br><br>
          <?php endif; ?>
          <?php if(isset($filterings['logs'])): ?>
            <label><?php echo $lang['ACTIVITY']; ?></label>
              <select name="searchActivity" class="js-example-basic-single">
                <option value="0">...</option>
                <option value="1" <?php if($filterings['logs'][0] == '1'){echo 'selected';}?> ><?php echo $lang['VACATION']; ?></option>
                <option value="2" <?php if($filterings['logs'][0] == '2'){echo 'selected';}?>><?php echo $lang['SPECIAL_LEAVE']; ?></option>
                <option value="4" <?php if($filterings['logs'][0] == '4'){echo 'selected';}?>><?php echo $lang['VOCATIONAL_SCHOOL']; ?></option>
                <option value="6" <?php if($filterings['logs'][0] == '6'){echo 'selected';}?>><?php echo $lang['COMPENSATORY_TIME']; ?></option>
              </select>
              <div class="checkbox"><label><input type="checkbox" <?php echo $filterings['logs'][1]; ?> name="searchAllTimestamps"/><?php echo $lang['HIDE_ZEROE_VALUE']; ?></label></div>
          <?php endif; ?>
        </div>

        <?php if(isset($filterings['date'])): ?>
        <div class="filter_column">
          <label><?php echo $lang['YEAR']; ?></label>
          <select class="js-example-basic-single" name="searchYear">
            <?php
            for($i = 2015; $i < 2025; $i++){
              $selected = ($i == substr($filterings['date'],0,4))?'selected':'';
              echo "<option $selected value='$i'>$i</option>";
            }
            ?>
          </select>
          <br><br>
          <label><?php echo $lang['MONTH']; ?></label>
          <select class="js-example-basic-single"  onchange="set_filter.changeValue(this.value, 'searchDay', '');" name="searchMonth" id="searchMonth">
            <option value="">...</option>
            <?php
            for($i = 1; $i < 13; $i++) {
              $selected= '';
              if ($i == substr($filterings['date'],5,2)) {
                $selected = 'selected';
              }
              echo '<option '.$selected.' value="'.sprintf("%02d",$i).'">'.$lang['MONTH_TOSTRING'][$i].'</option>';
            }
            ?>
          </select>
          <br><br>
          <label><?php echo $lang['DAY']; ?></label>
          <select class="js-example-basic-single" name="searchDay" id="searchDay">
            <option value="">...</option>
            <?php
            for($i = 1; $i < 32; $i++){
              $selected= '';
              if($i == substr($filterings['date'],8,2)) {
                $selected = 'selected';
              }
              echo "<option $selected value=".sprintf("%02d",$i).">$i</option>";
            }
            ?>
          </select>
        </div>
        <?php endif; ?>

        <?php if(isset($filterings['procedures'])): ?>
          <div class="filter_column">
            <label><?php echo $lang['PROCESSES']; ?></label>
            <select class="js-example-basic-single" name="searchTransitions[]" multiple="multiple">
              <?php
              foreach($transitions as $i){
                $selected = '';
                if(in_array($i, $filterings['procedures'][0])){
                  $selected = 'selected';
                }
                echo "<option $selected value='$i'>".$lang['PROPOSAL_TOSTRING'][$i].'</option>';
              }
              ?>
            </select>
            <br><br>
            <label><?php echo $lang['PROCESS_STATUS']; ?></label>
            <select class="js-example-basic-single"  name="searchProcessStatus">
              <option value="-1"><?php echo $lang['DISPLAY_ALL']; ?></option>
              <?php
              for($i=0; $i < 3; $i++){
                $selected = '';
                if($i == $filterings['procedures'][1]){
                  $selected = 'selected';
                }
                echo '<option value="2" '.$selected.' >'.$lang['OFFERSTATUS_TOSTRING'][$i].'</option>';
              }
              ?>
            </select>
            <div class="checkbox"><label><input type="checkbox" <?php echo $filterings['procedures'][2]; ?> name="searchAllProcesses"/><?php echo $lang['HIDE_PROCESSED_DATA']; ?></label></div>
          </div>
        <?php endif; ?>
        <div class="container-fluid text-right">
          <div class="col-xs-12"><br><button type="submit" class="btn btn-warning" name="set_filter_apply"><?php echo $lang['APPLY']; ?></button><br><br></div>
        </div>
      </div>
    </form>
  </div>
</div>

<script>
$('#filterings_dropdown .dropdown-menu').on({
  "click":function(e){
    e.stopPropagation();
  }
});
//namespace declaration
(function( set_filter, $, undefined ) {
  //public method
  set_filter.showProjects = function(client, project){
    $.ajax({
      url:'ajaxQuery/AJAX_getProjects.php',
      data:{clientID:client, projectID:project},
      type: 'get',
      success : function(resp){
        $("#searchProjectHint").html(resp);
      },
      error : function(resp){}
    });
  };
  set_filter.showClients = function(company, client){
    $.ajax({
      url:'ajaxQuery/AJAX_getClient.php',
      data:{companyID:company, clientID:client},
      type: 'get',
      success : function(resp){
        $("#searchClientHint").html(resp);
      },
      error : function(resp){},
      complete: function(resp){
        set_filter.showProjects(0, 0);
      }
    });
  };
  set_filter.changeValue = function(cVal, id, val){
    if(cVal == ''){
      document.getElementById(id).selectedIndex = val;
      $('#' + id).val(val).change();
    }
  };
}( window.set_filter = window.set_filter || {}, jQuery ));
</script>

<?php
if(!empty($filterings['client'])){
  echo '<script>';
  echo 'set_filter.showProjects('.$filterings['client'].', 0);';
  if(!empty($filterings['project'][0])){
    echo 'set_filter.showProjects('.$filterings['client'].', '.$filterings['project'][0].');';
  }
  echo '</script>';
}
?>
