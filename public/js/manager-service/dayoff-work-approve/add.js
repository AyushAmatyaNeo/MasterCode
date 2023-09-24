(function ($, app) {
    'use strict';
    $(document).ready(function () {
        $('select').select2();
        app.startEndDatePickerWithNepali('nepaliStartDate1', 'fromDate', 'nepaliEndDate1', 'toDate', function (fromDate, toDate) {
            if (fromDate <= toDate) {
                var oneDay = 24 * 60 * 60 * 1000; // hours*minutes*seconds*milliseconds
                var diffDays = Math.abs((fromDate.getTime() - toDate.getTime()) / (oneDay));
                var newValue = diffDays + 1;
                $("#duration").val(newValue);
            }
        });

        var $applyStatus = $("#applyStatus"); 

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
        };

        $("#employeeId").on("change", function () {
            employeeChange($(this).val());
        });

        app.floatingProfile.setDataFromRemote(employeeId);
        app.setLoadingOnSubmit("workOnDayoff-form");
    });
})(window.jQuery, window.app);

