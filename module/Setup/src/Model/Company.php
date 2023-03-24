<?php

namespace Setup\Model;

use Application\Model\Model;

class Company extends Model {

    const TABLE_NAME = "HRIS_COMPANY";
    const COMPANY_ID = "COMPANY_ID";
    const COMPANY_CODE = "COMPANY_CODE";
    const COMPANY_NAME = "COMPANY_NAME";
    const ADDRESS = "ADDRESS";
    const TELEPHONE = "TELEPHONE";
    const FAX = "FAX";
    const SWIFT = "SWIFT";
    const CREATED_DT = "CREATED_DT";
    const MODIFIED_DT = "MODIFIED_DT";
    const STATUS = "STATUS";
    const CREATED_BY = "CREATED_BY";
    const MODIFIED_BY = "MODIFIED_BY";
    const LOGO = "LOGO";
    CONST LINK_TRAVEL_TO_SYNERGY = "LINK_TRAVEL_TO_SYNERGY";
    CONST FORM_CODE = "FORM_CODE";
    CONST DR_ACC_CODE = "DR_ACC_CODE";
    CONST CR_ACC_CODE = "CR_ACC_CODE";
    CONST EXCESS_CR_ACC_CODE = "EXCESS_CR_ACC_CODE";
    CONST LESS_DR_ACC_CODE = "LESS_DR_ACC_CODE";
    CONST EQUAL_CR_ACC_CODE = "EQUAL_CR_ACC_CODE";
    CONST ADVANCE_DR_ACC_CODE = "ADVANCE_DR_ACC_CODE";
    CONST ADVANCE_CR_ACC_CODE = "ADVANCE_CR_ACC_CODE";
    CONST FORM_CODE_FAC = "FORM_CODE_FAC";
    CONST DR_ACC_CODE_FAC = "DR_ACC_CODE_FAC";
    CONST CR_ACC_CODE_FAC = "CR_ACC_CODE_FAC";
    CONST EXCESS_CR_ACC_CODE_FAC = "EXCESS_CR_ACC_CODE_FAC";
    CONST LESS_DR_ACC_CODE_FAC = "LESS_DR_ACC_CODE_FAC";
    CONST EQUAL_CR_ACC_CODE_FAC = "EQUAL_CR_ACC_CODE_FAC";
    CONST ADVANCE_DR_ACC_CODE_FAC = "ADVANCE_DR_ACC_CODE_FAC";
    CONST ADVANCE_CR_ACC_CODE_FAC = "ADVANCE_CR_ACC_CODE_FAC";


    public $companyId;
    public $companyCode;
    public $companyName;
    public $address;
    public $telephone;
    public $fax;
    public $swift;
    public $createdDt;
    public $modifiedDt;
    public $status;
    public $createdBy;
    public $modifiedBy;
    public $logo;
    public $linkTravelToSynergy = 'N';
    public $formCode = null;
    public $drAccCode = null;
    public $crAccCode = null;
    public $excessCrAccCode = null;
    public $lessDrAccCode = null;
    public $equalCrAccCode = null;
    public $advanceDrAccCode = null;
    public $advanceCrAccCode = null;
    public $formCodeFac = null;
    public $drAccCodeFac = null;
    public $crAccCodeFac = null;
    public $excessCrAccCodeFac = null;
    public $lessDrAccCodeFac = null;
    public $equalCrAccCodeFac = null;
    public $advanceDrAccCodeFac = null;
    public $advanceCrAccCodeFac = null;
    public $mappings = [
        'companyId' => self::COMPANY_ID,
        'companyCode' => self::COMPANY_CODE,
        'companyName' => self::COMPANY_NAME,
        'address' => self::ADDRESS,
        'telephone' => self::TELEPHONE,
        'fax' => self::FAX,
        'swift' => self::SWIFT,
        'createdDt' => self::CREATED_DT,
        'modifiedDt' => self::MODIFIED_DT,
        'status' => self::STATUS,
        'createdBy' => self::CREATED_BY,
        'modifiedBy' => self::MODIFIED_BY,
        'logo' => self::LOGO,
        'linkTravelToSynergy' => self::LINK_TRAVEL_TO_SYNERGY,
        'formCode' => self::FORM_CODE,
        'drAccCode' => self::DR_ACC_CODE,
        'crAccCode' => self::CR_ACC_CODE,
        'excessCrAccCode' => self::EXCESS_CR_ACC_CODE,
        'lessDrAccCode' => self::LESS_DR_ACC_CODE,
        'equalCrAccCode' => self::EQUAL_CR_ACC_CODE,
        'advanceDrAccCode' => self::ADVANCE_DR_ACC_CODE,
        'advanceCrAccCode' => self::ADVANCE_CR_ACC_CODE,
        'formCodeFac' => self::FORM_CODE_FAC,
        'drAccCodeFac' => self::DR_ACC_CODE_FAC,
        'crAccCodeFac' => self::CR_ACC_CODE_FAC,
        'excessCrAccCodeFac' => self::EXCESS_CR_ACC_CODE_FAC,
        'lessDrAccCodeFac' => self::LESS_DR_ACC_CODE_FAC,
        'equalCrAccCodeFac' => self::EQUAL_CR_ACC_CODE_FAC,
        'advanceDrAccCodeFac' => self::ADVANCE_DR_ACC_CODE_FAC,
        'advanceCrAccCodeFac' => self::ADVANCE_CR_ACC_CODE_FAC,
    ];

}
