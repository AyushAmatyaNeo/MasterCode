(function ($, app) {
    'use strict';
    $(document).ready(function () {
        $('select').select2();
        /**/
        var months = null;
        var companyList = null;
        var groupList = null;
        var selectedMonth = null;
        /**/
        var $year = $('#fiscalYearId');
        var $month = $('#monthId');
        var $company = $('#companyId');
        var $group = $('#groupId');
        var $table = $('#table');

        $month.on('change', function () {
            salarySheetChange();
        });
        $company.on('change', function () {
            salarySheetChange();
        });
        $group.on('change', function () {
            salarySheetChange();
        });

        app.setFiscalMonth($year, $month, function (yearList, monthList, currentMonth) {
            months = monthList;
        }, data.getFiscalYearMonthLink);

        (function ($companyId, link) {
            var onDataLoad = function (data) {
                companyList = data['company'];
                app.populateSelect($companyId, data['company'], 'COMPANY_ID', 'COMPANY_NAME', 'Select Company');
            };
            app.serverRequest(link, {}).then(function (response) {
                if (response.success) {
                    onDataLoad(response.data);
                }
            }, function (error) {

            });
        })($company, data.getSearchDataLink);

        (function ($groupId, link) {
            var onDataLoad = function (data) {
                groupList = data;
                app.populateSelect($groupId, groupList, 'GROUP_ID', 'GROUP_NAME', 'Select Group');
            };
            app.serverRequest(link, {}).then(function (response) {
                if (response.success) {
                    onDataLoad(response.data);
                }
            }, function (error) {

            });
        })($group, data.getGroupListLink);

        var salarySheetChange = function () {
            var monthId = $month.val();
            var companyId = $company.val();
            var groupId = $group.val();

            if (monthId === null && monthId === '') {
                return;
            }
            if (typeof $table.data('kendoGrid') === 'undefined') {
                $table.kendoGrid(kendoConfig);
            } else {
                $table.data('kendoGrid').dataSource.read();
                $table.data('kendoGrid').refresh();
            }

        };
        var columns = [
            {field: 'COMPANY_NAME', title: 'Company', width: 150, locked: true},
            {field: 'GROUP_NAME', title: 'Group', width: 150, locked: true},
            {field: 'FULL_NAME', title: 'Employee', width: 150, locked: true}
        ];
        var fields = {
            'COMPANY_NAME': {editable: false},
            'GROUP_NAME': {editable: false},
            'FULL_NAME': {editable: false},
        };

        $.each(data.ruleList, function (k, v) {
            columns.push({field: v['PAY_ID_COL'], title: v['PAY_EDESC'], width: 100});
            fields[v['PAY_ID_COL']] = {type: "number"};
        });

        var kendoConfig = {
            dataSource: {
                transport: {
                    type: "json",
                    read: {
                        url: data.pvmReadLink,
                        type: "POST",
                    },
                    update: {
                        url: data.pvmUpdateLink,
                        type: "POST",
                    },
                    parameterMap: function (options, operation) {

                        if (operation === "read") {
                            selectedMonth = $month.val();
                            var companyId = $company.val();
                            var groupId = $group.val();
                            return {
                                monthId: selectedMonth,
                                companyId: (companyId === undefined || companyId == '-1') ? null : companyId,
                                groupId: (groupId === undefined || groupId == '-1') ? null : groupId
                            };
                        }
                        if (operation !== "read" && options.models) {
                            console.log(options.models);
                            return {
                                monthId: selectedMonth,
                                models: kendo.stringify(options.models)};
                        }


                    }
                },
                batch: true,
                schema: {
                    model: {
                        id: "EMPLOYEE_ID",
                        fields: fields
                    }
                },
                pageSize: 20
            },
            pageable: true,
            height: 550,
            toolbar: ["save", "cancel"],
            columns: columns,
            editable: true
        };
    });
})(window.jQuery, window.app);