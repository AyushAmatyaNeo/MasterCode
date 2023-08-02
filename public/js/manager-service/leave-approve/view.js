(function ($) {
    'use strict';
    $(document).ready(function (e) {
      app.startEndDatePickerWithNepali('nepaliStartDate1', 'startDate', 'nepaliEndDate1', 'endDate');
        $("#approve").on("click", function () {
            try {
                var recommendRemarksId = $("#form-recommendedRemarks");
                var approveRemarksId = $("#form-approvedRemarks");

                if (typeof recommendRemarksId !== "undefined") {
                    recommendRemarksId.removeAttr("required");
                }
                if (typeof approveRemarksId !== "undefined") {
                    approveRemarksId.removeAttr("required");
                }
                var id = $(this);
                var parentId = id.parent(".form-group");
                var availableBalance = parseFloat($("#availableDays").val());
                var noOfDays = parseFloat($("#noOfDays").val());
                if (typeof document.isHalfDay !== 'undefined') {
                    noOfDays = noOfDays / (document.isHalfDay ? 2 : 1);
                }
                if (noOfDays > availableBalance && typeof document.leaveCancel=="undefined") {
                    var errorMsgSpan = $('<span />', {
                        "class": 'errorMsgNoLeft',
                        text: "There is not enough available days...!!!"
                    });
                    parentId.append(errorMsgSpan);
                    $("#availableDaysText").css("font-weight", "bold");
                    this.disabled = true;
                } else {
                    this.disabled = false;
                }
            } catch (e) {
                console.log("onApproveBtnClick", e.message);
            }
            App.blockUI({target: "#hris-page-content"});
        });
    });
})(window.jQuery, window.app);
