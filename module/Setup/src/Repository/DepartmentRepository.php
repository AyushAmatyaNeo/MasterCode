<?php

namespace Setup\Repository;

use Application\Model\Model;
use Application\Repository\RepositoryInterface;
use Setup\Model\Department;
use Zend\Db\Adapter\AdapterInterface;
use Zend\Db\TableGateway\TableGateway;

class DepartmentRepository implements RepositoryInterface
{
    private $tableGateway;
    
    public function __construct(AdapterInterface $adapter)
    {
        $this->tableGateway=new TableGateway(Department::TABLE_NAME,$adapter);

    }

    public function add(Model $model)
    {
        $this->tableGateway->insert($model->getArrayCopyForDB());
    }

    public function edit(Model $model,$id)
    {
        $temp=$model->getArrayCopyForDB();
        $this->tableGateway->update($temp,[Department::DEPARTMENT_ID=>$id]);
    }

    public function fetchAll()
    {
        return $this->tableGateway->select([Department::STATUS=>'E']);
    }

    public function fetchById($id)
    {
        $rowset= $this->tableGateway->select([Department::DEPARTMENT_ID=>$id,Department::STATUS=>'E']);
        return $rowset->current();
    }

    public function delete($id)
    {
    	$this->tableGateway->update([Department::STATUS=>'D'],[Department::DEPARTMENT_ID=>$id]);
    }
}