(function ($, app) {
    'use strict';
    $(document).ready(function () {
        $('select').select2();
        var $table = $('#table');
        var $search = $('#search');
        var $trainingId = $('#trainingId');
        var $fromDate = $('#fromDate');
        var $toDate = $('#toDate');
        // var $bulkActionDiv = $('#bulkActionDiv');
        // var $bulkBtns = $(".btnApproveReject");
        var $superpower = $("#super_power");
        var hoursPerDay;


        app.initializeKendoGrid($table, [
            { field: "COMPANY_NAME", title: "Company" },
            { field: "EMPLOYEE_CODE", title: "Employee Code" },
            { field: "FULL_NAME", title: "Employee" },
            { field: "TITLE", title: "Training" },
            { field: "TRAINING_TYPE", title: "Type" },
            {
                title: "Start Date",
                columns: [{
                    field: "START_DATE",
                    title: "AD",
                },
                {
                    field: "START_DATE_BS",
                    title: "BS",
                }]
            },
            {
                title: "End Date",
                columns: [{
                    field: "END_DATE",
                    title: "AD",
                },
                {
                    field: "END_DATE_BS",
                    title: "BS",
                }]
            },
            { field: "DURATION", title: "Duration" },
            {
                title: "Requested Date",
                columns: [
                    {
                        field: "REQUESTED_DATE",
                        title: "AD",
                    },
                    {
                        field: "REQUESTED_DATE_BS",
                        title: "BS",
                    }]
            },
            { field: "STATUS_DETAIL", title: "Status" },
        ]);

        $search.on('click', function () {
            var data = document.searchManager.getSearchValues();
            data['trainingId'] = $trainingId.val();
            // data['fromDate'] = $fromDate.val();
            // data['toDate'] = $toDate.val();
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
        app.searchTable($table, ['FULL_NAME', 'EMPLOYEE_CODE', 'TITLE', 'TRAINING_TYPE', 'REQUESTED_DATE', 'REQUESTED_DATE_BS',
            'START_DATE', 'START_DATE_BS',
            'END_DATE', 'END_DATE_BS',
            'DURATION', 'STATUS_DETAIL',
            'REMARKS'
        ]);
        var exportMap = {
            'COMPANY_NAME': 'Service Provider',
            'INSTRUCTOR': 'Trainer',
            'TITLE': 'Training Name',
            'FULL_NAME': 'Employee Name',
            'ATTD_STATUS': 'Remarks',
            'GENDER': 'Gender',
            'DESIGNATION': 'Designation',
            'DEPARTMENT': 'Department',
            'TRAINING_TYPE': 'Training Type',
            'TRAINING_TYPE': 'Training Type',
            'lDProgram': 'Type of L&D Program',
            'START_DATE': 'Start Date(AD)',
            'START_DATE_BS': 'Start Date(BS)',
            'END_DATE': 'End Date(AD)',
            'END_DATE_BS': 'End Date(BS)',
            'DURATION': 'No of Days',
            'TRAINING_HOUR': 'Hours Per Day',
        };
        var extraData = {};
        $('#trainingId').on('change', function () {
            var data = {};
            data['trainingId'] = $trainingId.val()
            app.pullDataById(document.pullHourLink, data).then(function (response) {
                hoursPerDay = response.data;
                if (response.success) {
                    extraData = [[{ value: 'Q2 Man-hour:' }, { value: '' }],
                    [{ value: 'Number of hours worked a days :' }, { value: hoursPerDay + ' hrs' }],
                    [{ value: 'Total number of participants :' }, { value: '' }],
                    [{ value: 'Specific period of time:' }, { value: '' }],
                    [{ value: 'Assuming weekends and public holiday in Q3:' }, { value: '' }],
                    [{ value: 'Total working days:' }, { value: '' }],
                    [{ value: 'Man hour:' }, { value: '' }],
                    [{ value: 'Mandays:' }, { value: '' }],
                    [{ value: 'External training participants:' }, { value: '' }],
                    [{ value: 'Assuming 8 hrs training:' }, { value: '' }],
                    [{ value: 'Internal training participants:' }, { value: '' }],
                    [{ value: 'Assuming 4hrs training:' }, { value: '' }],
                    [{ value: 'Total hours :' }, { value: '' }],
                    [{ value: 'Total Mandays:' }, { value: '' }]];
                } else {
                    app.showMessage(response.error, 'error');
                }
            }, function (error) {
                app.showMessage(error, 'error');
            });
        });
        exportMap = app.prependPrefExportMap(exportMap);
        $('#excelExport').on('click', function () {
            app.excelExportCustomised($table, exportMap, 'Training Report List.xlsx', extraData);
        });

        $('#pdfExport').on('click', function () {
            app.exportToPDF($table, exportMap, 'Training Report List.pdf');
        });



    });
})(window.jQuery, window.app);
