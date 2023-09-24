(function ($, app) {
    'use strict';
    $(document).ready(function () {
        var $table = $('#table');
        var action = `
            <div class="clearfix">
                <a class="btn btn-icon-only green" href="${document.viewLink}/#:ID#/#:ROLE#" style="height:17px;" title="View Detail">
                    <i class="fa fa-search"></i>
                </a>
            </div>
        `;

        var rowTemplateString =
            //   "<tr  class='#: (PRI_SEC=='SECONDARY')  ? 'bg-primary' : '' #' data-uid='#: uid #'>" +
            "<td><input type='checkbox' id='#:ID#' role-id='#:ROLE#'  class='k-checkbox row-checkbox'><label class='k-checkbox-label' for='#:ID#'></label></td>" +
            "<td>#: EMPLOYEE_CODE #</td>" +
            "<td>#: FULL_NAME #</td>" +
            "<td>#: LEAVE_ENAME #</td>" +
            "<td>#: APPLIED_DATE_AD #</td>" +
            "<td>#: APPLIED_DATE_BS #</td>" +
            "<td>#: START_DATE_AD #</td>" +
            "<td>#: START_DATE_BS #</td>" +
            "<td>#: END_DATE_AD #</td>" +
            "<td>#: END_DATE_BS #</td>" +
            "<td>#: HALF_DAY_DETAIL #</td>" +
            "<td>#: GRACE_PERIOD_DETAIL #</td>" +
            "<td>#: NO_OF_DAYS #</td>" +
            "<td>#: STATUS_DETAIL #</td>" +
            "<td>" + action + "</td>" +
            "</tr>";

        app.initializeKendoGrid($table, [
            {
                title: 'Select All',
                headerTemplate: "<input type='checkbox' id='header-chb' class='k-checkbox header-checkbox'><label class='k-checkbox-label' for='header-chb'></label>",
                template: "<input type='checkbox' id='#:ID#' role-id='#:ROLE#'  class='k-checkbox row-checkbox'><label class='k-checkbox-label' for='#:ID#'></label>",
                width: 40
            },
            { field: "EMPLOYEE_CODE", title: "Code" },
            { field: "FULL_NAME", title: "Employee" },
            { field: "LEAVE_ENAME", title: "Leave Name" },
            {
                title: "Applied Date",
                columns: [{
                    field: "APPLIED_DATE_AD",
                    title: "AD",
                },
                {
                    field: "APPLIED_DATE_BS",
                    title: "BS",
                }]
            },

            {
                title: "From Date",
                columns: [{
                    field: "START_DATE_AD",
                    title: "AD",
                },
                {
                    field: "START_DATE_BS",
                    title: "BS",
                }]
            },
            {
                title: "To Date",
                columns: [{
                    field: "END_DATE_AD",
                    title: "AD",
                },
                {
                    field: "END_DATE_BS",
                    title: "BS",
                }]
            },
            { field: "HALF_DAY_DETAIL", title: "Day Interval" },
            { field: "GRACE_PERIOD_DETAIL", title: "Grace" },
            { field: "NO_OF_DAYS", title: "Duration" },
            { field: "STATUS_DETAIL", title: "Status" },
            { field: ["ID", "ROLE"], title: "Action", template: action }
        ], null, null, { rowTemplate: rowTemplateString }, 'Leave Request');


        app.pullDataById('', {}).then(function (response) {
            if (response.success) {
                app.renderKendoGrid($table, response.data);
                selectItems = {};
                var data = response.data;
                for (var i in data) {
                    selectItems[data[i]['ID']] = { 'checked': false, 'role': data[i]['ROLE'] };
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
            'LEAVE_ENAME': 'Leave',
            'APPLIED_DATE_AD': 'Requested Date(AD)',
            'APPLIED_DATE_BS': 'Requested Date(BS)',
            'START_DATE_AD': 'Start Date(AD)',
            'START_DATE_BS': 'Start Date(BS)',
            'END_DATE_AD': 'End Date(AD)',
            'END_DATE_BS': 'End Date(BS)',
            'HALF_DAY_DETAIL': 'Day Interval',
            'GRACE_PERIOD_DETAIL': 'Grace',
            'STATUS_DETAIL': 'Status',
            'REMARKS': 'Remarks',
            'RECOMMENDED_BY': 'Recommended By',
            'RECOMMENDED_DT': 'Recommended Date',
            'RECOMMENDED_REMARKS': 'Recommender Remarks',
            'YOUR_ROLE': 'Your Role'
            // 'APPROVED_BY': 'Approver By',
            // 'APPROVED_DT': 'Aprroved Date',
            // 'APPROVED_REMARKS': 'Approver Remarks'
        };
        $('#excelExport').on('click', function () {
            app.excelExport($table, exportMap, 'Leave Request List');
        });

        $('#pdfExport').on('click', function () {
            app.exportToPDF($table, exportMap, 'Leave Request List');
        });
        var selectItems = {};
        var $bulkBtnContainer = $('#acceptRejectDiv');
        var $bulkBtns = $(".btnApproveReject");
        $table.on('click', '.k-checkbox', function () {
            var checked = this.checked;
            var row = $(this).closest("tr");
            var grid = $table.data("kendoGrid");
            var dataItem = grid.dataItem(row);
            selectItems[dataItem['ID']].checked = checked;
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
                    selectedValues.push({ id: i, role: selectItems[i]['role'] });
                }
            }

            App.blockUI({ target: "#hris-page-content" });
            app.pullDataById(
                document.approveRejectUrl,
                { data: selectedValues, btnAction: btnId }
            ).then(function (success) {
                App.unblockUI("#hris-page-content");
                window.location.reload(true);
            }, function (failure) {
                App.unblockUI("#hris-page-content");
            });
        });

    });
})(window.jQuery, window.app);
