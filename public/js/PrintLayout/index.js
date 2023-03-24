(function ($) {
    'use strict';
    $(document).ready(function () {
        var $table = $('#reportTable');
        
        var editAction = '<a class="btn-edit" title="Edit" href="' + document.editLink + '/#:PR_ID#" style="height:17px;"> <i class="fa fa-edit"></i></a>';
        var deleteAction = '<a   class=" confirmation btn-edit" title="Delete" href="' + document.deleteLink + '/#:PR_ID#" style="height:17px;"> <i class="fa fa-trash"></i></a>';
        var action = editAction + deleteAction;
        app.initializeKendoGrid($table, [
            {field: "SN", title: "SN.", width: 20},
            {field: "PR_CODE", title: "Report Code", width: 80},
            {field: "SUBJECT", title: "Subject", width: 100},
            // {field: "CC", title: "CC", width: 80},
            // {field: "INSURANCE_DT", title: "Insurance Date", width: 80},
            // {field: "IS_COMPLETED", title: "Completed", width: 80},
            // {field: "MATURED_DT", title: "Matured Date", width: 80},
            // {field: "REMARKS", title: "Remarks", width: 120},
            {field: "PR_ID", title: "Action", width: 100, template: action},
        ], null, null, null, 'Print Report List');

        app.serverRequest(document.getReportsTableData,'').then(function(success){
            console.log(success);
            app.renderKendoGrid($table,success.data);
        }, function (failure){
            ApplicationCache.unblockUI("#hris-page-content");
        });
        app.searchTable('reportTable', ['SN','PR_CODE', 'SUBJECT']);
        $('#excelExport').on('click', function () {
            app.excelExport($table, {
                'SN': 'SN.',
                'PR_CODE': 'Report Code',
                'SUBJECT': 'Subject',
                'CC': 'CC',
                // 'INSURANCE_DT': 'Insurance Date',
                // 'IS_COMPLETED': 'Completed',
                // 'MATURED_DT': 'Matured Date',
                // 'REMARKS': 'Remarks',
            }, 'Print Report List');
        });

        $('#pdfExport').on('click', function () {
            app.exportToPDF($table, {
                'SN': 'SN.',
                'PR_CODE': 'Report Code',
                'SUBJECT': 'Subject',
                'CC': 'CC',
                // 'INSURANCE_DT': 'Insurance Date',
                // 'IS_COMPLETED': 'Completed',
                // 'MATURED_DT': 'Matured Date',
                // 'REMARKS': 'Remarks',
            }, 'Print Report List');
        });
    });
})(window.jQuery);

