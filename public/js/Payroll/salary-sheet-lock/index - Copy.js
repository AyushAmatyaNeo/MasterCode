(function ($, app) {
    'use strict';
    $(document).ready(function () {
        app.startEndDatePickerWithNepali('nepaliFromDate', 'fromDate', 'nepaliToDate', 'toDate', null, true);

        var data = document.data;
        var salarySheetList = data['salarySheetList'];
        var monthList = null;
        var getSearchDataLink = data['links']['getSearchDataLink'];
        var getGroupListLink = data['links']['getGroupListLink'];
        var regenEmpSalSheLink = data['links']['regenEmpSalSheLink'];
        var loadingLogoLink = data['loading-icon'];
        var companyList = [];
        var groupList = [];
        var payrollProcess = null;
        var selectedSalarySheetList = [];
        var selectedMonth = {};
        var $warningTable = $("#dialog-modal");

        var $fiscalYear = $('#fiscalYearId');
        var $month = $('#monthId');
        var $table = $('#table');
        var $fromDate = $('#fromDate');
        var $nepaliFromDate = $('#nepaliFromDate');
        var $toDate = $('#toDate');
        var $nepaliToDate = $('#nepaliToDate');
        var $viewBtn = $('#viewBtn');
        var $generateBtn = $('#generateBtn');
        var $companyId = $('#companyId');
        var $groupId = $('#groupId');
        var $salaryTypeId = $('#salaryTypeId');
        var $allSheetId = $('#allSheetId');
        var $bulkApproveDiv = $('#bulkApproveDiv');
        var $bulkNotApproveDiv = $('#bulkNotApproveDiv');
        var $bulkLockDiv = $('#bulkLockDiv');
        var $bulkGenerateVoucherDiv = $('#bulkGenerateVoucherDiv');
        var $bulkUnlockDiv = $('#bulkUnlockDiv');
        var $bulkActionDiv = $('#bulkActionDiv');

        var $pleaseWaitOptions = $('#please-wait-options');
        var loadingLogoLink = data['loading-icon'];
        var $cancelBtn = $('#cancelBtn');
        var loadingHtml = '<div class="sk-spinner sk-spinner-wandering-cubes"><div class="sk-cube1"></div><div class="sk-cube2"></div></div>';
        var loading_screen = null;
        var updateLoadingHtml = function () {
            loading_screen.updateOptions({
                logo: loadingLogoLink,
                backgroundColor: '#2f9e1e',
                loadingHtml: "<p class='loading-message' style='color:white !important;'>" + "Generating Voucher" + "</p>" + loadingHtml
            });
        };

        app.populateSelect($salaryTypeId, data['salaryType'], 'SALARY_TYPE_ID', 'SALARY_TYPE_NAME', null, null, 1);
        
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
        })($companyId, getSearchDataLink);

        

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
        })($groupId, getGroupListLink);

        $fiscalYear.select2();
        $month.select2();
        $companyId.select2();
        $groupId.select2();
        $salaryTypeId.select2();


        app.setFiscalMonth($fiscalYear, $month, function (years, months, currentMonth) {
            monthList = months;
        });
        var monthChangeAction = function () {
            var monthValue = $month.val();
            if (monthValue === null || monthValue == '') {
                return;
            }
            var companyValue = $companyId.val();
            var groupValue = $groupId.val();
            var salaryType = $salaryTypeId.val();
            for (var i in monthList) {
                if (monthList[i]['MONTH_ID'] == monthValue) {
                    selectedMonth = monthList[i];
                    break;
                }
            }
            selectedSalarySheetList = [];
            for (var i in salarySheetList) {
                if (salarySheetList[i]['MONTH_ID'] == monthValue &&
                        (companyValue == -1 || companyValue == salarySheetList[i]['COMPANY_ID'])
                        && (groupValue == -1 || groupValue == salarySheetList[i]['GROUP_ID'])
                        && (salaryType == -1 || salaryType == salarySheetList[i]['SALARY_TYPE_ID'])

                        ) {
                    selectedSalarySheetList.push(salarySheetList[i]);
                    if (groupValue != '-1') {
                        break;
                    }
                }
            }

            $fromDate.val(selectedMonth['FROM_DATE']);
            $nepaliFromDate.val(nepaliDatePickerExt.fromEnglishToNepali(selectedMonth['FROM_DATE']));
            $toDate.val(selectedMonth['TO_DATE']);
            $nepaliToDate.val(nepaliDatePickerExt.fromEnglishToNepali(selectedMonth['TO_DATE']));
            
        };
        $month.on('change', function () {
            monthChangeAction();
        });

        $companyId.on('change', function () {
            monthChangeAction();
        });
        
         var $sheetTable = $('#sheetTable');
         
        var grid = app.initializeKendoGrid($sheetTable, [
            {field: "SHEET_NO", title: "Sheet", width: 80},
            {field: "MONTH_EDESC", title: "Month", width: 100},
            {field: "SALARY_TYPE_NAME", title: "Salary Type", width: 100},
            {field: "GROUP_NAME", title: "Group", width: 100},
            {field: "APPROVED", title: "Approved", width: 100},
            {field: "LOCKED", title: "Locked", width: 100},
        ], null, {id: "SHEET_NO", atLast: false, fn: function (selected) {
                if (selected) {
                    $bulkActionDiv.show();
                    var approved_locked = getTableValues();
                    console.log(approved_locked);
                    if(approved_locked.includes('N',0) || approved_locked.length > 1){
                        $bulkGenerateVoucherDiv.hide();
                    }else{
                        $bulkGenerateVoucherDiv.show();
                    }                    
                } else {
                    $bulkActionDiv.hide();
                }
            }});
        
        $('#header-chb2').change(function (ev) {
            var checked = ev.target.checked;
            $('.row-checkbox2').each(function (idx, item) {
                if (checked) {
                    if (!($(item).closest('tr').is('.k-state-selected'))) {
                        $(item).click();
                    }
                } else {
                    if ($(item).closest('tr').is('.k-state-selected')) {
                        $(item).click();
                    }
                }
            });
        });

        $groupId.on("select2:select select2:unselect", function (event) {
            monthChangeAction();
        });
        
        var groupChangeFn=function(){
            let selectedGroups = $groupId.val();
            if(selectedGroups==null){
                let allGroup=[];
                $.each(groupList, function (key, value) {
                    allGroup.push(value['GROUP_ID']);
                });
                selectedGroups=allGroup;
            }
            let selectedSalaryTypeId = $salaryTypeId.val();
            if (selectedGroups !== null || selectedGroups !== '-1') {
                app.serverRequest(document.pullGroupEmployeeLink, {
                    group: selectedGroups,
                    monthId: selectedMonth['MONTH_ID'],
                    salaryTypeId: selectedSalaryTypeId
                }).then(function (response) {
                    let empLoadData=[];
                    
                    if ($groupId.val() == null) {
                        $.each(response.data, function (index, value) {
                            value['CHECKED_FLAG'] = 'N';
                            empLoadData.push(value);
                        });
                    } else {
                        empLoadData = response.data;
                    }
                    app.populateSelect($allSheetId, response.sheetData, 'SHEET_NO', 'SHEET_NO', 'ALL', -1, -1);
                    app.renderKendoGrid($sheetTable, response.sheetData);
                    $bulkActionDiv.hide();
                });
            }
        }

        $salaryTypeId.on('change', function () {
            monthChangeAction();
        });

        $viewBtn.on('click', function () {
            groupChangeFn();
        });

        function getSelectedSheets(){
            var list = grid.getSelected();
            var selectedValues = [];
            for (var i in list) {
                selectedValues.push(list[i].SHEET_NO);
            }
            return selectedValues;
        }

        function getTableValues(){
            var list = grid.getSelected();
            var selectedValues = [];
            for (var i in list) {
                selectedValues.push(list[i].APPROVED);
                // selectedValues.push(list[i].LOCKED);
            }
            return selectedValues;
        }
        

        $bulkApproveDiv.bind("click", function () {
            var selectedValues = getSelectedSheets();
            // console.log(selectedValues);

            app.serverRequest(document.bulkActionLink, {data : selectedValues, action : 'A'}).then(function (success) {
                $viewBtn.trigger('click');
                app.showMessage('Sheets Approved', 'success');
            }, function (failure) {

            });
        });
        $bulkNotApproveDiv.bind("click", function () {
            var selectedValues = getSelectedSheets();
            app.serverRequest(document.bulkActionLink, {data : selectedValues, action : 'NA'}).then(function (success) {
                $viewBtn.trigger('click');
                app.showMessage('Sheets Unapproved', 'success');
            }, function (failure) {

            });
        });
        $bulkLockDiv.bind("click", function () {
            var selectedValues = getSelectedSheets();
            app.serverRequest(document.bulkActionLink, {data : selectedValues, action : 'L'}).then(function (success) {
                $viewBtn.trigger('click');
                app.showMessage('Sheets Locked', 'success');
            }, function (failure) {

            });
        });
        $bulkUnlockDiv.bind("click", function () {
            var selectedValues = getSelectedSheets();
            app.serverRequest(document.bulkActionLink, {data : selectedValues, action : 'UL'}).then(function (success) {
                $viewBtn.trigger('click');
                app.showMessage('Sheets Unlocked', 'success');
            }, function (failure) {

            });
        });
        $bulkGenerateVoucherDiv.bind("click", function () {
            var selectedValues = getSelectedSheets();
            
            
            loading_screen = pleaseWait({
                logo: loadingLogoLink,
                backgroundColor: '#2f9e1e',
                loadingHtml: "<p class='loading-message' style='color:white !important;'>" + "Generating Voucher" + "</p>" + loadingHtml
            });
            
            // var counter = 0;
            // counter++;
            // $pleaseWaitOptions.show();
            // if (counter>2){
            //     loading_screen.finish();
            //     $pleaseWaitOptions.hide();
            // }
            app.serverRequest(document.generateVoucherLink, {data : selectedValues}).then(function (success) {
                $viewBtn.trigger('click');
                updateLoadingHtml();
                if(success.success){
                    loading_screen.finish();
                    $pleaseWaitOptions.hide();
                }
                app.showMessage('Voucher Generated.', 'success');
                if(success.unmapped.length > 0){
                    var html = '<h3 align="center">Unmapped Employees</h3><table class="table table-striped header-fixed"><tr><th>Employee ID</th><th>Employee Name</th><th>Account Code</th><th>Account Name</th></tr>';
                    for(var i in success.unmapped){
                        html+="<tr><td>"+success.unmapped[i].EMPLOYEE_ID+"</td><td>"+success.unmapped[i].FULL_NAME+"</td><td>"+success.unmapped[i].ACC_CODE+"</td><td>"+success.unmapped[i].ACC_NAME+"</td></tr>";
                    }
                    html+="</table>";
                    $( ".modal-body" ).append(html);
                    $( "#modal-toggle" ).click();

                }
            }, function (failure) {

            });
            
        });
        $pleaseWaitOptions.hide();
        $cancelBtn.on('click', function () {
            loading_screen.finish();
            $pleaseWaitOptions.hide();
        });
        
        $(document).on('click', '#excelExport', function(){    
            $("#unmapped-table").table2excel({
                exclude: ".noExl",
                name: "Unmapped Employees",
                filename: "Unmapped Employees.xls" 
            });
        });
    });
})(window.jQuery, window.app);


