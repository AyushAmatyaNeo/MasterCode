(function ($, app) {
    'use strict';
    $(document).ready(function () {
        $("select").select2();
        app.startEndDatePickerWithNepali('nepaliFromDate', 'fromDate', 'nepaliToDate', 'toDate', null, true);
        var $tableContainer = $("#holidayWorkRequestStatusTable");
        var $search = $('#search');
        var columns = [
            { field: "EMPLOYEE_CODE", title: "Code" },
            { field: "FULL_NAME", title: "Employee" },
            { field: "HOLIDAY_ENAME", title: "Holiday" },
            {
                title: "Requested Date",
                columns: [{
                    field: "REQUESTED_DATE_AD",
                    title: "AD",
                    template: "<span>#: (REQUESTED_DATE_AD == null) ? '-' : REQUESTED_DATE_AD #</span>"
                },
                {
                    field: "REQUESTED_DATE_BS",
                    title: "BS",
                    template: "<span>#: (REQUESTED_DATE_BS == null) ? '-' : REQUESTED_DATE_BS #</span>"
                }]
            },
            {
                title: "From Date",
                columns: [{
                    field: "FROM_DATE_AD",
                    title: "AD",
                    template: "<span>#: (FROM_DATE_AD == null) ? '-' : FROM_DATE_AD #</span>"
                },
                {
                    field: "FROM_DATE_BS",
                    title: "BS",
                    template: "<span>#: (FROM_DATE_BS == null) ? '-' : FROM_DATE_BS #</span>"
                }]
            },
            {
                title: "To Date",
                columns: [{
                    field: "TO_DATE_AD",
                    title: "AD",
                    template: "<span>#: (TO_DATE_AD == null) ? '-' : TO_DATE_AD #</span>"
                },
                {
                    field: "TO_DATE_BS",
                    title: "BS",
                    template: "<span>#: (TO_DATE_BS == null) ? '-' : TO_DATE_BS #</span>"
                }]
            },
            { field: "DURATION", title: "Duration" },
            { field: "YOUR_ROLE", title: "Your Role" },
            { field: "STATUS", title: "Status" },
            {
                field: ["ID", "ROLE"], title: "Action", template: `<span> <a class="btn  btn-icon-only btn-success"
        href="${document.viewLink}/#: ID #/#: ROLE #" style="height:17px;" title="view">
        <i class="fa fa-search-plus"></i></a>
        </span>`}
        ];

        var map = {
            'EMPLOYEE_CODE': 'Code',
            'FULL_NAME': 'Name',
            'HOLIDAY_ENAME': 'Holiday',
            'REQUESTED_DATE_AD': 'Request Date(AD)',
            'REQUESTED_DATE_BS': 'Request Date(BS)',
            'FROM_DATE_AD': 'From Date(AD)',
            'FROM_DATE_BS': 'From Date(BS)',
            'TO_DATE_AD': 'To Date(AD)',
            'TO_DATE_BS': 'To Date(BS)',
            'DURATION': 'Duration',
            'YOUR_ROLE': 'Role',
            'STATUS': 'Status',
            'REMARKS': 'Remarks',
            'RECOMMENDED_REMARKS': 'Recommended Remarks',
            'RECOMMENDED_DATE': 'Recommended Date',
            'APPROVED_REMARKS': 'Approved Remarks',
            'APPROVED_DATE': 'Approved Date'

        };
        app.initializeKendoGrid($tableContainer, columns, null, null, null, 'Work on Holiday Request List');
        app.searchTable('holidayWorkRequestStatusTable', ['FULL_NAME', 'EMPLOYEE_CODE', 'HOLIDAY_ENAME', 'REQUESTED_DATE_AD', 'FROM_DATE_AD', 'TO_DATE_AD', 'REQUESTED_DATE_BS', 'FROM_DATE_BS', 'TO_DATE_BS', 'DURATION', 'YOUR_ROLE', 'STATUS']);

        $search.on('click', function () {
            var q = document.searchManager.getSearchValues();
            q['holidayId'] = $('#holidayId').val();
            q['requestStatusId'] = $('#requestStatusId').val();
            q['fromDate'] = $('#fromDate').val();
            q['toDate'] = $('#toDate').val();
            q['recomApproveId'] = $('#recomApproveId').val();
            App.blockUI({ target: "#hris-page-content" });
            app.pullDataById(document.pullHoliayWorkRequestStatusListLink, q).then(function (success) {
                App.unblockUI("#hris-page-content");
                app.renderKendoGrid($tableContainer, success.data);
            }, function (failure) {
                App.unblockUI("#hris-page-content");
            });
        });

        $('#excelExport').on("click", function () {
            app.excelExport($tableContainer, map, "Work on Holiday Request List.xlsx");
        });
        $('#pdfExport').on("click", function () {
            app.exportToPDF($tableContainer, map, "Work on Holiday Request List.pdf");
        });

        //        $("#reset").on("click", function () {
        //            $(".form-control").val("");
        //            $("#fromDate").val("");
        //        });

    });
})(window.jQuery, window.app);
