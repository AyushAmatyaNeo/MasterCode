(function ($, app) {
    'use strict';
    $(document).ready(function () {
        $('select').select2();
        var unpaid = parseInt($("#unpaidTotal").val());
        
        $("#months").on('change paste input',function(){
            var months = parseInt($("#months").val());
            var installment = parseFloat(unpaid/months);
            $("#installment").val(installment);
        });

    });
})(window.jQuery, window.app);

