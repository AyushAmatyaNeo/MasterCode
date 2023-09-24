(function ($, app) {
    'use strict';
    $(document).ready(function () {
        $('select').select2();
        var $table = $('#table');
        var $search = $('#search');
        var extraData = {};
        var male, female, maleratio, femaleRatio;
        app.initializeKendoGrid($table, [
            { field: "EMPLOYEE_CODE", title: "Code" },
            { field: "FULL_NAME", title: "Employee" },
            { field: "GENDER_NAME", title: "Gender" }
        ]);
        $search.on('click', function () {
            var data = document.searchManager.getSearchValues();
            app.serverRequest('', data).then(function (response) {
                var male = 0;
                var female = 0;
                var maleratio = 0;
                var femaleRatio = 0;

                // Check if response contains the required data
                if (response.headCountGender && Array.isArray(response.headCountGender)) {
                    for (var i = 0; i < response.headCountGender.length; i++) {
                        var genderData = response.headCountGender[i];
                        if (genderData['GENDER_NAME'] === 'Male') {
                            male = genderData['HEAD_COUNT'] || 0;
                            maleratio = genderData['PERCENTAGE'] || 0;
                        } else if (genderData['GENDER_NAME'] === 'Female') {
                            female = genderData['HEAD_COUNT'] || 0;
                            femaleRatio = genderData['PERCENTAGE'] || 0;
                        }
                    }
                }

                extraData = [[{ value: 'Male:' }, { value: male }],
                [{ value: 'Female :' }, { value: female }],
                [{ value: 'Male Ratio:' }, { value: maleratio + ' %' }],
                [{ value: 'Female Ratio:' }, { value: femaleRatio + ' %' }]];
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
            'EMPLOYEE_CODE': 'Employee Code',
            'FULL_NAME': 'Employee Name',
            'GENDER_NAME': 'Gender',
        };
        $('#excelExport').on('click', function () {
            app.excelExportCustomised($table, exportMap, 'Male Female Report List.xlsx', extraData);
        });
        $('#pdfExport').on('click', function () {
            app.exportToPDF($table, exportMap, 'Male Female Report List.pdf');
        });


    });
})(window.jQuery, window.app);
