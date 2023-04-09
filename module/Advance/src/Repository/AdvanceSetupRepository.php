<?php

namespace Advance\Repository;

use Advance\Model\AdvanceSetupModel;
use Application\Helper\EntityHelper;
use Application\Model\Model;
use Application\Repository\RepositoryInterface;
use Zend\Db\Adapter\AdapterInterface;
use Zend\Db\Sql\Select;
use Zend\Db\TableGateway\TableGateway;

class AdvanceSetupRepository implements RepositoryInterface
{

    private $adapter;
    private $gateway;

    public function __construct(AdapterInterface $adapter)
    {
        $this->adapter = $adapter;
        $this->gateway = new TableGateway(AdvanceSetupModel::TABLE_NAME, $adapter);
    }

    public function add(Model $model)
    {
        $this->gateway->insert($model->getArrayCopyForDB());
    }

    public function delete($id)
    {
        $this->gateway->update([AdvanceSetupModel::STATUS => 'D'], [AdvanceSetupModel::ADVANCE_ID => $id]);
    }

    public function edit(Model $model, $id)
    {
        $this->gateway->update($model->getArrayCopyForDB(), [AdvanceSetupModel::ADVANCE_ID => $id]);
    }

    public function fetchAll()
    {
        $sql = "SELECT
        advance_id,
        advance_code,
        advance_ename,
        advance_lname,
        allowed_to,
        allowed_month_gap,
        boolean_desc(allow_uncleared_advance)   AS allow_uncleared_advance,
        max_salary_rate,
        max_advance_month,
        deduction_type,
        deduction_rate,
        deduction_in,
        boolean_desc(allow_override_rate)       AS allow_override_rate,
        min_override_rate,
        boolean_desc(allow_override_month)      AS allow_override_month,
        max_override_month,
        boolean_desc(override_recommender_flag) AS override_recommender_flag,
        boolean_desc(override_approver_flag)    AS override_approver_flag,
        status_desc(status)                     AS status
    FROM
        hris_advance_master_setup where status='E'";
        $rawResult = EntityHelper::rawQueryResult($this->adapter, $sql);
        return $rawResult;
    }

    public function fetchById($id)
    {
        $rawResult = $this->gateway->select(function (Select $select) use ($id) {
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
