(function ($, app) {
    'use strict';
    $(document).ready(function () {
        $("select").select2();
        app.startEndDatePickerWithNepali('nepaliFromDate', 'fromDate', 'nepaliToDate', 'toDate', null, true);

        app.startEndDatePickerWithNepali('nepaliFromDate1', 'fromDate1', 'nepaliToDate1', 'toDate1', null, true);

        var setEndDate = function () {
            app.getServerDate().then(function (response) {
                $("#fromDate1").datepicker('setEndDate', app.getSystemDate(response.data.serverDate));
                $("#toDate1").datepicker('setEndDate', app.getSystemDate(response.data.serverDate))
            }, function (error) {
                console.log("error=>getServerDate", error);
            });
        }
        var resetDate = function () {
            $("#calculateOvertimeForm").trigger('reset');
            $("#fromDate1").val('').datepicker('remove').datepicker();
            $("#toDate1").val('').datepicker('remove').datepicker();
            setEndDate();
        }

        $('.calculateOvertimeFormModal').on('hidden.bs.modal', function (e)
        {
            resetDate();
        });
        $("#resetForm").on("click", function () {
            resetDate();
        });

        setEndDate();

        $('#reset').on('click', function () {
            $('.form-control').val("");
        });
    });
})(window.jQuery, window.app);

