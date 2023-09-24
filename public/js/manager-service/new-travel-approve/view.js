(function ($, app) {
    'use strict';
    $(document).ready(function (e) {
        app.startEndDatePickerWithNepali('nepaliStartDate1', 'form-fromDate', 'nepaliEndDate1', 'form-toDate')
        $('#approve').on('click', function () {
            var recommendRemarksId = $("#form-recommendedRemarks");
            var approveRemarksId = $("#form-approvedRemarks");

            if (typeof recommendRemarksId !== "undefined") {
                recommendRemarksId.removeAttr("required");
            }
            if (typeof approveRemarksId !== "undefined") {
                approveRemarksId.removeAttr("required");
            }
            App.blockUI({ target: "#hris-page-content" });
        });
        var employeeId = $('#employeeId').val();
        window.app.floatingProfile.setDataFromRemote(employeeId);

        var $print = $('#print');
        $print.on('click', function () {
            app.exportDomToPdf('printableArea', document.urlCss);
        });

        var $noOfDays = $('#noOfDays');
        var $fromDate = $('#form-fromDate');
        var $toDate = $('#form-toDate');
        var $nepaliFromDate = $('#nepaliStartDate1');
        var $nepaliToDate = $('#nepaliEndDate1');

        var diff = Math.floor((Date.parse($toDate.val()) - Date.parse($fromDate.val())) / 86400000);
        $noOfDays.val(diff + 1);

        $fromDate.on('change', function () {
            var diff = Math.floor((Date.parse($toDate.val()) - Date.parse($fromDate.val())) / 86400000);
            $noOfDays.val(diff + 1);
        });

        $toDate.on('change', function () {
            var diff = Math.floor((Date.parse($toDate.val()) - Date.parse($fromDate.val())) / 86400000);
            $noOfDays.val(diff + 1);
        });

        $nepaliFromDate.on('change', function () {
            var diff = Math.floor((Date.parse($toDate.val()) - Date.parse($fromDate.val())) / 86400000);
            $noOfDays.val(diff + 1);
        });

        $nepaliToDate.on('change', function () {
            var diff = Math.floor((Date.parse($toDate.val()) - Date.parse($fromDate.val())) / 86400000);
            $noOfDays.val(diff + 1);
        });
    });
})(window.jQuery, window.app);
