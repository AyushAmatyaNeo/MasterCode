(function ($, app) {
    'use strict';
    $(document).ready(function () {
        $("select").select2();
        app.startEndDatePickerWithNepali('nepaliFromDate', 'fromDate', 'nepaliToDate', 'toDate', null, true);
        var $tableContainer = $("#loanRequestStatusTable");
        var $search = $('#search');
        var $employeeId = $('#employee');
        app.populateSelect($employeeId, document.employees, 'id', 'name', null, null, false);
        var columns = [
            { field: "FULL_NAME", title: "Employee", width: 200 },
            { field: "LOAN_NAME", title: "Loan", width: 120 },
            {
                title: "Requested Date",
                columns: [{
                    field: "REQUESTED_DATE_AD",
                    title: "AD"
                },
                {
                    field: "REQUESTED_DATE_BS",
                    title: "BS",
                }
                ]
            },
            {
                title: "Loan Date",
                columns: [{
                    field: "LOAN_DATE_AD",
                    title: "AD",
                },
                {
                    field: "LOAN_DATE_BS",
                    title: "BS",
                }]
            },
            { field: "REQUESTED_AMOUNT", title: "Requested Amount", width: 150 },
            { field: "YOUR_ROLE", title: "Your Role", width: 150 },
            { field: "STATUS", title: "Status", width: 90 },
            {
                field: ["LOAN_REQUEST_ID", "ROLE"], title: "Action", template: `
            <span> 
                <a class="btn  btn-icon-only btn-success" href="${document.viewLink}/#: LOAN_REQUEST_ID #/#: ROLE #" style="height:17px;" title="view">
                    <i class="fa fa-search-plus"></i>
                </a>
            </span>`}
        ];

        var map = {
            'FULL_NAME': 'Name',
            'LOAN_NAME': 'Loan',
            'REQUESTED_DATE_AD': 'Request Date(AD)',
            'REQUESTED_DATE_BS': 'Request Date(BS)',
            'LOAN_DATE_AD': 'Loan Date(AD)',
            'LOAN_DATE_BS': 'Loan Date(BS)',
            'REQUESTED_AMOUNT': 'Reqest Amt',
            'YOUR_ROLE': 'Role',
            'STATUS': 'Status',
            'REASON': 'Reason',
            'RECOMMENDED_REMARKS': 'Recommended Remarks',
            'RECOMMENDED_DATE': 'Recommended Date',
            'APPROVED_REMARKS': 'Approved Remarks',
            'APPROVED_DATE': 'Approved Date'

        }
        app.initializeKendoGrid($tableContainer, columns);
        app.searchTable($tableContainer, ['FULL_NAME']);

        $search.on('click', function () {
            var q = document.searchManager.getSearchValues();
            q['loanId'] = $('#loanId').val();
            q['loanRequestStatusId'] = $('#loanRequestStatusId').val();
            q['fromDate'] = $('#fromDate').val();
            q['toDate'] = $('#toDate').val();
            q['recomApproveId'] = $('#recomApproveId').val();
            App.blockUI({ target: "#hris-page-content" });
            window.app.pullDataById(document.pullLoanRequestStatusListLink, q).then(function (success) {
                App.unblockUI("#hris-page-content");
                app.renderKendoGrid($tableContainer, success.data);
            }, function (failure) {
                App.unblockUI("#hris-page-content");
            });
        });
        $('#excelExport').on('click', function () {
            app.excelExport($tableContainer, map, "Loan Request List.xlsx");
        });
        $('#pdfExport').on('click', function () {
            app.exportToPDF($tableContainer, map, "Loan Request List.pdf");
        });

        //        $("#reset").on("click", function () {
        //            $(".form-control").val("");
        //            $("#fromDate").val("");
        //        });



    });
})(window.jQuery, window.app);
