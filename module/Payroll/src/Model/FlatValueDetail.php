<?php

namespace Payroll\Model;

use Application\Model\Model;

class FlatValueDetail extends Model {

    const TABLE_NAME = "HRIS_FLAT_VALUE_DETAIL";
    const FLAT_ID = "FLAT_ID";
    const EMPLOYEE_ID = "EMPLOYEE_ID";
    const FLAT_VALUE = "FLAT_VALUE";
    const CREATED_DT = "CREATED_DT";
    const MODIFIED_DT = "MODIFIED_DT";
    CONST FISCAL_YEAR_ID = "FISCAL_YEAR_ID";
    CONST MONTH_ID = "MONTH_ID";

    public $flatId;
    public $employeeId;
    public $flatValue;
    public $createdDt;
    public $modifiedDt;
    public $fiscalYearId;
    public $monthId;
    public $mappings = [
        'flatId' => self::FLAT_ID,
        'employeeId' => self::EMPLOYEE_ID,
        'flatValue' => self::FLAT_VALUE,
        'createdDt' => self::CREATED_DT,
        'modifiedDt' => self::MODIFIED_DT,
        'fiscalYearId' => self::FISCAL_YEAR_ID,
        'monthId' => self::MONTH_ID,
    ];

}
