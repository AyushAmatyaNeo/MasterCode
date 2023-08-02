(function($){
    'use strict';
    $(document).ready(function(){
        var $table=$('#travelCategoryTable');
        var editAction = document.acl.ALLOW_UPDATE == 'Y' ? '<a class="btn-edit" title="Edit" href="' + document.editLink + '/#:ID#" style="height:17px;"> <i class="fa fa-edit"></i></a>' : '';
        var deleteAction = document.acl.ALLOW_DELETE == 'Y' ? '<a class="confirmation btn-delete" title="Delete" href="' + document.deleteLink + '/#:ID#" style="height:17px;"><i class="fa fa-trash-o"></i></a>' : '';
        var action = editAction + deleteAction+ `
                <a class="btn btn-icon-only green" href="${document.viewLink}/#:ID#" style="height:10px;margin-top:-6px;width:27px;" title="View Detail">
                    <i class="fa fa-search" sty></i>
                </a>
                        `;
        app.initializeKendoGrid($table,[
            {field:"LEVEL_NO", title:"Level No"},
            {field:"ADVANCE_AMOUNT", title:"Advance Amount"},
            {field:"DAILY_ALLOWANCE_AMOUNT", title:"Daily Allowance"},
            {field:"ID",title:"Action",width:120,template:action}
        ],null,null,null,'TravelCategoryList');

        app.searchTable('travelCategoryTable',['LEVEL_NO','STATUS','ADVANCE_AMOUNT','DAILY_ALLOWANCE']);

        $('#excelExport').on('click', function () {
            app.excelExport($table, {
               'LEVEL_NO':'Level No',
                'ADVANCE_AMOUNT':'Advance Amount',
                'DAILY_ALLOWANCE_AMOUNT':'Daily Allowance'
            },'TravelCategoryList');
        });
        $('#pdfExport').on('click',function(){
            app.exportToPDF($table,{
                'LEVEL_NO':'Level No',
                'ADVANCE_AMOUNT':'Advance Amount',
                'DAILY_ALLOWANCE_AMOUNT':'Daily Allowance'
            },'TravelCategoryList');
        });

        app.pullDataById("",{}).then(function(response){
            app.renderKendoGrid($table,response.data);
        },function(error){

        });
    });

})(window.jQuery);
