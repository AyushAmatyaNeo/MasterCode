<?php
namespace SelfService\Model;

use Application\Model\Model;

class Overtime extends Model{
    const TABLE_NAME ="HRIS_OVERTIME";
    
    const OVERTIME_ID = "OVERTIME_ID";
    const EMPLOYEE_ID = "EMPLOYEE_ID";
    const OVERTIME_DATE = "OVERTIME_DATE";
    const REQUESTED_DATE = "REQUESTED_DATE";
    const DESCRIPTION = "DESCRIPTION";
    const REMARKS = "REMARKS";
    const STATUS = "STATUS";
    const RECOMMENDED_BY = "RECOMMENDED_BY";
    const RECOMMENDED_DATE = "RECOMMENDED_DATE";
    const RECOMMENDED_REMARKS = "RECOMMENDED_REMARKS";
    const APPROVED_BY ="APPROVED_BY";
    const APPROVED_DATE = "APPROVED_DATE";
    const APPROVED_REMARKS = "APPROVED_REMARKS";
    const MODIFIED_DATE = "MODIFIED_DATE";
    const TOTAL_HOUR = "TOTAL_HOUR";
    
    public $overtimeId;
    public $employeeId;
    public $overtimeDate;
    public $requestedDate;
    public $description;
    public $remarks;
    public $status;
    public $recommendedBy;
    public $recommendedDate;
    public $recommendedRemarks;
    public $approvedBy;
    public $approvedDate;
    public $approvedRemarks;
    public $modifiedDate;
    public $allTotalHour;
    
    public $mappings = [
        'overtimeId'=>self::OVERTIME_ID,
        'employeeId'=>self::EMPLOYEE_ID,
        'overtimeDate'=>self::OVERTIME_DATE,
        'requestedDate'=>self::REQUESTED_DATE,
        'description'=>self::DESCRIPTION,
        'remarks'=>self::REMARKS,
        'status'=>self::STATUS,
        'recommendedBy'=>self::RECOMMENDED_BY,
        'recommendedDate'=>self::RECOMMENDED_DATE,
        'recommendedRemarks'=>self::RECOMMENDED_REMARKS,
        'approvedBy'=>self::APPROVED_BY,
        'approvedDate'=>self::APPROVED_DATE,
        'approvedRemarks'=>self::APPROVED_REMARKS,
        'modifiedDate'=>self::MODIFIED_DATE,
        'allTotalHour'=>self::TOTAL_HOUR
    ];
}