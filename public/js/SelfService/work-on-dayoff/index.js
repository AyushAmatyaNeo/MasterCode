(function ($, app) {
    'use strict';
    $(document).ready(function () {

        var $table = $('#workOnDayoffTbl');
        var action = `
            <div class="clearfix">
                <a class="btn btn-icon-only green" href="${document.viewLink}/#:ID#" style="height:17px;" title="View Detail">
                    <i class="fa fa-search"></i>
                </a>
                #if(ALLOW_DELETE=='Y'){#
                <a  class="btn btn-icon-only red confirmation" href="${document.deleteLink}/#:ID#" style="height:17px;" title="Cancel">
                    <i class="fa fa-times"></i>
                </a>
                #}#
            </div>
        `;

        // #if(ALLOW_EDIT=='Y'){#
        //     <a class="btn btn-icon-only yellow" href="${document.editLink}/#:ID#" style="height:17px;" title="Edit">
        //         <i class="fa fa-edit"></i>
        //     </a>
        //     #}#
        app.initializeKendoGrid($table, [
            {title: "Applied Date",
                columns: [{
                        field: "REQUESTED_DATE_AD",
                        title: "AD",
                        template: "<span>#: (REQUESTED_DATE_AD == null) ? '-' : REQUESTED_DATE_AD #</span>"},
                    {field: "REQUESTED_DATE_BS",
                        title: "BS",
                        template: "<span>#: (REQUESTED_DATE_BS == null) ? '-' : REQUESTED_DATE_BS #</span>"
                    }]},
            {title: "From Date",
                columns: [{
                        field: "FROM_DATE_AD",
                        title: "AD",
                        template: "<span>#: (FROM_DATE_AD == null) ? '-' : FROM_DATE_AD #</span>"},
                    {field: "FROM_DATE_BS",
                        title: "BS",
                        template: "<span>#: (FROM_DATE_BS == null) ? '-' : FROM_DATE_BS #</span>"
                    }]},
            {title: "To Date",
                columns: [{
                        field: "TO_DATE_AD",
                        title: "AD",
                        template: "<span>#: (TO_DATE_AD == null) ? '-' : TO_DATE_AD #</span>"},
                    {field: "TO_DATE_BS",
                        title: "BS",
                        template: "<span>#: (TO_DATE_BS == null) ? '-' : TO_DATE_BS #</span>"
                    }]},
            {field: "DURATION", title: "Duration"},
            {field: "STATUS_DETAIL", title: "Status"},
            {field: "ID", title: "Action", template: action}
        ], null, null, null, 'Dayoff Work Request');

        app.searchTable('workOnDayoffTbl', ['REQUESTED_DATE_AD', 'REQUESTED_DATE_BS', 'FROM_DATE_AD', 'FROM_DATE_BS', 'TO_DATE_AD', 'TO_DATE_BS', 'DURATION', 'STATUS_DETAIL']);

        app.pullDataById("", {}).then(function (response) {
            app.renderKendoGrid($table, response.data);
        }, function (error) {

        });
        var exportMap = {
            'REQUESTED_DATE_AD': 'Requested Date(AD)',
            'REQUESTED_DATE_BS': 'Requested Date(BS)',
            'FROM_DATE_AD': 'From Date(AD)',
            'FROM_DATE_BS': 'From Date(BS)',
            'TO_DATE_AD': 'To Date(AD)',
            'TO_DATE_BS': 'To Date(BS)',
            'DURATION': 'Duration',
            'STATUS_DETAIL': 'Status',
            'REMARKS': 'Remarks',
            'RECOMMENDER_NAME': 'Recommender',
            'APPROVER_NAME': 'Approver',
            'RECOMMENDED_REMARKS': 'Recommended Remarks',
            'RECOMMENDED_DATE': 'Recommended Dt',
            'APPROVED_REMARKS': 'Approved Remarks',
            'APPROVED_DATE': 'Approved Dt'
        };
        $('#excelExport').on('click', function () {
            app.excelExport($table, exportMap, 'Work On day Off Request');
        });
        $('#pdfExport').on('click', function () {
            app.exportToPDF($table, exportMap, 'Work On day Off Request');
        });

    });
})(window.jQuery, window.app);