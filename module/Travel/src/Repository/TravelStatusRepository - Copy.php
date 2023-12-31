<?php
namespace Travel\Repository;

use Application\Helper\EntityHelper;
use Application\Repository\HrisRepository;
use Zend\Db\Adapter\AdapterInterface;

class TravelStatusRepository extends HrisRepository {

    public function __construct(AdapterInterface $adapter, $tableName = null) {
        parent::__construct($adapter, $tableName);
    }

    public function getFilteredRecord($search):array {
        $condition = "";
        $condition = EntityHelper::getSearchConditon($search['companyId'], $search['branchId'], $search['departmentId'], $search['positionId'], $search['designationId'], $search['serviceTypeId'], $search['serviceEventTypeId'], $search['employeeTypeId'], $search['employeeId'], null, null, $search['functionalTypeId']);
        if (isset($search['fromDate']) && $search['fromDate'] != null) {
            $condition .= " AND TR.FROM_DATE>=TO_DATE('{$search['fromDate']}','DD-MM-YYYY') ";
        }
        if (isset($search['fromDate']) && $search['toDate'] != null) {
            $condition .= " AND TR.TO_DATE<=TO_DATE('{$search['toDate']}','DD-MM-YYYY') ";
        }
        $startdate = '01-JAN-'.$search['year'];
        $enddate = '30-DEC-'.$search['year'];

        if (isset($search['status']) && $search['status'] != null && $search['status'] != -1) {
            if (gettype($search['status']) === 'array') {
                $csv = "";
                for ($i = 0; $i < sizeof($search['status']); $i++) {
                    if ($i == 0) {
                        $csv = "'{$search['status'][$i]}'";
                    } else {
                        $csv .= ",'{$search['status'][$i]}'";
                    }
                }
                $condition .= "AND TR.STATUS IN ({$csv})";
            } else {
                $condition .= "AND TR.STATUS IN ('{$search['status']}')";
            }
        }
 
        $sql = "SELECT TR.TRAVEL_ID                        AS TRAVEL_ID,
                  TR.TRAVEL_CODE                           AS TRAVEL_CODE,
                  TR.EMPLOYEE_ID                           AS EMPLOYEE_ID,
                  TR.HARDCOPY_SIGNED_FLAG                  AS HARDCOPY_SIGNED_FLAG,
                  (CASE WHEN TR.STATUS = 'RQ' THEN 'Y' ELSE 'N' END) AS ALLOW_EDIT,
                  E.EMPLOYEE_CODE                          AS EMPLOYEE_CODE,
                  E.FULL_NAME                              AS EMPLOYEE_NAME,
                  TO_CHAR(TR.REQUESTED_DATE,'DD-MON-YYYY') AS REQUESTED_DATE_AD,
                  BS_DATE(TR.REQUESTED_DATE)               AS REQUESTED_DATE_BS,
                  TO_CHAR(TR.FROM_DATE,'DD-MON-YYYY')      AS FROM_DATE_AD,
                  BS_DATE(TR.FROM_DATE)                    AS FROM_DATE_BS,
                  TO_CHAR(TR.TO_DATE,'DD-MON-YYYY')        AS TO_DATE_AD,
                  BS_DATE(TR.TO_DATE)                      AS TO_DATE_BS,
                  TR.DESTINATION                           AS DESTINATION,
                  TR.DEPARTURE                             AS DEPARTURE,
                  TR.PURPOSE                               AS PURPOSE,
                  TR.VOUCHER_NO                            AS VOUCHER_NO,
                  TR.REQUESTED_TYPE                        AS REQUESTED_TYPE,
                  (
                  CASE
                  WHEN TR.REQUESTED_TYPE = 'ad' and TR.TRAVEL_TYPE = 'LTR'
                    THEN 'Advance For Domestic Travel'
                    WHEN TR.REQUESTED_TYPE = 'ad' and TR.TRAVEL_TYPE = 'ITR'
                    THEN 'Advance For International Travel'
                    WHEN TR.REQUESTED_TYPE = 'ep' and TR.TRAVEL_TYPE = 'DT'
                    THEN 'Expense Reimbursement'
                    ELSE 'Expense For Travel'
                  END)                                                            AS REQUESTED_TYPE_DETAIL,
                  NVL(TR.REQUESTED_AMOUNT,0)                                      AS REQUESTED_AMOUNT,
                  TR.TRANSPORT_TYPE                                               AS TRANSPORT_TYPE,
                  INITCAP(HRIS_GET_FULL_FORM(TR.TRANSPORT_TYPE,'TRANSPORT_TYPE')) AS TRANSPORT_TYPE_DETAIL,
                  TO_CHAR(TR.DEPARTURE_DATE)                                      AS DEPARTURE_DATE_AD,
                  BS_DATE(TR.DEPARTURE_DATE)                                      AS DEPARTURE_DATE_BS,
                  TO_CHAR(TR.RETURNED_DATE)                                       AS RETURNED_DATE_AD,
                  BS_DATE(TR.RETURNED_DATE)                                       AS RETURNED_DATE_BS,
                  TR.REMARKS                                                      AS REMARKS,
                  TR.STATUS                                                       AS STATUS,
                  travel_status_desc(TR.STATUS)                                    AS STATUS_DETAIL,
                  TR.RECOMMENDED_BY                                               AS RECOMMENDED_BY,
                  RE.FULL_NAME                                                    AS RECOMMENDED_BY_NAME,
                  TO_CHAR(TR.RECOMMENDED_DATE)                                    AS RECOMMENDED_DATE_AD,
                  BS_DATE(TR.RECOMMENDED_DATE)                                    AS RECOMMENDED_DATE_BS,
                  TR.RECOMMENDED_REMARKS                                          AS RECOMMENDED_REMARKS,
                  TR.APPROVED_BY                                                  AS APPROVED_BY,
                  AE.FULL_NAME                                                    AS APPROVED_BY_NAME,
                  TO_CHAR(TR.APPROVED_DATE)                                       AS APPROVED_DATE_AD,
                  BS_DATE(TR.APPROVED_DATE)                                       AS APPROVED_DATE_BS,
                  TR.APPROVED_REMARKS                                             AS APPROVED_REMARKS,
                  RAR.EMPLOYEE_ID                                                 AS RECOMMENDER_ID,
                  RAR.FULL_NAME                                                   AS RECOMMENDER_NAME,
                  RAA.EMPLOYEE_ID                                                 AS APPROVER_ID,
                  RAA.FULL_NAME                                                   AS APPROVER_NAME
                FROM HRIS_EMPLOYEE_TRAVEL_REQUEST TR
                LEFT JOIN HRIS_EMPLOYEES E
                ON (E.EMPLOYEE_ID =TR.EMPLOYEE_ID)
                LEFT JOIN HRIS_EMPLOYEES RE
                ON(RE.EMPLOYEE_ID =TR.RECOMMENDED_BY)
                LEFT JOIN HRIS_EMPLOYEES AE
                ON (AE.EMPLOYEE_ID =TR.APPROVED_BY)
                LEFT JOIN HRIS_RECOMMENDER_APPROVER RA
                ON (RA.EMPLOYEE_ID=TR.EMPLOYEE_ID)
                LEFT JOIN HRIS_EMPLOYEES RAR
                ON (RA.RECOMMEND_BY=RAR.EMPLOYEE_ID)
                LEFT JOIN HRIS_EMPLOYEES RAA
                ON(RA.APPROVED_BY=RAA.EMPLOYEE_ID)
                WHERE 1          =1 {$condition}
                AND TR.REQUESTED_DATE BETWEEN '{$startdate}' AND '{$enddate}'
                ";

        $finalSql = $this->getPrefReportQuery($sql);
        return $this->rawQuery($finalSql);
    }

