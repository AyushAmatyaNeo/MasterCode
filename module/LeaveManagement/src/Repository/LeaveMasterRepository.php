<?php

namespace LeaveManagement\Repository;

use Application\Helper\EntityHelper;
use Application\Model\Model;
use Application\Repository\RepositoryInterface;
use LeaveManagement\Model\LeaveMaster;
use Setup\Model\Company;
use Zend\Db\Adapter\AdapterInterface;
use Zend\Db\Sql\Expression;
use Zend\Db\Sql\Select;
use Setup\Repository\BranchRepository;
use Setup\Model\Logs;
use Zend\Db\Sql\Sql;
use Zend\Db\TableGateway\TableGateway;

class LeaveMasterRepository implements RepositoryInterface
{

    private $tableGateway;
    private $adapter;
    private $logTable;

    public function __construct(AdapterInterface $adapter)
    {
        $this->tableGateway = new TableGateway(LeaveMaster::TABLE_NAME, $adapter);
        $this->logTable = new TableGateway(Logs::TABLE_NAME, $adapter);
        $this->adapter = $adapter;
    }

    public function add(Model $model)
    {
        $this->tableGateway->insert($model->getArrayCopyForDB());
        $array = $model->getArrayCopyForDB();
        $branch = new BranchRepository($this->adapter);
        $logs = new Logs();
        $logs->module = 'Leave';
        $logs->operation = 'I';
        $logs->createdBy = $array['CREATED_BY'];
        $logs->createdDesc = 'leave id - ' . $array['LEAVE_ID'];
        $logs->tableDesc = 'HRIS_LEAVE_MASTER_SETUP';
        $branch->insertLogs($logs);
    }

    public function edit(Model $model, $id)
    {
        $array = $model->getArrayCopyForDB();
        unset($array[LeaveMaster::LEAVE_ID]);
        unset($array[LeaveMaster::CREATED_DT]);
        unset($array[LeaveMaster::STATUS]);
        if (!array_key_exists(LeaveMaster::DEFAULT_DAYS, $array)) {
            $array[LeaveMaster::DEFAULT_DAYS] = 0;
        }
        $this->tableGateway->update($array, [LeaveMaster::LEAVE_ID => $id]);
        $branch = new BranchRepository($this->adapter);
        $logs = new Logs();
        $logs->module = 'Leave';
        $logs->operation = 'U';
        $logs->modifiedBy = $array['MODIFIED_BY'];
        $logs->modifiedDesc = 'leave id - ' . $id;
        $logs->tableDesc = 'HRIS_LEAVE_MASTER_SETUP';

        $branch->updateLogs($logs);
    }

    public function fetchAll()
    {
        $sql = new Sql($this->adapter);
        $select = $sql->select();
        $select->columns(EntityHelper::getColumnNameArrayWithOracleFns(LeaveMaster::class, [LeaveMaster::LEAVE_ENAME], NULL, NULL, NULL, NULL, 'L', false), false);
        $select->from(['L' => LeaveMaster::TABLE_NAME]);
        $select->where(["L.STATUS='E'"]);
        $select->order(LeaveMaster::LEAVE_ENAME . " ASC");
        $statement = $sql->prepareStatementForSqlObject($select);
        $result = $statement->execute();
        return $result;
    }

    public function fetchById($id)
    {
        $rowset = $this->tableGateway->select(function (Select $select) use ($id) {
            $select->columns(EntityHelper::getColumnNameArrayWithOracleFns(LeaveMaster::class, [LeaveMaster::LEAVE_ENAME]), false);
            $select->where([LeaveMaster::LEAVE_ID => $id, LeaveMaster::STATUS => 'E']);
        });
        return $result = $rowset->current();
    }

    public function fetchActiveRecord()
    {
        return $rowset = $this->tableGateway->select(function (Select $select) {
            $select->where([LeaveMaster::STATUS => 'E']);
            $select->order(LeaveMaster::LEAVE_ENAME . " ASC");
        });
    }

    public function deleteLeave($id, $employeeId)
    {
        $this->tableGateway->update([LeaveMaster::STATUS => 'D'], [LeaveMaster::LEAVE_ID => $id]);
        $branch = new BranchRepository($this->adapter);
        $logs = new Logs();
        $logs->module = 'Leave';
        $logs->operation = 'D';
        $logs->deletedBy = $employeeId;
        $logs->deletedDesc = 'Leave id - ' . $id;
        $logs->tableDesc = 'HRIS_LEAVE_MASTER_SETUP';
        $branch->deleteLogs($logs);
    }

    public function delete($id)
    {
    }

    public function checkIfCashable(int $leaveId)
    {
        $leave = $this->tableGateway->select([LeaveMaster::LEAVE_ID => $leaveId, LeaveMaster::STATUS => 'E'])->current();
        return ($leave[LeaveMaster::CASHABLE] == 'Y') ? true : false;
    }

    public function getSubstituteLeave()
    {
        $result = $this->tableGateway->select([LeaveMaster::STATUS => 'E', LeaveMaster::IS_SUBSTITUTE => 'Y']);
        return $result->current();
    }
}
