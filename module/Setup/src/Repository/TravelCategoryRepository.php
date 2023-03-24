<?php

namespace Setup\Repository;

use Application\Helper\EntityHelper;
use Application\Helper;
use Application\Model\Model;
use Application\Repository\HrisRepository;
use Application\Repository\RepositoryInterface;
use Zend\Db\Adapter\AdapterInterface;
use Setup\Model\TravelCategory;
use Zend\Db\Sql\Expression;
use Zend\Db\Sql\Sql;
use Zend\Db\TableGateway\TableGateway;

class TravelCategoryRepository extends HrisRepository{
    protected $tableGateway;
    protected $adapter;

    public function __construct(AdapterInterface $adapter)
    {
        $this->tableGateway=new TableGateway(TravelCategory::TABLE_NAME,$adapter);
        $this->adapter=$adapter;
    }

    public function fetchAll(){
        $sql="
        SELECT
       *
    FROM
    hris_travel_category  where STATUS='E'";

    return $this->rawQuery($sql);
    }

    public function add(Model $model) {
        $this->tableGateway->insert($model->getArrayCopyForDB());
    }

    public function delete($id) {
        $this->tableGateway->update([TravelCategory::STATUS => 'D'], [TravelCategory::ID => $id]);
    }

    public function edit(Model $model, $id) {
        $array = $model->getArrayCopyForDB();
        // var_dump($array); die;
        $this->tableGateway->update($array, [TravelCategory::ID => $id]);
    }

    public function fetchById($id) {
        $sql="SELECT * FROM hris_travel_category where STATUS='E' AND ID=$id ";
        return $this->rawQuery($sql);

    }

}
?>



