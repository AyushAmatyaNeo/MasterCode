(function ($, app) {
    'use strict';

    $(document).ready(function () {
        app.startEndDatePickerWithNepali('nepaliFromDate', 'fromDate', 'nepaliToDate', 'toDate', null, true);
        var $tableContainer = $("#leaveRequestStatusTable");
        var $leave = $('#leaveId');
        var $status = $('#leaveRequestStatusId');
        var $search = $('#search');
        var $bulkActionDiv = $('#bulkActionDiv');
        var $bulkBtns = $(".btnApproveReject");
        var $superpower = $("#super_power");
        var $employeeId = $('#employeeId');


        app.populateSelect($employeeId, document.employees, 'id', 'name', '--------', null, null, false);

        $.each(document.searchManager.getIds(), function (key, value) {
            $('#' + value).select2();
        });

        $leave.select2();
        $status.select2();
        var baseColumns = [
            { field: "EMPLOYEE_CODE", title: "Code" },
            { field: "SAP_ID", title: "SAP ID" },
            { field: "FULL_NAME", title: "Employee" },
            { field: "LEAVE_ENAME", title: "Leave" },
            {
                title: "Requested Date",
                columns: [{
                    field: "APPLIED_DATE_AD",
                    title: "AD",
                    template: "<span>#: (APPLIED_DATE_AD == null) ? '-' : APPLIED_DATE_AD #</span>"
                },
                {
                    field: "APPLIED_DATE_BS",
                    title: "BS",
                    template: "<span>#: (APPLIED_DATE_BS == null) ? '-' : APPLIED_DATE_BS #</span>"
                }]
            },
            {
                title: "From Date",
                columns: [{
                    field: "START_DATE_AD",
                    title: "AD",
                    template: "<span>#: (START_DATE_AD == null) ? '-' : START_DATE_AD #</span>"
                },
                {
                    field: "START_DATE_BS",
                    title: "BS",
                    template: "<span>#: (START_DATE_BS == null) ? '-' : START_DATE_BS #</span>"
                }]
            },
            {
                title: "To Date",
                columns: [{
                    field: "END_DATE_AD",
                    title: "AD",
                    template: "<span>#: (END_DATE_AD == null) ? '-' : END_DATE_AD #</span>"
                },
                {
                    field: "END_DATE_BS",
                    title: "BS",
                    template: "<span>#: (END_DATE_BS == null) ? '-' : END_DATE_BS #</span>"
                }]
            },
            { field: "NO_OF_DAYS", title: "Duration" },
            { field: "HALF_DAY_DETAIL", title: "Type" },
            { field: "STATUS", title: "Status" },
        ];
        var anyCheckboxChecked;
        var grid;
        var pk;
        var map;
        var columns = baseColumns.slice();
        $search.on('click', function () {
            columns = baseColumns.slice();
            var q = document.searchManager.getSearchValues();
            anyCheckboxChecked = $('input[name="checkboxChoices[]"]:checked').length > 0;
            q['leaveId'] = $leave.val();
            q['leaveRequestStatusId'] = $status.val();
            q['fromDate'] = $('#fromDate').val();
            q['toDate'] = $('#toDate').val();
            q['recomApproveId'] = $('#recomApproveId').val();
            App.blockUI({ target: "#hris-page-content" });
            if (anyCheckboxChecked) {
                columns.push(
                    { field: "SUB_EMPLOYEE_NAME", title: "Substitute Employee" },
                    { field: "SUB_REMARKS", title: "Substitute Remarks" },
                    {
                        field: "ID", title: "Action", template: `
                    <span>                                  
                        <a class="btn  btn-icon-only btn-success" href="${document.viewLink}/#: ID #" style="height:17px; width:13px" title="view">
                        <i class="fa fa-search-plus"></i>
                        </a>
                    </span>`}
                )
                map['SUB_EMPLOYEE_NAME'] = 'Substitute Name';
                map['SUB_REMARKS'] = 'Substitute Employee Remarks';
            } else {
                columns.push(
                    {
                        field: "ID", title: "Action", template: `
                    <span>                                  
                        <a class="btn  btn-icon-only btn-success" href="${document.viewLink}/#: ID #" style="height:17px; width:13px" title="view">
                        <i class="fa fa-search-plus"></i>
                        </a>
                    </span>`}
                )
            }
            if (grid) {
                $tableContainer.empty();
            }
            columns = app.prependPrefColumns(columns);
            pk = 'ID';
            grid = app.initializeKendoGrid($tableContainer, columns, null, {
                id: pk, atLast: false, fn: function (selected) {
                    if (selected) {
                        $bulkActionDiv.show();
                    } else {
                        $bulkActionDiv.hide();
                    }
                }
            }, null, 'Leave Status Report.xlsx');
            app.pullDataById(document.pullLeaveRequestStatusListLink, q).then(function (success) {
                App.unblockUI("#hris-page-content");

                app.renderKendoGrid($tableContainer, success.data);

                // }
            }, function (failure) {
                App.unblockUI("#hris-page-content");
            });
        });
        app.searchTable($tableContainer, ["FULL_NAME", "EMPLOYEE_CODE"]);

        map = {
            'EMPLOYEE_CODE': 'Code',
            'SAP_ID': 'SAP ID',
            'FULL_NAME': 'Name',
            'FUNCTIONAL_TYPE_EDESC': 'Functional Type',
            'LEAVE_ENAME': 'Leave',
            'APPLIED_DATE_AD': 'Applied Date(AD)',
            'APPLIED_DATE_BS': 'Applied Date(BS)',
            'START_DATE_AD': 'Start Date(AD)',
            'START_DATE_BS': 'Start Date(BS)',
            'END_DATE_AD': 'End Date(AD)',
            'END_DATE_BS': 'End Date(BS)',
            'NO_OF_DAYS': 'No Of Days',
            'STATUS': 'Status',
            'REMARKS': 'Remarks',
            'RECOMMENDER_NAME': 'Recommender',
            'APPROVER_NAME': 'Approver',
            'RECOMMENDED_BY_NAME': 'Recommended By',
            'APPROVED_BY_NAME': 'Approved By',
            'APPROVED_REMARKS': 'Approved Remarks',
            'RECOMMENDED_DT': 'Approved Date',
            'APPROVED_DT': 'Approved Date',
            'RECOMMENDED_REMARKS': 'Recommended Remarks'
        };
        map = app.prependPrefExportMap(map);
        $('#excelExport').on('click', function () {
            app.excelExport($tableContainer, map, "Leave Request List.xlsx");
        });
        $('#pdfExport').on('click', function () {
            app.exportToPDF($tableContainer, map, "Leave Request List.pdf", 'A2');
        });

        $bulkBtns.bind("click", function () {
            var list = grid.getSelected();
            var action = $(this).attr('action');
            var superPower = $superpower.prop('checked');

            var selectedValues = [];
            for (var i in list) {
                selectedValues.push({ id: list[i][pk], action: action, status: list[i]['STATUS'], super_power: superPower });
            }
            app.bulkServerRequest(document.bulkLink, selectedValues, function () {
                $search.trigger('click');
            }, function (data, error) {

            });
        });


    });
})(window.jQuery, window.app);
