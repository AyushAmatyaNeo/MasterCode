<?php

namespace SelfService\Repository;

use Application\Helper\EntityHelper;
use Application\Model\Model;
use Application\Repository\RepositoryInterface;
use SelfService\Model\WorkOnDayoff;
use Zend\Db\Adapter\AdapterInterface;
use Zend\Db\Sql\Expression;
use Zend\Db\Sql\Sql;
use Zend\Db\TableGateway\TableGateway;
use Application\Helper\Helper;

class WorkOnDayoffRepository implements RepositoryInterface {

    private $tableGateway;
    private $adapter;

    public function __construct(AdapterInterface $adapter) {
        $this->adapter = $adapter;
        $this->tableGateway = new TableGateway(WorkOnDayoff::TABLE_NAME, $adapter);
    }

    public function add(Model $model) {
        $addData=$model->getArrayCopyForDB();
        $this->tableGateway->insert($addData);
        

        if ($addData['STATUS']=='AP' && date('Y-m-d', strtotime($model->fromDate)) <= date('Y-m-d')) {
            $sql = "BEGIN 
            HRIS_REATTENDANCE('{$model->fromDate}',$model->employeeId,'{$model->toDate}');
            commit;
               END; ";
            // echo '<pre>';print_r($sql);die;
            EntityHelper::rawQueryResult($this->adapter, $sql);
        }
    }

    public function delete($id) {
        $sql = "BEGIN
UPDATE HRIS_EMPLOYEE_WORK_DAYOFF SET STATUS='C',MODIFIED_DATE=TRUNC(SYSDATE) WHERE ID={$id};
DELETE FROM HRIS_EMPLOYEE_LEAVE_ADDITION WHERE WOD_ID={$id};
DELETE FROM HRIS_OVERTIME_DETAIL WHERE WOD_ID= {$id};
DELETE FROM HRIS_OVERTIME WHERE WOD_ID = {$id};
END;";
EntityHelper::rawQueryResult($this->adapter, $sql);

$sql="select * from HRIS_EMPLOYEE_WORK_DAYOFF where id={$id}";
$statement = $this->adapter->query($sql);
$result = $statement->execute()->current();
// echo '<pre>';print_r($result);die;

if ($result['STATUS']=='C') {
    $sql = "BEGIN 
    HRIS_REATTENDANCE('{$result['FROM_DATE']}',{$result['EMPLOYEE_ID']},'{$result['FROM_DATE']}');
       END; ";
// echo '<pre>';print_r($sql);die;

    EntityHelper::rawQueryResult($this->adapter, $sql);
}
    }

    public function edit(Model $model, $id) {
        
    }

    public function fetchAll() {
        
    }

    public function fetchById($id) {
        $sql = new Sql($this->adapter);
        $select = $sql->select();
        $select->columns([
            new Expression("WD.ID AS ID"),
            new Expression("WD.EMPLOYEE_ID AS EMPLOYEE_ID"),
            new Expression("INITCAP(TO_CHAR(WD.REQUESTED_DATE, 'DD-MON-YYYY')) AS REQUESTED_DATE"),
            new Expression("INITCAP(TO_CHAR(WD.FROM_DATE, 'DD-MON-YYYY')) AS FROM_DATE"),
            new Expression("INITCAP(TO_CHAR(WD.TO_DATE, 'DD-MON-YYYY')) AS TO_DATE"),
            new Expression("WD.DURATION AS DURATION"),
            new Expression("WD.REMARKS AS REMARKS"),
            new Expression("WD.STATUS AS STATUS"),
            new Expression("LEAVE_STATUS_DESC(WD.STATUS) AS STATUS_DETAIL"),
            new Expression("WD.RECOMMENDED_BY AS RECOMMENDED_BY"),
            new Expression("INITCAP(TO_CHAR(WD.RECOMMENDED_DATE, 'DD-MON-YYYY')) AS RECOMMENDED_DATE"),
            new Expression("WD.RECOMMENDED_REMARKS AS RECOMMENDED_REMARKS"),
            new Expression("WD.APPROVED_BY AS APPROVED_BY"),
            new Expression("INITCAP(TO_CHAR(WD.APPROVED_DATE, 'DD-MON-YYYY')) AS APPROVED_DATE"),
            new Expression("WD.APPROVED_REMARKS AS APPROVED_REMARKS"),
            new Expression("INITCAP(TO_CHAR(WD.MODIFIED_DATE, 'DD-MON-YYYY')) AS MODIFIED_DATE"),
                ], true);

        $select->from(['WD' => WorkOnDayoff::TABLE_NAME])
                ->join(['E' => 'HRIS_EMPLOYEES'], 'E.EMPLOYEE_ID=WD.EMPLOYEE_ID', ["FULL_NAME" => new Expression("INITCAP(E.FULL_NAME)")], "left")
                ->join(['E2' => "HRIS_EMPLOYEES"], "E2.EMPLOYEE_ID=WD.RECOMMENDED_BY", ['RECOMMENDED_BY_NAME' => new Expression("INITCAP(E2.FULL_NAME)")], "left")
                ->join(['E3' => "HRIS_EMPLOYEES"], "E3.EMPLOYEE_ID=WD.APPROVED_BY", ['APPROVED_BY_NAME' => new Expression("INITCAP(E3.FULL_NAME)")], "left")
                ->join(['RA' => "HRIS_RECOMMENDER_APPROVER"], "RA.EMPLOYEE_ID=WD.EMPLOYEE_ID", ['RECOMMENDER_ID' => 'RECOMMEND_BY', 'APPROVER_ID' => 'APPROVED_BY'], "left")
                ->join(['RECM' => "HRIS_EMPLOYEES"], "RECM.EMPLOYEE_ID=RA.RECOMMEND_BY", ['RECOMMENDER_NAME' => new Expression("INITCAP(RECM.FULL_NAME)")], "left")
                ->join(['APRV' => "HRIS_EMPLOYEES"], "APRV.EMPLOYEE_ID=RA.APPROVED_BY", ['APPROVER_NAME' => new Expression("INITCAP(APRV.FULL_NAME)")], "left");
        $select->where([
            "WD.ID=" . $id
        ]);
        $select->order("WD.REQUESTED_DATE DESC");
        $statement = $sql->prepareStatementForSqlObject($select);
        $result = $statement->execute();
        return $result->current();
    }

