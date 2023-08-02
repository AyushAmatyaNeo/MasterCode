(function ($, app) {
    'use strict';
    $(document).ready(function () {
        $("select").select2();
        $('select').select2();
        // app.datePickerWithNepali("fromDate","nepaliFromDate");
        // $('#form-paidDate').datepicker("setStartDate", new Date());
        app.startEndDatePickerWithNepali('nepaliFromDate', 'fromDate', 'nepaliToDate', 'toDate', null, false);
        var monthShortNames = ["Jan", "Feb", "Mar", "Apr", "May", "Jun",
        "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];
        var d = new Date();
        d.getMonth() <= 6 ? $("#fromDate").val("17-Jul-" + (d.getFullYear()-1)) : $("#fromDate").val(("0" + d.getDate()).slice(-2) + "-Jul-" + d.getFullYear()) ;
        $("#nepaliFromDate").val(window.nepaliDatePickerExt.fromEnglishToNepali($("#fromDate").val()));
        $("#toDate").val(("0" + d.getDate()).slice(-2) + "-" + monthShortNames[(d.getMonth())] + "-" +d.getFullYear());
        $("#nepaliToDate").val(window.nepaliDatePickerExt.fromEnglishToNepali($("#toDate").val()));
        var $tableContainer = $("#loanRequestStatusTable");
        var $summaryTable = $("#summaryTable");
        var $search = $('#search');
        var summaryBalance;
        
        $("#summary").change(function() {
            if(this.checked) {
                $tableContainer.hide();
                $summaryTable.show();
            }
            else{
                $summaryTable.hide();
                $tableContainer.show();
            }
        });

        $search.on('click', function () {
            var employee = $("#employeeId").val();
            
            var empName = employee != '' && employee != null ? $("#employeeId option:selected").text() : 'Total';

            var summaryTotal = {
                drSalary : 0,
                drInt : 0,
                crSalary : 0,
                crInt : 0,
                balance : 0,
                opening: 0
            };
            let shtmlData = '<table class="table table-bordered table-dark">';
            shtmlData += '<tr><th>Name</th><th>opening</th><th>Dr. Salary</th><th>Dr. Int</th><th>Cr. Salary</th><th>Cr. Int</th><th>Balance</th></tr>';

                var fromDate = $("#fromDate").val();
                var toDate = $("#toDate").val();
                var loanType = $("#account").val();
                if (fromDate == "" || toDate == "") {
                    alert("From date and To Date are required");
                    return false;
                }
                var summaryData = {
                    drSalary : 0,
                    drInt : 0,
                    crSalary : 0,
                    crInt : 0,
                    balance : 0
                };
                
                $tableContainer.empty();
                $summaryTable.empty();
                var data = {
                    'emp_id': employee,
                    'fromDate': fromDate,
                    'toDate': toDate,
                    'loanId' : loanType
                };
                App.blockUI({target: "#hris-page-content"});
                window.app.pullDataById(document.pullLoanVoucherDetailsLink, data).then(function (success) {
                    App.unblockUI("#hris-page-content");
                    var data = success.data;
                    var balanceData = success.balanceData;
                    if(balanceData.length == 0){
                        balanceData.push({ OPENING_BALANCE : '0.00' }) ;
                    }
                    data[0].BALANCE = data[0].DEBIT_AMOUNT;
                    data.push({
                        DT: '',
                        PARTICULARS: 'Total',
                        DEBIT_AMOUNT: data[0].DEBIT_AMOUNT,
                        CREDIT_AMOUNT: data[0].CREDIT_AMOUNT,
                        BALANCE: 0
                    });
                    for (var i = 1; i < data.length - 1; i++) {
                        data[i].BALANCE = parseFloat(data[i - 1].BALANCE) + parseFloat(data[i].DEBIT_AMOUNT) - parseFloat(data[i].CREDIT_AMOUNT);
                        data[data.length - 1].DEBIT_AMOUNT = parseFloat(data[data.length - 1].DEBIT_AMOUNT) + parseFloat(data[i].DEBIT_AMOUNT);
                        data[data.length - 1].CREDIT_AMOUNT = parseFloat(data[data.length - 1].CREDIT_AMOUNT) + parseFloat(data[i].CREDIT_AMOUNT);
                    }
                    data[data.length - 1].BALANCE = data[data.length - 1].DEBIT_AMOUNT - data[data.length - 1].CREDIT_AMOUNT;
                    var htmlData = '<table class="table table-bordered table-dark"><caption style="text-align : center; font-weight : bold;">'+empName+'</caption>';
                    htmlData += '<tr><th>Date</th><th>Particulars</th><th>Debit Amount</th><th>Credit Amount</th><th>Balance</th></tr>';
                    
                    var span = true;
                    var spanTracker = 0;

                    if(data[0].PARTICULARS != 'Opening Balance'){
                        data.unshift({
                        DT: $("#fromDate").val(),
                        PARTICULARS: 'Opening Balance',
                        DEBIT_AMOUNT: '0.00',
                        CREDIT_AMOUNT: '0.00',
                        BALANCE: '0.00'
                    });
                    }
                    
                    for (var i = 0; i < data.length; i++) {
                        summaryData.drInt += data[i].PARTICULARS == 'Interest Due' ? parseFloat(data[i].DEBIT_AMOUNT) : 0 ;
                        summaryData.crSalary += data[i].PARTICULARS == 'Amount Paid' ? parseFloat(data[i].CREDIT_AMOUNT) : 0 ;
                        summaryData.crSalary += data[i].PARTICULARS == 'Cash Amount Paid' ? parseFloat(data[i].CREDIT_AMOUNT) : 0 ;


                        if(spanTracker % 3 === 0){
                            span = true;
                        }
                        if(data[i].PARTICULARS == 'Loan Taken'){
                            summaryData.drSalary+= parseFloat(data[i].DEBIT_AMOUNT);
                            span = false;
                            htmlData += '<tr><td style="vertical-align : middle;">' + data[i].DT + '</td><td>' + data[i].PARTICULARS + '</td><td>' + parseFloat(data[i].DEBIT_AMOUNT).toFixed(2) + '</td><td>' + parseFloat(data[i].CREDIT_AMOUNT).toFixed(2) + '</td><td>' + parseFloat(data[i].BALANCE).toFixed(2) + '</td></tr>';
                            continue;
                        }
                        if(data[i].PARTICULARS == 'Opening Balance'){
                            span = false;
                            htmlData += '<tr><td style="vertical-align : middle;">' + data[i].DT + '</td><td>' + data[i].PARTICULARS + '</td><td>' + parseFloat(data[i].DEBIT_AMOUNT).toFixed(2) + '</td><td>' + parseFloat(data[i].CREDIT_AMOUNT).toFixed(2) + '</td><td>' + parseFloat(data[i].BALANCE).toFixed(2) + '</td></tr>';
                            continue;
                        }
                        if(data[i].PARTICULARS == 'Cash Amount Paid'){
                            span = false;
                            htmlData += '<tr><td style="vertical-align : middle;">' + data[i].DT + '</td><td>' + data[i].PARTICULARS + '</td><td>' + parseFloat(data[i].DEBIT_AMOUNT).toFixed(2) + '</td><td>' + parseFloat(data[i].CREDIT_AMOUNT).toFixed(2) + '</td><td>' + parseFloat(data[i].BALANCE).toFixed(2) + '</td></tr>';
                            continue;
                        }
                        if(span == true){
                            span = false;
                            htmlData += '<tr><td style="vertical-align : middle;" rowspan="3">' + data[i].DT + '</td><td>' + data[i].PARTICULARS + '</td><td>' + parseFloat(data[i].DEBIT_AMOUNT).toFixed(2) + '</td><td>' + parseFloat(data[i].CREDIT_AMOUNT).toFixed(2) + '</td><td>' + parseFloat(data[i].BALANCE).toFixed(2) + '</td></tr>';
                        }
                        else{
                             htmlData += '<tr><td>' + data[i].PARTICULARS + '</td><td>' + parseFloat(data[i].DEBIT_AMOUNT).toFixed(2) + '</td><td>' + parseFloat(data[i].CREDIT_AMOUNT).toFixed(2) + '</td><td>' + parseFloat(data[i].BALANCE).toFixed(2) + '</td></tr>';
                        }          
                        spanTracker++;
                    }
                    htmlData += '</table>';
                    $tableContainer.append(htmlData);
                    $tableContainer.css('border', '1px solid gray');
                    
                    summaryData.crInt = summaryData.drInt;
                    summaryData.balance = parseFloat(data[data.length-1].BALANCE);
                    
                    shtmlData += '<tr><td>'+empName+'</td><td>'+balanceData[0].OPENING_BALANCE+'</td><td>'+Math.abs(summaryData.drSalary).toFixed(2)+'</td><td>'+Math.abs(summaryData.drInt).toFixed(2)+'</td><td>'+Math.abs(summaryData.crSalary).toFixed(2)+'</td><td>'+Math.abs(summaryData.crInt).toFixed(2)+'</td><td>'+Math.abs(summaryData.balance).toFixed(2)+'</td></tr>';

                    summaryTotal.drInt += parseFloat(summaryData.drInt);
                    summaryTotal.crInt += parseFloat(summaryData.crInt);
                    summaryTotal.balance += parseFloat(summaryData.balance);
                    summaryTotal.crSalary += parseFloat(summaryData.crSalary);
                    summaryTotal.drSalary += parseFloat(summaryData.drSalary);
                    summaryTotal.opening += parseFloat(balanceData[0].OPENING_BALANCE);
                    $summaryTable.append(shtmlData);
                    if(document.getElementById('summary').checked) {
                        $tableContainer.hide();
                        $summaryTable.show();
                    }
                    else{
                        $summaryTable.hide();
                        $tableContainer.show();
                    }
                }, function (failure) {
                    App.unblockUI("#hris-page-content");
                });
        });

        $("#excelExport").click(function () {
            $tableContainer.table2excel({
                exclude: ".noExl",
                name: "loan-journal-voucher.xls",
                filename: "loan-journal-voucher.xls"
            });
        });

    });
})(window.jQuery, window.app);
