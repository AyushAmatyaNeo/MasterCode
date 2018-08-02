(function ($, app) {
    'use strict';
    $(document).ready(function () {
        app.startEndDatePickerWithNepali('nepaliFromDate', 'fromDate', 'nepaliToDate', 'toDate', null, true);

        var data = document.data;
        var salarySheetList = data['salarySheetList'];
        var monthList = null;
        var generateLink = data['links']['generateLink'];
        var getSalarySheetListLink = data['links']['getSalarySheetListLink'];
        var getSearchDataLink = data['links']['getSearchDataLink'];
        var getGroupListLink = data['links']['getGroupListLink'];
        var regenEmpSalSheLink = data['links']['regenEmpSalSheLink'];
        var loadingLogoLink = data['loading-icon'];
        var companyList = [];
        var groupList = [];
        var payrollProcess = null;
        var selectedSalarySheetList = [];
//        
        var selectedMonth = {};
//
        var $fiscalYear = $('#fiscalYearId');
        var $month = $('#monthId');
        var $table = $('#table');
        var $fromDate = $('#fromDate');
        var $nepaliFromDate = $('#nepaliFromDate');
        var $toDate = $('#toDate');
        var $nepaliToDate = $('#nepaliToDate');
        var $viewBtn = $('#viewBtn');
        var $generateBtn = $('#generateBtn');
        var $companyId = $('#companyId');
        var $groupId = $('#groupId');
//        
        var loading_screen = null;
        var loadingMessage = "Payroll generation started.";
        var loadingHtml = '<div class="sk-spinner sk-spinner-wandering-cubes"><div class="sk-cube1"></div><div class="sk-cube2"></div></div>';
        var $pleaseWaitOptions = $('#please-wait-options');
        var $cancelBtn = $('#cancelBtn');
        var $pauseBtn = $('#pauseBtn');
        var updateLoadingHtml = function () {
            loading_screen.updateOptions({
                loadingHtml: "<p class='loading-message'>" + loadingMessage + "</p>" + loadingHtml
            });
        };
        $pleaseWaitOptions.hide();
        $cancelBtn.on('click', function () {
            loading_screen.finish();
            $pleaseWaitOptions.hide();
        });
        $pauseBtn.on('click', function () {
            var $this = $(this);
            var action = $this.attr('action');
            switch (action) {
                case 'pause':
                    payrollProcess.pause();
                    $this.attr('action', "play");
                    $this.html("Play");
                    break;
                case 'play':
                    payrollProcess.play();
                    $this.attr('action', "pause");
                    $this.html("Pause");
                    break;
            }
        });

//
        (function ($companyId, link) {
            var onDataLoad = function (data) {
                companyList = data['company'];
                app.populateSelect($companyId, data['company'], 'COMPANY_ID', 'COMPANY_NAME', 'Select Company');
            };
            app.serverRequest(link, {}).then(function (response) {
                if (response.success) {
                    onDataLoad(response.data);
                }
            }, function (error) {

            });
        })($companyId, getSearchDataLink);

        (function ($groupId, link) {
            var onDataLoad = function (data) {
                groupList = data;
                app.populateSelect($groupId, groupList, 'GROUP_ID', 'GROUP_NAME', 'Select Group');
            };
            app.serverRequest(link, {}).then(function (response) {
                if (response.success) {
                    onDataLoad(response.data);
                }
            }, function (error) {

            });
        })($groupId, getGroupListLink);

        $fiscalYear.select2();
        $month.select2();
        $companyId.select2();
        $groupId.select2();

        $viewBtn.hide();

        app.setFiscalMonth($fiscalYear, $month, function (years, months, currentMonth) {
            monthList = months;
        });
        var monthChangeAction = function () {
            var monthValue = $month.val();
            if (monthValue === null || monthValue == '') {
                return;
            }
            var companyValue = $companyId.val();
            var groupValue = $groupId.val();
            for (var i in monthList) {
                if (monthList[i]['MONTH_ID'] == monthValue) {
                    selectedMonth = monthList[i];
                    break;
                }
            }
            selectedSalarySheetList = [];
            for (var i in salarySheetList) {
                if (salarySheetList[i]['MONTH_ID'] == monthValue && (companyValue == -1 || companyValue == salarySheetList[i]['COMPANY_ID']) && (groupValue == -1 || groupValue == salarySheetList[i]['GROUP_ID'])) {
                    selectedSalarySheetList.push(salarySheetList[i]);
                    break;
                }
            }
            if (selectedSalarySheetList.length > 0) {
                $viewBtn.show();
            } else {
                $viewBtn.hide();
            }
            $fromDate.val(selectedMonth['FROM_DATE']);
            $nepaliFromDate.val(nepaliDatePickerExt.fromEnglishToNepali(selectedMonth['FROM_DATE']));
            $toDate.val(selectedMonth['TO_DATE']);
            $nepaliToDate.val(nepaliDatePickerExt.fromEnglishToNepali(selectedMonth['TO_DATE']));

        };
        $month.on('change', function () {
            monthChangeAction();
        });

        $companyId.on('change', function () {
            monthChangeAction();
        });

        $groupId.on('change', function () {
            monthChangeAction();
        });

        var exportMap = {
            "EMPLOYEE_ID": "Employee Id",
            "EMPLOYEE_CODE": "Employee Code",
            "EMPLOYEE_NAME": "Employee",
            "BRANCH_NAME": "Branch",
            "POSITION_NAME": "Position",
            "ID_ACCOUNT_NO": "Account No"
        };
        var employeeIdColumn = {
            field: "EMPLOYEE_ID",
            title: "Id",
            width: 50
        };
        var employeeCodeColumn = {
            field: "EMPLOYEE_CODE",
            title: "Code",
            width: 80
        };
        var employeeBranchColumn = {
            field: "BRANCH_NAME",
            title: "Branch",
            width: 100
        };
        var employeePositionColumn = {
            field: "POSITION_NAME",
            title: "Position",
            width: 100
        };
        var employeeAccountColumn = {
            field: "ID_ACCOUNT_NO",
            title: "Acc",
            width: 70
        };
        var employeeNameColumn = {
            field: "EMPLOYEE_NAME",
            title: "Employee",
            width: 150
        };
        var actionColumn = {
            field: ["EMPLOYEE_ID", "SHEET_NO"],
            title: "Action",
            width: 50,
            template: `<a class="btn-edit hris-regenerate-salarysheet" title="Regenerate" sheet-no="#: SHEET_NO #" employee-id="#: EMPLOYEE_ID #" style="height:17px;"> <i class="fa fa-recycle"></i></a>`
        };
        if (data.ruleList.length > 0) {
            employeeNameColumn.locked = true;
            actionColumn.locked = true;
            employeeIdColumn.locked = true;
            employeeCodeColumn.locked = true;
            employeeBranchColumn.locked = true;
            employeePositionColumn.locked = true;
            employeeAccountColumn.locked = true;
        }
        var columns = [
            employeeIdColumn,
            employeeCodeColumn,
            employeeNameColumn,
            employeeBranchColumn,
            employeePositionColumn,
            employeeAccountColumn,
            actionColumn
        ];

        $.each(data.ruleList, function (key, value) {
            var signFn = function ($type) {
                var sign = "";
                switch ($type) {
                    case "A":
                        sign = "+";
                        break;
                    case "D":
                        sign = "-";
                        break;
                    case "V":
                        sign = ".";
                        break;
                }
                return sign;
            };
            columns.push({field: "P_" + value['PAY_ID'], title: value['PAY_EDESC'] + "(" + signFn(value['PAY_TYPE_FLAG']) + ")", width: 150});
            exportMap["P_" + value['PAY_ID']] = value['PAY_EDESC'] + "(" + signFn(value['PAY_TYPE_FLAG']) + ")";
        });
        app.initializeKendoGrid($table, columns);

        $viewBtn.on('click', function () {
            var sheetNoList = [];
            for (var i in selectedSalarySheetList) {
                sheetNoList.push(selectedSalarySheetList[i]['SHEET_NO']);
            }

            app.serverRequest(data['links']['viewLink'], {sheetNo: sheetNoList}).then(function (response) {
                app.renderKendoGrid($table, response.data);
            });
        });
        $generateBtn.on('click', function () {
            payrollGeneration();
        });


        var payrollGeneration = function () {
            var stage = 1;
            var monthId = selectedMonth['MONTH_ID'];
            var year = selectedMonth['YEAR'];
            var monthNo = selectedMonth['MONTH_NO'];
            var fromDate = selectedMonth['FROM_DATE'];
            var toDate = selectedMonth['TO_DATE'];
            var company = $companyId.val();
            if (company === null || company === '-1') {
                company = [];
                $.each(companyList, function (key, value) {
                    company.push(value['COMPANY_ID']);
                });
            } else {
                company = [company];
            }
            var group = $groupId.val();
            if (group === null || group === '-1') {
                group = [];
                $.each(groupList, function (key, value) {
                    group.push(value['GROUP_ID']);
                });
            } else {
                group = [group];
            }
            var stage1 = function () {
                app.pullDataById(data['links']['generateLink'], {
                    stage: stage,
                    monthId: monthId,
                    year: year,
                    monthNo: monthNo,
                    fromDate: fromDate,
                    toDate: toDate,
                    companyId: company,
                    groupId: group
                }).then(function (response) {
                    stage2(response.data);
                }, function (error) {

                });
            };
            stage1();
            var sheetNo = null;
            var employeeList = null;

            var stage2 = function (data) {
                var dataList = [];
                for (var x in data) {
                    sheetNo = data[x]['sheetNo'];
                    employeeList = data[x]['employeeList'];
                    for (var i in employeeList) {
                        dataList.push({
                            stage: 2,
                            sheetNo: sheetNo,
                            monthId: monthId,
                            year: year,
                            monthNo: monthNo,
                            fromDate: fromDate,
                            toDate: toDate,
                            employeeId: employeeList[i]['EMPLOYEE_ID']
                        });
                    }

                }
                payrollProcess = (function (dataList) {
                    var play = true;
                    var counter = 0;
                    var length = dataList.length;
                    var recursionFn = function (data) {
                        app.pullDataById(generateLink, data).then(function (response) {
                            var empCount = counter + 1;
                            loadingMessage = `Generating ${empCount} of ${length}`;
                            updateLoadingHtml();
                            counter++;
                            if (!response.success) {
                                stage2Error(data, response.error);
                            }
                            if (counter >= length) {
                                loading_screen.finish();
                                $pleaseWaitOptions.hide();
                                stage3();
                                return;
                            }
                            if (play) {
                                recursionFn(dataList[counter]);
                            }
                        }, function (error) {
                            stage2Error(data, error);
                        });
                    };
                    loading_screen = pleaseWait({
                        logo: loadingLogoLink,
                        backgroundColor: '#f46d3b',
                        loadingHtml: "<p class='loading-message'>" + loadingMessage + "</p>" + loadingHtml
                    });
                    $pleaseWaitOptions.show();
                    recursionFn(dataList[counter]);
                    return {
                        pause: function () {
                            play = false;
                        },
                        play: function () {
                            play = true;
                            recursionFn(dataList[counter]);
                        }
                    }
                })(dataList);
            };
            var stage2Error = function (data, error) {
                app.showMessage(error, 'error');
            };
            var stage3 = function () {
                app.serverRequest(getSalarySheetListLink, {}).then(function (response) {
                    salarySheetList = response.data;
                    monthChangeAction($month.val());
                }, function (error) {

                });
            };

        };



        $('#excelExport').on('click', function () {
            app.excelExport($table, exportMap, 'Salary Sheet');
        });
        $('#pdfExport').on('click', function () {
            app.exportToPDF($table, exportMap, 'Salary Sheet');
        });

        $('#hris-page-content').on('click', '.hris-regenerate-salarysheet', function () {
            var $this = $(this);
            var employeeId = $this.attr('employee-id');
            var sheetNo = $this.attr('sheet-no');
            var salarySheet = app.findOneBy(salarySheetList, {SHEET_NO: sheetNo});
            var monthId = salarySheet['MONTH_ID'];
            app.serverRequest(regenEmpSalSheLink, {
                employeeId: employeeId,
                monthId: monthId,
                sheetNo: sheetNo,
            }).then(function (response) {
                $viewBtn.trigger('click');
            }, function (error) {

            });
        });

    });
})(window.jQuery, window.app);


