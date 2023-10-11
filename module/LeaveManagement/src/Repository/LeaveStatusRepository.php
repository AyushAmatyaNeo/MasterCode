<?php

namespace LeaveManagement\Repository;

use Application\Helper\EntityHelper;
use Application\Repository\HrisRepository;
use LeaveManagement\Model\LeaveApply;
use Setup\Model\HrEmployees;
use Zend\Db\Sql\Expression;
use Zend\Db\Sql\Sql;

class LeaveStatusRepository extends HrisRepository
{

    public function getAllRequest($status = null, $date = null, $branchId = NULL, $employeeId = NULL)
    {
        $sql = "SELECT INITCAP(L.LEAVE_ENAME) AS LEAVE_ENAME,
                LA.NO_OF_DAYS,
                INITCAP(TO_CHAR(LA.START_DATE, 'DD-MON-YYYY'))     AS START_DATE,
                INITCAP(TO_CHAR(LA.END_DATE, 'DD-MON-YYYY'))       AS END_DATE,
                INITCAP(TO_CHAR(LA.REQUESTED_DT, 'DD-MON-YYYY'))   AS APPLIED_DATE,
                LA.STATUS                                 AS STATUS,
                LA.ID                                     AS ID,
                INITCAP(TO_CHAR(LA.RECOMMENDED_DT, 'DD-MON-YYYY')) AS RECOMMENDED_DT,
                INITCAP(TO_CHAR(LA.APPROVED_DT, 'DD-MON-YYYY'))    AS APPROVED_DT,
                INITCAP(E.FIRST_NAME) AS FIRST_NAME,
                INITCAP(E.MIDDLE_NAME AS MIDDLE_NAME,
                INITCAP(E.LAST_NAME) AS LAST_NAME
                FROM HRIS_EMPLOYEE_LEAVE_REQUEST LA,
                  HRIS_LEAVE_MASTER_SETUP L,
                  HRIS_EMPLOYEES E
                WHERE L.STATUS   ='E'
                AND E.STATUS     ='E'
                AND L.LEAVE_ID   =LA.LEAVE_ID
                AND E.EMPLOYEE_ID=LA.EMPLOYEE_ID ";
        if ($status != null) {
            $sql .= " AND LA.STATUS ='" . $status . "'";
        }
        if ($date != null) {
            $sql .= "AND (" . $date->getExpression() . " between LA.START_DATE AND LA.END_DATE)";
        }

        if ($branchId != null) {
            $sql .= " AND E." . HrEmployees::EMPLOYEE_ID . " IN (SELECT " . HrEmployees::EMPLOYEE_ID . " FROM " . HrEmployees::TABLE_NAME . " WHERE " . HrEmployees::BRANCH_ID . "= $branchId)";
        }

        if ($employeeId != null) {
            $sql .= "AND E." . HrEmployees::EMPLOYEE_ID . " = $employeeId";
        }
        $statement = $this->adapter->query($sql);

        $result = $statement->execute();
        return $result;
    }

    public function fetchById($id)
    {
        $sql = new Sql($this->adapter);
        $select = $sql->select();
        $select->columns(EntityHelper::getColumnNameArrayWithOracleFns(LeaveApply::class, NULL, [
            LeaveApply::START_DATE,
            LeaveApply::REQUESTED_DT,
            LeaveApply::END_DATE,
            LeaveApply::APPROVED_DT
        ], NULL, NULL, NULL, 'LA'), false);


        $select->from(['LA' => LeaveApply::TABLE_NAME])
            ->join(['E' => "HRIS_EMPLOYEES"], "E.EMPLOYEE_ID=LA.EMPLOYEE_ID", ['FIRST_NAME' => new Expression('INITCAP(E.FIRST_NAME)'), 'MIDDLE_NAME' => new Expression('INITCAP(E.MIDDLE_NAME)'), 'LAST_NAME' => new Expression('INITCAP(E.LAST_NAME)')], "left")
            ->join(['E1' => "HRIS_EMPLOYEES"], "E1.EMPLOYEE_ID=LA.RECOMMENDED_BY", ['FN1' => new Expression("INITCAP(E1.FIRST_NAME)"), 'MN1' => new Expression("INITCAP(E1.MIDDLE_NAME)"), 'LN1' => new Expression("INITCAP(E1.LAST_NAME)")], "left")
            ->join(['E2' => "HRIS_EMPLOYEES"], "E2.EMPLOYEE_ID=LA.APPROVED_BY", ['FN2' => new Expression("INITCAP(E2.FIRST_NAME)"), 'MN2' => new Expression("INITCAP(E2.MIDDLE_NAME)"), 'LN2' => new Expression("INITCAP(E2.LAST_NAME)")], "left");

        $select->where([
            "LA.ID=" . $id
        ]);

        $statement = $sql->prepareStatementForSqlObject($select);
        $result = $statement->execute();
        return $result->current();
    }

    public function getFilteredRecord($data, $recomApproveId = null)
    {
        $employeeId = $data['employeeId'];
        $companyId = $data['companyId'];
        $branchId = $data['branchId'];
        $departmentId = $data['departmentId'];
        $designationId = $data['designationId'];
        $positionId = $data['positionId'];
        $serviceTypeId = $data['serviceTypeId'];
        $serviceEventTypeId = $data['serviceEventTypeId'];
        $employeeTypeId = $data['employeeTypeId'];

        $leaveRequestStatusId = $data['leaveRequestStatusId'];
        $leaveId = $data['leaveId'];
        $fromDate = $data['fromDate'];
        $toDate = $data['toDate'];


        $searchCondition = $this->getSearchConditon($companyId, $branchId, $departmentId, $positionId, $designationId, $serviceTypeId, $serviceEventTypeId, $employeeTypeId, $employeeId);
        $statusCondition = '';
        $leaveCondition = '';
        $fromDateCondition = "";
        $toDateCondition = "";
        if ($leaveRequestStatusId != -1) {
            $statusCondition = " AND LA.STATUS='{$leaveRequestStatusId}'";
        }

        if ($leaveId != null && $leaveId != -1) {
            $leaveCondition = " AND LA.LEAVE_ID = {$leaveId}";
        }

        if ($fromDate != null) {
            $fromDateCondition = " AND LA.START_DATE>=TO_DATE('{$fromDate}','DD-MM-YYYY')";
        }

        if ($toDate != null) {
            $toDateCondition = "AND LA.END_DATE<=TO_DATE('{$toDate}','DD-MM-YYYY')";
        }

        $sql = "SELECT INITCAP(L.LEAVE_ENAME) AS LEAVE_ENAME,
                  L.LEAVE_CODE,
                  (CASE WHEN (LA.HALF_DAY IS NULL OR  LA.HALF_DAY = 'N')  THEN LA.NO_OF_DAYS else la.NO_OF_DAYS/2 end) as no_of_days,
                INITCAP(TO_CHAR(LA.START_DATE, 'DD-MON-YYYY'))                  AS START_DATE_AD,
                  BS_DATE(TO_CHAR(LA.START_DATE, 'DD-MON-YYYY'))                  AS START_DATE_BS,
                  INITCAP(TO_CHAR(LA.END_DATE, 'DD-MON-YYYY'))                    AS END_DATE_AD,
                  BS_DATE(TO_CHAR(LA.END_DATE, 'DD-MON-YYYY'))                    AS END_DATE_BS,
                  INITCAP(TO_CHAR(LA.REQUESTED_DT, 'DD-MON-YYYY'))                AS APPLIED_DATE_AD,
                  BS_DATE(TO_CHAR(LA.REQUESTED_DT, 'DD-MON-YYYY'))                AS APPLIED_DATE_BS,
                  LEAVE_STATUS_DESC(LA.STATUS)                                    AS STATUS,
                  REC_APP_ROLE(U.EMPLOYEE_ID,
                  CASE WHEN L.ENABLE_OVERRIDE='Y'  THEN RAO.RECOMMENDER
                  WHEN ALR.R_A_ID IS NOT NULL THEN ALR.R_A_ID ELSE RA.RECOMMEND_BY END,
                  CASE WHEN L.ENABLE_OVERRIDE='Y'  THEN RAO.APPROVER
                  WHEN ALA.R_A_ID IS NOT NULL THEN ALA.R_A_ID ELSE RA.APPROVED_BY END
                  )      AS ROLE,
                  REC_APP_ROLE_NAME(U.EMPLOYEE_ID,
                  CASE WHEN L.ENABLE_OVERRIDE='Y'  THEN RAO.RECOMMENDER
                  WHEN ALR.R_A_ID IS NOT NULL THEN ALR.R_A_ID ELSE RA.RECOMMEND_BY END,
                  CASE WHEN L.ENABLE_OVERRIDE='Y'  THEN RAO.APPROVER
                  WHEN ALA.R_A_ID IS NOT NULL THEN ALA.R_A_ID ELSE RA.APPROVED_BY END
                  ) AS YOUR_ROLE,
                  CASE WHEN ( ALR.R_A_ID IS NOT NULL OR ALA.R_A_ID  IS NOT NULL ) THEN 'SECONDARY' ELSE 'PRIMARY' END AS PRI_SEC,
                  LA.ID                                                           AS ID,
                  LA.EMPLOYEE_ID                                                  AS EMPLOYEE_ID,
                  INITCAP(TO_CHAR(LA.RECOMMENDED_DT, 'DD-MON-YYYY'))              AS RECOMMENDED_DT,
                  INITCAP(TO_CHAR(LA.APPROVED_DT, 'DD-MON-YYYY'))                 AS APPROVED_DT,
                    E.EMPLOYEE_CODE AS EMPLOYEE_CODE,                  
                    INITCAP(E.FULL_NAME)                                            AS FULL_NAME,
                  INITCAP(E1.FULL_NAME)                                           AS RECOMMENDED_BY_NAME,
                  INITCAP(E2.FULL_NAME)                                           AS APPROVED_BY_NAME,
                  CASE WHEN ALR.R_A_ID IS NOT NULL THEN ALR.R_A_ID ELSE  RA.RECOMMEND_BY END AS RECOMMENDER_ID,
                CASE WHEN ALA.R_A_ID IS NOT NULL THEN ALA.R_A_ID ELSE  RA.APPROVED_BY END AS APPROVER_ID,
                CASE WHEN ALR_E.FULL_NAME IS NOT NULL THEN ALR_E.FULL_NAME ELSE  INITCAP(RECM.FULL_NAME) END AS RECOMMENDER_NAME,
                CASE WHEN ALA_E.FULL_NAME IS NOT NULL THEN ALA_E.FULL_NAME ELSE  INITCAP(APRV.FULL_NAME) END AS APPROVER_NAME,
                  LA.RECOMMENDED_BY                                               AS RECOMMENDED_BY,
                  LA.APPROVED_BY                                                  AS APPROVED_BY,
                  LA.RECOMMENDED_REMARKS                                          AS RECOMMENDED_REMARKS,
                  LA.APPROVED_REMARKS                                             AS APPROVED_REMARKS,
                  LS.APPROVED_FLAG                                                AS SUB_APPROVED_FLAG,
                  INITCAP(TO_CHAR(LS.APPROVED_DATE, 'DD-MON-YYYY'))               AS SUB_APPROVED_DATE,
                  LS.EMPLOYEE_ID                                                  AS SUB_EMPLOYEE_ID
                FROM HRIS_EMPLOYEE_LEAVE_REQUEST LA
                LEFT OUTER JOIN HRIS_LEAVE_MASTER_SETUP L
                ON L.LEAVE_ID=LA.LEAVE_ID
                LEFT OUTER JOIN HRIS_EMPLOYEES E
                ON E.EMPLOYEE_ID=LA.EMPLOYEE_ID
                LEFT OUTER JOIN HRIS_EMPLOYEES E1
                ON E1.EMPLOYEE_ID=LA.RECOMMENDED_BY
                LEFT OUTER JOIN HRIS_EMPLOYEES E2
                ON E2.EMPLOYEE_ID=LA.APPROVED_BY
                LEFT OUTER JOIN HRIS_RECOMMENDER_APPROVER RA
                ON LA.EMPLOYEE_ID = RA.EMPLOYEE_ID
                LEFT OUTER JOIN HRIS_EMPLOYEES RECM
                ON RECM.EMPLOYEE_ID = RA.RECOMMEND_BY
                LEFT OUTER JOIN HRIS_EMPLOYEES APRV
                ON APRV.EMPLOYEE_ID = RA.APPROVED_BY
                LEFT OUTER JOIN HRIS_LEAVE_SUBSTITUTE LS
                ON LA.ID = LS.LEAVE_REQUEST_ID
                LEFT OUTER JOIN HRIS_ALTERNATE_R_A ALR
                ON(ALR.R_A_FLAG='R' AND ALR.EMPLOYEE_ID=LA.EMPLOYEE_ID AND ALR.R_A_ID={$recomApproveId})
                LEFT OUTER JOIN HRIS_ALTERNATE_R_A ALA
                ON(ALA.R_A_FLAG='A' AND ALA.EMPLOYEE_ID=LA.EMPLOYEE_ID AND ALA.R_A_ID={$recomApproveId})
                LEFT JOIN hris_rec_app_override RAO ON E.EMPLOYEE_ID=RAO.EMPLOYEE_ID
                LEFT JOIN HRIS_EMPLOYEES ALR_E ON(ALR.R_A_ID=ALR_E.EMPLOYEE_ID)
                LEFT JOIN HRIS_EMPLOYEES ALA_E ON(ALA.R_A_ID=ALA_E.EMPLOYEE_ID)
                LEFT OUTER JOIN HRIS_EMPLOYEES U
                ON (
                (
                (U.EMPLOYEE_ID=RA.RECOMMEND_BY
                OR U.EMPLOYEE_ID =RA.APPROVED_BY
                OR U.EMPLOYEE_ID   =ALR.R_A_ID
                OR U.EMPLOYEE_ID   =ALA.R_A_ID)
                AND L.ENABLE_OVERRIDE='N' )
                OR
               (
                (U.EMPLOYEE_ID   = RAO.recommender
                OR U.EMPLOYEE_ID   =RAO.approver
               ) AND L.ENABLE_OVERRIDE='Y' 
               )
               )
                WHERE L.STATUS   ='E'
                AND E.STATUS     ='E'
                AND (LS.APPROVED_FLAG =
                  CASE
                    WHEN LS.EMPLOYEE_ID IS NOT NULL
                    THEN ('Y')
                  END
                OR LS.EMPLOYEE_ID IS NULL)
                AND U.EMPLOYEE_ID  ={$recomApproveId} {$searchCondition} {$statusCondition} {$leaveCondition} {$fromDateCondition} {$toDateCondition}
                ORDER BY LA.START_DATE DESC";
        $statement = $this->adapter->query($sql);
        $result = $statement->execute();
        return $result;
    }

    //     public function getLeaveRequestList($data): array {

    // //        print_r($data);
    // //        die();
    //         $employeeId = $data['employeeId'];
    //         $companyId = $data['companyId'];
    //         $branchId = $data['branchId'];
    //         $departmentId = $data['departmentId'];
    //         $designationId = $data['designationId'];
    //         $positionId = $data['positionId'];
    //         $serviceTypeId = $data['serviceTypeId'];
    //         $serviceEventTypeId = $data['serviceEventTypeId'];
    //         $employeeTypeId = $data['employeeTypeId'];
    //         $functionalTypeId = $data['functionalTypeId'];

    //         $leaveRequestStatusId = $data['leaveRequestStatusId'];
    //         $leaveId = $data['leaveId'];
    //         $fromDate = $data['fromDate'];
    //         $toDate = $data['toDate'];
    //         $leaveYear = $data['leaveYear'];


    //         $searchCondition = $this->getSearchConditon($companyId, $branchId, $departmentId, $positionId, $designationId, $serviceTypeId, $serviceEventTypeId, $employeeTypeId, $employeeId,null,null,$functionalTypeId);
    //         $statusCondition = '';
    //         $leaveCondition = '';
    //         $fromDateCondition = "";
    //         $toDateCondition = "";

    //         if($leaveYear!=null){
    //             $leaveYearStatusCondition="( ( L.STATUS ='E' OR L.OLD_LEAVE='Y' ) AND L.LEAVE_YEAR= {$leaveYear} )";
    //         }else{
    //             $leaveYearStatusCondition="L.STATUS ='E'";
    //         }

    //         if ($leaveRequestStatusId != -1) {
    //             $statusCondition = " AND LA.STATUS='{$leaveRequestStatusId}'";
    //         }

    //         if ($leaveId != null && $leaveId != -1) {
    //             $leaveCondition = " AND LA.LEAVE_ID = {$leaveId}";
    //         }

    //         if ($fromDate != null) {
    //             $fromDateCondition = " AND LA.START_DATE>=TO_DATE('{$fromDate}','DD-MM-YYYY')";
    //         }

    //         if ($toDate != null) {
    //             $toDateCondition = "AND LA.END_DATE<=TO_DATE('{$toDate}','DD-MM-YYYY')";
    //         }

    //         $sql = "SELECT 
    //              FUNT.FUNCTIONAL_TYPE_EDESC                                        AS FUNCTIONAL_TYPE_EDESC,
    //             --INITCAP(L.LEAVE_ENAME) AS LEAVE_ENAME,
    //             CASE WHEN SUB_REF_ID IS NULL THEN 
    // INITCAP(L.LEAVE_ENAME)
    // ELSE
    // INITCAP(L.LEAVE_ENAME)||'('||SLR.SUB_NAME||')'
    // END
    // AS LEAVE_ENAME,
    //                   L.LEAVE_CODE,
    //                   L.SHOW_LEAVE_FORM,
    //                   L.LEAVE_ID,
    //                   LA.NO_OF_DAYS,
    //                   case when L.ALLOW_HALFDAY = 'Y'
    //                   then LA.NO_OF_DAYS/2
    //                   else LA.NO_OF_DAYS
    //                   END as ACTUAL_DAYS,
    //                   (CASE WHEN (LA.HALF_DAY IS NULL OR LA.HALF_DAY = 'N') 
    //                   THEN 'Full Day' 
    //                   WHEN (LA.HALF_DAY = 'F') THEN 'First Half' 
    //                   ELSE 'Second Half' END) AS HALF_DAY_DETAIL,
    //                   INITCAP(TO_CHAR(LA.START_DATE, 'DD-MON-YYYY'))     AS START_DATE_AD,
    //                   BS_DATE(TO_CHAR(LA.START_DATE, 'DD-MON-YYYY'))     AS START_DATE_BS,
    //                   INITCAP(TO_CHAR(LA.END_DATE, 'DD-MON-YYYY'))       AS END_DATE_AD,
    //                   BS_DATE(TO_CHAR(LA.END_DATE, 'DD-MON-YYYY'))       AS END_DATE_BS,
    //                   INITCAP(TO_CHAR(LA.REQUESTED_DT, 'DD-MON-YYYY'))   AS APPLIED_DATE_AD,
    //                   BS_DATE(TO_CHAR(LA.REQUESTED_DT, 'DD-MON-YYYY'))   AS APPLIED_DATE_BS,
    //                   LEAVE_STATUS_DESC(LA.STATUS)                       AS STATUS,
    //                   LA.ID                                              AS ID,
    //                   LA.EMPLOYEE_ID                                     AS EMPLOYEE_ID,
    //                   INITCAP(TO_CHAR(LA.RECOMMENDED_DT, 'DD-MON-YYYY')) AS RECOMMENDED_DT,
    //                   INITCAP(TO_CHAR(LA.APPROVED_DT, 'DD-MON-YYYY'))    AS APPROVED_DT,
    //                   E.EMPLOYEE_CODE                                    AS EMPLOYEE_CODE,                  
    //                   INITCAP(E.FULL_NAME)                               AS FULL_NAME,
    //                   INITCAP(E1.FULL_NAME)                              AS RECOMMENDED_BY_NAME,
    //                   INITCAP(E2.FULL_NAME)                              AS APPROVED_BY_NAME,
    //                   RA.RECOMMEND_BY                                    AS RECOMMENDER_ID,
    //                   RA.APPROVED_BY                                     AS APPROVER_ID,
    //                   INITCAP(RECM.FULL_NAME)                            AS RECOMMENDER_NAME,
    //                   INITCAP(APRV.FULL_NAME)                            AS APPROVER_NAME,
    //                   LA.RECOMMENDED_BY                                  AS RECOMMENDED_BY,
    //                   LA.APPROVED_BY                                     AS APPROVED_BY,
    //                   LA.RECOMMENDED_REMARKS                             AS RECOMMENDED_REMARKS,
    //                   LA.APPROVED_REMARKS                                AS APPROVED_REMARKS,
    //                   LA.HARDCOPY_SIGNED_FLAG                            AS HARDCOPY_SIGNED_FLAG,
    //                   LS.APPROVED_FLAG                                   AS SUB_APPROVED_FLAG,
    //                   INITCAP(TO_CHAR(LS.APPROVED_DATE, 'DD-MON-YYYY'))  AS SUB_APPROVED_DATE,
    //                   LS.EMPLOYEE_ID                                     AS SUB_EMPLOYEE_ID
    //                 FROM HRIS_EMPLOYEE_LEAVE_REQUEST LA
    //                 LEFT OUTER JOIN HRIS_LEAVE_MASTER_SETUP L
    //                 ON L.LEAVE_ID=LA.LEAVE_ID
    //                 LEFT OUTER JOIN HRIS_EMPLOYEES E
    //                 ON E.EMPLOYEE_ID=LA.EMPLOYEE_ID
    //                 LEFT OUTER JOIN HRIS_EMPLOYEES E1
    //                 ON E1.EMPLOYEE_ID=LA.RECOMMENDED_BY
    //                 LEFT OUTER JOIN HRIS_EMPLOYEES E2
    //                 ON E2.EMPLOYEE_ID=LA.APPROVED_BY
    //                 LEFT OUTER JOIN HRIS_RECOMMENDER_APPROVER RA
    //                 ON LA.EMPLOYEE_ID = RA.EMPLOYEE_ID
    //                 LEFT OUTER JOIN HRIS_EMPLOYEES RECM
    //                 ON RECM.EMPLOYEE_ID = RA.RECOMMEND_BY
    //                 LEFT OUTER JOIN HRIS_EMPLOYEES APRV
    //                 ON APRV.EMPLOYEE_ID = RA.APPROVED_BY
    //                 LEFT OUTER JOIN HRIS_LEAVE_SUBSTITUTE LS
    //                 ON LA.ID       = LS.LEAVE_REQUEST_ID
    //                 LEFT JOIN 
    //                 (SELECT 
    // WOD_ID AS ID
    // ,LA.EMPLOYEE_ID
    // ,NO_OF_DAYS
    // ,WD.FROM_DATE||' - '||WD.TO_DATE AS SUB_NAME
    // from 
    // HRIS_EMPLOYEE_LEAVE_ADDITION LA
    // JOIN Hris_Employee_Work_Dayoff WD ON (LA.WOD_ID=WD.ID)
    // UNION
    // SELECT 
    // WOH_ID AS ID
    // ,LA.EMPLOYEE_ID
    // ,NO_OF_DAYS
    // ,H.Holiday_Ename||'-'||WH.FROM_DATE||' - '||WH.TO_DATE AS SUB_NAME
    // from 
    // HRIS_EMPLOYEE_LEAVE_ADDITION LA
    // JOIN Hris_Employee_Work_Holiday WH ON (LA.WOH_ID=WH.ID)
    // LEFT JOIN Hris_Holiday_Master_Setup H ON (WH.HOLIDAY_ID=H.HOLIDAY_ID)) SLR ON (SLR.ID=LA.SUB_REF_ID AND SLR.EMPLOYEE_ID=LA.EMPLOYEE_ID)
    // LEFT JOIN HRIS_FUNCTIONAL_TYPES FUNT
    //     ON E.FUNCTIONAL_TYPE_ID=FUNT.FUNCTIONAL_TYPE_ID                
    //                 WHERE 
    //                 {$leaveYearStatusCondition}
    //                 AND E.STATUS   ='E'
    //                 {$searchCondition} {$statusCondition} {$leaveCondition} {$fromDateCondition} {$toDateCondition}
    //                 ORDER BY LA.REQUESTED_DT DESC";
    //         $finalSql = $this->getPrefReportQuery($sql);
    //         return $this->rawQuery($finalSql);
    //     }

    public function getLeaveRequestList($data): array
    {
        $employeeId = $data['employeeId'];
        $companyId = $data['companyId'];
        $branchId = $data['branchId'];
        $departmentId = $data['departmentId'];
        $designationId = $data['designationId'];
        $positionId = $data['positionId'];
        $serviceTypeId = $data['serviceTypeId'];
        $serviceEventTypeId = $data['serviceEventTypeId'];
        $employeeTypeId = $data['employeeTypeId'];
        $functionalTypeId = $data['functionalTypeId'];

        $leaveRequestStatusId = $data['leaveRequestStatusId'];
        $leaveId = $data['leaveId'];
        $fromDate = $data['fromDate'];
        $toDate = $data['toDate'];


        $searchCondition = $this->getSearchConditon($companyId, $branchId, $departmentId, $positionId, $designationId, $serviceTypeId, $serviceEventTypeId, $employeeTypeId, $employeeId, null, null, $functionalTypeId);
        $statusCondition = '';
        $leaveCondition = '';
        $fromDateCondition = "";
        $toDateCondition = "";
        if ($leaveRequestStatusId != -1) {
            $statusCondition = " AND LA.STATUS='{$leaveRequestStatusId}'";
        }

        if ($leaveId != null && $leaveId != -1) {
            $leaveCondition = " AND LA.LEAVE_ID = {$leaveId}";
        }

        if ($fromDate != null) {
            $fromDateCondition = " AND LA.START_DATE>=TO_DATE('{$fromDate}','DD-MM-YYYY')";
        }

        if ($toDate != null) {
            $toDateCondition = "AND LA.END_DATE<=TO_DATE('{$toDate}','DD-MM-YYYY')";
        }

        $sql = "SELECT 
             FUNT.FUNCTIONAL_TYPE_EDESC                                        AS FUNCTIONAL_TYPE_EDESC,
            --INITCAP(L.LEAVE_ENAME) AS LEAVE_ENAME,
            CASE WHEN SUB_REF_ID IS NULL THEN 
INITCAP(L.LEAVE_ENAME)
ELSE
INITCAP(L.LEAVE_ENAME)||'('||SLR.SUB_NAME||')'
END
AS LEAVE_ENAME,
                  L.LEAVE_CODE,
                  (CASE WHEN (LA.HALF_DAY IS NULL OR  LA.HALF_DAY = 'N')  THEN LA.NO_OF_DAYS else la.NO_OF_DAYS/2 end) as no_of_days,
                  case when L.ALLOW_HALFDAY = 'Y'
                  then LA.NO_OF_DAYS/2
                  else LA.NO_OF_DAYS
                  END as ACTUAL_DAYS,
                  (CASE WHEN (LA.HALF_DAY IS NULL OR LA.HALF_DAY = 'N') 
                  THEN 'Full Day' 
                  WHEN (LA.HALF_DAY = 'F') THEN 'First Half' 
                  ELSE 'Second Half' END) AS HALF_DAY_DETAIL,
                  INITCAP(TO_CHAR(LA.START_DATE, 'DD-MON-YYYY'))     AS START_DATE_AD,
                  BS_DATE(TO_CHAR(LA.START_DATE, 'DD-MON-YYYY'))     AS START_DATE_BS,
                  INITCAP(TO_CHAR(LA.END_DATE, 'DD-MON-YYYY'))       AS END_DATE_AD,
                  BS_DATE(TO_CHAR(LA.END_DATE, 'DD-MON-YYYY'))       AS END_DATE_BS,
                  INITCAP(TO_CHAR(LA.REQUESTED_DT, 'DD-MON-YYYY'))   AS APPLIED_DATE_AD,
                  BS_DATE(TO_CHAR(LA.REQUESTED_DT, 'DD-MON-YYYY'))   AS APPLIED_DATE_BS,
                  LEAVE_STATUS_DESC(LA.STATUS)                       AS STATUS,
                  LA.ID                                              AS ID,
                  LA.EMPLOYEE_ID                                     AS EMPLOYEE_ID,
                  INITCAP(TO_CHAR(LA.RECOMMENDED_DT, 'DD-MON-YYYY')) AS RECOMMENDED_DT,
                  INITCAP(TO_CHAR(LA.APPROVED_DT, 'DD-MON-YYYY'))    AS APPROVED_DT,
                  E.EMPLOYEE_CODE                                    AS EMPLOYEE_CODE,                  
                  INITCAP(E.FULL_NAME)                               AS FULL_NAME,
                  INITCAP(E1.FULL_NAME)                              AS RECOMMENDED_BY_NAME,
                  INITCAP(E2.FULL_NAME)                              AS APPROVED_BY_NAME,
                  RA.RECOMMEND_BY                                    AS RECOMMENDER_ID,
                  RA.APPROVED_BY                                     AS APPROVER_ID,
                  INITCAP(RECM.FULL_NAME)                            AS RECOMMENDER_NAME,
                  INITCAP(APRV.FULL_NAME)                            AS APPROVER_NAME,
                  LA.RECOMMENDED_BY                                  AS RECOMMENDED_BY,
                  LA.APPROVED_BY                                     AS APPROVED_BY,
                  LA.RECOMMENDED_REMARKS                             AS RECOMMENDED_REMARKS,
                  LA.APPROVED_REMARKS                                AS APPROVED_REMARKS,
                  LA.HARDCOPY_SIGNED_FLAG                            AS HARDCOPY_SIGNED_FLAG,
                  LS.APPROVED_FLAG                                   AS SUB_APPROVED_FLAG,
                  INITCAP(TO_CHAR(LS.APPROVED_DATE, 'DD-MON-YYYY'))  AS SUB_APPROVED_DATE,
                  LS.EMPLOYEE_ID                                     AS SUB_EMPLOYEE_ID,
                  HLS.FULL_NAME                                      AS SUB_EMPLOYEE_NAME,
                  LS.REMARKS                                         AS SUB_REMARKS
                FROM HRIS_EMPLOYEE_LEAVE_REQUEST LA
                LEFT OUTER JOIN HRIS_LEAVE_MASTER_SETUP L
                ON L.LEAVE_ID=LA.LEAVE_ID
                LEFT OUTER JOIN HRIS_EMPLOYEES E
                ON E.EMPLOYEE_ID=LA.EMPLOYEE_ID
                LEFT OUTER JOIN HRIS_EMPLOYEES E1
                ON E1.EMPLOYEE_ID=LA.RECOMMENDED_BY
                LEFT OUTER JOIN HRIS_EMPLOYEES E2
                ON E2.EMPLOYEE_ID=LA.APPROVED_BY
                LEFT OUTER JOIN HRIS_RECOMMENDER_APPROVER RA
                ON LA.EMPLOYEE_ID = RA.EMPLOYEE_ID
                LEFT OUTER JOIN HRIS_EMPLOYEES RECM
                ON RECM.EMPLOYEE_ID = RA.RECOMMEND_BY
                LEFT OUTER JOIN HRIS_EMPLOYEES APRV
                ON APRV.EMPLOYEE_ID = RA.APPROVED_BY
                LEFT OUTER JOIN HRIS_LEAVE_SUBSTITUTE LS
                ON LA.ID       = LS.LEAVE_REQUEST_ID and ls.status='E'
                LEFT OUTER JOIN HRIS_EMPLOYEES HLS
                ON LS.EMPLOYEE_ID=HLS.EMPLOYEE_ID
                LEFT JOIN 
                (SELECT 
WOD_ID AS ID
,LA.EMPLOYEE_ID
,NO_OF_DAYS
,WD.FROM_DATE||' - '||WD.TO_DATE AS SUB_NAME
from 
HRIS_EMPLOYEE_LEAVE_ADDITION LA
JOIN Hris_Employee_Work_Dayoff WD ON (LA.WOD_ID=WD.ID)
UNION
SELECT 
WOH_ID AS ID
,LA.EMPLOYEE_ID
,NO_OF_DAYS
,H.Holiday_Ename||'-'||WH.FROM_DATE||' - '||WH.TO_DATE AS SUB_NAME
from 
HRIS_EMPLOYEE_LEAVE_ADDITION LA
JOIN Hris_Employee_Work_Holiday WH ON (LA.WOH_ID=WH.ID)
LEFT JOIN Hris_Holiday_Master_Setup H ON (WH.HOLIDAY_ID=H.HOLIDAY_ID)) SLR ON (SLR.ID=LA.SUB_REF_ID AND SLR.EMPLOYEE_ID=LA.EMPLOYEE_ID)
LEFT JOIN HRIS_FUNCTIONAL_TYPES FUNT
    ON E.FUNCTIONAL_TYPE_ID=FUNT.FUNCTIONAL_TYPE_ID                
                WHERE L.STATUS ='E' 
                AND E.STATUS   ='E'
                {$searchCondition} {$statusCondition} {$leaveCondition} {$fromDateCondition} {$toDateCondition}
                ORDER BY LA.START_DATE DESC";
        // echo '<pre>';
        // print_r($sql);
        // die;
        $finalSql = $this->getPrefReportQuery($sql);
        return $this->rawQuery($finalSql);
    }

    public function getSameDateApprovedStatus($employeeId, $startDate, $endDate)
    {
        $sql = "SELECT COUNT(*) as LEAVE_COUNT
  FROM HRIS_EMPLOYEE_LEAVE_REQUEST
  WHERE (('{$startDate}' BETWEEN START_DATE AND END_DATE)
  OR ('{$endDate}' BETWEEN START_DATE AND END_DATE))
  AND STATUS  IN ('AP','CP','CR')
  AND EMPLOYEE_ID = $employeeId
                ";
        $statement = $this->adapter->query($sql);
        $result = $statement->execute();
        return $result->current();
    }

    public function getLfcData($id)
    {
        $leaveId = "SELECT LEAVE_ID FROM  HRIS_EMPLOYEE_LEAVE_REQUEST where id = {$id}";
        $employeeId = "SELECT EMPLOYEE_ID FROM  HRIS_EMPLOYEE_LEAVE_REQUEST where id = {$id}";

        $sql = "SELECT L.*, 
                BS_DATE(TO_CHAR(L.START_DATE, 'DD-MON-YYYY')) as START_DATE_BS,
                BS_DATE(TO_CHAR(L.END_DATE, 'DD-MON-YYYY')) as END_DATE_BS,
                BS_DATE(TO_CHAR(TRUNC(SYSDATE), 'DD-MON-YYYY')) as CURRENT_DATE 
                from (SELECT LR.*,
                E.FULL_NAME as EMPLOYEE,
                B.BRANCH_NAME as BRANCH,
                D.DEPARTMENT_NAME as DEPARTMENT,
                DE.DESIGNATION_TITLE as DESIGNATION,
                E1.FULL_NAME as RECOMMENDER,
                E2.FULL_NAME as APPROVER
                from HRIS_EMPLOYEE_LEAVE_REQUEST LR 
                left join HRIS_EMPLOYEES E on (LR.EMPLOYEE_ID = E.EMPLOYEE_ID)
                left join HRIS_EMPLOYEES E1 on (LR.RECOMMENDED_BY = E1.EMPLOYEE_ID)
                left join HRIS_EMPLOYEES E2 on (LR.APPROVED_BY = E2.EMPLOYEE_ID)
                left join HRIS_DEPARTMENTS D on (E.DEPARTMENT_ID = D.DEPARTMENT_ID)
                left join HRIS_DESIGNATIONS DE on (E.DESIGNATION_ID = DE.DESIGNATION_ID)
                left join HRIS_BRANCHES B on (E.BRANCH_ID = B.BRANCH_ID)
                where LR.EMPLOYEE_ID = ({$employeeId})
                and LR.LEAVE_ID = ({$leaveId}) 
                and LR.STATUS = 'AP') L";

        $statement = $this->adapter->query($sql);
        $result = $statement->execute();
        return $result;
    }


    public function getAllLeaveforReport()
    {
        $sql = "select 
                lms.leave_id,
                lms.LEAVE_CODE,
                lms.LEAVE_ENAME,
                lms.LEAVE_YEAR 
                from hris_leave_master_setup lms
                where ( status='E' or OLD_LEAVE='Y' ) order by VIEW_ORDER asc  ";
        $statement = $this->adapter->query($sql);
        $result = $statement->execute();

        $allLeaveForReport = [];
        foreach ($result as $allLeave) {
            $tempId = $allLeave['LEAVE_YEAR'];
            (!array_key_exists($tempId, $allLeaveForReport)) ?
                $allLeaveForReport[$tempId][0] = $allLeave :
                array_push($allLeaveForReport[$tempId], $allLeave);
        }

        return $allLeaveForReport;
    }


    public function getMonthlyLeaveforReport($monthly = false)
    {
        $monthlyCondition = ($monthly) ? "AND  IS_MONTHLY='Y' " : "AND  IS_MONTHLY='N'";
        $sql = "select 
                lms.leave_id,
                lms.LEAVE_CODE,
                lms.LEAVE_ENAME,
                lms.LEAVE_YEAR 
                from hris_leave_master_setup lms
                where ( status='E' or OLD_LEAVE='Y' ) {$monthlyCondition} order by VIEW_ORDER asc  ";
        $statement = $this->adapter->query($sql);
        $result = $statement->execute();

        $allLeaveForReport = [];
        foreach ($result as $allLeave) {
            $tempId = $allLeave['LEAVE_YEAR'];
            (!array_key_exists($tempId, $allLeaveForReport)) ?
                $allLeaveForReport[$tempId][0] = $allLeave :
                array_push($allLeaveForReport[$tempId], $allLeave);
        }

        return $allLeaveForReport;
    }
}
