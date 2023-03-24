(function ($, app) {
    'use strict';
    $(document).ready(function () {
        $('select').select2();
        app.startEndDatePickerWithNepali('nepaliFromDate', 'fromDate', 'nepaliToDate', 'toDate', null, true);
        var $table = $('#table');
        var $search = $('#search');
        var $status = $('#status');
        var $fromDate = $('#fromDate');
        var $toDate = $('#toDate');
        var $bulkActionDiv = $('#bulkActionDiv');
        var $bulkBtns = $(".btnApproveReject");
        var $superpower = $("#super_power");
        var action = `
            <div class="clearfix">
                <a class="btn btn-icon-only green" href="${document.viewLink}/#:REQUEST_ID#" style="height:17px;" title="View Detail">
                    <i class="fa fa-search"></i>
                </a>
            </div>
        `;
        var columns = [
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
            {field: "REQUEST_ID", title: "Action", template: action}
        ];
        columns=app.prependPrefColumns(columns);
        var pk = 'REQUEST_ID';
        var grid = app.initializeKendoGrid($table, columns, null, {id: pk, atLast: false, fn: function (selected) {
                if (selected) {
                    $bulkActionDiv.show();
                } else {
                    $bulkActionDiv.hide();
                }
            }});

        $search.on('click', function () {
            var data = document.searchManager.getSearchValues();
            data['status'] = $status.val();
            data['fromDate'] = $fromDate.val();
            data['toDate'] = $toDate.val();
            app.serverRequest('', data).then(function (response) {
                if (response.success) {
                    app.renderKendoGrid($table, response.data);
                } else {
                    app.showMessage(response.error, 'error');
                }
            }, function (error) {
                app.showMessage(error, 'error');
            });
        });
        app.searchTable($table, ['FULL_NAME','EMPLOYEE_CODE','TITLE','EVENT_TYPE','REQUESTED_DATE','REQUESTED_DATE_BS',
            'START_DATE','START_DATE_BS',
            'END_DATE','END_DATE_BS',
            'DURATION','STATUS_DETAIL',
            'REMARKS'
        ]);
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
        exportMap=app.prependPrefExportMap(exportMap);
        $('#excelExport').on('click', function () {
            app.excelExport($table, exportMap, 'Event Request List.xlsx');
        });
        $('#pdfExport').on('click', function () {
            app.exportToPDF($table, exportMap, 'Event Request List.pdf');
        });

        $bulkBtns.bind("click", function () {
            var list = grid.getSelected();
            var action = $(this).attr('action');
            var superPower = $superpower.prop('checked');

            var selectedValues = [];
            for (var i in list) {
                selectedValues.push({id: list[i][pk], action: action, status: list[i]['STATUS'], super_power: superPower});
            }
            app.bulkServerRequest(document.bulkLink, selectedValues, function () {
                $search.trigger('click');
            }, function (data, error) {

            });
        });
        
    });
})(window.jQuery, window.app);
