(function ($, app) {
    'use strict';
    $(document).ready(function () {
        $('select').select2();
        var $positionId=$('#positionId');
        var $dailyAllowance=$('#dailyAllowance');
        var $advanceAmount=$('#advanceAmount');
        var $submit=$('#submit');
        var $travelCategoryForm=$('#travelCategoryForm');

      
        $submit.on('click',function(){
            if($("#travelCategoryForm").valid()){
                travelCategory(this);
            }
        })
        var travelCategory=function(obj){
            var $this=$(obj);
            app.pullDataById(document.addTravelCategoryLink,{
                'positionId':$positionId.val(),
                'dailyAllowance':$dailyAllowance.val(),
                'advanceAmount':$advanceAmount.val(),}).then(function(response){
                app.showMessage("Travel Category Assigned Successfully.");
                window.location.href = '../travelCategory';
            },function(error){
    
            });
        }
        var validate =  $travelCategoryForm.validate({
            rules: {
                positionId: {
                    required: true
                },
                dailyAllowance:{
                    required: true
                },
                advanceAmount:{
                    required:false
                }
            },
            messages: {
              
            }
        });
    });
})(window.jQuery, window.app);

