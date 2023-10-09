<?php

namespace ManagerService\Repository;

use Application\Model\Model;
use Application\Repository\RepositoryInterface;
use Zend\Db\Adapter\AdapterInterface;
use Application\Helper\EntityHelper;
use Application\Helper\Helper;


class ManagerReportRepo implements RepositoryInterface
{

  private $adapter;

  public function __construct(AdapterInterface $adapter)
  {
    $this->adapter = $adapter;
  }

  public function add(Model $model)
  {
  }

  public function delete($id)
  {
  }

  public function edit(Model $model, $id)
  {
  }

  public function fetchAll()
  {
  }

  public function fetchById($id)
  {
  }

  public function fetchAllEmployee($employeeId)
  {
    $sql = "SELECT RA.EMPLOYEE_ID, E.EMPLOYEE_CODE||'-'||E.FULL_NAME AS FULL_NAME
                FROM HRIS_RECOMMENDER_APPROVER  RA
                LEFT join HRIS_EMPLOYEES E ON (E.EMPLOYEE_ID=RA.EMPLOYEE_ID)
                  WHERE (RA.RECOMMEND_BY={$employeeId}
                  OR RA.APPROVED_BY    = {$employeeId})
                  AND E.STATUS = 'E'  AND (E.SERVICE_TYPE_ID IN (SELECT SERVICE_TYPE_ID FROM HRIS_SERVICE_TYPES WHERE TYPE NOT IN ('RESIGNED','RETIRED')) OR E.SERVICE_TYPE_ID IS NULL)
                AND E.RETIRED_FLAG = 'N' AND E.RESIGNED_FLAG = 'N'";
    $statement = $this->adapter->query($sql);
    $result = $statement->execute();

    $list = [];
    $list[-1] = 'All Employee';
    foreach ($result as $data) {
      $list[$data['EMPLOYEE_ID']] = $data['FULL_NAME'];
    }
    return $list;
  }

