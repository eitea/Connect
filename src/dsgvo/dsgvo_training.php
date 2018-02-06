<?php require dirname(__DIR__) . '/header.php'; enableToDSGVO($userID);?>
<?php require dirname(__DIR__) . "/misc/helpcenter.php"; ?>
<script src='../plugins/tinymce/tinymce.min.js'></script>

<?php
// A "training" is a group of questions (set)
// A Question is a text with different answers

$trainingID = 0;
$companyID = intval($_REQUEST['n']);
if($_SERVER['REQUEST_METHOD'] == 'POST'){
    if(isset($_POST['createTraining']) && !empty($_POST['name'])){
        $name = test_input($_POST['name']);
        $conn->query("INSERT INTO dsgvo_training (name,companyID) VALUES('$name', $companyID)");
        $trainingID = mysqli_insert_id($conn);
    } elseif(isset($_POST['removeTraining'])){
        $trainingID = intval($_POST['removeTraining']);
        $conn->query("DELETE FROM dsgvo_training WHERE id = $trainingID");
    } elseif(isset($_POST['addQuestion']) && !empty($_POST['question']) && !empty($_POST["title"])){
        $trainingID = intval($_POST['addQuestion']);
        $title = test_input($_POST["title"]);
        $text = test_input($_POST["question"]);
        $conn->query("INSERT INTO dsgvo_training_questions (trainingID, text, title) VALUES($trainingID, '$text', '$title')");
    } elseif(isset($_POST["removeQuestion"])){
        $trainingID = $_POST["trainingID"];
        $questionID = intval($_POST["removeQuestion"]);
        $conn->query("DELETE FROM dsgvo_training_questions WHERE id = $questionID");
    } elseif(isset($_POST["editQuestion"])){
        $questionID = intval($_POST["editQuestion"]);
        $title = test_input($_POST["title"]);
        $text = $_POST["question"];
        echo "question text hasn't been sanitized";
        $conn->query("UPDATE dsgvo_training_questions SET text = '$text', title = '$title' WHERE id = $questionID");
    } elseif(isset($_POST["editTraining"])){
        $trainingID = $_POST["editTraining"];
        $version = 1;
        if(isset($_POST["version"])){
            $version = intval($_POST["version"]);
        }
        $name = test_input($_POST["name"]);
        $onLogin = test_input($_POST["onLogin"]);
        $conn->query("UPDATE dsgvo_training SET version = $version, name = '$name', onLogin = '$onLogin' WHERE id = $trainingID");
        $conn->query("DELETE FROM dsgvo_training_user_relations WHERE trainingID = $trainingID");
        $conn->query("DELETE FROM dsgvo_training_team_relations WHERE trainingID = $trainingID");
        if(isset($_POST["employees"])){
            $employeeID = $teamID = "";
            $stmtUser = $conn->prepare("INSERT INTO dsgvo_training_user_relations (trainingID, userID) VALUES ($trainingID, ?)"); echo $conn->error;
            $stmtTeam = $conn->prepare("INSERT INTO dsgvo_training_team_relations (trainingID, teamID) VALUES ($trainingID, ?)"); echo $conn->error;
            $stmtUser->bind_param("i", $employeeID);
            $stmtTeam->bind_param("i", $teamID);
            foreach ($_POST["employees"] as $employee) {
                $emp_array = explode(";", $employee);
                if ($emp_array[0] == "user") {
                    $employeeID = intval($emp_array[1]);
                    $stmtUser->execute();
                } else { //team
                    $teamID = intval($emp_array[1]);
                    $stmtTeam->execute();
                }
            }
        }
    }
}
$activeTab = $trainingID;
echo mysqli_error($conn);
?>

<div class="page-header">
  <h3>Schulungen <div class="page-header-button-group"><button type="button" data-toggle="modal" data-target="#newTrainingModal" title="<?php echo $lang['ADD']; ?>" class="btn btn-default">+</button></div></h3>
</div>

<div class="container-fluid">
    <?php
    $result = $conn->query("SELECT * FROM dsgvo_training WHERE companyID = $companyID");
    while($result && ($row = $result->fetch_assoc())):
        $trainingID = $row['id'];
    ?>
    <form method="post">
        <input type="hidden" name="trainingID" value="<?php echo $trainingID; ?>" />
    <div class="panel panel-default">
      <div class="panel-heading container-fluid">
        <div class="col-xs-6"><a data-toggle="collapse" href="#trainingCollapse-<?php echo $trainingID; ?>"><?php echo $row['name']; ?></a></div>
        <div class="col-xs-6 text-right"><button type="submit" style="background:none;border:none;color:#d90000;" name="removeTraining" value="<?php echo $trainingID; ?>"><i class="fa fa-trash-o"></i></button></div>
      </div>
      <div class="collapse <?php if($trainingID == $activeTab) echo 'in'; ?>" id="trainingCollapse-<?php echo $trainingID; ?>">
        <div class="panel-body container-fluid"> 
            <?php

            $result_question = $conn->query("SELECT * FROM dsgvo_training_questions WHERE trainingID = $trainingID");
            while ($row_question = $result_question->fetch_assoc()):
                $questionID = $row_question["id"];
                $title = $row_question["title"];
                $text = $row_question["text"];
                ?>
                 <div class="col-md-4"><button type="submit" style="background:none;border:none" name="removeQuestion" value="<?php echo $questionID; ?>"><i class="fa fa-trash"></i></button>
                 <button type="button" style="background:none;border:none" name="editQuestion" value="<?php echo $questionID; ?>"><i class="fa fa-edit"></i></button>
            <?php echo $title ?></div>
