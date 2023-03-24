(function ($, app) {
    'use strict';
    $(document).ready(function () {
        $("select").select2();
        app.startEndDatePickerWithNepali('nepaliFromDate', 'fromDate', 'nepaliToDate', 'toDate', null, true);

        var $table = $('#table');
        var action = `
            <div class="clearfix">
                <a class="btn btn-icon-only green" href="${document.viewLink}/#:TRAVEL_ID#" style="height:17px;" title="View Detail">
                    <i class="fa fa-search"></i>
                </a>
                #if(ALLOW_EDIT=='Y'){#
                <a class="btn btn-icon-only yellow" href="${document.editLink}/#:TRAVEL_ID#" style="height:17px;" title="Edit">
                    <i class="fa fa-edit"></i>
                </a>
                #}#
                #if(ALLOW_DELETE=='Y' && STATUS!='AP'){#
                <a  class="btn btn-icon-only red confirmation" href="${document.deleteLink}/#:TRAVEL_ID#" style="height:17px;" title="Cancel">
                    <i class="fa fa-times"></i>
                </a>
                #}#
                #if(ALLOW_EXPENSE_APPLY=='Y'){#
                <a  class="btn btn-icon-only blue" href="${document.expenseAddLink}/#:TRAVEL_ID#" style="height:17px;" title="Apply For Expense">
                    <i class="fa fa-arrow-right"></i>
                </a>
                #}#
            </div>
        `;
        app.initializeKendoGrid($table, [
            {title: "Start Date",
                columns: [{
                        field: "FROM_DATE_AD",
                        title: "English",
                    },
                    {
                        field: "FROM_DATE_BS",
                        title: "Nepali",
                    }]},
            {title: "To Date",
                columns: [{
                        field: "TO_DATE_AD",
                        title: "English",
                    },
                    {field: "TO_DATE_BS",
                        title: "Nepali",
                    }]},
            {title: "Applied Date",
                columns: [{
                        field: "REQUESTED_DATE_AD",
                        title: "English",
                    },
                    {field: "REQUESTED_DATE_BS",
                        title: "Nepali",
                    }]},
            {field: "DEPARTURE", title: "Departure"},
            {field: "DESTINATION", title: "Destination"},
            {field: "REQUESTED_AMOUNT", title: "Request Amt."},
            {field: "REQUESTED_TYPE", title: "Request For"},
            {field: "TRANSPORT_TYPE_DETAIL", title: "Transport"},
            {field: "STATUS_DETAIL", title: "Status"},
            {field: "TRAVEL_ID", title: "Action", template: action}
        ], null, null, null, 'Travel Request List');


        $('#search').on('click', function () {
            var employeeId = $('#employeeId').val();
            var statusId = $('#statusId').val();
            var fromDate = $('#fromDate').val();
            var toDate = $('#toDate').val();

            app.pullDataById('', {
                'employeeId': employeeId,
                'statusId': statusId,
                'fromDate': fromDate,
                'toDate': toDate
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
            'FROM_DATE_AD': 'From Date(AD)',
            'FROM_DATE_BS': 'From Date(BS)',
            'TO_DATE_AD': 'To Date(AD)',
            'TO_DATE_BS': 'To Date(BS)',
            'REQUESTED_DATE_AD': 'Request Date(AD)',
            'REQUESTED_DATE_BS': 'Request Date(BS)',
            'DESTINATION': 'Destination',
            'DEPARTURE': 'Departure',
            'REQUESTED_AMOUNT': 'Request Amt',
            'REQUESTED_TYPE_DETAIL': 'Request Type',
            'TRANSPORT_TYPE_DETAIL': 'Transport',
            'STATUS_DETAIL': 'Status',
            'PURPOSE': 'Purpose',
            'REMARKS': 'Remarks',
            'RECOMMENDER_NAME': 'Recommender',
            'APPROVER_NAME': 'Approver',
            'RECOMMENDED_BY_NAME': 'Recommended By',
            'APPROVED_BY_NAME': 'Approved By',
            'RECOMMENDED_REMARKS': 'Recommended Remarks',
            'RECOMMENDED_DATE': 'Recommended Date',
            'APPROVED_REMARKS': 'Approved Remarks',
            'APPROVED_DATE': 'Approved Date'
        };
        $('#excelExport').on('click', function () {
            app.excelExport($table, exportMap, 'Travel Request List.xlsx');
        });

        $('#pdfExport').on('click', function () {
            app.exportToPDF($table, exportMap, 'Travel Request List.pdf');
        });

    });
})(window.jQuery, window.app);
