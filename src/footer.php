</div>
</div>
<?php if(0): //saving this for later ?>
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
  </form>
  <!-- /modal -->
<?php endif; ?>

<script>
function onPageLoad(){
  if($(".js-example-basic-single")[0]){
    var open = false;
    var elem = $(".js-example-basic-single");
    elem.select2();
    elem.on('select2:select', (function(){ open = true; $(this).focus(); open = false; }));
    elem.on("select2:focus", function (e) {
      if(!open && $(this).is(':enabled') && !$(this).attr('multiple')){ $(this).select2("open") };
    });
  }

  $('input:checkbox').keypress(function(e) {
      if((e.keyCode ? e.keyCode : e.which) == 13){
        $(this).trigger('click');
        e.preventDefault();
    }
  });

  //initalize them when the user needs them
  //datepicker doc: https://www.malot.fr/bootstrap-datetimepicker/index.php
  $('.monthpicker').click(function() {
    $('.monthpicker').datetimepicker({
      autoclose: 1,
      todayBtn:  1,
      startView: 3,
      minView: 3,
      maxView: 4,
      format: 'yyyy-mm',
      keyboardNavigation: false
    });
    $(this).datetimepicker('show');
  });

  $('.datepicker').click(function() {
    $(this).datetimepicker({
      weekStart: 1,
      todayBtn:  1,
      autoclose: 1,
      minView: 2,
      forceParse: 0,
      startDate: '2000-01-01',
      format: 'yyyy-mm-dd',
      keyboardNavigation: false
    });
    $(this).datetimepicker('show');
    $(this).datetimepicker().on('changeDate', function() { this.focus(); });
  });
  $('.datepicker').mask("0000-00-00");
  $('.datepicker').attr('placeholder', 'yyyy-mm-dd');

  $('.datetimepicker').click(function() {
    $(this).datetimepicker({
      weekStart: 1,
      todayBtn:  1,
      autoclose: 1,
      forceParse: 0,
      startDate: '2010-01-01',
      format: 'yyyy-mm-dd hh:ii',
      keyboardNavigation: false
    });
    $(this).datetimepicker('show');
  });
  /*
  $('.timepicker').timepicker({
    todayBtn:  1,
    autoclose: 1,
    todayHighlight: 1,
    startView: 1,
    minView: 0,
    maxView: 1,
    forceParse: 0,
    format: 'hh:ii'
  });
  */
  $('.timepicker').attr('pattern', '^([01][0-9]|2[0-3]):([0-5][0-9])$');
  $('.timepicker').mask("20:50", {
    placeholder: "--:--",
    selectOnFocus: true,
    translation: {
      2: {pattern: /[0-2]/},
      5: {pattern: /[0-5]/}
    }
  });
}
$('.money').blur(function(e){
  var number = this.value;
  if(number == "") return;
  this.value = parseFloat(number).toFixed(2);
});

$(document).ready(function() {
  onPageLoad();
  var isDirty = false;
  //triggers change in all input fields including text type
  $(":input:not([type=search]):not(.not-dirty)").keyup(function(){
    isDirty = true;
    var blink = $(this).closest('form').find('.blinking');
    if(!blink.length){ blink = $('.blinking'); }
    blink.attr('class', 'btn btn-warning blinking');
    setInterval(function() {
      blink.fadeOut(500, function() {
        blink.fadeIn(500);
      });
    }, 1000);
  });
  $(':submit').click(function() {
    isDirty = false;
  });

  window.onbeforeunload = function() {
    if(isDirty){ return "You have unsaved changes on this page. Discard your changes?"; }
    document.getElementById("loader").style.display = "block";
  };
  document.getElementById("loader").style.display = "none";
  document.getElementById("bodyContent").style.display = "block";  
});

$(window).scroll(function() {
  sessionStorage.scrollTop = $(this).scrollTop();
});
$(document).ready(function() {
  if (sessionStorage.scrollTop != "undefined") {
    $(window).scrollTop(sessionStorage.scrollTop);
  }
});
</script>
</body>
</html>
