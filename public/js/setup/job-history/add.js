(function ($, app) {
    $(document).ready(function () {
        $('select').select2();
        app.startEndDatePickerWithNepali('nepaliStartDate1', 'startDate', 'nepaliEndDate1', 'endDate');
        app.datePickerWithNepali('eventDate', 'eventDateNepali');

        var $employeeId = $("#employeeId");
        var $serviceEventTypeId = $("#serviceEventTypeId");

        var $toCompanyId = $('#toCompanyId');
        var $toBranchId = $('#toBranchId');
        var $toDepartmentId = $('#toDepartmentId');
        var $toDesignationId = $('#toDesignationId');
        var $toPositionId = $('#toPositionId');
        var $toServiceTypeId = $('#toServiceTypeId');
        var $toSalary = $("#toSalary");
        var $startDate = $('#startDate');
        var $isRetired = $('#isRetired');
        var $isDisabled = $('#isDisabled');


        app.floatingProfile.setDataFromRemote($employeeId.val());

        var getPreviousHistory = function (startDate, employeeId) {
            if (typeof startDate === "undefined" || typeof employeeId === "undefined" || startDate == null || employeeId == null || employeeId == -1) {
                return;
            }

            app.pullDataById(document.wsGetPreviousHistory, {
                employeeId: employeeId,
                startDate: startDate
            }).then(function (response) {
                var data = response.data;
                if (typeof data === "undefined" || data == null || data == false) {
                    return;
                }
                $serviceEventTypeId.val(data['SERVICE_EVENT_TYPE_ID']).trigger('change.select2');
                $toCompanyId.val(data.TO_COMPANY_ID).trigger('change.select2');
                $toBranchId.val(data.TO_BRANCH_ID).trigger('change.select2');
                $toDepartmentId.val(data.TO_DEPARTMENT_ID).trigger('change.select2');
                $toDesignationId.val(data.TO_DESIGNATION_ID).trigger('change.select2');
                $toPositionId.val(data.TO_POSITION_ID).trigger('change.select2');
                $toServiceTypeId.val(data.TO_SERVICE_TYPE_ID).trigger('change.select2');
                $toSalary.val(data.TO_SALARY);
                $isRetired.prop("checked", data.RETIRED_FLAG === "Y");
                $isDisabled.prop("checked", data.DISABLED_FLAG === "Y");


            }, function (error) {
                console.log(error)
            });
        };

        $employeeId.on('change', function () {
            var $this = $(this);
            var value = $this.val();
            app.floatingProfile.setDataFromRemote(value);
            getPreviousHistory($startDate.val(), value);
            showHistory(value);
        });
        $startDate.on('change', function () {
            var $this = $(this);
            var value = $this.val();
            getPreviousHistory(value, $employeeId.val());
        });

        var showHistory = function (employeeId) {
            app.pullDataById(document.wsGetHistoryList, { employeeId }).then(function (response) {
                console.log(response);
                if (response.success) {
                    var data = [];
                    var services = response.data;

                    $.each(services, function (key, item) {
                        data.push({
                            time: item['START_DATE'],
                            header: item['SERVICE_EVENT_TYPE_NAME'],
                            body: [{
                                tag: 'div',
                                content: `
                                            <table class="table">
                                            <tr><td>Company</td><td>${item['COMPANY_NAME']}</td></tr>
                                            <tr><td>Branch</td><td>${item['BRANCH_NAME']}</td></tr>
                                            <tr><td>Department</td><td>${item['DEPARTMENT_NAME']}</td></tr>
                                            <tr><td>Designation</td><td>${item['DESIGNATION_TITLE']}</td></tr>
                                            <tr><td>Position</td><td>${item['POSITION_NAME']}</td></tr>
                                            <tr><td>Service Type</td><td>${item['SERVICE_TYPE_NAME']}</td></tr>
                                            <tr><td>Salary</td><td>${item['TO_SALARY']}</td></tr>
                                            </table>`
                            }],
                        });
                    });
                    $('#myTimeline').albeTimeline(data);

                }
            }, function () {

            });
        };
        showHistory($employeeId.val());
        app.setDropZone($('#fileId'), $('#dropZone'), document.uploadFileLink);
    });
})(window.jQuery, window.app);


