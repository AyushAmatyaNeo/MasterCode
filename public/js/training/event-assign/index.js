(function ($, app) {
    'use strict';
    $(document).ready(function () {
        $('select').select2();
        app.startEndDatePickerWithNepali('nepaliFromDate', 'fromDate', 'nepaliToDate', 'toDate', null, true);
        var $table = $('#table');
        var $search = $('#search');
        var $event = $('#eventId');
        var $fromDate = $('#fromDate');
        var $toDate = $('#toDate');
        var action = `
            <div class="clearfix">
                #if(ALLOW_VIEW=='Y'){#
                <a class="btn btn-icon-only green" href="${document.viewLink}/#:EMPLOYEE_ID#/#:EVENT_ID#" style="height:17px;" title="View Detail">
                    <i class="fa fa-search"></i>
                </a>
                #} if(ALLOW_DELETE='Y'){#
                <a  class="btn btn-icon-only red confirmation" href="${document.deleteLink}/#:EMPLOYEE_ID#/#:EVENT_ID#" style="height:17px;" title="Cancel">
                    <i class="fa fa-times"></i>
                </a>
                #}#
            </div>
        `;
        app.initializeKendoGrid($table, [
            {field: "EVENT_CODE", title: "Code"},
            {field: "EVENT_NAME", title: "Name"},
            {field: "EVENT_TYPE_DETAIL", title: "Type"},
            {title: "Start Date",
                columns: [{
                        field: "START_DATE_AD",
                        title: "English",
                    },
                    {
                        field: "START_DATE_BS",
                        title: "Nepali",
                    }]},
            {title: "To Date",
                columns: [{
                        field: "END_DATE_AD",
                        title: "English",
                    },
                    {field: "END_DATE_BS",
                        title: "Nepali",
                    }]},
            {field: "EMPLOYEE_CODE", title: "Code"},  
            {field: "EMPLOYEE_NAME", title: "Employee"},
            {field: "EMPLOYEE_ID", title: "Action", template: action}
        ],null, null, null, 'Employee Event Assigned List');
        $search.on('click', function () {
            var search = document.searchManager.getSearchValues();
            search['eventId'] = $event.val();
            search['fromDate'] = $fromDate.val();
            search['toDate'] = $toDate.val();
            app.pullDataById('', search).then(function (response) {
                if (response.success) {
                    app.renderKendoGrid($table, response.data);
                } else {
                    app.showMessage(response.error, 'error');
                }
            }, function (error) {
                app.showMessage(error, 'error');
            });
        });
        app.searchTable($table, ['EMPLOYEE_NAME', 'EMPLOYEE_CODE']);
        var exportMap = {
            'EVENT_CODE': 'Code',
            'EVENT_NAME': 'Name',
            'EVENT_TYPE_DETAIL': 'Type',
            'START_DATE_AD': 'From Date(AD)',
            'START_DATE_BS': 'From Date(BS)',
            'END_DATE_AD': 'To Date(AD)',
            'END_DATE_BS': 'To Date(BS)',
            'EMPLOYEE_CODE': 'Code',
            'EMPLOYEE_NAME': 'Employee Name',
        };
        $('#excelExport').on('click', function () {
            app.excelExport($table, exportMap, 'Employee Event Assigned List.xlsx');
        });
        $('#pdfExport').on('click', function () {
            app.exportToPDF($table, exportMap, 'Employee Event Assigned List.pdf');
        });
        
//        $('#reset').on('click',function (){
//            $('.form-control').val("");
//        });
    });
})(window.jQuery, window.app);
