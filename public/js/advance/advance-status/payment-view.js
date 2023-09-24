(function ($) {
    'use strict';
    $(document).ready(function () {
        var $table = $('#table');

        var action = `
        if (STATUS == 'PE' {
            <div class="clearfix">
                <a class="btn-edit red confirmation skipMonth" id="#:ID#" style="height:17px;" title="Skip">
                    <i class="fa fa-fast-forward"></i>
                </a>
            </div>
        }`
            ;

        var actiontemplateConfig = {
            update: {
                'ALLOW_UPDATE': document.acl.ALLOW_UPDATE,
                'params': ["ID"],
                'url': document.editLink
            },
            delete: {
                'ALLOW_DELETE': 'N',
                'params': ["ID"],
                'url': document.deleteLink
            }
        };

        var columns = [
            { field: "NEP_YEAR", title: "Year", width: 150 },
            { field: "MONTH_EDESC", title: "Month", width: 150 },
            { field: "AMOUNT", title: "Amount", width: 150 },
            { field: "STATUS_DESC", title: "Status", width: 150 },
            { field: ["ID"], title: "Action", width: 80, template: app.genKendoActionTemplate(actiontemplateConfig) },
            { field: "PAYAMENT_DATE", title: "Payment Date", width: 150 },
            { field: "PAYMENT_MODE_DESC", title: "Payment Mode", width: 150 }
            // { field: ["ID"], title: "Action", template: action }

        ];
        $(document).on('click', '.btn-edit', function () {
            var val = $(this).parent().siblings(":nth-of-type(7)").text();
            if (val == 0) {
                return confirm("Are you sure you want to revert the skip this month?") ? true : false;
            }
            else {
                return confirm("Are you sure to skip advance payment this month?") ? true : false;
            }
        });
        var map = {
            'NEP_YEAR': 'Year',
            'MONTH_EDESC': 'Month',
            'AMOUNT': 'Amount',
            'STATUS_DESC': 'Status',
            'PAYAMENT_DATE': 'Payment Date',
            'PAYMENT_MODE_DESC': 'Payment Mode'
        }
        app.initializeKendoGrid($table, columns, "Advance Paymnet List.xlsx");

        app.searchTable($table, ['NEP_YEAR', 'MONTH_EDESC', 'STATUS_EDESC', 'AMOUNT', 'PAYAMENT_DATE', 'PAYMENT_MODE_DESC']);

        $('#excelExport').on('click', function () {
            app.excelExport($table, map, 'Advance Paymnet List.xlsx');
        });

        $('#pdfExport').on('click', function () {
            app.exportToPDF($table, map, 'Advance Paymnet List.pdf');
        });

        app.pullDataById("", {}).then(function (response) {
            console.log(response.data);
            app.renderKendoGrid($table, response.data);
        }, function (error) {

        });


        $table.on('click', '.skipMonth', function () {
            var selectedYear = $(this).attr('year');
            var selectMonth = $(this).attr('month');
            app.pullDataById(document.skipAdvance, { year: selectedYear, month: selectMonth }).then(function (response) {
                console.log(response.data);
                App.unblockUI("#hris-page-content");
                window.location.reload(true);
            }, function (error) {
                App.unblockUI("#hris-page-content");
            });
        });



    });
})(window.jQuery);

