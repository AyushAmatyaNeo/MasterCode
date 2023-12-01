<?php

namespace Setup\Model;

use Application\Model\Model;

class Bank extends Model
{

    const TABLE_NAME = "HRIS_BANKS";
    const BANK_ID = "BANK_ID";
    const BANK_NAME = "BANK_NAME";
    const COMPANY_ACC_NO = "COMPANY_ACC_NO";
    const BRANCH_NAME = "BRANCH_NAME";
    const CREATED_BY = "CREATED_BY";
    const CREATED_DT = "CREATED_DT";
    const STATUS = "STATUS";
    const EMAIL = "EMAIL";

    public $bankId;
    public $bankName;
    public $comapnyAccNo;
    public $branchName;
    public $createdBy;
    public $createdDt;
    public $status;
    public $email;
    public $mappings = [
        'bankId' => self::BANK_ID,
        'bankName' => self::BANK_NAME,
        'comapnyAccNo' => self::COMPANY_ACC_NO,
        'branchName' => self::BRANCH_NAME,
        'createdBy' => self::CREATED_BY,
        'createdDt' => self::CREATED_DT,
        'status' => self::STATUS,
        'email' => self::EMAIL
    ];
}
