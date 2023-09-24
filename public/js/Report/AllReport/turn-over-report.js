(function ($, app) {
    'use strict';
    $(document).ready(function () {
        $('select').select2();
        var $table = $('#table');
        var $search = $('#search');
        var male, female, maleratio, femaleRatio;
        var months = null;
        var $year = $('#fiscalYearId');
        var $month = $('#monthId');
        //        app.setFiscalMonth($year, $month, function (yearList, monthList, currentMonth) {
        //            months = monthList;
        //        });
        app.setFiscalMonth($year, $month, function (yearList, monthList, currentMonth) {
            months = monthList;
        });
        app.initializeKendoGrid($table, [
            { field: "COMPANY_NAME", title: "Unit" },
            { field: "BEGINNING", title: "Beginning Employees" },
            { field: "ENDING", title: "Ending Employees" },
            { field: "REAL_SEPARATION", title: "Real Separation" },
            { field: "AVERAGE", title: "Average" },
            { field: "EMPLOYEE_TURNOVER", title: "Employee TurnOver" }
        ]);
        $search.on('click', function () {
            var data = document.searchManager.getSearchValues();
            data['year'] = $year.val();
            data['month'] = $month.val();
            app.serverRequest('', data).then(function (response) {
                if (response.success) {
                    app.renderKendoGrid($table, response.data);
                } else {
                    app.showMessage(response.error, 'error');
                }
            }, function (error) {
                app.showMessage(error, 'error');
            });
        });
        app.searchTable($table, ['COMPANY_NAME', 'BEGINNING', 'ENDING']);
        var exportMap = {
            'COMPANY_NAME': 'Unit',
            'BEGINNING': 'Beginning Employees',
            'ENDING': 'Ending Employees',
            'REAL_SEPARATION': 'Real Separation',
            'AVERAGE': 'Average',
            'EMPLOYEE_TURNOVER': 'Employee TurnOver',
        };

        $('#excelExport').on('click', function () {
            app.excelExport($table, exportMap, 'Renumeration Report List.xlsx');
        });
        $('#pdfExport').on('click', function () {
            app.exportToPDF($table, exportMap, 'Renumeration Report List.pdf');
        });


    });
})(window.jQuery, window.app);
