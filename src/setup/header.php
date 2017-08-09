<?php
if(file_exists(dirname(__DIR__) . '/connection_config.php')){
  header("Location: ../login/auth");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta http-equiv="Cache-Control" content="max-age=600, must-revalidate">

  <script src="plugins/jQuery/jquery-3.2.1.min.js"></script>
  <link rel="stylesheet" href="plugins/font-awesome/css/font-awesome.min.css"/>

  <link href="plugins/bootstrap/css/bootstrap.min.css" rel="stylesheet"/>
  <script src="plugins/bootstrap/js/bootstrap.min.js"></script>

  <link rel="stylesheet" type="text/css" href="plugins/select2/css/select2.min.css">
  <script src='plugins/select2/js/select2.js'></script>

  <link href="plugins/homeMenu/homeMenu.css" rel="stylesheet" />
  <title>Connect</title>
  <script>
  document.onreadystatechange = function() {
    var state = document.readyState
    if(state == 'complete') {
      document.getElementById("loader").style.display = "none";
      document.getElementById("bodyContent").style.display = "block";
    }
  }
  $(document).ready(function() {
    if($(".js-example-basic-single")[0]){
      $(".js-example-basic-single").select2();
    }
  });
  </script>
</head>
<body id="body_container" class="is-table-row">
  <div id="loader"></div>
  <!-- navbar -->
  <nav id="fixed-navbar-header" class="navbar navbar-default navbar-fixed-top">
    <div class="container-fluid">
      <div class="navbar-header hidden-xs">
        <a class="navbar-brand" >Connect</a>
      </div>
      <div class="navbar-right">
        <a class="btn navbar-btn navbar-link" data-toggle="collapse" href="#infoDiv_collapse"><strong>info</strong></a>
      </div>
    </div>
  </nav>
  <div class="collapse" id="infoDiv_collapse">
    <div class="well">
      <a href='http://www.eitea.at'> EI-TEA Partner GmbH </a> - <?php include dirname(__DIR__).'/version_number.php'; echo $VERSION_TEXT; ?><br>
      The Licensor does not warrant that commencing upon the date of delivery or installation, that when operated in accordance with the documentation or other instructions provided by the Licensor,
      the Software will perform substantially in accordance with the functional specifications set forth in the documentation. The software is provided "as is", without warranty of any kind, express or implied.
    </div>
  </div>
  <!-- /navbar -->
  <?php
  function test_input($data) {
    $data = preg_replace("~[^A-Za-z0-9\-?!=:.,/@€§$%()+*öäüÖÄÜß_ ]~", "", $data);
    $data = trim($data);
    return $data;
  }
  function clean($string) {
    return preg_replace('/[^\.A-Za-z0-9\-]/', '', $string);
  }
  function match_passwordpolicy($p, &$out = ''){
    if(strlen($p) < 6){
      $out = "Password must be at least 6 Characters long.";
      return false;
    }
    if(!preg_match('/[A-Z]/', $p) || !preg_match('/[0-9]/', $p)){
      $out = "Password must contain at least one captial letter and one number";
      return false;
    }
    return true;
  }
  function icsToArray($paramUrl) {
    $icsFile = file_get_contents($paramUrl);
    $icsData = explode("BEGIN:", $icsFile);
    foreach ($icsData as $key => $value) {
      $icsDatesMeta[$key] = explode("\n", $value);
    }
    foreach ($icsDatesMeta as $key => $value) {
      foreach ($value as $subKey => $subValue) {
        if ($subValue != "") {
          if ($key != 0 && $subKey == 0) {
            $icsDates[$key]["BEGIN"] = $subValue;
          } else {
            $subValueArr = explode(":", $subValue, 2);
            $icsDates[$key][$subValueArr[0]] = $subValueArr[1];
          }
        }
      }
    }
    return $icsDates;
  }
   ?>
  <div id="bodyContent" style="display:none;" >
    <div class="affix-content">
      <div class="container-fluid">
