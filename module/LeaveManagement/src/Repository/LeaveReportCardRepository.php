<?php

namespace LeaveManagement\Repository;

use Application\Helper\EntityHelper;
use Application\Repository\HrisRepository;
use LeaveManagement\Model\LeaveApply;
use Setup\Model\HrEmployees;
use Zend\Db\Sql\Expression;
use Zend\Db\Sql\Sql;

class LeaveReportCardRepository extends HrisRepository
{

    public function fetchLeaveReportCard($by)
    {
        $leaveId = $by['data']['leaveId'];
        $leaveIdFilter = "";
        $boundedParameter = [];
        if ($leaveId != '' && $leaveId != null) {
            $leaveData = $this->getBoundedForArray($leaveId, 'leaveId');
            $boundedParameter = array_merge($boundedParameter, $leaveData['parameter']);
            $leaveIdFilter = " and l.leave_id IN ({$leaveData['sql']})";
        }

        $employees = $by['data']['employeeId'];
        $boundedParameter['employees'] = $employees;
        //$employees = implode(',', $employees);

        $leaveYear = $by['data']['leaveYear'];

        if ($leaveYear != null) {
            $boundedParameter['leaveYear'] = $leaveYear;
            $leaveYearStatusCondition = "( ( L.STATUS ='E' OR L.OLD_LEAVE='Y' ) AND L.LEAVE_YEAR= :leaveYear )";
        } else {
            $leaveYearStatusCondition = "L.STATUS ='E'";
        }


        $sql = "( SELECT
    la.id                 AS id,
    e.employee_code       AS employee_id,
    e.employee_code       AS employee_code,
    e.join_date           AS join_date,
    la.leave_id           AS leave_id,
    ( CASE
        WHEN e.addr_perm_street_address IS NULL THEN '-'
        ELSE e.addr_perm_street_address
    END ) AS addr_perm_street_address,
    ( CASE
        WHEN e.addr_temp_street_address IS NULL THEN '-'
        ELSE e.addr_temp_street_address
    END ) AS addr_temp_street_address,
    d.designation_title   AS designation_title,
    hd.department_name    AS department,
    initcap(TO_CHAR(la.start_date, 'DD-MON-YYYY')) AS from_date_ad,
    bs_date(TO_CHAR(la.start_date, 'DD-MON-YYYY')) AS from_date_bs,
    initcap(TO_CHAR(la.end_date, 'DD-MON-YYYY')) AS to_date_ad,
    bs_date(TO_CHAR(la.end_date, 'DD-MON-YYYY')) AS to_date_bs,
    la.half_day           AS half_day,
    ( CASE
        WHEN ( la.half_day IS NULL
               OR la.half_day = 'N' ) THEN 'Full Day'
        WHEN ( la.half_day = 'F' ) THEN 'First Half'
        ELSE 'Second Half'
    END ) AS half_day_detail,
    la.grace_period       AS grace_period,
    ( CASE
        WHEN la.grace_period = 'E' THEN 'Early'
        WHEN la.grace_period = 'L' THEN 'Late'
        ELSE '-'
    END ) AS grace_period_detail,
    la.no_of_days         AS no_of_days,
    (TO_date(la.requested_dt, 'DD-MON-YYYY')) AS requested_dt_ad,
    bs_date(TO_CHAR(la.requested_dt, 'DD-MON-YYYY')) AS requested_dt_bs,
    ( CASE
        WHEN la.remarks IS NULL THEN '-'
        ELSE la.remarks
    END ) AS remarks,
    TO_CHAR(la.status) AS status,
    TO_CHAR(leave_status_desc(la.status)) AS status_detail,
    TO_CHAR(la.recommended_by) AS recommended_by,
    initcap(TO_CHAR(la.recommended_dt, 'DD-MON-YYYY')) AS recommended_dt,
    TO_CHAR(la.recommended_remarks) AS recommended_remarks,
    TO_CHAR(la.approved_by) AS approved_by,
    initcap(TO_CHAR(la.approved_dt, 'DD-MON-YYYY')) AS approved_dt,
    TO_CHAR(la.approved_remarks) AS approved_remarks,
    ( CASE
        WHEN la.status = 'XX' THEN 'Y'
        ELSE 'N'
    END ) AS allow_edit,
    ( CASE
        WHEN la.status IN (
            'RQ',
            'RC',
            'AP'
        ) THEN 'Y'
        ELSE 'N'
    END ) AS allow_delete,
    l.leave_code          AS leave_code,
    initcap(l.leave_ename) AS leave_ename,
    initcap(e.full_name) AS full_name,
    initcap(e2.full_name) AS recommended_by_name,
    initcap(e3.full_name) AS approved_by_name,
    ra.recommend_by       AS recommender_id,
    ra.approved_by        AS approver_id,
    initcap(recm.full_name) AS recommender_name,
    initcap(aprv.full_name) AS approver_name
FROM
    hris_employee_leave_request la
    INNER JOIN hris_leave_master_setup l ON l.leave_id = la.leave_id
    LEFT JOIN hris_employees e ON la.employee_id = e.employee_id
    LEFT JOIN hris_employees e2 ON e2.employee_id = la.recommended_by
    LEFT JOIN hris_employees e3 ON e3.employee_id = la.approved_by
    LEFT JOIN hris_recommender_approver ra ON ra.employee_id = la.employee_id
    LEFT JOIN hris_employees recm ON recm.employee_id = ra.recommend_by
    LEFT JOIN hris_employees aprv ON aprv.employee_id = ra.approved_by
    LEFT JOIN hris_designations d ON e.designation_id = d.designation_id
    LEFT JOIN hris_departments hd ON e.department_id = hd.department_id
WHERE
    ( ( l.status = 'E'
        OR l.old_leave = 'Y' )
      AND l.leave_year = :leaveyear )
    AND la.status IN (
        'AP',
        'CP',
        'CR'
    )
    AND e.employee_id IN (
        :employees
    )
UNION
SELECT
    0 AS id,
    e.employee_code       AS employee_id,
    e.employee_code       AS employee_code,
    e.join_date           AS join_date,
    la.leave_id           AS leave_id,
    ( CASE
        WHEN e.addr_perm_street_address IS NULL THEN '-'
        ELSE e.addr_perm_street_address
    END ) AS addr_perm_street_address,
    ( CASE
        WHEN e.addr_temp_street_address IS NULL THEN '-'
        ELSE e.addr_temp_street_address
    END ) AS addr_temp_street_address,
    d.designation_title   AS designation_title,
    hd.department_name    AS department,
    initcap(TO_CHAR(la.attendance_dt, 'DD-MON-YYYY')) AS from_date_ad,
    '' AS from_date_bs,
    initcap(TO_CHAR(la.attendance_dt, 'DD-MON-YYYY')) AS to_date_ad,
    '' AS to_date_bs,
    '' AS half_day,
    '' AS half_day_detail,
    '' AS grace_period,
    '' AS grace_period_detail,
    la.no_of_days         AS no_of_days,
    (TO_date(la.attendance_dt, 'DD-MON-YYYY')) AS requested_dt_ad,
    '' AS requested_dt_bs,
    ( CASE
        WHEN la.remarks IS NULL THEN '-'
        ELSE la.remarks
    END ) AS remarks,
    '' AS status,
    '' AS status_detail,
    '' AS recommended_by,
    '',
    '' AS recommended_remarks,
    '' AS approved_by,
    '' AS approved_dt,
    '' AS approved_remarks,
    'N' AS allow_edit,
    'N' AS allow_delete,
    l.leave_code          AS leave_code,
    initcap(l.leave_ename) AS leave_ename,
    initcap(e.full_name) AS full_name,
    'Deduction' AS recommended_by_name,
    'Deduction' AS approved_by_name,
    1 AS recommender_id,
    1 AS approver_id,
    '' AS recommender_name,
    '' AS approver_name
FROM
    hris_employee_penalty_days la
    INNER JOIN hris_leave_master_setup l ON l.leave_id = la.leave_id
    LEFT JOIN hris_employees e ON la.employee_id = e.employee_id
    LEFT JOIN hris_designations d ON e.designation_id = d.designation_id
    LEFT JOIN hris_departments hd ON e.department_id = hd.department_id
WHERE
    ( ( l.status = 'E'
        OR l.old_leave = 'Y' )
      AND l.leave_year = :leaveyear )
    AND e.employee_id IN (
        :employees
    ) 
UNION
SELECT
    0 AS id,
    e.employee_code       AS employee_id,
    e.employee_code       AS employee_code,
    e.join_date           AS join_date,
    la.leave_id           AS leave_id,
    ( CASE
        WHEN e.addr_perm_street_address IS NULL THEN '-'
        ELSE e.addr_perm_street_address
    END ) AS addr_perm_street_address,
    ( CASE
        WHEN e.addr_temp_street_address IS NULL THEN '-'
        ELSE e.addr_temp_street_address
    END ) AS addr_temp_street_address,
    d.designation_title   AS designation_title,
    hd.department_name    AS department,
    initcap(TO_CHAR(la.CREATED_DATE, 'DD-MON-YYYY')) AS from_date_ad,
    '' AS from_date_bs,
    initcap(TO_CHAR(la.CREATED_DATE, 'DD-MON-YYYY')) AS to_date_ad,
    '' AS to_date_bs,
    '' AS half_day,
    '' AS half_day_detail,
    '' AS grace_period,
    '' AS grace_period_detail,
    la.ENCASH_DAYS         AS no_of_days,
    (TO_date(la.CREATED_DATE, 'DD-MON-YYYY')) AS requested_dt_ad,
    '' AS requested_dt_bs,
    'Encashed' AS remarks,
    '' AS status,
    '' AS status_detail,
    '' AS recommended_by,
    '',
    '' AS recommended_remarks,
    '' AS approved_by,
    '' AS approved_dt,
    '' AS approved_remarks,
    'N' AS allow_edit,
    'N' AS allow_delete,
    l.leave_code          AS leave_code,
    initcap(l.leave_ename) AS leave_ename,
    initcap(e.full_name) AS full_name,
    'Encashed' AS recommended_by_name,
    'Encashed' AS approved_by_name,
    1 AS recommender_id,
    1 AS approver_id,
    '' AS recommender_name,
    '' AS approver_name
FROM
    hris_emp_self_leave_closing la
    INNER JOIN hris_leave_master_setup l ON l.leave_id = la.leave_id
    LEFT JOIN hris_employees e ON la.employee_id = e.employee_id
    LEFT JOIN hris_designations d ON e.designation_id = d.designation_id
    LEFT JOIN hris_departments hd ON e.department_id = hd.department_id
WHERE
    ( ( l.status = 'E'
        OR l.old_leave = 'Y' )
      AND l.leave_year = :leaveyear )
    AND e.employee_id IN (
        :employees
    ) 
)
ORDER BY
    requested_dt_ad asc
";
        //                            echo $sql; die;
        return $this->rawQuery($sql, $boundedParameter);
    }

    public function fetchLeaves($empId, $leaveId, $leaveYear)
    {
        $boundedParameter = [];
        $leaveIdFilter = "";
        if ($leaveId != '' && $leaveId != null) {
            $leaveData = $this->getBoundedForArray($leaveId, 'leaveId');
            $boundedParameter = array_merge($boundedParameter, $leaveData['parameter']);
            $leaveIdFilter = " and lms.leave_id IN ({$leaveData['sql']})";
        }


        if ($leaveYear != null) {
            $boundedParameter['leaveYear'] = $leaveYear;
            $leaveYearStatusCondition = "( ( lms.STATUS ='E' OR lms.OLD_LEAVE='Y' ) AND lms.LEAVE_YEAR= :leaveYear )";
        } else {
            $leaveYearStatusCondition = "lms.STATUS ='E'";
        }

        $boundedParameter['empId'] = $empId;

        $sql = "select 
    Lms.Leave_Ename,Lms.LEAVE_ID,
    la.Total_Days - 
    case when lms.is_monthly='Y' 
    and lms.CARRY_FORWARD='Y' then nvl(la.PREVIOUS_YEAR_BAL, 0) else 0 end as total_days, nvl(la.PREVIOUS_YEAR_BAL, 0) as PREVIOUS_YEAR_BAL,
    la.Total_Days + case when lms.is_monthly='Y' then 0 else nvl(la.PREVIOUS_YEAR_BAL, 0) end as Balance
    from hris_leave_master_setup lms
    left join Hris_Employee_Leave_Assign la on (lms.leave_id=la.leave_id )
    where {$leaveYearStatusCondition} and la.employee_id= :empId 
        and
    (la.FISCAL_YEAR_MONTH_NO =
                  CASE
                    WHEN lms.is_monthly = 'Y'  THEN 
                    (SELECT LEAVE_YEAR_MONTH_NO FROM HRIS_LEAVE_MONTH_CODE
                    WHERE (
      select 
                       case when trunc(sysdate)>max(to_date) then
                        max(to_date)
                        else 
                        trunc(sysdate)
                        end
                        from HRIS_LEAVE_MONTH_CODE
                        ) BETWEEN FROM_DATE AND TO_DATE)
                  END
                OR la.FISCAL_YEAR_MONTH_NO IS NULL)
    {$leaveIdFilter}
    order by Lms.VIEW_ORDER asc";
        //    echo $sql; die;
        return $this->rawQuery($sql, $boundedParameter);
    }
}
