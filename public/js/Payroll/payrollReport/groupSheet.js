(function ($, app) {
    'use strict';
    $(document).ready(function () {
        $('Select').select2();

        var monthList = null;
        var $fiscalYear = $('#fiscalYearId');
        var $month = $('#monthId');
        var $reportType = $('#reportType');
        var $otVariable = $('#otVariable');
        var $extraFields = $('#extraFields');
        var $groupVariable = $('#groupVariable');
        var $table = $('#table');
        var $salaryTypeId = $('#salaryTypeId');
        var $orderBy = $('#orderBy');
        var map = {};
        var exportType = {
            "ACCOUNT_NO": "STRING",
        };
        var salaryData;
        var selectedMonthDetails;
        var subTotal={};
        var grandTotalHideUnHide={};
        var grandTotal={};
        var printHeads={};
        var breakCount=40;
        var printFont=11;
        var printPixel=1330;
		var $allSheetId = $('#sheetNo');
        var $companyId = $('#companyId');
        var $groupId = $('#groupId');
        var groupList = [];
        var selectedMonth = {};
        var data = document.data;
        var getGroupListLink = data['links']['getGroupListLink'];
        var salarySheetList = data['salarySheetList'];
        var selectedSalarySheetList = [];
        

        var extraFieldsList = [
            {ID: "DESIGNATION_TITLE", VALUE: "Designation"},
            {ID: "DEPARTMENT_NAME", VALUE: "Department"},
            {ID: "FUNCTIONAL_TYPE_EDESC", VALUE: "Functional Type"},
            {ID: "ACCOUNT_NO", VALUE: "Account No"},
            {ID: "BIRTH_DATE", VALUE: "Birth Date"},
            {ID: "JOIN_DATE", VALUE: "Join Date"},
             {ID: "ID_PAN_NO", VALUE: "Pan No"},
             {ID: "BRANCH_NAME", VALUE: "Branch Name"},
             {ID: "ID_ACCOUNT_NO", VALUE: "Account No"}
        ];

        app.setFiscalMonth($fiscalYear, $month, function (years, months, currentMonth) {
            monthList = months;
        });

        app.populateSelect($otVariable, document.nonDefaultList, 'VARIANCE_ID', 'VARIANCE_NAME', '---', '');
        app.populateSelect($groupVariable, document.groupVariables, 'VARIANCE_ID', 'VARIANCE_NAME', '---', '');
        app.populateSelect($extraFields, extraFieldsList, 'ID', 'VALUE', '---', '');
        
        //app.populateSelect($salaryTypeId, document.salaryType, 'SALARY_TYPE_ID', 'SALARY_TYPE_NAME', 'All',-1,-1);
		app.populateSelect($salaryTypeId, document.salaryType, 'SALARY_TYPE_ID', 'SALARY_TYPE_NAME', null, null, 1);

        var initKendoGrid = function (defaultColumns, otVariables, extraVariable, data) {
            let dataSchemaCols = {};
            let aggredCols = [];
            $table.empty();
            map = {
                'EMPLOYEE_ID' : 'ID',
                'FULL_NAME': 'Employee',
                'EMPLOYEE_CODE': 'EID',
                'POSITION_NAME': 'Position',
                'SERVICE_TYPE_NAME': 'Service',
                'BANK_NAME': 'Bank Name',
                'ID_ACCOUNT_NO': 'Account No.',
            }

            var columns = [
                {field: "EMPLOYEE_ID", title: "ID", width: 80, locked: true},
                {field: "EMPLOYEE_CODE", title: "Code", width: 80, locked: true},
                {field: "FULL_NAME", title: "Employee", width: 120, locked: true},
                {field: "POSITION_NAME", title: "Position", width: 100, locked: true},
                {field: "SERVICE_TYPE_NAME", title: "Service", width: 80, locked: true},
                {field: "BANK_NAME", title: "Bank Name", width: 100, locked: true},
                {field: "ID_ACCOUNT_NO", title: "Account No.", width: 120, locked: true},
            ];

            $.each(extraVariable, function (index, value) {
                for (var i in extraFieldsList) {
                    if (extraFieldsList[i]['ID'] == value) {
                        columns.push({
                            field: value,
                            title: extraFieldsList[i]['VALUE'],
                            width: 100
                        });
                        map[value] = extraFieldsList[i]['VALUE'];
                    }
                }
            });
            
            $.each(defaultColumns, function (index, value) {
                columns.push({
                    field: value['VARIANCE'],
                    title: value['VARIANCE_NAME'],
                    width: 100,
                    aggregates: ["sum"],
                    //footerTemplate: "#=sum||''#"
					footerTemplate: "#=kendo.toString(sum,'0.00')#"
                
                });
                map[value['VARIANCE']] = value['VARIANCE_NAME'];
                dataSchemaCols[value['VARIANCE']] = {type: "number"};
                aggredCols.push({field: value['VARIANCE'], aggregate: "sum"});
            });
            
            $.each(otVariables, function (index, value) {
                for (var i in document.nonDefaultList) {
                    if (document.nonDefaultList[i]['VARIANCE_ID'] == value) {
                        columns.push({
                            field: 'V' + value,
                            title: document.nonDefaultList[i]['VARIANCE_NAME'],
                            width: 100,
                            aggregates: ["sum"],
                            footerTemplate: "#=sum||''#"
                        });
                        map['V' + value] = document.nonDefaultList[i]['VARIANCE_NAME'];
                        dataSchemaCols['V' + value] = {type: "number"};
                        aggredCols.push({field: 'V' + value, aggregate: "sum"});
                    }
                }
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
                    fileName: "Group Sheet Report.xlsx",
                    filterable: false,
                    allPages: true
                },
                excelExport: function(e) {
                    var rows = e.workbook.sheets[0].rows;
                    var columns = e.workbook.sheets[0].columns;
					const salaryTypes = document.salaryType;
                    const salaryType = salaryTypes.filter(salaryType => salaryType.SALARY_TYPE_ID == selectedSalarySheetList[0].SALARY_TYPE_ID);
                    
                    
                    rows.unshift({
                        cells: [
                        {value: salaryType[0].SALARY_TYPE_NAME + " Salary Sheet of " + selectedMonthDetails.MONTH_EDESC+ " ," + selectedMonthDetails.YEAR + " ", colSpan: columns.length, textAlign: "left"}
                        ]
                    });
                    if(document.preference != undefined){
                        if(document.preference.companyAddress != null){
                            rows.unshift({
                                cells: [
                                {value: document.preference.companyAddress, colSpan: columns.length, textAlign: "left"}
                                ]
                            });
                        }
                    }
                    if(document.preference != undefined){
                        if(document.preference.companyName != null){
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
					refresh:true,
					pageSizes:true,
                    input: true,
                    numeric: false
                },
                columns: columns
            });
            
            renderForPrint(map,data);
            
        }
        
        $('#unhideAllZero').on('click', function () {
            $.each(grandTotalHideUnHide, function (i, v) {
                let getStringNumber = i.substr(1);
                let columHeaderId = '#' + i;
                if (!isNaN(getStringNumber)) {
                    $(columHeaderId).prop("checked", false);
                }
            });
        });

        $('#hideAllZero').on('click', function () {
//            console.log('clicked', grandTotal);
            $.each(grandTotalHideUnHide, function (i, v) {
                let getStringNumber = i.substr(1);
                let columHeaderId = '#' + i;
                if (!isNaN(getStringNumber)) {
                    if (parseInt(v) == 0)
                    {
                        $(columHeaderId).prop("checked", true);
                    }
                }
            });
        });
        
        var renderForPrint = function (map, data) {
            let snCount = 1;
            let appendData = ``;

            var printEditor = $('#printEditor');
            printEditor.empty();
            grandTotal={};
            appendData = '<style> #printEditor table th { font-size: ' + printFont + 'px; } #printEditor table td { font-size: ' + printFont + 'px; }</style>';
            appendData += '<table style="white-space: nowrap;" class="table table-bordered table-striped table-condensed" id="testTable">';

//     for heading checbox only start
            appendData += '<tr><th></th>';

            $.each(map, function (index, value) {
                grandTotal[index] = 0;
                let colLock = (index == 'EMPLOYEE_CODE' || index == 'FULL_NAME') ? '' : '';
                let colMargin = (index == 'FULL_NAME') ? '' : '';
                appendData += '<th style="white-space: normal;" ' + colLock + ' ' + colMargin + '  > <input type="checkbox" id="' + index + '"> </th>';
            });
//            console.log(grandTotal);
            appendData += '</tr>';
//     for heading checbox only end
            
            
//     for heading start
            appendData += '<tr><th id="SN_HEAD">SN</th>';

            $.each(map, function (index, value) {
                grandTotal[index] = 0;
                let colLock = (index == 'EMPLOYEE_CODE' || index == 'FULL_NAME') ? '' : '';
                let colMargin = (index == 'FULL_NAME') ? '' : '';
//                let colLock = (index == 'EMPLOYEE_CODE' || index == 'FULL_NAME') ? 'class="freeze"' : '';
//                let colMargin = (index == 'FULL_NAME') ? 'style="left:70px;"' : '';
//                appendData += '<th style="white-space: normal;" ' + colLock + ' ' + colMargin + ' id="' + index + '_HEAD" > <input type="checkbox" id="' + index + '"> ' + value + '</th>';
                appendData += '<th style="white-space: normal;" ' + colLock + ' ' + colMargin + ' id="' + index + '_HEAD"  > ' + value + '</th>';
            });
//     for heading end


            $.each(data, function (index, value) {
                appendData += '<tr>';
                appendData += '<td class="freeze" >' + snCount + '</td>';
                $.each(map, function (i, v) {
                    grandTotal[i] = grandTotal[i] + parseFloat(value[i]);
                    let colLock = (i == 'EMPLOYEE_CODE' || i == 'FULL_NAME') ? 'class="freeze"' : '';
//                    let colMargin = (i == 'FULL_NAME') ? 'style="left:20px;"' : '';
                    let colMargin = '';
                    if (i == 'FULL_NAME') {
                        colMargin = 'style="left:20px;"';
                    } else if (i == 'EMPLOYEE_CODE') {
                        colMargin = 'style="left:175px;"';
                    }

                    appendData += '<td ' + colLock + ' ' + colMargin + '>' + value[i] + '</td>';
                });
                appendData += '</tr>';
                snCount++;
            });

            appendData += '<tr><td></td>';
            $.each(grandTotal, function (i, v) {
                let getStringNumber = i.substr(1);
                let colLock = (i == 'EMPLOYEE_CODE' || i == 'FULL_NAME') ? 'class="freeze"' : '';
                let colMargin = (i == 'FULL_NAME') ? 'style="left:20px;"' : '';
                let printTotal = (i == 'FULL_NAME') ? 'Total' : '';
                if (isNaN(getStringNumber)) {
                    appendData += '<td ' + colLock + ' ' + colMargin + '>' + printTotal + '</td>';
                } else {
                    appendData += '<td ><b>' + parseFloat(v).toFixed(2) + '</b></td>';
                }
            });
            appendData += '</tr>';


            appendData += '</table>';
            printEditor.append(appendData);
//            console.log(grandTotal);
grandTotalHideUnHide=JSON.parse(JSON.stringify(grandTotal));
        }
        
        
           
        app.searchTable($table, ['EMPLOYEE_CODE','FULL_NAME']);

        $('#searchEmployeesBtn').on('click', function () {
            breakCount=$("#printBreakUp").val();
            printFont=$("#printFontSize").val();
            printPixel=$("#printPixel").val();
            var q = document.searchManager.getSearchValues();
            q['fiscalId'] = $fiscalYear.val();
            q['monthId'] = $month.val();
            q['extVar'] = $otVariable.val();
            q['extField'] = $extraFields.val();
            q['reportType'] = $reportType.val();
            q['groupVariable'] = $groupVariable.val();
            q['salaryTypeId'] = $salaryTypeId.val();
            q['orderBy'] = $orderBy.val();
			q['sheetNo'] = $allSheetId.val();
            q['groupId'] = $groupId.val();

            app.serverRequest(document.pullGroupSheetLink, q).then(function (response) {
                if (response.success) {
                    salaryData=response.data;
                    selectedMonthDetails=response.monthDetails;
                    if(q['reportType']=='GS'){
                    initKendoGrid(response.columns, $otVariable.val(), $extraFields.val(), response.data);
                }else if(q['reportType']=='GD'){
                    initKendoGrid(response.columns, [], $extraFields.val(), response.data);
                }
                    //app.renderKendoGrid($table, response.data);
                } else {
                    app.showMessage(response.error, 'error');
                }
            }, function (error) {
                app.showMessage(error, 'error');
            });
        });

        $('#excelExport').on('click', function () {
            app.excelExportModified($table, map, 'GroupSheet.xlsx',exportType);
        });
        $('#pdfExport').on('click', function () {
            app.exportToPDF($table, map, 'GroupSheet.pdf','A1');
        });
        
        

        $('#printGroupSheet').on('click', function () {
            let headerHeight=$('#SN_HEAD').height();
            let maxTableWidth = printPixel;
            let printColumns = JSON.parse(JSON.stringify(map));
            let tablesArr = [];
            let totalTableWidth = $('#SN_HEAD').width();
            let tempWidth = $('#SN_HEAD').width();
            let allTableHead = {};
            let tempHeadArray = {};
            let tableNo = 1;
            let headCount = 1;
            grandTotal = {};
            $('table input[type=checkbox]').each(function (i, obj) {

                let toDeletId = $(obj).attr("id");
                if ($(obj).is(":checked")) {
                    delete printColumns[toDeletId];
                } else {
                    grandTotal[toDeletId] = 0;
//                    allTableHead[tableNo][toDeletId]=printColumns[toDeletId];

                    let colWidth = $('#' + toDeletId + '_HEAD').width();
                    totalTableWidth += colWidth;
                    tempWidth += colWidth;
//                    console.log(tempWidth);
                    tempHeadArray[toDeletId] = printColumns[toDeletId];
                    if (tempWidth > maxTableWidth || headCount == $('table input[type=checkbox]').length) {
                        allTableHead[tableNo] = tempHeadArray;
                        tempHeadArray = {};
                        tempHeadArray['FULL_NAME'] = 'Employee';
                        let snWidth = $('#SN_HEAD').width();
                        let EmployeeNameWidth = $('#FULL_NAME_HEAD').width();
                        tempWidth = snWidth + EmployeeNameWidth + colWidth;
                        tablesArr.push(tablesArr.length + 1);
                        tableNo++;
                    }
                }
                headCount++;
            });

            let snCount = 1;
            let appendData = ``;
            var finalPrint = $('#finalPrint');
            finalPrint.empty();
            let totalRows = salaryData.length;
            let startloop = 0;
            let endloop = breakCount;
            let loopCounter = 0;
            let continueLoop = true;
            while (continueLoop) {
                $.each(allTableHead, function (index, value) {
                    let pageHeads = value;

                    subTotal = {};
                    appendData += '<div style="break-after:page"></div>';
                    appendData += '<div class="header-space">' + document.preference.companyName +'</br> Salary Sheet '+selectedMonthDetails.MONTH_EDESC+' '+ selectedMonthDetails.YEAR +'</div></br>';
                    appendData += '<table style="white-space:nowrap; table-layout: fixed;">';
                    let colWidth = $('#SN_HEAD').width() + 'px';
                    appendData += '<tr><th style="width:' + colWidth + '; line-height:'+headerHeight+'px;">SN</th>';

                    $.each(pageHeads, function (i, v) {
                        subTotal[i] = 0;
                        let colWidth = $('#' + i + '_HEAD').width() + 'px';
                        appendData += '<th style=" white-space: normal; width:' + colWidth + '; height:'+headerHeight+'px;" >' + v + '</th>';
                    });
                    appendData += '</tr>';

                    snCount = startloop + 1;
                    for (loopCounter = startloop; loopCounter < endloop; loopCounter++) {
                        if (loopCounter < totalRows) {

                            let currentData = salaryData[loopCounter];
//                            console.log(currentData);

                            appendData += '<tr>';
                            appendData += '<td>' + snCount + '</td>';

                            $.each(pageHeads, function (i, v) {
                                grandTotal[i] = grandTotal[i] + parseFloat(currentData[i]);
                                subTotal[i] = subTotal[i] + parseFloat(currentData[i]);
                                appendData += '<td>' + currentData[i] + '</td>';
                            });
                            appendData += '</tr>';



                            snCount++;
                        }
                    }

                    appendData += '<tr><td></td>';
                    $.each(subTotal, function (i, v) {
                        let getStringNumber = i.substr(1);
                        let printTotal = (i == 'FULL_NAME') ? '<b>SubTotal</b>' : '';
                        if (isNaN(getStringNumber)) {
                            appendData += '<td >' + printTotal + '</td>';
                        } else {
                            appendData += '<td ><b>' + parseFloat(v).toFixed(2) + '</b></td>';
                        }
                    });
                    appendData += '</tr>';

                    if (loopCounter >= totalRows) {
                        appendData += '<tr><td></td>';
                        $.each(subTotal, function (i, v) {
                            let getStringNumber = i.substr(1);
                            let printTotal = (i == 'FULL_NAME') ? '<b>Total</b>' : '';
                            if (isNaN(getStringNumber)) {
                                appendData += '<td >' + printTotal + '</td>';
                            } else {
                                appendData += '<td ><b>' + parseFloat(grandTotal[i]).toFixed(2) + '</b></td>';
                            }
                        });
                        appendData += '</tr>';
                    }



                    appendData += '</table>';
//                    appendData += `<div class="footer-space" style="text-align:initial; margin-top: 50px;">
                    appendData += `<div class="footer-space" style="text-align:initial; ">
             <span class="footerMargin" >   Prepared By     </span>
             <span class="footerMargin">   Checked By      </span>
             <span class="footerMargin">   Recommended By  </span>
             <span class="footerMargin">   Approved By     </span>
                </div>`;
//                    appendData += '<div style="break-after:page"></div>';
                });


                if (snCount >= totalRows) {
                    continueLoop = false;
                } else {
                    continueLoop = true;
                    startloop = loopCounter;
                    endloop = parseInt(loopCounter) + parseInt(breakCount);
                }

            }
            finalPrint.append(appendData);
            var divToPrint = document.getElementById('finalPrint');
            var newWin = window.open('', 'Print-Window');
            newWin.document.open();
//            let tableFontSize = ''
            let footerMargin=$('#printFooterMargin').val();
            let tableFontSize=' table th { font-size: '+printFont+'px; } table td { font-size: '+printFont+'px; } '
            let printCss = tableFontSize + ` td { border: 1px solid black; text-align: left; }
                        @media print {
              .footer-space {
                position: fixed;
                bottom: 0;
              } } 
            table {border-collapse: collapse; }table, th {border: 1px solid black;text-align: center;} 
            .footerMargin{margin-left: `+footerMargin+`px;}`;
            newWin.document.write(`<html><body onload="window.print()"> <div style="text-align: center;">` + divToPrint.innerHTML + `<br/></body><style> ` + printCss + `  </style></html>`);
            newWin.document.close();
            finalPrint.empty();



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
            groupChangeFn();
        };

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
                app.serverRequest(document.pullGroupLink, {
                    group: selectedGroups,
                    monthId: selectedMonth['MONTH_ID'],
                    salaryTypeId: selectedSalaryTypeId
                }).then(function (response) {

                    app.populateSelect($allSheetId, response.sheetData, 'SHEET_NO', 'SHEET_NO', 'ALL', -1, -1);
                });
            }
        }

        $month.on('change', function () {
            monthChangeAction();
        });

        $companyId.on('change', function () {
            monthChangeAction();
        });

        $salaryTypeId.on('change', function () {
            monthChangeAction();
        });

        (function ($groupId, link) {
            var onDataLoad = function (data) {
                groupList = data;
                app.populateSelect($groupId, groupList, 'GROUP_ID', 'GROUP_NAME', 'Select Group');
				var acl = document.acl;
                console.log(acl);
                if(acl['CONTROL'] == 'C'){
					//Added for group control
					var groupListControl = [];
					//Ends Added for group control 

                    var companyWiseGroup = document.getCompanyWiseGroup;
                    if(companyWiseGroup[0]['GROUP_ID']){
                        $groupId.val(companyWiseGroup[0]['GROUP_ID']);
                    }
					//Added for group control
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
					
					//Ends adding group control
                    
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

        $month.select2();
        $groupId.select2();
        $salaryTypeId.select2();

        $groupId.on("select2:select select2:unselect", function (event) {
            monthChangeAction();
        });
    
        // (function ($groupId, link) {
        //     var onDataLoad = function (data) {
        //         groupList = data;
        //         app.populateSelect($groupId, groupList, 'GROUP_ID', 'GROUP_NAME', 'Select Group');
        //     };
        //     app.serverRequest(link, {}).then(function (response) {
        //         if (response.success) {
        //             onDataLoad(response.data);
        //         }
        //     }, function (error) {

        //     });
        // })($groupId, getGroupListLink);
        
        
    });
})(window.jQuery, window.app);