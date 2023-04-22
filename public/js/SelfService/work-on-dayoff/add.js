(function ($, app) {
    'use strict';
    $(document).ready(function () {
        $('select').select2();
        var $employeeId = $('#employeeId');
        app.startEndDatePickerWithNepali('nepaliStartDate1', 'fromDate', 'nepaliEndDate1', 'toDate', function (fromDate, toDate, startDateStr, endDateStr) {
            if (fromDate <= toDate) {
                var oneDay = 24 * 60 * 60 * 1000; // hours*minutes*seconds*milliseconds
                var diffDays = Math.abs((fromDate.getTime() - toDate.getTime()) / (oneDay));
                var newValue = diffDays + 1;
                $("#duration").val(newValue);
                var employeeId = $employeeId.val();
                checkForErrors(startDateStr, endDateStr, employeeId);  
            }
        });

        var $form = $('#workOnDayoff-form');
        var checkForErrors = function (startDateStr, endDateStr, employeeId) {
            app.pullDataById(document.wsValidateWODRequest, {startDate: startDateStr, endDate: endDateStr, employeeId: employeeId}).then(function (response) {
                if (response.data['ERROR'] === null) {
                    $form.prop('valid', 'true');
                    $form.prop('error-message', '');
                    $('#request').attr("disabled", false);
                }
                else{
                    $form.prop('valid', 'false');
                    $form.prop('error-message', response.data['ERROR']);
                    app.showMessage(response.data['ERROR'], 'error');
                    $('#request').attr('disabled', 'disabled');
                }
            }, function (error) {
                app.showMessage(error, 'error');
            });
        }


        var employeeId = $('#employeeId').val();
        app.floatingProfile.setDataFromRemote(employeeId);
        app.setLoadingOnSubmit("workOnDayoff-form");
    });
})(window.jQuery, window.app);

