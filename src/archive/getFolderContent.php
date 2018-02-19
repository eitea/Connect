<?php

require dirname(__DIR__) . "/connection.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['folderid']) && !empty($_POST['userid'])) {
        $folderid = intval($_POST['folderid']);
        $userid = intval($_POST['userid']);
        $result = $conn->query("SELECT * FROM archive_savedfiles WHERE userid = $userid AND folderid = $folderid");

        $data = array();
        $files = array();
        $folders = array();
        if ($result && $result->num_rows > 0) {
            $i = 0;
            while ($row = $result->fetch_assoc()) {
                $files[$i]['id'] = $row['id'];
                $files[$i]['name'] = $row['name'];
                $files[$i]['type'] = $row['type'];
                $files[$i]['folderid'] = $row['folderid'];
                $files[$i]['userid'] = $row['userid'];
                $files[$i]['hashkey'] = $row['hashkey'];
                $files[$i]['filesize'] = $row['filesize'];
                $files[$i]['uploaddate'] = $row['uploaddate'];
                $files[$i]['isS3'] = $row['isS3'];
                $i++;
            }
        }
        $data[0] = $files;
        $result = $conn->query("SELECT * FROM archive_folders WHERE userid = $userid AND parent_folder = $folderid");
        if ($result && $result->num_rows > 0) {
            $i = 0;
            while ($row = $result->fetch_assoc()) {
                $folders[$i]['folderid'] = $row['folderid'];
                $folders[$i]['userid'] = $row['userid'];
                $folders[$i]['name'] = $row['name'];
                $folders[$i]['parent_folder'] = $row['parent_folder'];
                $i++;
            }
        }
        $result = $conn->query("SELECT * FROM archive_folders WHERE userid = $userid AND folderid = $folderid");
        $data[1] = $folders;
        $data[2] = $result->fetch_assoc()['parent_folder'];
        echo json_encode($data);
    }
}
return;
?>