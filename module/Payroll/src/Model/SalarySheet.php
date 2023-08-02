<?php

namespace Payroll\Model;

use Application\Model\Model;

class SalarySheet extends Model {

    const TABLE_NAME = "HRIS_SALARY_SHEET";
    const SHEET_NO = "SHEET_NO";
    const MONTH_ID = "MONTH_ID";
    const STATUS = "STATUS";
    const REMARKS = "REMARKS";
    const CREATED_DT = "CREATED_DT";
    const MODIFIED_DT = "MODIFIED_DT";
    const YEAR = "YEAR";
    const MONTH_NO = "MONTH_NO";
    const START_DATE = "START_DATE";
    const END_DATE = "END_DATE";
    const COMPANY_ID = "COMPANY_ID";
    const GROUP_ID = "GROUP_ID";
    const SALARY_TYPE_ID = "SALARY_TYPE_ID";
    const EXCHANGE_RATE = "EXCHANGE_RATE";

    public $sheetNo;
    public $monthId;
    public $status;
    public $remarks;
    public $createdDt;
    public $modifiedDt;
    public $year;
    public $monthNo;
    public $startDate;
    public $endDate;
    public $companyId;
    public $groupId;
    public $salaryTypeId;
    public $exchangeRate;
    public $mappings = [
        'sheetNo' => self::SHEET_NO,
        'monthId' => self::MONTH_ID,
        'status' => self::STATUS,
        'remarks' => self::REMARKS,
        'createdDt' => self::CREATED_DT,
        'modifiedDt' => self::MODIFIED_DT,
        'year' => self::YEAR,
        'monthNo' => self::MONTH_NO,
        'startDate' => self::START_DATE,
        'endDate' => self::END_DATE,
        'companyId' => self::COMPANY_ID,
        'groupId' => self::GROUP_ID,
        'salaryTypeId' => self::SALARY_TYPE_ID,
        'exchangeRate' => self::EXCHANGE_RATE,
    ];

}
