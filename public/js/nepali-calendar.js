window.nepaliCalendar = (function ($) {
    "use strict";
    console.log('Calender Type', document.calendarType);
    var bsadMap = {
        "N": {"2074":
                    {
                        '01': {from: "2017-04-14", to: "2017-05-14"},
                        '02': {from: "2017-05-15", to: "2017-06-14"},
                        '03': {from: "2017-06-15", to: "2017-07-15"},
                        '04': {from: "2017-07-16", to: "2017-08-16"},
                        '05': {from: "2017-08-17", to: "2017-09-16"},
                        '06': {from: "2017-09-17", to: "2017-10-17"},
                        '07': {from: "2017-10-18", to: "2017-11-16"},
                        '08': {from: "2017-11-17", to: "2017-12-15"},
                        '09': {from: "2017-12-16", to: "2018-01-14"},
                        '10': {from: "2018-01-15", to: "2018-02-12"},
                        '11': {from: "2018-02-13", to: "2018-03-14"},
                        '12': {from: "2018-03-15", to: "2018-04-13"}
                    },
            "2075":
                    {
                        '01': {from: "2018-04-14", to: "2018-05-14"},
                        '02': {from: "2018-05-15", to: "2018-06-14"},
                        '03': {from: "2018-06-15", to: "2018-07-16"},
                        '04': {from: "2018-07-17", to: "2018-08-16"},
                        '05': {from: "2018-08-17", to: "2018-09-16"},
                        '06': {from: "2018-09-17", to: "2018-10-17"},
                        '07': {from: "2018-10-18", to: "2018-11-16"},
                        '08': {from: "2018-11-17", to: "2018-12-15"},
                        '09': {from: "2018-12-16", to: "2019-01-14"},
                        '10': {from: "2019-01-15", to: "2019-02-12"},
                        '11': {from: "2019-02-13", to: "2019-03-14"},
                        '12': {from: "2019-03-15", to: "2019-04-13"}
                    },
            "2076": {
                '01': {from: "2019-04-14", to: "2019-05-14"},
                '02': {from: "2019-05-15", to: "2019-06-15"},
                '03': {from: "2019-06-16", to: "2019-07-16"},
                '04': {from: "2019-07-17", to: "2019-08-17"},
                '05': {from: "2019-08-18", to: "2019-09-17"},
                '06': {from: "2019-09-18", to: "2019-10-17"},
                '07': {from: "2019-10-18", to: "2019-11-16"},
                '08': {from: "2019-11-17", to: "2019-12-16"},
                '09': {from: "2019-12-17", to: "2020-01-14"},
                '10': {from: "2020-01-15", to: "2020-02-12"},
                '11': {from: "2020-02-13", to: "2020-03-13"},
                '12': {from: "2020-03-14", to: "2020-04-12"}
            },
            "2077": {
                '01': {from: "2020-04-13", to: "2020-05-13"},
                '02': {from: "2020-05-14", to: "2020-06-14"},
                '03': {from: "2020-06-15", to: "2020-07-15"},
                '04': {from: "2020-07-16", to: "2020-08-16"},
                '05': {from: "2020-08-17", to: "2020-09-16"},
                '06': {from: "2020-09-17", to: "2020-10-16"},
                '07': {from: "2020-10-17", to: "2020-11-15"},
                '08': {from: "2020-11-16", to: "2020-12-15"},
                '09': {from: "2020-12-16", to: "2021-01-13"},
                '10': {from: "2021-01-14", to: "2021-02-12"},
                '11': {from: "2021-02-13", to: "2021-03-13"},
                '12': {from: "2021-03-14", to: "2021-04-13"}
            },
			"2078": {
			'01': {from: "2021-04-14", to: "2021-05-14"},
			'02': {from: "2021-05-15", to: "2021-06-14"},
			'03': {from: "2021-06-15", to: "2021-07-15"},
			'04': {from: "2021-07-16", to: "2021-08-16"},
			'05': {from: "2021-08-17", to: "2021-09-16"},
			'06': {from: "2021-09-17", to: "2021-10-17"},
			'07': {from: "2021-10-18", to: "2021-11-16"},
			'08': {from: "2021-11-17", to: "2021-12-15"},
			'09': {from: "2021-12-16", to: "2022-01-14"},
			'10': {from: "2022-01-15", to: "2022-02-12"},
			'11': {from: "2022-02-13", to: "2022-03-14"},
			'12': {from: "2022-03-15", to: "2022-04-13"}
			},
			"2079": {
			'01': {from: "2022-04-14", to: "2022-05-14"},
			'02': {from: "2022-05-15", to: "2022-06-14"},
			'03': {from: "2022-06-15", to: "2022-07-16"},
			'04': {from: "2022-07-17", to: "2022-08-16"},
			'05': {from: "2022-08-17", to: "2022-09-16"},
			'06': {from: "2022-09-17", to: "2022-10-17"},
			'07': {from: "2022-10-18", to: "2022-11-16"},
			'08': {from: "2022-11-17", to: "2022-12-15"},
			'09': {from: "2022-12-16", to: "2023-01-14"},
			'10': {from: "2023-01-15", to: "2023-02-12"},
			'11': {from: "2023-02-13", to: "2023-03-14"},
			'12': {from: "2023-03-15", to: "2023-04-13"}
			}
        },
        "E": {
            "2019":
                    {
                        '01': {from: "2019-01-01", to: "2019-01-31"},
                        '02': {from: "2019-02-01", to: "2019-02-28"},
                        '03': {from: "2019-03-01", to: "2019-03-31"},
                        '04': {from: "2019-04-01", to: "2019-04-30"},
                        '05': {from: "2019-05-01", to: "2019-05-31"},
                        '06': {from: "2019-06-01", to: "2019-06-30"},
                        '07': {from: "2019-07-01", to: "2019-07-31"},
                        '08': {from: "2019-08-01", to: "2019-08-31"},
                        '09': {from: "2019-09-01", to: "2019-09-30"},
                        '10': {from: "2019-10-01", to: "2019-10-31"},
                        '11': {from: "2019-11-01", to: "2019-11-30"},
                        '12': {from: "2019-12-01", to: "2019-12-31"}
                    },
            "2020":
                {
                    '01': {from: "2020-01-01", to: "2020-01-31"},
                    '02': {from: "2020-02-01", to: "2020-02-29"},
                    '03': {from: "2020-03-01", to: "2020-03-31"},
                    '04': {from: "2020-04-01", to: "2020-04-30"},
                    '05': {from: "2020-05-01", to: "2020-05-31"},
                    '06': {from: "2020-06-01", to: "2020-06-30"},
                    '07': {from: "2020-07-01", to: "2020-07-31"},
                    '08': {from: "2020-08-01", to: "2020-08-31"},
                    '09': {from: "2020-09-01", to: "2020-09-30"},
                    '10': {from: "2020-10-01", to: "2020-10-31"},
                    '11': {from: "2020-11-01", to: "2020-11-30"},
                    '12': {from: "2020-12-01", to: "2020-12-31"}
                }



        }
    };
    var weekdaytemplate = {
        1: [],
        2: [],
        3: [],
        4: [],
        5: [],
        6: [],
        7: []
    };

    var weekday = null;
    var months = {
        "N": {'01': 'Baishakh',
            '02': 'Jestha',
            '03': 'Asar',
            '04': 'Shrawan',
            '05': 'Bhadra',
            '06': 'Aswin',
            '07': 'Kartik',
            '08': 'Mansir',
            '09': 'Poush',
            '10': 'Magh',
            '11': 'Falgun',
            '12': 'Chaitra', },
        "E": {'01': 'January',
            '02': 'February',
            '03': 'March',
            '04': 'April',
            '05': 'May',
            '06': 'June',
            '07': 'July',
            '08': 'August',
            '09': 'September',
            '10': 'October',
            '11': 'November',
            '12': 'December', }

    }
    var formatDate = function (date) {
        var d = new Date(date),
                month = '' + (d.getMonth() + 1),
                day = '' + d.getDate(),
                year = d.getFullYear();
        if (month.length < 2)
            month = '0' + month;
        if (day.length < 2)
            day = '0' + day;
        return [year, month, day].join('-');
    }

    var getYears = function () {
        return (document.calendarType == 'E') ? Object.keys(bsadMap.E) : Object.keys(bsadMap.N);
    };
    var getMonths = function () {
        return (document.calendarType == 'E') ? months.E : months.N;
    };

    var getCalendar = function (year, month) {
        var bsadMapType = (document.calendarType == 'E') ? bsadMap.E : bsadMap.N;
        var weekday = $.extend(true, {}, weekdaytemplate);
        var monthData = bsadMapType[year][month];
        var fromDate = new Date(monthData['from']);
        var toDate = new Date(monthData['to']);
        var timeDiff = Math.abs(toDate.getTime() - fromDate.getTime()) + 3600;
        var diffDays = Math.ceil(timeDiff / (1000 * 3600 * 24));
        var f = false;
        var j = 0;
        var loopDate = new Date();
        for (var i = 0; i < 50; i++) {
            var index = i % 7;
            if (!f) {
                if (index === fromDate.getDay()) {
                    f = true;
                } else {
                    weekday[index + 1].push({});
                }
            }

            if (f) {
                if (j == diffDays) {
                    break;
                }
                loopDate.setTime(fromDate.getTime() + j * 86400000);
                weekday[index + 1].push({date: formatDate(loopDate), day: j + 1});
                j++;
            }

        }
        return weekday;
    };
    return {
        getCalendar: getCalendar,
        bsadMap: bsadMap,
        getYears: getYears,
        getMonths: getMonths
    };
})(window.jQuery);
(function ($, app, nc) {
    'use strict';
    $(document).ready(function () {
        var $nepaliCalendar = $('#nepaliCalendar');
        var $content = $('#nc-content');
        var $sunday = $content.find('#nc-sunday');
        var $monday = $content.find('#nc-monday');
        var $tuesday = $content.find('#nc-tuesday');
        var $wednesday = $content.find('#nc-wednesday');
        var $thrusday = $content.find('#nc-thrusday');
        var $friday = $content.find('#nc-friday');
        var $saturday = $content.find('#nc-saturday');

        var template = `
        <div class='nc-date'>
            <table class="table table-condensed" style="inherit">
                <tr>
                    <td colspan="2" class="day" style="font-size:10px;">
                    <td>
                </tr>
                <tr>
                    <td colspan="2" class="status" style="font-size:0.6em;">
                    <td>
                </tr>
                <tr>
                    <td class="in-time" style="font-size:10px;"></td>
                    <td class="out-time" style="font-size:10px;"></td>
                </tr>
            </table>
        </div>`;


        var $year = $('#nc-year');
        var $month = $('#nc-month');

        var years = nc.getYears();
        var months = nc.getMonths();

        $month.on('change', function () {
            loadCalendar($year.val(), $month.val());
        });
        
        $('#cal_emp').on('change', function () {
            let selectedEmployeeId=$(this).val();
            let selectedProfileVal=document.empProfile[selectedEmployeeId];
            if (selectedProfileVal != null) {
                $('#employeeImageCalendar').attr("src", document.basePath + '/uploads/' + selectedProfileVal);
            }else{
                $('#employeeImageCalendar').attr("src", document.basePath + '/img/nobody_m.original.jpg');
            }
            loadCalendar($year.val(), $month.val());
        });

        var serverDate = (document.calendarType == 'E') ? 'getServerDateForCalender' : 'getServerDateBS';

        app.pullDataById(document.restfulUrl, {action: serverDate}).then(function (response) {
            var currentDate = (document.calendarType == 'E') ? response.data.serverDate : response.data.CURRENT_DATE;
            var currentYear = currentDate.split('-')[0];
            var currentMonth = currentDate.split('-')[1];
            $year.html('');
            for (var i in years) {
                if (years[i] == currentYear) {
                    $year.append($("<option selected='selected'></option>").val(years[i]).text(years[i]));
                } else {
                    $year.append($("<option></option>").val(years[i]).text(years[i]));
                }
            }
            $month.html('');
            for (var i in months) {
                if (i == currentMonth) {
                    $month.append($("<option selected='selected'></option>").val(i).text(months[i]));
                } else {
                    $month.append($("<option></option>").val(i).text(months[i]));
                }
            }
            loadCalendar(currentYear, currentMonth);
        });

        var loadCalendar = function (year, month) {

            var monthData = nc.getCalendar(year, month);
            $sunday.html('');
            $monday.html('');
            $tuesday.html('');
            $wednesday.html('');
            $thrusday.html('');
            $friday.html('');
            $saturday.html('');

            var sundayData = monthData[1];
            for (var i = 0; i < sundayData.length; i++) {
                var $template = $(template);
                $template.attr('date', sundayData[i].date || " - ");
                $template.find('.day').append(sundayData[i].day || " - ");
                $sunday.append($template);
            }
            var mondayData = monthData[2];
            for (var i = 0; i < mondayData.length; i++) {
                var $template = $(template);
                $template.attr('date', mondayData[i].date || " - ");
                $template.find('.day').append(mondayData[i].day || " - ");
                $monday.append($template);
            }
            var tuesdayData = monthData[3];
            for (var i = 0; i < tuesdayData.length; i++) {
                var $template = $(template);
                $template.attr('date', tuesdayData[i].date || " - ");
                $template.find('.day').append(tuesdayData[i].day || " - ");
                $tuesday.append($template);
            }
            var wednesdayData = monthData[4];
            for (var i = 0; i < wednesdayData.length; i++) {
                var $template = $(template);
                $template.attr('date', wednesdayData[i].date || " - ");
                $template.find('.day').append(wednesdayData[i].day || " - ");
                $wednesday.append($template);
            }
            var thrusdayData = monthData[5];
            for (var i = 0; i < thrusdayData.length; i++) {
                var $template = $(template);
                $template.attr('date', thrusdayData[i].date || " - ");
                $template.find('.day').append(thrusdayData[i].day || " - ");
                $thrusday.append($template);
            }
            var fridayData = monthData[6];
            for (var i = 0; i < fridayData.length; i++) {
                var $template = $(template);
                $template.attr('date', fridayData[i].date || " - ");
                $template.find('.day').append(fridayData[i].day || " - ");
                $friday.append($template);
            }
            var saturdayData = monthData[7];
            for (var i = 0; i < saturdayData.length; i++) {
                var $template = $(template);
                $template.attr('date', saturdayData[i].date || " - ");
                $template.find('.day').append(saturdayData[i].day || " - ");
                $saturday.append($template);
            }
            var m = (document.calendarType == 'E') ? nc.bsadMap['E'][year][month] : nc.bsadMap['N'][year][month];
            
            var selEmp=$('#cal_emp').val();
//            console.log(selEmp);
            
            
            
            
            app.pullDataById(document.calendarJsonFeedUrl, {'startDate': m.from, 'endDate': m.to,'selEmp':selEmp}).then(function (response) {
                $.each(response, function (key, value) {
                    var $date = $nepaliCalendar.find('[date=' + value.ATTENDANCE_DT + ']');
                    $date.find('.in-time').html(value.IN_TIME);
                    $date.find('.out-time').html(value.OUT_TIME);
                    $date.find('.status').html(value.ATTENDANCE_STATUS);
                    if (value.OVERALL_STATUS == 'DO' || value.OVERALL_STATUS == 'WD') {
                        $date.css('background-color', '#ADFF2F');
                        $date.children().css('background-color', '#ADFF2F');
                    } else if (value.OVERALL_STATUS == 'HD' || value.OVERALL_STATUS == 'WH') {
                        $date.css('background-color', '#eaea2a');
                        $date.children().css('background-color', '#eaea2a');
                    } else if (value.OVERALL_STATUS == 'LV' || value.OVERALL_STATUS == 'LP') {
                        $date.css('background-color', '#a7aeaf');
                        $date.children().css('background-color', '#a7aeaf');
                    } else if (value.OVERALL_STATUS == 'TN' || value.OVERALL_STATUS == 'TP') {
                        $date.css('background-color', '#39c7b8');
                        $date.children().css('background-color', '#39c7b8');
                    } else if (value.OVERALL_STATUS == 'TV' || value.OVERALL_STATUS == 'VP') {
                        $date.css('background-color', '#e89c0a');
                        $date.children().css('background-color', '#e89c0a');
                    } else if (value.OVERALL_STATUS == 'AB') {
                        $date.css('background-color', '#cc0000');
                        $date.children().css('background-color', '#cc0000');
                    } else if (value.OVERALL_STATUS == 'EC') {
                        $date.css('background-color', '#d14ef5');
                        $date.children().css('background-color', '#d14ef5');
                    }
                });
            }, function (error) {

            });
        };
    });
})(window.jQuery, window.app, window.nepaliCalendar);