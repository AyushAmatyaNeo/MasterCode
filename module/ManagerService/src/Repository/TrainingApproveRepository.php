<?php
namespace ManagerService\Repository;

use Application\Helper\EntityHelper;
use Application\Model\Model;
use Application\Repository\HrisRepository;
use SelfService\Model\TrainingRequest;
use Setup\Model\Training;
use Zend\Db\Adapter\AdapterInterface;
use Zend\Db\Sql\Expression;
use Zend\Db\Sql\Select;
use Zend\Db\Sql\Sql;

class TrainingApproveRepository extends HrisRepository {

    public function __construct(AdapterInterface $adapter) {
        parent::__construct($adapter, TrainingRequest::TABLE_NAME);
    }

    public function edit(Model $model, $id) {
        $temp = $model->getArrayCopyForDB();
        $this->tableGateway->update($temp, [TrainingRequest::REQUEST_ID => $id]);
//        $this->executeStatement("
//                DECLARE
//                  V_TRAINING_ID HRIS_EMPLOYEE_TRAINING_REQUEST.TRAINING_ID%TYPE;
//                  V_START_DATE HRIS_EMPLOYEE_TRAINING_REQUEST.START_DATE%TYPE;
//                  V_END_DATE HRIS_EMPLOYEE_TRAINING_REQUEST.END_DATE%TYPE;
//                  V_EMPLOYEE_ID HRIS_EMPLOYEE_TRAINING_REQUEST.EMPLOYEE_ID%TYPE;
//                  V_STATUS HRIS_EMPLOYEE_TRAINING_REQUEST.STATUS%TYPE;
//                  V_REQUEST_ID HRIS_EMPLOYEE_TRAINING_REQUEST.REQUEST_ID%TYPE:= {$id};
//                  V_ASSIGNED CHAR(1 BYTE)                                    :=NULL;
//                BEGIN
//                  SELECT TRAINING_ID,
//                    TRUNC( START_DATE ),
//                    TRUNC( END_DATE ),
//                    EMPLOYEE_ID,
//                    STATUS
//                  INTO V_TRAINING_ID,
//                    V_START_DATE,
//                    V_END_DATE,
//                    V_EMPLOYEE_ID,
//                    V_STATUS
//                  FROM HRIS_EMPLOYEE_TRAINING_REQUEST
//                  WHERE REQUEST_ID =V_REQUEST_ID;
//                  --
//                  IF V_STATUS IN ('AP','C') THEN
//                     HRIS_TRAINING_LEAVE_REWARD(V_REQUEST_ID);
//                    IF V_START_DATE <TRUNC(SYSDATE) THEN
//                        HRIS_REATTENDANCE(V_START_DATE,V_EMPLOYEE_ID,V_END_DATE);
//                    END IF;                  
//                  END IF;                  
//                  
//                EXCEPTION
//                WHEN NO_DATA_FOUND THEN
//                  DBMS_OUTPUT.PUT('NO DATA FOUND FOR ID =>'|| V_REQUEST_ID);
//                END;
//");
        $boundedParameter = [];
        $boundedParameter['id'] = $id;

        $this->executeStatement("
                DECLARE
                  V_TRAINING_ID HRIS_EMPLOYEE_TRAINING_REQUEST.TRAINING_ID%TYPE;
                  V_START_DATE HRIS_EMPLOYEE_TRAINING_REQUEST.START_DATE%TYPE;
                  V_END_DATE HRIS_EMPLOYEE_TRAINING_REQUEST.END_DATE%TYPE;
                  V_EMPLOYEE_ID HRIS_EMPLOYEE_TRAINING_REQUEST.EMPLOYEE_ID%TYPE;
                  V_STATUS HRIS_EMPLOYEE_TRAINING_REQUEST.STATUS%TYPE;
                  V_REQUEST_ID HRIS_EMPLOYEE_TRAINING_REQUEST.REQUEST_ID%TYPE:= :id;
                  V_ASSIGNED CHAR(1 BYTE)                                    :=NULL;
                  V_DURATION HRIS_EMPLOYEE_TRAINING_REQUEST.DURATION%TYPE;
                BEGIN
                  SELECT TRAINING_ID,
                    TRUNC( START_DATE ),
                    TRUNC( END_DATE ),
                    EMPLOYEE_ID,
                    STATUS,
                    DURATION
                  INTO V_TRAINING_ID,
                    V_START_DATE,
                    V_END_DATE,
                    V_EMPLOYEE_ID,
                    V_STATUS,
                    V_DURATION
                  FROM HRIS_EMPLOYEE_TRAINING_REQUEST
                  WHERE REQUEST_ID =V_REQUEST_ID;
                  --
                  IF V_TRAINING_ID IS NOT NULL THEN
                    SELECT (
                      CASE
                        WHEN COUNT(*)>0
                        THEN 'Y'
                        ELSE 'N'
                      END)
                    INTO V_ASSIGNED
                    FROM HRIS_EMPLOYEE_TRAINING_ASSIGN
                    WHERE TRAINING_ID = V_TRAINING_ID
                    AND EMPLOYEE_ID   =V_EMPLOYEE_ID;
                    
-- TO INSERT INTO ASSIGNED IF NOT ASSIGNED
                    IF V_ASSIGNED    ='N' AND V_STATUS='AP' THEN
                      INSERT
                      INTO HRIS_EMPLOYEE_TRAINING_ASSIGN
                        (
                          TRAINING_ID,
                          EMPLOYEE_ID,
                          STATUS,
                          CREATED_DATE,
                          CREATED_BY
                        )
                        VALUES
                        (
                          V_TRAINING_ID,
                          V_EMPLOYEE_ID,
                          'E',
                          TRUNC(SYSDATE),
                          V_EMPLOYEE_ID
                        );
                    END IF;
                   
-- UPDATE IF ALREADY ASSIGNED
                    IF V_ASSIGNED    ='Y' AND V_STATUS='AP' THEN
                      UPDATE HRIS_EMPLOYEE_TRAINING_ASSIGN
                        SET STATUS='E',MODIFIED_DATE=TRUNC(SYSDATE) 
                        WHERE TRAINING_ID=V_TRAINING_ID AND EMPLOYEE_ID=V_EMPLOYEE_ID;
                    END IF;

                    IF V_STATUS IN ('AP','C','R')  THEN
                        HRIS_REATTENDANCE(V_START_DATE,V_EMPLOYEE_ID,V_END_DATE);
                    END IF;
                    

                BEGIN
                DELETE  FROM  HRIS_EMP_TRAINING_ATTENDANCE WHERE
                TRAINING_ID=V_TRAINING_ID AND EMPLOYEE_ID=V_EMPLOYEE_ID;
                END;
                 FOR i IN 0..V_DURATION - 1 LOOP

                    DBMS_OUTPUT.PUT_LINE(V_START_DATE+i);
                 INSERT INTO HRIS_EMP_TRAINING_ATTENDANCE VALUES
                 (V_TRAINING_ID,V_EMPLOYEE_ID,V_START_DATE+i,'P');
                END LOOP;
                    

                    HRIS_TRAINING_LEAVE_REWARD(V_EMPLOYEE_ID,V_TRAINING_ID);
                END IF;
                  
                EXCEPTION
                WHEN NO_DATA_FOUND THEN
                  DBMS_OUTPUT.PUT('NO DATA FOUND FOR ID =>'|| V_REQUEST_ID);
                END;
", $boundedParameter);
    }

    public function fetchById($id) {
        $sql = new Sql($this->adapter);
        $select = $sql->select();
        $select->columns([
            new Expression("TR.REQUEST_ID AS REQUEST_ID"),
            new Expression("TR.EMPLOYEE_ID AS EMPLOYEE_ID"),
            new Expression("INITCAP(TO_CHAR(TR.REQUESTED_DATE, 'DD-MON-YYYY')) AS REQUESTED_DATE"),
            new Expression("INITCAP(TO_CHAR(TR.REQUESTED_DATE, 'DD-MON-YYYY')) AS REQUESTED_DATE_AD"),
            new Expression("BS_DATE(TO_CHAR(TR.REQUESTED_DATE, 'DD-MON-YYYY')) AS REQUESTED_DATE_BS"),
            new Expression("INITCAP(TO_CHAR(TR.START_DATE, 'DD-MON-YYYY')) AS START_DATE"),
            new Expression("INITCAP(TO_CHAR(TR.START_DATE, 'DD-MON-YYYY')) AS START_DATE_AD"),
            new Expression("BS_DATE(TO_CHAR(TR.START_DATE, 'DD-MON-YYYY')) AS START_DATE_BS"),
            new Expression("INITCAP(TO_CHAR(TR.END_DATE, 'DD-MON-YYYY')) AS END_DATE"),
            new Expression("INITCAP(TO_CHAR(TR.END_DATE, 'DD-MON-YYYY')) AS END_DATE_AD"),
            new Expression("BS_DATE(TO_CHAR(TR.END_DATE, 'DD-MON-YYYY')) AS END_DATE_BS"),
            new Expression("TR.TRAINING_ID AS TRAINING_ID"),
            new Expression("(CASE WHEN TR.TRAINING_ID IS NULL THEN TR.TITLE ELSE T.TRAINING_NAME END) AS TITLE"),
            new Expression("TR.DESCRIPTION AS DESCRIPTION"),
            new Expression("TR.DURATION AS DURATION"),
            new Expression("TR.TRAINING_TYPE AS TRAINING_TYPE"),
            new Expression("(CASE WHEN TR.TRAINING_TYPE = 'CC' THEN 'Company Contribution' ELSE 'Personal' END) AS TRAINING_TYPE_DETAIL"),
            new Expression("TR.REMARKS AS REMARKS"),
            new Expression("TR.STATUS AS STATUS"),
            new Expression("LEAVE_STATUS_DESC(TR.STATUS) AS STATUS_DETAIL"),
            new Expression("TR.RECOMMENDED_BY AS RECOMMENDED_BY"),
            new Expression("INITCAP(TO_CHAR(TR.RECOMMENDED_DATE, 'DD-MON-YYYY')) AS RECOMMENDED_DATE"),
            new Expression("TR.RECOMMENDED_REMARKS AS RECOMMENDED_REMARKS"),
            new Expression("TR.APPROVED_BY AS APPROVED_BY"),
            new Expression("INITCAP(TO_CHAR(TR.APPROVED_DATE, 'DD-MON-YYYY')) AS APPROVED_DATE"),
            new Expression("TR.APPROVED_REMARKS AS APPROVED_REMARKS"),
            new Expression("INITCAP(TO_CHAR(TR.MODIFIED_DATE, 'DD-MON-YYYY')) AS MODIFIED_DATE"),
            ], true);

        $select->from(['TR' => TrainingRequest::TABLE_NAME])
            ->join(['T' => Training::TABLE_NAME], "T." . Training::TRAINING_ID . "=TR." . TrainingRequest::TRAINING_ID, [
                Training::TRAINING_CODE,
                "TRAINING_INSTRUCTOR_NAME" => new Expression("INITCAP(T.INSTRUCTOR_NAME)"),
                "TRAINING_NAME" => new Expression("INITCAP(T.TRAINING_NAME)"),
                "TRAINING_START_DATE" => new Expression("INITCAP(TO_CHAR(T.START_DATE, 'DD-MON-YYYY'))"),
                "TRAINING_END_DATE" => new Expression("INITCAP(TO_CHAR(T.END_DATE, 'DD-MON-YYYY'))"),
                "TRAINING_DURATION" => Training::DURATION,
                "TRAINING_TRAINING_TYPE" => Training::TRAINING_TYPE], "left")
            ->join(['E' => 'HRIS_EMPLOYEES'], 'E.EMPLOYEE_ID=TR.EMPLOYEE_ID', ["FULL_NAME" => new Expression("INITCAP(E.FULL_NAME)")], "left")
            ->join(['E2' => "HRIS_EMPLOYEES"], "E2.EMPLOYEE_ID=TR.RECOMMENDED_BY", ['RECOMMENDED_BY_NAME' => new Expression("INITCAP(E2.FULL_NAME)")], "left")
            ->join(['E3' => "HRIS_EMPLOYEES"], "E3.EMPLOYEE_ID=TR.APPROVED_BY", ['APPROVED_BY_NAME' => new Expression("INITCAP(E3.FULL_NAME)")], "left")
            ->join(['RA' => "HRIS_RECOMMENDER_APPROVER"], "RA.EMPLOYEE_ID=TR.EMPLOYEE_ID", ['RECOMMENDER_ID' => 'RECOMMEND_BY', 'APPROVER_ID' => 'APPROVED_BY'], "left")
            ->join(['RECM' => "HRIS_EMPLOYEES"], "RECM.EMPLOYEE_ID=RA.RECOMMEND_BY", ['RECOMMENDER_NAME' => new Expression("INITCAP(RECM.FULL_NAME)")], "left")
            ->join(['APRV' => "HRIS_EMPLOYEES"], "APRV.EMPLOYEE_ID=RA.APPROVED_BY", ['APPROVER_NAME' => new Expression("INITCAP(APRV.FULL_NAME)")], "left");

        $select->where(["TR.REQUEST_ID" => $id]);
        $select->order(["TR.REQUESTED_DATE" => Select::ORDER_DESCENDING]);
        $statement = $sql->prepareStatementForSqlObject($select);
        $result = $statement->execute();
        return $result->current();
    }

    public function getPendingList($search) {
        $sql = "SELECT TR.REQUEST_ID,
                  TR.EMPLOYEE_ID,
                  E.FULL_NAME                                            AS FULL_NAME,
                  E.EMPLOYEE_CODE                                            AS EMPLOYEE_CODE,
                  INITCAP(TO_CHAR(TR.REQUESTED_DATE, 'DD-MON-YYYY')) AS REQUESTED_DATE,
                  BS_DATE(TO_CHAR(TR.REQUESTED_DATE, 'DD-MON-YYYY')) AS REQUESTED_DATE_BS,
                  TR.APPROVED_BY,
                  TR.RECOMMENDED_BY,
                  TR.REMARKS,
                  (
                  CASE
                    WHEN TR.TRAINING_ID IS NULL
                    THEN TR.DURATION
                    ELSE T.DURATION
                  END) AS DURATION ,
                  TR.DESCRIPTION,
                  INITCAP(
                  CASE
                    WHEN TR.TRAINING_ID IS NULL
                    THEN TR.TITLE
                    ELSE T.TRAINING_NAME
                  END) AS TITLE,
                  TR.STATUS,
                  TR.TRAINING_ID,
                  TRAINING_TYPE_DESC(
                  CASE
                    WHEN TR.TRAINING_ID IS NULL
                    THEN TR.TRAINING_TYPE
                    ELSE T.TRAINING_TYPE
                  END) AS TRAINING_TYPE,
                  TR.RECOMMENDED_REMARKS,
                  TR.APPROVED_REMARKS,
                  (
                  CASE
                    WHEN TR.TRAINING_ID IS NULL
                    THEN INITCAP(TO_CHAR(TR.START_DATE, 'DD-MON-YYYY'))
                    ELSE INITCAP(TO_CHAR(T.START_DATE, 'DD-MON-YYYY'))
                  END) AS START_DATE,
                  (
                  CASE
                    WHEN TR.TRAINING_ID IS NULL
                    THEN BS_DATE(TR.START_DATE)
                    ELSE BS_DATE(T.START_DATE)
                  END) AS START_DATE_BS,
                  (
                  CASE
                    WHEN TR.TRAINING_ID IS NULL
                    THEN INITCAP(TO_CHAR(TR.END_DATE, 'DD-MON-YYYY'))
                    ELSE INITCAP(TO_CHAR(T.END_DATE, 'DD-MON-YYYY'))
                  END) AS END_DATE,
                  (
                  CASE
                    WHEN TR.TRAINING_ID IS NULL
                    THEN BS_DATE(TR.END_DATE)
                    ELSE BS_DATE(T.END_DATE)
                  END)                                                            AS END_DATE_BS,
                  TR.RECOMMENDED_BY                                               AS RECOMMENDED_BY,
                  RE.FULL_NAME                                                    AS RECOMMENDED_BY_NAME,
                  INITCAP(TO_CHAR(TR.RECOMMENDED_DATE, 'DD-MON-YYYY'))            AS RECOMMENDED_DATE,
                  TR.APPROVED_BY                                                  AS APPROVED_BY,
                  AE.FULL_NAME                                                    AS APPROVED_BY_NAME,
                  INITCAP(TO_CHAR(TR.APPROVED_DATE, 'DD-MON-YYYY'))               AS APPROVED_DATE,
                  INITCAP(TO_CHAR(TR.MODIFIED_DATE, 'DD-MON-YYYY'))               AS MODIFIED_DATE,
                  RAR.EMPLOYEE_ID                                                 AS RECOMMENDER_ID,
                  RAR.FULL_NAME                                                   AS RECOMMENDER_NAME,
                  RAA.EMPLOYEE_ID                                                 AS APPROVER_ID,
                  RAA.FULL_NAME                                                   AS APPROVER_NAME,
                  TR.STATUS                                                       AS STATUS ,
                  LEAVE_STATUS_DESC(TR.STATUS)                                    AS STATUS_DETAIL ,
                  REC_APP_ROLE(U.EMPLOYEE_ID,
                  CASE WHEN ALR.R_A_ID IS NOT NULL THEN ALR.R_A_ID ELSE RA.RECOMMEND_BY END,
                  CASE WHEN ALA.R_A_ID IS NOT NULL THEN ALA.R_A_ID ELSE RA.APPROVED_BY END
                  )      AS ROLE,
                  REC_APP_ROLE_NAME(U.EMPLOYEE_ID,
                  CASE WHEN ALR.R_A_ID IS NOT NULL THEN ALR.R_A_ID ELSE RA.RECOMMEND_BY END,
                  CASE WHEN ALA.R_A_ID IS NOT NULL THEN ALA.R_A_ID ELSE RA.APPROVED_BY END
                  ) AS YOUR_ROLE
                FROM HRIS_EMPLOYEE_TRAINING_REQUEST TR
                LEFT JOIN HRIS_TRAINING_MASTER_SETUP T
                ON T.TRAINING_ID=TR.TRAINING_ID
                LEFT JOIN HRIS_EMPLOYEES E
                ON E.EMPLOYEE_ID=TR.EMPLOYEE_ID
                LEFT JOIN HRIS_EMPLOYEES RE
                ON(RE.EMPLOYEE_ID =TR.RECOMMENDED_BY)
                LEFT JOIN HRIS_EMPLOYEES AE
                ON (AE.EMPLOYEE_ID =TR.APPROVED_BY)
                LEFT JOIN HRIS_RECOMMENDER_APPROVER RA
                ON E.EMPLOYEE_ID =RA.EMPLOYEE_ID
                LEFT JOIN HRIS_EMPLOYEES RAR
                ON (RA.RECOMMEND_BY=RAR.EMPLOYEE_ID)
                LEFT JOIN HRIS_EMPLOYEES RAA
                ON(RA.APPROVED_BY=RAA.EMPLOYEE_ID)
                LEFT JOIN HRIS_ALTERNATE_R_A ALR
                ON(ALR.R_A_FLAG='R' AND ALR.EMPLOYEE_ID=TR.EMPLOYEE_ID AND ALR.R_A_ID=:userId)
                LEFT JOIN HRIS_ALTERNATE_R_A ALA
                ON(ALA.R_A_FLAG='A' AND ALA.EMPLOYEE_ID=TR.EMPLOYEE_ID AND ALA.R_A_ID=:userId)
                LEFT JOIN HRIS_EMPLOYEES U
                ON(U.EMPLOYEE_ID = RA.RECOMMEND_BY
                OR U.EMPLOYEE_ID =RA.APPROVED_BY
                OR U.EMPLOYEE_ID   =ALR.R_A_ID
                OR U.EMPLOYEE_ID   =ALA.R_A_ID)
                WHERE 1          =1
                AND ((
                (
                (RA.RECOMMEND_BY= U.EMPLOYEE_ID)
                OR(ALR.R_A_ID= U.EMPLOYEE_ID)
                )
                AND TR.STATUS IN ('RQ')) 
                OR (
                ((RA.APPROVED_BY= U.EMPLOYEE_ID)
                OR(ALA.R_A_ID= U.EMPLOYEE_ID)
                )
                AND TR.STATUS IN ('RC')) )
                AND U.EMPLOYEE_ID=:userId";
                $boundedParameter = [];
                $boundedParameter['userId'] = $search['userId'];
        return $this->rawQuery($sql, $boundedParameter);
    }

    public function getAllList($search): array {
        $condition = "";
        $boundedParameter = [];
        if (isset($search['fromDate']) && $search['fromDate'] != null) {
            $condition .= " AND TR.START_DATE>=TO_DATE(:fromDate,'DD-MM-YYYY') ";
            $boundedParameter['fromDate'] = $search['fromDate'];
        }
        if (isset($search['toDate']) && $search['toDate'] != null) {
            $condition .= " AND TR.END_DATE<=TO_DATE(:toDate,'DD-MM-YYYY') ";
            $boundedParameter['toDate'] = $search['toDate'];
        }
        $boundedParameter['userId'] = $search['userId'];

        if (isset($search['status']) && $search['status'] != null && $search['status'] != -1) {
            if (gettype($search['status']) === 'array') {
                $csv = "";
                for ($i = 0; $i < sizeof($search['status']); $i++) {
                    if ($i == 0) {
                        $csv = ":status".$i;
                        $boundedParameter["status".$i] = $search['status'][$i];
                    } else {
                        $csv .= ",:status".$i;
                        $boundedParameter["status".$i] = $search['status'][$i];
                    }
                }
                $condition .= "AND TR.STATUS IN ({$csv})";
            } else {
                $condition .= "AND TR.STATUS IN (:status)";
                $boundedParameter['status'] = $search['status'];
            }
        }

        $sql = "SELECT TR.REQUEST_ID,E.EMPLOYEE_CODE,
                  TR.EMPLOYEE_ID,
                  E.FULL_NAME                                            AS FULL_NAME,
                  INITCAP(TO_CHAR(TR.REQUESTED_DATE, 'DD-MON-YYYY')) AS REQUESTED_DATE,
                  BS_DATE(TO_CHAR(TR.REQUESTED_DATE, 'DD-MON-YYYY')) AS REQUESTED_DATE_BS,
                  TR.APPROVED_BY,
                  TR.RECOMMENDED_BY,
                  TR.REMARKS,
                  (
                  CASE
                    WHEN TR.TRAINING_ID IS NULL
                    THEN TR.DURATION
                    ELSE T.DURATION
                  END) AS DURATION ,
                  TR.DESCRIPTION,
                  INITCAP(
                  CASE
                    WHEN TR.TRAINING_ID IS NULL
                    THEN TR.TITLE
                    ELSE T.TRAINING_NAME
                  END) AS TITLE,
                  TR.STATUS,
                  TR.TRAINING_ID,
                  TRAINING_TYPE_DESC(
                  CASE
                    WHEN TR.TRAINING_ID IS NULL
                    THEN TR.TRAINING_TYPE
                    ELSE T.TRAINING_TYPE
                  END) AS TRAINING_TYPE,
                  TR.RECOMMENDED_REMARKS,
                  TR.APPROVED_REMARKS,
                  (
                  CASE
                    WHEN TR.TRAINING_ID IS NULL
                    THEN INITCAP(TO_CHAR(TR.START_DATE, 'DD-MON-YYYY'))
                    ELSE INITCAP(TO_CHAR(T.START_DATE, 'DD-MON-YYYY'))
                  END) AS START_DATE,
                  (
                  CASE
                    WHEN TR.TRAINING_ID IS NULL
                    THEN BS_DATE(TR.START_DATE)
                    ELSE BS_DATE(T.START_DATE)
                  END) AS START_DATE_BS,
                  (
                  CASE
                    WHEN TR.TRAINING_ID IS NULL
                    THEN INITCAP(TO_CHAR(TR.END_DATE, 'DD-MON-YYYY'))
                    ELSE INITCAP(TO_CHAR(T.END_DATE, 'DD-MON-YYYY'))
                  END) AS END_DATE,
                  (
                  CASE
                    WHEN TR.TRAINING_ID IS NULL
                    THEN BS_DATE(TR.END_DATE)
                    ELSE BS_DATE(T.END_DATE)
                  END)                                                            AS END_DATE_BS,
                  TR.RECOMMENDED_BY                                               AS RECOMMENDED_BY,
                  RE.FULL_NAME                                                    AS RECOMMENDED_BY_NAME,
                  INITCAP(TO_CHAR(TR.RECOMMENDED_DATE, 'DD-MON-YYYY'))            AS RECOMMENDED_DATE,
                  TR.APPROVED_BY                                                  AS APPROVED_BY,
                  AE.FULL_NAME                                                    AS APPROVED_BY_NAME,
                  INITCAP(TO_CHAR(TR.APPROVED_DATE, 'DD-MON-YYYY'))               AS APPROVED_DATE,
                  INITCAP(TO_CHAR(TR.MODIFIED_DATE, 'DD-MON-YYYY'))               AS MODIFIED_DATE,
                  RAR.EMPLOYEE_ID                                                 AS RECOMMENDER_ID,
                  RAR.FULL_NAME                                                   AS RECOMMENDER_NAME,
                  RAA.EMPLOYEE_ID                                                 AS APPROVER_ID,
                  RAA.FULL_NAME                                                   AS APPROVER_NAME,
                  TR.STATUS                                                       AS STATUS ,
                  LEAVE_STATUS_DESC(TR.STATUS)                                    AS STATUS_DETAIL ,
                  REC_APP_ROLE(U.EMPLOYEE_ID,
                  CASE WHEN ALR.R_A_ID IS NOT NULL THEN ALR.R_A_ID ELSE RA.RECOMMEND_BY END,
                  CASE WHEN ALA.R_A_ID IS NOT NULL THEN ALA.R_A_ID ELSE RA.APPROVED_BY END
                  )      AS ROLE,
                  REC_APP_ROLE_NAME(U.EMPLOYEE_ID,
                  CASE WHEN ALR.R_A_ID IS NOT NULL THEN ALR.R_A_ID ELSE RA.RECOMMEND_BY END,
                  CASE WHEN ALA.R_A_ID IS NOT NULL THEN ALA.R_A_ID ELSE RA.APPROVED_BY END
                  ) AS YOUR_ROLE
                FROM HRIS_EMPLOYEE_TRAINING_REQUEST TR
                LEFT JOIN HRIS_TRAINING_MASTER_SETUP T
                ON T.TRAINING_ID=TR.TRAINING_ID
                LEFT JOIN HRIS_EMPLOYEES E
                ON E.EMPLOYEE_ID=TR.EMPLOYEE_ID
                LEFT JOIN HRIS_EMPLOYEES RE
                ON(RE.EMPLOYEE_ID =TR.RECOMMENDED_BY)
                LEFT JOIN HRIS_EMPLOYEES AE
                ON (AE.EMPLOYEE_ID =TR.APPROVED_BY)
                LEFT JOIN HRIS_RECOMMENDER_APPROVER RA
                ON E.EMPLOYEE_ID =RA.EMPLOYEE_ID
                LEFT JOIN HRIS_EMPLOYEES RAR
                ON (RA.RECOMMEND_BY=RAR.EMPLOYEE_ID)
                LEFT JOIN HRIS_EMPLOYEES RAA
                ON(RA.APPROVED_BY=RAA.EMPLOYEE_ID)
                LEFT JOIN HRIS_ALTERNATE_R_A ALR
                ON(ALR.R_A_FLAG='R' AND ALR.EMPLOYEE_ID=TR.EMPLOYEE_ID AND ALR.R_A_ID={$search['userId']})
                LEFT JOIN HRIS_ALTERNATE_R_A ALA
                ON(ALA.R_A_FLAG='A' AND ALA.EMPLOYEE_ID=TR.EMPLOYEE_ID AND ALA.R_A_ID={$search['userId']})
                LEFT JOIN HRIS_EMPLOYEES U
                ON(U.EMPLOYEE_ID = RA.RECOMMEND_BY
                OR U.EMPLOYEE_ID =RA.APPROVED_BY
                OR U.EMPLOYEE_ID   =ALR.R_A_ID
                OR U.EMPLOYEE_ID   =ALA.R_A_ID)
                WHERE 1          =1
                AND U.EMPLOYEE_ID= :userId {$condition}";
        return $this->rawQuery($sql, $boundedParameter);
    }

    public function getListAdmin($search) {
        
        $fromDate = $search['fromDate'];
        $toDate = $search['toDate'];
        $employeeId = $search['employeeId'];
        $companyId = $search['companyId'];
        $branchId = $search['branchId'];
        $departmentId = $search['departmentId'];
        $designationId = $search['designationId'];
        $positionId = $search['positionId'];
        $serviceTypeId = $search['serviceTypeId'];
        $serviceEventTypeId = $search['serviceEventTypeId'];
        $status = $search['status'];
        $functionalTypeId = $search['functionalTypeId'];
        $employeeTypeId = $search['employeeTypeId'];
        $searchCondition = EntityHelper::getSearchConditonBounded($companyId, $branchId, $departmentId, $positionId, $designationId, $serviceTypeId, $serviceEventTypeId, $employeeTypeId, $employeeId, null, null, $functionalTypeId);
        $boundedParameter = [];
        $boundedParameter=array_merge($boundedParameter, $searchCondition['parameter']);
        
        $condition = "";
        if (isset($search['fromDate']) && $search['fromDate'] != null) {
            $condition .= " AND TR.START_DATE>=TO_DATE(:fromDate,'DD-MM-YYYY') ";
            $boundedParameter['fromDate'] = $fromDate;
        }
        if (isset($search['fromDate']) && $search['toDate'] != null) {
            $condition .= " AND TR.END_DATE<=TO_DATE(:toDate,'DD-MM-YYYY') ";
            $boundedParameter['toDate'] = $toDate;
        }


        if (isset($search['status']) && $search['status'] != null && $search['status'] != -1) {
            if (gettype($search['status']) === 'array') {
                $csv = "";
                for ($i = 0; $i < sizeof($search['status']); $i++) {
                    if ($i == 0) {
                        $csv = ":status".$i;
                        $boundedParameter["status".$i] = $search['status'][$i];
                    } else {
                        $csv .= ",:status".$i;
                        $boundedParameter["status".$i] = $search['status'][$i];
                    }
                }
                $condition .= "AND TR.STATUS IN ({$csv})";
            } else {
                $condition .= "AND TR.STATUS IN (:status)";
                $boundedParameter['status'] = $search['status'];
            }
        }

        $sql = "SELECT TR.REQUEST_ID,
                  TR.EMPLOYEE_ID,
                  E.EMPLOYEE_CODE,
                  E.FULL_NAME                                        AS FULL_NAME,
                  INITCAP(TO_CHAR(TR.REQUESTED_DATE, 'DD-MON-YYYY')) AS REQUESTED_DATE,
                  BS_DATE(TO_CHAR(TR.REQUESTED_DATE, 'DD-MON-YYYY')) AS REQUESTED_DATE_BS,
                  TR.REMARKS,
                  (
                  CASE
                    WHEN TR.TRAINING_ID IS NULL
                    THEN TR.DURATION
                    ELSE T.DURATION
                  END) AS DURATION ,
                  TR.DESCRIPTION,
                  INITCAP(
                  CASE
                    WHEN TR.TRAINING_ID IS NULL
                    THEN TR.TITLE
                    ELSE T.TRAINING_NAME
                  END) AS TITLE,
                  TR.TRAINING_ID,
                  TRAINING_TYPE_DESC(
                  CASE
                    WHEN TR.TRAINING_ID IS NULL
                    THEN TR.TRAINING_TYPE
                    ELSE T.TRAINING_TYPE
                  END) AS TRAINING_TYPE,
                  TR.RECOMMENDED_REMARKS,
                  TR.APPROVED_REMARKS,
                  (
                  CASE
                    WHEN TR.TRAINING_ID IS NULL
                    THEN INITCAP(TO_CHAR(TR.START_DATE, 'DD-MON-YYYY'))
                    ELSE INITCAP(TO_CHAR(T.START_DATE, 'DD-MON-YYYY'))
                  END) AS START_DATE,
                  (
                  CASE
                    WHEN TR.TRAINING_ID IS NULL
                    THEN BS_DATE(TR.START_DATE)
                    ELSE BS_DATE(T.START_DATE)
                  END) AS START_DATE_BS,
                  (
                  CASE
                    WHEN TR.TRAINING_ID IS NULL
                    THEN INITCAP(TO_CHAR(TR.END_DATE, 'DD-MON-YYYY'))
                    ELSE INITCAP(TO_CHAR(T.END_DATE, 'DD-MON-YYYY'))
                  END) AS END_DATE,
                  (
                  CASE
                    WHEN TR.TRAINING_ID IS NULL
                    THEN BS_DATE(TR.END_DATE)
                    ELSE BS_DATE(T.END_DATE)
                  END)                                                 AS END_DATE_BS,
                  TR.RECOMMENDED_BY                                    AS RECOMMENDED_BY,
                  RE.FULL_NAME                                         AS RECOMMENDED_BY_NAME,
                  INITCAP(TO_CHAR(TR.RECOMMENDED_DATE, 'DD-MON-YYYY')) AS RECOMMENDED_DATE,
                  TR.APPROVED_BY                                       AS APPROVED_BY,
                  AE.FULL_NAME                                         AS APPROVED_BY_NAME,
                  INITCAP(TO_CHAR(TR.APPROVED_DATE, 'DD-MON-YYYY'))    AS APPROVED_DATE,
                  INITCAP(TO_CHAR(TR.MODIFIED_DATE, 'DD-MON-YYYY'))    AS MODIFIED_DATE,
                  RAR.EMPLOYEE_ID                                      AS RECOMMENDER_ID,
                  RAR.FULL_NAME                                        AS RECOMMENDER_NAME,
                  RAA.EMPLOYEE_ID                                      AS APPROVER_ID,
                  RAA.FULL_NAME                                        AS APPROVER_NAME,
                  TR.STATUS                                            AS STATUS ,
                  LEAVE_STATUS_DESC(TR.STATUS)                         AS STATUS_DETAIL
                FROM HRIS_EMPLOYEE_TRAINING_REQUEST TR
                LEFT JOIN HRIS_TRAINING_MASTER_SETUP T
                ON T.TRAINING_ID=TR.TRAINING_ID
                LEFT JOIN HRIS_EMPLOYEES E
                ON E.EMPLOYEE_ID=TR.EMPLOYEE_ID
                LEFT JOIN HRIS_EMPLOYEES RE
                ON(RE.EMPLOYEE_ID =TR.RECOMMENDED_BY)
                LEFT JOIN HRIS_EMPLOYEES AE
                ON (AE.EMPLOYEE_ID =TR.APPROVED_BY)
                LEFT JOIN HRIS_RECOMMENDER_APPROVER RA
                ON E.EMPLOYEE_ID =RA.EMPLOYEE_ID
                LEFT JOIN HRIS_EMPLOYEES RAR
                ON (RA.RECOMMEND_BY=RAR.EMPLOYEE_ID)
                LEFT JOIN HRIS_EMPLOYEES RAA
                ON(RA.APPROVED_BY=RAA.EMPLOYEE_ID)
                WHERE 1          =1 {$searchCondition['sql']} {$condition}";
                
        $finalSql = $this->getPrefReportQuery($sql);
        return $this->rawQuery($finalSql, $boundedParameter);
    }
}