angular.module('hris', [])
        .controller("attendanceWidOTListController", function ($scope, $http) {
            var $tableContainer = $("#attendanceWidOTTable");
            var displayKendoFirstTime = true;
            $scope.view = function () {
                var employeeId = angular.element(document.getElementById('employeeId')).val();
                var companyId = angular.element(document.getElementById('companyId')).val();
                var branchId = angular.element(document.getElementById('branchId')).val();
                var departmentId = angular.element(document.getElementById('departmentId')).val();
                var designationId = angular.element(document.getElementById('designationId')).val();
                var positionId = angular.element(document.getElementById('positionId')).val();
                var serviceTypeId = angular.element(document.getElementById('serviceTypeId')).val();
                var serviceEventTypeId = angular.element(document.getElementById('serviceEventTypeId')).val();
                var fromDate = angular.element(document.getElementById('fromDate')).val();
                var toDate = angular.element(document.getElementById('toDate')).val();
                var status = angular.element(document.getElementById('statusId')).val();
                var employeeTypeId = angular.element(document.getElementById('employeeTypeId')).val();
                var overtimeOnly = 0;
                if (($("#overtimeOnly").is(":checked"))) {
                    overtimeOnly = 1;
                }
                window.app.serverRequest(document.pullAttendanceWidOvertimeListLink, {
                    'employeeId': employeeId,
                    'companyId': companyId,
                    'branchId': branchId,
                    'departmentId': departmentId,
                    'designationId': designationId,
                    'positionId': positionId,
                    'serviceTypeId': serviceTypeId,
                    'serviceEventTypeId': serviceEventTypeId,
                    'fromDate': fromDate,
                    'toDate': toDate,
                    'status': status,
                    'employeeTypeId': employeeTypeId,
                    'overtimeOnly': parseInt(overtimeOnly)
                }).then(function (success) {
                    console.log(success.data);
                    if (displayKendoFirstTime) {
                        $scope.initializekendoGrid();
                        displayKendoFirstTime = false;
                    }
                    var dataSource = new kendo.data.DataSource({data: success.data, pageSize: 20});
                    var grid = $('#attendanceWidOTTable').data("kendoGrid");
                    dataSource.read();
                    grid.setDataSource(dataSource);

                    window.app.scrollTo('attendanceWidOTTable');
                }, function (failure) {
                    console.log(failure);
                });
            };
            var firstTime = true;
            $scope.initializekendoGrid = function () {
                $("#attendanceWidOTTable").kendoGrid({
                    excel: {
                        fileName: "AttendanceWidOTList.xlsx",
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
                    columns: [
                        {field: "EMPLOYEE_NAME", title: "Employee", width: 160},
                        {field: "ATTENDANCE_DT", title: "Attendance Date", width: 120},
                        {field: "IN_TIME", title: "Check In", width: 80},
                        {field: "OUT_TIME", title: "Check Out", width: 100},
                        {field: "STATUS", title: "Status", width: 80},
                        {field: "OVERTIME_IN_HOUR", title: "Overtime(in Hour)", width: 130},
                    ],
                    detailInit: detailInit,
                });

                app.searchTable('attendanceWidOTTable', ['EMPLOYEE_NAME', 'ATTENDANCE_DT', 'IN_TIME', 'OUT_TIME', 'STATUS', 'OVERTIME_IN_HOUR']);

                app.pdfExport(
                        'attendanceWidOTTable',
                        {
                            'EMPLOYEE_NAME': ' Name',
                            'ATTENDANCE_DT': 'Attendance Date',
                            'IN_TIME': 'In Time',
                            'OUT_TIME': 'Out Time',
                            'IN_REMARKS': 'In Remarks',
                            'OUT_REMARKS': 'Out Remarks',
                            'TOTAL_HOUR': 'Total Hrs',
                            'OVERTIME_IN_HOUR': 'Overtime Hrs',
                            'STATUS': 'Status'
                        }
                );



            };
            function detailInit(e) {
                var dataSource = $("#attendanceWidOTTable").data("kendoGrid").dataSource.data();
                var parentId = e.data.ID;
                var childData = $.grep(dataSource, function (e) {
                    return e.ID === parentId;
                });
                if (firstTime) {
                    App.blockUI({target: "#hris-page-content"});

                } else {
                    App.blockUI({target: "#attendanceWidOTTable"});
                }
                window.app.pullDataById(document.pullInOutTimeLink, {
                    employeeId: e.data.EMPLOYEE_ID,
                    attendanceDt: e.data.ATTENDANCE_DT
                }).then(function (success) {
                    if (firstTime) {
                        App.unblockUI("#hris-page-content");
                        firstTime = false;
                    } else {
                        App.unblockUI("#attendanceWidOTTable");
                    }
                    console.log(success.data);
                    if (success.data.length > 0) {
                        inOutTimeList = success.data;
                    } else {
                        inOutTimeList = childData;
                    }
                    $("<div/>", {
                        class: "col-sm-3",
                        css: {
                            float: "left",
                            padding: "0px",
                        }
                    }).appendTo(e.detailCell).kendoGrid({
                        dataSource: {
                            data: inOutTimeList,
                            pageSize: 10,
                        },
                        scrollable: false,
                        sortable: false,
                        pageable: false,
                        columns:
                                [
                                    {field: "IN_TIME", title: "In Time"},
                                    {field: "OUT_TIME", title: "Out Out"},
                                ]
                    }).data("kendoGrid");
                    $("<div/>", {
                        class: "col-sm-8",
                        css: {
                            float: "left",
                            padding: "0px",
                            margin: "0px 0px 0px 20px"
                        }
                    }).appendTo(e.detailCell).kendoGrid({
                        dataSource: {
                            data: childData,
                            pageSize: 5,
                        },
                        scrollable: false,
                        sortable: false,
                        pageable: false,
                        rowTemplate: kendo.template($("#rowTemplate").html()),
                        columns:
                                [
                                    {field: "IN_REMARKS", title: "In Remarks"},
                                    {field: "OUT_REMARKS", title: "Out Remarks"},
                                    {field: "DETAILS", title: "Overtime(From-To)"},
                                    {field: "DETAILS", title: "Overtime(In Hour)"},
                                ]
                    }).data("kendoGrid");
                }, function (failure) {
                    if (firstTime) {
                        App.unblockUI("#hris-page-content");
                        firstTime = false;
                    } else {
                        App.unblockUI("#attendanceWidOTTable");
                    }
                    console.log(failure);
                });
            }
            ;
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
                var rows = [{
                        cells: [
                            {value: "Employee Name"},
                            {value: "Attendance Date"},
                            {value: "Check In Time"},
                            {value: "Check Out Time"},
                            {value: "Late In Reason"},
                            {value: "Late Out Reason"},
                            {value: "Total Hour"},
                            {value: "Overtime(From-To)"},
                            {value: "Overtime(in Hour)"},
                            {value: "Status"}
                        ]
                    }];
                var dataSource = $("#attendanceWidOTTable").data("kendoGrid").dataSource;
                var filteredDataSource = new kendo.data.DataSource({
                    data: dataSource.data(),
                    filter: dataSource.filter()
                });

                filteredDataSource.read();
                var data = filteredDataSource.view();

                for (var i = 0; i < data.length; i++) {
                    var dataItem = data[i];
                    var details = [];
                    for (var j = 0; j < dataItem.DETAILS.length; j++) {
                        details.push(dataItem.DETAILS[j].START_TIME + "-" + dataItem.DETAILS[j].END_TIME);
                    }
                    var details1 = details.toString();
                    rows.push({
                        cells: [
                            {value: dataItem.EMPLOYEE_NAME},
                            {value: dataItem.ATTENDANCE_DT},
                            {value: dataItem.IN_TIME},
                            {value: dataItem.OUT_TIME},
                            {value: dataItem.IN_REMARKS},
                            {value: dataItem.OUT_REMARKS},
                            {value: dataItem.TOTAL_HOUR},
                            {value: details1},
                            {value: dataItem.OVERTIME_IN_HOUR},
                            {value: dataItem.STATUS}
                        ]
                    });
                }
                excelExport(rows);
                e.preventDefault();
            });

            function excelExport(rows) {
                var workbook = new kendo.ooxml.Workbook({
                    sheets: [
                        {
                            columns: [
                                {autoWidth: true},
                                {autoWidth: true},
                                {autoWidth: true},
                                {autoWidth: true},
                                {autoWidth: true},
                                {autoWidth: true},
                                {autoWidth: true},
                                {autoWidth: true},
                                {autoWidth: true},
                                {autoWidth: true}
                            ],
                            title: "Attendance Wid Overtime Report",
                            rows: rows
                        }
                    ]
                });
                kendo.saveAs({dataURI: workbook.toDataURL(), fileName: "AttendanceWidOTList.xlsx"});
            }

            function getInOutTime(employeeId, attendanceDt) {
                console.log(employeeId);
                console.log(attendanceDt);

            }


        });
