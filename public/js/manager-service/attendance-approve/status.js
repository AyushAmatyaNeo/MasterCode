(function ($, app) {
    'use strict';
    $(document).ready(function () {
        $("select").select2();
        app.startEndDatePickerWithNepali('nepaliFromDate', 'fromDate', 'nepaliToDate', 'toDate', null, true);
        var $employeeId = $('#employee');
        app.populateSelect($employeeId, document.employees, 'id', 'name', null, null, false);

        var $tableContainer = $("#attendanceRequestStatusTable");
        var $search = $("#search");
        var $excelExport = $("#excelExport");
        var $pdfExport = $("#pdfExport");
        var columns = [
            { field: "EMPLOYEE_CODE", title: "Code" },
            { field: "FULL_NAME", title: "Employee", template: "<span>#: (FULL_NAME == null) ? '-' : FULL_NAME #</span>" },
            {
                title: "Requested Date",
                columns: [{
                    field: "REQUESTED_DT_AD",
                    title: "AD",
                    template: "<span>#: (REQUESTED_DT_AD == null) ? '-' : REQUESTED_DT_AD #</span>"
                },
                {
                    field: "REQUESTED_DT_BS",
                    title: "BS",
                    template: "<span>#: (REQUESTED_DT_BS == null) ? '-' : REQUESTED_DT_BS #</span>"
                }]
            },
            {
                title: "Attendance Date",
                columns: [{
                    field: "ATTENDANCE_DT_AD",
                    title: "AD",
                    template: "<span>#: (ATTENDANCE_DT_AD == null) ? '-' : ATTENDANCE_DT_AD #</span>"
                },
                {
                    field: "ATTENDANCE_DT_BS",
                    title: "BS",
                    template: "<span>#: (ATTENDANCE_DT_BS == null) ? '-' : ATTENDANCE_DT_BS #</span>"
                }]
            },
            { field: "IN_TIME", title: "Check In", template: "<span>#: (IN_TIME == null) ? '-' : IN_TIME #</span>" },
            { field: "OUT_TIME", title: "Check Out", template: "<span>#: (OUT_TIME == null) ? '-' : OUT_TIME #</span>" },
            { field: "YOUR_ROLE", title: "Your Role", template: "<span>#: (YOUR_ROLE == null) ? '-' : YOUR_ROLE #</span>" },
            { field: "STATUS", title: "Status", template: "<span>#: (STATUS == null) ? '-' : STATUS #</span>" },
            {
                field: ["ID", "ROLE"], title: "Action", template: `
                <span>
                    <a class="btn  btn-icon-only btn-success" href="${document.viewLink}/#: ID #/#: ROLE #" style="height:17px;" title="view">
                        <i class="fa fa-search-plus"></i>
                    </a>
                </span>
`}
        ];
        var map = {
            'EMPLOYEE_CODE': 'Code',
            'FULL_NAME': 'Name',
            'REQUESTED_DT': 'Req.Date(AD)',
            'REQUESTED_DT_N': 'Req.Date(BS)',
            'ATTENDANCE_DT': 'AttenDate(AD)',
            'ATTENDANCE_DT_N': 'AttenDate(BS)',
            'IN_TIME': 'In Time',
            'OUT_TIME': 'Out Time',
            'TOTAL_HOUR': 'Total Hrs',
            'IN_REMARKS': 'In Remarks',
            'OUT_REMARKS': 'Out Remarks',
            'STATUS': 'Status',
            'APPROVED_DT': 'Approved Date',
            'APPROVED_REMARKS': 'Approved Remarks',

        };
        app.initializeKendoGrid($tableContainer, columns, null, null, null, 'AttendanceRequestList');
        app.searchTable('attendanceRequestStatusTable', ['FULL_NAME', 'EMPLOYEE_CODE', 'REQUESTED_DT_AD', 'ATTENDANCE_DT_AD', 'REQUESTED_DT_BS', 'ATTENDANCE_DT_BS', 'IN_TIME', 'OUT_TIME', 'YOUR_ROLE', 'STATUS']);


        $search.on("click", function () {
            var q = document.searchManager.getSearchValues();
            q['attendanceRequestStatusId'] = $('#attendanceRequestStatusId').val();
            q['fromDate'] = $('#fromDate').val();
            q['toDate'] = $('#toDate').val();
            q['approverId'] = $('#approverId').val();

            App.blockUI({ target: "#hris-page-content" });
            window.app.pullDataById(document.pullAttendanceRequestStatusListLink, q).then(function (success) {
                App.unblockUI("#hris-page-content");
                app.renderKendoGrid($tableContainer, success.data);
            }, function (failure) {
                App.unblockUI("#hris-page-content");
            });
        });

        $excelExport.on('click', function () {
            app.excelExport($tableContainer, map, "AttendanceRequestList.xlsx");
        });
        $pdfExport.on('click', function () {
            app.exportToPDF($tableContainer, map, "AttendanceRequestList.pdf");
        });

        //        $("#reset").on("click", function () {
        //            $(".form-control").val("");
        //            $("#fromDate").val("");
        //        });
    });
})(window.jQuery, window.app);

