(function ($, app) {
    'use strict';
    $(document).ready(function () {
        var address = document.address || {};
        var addrPermZoneId = $('#addrPermZoneId');
        var addrPermDistrictId = $('#addrPermDistrictId');
        var addrPermVdcMunicipalityId = $('#addrPermVdcMunicipalityId');
        var addrTempZoneId = $('#addrTempZoneId');
        var addrTempDistrictId = $('#addrTempDistrictId');
        var addrTempVdcMunicipality = $('#addrTempVdcMunicipality');
        var empCode = document.employeeCode;
        var previousSalary = document.previousSalary ? document.previousSalary : 0;
        $('#employeeCode').val(empCode)
        $('#employeeCode').prop('disabled', true);
        $('#contractExpiryDt').prop('disabled', true);
        $('#previousSalary').prop('disabled', true);
        $('#contractExpiryDtNepali').prop('disabled', true);
        $('#previousSalary').val(previousSalary);
        var $serviceEventId = $('#serviceEventId');
        /*
         * 
         */

        app.populateSelectElement($('#idCitizenshipIssuePlace'), document.allDistrict, address['citizenshipIssuePlace']);

        var onChangePermZone = function (zoneId) {
            if (zoneId == null) {
                app.populateSelectElement(addrPermDistrictId, []);
                onChangePermDistrict(null);
                return;
            }
            app.pullDataById(document.urlDistrict, { id: zoneId }).then(function (data) {
                app.populateSelectElement(addrPermDistrictId, data, address['addrPermDistrictId']);
                onChangePermDistrict(addrPermDistrictId.val());
            }, function (error) {
                console.log("url=>" + document.urlDistrict, error);
            });
        };

        var onChangePermDistrict = function (districtId) {
            if (districtId == null) {
                addrPermVdcMunicipalityId.val('');
                addrPermVdcMunicipalityId.autocomplete({
                    source: []
                });
                return;
            }

            app.pullDataById(document.urlMunicipality, { id: districtId }).then(function (data) {
                var nameList = [];
                var value = "";
                $.each(data, function (key, item) {
                    nameList.push(item);
                    if (address['addrPermVdcMunicipalityId'] == key) {
                        value = item;
                    }
                });
                addrPermVdcMunicipalityId.val(value);
                addrPermVdcMunicipalityId.autocomplete({
                    source: nameList
                });
            }, function (error) {
                console.log("url=>" + document.urlMunicipality, error);
            });
        };

        var onChangeTempZone = function (zoneId) {
            if (zoneId == null) {
                app.populateSelectElement(addrTempDistrictId, []);
                onChangeTempDistrict(null);
                return;
            }
            app.pullDataById(document.urlDistrict, { id: zoneId }).then(function (data) {
                app.populateSelectElement(addrTempDistrictId, data, address['addrTempDistrictId']);
                onChangeTempDistrict(addrTempDistrictId.val());
            }, function (error) {
                console.log("url=>" + document.urlDistrict, error);
            });
        };

        var onChangeTempDistrict = function (districtId) {
            if (districtId == null) {
                addrTempVdcMunicipality.val('');
                addrTempVdcMunicipality.autocomplete({
                    source: []
                });
                return;
            }

            app.pullDataById(document.urlMunicipality, { id: districtId }).then(function (data) {
                var nameList = [];
                var value = "";
                $.each(data, function (key, item) {
                    nameList.push(item);
                    if (address['addrTempVdcMunicipalityId'] == key) {
                        value = item;
                    }
                });
                addrTempVdcMunicipality.val(value);
                addrTempVdcMunicipality.autocomplete({
                    source: nameList
                });
            }, function (error) {
                console.log("url=>" + document.urlMunicipality, error);
            });
        };


        /*
         * 
         */
        addrPermZoneId.on('change', function () {
            var $this = $(this);
            onChangePermZone($this.val());
        });

        addrPermDistrictId.on('change', function () {
            var $this = $(this);
            onChangePermDistrict($this.val());
        });

        addrTempZoneId.on('change', function () {
            var $this = $(this);
            onChangeTempZone($this.val());
        });

        addrTempDistrictId.on('change', function () {
            var $this = $(this);
            onChangeTempDistrict($this.val());
        });

        onChangePermZone(addrPermZoneId.val());
        onChangeTempZone(addrTempZoneId.val())



        $('#finishBtn').on('click', function () {
            if (typeof document.urlEmployeeList !== 'undefined') {
                location.href = document.urlSetupComplete;
            }
        });
        if (typeof document.currentTab !== "undefined") {
            $('#rootwizard').bootstrapWizard('show', parseInt(document.currentTab) - 1);
        }


        $('#filePath').on('change', function () {
            if (this.files && this.files[0]) {
                var reader = new FileReader();

                reader.onload = function (e) {
                    var previewUpload = $('#previewUpload');
                    previewUpload.attr('src', e.target.result);
                    if (previewUpload.hasClass('hidden')) {
                        previewUpload.removeClass('hidden');
                    }

                };
                reader.readAsDataURL(this.files[0]);
            }
        });
        $('form').bind('submit', function () {
            $(this).find(':disabled').removeAttr('disabled');
        });

        var $addDegree = $('#add-degree');
        var $addUniversity = $('#add-university');
        var $addProgram = $('#add-program');
        var $addCourse = $('#add-course');

        var $modalDegree = $('#modal-degree');
        var $modalUniversity = $('#modal-university');
        var $modalProgram = $('#modal-program');
        var $modalCourse = $('#modal-course');

        $addDegree.on('click', function () {
            $modalDegree.modal('show');
        });
        $addUniversity.on('click', function () {
            $modalUniversity.modal('show');
        });
        $addProgram.on('click', function () {
            $modalProgram.modal('show');
        });
        $addCourse.on('click', function () {
            $modalCourse.modal('show');
        });


        (function (formList) {
            $.each(formList, function (key, form) {
                var $saveBtn = $(form.form.find('.model-save-btn'));
                var $form = $(form.form.find('form'));
                $form.on('submit', function () {
                    var isValid = true;
                    var requiredInputList = $(this).find('[required]');
                    $.each(requiredInputList, function (key, value) {
                        var $value = $(value);
                        if ($value.val() == "") {
                            app.showMessage($value.attr('name') + ' is required.', 'error');
                            $value.focus();
                            return false;
                        }
                    });
                    if (isValid) {
                        $modalDegree.modal('hide');
                        var data = {};
                        var formDataList = $form.serializeArray();
                        $.each(formDataList, function (key, formData) {
                            data[formData['name']] = formData['value'];
                        });
                        app.serverRequest(form.url, data).then(function (response) {
                            if (response.success) {
                                app.showMessage(response.message);
                                window.location.reload();
                            }
                        });
                    }

                    return false;
                })
                $saveBtn.on('click', function () {
                    $form.trigger('submit');
                });
            });
        })([
            { form: $modalDegree, url: document.addDegreeLink },
            { form: $modalUniversity, url: document.addUniversityLink },
            { form: $modalProgram, url: document.addProgramLink },
            { form: $modalCourse, url: document.addCourseLink }
        ]);



        $('#distributionBtn').confirmation({
            placement: 'right',
            onConfirm: function () {
                app.serverRequest(document.addDistributionEmp, { id: document.employeeId }).then(
                    function (success) {
                        app.showMessage('Sucessfully Created Employee for Distribution', 'success', 'succcess')
                    });
            },
            onCancel: function () {
                console.log('cancel');
            },

        });

        function enableAbroadAddress(countryId) {
            if (countryId != 168) {
                $('#permanentAddressDiv :input').attr("disabled", true);
                $('#permanentAddressDiv').hide();
                $('#abroadAddressDiv').show();
            } else {
                $('#permanentAddressDiv :input').attr("disabled", false);
                $('#permanentAddressDiv').show();
                $('#abroadAddressDiv').hide();
            }
        }

        $('#countryId').on('change', function () {
            enableAbroadAddress($(this).val());
        });

        enableAbroadAddress($('#countryId').val());

        // hide and unhide update service status div
        $serviceEventId.hide();
        $('input[name=update]').change(function () {
            if ($(this).is(':checked')) {
                $serviceEventId.show();
                $('#serviceEventTypeId').prop('required', 'required');
                $('#eventDate').prop('required', 'required');
                $('#startDate').prop('required', 'required');
            } else {
                $serviceEventId.hide();
                $('#serviceEventTypeId').prop('required', false);
                $('#eventDate').prop('required', false);
                $('#startDate').prop('required', false);
            }
        });

        $('#joinDate').on('change', function () {
            var joinDateStr = $(this).val(); // get the value of the input field
            var joinDate = new Date(joinDateStr); // create a new Date object from the string
            joinDate.setMonth(joinDate.getMonth() + 6); // add 6 months to the date
            var options = { day: '2-digit', month: 'short', year: 'numeric' }; // specify the desired format
            var newJoinDateStr = joinDate.toLocaleDateString('en-GB', options).replace(',', '').replace(/ /g, '-'); // format the date as "dd-Mon-yyyy"
            $('#contractExpiryDt').val(newJoinDateStr); // set the value of the input field to the new date string
            var nepaliJoinDt = $('#nepalijoinDate').val();
            var date = new Date(nepaliJoinDt);
            date.setMonth(date.getMonth() + 6);
            var year = date.getFullYear();
            var month = String(date.getMonth() + 1).padStart(2, "0");
            var day = String(date.getDate()).padStart(2, "0");
            var newDateString = `${year}-${month}-${day}`;
            $('#contractExpiryDtNepali').val(newDateString); // set the value of the input field to the new date string

            alert('Your Contract Expiry Date is ' + newJoinDateStr + '.');
        });

    });

})(window.jQuery, window.app);


