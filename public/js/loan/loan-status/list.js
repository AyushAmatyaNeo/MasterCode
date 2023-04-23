(function ($, app) {
    'use strict';
    $(document).ready(function () {
        $("select").select2();
        app.startEndDatePickerWithNepali('nepaliFromDate', 'fromDate', 'nepaliToDate', 'toDate', null, true);
        var $tableContainer = $("#table");
        var $search = $('#search');

        var columns = [
            {field: "EMPLOYEE_CODE", title: "Emp. Code", width: 150},
            {field: "FULL_NAME", title: "Employee", width: 150},
            {field: "LOAN_NAME", title: "Loan", width: 120},
            //{field: "TOTAL_AMOUNT", title: "Total Amount", width: 150},
            //{field: "PAID", title: "Paid", width: 150},
            {field: "BALANCE", title: "Unpaid Balance", width: 150},
			{field: "LOAN_ID", title: "Action", template: `
          <span> 
            <a class="btn btn-icon-only btn-success" href="${document.loanClosing}/#: LOAN_ID #/#: EMPLOYEE_ID #" style="height:17px;" title="Pay Cash">
                <i class="fa fa-money"></i>
            </a>
        </span>
        <span> 
            <a class="btn btn-icon-only btn-success" href="${document.changeInstallment}/#: LOAN_ID #/#: EMPLOYEE_ID #" style="height:17px;" title="Change Installment">
                <i class="fa fa-edit"></i>
            </a>
        </span>`
            , width: 120}
        ];

        var map = {
            'FULL_NAME': 'Name',
            'LOAN_NAME': 'Loan',
            'REQUESTED_DATE_AD': 'Request Date(AD)',
            'REQUESTED_DATE_BS': 'Request Date(BS)',
            'LOAN_DATE_AD': 'Loan Date(AD)',
            'LOAN_DATE_BS': 'Loan Date(BS)',
            'REQUESTED_AMOUNT': 'Requested Amt'
        };
		
        app.initializeKendoGrid($tableContainer, columns, null, null, null, 'Loan Status Report.xlsx');
        app.searchTable($tableContainer, ['FULL_NAME']);

        $search.on('click', function () {
            var q = document.searchManager.getSearchValues();
            q['loanId'] = $('#loanId').val();
            q['loanRequestStatusId'] = $('#loanRequestStatusId').val();
            q['fromDate'] = $('#fromDate').val();
            q['toDate'] = $('#toDate').val();
            q['loanStatus'] = $('#loanStatus').val();
            App.blockUI({target: "#hris-page-content"});
            window.app.pullDataById(document.pullLoanListLink, q).then(function (success) {
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
    });
})(window.jQuery, window.app);
