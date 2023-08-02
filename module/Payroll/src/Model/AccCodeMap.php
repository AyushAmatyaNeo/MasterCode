<?php

namespace Payroll\Model;

use Application\Model\Model;

class AccCodeMap extends Model {

    CONST TABLE_NAME = "HRIS_ACC_CODE_MAP";
    CONST ID = "ID";
    CONST PAY_ID = "PAY_ID";
    CONST ACC_CODE = "ACC_CODE";
    CONST DELETED_FLAG = "DELETED_FLAG";
    CONST BRANCH_CODE = "BRANCH_CODE";
    CONST COMPANY_CODE = "COMPANY_CODE";
    CONST GROUP_ID = "GROUP_ID";

    public $Id;
    public $payId;
    public $accCode;
    public $deleteFlag;
    public $branchCode;
    public $companyCode;
    public $groupId;
    public $mappings = [
        'Id' => self:: ID,
        'payId' => self:: PAY_ID,
        'accCode' => self:: ACC_CODE,
        'deleteFlag' => self:: DELETED_FLAG,
        'branchCode' => self:: BRANCH_CODE,
        'companyCode' => self:: COMPANY_CODE,
        'groupId' => self:: GROUP_ID
    ];

}
