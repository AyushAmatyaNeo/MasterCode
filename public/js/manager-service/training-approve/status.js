(function ($, app) {
    'use strict';
    $(document).ready(function () {
        $("select").select2();
        app.startEndDatePickerWithNepali('nepaliFromDate', 'fromDate', 'nepaliToDate', 'toDate', null, true);
        var $table = $("#trainingTable");
        var $search = $('#search');

        var columns = [
            { field: "EMPLOYEE_CODE", title: "Code" },
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
            { field: "TOTAL_HOUR", title: "Total Hour" },
            { field: "YOUR_ROLE", title: "Your Role" },
            { field: "STATUS", title: "Status" },
            {
                field: ["REQUEST_ID", "ROLE"], title: "Action", template: `
            <span> 
                <a class="btn  btn-icon-only btn-success" href="${document.viewLink}/#: REQUEST_ID #/#: ROLE #" style="height:17px;" title="view">
                    <i class="fa fa-search-plus"></i>
                </a>
            </span>`}
        ];
        var map = {
            'EMPLOYEE_CODE': 'Code',
            'FULL_NAME': 'Employee Name',
            'TITLE': 'Training Name',
            'TRAINING_TYPE': 'Training Type',
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
        app.initializeKendoGrid($table, columns, null, null, null, 'Training Request List');
        app.searchTable($table, ["FULL_NAME"]);

        $('#excelExport').on('click', function () {
            app.excelExport($table, map, "Training Request List.xlsx");
        });
        $('#pdfExport').on('click', function () {
            app.exportToPDF($table, map, "Training Request List.pdf");
        });

        $search.on('click', function () {
            var q = document.searchManager.getSearchValues();
            q['requestStatusId'] = $('#status').val();
            q['fromDate'] = $('#fromDate').val();
            q['toDate'] = $('#toDate').val();
            q['recomApproveId'] = $('#recomApproveId').val();
            app.serverRequest("", q).then(function (success) {
                app.renderKendoGrid($table, success.data);
            }, function (failure) {
            });
        });

        //        $("#reset").on("click", function () {
        //            $(".form-control").val("");
        //        });

    });
})(window.jQuery, window.app);
