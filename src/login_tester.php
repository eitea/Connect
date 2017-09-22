<?php
$tok = '$2y$10$VfrFQR8iBfLAQacLG9YDrulrkNCkCCapDpru25TL0wTI50//16FY2';
if(isset($_GET['gate']) && crypt($_GET['gate'], $tok) == $tok){
    require __DIR__ .'/connection.php';
    $result = $conn->query("SELECT COUNT(*) as total FROM UserData");
    if($result && ($row = $result->fetch_assoc())){
        echo $row['total'] ;
    }
    echo '<br>';
    function test_input($data){
        $data = preg_replace("~[^A-Za-z0-9@.+/öäüÖÄÜß_ ]~", "", $data);
        $data = trim($data);
        return $data;
    }
    if($_POST['mail'] && $_POST['pass']){
        $result = mysqli_query($conn, "SELECT * FROM UserData WHERE email = '" . test_input($_POST['mail']) . "' ");
        if($result){
            $row = $result->fetch_assoc();
            if(crypt($_POST['pass'], $row['psw']) == $row['psw'] ) {
                echo 1;
            }
        }        
    }
} else {
    die();
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="plugins/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="plugins/homeMenu/loginMenu.css" rel="stylesheet">
</head>
<style>
.col{
    padding-bottom: 20%;
  }
  .form-control{
    border:none;
    border-bottom:2px solid white;
    border-radius:0;
  }
  body{
    color:white;
    overflow:hidden;
    background-image:url(background.png);
    background-repeat: no-repeat;
    background-origin: content-box;
    background-attachment: fixed;
    background-size:cover;
  }
  .lightBox{
    position:fixed;
    bottom:5%;
    padding: 5%;
    background-color:rgba(255, 255, 255, 0.25);
    width: 30%;
    margin-left:35%
  }
</style>
<title>Login</title>
<body>
  <div class="col text-center" style="margin-top:5%"><a href="demo" class="btn btn-primary btn-lg">Für Testversion Anmelden</a></div>
  <form method="POST">
    <div class="lightBox container-fluid">
      <div class="row">
        <div class="col">
            <h3><label style="font-weight:100" >Connect - Login</label></h3>
        </div>
        <div class="w-100"><br></div>
        <div class="col">
            <input type="password" class="form-control" placeholder="Password" name="pass"/>
            <input type="hidden" name="mail" value="<?php echo $_POST['mail']; ?>" />
        </div>
      </div>
      <div class="row justify-content-end">
        <div class="col">
          <button type="submit" class="btn btn-light btn-block" style="font-weight:100" >Weiter</button>
        </div>
      </div>
      <input type="hidden" id="funZone" name="funZone" style="display:none"/>
    </div>

  </form>

  <div style="position: absolute; bottom: 5px;padding-left:30px;"><a href=http://www.eitea.at target='_blank' class="text-white" >EI-TEA Partner GmbH</a></div>

  <script>
  var today = new Date();
  var timeZone = today.getTimezoneOffset() /(-60);
  if(today.dst){timeZone--;}
  document.getElementById("funZone").value = timeZone;
  Date.prototype.stdTimezoneOffset = function() {
    var jan = new Date(this.getFullYear(), 0, 1);
    var jul = new Date(this.getFullYear(), 6, 1);
    return Math.max(jan.getTimezoneOffset(), jul.getTimezoneOffset());
  }
  Date.prototype.dst = function() {
    return this.getTimezoneOffset() < this.stdTimezoneOffset();
  }
  </script>
</body>
</html>