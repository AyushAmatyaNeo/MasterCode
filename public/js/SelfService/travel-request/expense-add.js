(function ($, app) {
    $(document).ready(function () {


        $('form').bind('submit', function () {
            $(this).find(':disabled').removeAttr('disabled');
        });
    });
})(window.jQuery, window.app);

