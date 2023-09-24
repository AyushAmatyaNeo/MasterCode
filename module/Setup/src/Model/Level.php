<?php 

namespace Setup\Model;

use Application\Model\Model;

class level extends Model{

    const TABLE_NAME="HRIS_LEVELS";
    const LEVEL_ID="LEVEL_ID";
    const LEVEL_CODE="LEVEL_CODE";
    const LEVEL_NAME="LEVEL_NAME";
    const AMOUNT="AMOUNT";
    const CREATED_DT="CREATED_DT";
    const CREATED_BY="CREATED_BY";
    const MODIFIED_DT="MODIFIED_DT";
    const MODIFIED_BY="MODIFIED_BY";
    const STATUS="STATUS";
    const REMARKS="REMARKS";


    public $levelId;
    public $levelCode;
    public $levelName;
    public $amount;
    public $createdDt;
    public $createdBy;
    public $modifiedDt;
    public $modifiedBy;
    public $status;
    public $remarks;

    public $mappings=[
        'levelId' =>self::LEVEL_ID,
        'levelCode'=>self::LEVEL_CODE,
        'levelName'=>self::LEVEL_NAME,
        'amount'=>self::AMOUNT,
        'createdDt'=>self::CREATED_DT,
        'createdBy'=>self::CREATED_BY,
        'modifiedDt'=>self::MODIFIED_DT,
        'modifiedBy'=>self::MODIFIED_BY,
        'status'=>self::STATUS,
        'remarks'=>self::REMARKS,
    ];
}








