<?php

namespace WorkOnDayoff\Repository;

use Application\Helper\EntityHelper;
use Application\Model\Model;
use Exception;
use Application\Repository\HrisRepository;
use Setup\Model\HrEmployees;
use Zend\Authentication\AuthenticationService;
use Zend\Db\Adapter\AdapterInterface;
use Zend\Db\Sql\Expression;
use Zend\Db\Sql\Sql;
use Zend\Db\TableGateway\TableGateway;

class WorkOnDayoffStatusRepository extends HrisRepository
{

  public function getFilteredRecord($data, $recomApproveId)
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
    $requestStatusId = $data['requestStatusId'];
    $fromDate = $data['fromDate'];
    $toDate = $data['toDate'];

    $searchCondition = EntityHelper::getSearchConditonBounded($companyId, $branchId, $departmentId, $positionId, $designationId, $serviceTypeId, $serviceEventTypeId, $employeeTypeId, $employeeId);
    $boundedParameter = [];
    $boundedParameter = array_merge($boundedParameter, $searchCondition['parameter']);
    $statusCondition = "";
    $fromDateCondition = "";
    $toDateCondition = "";

    if ($requestStatusId != -1) {
      $statusCondition = " AND WD.STATUS =:requestStatusId";
      $boundedParameter['requestStatusId'] = $requestStatusId;
    }

    if ($fromDate != null) {
      $fromDateCondition = " AND WD.FROM_DATE>=TO_DATE(:fromDate,'DD-MM-YYYY')";
      $boundedParameter['fromDate'] = $fromDate;
    }

    if ($toDate != null) {
      $toDateCondition = "AND WD.TO_DATE<=TO_DATE(:toDate,'DD-MM-YYYY')";
      $boundedParameter['toDate'] = $toDate;
    }

