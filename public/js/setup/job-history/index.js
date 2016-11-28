(function ($,app) {
    'use strict';
    $(document).ready(function () {
        $('select').select2();
        app.addDatePicker(
            $("#fromDate"),
            $("#toDate")
        );
    });
})(window.jQuery,window.app);

angular.module('hris',[])
        .controller('jobHistoryController',function($scope,$http){
            $scope.jobHistoryList = [];
            $scope.view = function () {
                var employeeId = angular.element(document.getElementById('employeeId')).val();
                var serviceEventTypeId = angular.element(document.getElementById('serviceEventTypeId')).val();
                var fromDate = angular.element(document.getElementById('fromDate1')).val();
                var toDate = angular.element(document.getElementById('toDate1')).val();

                window.app.pullDataById(document.url, {
                    action: 'pullJobHistoryList',
                    data: {
                        'fromDate': fromDate,
                        'toDate': toDate,
                        'employeeId': employeeId,
                        'serviceEventTypeId':serviceEventTypeId
                    }
                }).then(function (success) {
                    console.log(success.data);
                    $scope.initializekendoGrid(success.data);
                }, function (failure) {
                    console.log(failure);
                });
            }

            $scope.initializekendoGrid = function (jobHistoryRecord) {
                console.log(jobHistoryRecord);
                $("#jobHistoryTable").kendoGrid({
                    dataSource: {
                        data: jobHistoryRecord,
                        pageSize: 20
                    },
                    height: 450,
                    scrollable: true,
                    sortable: true,
                    filterable: true,
                    pageable: {
                        input: true,
                        numeric: false
                    },
                    rowTemplate: kendo.template($("#rowTemplate").html()),
                    columns: [
                        {field: "FIRST_NAME", title: "Employee Name",width: 200 },
                        {field: "START_DATE", title: "Start Date",width: 120 },
                        {field: "END_DATE", title: "End Date",width: 120},
                        {field: "SERVICE_EVENT_TYPE_NAME", title: "Service Event Type",width: 180},
                        {field: "FROM_SERVICE_NAME", title: "Service Type (From-To)" ,width: 220},
                        {field: "FROM_BRANCH_NAME", title: "Branch (From-To)" ,width: 250},
                        {field: "FROM_DEPARTMENT_NAME", title: "Department (From-To)" ,width: 300},
                        {field: "FROM_DESIGNATION_TITLE", title: "Designation (From-To)" ,width:300},
                        {field: "FROM_POSITION_NAME", title: "Position (From-To)" ,width: 300},
                        {title: "Action" ,width: 100}
                    ]
                });
            } 
        });