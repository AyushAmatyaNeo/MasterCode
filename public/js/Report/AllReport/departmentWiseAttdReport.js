(function ($, app) {
    'use strict';
    $(document).ready(function () {
        app.datePickerWithNepali('fromDate', 'nepaliFromDate');
        var reportData;
        $("#generate").on("click", function () {
            var date1 = $("#fromDate").val();
            var company = $("#company").val();
            var date2 = date1;
            app.serverRequest('', {
                date1: date1,
                date2: date2,
                company: company
            }).then(function (response) {
                if (response.success) {
                    reportData = response.data;
                    reportData.push({
                        DEPARTMENT_NAME: 'Total',
                        AB: reportData.reduce((a, b) => +a + +b.AB, 0),
                        total: reportData.reduce((a, b) => +a + +b.total, 0),
                        PR: reportData.reduce((a, b) => +a + +b.PR, 0),
                        LV: reportData.reduce((a, b) => +a + +b.LV, 0),
                        WD: reportData.reduce((a, b) => +a + +b.WD, 0),
                        WH: reportData.reduce((a, b) => +a + +b.WH, 0),
                        HD: reportData.reduce((a, b) => +a + +b.HD, 0),
                        DO: reportData.reduce((a, b) => +a + +b.DO, 0)
                    });
                    console.log(reportData);
                    for (var i = 0; i < reportData.length; i++) {
                        reportData[i].total = (parseInt(reportData[i].PR) || 0) + (parseInt(reportData[i].WD) || 0) + (parseInt(reportData[i].HD) || 0) + (parseInt(reportData[i].LV) || 0) + (parseInt(reportData[i].WH) || 0) + (parseInt(reportData[i].DO) || 0) + (parseInt(reportData[i].AB) || 0);

                        reportData[i].absentRate = parseFloat(((parseInt(reportData[i].AB) || 0) / (parseInt(reportData[i].total) || 0)) * 100);
                    }
                    generateReport();

                } else {
                    app.showMessage(response.error, 'error');
                }
            }, function (error) {
                app.showMessage(error, 'error');
            });

        });

        function generateReport() {
            $("#grid").kendoGrid({
                toolbar: ["excel"],
                excel: {
                    fileName: "Department Wise Attendance Report.xlsx",
                    filterable: true
                },
                dataSource: {
                    data: reportData,
                    schema: {
                        model: {
                            fields: {
                                DEPARTMENT_NAME: {type: "string"},
                                AB: {type: "number"},
                                PR: {type: "number"},
                                DO: {type: "number"},
                                LV: {type: "number"},
                                HD: {type: "number"},
                                WH: {type: "number"},
                                WD: {type: "number"},
                                total: {type: "number"},
                                absentRate: {type: "number"}
                            }
                        }
                    },
                    pageSize: 20
                },
                height: 550,
                scrollable: true,
                sortable: true,
                groupable: true,
                filterable: true,
                pageable: {
                    input: true,
                    numeric: false
                },
                columns: [
                    {field: "DEPARTMENT_NAME", title: "Department", format: "{0:c}", width: "100px"},
                    {field: "total", title: "Total Employees", width: "50px"},
                    {field: "PR", title: "Present", width: "50px"},
                    {field: "AB", title: "Absent", width: "50px"},
                    {field: "LV", title: "Leave", width: "50px"},
                    {field: "DO", title: "Day Off", width: "50px"},
                    {field: "WD", title: "WOD", width: "50px"},
                    {field: "HD", title: "Holiday", width: "50px"},
                    {field: "WH", title: "WOH", width: "50px"},
                    {field: "absentRate", title: "% Absent", format: "{0:0.##}", width: "50px"}
                ]
            });
        } 
    });
})(window.jQuery, window.app);
