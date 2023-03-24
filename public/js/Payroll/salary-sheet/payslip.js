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
                        additionSum = additionSum + parseFloat(item['VAL']);
                        additionCounter++;
                        break;
                    case 'D':
                        deductionData[deductionCounter] = item;
                        deductionSum = deductionSum + parseFloat(item['VAL']);
                        deductionCounter++;
                        break;
                }
                netSum = additionSum - deductionSum;
            });
            add = parseFloat(additionSum).toFixed(2);
            sub = parseFloat(deductionSum).toFixed(2);
            net= parseFloat(netSum).toFixed(2);
            var maxRows = (additionCounter > deductionCounter) ? additionCounter : deductionCounter;
            for (var i = 0; i < maxRows; i++) {
                var $row = $(`<tr>
                                <td>${(typeof additionData[i] !== 'undefined') ? additionData[i]['PAY_EDESC'] : ''}</td>
                                <td>${(typeof additionData[i] !== 'undefined') ? parseFloat(additionData[i]['VAL']).toFixed(2) : ''}</td>
                                <td>${(typeof deductionData[i] !== 'undefined') ? deductionData[i]['PAY_EDESC'] : ''}</td>
                                <td>${(typeof deductionData[i] !== 'undefined') ? parseFloat(deductionData[i]['VAL']).toFixed(2) : ''}</td>
                                </tr>`);
                $paySlipBody.append($row);
            }
            $paySlipBody.append($(`<tr>
                                <td><b>Total Addition:</b></td>
                                <td><b>${add}</b></td>
                                <td><b>Total Deduction:</b></td>
                                <td><b>${sub}</b></td>
                                </tr></tr> <td><b>Net Salary:</b></td>
                                 <td><b>${net}</b></td>`));

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
            var employee = employeeList.find(function (item) {
                return item['EMPLOYEE_ID'] == employeeId;
            });
            app.serverRequest('', {
                monthId: monthId,
                employeeId: employeeId,
                companyId: employee['COMPANY_ID'],
                groupId: employee['GROUP_ID'],
                salaryTypeId: salaryTypeId
            }).then(function (response) {
                showPaySlip(response.data['pay-detail']);
                showEmpDetail(response.data['emp-detail']);
            }, function (error) {

            });
        });

        $pdfExport.on('click', function () {
            app.exportDomToPdf2($('#paySlipView'));
        });

    });
})(window.jQuery, window.app);