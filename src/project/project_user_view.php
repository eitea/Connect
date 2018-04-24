<?php
$activeTab = '';
?>
<div class="page-header h4">Meine Projekte</div>
<div class="container-fluid panel-group" id="accordion">
    <?php
    if(!empty($_SESSION['external_id'])){
        $tableName = 'relationship_project_extern';
        $accessTableName = "LEFT JOIN security_external_access ON module = 'PRIVATE_PROJECT' AND optionalID = '$projectID' AND userID = $userID AND outDated = 'FALSE'";
    } else {
        $tableName = 'relationship_project_user';
        $accessTableName = "LEFT JOIN security_access ON module = 'PRIVATE_PROJECT' AND optionalID = '$projectID' AND userID = $userID AND outDated = 'FALSE'";
    }
    $result = $conn->query("SELECT p.name, p.id, c.companyID, s.publicKey, s.symmetricKey, c.name FROM $tableName
    INNER JOIN projectData p ON p.id = projectID INNER JOIN clientData c ON p.clientID = c.id LEFT JOIN security_projects s ON s.projectID = p.id AND s.outDated = 'FALSE'
    $accessTableName WHERE userID = $userID");
    echo $conn->error;
    while($result && ($projectRow = $result->fetch_assoc())):
        $projectID = $projectRow['id'];

        $keypair = base64_decode($privateKey).base64_decode($projectRow['publicKey']);
        $cipher = base64_decode($row['privateKey']);
        $nonce = mb_substr($cipher, 0, 24, '8bit');
        $encrypted = mb_substr($cipher, 24, null, '8bit');
        try {
            $project_private = sodium_crypto_box_open($encrypted, $nonce, $keypair);
            $cipher_symmetric = base64_decode($projectRow['symmetricKey']);
            $nonce = mb_substr($cipher_symmetric, 0, 24, '8bit');
            $project_symmetric = sodium_crypto_box_open(mb_substr($cipher_symmetric, 24, null, '8bit'), $nonce, $project_private.base64_decode($projectRow['publicKey']));
        } catch(Exception $e){
            echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$e->getMessage().'</div>';
        }
        $result = $conn->query("SELECT endpoint, awskey, secret FROM archiveconfig WHERE isActive = 'TRUE' LIMIT 1");
        if($result && ($row = $result->fetch_assoc())){
            $link_id = (getenv('IS_CONTAINER') || isset($_SERVER['IS_CONTAINER'])) ? substr($servername, 0, 8) : $identifier;
            try{
                $s3 = new Aws\S3\S3Client(array(
                    'version' => 'latest',
                    'region' => '',
                    'endpoint' => $row['endpoint'],
                    'use_path_style_endpoint' => true,
                    'credentials' => array('key' => $row['awskey'], 'secret' => $row['secret'])
                ));
            }catch(Exception $e){
                echo $e->getMessage();
            }
        } else {
			include dirname(__DIR__).DIRECTORY_SEPARATOR.'footer.php';
			die("No S3 Access found");
		}
    ?>
        <div class="panel panel-default">
            <div class="panel-heading" role="tab" id="heading<?php echo $projectID; ?>">
                <h4 class="panel-title">
                    <a role="button" data-toggle="collapse" data-parent="#accordion" href="#collapse<?php echo $projectID; ?>"><?php echo $row['name']; ?></a>
                </h4>
            </div>
            <div id="collapse<?php echo $projectID; ?>" class="panel-collapse collapse <?php if($projectID == $activeTab) echo 'in'; ?>">
                <div class="panel-body">
                    <?php if(!empty($s3)) : ?>
                        <h4>Dateifreigabe
                            <div class="page-header-button-group">
                                <div class="btn-group"><a class="btn btn-default dropdown-toggle" data-toggle="dropdown" title="Hochladen..."><i class="fa fa-upload"></i></a>
                                    <ul class="dropdown-menu">
                                        <li><a data-toggle="modal" data-target="#modal-new-folder">Neuer Ordner</a></li>
                                        <li><a data-toggle="modal" data-target="#modal-new-file">File</a></li>
                                        <!--li><a data-toggle="modal" data-target="#modal-new-text">Text</a></li-->
                                    </ul>
                                </div>
                            </div>
                        </h4><br>

                        <?php
                        $result = $conn->query("SELECT name FROM company_folders WHERE companyID = ".$projectRow['companyID']." AND name NOT IN
                            ( SELECT name FROM project_archive WHERE projectID = $projectID AND parent_directory = 'ROOT') ");
                        echo $conn->error;
                        while($result && ($row = $result->fetch_assoc())){
                            $conn->query("INSERT INTO project_archive(projectID, name, parent_directory, type) VALUES($projectID, '".$row['name']."', 'ROOT', 'folder')"); echo $conn->error;
                        }
                        function drawFolder($parent_structure, $visibility = true){
                            global $conn;
                            global $projectID;
                            global $project_symmetric;
                            $html = '<div id="folder-'.$parent_structure.'" >';
                            if(!$visibility) $html = substr_replace($html, 'style="display:none"', -1, 0);

                            if($parent_structure != 'ROOT') $html .= '<div class="row"><div class="col-xs-1"><i class="fa fa-arrow-left"></i></div>
                            <div class="col-xs-3"><button class="btn btn-link tree-node-back" data-parent="'.$parent_structure.'">Zurück</button></div></div>';
                            $subfolder = '';
                            $result = $conn->query("SELECT id, name, uploadDate, type, uniqID FROM project_archive WHERE projectID = $projectID AND parent_directory = '$parent_structure' ORDER BY type <> 'folder', type ASC ");
                            echo $conn->error;
                            while($result && ($row = $result->fetch_assoc())){
                                $html .= '<div class="row">';
                                if($row['type'] == 'folder'){
                                    $html .= '<div class="col-xs-1"><i class="fa fa-folder-open-o"></i></div>
                                    <div class="col-xs-4"><a class="folder-structure" data-child="'.$row['id'].'" data-parent="'.$parent_structure.'" >'.$row['name'].'</a></div><div class="col-xs-4">'.$row['uploadDate'].'</div>';
                                    $subfolder .= drawFolder($row['id'], false);
                                } else {
                                    $html .= '<div class="col-xs-1"><i class="fa fa-file-o"></i></div>
                                    <div class="col-xs-4">'.$row['name'].'</div><div class="col-xs-4">'.$row['uploadDate'].'</div>
                                    <div class="col-xs-3">
                                    <form method="POST" style="display:inline"><button type="submit" class="btn btn-default" name="delete-file" value="'.$row['uniqID'].'">
                                    <i class="fa fa-trash-o"></i></button></form>
                                    <form method="POST" style="display:inline" action="detailDownload" target="_blank">
                                    <input type="hidden" name="symmetricKey" value="'.base64_encode($project_symmetric).'" />
                                    <button type="submit" class="btn btn-default" name="download-file" value="'.$row['uniqID'].'"><i class="fa fa-download"></i></button>
                                    </form></div>';
                                }
                                $html .= '</div>';
                            }
                            $html .= '</div>';
                            $html .= $subfolder;
                            return $html;
                        }
                        echo drawFolder('ROOT');
                        ?>

                        <div id="modal-new-folder" class="modal fade">
                            <div class="modal-dialog modal-content modal-sm">
                                <form method="POST">
                                    <div class="modal-header h4">Neuer Ordner</div>
                                    <div class="modal-body">
                                        <label>Name</label>
                                        <input type="text" name="new-folder-name" class="form-control" />
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                                        <button type="submit" class="btn btn-warning modal-new" name="add-new-folder" value="ROOT"><?php echo $lang['ADD']; ?></button>
                                    </div>
                                </form>
                            </div>
                        </div>
                        <div id="modal-new-file" class="modal fade">
                            <div class="modal-dialog modal-content modal-sm">
                                <form method="POST" enctype="multipart/form-data">
                                    <div class="modal-header h4">File Hochladen</div>
                                    <div class="modal-body">
                                        <label class="btn btn-default">
                                            Datei Auswählen
                                            <input type="file" name="new-file-upload"  accept="application/msword, application/vnd.ms-excel, application/vnd.ms-powerpoint, text/plain, application/pdf,.doc, .docx" style="display:none" >
                                        </label>
                                        <small>Max. 15MB<br>Text, PDF, .Zip und Office</small>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                                        <button type="submit" class="btn btn-warning modal-new" name="add-new-file" value="ROOT"><?php echo $lang['ADD']; ?></button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <script>
                        var grandParent = ['ROOT'];
                        $('.tree-node-back').click(function(){
                            var grandPa = grandParent.pop();
                            $('#folder-'+ $(this).data('parent')).hide();
                            $('#folder-'+ grandPa).fadeIn();
                            changeUploadPlace(grandPa);
                        });
                        $('.folder-structure').click(function(event){
                            $('#folder-'+ $(this).data('parent')).hide();
                            $('#folder-'+ $(this).data('child')).fadeIn();
                            grandParent.push($(this).data('parent'));
                            changeUploadPlace($(this).data('child'));
                        });
                        function changeUploadPlace(place){
                            $('.modal-new').val(place);
                        }
                        </script>
                    <?php else: ?>
                        <h4>Dateifreigabe</h4>
                        <div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>Es konnte keine Verbindung zu einer S3 Schnittstelle hergestellt werden.
                        Um den Dateiupload nutzen zu können, überprüfen Sie bitte Ihre Archiv Optionen</div>
                    <?php endif; //s3 ?>
                </div>
            </div>
        </div>
    <?php endwhile; ?>
</div>
