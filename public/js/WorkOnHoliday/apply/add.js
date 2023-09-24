(function ($, app) {
    'use strict';
    $(document).ready(function () {
        const START_DATE = "START_DATE";
        const END_DATE = "END_DATE";
        const HOLIDAY_ENAME = "HOLIDAY_ENAME";
        const HOLIDAY_ID = "HOLIDAY_ID";

        $('select').select2();
        var $form = $('#workOnHoliday-form');
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


        var $employeeId = $('#employeeId');
        var $holidayId = $('#holidayId');
        var $fromDate = $("#fromDate");
        var $toDate = $("#toDate");

        var holidayList = [];

        var employeeChange = function (employeeId) {
            app.floatingProfile.setDataFromRemote(employeeId);
            app.pullDataById(document.pullHolidaysForEmployeeLink, {
                'employeeId': employeeId
            }).then(function (success) {
                holidayList = success.data;
                $holidayId.html($('<option style="display:none" value="" disabled="" selected>select a type</option>'));
                $.each(holidayList, function () {
                    var row = this;
                    $holidayId.append($('<option />').text(row[HOLIDAY_ENAME] + " (" + row[START_DATE] + " to " + row[END_DATE] + ")").val(row[HOLIDAY_ID]));
                });
            });
        };

        $employeeId.on("change", function () {
            employeeChange($(this).val());
        });
        var holidayChange = function ($this) {
            var holiday = holidayList.filter(function (item) {
                return item[HOLIDAY_ID] === $this.val();
            })[0];
            var startDate = app.getSystemDate(holiday[START_DATE]);
            var endDate = app.getSystemDate(holiday[END_DATE]);

            $fromDate.datepicker('setStartDate', startDate);
            $fromDate.datepicker('setEndDate', endDate);
            $toDate.datepicker('setStartDate', startDate);
            $toDate.datepicker('setEndDate', endDate);

            $fromDate.datepicker('setDate', startDate);
            $toDate.datepicker('setDate', endDate);

        };
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
        $holidayId.on('change', function () {
            holidayChange($(this));
        });

        app.setLoadingOnSubmit("workOnHoliday-form");
    });
})(window.jQuery, window.app);

