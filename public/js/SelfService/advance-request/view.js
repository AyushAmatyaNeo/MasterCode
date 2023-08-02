(function ($, app) {
    'use strict';
    $(document).ready(function () {
        $('select').select2();
        var $startDate = $("#startDate");
        app.datePickerWithNepali("form-advanceDate", "nepaliDate");
        $startDate.datepicker('setStartDate', new Date());
    });
})(window.jQuery, window.app);

angular.module("hris", ['ui.bootstrap'])
        .controller("advanceDetailController", function ($scope, $http, $uibModal) {
            var employeeId = angular.element(document.getElementById('form-employeeId')).val();
            var advanceId = angular.element(document.getElementById('form-advanceId')).val();
            var reqAmt = angular.element(document.getElementById("form-requestedAmount")).val();
            var terms = angular.element(document.getElementById("form-terms")).val();

            $scope.allowAmt = null;
            $scope.allowTerms = null;
            $scope.salaryDeduction = parseFloat(reqAmt) / parseFloat(terms);

            window.app.pullDataById(document.pullAdvanceDetailByEmpIdLink, {
                'employeeId': employeeId,
                'advanceId': advanceId
            }).then(function (success) {
                $scope.$apply(function () {
                    $scope.allowAmt = success.data.allowAmt;
                    $scope.allowTerms = success.data.allowTerms;
                });
            }, function (failure) {
                console.log("pullAdvanceDetailByEmpId", failure);
            });

            $scope.advanceRequestData = document.advanceRequestData;
            var generateVoucherFn = function () {
                App.blockUI({target: "#hris-page-content"});
                window.app.pullDataById(document.wsGenerateAdvanceVoucher, {
                    ADVANCE_REQUEST_ID: $scope.advanceRequestData.ADVANCE_REQUEST_ID
                }).then(function (response) {
                    App.unblockUI("#hris-page-content");
                    if (response.success) {
                        $scope.$apply(function () {
                            $scope.advanceRequestData.VOUCHER_NO = response.data.VOUCHER_NO;
                            $scope.voucherBtn = {
                                display: $scope.advanceRequestData.STATUS === 'AP',
                                text: ($scope.advanceRequestData.VOUCHER_NO == null) ? "Generate Voucher" : "Print Voucher",
                                fn: ($scope.advanceRequestData.VOUCHER_NO == null) ? generateVoucherFn : printVoucherFn
                            };
                            window.app.showMessage("Your Voucher has been generated.");
                        });
                    } else {
                        window.app.showMessage(response.error, 'error');
                        console.log('generateVoucherFn=>', response.error);
                    }
                }, function (error) {
                    App.unblockUI("#hris-page-content");
                    console.log('generateVoucherFn=>', error);
                });
            };
            var printVoucherFn = function () {

            };

            var showNGModal = function () {
                var modalInstance = $uibModal.open({
                    ariaLabelledBy: 'modal-title',
                    ariaDescribedBy: 'modal-body',
                    templateUrl: 'myModalContent.html',
                    resolve: {
                        fileTypes: function () {
                            return $scope.fileTypes;
                        }
                    },
                    controller: function ($scope, $uibModalInstance, fileTypes) {
                        $scope.formCode = null;
                        $scope.accCode = null;

                        $scope.ok = function () {
                            $uibModalInstance.close({formCode: $scope.formCode, accCode: $scope.accCode});
                        };
                        $scope.cancel = function () {
                            $uibModalInstance.dismiss('cancel');
                        };
                    }
                });
                modalInstance.rendered.then(function () {

                });
                modalInstance.result.then(function (selectedItem) {
                    generateVoucherFn();
                }, function () {
                    console.log("Modal Action Cancelled");
                });
            };
            $scope.voucherBtn = {
                display: $scope.advanceRequestData.STATUS === 'AP',
                text: ($scope.advanceRequestData.VOUCHER_NO == null) ? "Generate Voucher" : "Print Voucher",
                fn: ($scope.advanceRequestData.VOUCHER_NO == null) ? showNGModal : printVoucherFn
            };




        });
