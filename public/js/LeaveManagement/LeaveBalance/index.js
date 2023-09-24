(function ($, app) {
    'use strict';
    $(document).ready(function () {
        $("select").select2();
        var $table = $("#table");
        var $search = $('#search');
        var $leaveId = $("#leaveId");
        var $leaveYear = $('#leaveYear');
        var columns = [
            { field: "EMPLOYEE_CODE", title: "Code", width: 150, locked: true },
            { field: "FULL_NAME", title: "Employee", width: 150, locked: true },
        ];
        var map = {
            'EMPLOYEE_CODE': 'Code',
            //'EMPLOYEE_ID': 'Id', 
            'FULL_NAME': 'Name',
            'DEPARTMENT_NAME': 'Department',
            'FUNCTIONAL_TYPE_EDESC': 'Functional Type',
            'POSITION_NAME': ' Position'
        };

        var columnOptions = [];
        columnOptions.push({ 'VALUES': '0', 'COLUMNS': 'Previous' });
        columnOptions.push({ 'VALUES': '1', 'COLUMNS': 'Current' });
        columnOptions.push({ 'VALUES': '2', 'COLUMNS': 'Total' });
        columnOptions.push({ 'VALUES': '3', 'COLUMNS': 'Taken' });
        columnOptions.push({ 'VALUES': '4', 'COLUMNS': 'Encashed' });
        columnOptions.push({ 'VALUES': '5', 'COLUMNS': 'Deducted' });

        var $options = $('#options');
        app.populateSelect($options, columnOptions, 'VALUES', 'COLUMNS');

        var leaveList = document.leaves;
        app.populateSelect($leaveId, leaveList, 'LEAVE_ID', 'LEAVE_ENAME');
        function reinitializeKendo(optionalColumns) {
            console.log(optionalColumns);
            columns = [
                { field: "EMPLOYEE_CODE", title: "Code", width: 70, locked: true },
                { field: "FULL_NAME", title: "Employee", width: 100, locked: true },
                { field: "DEPARTMENT_NAME", title: "Department", width: 100, locked: true },
                { field: "FUNCTIONAL_TYPE_EDESC", title: "Functional Type", width: 100, locked: true },
            ];
            map = {
                'EMPLOYEE_CODE': 'Code',
                //'EMPLOYEE_ID': 'Id', 
                'FULL_NAME': 'Name',
                'DEPARTMENT_NAME': 'Department',
                'FUNCTIONAL_TYPE_EDESC': 'Functional Type',
                'POSITION_NAME': ' Position'
            };
            var flag, flag2;
            var columnsList;
            for (var i in leaveList) {
                flag = false;
                flag2 = false;
                columnsList = {
                    title: leaveList[i]['LEAVE_ENAME'],
                    columns: []
                };

                if (optionalColumns.indexOf("0") !== -1) {
                    columnsList.columns.push({
                        title: 'Previous',
                        field: 'L' + leaveList[i]['LEAVE_ID'] + '_' + 'PREVIOUS_YEAR_BAL',
                        width: 60
                    })
                    map['L' + leaveList[i]['LEAVE_ID'] + '_' + 'PREVIOUS_YEAR_BAL'] = leaveList[i]['LEAVE_ENAME'] + '(Previous)';
                }

                if (optionalColumns.indexOf("1") !== -1) {
                    columnsList.columns.push({
                        title: 'Current',
                        field: 'L' + leaveList[i]['LEAVE_ID'] + '_' + 'CURR',
                        width: 60
                    })
                    map['L' + leaveList[i]['LEAVE_ID'] + '_' + 'CURR'] = leaveList[i]['LEAVE_ENAME'] + '(Current)';
                }

                if (optionalColumns.indexOf("2") !== -1) {
                    columnsList.columns.push({
                        title: 'Total',
                        field: 'L' + leaveList[i]['LEAVE_ID'] + '_' + 'TOTAL',
                        width: 60
                    })
                    map['L' + leaveList[i]['LEAVE_ID'] + '_' + 'TOTAL'] = leaveList[i]['LEAVE_ENAME'] + '(Total)';
                }

                if (optionalColumns.indexOf("3") !== -1) {
                    columnsList.columns.push({
                        title: 'Taken',
                        field: 'L' + leaveList[i]['LEAVE_ID'] + '_' + 'TAKEN',
                        width: 60
                    })
                    map['L' + leaveList[i]['LEAVE_ID'] + '_' + 'TAKEN'] = leaveList[i]['LEAVE_ENAME'] + '(Taken)';
                }

                if (optionalColumns.indexOf("4") !== -1) {
                    columnsList.columns.push({
                        title: 'Encashed',
                        field: 'L' + leaveList[i]['LEAVE_ID'] + '_' + 'ENCASHED',
                        width: 70
                    })
                    map['L' + leaveList[i]['LEAVE_ID'] + '_' + 'ENCASHED'] = leaveList[i]['LEAVE_ENAME'] + '(Encashed)';
                }

                if (optionalColumns.indexOf("5") !== -1) {
                    columnsList.columns.push({
                        title: 'Deducted',
                        field: 'L' + leaveList[i]['LEAVE_ID'] + '_' + 'DEDUCTED',
                        width: 70
                    })
                    map['L' + leaveList[i]['LEAVE_ID'] + '_' + 'DEDUCTED'] = leaveList[i]['LEAVE_ENAME'] + '(Deducted)';
                }


                columnsList.columns.push({
                    title: 'Total',
                    field: 'L' + leaveList[i]['LEAVE_ID'] + '_' + 'TOTAL',
                    width: 60
                })
                columnsList.columns.push({
                    title: 'Taken',
                    field: 'L' + leaveList[i]['LEAVE_ID'] + '_' + 'TAKEN',
                    width: 60
                })
                columnsList.columns.push({
                    title: 'Balance',
                    field: 'L' + leaveList[i]['LEAVE_ID'] + '_' + 'BALANCE',
                    width: 60
                })
                map['L' + leaveList[i]['LEAVE_ID'] + '_' + 'TOTAL'] = leaveList[i]['LEAVE_ENAME'] + '(Total)';
                map['L' + leaveList[i]['LEAVE_ID'] + '_' + 'TAKEN'] = leaveList[i]['LEAVE_ENAME'] + '(Taken)';
                map['L' + leaveList[i]['LEAVE_ID'] + '_' + 'BALANCE'] = leaveList[i]['LEAVE_ENAME'] + '(Balance)';


                columns.push(columnsList);
            }
        }
        reinitializeKendo([]);
        app.initializeKendoGrid($table, columns, null, null, null, 'LeaveBalance.xlsx');
        app.searchTable($table, ['EMPLOYEE_ID', 'EMPLOYEE_CODE', 'FULL_NAME']);

        $search.on('click', function () {
            var optionalColumns = $options.val();
            $table.empty();
            var q = document.searchManager.getSearchValues();
            q['leaveId'] = $leaveId.val();
            q['leaveYear'] = $leaveYear.val();
            App.blockUI({ target: "#hris-page-content" });
            app.pullDataById(document.pullLeaveBalanceDetailLink, q).then(function (success) {
                leaveList = success.leaves;
                if (optionalColumns != null) { reinitializeKendo(optionalColumns); }
                else { reinitializeKendo([]); }
                app.initializeKendoGrid($table, columns, null, null, null, 'Leave Balance Report.xlsx');
                App.unblockUI("#hris-page-content");
                app.renderKendoGrid($table, success.data);
            }, function (failure) {
                App.unblockUI("#hris-page-content");
            });
        });

        $('#excelExport').on("click", function () {
            app.excelExport($table, map, "Employee Leave Balance Report.xlsx");
        });
        $('#pdfExport').on("click", function () {
            app.exportToPDF($table, map, "Employee Leave Balance Report.pdf", 'A2');
        });

        function leaveYearChange(leaveYear) {
            let leaveList = document.allLeaveForReport[leaveYear];
            app.populateSelect($leaveId, leaveList, 'LEAVE_ID', 'LEAVE_ENAME', 'All Leaves', -1, -1);
        }
        leaveYearChange($leaveYear.val());

        $leaveYear.on('change', function () {
            let selectedLeaveYear = $(this).val();
            leaveYearChange(selectedLeaveYear);
            //            let leaveList = document.allLeaveForReport[selectedLeaveYear];
            //            app.populateSelect($leaveId, leaveList, 'LEAVE_ID', 'LEAVE_ENAME', 'All Leaves', -1, -1);
        });


    });
})(window.jQuery, window.app);
