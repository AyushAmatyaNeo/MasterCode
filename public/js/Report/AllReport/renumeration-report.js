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
            { field: "FULL_NAME", title: "Employee" },
            { field: "BASIC_SALARY", title: "Basic Salary" },
            { field: "ALLOWANCE", title: "Allowance" },
            { field: "GROSS_AMOUNT", title: "Gross Salary" },
            { field: "SSF", title: "SSF (20%)" },
            { field: "TOTAL_RENUMERATION", title: "Total Monthly Remuneration" }
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
        app.searchTable($table, ['FULL_NAME', 'EMPLOYEE_CODE']);
        var exportMap = {
            'FULL_NAME': 'Employee Name',
            'BASIC_SALARY': 'Basic Salary',
            'ALLOWANCE': 'Allowance',
            'GROSS_AMOUNT': 'Gross Salary',
            'SSF': 'SSF(20%)',
            'TOTAL_RENUMERATION': 'Total Monthly Remuneration',
        };

        $('#excelExport').on('click', function () {
            app.excelExport($table, exportMap, 'Renumeration Report List.xlsx');
        });
        $('#pdfExport').on('click', function () {
            app.exportToPDF($table, exportMap, 'Renumeration Report List.pdf');
        });


    });
})(window.jQuery, window.app);
