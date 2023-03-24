<?php

namespace Training\Repository;

use Application\Model\Model;
use Application\Repository\RepositoryInterface;
use Setup\Model\HrEmployees;
use Setup\Model\Institute;
use Setup\Model\Events;
use Training\Model\EventAssign;
use Zend\Db\Adapter\AdapterInterface;
use Zend\Db\Sql\Expression;
use Zend\Db\Sql\Sql;
use Zend\Db\TableGateway\TableGateway;
use Application\Repository\HrisRepository;
use Application\Helper\EntityHelper;

class EventAssignRepository extends HrisRepository implements RepositoryInterface {

    protected $tableGateway;
    protected $adapter;

    public function __construct(AdapterInterface $adapter) {
        $this->tableGateway = new TableGateway(EventAssign::TABLE_NAME, $adapter);
        $this->adapter = $adapter;
    }

    public function add(Model $model) {
        $this->tableGateway->insert($model->getArrayCopyForDB());
        $this->eventReward($model->employeeId, $model->eventId);
    }

    public function delete($id) {
        $this->tableGateway->update([EventAssign::STATUS => 'D'], [EventAssign::EMPLOYEE_ID => $id[0], EventAssign::EVENT_ID => $id[1]]);
        $boundedParameter = [];
        $boundedParameter['id0'] = $id[0];
        $boundedParameter['id1'] = $id[1];
        // echo '<pre>';print_r($id[0]);die;
        $this->eventReward($id[0], $id[1]);

        // $this->executeStatement("BEGIN  HRIS_TRAINING_LEAVE_REWARD(:id0,:id1); END;", $boundedParameter);
    }

    public function getDetailByEmployeeID($employeeId, $eventid) {
        $boundedParams = [];
        $sql = new Sql($this->adapter);
        $select = $sql->select();
        $select->columns([
            new Expression("TA.EVENT_ID AS EVENT_ID"),
            new Expression("TA.EMPLOYEE_ID AS EMPLOYEE_ID"),
            new Expression("TA.STATUS AS STATUS"),
            new Expression("TA.REMARKS AS REMARKS"),
            new Expression("INITCAP(TO_CHAR(T." . Events::START_DATE . ", 'DD-MON-YYYY')) AS START_DATE"),
            new Expression("INITCAP(TO_CHAR(T." . Events::END_DATE . ", 'DD-MON-YYYY')) AS END_DATE")
                ], true);
        $select->from(['TA' => EventAssign::TABLE_NAME]);
        $select->join(['T' => Events::TABLE_NAME], "T." . Events::EVENT_ID . "=TA." . EventAssign::EVENT_ID, [Events::EVENT_ID, Events::EVENT_CODE, Events::DURATION, "EVENT_NAME" => new Expression("INITCAP(T.EVENT_NAME)"), "INSTRUCTOR_NAME" => new Expression("INITCAP(T.INSTRUCTOR_NAME)"), Events::REMARKS, Events::EVENT_TYPE], "left")
                ->join(['I' => Institute::TABLE_NAME], "I." . Institute::INSTITUTE_ID . "=T." . Events::INSTITUTE_ID, ["INSTITUTE_NAME" => new Expression("INITCAP(I.INSTITUTE_NAME)"), Institute::LOCATION, Institute::EMAIL, Institute::TELEPHONE], "left")
                ->join(['E' => HrEmployees::TABLE_NAME], "E." . HrEmployees::EMPLOYEE_ID . "=TA." . EventAssign::EMPLOYEE_ID, ["FIRST_NAME" => new Expression("INITCAP(E.FIRST_NAME)"), "MIDDLE_NAME" => new Expression("INITCAP(E.MIDDLE_NAME)"), "LAST_NAME" => new Expression("INITCAP(E.LAST_NAME)")], "left");


        $select->where([
            "TA.EMPLOYEE_ID= :employeeId",
            "TA.EVENT_ID= :eventid",
            "TA.STATUS='E'"
        ]);
        $boundedParams['employeeId'] = $employeeId;
        $boundedParams['eventid'] = $eventid;

        $statement = $sql->prepareStatementForSqlObject($select);
        $result = $statement->execute($boundedParams);
        return $result->current();
    }

