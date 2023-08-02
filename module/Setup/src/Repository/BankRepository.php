<?php

namespace Setup\Repository;

use Application\Helper\EntityHelper;
use Application\Model\Model;
use Application\Repository\RepositoryInterface;
use Setup\Model\Bank;
use Zend\Db\Adapter\AdapterInterface;
use Zend\Db\Sql\Expression;
use Zend\Db\Sql\Select;
use Zend\Db\Sql\Sql;
use Zend\Db\TableGateway\TableGateway;

class BankRepository implements RepositoryInterface {

    private $tableGateway;
    private $adapter;

    public function __construct(AdapterInterface $adapter) {
        $this->adapter = $adapter;
        $this->tableGateway = new TableGateway(Bank::TABLE_NAME, $adapter);
    }

    public function add(Model $model) {
        // echo '<pre>';print_r($model );die;
        $this->tableGateway->insert($model->getArrayCopyForDB());
    }

    public function edit(Model $model, $id) {
        $array = $model->getArrayCopyForDB();
        // echo '<pre>';print_r($array);die;
        unset($array[Bank::BANK_ID]);
        unset($array[Bank::CREATED_DT]);
        $this->tableGateway->update($array, [Bank::BANK_ID => $id]);
    }

    public function delete($id) {
        $this->tableGateway->update([Bank::STATUS => 'D'], [Bank::BANK_ID => $id]);
    }

    public function fetchAll() {
        return $this->tableGateway->select();
    }

    public function fetchBankDetails() {
        $sql ="Select * from hris_banks where status='E'";
        $statement=$this->adapter->query($sql);
        $result=$statement->execute();
        $list = [];
        foreach ($result as $row) {
            array_push($list, $row);
        }
        return $list;
    }

    public function fetchById($id) {
        $row = $this->tableGateway->select(function(Select $select)use($id) {
            $select->columns(EntityHelper::getColumnNameArrayWithOracleFns(Bank::class, [Bank::BANK_NAME]), false);
            $select->where([Bank::BANK_ID => $id]);
        });
        return $row->current();
    }

}
