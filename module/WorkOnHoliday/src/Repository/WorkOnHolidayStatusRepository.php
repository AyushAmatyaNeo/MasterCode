<?php

namespace WorkOnHoliday\Repository;

use Application\Helper\EntityHelper;
use Application\Repository\HrisRepository;
use Setup\Model\HrEmployees;
use Zend\Db\Adapter\AdapterInterface;

class WorkOnHolidayStatusRepository extends HrisRepository
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
    $holidayId = $data['holidayId'];
    $requestStatusId = $data['requestStatusId'];
    $fromDate = $data['fromDate'];
    $toDate = $data['toDate'];

    $searchCondition = EntityHelper::getSearchConditonBounded($companyId, $branchId, $departmentId, $positionId, $designationId, $serviceTypeId, $serviceEventTypeId, $employeeTypeId, $employeeId);
    $boundedParameter = [];
    $boundedParameter = array_merge($boundedParameter, $searchCondition['parameter']);
    $statusCondition = '';
    $holidayCondition = '';
    $fromDateCondition = '';
    $toDateCondition = '';
    if ($requestStatusId != -1) {
      $statusCondition = " AND  WH.STATUS=:requestStatusId ";
      $boundedParameter['requestStatusId'] = $requestStatusId;
    }

    if ($holidayId != -1) {
      $holidayCondition = " AND WH.HOLIDAY_ID =:holidayId";
      $boundedParameter['holidayId'] = $holidayId;
    }

    if ($fromDate != null) {
      $fromDateCondition = " AND WH.FROM_DATE>=TO_DATE(:fromDate,'DD-MM-YYYY')";
      $boundedParameter['fromDate'] = $fromDate;
    }

    if ($toDate != null) {
      $toDateCondition = " AND WH.TO_DATE<=TO_DATE(:toDate,'DD-MM-YYYY')";
      $boundedParameter['toDate'] = $toDate;
    }

    $sql = "SELECT INITCAP(H.HOLIDAY_ENAME) AS HOLIDAY_ENAME,
                  WH.DURATION,
                  INITCAP(TO_CHAR(WH.FROM_DATE, 'DD-MON-YYYY'))                   AS FROM_DATE_AD,
                  BS_DATE(TO_CHAR(WH.FROM_DATE, 'DD-MON-YYYY'))                   AS FROM_DATE_BS,
                  INITCAP(TO_CHAR(WH.TO_DATE, 'DD-MON-YYYY'))                     AS TO_DATE_AD,
                  BS_DATE(TO_CHAR(WH.TO_DATE, 'DD-MON-YYYY'))                     AS TO_DATE_BS,
                  INITCAP(TO_CHAR(WH.REQUESTED_DATE, 'DD-MON-YYYY'))              AS REQUESTED_DATE_AD,
                  BS_DATE(TO_CHAR(WH.REQUESTED_DATE, 'DD-MON-YYYY'))              AS REQUESTED_DATE_BS,
                  LEAVE_STATUS_DESC(WH.STATUS)                                    AS STATUS,
                  REC_APP_ROLE(U.EMPLOYEE_ID,
                  CASE WHEN ALR.R_A_ID IS NOT NULL THEN ALR.R_A_ID ELSE RA.RECOMMEND_BY END,
                  CASE WHEN ALA.R_A_ID IS NOT NULL THEN ALA.R_A_ID ELSE RA.APPROVED_BY END
                  )      AS ROLE,
                    REC_APP_ROLE_NAME(U.EMPLOYEE_ID,
                  CASE WHEN ALR.R_A_ID IS NOT NULL THEN ALR.R_A_ID ELSE RA.RECOMMEND_BY END,
                  CASE WHEN ALA.R_A_ID IS NOT NULL THEN ALA.R_A_ID ELSE RA.APPROVED_BY END
                  ) AS YOUR_ROLE,
                  WH.EMPLOYEE_ID                                                  AS EMPLOYEE_ID,
                  E.EMPLOYEE_CODE                                                 AS EMPLOYEE_CODE,
                  WH.ID                                                           AS ID,
                  WH.REMARKS                                                      AS REMARKS,
                  INITCAP(TO_CHAR(WH.RECOMMENDED_DATE, 'DD-MON-YYYY'))            AS RECOMMENDED_DATE,
                  INITCAP(TO_CHAR(WH.APPROVED_DATE, 'DD-MON-YYYY'))               AS APPROVED_DATE,
                  INITCAP(E.FULL_NAME)                                            AS FULL_NAME,
                  INITCAP(E1.FULL_NAME)                                           AS RECOMMENDED_BY_NAME,
                  INITCAP(E2.FULL_NAME)                                           AS APPROVED_BY_NAME,
                  RA.RECOMMEND_BY                                                 AS RECOMMENDER_ID,
                  RA.APPROVED_BY                                                  AS APPROVER_ID,
                  INITCAP(RECM.FULL_NAME)                                         AS RECOMMENDER_NAME,
                  INITCAP(APRV.FULL_NAME)                                         AS APPROVER_NAME,
                  WH.RECOMMENDED_BY                                               AS RECOMMENDED_BY,
                  WH.APPROVED_BY                                                  AS APPROVED_BY,
                  WH.RECOMMENDED_REMARKS                                          AS RECOMMENDED_REMARKS,
                  WH.APPROVED_REMARKS                                             AS APPROVED_REMARKS
                FROM HRIS_EMPLOYEE_WORK_HOLIDAY WH
                LEFT OUTER JOIN HRIS_HOLIDAY_MASTER_SETUP H
                ON H.HOLIDAY_ID=WH.HOLIDAY_ID
                LEFT OUTER JOIN HRIS_EMPLOYEES E
                ON E.EMPLOYEE_ID=WH.EMPLOYEE_ID
                LEFT OUTER JOIN HRIS_EMPLOYEES E1
                ON E1.EMPLOYEE_ID=WH.RECOMMENDED_BY
                LEFT OUTER JOIN HRIS_EMPLOYEES E2
                ON E2.EMPLOYEE_ID=WH.APPROVED_BY
                LEFT OUTER JOIN HRIS_RECOMMENDER_APPROVER RA
                ON WH.EMPLOYEE_ID = RA.EMPLOYEE_ID
                LEFT OUTER JOIN HRIS_EMPLOYEES RECM
                ON RECM.EMPLOYEE_ID = RA.RECOMMEND_BY
                LEFT OUTER JOIN HRIS_EMPLOYEES APRV
                ON APRV.EMPLOYEE_ID = RA.APPROVED_BY
                LEFT JOIN HRIS_ALTERNATE_R_A ALR
                    ON (ALR.R_A_FLAG='R' AND ALR.EMPLOYEE_ID=WH.EMPLOYEE_ID AND ALR.R_A_ID={$recomApproveId})
                    LEFT JOIN HRIS_ALTERNATE_R_A ALA
                    ON (ALA.R_A_FLAG='A' AND ALA.EMPLOYEE_ID=WH.EMPLOYEE_ID AND ALA.R_A_ID={$recomApproveId})
                LEFT OUTER JOIN HRIS_EMPLOYEES U
                ON (U.EMPLOYEE_ID = RA.RECOMMEND_BY
                OR U.EMPLOYEE_ID  =RA.APPROVED_BY
                OR U.EMPLOYEE_ID   =ALR.R_A_ID
                OR U.EMPLOYEE_ID   =ALA.R_A_ID)
                WHERE H.STATUS    ='E'
                AND E.STATUS      ='E'
                AND U.EMPLOYEE_ID= {$recomApproveId}
                {$searchCondition['sql']}
                {$statusCondition}
                {$holidayCondition}
                {$fromDateCondition}
                {$toDateCondition}
                ORDER BY WH.FROM_DATE DESC";
    return $this->rawQuery($sql, $boundedParameter);
  }

  public function getWOHRequestList($data)
  {
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
    $holidayId = $data['holidayId'];
    $requestStatusId = $data['requestStatusId'];
    $fromDate = $data['fromDate'];
    $toDate = $data['toDate'];

    $searchCondition = EntityHelper::getSearchConditonBounded($companyId, $branchId, $departmentId, $positionId, $designationId, $serviceTypeId, $serviceEventTypeId, $employeeTypeId, $employeeId, null, null, $functionalTypeId);
    $boundedParameter = [];
    $boundedParameter = array_merge($boundedParameter, $searchCondition['parameter']);
    $statusCondition = '';
    $holidayCondition = '';
    $fromDateCondition = '';
    $toDateCondition = '';
    if ($requestStatusId != -1) {
      $statusCondition = " AND  WH.STATUS=:requestStatusId ";
      $boundedParameter['requestStatusId'] = $requestStatusId;
    }

    if ($holidayId != -1) {
      $holidayCondition = " AND WH.HOLIDAY_ID =:holidayId";
      $boundedParameter['holidayId'] = $holidayId;
    }

    if ($fromDate != null) {
      $fromDateCondition = " AND WH.FROM_DATE>=TO_DATE(:fromDate,'DD-MM-YYYY')";
      $boundedParameter['fromDate'] = $fromDate;
    }

    if ($toDate != null) {
      $toDateCondition = " AND WH.TO_DATE<=TO_DATE(:toDate,'DD-MM-YYYY')";
      $boundedParameter['toDate'] = $toDate;
    }
    $sql = "SELECT INITCAP(H.HOLIDAY_ENAME) AS HOLIDAY_ENAME,
                  WH.DURATION,
                  INITCAP(TO_CHAR(WH.FROM_DATE, 'DD-MON-YYYY'))                   AS FROM_DATE_AD,
                  BS_DATE(TO_CHAR(WH.FROM_DATE, 'DD-MON-YYYY'))                   AS FROM_DATE_BS,
                  INITCAP(TO_CHAR(WH.TO_DATE, 'DD-MON-YYYY'))                     AS TO_DATE_AD,
                  BS_DATE(TO_CHAR(WH.TO_DATE, 'DD-MON-YYYY'))                     AS TO_DATE_BS,
                  INITCAP(TO_CHAR(WH.REQUESTED_DATE, 'DD-MON-YYYY'))              AS REQUESTED_DATE_AD,
                  BS_DATE(TO_CHAR(WH.REQUESTED_DATE, 'DD-MON-YYYY'))              AS REQUESTED_DATE_BS,
                  LEAVE_STATUS_DESC(WH.STATUS)                                    AS STATUS,
                  WH.EMPLOYEE_ID                                                  AS EMPLOYEE_ID,
                  WH.ID                                                           AS ID,
                  WH.REMARKS                                                      AS REMARKS,
                  INITCAP(TO_CHAR(WH.RECOMMENDED_DATE, 'DD-MON-YYYY'))            AS RECOMMENDED_DATE,
                  INITCAP(TO_CHAR(WH.APPROVED_DATE, 'DD-MON-YYYY'))               AS APPROVED_DATE,
                  E.EMPLOYEE_CODE                                                 AS EMPLOYEE_CODE,
                  INITCAP(E.FULL_NAME)                                            AS FULL_NAME,
                  INITCAP(E1.FULL_NAME)                                           AS RECOMMENDED_BY_NAME,
                  INITCAP(E2.FULL_NAME)                                           AS APPROVED_BY_NAME,
                  RA.RECOMMEND_BY                                                 AS RECOMMENDER_ID,
                  RA.APPROVED_BY                                                  AS APPROVER_ID,
                  INITCAP(RECM.FULL_NAME)                                         AS RECOMMENDER_NAME,
                  INITCAP(APRV.FULL_NAME)                                         AS APPROVER_NAME,
                  WH.RECOMMENDED_BY                                               AS RECOMMENDED_BY,
                  WH.APPROVED_BY                                                  AS APPROVED_BY,
                  WH.RECOMMENDED_REMARKS                                          AS RECOMMENDED_REMARKS,
                  WH.APPROVED_REMARKS                                             AS APPROVED_REMARKS
                FROM HRIS_EMPLOYEE_WORK_HOLIDAY WH
                LEFT OUTER JOIN HRIS_HOLIDAY_MASTER_SETUP H
                ON H.HOLIDAY_ID=WH.HOLIDAY_ID
                LEFT OUTER JOIN HRIS_EMPLOYEES E
                ON E.EMPLOYEE_ID=WH.EMPLOYEE_ID
                LEFT OUTER JOIN HRIS_EMPLOYEES E1
                ON E1.EMPLOYEE_ID=WH.RECOMMENDED_BY
                LEFT OUTER JOIN HRIS_EMPLOYEES E2
                ON E2.EMPLOYEE_ID=WH.APPROVED_BY
                LEFT OUTER JOIN HRIS_RECOMMENDER_APPROVER RA
                ON WH.EMPLOYEE_ID = RA.EMPLOYEE_ID
                LEFT OUTER JOIN HRIS_EMPLOYEES RECM
                ON RECM.EMPLOYEE_ID = RA.RECOMMEND_BY
                LEFT OUTER JOIN HRIS_EMPLOYEES APRV
                ON APRV.EMPLOYEE_ID = RA.APPROVED_BY
                WHERE H.STATUS    ='E'
                AND E.STATUS      ='E'
                AND (E1.STATUS    =
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
                OR APRV.STATUS  IS NULL)
                {$searchCondition['sql']}
                {$statusCondition}
                {$holidayCondition}
                {$fromDateCondition}
                {$toDateCondition} ORDER BY WH.FROM_DATE DESC";
    // FOR SHIVAM
    //        $sql = "SELECT INITCAP(H.HOLIDAY_ENAME) AS HOLIDAY_ENAME,
    //                  WH.DURATION,
    //                  INITCAP(TO_CHAR(WH.FROM_DATE, 'DD-MON-YYYY'))                   AS FROM_DATE_AD,
    //                  BS_DATE(TO_CHAR(WH.FROM_DATE, 'DD-MON-YYYY'))                   AS FROM_DATE_BS,
    //                  INITCAP(TO_CHAR(WH.TO_DATE, 'DD-MON-YYYY'))                     AS TO_DATE_AD,
    //                  BS_DATE(TO_CHAR(WH.TO_DATE, 'DD-MON-YYYY'))                     AS TO_DATE_BS,
    //                  INITCAP(TO_CHAR(WH.REQUESTED_DATE, 'DD-MON-YYYY'))              AS REQUESTED_DATE_AD,
    //                  BS_DATE(TO_CHAR(WH.REQUESTED_DATE, 'DD-MON-YYYY'))              AS REQUESTED_DATE_BS,
    //                  LEAVE_STATUS_DESC(WH.STATUS)                                    AS STATUS,
    //                  WH.EMPLOYEE_ID                                                  AS EMPLOYEE_ID,
    //                  WH.ID                                                           AS ID,
    //                  WH.REMARKS                                                      AS REMARKS,
    //                  INITCAP(TO_CHAR(WH.RECOMMENDED_DATE, 'DD-MON-YYYY'))            AS RECOMMENDED_DATE,
    //                  INITCAP(TO_CHAR(WH.APPROVED_DATE, 'DD-MON-YYYY'))               AS APPROVED_DATE,
    //                  E.EMPLOYEE_CODE                                                 AS EMPLOYEE_CODE,
    //                  INITCAP(E.FULL_NAME)                                            AS FULL_NAME,
    //                  WH.RECOMMENDED_BY                                               AS RECOMMENDED_BY,
    //                  WH.APPROVED_BY                                                  AS APPROVED_BY,
    //                  WH.RECOMMENDED_REMARKS                                          AS RECOMMENDED_REMARKS,
    //                  WH.APPROVED_REMARKS                                             AS APPROVED_REMARKS
    //                FROM HRIS_EMPLOYEE_WORK_HOLIDAY WH
    //                LEFT OUTER JOIN HRIS_HOLIDAY_MASTER_SETUP H
    //                ON H.HOLIDAY_ID=WH.HOLIDAY_ID
    //                LEFT OUTER JOIN HRIS_EMPLOYEES E
    //                ON E.EMPLOYEE_ID=WH.EMPLOYEE_ID
    //                WHERE H.STATUS    ='E'
    //                AND E.STATUS      ='E'
    //                {$searchCondition}
    //                {$statusCondition}
    //                {$holidayCondition}
    //                {$fromDateCondition}
    //                {$toDateCondition} ORDER BY WH.REQUESTED_DATE DESC";
    $finalSql = $this->getPrefReportQuery($sql);
    return $this->rawQuery($finalSql, $boundedParameter);
  }

  public function getAttendedHolidayList($employeeId)
  {
    return EntityHelper::rawQueryResult($this->adapter, "
                    SELECT H.HOLIDAY_ID,
                      H.HOLIDAY_CODE,
                      H.HOLIDAY_ENAME,
                      H.HOLIDAY_LNAME,
                      TO_CHAR(H.START_DATE,'DD-MON-YYYY') AS START_DATE,
                      TO_CHAR(H.END_DATE,'DD-MON-YYYY')   AS END_DATE,
                      H.HALFDAY,
                      H.FISCAL_YEAR
                    FROM HRIS_HOLIDAY_MASTER_SETUP H
                    JOIN HRIS_EMPLOYEE_HOLIDAY EH
                    ON (H.HOLIDAY_ID=EH.HOLIDAY_ID),
                      (SELECT MIN(ATTENDANCE_DT) AS MIN_ATTENDANCE_DT,
                        MAX(ATTENDANCE_DT)       AS MAX_ATTENDANCE_DT
                      FROM HRIS_ATTENDANCE_DETAIL
                      WHERE EMPLOYEE_ID={$employeeId}
                      )A
                    WHERE H.STATUS     ='E'
                    AND EH.EMPLOYEE_ID = {$employeeId}
                    AND H.START_DATE >= A.MIN_ATTENDANCE_DT ORDER BY H.START_DATE");
  }
}
