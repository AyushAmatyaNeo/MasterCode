<?php

namespace Setup\Form;

use Application\Model\Model;
use Zend\Form\Annotation;

class HrEmployeesFormTabOne extends Model {

    /**
     * @Annotation\Type("Zend\Form\Element\Text")
     * @Annotation\Required(false)
     * @Annotation\Filter({"name":"StringTrim","name":"StripTags"})
     * @Annotation\Options({"label":"Employee Code"})
     * @Annotation\Validator({"name":"StringLength", "options":{"max":"15"}})
     * @Annotation\Attributes({ "id":"employeeCode", "class":"form-control" })
     */
    public $employeeCode;
    public $employeeId;

    /**
     * @Annotation\Type("Zend\Form\Element\Select")
     * @Annotation\Required(true)
     * @Annotation\Options({"disable_inarray_validator":"true","label":"Company"})
     * @Annotation\Attributes({ "id":"companyId","class":"form-control"})
     */
    public $companyId;

    /**
     * @Annotation\Type("Zend\Form\Element\Text")
     * @Annotation\Required(true)
     * @Annotation\Filter({"name":"StringTrim","name":"StripTags"})
     * @Annotation\Options({"label":"First Name"})
     * @Annotation\Attributes({ "id":"firstName", "class":"form-control" })
     */
    public $firstName;

    /**
     * @Annotation\Type("Zend\Form\Element\Text")
     * @Annotation\Required(false)
     * @Annotation\Filter({"name":"StringTrim","name":"StripTags"})
     * @Annotation\Options({"label":"Middle Name"})
     * @Annotation\Attributes({ "id":"middleName", "class":"form-control" })
     */
    public $middleName;

    /**
     * @Annotation\Type("Zend\Form\Element\Text")
     * @Annotation\Required(true)
     * @Annotation\Filter({"name":"StringTrim","name":"StripTags"})
     * @Annotation\Options({"label":"Last Name"})
     * @Annotation\Attributes({ "id":"lastName", "class":"form-control" })
     */
    public $lastName;

    /**
     * @Annotation\Type("Zend\Form\Element\Text")
     * @Annotation\Required(false)
     * @Annotation\Filter({"name":"StringTrim","name":"StripTags"})
     * @Annotation\Options({"label":"Name in Nepali"})
     * @Annotation\Attributes({ "id":"nameNepali", "class":"form-control" })
     */
    public $nameNepali;

    /**
     * @Annotation\Type("Zend\Form\Element\Select")
     * @Annotation\Filter({"name":"StringTrim","name":"StripTags"})
     * @Annotation\Required(true)
     * @Annotation\Options({"disable_inarray_validator":"true","label":"Gender"})
     * @Annotation\Attributes({ "id":"genderId","class":"form-control"})
     */
    public $genderId;

    /**
     * @Annotation\Type("Zend\Form\Element\Text")
     * @Annotation\Required(true)
     * @Annotation\Filter({"name":"StringTrim","name":"StripTags"})
     * @Annotation\Options({"label":"Birth Date"})
     * @Annotation\Attributes({ "class":"form-control", "id":"birthdate" })
     */
    public $birthDate;

    /**
     * @Annotation\Type("Zend\Form\Element\Select")
     * @Annotation\Required(false)
     * @Annotation\Filter({"name":"StringTrim","name":"StripTags"})
     * @Annotation\Options({"disable_inarray_validator":"true","label":"Blood Group"})
     * @Annotation\Attributes({ "id":"bloodGroupId","class":"form-control"})
     */
    public $bloodGroupId;

    /**
     * @Annotation\Type("Zend\Form\Element\Select")
     * @Annotation\Required(false)
     * @Annotation\Filter({"name":"StringTrim","name":"StripTags"})
     * @Annotation\Options({"disable_inarray_validator":"true","label":"Religion"})
     * @Annotation\Attributes({ "id":"religionId","class":"form-control"})
     */
    public $religionId;

    /**
     * @Annotation\Type("Zend\Form\Element\Text")
     * @Annotation\Required(false)
     * @Annotation\Filter({"name":"StringTrim","name":"StripTags"})
     * @Annotation\Options({"label":"Social Activity"})
     * @Annotation\Attributes({ "id":"socialActivity","class":"form-control" })
     */
    public $socialActivity;

    /**
     * @Annotation\Type("Application\Custom\FormElement\Telephone")
     * @Annotation\Required(false)
     * @Annotation\Filter({"name":"StringTrim","name":"StripTags"})
     * @Annotation\Options({"label":"Telephone No"})
     * @Annotation\Attributes({ "id":"telephoneNo", "placeholder":"xxx-xxxxxxx", "pattern":"^\(?\d{2,3}\)?[- ]?\d{7}$", "class":"form-control","title"="Enter your mobile number(xx-xxxxxxx)"})
     */
    public $telephoneNo;

