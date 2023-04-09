
(function ($, app) {
//    'use strict';
    $(document).ready(function () {
        $('select').select2();

        var subLeaveReference = document.subLeaveReference;
        var subLeaveMaxDays = document.subLeaveMaxDays;
//        console.log(subLeaveMaxDays);

        var $employee = $('#employeeId');
        var $leave = $('#leaveId');
        var $halfDay = $("#halfDay");
        var $availableDays = $('#availableDays');
        var $noOfDays = $('#noOfDays');
        var $request = $("#request");
        var $errorMsg = $("#errorMsg");
        var $startDate = $('#startDate'), $endDate = $('#endDate');
        var $leaveSubstitute = $('#leaveSubstitute');
        var $subRefId = $('#subRefId');
        var $halfDayMsg = $('#halfDayMsg');

        var substituteDetails = [];

        var dateDiff1 = "";

        var daysForDocs = "";
        var rowCount = "";
        var requireDocument = "";
        var leaveName = "";
        
        var applyLimit;
        var substituteEmp = {
            list: [],
            disable: function (employeeIds) {
                if (this.list.length > 0) {
                    $.each(this.list, function (key, value) {
                        $leaveSubstitute.find('option[value="' + value + '"]').prop('disabled', false);
                    });
                    this.list = [];
                }
                $.each(employeeIds, function (key, value) {
                    $leaveSubstitute.find('option[value="' + value + '"]').prop('disabled', true);
                });
                this.list = employeeIds;
            }};

        app.floatingProfile.registerListener(function (data) {
            substituteEmp.disable([data.employeeId, data.recommenderId, data.approverId]);
        });
        app.floatingProfile.setDataFromRemote($employee.val());
        var leaveList = [];
        var availableDays = null;


        var calculateAvailableDays = function (startDateStr, endDateStr, halfDay, employeeId, leaveId) {
            if (startDateStr === null || startDateStr == '' || endDateStr === null || endDateStr == '' || employeeId === null || employeeId == '' || leaveId === null || leaveId == '') {
                return;
            }
            app.serverRequest(document.wsFetchAvailableDays, {
                startDate: startDateStr,
                endDate: endDateStr,
                employeeId: employeeId,
                halfDay: halfDay,
                leaveId: leaveId
            }).then(function (response) {
                if (!response.success) {
                    app.showMessage(response.error, 'error');
                    return;
                }

                var dateDiff = parseFloat(response.data['AVAILABLE_DAYS']);
                var availableDays = parseFloat($availableDays.val());

                $noOfDays.val(dateDiff);
                var balanceDiff = dateDiff / (halfDay === 'N' ? 1 : 2);
                
                if (balanceDiff > availableDays) {
                    $errorMsg.html("* Applied days can't be more than available days.");
                    $request.prop("disabled", true);
                } else if (balanceDiff === 0) {
                    $errorMsg.html("* Applied days can't be 0 day.");
                    $request.prop("disabled", true);
                } else if (applyLimit!=null  && applyLimit < balanceDiff) {
                    $errorMsg.html("* Cant be Applied More than ".applyLimit);
                    $request.prop("disabled", true);
                    app.showMessage(" Cant be Applied More than "+applyLimit+" days" , "warning");
                } else {
                    $errorMsg.html("");
                    $request.prop("disabled", false);
                    // to check substitute leave
                    if (subLeaveReference == 'Y') {
                        var selectedSubRefId = $subRefId.val();
                        $.each(substituteDetails, function (index, value) {
                            if (selectedSubRefId == value.ID) {
                                validateSubstitueLeave(startDateStr, endDateStr, value);
                            }
                        });
                    }
                }
                rowCount = document.getElementById('fileDetailsTbl').rows.length;
                dateDiff1 = dateDiff;
                if (dateDiff1 != null) {
                    if (requireDocument == 'Y' && dateDiff1 > daysForDocs && rowCount <= 1) {
                        app.showMessage('Sick Leave for more than ' + daysForDocs + ' days so you need to submit documents', 'warning');
                        $($request).attr('disabled', 'disabled');
                    }
                }
                

            }, function (error) {
                app.showMessage(error, 'error');
            });
        };
        app.startEndDatePickerWithNepali('nepaliStartDate1', 'startDate', 'nepaliEndDate1', 'endDate', function (startDate, endDate, startDateStr, endDateStr) {
            var employeeId = $employee.val();
            var leaveId = $leave.val();
            if (typeof employeeId === 'undefined' || employeeId === null || employeeId === '' || employeeId === -1) {
                return;
            }
            leaveChange($leave[0]);
            var halfDayValue = $halfDay.is(':visible') ? $halfDay.val() : 'N';
            calculateAvailableDays(startDateStr, endDateStr, halfDayValue, employeeId, leaveId); 
            checkForErrors(startDateStr, endDateStr, employeeId);
        });

        var $form = $('#leaveApply');
        var checkForErrors = function (startDateStr, endDateStr, employeeId) {
            app.pullDataById(document.wsValidateLeaveRequest, {startDate: startDateStr, endDate: endDateStr, employeeId: employeeId}).then(function (response) {
                if (response.data['ERROR'] === null) {
                    $form.prop('valid', 'true');
                    $form.prop('error-message', '');
                } else {
                    $form.prop('valid', 'false');
                    $form.prop('error-message', response.data['ERROR']);
                    app.showMessage(response.data['ERROR'], 'error');
                }
            }, function (error) {
                app.showMessage(error, 'error');
            });
        }

        app.setLoadingOnSubmit("leaveApply", function ($form) {
            if ($form.prop('valid') === 'true') {
                return true;
            } else {
                app.showMessage($form.prop('error-message'), 'error');
                return false;
            }
        });

        var toggleSubstituteEmployeeReq = function ($flag) {
            if ($flag) {
                $('#substituteEmployeeCol').find('span[class="required"]').show();
                $('#leaveSubstitute').find('option[value=""]').prop('disabled', true);
                $('#leaveSubstitute').prop('required', true);
            } else {
                $('#substituteEmployeeCol').find('span[class="required"]').hide();
                $('#leaveSubstitute').find('option[value=""]').prop('disabled', false);
                $('#leaveSubstitute').prop('required', false);
            }
        };
        var toggleGracePeriod = function ($flag) {
            if ($flag) {
                $('#gracePeriodCol').show();
                $('#gracePeriod').prop('disabled', false);
            } else {
                $('#gracePeriodCol').hide();
                $('#gracePeriod').prop('disabled', true);
            }
        };
        var toggleHalfDay = function ($flag) {
            if ($flag) {
                $('#halfDayCol').show();
                $('#halfDay').prop('disabled', false);
            } else {
                $('#halfDayCol').hide();
                $('#halfDay').prop('disabled', true);
            }
        };
        var toggleSubstituteEmployee = function ($flag) {
            if ($flag) {
                $('#substituteEmployeeCol').show();
                $('#leaveSubstitute').prop('disabled', false);
            } else {
                $('#substituteEmployeeCol').hide();
                $('#leaveSubstitute').prop('disabled', true);
            }
        };

        toggleHalfDay(false);
        toggleGracePeriod(false);
        toggleSubstituteEmployee(false)
        toggleSubstituteEmployeeReq(false);



        var leaveChange = function (obj) {
            var $this = $(obj);
            if ($this.val() === null || $this.val() === '' || $this.val() === '-1') {
                return;
            }
            calculateAvailableDays($startDate.val(), $endDate.val(), $halfDay.val(), $employee.val(), $leave.val());
            App.blockUI({target: "#hris-page-content", message: "Calculating Leave Days"});
            var startDateValue = $startDate.val();
			var endDateValue = $endDate.val();
            app.pullDataById(document.wsPullLeaveDetail, {
                'leaveId': $this.val(),
                'employeeId': $employee.val(),
                'startDate': (startDateValue == '') ? null : startDateValue,
				// 'endDate': (endDateValue == '') ? null : endDateValue
            }).then(function (success) {
                App.unblockUI("#hris-page-content");
                var leaveDetail = success.data;
                applyLimit = leaveDetail.APPLY_LIMIT;
                substituteDetails = success.subtituteDetails;
//                app.populateSelect($leave, substituteDetails, 'id', 'name', 'Select a Leave', null, null, false);
                app.populateSelect($subRefId, substituteDetails, 'ID', 'SUB_NAME', 'Select Substitute Date ', ' ', $subRefId.val(), false);
                $subRefId.prop('required', true);
                if (success.data.IS_SUBSTITUTE == 'Y') {
                    $('#SubReferenceDiv').show();
                    (subLeaveReference == 'Y') ? $('#request').attr("disabled", true) : $('#request').attr("disabled", false);
                } else {
                    $('#request').attr("disabled", false);
                    $('#SubReferenceDiv').hide()
                }
                availableDays = (typeof leaveDetail.BALANCE == 'undefined') ? 0 : parseFloat(leaveDetail.BALANCE);
                if ($subRefId.val() == ' ' || subLeaveReference != 'Y') {
                    $availableDays.val(availableDays);
                }
                if ($subRefId.val() == ' ' && success.data.IS_SUBSTITUTE == 'Y' && subLeaveReference == 'Y') {
                    $availableDays.val(0);
                }

                var noOfDays = parseFloat($noOfDays.val());

                if (noOfDays > availableDays) {
                    $("#errorMsg").html("* Applied days can't be more than available days");
                    $("#request").attr("disabled", "disabled");
                } else if ((availableDays != "" && noOfDays != "") && (noOfDays <= availableDays)) {
                    $("#errorMsg").html("");
                    $("#request").removeAttr("disabled");
                }

                toggleGracePeriod(leaveDetail.ALLOW_GRACE_LEAVE === "Y");
                toggleHalfDay(leaveDetail.ALLOW_HALFDAY === "Y");
                toggleSubstituteEmployee(leaveDetail.ENABLE_SUBSTITUTE === "Y");
                toggleSubstituteEmployeeReq(leaveDetail.IS_SUBSTITUTE_MANDATORY === 'Y');
                
                requireDocument = leaveDetail.DOCUMENT_REQUIRED;
                daysForDocs = leaveDetail.DOCS_COMP_DAYS;
                leaveName = leaveDetail.LEAVE_ENAME;
                
                var rowCount1 = document.getElementById('fileDetailsTbl').rows.length;
                if(requireDocument == 'Y' && dateDiff1 > daysForDocs && rowCount1 <= 1 ){
                    $($request).attr('disabled', 'disabled');
                }
                checkHalfDay(this);
            }, function (failure) {
                App.unblockUI("#hris-page-content");
                console.log(failure);
            });
        };

        $leave.on('change', function () {
            leaveChange(this);
        });

        

        var employeeChange = function (obj) {
            checkHalfDay(this);
            
            var $this = $(obj);
            app.floatingProfile.setDataFromRemote($this.val());
            App.blockUI({target: "#hris-page-content", message: "Fetching Employee Leaves"});
            app.pullDataById(document.wsPullLeaveDetailWidEmployeeId, {
                'employeeId': $this.val()
            }).then(function (success) {
                App.unblockUI("#hris-page-content");
                leaveList = success.data;
                app.populateSelect($leave, leaveList, 'id', 'name', 'Select a Leave', null, null, false);

                if ($startDate.val() != '' && $endDate.val() != '') {
                    var halfDayValue = $halfDay.is(':visible') ? $halfDay.val() : 'N';
                    calculateAvailableDays($startDate.val(), $endDate.val(), halfDayValue, $this.val(), $leave.val());
                }

            }, function (failure) {
                App.unblockUI("#hris-page-content");
                console.log(failure);
            });
        };

        $employee.on('change', function () {
            employeeChange(this);
        });
        
        $halfDay.on('change', function () {
            checkHalfDay(this);
            if ($startDate.val() !== '' && $endDate.val() !== '') {
                var halfDayValue = $halfDay.is(':visible') ? $halfDay.val() : 'N';
                calculateAvailableDays($startDate.val(), $endDate.val(), halfDayValue, $employee.val(), $leave.val());
            }
        });

        var checkHalfDay = function(obj){
            $halfDayMsg.html("");
            if ($startDate.val() !== '' && $endDate.val() !== '') {
                var halfDayValue = $halfDay.is(':visible') ? $halfDay.val() : 'N';
                if(halfDayValue != 'N'){
                    $halfDayMsg.html("Actual Days: " + ($noOfDays.val())/2);
                }
        }};

        var myDropzone;
        Dropzone.autoDiscover = false;
        myDropzone = new Dropzone("div#dropZoneContainer", {
            url: document.uploadUrl,
            autoProcessQueue: false,
            maxFiles: 1,
            addRemoveLinks: true,
            init: function () {
                this.on("success", function (file, success) {
                    if (success.success) {
                        imageUpload(success.data);
                    }
                });
                this.on("complete", function (file) {
                    this.removeAllFiles(true);
                });
            }
        });

        $('#addDocument').on('click', function () {
            $('#documentUploadModel').modal('show');
        });

        $('#uploadSubmitBtn').on('click', function () {
            if (myDropzone.files.length == 0) {
                $('#uploadErr').show();
                return;
            } else {
                $('#uploadErr').hide();
            }
            $('#documentUploadModel').modal('hide');
            myDropzone.processQueue();
        });

        var imageUpload = function (data) {
            window.app.pullDataById(document.pushLeaveFileLink, {
                'filePath': data.fileName,
                'fileName': data.oldFileName
            }).then(function (success) {
                if (success.success) {
                    $('#fileDetailsTbl').append('<tr>'
                            + '<input type="hidden" name="fileUploadList[]" value="' + success.data.FILE_ID + '"><td>' + success.data.FILE_NAME + '</td>'
                            + '<td><a target="blank" href="' + document.basePath + '/uploads/leave_documents/' + success.data.FILE_IN_DIR_NAME + '"><i class="fa fa-download"></i></a></td>'
                            + '<td><button type="button" class="btn btn-danger deleteFile">DELETE</button></td></tr>');
                
                sickLeaveCheck();
                    
                }
            }, function (failure) {
            });
        }

        $('#uploadCancelBtn').on('click', function () {
            $('#documentUploadModel').modal('hide');
        });

        $('#fileDetailsTbl').on('click', '.deleteFile', function () {
            var selectedtr = $(this).parent().parent();
            selectedtr.remove();
            var rowCount1 = document.getElementById('fileDetailsTbl').rows.length;
        sickLeaveCheck();
        });
        
        
        var sickLeaveCheck=function(){
            let availableDays=$availableDays.val();
            var rowCount1 = document.getElementById('fileDetailsTbl').rows.length;
            console.log('availableDays',availableDays);
            console.log('dateDiff1',dateDiff1);
            if ((requireDocument == 'Y'  && rowCount1<=1 && dateDiff1 > daysForDocs) || (availableDays<dateDiff1)) {
                app.showMessage(leaveName + ' for more than ' + daysForDocs + ' days so you need to submit documents', 'warning');
                $($request).attr('disabled',true);
            }else{
                 $($request).attr("disabled", false);
            }
            
        }

        $subRefId.on('change', function () {
            if (subLeaveReference == 'Y') {
                
                calculateAvailableDays($startDate.val(), $endDate.val(), $halfDay.val(), $employee.val(), $leave.val());

                var selectedSubRefId = $(this).val();
                if (selectedSubRefId !== ' ') {
                    $('#request').attr("disabled", false);
                } else {
                    $('#request').attr("disabled", true);
                }

                $.each(substituteDetails, function (index, value) {
                    if (selectedSubRefId == value.ID) {
                        $availableDays.val(value.AVAILABLE_DAYS);
                    }
                });
            }
        });

        var validateSubstitueLeave = function (startDate, endDate, $subDetail) {
            let sD = new Date(startDate);
            let eD = new Date(endDate);
            let subEndD = new Date($subDetail['SUB_END_DATE']);
            let subValD = new Date($subDetail['SUB_VALIDATE_DAYS']);

            if (sD <= subEndD) {
                $('#errorMsgSubRef').html("* LeaveCant Be Taken Before Event");
                $request.prop("disabled", true);
            } else if (sD > subValD) {
                $('#errorMsgSubRef').html("* Leave Has been Expired");
                $request.prop("disabled", true);
            } else {
                $('#errorMsgSubRef').html("");
                $request.prop("disabled", false);
            }

        }

    });

})(window.jQuery, window.app);


