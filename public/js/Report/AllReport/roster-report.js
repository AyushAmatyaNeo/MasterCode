(function ($, app) {
    'use strict';
    $(document).ready(function () {
        $('select').select2();
        app.startEndDatePickerWithNepali('nepaliFromDate', 'fromDate', 'nepaliToDate', 'toDate', null, false);
        var $table = $('#table');
        var $search = $('#search');
        var $fromDate = $('#fromDate');
        var $toDate = $('#toDate');

        var columns = [];
        var map = {};

        app.searchTable('table', ['EMPLOYEE_ID', 'FULL_NAME'], false);

        $search.on('click', function () {
            var q = document.searchManager.getSearchValues();
            q['fromDate'] = $fromDate.val();
            q['toDate'] = $toDate.val();
            if ($fromDate.val() === "") {
                $fromDate.focus();
                app.showMessage("From Date is required.", 'error');
                return;
            }
            if ($toDate.val() === "") {
                $toDate.focus();
                app.showMessage("To Date is required.", 'error');
                return;
            }

            app.serverRequest(document.getRosterReportLink, q).then(function (response) {
                $table.empty();

                var dateRange = app.getDateRangeBetween(nepaliDatePickerExt.getDate(q['fromDate']), nepaliDatePickerExt.getDate(q['toDate']));
                columns = [{ field: "EMPLOYEE_CODE", title: "Code", locked: true, width: 100 },
                { field: "FULL_NAME", title: "Employee Name", locked: true, width: 100 }
                ];
                for (var i = 0; i < response.dates.length; i++) {
                    var columnTitle = dateRange[i].getFullYear() + "-" + ("0" + (dateRange[i].getMonth() + 1)).slice(-2) + "-" + ("0" + dateRange[i].getDate()).slice(-2);
                    let temp = response.dates[i].replace(/-/g, '_');
                    columns.push({ field: "DATE_" + temp.toUpperCase(), title: columnTitle, width: 150 });

                }
                app.initializeKendoGrid($table, columns, null, null, null, 'Roster Report');
                app.renderKendoGrid($table, response.data);
            }, function (error) {

            });
        });

        $('#excelExport').on('click', function () {
            app.excelExport($table, map, 'Roster Report.xlsx');
        });
        $('#pdfExport').on('click', function () {
            app.excelExport($table, map, 'Roster Report.pdf');
        });

        //        $('#reset').on('click', function () {
        //            $('.form-control').val("");
        //        });
    });
})(window.jQuery, window.app);