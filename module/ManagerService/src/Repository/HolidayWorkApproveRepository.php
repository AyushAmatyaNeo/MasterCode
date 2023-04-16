<?php

namespace ManagerService\Repository;

use Application\Helper\EntityHelper;
use Application\Model\Model;
use Exception;
use Notification\Controller\HeadNotification;
use Notification\Model\NotificationEvents;
use SelfService\Model\WorkOnHoliday;
use Traversable;
use Zend\Db\Adapter\AdapterInterface;
use Zend\Db\Sql\Expression;
use Zend\Db\Sql\Sql;
use Zend\Db\TableGateway\TableGateway;

class HolidayWorkApproveRepository {

    private $tableGateway;
    private $adapter;

    public function __construct(AdapterInterface $adapter) {
        $this->adapter = $adapter;
        $this->tableGateway = new TableGateway(WorkOnHoliday::TABLE_NAME, $adapter);
    }

    public function edit(Model $model, $id) {
        $temp = $model->getArrayCopyForDB();
        $this->tableGateway->update($temp, [WorkOnHoliday::ID => $id]);
        $sql = "
            DECLARE
                  V_ID HRIS_EMPLOYEE_WORK_HOLIDAY.ID%TYPE;
                  V_STATUS HRIS_EMPLOYEE_WORK_HOLIDAY.STATUS%TYPE;
                  V_FROM_DATE HRIS_EMPLOYEE_WORK_HOLIDAY.FROM_DATE%TYPE;
                  V_TO_DATE HRIS_EMPLOYEE_WORK_HOLIDAY.TO_DATE%TYPE;
                  V_EMPLOYEE_ID HRIS_EMPLOYEE_WORK_HOLIDAY.EMPLOYEE_ID%TYPE;
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
                  FROM HRIS_EMPLOYEE_WORK_HOLIDAY
                  WHERE ID                                    = {$id};
                  IF(V_STATUS IN ('AP','C','R') and V_FROM_DATE <= trunc(SYSDATE)) THEN
                    HRIS_REATTENDANCE(V_FROM_DATE,V_EMPLOYEE_ID,V_TO_DATE);
                  END IF;
                END;
            ";
        EntityHelper::rawQueryResult($this->adapter, $sql);
    }

