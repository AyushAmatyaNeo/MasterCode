<?php

namespace Advance\Repository;

use Application\Helper\EntityHelper;
use Application\Repository\HrisRepository;
use Setup\Model\HrEmployees;

class AdvanceStatusRepository extends HrisRepository
{

  public function getFilteredRecord($data)
  {
    $fromDate = $data['fromDate'];
    $toDate = $data['toDate'];
    $employeeId = $data['employeeId'];
    $companyId = $data['companyId'];
    $branchId = $data['branchId'];
    $departmentId = $data['departmentId'];
    $designationId = $data['designationId'];
    $positionId = $data['positionId'];
    $serviceTypeId = $data['serviceTypeId'];
    $functionalTypeId = $data['functionalTypeId'];
    $serviceEventTypeId = $data['serviceEventTypeId'];
    $status = $data['status'];
    $employeeTypeId = $data['employeeTypeId'];

    $searchConditon = EntityHelper::getSearchConditon($companyId, $branchId, $departmentId, $positionId, $designationId, $serviceTypeId, $serviceEventTypeId, $employeeTypeId, $employeeId, null, null, $functionalTypeId);
    $fromDateCondition = "";
    $toDateCondition = "";
    $statusCondition = '';

    if ($fromDate != null) {
      $fromDateCondition = " AND AR.REQUESTED_DATE>=TO_DATE('" . $fromDate . "','DD-MM-YYYY') ";
    }
    if ($toDate != null) {
      $toDateCondition = " AND AR.REQUESTED_DATE<=TO_DATE('" . $toDate . "','DD-MM-YYYY') ";
    }

    if ($status != -1 && $status != null) {
      $statusCondition = "AND AR.STATUS='" . $status . "' ";
    }


    $sql = "SELECT
          AR.EMPLOYEE_ID AS EMPLOYEE_ID,
          E.EMPLOYEE_CODE AS EMPLOYEE_CODE,
          AR.ADVANCE_REQUEST_ID AS ADVANCE_REQUEST_ID,
          INITCAP(TO_CHAR(AR.REQUESTED_DATE,'DD-MON-YYYY') ) AS REQUESTED_DATE,
          INITCAP(TO_CHAR(AR.REQUESTED_DATE,'DD-MON-YYYY') ) AS REQUESTED_DATE_AD,
          BS_DATE(TO_CHAR(AR.REQUESTED_DATE,'DD-MON-YYYY') ) AS REQUESTED_DATE_BS,
          INITCAP(TO_CHAR(AR.DATE_OF_ADVANCE,'DD-MON-YYYY') ) AS DATE_OF_ADVANCE,
          INITCAP(TO_CHAR(AR.DATE_OF_ADVANCE,'DD-MON-YYYY') ) AS DATE_OF_ADVANCE_AD,
          BS_DATE(TO_CHAR(AR.DATE_OF_ADVANCE,'DD-MON-YYYY') ) AS DATE_OF_ADVANCE_BS,
          INITCAP(TO_CHAR(AR.RECOMMENDED_DATE,'DD-MON-YYYY') ) AS RECOMMENDED_DATE,
          INITCAP(TO_CHAR(AR.APPROVED_DATE,'DD-MON-YYYY') ) AS APPROVED_DATE,
          AR.REQUESTED_AMOUNT AS REQUESTED_AMOUNT,
          AR.REASON AS REASON,
          AR.STATUS AS STATUS,
          AR.RECOMMENDED_REMARKS AS RECOMMENDED_REMARKS,
          AR.APPROVED_REMARKS AS APPROVED_REMARKS,
          AR.DEDUCTION_TYPE AS DEDUCTION_TYPE,
          AR.DEDUCTION_RATE AS DEDUCTION_RATE,
          AR.DEDUCTION_IN AS DEDUCTION_IN,
          AR.VOUCHER_NO AS VOUCHER_NO,
          (
            CASE
              WHEN AR.DEDUCTION_TYPE = 'M' THEN 'MONTH'
              ELSE 'SALARY'
            END
          ) AS DEDUCTION_TYPE_NAME,
          A.ADVANCE_CODE AS ADVANCE_CODE,
          INITCAP(A.ADVANCE_ENAME) AS ADVANCE_ENAME,
          INITCAP(E.FULL_NAME) AS EMPLOYEE_NAME,
          INITCAP(E2.FULL_NAME) AS RECOMMENDED_BY_NAME,
          INITCAP(E3.FULL_NAME) AS APPROVED_BY_NAME,
          (
            CASE
              WHEN AR.OVERRIDE_RECOMMENDER_ID IS NOT NULL THEN AR.OVERRIDE_RECOMMENDER_ID
              ELSE RA.RECOMMEND_BY
            END
          ) AS RECOMMENDER_ID,
          (
            CASE
              WHEN AR.OVERRIDE_APPROVER_ID IS NOT NULL THEN AR.OVERRIDE_APPROVER_ID
              ELSE RA.APPROVED_BY
            END
          ) AS APPROVER_ID,
          INITCAP(
            CASE
              WHEN
                AR.OVERRIDE_RECOMMENDER_ID
              IS NOT NULL THEN
                OVR.FULL_NAME
              ELSE
                RECM.FULL_NAME
            END
          ) AS RECOMMENDER_NAME,
          INITCAP(
            CASE
              WHEN
                AR.OVERRIDE_APPROVER_ID
              IS NOT NULL THEN
                OVA.FULL_NAME
              ELSE
                APRV.FULL_NAME
            END
          ) AS APPROVER_NAME,
          LEAVE_STATUS_DESC(TRIM(AR.STATUS)) AS STATUS_DETAIL
        FROM
          HRIS_EMPLOYEE_ADVANCE_REQUEST AR
          INNER JOIN HRIS_ADVANCE_MASTER_SETUP A ON A.ADVANCE_ID = AR.ADVANCE_ID
          LEFT JOIN HRIS_EMPLOYEES E ON AR.EMPLOYEE_ID = E.EMPLOYEE_ID
          LEFT JOIN HRIS_EMPLOYEES E2 ON E2.EMPLOYEE_ID = AR.RECOMMENDED_BY
          LEFT JOIN HRIS_EMPLOYEES E3 ON E3.EMPLOYEE_ID = AR.APPROVED_BY
          LEFT JOIN HRIS_RECOMMENDER_APPROVER RA ON RA.EMPLOYEE_ID = AR.EMPLOYEE_ID
          LEFT JOIN HRIS_EMPLOYEES RECM ON RECM.EMPLOYEE_ID = RA.RECOMMEND_BY
          LEFT JOIN HRIS_EMPLOYEES APRV ON APRV.EMPLOYEE_ID = RA.APPROVED_BY
          LEFT JOIN HRIS_EMPLOYEES OVR ON OVR.EMPLOYEE_ID = AR.OVERRIDE_RECOMMENDER_ID
          LEFT JOIN HRIS_EMPLOYEES OVA ON OVA.EMPLOYEE_ID = AR.OVERRIDE_APPROVER_ID
          WHERE 1=1
          {$searchConditon}
          {$fromDateCondition}
          {$toDateCondition}
          {$statusCondition}";
    $sql .= " ORDER BY AR.DATE_OF_ADVANCE DESC";
    $finalQuery = $this->getPrefReportQuery($sql);
    $statement = $this->adapter->query($finalQuery);
    $result = $statement->execute();
    return $result;
  }

