(function ($, app) {
    'use strict';
    $(document).ready(function () {
        $('select').select2();
        app.datePickerWithNepali("form-loanDate", "nepaliDate");
        /* prevent past event post */
        //        $('#form-loanDate').datepicker("setStartDate", new Date());
        $('#form-loanDate').datepicker("setStartDate",);
        /* end of  prevent past event post */
        app.setLoadingOnSubmit("loanApprove-form");
        app.setLoadingOnSubmit("loan-form");

        $("#loanId").on('input change', function () {
            var loanId = $("#loanId").val();
            for (let i = 0; i < document.rateDetails.length; i++) {
                if (document.rateDetails[i].LOAN_ID == loanId) {
                    $("#interestRate").val(document.rateDetails[i].INTEREST_RATE);
                    document.rateDetails[i].IS_RATE_FLEXIBLE == 'Y' ? $("#interestRate").removeAttr('readonly') : $("#interestRate").attr('readonly', 'readonly');
                    $("#requestedAmount").attr("max", document.rateDetails[i].MAX_AMOUNT);
                    $("#requestedAmount").attr("min", document.rateDetails[i].MIN_AMOUNT);
                    break;
                }
            }
        });

        var employeeId = $('#employeeId').val();
        window.app.floatingProfile.setDataFromRemote(employeeId);
    });
})(window.jQuery, window.app);

