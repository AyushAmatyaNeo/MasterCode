<?php

namespace Setup\Model;

use Application\Model\Model;

class TravelExpenseClass extends Model{

    const TABLE_NAME="HRIS_TRAVELS_EXPENSES_CATEGORY";
    const ID="ID";
    const CATEGORY_NAME="CATEGORY_NAME";
    const ALLOWANCE_PERCENTAGE="ALLOWANCE_PERCENTAGE";
    const STATUS="STATUS";
    const CREATED_DT="CREATED_DT";
    const CREATED_BY="CREATED_BY";
   

    public $id;
    public $categoryName;
    public $allowancePercentage;
    public $status;
    public $createdDt;
    public $createdBy;
    

    public $mappings=[
        'id'=>self::ID,
        'categoryName'=>self::CATEGORY_NAME,
        'allowancePercentage'=>self::ALLOWANCE_PERCENTAGE,
        'status'=>self::STATUS,
        'createdDt'=>self::CREATED_DT,
        'createdBy'=>self::CREATED_BY,
       

    ];
}

