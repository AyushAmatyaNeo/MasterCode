<?php
/**
 * Created by PhpStorm.
 * User: root
 * Date: 10/25/16
 * Time: 12:08 PM
 */
namespace LeaveManagement\Repository;

use Application\Model\Model;
use Application\Repository\RepositoryInterface;
use Zend\Db\Adapter\AdapterInterface;
use Zend\Db\Sql\Sql;
use Zend\Db\Sql\Expression;
use LeaveManagement\Model\LeaveApply;

class LeaveStatusRepository implements RepositoryInterface {

    private $adapter;
    public function __construct(AdapterInterface $adapter)
    {
        $this->adapter = $adapter;
    }

    public function add(Model $model)
    {
        // TODO: Implement add() method.
    }

    public function edit(Model $model, $id)
    {
        // TODO: Implement edit() method.
    }
    public function getAllRequest($status=null)
    {
        if($status!=null){
            $where = "LA.STATUS ='".$status."'";
        }else{
            $where ="";
        }
        $sql = "SELECT L.LEAVE_ENAME,LA.NO_OF_DAYS,LA.START_DATE
                ,LA.END_DATE,LA.REQUESTED_DT AS APPLIED_DATE,
                LA.STATUS AS STATUS,
                LA.ID AS ID,
                LA.RECOMMENDED_DT AS RECOMMENDED_DT,
                LA.APPROVED_DT AS APPROVED_DT,
                E.FIRST_NAME,E.MIDDLE_NAME,E.LAST_NAME,
                E1.FIRST_NAME AS FN1,E1.MIDDLE_NAME AS MN1,E1.LAST_NAME AS LN1,
                E2.FIRST_NAME AS FN2,E2.MIDDLE_NAME AS MN2,E2.LAST_NAME AS LN2,
                LA.RECOMMENDED_BY AS RECOMMENDER,
                LA.APPROVED_BY AS APPROVER
                FROM HR_EMPLOYEE_LEAVE_REQUEST LA, 
                HR_LEAVE_MASTER_SETUP L,
                HR_EMPLOYEES E,
                HR_EMPLOYEES E1,
                HR_EMPLOYEES E2
                WHERE 
                L.LEAVE_ID=LA.LEAVE_ID AND
                E.EMPLOYEE_ID=LA.EMPLOYEE_ID AND
                E1.EMPLOYEE_ID=LA.RECOMMENDED_BY AND
                E2.EMPLOYEE_ID=LA.APPROVED_BY AND ".$where;
        $statement = $this->adapter->query($sql);
        //return $statement->getSql();
        $result = $statement->execute();
        return $result;
    }

    public function fetchAll()
    {
        // TODO: Implement fetchAll() method.
    }

    public function fetchById($id)
    {
        $sql = new Sql($this->adapter);
        $select = $sql->select();
        $select->columns([
            new Expression("LA.START_DATE AS START_DATE"),
            new Expression("TO_CHAR(LA.REQUESTED_DT, 'DD-MON-YYYY') AS REQUESTED_DT"),
            new Expression("LA.STATUS AS STATUS"),
            new Expression("LA.ID AS ID"),
            new Expression("LA.END_DATE AS END_DATE"),
            new Expression("LA.NO_OF_DAYS AS NO_OF_DAYS"),
            new Expression("LA.HALF_DAY AS HALF_DAY"),
            new Expression("LA.EMPLOYEE_ID AS EMPLOYEE_ID"),
            new Expression("LA.LEAVE_ID AS LEAVE_ID"),
        ], true);

        $select->from(['LA' => LeaveApply::TABLE_NAME])
            ->join(['E'=>"HR_EMPLOYEES"],"E.EMPLOYEE_ID=LA.EMPLOYEE_ID",['FIRST_NAME','MIDDLE_NAME','LAST_NAME'])
            ->join(['E1'=>"HR_EMPLOYEES"],"E1.EMPLOYEE_ID=LA.RECOMMENDED_BY",['FN1'=>'FIRST_NAME','MN1'=>'MIDDLE_NAME','LN1'=>'LAST_NAME'])
            ->join(['E2'=>"HR_EMPLOYEES"],"E2.EMPLOYEE_ID=LA.APPROVED_BY",['FN2'=>'FIRST_NAME','MN2'=>'MIDDLE_NAME','LN2'=>'LAST_NAME']);

        $select->where([
            "LA.ID=".$id
        ]);

        $statement = $sql->prepareStatementForSqlObject($select);
        //print_r($statement->getSql()); DIE();
        $result = $statement->execute();
        return $result->current();
    }
    public function delete($id)
    {
        // TODO: Implement delete() method.
    }
}