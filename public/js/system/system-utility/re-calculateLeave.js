
(function ($, app) {
    'use strict';
    $(document).ready(function () {
        var $employeeId=$('#employeeId');
        var $leaveId=$('#leaveId');
        var $submitBtn=$('#btn-recalLeave');
        $employeeId.select2();
        $leaveId.select2();
        
        app.populateSelect($employeeId,document.employeeList,'EMPLOYEE_ID','FULL_NAME','------','');
        app.populateSelect($leaveId,document.leaveList,'LEAVE_ID','LEAVE_ENAME','---------','');

        $submitBtn.on('click',function (){
            var leaveId=$('#leaveId').val();

            if (leaveId==0){
                app.showMessage("Please select Leave.");
                return;
            }
            var employeeList=[];
            var selectedEmployees=[];
            $.each($("#employeeId option:selected"), function (){
                var employeeId=$(this).val();
                var employeeData={
                    EMPLOYEE_ID: employeeId,
                    LEAVE_ID: leaveId,
                }
                selectedEmployees.push(employeeData);
            });
            if(selectedEmployees.length > 0){
                employeeList=selectedEmployees;
            }else{
                var employeeListwithLeave = [];
                $.each(document.employeeList,function (index, value){
                    var employeeData={
                        EMPLOYEE_ID: value.EMPLOYEE_ID,
                        LEAVE_ID: leaveId,
                    }
                    employeeListwithLeave.push(employeeData);
                });
                employeeList = employeeListwithLeave;
            }
            app.bulkServerRequest(document.recalLeaveLink, employeeList, function (){
                app.showMessage("Leave Report Calculation Successful!!");
            },function (data,error){
                app.showMessage(error, 'error');
            });

        });

    });
})(window.jQuery, window.app);


