<?php
namespace SelfService\Model;

use Application\Model\Model;

class LoanRequest extends Model{
    const TABLE_NAME = "HRIS_EMPLOYEE_LOAN_REQUEST";
    const LOAN_REQUEST_ID = "LOAN_REQUEST_ID";
    const EMPLOYEE_ID = "EMPLOYEE_ID";
    const REPAYMENT_MONTHS = "REPAYMENT_MONTHS";
    const LOAN_ID = "LOAN_ID";
    const REQUESTED_AMOUNT = "REQUESTED_AMOUNT";
    const REQUESTED_DATE = "REQUESTED_DATE";
    const LOAN_DATE = "LOAN_DATE";
    const REASON = "REASON";
    const STATUS = "STATUS";
    const APPROVED_AMOUNT = "APPROVED_AMOUNT";
    const INTEREST_RATE = "INTEREST_RATE";
    const RECOMMENDED_BY = "RECOMMENDED_BY";
    const RECOMMENDED_DATE = "RECOMMENDED_DATE";
    const RECOMMENDED_REMARKS = "RECOMMENDED_REMARKS";
    const APPROVED_BY = "APPROVED_BY";
    const APPROVED_DATE = "APPROVED_DATE";
    const APPROVED_REMARKS = "APPROVED_REMARKS";
    const DEDUCT_ON_SALARY = "DEDUCT_ON_SALARY";
     
    public $loanRequestId;
    public $employeeId;
    public $loanId;
    public $requestedDate;
    public $repaymentMonths;
    public $requestedAmount;
    public $loanDate;
    public $reason;
    public $status;
    public $approvedAmount;
    public $deductOnSalary;
    public $recommendedBy;
    public $recommendedDate;
    public $interestRate;
    public $recommendedRemarks;
    public $approvedBy;
    public $approvedDate;
    public $approvedRemarks;
    
    public $mappings = [
        'loanRequestId'=> self::LOAN_REQUEST_ID,
        'employeeId'=> self::EMPLOYEE_ID,
        'loanId'=> self::LOAN_ID,
        'requestedAmount'=> self::REQUESTED_AMOUNT,
        'requestedDate'=>self::REQUESTED_DATE,
        'loanDate'=>self::LOAN_DATE,
        'reason'=>self::REASON,
        'status'=>self::STATUS,
        'repaymentMonths' => self::REPAYMENT_MONTHS,
        'approvedAmount'=>self::APPROVED_AMOUNT,
        'interestRate'=>self::INTEREST_RATE,
        'recommendedBy'=>self::RECOMMENDED_BY,
        'recommendedDate'=>self::RECOMMENDED_DATE,
        'recommendedRemarks'=>self::RECOMMENDED_REMARKS,
        'approvedBy'=>self::APPROVED_BY,
        'approvedDate'=>self::APPROVED_DATE,
        'approvedRemarks'=>self::APPROVED_REMARKS,
        'deductOnSalary'=>self::DEDUCT_ON_SALARY
    ];
    
}