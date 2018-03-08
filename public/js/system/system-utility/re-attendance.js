(function ($, app) {
    'use strict';
    $(document).ready(function () {
        app.datePickerWithNepali('fromDate', 'nepalifromDate');

        var $employeeId = $('#employeeId');
        var $filterBtn = $('#btn-filter');
        var $submitBtn = $('#btn-reAttendnace');
        var $companyId = $('#companyId');
        var $branchId = $('#branchId');
        var $departmentId = $('#departmentId');
        var $designationId = $('#designationId');
        var $positionId = $('#positionId');
        var $employeeTypeId = $('#employeeTypeId');
        var $serviceTypeId = $('#serviceTypeId');
        var $genderId = $('#genderId');
        var $serviceEventTypeId = $('#serviceEventTypeId');
        $employeeId.select2();


        app.populateSelect($employeeId, document.employeeList, 'EMPLOYEE_ID', 'FULL_NAME', '---', '');
        app.populateSelect($companyId, document.searchValues['company'], 'COMPANY_ID', 'COMPANY_NAME', 'All Company', '');
        app.populateSelect($branchId, document.searchValues['branch'], 'BRANCH_ID', 'BRANCH_NAME', 'All Branch', '');
        app.populateSelect($departmentId, document.searchValues['department'], 'DEPARTMENT_ID', 'DEPARTMENT_NAME', 'All Departments', '');
        app.populateSelect($designationId, document.searchValues['designation'], 'DESIGNATION_ID', 'DESIGNATION_TITLE', 'All Designation', '');
        app.populateSelect($positionId, document.searchValues['position'], 'POSITION_ID', 'POSITION_NAME', 'All Position', '');
        //app.populateSelect($employeeTypeId, document.searchValues['employeeType'], 'EMPLOYEE_TYPE', 'EMPLOYEE_TYPE', 'All Employee Type', '');
        app.populateSelect($serviceTypeId, document.searchValues['serviceType'], 'SERVICE_TYPE_ID', 'SERVICE_TYPE_NAME', 'All Service Type', '');
        app.populateSelect($genderId, document.searchValues['gender'], 'GENDER_ID', 'GENDER_NAME', 'All Gender', '');
        app.populateSelect($serviceEventTypeId, document.searchValues['serviceEventType'], 'SERVICE_EVENT_TYPE_ID', 'SERVICE_EVENT_TYPE_NAME', 'All Working Type','');
        $submitBtn.on('click', function () {
            var selectedDate = $('#fromDate').val();
            if (!selectedDate) {
                app.showMessage("Please select a date First");
                return;
            }
            var employeeList = [];
            var selectedEmployees = [];
            $.each($("#employeeId option:selected"), function () {
                var employeeid = $(this).val();
                var employeeData = {
                    EMPLOYEE_ID: employeeid,
                    ATTENDANCE_DATE: selectedDate
                }
                selectedEmployees.push(employeeData);
            });

            if (selectedEmployees.length > 0) {
                employeeList = selectedEmployees;
            } else {
                var employeeListWithDate = [];
                $.each(document.employeeList, function (index, value) {
                    var employeeData = {
                        EMPLOYEE_ID: value.EMPLOYEE_ID,
                        ATTENDANCE_DATE: selectedDate
                    }
                    employeeListWithDate.push(employeeData);
                });
                employeeList = employeeListWithDate;
            }
            app.bulkServerRequest('', employeeList, function () {
                app.showMessage("Reattendance Successful.");
            }, function (data, error) {
                app.showMessage(error, 'error');
            });
        });
        $filterBtn.on('click', function () {
            $('#myModal').modal('show');
        });

        $('#filterForRole').on('click', function () {

            var companyId = $companyId.val();
            //console.log(companyId);
            var branchId = $branchId.val();
            var departmentId = $departmentId.val();
            var designationId = $designationId.val();
            var positionId = $positionId.val();
            var serviceTypeId = $serviceTypeId.val();
            var genderId = $genderId.val();
            var employeeType = $employeeTypeId.val();
            var serviceEventTypeId = $serviceEventTypeId.val();
            console.log(employeeType);
            //console.log(genderId);

            
            app.pullDataById(document.employeeFilter, {
                branchId: branchId,
                departmentId: departmentId,
                designationId: designationId,
                companyId: companyId,
                positonId: positionId,
                employeeType: employeeType,
                serviceTypeId: serviceTypeId,
                genderId: genderId,
                serviceEventTypeId: serviceEventTypeId,

            }).then(function (response) {
                //console.log(response.data);
                var data = response.data;
                console.log(data);
                var employeeIdArray = [];
                $('#myModal').modal('hide');
                $employeeId.empty();
                app.populateSelect($employeeId, data, 'EMPLOYEE_ID', 'FULL_NAME', '---', '');
                for(var i=0;i<data.length;i++){
                    employeeIdArray[i] = data[i]['EMPLOYEE_ID'];
                }
                $employeeId.select2().val(employeeIdArray).trigger('change');
                //$employeeId.select2().val().trigger('change');
                
            }, function (failure) {
                console.log("Employee list for failure", failure);
            });
        });
        

    });
})(window.jQuery, window.app);