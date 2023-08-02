(function ($) {
    'use strict';
    $(document).ready(function () {

        $("#shiftAdjustmentTable").kendoGrid({
            excel: {
                fileName: "ShiftAdjustList.xlsx",
                filterable: true,
                allPages: true
            },
            dataSource: {
                data: document.shiftsAdjust,
                pageSize: 20
            },
            height: 450,
            scrollable: true,
            sortable: true,
            filterable: true,
            pageable: {
                input: true,
                numeric: false
            },
            dataBound: gridDataBound,
//            rowTemplate: kendo.template($("#rowTemplate").html()),
            columns: [
//                {field: "ADJUSTMENT_START_DATE", title: "Start Date", width: 120},
//                {field: "ADJUSTMENT_END_DATE", title: "End Date", width: 130},
                {title: "Start Date",
                    columns: [
                        {field: "ADJUSTMENT_START_DATE",
                            title: "AD",
                            template: "<span>#: (ADJUSTMENT_START_DATE == null) ? '-' : ADJUSTMENT_START_DATE # </span>"},
                        {field: "ADJUSTMENT_START_DATE_N",
                            title: "BS",
                            template: "<span>#: (ADJUSTMENT_START_DATE_N == null) ? '-' : ADJUSTMENT_START_DATE_N # </span>"}
                    ]},
                {title: "End Date",
                    columns: [
                        {field: "ADJUSTMENT_END_DATE",
                            title: "AD",
                            template: "<span>#: (ADJUSTMENT_END_DATE == null) ? '-' : ADJUSTMENT_END_DATE # </span>"},
                        {field: "ADJUSTMENT_END_DATE_N",
                            title: "BS",
                            template: "<span>#: (ADJUSTMENT_END_DATE_N == null) ? '-' : ADJUSTMENT_END_DATE_N # </span>"}
                    ]},
                {field: "START_TIME", title: "Start Time", template: "<span>#: (START_TIME == null) ? '-' : START_TIME # </span>"},
                {field: "END_TIME", title: "End Time", template: "<span>#: (END_TIME == null) ? '-' : END_TIME # </span>"},
                {field: "ADJUSTMENT_ID", title: "Action", template: `<span><a class="btn-edit" 
        href="` + document.addLink + `/#:ADJUSTMENT_ID #" style="height:17px;">
        <i class="fa fa-edit"></i>
        </a> </span>`}
            ]
        });

        app.searchTable('shiftAdjustmentTable', ['ADJUSTMENT_START_DATE', 'ADJUSTMENT_END_DATE', 'ADJUSTMENT_START_DATE_N', 'ADJUSTMENT_END_DATE_N', 'START_TIME', 'END_TIME']);

        app.pdfExport(
                'shiftAdjustmentTable',
                {
                    'ADJUSTMENT_START_DATE': ' StartDate(AD)',
                    'ADJUSTMENT_START_DATE_N': ' StartDate(BS)',
                    'ADJUSTMENT_END_DATE': 'End Date(AD)',
                    'ADJUSTMENT_END_DATE_N': 'End Date(BS)',
                    'START_TIME': 'Start Time',
                    'END_TIME': 'End Time'
                }
        );

        $("#export").click(function (e) {
            var grid = $("#shiftAdjustmentTable").data("kendoGrid");
            grid.saveAsExcel();
        });



        function gridDataBound(e) {
            var grid = e.sender;
            if (grid.dataSource.total() == 0) {
                var colCount = grid.columns.length;
                $(e.sender.wrapper)
                        .find('tbody')
                        .append('<tr class="kendo-data-row"><td colspan="' + colCount + '" class="no-data">There is no data to show in the grid.</td></tr>');
            }
        }

//        $("#reset").on("click", function () {
//            $(".form-control").val("");
//        });
    });
})(window.jQuery, window.app);
