(function ($, app) {
    'use strict';
    $(document).ready(function () {
        $("select").select2();

        var $flatValueId = $("#flatValueId");
        var $fiscalYearId = $("#fiscalYearId");

        var $companyId = $("#companyId");
        var $branchId = $("#branchId");
        var $departmentId = $("#departmentId");
        var $designationId = $("#designationId");
        var $positionId = $("#positionId");
        var $serviceTypeId = $("#serviceTypeId");
        var $serviceEventTypeId = $("#serviceEventTypeId");
        var $employeeTypeId = $("#employeeTypeId");
        var $employeeId = $("#employeeId");

        var $searchEmployeesBtn = $('#searchEmployeesBtn');
        var $assignFlatValueBtn = $('#assignFlatValueBtn');

        var $grid = $('#flatValueDetailGrid');
        var $header = $('#flatValuesDetailHeader');
        var $table = $('#flatValueDetailTable');
        var $footer = $('#flatValueDetailFooter');

        app.populateSelect($flatValueId, document.flatValues, "FLAT_ID", "FLAT_EDESC", "Select Flat Value");
        app.populateSelect($fiscalYearId, document.fiscalYears, "FISCAL_YEAR_ID", "FISCAL_YEAR_NAME", "Select Fiscal Year");

        $searchEmployeesBtn.on('click', function () {
            if ($flatValueId.val() == -1) {
                app.showMessage("No monthly value Selected.", 'error');
                $flatValueId.focus();
                return;
            }
            if ($fiscalYearId.val() == -1) {
                app.showMessage("No fiscal year Selected.", 'error');
                $fiscalYearId.focus();
                return;
            }
            app.pullDataById(document.getFlatValueDetailWS, {
                flatId: $flatValueId.val(),
                fiscalYearId: $fiscalYearId.val(),
                employeeFilter: {
                    companyId: $companyId.val(),
                    branchId: $branchId.val(),
                    departmentId: $departmentId.val(),
                    designationId: $designationId.val(),
                    positionId: $positionId.val(),
                    serviceTypeId: $serviceTypeId.val(),
                    serviceEventTypeId: $serviceEventTypeId.val(),
                    employeeTypeId: $employeeTypeId.val(),
                    employeeId: $employeeId.val()
                }}).then(function (response) {
                initTable($fiscalYearId.val(), document.searchManager.getEmployee(), response.data);
            }, function (error) {
                console.log(error);
            });
        });

        var findMonthValue = function (serverData, employeeId) {
            var result = serverData.filter(function (item) {
                return item['EMPLOYEE_ID'] == employeeId;
            });

            if (result.length > 0) {
                return result[0]['FLAT_VALUE'];
            } else {
                return null;
            }
        };

        var initTable = function (fiscalYearId, employeeList, serverData) {
            $header.html('');
            $header.append($('<th>', {text: 'Id'}));
            $header.append($('<th>', {text: 'Name'}));
            $header.append($('<th>', {text: 'Value'}));

            $grid.html('');
            $.each(employeeList, function (index, item) {
                var $tr = $('<tr>');

                $tr.append($('<td>', {text: item['EMPLOYEE_ID']}));
                $tr.append($('<td>', {text: item['FULL_NAME']}))

                var $td = $('<td>');
                $td.append($('<input>', {type: 'number', col: 'col', row: item['EMPLOYEE_ID'], value: findMonthValue(serverData, item['EMPLOYEE_ID']), class: 'form-control'}));
                $tr.append($td);

                $grid.append($tr);
            });

            $footer.html('');
            var $tr = $('<tr>');

            $tr.append($('<td>', {text: ''}));
            $tr.append($('<td>', {text: ''}))

            var $td = $('<td>');
            $td.append($('<input>', {type: 'number', class: 'group form-control'}));
            $tr.append($td);

            $footer.append($tr);
            $table.bootstrapTable({height: 400});
        };

        $table.on('change', '.group', function () {
            var $this = $(this);
            var value = $this.val();
            $('input[col="col"]').val(value);
        });

        $assignFlatValueBtn.on('click', function () {
            var fiscalYearId = $fiscalYearId.val();
            var flatId = $flatValueId.val();

            var promiseList = [];
            App.blockUI({target: "#hris-page-content"});
            $.each($grid.find('input[col="col"]'), function (key, item) {
                var $item = $(item);
                var rowValue = $item.attr('row');
                var value = $item.val();
                if (value != null && value != "") {
                    promiseList.push(app.pullDataById(document.postFlatValueDetailWS, {
                        data: {
                            flatId: flatId,
                            fiscalYearId: fiscalYearId,
                            employeeId: rowValue,
                            flatValue: value
                        }
                    }));
                }

            });

            Promise.all(promiseList).then(function (response) {
                App.unblockUI("#hris-page-content");
                app.showMessage("Flat Value assigned successfully!!!");
            }, function (error) {
                App.unblockUI("#hris-page-content");
            });



        });



    });
})(window.jQuery, window.app);