<?php

namespace Payroll\Repository;

use Application\Helper\EntityHelper;
use Application\Model\Model;
use Application\Repository\RepositoryInterface;
use Payroll\Model\VariancePayhead;
use Payroll\Model\VarianceSetup;
use Zend\Db\Adapter\AdapterInterface;
use Zend\Db\Sql\Select;
use Zend\Db\TableGateway\TableGateway;

class VariancePayHeadRepo implements RepositoryInterface
{
    private $adapter;
    private $tableGateway;

    public function __construct(AdapterInterface $adapter)
    {
        $this->adapter=$adapter;
        $this->tableGateway = new TableGateway(VariancePayhead::TABLE_NAME, $adapter);
    }

    public function add(Model $model)
    {
        return $this->tableGateway->insert($model->getArrayCopyForDB());
    }

    public function edit(Model $model, $id)
    {
//        return $this->tableGateway->update($model->getArrayCopyForDB(),[FlatValue::FLAT_ID=>$id]);
    }

    public function fetchAll()
    {
//        return $this->tableGateway->select(function(Select $select){
//            $select->columns(EntityHelper::getColumnNameArrayWithOracleFns(VarianceSetup::class,
//                    [VarianceSetup::VARIANCE_NAME]),false);
//            $select->where([VarianceSetup::STATUS=>'E']);
//        });
    }

    public function fetchById($id)
    {
//        return $this->tableGateway->select(function(Select $select) use($id){
//            $select->columns(EntityHelper::getColumnNameArrayWithOracleFns(FlatValue::class,
//                    [FlatValue::FLAT_EDESC, FlatValue::FLAT_LDESC]),false);
//            $select->where([FlatValue::FLAT_ID=>$id]);    
//        })->current();
    }

    public function delete($id)
    {
    return $this->tableGateway->delete([VariancePayhead::VARIANCE_ID=>$id]);
    }
}