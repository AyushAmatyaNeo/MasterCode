(function ($, app) {
    'use strict';
    $(document).ready(function () {
        $('select').select2();
        var $categoryName=$('#categoryName');
        var $requestedType=$('#requestedType');
        var $rate=$('#rate');
        var $submit=$('#submit');

      
        $submit.on('click',function(){
            travelCategory(this);
        })
        var travelCategory=function(obj){
            var $this=$(obj);
            // app.pullDataById(document.addTravelCategoryLink,{
            //     'categoryName':$categoryName.val(),
            //     'requestedType':$requestedType.val(),
            //     'rate':$rate.val(),
            // },function(failure){
            //     app.showMessage("Training Assigned Successfully.");
            // });

            app.pullDataById(document.viewTravelCategoryLink,{
                }).then(function(response){
                window.location.href = '../../travelCategory';
            },function(error){
    
            });
        }
    });
})(window.jQuery, window.app);

