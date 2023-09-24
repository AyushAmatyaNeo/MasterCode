<?php

namespace LeaveManagement\Model;

use Application\Model\Model;

class LeaveMaster extends Model
{

    const TABLE_NAME = "HRIS_LEAVE_MASTER_SETUP";
    const LEAVE_ID = "LEAVE_ID";
    const LEAVE_CODE = "LEAVE_CODE";
    const LEAVE_ENAME = "LEAVE_ENAME";
    const LEAVE_LNAME = "LEAVE_LNAME";
    const ALLOW_HALFDAY = "ALLOW_HALFDAY";
    const DEFAULT_DAYS = "DEFAULT_DAYS";
    const FISCAL_YEAR = "FISCAL_YEAR";
    const CARRY_FORWARD = "CARRY_FORWARD";
    const CASHABLE = "CASHABLE";
    const CREATED_DT = "CREATED_DT";
    const MODIFIED_DT = "MODIFIED_DT";
    const STATUS = "STATUS";
    const REMARKS = "REMARKS";
    const CREATED_BY = "CREATED_BY";
    const MODIFIED_BY = "MODIFIED_BY";
    const PAID = "PAID";
    const MAX_ACCUMULATE_DAYS = "MAX_ACCUMULATE_DAYS";
    const IS_SUBSTITUTE = "IS_SUBSTITUTE";
    const ALLOW_GRACE_LEAVE = "ALLOW_GRACE_LEAVE";
    const IS_MONTHLY = "IS_MONTHLY";
    const IS_SUBSTITUTE_MANDATORY = "IS_SUBSTITUTE_MANDATORY";
    const ASSIGN_ON_EMPLOYEE_SETUP = "ASSIGN_ON_EMPLOYEE_SETUP";
    const IS_PRODATA_BASIS = "IS_PRODATA_BASIS";
    const ENABLE_SUBSTITUTE = "ENABLE_SUBSTITUTE";
    const COMPANY_ID = "COMPANY_ID";
    const BRANCH_ID = "BRANCH_ID";
    const DEPARTMENT_ID = "DEPARTMENT_ID";
    const DESIGNATION_ID = "DESIGNATION_ID";
    const POSITION_ID = "POSITION_ID";
    const SERVICE_TYPE_ID = "SERVICE_TYPE_ID";
    const EMPLOYEE_TYPE = "EMPLOYEE_TYPE";
    const GENDER_ID = "GENDER_ID";
    const EMPLOYEE_ID = "EMPLOYEE_ID";
    const DAY_OFF_AS_LEAVE = "DAY_OFF_AS_LEAVE";
    const HOLIDAY_AS_LEAVE = "HOLIDAY_AS_LEAVE";
    const APPLY_LIMIT = "APPLY_LIMIT";
    const HR_ONLY = "HR_ONLY";
    const ENABLE_OVERRIDE = "ENABLE_OVERRIDE";
    const LEAVE_YEAR = "LEAVE_YEAR";
    const OLD_LEAVE = "OLD_LEAVE";
    const IS_SUB_LEAVE = "IS_SUB_LEAVE";
    const VIEW_ORDER = "VIEW_ORDER";


    public $leaveId;
    public $leaveCode;
    public $leaveEname;
    public $leaveLname;
    public $allowHalfday;
    public $defaultDays;
    public $fiscalYear;
    public $carryForward;
    public $cashable;
    public $createdDt;
    public $modifiedDt;
    public $status;
    public $remarks;
    public $createdBy;
    public $modifiedBy;
    public $paid;
    public $maxAccumulateDays;
    public $allowGraceLeave;
    public $isMonthly;
    public $isSubstituteMandatory;
    public $assignOnEmployeeSetup;
    public $isProdataBasis;
    public $enableSubstitute;
    public $companyId;
    public $branchId;
    public $departmentId;
    public $designationId;
    public $positionId;
    public $serviceTypeId;
    public $employeeType;
    public $genderId;
    public $employeeId;
    public $dayOffAsLeave;
    public $holidayAsLeave;
    public $applyLimit;
    public $hrOnly;
    public $enableOverride;
    public $leaveYear;
    public $isSubstitute;
    public $inSubLeave;
    public $oldLeave;
    public $viewOrder;
    public $mappings = [
        'leaveId' => self::LEAVE_ID,
        'leaveCode' => self::LEAVE_CODE,
        'leaveEname' => self::LEAVE_ENAME,
        'leaveLname' => self::LEAVE_LNAME,
        'allowHalfday' => self::ALLOW_HALFDAY,
        'defaultDays' => self::DEFAULT_DAYS,
        'fiscalYear' => self::FISCAL_YEAR,
        'carryForward' => self::CARRY_FORWARD,
        'cashable' => self::CASHABLE,
        'createdDt' => self::CREATED_DT,
        'modifiedDt' => self::MODIFIED_DT,
        'status' => self::STATUS,
        'remarks' => self::REMARKS,
        'createdBy' => self::CREATED_BY,
        'modifiedBy' => self::MODIFIED_BY,
        'paid' => self::PAID,
        'inSubLeave' => self::IS_SUB_LEAVE,
        'maxAccumulateDays' => self::MAX_ACCUMULATE_DAYS,
        'isSubstitute' => self::IS_SUBSTITUTE,
        'allowGraceLeave' => self::ALLOW_GRACE_LEAVE,
        'isMonthly' => self::IS_MONTHLY,
        'isSubstituteMandatory' => self::IS_SUBSTITUTE_MANDATORY,
        'assignOnEmployeeSetup' => self::ASSIGN_ON_EMPLOYEE_SETUP,
        'isProdataBasis' => self::IS_PRODATA_BASIS,
        'enableSubstitute' => self::ENABLE_SUBSTITUTE,
        'companyId' => self::COMPANY_ID,
        'branchId' => self::BRANCH_ID,
        'departmentId' => self::DEPARTMENT_ID,
        'designationId' => self::DESIGNATION_ID,
        'positionId' => self::POSITION_ID,
        'serviceTypeId' => self::SERVICE_TYPE_ID,
        'employeeType' => self::EMPLOYEE_TYPE,
        'genderId' => self::GENDER_ID,
        'employeeId' => self::EMPLOYEE_ID,
        'dayOffAsLeave' => self::DAY_OFF_AS_LEAVE,
        'holidayAsLeave' => self::HOLIDAY_AS_LEAVE,
        'applyLimit' => self::APPLY_LIMIT,
        'hrOnly' => self::HR_ONLY,
        'enableOverride' => self::ENABLE_OVERRIDE,
        'leaveYear' => self::LEAVE_YEAR,
        'oldLeave' => self::OLD_LEAVE,
        'viewOrder' => self::VIEW_ORDER,
    ];
}
