(function ($, app) {
    'use strict';
    $(document).ready(function () {
        app.startEndDatePickerWithNepali('nepaliFromDate', 'fromDate', 'nepaliToDate', 'toDate', null, true);
        var $table = $('#table');
        var $search = $('#search');
        var $status = $('#status');
        var $fromDate = $('#fromDate');
        var $toDate = $('#toDate');
        var action = `
            <div class="clearfix">
                <a class="btn btn-icon-only green" href="${document.viewLink}/#:REQUEST_ID#/#:ROLE#" style="height:17px;" title="View Detail">
                    <i class="fa fa-search"></i>
                </a>
            </div>
        `;
        app.initializeKendoGrid($table, [
            {field: "EMPLOYEE_CODE", title: "Code"},
            {field: "FULL_NAME", title: "Employee"},
            {field: "TITLE", title: "Event"},
            {field: "EVENT_TYPE", title: "Type"},

            {title: "Start Date",
                columns: [{
                        field: "START_DATE",
                        title: "AD",
                    },
                    {
                        field: "START_DATE_BS",
                        title: "BS",
                    }]},
            {title: "End Date",
                columns: [{
                        field: "END_DATE",
                        title: "AD",
                    },
                    {
                        field: "END_DATE_BS",
                        title: "BS",
                    }]},
            {field: "DURATION", title: "Duration"},
            {title: "Requested Date",
                columns: [
                    {
                        field: "REQUESTED_DATE",
                        title: "AD",
                    },
                    {
                        field: "REQUESTED_DATE_BS",
                        title: "BS",
                    }]},
            {field: "STATUS_DETAIL", title: "Status"},
            {field: "YOUR_ROLE", title: "Role"},
            {field: ["REQUEST_ID", "ROLE"], title: "Action", template: action}
        ], null, null, null, 'Event Request List');

        $search.on('click', function () {
            app.pullDataById('', {
                'status': $status.val(),
                'fromDate': $fromDate.val(),
                'toDate': $toDate.val()
            }).then(function (response) {
                if (response.success) {
                    app.renderKendoGrid($table, response.data);
                } else {
                    app.showMessage(response.error, 'error');
                }
            }, function (error) {
                app.showMessage(error, 'error');
            });
        });
        app.searchTable($table, ['EMPLOYEE_NAME', 'EMPLOYEE_CODE']);
        var exportMap = {
            'EMPLOYEE_CODE': 'Code',
            'FULL_NAME': 'Employee Name',
            'TITLE': 'Event Name',
            'EVENT_TYPE': 'Event Type',
            'REQUESTED_DATE': 'Requested Date(AD)',
            'REQUESTED_DATE_BS': 'Requested Date(BS)',
            'START_DATE': 'Start Date(AD)',
            'START_DATE_BS': 'Start Date(BS)',
            'END_DATE': 'End Date(AD)',
            'END_DATE_BS': 'End Date(BS)',
            'DURATION': 'Duration',
            'STATUS_DETAIL': 'Status',
            'REMARKS': 'Remarks',
            'RECOMMENDER_NAME': 'Recommender',
            'RECOMMENDED_DT': 'Recommended Date',
            'RECOMMENDED_REMARKS': 'Recommender Remarks',
            'APPROVER_NAME': 'Approver',
            'APPROVED_DT': 'Aprroved Date',
            'APPROVED_REMARKS': 'Approver Remarks'
        };
        $('#excelExport').on('click', function () {
            app.excelExport($table, exportMap, 'Event Request List.xlsx');
        });
        $('#pdfExport').on('click', function () {
            app.exportToPDF($table, exportMap, 'Event Request List.pdf');
        });
        
//        $("#reset").on("click", function () {
//            $(".form-control").val("");
//        });
    });
})(window.jQuery, window.app);
