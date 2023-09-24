(function ($, app) {
    'use strict';
    $(document).ready(function () {
        $('select').select2();
        app.startEndDatePickerWithNepali('nepaliFromDate', 'fromDate', 'nepaliToDate', 'toDate', null, false);
        //        
        var $table = $('#table');
        app.searchTable($table, ['FULL_NAME', 'EMPLOYEE_CODE']);
        var $fromDate = $('#fromDate');
        var $toDate = $('#toDate');
        var $search = $('#search');
        var columns = [{ title: "Code", field: "EMPLOYEE_CODE", width: 80 },
        { title: "Employee", field: "FULL_NAME", width: 100 }];

        var selectedFromDate;
        var selectedToDate;

        var kendoConfig = {
            height: 500,
            scrollable: true,
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

                $('.r-cell').each(function () {
                    var $this = $(this);
                    var selectedShift = $this.attr('shift-id');
                    app.populateSelect($this, document.shifts, 'SHIFT_ID', 'SHIFT_ENAME', 'Select Shift', -1, selectedShift != "" ? selectedShift : null);
                });

            },
            pageable: {
                input: true,
                numeric: false,
                //                 refresh: true,
                pageSizes: true,
            },
            columns: columns,
            selectable: "multiple cell",
        };

        var cellTemplate = function (forDate, shiftId) {
            return `<select class="r-cell" employee-id="#:EMPLOYEE_ID#" for-date="#:${forDate}#" shift-id="#:${shiftId}#"></select>`;
        };
        var initialize = function ($table, kendoConfig) {
            $table.kendoGrid(kendoConfig);
        };

        $search.on('click', function () {
            if ($fromDate.val() === "") {
                $fromDate.focus();
                app.showMessage("From Date is required.", 'error');
                return;
            }
            if ($toDate.val() === "") {
                $toDate.focus();
                app.showMessage("To Date is required.", 'error');
                return;
            }
            selectedFromDate = $fromDate.val();
            selectedToDate = $toDate.val();

            $table.html("");
            var fromDate = $fromDate.val();
            var toDate = $toDate.val();
            var dateRange = app.getDateRangeBetween(nepaliDatePickerExt.getDate(fromDate), nepaliDatePickerExt.getDate(toDate));
            var weekday = new Array(7);
            weekday[0] = "Sun";
            weekday[1] = "Mon";
            weekday[2] = "Tue";
            weekday[3] = "Wed";
            weekday[4] = "Thu";
            weekday[5] = "Fri";
            weekday[6] = "Sat";
            columns.splice(2);
            for (var i in dateRange) {
                var columnTitle = dateRange[i].getFullYear() + "-" + ("0" + (dateRange[i].getMonth() + 1)).slice(-2) + "-" + ("0" + dateRange[i].getDate()).slice(-2) + " " + "(" + weekday[dateRange[i].getDay()] + ")";
                //                var forDate = "f" + dateRange[i].getFullYear() + ("0" + (dateRange[i].getMonth() + 1)).slice(-2) + ("0" + dateRange[i].getDate()).slice(-2);
                //                var shiftId = "s" + dateRange[i].getFullYear() + ("0" + (dateRange[i].getMonth() + 1)).slice(-2) + ("0" + dateRange[i].getDate()).slice(-2);
                var forDate = "F" + dateRange[i].getFullYear() + ("0" + (dateRange[i].getMonth() + 1)).slice(-2) + ("0" + dateRange[i].getDate()).slice(-2) + "_D";
                var shiftId = "F" + dateRange[i].getFullYear() + ("0" + (dateRange[i].getMonth() + 1)).slice(-2) + ("0" + dateRange[i].getDate()).slice(-2) + "_S";
                columns.push({ title: columnTitle, width: 170, field: [forDate, "EMPLOYEE_ID", shiftId], template: cellTemplate(forDate, shiftId) });
            }

            initialize($table, kendoConfig);
            getRoaster(function (rData) {
                //                var employees = document.searchManager.getSelectedEmployee();
                //
                //                var data = [];
                //                var searchInRData = function (on, by, datecheck) {
                //                    for (var i in on) {
                //                        if (on[i]['EMPLOYEE_ID'] == by['EMPLOYEE_ID'] && on[i]['FOR_CHECK'] == by[datecheck]) {
                //                            return on[i];
                //                        }
                //                    }
                //                    return false;
                //                };
                //
                //                for (var i in employees) {
                //                    var cell = {'FULL_NAME': employees[i]['FULL_NAME']};
                //                    for (var j in dateRange) {
                //                        var columndate = nepaliDatePickerExt.getFormatedDate(dateRange[j]);
                //                        var dateCheckString = "f" + dateRange[j].getFullYear() + ("0" + (dateRange[j].getMonth() + 1)).slice(-2) + ("0" + dateRange[j].getDate()).slice(-2);
                //                        cell["f" + dateRange[j].getFullYear() + ("0" + (dateRange[j].getMonth() + 1)).slice(-2) + ("0" + dateRange[j].getDate()).slice(-2)] = columndate;
                //                        cell['EMPLOYEE_ID'] = employees[i]['EMPLOYEE_ID'];
                //                        var check = searchInRData(rData, cell, dateCheckString);
                //                        cell["s" + dateRange[j].getFullYear() + ("0" + (dateRange[j].getMonth() + 1)).slice(-2) + ("0" + dateRange[j].getDate()).slice(-2)] = check != false ? check['SHIFT_ID'] : '';
                //                    }
                //                    data.push(cell);
                //                }
                app.renderKendoGrid($table, rData);
                $('.r-cell').each(function () {
                    var $this = $(this);
                    var selectedShift = $this.attr('shift-id');
                    app.populateSelect($this, document.shifts, 'SHIFT_ID', 'SHIFT_ENAME', 'Select Shift', -1, selectedShift != "" ? selectedShift : null);
                });

                /////////------

                //app.renderKendoGrid($table, rData);



            });
        });

        var getRoaster = function (fn) {
            var q = document.searchManager.getSearchValues();
            q['fromDate'] = $fromDate.val();
            q['toDate'] = $toDate.val();
            app.pullDataById(document.getRoasterListLink, { q }).then(function (response) {
                var data = response.data;
                fn(data);
                for (var i in data) {
                    $("select[employee-id=" + data[i]['EMPLOYEE_ID'] + "][for-date=" + data[i]['FOR_DATE'] + "]").val(data[i]['SHIFT_ID']);
                }
            }, function () {

            });
        };

        $('#assignBtn').on('click', function () {
            var data = [];
            $('.r-cell').each(function () {
                var $this = $(this);
                //                if ($this.val() !== null && $this.val() != -1) {
                if ($this.val() !== null) {
                    data.push({
                        'EMPLOYEE_ID': $this.attr('employee-id'),
                        'FOR_DATE': $this.attr('for-date'),
                        'SHIFT_ID': $this.val()
                    });
                }
            });
            app.serverRequest(document.assignRoasterLink, { 'data': data }).then(function (response) {
                app.showMessage('Roster assigned successfully.');
                window.location.reload();
            }, function (error) {
            });

        });



        $table.on("click", ".r-cell", function () {
            var $this = $(this);
            let row = $(this).closest("tr"),
                grid = $('#table').data("kendoGrid"),
                dataItem = grid.dataItem(row);
            var ds = grid.dataSource;
            var selectedRow = ds.getByUid(dataItem.uid);
            var selectedShiftId = $this.val();
            var selectedDate = $this.attr('for-date');

            app.serverRequest(document.getShiftDetails, {
                shiftId: selectedShiftId,
                fromDate: selectedFromDate,
                toDate: selectedToDate,
                selectedDate: selectedDate
            }).then(function (response) {
                if (response.success == true) {
                    console.log(response);
                    $.each(response.data, function (key, value) {
                        var tempDate = new Date(value.DATES);
                        var tempDateVal = "F" + tempDate.getFullYear() + ("0" + (tempDate.getMonth() + 1)).slice(-2) + ("0" + tempDate.getDate()).slice(-2) + "_S";
                        if (key > 0) {
                            if (value.DAY_OFF == 'DAY_OFF' && value.DAY == value.WEEK_NO) {
                                var currentSelectElement = $this.closest('tr').find('[for-date=' + value.DATES + ']');
                                app.populateSelect(currentSelectElement, document.shifts, 'SHIFT_ID', 'SHIFT_ENAME', 'Select Shift', -1, value.SHIFT_ID);
                                selectedRow.set(tempDateVal, value.SHIFT_ID);
                            }

                            if (value.DAY_OFF != 'DAY_OFF') {
                                var currentSelectElement = $this.closest('tr').find('[for-date=' + value.DATES + ']');
                                app.populateSelect(currentSelectElement, document.shifts, 'SHIFT_ID', 'SHIFT_ENAME', 'Select Shift', -1, value.SHIFT_ID);
                                selectedRow.set(tempDateVal, value.SHIFT_ID);
                            }

                        } else {
                            selectedRow.set(tempDateVal, value.SHIFT_ID);
                        }
                    });
                }
            });
        });

    });
})(window.jQuery, window.app);