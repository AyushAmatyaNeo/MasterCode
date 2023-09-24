(function ($, app) {
    'use strict';
    $(document).ready(function () {
        $('select').select2();
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
        var $holidayId = $('#holidayId');
        var $fromDate = $("#fromDate");
        var $toDate = $("#toDate");
        var $form = $('#workOnHoliday-form');
        var $request = $("#request");

        var checkForErrors = function (startDateStr, endDateStr, employeeId) {
            app.pullDataById(document.wsValidateWOHRequest, { startDate: startDateStr, endDate: endDateStr, employeeId: employeeId }).then(function (response) {
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

        app.populateSelect($holidayId, document.holidayList, 'HOLIDAY_ID', 'HOLIDAY_ENAME', "Select a holiday", null);
        var holidayChange = function ($this) {
            var value = $this.val();
            if (value == null || value == "" || value == -1) {
                return;
            }

            var startDate = app.getSystemDate(document.holidayList[value]["START_DATE"]);
            var endDate = app.getSystemDate(document.holidayList[value]["END_DATE"]);

            $fromDate.datepicker('setStartDate', startDate);
            $fromDate.datepicker('setEndDate', endDate);

            $toDate.datepicker('setStartDate', startDate);
            $toDate.datepicker('setEndDate', endDate);

            $fromDate.datepicker('setDate', startDate);
            $toDate.datepicker('setDate', endDate);

        };

        $holidayId.on('change', function () {
            holidayChange($(this));
        });

        var employeeId = $('#employeeId').val();
        window.app.floatingProfile.setDataFromRemote(employeeId);

        holidayChange($holidayId);
        app.setLoadingOnSubmit("workOnHoliday-form");
    });
})(window.jQuery, window.app);

