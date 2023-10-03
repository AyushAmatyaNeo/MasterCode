<?php

namespace LeaveManagement\Model;

use Application\Model\Model;

class LeaveApply extends Model
{

    const TABLE_NAME = "HRIS_EMPLOYEE_LEAVE_REQUEST";
    const EMPLOYEE_ID = "EMPLOYEE_ID";
    const LEAVE_ID = "LEAVE_ID";
    const START_DATE = "START_DATE";
    const END_DATE = "END_DATE";
    const NO_OF_DAYS = "NO_OF_DAYS";
    const STATUS = "STATUS";
    const RECOMMENDED_BY = "RECOMMENDED_BY";
    const RECOMMENDED_DT = "RECOMMENDED_DT";
    const APPROVED_BY = "APPROVED_BY";
    const APPROVED_DT = "APPROVED_DT";
    const HALF_DAY = "HALF_DAY";
    const REQUESTED_DT = "REQUESTED_DT";
    const ID = "ID";
    const REMARKS = "REMARKS";
    const RECOMMENDED_REMARKS = "RECOMMENDED_REMARKS";
    const APPROVED_REMARKS = "APPROVED_REMARKS";
    const CREATED_BY = "CREATED_BY";
    const MODIFIED_DT = "MODIFIED_DT";
    const GRACE_PERIOD = "GRACE_PERIOD";
    const CANCEL_REC_BY = "CANCEL_REC_BY";
    const CANCEL_APP_BY = "CANCEL_APP_BY";
    const CANCEL_REC_DT = "CANCEL_REC_DT";
    const CANCEL_APP_DT = "CANCEL_APP_DT";
    const SUB_REF_ID = "SUB_REF_ID";
    const HARDCOPY_SIGNED_FLAG = "HARDCOPY_SIGNED_FLAG";

    public $id;
    public $employeeId;
    public $leaveId;
    public $startDate;
    public $endDate;
    public $noOfDays;
    public $status;
    public $recommendedBy;
    public $recommendedDt;
    public $approvedBy;
    public $approvedDt;
    public $halfDay;
    public $requestedDt;
    public $remarks;
    public $recommendedRemarks;
    public $approvedRemarks;
    public $modifiedDt;
    public $gracePeriod;
    public $cancelRecBy;
    public $cancelAppBy;
    public $cancelRecDt;
    public $cancelAppDt;
    public $subRefId;
    public $createdBy;
    public $hardcopySignedFlag;
    public $mappings = [
        'id' => self::ID,
        'employeeId' => self::EMPLOYEE_ID,
        'leaveId' => self::LEAVE_ID,
        'startDate' => self::START_DATE,
        'endDate' => self::END_DATE,
        'noOfDays' => self::NO_OF_DAYS,
        'status' => self::STATUS,
        'recommendedBy' => self::RECOMMENDED_BY,
        'recommendedDt' => self::RECOMMENDED_DT,
        'approvedBy' => self::APPROVED_BY,
        'approvedDt' => self::APPROVED_DT,
        'halfDay' => self::HALF_DAY,
        'requestedDt' => self::REQUESTED_DT,
        'remarks' => self::REMARKS,
        'recommendedRemarks' => self::RECOMMENDED_REMARKS,
        'approvedRemarks' => self::APPROVED_REMARKS,
        'modifiedDt' => self::MODIFIED_DT,
        'gracePeriod' => self::GRACE_PERIOD,
        'cancelRecBy' => self::CANCEL_APP_BY,
        'cancelAppBy' => self::CANCEL_REC_BY,
        'cancelRecDt' => self::CANCEL_REC_DT,
        'cancelAppDt' => self::CANCEL_APP_DT,
        'subRefId' => self::SUB_REF_ID,
        'createdBy' => self::CREATED_BY,
        'hardcopySignedFlag' => self::HARDCOPY_SIGNED_FLAG
    ];
}
