(function ($, app) {
    //    'use strict';
    $(document).ready(function () {
        $('select').select2();

        app.startEndDatePickerWithNepali('nepaliStartDate1', 'startDate', 'nepaliEndDate1', 'endDate')

    })
})(window.jQuery, window.app);


