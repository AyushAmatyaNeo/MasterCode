(function ($, app) {
    'use strict';
    angular.module('hris', [])
            .controller('edit', function ($scope, $http, $window) {
                $scope.overtimeDetailList = [];
                $scope.calculateHour = function (startTime, endTime) {
                    var tim_i = new Date("01/01/2007 " + startTime);
                    var tim_o = new Date("01/01/2007 " + endTime);

                    var diff1 = (tim_i - tim_o) / 60000; //dividing by seconds and milliseconds
                    if (startTime.includes('PM') && endTime.includes('AM')) {
                        diff1 = 1440 - diff1;
                    }
                    var diff = Math.abs(diff1);
                    var minutes = diff % 60;
                    var hours = (diff - minutes) / 60;

                    var total_tim = hours + ':' + minutes;
                    return total_tim;
                }

                $scope.totalCalculateHour = function (startTime, endTime) {

                    var tim_i = new Date("01/01/2007 " + startTime);
                    var tim_o = new Date("01/01/2007 " + endTime);

                    var diff1 = (tim_i - tim_o) / 60000; //dividing by seconds and milliseconds
                    if (startTime.includes('PM') && endTime.includes('AM')) {
                     diff1=1440-diff1;
                    }
                    var diff = Math.abs(diff1);
                    return diff;
                }
                

                $scope.sumAllTotalHour = function (list) {
                    var total = 0;
                    angular.forEach(list, function (item) {
//                        console.log($('#totalHour'));
//                        var total1 = $scope.totalCalculateHour(item.totalHour); //FOR SHIVAM CEMENT TO ENTER OVERTIME MANUALLY
                        var total1 = $scope.totalCalculateHour(item.startTime, item.endTime);
                        total += parseFloat(total1);
                    });
                    var minutes = total % 60;
                    var hours = (total - minutes) / 60;

                    var total_tim = hours + ':' + minutes;
                    return total_tim;
                }


                $scope.overtimeDetailTemplate = {
                    detailId: 0,
                    startTime: "",
                    endTime: "",
                };
                var overtimeId = parseInt(angular.element(document.getElementById('overtimeId')).val());
                if (overtimeId !== 0) {
                    window.app.pullDataById(document.urlOvertime, {
                            'overtimeId': overtimeId
                    }).then(function (success) {
                        $scope.$apply(function () {
                            var overtimeDetailList = success.data;
                            var num=success.data.length;
                            if (num > 0) {
                                $scope.counter = num;
                                    $scope.overtimeDetailList.push(angular.copy({
                                        detailId: overtimeDetailList[0].DETAIL_ID,
                                        startTime: overtimeDetailList[0].START_TIME,
                                        endTime: overtimeDetailList[0].END_TIME,
                                    }));
                            } else {
                                $scope.overtimeDetailList.push(angular.copy($scope.overtimeDetailTemplate));
                            }
                        });
                    }, function (failure) {
                        console.log(failure);
                    });
                } else {
                    $scope.overtimeDetailList.push(angular.copy($scope.overtimeDetailTemplate));
                }

                $scope.counter = 1;
                $scope.addOvertimeDetail = function () {
                    $scope.overtimeDetailList.push(angular.copy({
                        detailId: 0,
                        startTime: "",
                        endTime: "",
                    }));
                    $scope.counter++;
                }

                $scope.delete = function (index) {
                    console.log(index);
                    var detailItem = $scope.overtimeDetailList[index];
                    if (detailItem.detailId != 0) {
                        window.app.pullDataById(document.urlDeleteOvertimeDetail, {
                            data: {
                                "detailId": detailItem.detailId
                            }
                        }).then(function (success) {
                            $scope.$apply(function () {
                                $scope.overtimeDetailList.splice(index, 1);
                            });
                        }, function (failure) {
                            console.log(failure);
                        });
                    } else {
                        $scope.overtimeDetailList.splice(index, 1);
                    }
                }
            }).directive("timepicker", function () {
        return {
            restrict: "A",
            require: "ngModel",
            link: function (scope, elem, attrs, ngModelCtrl) {
                $(elem).val(attrs.dvalue);
                app.addTimePicker($(elem));
            }
        }
    });
})(window.jQuery, window.app);