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
        var $salaryTypeId = $('#salaryTypeId');
        var $allSheetId = $('#allSheetId');
        var $allPayHeads = $('#allPayHeads');
		
		const deleteSalSheLink = data['links']['deleteEmpSalSheLink'];

console.log(data['ruleList']);

        app.populateSelect($salaryTypeId, data['salaryType'], 'SALARY_TYPE_ID', 'SALARY_TYPE_NAME', null, null, 1);
        app.populateSelect($allPayHeads, data['ruleList'], 'PAY_ID', 'PAY_EDESC');
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
				var acl = document.getAcl;
                if(acl['CONTROL'] == 'C'){
                    $companyId.val(acl['CONTROL_VALUES'][0]['VAL']);
                    document.getElementById("companyId").setAttribute("disabled", "disabled");
                }else{
                    console.log('Role is not company wise');
                }
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
				var acl = document.getAcl;
                console.log(acl);
                if(acl['CONTROL'] == 'C'){
					var groupListControl = [];
					
                    var companyWiseGroup = document.getCompanyWiseGroup;
                    if(companyWiseGroup[0]['GROUP_ID']){
                        $groupId.val(companyWiseGroup[0]['GROUP_ID']);
                    }     

					var totarrLength = (companyWiseGroup.length) - 1;
					if(totarrLength == 0) 
					{
						document.getElementById("groupId").setAttribute("disabled", "disabled");
					}
					
					if(totarrLength == 0) 
					{
						$.each(groupList, function (i, value) {
							if(companyWiseGroup[0]['GROUP_ID'] == value.GROUP_ID) {
								groupListControl.push({GROUP_ID: value.GROUP_ID, GROUP_NAME: value.GROUP_NAME});
							}
						});
					}
					
					if(totarrLength == 1) 
					{
						$.each(groupList, function (i, value) {
							if(companyWiseGroup[0]['GROUP_ID'] == value.GROUP_ID || companyWiseGroup[1]['GROUP_ID'] == value.GROUP_ID) {
								groupListControl.push({GROUP_ID: value.GROUP_ID, GROUP_NAME: value.GROUP_NAME});
							}
						});
					}
					
					if(totarrLength == 2) 
					{
						$.each(groupList, function (i, value) {
							if(companyWiseGroup[0]['GROUP_ID'] == value.GROUP_ID || companyWiseGroup[1]['GROUP_ID'] == value.GROUP_ID || companyWiseGroup[2]['GROUP_ID'] == value.GROUP_ID) {
								groupListControl.push({GROUP_ID: value.GROUP_ID, GROUP_NAME: value.GROUP_NAME});
							}
						});
					}
					
					if(totarrLength == 3) 
					{
						$.each(groupList, function (i, value) {
							if(companyWiseGroup[0]['GROUP_ID'] == value.GROUP_ID || companyWiseGroup[1]['GROUP_ID'] == value.GROUP_ID || companyWiseGroup[2]['GROUP_ID'] == value.GROUP_ID || companyWiseGroup[3]['GROUP_ID'] == value.GROUP_ID) {
								groupListControl.push({GROUP_ID: value.GROUP_ID, GROUP_NAME: value.GROUP_NAME});
							}
						});
					}

                    if(totarrLength == 4) 
					{
						$.each(groupList, function (i, value) {
							if(companyWiseGroup[0]['GROUP_ID'] == value.GROUP_ID || companyWiseGroup[1]['GROUP_ID'] == value.GROUP_ID || companyWiseGroup[2]['GROUP_ID'] == value.GROUP_ID || companyWiseGroup[3]['GROUP_ID'] == value.GROUP_ID) {
								groupListControl.push({GROUP_ID: value.GROUP_ID, GROUP_NAME: value.GROUP_NAME});
							}
						});
					}
					
					if(totarrLength == 5) 
					{
						$.each(groupList, function (i, value) {
							if(companyWiseGroup[0]['GROUP_ID'] == value.GROUP_ID || companyWiseGroup[1]['GROUP_ID'] == value.GROUP_ID || companyWiseGroup[2]['GROUP_ID'] == value.GROUP_ID || companyWiseGroup[3]['GROUP_ID'] == value.GROUP_ID || companyWiseGroup[4]['GROUP_ID'] == value.GROUP_ID) {
								groupListControl.push({GROUP_ID: value.GROUP_ID, GROUP_NAME: value.GROUP_NAME});
							}
						});
					}
					
					//console.log(groupListControl);
					
					app.populateSelect($groupId, groupListControl, 'GROUP_ID', 'GROUP_NAME', 'Select Group');
					
					//FOR selecting the group
					if(companyWiseGroup[0]['GROUP_ID']){
                        $groupId.val(companyWiseGroup[0]['GROUP_ID']);
                    } 
                    //document.getElementById("groupId").setAttribute("disabled", "disabled");
                }else{
                    console.log('Role is not company wise');
                }
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
        $allPayHeads.select2();

