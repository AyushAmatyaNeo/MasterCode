<?php
namespace Setup\Model;

use Application\Model\Model;

class EmployeeSalaryHistory extends Model{
    const TABLE_NAME = "HRIS_EMPLOYEE_SALARY_HISTORY";
    
    const ID = "ID";
    const EMPLOYEE_ID = "EMPLOYEE_ID";
    const PAY_ID = "PAY_ID";
    const AMOUNT = "AMOUNT";
    const FROM_DATE = "FROM_DATE";
    const TO_DATE = "TO_DATE";
    const EFFECT_BY_WORKING_DAY = "EFFECT_BY_WORKING_DAY";
    const IS_DEFAULT_HEAD = "IS_DEFAULT_HEAD";
    const IS_ENABLE = "IS_ENABLE";
    const CREATED_BY = "CREATED_BY";
    const CREATED_DATE = "CREATED_DATE";
    const MODIFIED_BY = "MODIFIED_BY";
    const MODIFIED_DATE = "MODIFIED_DATE";
    const STATUS = "STATUS";
    
    public $id;
    public $employeeId;
    public $amount;
    public $effectByWorkingDay;
    public $salaryHead;
    public $fromDate;
    public $toDate;
    public $isDefaultHead;
    public $isEnable;
    public $createdBy;
    public $createdDate;
    public $modifiedBy;
    public $modifiedDate;
    public $status;
    
    public $mappings= [
        'id'=>self::ID,
        'employeeId'=>self::EMPLOYEE_ID,
        'amount'=>self::AMOUNT,
        'effectByWorkingDay'=>self::EFFECT_BY_WORKING_DAY,
        'salaryHead'=>self::PAY_ID,
        'fromDate'=>self::FROM_DATE,
        'toDate'=>self::TO_DATE,
        'isDefaultHead'=>self::IS_DEFAULT_HEAD,
        'isEnable'=>self::IS_ENABLE,
        'createdBy'=>self::CREATED_BY,
        'createdDate'=>self::CREATED_DATE,
        'modifiedBy'=>self::MODIFIED_BY,
        'modifiedDate'=>self::MODIFIED_DATE,
        'status'=>self::STATUS
    ];
}