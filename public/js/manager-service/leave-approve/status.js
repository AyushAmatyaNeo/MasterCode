(function ($, app) {
    'use strict';
    $(document).ready(function () {
        $("select").select2();
        app.startEndDatePickerWithNepali('nepaliFromDate', 'fromDate', 'nepaliToDate', 'toDate', null, true);
        var $tableContainer = $("#leaveRequestStatusTable");
        var $search = $('#search');
        var $employeeId = $('#employee');
        app.populateSelect($employeeId, document.employees, 'id', 'name', null, null, false);
        var columns = [
            { field: "EMPLOYEE_CODE", title: "Code" },
            { field: "FULL_NAME", title: "Employee" },
            { field: "LEAVE_ENAME", title: "Leave" },
            {
                title: "Requested Date",
                columns: [{
                    field: "APPLIED_DATE_AD",
                    title: "AD",
                    template: "<span>#: (APPLIED_DATE_AD == null) ? '-' : APPLIED_DATE_AD #</span>"
                },
                {
                    field: "APPLIED_DATE_BS",
                    title: "BS",
                    template: "<span>#: (APPLIED_DATE_BS == null) ? '-' : APPLIED_DATE_BS #</span>"
                }]
            },
            {
                title: "From Date",
                columns: [{
                    field: "START_DATE_AD",
                    title: "AD",
                    template: "<span>#: (START_DATE_AD == null) ? '-' : START_DATE_AD #</span>"
                },
                {
                    field: "START_DATE_BS",
                    title: "BS",
                    template: "<span>#: (START_DATE_BS == null) ? '-' : START_DATE_BS #</span>"
                }]
            },
            {
                title: "To Date",
                columns: [{
                    field: "END_DATE_AD",
                    title: "AD",
                    template: "<span>#: (END_DATE_AD == null) ? '-' : END_DATE_AD #</span>"
                },
                {
                    field: "END_DATE_BS",
                    title: "BS",
                    template: "<span>#: (END_DATE_BS == null) ? '-' : END_DATE_BS #</span>"
                }]
            },
            { field: "NO_OF_DAYS", title: "Duration" },
            { field: "YOUR_ROLE", title: "Your Role" },
            { field: "STATUS", title: "Status" },
            {
                field: ["ID", "ROLE"], title: "Action", template: `
            <span>                                  
                <a class="btn  btn-icon-only btn-success" href="${document.viewLink}/#: ID #/#: ROLE #" style="height:17px;" title="view">
                <i class="fa fa-search-plus"></i>
                </a>
            </span>`}
        ];
        app.initializeKendoGrid($tableContainer, columns, null, null, null, 'Leave Request List');
        app.searchTable($tableContainer, ["FULL_NAME"]);

        var map = {
            'EMPLOYEE_CODE': 'Code',
            'FULL_NAME': 'Name',
            'LEAVE_ENAME': 'Leave',
            'APPLIED_DATE_AD': 'Applied Date(AD)',
            'APPLIED_DATE_BS': 'Applied Date(BS)',
            'START_DATE_AD': 'Start Date(AD)',
            'START_DATE_BS': 'Start Date(BS)',
            'END_DATE_AD': 'End Date(AD)',
            'END_DATE_BS': 'End Date(BS)',
            'YOUR_ROLE': 'Role',
            'NO_OF_DAYS': 'No Of Days',
            'STATUS': 'Status',
            'REMARKS': 'Remarks',
            'RECOMMENDED_REMARKS': 'Recommended Remarks',
            'RECOMMENDED_DATE': 'Recommended Date',
            'APPROVED_REMARKS': 'Approved Remarks',
            'APPROVED_DATE': 'Approved Date'

        };

        $search.on('click', function () {
            var q = document.searchManager.getSearchValues();
            q['leaveId'] = $('#leaveId').val();
            q['leaveRequestStatusId'] = $('#leaveRequestStatusId').val();
            q['fromDate'] = $('#fromDate').val();
            q['toDate'] = $('#toDate').val();
            q['recomApproveId'] = $('#recomApproveId').val();
            App.blockUI({ target: "#hris-page-content" });
            app.pullDataById(document.pullLeaveRequestStatusListLink, q).then(function (success) {
                App.unblockUI("#hris-page-content");
                app.renderKendoGrid($tableContainer, success.data);
            }, function (failure) {
                App.unblockUI("#hris-page-content");
            });
        });

        $('#excelExport').on('click', function () {
            app.excelExport($tableContainer, map, "Leave Request List.xlsx");
        });
        $('#pdfExport').on('click', function () {
            app.exportToPDF($tableContainer, map, "Leave Request List.pdf");
        });

    });
})(window.jQuery, window.app);
