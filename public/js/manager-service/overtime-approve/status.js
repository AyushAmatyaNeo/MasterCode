(function ($, app) {
    'use strict';
    $(document).ready(function () {
        $("select").select2();
        app.startEndDatePickerWithNepali('nepaliFromDate', 'fromDate', 'nepaliToDate', 'toDate', null, true);
        var $tableContainer = $("#overtimeRequestStatusTable");
        var $search = $('#search');

        var columns = [
            { field: "EMPLOYEE_CODE", title: "Code" },
            { field: "FULL_NAME", title: "Employee" },
            {
                title: "Requested Date",
                columns: [
                    {
                        field: "REQUESTED_DATE_AD",
                        title: "AD",
                    },
                    {
                        field: "REQUESTED_DATE_BS",
                        title: "BS",
                    }]
            },
            {
                title: "Overtime Date",
                columns: [
                    {
                        field: "OVERTIME_DATE_AD",
                        title: "AD",
                    },
                    {
                        field: "OVERTIME_DATE_BS",
                        title: "BS",
                    }]
            },
            {
                field: "DETAILS", title: "Time (From-To)", template: `
                <ul id="branchList"> #  ln=DETAILS.length # #for(var i=0; i<ln; i++) { #
                    <li>
                       #=i+1 #) #=DETAILS[i].START_TIME # - #=DETAILS[i].END_TIME #
                    </li> #}#
                </ul>`},
            { field: "TOTAL_HOUR", title: "Total Hour" },
            { field: "YOUR_ROLE", title: "Your Role" },
            { field: "STATUS", title: "Status" },
            {
                field: ["OVERTIME_ID", "ROLE"], title: "Action", template: `
            <span> 
                <a class="btn  btn-icon-only btn-success" href="${document.viewLink}/#: OVERTIME_ID #/#: ROLE #" style="height:17px;" title="view">
                    <i class="fa fa-search-plus"></i>
                </a>
            </span>`}
        ];
        var map = {
            'FULL_NAME': 'Name',
            'REQUESTED_DATE_AD': 'Request Date(AD)',
            'REQUESTED_DATE_BS': 'Request Date(BS)',
            'OVERTIME_DATE_AD': 'Overtime Date(AD)',
            'OVERTIME_DATE_BS': 'Overtime Date(BS)',
            'TOTAL_HOUR': 'Total Hour',
            'DESCRIPTION': 'Description',
            'YOUR_ROLE': 'Role',
            'STATUS': 'Status',
            'REMARKS': 'Remarks',
            'RECOMMENDED_REMARKS': 'Recommended Remarks',
            'RECOMMENDED_DATE': 'Recommended Date',
            'APPROVED_REMARKS': 'Approved Remarks',
            'APPROVED_DATE': 'Approved Date'

        };
        app.initializeKendoGrid($tableContainer, columns, null, null, null, 'OT Request List');
        app.searchTable($tableContainer, ["FULL_NAME"]);

        $('#excelExport').on('click', function () {
            app.excelExport($tableContainer, map, "OT Request List.xlsx");
        });
        $('#pdfExport').on('click', function () {
            app.exportToPDF($tableContainer, map, "OT Request List.pdf");
        });

        $search.on('click', function () {
            var q = document.searchManager.getSearchValues();
            q['requestStatusId'] = $('#requestStatusId').val();
            q['fromDate'] = $('#fromDate').val();
            q['toDate'] = $('#toDate').val();
            q['recomApproveId'] = $('#recomApproveId').val();
            app.serverRequest("", q).then(function (success) {
                app.renderKendoGrid($tableContainer, success.data);
            }, function (failure) {
            });
        });

        //        $("#reset").on("click", function () {
        //            $(".form-control").val("");
        //        });

    });
})(window.jQuery, window.app);
