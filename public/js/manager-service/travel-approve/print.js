(function ($, app) {
    'use strict';
    $(document).ready(function () {
        var $close = $("#close");
        var $pdfExport = $("#pdfExport");
        var $body = $("#body");

        var pdf = new jsPDF('p', 'mm','a4');
        var specialElementHandlers = {
            '#ignore': function (element, renderer) {
                return true;
            }
        };

        $close.on('click', function () {
            window.close();
        });

        $("#pdfExport").click(function (e) {
            var divToPrint = document.getElementById('body');
            var newWin = window.open('', 'Print-Window');
            newWin.document.open();
            newWin.document.write('<html>' +
                '<body onload="window.print()"> ' +
                '<div style="text-align: left;"></div>' + divToPrint.innerHTML + '' +
                '</body>' +
                '<style>' +
                    'table {border-collapse: collapse; text-align: left; width: 100%;}' +
                    'table, th, td {border: 1px solid black; text-align: left; padding: 10px;} ' +
                    'table td:nth-child(2){ text-align: left; }' +
                '</style>' +
                '</html>');
            newWin.document.close();

            branchName = 'ALL';
        });

    });

})(window.jQuery, window.app);