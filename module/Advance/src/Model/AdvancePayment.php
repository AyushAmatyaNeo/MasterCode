<?php

namespace Advance\Model;

use Application\Model\Model;

class AdvancePayment extends Model
{

    const TABLE_NAME = 'HRIS_EMPLOYEE_ADVANCE_PAYMENT';
    const ADVANCE_REQUEST_ID = 'ADVANCE_REQUEST_ID';
    const AMOUNT = 'AMOUNT';
    const STATUS = 'STATUS';
    const PAYMENT_MODE = 'PAYMENT_MODE';
    const PAYAMENT_DATE = 'PAYAMENT_DATE';
    const NEP_YEAR = 'NEP_YEAR';
    const NEP_MONTH = 'NEP_MONTH';
    const REF_NEP_YEAR = 'REF_NEP_YEAR';
    const REF_NEP_MONTH = 'REF_NEP_MONTH';
    const CREATED_BY = 'CREATED_BY';
    const CREATED_DATE = 'CREATED_DATE';
    const MODIFIED_BY = 'MODIFIED_BY';
    const MODIFIED_DATE = 'MODIFIED_DATE';
    const ID = 'ID';


    public $advanceRequestId;
    public $amount;
    public $status;
    public $paymentMode;
    public $paymentDate;
    public $nepYear;
    public $nepMonth;
    public $refNepYear;
    public $refNepMonth;
    public $createdBy;
    public $createdDate;
    public $modifiedBy;
    public $modifiedDate;
    public $id;
    public $mappings = [
        'advanceRequestId' => self::ADVANCE_REQUEST_ID,
        'amount' => self::AMOUNT,
        'status' => self::STATUS,
        'paymentMode' => self::PAYMENT_MODE,
        'paymentDate' => self::PAYAMENT_DATE,
        'nepYear' => self::NEP_YEAR,
        'nepMonth' => self::NEP_MONTH,
        'refNepYear' => self::REF_NEP_YEAR,
        'refNepMonth' => self::REF_NEP_MONTH,
        'createdBy' => self::CREATED_BY,
        'createdDate' => self::CREATED_DATE,
        'modifiedBy' => self::MODIFIED_BY,
        'modifiedDate' => self::MODIFIED_DATE,
        'id' => self::ID
    ];
}
