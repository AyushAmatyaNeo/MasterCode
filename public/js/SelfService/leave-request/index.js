(function ($, app) {
    'use strict';
    $(document).ready(function () {
        $("select").select2();
        var $leaveId = $("#leaveId");
        var $leaveYear = $('#leaveYear');
        app.startEndDatePickerWithNepali('nepaliFromDate', 'fromDate', 'nepaliToDate', 'toDate', null, true);

        $("#reset").on("click", function () {
            if (typeof document.ids !== "undefined") {
                $.each(document.ids, function (key, value) {
                    $("#" + key).val(value).change();
                });
            }
        });

        var $table = $('#leaveRequestTable');
        var action = `
            <div class="clearfix">
                <a class="btn btn-icon-only green" href="${document.viewLink}/#:ID#" style="height:17px;" title="View Detail">
                    <i class="fa fa-search"></i>
                </a>
                #if(STATUS==='RQ'){#
                    <a class="btn btn-icon-only yellow" href="${document.editLink}/#:ID#" style="height:17px;" title="Edit">
                        <i class="fa fa-edit"></i>
                    </a>
                    #}#
            </div>
        `;

        app.initializeKendoGrid($table, [
            { field: "LEAVE_ENAME", title: "Leave Name" },
            {
                title: "Applied Date",
                columns: [{
                    field: "REQUESTED_DATE_AD",
                    title: "AD",
                    template: "<span>#: (REQUESTED_DT_AD == null) ? '-' : REQUESTED_DT_AD #</span>"
                },
                {
                    field: "REQUESTED_DT_BS",
                    title: "BS",
                    template: "<span>#: (REQUESTED_DT_BS == null) ? '-' : REQUESTED_DT_BS #</span>"
                }]
            },

            {
                title: "From Date",
                columns: [{
                    field: "FROM_DATE_AD",
                    title: "AD",
                    template: "<span>#: (FROM_DATE_AD == null) ? '-' : FROM_DATE_AD #</span>"
                },
                {
                    field: "FROM_DATE_BS",
                    title: "BS",
                    template: "<span>#: (FROM_DATE_BS == null) ? '-' : FROM_DATE_BS #</span>"
                }]
            },
            {
                title: "To Date",
                columns: [{
                    field: "TO_DATE_AD",
                    title: "AD",
                    template: "<span>#: (TO_DATE_AD == null) ? '-' : TO_DATE_AD #</span>"
                },
                {
                    field: "TO_DATE_BS",
                    title: "BS",
                    template: "<span>#: (TO_DATE_BS == null) ? '-' : TO_DATE_BS #</span>"
                }]
            },
            { field: "HALF_DAY_DETAIL", title: "Day Interval" },
            { field: "GRACE_PERIOD_DETAIL", title: "Grace" },
            { field: "NO_OF_DAYS", title: "Duration" },
            { field: "HALF_DAY_DETAIL", title: "Type" },
            { field: "STATUS_DETAIL", title: "Status" },
            { field: ["ID"], title: "Action", template: action }
        ], null, null, null, 'Leave Request List');


        $('#viewLeaveRequestStatus').on('click', function () {
            var employeeId = $('#employeeId').val();
            var leaveId = $('#leaveId').val();
            var leaveRequestStatusId = $('#leaveRequestStatusId').val();
            var fromDate = $('#fromDate').val();
            var toDate = $('#toDate').val();
            let leaveYear = $leaveYear.val();

            app.pullDataById('', {
                'employeeId': employeeId,
                'leaveId': leaveId,
                'leaveRequestStatusId': leaveRequestStatusId,
                'fromDate': fromDate,
                'toDate': toDate,
                'leaveYear': leaveYear,
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


        app.searchTable('leaveRequestTable', ['LEAVE_ENAME', 'REQUESTED_DT_AD', 'REQUESTED_DT_BS', 'FROM_DATE_AD', 'FROM_DATE_BS', 'TO_DATE_AD', 'TO_DATE_BS', 'NO_OF_DAYS', 'STATUS_DETAIL']);
        var exportMap = {
            'LEAVE_ENAME': 'Leave',
            'REQUESTED_DT_AD': 'Requested Date(AD)',
            'REQUESTED_DT_BS': 'Requested Date(BS)',
            'FROM_DATE_AD': 'Start Date(AD)',
            'FROM_DATE_BS': 'Start Date(BS)',
            'TO_DATE_AD': 'End Date(AD)',
            'TO_DATE_BS': 'End Date(BS)',
            'HALF_DAY_DETAIL': 'Day Interval',
            'GRACE_PERIOD_DETAIL': 'Grace',
            'STATUS_DETAIL': 'Status',
            'REMARKS': 'Remarks',
            'RECOMMENDER_NAME': 'Recommender',
            'RECOMMENDED_REMARKS': 'Recommender Remarks',
            'RECOMMENDED_DT': 'Recommended Date',
            'APPROVER_NAME': 'Approver',
            'APPROVED_REMARKS': 'Approver Remarks',
            'APPROVED_DT': 'Aprroved Date'
        };
        $('#excelExport').on('click', function () {
            app.excelExport($table, exportMap, 'Leave Request List');
        });

        $('#pdfExport').on('click', function () {
            app.exportToPDF($table, exportMap, 'Leave Request List');
        });


        function leaveYearChange(leaveYear) {
            let leaveList = document.allLeaveForReport[leaveYear];
            app.populateSelect($leaveId, leaveList, 'LEAVE_ID', 'LEAVE_ENAME', 'All Leaves', -1, -1);
        }
        leaveYearChange($leaveYear.val());

        $leaveYear.on('change', function () {
            let selectedLeaveYear = $(this).val();
            leaveYearChange(selectedLeaveYear);
            //            let leaveList = document.allLeaveForReport[selectedLeaveYear];
            //            app.populateSelect($leaveId, leaveList, 'LEAVE_ID', 'LEAVE_ENAME', 'All Leaves', -1, -1);
        });

    });
})(window.jQuery, window.app);
