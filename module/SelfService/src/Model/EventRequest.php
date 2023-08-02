<?php

namespace SelfService\Model;

use Application\Model\Model;

class EventRequest extends Model {

    const TABLE_NAME = "HRIS_EMPLOYEE_EVENT_REQUEST";
    const REQUEST_ID = "REQUEST_ID";
    const REQUESTED_DATE = "REQUESTED_DATE";
    const EMPLOYEE_ID = "EMPLOYEE_ID";
    const EVENT_ID = "EVENT_ID";
    const TITLE = "TITLE";
    const DESCRIPTION = "DESCRIPTION";
    const EVENT_TYPE = "EVENT_TYPE";
    const START_DATE = "START_DATE";
    const END_DATE = "END_DATE";
    const STATUS = "STATUS";
    const RECOMMENDED_BY = "RECOMMENDED_BY";
    const RECOMMENDED_DATE = "RECOMMENDED_DATE";
    const RECOMMENDED_REMARKS = "RECOMMENDED_REMARKS";
    const APPROVED_BY = "APPROVED_BY";
    const APPROVED_DATE = "APPROVED_DATE";
    const APPROVED_REMARKS = "APPROVED_REMARKS";
    const MODIFIED_DATE = "MODIFIED_DATE";
    const DURATION = "DURATION";
    const REMARKS = "REMARKS";
    const IS_WITHIN_COMPANY = "IS_WITHIN_COMPANY";
    const DAILY_EVENT_HOUR = "DAILY_EVENT_HOUR";

    public $requestId;
    public $requestedDate;
    public $employeeId;
    public $eventId;
    public $title;
    public $description;
    public $eventType;
    public $startDate;
    public $endDate;
    public $status;
    public $recommendedBy;
    public $recommendedDate;
    public $recommendedRemarks;
    public $approvedBy;
    public $approvedDate;
    public $approvedRemarks;
    public $modifiedDate;
    public $duration;
    public $remarks;
    public $isWithinCompany;
    public $dailyEventHour;
    public $mappings = [
        'requestId' => self::REQUEST_ID,
        'requestedDate' => self::REQUESTED_DATE,
        'employeeId' => self::EMPLOYEE_ID,
        'eventId' => self::EVENT_ID,
        'title' => self::TITLE,
        'description' => self::DESCRIPTION,
        'eventType' => self::EVENT_TYPE,
        'startDate' => self::START_DATE,
        'endDate' => self::END_DATE,
        'status' => self::STATUS,
        'recommendedBy' => self::RECOMMENDED_BY,
        'recommendedDate' => self::RECOMMENDED_DATE,
        'recommendedRemarks' => self::RECOMMENDED_REMARKS,
        'approvedBy' => self::APPROVED_BY,
        'approvedDate' => self::APPROVED_DATE,
        'approvedRemarks' => self::APPROVED_REMARKS,
        'modifiedDate' => self::MODIFIED_DATE,
        'duration' => self::DURATION,
        'remarks' => self::REMARKS,
        'isWithinCompany' => self::IS_WITHIN_COMPANY,
        'dailyEventHour' => self::DAILY_EVENT_HOUR
    ];

}
