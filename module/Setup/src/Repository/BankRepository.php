<?php

namespace Setup\Repository;

use Application\Helper\EntityHelper;
use Application\Model\Model;
use Application\Repository\RepositoryInterface;
use Setup\Model\Bank;
use Setup\Model\Logs;
use Zend\Db\Adapter\AdapterInterface;
use Zend\Db\Sql\Expression;
use Zend\Db\Sql\Select;
use Zend\Db\Sql\Sql;
use Zend\Db\TableGateway\TableGateway;

class BankRepository implements RepositoryInterface
{

    private $tableGateway;
    private $adapter;
    private $logTable;

    public function __construct(AdapterInterface $adapter)
    {
        $this->adapter = $adapter;
        $this->logTable = new TableGateway(Logs::TABLE_NAME, $adapter);
        $this->tableGateway = new TableGateway(Bank::TABLE_NAME, $adapter);
    }

    public function add(Model $model)
    {

        $this->tableGateway->insert($model->getArrayCopyForDB());
        $array = $model->getArrayCopyForDB();

        $branch = new BranchRepository($this->adapter);
        $logs = new Logs();
        $logs->module = 'Bank';
        $logs->operation = 'I';
        $logs->createdBy = $array['CREATED_BY'];
        $logs->createdDesc = 'bank id - ' . $array['BANK_ID'];
        $logs->tableDesc = 'HRIS_BANKS';
        $branch->insertLogs($logs);
    }

    public function edit(Model $model, $id)
    {
        $array = $model->getArrayCopyForDB();
        // echo '<pre>';print_r($array);die;
        unset($array[Bank::BANK_ID]);
        unset($array[Bank::CREATED_DT]);
        $this->tableGateway->update($array, [Bank::BANK_ID => $id]);
        $branch = new BranchRepository($this->adapter);
        $logs = new Logs();
        $logs->module = 'Bank';
        $logs->operation = 'U';
        $logs->modifiedBy = $array['CREATED_BY'];
        $logs->modifiedDesc = 'Bank id - ' . $id;
        $logs->tableDesc = 'HRIS_BANKS';

        $branch->updateLogs($logs);
    }

    public function deleteBank($id, $employeeId)
    {
        $this->tableGateway->update([Bank::STATUS => 'D'], [Bank::BANK_ID => $id]);
        $branch = new BranchRepository($this->adapter);
        $logs = new Logs();
        $logs->module = 'Bank';
        $logs->operation = 'D';
        $logs->deletedBy = $employeeId;
        $logs->deletedDesc = 'Bank id - ' . $id;
        $logs->tableDesc = 'HRIS_BANKS';
        $branch->deleteLogs($logs);
    }
    public function delete($id)
    {
        // $this->tableGateway->update([Bank::STATUS => 'D'], [Bank::BANK_ID => $id]);
    }
    public function fetchAll()
    {
        return $this->tableGateway->select();
    }

    public function fetchBankDetails()
    {
        $sql = "Select * from hris_banks where status='E'";
        $statement = $this->adapter->query($sql);
        $result = $statement->execute();
        $list = [];
        foreach ($result as $row) {
            array_push($list, $row);
        }
        return $list;
    }

    public function fetchById($id)
    {
        $row = $this->tableGateway->select(function (Select $select) use ($id) {
            $select->columns(EntityHelper::getColumnNameArrayWithOracleFns(Bank::class, [Bank::BANK_NAME]), false);
            $select->where([Bank::BANK_ID => $id]);
        });
        return $row->current();
    }
}
