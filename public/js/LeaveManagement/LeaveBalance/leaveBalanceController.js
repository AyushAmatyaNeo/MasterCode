/**
 * Created by root on 11/7/16.
 */
(function ($, app) {
    'use strict';
    $(document).ready(function () {
        $('select').select2();
    });
})(window.jQuery, window.app);

angular.module('hris', [])
        .controller('leaveBalanceController', function ($scope, $http) {
            $scope.leaves = document.leaves;
            $scope.allList = {};
            console.log($scope.leaves);

            $scope.view = function () {
                var employeeId = angular.element(document.getElementById('employeeId')).val();
                var branchId = angular.element(document.getElementById('branchId')).val();
                var departmentId = angular.element(document.getElementById('departmentId')).val();
                var designationId = angular.element(document.getElementById('designationId')).val();
                var positionId = angular.element(document.getElementById('positionId')).val();
                var serviceTypeId = angular.element(document.getElementById('serviceTypeId')).val();
                var serviceEventTypeId = angular.element(document.getElementById('serviceEventTypeId')).val();

                //console.log(employeeId+","+branchId+","+departmentId+","+designationId+","+positionId+","+serviceTypeId);

                window.app.pullDataById(document.url, {
                    action: 'pullLeaveBalanceDetail',
                    data: {
                        'employeeId': employeeId,
                        'branchId': branchId,
                        'departmentId': departmentId,
                        'designationId': designationId,
                        'positionId': positionId,
                        'serviceTypeId': serviceTypeId,
                        'serviceEventTypeId': serviceEventTypeId
                    }
                }).then(function (success) {
                    $scope.$apply(function () {
                        $scope.allList = success.allList;
                        $scope.num = success.num;

                        initializeHeaders($scope.leaves);
                        initializeDatas($scope.leaves, $scope.allList);
                        initializekendoGrid(headers, datas);
                    });
                }, function (failure) {
                    console.log(failure);
                });

            };


            var headers = [];
            var datas = [];

            var initializeHeaders = function (cols) {
                headers = [];
                headers.push({field: "EMPLOYEE_NAME", title: "Employee Name"});
                for (var i in cols) {
                    headers.push({
                        field: "h" + i,
                        title: cols[i].LEAVE_ENAME,
                        template:'#if(h'+i+'.val>0){# <a href="'+document.leaveApplyUrl+'/#=h'+i+'.eID#/#=h'+i+'.leaveId#">#=h'+i+'.val#</a> #}else{# #=h'+i+'.val#  #}#'
                    });       
                }
            };

            var initializeDatas = function (cols, rows) {
                datas = [];
                for (var i in rows) {
                    var temp = {};
                    temp.EMPLOYEE_NAME = i;
                    for (var j in rows[i]) {
                        temp["h" + j  ] ={val: rows[i][j].BALANCE,eID:rows[i][j].EMPLOYEE_ID,leaveId:rows[i][j].LEAVE_ID};
                    }
                    datas.push(temp);
                }
            };
            var initializekendoGrid = function (columns, datas) {
               // console.log(datas);
                $("#leaveBalanceTable").kendoGrid({
                    //toolbar: ["excel"],
                    excel: {
                        fileName: "LeaveBalance.xlsx",
                        filterable: true,
                        allPages: true
                    },
                    dataSource: {
                        data: datas,
                        pageSize: 20,
                    },
                    height: 450,
                    scrollable: true,
                    sortable: true,
                    filterable: true,
                    dataBound: gridDataBound,
                    pageable: {
                        input: true,
                        numeric: false
                    },
                    columns: columns
                });

            };
            function gridDataBound(e) {
                var grid = e.sender;
                if (grid.dataSource.total() == 0) {
                    var colCount = grid.columns.length;
                    $(e.sender.wrapper)
                            .find('tbody')
                            .append('<tr class="kendo-data-row"><td colspan="' + colCount + '" class="no-data">There is no data to show in the grid.</td></tr>');
                }
            };

//            $("#export").click(function (e) {
//                var grid = $("#leaveBalanceTable").data("kendoGrid");
//                grid.saveAsExcel();
//            });
        });