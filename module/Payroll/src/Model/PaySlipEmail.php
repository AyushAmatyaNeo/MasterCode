<?php

namespace Payroll\Model;

use Application\Model\Model;

class PaySlipEmail extends Model
{

    const TABLE_NAME = "HRIS_PAYSLIP_EMAIL";
    const ID = "ID";
    const EMPLOYEE_ID = "EMPLOYEE_ID";
    const CREATED_BY = "CREATED_BY";
    const CREATED_DT = "CREATED_DT";


    public $id;
    public $employeeId;
    public $createdBy;
    public $createdDt;

    public $mappings = [
        'id' => self::ID,
        'employeeId' => self::EMPLOYEE_ID,
        'createdBy' => self::CREATED_BY,
        'createdDt' => self::CREATED_DT
    ];
}