    public function fetchById($id) {
        $sql = new Sql($this->adapter);
        $select = $sql->select();
        $select->columns([
            new Expression("WH.ID AS ID"),
            new Expression("WH.EMPLOYEE_ID AS EMPLOYEE_ID"),
            new Expression("WH.HOLIDAY_ID AS HOLIDAY_ID"),
            new Expression("INITCAP(TO_CHAR(WH.REQUESTED_DATE, 'DD-MON-YYYY')) AS REQUESTED_DATE"),
            new Expression("INITCAP(TO_CHAR(WH.REQUESTED_DATE, 'DD-MON-YYYY')) AS REQUESTED_DATE_AD"),
            new Expression("BS_DATE(TO_CHAR(WH.REQUESTED_DATE, 'DD-MON-YYYY')) AS REQUESTED_DATE_BS"),
            new Expression("INITCAP(TO_CHAR(WH.FROM_DATE, 'DD-MON-YYYY')) AS FROM_DATE"),
            new Expression("INITCAP(TO_CHAR(WH.FROM_DATE, 'DD-MON-YYYY')) AS FROM_DATE_AD"),
            new Expression("BS_DATE(WH.FROM_DATE) AS FROM_DATE_BS"),
            new Expression("INITCAP(TO_CHAR(WH.TO_DATE, 'DD-MON-YYYY')) AS TO_DATE"),
            new Expression("INITCAP(TO_CHAR(WH.TO_DATE, 'DD-MON-YYYY')) AS TO_DATE_AD"),
            new Expression("BS_DATE(WH.TO_DATE) AS TO_DATE_BS"),
            new Expression("WH.DURATION AS DURATION"),
            new Expression("WH.REMARKS AS REMARKS"),
            new Expression("WH.STATUS AS STATUS"),
            new Expression("LEAVE_STATUS_DESC(WH.STATUS)                     AS STATUS_DETAIL"),
            new Expression("WH.RECOMMENDED_BY AS RECOMMENDED_BY"),
            new Expression("INITCAP(TO_CHAR(WH.RECOMMENDED_DATE, 'DD-MON-YYYY')) AS RECOMMENDED_DATE"),
            new Expression("WH.RECOMMENDED_REMARKS AS RECOMMENDED_REMARKS"),
            new Expression("WH.APPROVED_BY AS APPROVED_BY"),
            new Expression("INITCAP(TO_CHAR(WH.APPROVED_DATE, 'DD-MON-YYYY')) AS APPROVED_DATE"),
            new Expression("WH.APPROVED_REMARKS AS APPROVED_REMARKS"),
            new Expression("INITCAP(TO_CHAR(WH.MODIFIED_DATE, 'DD-MON-YYYY')) AS MODIFIED_DATE"),
                ], true);

        $select->from(['WH' => WorkOnHoliday::TABLE_NAME])
                ->join(['H' => "HRIS_HOLIDAY_MASTER_SETUP"], "H.HOLIDAY_ID=WH.HOLIDAY_ID", ["HOLIDAY_ENAME"], "left")
                ->join(['E' => "HRIS_EMPLOYEES"], "E.EMPLOYEE_ID=WH.EMPLOYEE_ID", ["FULL_NAME" => new Expression("INITCAP(E.FULL_NAME)")], "left")
                ->join(['E1' => "HRIS_EMPLOYEES"], "E1.EMPLOYEE_ID=WH.RECOMMENDED_BY", ['RECOMMENDED_BY_NAME' => new Expression("INITCAP(E1.FULL_NAME)")], "left")
                ->join(['E2' => "HRIS_EMPLOYEES"], "E2.EMPLOYEE_ID=WH.APPROVED_BY", ['APPROVED_BY_NAME' => new Expression("INITCAP(E2.FULL_NAME)")], "left")
                ->join(['RA' => "HRIS_RECOMMENDER_APPROVER"], "RA.EMPLOYEE_ID=WH.EMPLOYEE_ID", ['RECOMMENDER_ID' => 'RECOMMEND_BY', 'APPROVER_ID' => 'APPROVED_BY'], "left")
                ->join(['RECM' => "HRIS_EMPLOYEES"], "RECM.EMPLOYEE_ID=RA.RECOMMEND_BY", ['RECOMMENDER_NAME' => new Expression("INITCAP(RECM.FULL_NAME)")], "left")
                ->join(['APRV' => "HRIS_EMPLOYEES"], "APRV.EMPLOYEE_ID=RA.APPROVED_BY", ['APPROVER_NAME' => new Expression("INITCAP(APRV.FULL_NAME)")], "left");
        $select->where(["WH.ID=" . $id]);

        $statement = $sql->prepareStatementForSqlObject($select);
        $result = $statement->execute();
        return $result->current();
    }

