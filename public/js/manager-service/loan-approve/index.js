(function ($) {
    'use strict';
    $(document).ready(function () {
        console.log(document.loanApprove);
        var loanGrid = $("#loanApproveTable").kendoGrid({
            excel: {
                fileName: "LoanRequestList.xlsx",
                filterable: true,
                allPages: true
            },
            dataSource: {
                data: document.loanApprove,
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
            columns: [
                {
                    title: 'Select All',
                    headerTemplate: "<input type='checkbox' id='header-chb' class='k-checkbox header-checkbox'><label class='k-checkbox-label' for='header-chb'></label>",
                    template: "<input type='checkbox' id='#:LOAN_REQUEST_ID#' role-id='#:ROLE#'  class='k-checkbox row-checkbox'><label class='k-checkbox-label' for='#:LOAN_REQUEST_ID#'></label>",
                    width: 80
                },
                {field: "EMPLOYEE_CODE", title: "Code"},
                {field: "FULL_NAME", title: "Employee"},
                {field: "LOAN_NAME", title: "Loan"},
                {title: "Requested Date",
                    columns: [{
                            field: "REQUESTED_DATE",
                            title: "English",
                            template: "<span>#: (REQUESTED_DATE == null) ? '-' : REQUESTED_DATE #</span>"},
                        {field: "REQUESTED_DATE_N",
                            title: "Nepali",
                            template: "<span>#: (REQUESTED_DATE_N == null) ? '-' : REQUESTED_DATE_N #</span>"}]},
                {title: "Loan Date",
                    columns: [{
                            field: "LOAN_DATE",
                            title: "English",
                            template: "<span>#: (LOAN_DATE == null) ? '-' : LOAN_DATE #</span>"},
                        {field: "LOAN_DATE_N",
                            title: "Nepali",
                            template: "<span>#: (LOAN_DATE_N == null) ? '-' : LOAN_DATE_N #</span>"}]},
                {field: "REQUESTED_AMOUNT", title: "Requested Amount"},
                {field: "YOUR_ROLE", title: "Your Role"},
                {field: ["LOAN_REQUEST_ID","ROLE"], title: "Action", template: `<span><a class="btn-edit" href="` + document.viewLink + `/#: LOAN_REQUEST_ID #/#: ROLE #" style="height:17px;" title="view detail">
                            <i class="fa fa-search-plus"></i>
                            </a></span>`}
            ]
        });

        var checkedIds = {};

        loanGrid.on("click", ".k-checkbox", selectRow);

        function selectRow() {
            var checked = this.checked,
                    row = $(this).closest("tr"),
                    grid = $("#loanApproveTable").data("kendoGrid"),
                    dataItem = grid.dataItem(row);
            checkedIds[dataItem.LOAN_REQUEST_ID] = {
                'checked': checked,
                data: {
                    'id': dataItem.LOAN_REQUEST_ID,
                    'role': dataItem.ROLE
                }

            }
            if (checked) {
                row.addClass("k-state-selected");
            } else {
                row.removeClass("k-state-selected");
            }

            var checkedNo = $('.k-state-selected').length;
            if (checkedNo > 0) {
                $('#acceptRejectDiv').show();
                if ($('#header-chb').prop('checked') == 1 && checkedNo == 1) {
                    $('#acceptRejectDiv').hide();
                }
            } else {
                $('#acceptRejectDiv').hide();
            }
        }


        $('#header-chb').change(function (ev) {
            var checked = ev.target.checked;
            $('.row-checkbox').each(function (idx, item) {
                if (checked) {
                    if (!($(item).closest('tr').is('.k-state-selected'))) {
                        $(item).click();
                    }
                } else {
                    if ($(item).closest('tr').is('.k-state-selected')) {
                        $(item).click();
                    }
                }
            });
        });


        $(".btnApproveReject").bind("click", function () {
            var btnId = $(this).attr('id');
            var selectedValues = [];
            for (var i in checkedIds) {
                if (checkedIds[i].checked) {
                    selectedValues.push(checkedIds[i].data);
                }
            }

//            console.log(selectedValues);
            App.blockUI({target: "#hris-page-content"});
            app.pullDataById(
                    document.approveRejectUrl,
                    {data: selectedValues, btnAction: btnId}
            ).then(function (success) {
                App.unblockUI("#hris-page-content");
                console.log(success);
                if (success.success == true) {
                    var dataSource = new kendo.data.DataSource({data: success.data, pageSize: 20});
                    var grid = $('#loanApproveTable').data("kendoGrid");
                    dataSource.read();
                    grid.setDataSource(dataSource);
                    checkedIds = {};
                    $('#acceptRejectDiv').hide();
                }

            }, function (failure) {
                App.unblockUI("#hris-page-content");
                console.log(failure);
            });
        });





        app.searchTable('loanApproveTable', ['FULL_NAME', 'EMPLOYEE_CODE', 'LOAN_NAME', 'REQUESTED_DATE', 'REQUESTED_DATE_N', 'LOAN_DATE', 'LOAN_DATE_N', 'REQUESTED_AMOUNT', 'YOUR_ROLE']);

        app.pdfExport(
                'loanApproveTable',
                {
                    'EMPLOYEE_CODE': 'Code',
                    'FULL_NAME': 'Name',
                    'LOAN_NAME': 'Loan',
                    'REQUESTED_DATE': 'Requested Date(AD)',
                    'REQUESTED_DATE_N': 'Requested Date(BS)',
                    'LOAN_DATE': 'Loan Date(AD)',
                    'LOAN_DATE_N': 'Loan Date(BS)',
                    'REQUESTED_AMOUNT': 'Request Amt',
                    'YOUR_ROLE': 'Role',
                    'STATUS': 'Status',
                    'REASON': 'Reason',
                    'RECOMMENDED_REMARKS': 'Recommended Remarks',
                    'RECOMMENDED_DATE': 'Recommended Date',
                    'APPROVED_REMARKS': 'Approved Remarks',
                    'APPROVED_DATE': 'Approved Date'

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
                        {value: "Loan Name"},
                        {value: "Requested Date(AD)"},
                        {value: "Requested Date(BS)"},
                        {value: "Loan Date(AD)"},
                        {value: "Loan Date(BS)"},
                        {value: "Requested Amount"},
                        {value: "Your Role"},
                        {value: "Status"},
                        {value: "Reason"},
                        {value: "Remarks By Recommender"},
                        {value: "Recommended Date"},
                        {value: "Remarks By Approver"},
                        {value: "Approved Date"}
                    ]
                }];
            var dataSource = $("#loanApproveTable").data("kendoGrid").dataSource;
            var filteredDataSource = new kendo.data.DataSource({
                data: dataSource.data(),
                filter: dataSource.filter()
            });

            filteredDataSource.read();
            var data = filteredDataSource.view();

            for (var i = 0; i < data.length; i++) {
                var dataItem = data[i];

                rows.push({
                    cells: [
                        {value: dataItem.FULL_NAME},
                        {value: dataItem.LOAN_NAME},
                        {value: dataItem.REQUESTED_DATE},
                        {value: dataItem.REQUESTED_DATE_N},
                        {value: dataItem.LOAN_DATE},
                        {value: dataItem.LOAN_DATE_N},
                        {value: dataItem.REQUESTED_AMOUNT},
                        {value: dataItem.YOUR_ROLE},
                        {value: dataItem.STATUS},
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
                            {autoWidth: true},
                            {autoWidth: true}
                        ],
                        title: "Loan Request List",
                        rows: rows
                    }
                ]
            });
            kendo.saveAs({dataURI: workbook.toDataURL(), fileName: "LoanRequestList.xlsx"});
        }
    });
})(window.jQuery, window.app);
