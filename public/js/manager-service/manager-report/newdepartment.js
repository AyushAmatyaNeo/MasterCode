
function setTemplate(temp) {
    var returnvalue = '';
    if (temp == null || temp == 'null' || typeof temp == 'undefined') {
        var checkLeaveVal = '';
    } else {
        var checkLeaveVal = temp.slice(0, 2);
    }
    if (temp == 'PR') {
        returnvalue = 'blue';
    }
    else if (temp == 'AB') {
        returnvalue = 'red';
    } else if (checkLeaveVal == "L-" || checkLeaveVal == "HL") {
        returnvalue = 'green';
    } else if (temp == 'DO') {
        returnvalue = 'yellow';
    } else if (temp == 'HD') {
        returnvalue = 'purple';
    } else if (temp == 'WD') {
        returnvalue = 'purple-soft';
    } else if (temp == 'WH') {
        returnvalue = 'yellow-soft';
    } else if (temp == 'LV') {
        returnvalue = 'green';
    }
    else if (temp == 'TV') {
        returnvalue = 'blue';
    }
    else if (temp == 'TN') {
        returnvalue = 'violet';
    } else if (temp == 'EC') {
        returnvalue = 'brown';
    }
    return returnvalue;
}


function setAbbr(temp) {
    var returnvalue = '';
    if (temp == null || temp == 'null' || typeof temp == 'undefined') {
        var checkLeaveVal = '';
    } else {
        var checkLeaveVal = temp.slice(0, 2);
    }
    if (temp == 'PR') {
        returnvalue = 'Present';
    }
    else if (temp == 'AB') {
        returnvalue = 'Absent';
    } else if (checkLeaveVal == "L-") {
        returnvalue = 'On Leave';
    } else if (checkLeaveVal == "HL") {
        returnvalue = 'On Half Leave';
    } else if (temp == 'DO') {
        returnvalue = 'Day Off';
    } else if (temp == 'HD') {
        returnvalue = 'Holiday';
    } else if (temp == 'WD') {
        returnvalue = 'Work On Day Off';
    } else if (temp == 'WH') {
        returnvalue = 'Work On Holiday';
    } else if (temp == 'LV') {
        returnvalue = 'Leave';
    }
    else if (temp == 'TV') {
        returnvalue = 'Travel';
    } else if (temp == 'TN') {
        returnvalue = 'Training';
    } else if (temp == 'EC') {
        returnvalue = 'Events';
    }
    return returnvalue;
}


