(function ($, app) {
    'use strict';
    $(document).ready(function () {

        $('select').select2();
        $('#inTime').combodate({
            minuteStep: 1
        });
        $('#outTime').combodate({
            minuteStep: 1
        });

        var $attendanceDt = $("#attendanceDt");
        
        if (!($attendanceDt.is('[readonly]'))) {
            app.datePickerWithNepali("attendanceDt", "nepaliDate");
            app.getServerDate().then(function (response) {
                $attendanceDt.datepicker('setEndDate', app.getSystemDate(response.data.serverDate));
            }, function (error) {
                console.log("error=>getServerDate", error);
            });
        } else {
            app.datePickerWithNepali("attendanceDt", "nepaliDate");
        }

        var totalHour = function () {
            var inTime = $('#inTime').val();

            var tim_i = new Date("01/01/2007 " + $('#inTime').val());
            var tim_o = new Date("01/01/2007 " + $('#outTime').val());
            var diff1 = (tim_i - tim_o) / 60000; //dividing by seconds and milliseconds
            var diff = Math.abs(diff1);
            var minutes = diff % 60;
            var hours = (diff - minutes) / 60;

            var total_tim = hours + '.' + minutes;
            $("#totalHour").val(total_tim);
        };
        $("#inTime").on("change", totalHour);
        $("#outTime").on("change", totalHour);

        var $employeeId = $('#employeeId');
        app.floatingProfile.setDataFromRemote($employeeId.val());

        $employeeId.on("change", function (e) {
            app.floatingProfile.setDataFromRemote($(e.target).val());
        });
        app.setLoadingOnSubmit("attendanceByHr", function () {
            if ($('#inTime').val() == '' && $('#outTime').val() == '') {
                app.showMessage('Both Intime and Outtime are not set.', 'error');
                return false;
            }
            return true;
        });

        $("#requestForm").on('submit', function(e){

            if(!$('#nextDay').is(':checked')){
                // if($('#inTime').val() > $('#outTime').val()){
                if($('#inTime').val() > $('#outTime').val()){
                    e.preventDefault();
                    app.showMessage('In time cannot be greater than out time', 'error');
                } 
                // else{
                //     e.preventDefault();
                //     app.showMessage('In time cannot be greater than out time', 'error');
                // }
            }

        });

    });
})(window.jQuery, window.app);


