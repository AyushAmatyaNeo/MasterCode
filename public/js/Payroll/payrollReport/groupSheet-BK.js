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
        var map = {};
        var exportType = {
            "ACCOUNT_NO": "STRING",
        };
        var salaryData;
        var subTotal={};
        var grandTotal={};
        var printHeads={};
        

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
        
         app.populateSelect($salaryTypeId, document.salaryType, 'SALARY_TYPE_ID', 'SALARY_TYPE_NAME', 'All',-1,-1);

        var initKendoGrid = function (defaultColumns, otVariables, extraVariable, data) {
            let dataSchemaCols = {};
            let aggredCols = [];
            $table.empty();
            map = {
                'EMPLOYEE_CODE': 'Employee Code',
                'FULL_NAME': 'Employee',
                'POSITION_NAME': 'Position',
                'SERVICE_TYPE_NAME': 'Service',
            }

            var columns = [
                {field: "EMPLOYEE_CODE", title: "Code", width: 80, locked: true},
                {field: "FULL_NAME", title: "Employee", width: 120, locked: true},
                {field: "POSITION_NAME", title: "Position", width: 120, locked: true},
                {field: "SERVICE_TYPE_NAME", title: "Service", width: 120, locked: true}
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

//           $table.kendoGrid({
//                dataSource: {
//                    data: data,
//                    schema: {
//                        model: {
//                            fields: dataSchemaCols
//                        }
//                    },
//                    pageSize: 20,
//                    aggregate: aggredCols
//                },
//                toolbar: ["excel"],
//                excel: {
//                    fileName: "Group Sheet Report.xlsx",
//                    filterable: false,
//                    allPages: true
//                },
//                excelExport: function(e) {
//                    var rows = e.workbook.sheets[0].rows;
//                    var columns = e.workbook.sheets[0].columns;
//                    
//                    rows.unshift({
//                        cells: [
//                        {value: "Group Sheet Report", colSpan: columns.length, textAlign: "left"}
//                        ]
//                    });
//                    if(document.preference != undefined){
//                        if(document.preference.companyAddress != null){
//                            rows.unshift({
//                                cells: [
//                                {value: document.preference.companyAddress, colSpan: columns.length, textAlign: "left"}
//                                ]
//                            });
//                        }
//                    }
//                    if(document.preference != undefined){
//                        if(document.preference.companyName != null){
//                            rows.unshift({
//                                cells: [
//                                {value: document.preference.companyName, colSpan: columns.length, textAlign: "left"}
//                                ]
//                            });
//                        }
//                    }
//                },
//                height: 550,
//                scrollable: true,
//                sortable: true,
//                groupable: true,
//                filterable: true,
//                pageable: {
//					refresh:true,
//					pageSizes:true,
//                    input: true,
//                    numeric: false
//                },
//                columns: columns
//            });
            
            renderForPrint(map,data);
            
        }
        
        $('#unhideAllZero').on('click', function () {
            $.each(grandTotal, function (i, v) {
                let getStringNumber = i.substr(1);
                let columHeaderId = '#' + i;
                if (!isNaN(getStringNumber)) {
                        $(columHeaderId).prop("checked", false);
                }
            });
        });
        
        $('#hideAllZero').on('click', function () {
            console.log('clicked', grandTotal);
            $.each(grandTotal, function (i, v) {
                let getStringNumber = i.substr(1);
                let columHeaderId = '#' + i;
                if (!isNaN(getStringNumber)) {
                    if (parseInt(v)== 0)
                    {
                        $(columHeaderId).prop("checked", true);
                    }
                }
            });
        });
        
        var renderForPrint = function (map, data) {
            let snCount=1;
            let breakCount=40;
//            console.log('print Starts here', map);

            let appendData = ``;

            var printEditor = $('#printEditor');
            printEditor.empty();
            appendData += '<table class="table table-bordered table-striped table-condensed" id="testTable">';

            appendData += '<tr><th>SN</th>';
            
             
            $.each(map, function (index, value) {
                grandTotal[index] = 0;
                let colLock = (index == 'EMPLOYEE_CODE' || index == 'FULL_NAME') ? '' : '';
                let colMargin = (index == 'FULL_NAME') ? '' : '';
//                let colLock = (index == 'EMPLOYEE_CODE' || index == 'FULL_NAME') ? 'class="freeze"' : '';
//                let colMargin = (index == 'FULL_NAME') ? 'style="left:70px;"' : '';
                appendData += '<th ' + colLock + ' ' + colMargin + '> <input type="checkbox" id="' + index + '"> ' + value + '</th>';
            });
//            console.log(grandTotal);
            appendData += '</tr>';


            $.each(data, function (index, value) {
                appendData += '<tr>';
                appendData += '<td class="freeze" >'+snCount+'</td>';
                $.each(map, function (i, v) {
//                    if(i='V151'){
//                        console.log(value['FULL_NAME'],parseFloat(value[i]));
//                    }
                    grandTotal[i] = grandTotal[i] + parseFloat(value[i]);
                    let colLock = (i == 'EMPLOYEE_CODE' || i == 'FULL_NAME') ? 'class="freeze"' : '';
                    let colMargin = (i == 'FULL_NAME') ? 'style="left:70px;"' : '';
                    appendData += '<td ' + colLock + ' ' + colMargin + '>' + value[i] + '</td>';
                });
                appendData += '</tr>';
                snCount++;
            });

            appendData += '<tr><td></td>';
            $.each(grandTotal, function (i, v) {
                let getStringNumber = i.substr(1);
                let colLock = (i == 'EMPLOYEE_CODE' || i == 'FULL_NAME') ? 'class="freeze"' : '';
                let colMargin = (i == 'FULL_NAME') ? 'style="left:70px;"' : '';
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
        }
        
        
//           $('#aa').on('click', function () {
//               renderForPrint();
//           });
           

        $('#searchEmployeesBtn').on('click', function () {
            var q = document.searchManager.getSearchValues();
            q['fiscalId'] = $fiscalYear.val();
            q['monthId'] = $month.val();
            q['extVar'] = $otVariable.val();
            q['extField'] = $extraFields.val();
            q['reportType'] = $reportType.val();
            q['groupVariable'] = $groupVariable.val();
            q['salaryTypeId'] = $salaryTypeId.val();

            app.serverRequest(document.pullGroupSheetLink, q).then(function (response) {
                if (response.success) {
                    salaryData=response.data;
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
            app.excelExport($table, map, 'GroupSheet.xlsx',exportType);
        });
        $('#pdfExport').on('click', function () {
//            app.exportToPDF($table, map, 'GroupSheet.pdf','A1');


// Default export is a4 paper, portrait, using millimeters for units
//const doc = new jsPDF("landscape");
//
//doc.text("Hello world!", 10, 10);
//doc.save("a4.pdf");

        var pdf = new jsPDF('p', 'mm', [400, 480]);
        pdf.save("screen-3.pdf");





        });
//        aaaa
        
           $('#aaaa').on('click', function () {
            let printColums = JSON.parse(JSON.stringify(map));
            $('table input[type=checkbox]').each(function (i, obj) {
                if ($(obj).is(":checked")) {
                    let toDeletId = $(obj).attr("id");
                    delete printColums[toDeletId];
                }
            });



            let snCount = 1;
            let breakCount = 40;
//            console.log('print Starts here', map);

            let appendData = ``;

            var finalPrint = $('#finalPrint');
            finalPrint.empty();
            appendData += '<table class="table table-bordered table-striped table-condensed" id="testTable">';

            appendData += '<tr><th>SN</th>';


            grandTotal = {};
            $.each(printColums, function (index, value) {
                grandTotal[index] = 0;
                let colLock = (index == 'EMPLOYEE_CODE' || index == 'FULL_NAME') ? '' : '';
                let colMargin = (index == 'FULL_NAME') ? '' : '';
                appendData += '<th ' + colLock + ' ' + colMargin + '>' + value + '</th>';
            });
            appendData += '</tr>';

            console.log(salaryData);

            $.each(salaryData, function (index, value) {
                appendData += '<tr>';
                appendData += '<td class="freeze" >' + snCount + '</td>';
                $.each(printColums, function (i, v) {
                    grandTotal[i] = grandTotal[i] + parseFloat(value[i]);
                    let colLock = (i == 'EMPLOYEE_CODE' || i == 'FULL_NAME') ? 'class="freeze"' : '';
                    let colMargin = (i == 'FULL_NAME') ? 'style="left:70px;"' : '';
                    appendData += '<td ' + colLock + ' ' + colMargin + '>' + value[i] + '</td>';
                });
                appendData += '</tr>';
                snCount++;
            });

            appendData += '<tr><td></td>';
            $.each(grandTotal, function (i, v) {
                let getStringNumber = i.substr(1);
                let colLock = (i == 'EMPLOYEE_CODE' || i == 'FULL_NAME') ? '' : '';
                let colMargin = (i == 'FULL_NAME') ? '' : '';
                let printTotal = (i == 'FULL_NAME') ? 'Total' : '';
                if (isNaN(getStringNumber)) {
                    appendData += '<td ' + colLock + ' ' + colMargin + '>' + printTotal + '</td>';
                } else {
                    appendData += '<td ><b>' + parseFloat(v).toFixed(2) + '</b></td>';
                }
            });
            appendData += '</tr>';


            appendData += '</table>';
            finalPrint.append(appendData);




             var divToPrint = document.getElementById('finalPrint');
             finalPrint.empty();
//             console.log(divToPrint.innerHTML);

            var newWin = window.open('', 'Print-Window');
            newWin.document.open();
//            newWin.document.write(divToPrint.innerHTML);
            newWin.document.write(`<html><body onload="window.print()"> <div style="text-align: center;">` + divToPrint.innerHTML + `<br/></body><style>table {border-collapse: collapse;}table, th, td {border: 1px solid black;text-align: center;} </style></html>`);
            newWin.document.close();



        });
    
    
    
        
        
    });
})(window.jQuery, window.app);


