(function ($, app) {
    'use strict';
    $(document).ready(function () {
        $("select").select2();

        var $table = $('#penalty');
        var $fiscalYearId = $('#fiscalYearId');
        var $monthId = $('#monthId');
        var $search = $('#search');

        app.populateSelect($fiscalYearId, document.fiscalYears, 'FISCAL_YEAR_ID', 'FISCAL_YEAR_NAME', 'Select Fiscal Year');
        $fiscalYearId.on('change', function () {
            var $this = $(this);
            var value = $this.val();
            var months = document.months.filter(function (item) {
                return item['FISCAL_YEAR_ID'] == value;
            });
            app.populateSelect($monthId, months, 'MONTH_ID', 'MONTH_EDESC', "Select Month");
        });

        app.initializeKendoGrid($table, [
            {field: "COMPANY_NAME", title: "Company", width: 150},
            {field: "DEPARTMENT_NAME", title: "Department", width: 150},
            {field: "EMPLOYEE_CODE", title: "Code", width: 150},
            {field: "FULL_NAME", title: "Name", width: 150},
            {field: "MONTH_EDESC", title: "Month", width: 150},
			{field: "TOTAL_DEDUCTION_DAYS", title: "Total Deducted Days", width: 150},
        ], function (e) {
            app.pullDataById(document.getLeaveDeductedDetail, {employeeId: e.data.EMPLOYEE_ID, monthId: e.data.MONTH_ID}).then(function (response) {
                if (!response.success) {
                    app.showMessage(response.error, 'error');
                    return;
                }
                $("<div/>").appendTo(e.detailCell).kendoGrid({
                    dataSource: {
                        data: response.data,
                        pageSize: 20
                    },
                    scrollable: false,
                    sortable: false,
                    pageable: false,
                    columns: [
                        {title: "Date", columns: [
                                {field: "ATTENDANCE_DT", title: "AD"},
                                {field: "ATTENDANCE_DT_N", title: "BS"},
                            ]},
                        {field: "LEAVE_ENAME", title: "Leave Name", width: 150},
                        {field: "NO_OF_DAYS", title: "Deduct Days", width: 150},
                        {field: "REMARKS", title: "Remarks", width: 150},
                    ]
                });
            }, function (error) {
            });
        });
        app.searchTable('withOTReport', ['COMPANY_NAME', 'EMPLOYEE_CODE', 'DEPARTMENT_NAME', 'FULL_NAME', 'ATTENDANCE_DT', 'ATTENDANCE_DT_N', 'TYPE'], true);
        var exportMap = {
            'COMPANY_NAME': 'Company',
            'DEPARTMENT_NAME': 'Department',
            'EMPLOYEE_CODE': 'Code',
            'FULL_NAME': 'Name',
            'MONTH_EDESC': 'Month',
            'TOTAL_DEDUCTION_DAYS': 'Total Deduction',
        };
        $('#excelExport').on('click', function () {
            app.excelExport($table, exportMap, "AttendanceList.xlsx");
        });
        $('#pdfExport').on('click', function () {
            app.exportToPDF($table, exportMap, "AttendanceList.pdf");

        });



        $search.on('click', function () {
            var monthValue = $monthId.val();
            if (monthValue === null) {
                app.showMessage("Please select month first.");
                $monthId.focus();
                return;
            }
            var data = document.searchManager.getSearchValues();
            data['monthId'] = monthValue;
            app.pullDataById(document.getMonthlyReport, data).then(function (response) {
                if (response.success) {
                    app.renderKendoGrid($table, response.data);
                } else {
                    app.showMessage(response.error, 'error');
                }
            }, function (error) {
                app.showMessage(error, 'error');
            });
        });



    });
})(window.jQuery, window.app);