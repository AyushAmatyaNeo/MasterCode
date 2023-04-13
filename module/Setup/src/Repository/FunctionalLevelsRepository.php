<?php

namespace Setup\Repository;

use Application\Model\Model;
use Application\Repository\RepositoryInterface;
use Setup\Model\FunctionalLevels;
use Setup\Model\FunctionalTypes;
use Zend\Db\Adapter\AdapterInterface;
use Zend\Db\Sql\Select;
use Zend\Db\Sql\Sql;
use Zend\Db\TableGateway\TableGateway;

class FunctionalLevelsRepository implements RepositoryInterface {

    private $tableGateway;
    private $adapter;

    public function __construct(AdapterInterface $adapter) {
        $this->tableGateway = new TableGateway(FunctionalLevels::TABLE_NAME, $adapter);
        $this->adapter = $adapter;
    }

    public function fetchAll() {
        $sql = new Sql($this->adapter);
        $select = $sql->select();
        $select->from(["D" => FunctionalLevels::TABLE_NAME])
                ->join(["T" => FunctionalTypes::TABLE_NAME], "D." . FunctionalLevels::FUNCTIONAL_TYPE_ID . "=" . "T." . FunctionalTypes::FUNCTIONAL_TYPE_ID, [FunctionalTypes::FUNCTIONAL_TYPE_EDESC], Select::JOIN_LEFT);
        $select->where(["D.STATUS= 'E'"]);        
        $select->order(["D." . FunctionalLevels::FUNCTIONAL_LEVEL_ID => Select::ORDER_ASCENDING]);
        $statement = $sql->prepareStatementForSqlObject($select);
        // echo '<pre>';print_r($statement);die;
        $result = $statement->execute();
        return $result;
    }

    public function fetchById($id) {
        $rowset = $this->tableGateway->select(function(Select $select)use($id) {
            $select->where([FunctionalLevels::FUNCTIONAL_LEVEL_ID => $id, FunctionalLevels::STATUS => 'E']);
        });
        return $rowset->current();
    }

    public function add(Model $model) {
        $this->tableGateway->insert($model->getArrayCopyForDB());
    }

    public function edit(Model $model, $id) {
        $array = $model->getArrayCopyForDB();
        $this->tableGateway->update($array, [FunctionalLevels::FUNCTIONAL_LEVEL_ID => $id]);
    }

    public function delete($id) {
        $this->tableGateway->update([FunctionalLevels::STATUS => 'D'], ["FUNCTIONAL_LEVEL_ID" => $id]);
    }

}