  public function getAdvanceReqList($data)
  {
    $fromDate = $data['fromDate'];
    $toDate = $data['toDate'];
    $employeeId = $data['employeeId'];
    $companyId = $data['companyId'];
    $branchId = $data['branchId'];
    $departmentId = $data['departmentId'];
    $designationId = $data['designationId'];
    $positionId = $data['positionId'];
    $serviceTypeId = $data['serviceTypeId'];
    $serviceEventTypeId = $data['serviceEventTypeId'];
    $advanceId = $data['advanceId'];
    $advanceRequestStatusId = $data['advanceRequestStatusId'];
    $employeeTypeId = $data['employeeTypeId'];

    $sql = "SELECT INITCAP(A.ADVANCE_NAME) AS ADVANCE_NAME,
                  A.ADVANCE_CODE,
                  AR.REQUESTED_AMOUNT,
                  INITCAP(TO_CHAR(AR.ADVANCE_DATE, 'DD-MON-YYYY'))                AS ADVANCE_DATE_AD,
                  BS_DATE(TO_CHAR(AR.ADVANCE_DATE, 'DD-MON-YYYY'))                AS ADVANCE_DATE_BS,
                  INITCAP(TO_CHAR(AR.REQUESTED_DATE, 'DD-MON-YYYY'))              AS REQUESTED_DATE_AD,
                  BS_DATE(TO_CHAR(AR.REQUESTED_DATE, 'DD-MON-YYYY'))              AS REQUESTED_DATE_BS,
                  LEAVE_STATUS_DESC(AR.STATUS)                                    AS STATUS,
                  AR.TERMS                                                        AS TERMS,
                  AR.EMPLOYEE_ID                                                  AS EMPLOYEE_ID,
                  AR.ADVANCE_REQUEST_ID                                           AS ADVANCE_REQUEST_ID,
                  INITCAP(TO_CHAR(AR.RECOMMENDED_DATE, 'DD-MON-YYYY'))            AS RECOMMENDED_DATE,
                  INITCAP(TO_CHAR(AR.APPROVED_DATE, 'DD-MON-YYYY'))               AS APPROVED_DATE,
                  INITCAP(E.FULL_NAME)                                            AS FULL_NAME,
                  INITCAP(E1.FULL_NAME)                                           AS RECOMMENDED_BY_NAME,
                  INITCAP(E2.FULL_NAME)                                           AS APPROVED_BY_NAME,
                  RA.RECOMMEND_BY                                                 AS RECOMMENDER_ID,
                  RA.APPROVED_BY                                                  AS APPROVER_ID,
                  INITCAP(RECM.FULL_NAME)                                         AS RECOMMENDER_NAME,
                  INITCAP(APRV.FULL_NAME)                                         AS APPROVER_NAME,
                  AR.RECOMMENDED_BY                                               AS RECOMMENDED_BY,
                  AR.APPROVED_BY                                                  AS APPROVED_BY,
                  AR.RECOMMENDED_REMARKS                                          AS RECOMMENDED_REMARKS,
                  AR.APPROVED_REMARKS                                             AS APPROVED_REMARKS
                FROM HRIS_EMPLOYEE_ADVANCE_REQUEST AR
                LEFT OUTER JOIN HRIS_ADVANCE_MASTER_SETUP A
                ON A.ADVANCE_ID=AR.ADVANCE_ID
                LEFT OUTER JOIN HRIS_EMPLOYEES E
                ON E.EMPLOYEE_ID=AR.EMPLOYEE_ID
                LEFT OUTER JOIN HRIS_EMPLOYEES E1
                ON E1.EMPLOYEE_ID=AR.RECOMMENDED_BY
                LEFT OUTER JOIN HRIS_EMPLOYEES E2
                ON E2.EMPLOYEE_ID=AR.APPROVED_BY
                LEFT OUTER JOIN HRIS_RECOMMENDER_APPROVER RA
                ON AR.EMPLOYEE_ID = RA.EMPLOYEE_ID
                LEFT OUTER JOIN HRIS_EMPLOYEES RECM
                ON RECM.EMPLOYEE_ID = RA.RECOMMEND_BY
                LEFT OUTER JOIN HRIS_EMPLOYEES APRV
                ON APRV.EMPLOYEE_ID = RA.APPROVED_BY
                WHERE A.STATUS        ='E'
                AND E.STATUS        ='E'
                AND (E1.STATUS      =
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
                OR APRV.STATUS IS NULL)";

    if ($advanceRequestStatusId != -1) {
      $sql .= " AND AR.STATUS = '{$advanceRequestStatusId}'";
    }

    if ($advanceId != -1) {
      $sql .= " AND AR.ADVANCE_ID ='" . $advanceId . "'";
    }

    if ($fromDate != null) {
      $sql .= " AND AR.ADVANCE_DATE>=TO_DATE('" . $fromDate . "','DD-MM-YYYY')";
    }

    if ($toDate != null) {
      $sql .= " AND AR.ADVANCE_DATE<=TO_DATE('" . $toDate . "','DD-MM-YYYY')";
    }

    if ($employeeTypeId != null && $employeeTypeId != -1) {
      $sql .= "AND E.EMPLOYEE_TYPE='" . $employeeTypeId . "' ";
    }

    if ($employeeId != -1) {
      $sql .= " AND E." . HrEmployees::EMPLOYEE_ID . " = $employeeId";
    }

    if ($companyId != -1) {
      $sql .= " AND E." . HrEmployees::EMPLOYEE_ID . " IN (SELECT " . HrEmployees::EMPLOYEE_ID . " FROM " . HrEmployees::TABLE_NAME . " WHERE " . HrEmployees::COMPANY_ID . "= $companyId)";
    }
    if ($branchId != -1) {
      $sql .= " AND E." . HrEmployees::EMPLOYEE_ID . " IN (SELECT " . HrEmployees::EMPLOYEE_ID . " FROM " . HrEmployees::TABLE_NAME . " WHERE " . HrEmployees::BRANCH_ID . "= $branchId)";
    }
    if ($departmentId != -1) {
      $sql .= " AND E." . HrEmployees::EMPLOYEE_ID . " IN (SELECT " . HrEmployees::EMPLOYEE_ID . " FROM " . HrEmployees::TABLE_NAME . " WHERE " . HrEmployees::DEPARTMENT_ID . "= $departmentId)";
    }
    if ($designationId != -1) {
      $sql .= " AND E." . HrEmployees::EMPLOYEE_ID . " IN (SELECT " . HrEmployees::EMPLOYEE_ID . " FROM " . HrEmployees::TABLE_NAME . " WHERE " . HrEmployees::DESIGNATION_ID . "= $designationId)";
    }
    if ($positionId != -1) {
      $sql .= " AND E." . HrEmployees::EMPLOYEE_ID . " IN (SELECT " . HrEmployees::EMPLOYEE_ID . " FROM " . HrEmployees::TABLE_NAME . " WHERE " . HrEmployees::POSITION_ID . "= $positionId)";
    }
    if ($serviceTypeId != -1) {
      $sql .= " AND E." . HrEmployees::EMPLOYEE_ID . " IN (SELECT " . HrEmployees::EMPLOYEE_ID . " FROM " . HrEmployees::TABLE_NAME . " WHERE " . HrEmployees::SERVICE_TYPE_ID . "= $serviceTypeId)";
    }
    if ($serviceEventTypeId != -1) {
      $sql .= " AND E." . HrEmployees::EMPLOYEE_ID . " IN (SELECT " . HrEmployees::EMPLOYEE_ID . " FROM " . HrEmployees::TABLE_NAME . " WHERE " . HrEmployees::SERVICE_EVENT_TYPE_ID . "= $serviceEventTypeId)";
    }

    $sql .= " ORDER BY AR.ADVANCE_DATE DESC";

    $statement = $this->adapter->query($sql);
    $result = $statement->execute();
    return $result;
  }
}
