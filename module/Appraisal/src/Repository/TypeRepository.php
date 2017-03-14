<?php
namespace Appraisal\Repository;

use Application\Repository\RepositoryInterface;
use Zend\Db\Adapter\AdapterInterface;
use Zend\Db\TableGateway\TableGateway;
use Zend\Db\Sql\Sql;
use Zend\Db\Sql\Select;
use Zend\Db\Sql\Expression;
use Appraisal\Model\Type;

class TypeRepository implements RepositoryInterface{
    private $tableGateway;
    private $adapter;
    
    public function __construct(AdapterInterface $adapter) {
        $this->tableGateway = new TableGateway(Type::TABLE_NAME,$adapter);
        $this->adapter = $adapter;
    }

    public function add(\Application\Model\Model $model) {
        $this->tableGateway->insert($model->getArrayCopyForDB());
    }

    public function delete($id) {
        $this->tableGateway->update([Type::STATUS=>'D'],[Type::APPRAISAL_TYPE_ID=>$id]);
    }

    public function edit(\Application\Model\Model $model, $id) {
        $array = $model->getArrayCopyForDB();
        unset($array[Type::APPRAISAL_TYPE_ID]);
        unset($array[Type::CREATED_DATE]);
        unset($array[Type::STATUS]);
        $this->tableGateway->update($array, [Type::APPRAISAL_TYPE_ID => $id]);
    }

    public function fetchAll() {
        $sql = new Sql($this->adapter);
        $select = $sql->select();
        $select->columns([
            new Expression("AT.APPRAISAL_TYPE_ID AS APPRAISAL_TYPE_ID"), 
            new Expression("AT.APPRAISAL_TYPE_CODE AS APPRAISAL_TYPE_CODE"),
            new Expression("AT.APPRAISAL_TYPE_EDESC AS APPRAISAL_TYPE_EDESC"), 
            new Expression("AT.APPRAISAL_TYPE_NDESC AS APPRAISAL_TYPE_NDESC")
            ], true);
        $select->from(['AT' => "HR_APPRAISAL_TYPE"])
                ->join(['ST' => 'HR_SERVICE_TYPES'], 'AT.SERVICE_TYPE_ID=ST.SERVICE_TYPE_ID', ["SERVICE_TYPE_NAME"], "left");
        
        $select->where(["AT.STATUS='E'"]);
        $select->order("AT.APPRAISAL_TYPE_EDESC");
        $statement = $sql->prepareStatementForSqlObject($select);
        $result = $statement->execute();
        return $result;
    }

    public function fetchById($id) {
        $rowset = $this->tableGateway->select([Type::APPRAISAL_TYPE_ID => $id, Type::STATUS => 'E']);
        return $result = $rowset->current();
    }

}