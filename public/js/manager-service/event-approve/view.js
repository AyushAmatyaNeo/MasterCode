(function ($, app) {
    'use strict';
    $(document).ready(function (e) {
        $("#approve").on("click", function () {
            try {
                var $recommendedRemarks = $("#recommendedRemarks");
                var $approvedRemarks = $("#form-approvedRemarks");

                if (typeof $recommendedRemarks !== "undefined") {
                    $recommendedRemarks.removeAttr("required");
                }
                if (typeof $approvedRemarks !== "undefined") {
                    $approvedRemarks.removeAttr("required");
                }
                app.setLoadingOnSubmit('TrainingRequest');
            } catch (e) {
                console.log("onApproveBtnClick", e.message);
            }
        });
    });
})(window.jQuery, window.app);
