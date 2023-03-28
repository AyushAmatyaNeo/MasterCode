<?php

namespace Training\Repository;

use Application\Model\Model;
use Application\Repository\RepositoryInterface;
use Setup\Model\HrEmployees;
use Setup\Model\Institute;
use Setup\Model\Training;
use Training\Model\TrainingAssign;
use Zend\Db\Adapter\AdapterInterface;
use Zend\Db\Sql\Expression;
use Zend\Db\Sql\Sql;
use Zend\Db\TableGateway\TableGateway;
use Application\Repository\HrisRepository;
use Application\Helper\EntityHelper;

class TrainingAssignRepository extends HrisRepository implements RepositoryInterface {

    protected $tableGateway;
    protected $adapter;

    public function __construct(AdapterInterface $adapter) {
        $this->tableGateway = new TableGateway(TrainingAssign::TABLE_NAME, $adapter);
        $this->adapter = $adapter;
    }

    public function add(Model $model) {
        $this->tableGateway->insert($model->getArrayCopyForDB());
        $this->leaveReward($model->employeeId, $model->trainingId);
    }

    public function delete($id) {
        $this->tableGateway->update([TrainingAssign::STATUS => 'D'], [TrainingAssign::EMPLOYEE_ID => $id[0], TrainingAssign::TRAINING_ID => $id[1]]);
        $boundedParameter = [];
        $boundedParameter['id0'] = $id[0];
        $boundedParameter['id1'] = $id[1];
        $this->executeStatement("BEGIN  HRIS_TRAINING_LEAVE_REWARD(:id0,:id1); END;", $boundedParameter);
        $this->reAttendance($id[0],$id[1]);

    }

    public function getDetailByEmployeeID($employeeId, $trainingId) {
        $boundedParams = [];
        $sql = new Sql($this->adapter);
        $select = $sql->select();
        $select->columns([
            new Expression("TA.TRAINING_ID AS TRAINING_ID"),
            new Expression("TA.EMPLOYEE_ID AS EMPLOYEE_ID"),
            new Expression("TA.STATUS AS STATUS"),
            new Expression("TA.REMARKS AS REMARKS"),
            new Expression("INITCAP(TO_CHAR(T." . Training::START_DATE . ", 'DD-MON-YYYY')) AS START_DATE"),
            new Expression("INITCAP(TO_CHAR(T." . Training::END_DATE . ", 'DD-MON-YYYY')) AS END_DATE")
                ], true);
        $select->from(['TA' => TrainingAssign::TABLE_NAME]);
        $select->join(['T' => Training::TABLE_NAME], "T." . Training::TRAINING_ID . "=TA." . TrainingAssign::TRAINING_ID, [Training::TRAINING_ID, Training::TRAINING_CODE, Training::DURATION, "TRAINING_NAME" => new Expression("INITCAP(T.TRAINING_NAME)"), "INSTRUCTOR_NAME" => new Expression("INITCAP(T.INSTRUCTOR_NAME)"), Training::REMARKS, Training::TRAINING_TYPE], "left")
                ->join(['I' => Institute::TABLE_NAME], "I." . Institute::INSTITUTE_ID . "=T." . Training::INSTITUTE_ID, ["INSTITUTE_NAME" => new Expression("INITCAP(I.INSTITUTE_NAME)"), Institute::LOCATION, Institute::EMAIL, Institute::TELEPHONE], "left")
                ->join(['E' => HrEmployees::TABLE_NAME], "E." . HrEmployees::EMPLOYEE_ID . "=TA." . TrainingAssign::EMPLOYEE_ID, ["FIRST_NAME" => new Expression("INITCAP(E.FIRST_NAME)"), "MIDDLE_NAME" => new Expression("INITCAP(E.MIDDLE_NAME)"), "LAST_NAME" => new Expression("INITCAP(E.LAST_NAME)")], "left");


        $select->where([
            "TA.EMPLOYEE_ID= :employeeId",
            "TA.TRAINING_ID= :trainingId",
            "TA.STATUS='E'"
        ]);

        $boundedParams['employeeId'] = $employeeId;
        $boundedParams['trainingId'] = $trainingId;

        $statement = $sql->prepareStatementForSqlObject($select);
        $result = $statement->execute($boundedParams);
        return $result->current();
    }

