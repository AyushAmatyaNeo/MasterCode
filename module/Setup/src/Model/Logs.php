<?php

namespace Setup\Model;

use Application\Model\Model;

class Logs extends Model
{

    const TABLE_NAME = "HRIS_LOGS";
    const ID = "ID";
    const MODULE = "MODULE";
    const OPEARTION = "OPEARTION";
    const EMPLOYEE_ID = "EMPLOYEE_ID";
    const CREATED_DT = "CREATED_DT";
    const MODIFIED_DT = "MODIFIED_DT";
    const CREATED_BY = "CREATED_BY";
    const MODIFIED_BY = "MODIFIED_BY";
    const DELETED_BY = "DELETED_BY";
    const DELETED_DT = "DELETED_DT";
    const COMPANY_ID = "COMPANY_ID";
    const BRANCH_ID = "BRANCH_ID";
    const IP_ADDRESS = 'IP_ADDRESS';
    const HOST_NAME = 'HOST_NAME';
    const CREATED_DESC = 'CREATED_DESC';
    const MODIFIED_DESC = 'MODIFIED_DESC';
    const DELETED_DESC = 'DELETED_DESC';
    const TABLE_DESC = "TABLE_DESC";


    public $id;
    public $module;
    public $operation;
    public $employeeId;
    public $createdDt;
    public $modifiedDt;
    public $createdBy;
    public $modifiedBy;
    public $deletedBy;
    public $deletedDt;
    public $companyId;
    public $branchId;
    public $ipAddress;
    public $hostName;
    public $createdDesc;
    public $modifiedDesc;
    public $deletedDesc;
    public $tableDesc;

    public $mappings = [
        'id' => self::ID,
        'module' => self::MODULE,
        'operation' => self::OPEARTION,
        'employeeId' => self::EMPLOYEE_ID,
        'createdDt' => self::CREATED_DT,
        'modifiedDt' => self::MODIFIED_DT,
        'createdBy' => self::CREATED_BY,
        'modifiedBy' => self::MODIFIED_BY,
        'deletedBy' => self::DELETED_BY,
        'deletedDt' => self::DELETED_DT,
        'companyId' => self::COMPANY_ID,
        'branchId' => self::BRANCH_ID,
        'ipAddress' => self::IP_ADDRESS,
        'hostName' => self::HOST_NAME,
        'createdDesc' => self::CREATED_DESC,
        'modifiedDesc' => self::MODIFIED_DESC,
        'deletedDesc' => self::DELETED_DESC,
        'tableDesc' => self::TABLE_DESC,
    ];
}
