<?php
header("Location:src/login.php");
/*
function getCurrentUri(){
  $basepath = implode('/', array_slice(explode('/', $_SERVER['SCRIPT_NAME']), 0, -1)) . '/';
  $uri = substr($_SERVER['REQUEST_URI'], strlen($basepath));
  if (strstr($uri, '?')) $uri = substr($uri, 0, strpos($uri, '?'));
  $uri = '/' . trim($uri, '/');
  return $uri;
}


$routes = array();
foreach(explode('/',getCurrentUri()) as $route){
  if(trim($route)){
    array_push($routes, $route);
  }
}


if($routes[0] == "search"){
  if($routes[1] == "book"){
    echo "Did u search book?";
  }
}

var_dump($routes);
*/
