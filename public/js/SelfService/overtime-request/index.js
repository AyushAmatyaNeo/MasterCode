(function ($, app) {
    'use strict';
    $(document).ready(function () {
        var $table = $('#table');
        var action = `
            <div class="clearfix">
                <a class="btn btn-icon-only green" href="${document.viewLink}/#:OVERTIME_ID#" style="height:17px;" title="View Detail">
                    <i class="fa fa-search"></i>
                </a>
             
                #if(ALLOW_DELETE=='Y'){#
                <a  class="btn btn-icon-only red confirmation" href="${document.deleteLink}/#:OVERTIME_ID#" style="height:17px;" title="Cancel">
                    <i class="fa fa-times"></i>
                </a>
                #}#
            </div>
        `;
        // #if(ALLOW_EDIT=='Y'){#
        //     <a class="btn btn-icon-only yellow" href="${document.editLink}/#:OVERTIME_ID#" style="height:17px;" title="Edit">
        //         <i class="fa fa-edit"></i>
        //     </a>
        //     #}#
        var columns = [
            {title: "Overtime Date",
                columns: [
                    {
                        field: "OVERTIME_DATE_AD",
                        title: "AD"
                    },
                    {
                        field: "OVERTIME_DATE_BS",
                        title: "BS",
                    }
                ]},
            {field: "DETAILS", title: "Overtime (From - To)", template: `
                <ul>  
                    #for(var i =0;i < DETAILS.length;i++) { #
                    <li>
                       #=DETAILS[i].START_TIME # - #=DETAILS[i].END_TIME #
                    </li>
                    #}#
                </ul>`},
            {field: "TOTAL_HOUR", title: "Total Hour"},
            {title: "Requested Date",
                columns: [{
                        field: "REQUESTED_DATE_AD",
                        title: "English",
                    },
                    {
                        field: "REQUESTED_DATE_BS",
                        title: "Nepali",
                    }]},
            {field: "STATUS", title: "Status"},
            {field: ["OVERTIME_ID", "ALLOW_EDIT", "ALLOW_DELETE"], title: "Action", template: action}
        ];
        var map = {
            'OVERTIME_DATE_AD': ' Overtime Date(AD)',
            'OVERTIME_DATE_BS': ' Overtime Date(BS)',
            'REQUESTED_DATE_AD': 'Request Date(AD)',
            'REQUESTED_DATE_BS': 'Request Date(BS)',
            'TOTAL_HOUR': 'Total Hour',
            'STATUS': 'Status',
            'DESCRIPTION': 'Description',
            'REMARKS': 'Remarks',
            'RECOMMENDER_NAME': 'Recommender',
            'APPROVER_NAME': 'Approver',
            'RECOMMENDED_REMARKS': 'Recommended Remarks',
            'RECOMMENDED_DATE': 'Recommended Date',
            'APPROVED_REMARKS': 'Approved Remarks',
            'APPROVED_DATE': 'Approved Date'
        };
        app.initializeKendoGrid($table, columns, null, null, null, 'Overtime Request');

        app.searchTable($table, ['OVERTIME_DATE_AD', 'OVERTIME_DATE_BS', 'REQUESTED_DATE_AD', 'REQUESTED_DATE_BS', 'STATUS']);

        $('#excelExport').on('click', function () {
            app.excelExport($table, map, 'Overtime Request List.xlsx');
        });
        $('#pdfExport').on('click', function () {
            app.exportToPDF($table, map, 'Overtime Request List.pdf');
        });

        app.pullDataById("", {}).then(function (response) {
            app.renderKendoGrid($table, response.data);
        }, function (error) {

        });
    });
})(window.jQuery, window.app);