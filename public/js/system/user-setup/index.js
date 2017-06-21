(function ($) {
    'use strict';
    $(document).ready(function () {    
       
        $("#userTable").kendoGrid({
            excel: {
                fileName: "UserList.xlsx",
                filterable: true,
                allPages: true
            },
            dataSource: {
                data: document.users,
                pageSize: 20
            },
            height: 450,
            scrollable: true,
            sortable: true,
            filterable: true,
            pageable: {
                input: true,
                numeric: false
            },
            rowTemplate: kendo.template($("#rowTemplate").html()),
            columns: [
                {field: "FULL_NAME", title: "Employee Name",width:200},
                {field: "USER_NAME", title: "User Name",width:200},
                {field: "ROLE_NAME", title: "Role Name",width:200},
                    {title: "Action",width:100}
            ]
        }); 
        
        app.searchTable('userTable',['FULL_NAME','USER_NAME','ROLE_NAME']);
        
        app.pdfExport(
                'userTable',
                {
                    'FULL_NAME': 'Name',
                    'USER_NAME': 'UserName',
                    'ROLE_NAME': 'Role'
                }
        );
        
        $("#export").click(function (e) {
            var grid = $("#userTable").data("kendoGrid");
            grid.saveAsExcel();
        });
        window.app.UIConfirmations();
    });   
})(window.jQuery, window.app);
