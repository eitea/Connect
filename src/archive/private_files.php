<?php

require dirname(dirname(__DIR__)) . "/src/misc/useS3Config.php";
require dirname(__DIR__) . "/connection.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    if ($_POST['function'] === "addFile") {
        try {
            $s3 = new Aws\S3\S3Client(getS3Config());
            $buckets = $s3->listBuckets();
            $thereisabucket = false;
            foreach ($buckets['Buckets'] as $bucket) {
                if ($bucket['Name'] === $s3privateFiles)
                    $thereisabucket = true;
            }
            if (!$thereisabucket) {
                $s3->createBucket(array('Bucket' => $s3privateFiles));
            }
            $folder = intval($_POST['folderid']);
            $amount = $_POST['amount'];
            for ($i = 0; $i < $amount; $i++) {
                $filename = pathinfo($_FILES['file' . $i]['name'], PATHINFO_FILENAME);
                $filetype = pathinfo($_FILES['file' . $i]['name'], PATHINFO_EXTENSION);
                $filesize = $_FILES['file' . $i]['size'];
                $hashkey = hash('md5', random_bytes(10));
                $conn->query("INSERT INTO archive_savedfiles VALUES (null,'$filename', '$filetype', $folder, " . $_POST['userID'] . ", '$hashkey', $filesize, null,'TRUE')");
                $s3->putObject(array(
                    'Bucket' => $s3privateFiles,
                    'Key' => $hashkey,
                    'SourceFile' => $_FILES['file' . $i]['tmp_name']
                ));
            }
        } catch (Exception $e) {
            echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>' . $e . '</div>';
        }
        if ($conn->error) {
            echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>' . $conn->error . '</div>';
        } else {
            echo $folder;
        }
    } elseif ($_POST['function'] === "deleteFile") {
        $s3 = new Aws\S3\S3Client(getS3Config());
        try {
            $fileID = $_POST['id'];
            $hash = $conn->query("SELECT hashkey, isS3 FROM archive_savedfiles WHERE id = $fileID");
            if (!$hash) {
                $info = array();
                $info[0] = array('name' => $_POST['fileID']);
                echo json_encode($info);
                exit();
            }
            $row = $hash->fetch_assoc();
            $isS3 = $row['isS3'];
            $hash = $row['hashkey'];
            if ($isS3 == "TRUE") {
                $s3->deleteObject(array(
                    'Bucket' => $s3privateFiles,
                    'Key' => $hash
                ));
            } else {
                $conn->query("DELETE FROM archive_editfiles WHERE hashid = $hash");
            }
            $conn->query("DELETE FROM archive_savedfiles WHERE id = $fileID");
        } catch (Exception $e) {
            var_dump($row);
            // echo $hash;
            // echo $e;
        }
    } elseif ($_POST['function'] === "getFileData") {
        try {
            $id = $_POST['id'];
            $result = $conn->query("SELECT f.name AS name, e.body AS body FROM archive_savedfiles f JOIN archive_editfiles e ON f.hashkey = e.hashid WHERE id = $id ORDER BY version DESC");
            $row = $result->fetch_assoc();
            $data = array();
            $data[0] = $row['name'];
            $data[1] = $row['body'];
            echo json_encode($data);
        } catch (Exception $e) {
            echo $e;
        }
    } elseif ($_POST['function'] === "deleteFolder") {
        try {
            $id = $_POST['id'];
            $files = array();
            $result = $conn->query("SELECT id FROM archive_savedfiles WHERE folderid = $id");
            $conn->query("DELETE FROM archive_folders WHERE folderid=$id AND userid=" . $_POST['userID']);
            $i = 0;
            while ($row = $result->fetch_assoc()) {
                $files[$i] = $row['id'];
                $i++;
            }
            echo json_encode($files);
        } catch (Exception $e) {
            echo $e;
        }
    } elseif ($_POST['function'] === "getTitle") {
        $id = $_POST['id'];
        $ids = array();
        $userID = $_POST['userID'];
        $title = $_POST['title'];
        $i = 1;
        while (true) {
            $result = $conn->query("SELECT parent_folder FROM archive_folders WHERE folderid=$id AND userid=$userID");
            $parent = $result->fetch_assoc()['parent_folder'];
            $ids[$i] = $id;
            if ($parent == -1)
                break;
            $id = $parent;
            $i++;
        }
        if ($i > 1) {
            for ($y = $i - 1; $y > 0; $y--) {
                $result = $conn->query("SELECT name FROM archive_folders WHERE folderid=" . $ids[$y] . " AND userid=$userID");
                $name = $result->fetch_assoc()['name'];
                $title = $title . " > " . $name;
            }
        }
        echo $title;
    } elseif ($_POST['function'] === "getFolders") {
        $userID = $_POST['userID'];
        $folders = array();
        $result = $conn->query("SELECT * FROM archive_folders WHERE userid = $userID ORDER BY parent_folder ASC");
        //$levels = $conn->query("SELECT folderid FROM archive_folders WHERE userid = $userID GROUP BY parent_folder");
        //$levels = $levels->num_rows;
        //$noChilds = $conn->query("SELECT name FROM archive_folders WHERE userid = $userID AND folderid NOT IN (SELECT parent_folder FROM archive_folders WHERE userid = $userID GROUP BY parent_folder)");
        $firstRow = $result->fetch_assoc();
        array_push($folders, new folderNode("root", 0, -1));
        while ($row = $result->fetch_assoc()) {
            array_push($folders, new folderNode($row['name'], intval($row['folderid']), intval($row['parent_folder'])));
        }
        foreach ($folders as $node) {
            $childs = array();
            foreach ($folders as $child) {
                if ($node->getId() == $child->getparent()) {
                    array_push($childs, $child);
                }
            }
            if (!empty($childs))
                $node->setChilds($childs);
        }
        echo json_encode($folders[0]->getNode());
    }elseif ($_POST['function'] === "moveFile") {
        $fileid = $_POST['id'];
        $folderid = $_POST['folder'];
        $conn->query("UPDATE archive_savedfiles SET folderid = $folderid WHERE id = $fileid");
        if ($conn->error) {
            echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>' . $conn->error . '</div>';
        } else {
            echo "EZ";
        }
    }
} elseif (!empty($_GET['n'])) {
    $s3 = new Aws\S3\S3Client(getS3Config());
    $hashkey = $_GET['n'];
    $result = $conn->query("SELECT * FROM archive_savedfiles WHERE hashkey='$hashkey'");
    if ($result) {
        $row = $result->fetch_assoc();
        $object = $s3->getObject(array(
            'Bucket' => $s3privateFiles,
            'Key' => $hashkey,
        ));
    }

    header("Cache-Control: public");
    header("Content-Description: File Transfer");
    header("Content-Disposition: attachment; filename=" . $row['name'] . "." . $row['type']);
    header("Content-Type: {$object['ContentType']}");
    echo $object['Body'];
}

class folderNode {

    private $name = "";
    private $id = 0;
    private $childs;
    private $parent = -1;

    public function __construct($name, $id, $parent, $childs = null) {
        $this->name = $name;
        $this->id = $id;
        $this->parent = $parent;
        $this->childs = $childs;
    }

    public function getNode() {
        if (isset($this->childs)) {
            $thisNode = [
                "text" => $this->name,
                "tags" => ["$this->id"],
                "nodes" => getChilds($this->childs)
            ];
        } else {
            $thisNode = [
                "text" => $this->name,
                "tags" => ["$this->id"],
            ];
        }
        return $thisNode;
    }

    public function setChilds($childs) {
        $this->childs = $childs;
    }

    public function getParent() {
        return $this->parent;
    }

    public function getId() {
        return $this->id;
    }

}

function getChilds($childArray) {
    $resultArray = array();
    foreach ($childArray as $child) {
        array_push($resultArray, $child->getNode());
    }
    return $resultArray;
}

?>