    public function getAllByEmployeeId($employeeId) {
        $sql = new Sql($this->adapter);
        $select = $sql->select();
        $select->columns([
            new Expression("WD.ID AS ID"),
            new Expression("WD.EMPLOYEE_ID AS EMPLOYEE_ID"),
            new Expression("INITCAP(TO_CHAR(WD.REQUESTED_DATE, 'DD-MON-YYYY')) AS REQUESTED_DATE_AD"),
            new Expression("BS_DATE(TO_CHAR(WD.REQUESTED_DATE, 'DD-MON-YYYY')) AS REQUESTED_DATE_BS"),
            new Expression("INITCAP(TO_CHAR(WD.FROM_DATE, 'DD-MON-YYYY')) AS FROM_DATE_AD"),
            new Expression("BS_DATE(TO_CHAR(WD.FROM_DATE, 'DD-MON-YYYY')) AS FROM_DATE_BS"),
            new Expression("INITCAP(TO_CHAR(WD.TO_DATE, 'DD-MON-YYYY')) AS TO_DATE_AD"),
            new Expression("BS_DATE(TO_CHAR(WD.TO_DATE, 'DD-MON-YYYY')) AS TO_DATE_BS"),
            new Expression("WD.DURATION AS DURATION"),
            new Expression("WD.REMARKS AS REMARKS"),
            new Expression("WD.STATUS AS STATUS"),
            new Expression("LEAVE_STATUS_DESC(WD.STATUS) AS STATUS_DETAIL"),
            new Expression("WD.RECOMMENDED_BY AS RECOMMENDED_BY"),
            new Expression("INITCAP(TO_CHAR(WD.RECOMMENDED_DATE, 'DD-MON-YYYY')) AS RECOMMENDED_DATE"),
            new Expression("WD.RECOMMENDED_REMARKS AS RECOMMENDED_REMARKS"),
            new Expression("WD.APPROVED_BY AS APPROVED_BY"),
            new Expression("INITCAP(TO_CHAR(WD.APPROVED_DATE, 'DD-MON-YYYY')) AS APPROVED_DATE"),
            new Expression("WD.APPROVED_REMARKS AS APPROVED_REMARKS"),
            new Expression("INITCAP(TO_CHAR(WD.MODIFIED_DATE, 'DD-MON-YYYY')) AS MODIFIED_DATE"),
            new Expression("(CASE WHEN WD.STATUS = 'XX' THEN 'Y' ELSE 'N' END) AS ALLOW_EDIT"),
            new Expression("(CASE WHEN WD.STATUS IN ('RQ','RC','AP') THEN 'Y' ELSE 'N' END) AS ALLOW_DELETE"),
                ], true);

        $select->from(['WD' => WorkOnDayoff::TABLE_NAME])
                ->join(['E' => 'HRIS_EMPLOYEES'], 'E.EMPLOYEE_ID=WD.EMPLOYEE_ID', ["FULL_NAME" => new Expression("INITCAP(E.FULL_NAME)")], "left")
                ->join(['E2' => "HRIS_EMPLOYEES"], "E2.EMPLOYEE_ID=WD.RECOMMENDED_BY", ['RECOMMENDED_BY_NAME' => new Expression("INITCAP(E2.FULL_NAME)")], "left")
                ->join(['E3' => "HRIS_EMPLOYEES"], "E3.EMPLOYEE_ID=WD.APPROVED_BY", ['APPROVED_BY_NAME' => new Expression("INITCAP(E3.FULL_NAME)")], "left")
                ->join(['RA' => "HRIS_RECOMMENDER_APPROVER"], "RA.EMPLOYEE_ID=WD.EMPLOYEE_ID", ['RECOMMENDER_ID' => 'RECOMMEND_BY', 'APPROVER_ID' => 'APPROVED_BY'], "left")
                ->join(['RECM' => "HRIS_EMPLOYEES"], "RECM.EMPLOYEE_ID=RA.RECOMMEND_BY", ['RECOMMENDER_NAME' => new Expression("INITCAP(RECM.FULL_NAME)")], "left")
                ->join(['APRV' => "HRIS_EMPLOYEES"], "APRV.EMPLOYEE_ID=RA.APPROVED_BY", ['APPROVER_NAME' => new Expression("INITCAP(APRV.FULL_NAME)")], "left");

        $select->where([
            "E.EMPLOYEE_ID=" . $employeeId
        ]);
        $select->where([
            "(TRUNC(SYSDATE)- WD.REQUESTED_DATE) < (
                      CASE
                        WHEN WD.STATUS = 'C'
                        THEN 20
                        ELSE 365
                      END)"
        ]);
        $select->order("WD.REQUESTED_DATE DESC");
        $statement = $sql->prepareStatementForSqlObject($select);
        $result = $statement->execute();
        return $result;
    }

    public function validateWODRequest($fromDate, $toDate, $employeeId) {
        $rawResult = EntityHelper::rawQueryResult($this->adapter, "SELECT HRIS_VALIDATE_WOD_REQUEST({$fromDate},{$toDate},{$employeeId}) AS ERROR FROM DUAL");
        return $rawResult->current();
    }

}
