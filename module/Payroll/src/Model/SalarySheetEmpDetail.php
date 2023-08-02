<?php
namespace Payroll\Model;

use Application\Model\Model;

class SalarySheetEmpDetail extends Model {

    const TABLE_NAME = "HRIS_SALARY_SHEET_EMP_DETAIL";
    const SHEET_NO = "SHEET_NO";
    const MONTH_ID = "MONTH_ID";
    const YEAR = "YEAR";
    const MONTH_NO = "MONTH_NO";
    const START_DATE = "START_DATE";
    const END_DATE = "END_DATE";
    const EMPLOYEE_ID = "EMPLOYEE_ID";
    const FULL_NAME = "FULL_NAME";
    const DEPARTMENT_NAME = "DEPARTMENT_NAME";
    const MARITAL_STATUS_DESC = "MARITAL_STATUS_DESC";
    const PRESENT = "PRESENT";
    const ABSENT = "ABSENT";
    const SALARY = "SALARY";
    const ACCOUNT_NO = "ACCOUNT_NO";
    const DESIGNATION_TITLE = "DESIGNATION_TITLE";
    const JOIN_DATE = "JOIN_DATE";
    const PAID_LEAVE = "PAID_LEAVE";
    const UNPAID_LEAVE = "UNPAID_LEAVE";

    
    public $sheetNo;
    public $monthId;
    public $year;
    public $monthNo;
    public $startDate;
    public $endDate;
    public $employeeId;
    public $fullName;
    public $departmentName;
    public $maritalStatusDesc;
    public $present;
    public $absent;
    public $salary;
    public $account;
    public $designation;
    public $join;
    public $paid;
    public $unpaid;

    public $mappings = [
        'sheetNo' => self::SHEET_NO,
        'monthId' => self::MONTH_ID,
        'year' => self::YEAR,
        'monthNo' => self::MONTH_NO,
        'startDate' => self::START_DATE,
        'endDate' => self::END_DATE,
        'employeeId' => self::EMPLOYEE_ID,
        'fullName' => self::FULL_NAME,
        'departmentName' => self::DEPARTMENT_NAME,
        'maritalStatusDesc' => self::MARITAL_STATUS_DESC,
        'present' => self::PRESENT,
        'absent' => self::ABSENT,
        'salary' => self::SALARY,
        'account' => self::ACCOUNT_NO,
        'designation'=>self::DESIGNATION_TITLE,
        'join'=>self::JOIN_DATE,
        'paid'=>self::PAID_LEAVE,
        'unpaid'=>self::UNPAID_LEAVE,
    ];

}
