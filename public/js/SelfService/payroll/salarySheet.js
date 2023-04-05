(function ($, app) {
    'use strict';
    $(document).ready(function () {
        $('select').select2();
        var $fiscalYear = $('#fiscalYearId');
        var $table = $('#table');
        var map = {};

        var initKendoGrid = function (defaultColumns, data) {
            let dataSchemaCols = {};
            let aggredCols = [];
            $table.empty();
            map = {
                'EMPLOYEE_ID': 'ID',
                'FULL_NAME': 'Employee',
                'EMPLOYEE_CODE': 'EID',
                'POSITION_NAME': 'Position',
                'SERVICE_TYPE_NAME': 'Service',
                'MONTH_EDESC': 'Month',
                'BANK_NAME': 'Bank Name',
                'ACCOUNT_NO': 'Account No.',
            }

            var columns = [
                { field: "EMPLOYEE_CODE", title: "Code", width: 80, locked: true },
                { field: "FULL_NAME", title: "Employee", width: 120, locked: true },
                { field: "POSITION_NAME", title: "Position", width: 100, locked: true },
                { field: "SERVICE_TYPE_NAME", title: "Service", width: 100, locked: true },
                { field: "MONTH_EDESC", title: "Month", width: 80, locked: true },
                { field: "SALARY_TYPE_NAME", title: "Salary Type", width: 80, locked: true }

            ];

            $.each(defaultColumns, function (index, value) {
                columns.push({
                    field: value['VARIANCE'],
                    title: value['VARIANCE_NAME'],
                    width: 80,
                    aggregates: ["sum"],
                    //footerTemplate: "#=sum||''#"
                    footerTemplate: "#=kendo.toString(sum,'0.00')#"

                });
                map[value['VARIANCE']] = value['VARIANCE_NAME'];
                dataSchemaCols[value['VARIANCE']] = { type: "number" };
                aggredCols.push({ field: value['VARIANCE'], aggregate: "sum" });
            });

            columns.push(
                { field: "BANK_NAME", title: "Bank Name", width: 100 },
                { field: "ID_ACCOUNT_NO", title: "Account No.", width: 120 }
            );

            $table.kendoGrid({
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
                toolbar: ["excel"],
                excel: {
                    fileName: "Employee Wise Group Sheet Report.xlsx",
                    filterable: false,
                    allPages: true
                },
                excelExport: function (e) {
                    var rows = e.workbook.sheets[0].rows;
                    var columns = e.workbook.sheets[0].columns;
                    const salaryTypes = document.salaryType;
                    const salaryType = salaryTypes.filter(salaryType => salaryType.SALARY_TYPE_ID == selectedSalarySheetList[0].SALARY_TYPE_ID);

                    if (document.preference != undefined) {
                        if (document.preference.companyAddress != null) {
                            rows.unshift({
                                cells: [
                                    {
                                        value: document.preference.companyAddress,
                                        colSpan: columns.length,
                                        textAlign: "left"
                                    }
                                ]
                            });
                        }
                    }
                    if (document.preference != undefined) {
                        if (document.preference.companyName != null) {
                            rows.unshift({
                                cells: [
                                    { value: document.preference.companyName, colSpan: columns.length, textAlign: "left" }
                                ]
                            });
                        }
                    }
                },
                height: 550,
                scrollable: true,
                sortable: true,
                groupable: true,
                filterable: true,
                pageable: {
                    refresh: true,
                    pageSizes: true,
                    input: true,
                    numeric: false
                },
                columns: columns
            });

        }

        // app.searchTable($table, ['EMPLOYEE_CODE', 'FULL_NAME']);
        $('#viewBtn').on('click', function () {
            var $fiscalId = $fiscalYear.val();

            app.serverRequest('', { 'fiscalId': $fiscalId }).then(function (response) {
                if (response.success) {
                    initKendoGrid(response.columns, response.data);
                }
                //app.renderKendoGrid($table, response.data);
                else {
                    app.showMessage(response.error, 'error');
                }
            }, function (error) {
                app.showMessage(error, 'error');
            });
        });
        $('#excelExport').on('click', function () {
            app.excelExport($table, map, 'Employee  GroupSheet.xlsx');
        });
        $('#pdfExport').on('click', function () {
            app.exportToPDF($table, map, 'Employee  GroupSheet.pdf', 'A1');
        });
    });
})(window.jQuery, window.app);