(function ($, app) {
    'use strict';
    $(document).ready(function () {
        $("select").select2();
        var $table = $("#report");
        var $attendance = $("#attendance");
        var $monthDetails = $("#monthDetails");
        var $leaveDetails = $("#leaveDetails");
        var $employeeId = $('#employeeId');
        app.populateSelect($employeeId, document.employees, 'id', 'name', null, null, false);

        app.startEndDatePickerWithNepali('nepaliFromDate', 'fromDate', 'nepaliToDate', 'toDate', null, false);
        var exportVals;
        //        var exportVals={
        //            'FULL_NAME': 'Employee Name',
        //        };


        var months = null;
        var $year = $('#fiscalYearId');
        var $month = $('#monthId');
        let selectedMonthId;
        app.setFiscalMonth($year, $month, function (yearList, monthList, currentMonth) {
            months = monthList;
        });

        var $fromDate = $('#fromDate');
        var $toDate = $('#toDate');
        var $nepaliFromDate = $('#nepaliFromDate');
        var $nepaliToDate = $('#nepaliToDate');

        var monthChange = function ($this) {
            var value = $this.val();
            if (value == null) {
                return;
            }
            var selectedMonthList = months.filter(function (item) {
                return item['MONTH_ID'] === value;
            });
            if (selectedMonthList.length <= 0) {
                return;
            }
            $fromDate.val(selectedMonthList[0]['FROM_DATE']);
            $toDate.val(selectedMonthList[0]['TO_DATE']);
            $nepaliFromDate.val(nepaliDatePickerExt.fromEnglishToNepali(selectedMonthList[0]['FROM_DATE']));
            $nepaliToDate.val(nepaliDatePickerExt.fromEnglishToNepali(selectedMonthList[0]['TO_DATE']));
            selectedMonthId = selectedMonthList[0]['MONTH_ID'];
        };
        $month.on('change', function () {
            monthChange($(this));
        });
        var selectedCheckboxValues;
        var $search = $('#search');
        $search.on('click', function () {
            var data = document.searchManager.getSearchValues();
            data['monthCodeId'] = $month.val();
            data['fromDate'] = $fromDate.val();
            data['toDate'] = $toDate.val();
            data['nepfromDate'] = $nepaliFromDate.val();
            data['neptoDate'] = $nepaliToDate.val();
            selectedCheckboxValues = $('input[name="checkboxChoices[]"]:checked').map(function () {
                return this.value;
            }).get().join(', ');

            app.serverRequest('', data).then(function (response) {
                //                var monthDays=response.data.monthDetail.DAYS;
                var leaveDetails = response.data.leaveDetails;
                var kendoDetails = response.data.kendoDetails;
                var monthData = response.data.monthData;

                // console.log(kendoDetails);
                var columns = generateColsForKendo(kendoDetails, monthData, leaveDetails);
                // console.log(columns);
                columns.forEach(function (column) {
                    column.headerAttributes = {
                        "style": "text-align: center;"
                    };
                    column.attributes = {
                        "style": "text-align: center;"
                    };
                });

                $table.empty();

                $table.kendoGrid({
                    toolbar: ["excel"],
                    excel: {
                        fileName: 'DepartmentWiseDaily',
                        filterable: false,
                        allPages: true
                    },
                    height: 450,
                    scrollable: true,
                    columns: columns,
                    sortable: true,
                    filterable: true,
                    groupable: true,
                    dataBound: function (e) {
                        var grid = e.sender;
                        if (grid.dataSource.total() === 0) {
                            var colCount = grid.columns.length;
                            $(e.sender.wrapper)
                                .find('tbody')
                                .append('<tr class="kendo-data-row"><td colspan="' + colCount + '" class="no-data">There is no data to show in the grid.</td></tr>');
                        }
                    },
                    pageable: {
                        refresh: true,
                        pageSizes: true,
                        buttonCount: 5
                    },
                });

                app.renderKendoGrid($table, response.data.data);

            }, function (error) {

            });
        });


        function generateColsForKendo(daycount, monthData, leaveDetails) {
            exportVals = {
                'COMPANY_NAME': 'Company',
                'DEPARTMENT_NAME': 'Department',
                'EMPLOYEE_CODE': 'Employee Code',
                'FULL_NAME': 'Employee Name',
                'PRESENT': 'Present',
                'LEAVE': 'Leave',
                'DAYOFF': 'Day Off',
                'HOLIDAY': 'Holiday',
                'TRAVEL': 'Travel',
                'TRAINING': 'Training',
                'WORK_DAYOFF': 'Work on Dayoff',
                'WORK_HOLIDAY': 'Work On Holiday',
                'ABSENT': 'Absent',
                'TOTAL_ATTD': 'Total Attendance',
                'Overtime': {
                    'OT_DAYS': 'Days',
                    'TOTAL_OT_HOURS': 'Hour'
                },
                'Late In': {
                    'LATEIN_DAYS': 'Days',
                    'TOTAL_LATEIN': 'Hours'
                },
                'Early Out': {
                    'EARLYOUT_DAYS': 'Days',
                    'TOTAL_EARLYOUT': 'Hours'
                },
                'Miss Punch': {
                    'MISSPUNCH_DAYS': 'Days'
                }
            };
            var cols = [];
            if (selectedCheckboxValues !== null) {

                if (selectedCheckboxValues.includes('company')) {
                    cols.push({
                        field: 'COMPANY_NAME',
                        title: "Company",
                        locked: true,
                        width: 80
                    })
                }
                if (selectedCheckboxValues.includes('department')) {
                    cols.push({
                        field: 'DEPARTMENT_NAME',
                        title: "Department",
                        locked: true,
                        width: 85
                    })

                }
                if (selectedCheckboxValues.includes('code')) {
                    cols.push({
                        field: 'EMPLOYEE_CODE',
                        title: "Code",
                        locked: true,
                        width: 70
                    })
                }
                cols.push({
                    field: 'FULL_NAME',
                    title: "Name",
                    locked: true,
                    template: '<span>#:FULL_NAME#</span>',
                    width: 100
                });
            }


            if (!selectedCheckboxValues || selectedCheckboxValues.trim() === "") {

                cols.push({
                    field: 'PRESENT',
                    title: "Present",
                    template: '<span>#:PRESENT#</span>',
                    width: 70
                });
                cols.push({
                    field: 'HOLIDAY',
                    title: "Holiday",
                    template: '<span>#:HOLIDAY#</span>',
                    width: 70
                });
                cols.push({
                    field: 'LEAVE',
                    title: "Leave",
                    template: '<span>#:LEAVE#</span>',
                    width: 65
                });

                cols.push({
                    field: 'DAYOFF',
                    title: "Dayoff",
                    template: '<span>#:DAYOFF#</span>',
                    width: 65
                });
                cols.push({
                    field: 'TRAVEL',
                    title: "Travel",
                    template: '<span>#:TRAVEL#</span>',
                    width: 65
                });
                cols.push({
                    field: 'TRAINING',
                    title: "Training",
                    template: '<span>#:TRAINING#</span>',
                    width: 70
                });
                cols.push({
                    field: 'EVENT_CONF ',
                    title: "Event",
                    template: '<span>#:EVENT_CONF#</span>',
                    width: 70
                });
                cols.push({
                    field: 'WORK_DAYOFF',
                    title: "Work Dayoff",
                    template: '<span>#:WORK_DAYOFF#</span>',
                    width: 90
                });
                cols.push({
                    field: 'WORK_HOLIDAY',
                    title: "Work Holiday",
                    template: '<span>#:WORK_HOLIDAY#</span>',
                    width: 90
                });
                cols.push({
                    field: 'ABSENT',
                    title: "Absent",
                    template: '<span>#:ABSENT#</span>',
                    width: 70
                });
                cols.push({
                    field: 'TOTAL_ATTD',
                    title: "Total Attendance",
                    template: '<span>#:TOTAL_ATTD#</span>',
                    width: 120
                });
                cols.push({
                    title: "Overtime",
                    columns: [
                        {
                            field: "OT_DAYS",
                            title: "Days",
                            template: '<span>#:OT_DAYS#</span>',
                            width: 55
                        },
                        {
                            field: "TOTAL_OT_HOURS",
                            title: "Hour",
                            template: '<span>#:TOTAL_OT_HOURS#</span>',
                            width: 55
                        }
                    ]
                });
                cols.push({
                    title: "Late In",
                    columns: [
                        {
                            field: "LATEIN_DAYS",
                            title: "Days",
                            template: '<span>#:LATEIN_DAYS#</span>',
                            width: 55
                        },
                        {
                            field: "TOTAL_LATEIN",
                            title: "Hours",
                            template: '<span>#:TOTAL_LATEIN#</span>',
                            width: 70
                        }
                    ]
                });
                cols.push({
                    title: "Early Out",
                    columns: [
                        {
                            field: "EARLYOUT_DAYS",
                            title: "Days",
                            template: '<span>#:EARLYOUT_DAYS#</span>',
                            width: 55
                        },
                        {
                            field: "TOTAL_EARLYOUT",
                            title: "Hours",
                            template: '<span>#:TOTAL_EARLYOUT#</span>',
                            width: 70
                        }
                    ]
                });
                cols.push({
                    title: "Miss Punch",
                    columns: [
                        {
                            field: "MISSPUNCH_DAYS",
                            title: "Days",
                            template: '<span>#:MISSPUNCH_DAYS#</span>',
                            width: 95
                        }
                    ]
                });
                var mainHeading = "Month " + monthData.YEAR + '-' + monthData.MONTH_EDESC;

                var mainHeadingObject = {
                    title: mainHeading,
                    columns: []
                };

                cols.splice(90, 0, mainHeadingObject);

                $.each(daycount, function (index, value) {
                    var temp = value.KENDO_NAME;
                    exportVals[temp] = value.COLUMN_NAME;
                    mainHeadingObject.columns.push({
                        field: temp,
                        title: value.COLUMN_NAME,
                        width: 38,
                        template: '<abbr title="#:setAbbr(' + temp + ')#"><button type="button" style="padding: 8px 0px 7px;" class="btn btn-block #:setTemplate(' + temp + ')#">#:(' + temp + ' == null) ? " " :' + temp + '#</button></abbr>',
                    });
                });
                cols.push({
                    field: 'TOTAL_HOUR_SUM',
                    title: "Total Hour",
                    template: '<span>#:TOTAL_HOUR_SUM#</span>',
                    width: 80
                });
                exportVals['TOTAL_HOUR_SUM'] = 'Total Hour';

                var leaveHeading = "Leave Details till date " + monthData.YEAR + '-' + monthData.MONTH_EDESC;

                var leaveHeadingObject = {
                    title: leaveHeading,
                    columns: []
                };

                cols.splice(cols.length, 0, leaveHeadingObject);

                $.each(leaveDetails, function (index, value) {
                    exportVals[value.LEAVE_STRING] = value.LEAVE_ENAME;

                    leaveHeadingObject.columns.push({
                        field: value.LEAVE_STRING,
                        title: value.LEAVE_ENAME,
                        width: 150
                    });
                });


            } else {
                if (selectedCheckboxValues.includes('attd')) {
                    cols.push({
                        field: 'PRESENT',
                        title: "Present",
                        template: '<span>#:PRESENT#</span>',
                        width: 70
                    });
                    cols.push({
                        field: 'HOLIDAY',
                        title: "Holiday",
                        template: '<span>#:HOLIDAY#</span>',
                        width: 70
                    });
                    cols.push({
                        field: 'LEAVE',
                        title: "Leave",
                        template: '<span>#:LEAVE#</span>',
                        width: 65
                    });

                    cols.push({
                        field: 'DAYOFF',
                        title: "Dayoff",
                        template: '<span>#:DAYOFF#</span>',
                        width: 65
                    });
                    cols.push({
                        field: 'TRAVEL',
                        title: "Travel",
                        template: '<span>#:TRAVEL#</span>',
                        width: 65
                    });
                    cols.push({
                        field: 'TRAINING',
                        title: "Training",
                        template: '<span>#:TRAINING#</span>',
                        width: 70
                    });
                    cols.push({
                        field: 'EVENT_CONF ',
                        title: "Event",
                        template: '<span>#:EVENT_CONF#</span>',
                        width: 70
                    });
                    cols.push({
                        field: 'WORK_DAYOFF',
                        title: "Work Dayoff",
                        template: '<span>#:WORK_DAYOFF#</span>',
                        width: 90
                    });
                    cols.push({
                        field: 'WORK_HOLIDAY',
                        title: "Work Holiday",
                        template: '<span>#:WORK_HOLIDAY#</span>',
                        width: 90
                    });
                    cols.push({
                        field: 'ABSENT',
                        title: "Absent",
                        template: '<span>#:ABSENT#</span>',
                        width: 70
                    });
                    cols.push({
                        field: 'TOTAL_ATTD',
                        title: "Total Attendance",
                        template: '<span>#:TOTAL_ATTD#</span>',
                        width: 120
                    });
                    cols.push({
                        title: "Overtime",
                        columns: [
                            {
                                field: "OT_DAYS",
                                title: "Days",
                                template: '<span>#:OT_DAYS#</span>',
                                width: 55
                            },
                            {
                                field: "TOTAL_OT_HOURS",
                                title: "Hour",
                                template: '<span>#:TOTAL_OT_HOURS#</span>',
                                width: 55
                            }
                        ]
                    });
                    cols.push({
                        title: "Late In",
                        columns: [
                            {
                                field: "LATEIN_DAYS",
                                title: "Days",
                                template: '<span>#:LATEIN_DAYS#</span>',
                                width: 55
                            },
                            {
                                field: "TOTAL_LATEIN",
                                title: "Hours",
                                template: '<span>#:TOTAL_LATEIN#</span>',
                                width: 70
                            }
                        ]
                    });
                    cols.push({
                        title: "Early Out",
                        columns: [
                            {
                                field: "EARLYOUT_DAYS",
                                title: "Days",
                                template: '<span>#:EARLYOUT_DAYS#</span>',
                                width: 55
                            },
                            {
                                field: "TOTAL_EARLYOUT",
                                title: "Hours",
                                template: '<span>#:TOTAL_EARLYOUT#</span>',
                                width: 70
                            }
                        ]
                    });
                    cols.push({
                        title: "Miss Punch",
                        columns: [
                            {
                                field: "MISSPUNCH_DAYS",
                                title: "Days",
                                template: '<span>#:MISSPUNCH_DAYS#</span>',
                                width: 95
                            }
                        ]
                    });
                }
                if (selectedCheckboxValues.includes('month')) {
                    var mainHeading = "Month " + monthData.YEAR + '-' + monthData.MONTH_EDESC;

                    var mainHeadingObject = {
                        title: mainHeading,
                        columns: []
                    };

                    cols.splice(90, 0, mainHeadingObject);

                    $.each(daycount, function (index, value) {
                        var temp = value.KENDO_NAME;
                        exportVals[temp] = value.COLUMN_NAME;

                        mainHeadingObject.columns.push({
                            field: temp,
                            title: value.COLUMN_NAME,
                            width: 38,
                            template: '<abbr title="#:setAbbr(' + temp + ')#"><button type="button" style="padding: 8px 0px 7px;" class="btn btn-block #:setTemplate(' + temp + ')#">#:(' + temp + ' == null) ? " " :' + temp + '#</button></abbr>',
                        });
                    });
                    cols.push({
                        field: 'TOTAL_HOUR_SUM',
                        title: "Total Hour",
                        template: '<span>#:TOTAL_HOUR_SUM#</span>',
                        width: 80
                    });
                    exportVals['TOTAL_HOUR_SUM'] = 'Total Hour';
                }
                // $.each(leaveDetails, function (index, value) {
                //     exportVals[value.LEAVE_STRING] = value.LEAVE_ENAME;
                //     cols.push({
                //         field: value.LEAVE_STRING,
                //         title: value.LEAVE_ENAME,
                //         width: 80
                //     });
                // });
                if (selectedCheckboxValues.includes('leave')) {

                    var leaveHeading = "Leave Details till date " + monthData.YEAR + '-' + monthData.MONTH_EDESC;

                    var leaveHeadingObject = {
                        title: leaveHeading,
                        columns: []
                    };

                    cols.splice(cols.length, 0, leaveHeadingObject);

                    $.each(leaveDetails, function (index, value) {
                        exportVals[value.LEAVE_STRING] = value.LEAVE_ENAME;

                        leaveHeadingObject.columns.push({
                            field: value.LEAVE_STRING,
                            title: value.LEAVE_ENAME,
                            width: 150
                        });
                    });
                }
            } return cols;
        }


        // $('#excelExport').on('click', function () {
        //     app.excelExportCustomised($table, exportVals, 'Department Wise Daily Attendance Report');
        //     // var grid = $("#report").data("kendoGrid");
        //     // grid.saveAsExcel();
        // });
        $('#excelExport').on('click', function () {
            var grid = $table.data("kendoGrid");

            grid.bind("excelExport", function (e) {
                var sheet = e.workbook.sheets[0];

                for (var rowIndex = 0; rowIndex < sheet.rows.length; rowIndex++) {
                    var row = sheet.rows[rowIndex];

                    for (var cellIndex = 0; cellIndex < row.cells.length; cellIndex++) {
                        var cell = row.cells[cellIndex];
                        var cellValue = cell.value;

                        if (rowIndex === 0) {
                            cell.hAlign = 'center';
                            cell.verticalAlign = 'center';
                        }

                        if (cellValue === 'AB') {
                            cell.color = '#FFFFFF';
                            cell.background = '#ff1a1a';
                        }
                        else if (cellValue === 'TV') {
                            cell.color = '#FFFFFF';
                            cell.background = '#1a75ff';
                        }
                        else if (cellValue === 'LV') {
                            cell.color = '#FFFFFF';
                            cell.background = '#40ff00';
                        }
                        else if (cellValue === 'DO') {
                            cell.color = '#FFFFFF';
                            cell.background = '#fcc603';
                        }
                        else if (cellValue === 'HD') {
                            cell.color = '#FFFFFF';
                            cell.background = '#0040ff';
                        }
                        else if (cellValue === 'WD') {
                            cell.color = '#FFFFFF';
                            cell.background = '#AF8FE9';
                        }
                        else if (cellValue === 'WH') {
                            cell.color = '#FFFFFF';
                            cell.background = '#ffff00';
                        } else if (cellValue === 'TN') {
                            cell.color = '#FFFFFF';
                            cell.background = '#7F00FF';
                        }
                        else if (cellValue === 'EC') {
                            cell.color = '#FFFFFF';
                            cell.background = '#964B00';
                        }
                    }
                }
            });

            grid.saveAsExcel();
        });


        $('#pdfExport').on('click', function () {
            app.exportToPDF($table, exportVals, 'Department Wise Daily Attendance Report', 'A1');
        });


    });
})(window.jQuery, window.app);