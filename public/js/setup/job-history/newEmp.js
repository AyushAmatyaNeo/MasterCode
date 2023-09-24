(function ($, app) {
    'use strict';
    $(document).ready(function () {
        $('select').select2();
        app.startEndDatePickerWithNepali('nepaliFromDate', 'fromDate', 'nepaliToDate', 'toDate');

        var $table = $("#table");
        var $excelExport = $('#excelExport');
        var $pdfExport = $('#pdfExport');

        var data = [];
        var columns = [
            { field: "EMPLOYEE_CODE", title: "Code", width: 100 },
            { field: "FULL_NAME", title: "Name", width: 100 },
            { field: "COMPANY_NAME", title: "Company", Company: 100 },
            {
                title: "Birth Date", columns: [
                    { field: "BIRTH_DATE_AD", title: "AD", width: 100 },
                    { field: "BIRTH_DATE_BS", title: "BS", width: 100 },
                ]
            },
            {
                title: "Join Date", columns: [
                    { field: "JOIN_DATE_AD", title: "AD", width: 100 },
                    { field: "JOIN_DATE_BS", title: "BS", width: 100 }
                ]
            },
            { field: "BRANCH_NAME", title: "Branch", width: 100 },
            { field: "DEPARTMENT_NAME", title: "Department", width: 100 },
            { field: "POSITION_NAME", title: "Position", width: 100 },
            { field: "DESIGNATION_TITLE", title: "Designation", width: 100 },
        ];
        app.initializeKendoGrid($table, columns, null, null, null, 'New Employees List.xlsx');
        app.searchTable('newEmployeeTable', ['FULL_NAME', 'EMPLOYEE_CODE', 'JOIN_DATE_AD', 'BRANCH_NAME', 'DEPARTMENT_NAME', 'DESIGNATION_TITLE', 'POSITION_NAME']);

        var exportKV = {
            'EMPLOYEE_CODE': 'Code',
            'FULL_NAME': 'Employee Name',
            'COMPANY_NAME': 'Company',
            'BIRTH_DATE_AD': 'Birth Date(AD)',
            'BIRTH_DATE_BS': 'Birth Date(BS)',
            'JOIN_DATE_AD': 'Join Date(AD)',
            'JOIN_DATE_BS': 'Join Date(BS)',
            'BRANCH_NAME': 'Branch',
            'DEPARTMENT_NAME': 'Department',
            'POSITION_NAME': 'Position',
            'DESIGNATION_TITLE': 'Designation',
        };
        $excelExport.on('click', function () {
            app.excelExport(data, exportKV, "New Employees List");
        });
        $pdfExport.on('click', function () {
            app.exportToPDF(data, exportKV, "New Employees List");
        });

        $('#search').on('click', function () {
            var query = document.searchManager.getSearchValues();
            query['fromDate'] = $('#fromDate').val();
            query['toDate'] = $('#toDate').val();
            if (query['fromDate'] === '') {
                $('#fromDate').focus();
                app.showMessage("From Date is required.", 'error');
                return;
            } else if (query['toDate'] === '') {
                $('#toDate').focus();
                app.showMessage("To Date is required.", 'error');
                return;
            }
            App.blockUI({ target: "#hris-page-content" });
            window.app.pullDataById(document.getNewEmpListWS, query).then(function (success) {
                App.unblockUI("#hris-page-content");
                data = success.data;
                app.renderKendoGrid($table, data);
                window.app.scrollTo($table);
            }, function (failure) {
                App.unblockUI("#hris-page-content");
            });
        });

        //        $('#reset').on('click',function (){
        //            $('.form-control').val("");
        //        });
    });
})(window.jQuery, window.app);