<?php
/**
 * Created by PhpStorm.
 * User: ukesh
 * Date: 9/11/16
 * Time: 4:55 PM
 */

namespace HolidayManagement\Model;


use Application\Model\Model;

class Holiday extends Model
{
    const TABLE_NAME="HR_HOLIDAY_MASTER_SETUP";

    const HOLIDAY_ID="HOLIDAY_ID";
    const HOLIDAY_CODE="HOLIDAY_CODE";
    const GENDER_ID="GENDER_ID";
    const BRANCH_ID="BRANCH_ID";
    const HOLIDAY_ENAME="HOLIDAY_ENAME";
    const HOLIDAY_LNAME="HOLIDAY_LNAME";
    const START_DATE="START_DATE";
    const END_DATE="END_DATE";
    const HALFDAY="HALFDAY";
    const FISCAL_YEAR="FISCAL_YEAR";
    const CREATED_DT="CREATED_DT";
    const MODIFIED_DT="MODIFIED_DT";
    const STATUS="STATUS";
    const REMARKS="REMARKS";


    public $holidayId;
    public $holidayCode;
    public $genderId;
    public $branchId;
    public $holidayEname;
    public $holidayLname;
    public $startDate;
    public $endDate;
    public $halfday;
    public $fiscalYear;

    public $createdDt;
    public $modifiedDt;
    public $status;
    public $remarks;

    public $mappings = [
        'holidayId'=>self::HOLIDAY_ID,
        'holidayCode'=>self::HOLIDAY_CODE,
        'genderId'=>self::GENDER_ID,
        'branchId'=>self::BRANCH_ID,
        'holidayEname'=>self::HOLIDAY_ENAME,
        'holidayLname'=>self::HOLIDAY_LNAME,
        'startDate'=>self::START_DATE,
        'endDate'=>self::END_DATE,
        'halfday'=>self::HALFDAY,
        'fiscalYear'=>self::FISCAL_YEAR,
        'createdDt'=>self::CREATED_DT,
        'modifiedDt'=>self::MODIFIED_DT,
        'status'=>self::STATUS,
        'remarks'=>self::REMARKS
    ];
}