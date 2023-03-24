(function ($, app) {
    'use strict';
    $(document).ready(function () {
        $('select').select2();
    });
})(window.jQuery, window.app);

angular.module('hris', [])
        .controller('eventAssignController', function ($scope, $http, $window) {
            var $event = $('#eventId');

            $scope.employeeList = [];
            $scope.all = false;
            $scope.assignShowHide = false;

            $scope.checkAll = function (item) {
                for (var i = 0; i < $scope.employeeList.length; i++) {
                    $scope.employeeList[i].checked = item;
                }
                $scope.assignShowHide = item && ($scope.employeeList.length > 0);
                if ($scope.assignShowHide) {
                }
            };

            $scope.checkUnit = function (item) {
                for (var i = 0; i < $scope.employeeList.length; i++) {
                    if ($scope.employeeList[i].checked) {
                        $scope.assignShowHide = true;
                        break;
                    }
                    $scope.assignShowHide = false;
                }
            };

            $scope.view = function () {
                var eventId = $event.val();
                if (typeof eventId === 'undefined' || eventId === null || eventId === '' || eventId === '-1') {
                    window.jQuery('#eventId').focus();
                    window.app.showMessage('No Event Selected.', 'error');
                    return;
                }
                var searchData = document.searchManager.getSearchValues();
                searchData['eventId'] = eventId;
                $scope.all = false;
                $scope.assignShowHide = false;

                window.app.serverRequest(document.pullEmployeeForEventAssignLink, searchData).then(function (success) {
                    $scope.$apply(function () {
                        $scope.employeeList = success.data;
                        for (var i = 0; i < $scope.employeeList.length; i++) {
                            $scope.employeeList[i].checked = false;
                        }
                    });
                });
            };

            $scope.assign = function () {
                var eventId = angular.element(document.getElementById('eventId')).val();
                if (typeof eventId === 'undefined' || eventId === null || eventId == '' || eventId == -1) {
                    window.toastr.error("No Event Selected.", "Alert");
                    return;
                }
                var requestData = [];
                for (var index in $scope.employeeList) {
                    if ($scope.employeeList[index].checked && (eventId != $scope.employeeList[index].EVENT_ID)) {
                        requestData.push({
                            employeeId: $scope.employeeList[index].EMPLOYEE_ID,
                            eventId: eventId,
                            oldeventId: $scope.employeeList[index].EVENT_ID
                        });
                    }
                }
                app.bulkServerRequest(document.assignEmployeeEventLink, requestData, function () {
                    app.showMessage("Event Assigned Successfully.");
                    $scope.view();
                }, function (data, error) {
                    app.showMessage(error, 'error');
                });
            };

            window.jQuery('#eventId').on('change', function () {
                $scope.view();
            });

            var employeeIdFromParam = window.location.href.substr(window.location.href.lastIndexOf('/') + 1);
            if (parseInt(employeeIdFromParam) > 0) {
                angular.element(document.getElementById('employeeId')).val(employeeIdFromParam).change();
                $scope.view();
            }
        });