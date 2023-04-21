<?php
namespace Loan\Model;

use Application\Model\Model;

class LoanClosing extends Model{
    const TABLE_NAME = "HRIS_LOAN_CASH_PAYMENT";
    const ID = "ID";
	const LOAN_ID = "LOAN_ID";
	const EMPLOYEE_ID = "EMPLOYEE_ID";
	const NEW_LOAN_REQ_ID = "NEW_LOAN_REQ_ID";
    const PAYMENT_DATE = "PAYMENT_DATE";
    const PAYMENT_AMOUNT = "PAYMENT_AMOUNT";
    const INTEREST = "INTEREST";
    const RECEIPT_NO = "RECEIPT_NO";   
	const REMARKS = "REMARKS";
	const CREATED_DT = "CREATED_DT";
	const CREATED_BY = "CREATED_BY";
	const MODIFIED_BY = "MODIFIED_BY";
	const MODIFIED_DT = "MODIFIED_DT";
	const DELETED_DT = "DELETED_DT";
	const DELETED_BY = "DELETED_BY";
	const TYPE = "TYPE";
     
    public $id;
	public $loan_id;
	public $employee_id;
	public $newLoanReqId;
    public $paymentDate;
    public $paymentAmount;
    public $interest;
    public $receiptNo;
    public $remarks;
	public $created_dt;
    public $created_by;
    public $modified_dt;
    public $modified_by;
    public $deleted_dt;
    public $deleted_by;
	public $type;
     
    public $mappings = [
        'id'=> self::ID,
		'loan_id'=>self::LOAN_ID,
        'employee_id'=> self::EMPLOYEE_ID,
		'newLoanReqId'=>self::NEW_LOAN_REQ_ID,
        'paymentDate'=> self::PAYMENT_DATE,
		'paymentAmount'=> self::PAYMENT_AMOUNT,
        'interest'=>self::INTEREST,
        'receiptNo'=>self::RECEIPT_NO,
        'remarks'=>self::REMARKS,
		'created_dt'=> self::CREATED_DT,
        'created_by'=>self::CREATED_BY,
        'modified_dt'=>self::MODIFIED_BY,
        'modified_by'=>self::MODIFIED_BY,
		'deleted_dt'=>self::DELETED_DT,
        'deleted_by'=>self::DELETED_BY,
		'type'=>self::TYPE
    ];
}