    public function getAllTrainingList($employeeId) {
        $boundedParams = [];
        $sql = new Sql($this->adapter);
        $select = $sql->select();
        $select->columns([
            new Expression("TA.EVENT_ID AS EVENT_ID"),
            new Expression("TA.EMPLOYEE_ID AS EMPLOYEE_ID"),
            new Expression("TA.STATUS AS STATUS"),
            new Expression("TA.REMARKS AS REMARKS"),
            new Expression("INITCAP(TO_CHAR(T." . Events::START_DATE . ", 'DD-MON-YYYY')) AS START_DATE_AD"),
            new Expression("BS_DATE(TO_CHAR(T." . Events::START_DATE . ", 'DD-MON-YYYY')) AS START_DATE_BS"),
            new Expression("INITCAP(TO_CHAR(T." . Events::END_DATE . ", 'DD-MON-YYYY')) AS END_DATE_AD"),
            new Expression("BS_DATE(TO_CHAR(T." . Events::END_DATE . ", 'DD-MON-YYYY')) AS END_DATE_BS")
                ], true);
        $select->from(['TA' => EventAssign::TABLE_NAME]);
        $select->join(['T' => Events::TABLE_NAME], "T." . Events::EVENT_ID . "=TA." . EventAssign::EVENT_ID, [Events::EVENT_ID, Events::EVENT_CODE, Events::DURATION, "EVENT_NAME" => new Expression("INITCAP(T.EVENT_NAME)"), "INSTRUCTOR_NAME" => new Expression("INITCAP(T.INSTRUCTOR_NAME)"), Events::REMARKS, Events::EVENT_TYPE], "left")
                ->join(['I' => Institute::TABLE_NAME], "I." . Institute::INSTITUTE_ID . "=T." . Events::INSTITUTE_ID, ["INSTITUTE_NAME" => new Expression("INITCAP(I.INSTITUTE_NAME)"), Institute::LOCATION, Institute::EMAIL, Institute::TELEPHONE], "left");

        $select->where([
            "TA.EMPLOYEE_ID = :employeeId ",
            "TA.STATUS = 'E'"
        ]);

        $boundedParams['employeeId'] = $employeeId;
        $statement = $sql->prepareStatementForSqlObject($select);
        $result = $statement->execute($boundedParams);
        return $result;
    }

    public function getAllDetailByEmployeeID($employeeId, $eventId) {
        $boundedParams = [];
        $sql = new Sql($this->adapter);
        $select = $sql->select();
        $select->columns([
            new Expression("TA.EVENT_ID AS EVENT_ID"),
            new Expression("TA.EMPLOYEE_ID AS EMPLOYEE_ID"),
            new Expression("TA.STATUS AS STATUS"),
            new Expression("TA.REMARKS AS REMARKS"),
            new Expression("INITCAP(TO_CHAR(T." . Events::START_DATE . ", 'DD-MON-YYYY')) AS START_DATE"),
            new Expression("INITCAP(TO_CHAR(T." . Events::END_DATE . ", 'DD-MON-YYYY')) AS END_DATE")
                ], true);
        $select->from(['TA' => EventAssign::TABLE_NAME]);
        $select->join(['T' => Events::TABLE_NAME], "T." . Events::EVENT_ID . "=TA." . EventAssign::EVENT_ID, [Events::EVENT_ID, Events::EVENT_CODE, Events::DURATION, "EVENT_NAME" => new Expression("INITCAP(T.EVENT_NAME)"), "INSTRUCTOR_NAME" => new Expression("INITCAP(T.INSTRUCTOR_NAME)"), Events::REMARKS, Events::EVENT_TYPE], "left")
                ->join(['I' => Institute::TABLE_NAME], "I." . Institute::INSTITUTE_ID . "=T." . Events::INSTITUTE_ID, ["INSTITUTE_NAME" => new Expression("INITCAP(I.INSTITUTE_NAME)")], "left");

        $select->where([
            "TA.EMPLOYEE_ID= :employeeId",
            "TA.EVENT_ID= :eventId"
        ]);

        $boundedParams['employeeId'] = $employeeId;
        $boundedParams['eventId'] = $eventId;
        $statement = $sql->prepareStatementForSqlObject($select);
        $result = $statement->execute($boundedParams);

        return $result;
    }

