(function ($, app) {
    'use strict';
    $(document).ready(function () {
        $('Select').select2();

        var monthList = null;
        var $fiscalYear = $('#fiscalYearId');
        var $month = $('#monthId');
        var previousColumns = [];
        var currentColumns = [];
        var $table = $('#table');
        var map = {
            'EMPLOYEE_CODE': 'Employee Code',
            'FULL_NAME': 'Employee',
            'DEPARTMENT_NAME': 'Department',
        }
        var exportType = {
            "PRE_ACCOUNT": "STRING",
            "CUR_ACCOUNT": "STRING"
        };


        function initKendoGrid(data){
            let dataSchemaCols = {};
            let aggredCols = [];
            var columns = [
                {field: "EMPLOYEE_CODE", title: "Code", width: 80, locked: true},
                {field: "FULL_NAME", title: "Employee", width: 120, locked: true},
                {field: "DEPARTMENT_NAME", title: "Department", width: 120, locked: true},
                {title: "Previous", columns: previousColumns},
                {title: "Current", columns: currentColumns}
            ];
            $table.empty();

            $.each(document.columnList.previous, function (index, value) {
                previousColumns.push({
                    field: value['VARIANCE'],
                    title: value['VARIANCE_NAME'],
                    width: 100,
                    aggregates: ["sum"],
                    footerTemplate: "#=sum||''#"
                });
                map[value['VARIANCE']] = 'Previous_' + value['VARIANCE_NAME'];
                dataSchemaCols[value['VARIANCE']] = {type: "number"};
                aggredCols.push({field: value['VARIANCE'], aggregate: "sum"});
            });
    
            $.each(document.columnList.current, function (index, value) {
                currentColumns.push({
                    field: value['VARIANCE'],
                    title: value['VARIANCE_NAME'],
                    width: 100,
                    aggregates: ["sum"],
                    footerTemplate: "#=sum||''#"
                });
                map[value['VARIANCE']] = 'Current_' + value['VARIANCE_NAME'];
                dataSchemaCols[value['VARIANCE']] = {type: "number"};
                aggredCols.push({field: value['VARIANCE'], aggregate: "sum"});
            });
    
            $.each(document.columnList.difference, function (index, value) {
                columns.push({
                    field: value['VARIANCE'],
                    title: value['VARIANCE_NAME'],
                    width: 100,
                    aggregates: ["sum"],
                    footerTemplate: "#=sum||''#"
                });
                map[value['VARIANCE']] = 'Difference_' + value['VARIANCE_NAME'];
                dataSchemaCols[value['VARIANCE']] = {type: "number"};
                aggredCols.push({field: value['VARIANCE'], aggregate: "sum"});
            });
    
            map['PRE_ADDRESS'] = 'Previous Address';
            map['CUR_ADDRESS'] = 'Address';
            map['ADDRESS_REMARKS'] = 'Address Remarks';
            map['PRE_ACCOUNT'] = 'Previous A/C';
            map['CUR_ACCOUNT'] = 'A/C';
            map['ACCOUNT_REMARKS'] = 'A/C Remarks';
            columns.push(
                    {field: "PRE_ADDRESS", title: "Previous Address", width: 120},
                    {field: "CUR_ADDRESS", title: "Address", width: 120},
                    {field: "ADDRESS_REMARKS", title: "Address Remarks", width: 120},
                    {field: "PRE_ACCOUNT", title: "Previous A/C", width: 120},
                    {field: "CUR_ACCOUNT", title: "A/C", width: 120},
                    {field: "ACCOUNT_REMARKS", title: "A/C Remarks", width: 120}
            );

            $table.kendoGrid({
                toolbar: ["excel"],
                excel: {
                    fileName: "Variance Report.xlsx",
                    filterable: true,
                    allPages: true
                },
                dataSource: {
                    data: data,
                    schema: {
                        model: {
                            fields: dataSchemaCols
                        }
                    },
                    pageSize: 20,
                    aggregate: aggredCols
                },
                height: 550,
                scrollable: true,
                sortable: true,
                groupable: true,
                filterable: true,
                pageable: {
                    input: true,
                    numeric: false
                },
                columns: columns
            });

        }

        app.setFiscalMonth($fiscalYear, $month, function (years, months, currentMonth) {
            monthList = months;
        });

        $('#searchEmployeesBtn').on('click', function () {
            var q = document.searchManager.getSearchValues();
            q['monthId'] = $month.val();
            console.log(q);

            app.serverRequest(document.pullVarianceListLink, q).then(function (response) {
                if (response.success) {
                    // app.renderKendoGrid($table, response.data);
                    initKendoGrid(response.data);
                } else {
                    app.showMessage(response.error, 'error');
                }
            }, function (error) {
                app.showMessage(error, 'error');
            });
        });



        $('#excelExport').on('click', function () {
            app.excelExport($table, map, 'VairanceReport.xlsx', exportType);
        });
        $('#pdfExport').on('click', function () {
            app.exportToPDF($table, map, 'VairanceReport.pdf');
        });
    });
})(window.jQuery, window.app);