    public function getFinanceRecord($search):array {
      $condition = "";
      $condition = EntityHelper::getSearchConditon($search['companyId'], $search['branchId'], $search['departmentId'], $search['positionId'], $search['designationId'], $search['serviceTypeId'], $search['serviceEventTypeId'], $search['employeeTypeId'], $search['employeeId'], null, null, $search['functionalTypeId']);
      if (isset($search['fromDate']) && $search['fromDate'] != null) {
          $condition .= " AND TR.FROM_DATE>=TO_DATE('{$search['fromDate']}','DD-MM-YYYY') ";
      }
      if (isset($search['fromDate']) && $search['toDate'] != null) {
          $condition .= " AND TR.TO_DATE<=TO_DATE('{$search['toDate']}','DD-MM-YYYY') ";
      }
      $startdate = '01-JAN-'.$search['year'];
      $enddate = '30-DEC-'.$search['year'];

      if (isset($search['status']) && $search['status'] != null && $search['status'] != -1) {
          if (gettype($search['status']) === 'array') {
              $csv = "";
              for ($i = 0; $i < sizeof($search['status']); $i++) {
                  if ($i == 0) {
                      $csv = "'{$search['status'][$i]}'";
                  } else {
                      $csv .= ",'{$search['status'][$i]}'";
                  }
              }
              $condition .= "AND TR.STATUS IN ({$csv})";
          } else {
              $condition .= "AND TR.STATUS IN ('{$search['status']}')";
          }
      }

      $sql = "SELECT TR.TRAVEL_ID                        AS TRAVEL_ID,
                TR.TRAVEL_CODE                           AS TRAVEL_CODE,
                TR.EMPLOYEE_ID                           AS EMPLOYEE_ID,
                TR.HARDCOPY_SIGNED_FLAG                  AS HARDCOPY_SIGNED_FLAG,
                (CASE WHEN TR.STATUS = 'RQ' THEN 'Y' ELSE 'N' END) AS ALLOW_EDIT,
                E.EMPLOYEE_CODE                          AS EMPLOYEE_CODE,
                E.FULL_NAME                              AS EMPLOYEE_NAME,
                TO_CHAR(TR.REQUESTED_DATE,'DD-MON-YYYY') AS REQUESTED_DATE_AD,
                BS_DATE(TR.REQUESTED_DATE)               AS REQUESTED_DATE_BS,
                TO_CHAR(TR.FROM_DATE,'DD-MON-YYYY')      AS FROM_DATE_AD,
                BS_DATE(TR.FROM_DATE)                    AS FROM_DATE_BS,
                TO_CHAR(TR.TO_DATE,'DD-MON-YYYY')        AS TO_DATE_AD,
                BS_DATE(TR.TO_DATE)                      AS TO_DATE_BS,
                TR.DESTINATION                           AS DESTINATION,
                TR.DEPARTURE                             AS DEPARTURE,
                TR.PURPOSE                               AS PURPOSE,
                TR.VOUCHER_NO                            AS VOUCHER_NO,
                TR.REQUESTED_TYPE                        AS REQUESTED_TYPE,
                (
                CASE
                WHEN TR.REQUESTED_TYPE = 'ep' and TR.TRAVEL_TYPE = 'LTR'
                    THEN 'Expense For Domestic Travel'
                    WHEN TR.REQUESTED_TYPE = 'ep' and TR.TRAVEL_TYPE = 'ITR'
                    THEN 'Expense For International Travel'
                  WHEN TR.REQUESTED_TYPE = 'ep' and TR.TRAVEL_TYPE = 'DT'
                  THEN 'Expense Reimbursement'
                  ELSE 'Expense For Travel'
                END)                                                            AS REQUESTED_TYPE_DETAIL,
                NVL(TR.REQUESTED_AMOUNT,0)                                      AS REQUESTED_AMOUNT,
                TR.TRANSPORT_TYPE                                               AS TRANSPORT_TYPE,
                INITCAP(HRIS_GET_FULL_FORM(TR.TRANSPORT_TYPE,'TRANSPORT_TYPE')) AS TRANSPORT_TYPE_DETAIL,
                TO_CHAR(TR.DEPARTURE_DATE)                                      AS DEPARTURE_DATE_AD,
                BS_DATE(TR.DEPARTURE_DATE)                                      AS DEPARTURE_DATE_BS,
                TO_CHAR(TR.RETURNED_DATE)                                       AS RETURNED_DATE_AD,
                BS_DATE(TR.RETURNED_DATE)                                       AS RETURNED_DATE_BS,
                TR.REMARKS                                                      AS REMARKS,
                TR.STATUS                                                       AS STATUS,
                travel_status_desc(TR.STATUS)                                    AS STATUS_DETAIL,
                TR.RECOMMENDED_BY                                               AS RECOMMENDED_BY,
                RE.FULL_NAME                                                    AS RECOMMENDED_BY_NAME,
                TO_CHAR(TR.RECOMMENDED_DATE)                                    AS RECOMMENDED_DATE_AD,
                BS_DATE(TR.RECOMMENDED_DATE)                                    AS RECOMMENDED_DATE_BS,
                TR.RECOMMENDED_REMARKS                                          AS RECOMMENDED_REMARKS,
                TR.APPROVED_BY                                                  AS APPROVED_BY,
                AE.FULL_NAME                                                    AS APPROVED_BY_NAME,
                TO_CHAR(TR.APPROVED_DATE)                                       AS APPROVED_DATE_AD,
                BS_DATE(TR.APPROVED_DATE)                                       AS APPROVED_DATE_BS,
                TR.APPROVED_REMARKS                                             AS APPROVED_REMARKS,
                RAR.EMPLOYEE_ID                                                 AS RECOMMENDER_ID,
                RAR.FULL_NAME                                                   AS RECOMMENDER_NAME,
                RAA.EMPLOYEE_ID                                                 AS APPROVER_ID,
                RAA.FULL_NAME                                                   AS APPROVER_NAME
              FROM HRIS_EMPLOYEE_TRAVEL_REQUEST TR
              LEFT JOIN HRIS_EMPLOYEES E
              ON (E.EMPLOYEE_ID =TR.EMPLOYEE_ID)
              LEFT JOIN HRIS_EMPLOYEES RE
              ON(RE.EMPLOYEE_ID =TR.RECOMMENDED_BY)
              LEFT JOIN HRIS_EMPLOYEES AE
              ON (AE.EMPLOYEE_ID =TR.APPROVED_BY)
              LEFT JOIN HRIS_RECOMMENDER_APPROVER RA
              ON (RA.EMPLOYEE_ID=TR.EMPLOYEE_ID)
              LEFT JOIN HRIS_EMPLOYEES RAR
              ON (RA.RECOMMEND_BY=RAR.EMPLOYEE_ID)
              LEFT JOIN HRIS_EMPLOYEES RAA
              ON(RA.APPROVED_BY=RAA.EMPLOYEE_ID)
              WHERE 1          =1 {$condition}
              AND TR.REQUESTED_DATE BETWEEN '{$startdate}' AND '{$enddate}'
              AND TR.REQUESTED_TYPE = 'ep'";
      // echo '<pre>';print_r($sql);die;
      $finalSql = $this->getPrefReportQuery($sql);
      return $this->rawQuery($finalSql);
  }


