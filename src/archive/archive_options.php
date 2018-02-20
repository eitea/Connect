<?php include dirname(__DIR__) . '/header.php';?>
<?php require dirname(__DIR__) . "/misc/helpcenter.php"; 
    $configs = $conn->query("SELECT * FROM archiveconfig");
?>
<div class="page-header"><h3 id="title" ><?php echo $lang['OPTIONS'] ?></h3>
</div>
    <table class="table" id="configTable">
        <thead>
            <tr>
                <td><label>Addresse</label></td>
                <td><label>Key</label></td>
                <td><label>Active</label></td>
                <td></td>
            </tr>
        </thead>
        <tbody id="tableContent">
            <?php 
                while($row = $configs->fetch_assoc()){
                    echo '<tr>';
                    echo '<td>'.$row['endpoint'].'</td>';
                    echo '<td>'.$row['awskey'].'</td>';
                    echo '<td>'.$row['isActive'].'</td>';
                    echo '<td></td>';
                    echo '</tr>';
                }
            ?>
        </tbody>
    </table>
<?php include dirname(__DIR__) . '/footer.php'; ?>
