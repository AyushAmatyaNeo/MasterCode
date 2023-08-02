(function ($, app) {
    'use strict';
    $(document).ready(function () {
        $('Select').select2();

        var monthList = null;
        var $fiscalYear = $('#fiscalYearId');
        var $reportType = $('#reportType');
        var $groupVariable = $('#groupVariable');
        var $table = $('#table');
        var map = {};
        var salaryData;
        var $companyId = $('#companyId');
        var $groupId = $('#groupId');
        var groupList = [];
        var data = document.data;
        var getGroupListLink = data['links']['getGroupListLink'];
        var salarySheetList = data['salarySheetList'];
        var selectedSalarySheetList = [];


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
                {field: "EMPLOYEE_CODE", title: "Code", width: 80, locked: true},
                {field: "FULL_NAME", title: "Employee", width: 120, locked: true},
                {field: "POSITION_NAME", title: "Position", width: 100, locked: true},
                {field: "SERVICE_TYPE_NAME", title: "Service", width: 100, locked: true},
                {field: "MONTH_EDESC", title: "Month", width: 80, locked: true},
                {field: "SALARY_TYPE_NAME", title: "Salary Type", width: 80, locked: true},
                {field: "BANK_NAME", title: "Bank Name", width: 100, locked: true},
                {field: "ACCOUNT_NO", title: "Account No.", width: 120, locked: true}
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
                dataSchemaCols[value['VARIANCE']] = {type: "number"};
                aggredCols.push({field: value['VARIANCE'], aggregate: "sum"});
            });

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
                                    {value: document.preference.companyName, colSpan: columns.length, textAlign: "left"}
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

        app.searchTable($table, ['EMPLOYEE_CODE', 'FULL_NAME']);

        $('#searchEmployeesBtn').on('click', function () {
            var q = document.searchManager.getSearchValues();
            q['fiscalId'] = $fiscalYear.val();
            q['groupVariable'] = $groupVariable.val();
            q['groupId'] = $groupId.val();

            app.serverRequest(document.pullEmpWiseGroupSheetLink, q).then(function (response) {
                if (response.success) {
                    salaryData = response.data;
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
            app.excelExport($table, map, 'Employee Wise GroupSheet.xlsx');
        });
        $('#pdfExport').on('click', function () {
            app.exportToPDF($table, map, 'Employee Wise GroupSheet.pdf', 'A1');
        });

        var groupChangeFn = function () {
            let selectedGroup = $groupId.val();

            if (selectedGroup == null) {
                let allGroup = [];
                $.each(groupList, function (key, value) {
//                    console.log(value);
                    allGroup.push(value['GROUP_ID']);
                });
                selectedGroup = allGroup;
            }
            // let selectedSalaryTypeId = $salaryTypeId.val();
//            console.log('gval', $(this).val());
        }

        $companyId.on('change', function () {
            groupChangeFn();
        });

        // $salaryTypeId.on('change', function () {
        //     groupChangeFn();
        // });
        // (function ($groupId, link) {
        //     var onDataLoad = function (data) {
        //         groupList = data;
        //         app.populateSelect($groupId, groupList, 'GROUP_ID', 'GROUP_NAME', 'Select Group');
		// 		var acl = document.acl;
        //         console.log(acl);
        //         if(acl['CONTROL'] == 'C'){
        //             var companyWiseGroup = document.getCompanyWiseGroup;
        //             if(companyWiseGroup[0]['GROUP_ID']){
        //                 $groupId.val(companyWiseGroup[0]['GROUP_ID']);
        //             }                    
        //             document.getElementById("groupId").setAttribute("disabled", "disabled");
        //         }else{
        //             console.log('Role is not company wise');
        //         }
        //     };
        //     app.serverRequest(link, {}).then(function (response) {
        //         if (response.success) {
        //             onDataLoad(response.data);
        //         }
        //     }, function (error) {

        //     });
        // })($groupId, getGroupListLink);

        $groupId.select2();
        // $salaryTypeId.select2();

        $groupId.on("select2:select select2:unselect", function (event) {
            groupChangeFn();
        });
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
							if(companyWiseGroup[0]['GROUP_ID'] == value.GROUP_ID || companyWiseGroup[1]['GROUP_ID'] == value.GROUP_ID || companyWiseGroup[2]['GROUP_ID'] == value.GROUP_ID || companyWiseGroup[3]['GROUP_ID'] == value.GROUP_ID || companyWiseGroup[4]['GROUP_ID'] == value.GROUP_ID) {
								groupListControl.push({GROUP_ID: value.GROUP_ID, GROUP_NAME: value.GROUP_NAME});
							}
						});
					}
					
					if(totarrLength == 5) 
					{
						$.each(groupList, function (i, value) {
							if(companyWiseGroup[0]['GROUP_ID'] == value.GROUP_ID || companyWiseGroup[1]['GROUP_ID'] == value.GROUP_ID || companyWiseGroup[2]['GROUP_ID'] == value.GROUP_ID || companyWiseGroup[3]['GROUP_ID'] == value.GROUP_ID || companyWiseGroup[4]['GROUP_ID'] == value.GROUP_ID || companyWiseGroup[5]['GROUP_ID'] == value.GROUP_ID ) {
								groupListControl.push({GROUP_ID: value.GROUP_ID, GROUP_NAME: value.GROUP_NAME});
							}
						});
					}
                    
					if(totarrLength == 6) 
					{
						$.each(groupList, function (i, value) {
							if(companyWiseGroup[0]['GROUP_ID'] == value.GROUP_ID || companyWiseGroup[1]['GROUP_ID'] == value.GROUP_ID || companyWiseGroup[2]['GROUP_ID'] == value.GROUP_ID || companyWiseGroup[3]['GROUP_ID'] == value.GROUP_ID || companyWiseGroup[4]['GROUP_ID'] == value.GROUP_ID || companyWiseGroup[5]['GROUP_ID'] == value.GROUP_ID || companyWiseGroup[6]['GROUP_ID'] == value.GROUP_ID) {
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
    });
})(window.jQuery, window.app);


