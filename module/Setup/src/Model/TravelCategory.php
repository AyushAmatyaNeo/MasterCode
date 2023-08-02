<?php

namespace Setup\Model;

use Application\Model\Model;

class TravelCategory extends Model{

    const TABLE_NAME="HRIS_TRAVEL_CATEGORY";
    const ID="ID";
    const LEVEL_NO="LEVEL_NO";
    const STATUS="STATUS";
    const ADVANCE_AMOUNT="ADVANCE_AMOUNT";
    const DAILY_ALLOWANCE_AMOUNT="DAILY_ALLOWANCE_AMOUNT";
    const CREATED_DT="CREATED_DT";
    const CREATED_BY="CREATED_BY";
    const MODIFIED_BY="MODIFIED_BY";
    const MODIFIED_DT="MODIFIED_DT";
    const DELETED_DT="DELETED_DT";
    const DELETED_BY="DELETED_BY";

    public $id;
    public $positionId;
    public $status;
    public $advanceAmount;
    public $dailyAllowance;
    public $createdDt;
    public $createdBy;
    public $modifiedDt;
    public $modifiedBy;
    public $deletedDt;
    public $deletedBy;

    public $mappings=[
        'id'=>self::ID,
        'positionId'=>self::LEVEL_NO,
        'status'=>self::STATUS,
        'advanceAmount'=>self::ADVANCE_AMOUNT,
        'dailyAllowance'=>self::DAILY_ALLOWANCE_AMOUNT,
        'createdDt'=>self::CREATED_DT,
        'createdBy'=>self::CREATED_BY,
        'modifiedDt'=>self::MODIFIED_DT,
        'modifiedBy'=>self::MODIFIED_BY,
        'deletedDt'=>self::DELETED_DT,
        'deletedBy'=>self::DELETED_BY

    ];
}

