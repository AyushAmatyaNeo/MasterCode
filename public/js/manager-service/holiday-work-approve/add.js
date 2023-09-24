(function ($, app) {
    'use strict';
    $(document).ready(function () {
        const START_DATE = "START_DATE";
        const END_DATE = "END_DATE";
        const HOLIDAY_ENAME = "HOLIDAY_ENAME";
        const HOLIDAY_ID = "HOLIDAY_ID";

        $('select').select2();
        var $applyStatus = $('#applyStatus');

        app.startEndDatePickerWithNepali('nepaliStartDate1', 'fromDate', 'nepaliEndDate1', 'toDate', function (fromDate, toDate) {
            if (fromDate <= toDate) {
                var oneDay = 24 * 60 * 60 * 1000; // hours*minutes*seconds*milliseconds
                var diffDays = Math.abs((fromDate.getTime() - toDate.getTime()) / (oneDay));
                var newValue = diffDays + 1;
                $("#duration").val(newValue);
            }
        });


        var $employeeId = $('#employeeId');
        var $holidayId = $('#holidayId');
        var $fromDate = $("#fromDate");
        var $toDate = $("#toDate");

        var holidayList = [];

        var employeeChange = function (employeeId) {

            var approveOptions = [{
                'ID': 'AP',
                'VAL': 'Approved'
            }];

            let eid = $("#employeeId").val();
            let is_approver = 'N';
            for(let key in document.approvers){
                if(eid == key){
                    is_approver = document.approvers[key];
                }
            }
            if(is_approver == 'N'){ approveOptions = []; }
            app.populateSelect($applyStatus, approveOptions, 'ID', 'VAL', 'Pending', 'RQ');

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

        $holidayId.on('change', function () {
            holidayChange($(this));
        });

        app.setLoadingOnSubmit("workOnHoliday-form");
    });
})(window.jQuery, window.app);

