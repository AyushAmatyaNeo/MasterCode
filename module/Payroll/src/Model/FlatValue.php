<?php

namespace Payroll\Model;

use Application\Model\Model;

class FlatValue extends Model {

    CONST TABLE_NAME = "HRIS_FLAT_VALUE_SETUP";
    CONST FLAT_ID = "FLAT_ID";
    CONST FLAT_CODE = "FLAT_CODE";
    CONST FLAT_EDESC = "FLAT_EDESC";
    CONST FLAT_LDESC = "FLAT_LDESC";
    CONST ASSIGN_TYPE = "ASSIGN_TYPE";
    CONST CREATED_DT = "CREATED_DT";
    CONST MODIFIED_DT = "MODIFIED_DT";
    CONST STATUS = "STATUS";
    CONST REMARKS = "REMARKS";

    public $flatId;
    public $flatCode;
    public $flatEdesc;
    public $flatLdesc;
    public $assignType;
    public $createdDt;
    public $modifiedDt;
    public $status;
    public $remarks;
    public $mappings = [
        'flatId' => self::FLAT_ID,
        'flatCode' => self::FLAT_CODE,
        'flatEdesc' => self::FLAT_EDESC,
        'flatLdesc' => self::FLAT_LDESC,
        'assignType' => self::ASSIGN_TYPE,
        'createdDt' => self::CREATED_DT,
        'modifiedDt' => self::MODIFIED_DT,
        'status' => self::STATUS,
        'remarks' => self::REMARKS,
    ];

}
