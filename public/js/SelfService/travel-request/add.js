(function ($, app) {
    'use strict';
    $(document).ready(function () {
        var $form = $('#travelRequest-form');
        // app.startEndDatePickerWithNepali('nepaliStartDate1', 'form-fromDate', 'nepaliEndDate1', 'form-toDate')
        var $request = $("#request");

        app.setLoadingOnSubmit("travelRequest-form");
        $('select#form-transportType').select2();
        $('select#travelSubstitute').select2();
        $('select#form-employeeId').select2();
        $('select#travelEmpSub').select2();
        var employeeId = $('#employeeId').val();
        app.floatingProfile.setDataFromRemote(employeeId);

        var $print = $('#print');
        $print.on('click', function () {
            app.exportDomToPdf('printableArea', document.urlCss);
        });

        var $noOfDays = $('#noOfDays');
        var $fromDate = $('#form-fromDate');
        var $toDate = $('#form-toDate');
        var $nepaliFromDate = $('#nepaliStartDate1');
        var $nepaliToDate = $('#nepaliEndDate1');

        $fromDate.on('change', function () {
            var diff = Math.floor((Date.parse($toDate.val()) - Date.parse($fromDate.val())) / 86400000);
            $noOfDays.val(diff + 1);
        });

        $toDate.on('change', function () {
            var diff = Math.floor((Date.parse($toDate.val()) - Date.parse($fromDate.val())) / 86400000);
            $noOfDays.val(diff + 1);
        });

        $nepaliFromDate.on('change', function () {
            var diff = Math.floor((Date.parse($toDate.val()) - Date.parse($fromDate.val())) / 86400000);
            $noOfDays.val(diff + 1);
        });

        $nepaliToDate.on('change', function () {
            var diff = Math.floor((Date.parse($toDate.val()) - Date.parse($fromDate.val())) / 86400000);
            $noOfDays.val(diff + 1);
        });

        app.startEndDatePickerWithNepali('nepaliStartDate1', 'form-fromDate', 'nepaliEndDate1', 'form-toDate', function (startDate, endDate, startDateStr, endDateStr) {
            var employeeId = $('#employeeId').val();
            var employeeId = $('#employeeId').val();
            if (typeof employeeId === 'undefined' || employeeId === null || employeeId === '' || employeeId === -1) {
                var employeeId = $('#form-employeeId').val();
                if (typeof employeeId === 'undefined' || employeeId === null || employeeId === '' || employeeId === -1) {
                    return;
                }
            }
            checkForErrors(startDateStr, endDateStr, employeeId);
        });

        var checkForErrors = function (startDateStr, endDateStr, employeeId) {
            app.pullDataById(document.wsValidateTravelRequest, { startDate: startDateStr, endDate: endDateStr, employeeId: employeeId }).then(function (response) {
                if (response.data['ERROR'] === null && response.travelError['ERROR'] === null && response.WODError['ERROR'] === null && response.WOHError['ERROR'] === null) {
                    $form.prop('valid', 'true');
                    $form.prop('error-message', '');
                    $('#request').attr("disabled", false);
                } else if (response.data['ERROR'] != null) {
                    $form.prop('valid', 'false');
                    $form.prop('error-message', response.data['ERROR']);
                    app.showMessage(response.data['ERROR'], 'error');
                    $($request).attr('disabled', 'disabled');

                } else if (response.travelError['ERROR'] != null) {
                    $form.prop('valid', 'false');
                    $form.prop('error-message', response.travelError['ERROR']);
                    app.showMessage(response.travelError['ERROR'], 'error');
                    $($request).attr('disabled', 'disabled');

                } else if (response.WODError['ERROR'] != null) {
                    $form.prop('valid', 'false');
                    $form.prop('error-message', response.WODError['ERROR']);
                    app.showMessage(response.WODError['ERROR'], 'error');
                    $($request).attr('disabled', 'disabled');
                }
                else {
                    $form.prop('valid', 'false');
                    $form.prop('error-message', response.WOHError['ERROR']);
                    app.showMessage(response.WOHError['ERROR'], 'error');
                    $('#request').attr('disabled', 'disabled');
                }
            }, function (error) {
                app.showMessage(error, 'error');
            });
        }
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
            window.app.pullDataById(document.pushTravelFileLink, {
                'filePath': data.fileName,
                'fileName': data.oldFileName
            }).then(function (success) {
                if (success.success) {
                    $('#fileDetailsTbl').append('<tr>'
                        + '<input type="hidden" name="fileUploadList[]" value="' + success.data.FILE_ID + '"><td>' + success.data.FILE_NAME + '</td>'
                        + '<td><a target="blank" href="' + document.basePath + '/uploads/travel_documents/' + success.data.FILE_IN_DIR_NAME + '"><i class="fa fa-download"></i></a></td>'
                        + '<td><button type="button" class="btn btn-danger deleteFile">DELETE</button></td></tr>');
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
        });




    });
})(window.jQuery, window.app);
