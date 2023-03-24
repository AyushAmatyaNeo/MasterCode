(function ($, app) {
    'use strict';
    $(document).ready(function () {
        var $employeeId = $('#employeeId');
        var $eventId = $("#eventId");
        var $title = $("#title");
        var $eventType = $("#eventType");
        var $startDate = $("#startDate");
        var $endDate = $("#endDate");
        var $nepaliStartDate = $("#nepaliStartDate");
        var $nepaliEndDate = $("#nepaliEndDate");
        var $duration = $("#duration");
        var $dailyEventHour = $("#dailyEventHour");

        $('select').select2();
        app.startEndDatePickerWithNepali('nepaliStartDate', 'startDate', 'nepaliEndDate', 'endDate', function (fromDate, toDate) {
            if (fromDate <= toDate) {
                var oneDay = 24 * 60 * 60 * 1000; // hours*minutes*seconds*milliseconds
                var diffDays = Math.abs((fromDate.getTime() - toDate.getTime()) / (oneDay));
                var newValue = diffDays + 1;
                $duration.val(newValue);
            }
        });

        var eventChange = function ($this) {
            if (typeof document.eventList === 'undefined' || document.eventList === null || document.eventList.length === 0) {
                return;
            }
            var event = document.eventList[$this.val()];
            var startDate = (event == null) ? '' : app.getSystemDate(event["START_DATE"]);
            var endDate = (event == null) ? '' : app.getSystemDate(event["END_DATE"]);
            
            if(event != null){
                $dailyEventHour.prop('readonly',true);
                $dailyEventHour.val(event['DAILY_EVENT_HOUR']);
            }else{
                $dailyEventHour.val('');
                $dailyEventHour.prop('readonly',false);
                }
        

            $title.val((event == null) ? '' : event["EVENT_NAME"]);
            $startDate.datepicker('setStartDate', startDate);
            $startDate.datepicker('setEndDate', endDate);
            $startDate.datepicker('setDate', startDate);
            $endDate.datepicker('setStartDate', startDate);
            $endDate.datepicker('setEndDate', endDate);
            $endDate.datepicker('setDate', endDate);
            $duration.val((event == null) ? '' : event["DURATION"]);
            $eventType.val((event == null) ? '' : event["EVENT_TYPE"]).trigger('change.select2');
            $(`input[type='radio'][name='isWithinCompany'][value='${(event == null) ? '' : event["IS_WITHIN_COMPANY"]}']`).prop('checked', true);
            app.lockField((event != null), [$title, $startDate,$nepaliStartDate, $endDate, $duration, $eventType, $("input[name='isWithinCompany']")]);
        };

        $eventId.on('change', function () {
            eventChange($(this));
        });
        app.floatingProfile.setDataFromRemote($employeeId.val());
        app.setLoadingOnSubmit("EventRequest", function () {
            app.lockField(false, [$title, $startDate,$nepaliStartDate, $endDate, $duration, $eventType, $("input[name='isWithinCompany']")]);
            return true;
        });
    });
})(window.jQuery, window.app);

