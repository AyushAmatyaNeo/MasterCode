<?php

namespace AttendanceManagement\Repository;

use Application\Helper\EntityHelper;
use Application\Helper\Helper;
use Application\Repository\HrisRepository;

class PenaltyRepo extends HrisRepository
{

  public function monthWiseReport($data)
  {
    //        $companyCondition = "";
    //        $branchCondition = "";
    //        $departmentCondition = "";
    //        $designationCondition = "";
    //        $positionCondition = "";
    //        $serviceTypeCondition = "";
    //        $serviceEventTypeConditon = "";
    //        $employeeCondition = "";
    //        $employeeTypeCondition = "";

    //        if (isset($data['companyId']) && $data['companyId'] != null && $data['companyId'] != -1) {
    //            $companyCondition = "AND E.COMPANY_ID = {$data['companyId']}";
    //        }
    //        if (isset($data['branchId']) && $data['branchId'] != null && $data['branchId'] != -1) {
    //            $branchCondition = "AND E.BRANCH_ID = {$data['branchId']}";
    //        }
    //        if (isset($data['departmentId']) && $data['departmentId'] != null && $data['departmentId'] != -1) {
    //            $departmentCondition = "AND E.DEPARTMENT_ID = {$data['departmentId']}";
    //        }
    //        if (isset($data['designationId']) && $data['designationId'] != null && $data['designationId'] != -1) {
    //            $designationCondition = "AND E.DESIGNATION_ID = {$data['designationId']}";
    //        }
    //        if (isset($data['positionId']) && $data['positionId'] != null && $data['positionId'] != -1) {
    //            $positionCondition = "AND E.POSITION_ID = {$data['positionId']}";
    //        }
    //        if (isset($data['serviceTypeId']) && $data['serviceTypeId'] != null && $data['serviceTypeId'] != -1) {
    //            $serviceTypeCondition = "AND E.SERVICE_TYPE_ID = {$data['serviceTypeId']}";
    //        }
    //        if (isset($data['serviceEventTypeId']) && $data['serviceEventTypeId'] != null && $data['serviceEventTypeId'] != -1) {
    //            $serviceEventTypeConditon = "AND E.SERVICE_EVENT_TYPE_ID = {$data['serviceEventTypeId']}";
    //        }
    //        if (isset($data['employeeId']) && $data['employeeId'] != null && $data['employeeId'] != -1) {
    //            $employeeCondition = "AND E.EMPLOYEE_ID = {$data['employeeId']}";
    //        }
    //        if (isset($data['employeeTypeId']) && $data['employeeTypeId'] != null && $data['employeeTypeId'] != -1) {
    //            $employeeTypeCondition = "AND E.EMPLOYEE_TYPE = '{$data['employeeTypeId']}'";
    //        }

    //        $condition = $companyCondition . $branchCondition . $departmentCondition . $designationCondition . $positionCondition . $serviceTypeCondition . $serviceEventTypeConditon . $employeeCondition . $employeeTypeCondition;

    $employeeId = $data['employeeId'];
    $companyId = $data['companyId'];
    $branchId = $data['branchId'];
    $departmentId = $data['departmentId'];
    $designationId = $data['designationId'];
    $positionId = $data['positionId'];
    $serviceTypeId = $data['serviceTypeId'];
    $serviceEventTypeId = $data['serviceEventTypeId'];
    $employeeTypeId = $data['employeeTypeId'];

    $boundedParams = [];
    $searchCondition = EntityHelper::getSearchConditonBounded($companyId, $branchId, $departmentId, $positionId, $designationId, $serviceTypeId, $serviceEventTypeId, $employeeTypeId, $employeeId, null, null, null);
    $boundedParams = array_merge($boundedParams, $searchCondition['parameter']);

    $sql = <<<EOT
                SELECT C.COMPANY_NAME,
                  D.DEPARTMENT_NAME,
                  E.FULL_NAME,
                  E.EMPLOYEE_ID,
                  E.EMPLOYEE_CODE,
				  nvl(pd.no_of_days,0) as Deducted_days,
                  TO_CHAR(A.ATTENDANCE_DT,'DD-MON-YYYY') AS ATTENDANCE_DT,
                  BS_DATE(A.ATTENDANCE_DT)               AS ATTENDANCE_DT_N,
                  (
                    CASE
                    WHEN
                    A.IN_TIME >= to_date(to_char(A.ATTENDANCE_DT,'DD-MON-YYYY')||' '||to_char(HS.start_time + INTERVAL '31' MINUTE,'HH:MI AM'),'DD-MON-YYYY HH:MI AM')
                    THEN 'More Than 30 min Late'  
                    WHEN
                     A.OUT_TIME < to_date(to_char(A.ATTENDANCE_DT,'DD-MON-YYYY')||' '||to_char(HS.end_time,'HH:MI AM'),'DD-MON-YYYY HH:MI AM')
                    THEN 'Early Out'  
                    ELSE '4th Day Late'
                    END) AS TYPE,
                    (
                      CASE
                      WHEN
                      A.IN_TIME >= to_date(to_char(A.ATTENDANCE_DT,'DD-MON-YYYY')||' '||to_char(HS.start_time + INTERVAL '31' MINUTE,'HH:MI AM'),'DD-MON-YYYY HH:MI AM')
                      THEN 'N'  
                      WHEN
                       A.OUT_TIME < to_date(to_char(A.ATTENDANCE_DT,'DD-MON-YYYY')||' '||to_char(HS.end_time,'HH:MI AM'),'DD-MON-YYYY HH:MI AM')
                      THEN 'N'  
                      ELSE 'Y'
                      END) AS TYPE_CODE
                FROM HRIS_ATTENDANCE_DETAIL A
                LEFT JOIN HRIS_SHIFTS HS
                ON (HS.SHIFT_ID=A.SHIFT_ID)
                LEFT JOIN HRIS_EMPLOYEES E
                ON (A.EMPLOYEE_ID =E.EMPLOYEE_ID)
                LEFT JOIN HRIS_COMPANY C
                ON (E.COMPANY_ID = C.COMPANY_ID)
                LEFT JOIN HRIS_DEPARTMENTS D
                ON (D.DEPARTMENT_ID = E.DEPARTMENT_ID)
				LEFT JOIN hris_employee_penalty_days PD on(PD.employee_id = E.employee_id and PD.attendance_dt = A.attendance_dt),
                  (SELECT * FROM HRIS_MONTH_CODE WHERE HRIS_MONTH_CODE.MONTH_ID={$data['monthId']}
                  ) M
                WHERE A.OVERALL_STATUS IN ('LA','BA')
                AND (A.ATTENDANCE_DT BETWEEN M.FROM_DATE AND M.TO_DATE )
                {$searchCondition['sql']}
                ORDER BY EMPLOYEE_ID,to_date(ATTENDANCE_DT) DESC
EOT;
    // echo('<pre>');print_r($sql);die;
    return $this->rawQuery($sql, $boundedParams);
  }

