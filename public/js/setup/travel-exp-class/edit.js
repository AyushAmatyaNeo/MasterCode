(function ($, app) {
    'use strict';
    $(document).ready(function () {
        $('select').select2();
        var $categoryName=$('#categoryName');
        var $allowancePercentage=$('#allowancePercentage');
        var $submit=$('#submit');
        var $travelClassForm=$('#travelClassForm');

      
        $submit.on('click',function(){
            if($("#travelClassForm").valid()){
                travelCategory(this);
            }
        })
        var travelCategory=function(obj){
            var $this=$(obj);
            app.pullDataById(document.editTravelCategoryLink,{

                'categoryName':$categoryName.val(),
                'allowancePercentage':$allowancePercentage.val(),
                }).then(function(response){
                // app.showMessage("Travel Class Updated Successfully.");
                // window.location.href = '../travelExpenseClass';
            },function(error){
    
            });
        }
        var validate =  $travelClassForm.validate({
            rules: {
                categoryName: {
                    required: true
                },
                allowancePercentage:{
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