    /**
     * @Annotation\Type("Application\Custom\FormElement\Mobile")
     * @Annotation\Required(true)
     * @Annotation\Filter({"name":"StringTrim","name":"StripTags"})
     * @Annotation\Options({"label":"Mobile No"})
     * @Annotation\Attributes({ "id":"MobileNo", "placeholder":"xxx-xxx-xxxx", "class":"form-control" , "pattern"="^\(?\d{3}\)?[- ]?\d{3}[- ]?\d{4}$", "title"="Enter your mobile number(xxx-xxx-xxxx)"})
     */
    public $mobileNo;

    /**
     * @Annotation\Required(false)
     * @Annotation\Type("Zend\Form\Element\Text")
     * @Annotation\Filter({"name":"StringTrim","name":"StripTags"})
     * @Annotation\Options({"label":"Extension Number"})
     * @Annotation\Validator({"name":"StringLength", "options":{"max":"50"}})
     * @Annotation\Attributes({ "id":"extensionNo", "class":"form-control" })
     */
    public $extensionNo;

    /**
     * @Annotation\Required(false)
     * @Annotation\Type("Zend\Form\Element\Email")
     * @Annotation\Filter({"name":"StringTrim","name":"StripTags"})
     * @Annotation\Options({"label":"Email Official"})
     * @Annotation\Attributes({ "id":"emailOfficial", "class":"form-control" })
     */
    public $emailOfficial;

    /**
     * @Annotation\Type("Zend\Form\Element\Email")
     * @Annotation\Required(false)
     * @Annotation\Filter({"name":"StringTrim","name":"StripTags"})
     * @Annotation\Options({"label":"Email Personal"})
     * @Annotation\Attributes({ "id":"emailPersonal", "class":"form-control" })
     */
    public $emailPersonal;

    /**
     * @Annotation\Type("Zend\Form\Element\Text")
     * @Annotation\Required(false)
     * @Annotation\Filter({"name":"StringTrim","name":"StripTags"})
     * @Annotation\Options({"label":"Social Network"})
     * @Annotation\Attributes({ "id":"socialNetwork", "class":"form-control" })
     */
    public $socialNetwork;

    /**
     * @Annotation\Type("Zend\Form\Element\Select")
     * @Annotation\Required(false)
     * @Annotation\Filter({"name":"StringTrim","name":"StripTags"})
     * @Annotation\Options({"disable_inarray_validator":"true","label":"Country"})
     * @Annotation\Attributes({ "id":"countryId","class":"form-control"})
     */
    public $countryId;

    /**
     * @Annotation\Required(false)
     * @Annotation\Type("Zend\Form\Element\Text")
     * @Annotation\Filter({"name":"StringTrim","name":"StripTags"})
     * @Annotation\Options({"label":"Name"})
     * @Annotation\Attributes({ "id":"emergContactName", "class":"form-control" })
     */
    public $emergContactName;

    /**
     * @Annotation\Type("Application\Custom\FormElement\Mobile")
     * @Annotation\Required(false)
     * @Annotation\Filter({"name":"StringTrim","name":"StripTags"})
     * @Annotation\Options({"label":"Phone No"})
     * @Annotation\Attributes({ "id":"emergContactNo", "placeholder":"xxx-xxx-xxxx", "class":"form-control" , "pattern"="^\(?\d{3}\)?[- ]?\d{3}[- ]?\d{4}$", "title"="Enter your mobile number(xxx-xxx-xxxx)"})
     */
    public $emergContactNo;

    /**
     * @Annotation\Type("Zend\Form\Element\Text")
     * @Annotation\Filter({"name":"StringTrim","name":"StripTags"})
     * @Annotation\Required(false)
     * @Annotation\Options({"label":"Address"})
     * @Annotation\Attributes({ "id":"emergContactAddress", "class":"form-control" })
     */
    public $emergContactAddress;

    /**
     * @Annotation\Type("Zend\Form\Element\Text")
     * @Annotation\Filter({"name":"StringTrim","name":"StripTags"})
     * @Annotation\Required(false)
     * @Annotation\Options({"label":"Relationship"})
     * @Annotation\Attributes({ "id":"emergContactRelationship", "class":"form-control" })
     */
    public $emergContactRelationship;

    /**
     * @Annotation\Type("Zend\Form\Element\Text")
     * @Annotation\Filter({"name":"StringTrim","name":"StripTags"})
     * @Annotation\Required(false)
     * @Annotation\Options({"label":" House No"})
     * @Annotation\Validator({"name":"StringLength", "options":{"max":"10"}})
     * @Annotation\Attributes({ "id":"addrPermHouseNo", "class":"form-control" })
     */
    public $addrPermHouseNo;