    public function notSettled(): array {
        $sql = "SELECT TR.TRAVEL_ID                   AS TRAVEL_ID,
                  TR.TRAVEL_CODE                      AS TRAVEL_CODE,
                  TR.EMPLOYEE_ID                      AS EMPLOYEE_ID,
                  E.EMPLOYEE_CODE                      AS EMPLOYEE_CODE,
                  E.FULL_NAME                         AS EMPLOYEE_NAME,
                  TO_CHAR(TR.REQUESTED_DATE,'DD-MON-YYYY') AS REQUESTED_DATE_AD,
                  BS_DATE(TR.REQUESTED_DATE)               AS REQUESTED_DATE_BS,
                  TO_CHAR(TR.FROM_DATE,'DD-MON-YYYY') AS FROM_DATE_AD,
                  BS_DATE(TR.FROM_DATE)               AS FROM_DATE_BS,
                  TO_CHAR(TR.TO_DATE,'DD-MON-YYYY')   AS TO_DATE_AD,
                  BS_DATE(TR.TO_DATE)                 AS TO_DATE_BS,
                  TR.DESTINATION                      AS DESTINATION,
                  TR.DEPARTURE                        AS DEPARTURE,
                  TR.PURPOSE                          AS PURPOSE,
                  TR.REASON                           AS REASON,
                  TR.REQUESTED_TYPE                   AS REQUESTED_TYPE,
                  TR.VOUCHER_NO                       AS VOUCHER_NO,
                   NVL(TR.REQUESTED_AMOUNT,0) AS REQUESTED_AMOUNT,
                  TR.TRANSPORT_TYPE          AS TRANSPORT_TYPE,
                  (
                  CASE
                    WHEN TR.TRANSPORT_TYPE = 'AP'
                    THEN 'Aeroplane'
                    WHEN TR.TRANSPORT_TYPE = 'OV'
                    THEN 'Office Vehicles'
                    WHEN TR.TRANSPORT_TYPE = 'TI'
                    THEN 'Taxi'
                    WHEN TR.TRANSPORT_TYPE = 'BS'
                    THEN 'Bus'
                    WHEN TR.TRANSPORT_TYPE = 'OF'
                    THEN 'On Foot'
                  END)                                                            AS TRANSPORT_TYPE_DETAIL,
                  TO_CHAR(TR.DEPARTURE_DATE)                                      AS DEPARTURE_DATE_AD,
                  BS_DATE(TR.DEPARTURE_DATE)                                      AS DEPARTURE_DATE_BS,
                  TO_CHAR(TR.RETURNED_DATE)                                       AS RETURNED_DATE_AD,
                  BS_DATE(TR.RETURNED_DATE)                                       AS RETURNED_DATE_BS,
                  TR.REMARKS                                                      AS REMARKS,
                  TR.STATUS                                                       AS STATUS,
                  LEAVE_STATUS_DESC(TR.STATUS)                                    AS STATUS_DETAIL,
                  TR.RECOMMENDED_BY                                               AS RECOMMENDED_BY,
                  RE.FULL_NAME                                                    AS RECOMMENDED_BY_NAME,
                  TO_CHAR(TR.RECOMMENDED_DATE)                                    AS RECOMMENDED_DATE_AD,
                  BS_DATE(TR.RECOMMENDED_DATE)                                    AS RECOMMENDED_DATE_BS,
                  TR.RECOMMENDED_REMARKS                                          AS RECOMMENDED_REMARKS,
                  TR.APPROVED_BY                                                  AS APPROVED_BY,
                  AE.FULL_NAME                                                    AS APPROVED_BY_NAME,
                  TO_CHAR(TR.APPROVED_DATE)                                       AS APPROVED_DATE_AD,
                  BS_DATE(TR.APPROVED_DATE)                                       AS APPROVED_DATE_BS,
                  TR.APPROVED_REMARKS                                             AS APPROVED_REMARKS,
                  RAR.EMPLOYEE_ID                                                 AS RECOMMENDER_ID,
                  RAR.FULL_NAME                                                   AS RECOMMENDER_NAME,
                  RAA.EMPLOYEE_ID                                                 AS APPROVER_ID,
                  RAA.FULL_NAME                                                   AS APPROVER_NAME
                FROM (SELECT AD.*,(CASE WHEN EP.STATUS IS NULL THEN 'Not Applied' ELSE 'Not Approved' END) AS REASON
                  FROM HRIS_EMPLOYEE_TRAVEL_REQUEST AD
                  LEFT JOIN HRIS_EMPLOYEE_TRAVEL_REQUEST EP
                  ON (AD.TRAVEL_ID        =EP.REFERENCE_TRAVEL_ID)
                  WHERE AD.REQUESTED_TYPE ='ad'
                  AND AD.STATUS           ='AP'
                  AND (TRUNC(
                    CASE
                      WHEN AD.REQUESTED_DATE IS NULL
                      THEN SYSDATE
                      ELSE AD.REQUESTED_DATE
                    END)- TRUNC(AD.TO_DATE))>7
                  AND (EP.STATUS            =
                    CASE
                      WHEN EP.STATUS IS NOT NULL
                      THEN 'AP'
                    END
                  OR NULL IS NULL )
                  ) TR
                LEFT JOIN HRIS_EMPLOYEES E
                ON (E.EMPLOYEE_ID =TR.EMPLOYEE_ID)
                LEFT JOIN HRIS_EMPLOYEES RE
                ON(RE.EMPLOYEE_ID =TR.RECOMMENDED_BY)
                LEFT JOIN HRIS_EMPLOYEES AE
                ON (AE.EMPLOYEE_ID =TR.APPROVED_BY)
                LEFT JOIN HRIS_RECOMMENDER_APPROVER RA
                ON (RA.EMPLOYEE_ID=TR.EMPLOYEE_ID)
                LEFT JOIN HRIS_EMPLOYEES RAR
                ON (RA.RECOMMEND_BY=RAR.EMPLOYEE_ID)
                LEFT JOIN HRIS_EMPLOYEES RAA
                ON(RA.APPROVED_BY=RAA.EMPLOYEE_ID) ORDER BY TR.REQUESTED_DATE DESC";
        return $this->rawQuery($sql);
    }
    
    public function getSameDateApprovedStatus($employeeId, $fromDate, $toDate) {
        $sql = "SELECT COUNT(*) as TRAVEL_COUNT
  FROM HRIS_EMPLOYEE_TRAVEL_REQUEST
  WHERE (('{$fromDate}' BETWEEN FROM_DATE AND TO_DATE)
  OR ('{$toDate}' BETWEEN FROM_DATE AND TO_DATE))
  AND STATUS  IN ('AP','CP','CR')
  AND EMPLOYEE_ID = $employeeId
                ";
        $statement = $this->adapter->query($sql);
        $result = $statement->execute();
        return $result->current();
    }
}
