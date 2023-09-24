<?php

namespace Setup\Repository;

use Application\Helper\EntityHelper;
use Application\Model\Model;
use Application\Repository\RepositoryInterface;
use Setup\Model\level;
use Zend\Db\Adapter\AdapterInterface;
use Zend\Db\Sql\Expression;
use Zend\Db\Sql\Select;
use Zend\Db\Sql\Sql;
use Zend\Db\TableGateway\TableGateway;

class LevelRepository implements RepositoryInterface {

    private $tableGateway;
    private $adapter;

    public function __construct(AdapterInterface $adapter) {
        $this->adapter = $adapter;
        $this->tableGateway = new TableGateway(level::TABLE_NAME, $adapter);
    }

    public function add(Model $model) {
        $this->tableGateway->insert($model->getArrayCopyForDB());
    }

    public function edit(Model $model, $id) {
        $array = $model->getArrayCopyForDB();
        // echo '<pre>';print_r($array);die;
        $this->tableGateway->update($array, [level::LEVEL_ID => $id]);
    }

    public function delete($id) {
        $this->tableGateway->update([level::STATUS => 'D'], [level::LEVEL_ID => $id]);
    }

    public function fetchAll() {
        return $this->tableGateway->select();
    }

    public function fetchActiveRecords() {
       $sql="Select * from hris_levels where status='E'";
       $statement=$this->adapter->query($sql);
       $result=$statement->execute();
       return $result;
    }

    public function fetchById($id) {
        $row = $this->tableGateway->select(function(Select $select)use($id) {
            $select->columns(EntityHelper::getColumnNameArrayWithOracleFns(level::class, [level::LEVEL_NAME]), false);
            $select->where([level::LEVEL_ID => $id]);
        });
        return $row->current();
    }

   

}