    /**
     * @Annotation\Type("Zend\Form\Element\Number")
     * @Annotation\Filter({"name":"StringTrim","name":"StripTags"})
     * @Annotation\Options({"label":" Ward No"})
     * @Annotation\Required(false)
     * @Annotation\Validator({"name":"StringLength", "options":{"max":"2"}})
     * @Annotation\Attributes({ "id":"addrPermWardNo", "class":"form-control","min":"1" })
     */
    public $addrPermWardNo;

    /**
     * @Annotation\Type("Zend\Form\Element\Text")
     * @Annotation\Filter({"name":"StringTrim","name":"StripTags"})
     * @Annotation\Required(false)
     * @Annotation\Options({"label":"Street Address"})
     * @Annotation\Attributes({ "id":"addrPermStreetAddress", "class":"form-control" })
     */
    public $addrPermStreetAddress;

    /**
     * @Annotation\Type("Zend\Form\Element\Text")
     * @Annotation\Filter({"name":"StringTrim","name":"StripTags"})
     * @Annotation\Required(false)
     * @Annotation\Options({"label":"VDC or Municipality"})
     * @Annotation\Attributes({ "id":"addrPermVdcMunicipalityId","class":"form-control"})
     */
    public $addrPermVdcMunicipalityId;

    /**
     * @Annotation\Type("Zend\Form\Element\Select")
     * @Annotation\Filter({"name":"StringTrim","name":"StripTags"})
     * @Annotation\Options({"disable_inarray_validator":"true","label":" District"})
     * @Annotation\Required(false)
     * @Annotation\Attributes({ "id":"addrPermDistrictId","class":"form-control"})
     *
     */
    public $addrPermDistrictId;

    /**
     * @Annotation\Type("Zend\Form\Element\Select")
     * @Annotation\Filter({"name":"StringTrim","name":"StripTags"})
     * @Annotation\Required(false)
     * @Annotation\Options({"disable_inarray_validator":"true","label":" Zone"})
     * @Annotation\Attributes({ "id":"addrPermZoneId","class":"form-control"})
     */
    public $addrPermZoneId;
    
    /**
     * @Annotation\Type("Zend\Form\Element\Select")
     * @Annotation\Filter({"name":"StringTrim","name":"StripTags"})
     * @Annotation\Required(false)
     * @Annotation\Options({"disable_inarray_validator":"true","label":" Province"})
     * @Annotation\Attributes({ "id":"addrPermProvinceId","class":"form-control"})
     */
    public $addrPermProvinceId;

    /**
     * @Annotation\Type("Zend\Form\Element\Text")
     * @Annotation\Filter({"name":"StringTrim","name":"StripTags"})
     * @Annotation\Options({"label":"House No"})
     * @Annotation\Required(false)
     * @Annotation\Validator({"name":"StringLength", "options":{"max":"10"}})
     * @Annotation\Attributes({ "id":"addrTempHouseNo", "class":"form-control" })
     */
    public $addrTempHouseNo;

    /**
     * @Annotation\Type("Zend\Form\Element\Number")
     * @Annotation\Filter({"name":"StringTrim","name":"StripTags"})
     * @Annotation\Required(false)
     * @Annotation\Options({"label":"Ward No"})
     * @Annotation\Validator({"name":"StringLength", "options":{"max":"2"}})
     * @Annotation\Attributes({ "id":"addrTempWardNo", "class":"form-control","min":"1" })
     */
    public $addrTempWardNo;

    /**
     * @Annotation\Type("Zend\Form\Element\Text")
     * @Annotation\Filter({"name":"StringTrim","name":"StripTags"})
     * @Annotation\Options({"label":"Street Address"})
     * @Annotation\Required(false)
     * @Annotation\Attributes({ "id":"addrTempStreetAddress", "class":"form-control" })
     */
    public $addrTempStreetAddress;

    /**
     * @Annotation\Type("Zend\Form\Element\Text")
     * @Annotation\Filter({"name":"StringTrim","name":"StripTags"})
     * @Annotation\Options({"label":"VDC or Municipality"})
     * @Annotation\Required(false)
     * @Annotation\Attributes({ "id":"addrTempVdcMunicipality","class":"form-control"})
     */
    public $addrTempVdcMunicipalityId;

    /**
     * @Annotation\Type("Zend\Form\Element\Select")
     * @Annotation\Filter({"name":"StringTrim","name":"StripTags"})
     * @Annotation\Options({"disable_inarray_validator":"true","label":"District"})
     * @Annotation\Required(false)
     * @Annotation\Attributes({ "id":"addrTempDistrictId","class":"form-control"})
     */
    public $addrTempDistrictId;