    public function getAllTrainingList($employeeId) {
        $boundedParams = [];
        $sql = new Sql($this->adapter);
        $select = $sql->select();
        $select->columns([
            new Expression("TA.TRAINING_ID AS TRAINING_ID"),
            new Expression("TA.EMPLOYEE_ID AS EMPLOYEE_ID"),
            new Expression("TA.STATUS AS STATUS"),
            new Expression("TA.REMARKS AS REMARKS"),
            new Expression("INITCAP(TO_CHAR(T." . Training::START_DATE . ", 'DD-MON-YYYY')) AS START_DATE_AD"),
            new Expression("BS_DATE(TO_CHAR(T." . Training::START_DATE . ", 'DD-MON-YYYY')) AS START_DATE_BS"),
            new Expression("INITCAP(TO_CHAR(T." . Training::END_DATE . ", 'DD-MON-YYYY')) AS END_DATE_AD"),
            new Expression("BS_DATE(TO_CHAR(T." . Training::END_DATE . ", 'DD-MON-YYYY')) AS END_DATE_BS")
                ], true);
        $select->from(['TA' => TrainingAssign::TABLE_NAME]);
        $select->join(['T' => Training::TABLE_NAME], "T." . Training::TRAINING_ID . "=TA." . TrainingAssign::TRAINING_ID, [Training::TRAINING_ID, Training::TRAINING_CODE, Training::DURATION, "TRAINING_NAME" => new Expression("INITCAP(T.TRAINING_NAME)"), "INSTRUCTOR_NAME" => new Expression("INITCAP(T.INSTRUCTOR_NAME)"), Training::REMARKS, Training::TRAINING_TYPE], "left")
                ->join(['I' => Institute::TABLE_NAME], "I." . Institute::INSTITUTE_ID . "=T." . Training::INSTITUTE_ID, ["INSTITUTE_NAME" => new Expression("INITCAP(I.INSTITUTE_NAME)"), Institute::LOCATION, Institute::EMAIL, Institute::TELEPHONE], "left");

        $select->where([
            "TA.EMPLOYEE_ID = :employeeId ",
            "TA.STATUS = 'E'"
        ]);

        $boundedParams['employeeId'] = $employeeId;
        $statement = $sql->prepareStatementForSqlObject($select);
        $result = $statement->execute($boundedParams);
        return $result;
    }

    public function getAllDetailByEmployeeID($employeeId, $trainingId) {
        $boundedParams = [];
        $sql = new Sql($this->adapter);
        $select = $sql->select();
        $select->columns([
            new Expression("TA.TRAINING_ID AS TRAINING_ID"),
            new Expression("TA.EMPLOYEE_ID AS EMPLOYEE_ID"),
            new Expression("TA.STATUS AS STATUS"),
            new Expression("TA.REMARKS AS REMARKS"),
            new Expression("INITCAP(TO_CHAR(T." . Training::START_DATE . ", 'DD-MON-YYYY')) AS START_DATE"),
            new Expression("INITCAP(TO_CHAR(T." . Training::END_DATE . ", 'DD-MON-YYYY')) AS END_DATE")
                ], true);
        $select->from(['TA' => TrainingAssign::TABLE_NAME]);
        $select->join(['T' => Training::TABLE_NAME], "T." . Training::TRAINING_ID . "=TA." . TrainingAssign::TRAINING_ID, [Training::TRAINING_ID, Training::TRAINING_CODE, Training::DURATION, "TRAINING_NAME" => new Expression("INITCAP(T.TRAINING_NAME)"), "INSTRUCTOR_NAME" => new Expression("INITCAP(T.INSTRUCTOR_NAME)"), Training::REMARKS, Training::TRAINING_TYPE], "left")
                ->join(['I' => Institute::TABLE_NAME], "I." . Institute::INSTITUTE_ID . "=T." . Training::INSTITUTE_ID, ["INSTITUTE_NAME" => new Expression("INITCAP(I.INSTITUTE_NAME)")], "left");

        $select->where([
            "TA.EMPLOYEE_ID= :employeeId",
            "TA.TRAINING_ID= :trainingId"
        ]);

        $boundedParams['employeeId'] = $employeeId;
        $boundedParams['trainingId'] = $trainingId;

        $statement = $sql->prepareStatementForSqlObject($select);
        $result = $statement->execute($boundedParams);
        return $result;
    }

