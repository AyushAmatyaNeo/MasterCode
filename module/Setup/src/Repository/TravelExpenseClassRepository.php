<?php

namespace Setup\Repository;

use Application\Helper\EntityHelper;
use Application\Helper\Helper;
use Application\Model\Model;
use Application\Repository\RepositoryInterface;
use Setup\Model\TravelExpenseClass;
use Zend\Db\Adapter\AdapterInterface;
use Zend\Db\Sql\Expression;
use Zend\Db\Sql\Sql;
use Zend\Db\TableGateway\TableGateway;

class TravelExpenseClassRepository implements RepositoryInterface {

    protected $tableGateway;
    protected $adapter;

    public function __construct(AdapterInterface $adapter) {
        $this->tableGateway = new TableGateway(TravelExpenseClass::TABLE_NAME, $adapter);
        $this->adapter = $adapter;
    }


    public function fetchAll()
    {
   
    return $this->tableGateway->select();
    
    }

    public function add(Model $model) {
        $this->tableGateway->insert($model->getArrayCopyForDB());
       // echo '<pre>';print_r('insert($model->getArrayCopyForDB())');die;
    }

    public function delete($id) {
        // $this->tableGateway->update([TravelExpenseClass::STATUS => 'D'], [TravelExpenseClass::ID => $id]);
        $this->tableGateway->delete(['ID' => $id]);
    }

    public function edit(Model $model, $id) {
        $array = $model->getArrayCopyForDB();
        // var_dump($array); die;
        $this->tableGateway->update($array, [TravelExpenseClass::ID => $id]);
    }

    public function fetchById($id) {
        $sql="SELECT * FROM hris_travels_expenses_category where STATUS='E' AND ID=$id ";
        return $this->rawQuery($sql);

    }

}
?>