  public function attendanceReport($currentEmployeeId, $fromDate, $toDate, $employeeId, $status, $missPunchOnly = false)
  {
    $fromDateCondition = "";
    $toDateCondition = "";
    $employeeCondition = '';
    $statusCondition = '';
    $missPunchOnlyCondition = '';
    if ($fromDate != null) {
      $fromDateCondition = " AND A.ATTENDANCE_DT>=TO_DATE('" . $fromDate . "','DD-MM-YYYY') ";
    }
    if ($toDate != null) {
      $toDateCondition = " AND A.ATTENDANCE_DT<=TO_DATE('" . $toDate . "','DD-MM-YYYY') ";
    }
    if ($employeeId != null) {
      $employeeCondition = " AND A.EMPLOYEE_ID ={$employeeId} ";
      if ($employeeId == -1) {
        $employeeCondition = " AND (RA.RECOMMEND_BY=$currentEmployeeId OR RA.APPROVED_BY = $currentEmployeeId)";
      }
    }
    if ($status == "A") {
      $statusCondition = "AND A.OVERALL_STATUS = 'AB'";
    }

    if ($status == "H") {
      $statusCondition = "AND (A.OVERALL_STATUS = 'HD' OR A.OVERALL_STATUS = 'WH' ) ";
    }

    if ($status == "L") {
      $statusCondition = "AND (A.OVERALL_STATUS = 'LV' OR A.OVERALL_STATUS = 'LP' ) ";
    }

    if ($status == "P") {
      $statusCondition = "AND (A.OVERALL_STATUS = 'PR' OR A.OVERALL_STATUS = 'WD' OR A.OVERALL_STATUS = 'WH' OR A.OVERALL_STATUS = 'BA' OR A.OVERALL_STATUS = 'LA' OR A.OVERALL_STATUS = 'TP' OR A.OVERALL_STATUS = 'LP' OR A.OVERALL_STATUS = 'VP' ) ";
    }
    if ($status == "T") {
      $statusCondition = "AND (A.OVERALL_STATUS = 'TN' OR A.OVERALL_STATUS = 'TP' ) ";
    }
    if ($status == "TVL") {
      $statusCondition = "AND (A.OVERALL_STATUS = 'TV' OR A.OVERALL_STATUS = 'VP' ) ";
    }
    if ($status == "WOH") {
      $statusCondition = "AND A.OVERALL_STATUS = 'WH'";
    }
    if ($status == "WOD") {
      $statusCondition = "AND A.OVERALL_STATUS = 'WD'";
    }
    if ($status == "LI") {
      $statusCondition = "AND (A.LATE_STATUS = 'L' OR A.LATE_STATUS = 'B' OR A.LATE_STATUS ='Y') ";
    }
    if ($status == "EO") {
      $statusCondition = "AND (A.LATE_STATUS = 'E' OR A.LATE_STATUS = 'B' ) ";
    }

    if ($missPunchOnly) {
      $missPunchOnlyCondition = "AND (A.LATE_STATUS = 'X' OR A.LATE_STATUS = 'Y' ) ";
    }

    $sql = "
                SELECT A.ID                                        AS ID,
                  E.FULL_NAME                                      AS FULL_NAME,
                  A.EMPLOYEE_ID                                    AS EMPLOYEE_ID,
                  E.EMPLOYEE_CODE                                    AS EMPLOYEE_CODE,
                  INITCAP(TO_CHAR(A.ATTENDANCE_DT, 'DD-MON-YYYY')) AS ATTENDANCE_DT,
                  BS_DATE(TO_CHAR(A.ATTENDANCE_DT, 'DD-MON-YYYY')) AS ATTENDANCE_DT_N,
                  INITCAP(TO_CHAR(A.IN_TIME, 'HH:MI AM'))          AS IN_TIME,
                  INITCAP(TO_CHAR(A.OUT_TIME, 'HH:MI AM'))         AS OUT_TIME,
                  A.IN_REMARKS                                     AS IN_REMARKS,
                  A.OUT_REMARKS                                    AS OUT_REMARKS,
                  MIN_TO_HOUR(A.TOTAL_HOUR)                        AS TOTAL_HOUR,
                  A.LEAVE_ID                                       AS LEAVE_ID,
                  A.HOLIDAY_ID                                     AS HOLIDAY_ID,
                  A.TRAINING_ID                                    AS TRAINING_ID,
                  A.TRAVEL_ID                                      AS TRAVEL_ID,
                  A.SHIFT_ID                                       AS SHIFT_ID,
                  A.DAYOFF_FLAG                                    AS DAYOFF_FLAG,
                  A.LATE_STATUS                                    AS LATE_STATUS,
                  INITCAP(E.FIRST_NAME)                            AS FIRST_NAME,
                  INITCAP(E.MIDDLE_NAME)                           AS MIDDLE_NAME,
                  INITCAP(E.LAST_NAME)                             AS LAST_NAME,
                  H.HOLIDAY_ENAME                                  AS HOLIDAY_ENAME,
                  L.LEAVE_ENAME                                    AS LEAVE_ENAME,
                  T.TRAINING_NAME                                  AS TRAINING_NAME,
                  TVL.DESTINATION                                  AS TRAVEL_DESTINATION,
                  (
                  CASE
                    WHEN A.OVERALL_STATUS = 'DO'
                    THEN 'Day Off'
                    WHEN A.OVERALL_STATUS ='HD'
                    THEN 'On Holiday('
                      ||H.HOLIDAY_ENAME
                      ||')'
                    WHEN A.OVERALL_STATUS ='LV'
                    THEN 'On Leave('
                      ||L.LEAVE_ENAME
                      || ')'
                    WHEN A.OVERALL_STATUS ='TV'
                    THEN 'On Travel('
                      ||TVL.DESTINATION
                      ||')'
                    WHEN A.OVERALL_STATUS ='TN'
                    THEN 'On Training('
                      || (CASE WHEN A.TRAINING_TYPE = 'A' THEN T.TRAINING_NAME ELSE ETN.TITLE END)
                      ||')'
                    WHEN A.OVERALL_STATUS ='WD'
                    THEN 'Work On Dayoff'
                    WHEN A.OVERALL_STATUS ='WH'
                    THEN 'Work on Holiday('
                      ||H.HOLIDAY_ENAME
                      ||')'
                    WHEN A.OVERALL_STATUS ='LP'
                    THEN 'Work on Leave('
                      ||L.LEAVE_ENAME
                      ||')'
                    WHEN A.OVERALL_STATUS ='VP'
                    THEN 'Work on Travel('
                      ||TVL.DESTINATION
                      ||')'
                      ||LATE_STATUS_DESC(A.LATE_STATUS)
                    WHEN A.OVERALL_STATUS ='TP'
                    THEN 'Present('
                      ||T.TRAINING_NAME
                      ||')'
                      ||LATE_STATUS_DESC(A.LATE_STATUS)
                    WHEN A.OVERALL_STATUS ='PR'
                    THEN 'Present'
                      ||LATE_STATUS_DESC(A.LATE_STATUS)
                    WHEN A.OVERALL_STATUS ='AB'
                    THEN 'Absent'
                    WHEN A.OVERALL_STATUS ='BA'
                    THEN 'Present(Late In and Early Out)'
                    WHEN A.OVERALL_STATUS ='LA'
                    THEN 'Present(Third Day Late)'
                  END)AS STATUS,
                  SS.SHIFT_ENAME        AS SHIFT_NAME,
                  TO_CHAR(SS.START_TIME, 'HH:MI AM')   AS START_TIME,
                  TO_CHAR(SS.END_TIME, 'HH:MI AM')    AS END_TIME
                FROM HRIS_ATTENDANCE_DETAIL A
                LEFT JOIN HRIS_EMPLOYEES E
                ON A.EMPLOYEE_ID=E.EMPLOYEE_ID
                LEFT JOIN HRIS_HOLIDAY_MASTER_SETUP H
                ON A.HOLIDAY_ID=H.HOLIDAY_ID
                LEFT JOIN HRIS_LEAVE_MASTER_SETUP L
                ON A.LEAVE_ID=L.LEAVE_ID
                LEFT JOIN HRIS_TRAINING_MASTER_SETUP T
                ON (A.TRAINING_ID=T.TRAINING_ID AND A.TRAINING_TYPE='A')
                LEFT JOIN HRIS_EMPLOYEE_TRAINING_REQUEST ETN
                ON (ETN.REQUEST_ID=A.TRAINING_ID AND A.TRAINING_TYPE ='R')
                LEFT JOIN HRIS_EMPLOYEE_TRAVEL_REQUEST TVL
                ON A.TRAVEL_ID      =TVL.TRAVEL_ID
                LEFT JOIN HRIS_RECOMMENDER_APPROVER  RA
                ON RA.EMPLOYEE_ID=E.EMPLOYEE_ID
                LEFT JOIN HRIS_SHIFTS SS ON (A.SHIFT_ID=SS.SHIFT_ID)
                WHERE 1=1 AND E.STATUS='E'
                {$employeeCondition}
                {$fromDateCondition}
                {$toDateCondition}
                {$statusCondition}
                {$missPunchOnlyCondition}
                ORDER BY A.ATTENDANCE_DT DESC,A.IN_TIME ASC
                ";

    $statement = $this->adapter->query($sql);
    $result = $statement->execute();
    return $result;
  }
  public function newEmployeeDailyReport($searchQuery, $empId)
  {

    $employeeCondition = '';
    $fromDate = $searchQuery['fromDate'];
    $toDate = $searchQuery['toDate'];
    $monthDetail = $this->getMonthDetailsByDate($fromDate, $toDate);

    if ($searchQuery['employeeId'] == '' || $searchQuery['employeeId'][0] == -1) {
      $employeeCondition = " AND (RA.RECOMMEND_BY=$empId OR RA.APPROVED_BY = $empId or E.employee_id=$empId )";
      $searchQuery['employeeId'] = null;
    }

    $pivotString = '';
    for ($i = 1; $i <= $monthDetail['DAYS']; $i++) {
      if ($i != $monthDetail['DAYS']) {
        $pivotString .= $i . ' AS ' . 'D' . $i . ', ';
      } else {
        $pivotString .= $i . ' AS ' . 'D' . $i;
      }
    }

    $kendoDetails = $this->getMonthDetailsForKendo($fromDate, $toDate);

    $leaveDetails = $this->getLeaveList();
    $leavePivotString = $this->getLeaveCodePivot($leaveDetails);
    $leavePivotLeaveIdString = $this->getLeaveIdPivot($leaveDetails);
    $boundedParameter = [];
    $boundedParameter['fromDate'] = $fromDate;
    $boundedParameter['toDate'] = $toDate;
    $searchConditon = EntityHelper::getSearchConditon($searchQuery['companyId'], $searchQuery['branchId'], $searchQuery['departmentId'], $searchQuery['positionId'], $searchQuery['designationId'], $searchQuery['serviceTypeId'], $searchQuery['serviceEventTypeId'], $searchQuery['employeeTypeId'], $searchQuery['employeeId'], null, null, $searchQuery['functionalTypeId']);

    $sql = <<<EOT
    SELECT PL.*,MLD.*,
CL.PRESENT,
CL.ABSENT,
CL.LEAVE,
CL.DAYOFF,
CL.TRAVEL,
CL.TRAINING,
CL.HOLIDAY,
CL.EVENT_CONF,
CL.total_ot_req_days,
Cl.total_ot_req,
(CASE
WHEN cl.TOTAL_OT_HOURS IS NULL  THEN '0'
WHEN cl.TOTAL_OT_HOURS = 0 THEN '0'
WHEN cl.TOTAL_OT_HOURS<60 THEN '.'|| cl.TOTAL_OT_HOURS
ELSE
   TO_CHAR(TRUNC((cl.TOTAL_OT_HOURS) / 60), '990') || '.' ||
   LPAD(MOD((cl.TOTAL_OT_HOURS), 60), 2, '0')
END )AS TOTAL_OT_HOURS,
CL.OT_DAYS,
CL.WORK_DAYOFF,
CL.WORK_HOLIDAY,
Cl.MISSPUNCH_DAYS,
CL.EARLYOUT_DAYS,
(CASE
    WHEN cl.TOTAL_earlyOut_Minutes IS NULL THEN '0'
    WHEN cl.TOTAL_earlyOut_Minutes = 0 THEN '0'
    WHEN cl.TOTAL_earlyOut_Minutes < 60 THEN '.' || TO_CHAR(TRUNC(cl.TOTAL_earlyOut_Minutes))
    ELSE
        TO_CHAR(TRUNC(cl.TOTAL_earlyOut_Minutes / 60), '990') || '.' ||
        TO_CHAR(MOD(cl.TOTAL_earlyOut_Minutes, 60), 'FM00')
END) AS TOTAL_EARLYOUT,
CL.LATEIN_DAYS,
(CASE
    WHEN cl.TOTAL_lateIn_Minutes IS NULL THEN '0'
    WHEN cl.TOTAL_lateIn_Minutes = 0 THEN '0'
    WHEN cl.TOTAL_lateIn_Minutes < 60 THEN '.' || TO_CHAR(TRUNC(cl.TOTAL_lateIn_Minutes))
    ELSE
        TO_CHAR(TRUNC(cl.TOTAL_lateIn_Minutes / 60), '990') || '.' ||
        TO_CHAR(MOD(cl.TOTAL_lateIn_Minutes, 60), 'FM00')
END) AS TOTAL_LATEIN,
CL.total_hour_sum,
(CL.PRESENT+CL.ABSENT+CL.LEAVE+CL.DAYOFF+CL.HOLIDAY+CL.WORK_DAYOFF+CL.WORK_HOLIDAY+CL.TRAINING+CL.TRAVEL+CL.EVENT_CONF) AS TOTAL,
(CL.PRESENT+CL.LEAVE+CL.DAYOFF+CL.HOLIDAY+CL.WORK_DAYOFF+CL.WORK_HOLIDAY+CL.TRAINING+CL.TRAVEL+CL.EVENT_CONF) AS TOTAL_ATTD
FROM
(SELECT *
FROM
(SELECT E.FULL_NAME,
 AD.EMPLOYEE_ID,
 E.EMPLOYEE_CODE,
 C.COMPANY_NAME,
 D.DEPARTMENT_NAME,
 --   CASE
--  WHEN AD.OVERALL_STATUS IN ('TV','TN','PR','BA','LA','TP','LP','VP')
--  THEN 'PR'
--    WHEN AD.OVERALL_STATUS = 'LV' AND AD.HALFDAY_FLAG='N' THEN 'L'||'-'||LMS.LEAVE_CODE
--    WHEN AD.OVERALL_STATUS = 'LV' AND AD.HALFDAY_FLAG!='N' THEN 'HL'||'-'||LMS.LEAVE_CODE
--   ELSE AD.OVERALL_STATUS
--  END AS OVERALL_STATUS,
--( CASE
--WHEN ad.total_hour IS NULL and ad.overall_status in ('PR','LA') THEN '0'
--WHEN ad.total_hour IS NULL and ad.overall_status NOT in ('PR') THEN ad.overall_status
--ELSE EXTRACT(MINUTE FROM NUMTODSINTERVAL(ad.total_hour, 'SECOND')) || '.' || 
--EXTRACT(SECOND FROM NUMTODSINTERVAL(ad.total_hour, 'SECOND'))
--END) AS total_hour,
(CASE
    WHEN ad.total_hour IS NULL and ad.overall_status in ('PR','LA') THEN '0'
    WHEN ad.total_hour IS NULL  and ad.overall_status NOT in ('PR') THEN ad.overall_status
    WHEN ad.total_hour < 60 THEN '.' || TO_CHAR(TRUNC(ad.total_hour))
    ELSE
        TO_CHAR(TRUNC(ad.total_hour / 60), '990') || '.' ||
        TO_CHAR(MOD(ad.total_hour, 60), 'FM00')
END) AS total_hour,
 --AD.ATTENDANCE_DT,
 (AD.ATTENDANCE_DT-TO_DATE('{$fromDate}')+1) AS DAY_COUNT
FROM HRIS_ATTENDANCE_DETAIL AD
LEFT JOIN HRIS_LEAVE_MASTER_SETUP LMS ON (AD.LEAVE_ID=LMS.LEAVE_ID)
JOIN HRIS_EMPLOYEES E ON (E.EMPLOYEE_ID =AD.EMPLOYEE_ID)
LEFT JOIN HRIS_COMPANY C ON (C.COMPANY_ID=E.COMPANY_ID)
LEFT JOIN HRIS_DEPARTMENTS D ON (D.DEPARTMENT_ID=E.DEPARTMENT_ID)
LEFT JOIN HRIS_RECOMMENDER_APPROVER  RA ON (RA.EMPLOYEE_ID=E.EMPLOYEE_ID)
WHERE (AD.ATTENDANCE_DT BETWEEN TO_DATE('{$fromDate}') AND TO_DATE('{$toDate}') )
{$searchConditon} {$employeeCondition}
-- ) PIVOT (MAX (OVERALL_STATUS) FOR DAY_COUNT IN ({$pivotString})) 
) PIVOT (MAX (total_hour) FOR DAY_COUNT IN ({$pivotString})) 
) PL
LEFT JOIN
(SELECT AD.EMPLOYEE_ID,
COUNT(
CASE
 WHEN AD.OVERALL_STATUS IN ('PR','BA','LA','TP','VP')
 THEN 1
END) AS PRESENT,
COUNT(
CASE AD.OVERALL_STATUS
 WHEN 'AB'
 THEN 1
END) AS ABSENT,
COUNT(
CASE AD.OVERALL_STATUS
  WHEN 'TV'
  THEN 1
END) AS TRAVEL,
COUNT(
CASE AD.OVERALL_STATUS
  WHEN 'TN'
  THEN 1
END) AS TRAINING,
COUNT(
CASE AD.OVERALL_STATUS
 WHEN 'LV'
 THEN 1
 WHEN 'LP'
 THEN 1
END) AS LEAVE,
COUNT(
CASE AD.OVERALL_STATUS
 WHEN 'DO'
 THEN 1
END) AS DAYOFF,
COUNT(
CASE AD.OVERALL_STATUS
 WHEN 'HD'
 THEN 1
END) AS HOLIDAY,
COUNT(
            CASE OVERALL_STATUS
              WHEN 'EC'
              THEN 1
            END) AS EVENT_CONF,
SUM(
CASE
    WHEN AD.OT_MINUTES IS NOT NULL AND AD.OT_MINUTES > 0 THEN 1
    ELSE 0
END
) AS OT_DAYS,
SUM(CASE
    WHEN AD.OT_MINUTES IS NOT NULL AND AD.OT_MINUTES > 0 THEN AD.OT_MINUTES
    ELSE 0
END) AS TOTAL_OT_HOURS,
COUNT(CASE AD.OVERALL_STATUS
 WHEN 'WD'
 THEN 1
END) AS WORK_DAYOFF,
COUNT(
CASE
    WHEN AD.late_status in ('X','Y') THEN
        1
END
) AS MISSPUNCH_DAYS,
COUNT(
CASE
    WHEN AD.late_status in('E','B') THEN
        1
END
) AS EARLYOUT_DAYS,
COALESCE(
SUM(
  CASE
      WHEN ad.late_status IN ('E', 'B') AND 
      TO_DATE(TO_CHAR(ATTENDANCE_DT,'DD-MON-YYYY') ||' ' || TO_CHAR(hS.END_TIME -((1/1440)*NVL(hS.EARLY_OUT,0)),'HH:MI AM'),'DD-MON-YYYY HH:MI AM')>ad.out_time   THEN
          EXTRACT(HOUR FROM ( TO_DATE(TO_CHAR(ATTENDANCE_DT,'DD-MON-YYYY') ||' ' || TO_CHAR(hS.END_TIME -((1/1440)*NVL(hS.EARLY_OUT,0)),'HH:MI AM'),'DD-MON-YYYY HH:MI AM') -ad.out_time )) * 60 +
          EXTRACT(MINUTE FROM (TO_DATE(TO_CHAR(ATTENDANCE_DT,'DD-MON-YYYY') ||' ' || TO_CHAR(hS.END_TIME -((1/1440)*NVL(hS.EARLY_OUT,0)),'HH:MI AM'),'DD-MON-YYYY HH:MI AM')- ad.out_time) ) +
          EXTRACT(SECOND FROM (TO_DATE(TO_CHAR(ATTENDANCE_DT,'DD-MON-YYYY') ||' ' || TO_CHAR(hS.END_TIME -((1/1440)*NVL(hS.EARLY_OUT,0)),'HH:MI AM'),'DD-MON-YYYY HH:MI AM') -ad.out_time  )) / 60
      ELSE
          0
  END
),
0
) AS TOTAL_earlyOut_Minutes,
COUNT(
CASE
    WHEN AD.late_status in ('L','B','Y') THEN
        1
END
) AS LATEIN_DAYS,
COALESCE(
SUM(
  CASE
      WHEN ad.late_status IN ('L', 'B', 'Y') AND ad.in_time > (TO_DATE(TO_CHAR(ATTENDANCE_DT,'DD-MON-YYYY')||' ' ||TO_CHAR(hS.START_TIME+((1/1440)*NVL(hS.LATE_IN,0)),'HH:MI AM'),'DD-MON-YYYY HH:MI AM')) THEN
          EXTRACT(HOUR FROM (ad.in_time - TO_DATE(TO_CHAR(ATTENDANCE_DT,'DD-MON-YYYY')||' ' ||TO_CHAR(hS.START_TIME+((1/1440)*NVL(hS.LATE_IN,0)),'HH:MI AM'),'DD-MON-YYYY HH:MI AM') )) * 60 +
          EXTRACT(MINUTE FROM (ad.in_time - TO_DATE(TO_CHAR(ATTENDANCE_DT,'DD-MON-YYYY')||' ' ||TO_CHAR(hS.START_TIME+((1/1440)*NVL(hS.LATE_IN,0)),'HH:MI AM'),'DD-MON-YYYY HH:MI AM'))) +
          EXTRACT(SECOND FROM (ad.in_time - TO_DATE(TO_CHAR(ATTENDANCE_DT,'DD-MON-YYYY')||' ' ||TO_CHAR(hS.START_TIME+((1/1440)*NVL(hS.LATE_IN,0)),'HH:MI AM'),'DD-MON-YYYY HH:MI AM') )) / 60
      ELSE
          0
  END
),
0
) AS TOTAL_lateIn_Minutes,
COUNT(
CASE AD.OVERALL_STATUS
 WHEN 'WH'
 THEN 1
END) AS WORK_HOLIDAY,
(CASE
WHEN SUM(ad.total_hour) IS NULL THEN '0'
ELSE
   TO_CHAR(TRUNC(SUM(ad.total_hour) / 60), '990') || '.' ||
   LPAD(MOD(SUM(ad.total_hour), 60), 2, '0')
END )AS total_hour_sum,
(CASE
WHEN SUM(ot.total_hour) IS NULL THEN '0'
ELSE TO_CHAR(TRUNC(SUM(ot.total_hour) / 60), '990') || '.' ||
     LPAD(MOD(SUM(ot.total_hour), 60), 2, '0')
END ) AS total_ot_req,
COUNT(
  CASE ot.status
   WHEN 'AP'
   THEN 1
  END) AS total_ot_req_days
FROM
hris_attendance_detail ad
LEFT JOIN hris_shifts hs ON ad.shift_id = hs.shift_id
LEFT JOIN hris_overtime ot ON (ad.attendance_dt = ot.overtime_date AND ad.employee_id = ot.employee_id and ot.status='AP')
WHERE ad.ATTENDANCE_DT BETWEEN TO_DATE('{$monthDetail['FROM_DATE']}','DD-MON-YY') AND TO_DATE('{$monthDetail['TO_DATE']}','DD-MON-YY')
GROUP BY ad.EMPLOYEE_ID
)CL
ON (PL.EMPLOYEE_ID=CL.EMPLOYEE_ID)
LEFT JOIN
(
select 
*
from
(
  SELECT LA.employee_id,la.balance as LTBM,la.leave_id
            FROM (SELECT *
                        FROM
                        (select employee_id, previous_year_bal, leave_id, total, 
                       case when (Previous_year_bal+total-takenNew)<0 then 0 else  Previous_year_bal+total-takenNew end as balance,
                        encashed, takenNew as taken from 
                        (SELECT 
                        HA.EMPLOYEE_ID,
                                HA.PREVIOUS_YEAR_BAL,
                                HA.LEAVE_ID,
                                HA.TOTAL_DAYS AS TOTAL,
                                HA.BALANCE,
                                (select max(total_days) from hris_employee_leave_assign where employee_id =ha.employee_id and leave_id = HA.leave_id) - HA.TOTAL_DAYS  as leave_added,
                HA.TOTAL_DAYS as max_balance,
                                HS.ENCASH_DAYS as ENCASHED,
                                ( HA.PREVIOUS_YEAR_BAL + ha.total_days - ha.balance - (case when
                                HS.ENCASH_DAYS is null then 0 else HS.ENCASH_DAYS end)) AS taken,
								(select nvl(sum(case when half_day ='N' then no_of_days else no_of_days/2 end),0) from hris_Employee_leave_request where status = 'AP' and leave_id = HA.leave_id
and employee_id = ha.employee_id and end_date between 
(select start_date from hris_leave_years where leave_year_id = (select fiscal_year from hris_leave_master_setup where leave_id = HA.leave_id))
and TO_DATE('{$monthDetail['TO_DATE']}','DD-MON-YY')
)  - (case when
                                HS.ENCASH_DAYS is null then 0 else HS.ENCASH_DAYS end) as takenNew
                        FROM 
                        HRIS_EMPLOYEE_LEAVE_ASSIGN HA
                                left JOIN 
                                HRIS_EMP_SELF_LEAVE_CLOSING HS
                                on (HA.EMPLOYEE_ID = HS.EMPLOYEE_ID and HA.leave_id = HS.leave_id)
                        WHERE ha.EMPLOYEE_ID IN
                            ( SELECT E.EMPLOYEE_ID FROM HRIS_EMPLOYEES E WHERE 1=1 AND E.STATUS='E' 
                            ) and ha.leave_id in ($leavePivotLeaveIdString) )
                        ) 
                        ) LA LEFT JOIN HRIS_EMPLOYEES E ON (LA.EMPLOYEE_ID=E.EMPLOYEE_ID)
                        LEFT JOIN HRIS_DESIGNATIONS DES
                ON E.DESIGNATION_ID=DES.DESIGNATION_ID 
                LEFT JOIN HRIS_POSITIONS P
                ON E.POSITION_ID=P.POSITION_ID
                LEFT JOIN hris_departments d on d.department_id=e.department_id
                left join Hris_Functional_Types funt on funt.Functional_Type_Id=e.Functional_Type_Id
                left join Hris_Service_Types st on (st.service_type_id=E.Service_Type_Id)
)
PIVOT ( MAX (LTBM) FOR LEAVE_ID IN (
{$leavePivotString}
)
)
) MLD
ON (PL.EMPLOYEE_ID=MLD.EMPLOYEE_ID)  
order by case when PL.employee_id = $empId  then 1 else 2 end,PL.full_name
EOT;
    // echo '<pre>';
    // print_r($sql);
    // die;
    $statement = $this->adapter->query($sql);
    $result = $statement->execute();
    return ['leaveDetails' => $leaveDetails, 'monthDetail' => $monthDetail, 'kendoDetails' => $kendoDetails, 'data' => Helper::extractDbData($result)];
  }
  public function getLeaveIdPivot($leave)
  {
    $resultSize = sizeof($leave);

    $pivotString = '';
    for ($i = 0; $i < $resultSize; $i++) {
      if (($i + 1) < $resultSize) {
        $pivotString .= $leave[$i]['LEAVE_ID'] . ', ';
      } else {
        $pivotString .= $leave[$i]['LEAVE_ID'];
      }
    }
    return $pivotString;
  }
  public function getLeaveCodePivot($leave)
  {
    $resultSize = sizeof($leave);

    $pivotString = '';
    for ($i = 0; $i < $resultSize; $i++) {
      if (($i + 1) < $resultSize) {
        $pivotString .= $leave[$i]['PIVOT_STRING'] . ', ';
      } else {
        $pivotString .= $leave[$i]['PIVOT_STRING'];
      }
    }
    return $pivotString;
  }
  public function getMonthDetailsForKendo($fromDate, $toDate)
  {
    $boundedParam = [];
    $boundedParam['fromDate'] = $fromDate;
    $boundedParam['toDate'] = $toDate;
    $sql = "select 
  FDATES.*,
  CASE WHEN CV.CALENDER_VIEW='E'
  then
   TO_CHAR(TO_DATE(FDATES.DATES),'DD')
  else
   SUBSTR(NEPALI_DATE,-2)
  END AS COLUMN_NAME
  from 
          (SELECT  
          TO_CHAR(TO_DATE(:fromDate,'DD-MON-YYYY') + ROWNUM -1,'DD-MON-YYYY')  AS DATES,
          'F'||TO_CHAR((TO_DATE(:fromDate,'DD-MON-YYYY') + ROWNUM -1),'YYYYMMDD') AS FORMATE_DATE,
           BS_DATE(TO_DATE(:fromDate,'DD-MON-YYYY') + ROWNUM -1) as NEPALI_DATE,
           'D'||ROWNUM as KENDO_NAME
          FROM dual D
          CONNECT BY  rownum <=  TO_DATE(:toDate,'DD-MON-YYYY') -  TO_DATE(:fromDate,'DD-MON-YYYY') + 1 ) 
          FDATES
          LEFT JOIN (select VALUE AS CALENDER_VIEW from HRIS_PREFERENCES where upper(key)='CALENDAR_VIEW') CV ON (1=1)
          LEFT JOIN HRIS_MONTH_CODE MC ON (FDATES.DATES BETWEEN MC.FROM_DATE AND MC.TO_DATE)";


    $statement = $this->adapter->query($sql);
    $result = $statement->execute($boundedParam);
    return Helper::extractDbData($result);
  }
  public function getLeaveList()
  {
    $sql = "select 
                  leave_code,leave_id,leave_ename
                  ,'ML'||leave_id as  LEAVE_STRING
                  ,leave_id||' as '||'ML'||leave_id
                  as PIVOT_STRING
                  from 
                  hris_leave_master_setup where status='E'";
    $statement = $this->adapter->query($sql);
    $result = $statement->execute();
    return Helper::extractDbData($result);
  }
  public function getMonthDetailsByDate($fromDate, $toDate)
  {
    $boundedParam = [];
    $boundedParam['fromDate'] = $fromDate;
    $boundedParam['toDate'] = $toDate;
    $sql = "SELECT 
    TO_DATE(:fromDate,'DD-MON-YYYY') AS FROM_DATE,
    TO_DATE(:toDate,'DD-MON-YYYY') AS TO_DATE,
    TO_DATE(:toDate,'DD-MON-YYYY')-TO_DATE(:fromDate,'DD-MON-YYYY')+1 AS DAYS FROM 
    DUAL";

    $statement = $this->adapter->query($sql);
    $result = $statement->execute($boundedParam)->current();
    return $result;
  }
  public function getMonthDetails($monthId)
  {
    $sql = "SELECT 
      FROM_DATE,TO_DATE,TO_DATE-FROM_DATE+1 AS DAYS,month_edesc,year FROM 
      HRIS_MONTH_CODE WHERE MONTH_ID={$monthId}";

    $statement = $this->adapter->query($sql);
    $result = $statement->execute()->current();
    return $result;
  }
}
