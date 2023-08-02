(function ($) {
    'use strict';
    $(document).ready(function () {
        console.log(document.news);
        $("#newsTable").kendoGrid({
            excel: {
                fileName: "NewsList.xlsx",
                filterable: true,
                allPages: true
            },
            dataSource: {
                data: document.news,
                page: 1,
            },
            height: 450,
            scrollable: true,
            sortable: true,
            filterable: true,
            pageable: true,
            rowTemplate: kendo.template($("#rowTemplate").html()),
            columns: [
                {field: "NEWS_DATE", title: "News Date",width:80},
                {field: "NEWS_TYPE_DESC", title: "News Type",width:120},
                {field: "NEWS_TITLE", title: "News Title",width:120},
                {field: "NEWS_EDESC", title: "News",width:120},
                {title: "Action",width:100}
            ],
        });
        
        app.searchTable('newsTable',['NEWS_DATE','NEWS_TYPE_DESC','NEWS_TITLE','NEWS_EDESC']);
        
        app.pdfExport(
                'newsTable',
                {
                    'NEWS_DATE': 'NewsDate',
                    'NEWS_TYPE_DESC': 'Type',
                    'NEWS_TITLE': 'Title',
                    'NEWS_EDESC': 'Desc',
                });
        
        $("#export").click(function (e) {
            var grid = $("#newsTable").data("kendoGrid");
            grid.saveAsExcel();
        });
    });
})(window.jQuery);