  public function getMonthlyReport($data)
  {
    $months = implode(",", $data['monthId']);
    $employeeId = $data['employeeId'];
    $companyId = $data['companyId'];
    $branchId = $data['branchId'];
    $departmentId = $data['departmentId'];
    $designationId = $data['designationId'];
    $positionId = $data['positionId'];
    $serviceTypeId = $data['serviceTypeId'];
    $serviceEventTypeId = $data['serviceEventTypeId'];
    $employeeTypeId = $data['employeeTypeId'];

    $boundedParams = [];
    $searchCondition = EntityHelper::getSearchConditonBounded($companyId, $branchId, $departmentId, $positionId, $designationId, $serviceTypeId, $serviceEventTypeId, $employeeTypeId, $employeeId, null, null, null);
    $boundedParams = array_merge($boundedParams, $searchCondition['parameter']);

    $sql = "
              SELECT C.COMPANY_NAME,
              D.DEPARTMENT_NAME,
              E.FULL_NAME,
              E.EMPLOYEE_ID,
              E.EMPLOYEE_CODE, M.month_edesc, M.month_id,
sum(no_of_days) as TOTAL_DEDUCTION_DAYS from hris_employee_penalty_days P
left join hris_month_code M on (P.attendance_dt between M.from_date and M.to_date)
left join hris_employees E on (E.employee_id = P.employee_id)
left join hris_company C on (C.company_id = E.company_id)
left join hris_departments D on (D.department_id = E.department_id)
where P.remarks in ('4th day penalty') and M.month_id in ($months) {$searchCondition['sql']}
group by C.COMPANY_NAME,
              D.DEPARTMENT_NAME,
              E.FULL_NAME,
              E.EMPLOYEE_ID,
              E.EMPLOYEE_CODE, M.month_edesc, M.month_id
order by E.employee_id, M.month_id";
    // echo('<pre>');print_r($sql);die;
    return $this->rawQuery($sql, $boundedParams);
  }

