<?php

namespace Setup\Model;

use Application\Model\Model;

class Events extends Model {

    const TABLE_NAME = "HRIS_EVENT_MASTER_SETUP";
    const EVENT_ID = "EVENT_ID";
    const EVENT_CODE = "EVENT_CODE";
    const EVENT_NAME = "EVENT_NAME";
    const INSTITUTE_ID = "INSTITUTE_ID";
    const EVENT_TYPE = "EVENT_TYPE";
    const START_DATE = "START_DATE";
    const END_DATE = "END_DATE";
    const DURATION = "DURATION";
    const INSTRUCTOR_NAME = "INSTRUCTOR_NAME";
    const REMARKS = "REMARKS";
    const STATUS = "STATUS";
    const CREATED_DATE = "CREATED_DATE";
    const CREATED_BY = "CREATED_BY";
    const MODIFIED_DATE = "MODIFIED_DATE";
    const MODIFIED_BY = "MODIFIED_BY";
    const COMPANY_ID = "COMPANY_ID";
    const IS_WITHIN_COMPANY = "IS_WITHIN_COMPANY";
    const DAILY_EVENT_HOUR = "DAILY_EVENT_HOUR";
    const SHOW_AS_EVENT = "SHOW_AS_EVENT";

    public $eventId;
    public $eventCode;
    public $eventName;
    public $eventType;
    public $instituteId;
    public $startDate;
    public $endDate;
    public $duration;
    public $remarks;
    public $status;
    public $createdDate;
    public $createdBy;
    public $modifiedDate;
    public $modifiedBy;
    public $instructorName;
    public $companyId;
    public $isWithinCompany;
    public $dailyEventHour;
    public $showAsEvent;
    public $mappings = [
        'eventId' => self::EVENT_ID,
        'eventCode' => self::EVENT_CODE,
        'eventName' => self::EVENT_NAME,
        'eventType' => self::EVENT_TYPE,
        'instituteId' => self::INSTITUTE_ID,
        'startDate' => self::START_DATE,
        'endDate' => self::END_DATE,
        'duration' => self::DURATION,
        'createdDate' => self::CREATED_DATE,
        'createdBy' => self::CREATED_BY,
        'modifiedDate' => self::MODIFIED_DATE,
        'modifiedBy' => self::MODIFIED_BY,
        'instructorName' => self::INSTRUCTOR_NAME,
        'remarks' => self::REMARKS,
        'status' => self::STATUS,
        'companyId' => self::COMPANY_ID,
        'isWithinCompany' => self::IS_WITHIN_COMPANY,
        'dailyEventHour' => self::DAILY_EVENT_HOUR,
        'showAsEvent' => self::SHOW_AS_EVENT
    ];

}