    $sql = "SELECT INITCAP(TO_CHAR(WD.FROM_DATE, 'DD-MON-YYYY'))              AS FROM_DATE_AD,
                  BS_DATE(TO_CHAR(WD.FROM_DATE, 'DD-MON-YYYY'))                   AS FROM_DATE_BS,
                  INITCAP(TO_CHAR(WD.TO_DATE, 'DD-MON-YYYY'))                     AS TO_DATE_AD,
                  BS_DATE(TO_CHAR(WD.TO_DATE, 'DD-MON-YYYY'))                     AS TO_DATE_BS,
                  INITCAP(TO_CHAR(WD.REQUESTED_DATE, 'DD-MON-YYYY'))              AS REQUESTED_DATE_AD,
                  BS_DATE(TO_CHAR(WD.REQUESTED_DATE, 'DD-MON-YYYY'))              AS REQUESTED_DATE_BS,
                  LEAVE_STATUS_DESC(WD.STATUS)                                    AS STATUS,
                   REC_APP_ROLE(U.EMPLOYEE_ID,
                  CASE WHEN ALR.R_A_ID IS NOT NULL THEN ALR.R_A_ID ELSE RA.RECOMMEND_BY END,
                  CASE WHEN ALA.R_A_ID IS NOT NULL THEN ALA.R_A_ID ELSE RA.APPROVED_BY END
                  )      AS ROLE,
                  REC_APP_ROLE_NAME(U.EMPLOYEE_ID,
                  CASE WHEN ALR.R_A_ID IS NOT NULL THEN ALR.R_A_ID ELSE RA.RECOMMEND_BY END,
                  CASE WHEN ALA.R_A_ID IS NOT NULL THEN ALA.R_A_ID ELSE RA.APPROVED_BY END
                  ) AS YOUR_ROLE,
                  WD.REMARKS                                                      AS REMARKS,
                  WD.DURATION                                                     AS DURATION,
                  WD.EMPLOYEE_ID                                                  AS EMPLOYEE_ID,
                  E.EMPLOYEE_CODE                                                  AS EMPLOYEE_CODE,
                  WD.ID                                                           AS ID,
                  WD.MODIFIED_DATE                                                AS MODIFIED_DATE,
                  INITCAP(TO_CHAR(WD.RECOMMENDED_DATE, 'DD-MON-YYYY'))            AS RECOMMENDED_DATE,
                  INITCAP(TO_CHAR(WD.APPROVED_DATE, 'DD-MON-YYYY'))               AS APPROVED_DATE,
                  INITCAP(E.FULL_NAME)                                            AS FULL_NAME,
                  INITCAP(E1.FULL_NAME)                                           AS RECOMMENDED_BY_NAME,
                  INITCAP(E2.FULL_NAME)                                           AS APPROVED_BY_NAME,
                  RA.RECOMMEND_BY                                                 AS RECOMMENDER_ID,
                  RA.APPROVED_BY                                                  AS APPROVER_ID,
                  INITCAP(RECM.FULL_NAME)                                         AS RECOMMENDER_NAME,
                  INITCAP(APRV.FULL_NAME)                                         AS APPROVER_NAME,
                  WD.RECOMMENDED_BY                                               AS RECOMMENDED_BY,
                  WD.APPROVED_BY                                                  AS APPROVED_BY,
                  WD.RECOMMENDED_REMARKS                                          AS RECOMMENDED_REMARKS,
                  WD.APPROVED_REMARKS                                             AS APPROVED_REMARKS
                FROM HRIS_EMPLOYEE_WORK_DAYOFF WD
                LEFT OUTER JOIN HRIS_EMPLOYEES E
                ON E.EMPLOYEE_ID=WD.EMPLOYEE_ID
                LEFT OUTER JOIN HRIS_EMPLOYEES E1
                ON E1.EMPLOYEE_ID=WD.RECOMMENDED_BY
                LEFT OUTER JOIN HRIS_EMPLOYEES E2
                ON E2.EMPLOYEE_ID=WD.APPROVED_BY
                LEFT OUTER JOIN HRIS_RECOMMENDER_APPROVER RA
                ON WD.EMPLOYEE_ID = RA.EMPLOYEE_ID
                LEFT OUTER JOIN HRIS_EMPLOYEES RECM
                ON RECM.EMPLOYEE_ID = RA.RECOMMEND_BY
                LEFT OUTER JOIN HRIS_EMPLOYEES APRV
                ON APRV.EMPLOYEE_ID = RA.APPROVED_BY
                LEFT JOIN HRIS_ALTERNATE_R_A ALR
                    ON (ALR.R_A_FLAG='R' AND ALR.EMPLOYEE_ID=WD.EMPLOYEE_ID AND ALR.R_A_ID={$recomApproveId})
                    LEFT JOIN HRIS_ALTERNATE_R_A ALA
                    ON (ALA.R_A_FLAG='A' AND ALA.EMPLOYEE_ID=WD.EMPLOYEE_ID AND ALA.R_A_ID={$recomApproveId})
                LEFT OUTER JOIN HRIS_EMPLOYEES U
                ON (U.EMPLOYEE_ID= RA.RECOMMEND_BY
                OR U.EMPLOYEE_ID = RA.APPROVED_BY
                OR U.EMPLOYEE_ID   =ALR.R_A_ID
                OR U.EMPLOYEE_ID   =ALA.R_A_ID)
                WHERE E.STATUS   ='E'
                AND U.EMPLOYEE_ID= {$recomApproveId}
                {$searchCondition['sql']}
                {$statusCondition}
                {$fromDateCondition}
                {$toDateCondition}
                ORDER BY WD.FROM_DATE DESC";
    return $this->rawQuery($sql, $boundedParameter);
    // $statement = $this->adapter->query($sql, $boundedParameter);
    // $result = $statement->execute();
    // return $result;
  }

  public function getWODReqList($data)
  {
    $boundedParameter = [];
    $employeeId = $data['employeeId'];
    $companyId = $data['companyId'];
    $branchId = $data['branchId'];
    $departmentId = $data['departmentId'];
    $designationId = $data['designationId'];
    $positionId = $data['positionId'];
    $serviceTypeId = $data['serviceTypeId'];
    $serviceEventTypeId = $data['serviceEventTypeId'];
    $functionalTypeId = $data['functionalTypeId'];
    $employeeTypeId = $data['employeeTypeId'];
    $requestStatusId = $data['requestStatusId'];
    $fromDate = $data['fromDate'];
    $toDate = $data['toDate'];

    $searchCondition = EntityHelper::getSearchConditonBounded($companyId, $branchId, $departmentId, $positionId, $designationId, $serviceTypeId, $serviceEventTypeId, $employeeTypeId, $employeeId, null, null, $functionalTypeId);
    $boundedParameter = [];
    $boundedParameter = array_merge($boundedParameter, $searchCondition['parameter']);
    $statusCondition = "";
    $fromDateCondition = "";
    $toDateCondition = "";

    //        if ($requestStatusId != -1) {
    //            $statusCondition = " AND WD.STATUS ='{$requestStatusId}'";
    //        }
    //
    //        if ($fromDate != null) {
    //            $fromDateCondition = " AND WD.FROM_DATE>=TO_DATE('{$fromDate}','DD-MM-YYYY')";
    //        }
    //
    //        if ($toDate != null) {
    //            $toDateCondition = "AND WD.TO_DATE<=TO_DATE('{$toDate}','DD-MM-YYYY')";
    //        }
    $sql = "SELECT INITCAP(TO_CHAR(WD.FROM_DATE, 'DD-MON-YYYY'))              AS FROM_DATE_AD,
                   BS_DATE(TO_CHAR(WD.FROM_DATE, 'DD-MON-YYYY'))                   AS FROM_DATE_BS,
                   INITCAP(TO_CHAR(WD.TO_DATE, 'DD-MON-YYYY'))                     AS TO_DATE_AD,
                   BS_DATE(TO_CHAR(WD.TO_DATE, 'DD-MON-YYYY'))                     AS TO_DATE_BS,
                   INITCAP(TO_CHAR(WD.REQUESTED_DATE, 'DD-MON-YYYY'))              AS REQUESTED_DATE_AD,
                   BS_DATE(TO_CHAR(WD.REQUESTED_DATE, 'DD-MON-YYYY'))              AS REQUESTED_DATE_BS,
                   LEAVE_STATUS_DESC(WD.STATUS)                                    AS STATUS,
                   WD.REMARKS                                                      AS REMARKS,
                   WD.DURATION                                                     AS DURATION,
                   WD.EMPLOYEE_ID                                                  AS EMPLOYEE_ID,
                   WD.ID                                                           AS ID,
                   WD.MODIFIED_DATE                                                AS MODIFIED_DATE,
                   INITCAP(TO_CHAR(WD.RECOMMENDED_DATE, 'DD-MON-YYYY'))            AS RECOMMENDED_DATE,
                   INITCAP(TO_CHAR(WD.APPROVED_DATE, 'DD-MON-YYYY'))               AS APPROVED_DATE,
                   E.EMPLOYEE_CODE                                                 AS EMPLOYEE_CODE,
                   INITCAP(E.FULL_NAME)                                            AS FULL_NAME,
                   INITCAP(E1.FULL_NAME)                                           AS RECOMMENDED_BY_NAME,
                   INITCAP(E2.FULL_NAME)                                           AS APPROVED_BY_NAME,
                   RA.RECOMMEND_BY                                                 AS RECOMMENDER_ID,
                   RA.APPROVED_BY                                                  AS APPROVER_ID,
                   INITCAP(RECM.FULL_NAME)                                         AS RECOMMENDER_NAME,
                   INITCAP(APRV.FULL_NAME)                                         AS APPROVER_NAME,
                   WD.RECOMMENDED_BY                                               AS RECOMMENDED_BY,
                   WD.APPROVED_BY                                                  AS APPROVED_BY,
                   WD.RECOMMENDED_REMARKS                                          AS RECOMMENDED_REMARKS,
                   WD.APPROVED_REMARKS                                             AS APPROVED_REMARKS
                 FROM HRIS_EMPLOYEE_WORK_DAYOFF WD
                 LEFT OUTER JOIN HRIS_EMPLOYEES E
                 ON E.EMPLOYEE_ID=WD.EMPLOYEE_ID
                 LEFT OUTER JOIN HRIS_EMPLOYEES E1
                 ON E1.EMPLOYEE_ID=WD.RECOMMENDED_BY
                 LEFT OUTER JOIN HRIS_EMPLOYEES E2
                 ON E2.EMPLOYEE_ID=WD.APPROVED_BY
                 LEFT OUTER JOIN HRIS_RECOMMENDER_APPROVER RA
                 ON WD.EMPLOYEE_ID = RA.EMPLOYEE_ID
                 LEFT OUTER JOIN HRIS_EMPLOYEES RECM
                 ON RECM.EMPLOYEE_ID = RA.RECOMMEND_BY
                 LEFT OUTER JOIN HRIS_EMPLOYEES APRV
                 ON APRV.EMPLOYEE_ID = RA.APPROVED_BY
                 WHERE E.STATUS   ='E'
                 AND (E1.STATUS   =
                   CASE
                     WHEN E1.STATUS IS NOT NULL
                     THEN ('E')
                   END
                 OR E1.STATUS  IS NULL)
                 AND (E2.STATUS =
                   CASE
                     WHEN E2.STATUS IS NOT NULL
                     THEN ('E')
                   END
                 OR E2.STATUS    IS NULL)
                 AND (RECM.STATUS =
                   CASE
                     WHEN RECM.STATUS IS NOT NULL
                     THEN ('E')
                   END
                 OR RECM.STATUS  IS NULL)
                 AND (APRV.STATUS =
                   CASE
                     WHEN APRV.STATUS IS NOT NULL
                     THEN ('E')
                   END
                 OR APRV.STATUS IS NULL)
                 {$searchCondition['sql']}
                 ";

    if ($requestStatusId != -1) {
      $sql .= " AND WD.STATUS =:requestStatusId";
      $boundedParameter['requestStatusId'] = $requestStatusId;
    }

    if ($fromDate != null) {
      $sql .= " AND WD.FROM_DATE>=TO_DATE(:fromDate,'DD-MM-YYYY')";
      $boundedParameter['fromDate'] = $fromDate;
    }

    if ($toDate != null) {
      $sql .= "AND WD.TO_DATE<=TO_DATE(:toDate,'DD-MM-YYYY')";
      $boundedParameter['toDate'] = $toDate;
    }

    $sql .= " ORDER BY WD.FROM_DATE DESC";
    // FOR SHIVAM
    //        $sql = "SELECT INITCAP(TO_CHAR(WD.FROM_DATE, 'DD-MON-YYYY'))              AS FROM_DATE_AD,
    //                  BS_DATE(TO_CHAR(WD.FROM_DATE, 'DD-MON-YYYY'))                   AS FROM_DATE_BS,
    //                  INITCAP(TO_CHAR(WD.TO_DATE, 'DD-MON-YYYY'))                     AS TO_DATE_AD,
    //                  BS_DATE(TO_CHAR(WD.TO_DATE, 'DD-MON-YYYY'))                     AS TO_DATE_BS,
    //                  INITCAP(TO_CHAR(WD.REQUESTED_DATE, 'DD-MON-YYYY'))              AS REQUESTED_DATE_AD,
    //                  BS_DATE(TO_CHAR(WD.REQUESTED_DATE, 'DD-MON-YYYY'))              AS REQUESTED_DATE_BS,
    //                  LEAVE_STATUS_DESC(WD.STATUS)                                    AS STATUS,
    //                  WD.REMARKS                                                      AS REMARKS,
    //                  WD.DURATION                                                     AS DURATION,
    //                  WD.EMPLOYEE_ID                                                  AS EMPLOYEE_ID,
    //                  WD.ID                                                           AS ID,
    //                  WD.MODIFIED_DATE                                                AS MODIFIED_DATE,
    //                  INITCAP(TO_CHAR(WD.RECOMMENDED_DATE, 'DD-MON-YYYY'))            AS RECOMMENDED_DATE,
    //                  INITCAP(TO_CHAR(WD.APPROVED_DATE, 'DD-MON-YYYY'))               AS APPROVED_DATE,
    //                  E.EMPLOYEE_CODE                                                 AS EMPLOYEE_CODE,
    //                  INITCAP(E.FULL_NAME)                                            AS FULL_NAME,
    //                  WD.RECOMMENDED_BY                                               AS RECOMMENDED_BY,
    //                  WD.APPROVED_BY                                                  AS APPROVED_BY,
    //                  WD.RECOMMENDED_REMARKS                                          AS RECOMMENDED_REMARKS,
    //                  WD.APPROVED_REMARKS                                             AS APPROVED_REMARKS
    //                FROM HRIS_EMPLOYEE_WORK_DAYOFF WD
    //                LEFT OUTER JOIN HRIS_EMPLOYEES E
    //                ON E.EMPLOYEE_ID=WD.EMPLOYEE_ID
    //                WHERE E.STATUS   ='E'
    //                {$searchCondition}
    //                {$statusCondition}
    //                {$fromDateCondition}
    //                {$toDateCondition}
    //                ORDER BY WD.REQUESTED_DATE DESC";
    $finalSql = $this->getPrefReportQuery($sql);
    return $this->rawQuery($finalSql, $boundedParameter);

    // $statement = $this->adapter->query($finalSql);
    // $result = $statement->execute($boundedParameter);
    // return $result;


  }
}