    public function filterRecords($search) {
//        $condition = "";
        $condition = EntityHelper::getSearchConditonBounded($search['companyId'], $search['branchId'], $search['departmentId'], $search['positionId'], $search['designationId'], $search['serviceTypeId'], $search['serviceEventTypeId'], $search['employeeTypeId'], $search['employeeId']);

        $boundedParameter = [];
        $boundedParameter=array_merge($boundedParameter, $condition['parameter']);

        if (isset($search['trainingId']) && $search['trainingId'] != null && $search['trainingId'] != -1) {
            if (gettype($search['trainingId']) === 'array') {
//                $csv = "";
//                for ($i = 0; $i < sizeof($search['trainingId']); $i++) {
//                    if ($i == 0) {
//                        $csv = "'{$search['trainingId'][$i]}'";
//                    } else {
//                        $csv .= ",'{$search['trainingId'][$i]}'";
//                    }
//                }
//                $condition['sql'] .= "AND TA.TRAINING_ID IN ({$csv})";
            } else {
                $condition['sql'] .= " AND TA.TRAINING_ID IN (:trainingId)";
                $boundedParameter['trainingId'] = $search['trainingId'];
            }
        }
 
        $sql = "SELECT TA.TRAINING_ID,
                  TMS.TRAINING_CODE,
                  TMS.TRAINING_NAME,
                  TMS.TRAINING_TYPE,
                  E.EMPLOYEE_CODE AS EMPLOYEE_CODE,
                  (
                  CASE
                    WHEN TMS.TRAINING_TYPE = 'CC'
                    THEN 'Company Contribution'
                    ELSE 'Personal'
                  END)                                  AS TRAINING_TYPE_DETAIL,
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
                FROM HRIS_EMPLOYEE_TRAINING_ASSIGN TA
                LEFT JOIN HRIS_TRAINING_MASTER_SETUP TMS
                ON (TA.TRAINING_ID= TMS.TRAINING_ID)
                LEFT JOIN HRIS_EMPLOYEES E
                ON (TA.EMPLOYEE_ID=E.EMPLOYEE_ID)
                WHERE 1=1 AND TA.STATUS='E' 
                {$condition['sql']} ORDER BY TMS.TRAINING_NAME,E.FULL_NAME";

        return $this->rawQuery($sql, $boundedParameter);
    }

    public function edit(Model $model, $id) {
        $this->tableGateway->update($model->getArrayCopyForDB(), [TrainingAssign::EMPLOYEE_ID => $id[0], TrainingAssign::TRAINING_ID => $id[1]]);
        $this->leaveReward($id[0], $id[1]);
    }

    public function fetchAll() {
        
    }

    public function fetchById($id) {
        
    }

