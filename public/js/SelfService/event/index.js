(function ($) {
    'use strict';
    $(document).ready(function () {

        var $table = $('#eventTable');
        var viewAction = '<span><a class="btn-edit" href="' + document.editLink + '/#:EMPLOYEE_ID #/#:EVENT_ID #" style="height:17px;" title="view detail">'
                + '<i class="fa fa-search-plus"></i>'
                + '</a>'
                + '</span>';
        var action = viewAction;
        app.initializeKendoGrid($table, [
            {field: "EVENT_NAME", title: "Event"},
            {title: "Start Date",
                columns: [{
                        field: "START_DATE_AD",
                        title: "AD",
                        template: "<span>#: (START_DATE_AD == null) ? '-' : START_DATE_AD #</span>"},
                    {field: "START_DATE_BS",
                        title: "BS",
                        template: "<span>#: (START_DATE_BS == null) ? '-' : START_DATE_BS #</span>"}]},
            {title: "End Date",
                columns: [{
                        field: "END_DATE_AD",
                        title: "AD",
                        template: "<span>#: (END_DATE_AD == null) ? '-' : END_DATE_AD #</span>"},
                    {field: "END_DATE_BS",
                        title: "BS",
                        template: "<span>#: (END_DATE_BS == null) ? '-' : END_DATE_BS #</span>"}]},
            {field: "DURATION", title: "Duration(in hour)"},
            {field: "INSTITUTE_NAME", title: "Institute Name",
                template: "<span>#: (INSTITUTE_NAME == null) ? '-' : INSTITUTE_NAME #</span>"
            },
            {field: "LOCATION", title: "Location"},
            {field: ["EMPLOYEE_ID", "EVENT_ID"], title: "Action", template: action}
        ], null, null, null, 'Event Request');


        app.pullDataById("", {}).then(function (response) {
            app.renderKendoGrid($table, response.data);
        }, function (error) {

        });


        $('#excelExport').on('click', function () {
            app.excelExport($table, {
                'EVENT_NAME': 'Event',
                'START_DATE_AD': 'Start Date AD',
                'START_DATE_BS': 'Start Date BS',
                'END_DATE_AD': 'End Date AD',
                'END_DATE_BS': 'End Date BS',
                'DURATION': 'Duration',
                'EVENT_TYPE': 'Event',
                'INSTRUCTOR_NAME': 'Instructor',
                'INSTITUTE_NAME': 'Institute',
                'LOCATION': 'Location',
                'TELEPHONE': 'Telephone',
                'EMAIL': 'Email',
                'REMARKS': 'Remarks'
            }, 'Event List');
        });
        $('#pdfExport').on('click', function () {
            app.exportToPDF($table, {
                'EVENT_NAME': 'Event',
                'START_DATE_AD': 'Start Date AD',
                'START_DATE_BS': 'Start Date BS',
                'END_DATE_AD': 'End Date AD',
                'END_DATE_BS': 'End Date BS',
                'DURATION': 'Duration',
                'EVENT_TYPE': 'Event',
                'INSTRUCTOR_NAME': 'Instructor',
                'INSTITUTE_NAME': 'Institute',
                'LOCATION': 'Location',
                'TELEPHONE': 'Telephone',
                'EMAIL': 'Email',
                'REMARKS': 'Remarks'
            }, 'Event List');
        });


        app.searchTable('EventTable', ['EVENT_NAME', 'START_DATE_AD', 'START_DATE_BS', 'END_DATE_AD', 'END_DATE_BS', 'DURATION', 'INSTITUTE_NAME', 'LOCATION']);

    });
})(window.jQuery, window.app);
