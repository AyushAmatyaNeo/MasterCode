(function ($, app) {
    'use strict';
    $(document).ready(function () {
        $('select').select2();
        var months = null;
        var $year = $('#fiscalYearId');
        var $month = $('#monthId');
        var $employeeId = $('#employeeId');
        var $viewBtn = $('#viewBtn');
        var $paySlipBody = $('#paySlipBody');
        var $excelExport = $('#excelExport');
        var $pdfExport = $('#pdfExport');
        var $salaryTypeId = $('#salaryTypeId');
        var $exchangeRateVal = $('input[name="exchangeRate"]:checked').val();
        $("input[name='exchangeRate']").change(function() {
             $exchangeRateVal = $(this).val();
        });
        app.populateSelect($salaryTypeId, document.salaryType, 'SALARY_TYPE_ID', 'SALARY_TYPE_NAME', null, null, 1);

        var employeeList = null;
        app.setFiscalMonth($year, $month, function (yearList, monthList, currentMonth) {
            months = monthList;
        });
        app.setEmployeeSearch($employeeId, function (empList) {
            employeeList = empList;
        });
        var showPaySlip = function ($data) {
            $paySlipBody.html('');
            var additionData = {};
            var additionCounter = 0;
            var additionSum = 0
            var deductionData = {};
            var deductionCounter = 0;
            var deductionSum = 0;
            var netSum = 0;
            var net=0;
            var add=0;
            var sub=0;
            $.each($data, function (index, item) {
                switch (item['PAY_TYPE_FLAG']) {
                    case 'A':
                        additionData[additionCounter] = item;
                        const myString = $.trim((item['VAL']));
                        additionSum = additionSum +parseFloat(myString.replace(',', ''));   
                        additionCounter++;
                        break;
                    case 'D':
                        deductionData[deductionCounter] = item;
                        const String = $.trim((item['VAL']));
                        deductionSum = deductionSum + parseFloat(String.replace(',', ''));  
                        deductionCounter++;
                        break;
                }
                netSum = additionSum - deductionSum;
            });
            add = (additionSum.toLocaleString('en-US',{ minimumFractionDigits: 2 }));
            sub = (deductionSum.toLocaleString('en-US',{ minimumFractionDigits: 2 }));
            net= (netSum.toLocaleString('en-US',{ minimumFractionDigits: 2 }
            ));
            var maxRows = (additionCounter > deductionCounter) ? additionCounter : deductionCounter;
            for (var i = 0; i < maxRows; i++) {
                var $row = $(`<tr>
                <td>${(typeof additionData[i] !== 'undefined') ? additionData[i]['PAY_EDESC'] : ''}</td>
                <td style="text-align: right">${(typeof additionData[i] !== 'undefined') ? (additionData[i]['VAL']) : ''}</td>
                <td>${(typeof deductionData[i] !== 'undefined') ? deductionData[i]['PAY_EDESC'] : ''}</td>
                <td style="text-align: right">${(typeof deductionData[i] !== 'undefined') ? (deductionData[i]['VAL']) : ''}</td>
                </tr>`);
                $paySlipBody.append($row);
            }
            $paySlipBody.append($(`<tr>
                                <td ><b>Total Addition:</b></td>
                                <td style="text-align: right"><b>${add}</b></td>
                                <td><b>Total Deduction:</b></td>
                                <td style="text-align: right"><b>${sub}</b></td>
                                </tr></tr> <td><b>Net Salary:</b></td>
                                 <td style="text-align: right"><b>${net}</b></td>`));

        }
        var showEmpDetail = function ($data) {
            for (var i in $data) {
                $(`td[key='${i}'] `).html($data[i]);
            }
        }
        $viewBtn.on('click', function () {
            var selectedYearText=$("#fiscalYearId option:selected" ).text();
            var selectedMonthText=$("#monthId option:selected" ).text();
            var selectNameText=$("#employeeId option:selected").text();
            var displayYearMonthtext='Payslip of '+selectNameText+' for '+selectedMonthText+' '+selectedYearText;
            $('#yearMonthDetails').html(displayYearMonthtext);

            var monthId = $month.val();
            var employeeId = $employeeId.val();
            var salaryTypeId =$salaryTypeId.val();
            var exchangeRate=$exchangeRateVal;
            var employee = employeeList.find(function (item) {
                return item['EMPLOYEE_ID'] == employeeId;
            });
            app.serverRequest('', {
                monthId: monthId,
                employeeId: employeeId,
                companyId: employee['COMPANY_ID'],
                groupId: employee['GROUP_ID'],
                salaryTypeId: salaryTypeId,
                exchangeRate:exchangeRate
            }).then(function (response) {
                showPaySlip(response.data['pay-detail']);
                showEmpDetail(response.data['emp-detail']);
            }, function (error) {

            });
        });
 
        $month.on('change',function(){
            var monthId=$(this).val();
            app.serverRequest(document.wsExchangeRate, {monthId: monthId}).then(function (response) {
                if (response.data === null || response.data ==='') {
                    $('#exchangeRateVal').val('Rs '+0);
                } else if(response.data!= null){
                    $('#exchangeRateVal').val('Rs '+response.data);
                 }
            }, function (error) {
                app.showMessage(error, 'error');
            });
        })
         
        $pdfExport.on('click', function () {
            app.exportDomToPdf2($('#paySlipView'));
        });

    });
})(window.jQuery, window.app);