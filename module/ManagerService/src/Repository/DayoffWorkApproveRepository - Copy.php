<?php

namespace ManagerService\Repository;

use Application\Helper\EntityHelper;
use Application\Model\Model;
use Application\Repository\RepositoryInterface;
use SelfService\Model\WorkOnDayoff;
use Zend\Db\Adapter\AdapterInterface;
use Zend\Db\Sql\Expression;
use Zend\Db\Sql\Sql;
use Zend\Db\TableGateway\TableGateway;

class DayoffWorkApproveRepository implements RepositoryInterface {

    private $tableGateway;
    private $adapter;

    public function __construct(AdapterInterface $adapter) {
        $this->adapter = $adapter;
        $this->tableGateway = new TableGateway(WorkOnDayoff::TABLE_NAME, $adapter);
    }

    public function add(Model $model) {
        
    }

    public function delete($id) {
        
    }

    public function getAllWidStatus($id, $status) {
        
    }

    public function edit(Model $model, $id) {
        $temp = $model->getArrayCopyForDB();
        $this->tableGateway->update($temp, [WorkOnDayoff::ID => $id]);
        $sql = "
            DECLARE
                  V_ID HRIS_EMPLOYEE_WORK_DAYOFF.ID%TYPE;
                  V_STATUS HRIS_EMPLOYEE_WORK_DAYOFF.STATUS%TYPE;
                  V_FROM_DATE HRIS_EMPLOYEE_WORK_DAYOFF.FROM_DATE%TYPE;
                  V_TO_DATE HRIS_EMPLOYEE_WORK_DAYOFF.TO_DATE%TYPE;
                  V_EMPLOYEE_ID HRIS_EMPLOYEE_WORK_DAYOFF.EMPLOYEE_ID%TYPE;
                BEGIN
                  SELECT ID,
                    STATUS,
                    FROM_DATE,
                    TO_DATE,
                    EMPLOYEE_ID
                  INTO V_ID,
                    V_STATUS,
                    V_FROM_DATE,
                    V_TO_DATE,
                    V_EMPLOYEE_ID
                  FROM HRIS_EMPLOYEE_WORK_DAYOFF
                  WHERE ID                                    = {$id};
                  IF(V_STATUS IN ('AP','C','R') and V_FROM_DATE <= trunc(SYSDATE)) THEN
                    HRIS_REATTENDANCE(V_FROM_DATE,V_EMPLOYEE_ID,V_TO_DATE);
                  END IF;
                END;
            ";
        EntityHelper::rawQueryResult($this->adapter, $sql);
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
            new Expression("WD.RECOMMENDED_BY AS RECOMMENDED_BY"),
            new Expression("INITCAP(TO_CHAR(WD.RECOMMENDED_DATE, 'DD-MON-YYYY')) AS RECOMMENDED_DATE"),
            new Expression("WD.RECOMMENDED_REMARKS AS RECOMMENDED_REMARKS"),
            new Expression("WD.APPROVED_BY AS APPROVED_BY"),
            new Expression("INITCAP(TO_CHAR(WD.APPROVED_DATE, 'DD-MON-YYYY')) AS APPROVED_DATE"),
            new Expression("WD.APPROVED_REMARKS AS APPROVED_REMARKS"),
            new Expression("INITCAP(TO_CHAR(WD.MODIFIED_DATE, 'DD-MON-YYYY')) AS MODIFIED_DATE"),
                ], true);

        $select->from(['WD' => WorkOnDayoff::TABLE_NAME])
                ->join(['E' => "HRIS_EMPLOYEES"], "E.EMPLOYEE_ID=WD.EMPLOYEE_ID", ["FULL_NAME" => new Expression("INITCAP(E.FULL_NAME)")], "left")
                ->join(['E1' => "HRIS_EMPLOYEES"], "E1.EMPLOYEE_ID=WD.RECOMMENDED_BY", ['RECOMMENDED_BY_NAME' => new Expression("INITCAP(E1.FULL_NAME)")], "left")
                ->join(['E2' => "HRIS_EMPLOYEES"], "E2.EMPLOYEE_ID=WD.APPROVED_BY", ['APPROVED_BY_NAME' => new Expression("INITCAP(E2.FULL_NAME)")], "left")
                ->join(['RA' => "HRIS_RECOMMENDER_APPROVER"], "RA.EMPLOYEE_ID=WD.EMPLOYEE_ID", ['RECOMMENDER_ID' => 'RECOMMEND_BY', 'APPROVER_ID' => 'APPROVED_BY'], "left")
                ->join(['RECM' => "HRIS_EMPLOYEES"], "RECM.EMPLOYEE_ID=RA.RECOMMEND_BY", ['RECOMMENDER_NAME' => new Expression("INITCAP(RECM.FULL_NAME)")], "left")
                ->join(['APRV' => "HRIS_EMPLOYEES"], "APRV.EMPLOYEE_ID=RA.APPROVED_BY", ['APPROVER_NAME' => new Expression("INITCAP(APRV.FULL_NAME)")], "left");

        $select->where([
            "WD.ID=" . $id
        ]);

        $statement = $sql->prepareStatementForSqlObject($select);
        $result = $statement->execute();
        return $result->current();
    }

    public function getAllRequest($id, $status = null) {
        $sql = "SELECT 
                    WD.ID,
                    E.EMPLOYEE_CODE AS EMPLOYEE_CODE,
                    WD.EMPLOYEE_ID,
                    INITCAP(TO_CHAR(WD.REQUESTED_DATE, 'DD-MON-YYYY')) AS REQUESTED_DATE,
                    BS_DATE(TO_CHAR(WD.REQUESTED_DATE, 'DD-MON-YYYY')) AS REQUESTED_DATE_N,
                    WD.APPROVED_BY,
                    WD.RECOMMENDED_BY,
                    WD.REMARKS,
                    WD.DURATION,
                    WD.RECOMMENDED_REMARKS,
                    WD.APPROVED_REMARKS,
                    INITCAP(TO_CHAR(WD.FROM_DATE, 'DD-MON-YYYY')) AS FROM_DATE,
                    BS_DATE(TO_CHAR(WD.FROM_DATE, 'DD-MON-YYYY')) AS FROM_DATE_N,
                    INITCAP(TO_CHAR(WD.TO_DATE, 'DD-MON-YYYY')) AS TO_DATE,
                    BS_DATE(TO_CHAR(WD.TO_DATE, 'DD-MON-YYYY')) AS TO_DATE_N,
                    INITCAP(TO_CHAR(WD.RECOMMENDED_DATE, 'DD-MON-YYYY')) AS RECOMMENDED_DATE,
                    INITCAP(TO_CHAR(WD.APPROVED_DATE, 'DD-MON-YYYY')) AS APPROVED_DATE,
                    INITCAP(TO_CHAR(WD.MODIFIED_DATE, 'DD-MON-YYYY')) AS MODIFIED_DATE,
                    INITCAP(E.FIRST_NAME) AS FIRST_NAME,
                    INITCAP(E.MIDDLE_NAME) AS MIDDLE_NAME,
                    INITCAP(E.LAST_NAME) AS LAST_NAME,
                    INITCAP(E.FULL_NAME) AS FULL_NAME,
                    RA.RECOMMEND_BY as RECOMMENDER,
                    RA.APPROVED_BY AS APPROVER,
                    LEAVE_STATUS_DESC(WD.STATUS)                     AS STATUS,
                    REC_APP_ROLE(U.EMPLOYEE_ID,
                  CASE WHEN ALR.R_A_ID IS NOT NULL THEN ALR.R_A_ID ELSE RA.RECOMMEND_BY END,
                  CASE WHEN ALA.R_A_ID IS NOT NULL THEN ALA.R_A_ID ELSE RA.APPROVED_BY END
                  )      AS ROLE,
                    REC_APP_ROLE_NAME(U.EMPLOYEE_ID,
                  CASE WHEN ALR.R_A_ID IS NOT NULL THEN ALR.R_A_ID ELSE RA.RECOMMEND_BY END,
                  CASE WHEN ALA.R_A_ID IS NOT NULL THEN ALA.R_A_ID ELSE RA.APPROVED_BY END
                  ) AS YOUR_ROLE
                    FROM HRIS_EMPLOYEE_WORK_DAYOFF WD
                    LEFT JOIN HRIS_EMPLOYEES E ON 
                    E.EMPLOYEE_ID=WD.EMPLOYEE_ID
                    LEFT JOIN HRIS_RECOMMENDER_APPROVER RA
                    ON E.EMPLOYEE_ID=RA.EMPLOYEE_ID
                    LEFT JOIN HRIS_ALTERNATE_R_A ALR
                    ON (ALR.R_A_FLAG='R' AND ALR.EMPLOYEE_ID=WD.EMPLOYEE_ID AND ALR.R_A_ID={$id})
                    LEFT JOIN HRIS_ALTERNATE_R_A ALA
                    ON (ALA.R_A_FLAG='A' AND ALA.EMPLOYEE_ID=WD.EMPLOYEE_ID AND ALA.R_A_ID={$id})
                    LEFT JOIN HRIS_EMPLOYEES U
                ON(U.EMPLOYEE_ID   = RA.RECOMMEND_BY
                OR U.EMPLOYEE_ID   =RA.APPROVED_BY
                OR U.EMPLOYEE_ID   =ALR.R_A_ID
                OR U.EMPLOYEE_ID   =ALA.R_A_ID)
                    WHERE  E.STATUS='E'
                    AND E.RETIRED_FLAG='N'
                    AND ((
                (
                (RA.RECOMMEND_BY= U.EMPLOYEE_ID)
                OR(ALR.R_A_ID= U.EMPLOYEE_ID)
                )
                AND WD.STATUS IN ('RQ')) 
                OR (
                ((RA.APPROVED_BY= U.EMPLOYEE_ID)
                OR(ALA.R_A_ID= U.EMPLOYEE_ID)
                )
                AND WD.STATUS IN ('RC')) )
                AND U.EMPLOYEE_ID={$id}
                    ORDER BY WD.REQUESTED_DATE DESC";
        $statement = $this->adapter->query($sql);
        $result = $statement->execute();
        return $result;
    }

    public function wodReward($wodId) {
        EntityHelper::rawQueryResult($this->adapter, "
                    BEGIN
                      HRIS_WOD_REWARD({$wodId});
                    END;");
    }

}
