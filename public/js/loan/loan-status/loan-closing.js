(function ($, app) {
    'use strict';
    $(document).ready(function () {
        $('select').select2();
        app.datePickerWithNepali("paidDate","nepaliDate");
        $("#totalPaid").val($("#principlePaid").val());
        var rate = parseFloat(document.rate);
        var principlePaid, interestPaid = 0, totalPaid;
        
        $("#totalPaid").on('change paste input',function(){
            totalPaid = parseFloat($("#totalPaid").val());
            principlePaid = totalPaid - interestPaid;
            totalPaid != '' ? $("#principlePaid").val(principlePaid) : $("#principlePaid").val('') ;
        });

        $("#interestPaid").on('change paste input',function(){
            interestPaid = parseFloat($("#interestPaid").val());
            totalPaid = parseFloat($("#totalPaid").val());
            principlePaid = totalPaid - interestPaid;
            totalPaid != '' ? $("#principlePaid").val(principlePaid) : $("#principlePaid").val('') ;
        });

        // $("#principlePaid").on('change paste input',function(){
        //     principlePaid = $("#principlePaid").val();
        //     totalPaid = parseInt(principlePaid) + parseInt(interestPaid);
        //     principlePaid != '' ? $("#totalPaid").val(totalPaid) : $("#totalPaid").val('') ;
        // });

        $('#calculate-interest').on('click', function(){
            var days = $("#days").val();
            var rate = $("#rate").val();
            interestPaid = parseFloat(($('#unpaidTotal').val()*rate/100)/365*days);
            $("#interestPaid").val(interestPaid||'');
            totalPaid = $("#totalPaid").val();
            principlePaid = totalPaid - interestPaid;
            totalPaid != '' ? $("#principlePaid").val(principlePaid) : $("#principlePaid").val('') ;
        });

        //$('#paidDate').datepicker("setStartDate", new Date());

        $('#cash-payment').on('submit', function(){ 
            principlePaid = parseFloat($('#principlePaid').val());
            var unpaidTotal = parseFloat($('#unpaidTotal').val());
            var remainingAmount = unpaidTotal - principlePaid;
            if(remainingAmount > 0){
                var repaymentmonths = prompt("Sum of " + remainingAmount + " is still remaining. This sum will be treated as a new loan. Please specify its repayment months.");
                $("#repaymentMonths").val(repaymentmonths);
                if(repaymentmonths == null || repaymentmonths == ''){
                    return false;
                }
            }
            return true;
        });
    });
})(window.jQuery, window.app);

