(function ($, app) {
    'use strict';
    $(document).ready(function () {
        $('select').select2();
        var $table = $('#table');
        var $search = $('#search');
        var male, female, maleratio, femaleRatio;
        var months = null;
        var $ageGeneration = $('#ageGeneration');

        app.initializeKendoGrid($table, [
            { field: "GENERATION", title: "Generation" },
            { field: "BORN", title: "Born" },
            { field: "CURRENT_AGE", title: "Current Age" },
            { field: "TOTAL", title: "Total" }
        ]);
        $search.on('click', function () {
            var data = document.searchManager.getSearchValues();
            data['ageGeneration'] = $ageGeneration.val();
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
        app.searchTable($table, ['GENERATION', 'BORN', 'CURRENT_AGE']);
        var exportMap = {
            'GENERATION': 'Generation',
            'BORN': 'Born',
            'CURRENT_AGE': 'Current Age',
            'TOTAL': 'Total'
        };

        $('#excelExport').on('click', function () {
            app.excelExport($table, exportMap, 'Age by Generation Report List.xlsx');
        });
        $('#pdfExport').on('click', function () {
            app.exportToPDF($table, exportMap, 'Age by Generation Report List.pdf');
        });


    });
})(window.jQuery, window.app);
