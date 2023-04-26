(function ($, app) {
    'use strict';
    $(document).ready(function () {
        if (document.employeeId == document.selfEmployeeId) {
            app.lockField(true, [
			'employeeCode', 'telephoneNo', 'socialActivity',
			'genderId', 'extensionNo', 'emailPersonal', 'socialNetwork',
			'countryId',
			'religionId',  
			'emailOfficial', 'emergContactName', 'emergContactRelationship', 
			'MobileNo', 'emergContactAddress', 'emergContactNo',
			,
			, 'firstName', 'middleName', 'lastName', 'nameNepali', 'nepaliBirthDate', 'companyId', 'idCardNo', 'idThumbId', 'idLbrf'
                        , 'tab1'
                        , 'tab2'
                        , 'idThumbId','idCardNo','idPassportExpiry','ssfNo','idBarCode','idPassportNo','idCitizenshipIssueDate','idCitizenshipNo','idCitizenshipIssueDate','idProvidentFundNo','idPanNo',
                        , 'tab4'
                        , 'tab7'
                        , 'tab8'
                        , 'tab9'
                        , 'tab10'
                    ]);
        }
    });
})(window.jQuery, window.app);