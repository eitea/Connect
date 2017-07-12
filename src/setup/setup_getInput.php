<?php require "header.php"; ?>
<?php
$accept = false;
if($_SERVER['REQUEST_METHOD'] == 'POST'){
  if(!empty($_POST['companyName']) && !empty($_POST['adminPass']) && !empty($_POST['firstname']) && !empty($_POST['type']) && !empty($_POST['localPart']) && !empty($_POST['domainPart'])){
    $out = '';
    if(match_passwordpolicy(test_input($_POST['pass']), $out)){
      $accept = true;
      $psw = password_hash($_POST['adminPass'], PASSWORD_BCRYPT);
    } else {
      echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$out.'</div>';
    }
  } else {
    echo 'Missing Fields. <br><br>';
  }
}
?>

<div class="">
  <form id="setup_form" method='post'>
    <h1>Login Data</h1><br><br>

    <div class="row">
      <div class="col-sm-8 col-lg-4">
        <div class="form-group">
          <div class="input-group">
            <span class="input-group-addon" style="min-width:150px">
              Firstname
            </span>
            <input type="text" class="form-control" name="firstname" placeholder="Firstname.." value="<?php if($_POST['firstname']) echo $_POST['firstname']; ?>" />
          </div>
        </div>
      </div>
      <div class="col-sm-8 col-lg-4">
        <div class="form-group">
          <div class="input-group">
            <span class="input-group-addon" style="min-width:150px">
              Lastname
            </span>
            <input type="text" class="form-control" name="lastname" placeholder="Lastname.." value="<?php if($_POST['lastname']) echo $_POST['lastname']; ?>" />
          </div>
        </div>
      </div>
    </div>

    <div class="row">
      <div class="col-sm-8">
        <div class="form-group">
          <div class="input-group">
            <span class="input-group-addon text-warning" style=min-width:150px>
              Login Password
            </span>
            <input type='password' class="form-control" name='adminPass' value="<?php if($accept) echo $psw; ?>">
          </div>
        </div>
      </div>
    </div>
    <div class="row">
      <div class="col-sm-6">
        <div class="form-group">
          <div class="input-group">
            <span class="input-group-addon text-warning" style=min-width:150px>
              Company Name
            </span>
            <input type='text' class="form-control" name='companyName' placeholder='Company Name' value="<?php if($_POST['companyName']) echo $_POST['companyName']; ?>">
          </div>
        </div>
      </div>
      <div class="col-sm-2">
        <div class="form-group">
          <select name="type" class="js-example-basic-single btn-block">
            <option selected>...</option>
            <option <?php if($accept && $_POST['type'] == "GmbH") echo "selected"; ?> value="GmbH">GmbH</option>
            <option <?php if($accept && $_POST['type'] == "AG") echo "selected"; ?> value="AG">AG</option>
            <option <?php if($accept && $_POST['type'] == "OG") echo "selected"; ?> value="OG">OG</option>
            <option <?php if($accept && $_POST['type'] == "KG") echo "selected"; ?> value="KG">KG</option>
            <option <?php if($accept && $_POST['type'] == "EU") echo "selected"; ?> value="EU">EU</option>
            <option <?php if($accept && $_POST['type'] == "-") echo "selected"; ?> value="-">Sonstiges</option>
          </select>
        </div>
      </div>
    </div>
    <br><br>
    <div class="row">
      <div class="col-sm-8">
        <label>Your Login E-Mail</label>
        <div class="form-group">
          <div class="input-group">
            <input type='text' class="form-control" name='localPart' placeholder='name' value="<?php if($accept) echo $_POST['localPart']; ?>">
            <span class="input-group-addon text-warning">
              @
            </span>
            <input type='text' class="form-control" name='domainPart' placeholder="domain.com" value="<?php if($_POST['domainPart']) echo $_POST['domainPart']; ?>">
          </div>
        </div>
        <small> * The Domain will be used for every login adress that will be created. Cannot be changed afterwards.<br><b> May not contain any special characters! </b></small>
      </div>
    </div>
    <br><hr><br>

    <?php if(!getenv('IS_Container') && !isset($_SERVER['IS_Container'])): ?>
      <h1>MySQL Database Connection</h1><br><br>

      <div class="row">
        <div class="col-sm-8">
          <div class="form-group">
            <div class="input-group">
              <span class="input-group-addon" style=min-width:150px>
                Server Address
              </span>
              <input type="text" class="form-control" name='serverName' value = "localhost">
            </div>
          </div>
        </div>
      </div>
      <div class="row">
        <div class="col-sm-8">
          <div class="form-group">
            <div class="input-group">
              <span class="input-group-addon" style=min-width:150px>
                Username
              </span>
              <input type="text" class="form-control" name='mysqlUsername' value = 'root'>
            </div>
          </div>
        </div>
      </div>
      <div class="row">
        <div class="col-sm-8">
          <div class="form-group">
            <div class="input-group">
              <span class="input-group-addon" style=min-width:150px>
                Password
              </span>
              <input type="text" class="form-control" name='pass' value = ''>
            </div>
          </div>
        </div>
      </div>
      <div class="row">
        <div class="col-sm-8">
          <div class="form-group">
            <div class="input-group">
              <span class="input-group-addon" style=min-width:150px>
                DB Name
              </span>
              <input type="text" class="form-control" name='dbName' value = 'Zeit1'>
            </div>
          </div>
        </div>
      </div>
      <br><hr><br>

    <?php else: ?>
      <div style="display:none" />
        <input type="text" readonly  name='serverName' value = "c-db">
        <input type="text" readonly name='mysqlUsername' value = 'connect'>
        <input type="text" readonly name='pass' value = 'Uforonudi499'>
        <input type="text" readonly name='dbName' value = 'connect'>
      </div>
    <?php endif; ?>

    <div class="container-fluid text-right">
        <button id="continueButton" type='submit' name'submitInput' class="btn btn-warning">Continue</button>
    </div>
  </form>

  <?php if($accept): ?>
    <script>
    var myForm = document.getElementById("setup_form");
    myForm.action = "setup.php";
    myForm.submit();
    </script>
  <?php endif; ?>
</div>
<?php include 'footer.php'; ?>
