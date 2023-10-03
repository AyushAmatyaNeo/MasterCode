(function ($, app) {
    'use strict';
    $(document).ready(function () {
        $("select").select2();
        app.searchTable('ageReport', ['EMPLOYEE_CODE', 'FULL_NAME'], false);

        var $greaterThan = $('#greaterThan');
        var $lessThan = $('#lessThan');
        var $search = $('#search');
        var $ageReport = $('#ageReport');

        var map = {
            'EMPLOYEE_CODE': 'Code',
            'FULL_NAME': 'Name',
            'DEPARTMENT_NAME': 'Department',
            'AGE': 'Age',
            'BIRTH_DATE': 'Birth Date'
        };

        app.initializeKendoGrid($ageReport, [
            { field: "EMPLOYEE_CODE", title: "Code", width: 100, locked: true },
            { field: "FULL_NAME", title: "Name", width: 250, locked: true },
            { field: "DEPARTMENT_NAME", title: "Department", width: 175 },
            { field: "AGE", title: "Age", width: 150 },
            { field: "BIRTH_DATE", title: "Birth Date", width: 180 }

        ], null, null, null, 'Age Report.xlsx');

        $search.on('click', function () {
            var data = document.searchManager.getSearchValues();
            var age = $('#ageGeneration').val();
            if (age == 1) {
                data['lessThan'] = 26;
                data['greaterThan'] = 11;
            } else if (age == 2) {
                data['lessThan'] = 42;
                data['greaterThan'] = 27;
            }
            else if (age == 3) {
                data['lessThan'] = 52;
                data['greaterThan'] = 43;
            } else if (age == 4) {
                data['lessThan'] = 68;
                data['greaterThan'] = 52;
            }
            else if (age == 5) {
                data['lessThan'] = 77;
                data['greaterThan'] = 69;
            } else {
                data['lessThan'] = null;
                data['greaterThan'] = null;
            }

            app.serverRequest(document.ageWs, data).then(function (response) {
                if (response.success) {
                    app.renderKendoGrid($ageReport, response.data);
                } else {
                    app.showMessage(response.error, 'error');
                }
            }, function (error) {
                app.showMessage(error, 'error');
            });
        });

        $('#export').on('click', function () {
            app.excelExport($ageReport, map, 'Age Report.xlsx');
        });

    });
})(window.jQuery, window.app);