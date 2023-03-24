(function ($, app) {
    'use strict';
    $(document).ready(function () {
        var $table = $('#table');
        var action = `
            <div class="clearfix">
                <a class="btn btn-icon-only green" href="${document.viewLink}/#:REQUEST_ID#/#:ROLE#" style="height:17px;" title="View Detail">
                    <i class="fa fa-search"></i>
                </a>
            </div>
        `;
        app.initializeKendoGrid($table, [
            {
                title: 'Select All',
                headerTemplate: "<input type='checkbox' id='header-chb' class='k-checkbox header-checkbox'><label class='k-checkbox-label' for='header-chb'></label>",
                template: "<input type='checkbox' id='#:REQUEST_ID#' role-id='#:ROLE#'  class='k-checkbox row-checkbox'><label class='k-checkbox-label' for='#:REQUEST_ID#'></label>",
                width: 40
            },
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


        app.pullDataById('', {}).then(function (response) {
            if (response.success) {
                app.renderKendoGrid($table, response.data);
                selectItems = {};
                var data = response.data;
                for (var i in data) {
                    selectItems[data[i]['ID']] = {'checked': false, 'role': data[i]['ROLE']};
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
            app.excelExport($table, exportMap, 'Event Request List');
        });

        $('#pdfExport').on('click', function () {
            app.exportToPDF($table, exportMap, 'Event Request List');
        });
        var selectItems = {};
        var $bulkBtnContainer = $('#acceptRejectDiv');
        var $bulkBtns = $(".btnApproveReject");
        $table.on('click', '.k-checkbox', function () {
            var checked = this.checked;
            var row = $(this).closest("tr");
            var grid = $table.data("kendoGrid");
            var dataItem = grid.dataItem(row);
            if (selectItems[dataItem['REQUEST_ID']] === undefined) {
                selectItems[dataItem['REQUEST_ID']] = {'checked': checked, 'role': dataItem['ROLE']};
            } else {
                selectItems[dataItem['REQUEST_ID']]['checked'] = checked;
            }
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
