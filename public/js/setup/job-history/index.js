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
            {field: "EMPLOYEE_CODE", title: "Code", width: 100, locked: true},
            {field: "FULL_NAME", title: "Name", width: 100, locked: true},
            {field: "SERVICE_EVENT_TYPE_NAME", title: "Event", width: 100, locked: true},
            {title: "Event Date", columns: [
                    {field: "EVENT_DATE_AD", title: "AD", width: 100},
                    {field: "EVENT_DATE_BS", title: "BS", width: 100},
                ]},
            {title: "Effective From", columns: [
                    {field: "START_DATE_AD", title: "AD", width: 100},
                    {field: "START_DATE_BS", title: "BS", width: 100}
                ]},
            {title: "Effective To", columns: [
                    {field: "END_DATE_AD", title: "AD", width: 100},
                    {field: "END_DATE_BS", title: "BS", width: 100},
                ]},
            {field: "TO_BRANCH_NAME", title: "Branch", width: 100},
            {field: "TO_DEPARTMENT_NAME", title: "Department", width: 100},
            {field: "TO_POSITION_NAME", title: "Position", width: 100},
            {field: "TO_DESIGNATION_TITLE", title: "Designation", width: 100},
            {field: "TO_SERVICE_NAME", title: "Service Type", width: 100},
            {field: "JOB_HISTORY_ID", title: "Action", width: 100, template: `
                        <a class="btn-edit"
                        href="` + document.editLink + `/#: JOB_HISTORY_ID #" style="height:17px;">
                        <i class="fa fa-edit"></i>
                        </a>
                        <a class="btn-delete confirmation"
                        href="` + document.deleteLink + `/#: JOB_HISTORY_ID #" id="bs_#:JOB_HISTORY_ID #" style="height:17px;">
                        <i class="fa fa-trash-o"></i></a>
                        </a>`}
        ];
        app.initializeKendoGrid($table, columns, null, null, null, 'Job History.xlsx');
        app.searchTable('jobHistoryTable', ['FULL_NAME', 'EMPLOYEE_CODE', 'START_DATE', 'SERVICE_EVENT_TYPE_NAME', 'TO_SERVICE_NAME', 'TO_BRANCH_NAME', 'TO_DEPARTMENT_NAME', 'TO_DESIGNATION_TITLE', 'TO_POSITION_NAME']);

        var exportKV = {
            'EMPLOYEE_CODE': 'Code',
            'FULL_NAME': 'Employee Name',
            'SERVICE_EVENT_TYPE_NAME': 'Service Event Type',
            'EVENT_DATE_AD': 'Event Date(AD)',
            'EVENT_DATE_BS': 'Event Date(BS)',
            'START_DATE_AD': 'Effective From(AD)',
            'START_DATE_BS': 'Effective From(BS)',
            'END_DATE_AD': 'Effective To(AD)',
            'END_DATE_BS': 'Effective To(BS)',
            'TO_BRANCH_NAME': 'Branch',
            'TO_DEPARTMENT_NAME': 'Department',
            'TO_POSITION_NAME': 'Position',
            'TO_DESIGNATION_TITLE': 'Designation',
            'TO_SERVICE_NAME': 'Service',
        };
        $excelExport.on('click', function () {
            app.excelExport(data, exportKV, "Employee Service History");
        });
        $pdfExport.on('click', function () {
            app.exportToPDF(data, exportKV, "Employee Service History");
        });

        $('#search').on('click', function () {
            var query = document.searchManager.getSearchValues();
            query['fromDate'] = $('#fromDate').val();
            query['toDate'] = $('#toDate').val();
            App.blockUI({target: "#hris-page-content"});
            window.app.pullDataById(document.getEmployeeLatestServiceWS, query).then(function (success) {
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