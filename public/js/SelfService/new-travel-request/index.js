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
                #if(ALLOW_DELETE=='Y'){#
                <a  class="btn btn-icon-only red confirmation" href="${document.deleteLink}/#:TRAVEL_ID#" style="height:17px;" title="Cancel">
                    <i class="fa fa-times"></i>
                </a>
                #}#
              
            </div>
        `;
        // #if(ALLOW_EXPENSE_APPLY=='Y'){#
        //     <a  class="btn btn-icon-only blue" href="${document.expenseAddLink}/#:TRAVEL_ID#" style="height:17px;" title="Apply For Expense">
        //         <i class="fa fa-arrow-right"></i>
        //     </a>
        //     #}#
        //     #if(ALLOW_ADVANCE_ITR=='Y'){#
        //         <a  class="btn btn-icon-only blue" href="${document.advanceInterAddLink}/#:TRAVEL_ID#" style="height:17px;" title="Apply For Expense">
        //             <i class="fa fa-arrow-right"></i>
        //         </a>
        //         #}#
        app.initializeKendoGrid($table, [
            {
                title: "Start Date",
                columns: [{
                    field: "FROM_DATE_AD",
                    title: "English",
                },
                {
                    field: "FROM_DATE_BS",
                    title: "Nepali",
                }]
            },
            {
                title: "To Date",
                columns: [{
                    field: "TO_DATE_AD",
                    title: "English",
                },
                {
                    field: "TO_DATE_BS",
                    title: "Nepali",
                }]
            },
            {
                title: "Applied Date",
                columns: [{
                    field: "REQUESTED_DATE_AD",
                    title: "English",
                },
                {
                    field: "REQUESTED_DATE_BS",
                    title: "Nepali",
                }]
            },
            { field: "DEPARTURE", title: "Departure" },
            { field: "DESTINATION", title: "Destination" },
            { field: "REQUESTED_AMOUNT", title: "Request Amt." },
            { field: "CURRENCY", title: "Currency" },
            { field: "REQUESTED_TYPE", title: "Request For" },
            { field: "TRANSPORT_TYPE_DETAIL", title: "Transport" },
            { field: "TRAVEL_TYPE", title: "Travel Type" },
            { field: "STATUS_DETAIL", title: "Status" },
            { field: "TRAVEL_ID", title: "Action", template: action }
        ]);


        $('#search').on('click', function () {
            var employeeId = $('#employeeId').val();
            var statusId = $('#statusId').val();
            var fromDate = $('#fromDate').val();
            var toDate = $('#toDate').val();
            var year = $('#appliedYear').val();
            var travelType = $('#travelId').val();

            app.pullDataById('', {
                'employeeId': employeeId,
                'statusId': statusId,
                'fromDate': fromDate,
                'toDate': toDate,
                'year': year,
                'travelType': travelType
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

        // $('#search').trigger('click');

        app.searchTable($table, ['EMPLOYEE_NAME', 'EMPLOYEE_CODE', 'FROM_DATE_AD', 'FROM_DATE_BS', 'TO_DATE_AD', 'TO_DATE_BS', 'TRAVEL_TYPE',]);
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
            'TRAVEL_TYPE': 'Travel Type',
            'PURPOSE': 'Purpose',
            'REMARKS': 'Remarks',
            'RECOMMENDER_NAME': 'Recommender',
            'APPROVER_NAME': 'Approver',
            'RECOMMENDED_BY_NAME': 'Recommended By',
            'APPROVED_BY_NAME': 'Approved By',
            'RECOMMENDED_REMARKS': 'Recommended Remarks',
            'RECOMMENDED_DATE': 'Recommended Date',
            'APPROVED_REMARKS': 'Approved Remarks',
            'APPROVED_DATE': 'Approved Date',
            'CURRENCY': 'Currency'
        };
        $('#excelExport').on('click', function () {
            app.excelExport($table, exportMap, 'Travel Request List.xlsx');
        });

        $('#pdfExport').on('click', function () {
            app.exportToPDF($table, exportMap, 'Travel Request List.pdf');
        });

    });
})(window.jQuery, window.app);
