(function ($, app) {
    'use strict';
    $(document).ready(function () {
        $('Select').select2();

        var monthList = null;
        var $fiscalYear = $('#fiscalYearId');
        var $month = $('#monthId');
        var $salaryTypeId = $('#salaryTypeId');
        var $table = $('#table');
        var map = {};
        var exportType = {
            "ACCOUNT_NO": "STRING",
        };
        app.setFiscalMonth($fiscalYear, $month, function (years, months, currentMonth) {
            monthList = months;
        });
        app.populateSelect($salaryTypeId, document.salaryType, 'SALARY_TYPE_ID', 'SALARY_TYPE_NAME', 'All', -1, -1);

        var firstLoop = '';
        var sencondLoop = '';
        
        var firstLoopArr=[document.incomes.length,
        document.taxExcemptions.length,
        document.otherTax.length];
    
        var sendLoopArr=[document.miscellaneous.length,
        document.bMiscellaneou.length,
        document.cMiscellaneou.length];
    
        var maxFirstLoop =  Math.max.apply(Math, firstLoopArr);
        var maxSecLoop =  Math.max.apply(Math, sendLoopArr);
    
        
        

        for (var n = 0; n < maxFirstLoop; ++ n){
            let incomeName=(document.incomes[n])?document.incomes[n]['VARIANCE_NAME']:'';
            let incomeTemp=(document.incomes[n])?document.incomes[n]['TEMPLATE_NAME']:'';
            let taxEmpName=(document.taxExcemptions[n])?document.taxExcemptions[n]['VARIANCE_NAME']:'';
            let taxEmpTemp=(document.taxExcemptions[n])?document.taxExcemptions[n]['TEMPLATE_NAME']:'';
            let otherTaxName=(document.otherTax[n])?document.otherTax[n]['VARIANCE_NAME']:'';
            let otherTaxTemp=(document.otherTax[n])?document.otherTax[n]['TEMPLATE_NAME']:'';
            let temp='<tr>';
             temp +='<td>' + incomeName + '</td>';
             temp +='<td>{{' + incomeTemp + '}}</td>';
             temp +='<td>' + taxEmpName + '</td>';
             temp +='<td>{{' + taxEmpTemp + '}}</td>';
             temp +='<td>' + otherTaxName + '</td>';
             temp +='<td>{{' + otherTaxTemp + '}}</td>';
             temp +='</tr>';

            firstLoop +=temp;

        };
        
        
        for (var n = 0; n < maxSecLoop; ++ n)
        {
            let misName=(document.miscellaneous[n])?document.miscellaneous[n]['VARIANCE_NAME']:'';
            let misTemp=(document.miscellaneous[n])?document.miscellaneous[n]['TEMPLATE_NAME']:'';
            let bMisName=(document.bMiscellaneou[n])?document.bMiscellaneou[n]['VARIANCE_NAME']:'';
            let bMisTemp=(document.bMiscellaneou[n])?document.bMiscellaneou[n]['TEMPLATE_NAME']:'';
            let cMisName=(document.cMiscellaneou[n])?document.cMiscellaneou[n]['VARIANCE_NAME']:'';
            let cMisTemp=(document.cMiscellaneou[n])?document.cMiscellaneou[n]['TEMPLATE_NAME']:'';
            
            
             let temp='<tr>';
             temp +='<td>' + misName + '</td>';
             temp +='<td>{{' + misTemp + '}}</td>';
             temp +='<td>' + bMisName + '</td>';
             temp +='<td>{{' + bMisTemp + '}}</td>';
             temp +='<td>' + cMisName + '</td>';
             temp +='<td>{{' + cMisTemp + '}}</td>';
             temp +='</tr>';

            sencondLoop +=temp;
            
            
            
        }





        var repTemplate = `{{#employees}}
<div><h4>{{COMPANY_NAME}}<h3>
<h5>{{BRANCH_NAME}}<h2>
<table class="table table-bordered table-striped table-condensed">
                    <tr>
                        <td><b>Estimate Income Tax</b></td>
                        <td><b>{{YEAR_MONTH_NAME}}</b></td>
                        <td></td>
                        <td></td>
                        <td><b>Designation</b></td>
                        <td><b>{{DESIGNATION_TITLE}}</b></td>
                    </tr>
                    <tr>
                        <td><b>Name</b></td>
                        <td><b>{{FULL_NAME}}</b></td>
                        <td></td>
                        <td></td>
                        <td><b>Department</b></td>
                        <td><b>{{DEPARTMENT_NAME}}</b></td>
                    </tr>
                    <tr>
                        <td><b>Code</b></td>
                        <td><b>{{EMPLOYEE_CODE}}</b></td>
                        <td></td>
                        <td></td>
                        <td><b>Maritual Status</b></td>
                        <td><b>{{MARITAL_STATUS}}</b></td>
                        
                    </tr>
                    <tr>
                        <td><b>PAN No</b></td>
                        <td><b>{{ID_PAN_NO}}</b></td>
                        <td></td>
                        <td></td>
                        <td><b>Assessment Choice</b></td>
                        <td><b>{{ASSESSMENT_CHOICE}}</b></td>
                    </tr>
                    <tr>
                        <td colspan="6">&nbsp;</td>
                    </tr>
                    <tr>
                        <td colspan="2"><b>Incomes</b></td>
                        <td colspan="2"><b>Tax Exemptions</b></td>
                        <td colspan="2"><b>Other Tax</b></td>
                    </tr>
        ` + firstLoop + `
                    <tr>
                        <td><b>Total Income</b></td>
                        <td>{{TOTAL_INCOME_VAL}}</td>
                        <td><b>Total Exemption</b></td>
                        <td>{{`+document.sumOfExemption['TEMPLATE_NAME']+`}}</td>
                        <td><b>Tax Due</b></td>
                        <td>{{`+document.sumOfOtherTax['TEMPLATE_NAME']+`}}</td>
                    </tr>
        ` + sencondLoop + `
                </table></div>{{/employees}}`;
        
//        console.log(repTemplate);
        
        //  $('#searchEmployeesBtn').on('click', function () {
        //     var q = document.searchManager.getSearchValues();
        //     q['fiscalId'] = $fiscalYear.val();
        //     q['monthId'] = $month.val();
        //     q['salaryTypeId'] = $salaryTypeId.val();


        //     app.serverRequest(document.pulltaxYearlyLink, q).then(function (response) {
        //         if (response.success) {
        //             $.each(response.data.employees, function (index, value) {
        //                 let tempTotal = 0;
        //                 $.each(document.incomes, function (i, v) {
        //                     let tempName = v['TEMPLATE_NAME'];
							
		// 					if(typeof value[tempName] != 'object'){
		// 					tempTotal += parseFloat(value[tempName]);
		// 					}
                            

        //                 });

        //                 response.data.employees[index]['TOTAL_INCOME_VAL'] = parseFloat(tempTotal).toFixed(2);

        //             }); 
        //             var mustHtml = Mustache.to_html(repTemplate, response.data);
        //             // $('#table').html(mustHtml);


        //         } else {
        //             app.showMessage(response.error, 'error');
        //         }
        //     }, function (error) {
        //         app.showMessage(error, 'error');
        //     });
        // });
        
        
        $('#excelExport').on('click', function () {
            app.excelExport($table, map, 'TaxSheet.xlsx',exportType);            
        });
        
        $('#pdfExport').on('click', function () {
            app.exportToPDF($table, map, 'TaxSheet.pdf','A1');
        });

        var initKendoGrid = function (defaultColumns, otVariables, extraVariable, data) {
            let dataSchemaCols = {};
            let aggredCols = [];
            $table.empty();
            map = {
                'EMPLOYEE_ID' : 'EMP ID',
                'FULL_NAME': 'Employee Name',
                'MARITAL_STATUS': 'Marital Status',
                'GROUP_NAME': 'Sheet Group',
            }

            var columns = [
                {field: "EMPLOYEE_ID", title: "ID", width: 80, locked: true},
                {field: "FULL_NAME", title: "Employee", width: 120, locked: true},
                {field: "MARITAL_STATUS", title: "Marital Status", width: 120, locked: true},
                {field: "GROUP_NAME", title: "Sheet Group", width: 120, locked: true},
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
                if(1 ==1){
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
                }
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
            
            // renderForPrint(map,data);
            
        }

        $('#searchEmployeesBtn').on('click', function () {
            //////////////
            var q = document.searchManager.getSearchValues();
            q['fiscalId'] = $fiscalYear.val();
            q['monthId'] = $month.val();
            q['salaryTypeId'] = $salaryTypeId.val();


            app.serverRequest(document.pulltaxYearlyLink, q).then(function (response) {
                if (response.success) {
                    console.log(response.columns);
                    $.each(response.data.employees, function (index, value) {
                        let tempTotal = 0;
                        $.each(document.incomes, function (i, v) {
                            let tempName = v['TEMPLATE_NAME'];
							
							if(typeof value[tempName] != 'object'){
							tempTotal += parseFloat(value[tempName]);
							}
                            

                        });

                        response.data.employees[index]['TOTAL_INCOME_VAL'] = parseFloat(tempTotal).toFixed(2);

                    }); 
                    // var mustHtml = Mustache.to_html(repTemplate, response.data);
                    // $('#table').html(mustHtml);
                    initKendoGrid(response.columns, [], [], response.data.employees);

                } else {
                    app.showMessage(response.error, 'error');
                }
            }, function (error) {
                app.showMessage(error, 'error');
            });
            /////////////


            // app.serverRequest(document.pullGroupSheetLink, q).then(function (response) {
            //     if (response.success) {
            //         salaryData=response.data;
            //         selectedMonthDetails=response.monthDetails;
            //         if(q['reportType']=='GS'){
            //         initKendoGrid(response.columns, $otVariable.val(), $extraFields.val(), response.data);
            //     }else if(q['reportType']=='GD'){
            //         initKendoGrid(response.columns, [], $extraFields.val(), response.data);
            //     }
            //         //app.renderKendoGrid($table, response.data);
            //     } else {
            //         app.showMessage(response.error, 'error');
            //     }
            // }, function (error) {
            //     app.showMessage(error, 'error');
            // });
        });
        
        



    });
})(window.jQuery, window.app);


