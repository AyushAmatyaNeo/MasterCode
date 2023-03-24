<?php
namespace Training\Model;

use Application\Model\Model;

class EventAssign extends Model{
    const TABLE_NAME = "HRIS_EMPLOYEE_EVENT_ASSIGN";
    const EVENT_ID = "EVENT_ID";
    const EMPLOYEE_ID = "EMPLOYEE_ID";
    const REMARKS = "REMARKS";
    const STATUS = "STATUS";
    const CREATED_BY = "CREATED_BY";
    const CREATED_DATE = "CREATED_DATE";
    const MODIFIED_BY = "MODIFIED_BY";
    const MODIFIED_DATE = "MODIFIED_DATE";
    
    public $eventId;
    public $employeeId;
    public $remarks; 
    public $status;
    public $createdBy;
    public $createdDt;
    public $modifiedBy;
    public $modifiedDt;
    
    public $mappings = [
        'eventId'=>self::EVENT_ID,
        'employeeId'=>self::EMPLOYEE_ID,
        'remarks'=>self::REMARKS,
        'status'=>self::STATUS,
        'createdBy'=>self::CREATED_BY,
        'createdDt'=>self::CREATED_DATE,
        'modifiedBy'=>self::MODIFIED_BY,
        'modifiedDt'=>self::MODIFIED_DATE
    ];
}