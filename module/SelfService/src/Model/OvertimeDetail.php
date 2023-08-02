<?php
namespace SelfService\Model;

use Application\Model\Model;

class OvertimeDetail extends Model{
    const TABLE_NAME = "HRIS_OVERTIME_DETAIL";
    const DETAIL_ID = "DETAIL_ID";
    const OVERTIME_ID = "OVERTIME_ID";
    const START_TIME = "START_TIME";
    const END_TIME = "END_TIME";
    const TOTAL_HOUR = "TOTAL_HOUR";
    const STATUS = "STATUS";
    const CREATED_BY = "CREATED_BY";
    const CREATED_DATE = "CREATED_DATE";
    const MODIFIED_BY = "MODIFIED_BY";
    const MODIFIED_DATE = "MODIFIED_DATE";
    
    public $detailId;
    public $overtimeId;
    public $startTime;
    public $endTime;
    public $totalHour;
    public $status;
    public $createdBy;
    public $createdDate;
    public $modifiedBy;
    public $modifiedDate;
    
    public $mappings = [
        'detailId'=>self::DETAIL_ID,
        'overtimeId'=>self::OVERTIME_ID,
        'startTime'=>self::START_TIME,
        'endTime'=>self::END_TIME,
        'totalHour'=>self::TOTAL_HOUR,
        'status'=>self::STATUS,
        'createdBy'=>self::CREATED_BY,
        'createdDate'=>self::CREATED_DATE,
        'modifiedBy'=>self::MODIFIED_BY,
        'modifiedDate'=>self::MODIFIED_DATE
    ];
}