    public function checkEmployeeTraining(int $employeeId, Expression $date) {
        $sql = new Sql($this->adapter);
        $select = $sql->select();
        $select->columns([TrainingAssign::TRAINING_ID]);
        $select->from(['TA' => TrainingAssign::TABLE_NAME])
                ->join(['T' => Training::TABLE_NAME], "TA." . TrainingAssign::TRAINING_ID . " = " . "T." . Training::TRAINING_ID, []);
        $select->where(["TA." . TrainingAssign::EMPLOYEE_ID . "=:employeeId"]);
        $select->where(["TA." . TrainingAssign::STATUS . "= 'E'"]);
        $select->where([$date->getExpression() . " BETWEEN " . "T." . Training::START_DATE . " AND T." . Training::END_DATE]);
        $boundedParams = [];
        $boundedParams['employeeId'] = $employeeId;
        $statement = $sql->prepareStatementForSqlObject($select);
        $result = $statement->execute($boundedParams);
        return $result->current();
    }
    
    public function leaveReward($employeeId,$trainingId){
        $boundedParams = [];
        $boundedParams['employeeId'] = $employeeId;
        $boundedParams['trainingId'] = $trainingId;
        $sql="DECLARE
                V_EMPLOYEE_ID NUMBER(7,0):= :employeeId;
                V_TRAINING_ID NUMBER(7,0):= :trainingId;
                V_START_DATE DATE;
                V_END_DATE DATE;
                V_DURATION NUMBER;
                BEGIN
                SELECT START_DATE,END_DATE,DURATION
                INTO V_START_DATE,V_END_DATE,V_DURATION
                FROM HRIS_TRAINING_MASTER_SETUP WHERE TRAINING_ID=V_TRAINING_ID;

                DBMS_OUTPUT.PUT_LINE(V_START_DATE);
                DBMS_OUTPUT.PUT_LINE(V_END_DATE);
                DBMS_OUTPUT.PUT_LINE(V_DURATION);


                BEGIN
                DELETE  FROM  HRIS_EMP_TRAINING_ATTENDANCE WHERE
                TRAINING_ID=V_TRAINING_ID AND EMPLOYEE_ID=V_EMPLOYEE_ID;
                END;
                 FOR i IN 0..v_duration - 1 LOOP

                    DBMS_OUTPUT.PUT_LINE(V_START_DATE+i);
                 INSERT INTO HRIS_EMP_TRAINING_ATTENDANCE VALUES
                 (V_TRAINING_ID,V_EMPLOYEE_ID,V_START_DATE+i,'P');


                 END LOOP;

                 BEGIN
                 HRIS_REATTENDANCE(V_START_DATE,V_EMPLOYEE_ID,V_END_DATE);
                 HRIS_TRAINING_LEAVE_REWARD(V_EMPLOYEE_ID,V_TRAINING_ID);
                 END;

                END;";
//        $statement = $this->adapter->query($sql);
        $this->executeStatement($sql, $boundedParams);
        return;
    }

    public function reAttendance($employeeId,$trainingId){
        $boundedParams = [];
        $boundedParams['employeeId'] = $employeeId;
        $boundedParams['trainingId'] = $trainingId;
        $sql="DECLARE
                V_EMPLOYEE_ID NUMBER(7,0):= :employeeId;
                V_TRAINING_ID NUMBER(7,0):= :trainingId;
                V_START_DATE DATE;
                V_END_DATE DATE;
                V_DURATION NUMBER;
                BEGIN
                SELECT START_DATE,END_DATE,DURATION
                INTO V_START_DATE,V_END_DATE,V_DURATION
                FROM HRIS_TRAINING_MASTER_SETUP WHERE TRAINING_ID=V_TRAINING_ID;

                DBMS_OUTPUT.PUT_LINE(V_START_DATE);
                DBMS_OUTPUT.PUT_LINE(V_END_DATE);
                DBMS_OUTPUT.PUT_LINE(V_DURATION);

                 BEGIN
                 HRIS_REATTENDANCE(V_START_DATE,V_EMPLOYEE_ID,V_END_DATE);
                 END;
                END;";
                // echo '<pre>';print_r($sql);die;

        $this->executeStatement($sql, $boundedParams);
        return;
    }
    

}
