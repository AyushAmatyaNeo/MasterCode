(function ($) {
    'use strict';
    $(document).ready(function () {
        var $table = $('#leaveTable');

        app.initializeKendoGrid($table, [
            {field: "LEAVE_ENAME", title: "Leave Name"},
            {field: "PREVIOUS_YEAR_BAL", title: "Previous"},
            {field: "TOTAL_DAYS", title: "Total Days"},
            {field: "LEAVE_TAKEN", title: "Leave taken"},
            {field: "ENCASHED", title: "Encashed"},
            {field: "LEAVE_DEDUCTED", title: "Leave Deducted"},
            {field: "LEAVE_ADDED", title: "Leave Added"},
            {field: "BALANCE", title: "Available Days"}
        ], null, null, null, 'Leave Balance List');


        app.pullDataById("", {}).then(function (response) {
            app.renderKendoGrid($table, response.data);
        }, function (error) {

        });


        app.searchTable('leaveTable', ['LEAVE_ENAME']);

        var exportMap = {
            'LEAVE_ENAME': 'Leave',
            'PREVIOUS_YEAR_BAL': 'Previous',
            'TOTAL_DAYS': 'Total Days',
            'LEAVE_TAKEN': 'Leave Taken',
            'LEAVE_DEDUCTED': 'Leave Deducted',
            'LEAVE_ADDED': 'Leave Added',
            'BALANCE': 'Available Days'
        };
        $('#excelExport').on('click', function () {
            app.excelExport($table, exportMap, 'Leave Balance List');
        });
        $('#pdfExport').on('click', function () {
            app.exportToPDF($table, exportMap, 'Leave Balance List');
        });


        console.log(document.currentMonth);

        app.populateSelect($('#leaveMonth'), document.monthList, 'LEAVE_YEAR_MONTH_NO', 'MONTH_EDESC', null, null, document.currentMonth)
        var $month = $('#leaveMonth');
        var $monthlyLeaveTable = $('#monthlyLeaveTable');



        app.initializeKendoGrid($monthlyLeaveTable, [
            {field: "LEAVE_ENAME", title: "Leave Name"},
            {field: "TOTAL_DAYS", title: "Total Days"},
            {field: "LEAVE_TAKEN", title: "Leave taken"},
            {field: "BALANCE", title: "Available Days"}
        ], null, null, null, 'Leave Balance List');


        var populateMonthlyLeave = function () {
            var value = $month.val();
            if (value == null) {
                return;
            }
            app.serverRequest("", {fiscalYearMonthNo: value}).then(function (response) {
                app.renderKendoGrid($monthlyLeaveTable, response.data);
            }, function (error) {

            });
        };

        populateMonthlyLeave();


        $month.on('change', function () {
            populateMonthlyLeave();
        });



    });
})(window.jQuery, window.app);
