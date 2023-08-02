(function ($, app) {
    'use strict';
    
    $("#rePassword").on("keyup blur", function () {
        var rePassword = $(this).val();
        var password = $("#password").val();
        checkPwd(password,rePassword);
    });
    
    $("#password").on("keyup blur", function () {
        var password = $(this).val();
        var rePassword = $("#rePassword").val();
        checkPwd(password,rePassword);
    });
    
    var checkPwd=function(password,rePassword){
        if(rePassword!==password){
            $(".errorMsg").html("* Password doesn't match!!!.");
        }else{
            $(".errorMsg").html("");
        }
    }
    
    
    
    $('#updatePwdForm').submit(function (e) {
        var err = [];
        $(".errorMsg").each(function () {
            var erroMsg = $.trim($(this).html());
            if (erroMsg !== "") {
                err.push("error");
            }
        });
        if (err.length > 0)
        {
            return false;
        }
    });

})(window.jQuery, window.app);

