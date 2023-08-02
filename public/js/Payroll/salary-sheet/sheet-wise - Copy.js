(function ($, app) {
    'use strict';
    $(document).ready(function () {
        app.startEndDatePickerWithNepali('nepaliFromDate', 'fromDate', 'nepaliToDate', 'toDate', null, true);

        var data = document.data;
        var salarySheetList = data['salarySheetList'];
        var monthList = null;
        var getSearchDataLink = data['links']['getSearchDataLink'];
        var getGroupListLink = data['links']['getGroupListLink'];
        var regenEmpSalSheLink = data['links']['regenEmpSalSheLink'];
        var loadingLogoLink = data['loading-icon'];
        var companyList = [];
        var groupList = [];
        var payrollProcess = null;
        var selectedSalarySheetList = [];
        var selectedMonth = {};

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
        var $salaryTypeId = $('#salaryTypeId');
        var $allSheetId = $('#allSheetId');
        var $bulkActionDiv = $('#bulkActionDiv');

        app.populateSelect($salaryTypeId, data['salaryType'], 'SALARY_TYPE_ID', 'SALARY_TYPE_NAME', null, null, 1);
        
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
        $salaryTypeId.select2();


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
            var salaryType = $salaryTypeId.val();
            for (var i in monthList) {
                if (monthList[i]['MONTH_ID'] == monthValue) {
                    selectedMonth = monthList[i];
                    break;
                }
            }
            selectedSalarySheetList = [];
            for (var i in salarySheetList) {
                if (salarySheetList[i]['MONTH_ID'] == monthValue &&
                        (companyValue == -1 || companyValue == salarySheetList[i]['COMPANY_ID'])
                        && (groupValue == -1 || groupValue == salarySheetList[i]['GROUP_ID'])
                        && (salaryType == -1 || salaryType == salarySheetList[i]['SALARY_TYPE_ID'])

                        ) {
                    selectedSalarySheetList.push(salarySheetList[i]);
                    if (groupValue != '-1') {
                        break;
                    }
                }
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
        
         var $sheetTable = $('#sheetTable');
         var actiontemplateConfigSheet = {
             update: {
                'ALLOW_UPDATE': 'N',
                'params': ["ADVANCE_ID"],
                'url': ''
            },
            delete: {
                'ALLOW_DELETE': 'Y',
                'params': ["SHEET_NO"],
                'url': document.deleteLink
            }
        };
        var grid = app.initializeKendoGrid($sheetTable, [
            {field: "SHEET_NO", title: "Sheet", width: 80},
            {field: "MONTH_EDESC", title: "Month", width: 130},
            {field: "SALARY_TYPE_NAME", title: "Salary Type", width: 130},
            {field: "GROUP_NAME", title: "Group", width: 130},
            {field: ["SHEET_NO"],
            title: "Action",
            width: 50,
            template: `<a class="btn-edit hris-regenerate-salarysheet-sheetWise" title="Regenerate" sheet-no="#: SHEET_NO #"  style="height:17px;"> <i class="fa fa-recycle"></i></a>`
            }
        ], null, {id: "SHEET_NO", atLast: false, fn: function (selected) {
                if (selected) {
                    $bulkActionDiv.show();
                } else {
                    $bulkActionDiv.hide();
                }
            }});
        
        function grid_dataBound(e) {
            $('.row-checkbox2').each(function (idx, item) {
                let checkStatus = $(this).prop("checked");
                if (checkStatus == true) {
                    let row = $(this).closest("tr");
                    row.addClass("k-state-selected");
                }
            });
        }

        $('#header-chb2').change(function (ev) {
            var checked = ev.target.checked;
            $('.row-checkbox2').each(function (idx, item) {
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

        $groupId.on("select2:select select2:unselect", function (event) {
            monthChangeAction();
        });
        
        var groupChangeFn=function(){
            let selectedGroups = $groupId.val();
            
            if(selectedGroups==null){
                let allGroup=[];
                $.each(groupList, function (key, value) {
                    allGroup.push(value['GROUP_ID']);
                });
                selectedGroups=allGroup;
            }
            let selectedSalaryTypeId = $salaryTypeId.val();
            if (selectedGroups !== null || selectedGroups !== '-1') {
                app.serverRequest(document.pullGroupEmployeeLink, {
                    group: selectedGroups,
                    monthId: selectedMonth['MONTH_ID'],
                    salaryTypeId: selectedSalaryTypeId
                }).then(function (response) {
                    let empLoadData=[];
                    
                    if ($groupId.val() == null) {
                        $.each(response.data, function (index, value) {
                            value['CHECKED_FLAG'] = 'N';
                            empLoadData.push(value);
                        });
                    } else {
                        empLoadData = response.data;
                    }
                    app.populateSelect($allSheetId, response.sheetData, 'SHEET_NO', 'SHEET_NO', 'ALL', -1, -1);
                    app.renderKendoGrid($sheetTable, response.sheetData);
                    $bulkActionDiv.hide();
                });
            }
        }

        $salaryTypeId.on('change', function () {
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
        //app.initializeKendoGrid($table, columns);

        $viewBtn.on('click', function () {
            groupChangeFn();
        });

        $('#excelExport').on('click', function () {
            app.excelExport($table, exportMap, 'Salary Sheet');
        });
        $('#pdfExport').on('click', function () {
            app.exportToPDF($table, exportMap, 'Salary Sheet');
        });



        $bulkActionDiv.bind("click", function () {
            var list = grid.getSelected();
            console.log(list);
            var action = $(this).attr('action');

            var selectedValues = [];
            for (var i in list) {
                selectedValues.push(list[i].SHEET_NO);
            }
            app.serverRequest(document.bulkDeleteLink, {data : selectedValues}).then(function (success) {
                $viewBtn.trigger('click');
            }, function (failure) {

            });
        });
        
        
        $('#sheetTable').on('click', '.hris-regenerate-salarysheet-sheetWise', function () {
            var $this = $(this);
//            var employeeId = $this.attr('employee-id');
            var sheetNo = $this.attr('sheet-no');
//            console.log(sheetNo);
            app.pullDataById(document.getEmployeeSheetWise, {
                sheetNo: sheetNo,
            }).then(function (response) {
                console.log(response);
//                $viewBtn.trigger('click');


//$.each(response.data, function( index, value ) {
////console.log(index,value);
//
//app.pullDataById(regenEmpSalSheLink, {
//                employeeId: value.EMPLOYEE_ID,
//                monthId: value.MONTH_ID,
//                sheetNo: value.SHEET_NO,
////                sheetNo: value.,
//            }).then(function (response) {
////                $viewBtn.trigger('click');
//            }, function (error) {
//
//            });
//
//
//});


 payrollProcess = (function (dataList) {
                    var play = true;
                    var counter = 0;
                    var length = dataList.length;
                    var recursionFn = function (data) {
                        app.pullDataById(regenEmpSalSheLink, {
                            employeeId: data.EMPLOYEE_ID,
                            monthId: data.MONTH_ID,
                            sheetNo: data.SHEET_NO,
                        }).then(function (response) {
                            var empCount = counter + 1;
                            loadingMessage = `Generating ${empCount} of ${length}`;
                            updateLoadingHtml();
                            counter++;
                            if (!response.success) {
                                console.log('sdfdsf');
//                                stage2Error(data, response.error);
                            }
                            if (counter >= length) {
                                loading_screen.finish();
                                $pleaseWaitOptions.hide();
//                                stage3();
                                groupChangeFn();
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
                })(response.data);





            }, function (error) {

            });
        });
        
        
    });
})(window.jQuery, window.app);


