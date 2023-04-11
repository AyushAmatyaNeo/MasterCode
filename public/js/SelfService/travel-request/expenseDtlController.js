(function ($, app) {
    'use strict';
    angular.module('hris', [])
            .controller('expenseDtlController', function ($scope, $http, $window) {
                $scope.expenseDtlFormList = [];
                $scope.counter = '';
                $scope.transportTypeList = [
                    {"id": "AP", "name": "Aeroplane"},
                    {"id": "OV", "name": "Office Vehicles"},
                    {"id": "TI", "name": "Taxi"},
                    {"id": "BS", "name": "Bus"},
                    {"id": "OF", "name": "On Foot"}
                ];
                $scope.travelDetail = {
                    departureDateMain: '',
                    returnedDate: ''
                };
                $scope.expenseDtlFormTemplate = {
                    id: 0,
                    departureDate: "",
                    departureTime: "",
                    departurePlace: "",
                    destinationDate: "",
                    destinationTime: "",
                    destinationPlace: "",
                    transportType: $scope.transportTypeList[0],
                    fare: 0,
                    allowance: 0,
                    localConveyence: 0,
                    miscExpense: 0,
                    total: 0,
                    remarks: "",
                    checkbox: "checkboxt0",
                    checked: false
                };

                var travelId = parseInt(angular.element(document.getElementById('travelId')).val());
                $scope.counter = 1;
                $scope.expenseDtlFormList.push(angular.copy($scope.expenseDtlFormTemplate));

                $scope.addExpenseDtl = function () {
                    $scope.expenseDtlFormList.push(angular.copy({
                        id: 0,
                        departureDate: "",
                        departureTime: "",
                        departurePlace: "",
                        destinationDate: "",
                        destinationTime: "",
                        destinationPlace: "",
                        transportType: $scope.transportTypeList[0],
                        fare: 0,
                        allowance: 0,
                        localConveyence: 0,
                        miscExpense: 0,
                        total: 0,
                        remarks: "",
                        checkbox: "checkboxt" + $scope.counter,
                        checked: false
                    }));
                    $scope.counter++;
                };
                $scope.deleteExpenseDtl = function () {
                    var tempT = 0;
                    var lengthT = $scope.expenseDtlFormList.length;
                    for (var i = 0; i < lengthT; i++) {
                        if ($scope.expenseDtlFormList[i - tempT].checked) {
                            var id = $scope.expenseDtlFormList[i - tempT].id;
                            if (id != 0) {
                                window.app.pullDataById(document.urlDeleteExpenseDetail, {
                                    data: {
                                        "id": parseInt(id)
                                    }
                                }).then(function (success) {
                                    $scope.$apply(function () {
                                        console.log(success.data);
                                    });
                                }, function (failure) {
                                    console.log(failure);
                                });
                            }

                            $scope.expenseDtlFormList.splice(i - tempT, 1);
                            tempT++;
                        }
                    }
                }
                $scope.total = function (fare, allowance, localConveyence, miscExpense) {
                    var fare1 = (typeof fare === 'undefined' || fare === null || isNaN(fare)) ? parseFloat(0) : parseFloat(fare);
                    var allowance1 = (typeof allowance === 'undefined' || allowance === null || isNaN(allowance)) ? parseFloat(0) : parseFloat(allowance);
                    var localConveyence1 = (typeof localConveyence === 'undefined' || localConveyence === null || isNaN(localConveyence)) ? parseFloat(0) : parseFloat(localConveyence);
                    var miscExpense1 = (typeof miscExpense === 'undefined' || miscExpense === null || isNaN(miscExpense)) ? parseFloat(0) : parseFloat(miscExpense);
                    var total = fare1 + allowance1 + localConveyence1 + miscExpense1;
                    return total || 0;
                }

                $scope.sumAllTotal = function (list) {
                    var total = 0;
                    angular.forEach(list, function (item) {
                        var total1 = $scope.total(item.fare, item.allowance, item.localConveyence, item.miscExpense);
                        total += parseFloat(total1);
                    });
                    return total;
                }

                $scope.submitExpenseDtl = function () {
                    var sumAllTotal = parseFloat(angular.element(document.getElementById('sumAllTotal')).val());
                    if ($scope.travelExpenseForm.$valid && $scope.expenseDtlFormList.length > 0) {
                        $scope.expenseDtlEmpty = 1;
                        if ($scope.expenseDtlFormList.length == 1 && angular.equals($scope.expenseDtlFormTemplate, $scope.expenseDtlFormList[0])) {
                            console.log("app log", "The form is not filled");
                            $scope.expenseDtlEmpty = 0;
                        }
                        app.serverRequest(document.urlExpenseAdd, {
                            data: {
                                expenseDtlList: $scope.expenseDtlFormList,
                                travelId: parseInt(travelId),
                                departureDate: $scope.travelDetail.departureDateMain,
                                returnedDate: $scope.travelDetail.returnedDate,
                                sumAllTotal: sumAllTotal,
                                expenseDtlEmpty: parseInt($scope.expenseDtlEmpty)
                            },
                        }).then(function (success) {
                            $scope.$apply(function () {
                                var tempData = success.data;
                                $window.location.href = document.urlExpense;
                                $window.localStorage.setItem("msg", tempData.msg);
                            });
                        }, function (failure) {
                            console.log(failure);
                        });
                    }
                }
            }).directive("datepicker", function () {
        return {
            restrict: "A",
            require: "ngModel",
            link: function (scope, elem, attrs, ngModelCtrl) {
                $(elem).val(attrs.dvalue);
                app.addDatePicker($(elem));
            }
        }
    }).directive("select2", function () {
        return {
            restrict: "A",
            require: "ngModel",
            link: function (scope, elem, attrs, ngModelCtrl) {
                $(elem).select2();
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

