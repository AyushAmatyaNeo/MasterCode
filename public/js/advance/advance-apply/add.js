(function ($, app) {
    'use strict';
    $(document).ready(function () {
        $('select').select2();
        app.datePickerWithNepali('dateOfadvance', 'nepalidateOfadvance');

        var $employeeId = $('#employeeId');
        var $advance = $('#advanceId');
        var $recommender = $('#overrideRecommenderId');
        var $approver = $('#overrideApproverId');
        var $deductionType = $('#deductionType');
        var $deductionRate = $('#deductionRate');
        var $deductionIn = $('#deductionIn');
        var $overrideDeductionMonth = $('#overrideDeductionMonth');
        var $overrideDeductionPer = $('#overrideDeductionPer');

        var monthlySalary;
        var salary;
        var advanceList;

        var advanceDetails;
        var $requestAmt = $('#requestedAmount');
        var $monthlyDeductionPercentage = $('#deductionRate');
        var $monthToRepay = $('#deductionIn');
        var maxRequestAmt;
        var monthlyDeductionValue = 0;

        app.populateSelect($employeeId, document.employeeList, 'EMPLOYEE_ID', 'FULL_NAME', '---', '');
        app.populateSelect($recommender, document.employeeList, 'EMPLOYEE_ID', 'FULL_NAME', '---', '');
        app.populateSelect($approver, document.employeeList, 'EMPLOYEE_ID', 'FULL_NAME', '---', '');


        function searchList(arrayList, searchField, searchValue) {
            for (var i = 0; i < arrayList.length; i++) {
                if (eval('arrayList[i].' + searchField) === searchValue) {
                    return arrayList[i];
                }
            }
        }


        $employeeId.on('change', function () {
            var selectedEmpVal = $(this).val();
            if (selectedEmpVal) {
                app.floatingProfile.setDataFromRemote(selectedEmpVal);
                // start request to get advanceList of Employee
                app.serverRequest(document.pullEmployeeAdvance, {
                    employeeId: selectedEmpVal
                }).then(function (response) {
                    console.log(response);
                    if (response.success = true) {
                        advanceList = response.data;
                        monthlySalary = response.salary;
                        salary =
                            app.populateSelect($advance, response.data, 'ADVANCE_ID', 'ADVANCE_ENAME', '---', '');
                    }
                }, function (error) {
                    console.log(error);
                });
                // end request to get advanceList of Employee

                var employeeDetail = searchList(document.employeeList, 'EMPLOYEE_ID', selectedEmpVal)
                if (employeeDetail['SALHISTORY']) {
                    monthlySalary = employeeDetail['SALHISTORY'] ? employeeDetail['SALHISTORY'] : employeeDetail['SALARY'];
                    $('#monthlySalary').text('Monthly Salary = ' + employeeDetail['SALHISTORY'] ? employeeDetail['SALHISTORY'] : employeeDetail['SALARY']);
                }
            } else {
                $('#monthlySalary').text('');
                $advance.find('option').remove()
            }

        });

        function calcMonthlyDecution(deductionRateValue) {
            monthlyDeductionValue = (deductionRateValue / 100) * monthlySalary;
            $('#monthlyDeductionAmt').text('Monthly Deduction= ' + monthlyDeductionValue);
        }


        function advanceConfig(advanceData) {
            console.log(advanceData);

            /*to show recommeder approver accpording to setip value start*/
            $recommender.prop('disabled', true);
            $('#overrideRecommenderDiv').hide();
            $approver.prop('disabled', true);
            $('#overrideApproverDiv').hide();

            if (advanceDetails.OVERRIDE_RECOMMENDER_FLAG === 'Y') {
                $('#overrideRecommenderDiv').show();
                $recommender.prop('disabled', false);
            }
            if (advanceDetails.OVERRIDE_APPROVER_FLAG === 'Y') {
                $('#overrideApproverDiv').show();
                $approver.prop('disabled', false);
            }
            /*to show recommeder approver accpording to setip value start*/


            $deductionType.val(advanceDetails.DEDUCTION_TYPE);
            $('#deductionRateDiv').hide();
            $('#deductionInDiv').hide();

            if (advanceDetails.DEDUCTION_TYPE === 'M') {
                $deductionIn.prop('required', true);
                $deductionRate.prop('required', false);
                $('#deductionInDiv').show();
                $deductionIn.val(advanceDetails.DEDUCTION_IN);
                $deductionIn.prop('readonly', true);
                if (advanceDetails.ALLOW_OVERRIDE_MONTH === 'Y'
                    && advanceDetails.MAX_OVERRIDE_MONTH != null
                    && Number(advanceDetails.MAX_OVERRIDE_MONTH) > Number(advanceDetails.DEDUCTION_IN)) {
                    $deductionIn.prop('readonly', false);
                    $overrideDeductionMonth.text('Max Override Payment months=' + advanceDetails.MAX_OVERRIDE_MONTH);
                    $deductionIn.attr('max', advanceDetails.MAX_OVERRIDE_MONTH);
                    $deductionIn.attr('min', advanceDetails.DEDUCTION_IN);
                }
            }

            if (advanceDetails.DEDUCTION_TYPE === 'S') {
                $deductionRate.prop('required', true);
                $deductionIn.prop('required', false);
                $('#deductionRateDiv').show();
                $deductionRate.val(advanceDetails.DEDUCTION_RATE);
                $deductionRate.prop('readonly', true);
                if (advanceDetails.ALLOW_OVERRIDE_RATE === 'Y'
                    && advanceDetails.MIN_OVERRIDE_RATE != null
                    && advanceDetails.MIN_OVERRIDE_RATE < advanceDetails.DEDUCTION_RATE) {
                    $deductionRate.prop('readonly', false);
                    $overrideDeductionPer.text('Min Payment Override rate=' + advanceDetails.MIN_OVERRIDE_RATE);
                    $deductionRate.attr('max', advanceDetails.DEDUCTION_RATE);
                    $deductionRate.attr('min', advanceDetails.MIN_OVERRIDE_RATE);
                } else {

                }
            }


            //TO VIEW ADVANCE DETAILS START
            var selectedAdvanceDtl = advanceDetails.MAX_SALARY_RATE + ' % of ' + monthlySalary + ' up to ' + advanceDetails.MAX_ADVANCE_MONTH + ' months.'
            $('#defaultValues').text(selectedAdvanceDtl);
            //TO VIEW ADVANCE DETAILS END


            //calculate MaxRequestAmt Start
            maxRequestAmt = (advanceDetails.MAX_SALARY_RATE * advanceDetails.MAX_ADVANCE_MONTH * monthlySalary) / 100;
            $('#maxReqAmt').text("Max Request Amount=Rs " + maxRequestAmt);

            $requestAmt.attr('max', maxRequestAmt);

        }



        $advance.on('change', function () {
            $('.clearText').text(" ");
            var selectedAdvanceId = $(this).val();
            var selectedAdvanceValues = searchList(document.advanceList, 'ADVANCE_ID', selectedAdvanceId);
            if (typeof selectedAdvanceValues != 'undefined') {
                advanceDetails = selectedAdvanceValues;
                advanceConfig(selectedAdvanceValues);
            } else {
                advanceDetails = null;
            }
        });

        $monthlyDeductionPercentage.on('change keydown paste input', function () {
            var deductionRateValue = $(this).val();
            var requestAmt = $requestAmt.val();

            if (requestAmt > 0) {
                calcMonthlyDecution(deductionRateValue);
                var repaymentMonths = Math.ceil(requestAmt / monthlyDeductionValue);
                if (advanceDetails.ALLOW_OVERRIDE_MONTH == 'Y') {
                    $monthToRepay.val(repaymentMonths);
                }
            }

        });






        //        app.setLoadingOnSubmit('AdvanceRequest', function () {
        //            var deductionPercentage = $monthlyDeductionPercentage.val();
        //            var deductionMonthValue = $monthToRepay.val();
        //            var requestAmt = $requestAmt.val();
        //
        //            var totalPaymnetVal = monthlyDeductionValue * deductionMonthValue;
        //
        //            var minPercentage = (advanceDetails.ALLOW_OVERRIDE_RATE == 'Y') ? advanceDetails.MIN_OVERRIDE_RATE : advanceDetails.DEDUCTION_RATE;
        //
        //            if (deductionPercentage > 100) {
        //                $('#beforeSubmitVal').text('Deduction Percetnage Cannot be Greater Than 100');
        //                return false;
        //            }
        //            if (minPercentage > deductionPercentage) {
        //                $('#beforeSubmitVal').text('Deduction Percetnage Cannot be less Than ' + minPercentage);
        //                return false;
        //            }
        //            if (requestAmt > totalPaymnetVal) {
        //                $('#beforeSubmitVal').text(requestAmt + 'cant be paid in ' + deductionMonthValue + ' Months with deduction of Rs' + monthlyDeductionValue);
        //                return false;
        //            }
        //            return true;
        //        });


    });
})(window.jQuery, window.app);
