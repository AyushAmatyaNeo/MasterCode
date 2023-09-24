(function ($, app) {
    'use strict';
    $(document).ready(function () {
        $("select").select2();

        var $employeeTable = $('#employeeTable');
        var $search = $('#search');

        let $branch = $('#branchId');
        let $province= $('#province');
        let populateBranch ;
        let exportData;

        $province.on("change", function () {
            populateBranch = [];
            $.each(document.braProv, function(k,v){
                if(v == $province.val()){
                    populateBranch.push(k);
                }
            });
            $branch.val(populateBranch).change();
        });

        var actiontemplateConfig = {
            view: {
                'params': ["EMPLOYEE_ID"],
                'url': document.viewLink
            },
            update: {
                'ALLOW_UPDATE': document.acl.ALLOW_UPDATE,
                'params': ["EMPLOYEE_ID"],
                'url': document.editLink
            },
            delete: {
                'ALLOW_DELETE': document.acl.ALLOW_DELETE,
                'params': ["EMPLOYEE_ID"],
                'url': document.deleteLink
            }
        };
        app.initializeKendoGrid($employeeTable, [
            {field: "EMPLOYEE_CODE", title: "Code", locked: true, width: 70},
            {field: "IMAGE_FULL_PATH", title: "Photo", locked: true, width: 70,
                template: "<div class = 'employee-photo' " +
                    "style='background-image: url(#:IMAGE_FULL_PATH#);'></div>"
                    },
            {field: "FULL_NAME", title: "Full Name", locked: true, width: 150},
            {field: "MOBILE_NO", title: "Mobile No", locked: true, width: 100},
            {title: "Birth Date", locked: true, columns: [
                    {field: "BIRTH_DATE_AD", title: "AD", width: 80},
                    {field: "BIRTH_DATE_BS", title: "BS", width: 80}
                ]},
            {title: "Join Date", locked: true, columns: [
                    {field: "JOIN_DATE_AD", title: "AD", width: 80},
                    {field: "JOIN_DATE_BS", title: "BS", width: 80}
                ]},
            {field: "COMPANY_NAME", title: "Company", width: 150},
            {field: "BRANCH_NAME", title: "Branch", width: 150},
            {field: "DEPARTMENT_NAME", title: "Department", width: 150},
            {field: "DESIGNATION_TITLE", title: "Designation", width: 150},
            {field: "POSITION_NAME", title: "Position", width: 150},
            {field: "LEVEL_NO", title: "Level", width: 150},
            {field: "LOCATION_EDESC", title: "Location", width: 150},
            {field: "FUNCTIONAL_TYPE_EDESC", title: "Functional Type", width: 150},
            {field: "FUNCTIONAL_LEVEL_EDESC", title: "Functional Level", width: 150},
            {field: "EMPLOYEE_ID", title: "Action", width: 120, locked: true, template: app.genKendoActionTemplate(actiontemplateConfig)}
        ], null, null, null, 'Employee List');
        app.searchTable('employeeTable', ['EMPLOYEE_CODE', 'FULL_NAME', 'MOBILE_NO', 'BIRTH_DATE', 'COMPANY_NAME', 'BRANCH_NAME', 'DEPARTMENT_NAME', 'DESIGNATION_TITLE'], false);
  
        var map = {
            'EMPLOYEE_ID': 'Employee Id',
            'EMPLOYEE_CODE': 'Employee Code',
            'TITLE': 'Title',
            'FULL_NAME': 'Employee',
            'GENDER_NAME': 'Gender',
            'BIRTH_DATE_AD': 'Birth Date(AD)',
            'BIRTH_DATE_BS': 'Birth Date(BS)',
            'JOIN_DATE_AD': 'Join Date(AD)',
            'JOIN_DATE_BS': 'Join Date(BS)',
            'COUNTRY_NAME': 'Country',
            'RELIGION_NAME': 'Religion',
            'BLOOD_GROUP_CODE': 'Blood Group',
            'MOBILE_NO': 'Mobile No',
            'TELEPHONE_NO': 'Telephone No',
			'MARITAL_STATUS': 'marital status',
            'SOCIAL_ACTIVITY': 'Social Activity',
            'EXTENSION_NO': 'Extension No',
            'EMAIL_OFFICIAL': 'Official Email',
            'EMAIL_PERSONAL': 'Personal Email',
            'SOCIAL_NETWORK': 'Social Network',
            'COMPANY_NAME': 'Company',
            'BRANCH_NAME': 'Branch',
            'DEPARTMENT_NAME': 'Department',
            'DESIGNATION_TITLE': 'Designation',
            'POSITION_NAME': 'Position',
            'LEVEL_NO': 'Level',
            'SERVICE_TYPE_NAME': 'Service Type',
            'EMPLOYEE_TYPE': 'Employee Type',
            'LOCATION_EDESC': 'Location',
            'FUNCTIONAL_TYPE_EDESC': 'Functional Type',
            'FUNCTIONAL_LEVEL_NO': 'Functional Level No',
            'FUNCTIONAL_LEVEL_EDESC': 'Functional Level',
            'ADDR_PERM_HOUSE_NO': 'Permanent House No',
            'ADDR_PERM_WARD_NO': 'Permanent Ward No',
            'ADDR_PERM_STREET_ADDRESS': 'Permanent Street Address',
            'ADDR_PERM_COUNTRY_NAME': 'Permanent Country',
            'ADDR_PERM_ZONE_NAME': 'Permanent Zone',
            'ADDR_PERM_DISTRICT_NAME': 'Permanent District',
            'VDC_MUNICIPALITY_NAME_PERM': 'Permanent VDC/Municipality',
            'ADDR_TEMP_HOUSE_NO': 'Temporary House No',
            'ADDR_TEMP_WARD_NO': 'Temporary Ward No',
            'ADDR_TEMP_STREET_ADDRESS': 'Temporary Street Address',
            'ADDR_TEMP_COUNTRY_NAME': 'Temporary Country',
            'ADDR_TEMP_ZONE_NAME': 'Temporary Zone',
            'ADDR_TEMP_DISTRICT_NAME': 'Temporary District',
            'VDC_MUNICIPALITY_NAME_TEMP': 'Temporary VDC/Municipality',
            'EMRG_CONTACT_NAME': 'Emergency Contact Name',
            'EMERG_CONTACT_RELATIONSHIP': 'Emergency Contact Relationship',
            'EMERG_CONTACT_ADDRESS': 'Emergency Contact Address',
            'EMERG_CONTACT_NO': 'Emergency Contact No',
            'ID_ACCOUNT_NO': 'Account No',
            'BANK_ACCOUNT': 'BANK',
            'ID_THUMB_ID': 'THUMB ID',
            'ID_PROVIDENT_FUND_NO':'Provident Fund',
            'ID_PAN_NO':'Pan'
        }; 

        var exportColumnParameters = [];
        for(var key in map){
            exportColumnParameters.push({'VALUES' : key, 'COLUMNS' : map[key]});
        }

        var $exparams = $('#exparamsId');
        app.populateSelect($exparams, exportColumnParameters, 'VALUES', 'COLUMNS');
 
        $('#excelExport').on('click', function () {
            var fc = app.filterExportColumns($("#exparamsId").val(), map);
            app.excelExport($employeeTable, fc, 'Employee List.xlsx');
        });
        $('#pdfExport').on('click', function () {
            var fc = app.filterExportColumns($("#exparamsId").val(), map);
            app.exportToPDF($employeeTable, fc, 'Employee List.pdf');
        });

        $('#excelExportWithImage').on('click', function () {
            let searchData = document.searchManager.getSearchValues();
            var data = {
                searchData : searchData,
//                exportData : exportData,
                map : map,
            };
            app.serverRequest(document.excelExportWithImageLink, data).then(function(response){
                window.open(document.excelExportWithImageDownload);
            });
        });

        $search.on('click', function () {
            var data = document.searchManager.getSearchValues();
            var imagePath = document.basePath + '/uploads/';
            app.serverRequest(document.pullEmployeeListForEmployeeTableLink, data).then(function (response) {
                if (response.success) {
                    exportData = response.data;
                    for ( var x in response.data) {
                    response.data[x]['IMAGE_FULL_PATH'] = imagePath + response.data[x]['FILE_PATH'];
                    }
                    app.renderKendoGrid($employeeTable, response.data);
                } else {
                    app.showMessage(response.error, 'error');
                }
            }, function (error) {
                app.showMessage(error, 'error');
            }); 
        });
        
    }); 
})(window.jQuery, window.app);