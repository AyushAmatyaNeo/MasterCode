<?php

namespace Payroll\Model;

use Application\Model\Model;

class PaySlipEmail extends Model
{

    const TABLE_NAME = "HRIS_PAYSLIP_EMAIL";
    const ID = "ID";
    const EMPLOYEE_ID = "EMPLOYEE_ID";


    public $id;
    public $employeeId;

    public $mappings = [
        'id' => self::ID,
        'employeeId' => self::EMPLOYEE_ID,

    ];
}
