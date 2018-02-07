<?php
require dirname(__DIR__) . "/connection.php";
require dirname(__DIR__) . "/language.php";


?>
<script src='../plugins/node_modules/survey-jquery/survey.jquery.min.js'></script>

<div class="modal fade">
    <div class="modal-dialog modal-content modal-md">
        <div class="modal-header">Bitte beantworten Sie folgende Fragen</div>
        <div class="modal-body">
            <div id="surveyElement"></div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-default" data-dismiss="modal">Abbrechen</button>
        </div>
    </div>
</div>

<script>
    Survey.Survey.cssType = "bootstrap";
    Survey.defaultBootstrapCss.navigationButton = "btn btn-warning";

    var json = {
        questions: [
            {
                type: "html",
                name: "info",
                html: "<table><body><row><td><img src='/Content/Images/examples/26178-20160417.jpg' width='100px' /></td><td style='padding:20px'>You may put here any html code. For example images, <b>text</b> or <a href='https://surveyjs.io/Survey/Builder'  target='_blank'>links</a></td></row></body></table>"
            },
            {
                type: "radiogroup",
                name: "car",
                title: "What car are you driving?",
                isRequired: true,
                colCount: 1,
                choices: [
                    "None",
                    "Ford",
                    "Vauxhall",
                    "Volkswagen"
                ]
            }
        ]
    };

    window.survey = new Survey.Model(json);

    survey
        .onComplete
        .add(function (result) {
            alert("result: " + JSON.stringify(result.data));
        });


    $("#surveyElement").Survey({ model: survey });
</script>