//        $viewBtn.hide();

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
//            if (selectedSalarySheetList.length > 0) {
//                $viewBtn.show();
//            } else {
//                $viewBtn.hide();
//            }
            $fromDate.val(selectedMonth['FROM_DATE']);
            $nepaliFromDate.val(nepaliDatePickerExt.fromEnglishToNepali(selectedMonth['FROM_DATE']));
            $toDate.val(selectedMonth['TO_DATE']);
            $nepaliToDate.val(nepaliDatePickerExt.fromEnglishToNepali(selectedMonth['TO_DATE']));
            
            groupChangeFn();

        };
        $month.on('change', function () {
            monthChangeAction();
        });

        $companyId.on('change', function () {
            monthChangeAction();
        });
        
        // salary sheet details start
         var $sheetTable = $('#sheetTable');
         
         
         var searchTable2 = function (kendoId, searchFields, isHidden) {
        var $kendoId = null;
        if (kendoId instanceof jQuery) {
            $kendoId = kendoId;
        } else {
            $kendoId = $("#" + kendoId);
        }
        var $searchHtml = $(`
            <div class='row search margin-bottom-5 margin-top-10' id='searchFieldDiv2'>
                <div class='col-xs-12 col-sm-6 col-md-4 col-lg-3'>
                    <input class='form-control' placeholder='search here' type='text' id='kendoSearchField2' />
                </div>
            </div>`);


        $searchHtml.insertBefore($kendoId);

        if (typeof isHidden !== "undefined" && isHidden) {
            $("#searchFieldDiv2").hide();
        }
        $("#kendoSearchField2").keyup(function () {
            var val = $(this).val();
            var filters = [];
            for (var i = 0; i < searchFields.length; i++) {
                filters.push({
                    field: searchFields[i],
                    operator: "contains",
                    value: val
                });
            }

            $kendoId.data("kendoGrid").dataSource.filter({
                logic: "or",
                filters: filters
            });
        });


    }
         
         
         searchTable2($table, ['EMPLOYEE_ID','EMPLOYEE_CODE','BRANCH_NAME','POSITION_NAME','ID_ACCOUNT_NO','EMPLOYEE_NAME']);
         
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
        app.initializeKendoGrid($sheetTable, [
            {field: "SHEET_NO", title: "Sheet", width: 80},
            {field: "MONTH_EDESC", title: "Month", width: 130},
            {field: "SALARY_TYPE_NAME", title: "Salary Type", width: 130},
            {field: "GROUP_NAME", title: "Group", width: 130},
//            {field: ["SHEET_NO"], title: "Action", width: 100,template: app.genKendoActionTemplate(actiontemplateConfigSheet)}
        ]);
        
        
        
        // salary sheet details end
        
        
        

