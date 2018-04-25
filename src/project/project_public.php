<?php require dirname(__DIR__).DIRECTORY_SEPARATOR.'header.php'; ?>

<div class="page-header h4">Meine Projekte</div>
<div class="container-fluid panel-group" id="accordion">
    <?php
	$activeTab = '';
    if(!empty($_SESSION['external_id'])){
        $tableName = 'relationship_project_extern';
    } else {
        $tableName = 'relationship_project_user';
	}
	$result_outer = $conn->query("SELECT p.name, p.id FROM $tableName t LEFT JOIN projectData p ON p.id = t.projectID $accessTableName WHERE t.userID = $userID");
    if(!$result_outer || $result_outer->num_rows < 1) echo $conn->error.__LINE__;
    while($result_outer && ($row_outer = $result_outer->fetch_assoc())):
        $projectID = $row_outer['id'];
    ?>
        <div class="panel panel-default">
            <div class="panel-heading" role="tab" id="heading<?php echo $projectID; ?>">
                <h4 class="panel-title">
                    <a role="button" data-toggle="collapse" data-parent="#accordion" href="#collapse<?php echo $projectID; ?>"><?php echo $row_outer['name']; ?></a>
                </h4>
            </div>
            <div id="collapse<?php echo $projectID; ?>" class="panel-collapse collapse <?php if($projectID == $activeTab) echo 'in'; ?>">
                <div class="panel-body">
        			<?php include __DIR__.DIRECTORY_SEPARATOR.'project_detail_include.php'; ?>
                </div>
            </div>
        </div>
    <?php endwhile; ?>
</div>

<?php require dirname(__DIR__).DIRECTORY_SEPARATOR.'footer.php'; ?>
