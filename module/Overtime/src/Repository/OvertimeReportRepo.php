<?php

namespace Overtime\Repository;

use Application\Helper\EntityHelper;
use Application\Repository\HrisRepository;

class OvertimeReportRepo extends HrisRepository {

    public function fetch($monthId) {
        $colsForPivot = $this->fetchColumnsForPivot($monthId);
        $sql = "SELECT *
                FROM
                  (SELECT R.EMPLOYEE_ID,
                    E.FULL_NAME,
                    'D_'
                    ||TO_NUMBER(regexp_substr(BS_DATE(R.ATTENDANCE_DT),'[^-]+', 1, 3))  AS MONTH_DAY,
                    (
                    CASE
                      WHEN R.OVERALL_STATUS IN ('PR','LP','LA')
                      AND R.TOTAL_HOUR      IS NOT NULL
                      THEN R.TOTAL_HOUR-R.TOTAL_WORKING_HR
                      ELSE 0
                    END) AS OT_HOUR
                  FROM
                    (SELECT AD.EMPLOYEE_ID,
                      AD.ATTENDANCE_DT,
                      AD.TOTAL_HOUR,
                      (
                      CASE
                        WHEN AD.HALFDAY_PERIOD ='F'
                        THEN ABS(EXTRACT( HOUR FROM (S.END_TIME -S.HALF_DAY_IN_TIME) ))*60 + ABS(EXTRACT( MINUTE FROM (S.END_TIME -S.HALF_DAY_IN_TIME) ))
                        WHEN AD.HALFDAY_PERIOD ='S'
                        THEN ABS(EXTRACT( HOUR FROM (S.HALF_DAY_OUT_TIME -S.START_TIME) ))*60 + ABS(EXTRACT( MINUTE FROM (S.HALF_DAY_OUT_TIME -S.START_TIME) ))
                        WHEN AD.GRACE_PERIOD='E'
                        THEN ABS(EXTRACT( HOUR FROM (S.END_TIME -S.GRACE_START_TIME) ))*60 + ABS(EXTRACT( MINUTE FROM (S.END_TIME -S.GRACE_START_TIME) ))
                        WHEN AD.GRACE_PERIOD='L'
                        THEN ABS(EXTRACT( HOUR FROM (S.GRACE_END_TIME -S.START_TIME) ))*60 + ABS(EXTRACT( MINUTE FROM (S.GRACE_END_TIME -S.START_TIME) ))
                        ELSE S.TOTAL_WORKING_HR
                      END) AS TOTAL_WORKING_HR,
                      AD.OVERALL_STATUS
                    FROM HRIS_ATTENDANCE_DETAIL AD
                    LEFT JOIN HRIS_SHIFTS S
                    ON (AD.SHIFT_ID =S.SHIFT_ID)
                    JOIN HRIS_MONTH_CODE M
                    ON (AD.ATTENDANCE_DT BETWEEN M.FROM_DATE AND M.TO_DATE)
                    WHERE M.MONTH_ID={$monthId}
                    ) R
                  LEFT JOIN HRIS_EMPLOYEES E
                  ON (E.EMPLOYEE_ID                     =R.EMPLOYEE_ID)
                  ) PIVOT (MAX(OT_HOUR ) FOR MONTH_DAY IN ({$colsForPivot}))
                ORDER BY FULL_NAME";
        return $this->rawQuery($sql);
    }

    public function fetchColumns($monthId): array {
        $sql = "SELECT 'D_'
                  ||ROWNUM AS MONTH_DAY_FIELD,
                  ROWNUM AS MONTH_DAY_TITLE
                FROM DUAL
                  CONNECT BY ROWNUM <=
                  (SELECT (TO_DATE-FROM_DATE)+1 FROM HRIS_MONTH_CODE WHERE MONTH_ID= :monthId
                  )";

        $boundedParameter = [];
        $boundedParameter['monthId'] = $monthId;
        return $this->rawQuery($sql, $boundedParameter);
    }