//        $groupId.on('change.select2', function () {
        var $empTable = $('#employeeTable');
        app.searchTable($empTable, ['FULL_NAME', 'EMPLOYEE_CODE']);
        app.initializeKendoGrid($empTable, [
            {field: "CHECKED_FLAG",
                headerTemplate: "<input type='checkbox' id='header-chb2' class='k-checkbox2 header-checkbox2'><label class='k-checkbox-label2' for='header-chb2'></label>",
//                headerTemplate: "<input type='checkbox' id='header-chb'>",
                title: "Select", width: 50,
                sortable: false,
                filterable: false,
                template: "<input type='checkbox' class='k-checkbox2 row-checkbox2' #if(CHECKED_FLAG == 'Y'){#checked#}#><label class='k-checkbox-label2'></label>"},
//                template: '<input class="employeeCheck" type="checkbox" name="FLAG_#EMPLOYEE_CODE#" VALUE="Y" #if(CHECKED_FLAG == "Y"){#checked#}#>'},
            {field: "EMPLOYEE_CODE", title: "Code", width: 80},
            {field: "FULL_NAME", title: "Employee", width: 130}
        ]);

        function grid_dataBound(e) {
            $('.row-checkbox2').each(function (idx, item) {
                let checkStatus = $(this).prop("checked");
                if (checkStatus == true) {
                    let row = $(this).closest("tr");
                    row.addClass("k-state-selected");
                }
            });
        }
        var tt = $empTable.data("kendoGrid");
        tt.bind("dataBound", grid_dataBound);




        $empTable.on("click", ".k-checkbox2", function () {
            var checked = this.checked,
                    row = $(this).closest("tr"),
                    grid = $empTable.data("kendoGrid"),
                    dataItem = grid.dataItem(row);
            if (checked) {
                row.addClass("k-state-selected");
                dataItem.CHECKED_FLAG = 'Y';
            } else {
                row.removeClass("k-state-selected");
                dataItem.CHECKED_FLAG = 'N';
            }
        });

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
//            groupChangeFn();

        });
        
        var groupChangeFn=function(){
            let selectedGroups = $groupId.val();
            
            if(selectedGroups==null){
                let allGroup=[];
                $.each(groupList, function (key, value) {
//                    console.log(value);
                    allGroup.push(value['GROUP_ID']);
                });
                selectedGroups=allGroup;
            }
            let selectedSalaryTypeId = $salaryTypeId.val();
//            console.log('gval', $(this).val());
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
//                    console.log(empLoadData);
                    
//                    console.log(response);
                    app.populateSelect($allSheetId, response.sheetData, 'SHEET_NO', 'SHEET_NO', 'ALL', -1, -1);
                    app.renderKendoGrid($empTable, empLoadData);
                    app.renderKendoGrid($sheetTable, response.sheetData);
                });
            }
        }

        $salaryTypeId.on('change', function () {
            monthChangeAction();
        });

        
        var employeeIdColumn = {
            field: "EMPLOYEE_ID",
            title: "Id",
            width: 70
        };
        var employeeCodeColumn = {
            field: "EMPLOYEE_CODE",
            title: "Code",
            width: 80
        };
        var employeeBranchColumn = {
            field: "BRANCH_NAME",
            title: "Branch",
            width: 80
        };
        var employeePositionColumn = {
            field: "POSITION_NAME",
            title: "Position",
            width: 80
        };
        var employeeAccountColumn = {
            field: "ID_ACCOUNT_NO",
            title: "Acc",
            width: 100
        };
        var employeeNameColumn = {
            field: "EMPLOYEE_NAME",
            title: "Employee",
            width: 100
        };
        var actionColumn = {
            field: ["EMPLOYEE_ID", "SHEET_NO"],
            title: "Action",
            width: 50,
            template: `<a class="btn-edit hris-regenerate-salarysheet" title="Regenerate" sheet-no="#: SHEET_NO #" employee-id="#: EMPLOYEE_ID #" style="height:17px;"> <i class="fa fa-recycle"></i></a>
            <a class="btn-delete hris-delete-salary" title="Delete" sheet-no="#: SHEET_NO #" employee-id="#: EMPLOYEE_ID #" style="height:17px;"> <i class="fa fa-trash-o"></i></a>`
        };
        if (data.ruleList.length > 0) {
            employeeNameColumn.locked = true;
            actionColumn.locked = true;
            employeeIdColumn.locked = true;
            employeeCodeColumn.locked = true;
            employeeBranchColumn.locked = true;
            employeePositionColumn.locked = true;
//            employeeAccountColumn.locked = true;
        }
        
        var exportMap ;
        
        var generateCols =function(){
            
            exportMap = {
            "EMPLOYEE_ID": "Employee Id",
            "EMPLOYEE_CODE": "Employee Code",
            "EMPLOYEE_NAME": "Employee",
            "BRANCH_NAME": "Branch",
            "POSITION_NAME": "Position",
            "ID_ACCOUNT_NO": "Account No"
        };
        
          var columns = [
            employeeIdColumn,
            employeeCodeColumn,
            employeeNameColumn,
            employeeBranchColumn,
            employeePositionColumn,
            employeeAccountColumn,
            actionColumn
        ];
        
            let selectedPayheads = $allPayHeads.val();

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

                if (selectedPayheads == null) {
                    columns.push({field: "P_" + value['PAY_ID'], title: value['PAY_EDESC'] + "(" + signFn(value['PAY_TYPE_FLAG']) + ")", width: 150});
                    exportMap["P_" + value['PAY_ID']] = value['PAY_EDESC'] + "(" + signFn(value['PAY_TYPE_FLAG']) + ")";
                } else {
                    if ($.inArray(value['PAY_ID'], selectedPayheads) >= 0) {
                        columns.push({field: "P_" + value['PAY_ID'], title: value['PAY_EDESC'] + "(" + signFn(value['PAY_TYPE_FLAG']) + ")", width: 150});
                        exportMap["P_" + value['PAY_ID']] = value['PAY_EDESC'] + "(" + signFn(value['PAY_TYPE_FLAG']) + ")";
                    }
                }
            });
            app.initializeKendoGrid($table, columns);
        }
        
            generateCols();
