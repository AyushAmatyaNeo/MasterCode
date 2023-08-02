
(function ($, app) {
    'use strict';
    $(document).ready(function () {

        var inputFieldId = "form-roleName";
        var formId = "rolesetup-form";
        var tableName = "HRIS_ROLES";
        var columnName = "ROLE_NAME";
        var checkColumnName = "ROLE_ID";
        var selfId = $("#roleId").val();
        var $controlOption = $("#controlOption");
        var $controlOptionsSelect = $("#controlOptionsSelect");

        var $selectOptions = $("#selectOptions");
        var $selectOptionsC = $("#selectOptionsC");
        var $selectOptionsDP = $("#selectOptionsDP");
        var $selectOptionsDS = $("#selectOptionsDS");
        var $selectOptionsP = $("#selectOptionsP");
        var $selectOptionsB = $("#selectOptionsB");
        var $control = $("#control");
        $selectOptions.select2();
        $selectOptionsC.select2();
        $selectOptionsDP.select2();
        $selectOptionsDS.select2();
        $selectOptionsP.select2();
        $selectOptionsB.select2();
        $control.select2();

        app.populateSelect($selectOptionsC, document.searchValues['company'], 'COMPANY_ID', 'COMPANY_NAME', '---', '');
        app.populateSelect($selectOptionsB, document.searchValues['branch'], 'BRANCH_ID', 'BRANCH_NAME', '---', '');
        app.populateSelect($selectOptionsDP, document.searchValues['department'], 'DEPARTMENT_ID', 'DEPARTMENT_NAME', '---', '');
        app.populateSelect($selectOptionsDS, document.searchValues['designation'], 'DESIGNATION_ID', 'DESIGNATION_TITLE', '---', '');
        app.populateSelect($selectOptionsP, document.searchValues['position'], 'POSITION_ID', 'POSITION_NAME', '---', '');

        var controlOptions = [
        {"key": "C", "value": "Company Specific"},
        {"key": "B", "value": "Branch Specific"},
        {"key": "DS", "value": "Designation Specific"},
        {"key": "DP", "value": "Department Specific"},
        {"key": "P", "value": "Position Specific"}
        ];
        var controlOptions1 = [
        {"key": "C", "value": "Select Company"},
        {"key": "B", "value": "Select Branch"},
        {"key": "DS", "value": "Select Designation"},
        {"key": "DP", "value": "Select Department"},
        {"key": "P", "value": "Select Position"}
        ];
        app.populateSelect($control, controlOptions, 'key', 'value');

        if(document.controls.indexOf('F') != -1){ document.controls.splice(document.controls.indexOf('F'), 1); }

        let selectedOptionsC = [], selectedOptionsDS = [], selectedOptionsDP = [], selectedOptionsP = [], selectedOptionsB = [];

        if(document.controls != null){
            if(document.controls.includes('C')){
                selectedOptionsC = document.selectedControlValues.filter(i => i.CONTROL == 'C').map(j => j.VAL);
                $selectOptionsC.val(selectedOptionsC);
            }
            if(document.controls.includes('DP')){
                selectedOptionsDP = document.selectedControlValues.filter(i => i.CONTROL == 'DP').map(j => j.VAL);
                $selectOptionsDP.val(selectedOptionsDP);
            }
            if(document.controls.includes('DS')){
                selectedOptionsDS = document.selectedControlValues.filter(i => i.CONTROL == 'DS').map(j => j.VAL);
                $selectOptionsDS.val(selectedOptionsDS);
            }
            if(document.controls.includes('P')){
                selectedOptionsP = document.selectedControlValues.filter(i => i.CONTROL == 'P').map(j => j.VAL);
                $selectOptionsP.val(selectedOptionsP);
            }
            if(document.controls.includes('B')){
                selectedOptionsB = document.selectedControlValues.filter(i => i.CONTROL == 'B').map(j => j.VAL);
                $selectOptionsB.val(selectedOptionsB);
            }
        }

        $control.change(function(){
            var a = $control.val();
            var populateValues = [];
            if(a != null){
                populateValues = controlOptions1.filter(function(item, i){
                    return a.includes(item.key);
                });
            }
            app.populateSelect($controlOptionsSelect, populateValues, 'key', 'value', '----Select One------');
            hideShowSelect();
        });

        if(document.controls.length > 0){ $control.val(document.controls); $control.change(); }

        $controlOptionsSelect.change(function () {
            var controlValue = $controlOptionsSelect.val();
            controlValue == -1 ? hideShowSelect() : hideShowSelect("selectOptions"+controlValue);
            //$selectOptions.val(populateValues).trigger('change.select2');
        });

        function hideShowSelect(show = null){
            $(".selectOptions, .selectOptionsP, .selectOptionsDS, .selectOptionsDP, .selectOptionsB, .selectOptionsC").hide();
            show == null ? $(".selectOptions").show() : $("."+show).show() ;
        }

        hideShowSelect();

        if (typeof selfId === "undefined") {
            selfId = 0;
        }
        app.checkUniqueConstraints(inputFieldId, formId, tableName, columnName, checkColumnName, selfId, function () {
            App.blockUI({target: "#hris-page-content"});
        });
    });
})(window.jQuery, window.app);

