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
                #if(REQUESTED_TYPE=='ad'){#
                <a class="btn btn-icon-only green" href="${document.viewLink}/#:TRAVEL_ID#/#:ROLE#" style="height:17px;" title="View Detail">
                    <i class="fa fa-search"></i>
                </a>
                #}else{#
                <a class="btn btn-icon-only green" href="${document.expenseDetailLink}/#:TRAVEL_ID#/#:ROLE#" style="height:17px;" title="View Detail">
                    <i class="fa fa-search"></i>
                </a>
                #}#
            </div>
        `;
        app.initializeKendoGrid($table, [
            {
                title: 'Select All',
                headerTemplate: "<input type='checkbox' id='header-chb' class='k-checkbox header-checkbox'><label class='k-checkbox-label' for='header-chb'></label>",
                template: "<input type='checkbox' id='#:TRAVEL_ID#' role-id='#:ROLE#'  class='k-checkbox row-checkbox'><label class='k-checkbox-label' for='#:TRAVEL_ID#'></label>",
                width: 40
            },
            {field: "EMPLOYEE_CODE", title: "Code"},
            {field: "EMPLOYEE_NAME", title: "Employee"},
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
            {field: "DESTINATION", title: "Destination"},
            {field: "REQUESTED_AMOUNT", title: "Request Amt."},
            {field: "REQUESTED_TYPE_DETAIL", title: "Request For"},
            {field: "TRANSPORT_TYPE_DETAIL", title: "Transport"},
            {field: "STATUS_DETAIL", title: "Status"},
            {field: ["TRAVEL_ID", "REQUESTED_TYPE", "ROLE"], title: "Action", template: action}
        ], null, null, null, 'Travel Request List');
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
            'EMPLOYEE_NAME': 'Employee Name',
            'REQUESTED_DATE_AD': 'Request Date(AD)',
            'REQUESTED_DATE_BS': 'Request Date(BS)',
            'FROM_DATE_AD': 'From Date(AD)',
            'FROM_DATE_BS': 'From Date(BS)',
            'TO_DATE_AD': 'To Date(AD)',
            'TO_DATE_BS': 'To Date(BS)',
            'DESTINATION': 'Destination',
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
        
//        $("#reset").on("click", function () {
//            $(".form-control").val("");
//        });
    });
})(window.jQuery, window.app);
