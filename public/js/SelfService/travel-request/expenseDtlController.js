(function ($, app) {
    'use strict';
    angular.module('hris', [])
        .controller('expenseDtlController', function ($scope, $http, $window) {
            $scope.expenseDtlFormList = [];
            $scope.counter = '';

            var currencyList = document.currencyList;
            $scope.currencyTypeList = currencyList;
            $scope.standardExchangeRate = '';

            $scope.transportTypeList = [
                { "id": "AP", "name": "Aeroplane" },
                { "id": "OV", "name": "Office Vehicles" },
                { "id": "TI", "name": "Taxi" },
                { "id": "BS", "name": "Bus" },
                { "id": "OF", "name": "On Foot" }
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
                currencyType: $scope.currencyTypeList[0],
                standardExchangeRate: "",
                exchangeRate: 0,
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
                    localConveyence: 0,
                    allowance: 0,
                    miscExpense: 0,
                    currencyType: $scope.currencyTypeList[0],
                    standardexchangeRate: "",
                    exchangeRate: 0,
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
            $scope.total = function (fare, localConveyence, miscExpense, allowance, standardExchangeRate, exchangeRate) {
                var fare1 = (typeof fare === 'undefined' || fare === null || isNaN(fare)) ? parseFloat(0) : parseFloat(fare);
                var allowance1 = (typeof allowance === 'undefined' || allowance === null || isNaN(allowance)) ? parseFloat(0) : parseFloat(allowance);
                var localConveyence1 = (typeof localConveyence === 'undefined' || localConveyence === null || isNaN(localConveyence)) ? parseFloat(0) : parseFloat(localConveyence);
                var miscExpense1 = (typeof miscExpense === 'undefined' || miscExpense === null || isNaN(miscExpense)) ? parseFloat(0) : parseFloat(miscExpense);
                var standardExchangeRate = (typeof standardExchangeRate === 'undefined' || standardExchangeRate === null || isNaN(standardExchangeRate)) ? parseFloat(0) : parseFloat(standardExchangeRate);
                var exchangeRate = (typeof exchangeRate === 'undefined' || exchangeRate === null || isNaN(exchangeRate)) ? parseFloat(0) : parseFloat(exchangeRate);

                var rate = exchangeRate ? exchangeRate : standardExchangeRate;
                //console.log(dailyAllowance1);
                var total = (fare1 + localConveyence1 + miscExpense1 + allowance1) * rate;
                // console.log(total);

                return total || 0;
            }

            $scope.sumAllTotal = function (list) {
                var total = 0;
                angular.forEach(list, function (item) {
                    var total1 = $scope.total(item.fare, item.localConveyence, item.miscExpense, item.allowance, item.standardExchangeRate, item.exchangeRate);
                    // console.log(total1);
                    total += parseFloat(total1.toFixed(2));

                });
                return total;
            }

            $scope.onCurrencyTypeChange = function (code) {
                var currentDate = new Date();
                var year = currentDate.getFullYear();
                var month = (currentDate.getMonth() + 1).toString().padStart(2, '0');
                var day = currentDate.getDate().toString().padStart(2, '0');
                var formattedDate = year + '-' + month + '-' + day;
                var requestData = {
                    page: 1,
                    per_page: 1,
                    from: formattedDate,
                    to: formattedDate,
                };

                // Return the promise directly
                return $http.get("https://www.nrb.org.np/api/forex/v1/rates", { params: requestData })
                    .then(function (response) {
                        var currencyVal = response.data.data.payload[0].rates;
                        var length = currencyVal.length;

                        var standardExchangeRate = 0;

                        for (var i = 0; i < length; i++) {
                            if (code == 'NPR') {
                                standardExchangeRate = 1;
                                break;
                            } else if (currencyVal[i].currency.iso3 == code) {
                                standardExchangeRate = parseFloat(currencyVal[i].buy / currencyVal[i].currency.unit);
                                break;
                            }
                        }

                        // Return the standardExchangeRate value
                        return standardExchangeRate;
                    });
            };


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
                    // app.addDatePicker($(elem));

                    // var dt2= new ($('fromdate').val());
                    // dt2.setDate(dt2.getDate() +7);
                    // var date2=dt2.getDate();
                    // var month2=dt2.getMonth();//Be careful! January is 0 not 1
                    // var year2=dt2.getFullYear();

                    // var months=['JAN','FEB','MAR','APR','MAY','JUN','JUL','AUG','SEP','OCT','NOV','DEC'];
                    // var extendedStartDate= (date2 +"-"+(months[month2]+"-"+year2));

                    // var dt1= new ($('returndate').val());
                    // dt1.setDate(dt1.getDate());
                    // var date1=dt1.getDate();
                    // var month1=dt1.getMonth();//Be careful! January is 0 not 1
                    // var year1=dt1.getFullYear();

                    // var extendedEndDate= (date1 +"-"+(months[month1]+"-"+year1));

                    // console.log('asdf');console.log($('#fromdate').val());

                    // $('travelExpense tbody').find('.depDateInternational:last').datepicker('setStartDate', app.getSystemDate(extendedStartDate));
                    // $('travelExpense tbody').find('.depDateInternational:last').datepicker('setStartDate', app.getSystemDate(extendedEndDate));

                    //to give user only to select betweeb from date and to date
                    $(elem).datepicker({
                        format: 'dd-M-yyyy',
                        todayHighlight: true,
                        autoclose: true,
                        setDate: new Date()
                    });
                    $(elem).datepicker('setStartDate', app.getSystemDate($('#fromdate').val()));
                    $(elem).datepicker('setEndDate', app.getSystemDate($('#returnDate').val()));
                    // ,app.getSystemDate('02-02-2023')
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
    // $(document).ready(function () {
    //     $('.categoryType').on('change', function(){
    //         // var categoryType = $scope.categoryType;
    //         console.log($('#categoryType').val());
    //         console.log(document.categoryWisePercentage[$('#categoryType').val()]);

    //         $(this).closest("tr").find("td input.expenseValue").val(5);

    //     });
    // });

    function updateStandardExchangeRatesOnLoad() {
        $(".currencyType").each(function () {
            var code = $(this).val();
            var $inputElement = $(this);

            var scope = angular.element(document.getElementById('currencyType')).scope();

            scope.onCurrencyTypeChange(code).then(function (standardExchangeRate) {
                $inputElement.closest("tr").find("td input.standardExchangeRate").val(standardExchangeRate);
                $inputElement.closest("tr").find("td input.standardExchangeRate").trigger('change');
            });
        });
    }

    // Call the function when the page loads
    $(document).ready(function () {
        updateStandardExchangeRatesOnLoad();
    });


    $(document).on('click', ".addExpense", function () {
        updateStandardExchangeRatesOnLoad();

    });

    $(document).on('change', ".categoryType", function () {
        var travelCategoryValue = parseFloat($(this).closest("tr").find("td input.travelCategoryValue").val());
        // console.log(travelCategoryValue);
        var percentage = parseFloat(document.categoryWisePercentage[$(this).val()]);
        var expenseValue = travelCategoryValue * percentage / 100;
        $(this).closest("tr").find("td input.expenseValue").val(expenseValue);
        $(this).closest("tr").find("td input.expenseValue").trigger('change');
    });

    $(document).on('change', ".currencyType", function () {
        var code = $(this).val();

        var $inputElement = $(this);
        var scope = angular.element(document.getElementById('currencyType')).scope();

        scope.$apply(function () {
            scope.onCurrencyTypeChange(code).then(function (standardExchangeRate) {
                alert("Standard Exchange Rate of 1 " + code + " is Rs " + standardExchangeRate + " according to Nepal Rastra Bank.");
                $inputElement.closest("tr").find("td input.standardExchangeRate").val(standardExchangeRate);
                $inputElement.closest("tr").find("td input.standardExchangeRate").trigger('change');
            });
        });
    });





    $(document).on('click', ".deleteExpense, .addExpense", function () {
        $(".travelCategoryValue").val(document.dailyAllowance);
        $(".travelCategoryValue:last").val(document.dailyAllowanceRet);
        $(".categoryType").trigger('change');
    });

    //to make from and to date as travel request in travl expense claim 

    // var dt2= new ($('fromdate').val());
    // dt2.setDate(dt2.getDate() +7);
    // var date2=dt2.getDate();
    // var month2=dt2.getMonth();//Be careful! January is 0 not 1
    // var year2=dt2.getFullYear();

    // var months=['JAN','FEB','MAR','APR','MAY','JUN','JUL','AUG','SEP','OCT','NOV','DEC'];
    // var extendedStartDate= (date2 +"-"+(months[month2]+"-"+year2));

    // var dt1= new ($('returndate').val());
    // dt1.setDate(dt1.getDate() +7);
    // var date1=dt1.getDate();
    // var month1=dt1.getMonth();//Be careful! January is 0 not 1
    // var year1=dt1.getFullYear();

    // var extendedEndDate= (date1 +"-"+(months[month1]+"-"+year1));

    // $('travelExpense tbody').find('.depDateInternational:last').datepicker('setStartDate', app.getSystemDate(extendedStartDate));
    // $('travelExpense tbody').find('.depDateInternational:last').datepicker('setStartDate', app.getSystemDate(extendedEndDate));


})(window.jQuery, window.app);

