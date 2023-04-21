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
        app.startEndDatePickerWithNepali('nepaliFromDate', 'fromDate', 'nepaliToDate', 'toDate', null, false);
        var $tableContainer = $("#loanRequestStatusTable"); 
        var $search = $('#search');

        var columns = [
            {field: "DT", title: "Date", width: 100},
            {field: "PARTICULARS", title: "Particulars", width: 150},
            {field: "DEBIT_AMOUNT", title: "Debit Rs", width: 120, format: "{0:0.##}"},
            {field: "CREDIT_AMOUNT", title: "Credit Rs", width: 120, format: "{0:0.##}"},
            {field: "BALANCE", title: "Balance", width: 120, format: "{0:0.##}"}
        ];
  
        var map = {
            'DT': 'Date',
            'PARTICULARS': 'Particulars',
            'DEBIT_AMOUNT': 'Debit Rs',
            'CREDIT_AMOUNT': 'Credit Rs',
            'BALANCE': 'Balance'
        }
        app.initializeKendoGrid($tableContainer, columns);
        app.searchTable($tableContainer, ['FULL_NAME']);

        $search.on('click', function () {
            var employee = $("#employeeId").val();
            var fromDate = $("#fromDate").val();
            var toDate = $("#toDate").val();
            var loanType = $("#account").val();
            if(employee == -1 || fromDate == "" || toDate == ""){
                alert("Employee, From date and To Date are required");
                return false;
            }
            //employee = employee == -1 ? null : employee ;
            //employee = employee == -1 ? null : employee ;
            var data = { 
                'emp_id' : employee,
                'fromDate' :  fromDate,
                'toDate' :  toDate,
                'loanId' : loanType
            };
            App.blockUI({target: "#hris-page-content"});
            window.app.pullDataById(document.pullLoanVoucherDetailsLink, data).then(function (success) {
                App.unblockUI("#hris-page-content");
                var data = success.data;
                data[0].BALANCE = data[0].DEBIT_AMOUNT;
                data.push({
                    DT : '',
                    PARTICULARS : 'Total',
                    DEBIT_AMOUNT : data[0].DEBIT_AMOUNT,
                    CREDIT_AMOUNT : data[0].CREDIT_AMOUNT,
                    BALANCE : 0
                });
                for(var i = 1; i < data.length-1; i++){
                    data[i].BALANCE = parseFloat(data[i-1].BALANCE) + parseFloat(data[i].DEBIT_AMOUNT) - parseFloat(data[i].CREDIT_AMOUNT);
                    data[data.length-1].DEBIT_AMOUNT = parseFloat(data[data.length-1].DEBIT_AMOUNT) + parseFloat(data[i].DEBIT_AMOUNT);
                    data[data.length-1].CREDIT_AMOUNT = parseFloat(data[data.length-1].CREDIT_AMOUNT) + parseFloat(data[i].CREDIT_AMOUNT);
                }
                data[data.length-1].BALANCE = data[data.length-1].DEBIT_AMOUNT - data[data.length-1].CREDIT_AMOUNT;
                
                app.renderKendoGrid($tableContainer, data);
            }, function (failure) {
                App.unblockUI("#hris-page-content");
            });
        });
        $('#excelExport').on('click', function () {
            app.excelExport($tableContainer, map, "Loan Request List.xlsx");
        });
        $('#pdfExport').on('click', function () {
            app.exportToPDF($tableContainer, map, "Loan Request List.pdf");
        });
        
//        $("#reset").on("click", function () {
//            $(".form-control").val("");
//        });
    });
})(window.jQuery, window.app);
