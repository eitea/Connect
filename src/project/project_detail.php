<?php
require dirname(__DIR__).DIRECTORY_SEPARATOR.'header.php';
enableToProject($userID);

if(!isset($_GET['p'])){ include dirname(__DIR__).DIRECTORY_SEPARATOR.'footer.php'; die('Invalid access'); }

$projectID = intval($_GET['p']);
require __DIR__.DIRECTORY_SEPARATOR.'project_detail_include.php';


require dirname(__DIR__).DIRECTORY_SEPARATOR.'footer.php';