    private function fetchColumnsForPivot($monthId) {
        $sql = "SELECT LISTAGG('''D_'
                  ||MONTH_DAY
                  ||''' AS D_'
                  ||MONTH_DAY, ',') WITHIN GROUP (
                ORDER BY MONTH_DAY) MONTH_DAY_IN
                FROM
                  (SELECT ROWNUM AS MONTH_DAY
                  FROM DUAL
                    CONNECT BY ROWNUM <=
                    (SELECT (TO_DATE-FROM_DATE)+1 FROM HRIS_MONTH_CODE WHERE MONTH_ID={$monthId}
                    )
                  )";

        return $this->rawQuery($sql)[0]['MONTH_DAY_IN'];
    }

    public function fetchMonthlyForGrid($by, $calenderType): array {
        $monthId = $by['monthId'];
        $pivotIn = $this->fetchColumnsForPivot($monthId);
        $searchCondition = EntityHelper::getSearchConditonBounded($by['companyId'], $by['branchId'], $by['departmentId'], $by['positionId'], $by['designationId'], $by['serviceTypeId'], $by['serviceEventTypeId'], $by['employeeTypeId'], $by['employeeId']);
        $boundedParameter = [];
        $boundedParameter=array_merge($boundedParameter, $searchCondition['parameter']);

        if ($calenderType == 'E') {
            $sql = "Select SS.*,AD.ADDITION, AD.DEDUCTION from (SELECT *
                FROM
                  (SELECT AD.EMPLOYEE_ID, E.EMPLOYEE_CODE AS EMPLOYEE_CODE,
                    E.FULL_NAME, D.DEPARTMENT_NAME,
                    'D_'
                    ||to_number(to_char(AD.ATTENDANCE_DATE,'DD')) AS MONTH_DAY,
                    OM.OVERTIME_HOUR
                  FROM
                    (SELECT AD.EMPLOYEE_ID,
                      AD.ATTENDANCE_DT AS ATTENDANCE_DATE
                    FROM HRIS_ATTENDANCE_DETAIL AD
                    JOIN HRIS_MONTH_CODE MC
                    ON (AD.ATTENDANCE_DT BETWEEN MC.FROM_DATE AND MC.TO_DATE)
                    WHERE MC.MONTH_ID= :monthId
                    ) AD
                  LEFT JOIN HRIS_OVERTIME_MANUAL OM
                  ON (AD.EMPLOYEE_ID    =OM.EMPLOYEE_ID
                  AND AD.ATTENDANCE_DATE=OM.ATTENDANCE_DATE)
                  JOIN HRIS_EMPLOYEES E
                  ON (AD.EMPLOYEE_ID                         =E.EMPLOYEE_ID)
                  LEFT JOIN HRIS_DEPARTMENTS D
                  ON (E.DEPARTMENT_ID = D.DEPARTMENT_ID)
                  WHERE 1=1 
                  {$searchCondition['sql']}
                  ) PIVOT (MAX(OVERTIME_HOUR) FOR MONTH_DAY IN ({$pivotIn}))) SS left join HRIS_OVERTIME_A_D AD
 on SS.EMPLOYEE_ID = AD.EMPLOYEE_ID AND AD.MONTH_ID = :monthId";
        } else {
            $sql = "Select SS.*,AD.ADDITION, AD.DEDUCTION from (SELECT *
                FROM
                  (SELECT AD.EMPLOYEE_ID, E.EMPLOYEE_CODE AS EMPLOYEE_CODE,
                    E.FULL_NAME, D.DEPARTMENT_NAME,
                    'D_'
                    ||TO_NUMBER(regexp_substr(BS_DATE(TRUNC(AD.ATTENDANCE_DATE)),'[^-]+', 1, 3)) AS MONTH_DAY,
                    OM.OVERTIME_HOUR
                  FROM
                    (SELECT AD.EMPLOYEE_ID,
                      AD.ATTENDANCE_DT AS ATTENDANCE_DATE
                    FROM HRIS_ATTENDANCE_DETAIL AD
                    JOIN HRIS_MONTH_CODE MC
                    ON (AD.ATTENDANCE_DT BETWEEN MC.FROM_DATE AND MC.TO_DATE)
                    WHERE MC.MONTH_ID= :monthId
                    ) AD
                  LEFT JOIN HRIS_OVERTIME_MANUAL OM
                  ON (AD.EMPLOYEE_ID    =OM.EMPLOYEE_ID
                  AND AD.ATTENDANCE_DATE=OM.ATTENDANCE_DATE)
                  JOIN HRIS_EMPLOYEES E
                  ON (AD.EMPLOYEE_ID                         =E.EMPLOYEE_ID)
                  LEFT JOIN HRIS_DEPARTMENTS D
                  ON (E.DEPARTMENT_ID = D.DEPARTMENT_ID)
                  WHERE 1=1 
                  {$searchCondition['sql']}
                  ) PIVOT (MAX(OVERTIME_HOUR) FOR MONTH_DAY IN ({$pivotIn}))) SS left join HRIS_OVERTIME_A_D AD
 on SS.EMPLOYEE_ID = AD.EMPLOYEE_ID AND AD.MONTH_ID = :monthId";
        }

        $boundedParameter['monthId'] = $monthId;
        return $this->rawQuery($sql, $boundedParameter);
    }

    public function bulkEdit($data,$addDed) {
        foreach ($data as $value) {
            $this->createOrUpdate($value['MONTH_ID'], $value['MONTH_DAY'], $value['EMPLOYEE_ID'], $value['OVERTIME_HOUR']);
        }

        foreach ($addDed as $value){
            $this->addAndDeduct($value['EMPLOYEE_ID'],$value['MONTH_ID'],$value['ADDITION'],$value['DEDUCTION']);
        }
    }

    private function createOrUpdate($m, $d, $e, $h) {
        $hour = $h == null ? 'NULL' : $h;
		if($d == 'DEPARTMENT_NAME'){
		} else {
        $sql = "BEGIN HRIS_OT_MANUAL_CR_OR_UP({$m},{$d},{$e},{$hour}); END;";
		$this->executeStatement($sql);
		}
    }

    private function addAndDeduct($e,$m,$a,$d){

        $aCondition = $a==null?'null':$a;
        $dCondition = $d==null?'null':$d;
        $sql = "DECLARE
            P_MONTH_ID NUMBER := {$m};
            P_EMPLOYEE_ID NUMBER := {$e};
            P_ADDITION HRIS_OVERTIME_A_D.ADDITION%type:= {$aCondition};
            P_DEDUCTION HRIS_OVERTIME_A_D.DEDUCTION%type:= {$dCondition};
            V_ROW_COUNT NUMBER;
        BEGIN
          SELECT COUNT(*)
          INTO V_ROW_COUNT
          FROM HRIS_OVERTIME_A_D
          WHERE MONTH_ID = P_MONTH_ID
          AND EMPLOYEE_ID       = P_EMPLOYEE_ID;
          IF (V_ROW_COUNT       >0 ) THEN
            UPDATE HRIS_OVERTIME_A_D
            SET ADDITION     = P_ADDITION, DEDUCTION = P_DEDUCTION
            WHERE MONTH_ID = P_MONTH_ID 
            AND EMPLOYEE_ID       = P_EMPLOYEE_ID ;
          ELSE
            INSERT
            INTO HRIS_OVERTIME_A_D
              (
                EMPLOYEE_ID,
                MONTH_ID,
                ADDITION,
                DEDUCTION
              )
              VALUES
              (
                P_EMPLOYEE_ID,
                P_MONTH_ID,
                P_ADDITION,
                P_DEDUCTION
              );
          END IF;
        END;";

        $this->executeStatement($sql);
    }

    public function fetchOvertimeReport($data, $dates) {
        $employeeId = $data['employeeId'];
        $companyId = $data['companyId'];
        $branchId = $data['branchId'];
        $departmentId = $data['departmentId'];
        $designationId = $data['designationId'];
        $positionId = $data['positionId'];
        $serviceTypeId = $data['serviceTypeId'];
        $serviceEventTypeId = $data['serviceEventTypeId'];
        $employeeTypeId = $data['employeeTypeId'];

        $searchCondition = EntityHelper::getSearchConditonBounded($companyId, $branchId, $departmentId, $positionId, $designationId, $serviceTypeId, $serviceEventTypeId, $employeeTypeId, $employeeId);
        $boundedParameter = [];
        $boundedParameter=array_merge($boundedParameter, $searchCondition['parameter']);

        $datesIn = "'";
        for ($i = 0; $i < count($dates); $i++) {
            $i == 0 ? $datesIn .= $dates[$i] . "' as DATE_" . str_replace('-', '_', $dates[$i]) : $datesIn .= ",'" . $dates[$i] . "' as DATE_" . str_replace('-', '_', $dates[$i]);
        }
        $sql = "
SELECT E.FULL_NAME,
  E.EMPLOYEE_CODE,
  D.DEPARTMENT_NAME,
  OVD.*
  FROM (SELECT * 
     FROM
    (SELECT
      CASE
        WHEN OM.EMPLOYEE_ID IS NOT NULL
        THEN OM.EMPLOYEE_ID
        ELSE O.EMPLOYEE_ID
      END AS EMPLOYEE_ID,
      CASE
        WHEN OM.ATTENDANCE_DATE IS NOT NULL
        THEN OM.ATTENDANCE_DATE
        ELSE O.OVERTIME_DATE
      END AS ATTENDANCE_DATE,
      CASE
        WHEN OM.OVERTIME_HOUR IS NOT NULL
        THEN (OM.OVERTIME_HOUR)
        ELSE (O.TOTAL)
      END                AS OVERTIME,
      (OM.OVERTIME_HOUR) AS OVERTIME_HOUR,
      (O.TOTAL)          AS TOTAL
    FROM
      (SELECT EMPLOYEE_ID,
        OVERTIME_DATE,
        SUM(TOTAL_HOUR/60) AS TOTAL
      FROM HRIS_OVERTIME
      WHERE STATUS='AP'
      GROUP BY EMPLOYEE_ID,
        OVERTIME_DATE
      ) O
    FULL JOIN HRIS_OVERTIME_MANUAL OM
    ON (O.OVERTIME_DATE = OM.ATTENDANCE_DATE
    AND O.EMPLOYEE_ID = OM.EMPLOYEE_ID)
    ) PIVOT ( MAX( TOTAL ) AS R, MAX( OVERTIME_HOUR ) AS M, MAX( OVERTIME ) AS A FOR ATTENDANCE_DATE IN ($datesIn) )
  ) OVD
LEFT JOIN HRIS_EMPLOYEES E
ON (OVD.employee_id = E.employee_id)
LEFT JOIN HRIS_DEPARTMENTS D
ON (E.DEPARTMENT_ID = D.DEPARTMENT_ID)
WHERE 1=1 {$searchCondition['sql']}";
        return $this->rawQuery($sql, $boundedParameter);
    }

}
