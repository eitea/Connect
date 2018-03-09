</div>
</div>
<div id="currentSurveyModal"></div>
<script>
$(".openSurvey").click(function(){
    openSurveyModal()
})
function openSurveyModal(){
  $.ajax({
        url:'ajaxQuery/AJAX_getTrainingSurvey.php',
        data:{<?php echo $userHasUnansweredOnLoginSurveys?"onLogin:true":"" ?>},
        type: 'get',
        success : function(resp){
            $("#currentSurveyModal").html(resp);
        },
        error : function(resp){console.error(resp)},
        complete: function(resp){
            $("#currentSurveyModal .survey-modal").modal({
                backdrop: 'static',
                keyboard: false
            });
        }
   });
}
<?php if($userHasUnansweredOnLoginSurveys){echo "setTimeout(function(){ openSurveyModal() },500)";} ?>
</script>
<?php // endif; ?>

<button type='button' class='btn btn-primary feedback-button'>Feedback</button>
<script>
$("#feedback_form").submit(function(event){
    console.log("feedBack");
    event.preventDefault();
    var img = window.feedbackCanvasObject.toDataURL()
    var postData =  {
        location:window.location.href,
        message: $("#feedback_message").val(),
        type:$('input[name=feedback_type]:checked').val()
    };
    if(document.getElementById("feedback_includeScreenshot").checked){
        postData.screenshot = img;
    }
    $.ajax({
        url: "ajaxQuery/AJAX_sendFeedback.php",
        data: postData,
        async: true,
        method: "POST",
        complete: function(response){
            alert(response.responseText);
            //clear form
            $("#feedback_message").val("");
            $('#feedbackModal').modal('hide');
        }
    });
});
$(".feedback-button").on("click",function(){
    html2canvas(document.body).then(function(canvas) {
        canvas.toBlob(function(blob) {
            var newImg = document.createElement('img'), url = URL.createObjectURL(blob);
            newImg.onload = function() {
                URL.revokeObjectURL(url);
            };
            newImg.src = url;
            $("#screenshot").html(newImg);
        });
        window.feedbackCanvasObject = canvas
        $('#feedbackModal').appendTo("body").modal('show');
    });
})

var reload = 0;
function onPageLoad(){
  if($(".js-example-basic-single")[0]){
    $(".js-example-basic-single").each(function(i, obj){
        var elem = $(obj);
        if(!elem.data('select2')) {
            var open = false;
            elem.select2();
            elem.on('select2:select', (function(){ open = true; $(this).focus(); open = false; }));
            elem.on("select2:focus", function (e) {
                if(!open && $(this).is(':enabled') && !$(this).attr('multiple')){ $(this).select2("open") };
            });
        }
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
