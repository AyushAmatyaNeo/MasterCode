<?php

namespace Setup\Repository;

use Application\Helper\EntityHelper;
use Application\Model\Model;
use Application\Repository\RepositoryInterface;
use Setup\Model\Branch;
use Setup\Model\Logs;
use Setup\Model\Company;
use Zend\Db\Adapter\AdapterInterface;
use Zend\Db\Sql\Join;
use Zend\Db\Sql\Predicate\Expression;
use Zend\Db\Sql\Select;
use Zend\Db\Sql\Sql;
use Zend\Db\TableGateway\TableGateway;
use Setup\Model\HrEmployees;

class BranchRepository implements RepositoryInterface
{

    private $tableGateway;
    private $logTable;
    private $employeeId;
    private $storageData;
    private $adapter;

    public function __construct(AdapterInterface $adapter)
    {
        $this->tableGateway = new TableGateway(Branch::TABLE_NAME, $adapter);
        $this->logTable = new TableGateway(Logs::TABLE_NAME, $adapter);
        $this->employeeId = $this->storageData['employee_id'];
        $this->adapter = $adapter;
    }

    public function add(Model $model)
    {
        $array = $model->getArrayCopyForDB();
        $this->tableGateway->insert($model->getArrayCopyForDB());
        $logs = new Logs();
        $logs->module = 'Branch';
        $logs->operation = 'I';
        $logs->createdBy = $array['CREATED_BY'];
        $logs->createdDesc = 'Branch id - ' . $array['BRANCH_ID'];
        $logs->tableDesc = 'HRIS_BRANCHES';
        $this->insertLogs($logs);
    }
    public function insertLogs(Model $model)
    {

        $array = $model->getArrayCopyForDB();
        $sql = "INSERT INTO hris_logs (
            operation,
            module,
            table_desc,
            ip_address,
            host_name,
            created_by,
            created_dt,
            created_desc
        ) VALUES (
            '$array[OPEARTION]',
            '$array[MODULE]',
            '$array[TABLE_DESC]',
            (SELECT sys_context('USERENV', 'IP_ADDRESS')  FROM dual),
            (SELECT sys_context('USERENV', 'HOST') FROM dual),
            $array[CREATED_BY],
            SYSTIMESTAMP,
            '$array[CREATED_DESC]'
        )";
        $statement = $this->adapter->query($sql);
        $statement->execute()->current();
    }
    public function edit(Model $model, $id)
    {
        $array = $model->getArrayCopyForDB();
        $this->tableGateway->update($array, [Branch::BRANCH_ID => $id]);
        $logs = new Logs();
        $logs->module = 'Branch';
        $logs->operation = 'U';
        $logs->modifiedBy = $array['MODIFIED_BY'];
        $logs->modifiedDesc = 'Branch id - ' . $id;
        $logs->tableDesc = 'HRIS_BRANCHES';

        $this->updateLogs($logs);
    }
    public function updateLogs(Model $model)
    {
        $array = $model->getArrayCopyForDB();
        $sql = "INSERT INTO hris_logs (
            operation,
            module,
            table_desc,
            ip_address,
            host_name,
            modified_by,
            modified_dt,
            modified_desc
        ) VALUES (
            '$array[OPEARTION]',
            '$array[MODULE]',
            '$array[TABLE_DESC]',
            (SELECT sys_context('USERENV', 'IP_ADDRESS')  FROM dual),
            (SELECT sys_context('USERENV', 'HOST') FROM dual),
            $array[MODIFIED_BY],
            SYSTIMESTAMP,
            '$array[MODIFIED_DESC]'
        )";
        $statement = $this->adapter->query($sql);
        $statement->execute()->current();
    }
    public function fetchAll()
    {
        return $this->tableGateway->select(function (Select $select) {
            $select->columns(EntityHelper::getColumnNameArrayWithOracleFns(Branch::class, [Branch::BRANCH_NAME]), false);
            $select->where([Branch::STATUS => EntityHelper::STATUS_ENABLED]);
            $select->order([Branch::BRANCH_NAME => Select::ORDER_ASCENDING]);
        });
    }

    public function fetchAllWithCompany()
    {
        $sql = new Sql($this->adapter);
        $select = $sql->select();
        $select->from(['B' => Branch::TABLE_NAME]);
        $select->columns(EntityHelper::getColumnNameArrayWithOracleFns(Branch::class, [Branch::BRANCH_NAME], null, null, null, null, 'B'), false);
        $companyIdKey = Company::COMPANY_ID;
        $companyNameKey = Company::COMPANY_NAME;
        $select->join(['C' => Company::TABLE_NAME], "C.{$companyIdKey} = B.{$companyIdKey}", [Company::COMPANY_NAME => new Expression("(C.{$companyNameKey})")], Join::JOIN_LEFT);
        $select->where(['B.' . Branch::STATUS => EntityHelper::STATUS_ENABLED]);
        $select->order([
            'B.' . Branch::BRANCH_NAME => Select::ORDER_ASCENDING,
            'C.' . Company::COMPANY_NAME => Select::ORDER_ASCENDING
        ]);
        $statement = $sql->prepareStatementForSqlObject($select);
        $return = $statement->execute();
        return $return;
    }

    public function fetchById($id)
    {
        $rowset = $this->tableGateway->select([Branch::BRANCH_ID => $id]);
        return $rowset->current();
    }

    public function delete($id)
    {
    }
    public function deleteBranch($id, $employeeId)
    {
        $this->tableGateway->update([Branch::STATUS => 'D'], [Branch::BRANCH_ID => $id]);
        $logs = new Logs();
        $logs->module = 'Branch';
        $logs->operation = 'D';
        $logs->deletedBy = $employeeId;
        $logs->deletedDesc = 'Branch id - ' . $id;
        $logs->tableDesc = 'HRIS_BRANCHES';
        $this->deleteLogs($logs);
    }
    public function deleteLogs(Model $model)
    {
        $array = $model->getArrayCopyForDB();
        $sql = "INSERT INTO hris_logs (
            operation,
            module,
            table_desc,
            ip_address,
            host_name,
            deleted_by,
            deleted_dt,
            deleted_desc
        ) VALUES (
            '$array[OPEARTION]',
            '$array[MODULE]',
            '$array[TABLE_DESC]',
            (SELECT sys_context('USERENV', 'IP_ADDRESS')  FROM dual),
            (SELECT sys_context('USERENV', 'HOST') FROM dual),
            $array[DELETED_BY],
            SYSTIMESTAMP,
            '$array[DELETED_DESC]'
        )";
        $statement = $this->adapter->query($sql);
        $statement->execute()->current();
    }
    public function fetchAllWithBranchManager()
    {
        $sql = new Sql($this->adapter);
        $select = $sql->select();
        $select->from(['B' => Branch::TABLE_NAME]);
        $select->columns(EntityHelper::getColumnNameArrayWithOracleFns(Branch::class, [Branch::BRANCH_NAME], null, null, null, null, 'B'), false);
        $companyIdKey = Company::COMPANY_ID;
        $companyNameKey = Company::COMPANY_NAME;
        $employeeIdKey = HrEmployees::EMPLOYEE_ID;
        $branchManagerIdKey = Branch::BRANCH_MANAGER_ID;
        $employeeNameKey = HrEmployees::FULL_NAME;
        $select->join(['C' => Company::TABLE_NAME], "C.{$companyIdKey} = B.{$companyIdKey}", [Company::COMPANY_NAME => new Expression("INITCAP(C.{$companyNameKey})")], Join::JOIN_LEFT);
        $select->join(['E' => HrEmployees::TABLE_NAME], "E.{$employeeIdKey} = B.{$branchManagerIdKey}", [HrEmployees::FULL_NAME => new Expression("INITCAP(E.{$employeeNameKey})")], Join::JOIN_LEFT);
        $select->where(['B.' . Branch::STATUS => EntityHelper::STATUS_ENABLED]);
        $select->order([
            'B.' . Branch::BRANCH_NAME => Select::ORDER_ASCENDING,
            'C.' . Company::COMPANY_NAME => Select::ORDER_ASCENDING
        ]);
        $statement = $sql->prepareStatementForSqlObject($select);
        $result = $statement->execute();
        return $result;
    }
}
