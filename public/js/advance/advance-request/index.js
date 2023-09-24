(function ($) {
    'use strict';
    $(document).ready(function () {
        var $table = $('#table');
        var action = `
            <div class="clearfix">
                <a class="btn btn-icon-only green" href="${document.viewLink}/#:ADVANCE_REQUEST_ID#" style="height:17px;" title="View Detail">
                    <i class="fa fa-search"></i>
                </a>
                 #if(STATUS=='AP'){#
                <a class="btn btn-icon-only green" href="${document.paymentViewLink}/#:ADVANCE_REQUEST_ID#" style="height:17px;" title="Paymnet Detail">
                    <i class="fa fa-money"></i>
                </a>
                #}#
               
                #if(ALLOW_DELETE=='Y'){#
                <a  class="btn btn-icon-only red confirmation" href="${document.deleteLink}/#:ADVANCE_REQUEST_ID#" style="height:17px;" title="Cancel">
                    <i class="fa fa-times"></i>
                </a>
                #}#
            </div>
        `;
        // #if(ALLOW_EDIT=='Y'){#
        //     <a class="btn btn-icon-only yellow" href="${document.editLink}/#:ADVANCE_REQUEST_ID#" style="height:17px;" title="Edit">
        //         <i class="fa fa-edit"></i>
        //     </a>
        //     #}#
        var columns = [
            { field: "ADVANCE_ENAME", title: "Advance", width: 100 },
            {
                title: "Request Date",
                columns: [{
                    field: "REQUESTED_DATE_AD", width: 80,
                    title: "AD",
                    template: "<span>#: (REQUESTED_DATE_AD == null) ? '-' : REQUESTED_DATE_AD #</span>"
                },
                {
                    field: "REQUESTED_DATE_BS", width: 80,
                    title: "BS",
                    template: "<span>#: (REQUESTED_DATE_BS == null) ? '-' : REQUESTED_DATE_BS #</span>"
                }]
            },
            {
                title: "Date Of Advance",
                columns: [{
                    field: "DATE_OF_ADVANCE_AD", width: 80,
                    title: "AD",
                    template: "<span>#: (DATE_OF_ADVANCE_AD == null) ? '-' : DATE_OF_ADVANCE_AD #</span>"
                },
                {
                    field: "DATE_OF_ADVANCE_BS", width: 80,
                    title: "BS",
                    template: "<span>#: (DATE_OF_ADVANCE_BS == null) ? '-' : DATE_OF_ADVANCE_BS #</span>"
                }]
            },
            { field: "REQUESTED_AMOUNT", title: "Advance Amt", width: 80 },
            { field: "DEDUCTION_RATE", title: "Monthly Deduction", width: 80 },
            { field: "DEDUCTION_IN", title: "Repayment Months", width: 80 },
            { field: "STATUS_DETAIL", title: "Status", width: 60 },
            { field: ["ADVANCE_REQUEST_ID", "ALLOW_EDIT", "ALLOW_DELETE"], title: "Action", width: 120, template: action }
        ];
        var map = {
            'ADVANCE_ENAME': 'Name',
            'REQUESTED_DATE_AD': 'Requested Date(AD)',
            'REQUESTED_DATE_BS': 'Requested Date(BS)',
            'DATE_OF_ADVANCE_AD': 'Date of Advance (AD)',
            'DATE_OF_ADVANCE_BS': 'Date of Advance (BS)',
            'REQUESTED_AMOUNT': 'Advance Amt',
            'DEDUCTION_RATE': 'Monthly Deduction',
            'DEDUCTION_IN': 'Repayment Months',
            'STATUS_DETAIL': 'Status'
        }
        app.initializeKendoGrid($table, columns, "Advance Request List.xlsx");

        app.searchTable($table, ['ADVANCE_ENAME', 'REQUESTED_DATE_AD', 'DATE_OF_ADVANCE_AD', 'DATE_OF_ADVANCE_BS', 'REQUESTED_AMOUNT', 'DEDUCTION_RATE', 'DEDUCTION_IN', 'STATUS_DETAIL']);

        $('#excelExport').on('click', function () {
            app.excelExport($table, map, 'Advance Request List.xlsx');
        });

        $('#pdfExport').on('click', function () {
            app.exportToPDF($table, map, 'Advance Request List.pdf');
        });

        app.pullDataById("", { 'employeeId': document.employeeId }).then(function (response) {
            console.log(response.data);
            app.renderKendoGrid($table, response.data);
        }, function (error) {

        });
    });
})(window.jQuery);

