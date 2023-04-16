(function ($, app) {
    'use strict';
    $(document).ready(function () {
        $('select').select2();

        var formId = "company-form";
        var inputFieldId = "form-companyName";
        var tableName = "HRIS_COMPANY";
        var columnName = "COMPANY_NAME";
        var checkColumnName = "COMPANY_ID";
        var $logo = $("#form-logo");
        var selfId = $("#companyId").val();
        if (typeof (selfId) == "undefined") {
            selfId = 0;
        }
        window.app.checkUniqueConstraints(inputFieldId, formId, tableName, columnName, checkColumnName, selfId, function () {
//            if ($logo.val() === "") {
//                app.errorMessage("No company logo is set.");
//                return false;
//            } else {
                App.blockUI({target: "#hris-page-content"});
                return true;
//            }
        });
        window.app.checkUniqueConstraints("form-companyCode", formId, tableName, "COMPANY_CODE", checkColumnName, selfId);

        var $myAwesomeDropzone = $('#my-awesome-dropzone');
        var $uploadedImage = $('#uploadedImage');

        var imageData = {
            fileCode: null,
            fileName: null,
            oldFileName: null
        };
        if (typeof document.imageData !== 'undefined' && document.imageData != null) {
            imageData = document.imageData
        }

        var toggle = function () {
            if (imageData.fileName == null) {
                $myAwesomeDropzone.show();
                $uploadedImage.hide();
                $('#uploadFile').text("Upload");
            } else {
                $($uploadedImage.children()[0]).attr('src', document.basePath + "/uploads/" + imageData.fileName);
                $logo.val(imageData.fileCode);
                $myAwesomeDropzone.hide();
                $uploadedImage.show();
                $('#uploadFile').text("Edit");
            }
        }
        toggle();

        var dropZone = null;
        Dropzone.options.myAwesomeDropzone = {
            maxFiles: 1,
            acceptedFiles: 'image/*',
            autoProcessQueue: false,
            addRemoveLinks: true,
            init: function () {
                dropZone = this;
                this.on('success', function (file, success) {
                    imageData = success.data;
                    $logo.val(imageData.fileCode);
                    toggle();
                });
            }
        };
        $('#uploadFile').on('click', function () {
            if ($(this).text() == "Edit") {
                imageData.fileName = null;
                toggle();
            } else {
                dropZone.processQueue();
            }
        });
    });
})(window.jQuery, window.app);
