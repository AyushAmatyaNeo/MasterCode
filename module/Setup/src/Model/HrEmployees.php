<?php
namespace Setup\Model;

use Application\Model\Model;
use function Complex\sec;

class HrEmployees extends Model {

    public $employeeId;
    public $companyId;
    public $employeeCode;
    public $firstName;
    public $middleName;
    public $lastName;
    public $nameNepali;
    public $genderId;
    public $birthDate;
    public $bloodGroupId;
    public $religionId;
    public $socialActivity;
    public $telephoneNo;
    public $mobileNo;
    public $extensionNo;
    public $emailOfficial;
    public $emailPersonal;
    public $socialNetwork;
    public $emergContactName;
    public $emergContactNo;
    public $emergContactAddress;
    public $emergContactRelationship;
    public $addrPermHouseNo;
    public $addrPermWardNo;
    public $addrPermStreetAddress;
    public $addrPermCountryId;
    public $addrPermVdcMunicipalityId;
    public $addrTempHouseNo;
    public $addrTempWardNo;
    public $addrTempStreetAddress;
    public $addrTempCountryId;
    public $addrTempVdcMunicipalityId;
    public $famFatherName;
    public $famFatherOccupation;
    public $famMotherName;
    public $famMotherOccupation;
    public $famGrandFatherName;
    public $famGrandMotherName;
    public $maritualStatus;
    public $famSpouseName;
    public $famSpouseOccupation;
    public $famSpouseBirthDate;
    public $famSpouseWeddingAnniversary;
    public $idCardNo;
    public $idLbrf;
    public $idBarCode;
    public $idProvidentFundNo;
    public $idDrivingLicenseNo;
    public $idDrivingLicenseType;
    public $idDrivingLicenseExpiry;
    public $idThumbId;
    public $idPanNo;
    public $idAccCode;
    public $idAccountId;
    public $idRetirementNo;
    public $idCitizenshipNo;
    public $idCitizenshipIssueDate;
    public $idCitizenshipIssuePlace;
    public $idPassportNo;
    public $idPassportExpiry;
    public $joinDate;
    public $salary;
    public $salaryPf;
    public $remarks;
    public $status;
    public $createdDt;
    public $serviceEventTypeId;
    public $serviceTypeId;
    public $positionId;
    public $designationId;
    public $departmentId;
    public $branchId;
    public $appServiceEventTypeId;
    public $appServiceTypeId;
    public $appPositionId;
    public $appDesignationId;
    public $appDepartmentId;
    public $appBranchId;
    public $countryId;
    public $profilePictureId;
    public $retiredFlag;
    public $employeeType;
    public $createdBy;
    public $modifiedBy;
    public $modifiedDt;
    public $fullName;
    public $isHR;
    public $addrTempZoneId;
    public $addrTempDistrictId;
    public $addrPermZoneId;
    public $addrPermDistrictId;
    public $locationId;
    public $functionalTypeId;
    public $functionalLevelId;
    public $groupId;
    public $deletedDate;
    public $deletedBy;
    public $empowerCompanyCode;
    public $empowerBranchCode;
    public $abroadAddress;
    public $addrPermProvinceId;
    public $addrTempProvinceId;
    public $payEmpType;
    public $wohFlag;
    public $overtimeEligible;
    public $allowance;
    public $bankId;
    public $gratuityDate;
    public $permanentDate;
    public $disabledFlag;
	public $taxBase;
    public $ssfNo;

    
    const TABLE_NAME = "HRIS_EMPLOYEES";
    const EMPLOYEE_ID = "EMPLOYEE_ID";
    const COMPANY_ID = "COMPANY_ID";
    const EMPLOYEE_CODE = "EMPLOYEE_CODE";
    const FIRST_NAME = 'FIRST_NAME';
    const MIDDLE_NAME = 'MIDDLE_NAME';
    const LAST_NAME = 'LAST_NAME';
    const NAME_NEPALI = 'NAME_NEPALI';
    const GENDER_ID = 'GENDER_ID';
    const BIRTH_DATE = "BIRTH_DATE";
    const BLOOD_GROUP_ID = "BLOOD_GROUP_ID";
    const RELIGION_ID = 'RELIGION_ID';
    const SOCIAL_ACTIVITY = 'SOCIAL_ACTIVITY';
    const TELEPHONE_NO = 'TELEPHONE_NO';
    const MOBILE_NO = 'MOBILE_NO';
    const EXTENSION_NO = 'EXTENSION_NO';
    const EMAIL_OFFICIAL = "EMAIL_OFFICIAL";
    const EMAIL_PERSONAL = "EMAIL_PERSONAL";
    const SOCIAL_NETWORK = "SOCIAL_NETWORK";
    const EMRG_CONTACT_NAME = "EMRG_CONTACT_NAME";
    const EMERG_CONTACT_NO = "EMERG_CONTACT_NO";
    const EMERG_CONTACT_ADDRESS = "EMERG_CONTACT_ADDRESS";
    const EMERG_CONTACT_RELATIONSHIP = "EMERG_CONTACT_RELATIONSHIP";
    const ADDR_PERM_HOUSE_NO = 'ADDR_PERM_HOUSE_NO';
    const ADDR_PERM_WARD_NO = 'ADDR_PERM_WARD_NO';
    const ADDR_PERM_STREET_ADDRESS = "ADDR_PERM_STREET_ADDRESS";
    const ADDR_PERM_VDC_MUNICIPALITY_ID = "ADDR_PERM_VDC_MUNICIPALITY_ID";
    const ADDR_TEMP_HOUSE_NO = "ADDR_TEMP_HOUSE_NO";
    const ADDR_TEMP_WARD_NO = 'ADDR_TEMP_WARD_NO';
    const ADDR_TEMP_STREET_ADDRESS = "ADDR_TEMP_STREET_ADDRESS";
    const ADDR_TEMP_VDC_MUNICIPALITY_ID = "ADDR_TEMP_VDC_MUNICIPALITY_ID";
    const FAM_FATHER_NAME = "FAM_FATHER_NAME";
    const FAM_FATHER_OCCUPATION = "FAM_FATHER_OCCUPATION";
    const FAM_MOTHER_NAME = "FAM_MOTHER_NAME";
    const FAM_MOTHER_OCCUPATION = "FAM_MOTHER_OCCUPATION";
    const FAM_GRAND_FATHER_NAME = "FAM_GRAND_FATHER_NAME";
    const FAM_GRAND_MOTHER_NAME = "FAM_GRAND_MOTHER_NAME";
    const MARITAL_STATUS = "MARITAL_STATUS";
    const FAM_SPOUSE_NAME = "FAM_SPOUSE_NAME";
    const FAM_SPOUSE_OCCUPATION = "FAM_SPOUSE_OCCUPATION";
    const FAM_SPOUSE_BIRTH_DATE = 'FAM_SPOUSE_BIRTH_DATE';
    const FAM_SPOUSE_WEDDING_ANNIVERSARY = "FAM_SPOUSE_WEDDING_ANNIVERSARY";
    const ID_CARD_NO = "ID_CARD_NO";
    const ID_LBRF = "ID_LBRF";
    const ID_BAR_CODE = "ID_BAR_CODE";
    const ID_PROVIDENT_FUND_NO = "ID_PROVIDENT_FUND_NO";
    const ID_DRIVING_LICENCE_NO = "ID_DRIVING_LICENCE_NO";
    const ID_DRIVING_LICENCE_TYPE = "ID_DRIVING_LICENCE_TYPE";
    const ID_DRIVING_LICENCE_EXPIRY = "ID_DRIVING_LICENCE_EXPIRY";
    const ID_THUMB_ID = "ID_THUMB_ID";
    const ID_PAN_NO = "ID_PAN_NO";
    const ID_ACCOUNT_NO = "ID_ACCOUNT_NO";
    const ID_ACC_CODE = "ID_ACC_CODE";
    const ID_RETIREMENT_NO = "ID_RETIREMENT_NO";
    const ID_CITIZENSHIP_NO = "ID_CITIZENSHIP_NO";
    const ID_CITIZENSHIP_ISSUE_DATE = "ID_CITIZENSHIP_ISSUE_DATE";
    const ID_CITIZENSHIP_ISSUE_PLACE = "ID_CITIZENSHIP_ISSUE_PLACE";
    const ID_PASSPORT_NO = "ID_PASSPORT_NO";
    const ID_PASSPORT_EXPIRY = "ID_PASSPORT_EXPIRY";
    const JOIN_DATE = "JOIN_DATE";
    const SALARY = "SALARY";
    const SALARY_PF = "SALARY_PF";
    const REMARKS = "REMARKS";
    const STATUS = "STATUS";
    const SERVICE_EVENT_TYPE_ID = "SERVICE_EVENT_TYPE_ID";
    const SERVICE_TYPE_ID = "SERVICE_TYPE_ID";
    const POSITION_ID = "POSITION_ID";
    const DESIGNATION_ID = "DESIGNATION_ID";
    const DEPARTMENT_ID = "DEPARTMENT_ID";
    const BRANCH_ID = "BRANCH_ID";
    const APP_SERVICE_EVENT_TYPE_ID = "APP_SERVICE_EVENT_TYPE_ID";
    const APP_SERVICE_TYPE_ID = "APP_SERVICE_TYPE_ID";
    const APP_POSITION_ID = "APP_POSITION_ID";
    const APP_DESIGNATION_ID = "APP_DESIGNATION_ID";
    const APP_DEPARTMENT_ID = "APP_DEPARTMENT_ID";
    const APP_BRANCH_ID = "APP_BRANCH_ID";
    const CREATED_DT = "CREATED_DT";
    const COUNTRY_ID = "COUNTRY_ID";
    const ADDR_PERM_COUNTRY_ID = "ADDR_PERM_COUNTRY_ID";
    const ADDR_TEMP_COUNTRY_ID = "ADDR_TEMP_COUNTRY_ID";
    const PROFILE_PICTURE_ID = "PROFILE_PICTURE_ID";
    const RETIRED_FLAG = "RETIRED_FLAG";
    const EMPLOYEE_TYPE = "EMPLOYEE_TYPE";
    const CREATED_BY = "CREATED_BY";
    const MODIFIED_BY = "MODIFIED_BY";
    const MODIFIED_DT = "MODIFIED_DT";
    const FULL_NAME = "FULL_NAME";
    const IS_HR = "IS_HR";
    const ADDR_PERM_ZONE_ID = "ADDR_PERM_ZONE_ID";
    const ADDR_PERM_DISTRICT_ID = "ADDR_PERM_DISTRICT_ID";
    const ADDR_TEMP_ZONE_ID = "ADDR_TEMP_ZONE_ID";
    const ADDR_TEMP_DISTRICT_ID = "ADDR_TEMP_DISTRICT_ID";
    const LOCATION_ID = "LOCATION_ID";
    const FUNCTIONAL_TYPE_ID = "FUNCTIONAL_TYPE_ID";
    const FUNCTIONAL_LEVEL_ID = "FUNCTIONAL_LEVEL_ID";
    const GROUP_ID = "GROUP_ID";
    const DELETED_DATE = "DELETED_DATE";
    const DELETED_BY = "DELETED_BY";
    const EMPOWER_COMPANY_CODE = "EMPOWER_COMPANY_CODE";
    const EMPOWER_BRANCH_CODE = "EMPOWER_BRANCH_CODE";
    const ABROAD_ADDRESS = "ABROAD_ADDRESS";
    const ADDR_PERM_PROVINCE_ID = "ADDR_PERM_PROVINCE_ID";
    const ADDR_TEMP_PROVINCE_ID = "ADDR_TEMP_PROVINCE_ID";
    const PAY_EMP_TYPE = "PAY_EMP_TYPE";
    const WOH_FLAG = "WOH_FLAG";
    const OVERTIME_ELIGIBLE = "OVERTIME_ELIGIBLE";
    const ALLOWANCE = "ALLOWANCE";
    const BANK_ID = "BANK_ID";
    const PERMANENT_DATE = "PERMANENT_DATE";
    const GRATUITY_DATE = "GRATUITY_DATE";
    const DISABLED_FLAG = "DISABLED_FLAG";
	const TAX_BASE = "TAX_BASE";
    const SSF_NO="SSF_NO";

    
    public $mappings = [
        'employeeId' => self::EMPLOYEE_ID,
        'companyId' => self::COMPANY_ID,
        'employeeCode' => self::EMPLOYEE_CODE,
        'firstName' => self::FIRST_NAME,
        'middleName' => self::MIDDLE_NAME,
        'lastName' => self::LAST_NAME,
        'nameNepali' => self::NAME_NEPALI,
        'genderId' => self::GENDER_ID,
        'birthDate' => self::BIRTH_DATE,
        'bloodGroupId' => self::BLOOD_GROUP_ID,
        'religionId' => self::RELIGION_ID,
        'socialActivity' => self::SOCIAL_ACTIVITY,
        'telephoneNo' => self::TELEPHONE_NO,
        'mobileNo' => self::MOBILE_NO,
        'extensionNo' => self::EXTENSION_NO,
        'emailOfficial' => self::EMAIL_OFFICIAL,
        'emailPersonal' => self::EMAIL_PERSONAL,
        'socialNetwork' => self::SOCIAL_NETWORK,
        'emergContactName' => self::EMRG_CONTACT_NAME,
        'emergContactNo' => self::EMERG_CONTACT_NO,
        'emergContactAddress' => self::EMERG_CONTACT_ADDRESS,
        'emergContactRelationship' => self::EMERG_CONTACT_RELATIONSHIP,
        'addrPermHouseNo' => self::ADDR_PERM_HOUSE_NO,
        'addrPermWardNo' => self::ADDR_PERM_WARD_NO,
        'addrPermStreetAddress' => self::ADDR_PERM_STREET_ADDRESS,
        'addrPermVdcMunicipalityId' => self::ADDR_PERM_VDC_MUNICIPALITY_ID,
        'addrTempHouseNo' => self::ADDR_TEMP_HOUSE_NO,
        'addrTempWardNo' => self::ADDR_TEMP_WARD_NO,
        'addrTempStreetAddress' => self::ADDR_TEMP_STREET_ADDRESS,
        'addrTempVdcMunicipalityId' => self::ADDR_TEMP_VDC_MUNICIPALITY_ID,
        'famFatherName' => self::FAM_FATHER_NAME,
        'famFatherOccupation' => self::FAM_FATHER_OCCUPATION,
        'famMotherName' => self::FAM_MOTHER_NAME,
        'famMotherOccupation' => self::FAM_MOTHER_OCCUPATION,
        'famGrandFatherName' => self::FAM_GRAND_FATHER_NAME,
        'famGrandMotherName' => self::FAM_GRAND_MOTHER_NAME,
        'maritualStatus' => self::MARITAL_STATUS,
        'famSpouseName' => self::FAM_SPOUSE_NAME,
        'famSpouseOccupation' => self::FAM_SPOUSE_OCCUPATION,
        'famSpouseBirthDate' => self::FAM_SPOUSE_BIRTH_DATE,
        'famSpouseWeddingAnniversary' => self::FAM_SPOUSE_WEDDING_ANNIVERSARY,
        'idCardNo' => self::ID_CARD_NO,
        'idLbrf' => self::ID_LBRF,
        'idBarCode' => self::ID_BAR_CODE,
        'idProvidentFundNo' => self::ID_PROVIDENT_FUND_NO,
        'idDrivingLicenseNo' => self::ID_DRIVING_LICENCE_NO,
        'idDrivingLicenseType' => self::ID_DRIVING_LICENCE_TYPE,
        'idDrivingLicenseExpiry' => self::ID_DRIVING_LICENCE_EXPIRY,
        'idThumbId' => self::ID_THUMB_ID,
        'idPanNo' => self::ID_PAN_NO,
        'idAccountId' => self::ID_ACCOUNT_NO,
        'idAccCode' => self::ID_ACC_CODE,
        'idRetirementNo' => self::ID_RETIREMENT_NO,
        'idCitizenshipNo' => self::ID_CITIZENSHIP_NO,
        'idCitizenshipIssueDate' => self::ID_CITIZENSHIP_ISSUE_DATE,
        'idCitizenshipIssuePlace' => self::ID_CITIZENSHIP_ISSUE_PLACE,
        'idPassportNo' => self::ID_PASSPORT_NO,
        'idPassportExpiry' => self::ID_PASSPORT_EXPIRY,
        'joinDate' => self::JOIN_DATE,
        'salary' => self::SALARY,
        'salaryPf' => self::SALARY_PF,
        'remarks' => self::REMARKS,
        'status' => self::STATUS,
        'serviceEventTypeId' => self::SERVICE_EVENT_TYPE_ID,
        'serviceTypeId' => self::SERVICE_TYPE_ID,
        'positionId' => self::POSITION_ID,
        'designationId' => self::DESIGNATION_ID,
        'departmentId' => self::DEPARTMENT_ID,
        'branchId' => self::BRANCH_ID,
        'appServiceEventTypeId' => self::APP_SERVICE_EVENT_TYPE_ID,
        'appServiceTypeId' => self::APP_SERVICE_TYPE_ID,
        'appPositionId' => self::APP_POSITION_ID,
        'appDesignationId' => self::APP_DESIGNATION_ID,
        'appDepartmentId' => self::APP_DEPARTMENT_ID,
        'appBranchId' => self::APP_BRANCH_ID,
        'createdDt' => self::CREATED_DT,
        'countryId' => self::COUNTRY_ID,
        'addrPermCountryId' => self::ADDR_PERM_COUNTRY_ID,
        'addrTempCountryId' => self::ADDR_TEMP_COUNTRY_ID,
        'profilePictureId' => self::PROFILE_PICTURE_ID,
        'retiredFlag' => self::RETIRED_FLAG,
        'employeeType' => self::EMPLOYEE_TYPE,
        'createdBy' => self::CREATED_BY,
        'modifiedBy' => self::MODIFIED_BY,
        'modifiedDt' => self::MODIFIED_DT,
        'fullName' => self::FULL_NAME,
        'isHR' => self::IS_HR,
        'addrTempZoneId' => self::ADDR_TEMP_ZONE_ID,
        'addrTempDistrictId' => self::ADDR_TEMP_DISTRICT_ID,
        'addrPermZoneId' => self::ADDR_PERM_ZONE_ID,
        'addrPermDistrictId' => self::ADDR_PERM_DISTRICT_ID,
        'locationId' => self::LOCATION_ID,
        'functionalTypeId' => self::FUNCTIONAL_TYPE_ID,
        'functionalLevelId' => self::FUNCTIONAL_LEVEL_ID,
        'groupId' => self::GROUP_ID,
        'deletedDate' => self::DELETED_DATE,
        'deletedBy' => self::DELETED_BY,
        'empowerCompanyCode' => self::EMPOWER_COMPANY_CODE,
        'empowerBranchCode' => self::EMPOWER_BRANCH_CODE,
        'abroadAddress' => self::ABROAD_ADDRESS,
        'addrPermProvinceId' =>self::ADDR_PERM_PROVINCE_ID,
        'addrTempProvinceId' =>self::ADDR_TEMP_PROVINCE_ID,
        'payEmpType' =>self::PAY_EMP_TYPE,
        'wohFlag' =>self::WOH_FLAG,
        'overtimeEligible' =>self::OVERTIME_ELIGIBLE,
        'allowance' => self::ALLOWANCE,
        'bankId' => self::BANK_ID,
        'permanentDate' => self::PERMANENT_DATE,
        'gratuityDate' => self::GRATUITY_DATE,
        'disabledFlag' => self::DISABLED_FLAG,
		'taxBase' => self::TAX_BASE,
        'ssfNo'=>self::SSF_NO,
    ];

}
