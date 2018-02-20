<!DOCTYPE html>
<html>
    <head>
        <link href="../plugins/homeMenu/template.css" rel="stylesheet">
    </head>
    <body>
        <?php
        require dirname(dirname(__DIR__)) . "/connection.php";

        $tempelID = intval($_GET['prevTemplate']);
        $result = $conn->query("SELECT * FROM $pdfTemplateTable WHERE id = $tempelID");
        $row = $result->fetch_assoc();
        echo $row['htmlCode'];
        ?>
    </body>
</html>
