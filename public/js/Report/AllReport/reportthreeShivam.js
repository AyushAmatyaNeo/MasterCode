
function setTemplate(temp) {
    var returnvalue = '';
    if (temp == null || temp == 'null' || typeof temp =='undefined' ) {
        var checkLeaveVal ='';
    }else{
        var checkLeaveVal = temp.slice(0, 2);
    }
    if (temp == 'PR') {
        returnvalue = 'blue';
    } 
    else if (temp == 'AB') {
        returnvalue = 'red';
    } else if (checkLeaveVal  == "L-" || checkLeaveVal=="HL") {
        returnvalue = 'green';
    } else if (temp == 'DO') {
        returnvalue = 'yellow';
    } else if (temp == 'HD') {
        returnvalue = 'purple';
    } else if (temp == 'WD') {
        returnvalue = 'purple-soft';
    } else if (temp == 'WH') {
        returnvalue = 'yellow-soft';
    }
    return returnvalue;
}


function setAbbr(temp){
    var returnvalue = '';
    if (temp == null || temp == 'null' || typeof temp =='undefined' ) {
        var checkLeaveVal ='';
    }else{
        var checkLeaveVal = temp.slice(0, 2);
    }
    if (temp == 'PR') {
        returnvalue = 'Present';
    } 
    else if (temp == 'AB') {
        returnvalue = 'Absent';
    } else if (checkLeaveVal  == "L-") {
        returnvalue = 'On Leave';
    } else if (checkLeaveVal  == "HL") {
        returnvalue = 'On Half Leave';
    } else if (temp == 'DO') {
        returnvalue = 'Day Off';
    } else if (temp == 'HD') {
        returnvalue = 'Holiday';
    } else if (temp == 'WD') {
        returnvalue = 'Work On Day Off';
    } else if (temp == 'WH') {
        returnvalue = 'Work On Holiday';
    }
    return returnvalue;
}


(function ($, app) {
    'use strict';
    $(document).ready(function () {
        $("select").select2();
        var $table = $("#report");
        
        var exportVals;
//        var exportVals={
//            'FULL_NAME': 'Employee Name',
//        };


        var months = null;
        var $year = $('#fiscalYearId');
        var $month = $('#monthId');
        app.setFiscalMonth($year, $month, function (yearList, monthList, currentMonth) {
            months = monthList;
        });

        var $search = $('#search');
        $search.on('click', function () {
            var data = document.searchManager.getSearchValues();
            data['monthCodeId'] = $month.val();
            app.serverRequest('', data).then(function (response) {
                var monthDays=response.data.monthDetail.DAYS;
                var leaveDetails=response.data.leaveDetails;
                var columns=generateColsForKendo(monthDays,leaveDetails);
                
                
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
        
        
        function generateColsForKendo(dayCount,leaveDetails) {
              exportVals={
            'FULL_NAME': 'Employee Name',
            'PRESENT': 'Present',
            'ABSENT': 'Absent',
            'LEAVE': 'Leave',
            'DAYOFF': 'Day Off',
            'HOLIDAY': 'Holiday',
            'WORK_DAYOFF': 'Work on Dayoff',
            'WORK_HOLIDAY': 'Work On Holiday',
            'NIGHT_SHIFT_6': 'Night Shift (6-6)',
            'NIGHT_SHIFT_8': 'Night Shift (8-8)',
            'C_SHIFT': 'C Shift'
        };
            var cols = [];
            cols.push({
                field: 'EMPLOYEE_CODE',
                title: "Code",
                locked: true,
                width: 70
            })
            cols.push({
                field: 'FULL_NAME',
                title: "Name",
                locked: true,
                template: '<span>#:FULL_NAME#</span>',
                width: 130
            });
            for (var i = 1; i <= dayCount; i++) {
                var temp = 'D' + i;
                exportVals[temp]=i;
                cols.push({
                    field: temp,
                    title: "" + i,
                     width: 70,
                     template: '<abbr title="#:setAbbr('+temp+')#"><button type="button" style="padding: 8px 0px 7px;" class="btn btn-block #:setTemplate('+temp+')#">#:(' + temp + ' == null) ? " " :'+temp+'#</button></abbr>',
//                     template: '<span  class="#: setTemplate(' + temp + ') #">#:(' + temp + ' == null) ? " " :'+temp+'#</span>',
                });
            }
            
            
            cols.push({
                field: 'PRESENT',
                title: "Present",
                template: '<span>#:PRESENT#</span>',
                width: 100
            });
            cols.push({
                field: 'ABSENT',
                title: "Absent",
                template: '<span>#:ABSENT#</span>',
                width: 100
            });
            cols.push({
                field: 'LEAVE',
                title: "Leave",
                template: '<span>#:LEAVE#</span>',
                width: 100
            });
            cols.push({
                field: 'DAYOFF',
                title: "Dayoff",
                template: '<span>#:DAYOFF#</span>',
                width: 100
            });
            cols.push({
                field: 'HOLIDAY',
                title: "Holiday",
                template: '<span>#:HOLIDAY#</span>',
                width: 100
            });
            cols.push({
                field: 'WORK_DAYOFF',
                title: "Work Dayoff",
                template: '<span>#:WORK_DAYOFF#</span>',
                width: 100
            });
            cols.push({
                field: 'WORK_HOLIDAY',
                title: "Work Holiday",
                template: '<span>#:WORK_HOLIDAY#</span>',
                width: 100
            });
            
            cols.push({
                field: 'NIGHT_SHIFT_6',
                title: "Night Shift 6-6",
                template: '<span>#:NIGHT_SHIFT_6#</span>',
                width: 100
            });
            cols.push({
                field: 'NIGHT_SHIFT_8',
                title: "Night Shift 8-8",
                template: '<span>#:NIGHT_SHIFT_8#</span>',
                width: 100
            });
            cols.push({
                field: 'C_SHIFT',
                title: "C Shift",
                template: '<span>#:C_SHIFT#</span>',
                width: 100
            });
            
            $.each(leaveDetails, function (index, value) {
                exportVals[value.LEAVE_STRING]=value.LEAVE_ENAME;
                cols.push({
                field: value.LEAVE_STRING,
                title: value.LEAVE_ENAME,
                width: 100
            });
            });
//            console.log(exportVals);
            return cols;
        }
        
        
        
        $('#excelExport').on('click', function () {
            app.excelExport($table, exportVals, 'Department Wise Daily Attendance Report');
        });
        $('#pdfExport').on('click', function () {
            app.exportToPDF($table, exportVals, 'Department Wise Daily Attendance Report','A1');
        });





    });
})(window.jQuery, window.app);