<?php
require dirname(__DIR__) . "/connection.php";
header('Content-Type: application/json');
if (!isset($_REQUEST["id"])) {
    die(json_encode(array("error" => "no id set")));
}

if (!isset($_REQUEST["client"])) {
    die(json_encode(array("error" => "no client set")));
}

$projectID = $conn->real_escape_string($_REQUEST["id"]);
$clientID = $conn->real_escape_string($_REQUEST["client"]);

$result = $conn->query(
    "SELECT dynamicprojects.*, dynamicprojectsclients.* FROM dynamicprojects, dynamicprojectsclients
     WHERE dynamicprojects.projectid = dynamicprojectsclients.projectid
     AND dynamicprojects.projectid = '$projectID'
     AND dynamicprojectsclients.clientid = $clientID
");

if ($conn->error) {
    die(json_encode(array("error" => $conn->error)));
}

if ($result->num_rows == 0) {
    die(json_encode(array("error" => "0 rows")));
}

echo json_encode(array("completed"=>$result->fetch_assoc()["projectcompleted"]));


// $data = ;
// echo json_encode($data);

// var data = new FormData()
// if(!file.type.match('image.*')){
//     alert("Not an image")
// }else if (file.size > 1048576){
//     alert("File too large")
// }else{
//     data.append('picture', file)
//     data.append('partner',<?php echo $x; ?)
//     $.ajax({
//         url: 'ajaxQuery/AJAX_socialSendMessage.php',
//         dataType: 'json',
//         data: data,
//         cache: false,
//         type: 'POST',
//         processData: false,
//         contentType: false,
//         success: function (response) {
//             getMessages(<?php echo $x; ?, "#messages<?php echo $x; ?", true, limit<?php echo $x; ?)
//         },
//         error: function(response){
//             getMessages(<?php echo $x; ?, "#messages<?php echo $x; ?", true, limit<?php echo $x; ?)
//         }
//     })
// }

// if ($result && $result->num_rows > 1) {
//     echo "<option name='clnt' value=0 >...</option>";
// }
// if ($result && $result->num_rows > 0) {
//     while ($row = $result->fetch_assoc()) {
//         $clientID = $row['id'];
//         $clientName = $row['name'];
//         $selected = "";
//         if ($p != 0 && $p == $clientID) {
//             $selected = "selected";
//         }
//         echo "<option $selected name='clnt' value=$clientID>$clientName</option>";
//     }
// }
