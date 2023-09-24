<?php

namespace Advance\Repository;

use Advance\Model\AdvanceSetupModel;
use Application\Helper\EntityHelper;
use Application\Model\Model;
use Application\Repository\RepositoryInterface;
use Zend\Db\Adapter\AdapterInterface;
use Zend\Db\Sql\Select;
use Zend\Db\TableGateway\TableGateway;

class AdvanceSetupRepository implements RepositoryInterface {

    private $adapter;
    private $gateway;

    public function __construct(AdapterInterface $adapter) {
        $this->adapter = $adapter;
        $this->gateway = new TableGateway(AdvanceSetupModel::TABLE_NAME, $adapter);
    }

    public function add(Model $model) {
        $this->gateway->insert($model->getArrayCopyForDB());
    }

    public function delete($id) {
        
    }

    public function edit(Model $model, $id) {
        $this->gateway->update($model->getArrayCopyForDB(), [AdvanceSetupModel::ADVANCE_ID => $id]);
    }

    public function fetchAll() {
        $sql = "SELECT ADVANCE_ID,ADVANCE_CODE,ADVANCE_ENAME,ADVANCE_LNAME,ALLOWED_TO,ALLOWED_MONTH_GAP,
                BOOLEAN_DESC(ALLOW_UNCLEARED_ADVANCE) AS ALLOW_UNCLEARED_ADVANCE ,
                MAX_SALARY_RATE,MAX_ADVANCE_MONTH,DEDUCTION_TYPE,DEDUCTION_RATE,DEDUCTION_IN,
                BOOLEAN_DESC(ALLOW_OVERRIDE_RATE) AS ALLOW_OVERRIDE_RATE,
                MIN_OVERRIDE_RATE,
                BOOLEAN_DESC(ALLOW_OVERRIDE_MONTH) AS ALLOW_OVERRIDE_MONTH,
                MAX_OVERRIDE_MONTH,
                BOOLEAN_DESC(OVERRIDE_RECOMMENDER_FLAG) AS OVERRIDE_RECOMMENDER_FLAG,
                BOOLEAN_DESC(OVERRIDE_APPROVER_FLAG) AS OVERRIDE_APPROVER_FLAG,
                STATUS_DESC(STATUS) AS STATUS FROM " . AdvanceSetupModel::TABLE_NAME;
        $rawResult = EntityHelper::rawQueryResult($this->adapter, $sql);
        return $rawResult;
    }

    public function fetchById($id) {
        $rawResult = $this->gateway->select(function(Select $select)use($id) {
            $select->columns(EntityHelper::getColumnNameArrayWithOracleFns(AdvanceSetupModel::class, [
                        AdvanceSetupModel::ADVANCE_ENAME,
                        AdvanceSetupModel::ADVANCE_LNAME,
                    ]), false);
            $select->where([AdvanceSetupModel::STATUS => EntityHelper::STATUS_ENABLED]);
            $select->where([AdvanceSetupModel::ADVANCE_ID => $id]);
            $select->order([AdvanceSetupModel::ADVANCE_ENAME => Select::ORDER_ASCENDING]);
        });
        return $rawResult->current();
    }

}
