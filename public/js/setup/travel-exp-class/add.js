(function ($, app) {
    'use strict';
    $(document).ready(function () {
        $('select').select2();
        // var $id=$('#id');
        var $categoryName=$('#categoryName');
        var $allowancePercentage=$('#allowancePercentage');
        var $submit=$('#submit');
        var $travelClassForm=$('#travelClassForm');

      
        $submit.on('click',function(){
            if($("#travelClassForm").valid()){
                travelExpenseClass(this);
            }
        })
        var travelExpenseClass=function(obj){
            var $this=$(obj);
            app.pullDataById(document.addTravelClassLink,{
                //'id':$id.val(),
                'categoryName':$categoryName.val(),
                'allowancePercentage':$allowancePercentage.val(),}).then(function(response){
                app.showMessage("Travel Class Added Successfully.");
                window.location.href = '../travelExpenseClass';
            },function(error){
    
            });
        }
        var validate =  $travelClassForm.validate({
            rules: {
                id: {
                    required: true
                },
                categoryName:{
                    required: true
                },
                allowancePercentage:{
                    required:true
                }
            },
            messages: {
              
            }
        });
    });
})(window.jQuery, window.app);

