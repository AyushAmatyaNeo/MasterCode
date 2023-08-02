(function ($, app) {
    'use strict';
    $(document).ready(function () {
        var $fromDate = $('#fromDate');
        var $toDate = $('#toDate'); 
        var $presentStatusId = $("#presentStatusId");
        var $status = $('#statusId');
        var $table = $('#table');
        var $search = $('#search');

        $('select').select2();
     
        // $.each(document.searchManager.getIds(), function (key, value) {
        //     $('#' + value).select2();
        // });
        app.startEndDatePickerWithNepali('nepaliFromDate', 'fromDate', 'nepaliToDate', 'toDate', null, false);
        app.getServerDate().then(function (response) {
            $fromDate.val(response.data.serverDate);
            $('#nepaliFromDate').val(nepaliDatePickerExt.fromEnglishToNepali(response.data.serverDate));
        });

        app.initializeKendoGrid($table, [
            //{field: "COMPANY_NAME", title: "Company"},
            //{field: "DEPARTMENT_NAME", title: "Department"},
            {field: "EMPLOYEE_CODE", title: "Code"},
            {field: "FULL_NAME", title: "Employee"},
            {title: "Attendance Date",
                columns: [
                    {
                        field: "ATTENDANCE_DT",
                        title: "AD",
                    },
                    {
                        field: "ATTENDANCE_DT_N",
                        title: "BS",
                    }
                ]},
            {field: "DAY", title: "Day"},
            {field: "ATTENDANCE_TIME", title: "Time"},
            {field: "PURPOSE", title: "In/Out"}
        ]);

        $search.on('click', function () {
            var q = document.searchManager.getSearchValues();
            q['fromDate'] = $fromDate.val();
            q['toDate'] = $toDate.val();
            if(q['fromDate'] == null || q['fromDate'] == ''){
                app.showMessage('From date is required.', 'warning');
                return;
            }
            app.serverRequest(document.pullAttendanceWS, q).then(function (response) {
                if (response.success) {
                    app.renderKendoGrid($table, response.data);
                    var data = response.data;
                } else {
                    app.showMessage(response.error, 'error');
                }
            }, function (error) {
                app.showMessage(error, 'error');
            });
        });

        app.searchTable($table, ['FULL_NAME', 'EMPLOYEE_CODE']);
        var exportMap = {
            'EMPLOYEE_CODE': 'Code',
            'FULL_NAME': ' Name',
            'ATTENDANCE_DT': 'Attendance Date(AD)',
            'ATTENDANCE_DT_N': 'Attendance Date(BS)',
            'DAY' : 'Day',
            'TIME': 'Time',
            'PURPOSE': 'In/Out',
        };
        $('#excelExport').on('click', function () {
            app.excelExport($table, exportMap, "AttendanceList.xlsx");
        });
        $('#pdfExport').on('click', function () {
            app.exportToPDF($table, exportMap, "AttendanceList.pdf");
        });

    });
})(window.jQuery, window.app);
