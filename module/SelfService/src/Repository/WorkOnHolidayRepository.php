<?php

namespace SelfService\Repository;

use Application\Helper\EntityHelper;
use Application\Model\Model;
use Application\Repository\RepositoryInterface;
use HolidayManagement\Model\Holiday;
use SelfService\Model\WorkOnHoliday;
use Zend\Db\Adapter\AdapterInterface;
use Zend\Db\Sql\Expression;
use Zend\Db\Sql\Sql;
use Zend\Db\TableGateway\TableGateway;

class WorkOnHolidayRepository implements RepositoryInterface {

    private $tableGateway;
    private $adapter;

    public function __construct(AdapterInterface $adapter) {
        $this->adapter = $adapter;
        $this->tableGateway = new TableGateway(WorkOnHoliday::TABLE_NAME, $adapter);
    }

    public function add(Model $model) {
        $addData=$model->getArrayCopyForDB();
        $this->tableGateway->insert($addData);
        

        if ($addData['STATUS']=='AP' && date('Y-m-d', strtotime($model->fromDate)) <= date('Y-m-d')) {
            $sql = "BEGIN 
            HRIS_REATTENDANCE('{$model->fromDate}',$model->employeeId,'{$model->toDate}');
               END; ";

            EntityHelper::rawQueryResult($this->adapter, $sql);
        }
    }