    public function filterRecords($search) {
//        $condition = "";
        $condition = EntityHelper::getSearchConditonBounded($search['companyId'], $search['branchId'], $search['departmentId'], $search['positionId'], $search['designationId'], $search['serviceTypeId'], $search['serviceEventTypeId'], $search['employeeTypeId'], $search['employeeId']);

        $boundedParameter = [];
        $boundedParameter=array_merge($boundedParameter, $condition['parameter']);

        if (isset($search['eventId']) && $search['eventId'] != null && $search['eventId'] != -1) {
            if (gettype($search['eventId']) === 'array') {
//                $csv = "";
//                for ($i = 0; $i < sizeof($search['eventId']); $i++) {
//                    if ($i == 0) {
//                        $csv = "'{$search['eventId'][$i]}'";
//                    } else {
//                        $csv .= ",'{$search['eventId'][$i]}'";
//                    }
//                }
//                $condition['sql'] .= "AND TA.EVENT_ID IN ({$csv})";
            } else {
                $condition['sql'] .= " AND TA.EVENT_ID IN (:eventId)";
                $boundedParameter['eventId'] = $search['eventId'];
            }
        }
 
        $sql = "SELECT TA.EVENT_ID,
                  TMS.EVENT_CODE,
                  TMS.EVENT_NAME,
                  TMS.EVENT_TYPE,
                  E.EMPLOYEE_CODE AS EMPLOYEE_CODE,
                  (
                  CASE
                    WHEN TMS.EVENT_TYPE = 'CC'
                    THEN 'Company Contribution'
                    ELSE 'Personal'
                  END)                                  AS EVENT_TYPE_DETAIL,
                  TO_CHAR(TMS.START_DATE,'DD-MON-YYYY') AS START_DATE,
                  TO_CHAR(TMS.START_DATE,'DD-MON-YYYY') AS START_DATE_AD,
                  BS_DATE(TMS.START_DATE)               AS START_DATE_BS,
                  TO_CHAR(TMS.END_DATE,'DD-MON-YYYY')   AS END_DATE,
                  TO_CHAR(TMS.END_DATE,'DD-MON-YYYY')   AS END_DATE_AD,
                  BS_DATE(TMS.END_DATE)                 AS END_DATE_BS,
                  TA.EMPLOYEE_ID,
                  E.FULL_NAME AS EMPLOYEE_NAME,
                  'Y' AS ALLOW_VIEW,
                  'Y' AS ALLOW_DELETE
                FROM HRIS_EMPLOYEE_EVENT_ASSIGN TA
                LEFT JOIN HRIS_EVENT_MASTER_SETUP TMS
                ON (TA.EVENT_ID= TMS.EVENT_ID)
                LEFT JOIN HRIS_EMPLOYEES E
                ON (TA.EMPLOYEE_ID=E.EMPLOYEE_ID)
                WHERE 1=1 AND TA.STATUS='E' 
                {$condition['sql']} ORDER BY TMS.EVENT_NAME,E.FULL_NAME";
        // echo '<pre>';print_r($sql);die;
        return $this->rawQuery($sql, $boundedParameter);
    }

    public function edit(Model $model, $id) {
        $this->tableGateway->update($model->getArrayCopyForDB(), [EventAssign::EMPLOYEE_ID => $id[0], EventAssign::EVENT_ID => $id[1]]);
        $this->eventReward($id[0], $id[1]);
    }

    public function fetchAll() {
        
    }

    public function fetchById($id) {
        
    }

