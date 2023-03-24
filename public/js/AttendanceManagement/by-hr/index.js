(function ($, app) {
    'use strict';
    $(document).ready(function () {
        var $fromDate = $('#fromDate');
        var $toDate = $('#toDate'); 
        var $presentStatusId = $("#presentStatusId");
        var $status = $('#statusId');
        var $table = $('#table');
        var $search = $('#search');
        var $presentType = $("#presentType");

        $('select').select2();
        $('#inTime').combodate({
            minuteStep: 1
        });
        $('#outTime').combodate({
            minuteStep: 1
        });
        
        (document.allowShiftChange=="Y")? $(".manualShift").show(): $(".manualShift").hide();
        (document.allowTimeChange=="Y")? $(".manualTime").show(): $(".manualTime").hide();

        $.each(document.searchManager.getIds(), function (key, value) {
            $('#' + value).select2();
        });
        $presentStatusId.select2();
        $status.select2();
        app.startEndDatePickerWithNepali('nepaliFromDate', 'fromDate', 'nepaliToDate', 'toDate', null, false);
        app.getServerDate().then(function (response) {
            $fromDate.val(response.data.serverDate);
            $('#nepaliFromDate').val(nepaliDatePickerExt.fromEnglishToNepali(response.data.serverDate));
        });

        var detailInit = function (e) {
            var dataSource = $table.data("kendoGrid").dataSource.data();
            var parentId = e.data.ID;
            var childData = $.grep(dataSource, function (e) {
                return e.ID === parentId;
            });
            var inOutTimeList = null;
            app.serverRequest(document.pullInOutTimeLink, {
                employeeId: e.data.EMPLOYEE_ID,
                attendanceDt: e.data.ATTENDANCE_DT
            }).then(function (success) {
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
                        read: {
                            cache: false
                        }
                    },
                    scrollable: false,
                    sortable: false,
                    pageable: false,
                    serverPaging: true,
                    serverSorting: true,
                    serverFiltering: true,
                    columns:
                            [
                                {field: "IN_TIME", title: "In Time"},
                                {field: "OUT_TIME", title: "Out Out"},
                            ]
                }).data("kendoGrid");
                $("<div/>", {
                    class: "col-sm-6",
                    css: {
                        float: "left",
                        padding: "0px",
                        margin: "0px 0px 0px 20px"
                    }
                }).appendTo(e.detailCell).kendoGrid({
                    dataSource: {
                        data: childData,
                        pageSize: 5,
                        read: {
                            cache: false
                        }
                    },
                    scrollable: false,
                    sortable: false,
                    pageable: false,
                    serverPaging: true, 
                    serverSorting: true,
                    serverFiltering: true,
                    columns:
                            [
                                {field: "IN_REMARKS", title: "In Remarks"},
                                {field: "OUT_REMARKS", title: "Out Remarks"},
                            ]
                }).data("kendoGrid");
                $("<div/>", {
                    class: "col-sm-2",
                    css: {
                        float: "left",
                        padding: "0px", 
                        margin: "0px 0px 0px 20px",
                        width: "11%"
                    }
                }).appendTo(e.detailCell).kendoGrid({
                    dataSource: {
                        data: childData,
                        pageSize: 5,
                        read: {
                            cache: false
                        }
                    },
                    scrollable: false,
                    sortable: false,
                    pageable: false,
                    serverPaging: true,
                    serverSorting: true,
                    serverFiltering: true,
                    columns:
                            [
                                {
                                    template: "<img class='img-thumbnail' style='height:35px;width:40px;' src='" + document.picUrl + "' id=''/>",
                                    field: "IN_REMARKS", title: "Attendance Photo"
                                },
                            ]
                }).data("kendoGrid");
            }, function (failure) {
                console.log(failure);
            });
        }; 
        app.initializeKendoGrid($table, [
            {
                title: 'Select All',
                headerTemplate: "<input type='checkbox' id='header-chb' class='k-checkbox header-checkbox'><label class='k-checkbox-label' for='header-chb'></label>",
                template: "<input type='checkbox' id='#:ID#'  class='k-checkbox row-checkbox'><label class='k-checkbox-label' for='#:ID#'></label>",
                width: 50
            },
            {field: "COMPANY_NAME", title: "Company"},
            {field: "DEPARTMENT_NAME", title: "Department"},
            {field: "EMPLOYEE_CODE", title: "Code"},
            {field: "EMPLOYEE_NAME", title: "Employee", template: "<span>#: (EMPLOYEE_NAME == null) ? '-' : EMPLOYEE_NAME # </span>"},
            {title: "Attendance Date",
                columns: [
                    {
                        field: "ATTENDANCE_DT",
                        title: "AD",
                    },
                    {
                        field: "ATTENDANCE_DT_N",
                        title: "BS",
                    }
                ]},
            {field: "IN_TIME", title: "Check In", template: "<span>#: (IN_TIME == null) ? '-' : IN_TIME # </span>"},
            {field: "OUT_TIME", title: "Check Out", template: "<span>#: (OUT_TIME == null) ? '-' : OUT_TIME # </span>"},
            {field: "IN_REMARKS", title: "In Remarks"},
            {field: "OUT_REMARKS", title: "Out Remarks"},
			{field: "TOTAL_HOUR", title: "Total Hour"},
			{field: "OT_HOUR", title: "OT Hour"},
//            {field: "SYSTEM_OVERTIME", title: "OT", template: "<span>#: (SYSTEM_OVERTIME == null) ? '-' : SYSTEM_OVERTIME # </span>"},
//            {field: "MANUAL_OVERTIME", title: "MOT", template: "<span>#: (MANUAL_OVERTIME == null) ? '-' : MANUAL_OVERTIME # </span>"},
            {field: "STATUS", title: "Status", template: "<span>#: (STATUS == null) ? '-' : STATUS # </span>"},
            {title: 'Shift Details', columns: [
                    {field: "SHIFT_ENAME", title: "Name"},
                    {field: "START_TIME", title: "From"},
                    {field: "END_TIME", title: "To"},
                ]}
        ], detailInit, null, null, 'Attendance Report.xlsx');

        $search.on("click", function () {

            var q = document.searchManager.getSearchValues();
            q['fromDate'] = $fromDate.val();
            q['toDate'] = $toDate.val();
            q['status'] = $status.val();
            q['presentStatus'] = $presentStatusId.val();
            q.presentType = $presentType.val();
            app.serverRequest(document.pullAttendanceWS, q).then(function (response) {
                if (response.success) {
                    app.renderKendoGrid($table, response.data);
                    selectItems = {};
                    var data = response.data;
                    for (var i in data) {
                        selectItems[data[i]['ID']] = {'checked': false, 'employeeId': data[i]['EMPLOYEE_ID'], 'attendanceDt': data[i]['ATTENDANCE_DT']};
                    }
                } else {
                    app.showMessage(response.error, 'error');
                }
            }, function (error) {
                app.showMessage(error, 'error');
            });
        });

        app.searchTable($table, ['EMPLOYEE_NAME', 'EMPLOYEE_CODE']);
        var exportMap = {
            'COMPANY_NAME': ' Company',
            'BRANCH_NAME': 'Branch',
            'BRANCH_PROVINCE':'Branch Province',
            'DEPARTMENT_NAME': ' Department',
            'POSITION_NAME': ' Position',
            'EMPLOYEE_CODE': 'Code',
            'EMPLOYEE_NAME': ' Name',
            'ATTENDANCE_DT': 'Attendance Date(AD)',
            'ATTENDANCE_DT_N': 'Attendance Date(BS)',
            'IN_TIME': 'In Time',
            'OUT_TIME': 'Out Time',
            'IN_REMARKS': 'In Remarks',
            'OUT_REMARKS': 'Out Remarks',
            'TOTAL_HOUR': 'Total Hour',
//            'SYSTEM_OVERTIME': 'System OT',
//            'MANUAL_OVERTIME': 'Manual OT',
            'STATUS': 'Status',
            'SHIFT_ENAME': 'Shift Name',
            'START_TIME': 'Start Time',
            'END_TIME': 'End Time',
            'FUNCTIONAL_TYPE_EDESC': 'Functional Type'
        };
        $('#excelExport').on('click', function () {
            app.excelExport($table, exportMap, "Attendance Report.xlsx");
        });
        $('#pdfExport').on('click', function () {
            app.exportToPDF($table, exportMap, "Attendance Report.pdf",'A3');
        });
        
        $('#pdfExportDaily').on('click', function () {
            app.exportToPDFPotrait($table, {
                'SN':'Sn',
            'EMPLOYEE_CODE': 'Code',
            'EMPLOYEE_NAME': ' Name',
            'FUNCTIONAL_TYPE_EDESC': 'Functional Type',
            'ATTENDANCE_DT': 'Date',
            'IN_TIME': 'In Time',
            'OUT_TIME': 'Out Time'}, "DailyAttendance.pdf");
        });

        $('#excelExportDaily').on('click',function(){
            app.excelExport($table, {
                'SN':'Sn',
            'EMPLOYEE_CODE': 'Code',
            'EMPLOYEE_NAME': ' Name',
            'FUNCTIONAL_TYPE_EDESC': 'Functional Type',
            'ATTENDANCE_DT': 'Date',
            'IN_TIME': 'In Time',
            'OUT_TIME': 'Out Time'}, "DailyAttendance.xlsx");
        });

        var selectItems = {};
        var $bulkBtnContainer = $('#acceptRejectDiv');
        var $bulkBtns = $(".btnApproveReject");
        $table.on('click', '.k-checkbox', function () {
            var checked = this.checked;
            var row = $(this).closest("tr");
            var grid = $table.data("kendoGrid");
            var dataItem = grid.dataItem(row);
            if (selectItems[dataItem['ID']] === undefined) {
                selectItems[dataItem['ID']] = {'checked': checked, 'employeeId': dataItem['EMPLOYEE_ID'], 'attendanceDt': dataItem['ATTENDANCE_DT']};
            } else {
                selectItems[dataItem['ID']]['checked'] = checked;
            }
            if (checked) {
                row.addClass("k-state-selected");
                $bulkBtnContainer.show();
            } else {
                row.removeClass("k-state-selected");
                var atleastOne = false;
                for (var key in selectItems) {
                    if (selectItems[key]['checked']) {
                        atleastOne = true;
                        return;
                    }
                }
                if (atleastOne) {
                    $bulkBtnContainer.show();
                } else {
                    $bulkBtnContainer.hide();
                }
 
            }
        });
        $bulkBtns.bind("click", function () { 
            var btnId = $(this).attr('id');
            var selectedValues = [];
            var shiftId = $("#shiftId").val();
            var in_time = $("#inTime").val();
            var out_time = $("#outTime").val();
            var outNextDay = $("#outNextDay").prop('checked');
            $bulkBtnContainer.hide();
            var impactOtherDays = $impactOtherDays.prop('checked');
            for (var i in selectItems) {
                if (selectItems[i].checked) {
                    selectedValues.push({
                        in_time: in_time,
                        out_time: out_time, 
                        shiftId: shiftId,
                        id: i, employeeId: selectItems[i]['employeeId'], 
                        attendanceDt: selectItems[i]['attendanceDt'], 
                        action: btnId, 
                        impactOtherDays: impactOtherDays,
                        outNextDay: outNextDay
                    });
                }
            }
            app.bulkServerRequest(document.bulkAttendanceWS, selectedValues, function () {
                $search.trigger('click');
            }, function (data, error) {
                
            }); 
        });
        var $impactOtherDays = $('#impact_other_days');


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
            var map = {1: 'P', 2: 'L', 3: 'T', 4: 'TVL', 5: 'WOH', 6: 'LI', 7: 'EO'};
            if (idFromParameter == 8) {
                $presentStatusId.prop("checked", true);
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

        let $branch = $('#branchId');
        let $province= $('#province');
        let populateBranch ;

        $province.on("change", function () {
            populateBranch = [];
            $.each(document.braProv, function(k,v){
                if(v == $province.val()){
                    populateBranch.push(k);
                }
            });
            $branch.val(populateBranch).change();
        });

    });
})(window.jQuery, window.app);