<?php
            endwhile;

            ?>
          
           <div class="col-md-12 text-right">
            <button type="button" class="btn btn-default" data-toggle="modal" data-target="#addQuestionModal_<?php echo $trainingID; ?>"><i class="fa fa-plus"></i></a>
            <button type="button" class="btn btn-warning" name="editTraining" value="<?php echo $trainingID; ?>"><i class="fa fa-pencil-square-o"></i></button>
           </div>
        </div>
      </div>
    </div>
    <!-- question add modal -->
     <div class="modal fade" id="addQuestionModal_<?php echo $trainingID; ?>">
      <div class="modal-dialog modal-content modal-md">
        <div class="modal-header">Neue Aufgabenstellung/Schulung</div>
        <div class="modal-body">
            <input type="text" name="title" class="form-control" placeholder="Title"></input><br/>
            <input type="text" name="question" class="form-control tinymce" placeholder="Question"></input>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default" data-dismiss="modal">Abbrechen</button>
          <button type="submit" class="btn btn-warning" name="addQuestion" value="<?php echo $trainingID; ?>">Frage erstellen</button>
        </div>
      </div>
    </div>
    <!-- /question add modal -->
</form>

  <?php endwhile; ?>
</div>

<!-- new training modal -->

<form method="post">
  <div id="newTrainingModal" class="modal fade" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-md" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h4 class="modal-title">Neues Set</h4>
        </div>
        <div class="modal-body">
        <label>Name*</label>
        <input type="text" class="form-control" name="name" placeholder="Name des Sets" />
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default" data-dismiss="modal">Abbrechen</button>
          <button type="submit" class="btn btn-warning" name="createTraining" value="true"><?php echo $lang['ADD']; ?></button>
        </div>
      </div>
    </div>
  </div>
</form>

<!-- /new training modal -->

<div id="currentQuestionModal"></div> <!-- for question and training edit modals -->

<script>
function setCurrentQuestionModal(index){
    $.ajax({
        url:'ajaxQuery/AJAX_dsgvoQuestionEdit.php',
        data:{questionID: index},
        type: 'get',
        success : function(resp){
            $("#currentQuestionModal").html(resp);
        },
        error : function(resp){console.error(resp)},
        complete: function(resp){
            if(index){
                onModalLoad();
                $("#currentQuestionModal .modal").modal('show');
            }
        }
   });
}
$("button[name=editQuestion]").click(function(){
    setCurrentQuestionModal($(this).val())
})
function setCurrentTrainingModal(index){
    $.ajax({
        url:'ajaxQuery/AJAX_dsgvoTrainingEdit.php',
        data:{trainingID: index},
        type: 'get',
        success : function(resp){
            $("#currentQuestionModal").html(resp);
        },
        error : function(resp){console.error(resp)},
        complete: function(resp){
            if(index){
                onModalLoad();
                $("#currentQuestionModal .modal").modal('show');
            }
        }
   });
}
$("button[name=editTraining]").click(function(){
    setCurrentTrainingModal($(this).val())
})
</script>

<script>
function formatState (state) {
    if (!state.id) { return state.text; }
    var $state = $(
        '<span><i class="fa fa-' + state.element.dataset.icon + '"></i> ' + state.text + '</span>'
    );
    return $state;
};
function onModalLoad(){
    tinymce.init({
        selector: '.tinymce',
        toolbar: 'undo redo | cut copy paste | styleselect | link | insertquestion | emoticons',
        setup: function(editor){
            function insertQuestion(){
                var html = "<p>{ </p><p>[-] Wrong Answer 1 </p><p>[+] Right Answer 2 </p><p> }</p>";
                editor.insertContent(html);
            }

            editor.addButton("insertquestion",{
                tooltip: "Insert question",
                icon: "template",
                onclick: insertQuestion,
            });
        },
        height : "480",
    });
    $(".select2-team-icons").select2({
        templateResult: formatState,
        templateSelection: formatState
    });
}
onModalLoad();
</script>

<!-- /BODY -->
<?php include dirname(__DIR__) . '/footer.php'; ?>
