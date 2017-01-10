(function ($) {
    'use strict';
    $(document).ready(function () {
        console.log(document.advanceApprove);
        $("#advanceApproveTable").kendoGrid({
            excel: {
                fileName: "AdvanceRequestList.xlsx",
                filterable: true,
                allPages: true
            },
            dataSource: {
                data: document.advanceApprove,
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
            rowTemplate: kendo.template($("#rowTemplate").html()),
            columns: [
                {field: "FIRST_NAME", title: "Employee Name", width: 150},
                {field: "ADVANCE_NAME", title: "Advance Name", width: 120},
                {field: "REQUESTED_DATE", title: "Requested Date", width: 120},
                {field: "ADVANCE_DATE", title: "Advance Date", width: 100},
                {field: "REQUESTED_AMOUNT", title: "Requested Amt.", width: 120},
                {field: "TERMS", title: "Terms(in terms)", width: 120},
                {field: "YOUR_ROLE", title: "Your Role", width: 100},
                {title: "Action", width: 70}
            ]
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
        ;
        $("#export").click(function (e) {
            var rows = [{
                    cells: [
                        {value: "Employee Name"},
                        {value: "Advance Name"},
                        {value: "Requested Date"},
                        {value: "Advance Date"},
                        {value: "Requested Amount"},
                        {value: "Terms"},
                        {value: "Your Role"},
                        {value: "Status"},
                        {value: "Reason"},
                        {value: "Remarks By Recommender"},
                        {value: "Recommended Date"},
                        {value: "Remarks By Approver"},
                        {value: "Approved Date"}
                    ]
                }];
            var dataSource = $("#advanceApproveTable").data("kendoGrid").dataSource;
            var filteredDataSource = new kendo.data.DataSource({
                data: dataSource.data(),
                filter: dataSource.filter()
            });

            filteredDataSource.read();
            var data = filteredDataSource.view();

            for (var i = 0; i < data.length; i++) {
                var dataItem = data[i];
                var middleName = dataItem.MIDDLE_NAME != null ? " " + dataItem.MIDDLE_NAME + " " : " ";

                rows.push({
                    cells: [
                        {value: dataItem.FIRST_NAME + middleName + dataItem.LAST_NAME},
                        {value: dataItem.ADVANE_NAME},
                        {value: dataItem.REQUESTED_DATE},
                        {value: dataItem.ADVANCE_DATE},
                        {value: dataItem.REQUESTED_AMOUNT},
                        {value: dataItem.TERMS},
                        {value: dataItem.YOUR_ROLE},
                        {value: "Pending"},
                        {value: dataItem.REASON},
                        {value: dataItem.RECOMMENDED_REMARKS},
                        {value: dataItem.RECOMMENDED_DATE},
                        {value: dataItem.APPROVED_REMARKS},
                        {value: dataItem.APPROVED_DATE},
                    ]
                });
            }
            excelExport(rows);
            e.preventDefault();
        });

        function excelExport(rows) {
            var workbook = new kendo.ooxml.Workbook({
                sheets: [
                    {
                        columns: [
                            {autoWidth: true},
                            {autoWidth: true},
                            {autoWidth: true},
                            {autoWidth: true},
                            {autoWidth: true},
                            {autoWidth: true},
                            {autoWidth: true},
                            {autoWidth: true},
                            {autoWidth: true},
                            {autoWidth: true},
                            {autoWidth: true},
                            {autoWidth: true},
                            {autoWidth: true}
                        ],
                        title: "Advance Request List",
                        rows: rows
                    }
                ]
            });
            kendo.saveAs({dataURI: workbook.toDataURL(), fileName: "AdvanceRequestList.xlsx"});
        }
    });
})(window.jQuery, window.app);