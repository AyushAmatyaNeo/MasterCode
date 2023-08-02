(function ($) {
    'use strict';
    $(document).ready(function () {
        var $table = $('#leaveTable');
        var editAction = document.acl.ALLOW_UPDATE == 'Y' ? '<a class="btn-edit" title="Edit" href="' + document.editLink + '/#:LEAVE_ID#" style="height:17px;"> <i class="fa fa-edit"></i></a>' : '';
        var deleteAction = document.acl.ALLOW_DELETE == 'Y' ? '<a class="confirmation btn-delete" title="Delete" href="' + document.deleteLink + '/#:LEAVE_ID#" style="height:17px;"><i class="fa fa-trash-o"></i></a>' : '';
        var action = editAction + deleteAction;
        app.initializeKendoGrid($table, [
            {field: "LEAVE_ENAME", title: "Leave"},
            {field: "ALLOW_HALFDAY", title: "Allow Halfday"},
            {field: "DEFAULT_DAYS", title: "Default Days"},
            {field: "CARRY_FORWARD", title: "Carry Forward"},
            {field: "CASHABLE", title: "Cashable"},
            {field: "PAID", title: "Paid"},
            {field: "LEAVE_ID", title: "Action", width: 120, template: action}
        ], null, null, null, 'Leave List');

        app.searchTable('leaveTable', ['LEAVE_ENAME', 'ALLOW_HALFDAY', 'DEFAULT_DAYS', 'CARRY_FORWARD', 'CASHABLE', 'PAID']);

        var map = {
            'LEAVE_ENAME': 'Leave',
            'ALLOW_HALFDAY': 'Allow Halfday',
            'DEFAULT_DAYS': 'Default Days',
            'CARRY_FORWARD': 'Carry Forward',
            'CASHABLE': 'Cashable',
            'PAID': 'Paid'
        };
        $('#excelExport').on('click', function () {
            app.excelExport($table, map, 'Leave List');
        });
        $('#pdfExport').on('click', function () {
            app.exportToPDF($table, map, 'Leave List');
        });
        app.pullDataById("", {}).then(function (response) {
            app.renderKendoGrid($table, response.data);
        }, function (error) {

        });
    });
})(window.jQuery);
