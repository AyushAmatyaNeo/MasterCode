<?php
namespace Setup\Repository;

use Application\Model\Model;
use Application\Repository\RepositoryInterface;
use Zend\Db\TableGateway\TableGateway;
use Zend\Db\Adapter\AdapterInterface;
use Setup\Model\Institute;
use Zend\Db\Sql\Sql;
use Zend\Db\Sql\Select;

class InstituteRepository implements RepositoryInterface{
	private $tableGateway;

	public function __construct(AdapterInterface $adapter){
		$this->tableGateway = new TableGateway(Institute::TABLE_NAME,$adapter);
	}
	public function add(Model $model){
		$this->tableGateway->insert($model->getArrayCopyForDB());
	}
	public function edit(Model $model,$id){
		$array = $model->getArrayCopyForDB();
		unset($array[Institute::INSTITUTE_ID]);
		unset($array[Institute::CREATED_DATE]);
		$this->tableGateway->update($array,[Institute::INSTITUTE_ID=>$id]);
	}
	public function delete($id){
        $this->tableGateway->update([Institute::STATUS=>'D'],[Institute::INSTITUTE_ID => $id]);
	}
	public function fetchAll(){
		return $this->tableGateway->select();
	}
	public function fetchActiveRecord()
    {
        $rowset= $this->tableGateway->select(function(Select $select){
                $select->where([Institute::STATUS=>'E']);
                $select->order(Institute::INSTITUTE_NAME." ASC");
            });
        $result = [];
        $i=1;
        foreach($rowset as $row){
            array_push($result, [
                'SN'=>$i,
                'INSTITUTE_ID'=>$row['INSTITUTE_ID'],
                'INSTITUTE_CODE'=>$row['INSTITUTE_CODE'],
                'INSTITUTE_NAME'=>$row['INSTITUTE_NAME'],
                'LOCATION'=>$row['LOCATION'],
                'TELEPHONE'=>$row['TELEPHONE'],
                'EMAIL'=>$row['EMAIL'],
                'REMARKS'=>$row['REMARKS']
            ]);
            $i+=1;
        }
        return $result;
    }
	public function fetchById($id){
		$row = $this->tableGateway->select([Institute::INSTITUTE_ID=>$id]);
		return $row->current();
	}
}