//        app.initializeKendoGrid($table, columns);

        $viewBtn.on('click', function () {
            $table.empty();
            generateCols();
//            var sheetNoList = [];
//            for (var i in selectedSalarySheetList) {
//                sheetNoList.push(selectedSalarySheetList[i]['SHEET_NO']);
//            }

            let sheetNo=$allSheetId.val();
            let selectedGroups = $groupId.val();
            let selectedSalaryTypeId = $salaryTypeId.val();
            let selectedMonthId = selectedMonth['MONTH_ID'];
            
            if (selectedGroups !== null || selectedGroups !== '-1') {
                
            app.serverRequest(data['links']['viewLink'], {
                monthId: selectedMonthId,
                sheetNo: sheetNo,
                groupId: selectedGroups,
                salaryTypeId: selectedSalaryTypeId
            }).then(function (response) {
                app.renderKendoGrid($table, response.data);
            });
                
            }


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
            var salaryType = $salaryTypeId.val();
            if (group === null || group === '-1') {
                group = [];
                $.each(groupList, function (key, value) {
                    group.push(value['GROUP_ID']);
                });
            }
//            else {
//                group = [group];
//            }

            let empList = [];

            let empGridData = $empTable.data("kendoGrid").dataSource.data();

            $.each(empGridData, function (index, value) {
                if (value['CHECKED_FLAG'] == 'Y') {
                    empList.push(value['EMPLOYEE_ID']);
                }
            });
            console.log(empList);






            var stage1 = function () {
                app.pullDataById(data['links']['generateLink'], {
                    stage: stage,
                    monthId: monthId,
                    year: year,
                    monthNo: monthNo,
                    fromDate: fromDate,
                    toDate: toDate,
                    companyId: company,
                    groupId: group,
                    salaryTypeId: salaryType,
                    empList: empList
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
        
		// here for deleting individual salary
        $('#hris-page-content').on('click', '.hris-delete-salary', function () {
            var $this = $(this);
            var employeeId = $this.attr('employee-id');
            var sheetNo = $this.attr('sheet-no');
            var salarySheet = app.findOneBy(salarySheetList, {SHEET_NO: sheetNo});
            var monthId = salarySheet['MONTH_ID'];
            app.serverRequest(deleteSalSheLink, {
                employeeId: employeeId,
                sheetNo: sheetNo,
            }).then(function (response) {
                $viewBtn.trigger('click');
                response.success == true ? app.showMessage("Deleted successfully", 'success') : app.showMessage("Approved or locked sheet cannot be deleted.", 'error');
            }, function (error) {

            });
        });
        // here for deleting individual salary
        
        $('#hideEmpSheet').on('click',function(){
           $("#employeeTableDiv").toggle(); 
        });
        

    });
})(window.jQuery, window.app);


