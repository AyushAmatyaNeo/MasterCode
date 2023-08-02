(function ($, app, formulaWriter) {
    'use strict';
    $(document).ready(function () {
        var $form = $('#rules');
        var $formula = $('#formula');
        var editor = formulaWriter('formula', formulaData);
        var normalFormula = editor.getValue();
        $(".flagContainer").hide(); 

        $form.on('submit', function () {
            var value = editor.getValue();
            if (value == '') {
                app.showMessage('Formula is Required.', 'error');
                return false;
            }
            return true;
        });

        $("#salaryType").on('input', function(){
            var selectedSalaryType = $("#salaryType").val();
            editor.setValue("");
            if(selectedSalaryType == 1){
                editor.setValue(normalFormula);
                $(".flagContainer").hide(); 
            }
            else{
                $(".flagContainer").show(); 
                var specialFormula = '';
                var flag = 'N';
                if(document.specialRules.length > 0){
                    for(let i = 0; i < document.specialRules.length; i++){
                        if(document.specialRules[i].SALARY_TYPE_ID == selectedSalaryType){
                            specialFormula = document.specialRules[i].FORMULA;
                            flag = document.specialRules[i].FLAG;
                            break;
                        }
                    }
                    editor.setValue(specialFormula);
                    (flag == 'Y') ? $('#flag option[value="Y"]').prop('selected', true) : $('#flag option[value="N"]').prop('selected', true);
                }
            }
        });
    });

})(window.jQuery, window.app, window.formulaWriter);