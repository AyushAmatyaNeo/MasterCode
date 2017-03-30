(function ($, app) {
    'use strict';
    $(document).ready(function () {
        var $tableContainer = $("#reportTable");
        var extractDetailData = function (rawData, employeeId) {
            var data = {};
            var column = {};

            for (var i in rawData) {
                console.log('data', rawData[i]);
                if (typeof data[rawData[i].MONTH_ID] !== 'undefined') {
                    data[rawData[i].MONTH_ID].MONTHS[rawData[i].FORMATTED_ATTENDANCE_DT] =
                            JSON.stringify({
                                IS_ABSENT: rawData[i].IS_ABSENT,
                                IS_PRESENT: rawData[i].IS_PRESENT,
                                ON_LEAVE: rawData[i].ON_LEAVE
                            });
                } else {
                    data[rawData[i].MONTH_ID] = {
                        MONTH_ID: rawData[i].MONTH_ID,
                        MONTH_EDESC: rawData[i].MONTH_EDESC,
                        MONTHS: {}
                    };
                    data[rawData[i].MONTH_ID].MONTHS[rawData[i].FORMATTED_ATTENDANCE_DT] =
                            JSON.stringify({
                                IS_ABSENT: rawData[i].IS_ABSENT,
                                IS_PRESENT: rawData[i].IS_PRESENT,
                                ON_LEAVE: rawData[i].ON_LEAVE
                            });

                }
                if (typeof column[rawData[i].MONTH_ID] === 'undefined') {
                    var temp = rawData[i].FORMATTED_ATTENDANCE_DT;
                    column[rawData[i].MONTH_ID] = {
                        field: temp,
                        title: rawData[i].ATTENDANCE_DT,
                        template: '<a data="#: ' + temp + ' #" class="btn widget-btn custom-btn-group"></a>'
                    }

                }
            }
            var returnData = {rows: [], cols: []};

            returnData.cols.push({field: 'employee', title: 'employees'});
            for (var k in column) {
                returnData.cols.push(column[k]);
            }

            for (var k in data) {
                var row = data[k].MONTHS;
                row['employee'] = data[k].MONTH_EDESC;
                returnData.rows.push(row);
            }
            return returnData;
        };
        var displayDataInBtnGroup = function (selector) {
            $(selector).each(function (k, group) {
                var $group = $(group);
                var data = JSON.parse($group.attr('data'));
                $group.html((data.IS_PRESENT == 1) ? 'P' : ((data.IS_ABSENT == 1) ? 'A' : (data.ON_LEAVE == 1) ? 'L' : 'H'));
            });

        };

        var initializeReport = function (employeeId) {
            $tableContainer.block();
            app.pullDataById(document.wsEmployeeWiseDailyReport, {employeeId: employeeId}).then(function (response) {
                $tableContainer.unblock();
                console.log('departmentWiseEmployeeMonthlyR', response);
                var extractedDetailData = extractDetailData(response.data, employeeId);
                console.log('extractedDetailData', extractedDetailData);
                $tableContainer.kendoGrid({
                    dataSource: {
                        data: extractedDetailData.rows,
                        pageSize: 20
                    },
                    scrollable: false,
                    sortable: true,
                    pageable: true,
                    columns: extractedDetailData.cols
                });
                displayDataInBtnGroup('.custom-btn-group');


            }, function (error) {
                $tableContainer.unblock();
                console.log('departmentWiseEmployeeMonthlyE', error);
            });
        };
        initializeReport(21, 1);


    });
})(window.jQuery, window.app);