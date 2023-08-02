<?php

namespace Payroll\Model;

use Application\Model\Model;

class Rules extends Model
{

    const TABLE_NAME = "HRIS_PAY_SETUP";
    const PAY_ID = "PAY_ID";
    const PAY_CODE = "PAY_CODE";
    const PAY_EDESC = "PAY_EDESC";
    const PAY_LDESC = "PAY_LDESC";
    const PAY_TYPE_FLAG = "PAY_TYPE_FLAG";
    const PRIORITY_INDEX = "PRIORITY_INDEX";
    const REMARKS = "REMARKS";
    const CREATED_BY = "CREATED_BY";
    const CREATED_DT = "CREATED_DT";
    const MODIFIED_DT = "MODIFIED_DT";
    const MODIFIED_BY = "MODIFIED_BY";
    const STATUS = "STATUS";
    const FORMULA = "FORMULA";
    const INCLUDE_IN_TAX = "INCLUDE_IN_TAX";
    const INCLUDE_IN_SALARY = "INCLUDE_IN_SALARY";
    const INCLUDE_PAST_VALUE = "INCLUDE_PAST_VALUE";
    const INCLUDE_FUTURE_VALUE = "INCLUDE_FUTURE_VALUE";
    const DEDUCTION_LIMIT_FLAG = "DEDUCTION_LIMIT_FLAG";

    public $payId;
    public $payCode;
    public $payEdesc;
    public $payLdesc;
    public $payTypeFlag;
    public $priorityIndex;
    public $remarks;
    public $createdBy;
    public $createdDt;
    public $modifiedDt;
    public $modifiedBy;
    public $status;
    public $formula;
    public $includeInTax;
    public $includeInSalary;
    public $includePastValue;
    public $includeFutureValue;
    public $deductionLimitFlag;
    public $mappings = [
        'payId' => self::PAY_ID,
        'payCode' => self::PAY_CODE,
        'payEdesc' => self::PAY_EDESC,
        'payLdesc' => self::PAY_LDESC,
        'payTypeFlag' => self::PAY_TYPE_FLAG,
        'priorityIndex' => self::PRIORITY_INDEX,
        'remarks' => self::REMARKS,
        'createdBy' => self::CREATED_BY,
        'createdDt' => self::CREATED_DT,
        'modifiedDt' => self::MODIFIED_DT,
        'modifiedBy' => self::MODIFIED_BY,
        'status' => self::STATUS,
        'formula' => self::FORMULA,
        'includeInTax' => self::INCLUDE_IN_TAX,
        'includeInSalary' => self::INCLUDE_IN_SALARY,
        'includePastValue' => self::INCLUDE_PAST_VALUE,
        'includeFutureValue' => self::INCLUDE_FUTURE_VALUE,
        'deductionLimitFlag' => self::DEDUCTION_LIMIT_FLAG,
    ];
}