  public function penaltyDetail($employeeId, $attendanceDt, $type)
  {
    $lateStatusCondition = "('E','L')";
    $rowNumCondition = "4";
    $extraCondition = "Y";

    if ($type == 'N') {
      $extraCondition = "N";
      $rowNumCondition = "1";
    }


    $sql = <<<EOT
                SELECT TO_CHAR(ATTENDANCE_DT,'DD-MON-YYYY') AS ATTENDANCE_DT,
                  BS_DATE(ATTENDANCE_DT)                    AS ATTENDANCE_DT_N,
                  TO_CHAR(IN_TIME,'HH:MI AM')               AS IN_TIME,
                  TO_CHAR(OUT_TIME,'HH:MI AM')              AS OUT_TIME,
                  TO_CHAR(START_TIME,'HH:MI AM')            AS START_TIME,
                  TO_CHAR(END_TIME,'HH:MI AM')              AS END_TIME,
                  TYPE
                FROM
                  (SELECT A.ATTENDANCE_DT,
                    A.IN_TIME,
                    A.OUT_TIME,
                    A.SHIFT_ID,
                    S.START_TIME   AS START_TIME,
                    S.END_TIME AS END_TIME,
                    (
                    CASE
                      WHEN A.LATE_STATUS = 'L'
                      THEN 'Late In'
                      ELSE 'Early Out'
                    END ) AS TYPE
                  FROM HRIS_ATTENDANCE_DETAIL A
                  LEFT JOIN HRIS_SHIFTS S
                  ON (A.SHIFT_ID        = S.SHIFT_ID)
                  WHERE A.ATTENDANCE_DT<={$attendanceDt->getExpression()}
                  and (A.IN_TIME < to_date(to_char(A.ATTENDANCE_DT,'DD-MON-YYYY')||' '||to_char(S.start_time + INTERVAL '31' MINUTE,'HH:MI AM'),'DD-MON-YYYY HH:MI AM')
                  and A.OUT_TIME >= to_date(to_char(A.ATTENDANCE_DT,'DD-MON-YYYY')||' '||to_char(S.end_time,'HH:MI AM'),'DD-MON-YYYY HH:MI AM')
                  or '{$extraCondition}' = 'N')
                  AND A.EMPLOYEE_ID     = {$employeeId}
                  AND A.LATE_STATUS    IN {$lateStatusCondition}
                  ORDER BY A.ATTENDANCE_DT DESC
                  )
                WHERE ROWNUM <={$rowNumCondition}
EOT;
    // echo('<pre>');print_r($sql);die;
    $statement = $this->adapter->query($sql);
    $result = $statement->execute();
    return Helper::extractDbData($result);
  }

  public function checkIfAlreadyDeducted($monthId)
  {
    return EntityHelper::rawQueryResult($this->adapter, "
            SELECT (
              CASE
                WHEN COUNT(PM.FISCAL_YEAR_ID) > 0
                THEN 'Y'
                ELSE 'N'
              END) AS IS_DEDUCTED
            FROM HRIS_PENALIZED_MONTHS PM
            JOIN HRIS_MONTH_CODE M
            ON (PM.FISCAL_YEAR_ID     =M.FISCAL_YEAR_ID
            AND PM.FISCAL_YEAR_MONTH_NO = M.FISCAL_YEAR_MONTH_NO)
            WHERE M.MONTH_ID= {$monthId} ")->current();
  }

  public function deduct($data)
  {
    EntityHelper::rawQueryResult($this->adapter, "
                BEGIN
                  HRIS_LATE_LEAVE_DEDUCTION({$data['companyId']},{$data['fiscalYearId']},{$data['fiscalYearMonthNo']},{$data['noOfDeductionDays']},{$data['employeeId']},'{$data['action']}');
                END;
");
  }

  public function penalizedMonthReport($fiscalYearId, $fiscalYearMonthNo): array
  {
    $sql = "SELECT CMC.COMPANY_ID,
                  CMC.COMPANY_NAME,
                  CMC.FISCAL_YEAR_ID,
                  CMC.FISCAL_YEAR_MONTH_NO,
                  CMC.MONTH_EDESC,
                  PM.NO_OF_DAYS
                FROM
                  (SELECT HRIS_COMPANY.*,HRIS_MONTH_CODE.* FROM HRIS_COMPANY , HRIS_MONTH_CODE
                  ) CMC
                LEFT JOIN HRIS_PENALIZED_MONTHS PM
                ON (PM.COMPANY_ID           =CMC.COMPANY_ID
                AND PM.FISCAL_YEAR_ID       =CMC.FISCAL_YEAR_ID
                AND PM.FISCAL_YEAR_MONTH_NO = CMC.FISCAL_YEAR_MONTH_NO)
                WHERE CMC.FISCAL_YEAR_ID    ={$fiscalYearId}
                AND CMC.FISCAL_YEAR_MONTH_NO={$fiscalYearMonthNo}
";
    //echo('<pre>');print_r($sql);die;
    $statement = $this->adapter->query($sql);
    $result = $statement->execute();
    return iterator_to_array($result, false);
  }

  public function getLeaveDeductedDetail($employeeId, $monthId)
  {
    $sql = "select PD.employee_id, PD.attendance_dt, bs_date(PD.attendance_dt) AS ATTENDANCE_DT_N, LM.leave_ename, PD.no_of_days, PD.remarks
      from hris_employee_penalty_days PD 
      left join hris_leave_master_setup LM on (PD.leave_id = LM.leave_id) where 
      PD.remarks in ('4th day penalty') and PD.employee_id = $employeeId
      and attendance_dt between (select from_date from hris_month_code where month_id = $monthId) and (select to_date from hris_month_code where month_id = $monthId)";
    // echo('<pre>');print_r($sql);die;
    $statement = $this->adapter->query($sql);
    $result = $statement->execute();
    return Helper::extractDbData($result);
  }
}
