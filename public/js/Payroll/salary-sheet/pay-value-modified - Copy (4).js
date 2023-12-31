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
        var data = window.data;
        var getGroupListLink = data['getGroupListLink'] ;
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
                var acl = document.acl;
            var employeeDetail = document.employeeDetail;
            if (typeof acl !== 'undefined' && typeof employeeDetail !== 'undefined') {
                console.log(acl['CONTROL']);
                for(let i = 0; i < acl['CONTROL'].length; i++){
                    var populateValues = [];
                    $.each(acl['CONTROL_VALUES'], function (k, v) {

                        if (v.CONTROL == acl['CONTROL'][i]) {
                            populateValues.push(v.VAL);
                        }
                    });
                    
                    switch (acl['CONTROL'][i]) {
                        case 'C':
                            $companyId.val((populateValues.length<1)?employeeDetail['COMPANY_ID']:populateValues);
                            console.log((populateValues.length<1)?employeeDetail['COMPANY_ID']:populateValues);
                            $companyId.trigger('change');
                            $companyId.prop('disabled', true);
                            break;
                        
                    }
                }
            }
            };
            app.serverRequest(link, {}).then(function (response) {
                if (response.success) {
                    onDataLoad(response.data);
                }
            }, function (error) {

            });
        })($company, data.getSearchDataLink);

        // (function ($groupId, link) {
        //     var onDataLoad = function (data) {
        //         groupList = data;
        //         app.populateSelect($groupId, groupList, 'GROUP_ID', 'GROUP_NAME', 'Select Group');
        //         var acl = document.acl;
        //         console.log(acl);
        //         if(acl['CONTROL'] == 'C'){
        //             var companyWiseGroup = document.getCompanyWiseGroup;
        //             if(companyWiseGroup[0]['GROUP_ID']){
        //                 $groupId.val(companyWiseGroup[0]['GROUP_ID']);
        //             }                    
        //             document.getElementById("groupId").setAttribute("disabled", "disabled");
        //         }else{
        //             console.log('Role is not company wise');
        //         }
        //     };
        //     app.serverRequest(link, {}).then(function (response) {
        //         if (response.success) {
        //             onDataLoad(response.data);
        //         }
        //     }, function (error) {

        //     });
        // })($group, data.getGroupListLink);

        (function ($groupId, link) {
            var onDataLoad = function (data) {
                groupList = data;
                app.populateSelect($groupId, groupList, 'GROUP_ID', 'GROUP_NAME', 'Select Group');
				var acl = document.getAcl;
                console.log(acl);
                if(acl['CONTROL'] == 'C'){
					var groupListControl = [];
					
                    var companyWiseGroup = document.getCompanyWiseGroup;
                    if(companyWiseGroup[0]['GROUP_ID']){
                        $groupId.val(companyWiseGroup[0]['GROUP_ID']);
                    }     

					var totarrLength = (companyWiseGroup.length) - 1;
					if(totarrLength == 0) 
					{
						document.getElementById("groupId").setAttribute("disabled", "disabled");
					}
					
					if(totarrLength == 0) 
					{
						$.each(groupList, function (i, value) {
							if(companyWiseGroup[0]['GROUP_ID'] == value.GROUP_ID) {
								groupListControl.push({GROUP_ID: value.GROUP_ID, GROUP_NAME: value.GROUP_NAME});
							}
						});
					}
					
					if(totarrLength == 1) 
					{
						$.each(groupList, function (i, value) {
							if(companyWiseGroup[0]['GROUP_ID'] == value.GROUP_ID || companyWiseGroup[1]['GROUP_ID'] == value.GROUP_ID) {
								groupListControl.push({GROUP_ID: value.GROUP_ID, GROUP_NAME: value.GROUP_NAME});
							}
						});
					}
					
					if(totarrLength == 2) 
					{
						$.each(groupList, function (i, value) {
							if(companyWiseGroup[0]['GROUP_ID'] == value.GROUP_ID || companyWiseGroup[1]['GROUP_ID'] == value.GROUP_ID || companyWiseGroup[2]['GROUP_ID'] == value.GROUP_ID) {
								groupListControl.push({GROUP_ID: value.GROUP_ID, GROUP_NAME: value.GROUP_NAME});
							}
						});
					}
					
					if(totarrLength == 3) 
					{
						$.each(groupList, function (i, value) {
							if(companyWiseGroup[0]['GROUP_ID'] == value.GROUP_ID || companyWiseGroup[1]['GROUP_ID'] == value.GROUP_ID || companyWiseGroup[2]['GROUP_ID'] == value.GROUP_ID || companyWiseGroup[3]['GROUP_ID'] == value.GROUP_ID) {
								groupListControl.push({GROUP_ID: value.GROUP_ID, GROUP_NAME: value.GROUP_NAME});
							}
						});
					}

                    if(totarrLength == 4) 
					{
						$.each(groupList, function (i, value) {
							if(companyWiseGroup[0]['GROUP_ID'] == value.GROUP_ID || companyWiseGroup[1]['GROUP_ID'] == value.GROUP_ID || companyWiseGroup[2]['GROUP_ID'] == value.GROUP_ID || companyWiseGroup[3]['GROUP_ID'] == value.GROUP_ID) {
								groupListControl.push({GROUP_ID: value.GROUP_ID, GROUP_NAME: value.GROUP_NAME});
							}
						});
					}
					
					if(totarrLength == 5) 
					{
						$.each(groupList, function (i, value) {
							if(companyWiseGroup[0]['GROUP_ID'] == value.GROUP_ID || companyWiseGroup[1]['GROUP_ID'] == value.GROUP_ID || companyWiseGroup[2]['GROUP_ID'] == value.GROUP_ID || companyWiseGroup[3]['GROUP_ID'] == value.GROUP_ID || companyWiseGroup[4]['GROUP_ID'] == value.GROUP_ID) {
								groupListControl.push({GROUP_ID: value.GROUP_ID, GROUP_NAME: value.GROUP_NAME});
							}
						});
					}
					
					//console.log(groupListControl);
					
					app.populateSelect($groupId, groupListControl, 'GROUP_ID', 'GROUP_NAME', 'Select Group');
					
					//FOR selecting the group
					if(companyWiseGroup[0]['GROUP_ID']){
                        $groupId.val(companyWiseGroup[0]['GROUP_ID']);
                    } 
                    //document.getElementById("groupId").setAttribute("disabled", "disabled");
                }else{
                    console.log('Role is not company wise');
                }
            };
            app.serverRequest(link, {}).then(function (response) {
                if (response.success) {
                    onDataLoad(response.data);
                }
            }, function (error) {

            });
        })($group, getGroupListLink);


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