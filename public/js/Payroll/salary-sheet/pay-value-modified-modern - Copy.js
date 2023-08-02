(function ($, app) {
    'use strict';
    $(document).ready(function () {

        $("select").select2();

        var $payHeadId = $("#payHeadId");
        var $fiscalYearId = $("#fiscalYearId");
        var $searchEmployeesBtn = $('#searchEmployeesBtn');
        var $saveChanges = $('#saveChanges');
    	let $monthId = $("#monthId");
        let $table = $("#table");
        let $employeeId = $("#employeeId");
        var $companyId = $('#companyId');
        var $groupId = $('#groupId');
        var $salaryTypeId = $('#salaryTypeId');
        salaryTypeId
        var changedValues = [];
        var companyList = null;
        var groupList = null;

        app.populateSelect($payHeadId, document.payHeads, "PAY_ID", "PAY_EDESC", "Select Pay Head");
        app.populateSelect($fiscalYearId, document.fiscalYears, "FISCAL_YEAR_ID", "FISCAL_YEAR_NAME", "Select Fiscal Year");
        $('#fiscalYearId').val($('#fiscalYearId option:last').val());
        app.populateSelect($employeeId, document.employees, "EMPLOYEE_ID", "FULL_NAME", "");
        app.populateSelect($salaryTypeId, document.salaryTypes, "SALARY_TYPE_ID", "SALARY_TYPE_NAME");
        document.getElementById("salaryTypeId").selectedIndex = "1";
        var selectedYearMonthList = document.months.filter(function (item) {
            return item['FISCAL_YEAR_ID'] == $fiscalYearId.val();
        });

        $groupId.on('change', function(){
            let groupId = $groupId.val();
            let filteredEmployees = document.employees.filter((x) => x.GROUP_ID == groupId);
            if(groupId == -1){ filteredEmployees = document.employees; }
            app.populateSelect($employeeId, filteredEmployees, "EMPLOYEE_ID", "FULL_NAME", "");
        });
        
        (function ($companyId, link) {
            var onDataLoad = function (data) {
                companyList = data['company'];
                app.populateSelect($companyId, data['company'], 'COMPANY_ID', 'COMPANY_NAME', 'Select Company');
            };
            app.serverRequest(link, {}).then(function (response) {
                if (response.success) {
                    onDataLoad(response.data);
                }
            }, function (error) {

            });
        })($companyId, data.getSearchDataLink);

        (function ($groupId, link) {
            var onDataLoad = function (data) {
                groupList = data;
                app.populateSelect($groupId, groupList, 'GROUP_ID', 'GROUP_NAME', 'Select Group');
            };
            app.serverRequest(link, {}).then(function (response) {
                if (response.success) {
                    onDataLoad(response.data);
                }
            }, function (error) {

            });
        })($groupId, data.getGroupListLink);

        app.populateSelect($monthId, selectedYearMonthList, 'MONTH_ID', 'MONTH_EDESC', 'Months');
        app.searchTable($table, ['FULL_NAME', 'EMPLOYEE_CODE']);
        $("#searchFieldDiv").hide();
        $saveChanges.hide();

        $fiscalYearId.change(function(){
    		selectedYearMonthList = document.months.filter(function (item) {
                return item['FISCAL_YEAR_ID'] == $fiscalYearId.val();
            });
            app.populateSelect($monthId, selectedYearMonthList, 'MONTH_ID', 'MONTH_EDESC', 'Months');
    	});

        $searchEmployeesBtn.on('click', function () {
            changedValues = [];
            var payHeadOptions = $("#payHeadId option");
            var payHeadId = [];
            payHeadId = $payHeadId.val();
            if(payHeadId == null || payHeadId.length == 0){
                payHeadId = $.map(payHeadOptions ,function(option) {
                    return option.value;
                });
            }
            if ($fiscalYearId.val() == -1) {
                app.showMessage("No fiscal year Selected.", 'error');
                $fiscalYearId.focus();
                return;
            }
            if ($monthId.val() == -1) {
                app.showMessage("No month Selected.", 'error');
                $monthId.focus();
                return;
            }
            if ($salaryTypeId.val() == -1) {
                app.showMessage("No Salary Type Selected.", 'error');
                $salaryTypeId.focus();
                return;
            }
            $table.empty();
            app.serverRequest(document.getPayValueModifiedLink, {
                payHeadId: payHeadId,
                //fiscalYearId: $fiscalYearId.val(),
                monthId: $monthId.val(),
                employeeId: $employeeId.val(),
                salaryTypeId: $salaryTypeId.val(),
                companyId: $companyId.val(),
                groupId: $groupId.val()}).then(function (response) {
                var columns = []; 
                columns.push({field: "COMPANY_NAME", title: "Company", width: 80, locked: true});
                columns.push({field: "GROUP_NAME", title: "Group", width: 80, locked: true});
                columns.push({field: "FULL_NAME", title: "Name", width: 90, locked: true});
                let totalRow = {};
                totalRow = {...totalRow, ...response.data[0]};
                for(let i in response.data[0]){
                    totalRow[i] = '';
                    if(i.startsWith("H_")){
                        let title = response.columns.filter((item) => item.TITLE == i);
                        columns.push({field: i, title: title[0].PAY_EDESC, width: 160,
                template: '<input type="number" class="'+i+'" value="#: '+i+'||""#" style="height:17px;">'});
                    }
                }
                response.data.push(totalRow);
                app.initializeKendoGrid($table, columns);
                app.renderKendoGrid($table, response.data);
                $("#searchFieldDiv").show();
                $("#saveChanges").show();
            }, function (error) {
                console.log(error);
            });
        });

        $table.on('input', 'input', function(e){
            var grid = $table.data("kendoGrid");
            var row = $(e.target).closest("tr");
            var dataItem = grid.dataItem(row);
            var updatedValue = this.value;
            //var data = $table.data("kendoGrid").dataItem($(e.target).closest("tr"));
            var key = this.className;
            dataItem[key] = updatedValue;

            if(row.is(":last-child") && (grid.dataSource.view().length == grid.dataSource.total())){
                $("."+key).val(updatedValue);
                var dataSource = grid.dataSource;
                $.each(grid.items(), function(index, item) {
                    var uid = $(item).data("uid");
                    var dataItem = dataSource.getByUid(uid);
                    dataItem[key] = updatedValue;
                    var index = changedValues.findIndex(function(x){
                        return x.employeeId == dataItem.EMPLOYEE_ID && x.payValue == key;
                    });
                    if(index == -1){ 
                        changedValues.push({employeeId: dataItem.EMPLOYEE_ID, payValue: key, payId: key.substring(2)}); 
                    }
                });
                return;
            }
            //var index = changedValues.findIndex(x => x.EMPLOYEE_ID==dataItem.EMPLOYEE_ID);
            var index = changedValues.findIndex(function(x){
                return x.employeeId == dataItem.EMPLOYEE_ID && x.payValue == key;
            });
            if(index == -1){ 
                changedValues.push({employeeId: dataItem.EMPLOYEE_ID, payValue: key, payId: key.substring(2)}); 
            }
        });

        $saveChanges.on('click', function () {
            var grid = $table.data("kendoGrid");
            var currentData = grid.dataSource._data;
            for(let x = 0; x < changedValues.length; x++){
                var data = currentData.filter(function(item, i) { 
                    return item.EMPLOYEE_ID == changedValues[x].employeeId;
                });
                changedValues[x].value = data[0][changedValues[x].payValue];
            }
            var monthId = $monthId.val();
            var salaryTypeId = $salaryTypeId.val();
            app.serverRequest(document.postPayValueModifiedLink, {data : changedValues, salaryTypeId: salaryTypeId, monthId : monthId}).then(function(){
                changedValues = [];
                app.showMessage('Operation successfull', 'success');
            }, function (error) {
                console.log(error);
            });
        });
    });
})(window.jQuery, window.app);
