(function ($, app) {
    'use strict';
    $(document).ready(function () {
        $('Select').select2();

        var monthList = null;
        var $fiscalYear = $('#fiscalYearId');
        var $kendoTable = $('#kendoTable');
        var controlValues = document.controlValues;

        app.initializeKendoGrid($kendoTable, [
            { field: "EMPLOYEE_CODE", title: "Employee Code" },
            { field: "FULL_NAME", title: "Full Name" },
            { field: "BANK_NAME", title: "Bank Name" },
            { field: "ID_ACCOUNT_NO", title: "Account Name" },
            { field: "VAL", title: "Amount" }
        ], null, null, null, 'Letter To The Bank');

        var exportMap = {
            'EMPLOYEE_CODE': 'Employee Code',
            'FULL_NAME': 'Employee Name',
            'BANK_NAME': 'Bank Name',
            'ID_ACCOUNT_NO': 'Account Number',
            'VAL': 'Amount'
        };

        var $month = $('#monthId');
        //        var $reportType = $('#reportType');
        //        var $otVariable = $('#otVariable');
        //        var $extraFields = $('#extraFields');
        //        var $groupVariable = $('#groupVariable');
        // var $table = $('#table');
        var $salaryTypeId = $('#salaryTypeId');
        var $bankTypeId = $('#bankTypeId');

        //        var map = {};
        //        var exportType = {
        //            "ACCOUNT_NO": "STRING",
        //        };

        app.setFiscalMonth($fiscalYear, $month, function (years, months, currentMonth) {
            monthList = months;
        });
        app.populateSelect($salaryTypeId, document.salaryType, 'SALARY_TYPE_ID', 'SALARY_TYPE_NAME', 'All', -1, -1);
        app.populateSelect($bankTypeId, document.bankType, 'BANK_ID', 'BANK_NAME', '----------', -1, -1);




        //        var person = {
        //    firstName: "Christophe",
        //    lastName: "Coenraets",
        //    blogURL: "http://coenraets.org"
        //};
        //var template = "<h1>{{firstName}} {{lastName}}</h1>Blog: {{blogURL}}";
        //var html = Mustache.to_html(template, person);
        //$('#table').html(html);


        //  document.taxExcemptions =<?php echo json_encode($taxExcemptions); ?>;
        //    document.otherTax =<?php echo json_encode($otherTax); ?>;


        var firstLoop = '';
        var sencondLoop = '';
        var loopData = 'aaa';
        var firstLoopArr = [document.incomes.length,
        document.taxExcemptions.length,
        document.otherTax.length];

        var sendLoopArr = [document.miscellaneous.length,
        document.bMiscellaneou.length,
        document.cMiscellaneou.length];

        var maxFirstLoop = Math.max.apply(Math, firstLoopArr);
        var maxSecLoop = Math.max.apply(Math, sendLoopArr);




        for (var n = 0; n < maxFirstLoop; ++n) {
            let incomeName = (document.incomes[n]) ? document.incomes[n]['VARIANCE_NAME'] : '';
            let incomeTemp = (document.incomes[n]) ? document.incomes[n]['TEMPLATE_NAME'] : '';
            let taxEmpName = (document.taxExcemptions[n]) ? document.taxExcemptions[n]['VARIANCE_NAME'] : '';
            let taxEmpTemp = (document.taxExcemptions[n]) ? document.taxExcemptions[n]['TEMPLATE_NAME'] : '';
            let otherTaxName = (document.otherTax[n]) ? document.otherTax[n]['VARIANCE_NAME'] : '';
            let otherTaxTemp = (document.otherTax[n]) ? document.otherTax[n]['TEMPLATE_NAME'] : '';
            let temp = '<tr>';
            temp += '<td>' + incomeName + '</td>';
            temp += '<td>{{' + incomeTemp + '}}</td>';
            temp += '<td>' + taxEmpName + '</td>';
            temp += '<td>{{' + taxEmpTemp + '}}</td>';
            temp += '<td>' + otherTaxName + '</td>';
            temp += '<td>{{' + otherTaxTemp + '}}</td>';
            temp += '</tr>';

            firstLoop += temp;

        };


        for (var n = 0; n < maxSecLoop; ++n) {
            let misName = (document.miscellaneous[n]) ? document.miscellaneous[n]['VARIANCE_NAME'] : '';
            let misTemp = (document.miscellaneous[n]) ? document.miscellaneous[n]['TEMPLATE_NAME'] : '';
            let bMisName = (document.bMiscellaneou[n]) ? document.bMiscellaneou[n]['VARIANCE_NAME'] : '';
            let bMisTemp = (document.bMiscellaneou[n]) ? document.bMiscellaneou[n]['TEMPLATE_NAME'] : '';
            let cMisName = (document.cMiscellaneou[n]) ? document.cMiscellaneou[n]['VARIANCE_NAME'] : '';
            let cMisTemp = (document.cMiscellaneou[n]) ? document.cMiscellaneou[n]['TEMPLATE_NAME'] : '';


            let temp = '<tr>';
            temp += '<td>' + misName + '</td>';
            temp += '<td>{{' + misTemp + '}}</td>';
            temp += '<td>' + bMisName + '</td>';
            temp += '<td>{{' + bMisTemp + '}}</td>';
            temp += '<td>' + cMisName + '</td>';
            temp += '<td>{{' + cMisTemp + '}}</td>';
            temp += '</tr>';

            sencondLoop += temp;



        }





        var repTemplate = `
<div style="padding:4%;"font-size:12px"><center><h1><b>{{COMPANY_NAME}}</b></h1>
<h5><b>{{ADDRESS}}</b></h5></center>
<br><br><div style="width:100%;text-align:right;"font-size:12px">Date: {{TODAY_DATE}}</div>
<br><br>To,<br>
The Branch Manager,<br>
<b>{{BANK_NAME}}</b>.<br><br>

<p style="margin-left:7%";"font-size:12px";>Subject: <u><b>Request for amount transfer</b></u></p>
<br><br>
<p style="font-weight:bold;">Dear Sir / Madam,</p><br><br>
<p style="font-size:12px";>We have provided with account holder's name, account number and the amount
to be deposited in the account number. Our Bank Account Number and total amount
to be debited is as mentioned below.<p>

<div style="font-weight:bold";"margin-left:7%";"font-size:12px">
(A) Amount to be deposited: {{TOTAL}}
<br>
(B) Account Number:
<br>
(C) Cheque No.:
<br><br>
</div>
For the Month of : {{MONTH_EDESC}}
<br>
For the Fiscal year of : {{FISCAL_YEAR_NAME}}
<br>
For Bank Information :
<br>

<table style="margin-top:10px;" class="table table-bordered table-striped table-condensed">
                    <th>
                        <td>S.N.</td>
                        <td>Employee Name</td>
                        <td>Bank</td>
                        <td>Account No.</td>
                        <td>Amount</td>
                        
                    </th>
                    {{#employees}}
                    <tr>
                        <td></td>
                        <td style = "font-size:10px">{{SERIAL}}</td>
                        <td style = "font-size:10px">{{FULL_NAME}}</td>
                        <td style = "font-size:10px">{{BANK_NAME}}</td>
                        <td style = "font-size:10px">{{ID_ACCOUNT_NO}}</td>
                        <td style = "font-size:10px; text-align: right;">{{VAL}}</td>
                       
                    <tr>
                    {{/employees}}
                    <tr>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td  style="font-weight:bold;">TOTAL</td>
                        <td style="font-weight:bold; text-align: right;">{{TOTAL}}</td>
                    </tr>
                </table>
<br>
<b>{{COMPANY_NAME}}
<br>{{ADDRESS}}
</b>
                </div>`;

        //        console.log(repTemplate);

        $('#searchEmployeesBtn').on('click', function () {
            var q = document.searchManager.getSearchValues();
            q['fiscalId'] = $fiscalYear.val();
            q['monthId'] = $month.val();
            q['salaryTypeId'] = $salaryTypeId.val();
            q['bankTypeId'] = $bankTypeId.val();
            q['companyId'] = $('#companyId').val();




            //            q['extVar'] = $otVariable.val();
            //            q['extField'] = $extraFields.val();
            //            q['reportType'] = $reportType.val();



            app.serverRequest(document.pullLetterToBankDetail, q).then(function (response) {
                if (response.success) {
                    let tempTotal = 0;
                    let serial = 1;
                    $.each(response.data.employees, function (index, value) {
                        const myString = $.trim((value['VAL']));
                        tempTotal = tempTotal + parseFloat(myString.replace(/,/g, ''));
                        loopData = loopData + `asdf`;
                        response.data.employees[index]['SERIAL'] = serial;
                        serial += 1;
                        response.data['TODAY_DATE'] = response.data.employees[index]['TODAY_DATE'];
                        response.data['BANK_NAME'] = response.data.employees[index]['BANK_NAME'];
                        response.data['MONTH_EDESC'] = response.data.employees[index]['MONTH_EDESC'];
                        response.data['FISCAL_YEAR_NAME'] = response.data.employees[index]['FISCAL_YEAR_NAME'];

                    });

                    response.data['COMPANY_NAME'] = (response.companyDetail[0]) ? response.companyDetail[0]['COMPANY_NAME'] : response.preference['companyAddress'];
                    response.data['ADDRESS'] = (response.companyDetail[0]) ? response.companyDetail[0]['ADDRESS'] : response.preference['companyAddress'];
                    response.data['TOTAL'] = tempTotal.toLocaleString('en-IN', { minimumFractionDigits: 2 });
                    console.log(response.data.employees);
                    var mustHtml = Mustache.to_html(repTemplate, response.data);
                    $('#table').html(mustHtml);
                    app.renderKendoGrid($kendoTable, response.data.employees);


                } else {
                    app.showMessage(response.error, 'error');
                }
            }, function (error) {
                app.showMessage(error, 'error');
            });
        });





        $('#pdfExport').on('click', function () {
            kendo.drawing.drawDOM($("#table"), {

                paperSize: "A4",
                multiPage: true,
                margin: { left: "0cm", top: "1cm", right: "0cm", bottom: "1cm" }
            })
                .then(function (group) {
                    // Render the result as a PDF file
                    return kendo.drawing.exportPDF(group);
                })
                .done(function (data) {
                    // Save the PDF file
                    kendo.saveAs({
                        dataURI: data,
                        fileName: "letterToBank.pdf"
                    });
                });

        });


        $('#excelExport').on('click', function () {
            app.excelExport($kendoTable, exportMap, 'Letter to Bank.xlsx');
        });

        if (controlValues == 'C') {
            $('#companyDiv').hide();
        } else {
            $('#companyDiv').show();
        }








    });
})(window.jQuery, window.app);