    public function checkEmployeeTraining(int $employeeId, Expression $date) {
        $sql = new Sql($this->adapter);
        $select = $sql->select();
        $select->columns([EventAssign::EVENT_ID]);
        $select->from(['TA' => EventAssign::TABLE_NAME])
                ->join(['T' => Events::TABLE_NAME], "TA." . EventAssign::EVENT_ID . " = " . "T." . Events::EVENT_ID, []);
        $select->where(["TA." . EventAssign::EMPLOYEE_ID . "=:employeeId"]);
        $select->where(["TA." . EventAssign::STATUS . "= 'E'"]);
        $select->where([$date->getExpression() . " BETWEEN " . "T." . Events::START_DATE . " AND T." . Events::END_DATE]);
        $boundedParams = [];
        $boundedParams['employeeId'] = $employeeId;
        $statement = $sql->prepareStatementForSqlObject($select);
        $result = $statement->execute($boundedParams);
        return $result->current();
    }
    
    public function leaveReward($employeeId,$eventId){
        $boundedParams = [];
        $boundedParams['employeeId'] = $employeeId;
        $boundedParams['eventId'] = $eventId;
        $sql="DECLARE
                V_EMPLOYEE_ID NUMBER(7,0):= :employeeId;
                V_EVENT_ID NUMBER(7,0):= :eventId;
                V_START_DATE DATE;
                V_END_DATE DATE;
                V_DURATION NUMBER;
                BEGIN
                SELECT START_DATE,END_DATE,DURATION
                INTO V_START_DATE,V_END_DATE,V_DURATION
                FROM HRIS_EVENT_MASTER_SETUP WHERE EVENT_ID=V_EVENT_ID;

                DBMS_OUTPUT.PUT_LINE(V_START_DATE);
                DBMS_OUTPUT.PUT_LINE(V_END_DATE);
                DBMS_OUTPUT.PUT_LINE(V_DURATION);


                BEGIN
                DELETE  FROM  HRIS_EMP_TRAINING_ATTENDANCE WHERE
                EVENT_ID=V_EVENT_ID AND EMPLOYEE_ID=V_EMPLOYEE_ID;
                END;
                 FOR i IN 0..v_duration - 1 LOOP

                    DBMS_OUTPUT.PUT_LINE(V_START_DATE+i);
                 INSERT INTO HRIS_EMP_TRAINING_ATTENDANCE VALUES
                 (V_EVENT_ID,V_EMPLOYEE_ID,V_START_DATE+i,'P');


                 END LOOP;

                 BEGIN
                 HRIS_REATTENDANCE(V_START_DATE,V_EMPLOYEE_ID,V_END_DATE);
                 HRIS_TRAINING_LEAVE_REWARD(V_EMPLOYEE_ID,V_EVENT_ID);
                 END;

                END;";
//        $statement = $this->adapter->query($sql);
        $this->executeStatement($sql, $boundedParams);
        return;
    }

    public function eventReward($employeeId,$eventId){
        $boundedParams = [];
        $boundedParams['employeeId'] = $employeeId;
        $boundedParams['eventId'] = $eventId;
        $sql="DECLARE
                V_EMPLOYEE_ID NUMBER(7,0):= :employeeId;
                V_EVENT_ID NUMBER(7,0):= :eventId;
                V_START_DATE DATE;
                V_END_DATE DATE;
                V_DURATION NUMBER;
                BEGIN
                SELECT START_DATE,END_DATE,DURATION
                INTO V_START_DATE,V_END_DATE,V_DURATION
                FROM HRIS_EVENT_MASTER_SETUP WHERE EVENT_ID=V_EVENT_ID;

                DBMS_OUTPUT.PUT_LINE(V_START_DATE);
                DBMS_OUTPUT.PUT_LINE(V_END_DATE);
                DBMS_OUTPUT.PUT_LINE(V_DURATION);

                 BEGIN
                 HRIS_REATTENDANCE(V_START_DATE,V_EMPLOYEE_ID,V_END_DATE);
                 END;

                END;";
//        $statement = $this->adapter->query($sql);
// echo '<pre>';print_r($sql);die;
        $this->executeStatement($sql, $boundedParams);
        return;
    }
    

}
