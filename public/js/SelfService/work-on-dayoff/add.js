(function ($, app) {
    'use strict';
    $(document).ready(function () {
        $('select').select2();
        var employeeId = $('#employeeId').val();
        var $form = $('#workOnDayoff-form');
        var $request = $("#request");

        app.startEndDatePickerWithNepali('nepaliStartDate1', 'fromDate', 'nepaliEndDate1', 'toDate', function (fromDate, toDate, startDateStr, endDateStr) {
            if (fromDate <= toDate) {
                var oneDay = 24 * 60 * 60 * 1000; // hours*minutes*seconds*milliseconds
                var diffDays = Math.abs((fromDate.getTime() - toDate.getTime()) / (oneDay));
                var newValue = diffDays + 1;
                $("#duration").val(newValue);
            }
            var employeeId = $('#employeeId').val();
            if (typeof employeeId === 'undefined' || employeeId === null || employeeId === '' || employeeId === -1) {
                var employeeId = $('#form-employeeId').val();
                if (typeof employeeId === 'undefined' || employeeId === null || employeeId === '' || employeeId === -1) {
                    return;
                }
            }
            checkForErrors(startDateStr, endDateStr, employeeId);
        });
        var checkForErrors = function (startDateStr, endDateStr, employeeId) {
            app.pullDataById(document.wsValidateWODRequest, { startDate: startDateStr, endDate: endDateStr, employeeId: employeeId }).then(function (response) {
                if (response.data['ERROR'] === null && response.WODError['ERROR'] === null && response.WOHError['ERROR'] === null) {
                    $form.prop('valid', 'true');
                    $form.prop('error-message', '');
                    $('#request').attr("disabled", false);
                } else if (response.data['ERROR'] != null) {
                    $form.prop('valid', 'false');
                    $form.prop('error-message', response.data['ERROR']);
                    app.showMessage(response.data['ERROR'], 'error');
                    $($request).attr('disabled', 'disabled');

                    // } else if (response.travelError['ERROR'] != null) {
                    //     $form.prop('valid', 'false');
                    //     $form.prop('error-message', response.travelError['ERROR']);
                    //     app.showMessage(response.travelError['ERROR'], 'error');
                    //     $($request).attr('disabled', 'disabled');

                } else if (response.WODError['ERROR'] != null) {
                    $form.prop('valid', 'false');
                    $form.prop('error-message', response.WODError['ERROR']);
                    app.showMessage(response.WODError['ERROR'], 'error');
                    $($request).attr('disabled', 'disabled');
                }
                else {
                    $form.prop('valid', 'false');
                    $form.prop('error-message', response.WOHError['ERROR']);
                    app.showMessage(response.WOHError['ERROR'], 'error');
                    $('#request').attr('disabled', 'disabled');
                }
            }, function (error) {
                app.showMessage(error, 'error');
            });
        }

        app.floatingProfile.setDataFromRemote(employeeId);
        app.setLoadingOnSubmit("workOnDayoff-form");
    });
})(window.jQuery, window.app);

