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
            {field:"ID", title:"ID"},
            {field:"CATEGORY_NAME", title:"Category Name"},
            {field:"ALLOWANCE_PERCENTAGE", title:"Allowance Percentage"},
            // {field:"STATUS", title:"Status"},
            {field:"ID",title:"Action",width:120,template:action}
        ],null,null,null,'travelClassList');

        app.searchTable('travelCategoryTable',['ID','CATEGORY_NAME','ALLOWANCE_PERCENTAGE']);

        $('#excelExport').on('click', function () {
            app.excelExport($table, {
               'ID':'ID',
                'CATEGORY_NAME':'Category Name',
                'ALLOWANCE_PERCENTAGE':'Percentage'
            },'travelClassList');
        });
        $('#pdfExport').on('click',function(){
            app.exportToPDF($table,{
                'ID':'ID',
                'CATEGORY_NAME':'Category Name',
                'ALLOWANCE_PERCENTAGE':'Percentage'
            },'travelClassList');
        });

        app.pullDataById("",{}).then(function(response){
            app.renderKendoGrid($table,response.data);
        },function(error){

        });
    });

})(window.jQuery);
