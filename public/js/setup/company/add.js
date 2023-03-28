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

        /**
         * Function to change state of uploaded file variable
         * If file is not uploaded disable submit button
         */
        var uploadedFile = 0;

        function setUploadedVal(value){
            uploadedFile = value;

            if(value){
                $("#submit").attr('disabled', false);
                $("#imgError").hide();
            }else{
                $("#submit").attr('disabled', true);
                $("#imgError").show();
            }
        }

        if(document.imageData){
            setUploadedVal(1);
        }else{
            setUploadedVal(0);
        }

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
                    setUploadedVal(1);
                    toggle();
                });
            }
        };
        $('#uploadFile').on('click', function () {
            if ($(this).text() == "Edit") {
                imageData.fileName = null;
                setUploadedVal(0);
                toggle();
            } else {
                dropZone.processQueue();
            }
        });

        // Validate if company code exists
        $("#companyCode").on('change', function(e){

            document.body.style.cursor='wait';

            app.pullDataById(document.validateCompanyCode, {
                'companyCode': $('#companyCode').val(),
                'companyId': document.companyId
            }).then(function (response) {
                document.body.style.cursor='default';
                if(response.validated){
                    if(uploadedFile == 1){
                        $("#submit").attr('disabled', false);
                    }
                    $("#codeError").hide();
                }else{
                    $("#submit").attr('disabled', true);
                    $("#codeError").show();
                }
            }, function (error) {
                app.showMessage(error, 'error');
            });

        });

    });
})(window.jQuery, window.app);
