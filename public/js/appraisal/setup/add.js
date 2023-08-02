(function ($,app) {
    'use strict';
    $(document).ready(function () {
        $('select').select2();
//        app.startEndDatePicker('startDate', 'endDate');
        app.startEndDatePickerWithNepali('nepaliStartDate1', 'startDate', 'nepaliEndDate1', 'endDate');
        var inputFieldId = "appraisalEdesc";
        var formId = "appraisalSetup-form";
        var tableName =  "HRIS_APPRAISAL_SETUP";
        var columnName = "APPRAISAL_EDESC";
        var checkColumnName = "APPRAISAL_ID";
        var selfId = $("#appraisalId").val();
        if (typeof(selfId) == "undefined"){
            selfId=0;
        }
        window.app.checkUniqueConstraints(inputFieldId,formId,tableName,columnName,checkColumnName,selfId, function () {
            App.blockUI({target: "#hris-page-content"});
        });     
        window.app.checkUniqueConstraints("appraisalCode",formId,tableName,"APPRAISAL_CODE",checkColumnName,selfId);
        window.app.checkUniqueConstraints("appraisalNdesc",formId,tableName,"APPRAISAL_NDESC",checkColumnName,selfId);
    });
})(window.jQuery,window.app);