    /**
     * @Annotation\Type("Zend\Form\Element\Select")
     * @Annotation\Filter({"name":"StringTrim","name":"StripTags"})
     * @Annotation\Required(false)
     * @Annotation\Options({"disable_inarray_validator":"true","label":"Zone"})
     * @Annotation\Attributes({ "id":"addrTempZoneId","class":"form-control"})
     */
    public $addrTempZoneId;
    
    /**
     * @Annotation\Type("Zend\Form\Element\Select")
     * @Annotation\Filter({"name":"StringTrim","name":"StripTags"})
     * @Annotation\Required(false)
     * @Annotation\Options({"disable_inarray_validator":"true","label":" Province"})
     * @Annotation\Attributes({ "id":"addrTempProvinceId","class":"form-control"})
     */
    public $addrTempProvinceId;
    
    /**
     * @Annotation\Type("Zend\Form\Element\Textarea")
     * @Annotation\Required(false)
     * @Annotation\Filter({"name":"StripTags","name":"StringTrim"})
     * @Annotation\Options({"label":"Abroad Address"})
     * @Annotation\Attributes({"id":"abroadAddress","rows":"6","cols":"120","style":"width: 100%; resize: none;"})
     */
    public $abroadAddress;
    
    
    public $addrPermCountryId;
    public $addrTempCountryId;
    public $status;
    public $createdDt;
    public $createdBy;
    public $modifiedBy;
    public $modifiedDt;
    public $mappings = [
        'employeeId' => 'EMPLOYEE_ID',
        'companyId' => 'COMPANY_ID',
        'employeeCode' => 'EMPLOYEE_CODE',
        'firstName' => 'FIRST_NAME',
        'middleName' => 'MIDDLE_NAME',
        'lastName' => 'LAST_NAME',
        'nameNepali' => 'NAME_NEPALI',
        'genderId' => 'GENDER_ID',
        'birthDate' => 'BIRTH_DATE',
        'bloodGroupId' => 'BLOOD_GROUP_ID',
        'religionId' => 'RELIGION_ID',
        'socialActivity' => 'SOCIAL_ACTIVITY',
        'countryId' => 'COUNTRY_ID',
        'telephoneNo' => 'TELEPHONE_NO',
        'mobileNo' => 'MOBILE_NO',
        'extensionNo' => 'EXTENSION_NO',
        'emailOfficial' => 'EMAIL_OFFICIAL',
        'emailPersonal' => 'EMAIL_PERSONAL',
        'socialNetwork' => 'SOCIAL_NETWORK',
        'emergContactName' => 'EMRG_CONTACT_NAME',
        'emergContactNo' => 'EMERG_CONTACT_NO',
        'emergContactAddress' => 'EMERG_CONTACT_ADDRESS',
        'emergContactRelationship' => 'EMERG_CONTACT_RELATIONSHIP',
        'addrPermHouseNo' => 'ADDR_PERM_HOUSE_NO',
        'addrPermWardNo' => 'ADDR_PERM_WARD_NO',
        'addrPermStreetAddress' => 'ADDR_PERM_STREET_ADDRESS',
        'addrPermVdcMunicipalityId' => 'ADDR_PERM_VDC_MUNICIPALITY_ID',
        'addrPermDistrictId' => 'ADDR_PERM_DISTRICT_ID',
        'addrPermZoneId' => 'ADDR_PERM_ZONE_ID',
        'addrTempHouseNo' => 'ADDR_TEMP_HOUSE_NO',
        'addrTempWardNo' => 'ADDR_TEMP_WARD_NO',
        'addrTempStreetAddress' => 'ADDR_TEMP_STREET_ADDRESS',
        'addrTempVdcMunicipalityId' => 'ADDR_TEMP_VDC_MUNICIPALITY_ID',
        'addrTempDistrictId' => 'ADDR_TEMP_DISTRICT_ID',
        'addrTempZoneId' => 'ADDR_TEMP_ZONE_ID',
        'addrPermCountryId' => 'ADDR_PERM_COUNTRY_ID',
        'addrTempCountryId' => 'ADDR_TEMP_COUNTRY_ID',
        'status' => 'STATUS',
        'createdDt' => 'CREATED_DT',
        'createdBy' => 'CREATED_BY',
        'modifiedBy' => 'MODIFIED_BY',
        'modifiedDt' => 'MODIFIED_DT',
        'abroadAddress' => 'ABROAD_ADDRESS',
        'addrPermProvinceId' => 'ADDR_PERM_PROVINCE_ID',
        'addrTempProvinceId' => 'ADDR_TEMP_PROVINCE_ID',
    ];

}
