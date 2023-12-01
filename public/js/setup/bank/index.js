(function ($) {
    'use strict';
    $(document).ready(function () {
        var $table = $('#bankTable');
        var editAction = document.acl.ALLOW_UPDATE == 'Y' ? '<a class="btn-edit" title="Edit" href="' + document.editLink + '/#:BANK_ID#" style="height:17px;"> <i class="fa fa-edit"></i></a>' : '';
        var deleteAction = document.acl.ALLOW_DELETE == 'Y' ? '<a class="confirmation btn-delete" title="Delete" href="' + document.deleteLink + '/#:BANK_ID#" style="height:17px;"><i class="fa fa-trash-o"></i></a>' : '';
        var action = editAction + deleteAction;
        app.initializeKendoGrid($table, [
            { field: "BANK_ID", title: "Bank Id", width: 50 },
            { field: "BANK_NAME", title: "Bank Name", width: 180 },
            { field: "EMAIL", title: "Email", width: 120 },
            { field: "BANK_ID", title: "Action", width: 50, template: action }
        ], null, null, null, 'Bank List');

        app.searchTable('bankTable', ['BANK_NAME', 'BANK_ID']);
        $('#excelExport').on('click', function () {
            app.excelExport($table, {
                'BANK_ID': 'Bank Id',
                'BANK_NAME': 'Bank Name',
                'EMAIL': 'Email',
            }, 'Bank List');
        });

        $('#pdfExport').on('click', function () {
            app.exportToPDF($table, {
                'BANK_ID': 'Bank Id',
                'BANK_NAME': 'Address',
                'EMAIL': 'Email',
            }, 'Bank List');
        });
        app.pullDataById("", {}).then(function (response) {
            app.renderKendoGrid($table, response.data);
        }, function (error) {
        });
    });
})(window.jQuery);

