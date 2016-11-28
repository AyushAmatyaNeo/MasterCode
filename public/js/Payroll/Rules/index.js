(function ($) {
    'use strict';
    $(document).ready(function () {    
       
        $("#ruleTable").kendoGrid({
            dataSource: {
                data: document.rules,
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
                {field: "PAY_CODE", title: "Pay Code"},
                {field: "PAY_EDESC", title: "EDesc"},
                {field: "PAY_TYPE_FLAG", title: "Type"},
                {title: "Action"}
            ]
        });    
    });   
})(window.jQuery, window.app);