(function ($, app) {
    'use strict';
    $(document).ready(function () {
        var pathName = window.location.pathname;
        var indexOfSlash = pathName.lastIndexOf("/");
        var newPathName = pathName.substring(0, indexOfSlash);
        $('#rootwizard').bootstrapWizard({
            onTabShow: function (tab, navigation, index) {
                $('#tab' + (index + 1) + " select").select2();
                switch (index + 1) {
                    case 1:
                        app.datePickerWithNepali('birthdate', 'nepaliBirthDate');
                        break;
                    case 2:
                        app.datePickerWithNepali('famSpouseBirthDate', 'famSpouseBirthDateNepali');
                        app.datePickerWithNepali('famSpouseWeddingAnniversary', 'weddingAnniversaryDateNepali');
                        break;
                    case 3:
                        app.datePickerWithNepali('idDrivingLicenseExpiry', 'drivingLicenseExpiaryNepali');
                        app.datePickerWithNepali('idCitizenshipIssueDate', 'citizenshipIssueDateNepali');
                        app.datePickerWithNepali('idPassportExpiry', 'passportExpiryNepali');
                        break;
                    case 4:
                        app.datePickerWithNepali('joinDate', 'nepalijoinDate');
                        app.datePickerWithNepali('toDate', 'nepaliToDate');
                        app.datePickerWithNepali('eventDate', 'nepalieventDate');
                        app.datePickerWithNepali('startDate', 'nepalistartDate');
                        app.datePickerWithNepali('endDate', 'nepaliendDate');
                        app.datePickerWithNepali('permanentDate', 'permanentDateNepali');
                        app.datePickerWithNepali('gratuityDate', 'nepaligratuityDate');
                        break;
                    case 7:
                        break;
                }

                var $total = navigation.find('li').length;
                var $current = index + 1;
                if ($current >= $total) {
                    $('#rootwizard').find('.pager .next').hide();
                    $('#rootwizard').find('.pager .finish').show().removeClass('disabled hidden');
                } else {
                    $('#rootwizard').find('.pager .next').show();
                    $('#rootwizard').find('.pager .finish').hide();
                }
                var li = navigation.find('li.active');
                var btnNext = $('#rootwizard').find('.pager .next').find('button');
                var btnPrev = $('#rootwizard').find('.pager .previous').find('button');

                function removeIcons(btn) {
                    btn.removeClass(function (index, css) {
                        return (css.match(/(^|\s)fa-\S+/g) || []).join(' ');
                    });
                }

            }, onNext: function (tab, navigation, index) {
                if (typeof document.currentTab !== 'undefined') {
                    if (index <= 4 || index == 7 || index == 8|| index == 9) {
                        $('#btnform' + index).click();
                    } else if (index == 5) {
                        angular.element('#quaConId').scope().addQualification();
                        return true;
                    } else {
                        return true;
                    }
                } else {
                    if (index == 1) {
                        $('#btnform' + index).click();
                    } else {
                        return true;
                    }
                }

                return false;

            }, onPrevious: function (tab, navigation, index) {
            }, onInit: function () {
                $('#rootwizard ul').removeClass('nav-pills');

            }
        });
        $('.remove-item').click(function () {
            $(this).parents('tr').fadeOut(function () {
                $(this).remove();
            });
        });
        if (document.setAppointmentFlag) {
            $('#toDate').prop('disabled', true);
            $('#nepaliToDate').prop('disabled', true);
        }

    });
})(window.jQuery, window.app);