</div>
</div>
</div>
</body>

<?php if(isset($_SERVER['RDS_HOSTNAME'])): ?>
<form method="post">
  <div class="container">
    <div class="navbar-fixed-bottom text-right">
      <button type="button" class="btn" style="margin-right:10px; background:#707070; color:white;" data-toggle="modal" data-target="#feedbackModal"><i class="fa fa-commenting-o"></i> Feedback</button>
    </div>
  </div>
  <!-- modal -->
  <div class="modal fade" id="feedbackModal" tabindex="-1" role="dialog" aria-labelledby="myFeedbackModalLabel">
    <div class="modal-dialog" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
          <h4 class="modal-title" id="myFeedbackModalLabel">Zur Verbesserung von T-Time beitragen</h4>
        </div>
        <div class="modal-body form-group">
          Bewerten Sie Ihre Erfahrungen mit T-Time <br><br>
          <textarea style="resize:none;color:black;" class="form-control" name="feedbackText" rows="5" placeholder="Teilen Sie uns mit, was Ihnen gefallen hat, und was wir besser machen können. Geben Sie zum Schutz Ihrer Daten keine persönlichen Informationen ein."></textarea>
          <br><br>
          <div class="checkbox" style="margin-left:20px;"><input type="checkbox" name="contactMePlease"><small>Sie dürfen mich wegen dieses Feedbacks kontaktieren.</small></div>
          <input type="email" class="form-control" name="feedbackmail" placeholder="example@email.com">
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-warning" name="sendUsYourFeedback">Absenden</button>
        </div>
      </div>
    </div>
  </div>
  <!-- /modal -->
  <?php if(isset($_POST['sendUsYourFeedback'])){
    $feedbackText = test_input($_POST['feedbackText']);
    if(isset($_POST['contactMePlease'])){
      $feedbackmail = test_input($_POST['feedbackmail']);
    } else {
      $feedbackmail = "";
    }
    $feedbackConn = mysqli_connect($_SERVER['RDS_HOSTNAME'], $_SERVER['RDS_USERNAME'], $_SERVER['RDS_PASSWORD'], 'systematic', $_SERVER['RDS_PORT']);
    $feedbackConn->query("INSERT INTO feedbacks (info, mail) VALUES('$feedbackText', '$feedbackmail')");
    $feedbackConn->close();
  }
endif; ?>
</form>
</html>
