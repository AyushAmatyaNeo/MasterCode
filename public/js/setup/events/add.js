(function ($, app) {
    'use strict';
    $(document).ready(function () {
        var $duration = $("#duration");
        $('select').select2();
        app.startEndDatePickerWithNepali('nepaliStartDate', 'startDate', 'nepaliEndDate', 'endDate', function (fromDate, toDate) {
            if (fromDate <= toDate) {
                var oneDay = 24 * 60 * 60 * 1000;
                var diffDays = Math.abs((fromDate.getTime() - toDate.getTime()) / (oneDay));
                var newValue = diffDays + 1;
                $duration.val(newValue);
            }
        });
        app.lockField(true, [$duration]);
        app.setLoadingOnSubmit("Event", function () {
            app.lockField(false, [$duration]);
            return true;
        });
    });
})(window.jQuery, window.app);


