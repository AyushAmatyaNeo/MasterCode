(function ($, app) {
    'use strict';
    $(document).ready(function () {
        $("select").select2();

        var $employeeTable = $('#employeeTable');
        var $company = $('#company');
        var $accHead = $('#accHead');
        var $branchName = $('#branchName');
        var $payHead = $('#payHead');
        var $btnSearchEmployees = $('#btnSearchEmployees');
        var $mapPayIdAndAccCode = $('#mapPayIdAndAccCode');
        var $groupId = $('#groupId');


        // $.each(document.searchManager.getIds(), function (key, value) {
        //     $('#' + value).select2();
        // });

        // $company.select2();
        // $accHead.select2();
        // $payHead.select2();

        app.searchTable($employeeTable, ["FULL_NAME", "EMPLOYEE_CODE"]);

        var grid = app.initializeKendoGrid($employeeTable, [
            // { field: "EMPLOYEE_CODE", title: "Employee Code", width: 80 },
            // { field: "FULL_NAME", title: "Employee Name", width: 80 },
            // { field: "BRANCH_NAME", title: "Branch Name", width: 100 },

            { field: "COMPANY_NAME", title: "Company"},
            { field: "BRANCH_NAME", title: "Branch"},
            { field: "SALARY_GROUP", title: "Salary Group"},
            { field: "PAY_HEAD", title: "Pay Head"},
            { field: "ACCOUNT_HEAD", title: "Account Head"},
            { field: "TRANSACTION_TYPE", title: 'Transaction Type'},
            {field: "ID", title: "Action", width: 80,template: `
            <span class="clearfix">                             
                <a  class="btn btn-icon-only red confirmation" href="${document.deleteLink}/#:ID#" style="height:17px;" title="Delete">
                    <i class="fa fa-times"></i>
                </a>
            </span>`}
        ], null, null);
        // {
        //     id: "EMPLOYEE_CODE", atLast: false, fn: function (selected) {
        //         if (selected) {
        //             //$btnMapEmployee.show();
        //         } else {
        //             //$btnMapEmployee.hide();
        //         }
        //     }
        // });


        $company.on('change', function () {
            let selectedCompany = $(this).val();
            console.log(selectedCompany);
            console.log(document.getAccountHeadByCompany);
            let accHead = document.getAccountHeadByCompany[selectedCompany];
            let branchName = document.getBranchNameByCompany[selectedCompany];
            app.populateSelect($accHead, accHead, 'ACC_CODE', 'ACC_EDESC', '---', -1, -1);
            app.populateSelect($branchName, branchName, 'BRANCH_CODE', 'BRANCH_EDESC', '---', -1, -1);

            const data = {
                company: $(this).val()
            };
            app.serverRequest(document.getMappedAccCode, data).then(function (success) {
                app.renderKendoGrid($employeeTable, success.data);
            });
        });

        var accHeads = [];
        $accHead.val(accHeads).trigger('change.select2');

        var branchName = [];
        $branchName.val(branchName).trigger('change.select2');

        $btnSearchEmployees.on('click', function () {
            // var data = [];
            // data['company'] = $company.val();
            // data['accHead'] = $accHead.val();
            // data['payHead'] = $payHead.val();
            const data = {
                company: $company.val(),
                payHead: $payHead.val(),
                accHead: $accHead.val(),
                branchName: $branchName.val(),
                // senderOrg : $senderOrganization.val(),
                // letterReferenceNum : $letterReferenceNumber.val(),
                // receivingDept : $receivingDepartment.val(),
                // receiverName : $receiverName.val(),
                // responseFlag : $responseFlag.val(),
                // fromDate : $fromDate.val(),
                // toDate : $toDate.val(),
                // desc : $description.val(),
            };
            app.serverRequest(document.employeeListOfCompany, data).then(function (success) {
                app.renderKendoGrid($employeeTable, success.data);
            });
        });

        $mapPayIdAndAccCode.on('click', function () {
            // var data = [];
            // data['company'] = $company.val();
            // data['accHead'] = $accHead.val();
            // data['payHead'] = $payHead.val();
            // var list = grid.getSelected();
            // var selectedEmployeeId = [];
            // for (var i in list) {
            //     selectedEmployeeId.push(list[i].EMPLOYEE_CODE);
            // }
            const data = {
                company: $company.val(),
                payHead: $payHead.val(),
                accHead: $accHead.val(),
                branchName: $branchName.val(),
                groupId: $groupId.val()
                //selectedEmployeeId: selectedEmployeeId,
                // selectedEmployeeId: $selectedEmployeeId,
                // senderOrg : $senderOrganization.val(),
                // letterReferenceNum : $letterReferenceNumber.val(),
                // receivingDept : $receivingDepartment.val(),
                // receiverName : $receiverName.val(),
                // responseFlag : $responseFlag.val(),
                // fromDate : $fromDate.val(),
                // toDate : $toDate.val(),
                // desc : $description.val(),
            };
            // app.serverRequest(document.insertIntoAccCodeMap, data);
            app.serverRequest(document.insertIntoAccCodeMap, data).then(function (response){
				const data = {
					company: $company.val()
				};
				app.serverRequest(document.getMappedAccCode, data).then(function (success) {
					app.renderKendoGrid($employeeTable, success.data);
				});
				window.toastr.success("Account Code Mapped!!!", "Notifications");
			}, function(error){
			});
        });
    });

})(window.jQuery, window.app);