    public function getAllRequest($id = null): Traversable {
        $sql = "SELECT 
                    WH.ID,
                    WH.EMPLOYEE_ID,
                    E.EMPLOYEE_CODE AS EMPLOYEE_CODE,
                    INITCAP(TO_CHAR(WH.REQUESTED_DATE, 'DD-MON-YYYY')) AS REQUESTED_DATE,
                    BS_DATE(TO_CHAR(WH.REQUESTED_DATE, 'DD-MON-YYYY')) AS REQUESTED_DATE_N,
                    WH.APPROVED_BY,
                    WH.RECOMMENDED_BY,
                    WH.REMARKS,
                    WH.DURATION,
                    WH.HOLIDAY_ID,
                    WH.RECOMMENDED_REMARKS,
                    WH.APPROVED_REMARKS,
                    INITCAP(TO_CHAR(WH.FROM_DATE, 'DD-MON-YYYY')) AS FROM_DATE,
                    BS_DATE(TO_CHAR(WH.FROM_DATE, 'DD-MON-YYYY')) AS FROM_DATE_N,
                    INITCAP(TO_CHAR(WH.TO_DATE, 'DD-MON-YYYY')) AS TO_DATE,
                    BS_DATE(TO_CHAR(WH.TO_DATE, 'DD-MON-YYYY')) AS TO_DATE_N,
                    INITCAP(TO_CHAR(WH.RECOMMENDED_DATE, 'DD-MON-YYYY')) AS RECOMMENDED_DATE,
                    INITCAP(TO_CHAR(WH.APPROVED_DATE, 'DD-MON-YYYY')) AS APPROVED_DATE,
                    INITCAP(TO_CHAR(WH.MODIFIED_DATE, 'DD-MON-YYYY')) AS MODIFIED_DATE,
                    INITCAP(E.FULL_NAME) AS FULL_NAME,
                    INITCAP(H.HOLIDAY_ENAME) AS HOLIDAY_ENAME,
                    INITCAP(H.HOLIDAY_LNAME) AS HOLIDAY_LNAME,
                    H.HOLIDAY_CODE,
                    RA.RECOMMEND_BY as RECOMMENDER,
                    RA.APPROVED_BY AS APPROVER,
                    WH.STATUS                                        AS STATUS,
                    LEAVE_STATUS_DESC(WH.STATUS)                     AS STATUS_DETAIL,
                      REC_APP_ROLE(U.EMPLOYEE_ID,
                  CASE WHEN ALR.R_A_ID IS NOT NULL THEN ALR.R_A_ID ELSE RA.RECOMMEND_BY END,
                  CASE WHEN ALA.R_A_ID IS NOT NULL THEN ALA.R_A_ID ELSE RA.APPROVED_BY END
                  )      AS ROLE,
                    REC_APP_ROLE_NAME(U.EMPLOYEE_ID,
                  CASE WHEN ALR.R_A_ID IS NOT NULL THEN ALR.R_A_ID ELSE RA.RECOMMEND_BY END,
                  CASE WHEN ALA.R_A_ID IS NOT NULL THEN ALA.R_A_ID ELSE RA.APPROVED_BY END
                  ) AS YOUR_ROLE
                    FROM HRIS_EMPLOYEE_WORK_HOLIDAY WH
                    LEFT JOIN HRIS_HOLIDAY_MASTER_SETUP H ON 
                    H.HOLIDAY_ID=WH.HOLIDAY_ID
                    LEFT JOIN HRIS_EMPLOYEES E ON 
                    E.EMPLOYEE_ID=WH.EMPLOYEE_ID
                    LEFT JOIN HRIS_RECOMMENDER_APPROVER RA
                    ON E.EMPLOYEE_ID=RA.EMPLOYEE_ID
                     LEFT JOIN HRIS_ALTERNATE_R_A ALR
                    ON (ALR.R_A_FLAG='R' AND ALR.EMPLOYEE_ID=WH.EMPLOYEE_ID AND ALR.R_A_ID={$id})
                    LEFT JOIN HRIS_ALTERNATE_R_A ALA
                    ON (ALA.R_A_FLAG='A' AND ALA.EMPLOYEE_ID=WH.EMPLOYEE_ID AND ALA.R_A_ID={$id})
                    LEFT JOIN HRIS_EMPLOYEES U
                ON(U.EMPLOYEE_ID   = RA.RECOMMEND_BY
                OR U.EMPLOYEE_ID   =RA.APPROVED_BY
                OR U.EMPLOYEE_ID   =ALR.R_A_ID
                OR U.EMPLOYEE_ID   =ALA.R_A_ID)
                    WHERE  H.STATUS = 'E' AND  E.STATUS='E'
                    AND E.RETIRED_FLAG='N' 
                    AND ((
                (
                (RA.RECOMMEND_BY= U.EMPLOYEE_ID)
                OR(ALR.R_A_ID= U.EMPLOYEE_ID)
                )
                AND WH.STATUS IN ('RQ')) 
                OR (
                ((RA.APPROVED_BY= U.EMPLOYEE_ID)
                OR(ALA.R_A_ID= U.EMPLOYEE_ID)
                )
                AND WH.STATUS IN ('RC')) )
                AND U.EMPLOYEE_ID={$id}
                    ORDER BY WH.REQUESTED_DATE DESC";
        $statement = $this->adapter->query($sql);
        $result = $statement->execute();
        return $result;
    }

    public function getWOHRuleType($employeeId) {
        return EntityHelper::rawQueryResult($this->adapter, "
                SELECT E.EMPLOYEE_ID,
                  P.WOH_FLAG
                FROM HRIS_EMPLOYEES E
                JOIN HRIS_POSITIONS P
                ON (E.POSITION_ID   = P.POSITION_ID)
                WHERE E.EMPLOYEE_ID ={$employeeId}")->current();
    }

    public function wohReward($wohId) {
      // echo '<pre>';print_r($wohId);die;
        EntityHelper::rawQueryResult($this->adapter, "
                    BEGIN
                      HRIS_WOH_REWARD({$wohId});
                    END;");
    }

}
