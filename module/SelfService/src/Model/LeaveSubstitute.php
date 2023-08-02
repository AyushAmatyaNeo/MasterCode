<?php
namespace SelfService\Model;

use Application\Model\Model;

class LeaveSubstitute extends Model{
    const TABLE_NAME = "HRIS_LEAVE_SUBSTITUTE";
   
    const LEAVE_REQUEST_ID = "LEAVE_REQUEST_ID";
    const EMPLOYEE_ID = "EMPLOYEE_ID";
    const REMARKS = "REMARKS";
    const CREATED_BY = "CREATED_BY";
    const CREATED_DATE = "CREATED_DATE";
    const APPROVED_FLAG = "APPROVED_FLAG";
    const APPROVED_DATE = "APPROVED_DATE";
    const STATUS = "STATUS";
    
    public $leaveRequestId;
    public $employeeId;
    public $remarks;
    public $createdBy;
    public $createdDate;
    public $approvedFlag;
    public $approvedDate;
    public $status;
    
    public $mappings= [
        'leaveRequestId'=>self::LEAVE_REQUEST_ID,
        'employeeId'=>self::EMPLOYEE_ID,
        'remarks' => self::REMARKS,
        'createdBy'=>self::CREATED_BY,
        'createdDate'=>self::CREATED_DATE,
        'approvedFlag'=>self::APPROVED_FLAG,
        'approvedDate'=>self::APPROVED_DATE,
        'status'=>self::STATUS
    ];
}