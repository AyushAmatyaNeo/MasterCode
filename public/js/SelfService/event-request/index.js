(function ($, app) {
    'use strict';
    $(document).ready(function () {

        var $table = $('#table');
        var action = `
            <div class="clearfix">
                <a class="btn btn-icon-only green" href="${document.viewLink}/#:REQUEST_ID#" style="height:17px;" title="View Detail">
                    <i class="fa fa-search"></i>
                </a>
               
                #if(ALLOW_DELETE=='Y'){#
                <a  class="btn btn-icon-only red confirmation" href="${document.deleteLink}/#:REQUEST_ID#" style="height:17px;" title="Cancel">
                    <i class="fa fa-times"></i>
                </a>
                #}#
            </div>
        `;
         // #if(ALLOW_EDIT=='Y'){#
                // <a class="btn btn-icon-only yellow" href="${document.editLink}/#:REQUEST_ID#" style="height:17px;" title="Edit">
                //     <i class="fa fa-edit"></i>
                // </a>
                // #}#
        app.initializeKendoGrid($table, [
            {field: "TITLE", title: "Event Name"},
            {title: "Applied Date",
                columns: [{
                        field: "REQUESTED_DATE_AD",
                        title: "AD",
                    },
                    {
                        field: "REQUESTED_DATE_BS",
                        title: "BS",
                    }
                ]},
            {title: "Start Date",
                columns: [{
                        field: "START_DATE_AD",
                        title: "AD",
                    },
                    {
                        field: "START_DATE_BS",
                        title: "BS",
                    }
                ]},
            {title: "End Date",
                columns: [{
                        field: "END_DATE_AD",
                        title: "AD",
                    },
                    {
                        field: "END_DATE_BS",
                        title: "BS",
                    }
                ]},
            {field: "DURATION", title: "Duration"},
            {field: "EVENT_TYPE_DETAIL", title: "Event Type"},
            {field: "STATUS_DETAIL", title: "Status"},
            {field: "REQUEST_ID", title: "Action", template: action}
        ], null, null, null, 'Event Request');

        app.pullDataById("", {}).then(function (response) {
            app.renderKendoGrid($table, response.data);
        }, function (error) {

        });

        var map = {
            'EVENT_CODE': 'Event',
            'TITLE': 'Title',
            'REQUESTED_DATE_AD': 'Requested Date(AD',
            'REQUESTED_DATE_BS': 'Requested Date(BS)',
            'START_DATE_AD': 'Start Date(AD)',
            'START_DATE_BS': 'Start Date(BS)',
            'END_DATE_AD': 'End Date(AD)',
            'END_DATE_BS': 'End Date(BS)',
            'DURATION': 'Duration',
            'EVENT_TYPE_DETAIL': 'Type',
            'STATUS_DETAIL': 'Status',
            'DESCRIPTION': 'Description',
            'REMARKS': 'Remarks',
            'RECOMMENDER_NAME': 'Recommeder',
            'APPROVER_NAME': 'Approver',
            'RECOMMENDED_REMARKS': 'Recommeder Remarks',
            'RECOMMENDED_DATE': 'Recommeder Date',
            'APPROVED_REMARKS': 'Approved Remarks',
            'APPROVED_DATE': 'Approved Dt'
        };
        $('#excelExport').on('click', function () {
            app.excelExport($table, map, 'Event Request List');
        });

        $('#pdfExport').on('click', function () {
            app.exportToPDF($table, map, 'Event Request List');
        });

        app.searchTable($table, ['TITLE', 'EVENT_TYPE', 'STATUS']);


    });
})(window.jQuery, window.app);