    public function delete($id) {
        $sql = "BEGIN
UPDATE HRIS_EMPLOYEE_WORK_HOLIDAY SET STATUS='C',MODIFIED_DATE=TRUNC(SYSDATE) WHERE ID={$id};
DELETE FROM HRIS_EMPLOYEE_LEAVE_ADDITION WHERE WOH_ID={$id};
DELETE FROM HRIS_OVERTIME_DETAIL WHERE WOH_ID= {$id};
DELETE FROM HRIS_OVERTIME WHERE WOH_ID = {$id};
END;";
 EntityHelper::rawQueryResult($this->adapter, $sql);
        $sql="select * from HRIS_EMPLOYEE_WORK_HOLIDAY where id={$id}";
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
            new Expression("WH.ID AS ID"),
            new Expression("WH.EMPLOYEE_ID AS EMPLOYEE_ID"),
            new Expression("WH.HOLIDAY_ID AS HOLIDAY_ID"),
            new Expression("INITCAP(TO_CHAR(WH.REQUESTED_DATE, 'DD-MON-YYYY')) AS REQUESTED_DATE"),
            new Expression("INITCAP(TO_CHAR(WH.FROM_DATE, 'DD-MON-YYYY')) AS FROM_DATE"),
            new Expression("INITCAP(TO_CHAR(WH.TO_DATE, 'DD-MON-YYYY')) AS TO_DATE"),
            new Expression("WH.DURATION AS DURATION"),
            new Expression("WH.REMARKS AS REMARKS"),
            new Expression("WH.STATUS AS STATUS"),
            new Expression("LEAVE_STATUS_DESC(WH.STATUS) AS STATUS_DETAIL"),
            new Expression("WH.RECOMMENDED_BY AS RECOMMENDED_BY"),
            new Expression("INITCAP(TO_CHAR(WH.RECOMMENDED_DATE, 'DD-MON-YYYY')) AS RECOMMENDED_DATE"),
            new Expression("WH.RECOMMENDED_REMARKS AS RECOMMENDED_REMARKS"),
            new Expression("WH.APPROVED_BY AS APPROVED_BY"),
            new Expression("INITCAP(TO_CHAR(WH.APPROVED_DATE, 'DD-MON-YYYY')) AS APPROVED_DATE"),
            new Expression("WH.APPROVED_REMARKS AS APPROVED_REMARKS"),
            new Expression("INITCAP(TO_CHAR(WH.MODIFIED_DATE, 'DD-MON-YYYY')) AS MODIFIED_DATE"),
                ], true);

        $select->from(['WH' => WorkOnHoliday::TABLE_NAME])
                ->join(['H' => Holiday::TABLE_NAME], "H." . Holiday::HOLIDAY_ID . "=WH." . WorkOnHoliday::HOLIDAY_ID, [Holiday::HOLIDAY_CODE, "HOLIDAY_ENAME" => new Expression("INITCAP(H.HOLIDAY_ENAME)")])
                ->join(['E' => 'HRIS_EMPLOYEES'], 'E.EMPLOYEE_ID=WH.EMPLOYEE_ID', ["FULL_NAME" => new Expression("INITCAP(E.FULL_NAME)")], "left")
                ->join(['E2' => "HRIS_EMPLOYEES"], "E2.EMPLOYEE_ID=WH.RECOMMENDED_BY", ['RECOMMENDED_BY_NAME' => new Expression("INITCAP(E2.FULL_NAME)")], "left")
                ->join(['E3' => "HRIS_EMPLOYEES"], "E3.EMPLOYEE_ID=WH.APPROVED_BY", ['APPROVED_BY_NAME' => new Expression("INITCAP(E3.FULL_NAME)")], "left")
                ->join(['RA' => "HRIS_RECOMMENDER_APPROVER"], "RA.EMPLOYEE_ID=WH.EMPLOYEE_ID", ['RECOMMENDER_ID' => 'RECOMMEND_BY', 'APPROVER_ID' => 'APPROVED_BY'], "left")
                ->join(['RECM' => "HRIS_EMPLOYEES"], "RECM.EMPLOYEE_ID=RA.RECOMMEND_BY", ['RECOMMENDER_NAME' => new Expression("INITCAP(RECM.FULL_NAME)")], "left")
                ->join(['APRV' => "HRIS_EMPLOYEES"], "APRV.EMPLOYEE_ID=RA.APPROVED_BY", ['APPROVER_NAME' => new Expression("INITCAP(APRV.FULL_NAME)")], "left");

        $select->where(["WH.ID" => $id]);
        $select->order("WH.REQUESTED_DATE DESC");
        $statement = $sql->prepareStatementForSqlObject($select);
        $result = $statement->execute();
        return $result->current();
    }

    public function getAllByEmployeeId($employeeId) {
        $sql = new Sql($this->adapter);
        $select = $sql->select();
        $select->columns([
            new Expression("WH.ID AS ID"),
            new Expression("WH.EMPLOYEE_ID AS EMPLOYEE_ID"),
            new Expression("WH.HOLIDAY_ID AS HOLIDAY_ID"),
            new Expression("INITCAP(TO_CHAR(WH.REQUESTED_DATE, 'DD-MON-YYYY')) AS REQUESTED_DATE_AD"),
            new Expression("BS_DATE(TO_CHAR(WH.REQUESTED_DATE, 'DD-MON-YYYY')) AS REQUESTED_DATE_BS"),
            new Expression("INITCAP(TO_CHAR(WH.FROM_DATE, 'DD-MON-YYYY')) AS FROM_DATE_AD"),
            new Expression("BS_DATE(TO_CHAR(WH.FROM_DATE, 'DD-MON-YYYY')) AS FROM_DATE_BS"),
            new Expression("INITCAP(TO_CHAR(WH.TO_DATE, 'DD-MON-YYYY')) AS TO_DATE_AD"),
            new Expression("BS_DATE(TO_CHAR(WH.TO_DATE, 'DD-MON-YYYY')) AS TO_DATE_BS"),
            new Expression("WH.DURATION AS DURATION"),
            new Expression("WH.REMARKS AS REMARKS"),
            new Expression("WH.STATUS AS STATUS"),
            new Expression("LEAVE_STATUS_DESC(WH.STATUS) AS STATUS_DETAIL"),
            new Expression("WH.RECOMMENDED_BY AS RECOMMENDED_BY"),
            new Expression("INITCAP(TO_CHAR(WH.RECOMMENDED_DATE, 'DD-MON-YYYY')) AS RECOMMENDED_DATE"),
            new Expression("WH.RECOMMENDED_REMARKS AS RECOMMENDED_REMARKS"),
            new Expression("WH.APPROVED_BY AS APPROVED_BY"),
            new Expression("INITCAP(TO_CHAR(WH.APPROVED_DATE, 'DD-MON-YYYY')) AS APPROVED_DATE"),
            new Expression("WH.APPROVED_REMARKS AS APPROVED_REMARKS"),
            new Expression("INITCAP(TO_CHAR(WH.MODIFIED_DATE, 'DD-MON-YYYY')) AS MODIFIED_DATE"),
            new Expression("(CASE WHEN WH.STATUS = 'RQ' THEN 'Y' ELSE 'N' END) AS ALLOW_EDIT"),
            new Expression("(CASE WHEN WH.STATUS IN ('RQ','RC','AP') THEN 'Y' ELSE 'N' END) AS ALLOW_DELETE"),
                ], true);

        $select->from(['WH' => WorkOnHoliday::TABLE_NAME])
                ->join(['H' => Holiday::TABLE_NAME], "H." . Holiday::HOLIDAY_ID . "=WH." . WorkOnHoliday::HOLIDAY_ID, [Holiday::HOLIDAY_CODE, "HOLIDAY_ENAME" => new Expression("INITCAP(H.HOLIDAY_ENAME)")])
                ->join(['E' => 'HRIS_EMPLOYEES'], 'E.EMPLOYEE_ID=WH.EMPLOYEE_ID', ["FULL_NAME" => new Expression("INITCAP(E.FULL_NAME)")], "left")
                ->join(['E2' => "HRIS_EMPLOYEES"], "E2.EMPLOYEE_ID=WH.RECOMMENDED_BY", ['RECOMMENDED_BY_NAME' => new Expression("INITCAP(E2.FULL_NAME)")], "left")
                ->join(['E3' => "HRIS_EMPLOYEES"], "E3.EMPLOYEE_ID=WH.APPROVED_BY", ['APPROVED_BY_NAME' => new Expression("INITCAP(E3.FULL_NAME)")], "left")
                ->join(['RA' => "HRIS_RECOMMENDER_APPROVER"], "RA.EMPLOYEE_ID=WH.EMPLOYEE_ID", ['RECOMMENDER_ID' => 'RECOMMEND_BY', 'APPROVER_ID' => 'APPROVED_BY'], "left")
                ->join(['RECM' => "HRIS_EMPLOYEES"], "RECM.EMPLOYEE_ID=RA.RECOMMEND_BY", ['RECOMMENDER_NAME' => new Expression("INITCAP(RECM.FULL_NAME)")], "left")
                ->join(['APRV' => "HRIS_EMPLOYEES"], "APRV.EMPLOYEE_ID=RA.APPROVED_BY", ['APPROVER_NAME' => new Expression("INITCAP(APRV.FULL_NAME)")], "left");

        $select->where([
            "E.EMPLOYEE_ID=" . $employeeId
        ]);
        $select->where([
            "(TRUNC(SYSDATE)- WH.REQUESTED_DATE) < (
                      CASE
                        WHEN WH.STATUS = 'C'
                        THEN 20
                        ELSE 365
                      END)"
        ]);
        $select->order("WH.REQUESTED_DATE DESC");
        $statement = $sql->prepareStatementForSqlObject($select);
        $result = $statement->execute();
        return $result;
    }

}
