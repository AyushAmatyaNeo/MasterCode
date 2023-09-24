(function ($, app) {
    'use strict';
    $(document).ready(function () {
        var $table = $('#table');
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

        var rowTemplateString = "<tr  class='#: (PRI_SEC=='SECONDARY')  ? 'bg-primary' : '' #' data-uid='#: uid #'>" +
          "<td><input type='checkbox' id='#:TRAVEL_ID#' role-id='#:ROLE#'  class='k-checkbox row-checkbox'><label class='k-checkbox-label' for='#:TRAVEL_ID#'></label></td>" +
          "<td>#: EMPLOYEE_CODE #</td>" +
          "<td>#: EMPLOYEE_NAME #</td>" +
          "<td>#: FROM_DATE_AD #</td>" +
          "<td>#: FROM_DATE_BS #</td>" +
          "<td>#: TO_DATE_AD #</td>" +
          "<td>#: TO_DATE_BS #</td>" +
          "<td>#: REQUESTED_DATE_AD #</td>" +
          "<td>#: REQUESTED_DATE_BS #</td>" +
          "<td>#: DESTINATION #</td>" +
          "<td>#: REQUESTED_AMOUNT #</td>" +
          "<td>#: TRANSPORT_TYPE_DETAIL #</td>" +
          "<td>#: STATUS_DETAIL #</td>" +
          "<td>"+action+"</td>" +
          "</tr>";

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
            //{field: "REQUESTED_TYPE_DETAIL", title: "Request For"},
            {field: "TRANSPORT_TYPE_DETAIL", title: "Transport"},
            {field: "STATUS_DETAIL", title: "Status"},
            {field: ["TRAVEL_ID", "REQUESTED_TYPE", "ROLE"], title: "Action", template: action}
        ], null, null, {rowTemplate : rowTemplateString}, 'Travel Request List');


        app.serverRequest('', {}).then(function (response) {
            if (response.success) {
                app.renderKendoGrid($table, response.data);
                selectItems = {};
                var data = response.data;
                for (var i in data) {
                    selectItems[data[i]['TRAVEL_ID']] = {'checked': false, 'role': data[i]['ROLE']};
                }
            } else {
                app.showMessage(response.error, 'error');
            }
        }, function (error) {
            app.showMessage(error, 'error');
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
        var selectItems = {};
        var $bulkBtnContainer = $('#acceptRejectDiv');
        var $bulkBtns = $(".btnApproveReject");
        $table.on('click', '.k-checkbox', function () {
            var checked = this.checked;
            var row = $(this).closest("tr");
            var grid = $table.data("kendoGrid");
            var dataItem = grid.dataItem(row);
            selectItems[dataItem['TRAVEL_ID']].checked = checked;
            if (checked) {
                row.addClass("k-state-selected");
                $bulkBtnContainer.show();
            } else {
                row.removeClass("k-state-selected");
                var atleastOne = false;
                for (var key in selectItems) {
                    if (selectItems[key]['checked']) {
                        atleastOne = true;
                        return;
                    }
                }
                if (atleastOne) {
                    $bulkBtnContainer.show();
                } else {
                    $bulkBtnContainer.hide();
                }

            }
        });
        $bulkBtns.bind("click", function () {
            var btnId = $(this).attr('id');
            var selectedValues = [];
            for (var i in selectItems) {
                if (selectItems[i].checked) {
                    selectedValues.push({id: i, role: selectItems[i]['role'], btnAction: btnId});
                }
            }
            app.bulkServerRequest(document.approveRejectUrl, selectedValues, function () {
                window.location.reload(true);
            }, function (data, error) {

            });
        });
    });
})(window.jQuery, window.app);
