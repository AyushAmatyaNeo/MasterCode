/**
 * Created by punam on 10/5/16.
 */
(function ($, app) {
    'use strict';
    $(document).ready(function () {
        $('select').select2();
        //        console.log(document.currentEmployeeId);
        app.startEndDatePickerWithNepali('nepaliFromDate', 'fromDate', 'nepaliToDate', 'toDate', null, true);
        $("#reset").on("click", function () {
            if (typeof document.ids !== "undefined") {
                $.each(document.ids, function (key, value) {
                    $("#" + key).val(value).change();
                });
            }
            //            $(".form-control").val("");
        });
    });
})(window.jQuery, window.app);


angular.module('hris', [])
    .controller('attendanceController', function ($scope, $http) {
        $scope.attendanceList = [];
        var $tableContainer = $("#attendanceTable");
        var displayKendoFirstTime = true;
        $scope.view = function () {
            var employeeId = angular.element(document.getElementById('employeeId')).val();
            var fromDate = angular.element(document.getElementById('fromDate')).val();
            var toDate = angular.element(document.getElementById('toDate')).val();
            var status = angular.element(document.getElementById('statusId')).val();
            var missPunchOnly = 0;
            if (($("#missPunchOnly").is(":checked"))) {
                missPunchOnly = 1;
            }

            App.blockUI({ target: "#hris-page-content" });
            window.app.pullDataById(document.url, {
                action: 'pullManagerAttendaceReport',
                data: {
                    'currentEmployee': document.currentEmployeeId,
                    'fromDate': fromDate,
                    'toDate': toDate,
                    'employeeId': employeeId,
                    'status': status,
                    'missPunchOnly': missPunchOnly
                }
            }).then(function (success) {
                App.unblockUI("#hris-page-content");
                if (displayKendoFirstTime) {
                    $scope.initializekendoGrid();
                    displayKendoFirstTime = false;
                }
                var dataSource = new kendo.data.DataSource({ data: success.data, pageSize: 20 });
                var grid = $('#attendanceTable').data("kendoGrid");
                dataSource.read();
                grid.setDataSource(dataSource);
            }, function (failure) {
                App.unblockUI("#hris-page-content");
                console.log(failure);
            });
        }

        $scope.initializekendoGrid = function () {
            $("#attendanceTable").kendoGrid({
                excel: {
                    fileName: "AttendanceList.xlsx",
                    filterable: true,
                    allPages: true
                },
                height: 450,
                scrollable: true,
                sortable: true,
                filterable: true,
                pageable: {
                    input: true,
                    numeric: false
                },
                dataBound: gridDataBound,
                //                    rowTemplate: kendo.template($("#rowTemplate").html()),
                columns: [
                    { field: "EMPLOYEE_CODE", title: "Code" },
                    { field: "FULL_NAME", title: "Employee" },
                    {
                        title: "Attendance Date",
                        columns: [
                            {
                                field: "ATTENDANCE_DT",
                                title: "English",
                                template: "<span>#: (ATTENDANCE_DT == null) ? '-' : ATTENDANCE_DT #</span>"
                            },
                            {
                                field: "ATTENDANCE_DT_N",
                                title: "Nepali",
                                template: "<span> #: (ATTENDANCE_DT_N == null) ? '-' : ATTENDANCE_DT_N #</span>"
                            }]
                    },
                    { field: "IN_TIME", title: "Check In" },
                    { field: "OUT_TIME", title: "Check Out" },
                    { field: "START_TIME", title: "Start Time" },
                    { field: "END_TIME", title: "End Time" },
                    { field: "TOTAL_HOUR", title: "Total Hour" },
                    { field: "STATUS", title: "Status" },
                    { field: "IN_REMARKS", title: "Late In Reason" },
                    { field: "OUT_REMARKS", title: "Early Out Reason" },
                ]
            });

            app.searchTable('attendanceTable', ['FULL_NAME', 'EMPLOYEE_CODE', 'ATTENDANCE_DT', 'ATTENDANCE_DT_N', 'IN_TIME', 'OUT_TIME', 'TOTAL_HOUR', 'STATUS', 'IN_REMARKS', 'OUT_REMARKS']);
            app.pdfExport(
                'attendanceTable',
                {
                    'EMPLOYEE_CODE': 'Code',
                    'FULL_NAME': 'Employee',
                    'ATTENDANCE_DT': ' Attendance Date(AD)',
                    'ATTENDANCE_DT_N': ' Attendance Date(BS)',
                    'IN_TIME': 'In Time',
                    'OUT_TIME': 'Out Time',
                    'START_TIME': 'Start Time',
                    'END_TIME': 'End Time',
                    'TOTAL_HOUR': 'Total Hour',
                    'STATUS': 'Status',
                    'IN_REMARKS': 'In Remarks',
                    'OUT_REMARKS': 'Out Remarks'
                }
            );
            function gridDataBound(e) {
                var grid = e.sender;
                if (grid.dataSource.total() == 0) {
                    var colCount = grid.columns.length;
                    $(e.sender.wrapper)
                        .find('tbody')
                        .append('<tr class="kendo-data-row"><td colspan="' + colCount + '" class="no-data">There is no data to show in the grid.</td></tr>');
                }
            }
            ;
            $("#export").click(function (e) {
                var grid = $("#attendanceTable").data("kendoGrid");
                grid.saveAsExcel();
            });
        }


        //            start to get the current Date in  DD-MON-YYY format
        var m_names = new Array("Jan", "Feb", "Mar",
            "Apr", "May", "Jun", "Jul", "Aug", "Sep",
            "Oct", "Nov", "Dec");

        var d = new Date();

        //to get today Date
        var curr_date = d.getDate();
        var curr_month = d.getMonth();
        var curr_year = d.getFullYear();
        var todayDate = curr_date + "-" + m_names[curr_month] + "-" + curr_year;

        //to get yesterday Date
        var yes_date = new Date(d);
        yes_date.setDate(d.getDate() - 1);
        var yesterday_date = yes_date.getDate();
        var yesterday_month = yes_date.getMonth();
        var yesterday_year = yes_date.getFullYear();
        var yesterdayDate = yesterday_date + "-" + m_names[yesterday_month] + "-" + yesterday_year;

        //End to get Current Date and YesterDay Date

        var idFromParameter = window.location.href.substr(window.location.href.lastIndexOf('/') + 1);
        if (parseInt(idFromParameter) > 0) {
            var $status = angular.element(document.getElementById('statusId'));
            var $missPunchOnly = angular.element(document.getElementById('missPunchOnly'));
            var $fromDate = angular.element(document.getElementById('fromDate'));
            var $toDate = angular.element(document.getElementById('toDate'));
            var map = { 1: 'P', 2: 'L', 3: 'T', 4: 'TVL', 5: 'WOH', 6: 'LI', 7: 'EO' };
            if (idFromParameter == 8) {
                $missPunchOnly.prop("checked", true);
                $fromDate.val(yesterdayDate);
                $toDate.val(yesterdayDate);
            } else {
                $status.val(map[idFromParameter]).change();
                if (idFromParameter == 7 || idFromParameter == 6) {
                    $fromDate.val(yesterdayDate);
                    $toDate.val(yesterdayDate);
                } else {
                    $fromDate.val(todayDate);
                    $toDate.val(todayDate);
                }
            }
            $scope.view();
        }






    });