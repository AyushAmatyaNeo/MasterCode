<?php

namespace Application\Repository;

use Application\Helper\Helper;
use Zend\Authentication\AuthenticationService;
use Zend\Db\Adapter\AdapterInterface;

class DashboardRepository {

    private $adapter;
    private $fiscalYr;

    public function __construct(AdapterInterface $adapter) {
        $this->adapter = $adapter;
        $auth = new AuthenticationService();
        $this->fiscalYr = $auth->getStorage()->read()['fiscal_year'];
    }

    /*
     * EMPLOYEE DASHBOARD FUNCTIONS
     */

    public function fetchEmployeeDashboardDetail($employeeId, $startDate, $endDate) {
        $sql = "
            SELECT EMPLOYEE_TBL.*,
              LATE_ATTEN_TBL.\"'L'\"+LATE_ATTEN_TBL.\"'B'\"+LATE_ATTEN_TBL.\"'Y'\" LATE_IN,
              LATE_ATTEN_TBL.\"'E'\"+LATE_ATTEN_TBL.\"'B'\" EARLY_OUT,
              LATE_ATTEN_TBL.\"'X'\"+LATE_ATTEN_TBL.\"'Y'\" MISSED_PUNCH,
              ATTEN_TBL.\"'PR'\"    +ATTEN_TBL.\"'WD'\"+ATTEN_TBL.\"'WH'\"+ ATTEN_TBL.\"'TP'\"+ ATTEN_TBL.\"'LP'\"+ATTEN_TBL.\"'VP'\" PRESENT_DAY,
              ATTEN_TBL.\"'AB'\"    +ATTEN_TBL.\"'BA'\"+ATTEN_TBL.\"'LA'\" ABSENT_DAY,
              ATTEN_TBL.\"'LV'\" LEAVE,
              ATTEN_TBL.\"'WH'\" WOH,
              ATTEN_TBL.\"'TV'\" TOUR,
              ATTEN_TBL.\"'TN'\"+ATTEN_TBL.\"'TP'\" TRAINING
            FROM
              (SELECT EMP.EMPLOYEE_ID,
                EMP.FULL_NAME,
                EMP.GENDER_ID,
                EMP.COMPANY_ID,
                EMP.BRANCH_ID,
                EMP.EMAIL_OFFICIAL,
                EMP.EMAIL_PERSONAL,
                TO_CHAR(EMP.JOIN_DATE, 'DD-MON-YYYY') JOIN_DATE,
                TRUNC(MONTHS_BETWEEN(SYSDATE, EMP.JOIN_DATE) / 12)                                        AS SERVICE_YEARS,
                TRUNC(MOD(MONTHS_BETWEEN(SYSDATE, EMP.JOIN_DATE), 12))                                    AS SERVICE_MONTHS,
                TRUNC(SYSDATE) - ADD_MONTHS(EMP.JOIN_DATE, TRUNC(MONTHS_BETWEEN(SYSDATE, EMP.JOIN_DATE))) AS SERVICE_DAYS,
                DSG.DESIGNATION_TITLE,
                EFL.FILE_PATH
              FROM HRIS_EMPLOYEES EMP,
                HRIS_DESIGNATIONS DSG,
                HRIS_EMPLOYEE_FILE EFL
              WHERE EMP.DESIGNATION_ID   = DSG.DESIGNATION_ID(+)
              AND EMP.PROFILE_PICTURE_ID = EFL.FILE_CODE(+)
              AND EMP.RETIRED_FLAG       = 'N'
              AND EMP.EMPLOYEE_ID        = {$employeeId}
              ) EMPLOYEE_TBL
            LEFT JOIN
              (SELECT *
              FROM
                (SELECT ad.EMPLOYEE_ID,
case when  ad.OVERALL_STATUS ='TN' and tms.SHOW_AS_TRAINING='N'
then
'CU'
else
ad.OVERALL_STATUS
end as OVERALL_STATUS
FROM HRIS_ATTENDANCE_DETAIL ad
                left join HRIS_TRAINING_MASTER_SETUP tms on(ad.training_id=tms.training_id)
                WHERE (ad.ATTENDANCE_DT BETWEEN TO_DATE('{$startDate}', 'DD-MON-YYYY') AND TO_DATE('{$endDate}', 'DD-MON-YYYY'))
                ) PIVOT (COUNT(OVERALL_STATUS) FOR OVERALL_STATUS IN ('DO','HD','LV','TV','TN','PR','AB','WD','WH','BA','LA','TP','LP','VP','CU'))
              ) ATTEN_TBL
            ON (EMPLOYEE_TBL.EMPLOYEE_ID = ATTEN_TBL.EMPLOYEE_ID)
            LEFT JOIN
              (SELECT *
              FROM
                (SELECT EMPLOYEE_ID,
                  LATE_STATUS
                FROM HRIS_ATTENDANCE_DETAIL
                WHERE (ATTENDANCE_DT BETWEEN TO_DATE('{$startDate}', 'DD-MON-YYYY') AND TO_DATE('{$endDate}', 'DD-MON-YYYY'))
                ) PIVOT (COUNT(LATE_STATUS) FOR LATE_STATUS IN ('L','E','B','N','X','Y'))
              ) LATE_ATTEN_TBL
            ON (EMPLOYEE_TBL.EMPLOYEE_ID = LATE_ATTEN_TBL.EMPLOYEE_ID)
          ";
            //echo $sql; die;
        $statement = $this->adapter->query($sql);
        $result = $statement->execute()->current();
        return $result;
    }

    public function fetchUpcomingHolidays($employeeId = null) {
        if ($employeeId == null) {
            $sql = "SELECT HM.HOLIDAY_ID,
                  HM.HOLIDAY_ENAME,
                  TO_CHAR(HM.START_DATE,'Day, fmddth Month') START_DATE,
                  TO_CHAR(HM.END_DATE,'Day, fmddth Month') END_DATE,
                  HM.HALFDAY,
                  TO_CHAR(HM.START_DATE, 'DAY') WEEK_DAY,
                  HM.START_DATE - TRUNC(SYSDATE) DAYS_REMAINING
                FROM HRIS_HOLIDAY_MASTER_SETUP HM
                WHERE  HM.START_DATE > TRUNC(SYSDATE) ORDER BY HM.START_DATE";
        } else {
            $sql = "SELECT HM.HOLIDAY_ID,
                  HM.HOLIDAY_ENAME,
                  TO_CHAR(HM.START_DATE,'Day, fmddth Month') START_DATE,
                  TO_CHAR(HM.END_DATE,'Day, fmddth Month') END_DATE,
                  HM.HALFDAY,
                  TO_CHAR(HM.START_DATE, 'DAY') WEEK_DAY,
                  HM.START_DATE - TRUNC(SYSDATE) DAYS_REMAINING
                FROM HRIS_HOLIDAY_MASTER_SETUP HM
                JOIN HRIS_EMPLOYEE_HOLIDAY EH
                ON (HM.HOLIDAY_ID   =EH.HOLIDAY_ID)
                WHERE EH.EMPLOYEE_ID={$employeeId} AND HM.START_DATE > TRUNC(SYSDATE) ORDER BY HM.START_DATE";
        }


        $statement = $this->adapter->query($sql);
        $result = $statement->execute();

        return $result;
    }

    public function fetchEmployeeNotice($employeeId = null) {
        $where = "";
        if ($employeeId != null) {
            $where = "AND {$employeeId} IN (SELECT EMPLOYEE_ID FROM HRIS_NEWS_EMPLOYEE WHERE NEWS_ID = N.NEWS_ID)";
        }
        $sql = "SELECT N.NEWS_ID,
                      N.NEWS_DATE,
                      TO_CHAR(N.NEWS_DATE, 'DD') NEWS_DAY,
                      TO_CHAR(N.NEWS_DATE, 'Mon YYYY') NEWS_MONTH_YEAR,
                      N.NEWS_TITLE,
                      N.NEWS_EDESC
                    FROM HRIS_NEWS N   
                    WHERE ( TRUNC(SYSDATE) BETWEEN TRUNC(NEWS_DATE) AND TRUNC(NEWS_EXPIRY_DT) ) 
                    AND N.STATUS = 'E'
                    {$where}
                    ";
        $statement = $this->adapter->query($sql);
        $result = $statement->execute();

        return $result;
    }

    public function fetchEmployeeTask($employeeId) {
        $sql = "SELECT TSK.TASK_ID,
                        ( CASE
                          WHEN EMP.MIDDLE_NAME IS NULL THEN EMP.FIRST_NAME || ' ' || EMP.LAST_NAME
                          ELSE EMP.FIRST_NAME || ' ' || EMP.MIDDLE_NAME || ' ' || EMP.LAST_NAME
                        END ) FULL_NAME, 
                        DSG.DESIGNATION_TITLE,
                        TSK.TASK_EDESC,
                        TSK.END_DATE,
                        TSK.STATUS
                    FROM HRIS_TASK TSK, HRIS_EMPLOYEES EMP, HRIS_DESIGNATIONS DSG
                    WHERE 1 = 1
                        AND TSK.EMPLOYEE_ID = EMP.EMPLOYEE_ID
                        AND EMP.DESIGNATION_ID = DSG.DESIGNATION_ID
                        AND TSK.EMPLOYEE_ID = {$employeeId}
                        AND (TSK.END_DATE> TRUNC(SYSDATE) OR TSK.STATUS = 'O')";

        $statement = $this->adapter->query($sql);
        $result = $statement->execute();

        return Helper::extractDbData($result);
    }

    public function fetchEmployeesBirthday() {
        $sql = "
                SELECT EMP.*,
                  DSG.DESIGNATION_TITLE,
                  EFL.FILE_PATH,
                  B.BRANCH_NAME,
                  P.POSITION_NAME
                FROM
                  (SELECT EMP.EMPLOYEE_ID,
                    EMP.FULL_NAME,
                    EMP.BIRTH_DATE,
                    TO_CHAR(EMP.BIRTH_DATE, 'fmddth Month') EMP_BIRTH_DATE,
                    EMP.PROFILE_PICTURE_ID,
                    EMP.BRANCH_ID,
                    EMP.POSITION_ID,
                    EMP.DESIGNATION_ID,
                    'TODAY' BIRTHDAYFOR
                  FROM HRIS_EMPLOYEES EMP
                  WHERE TO_CHAR(EMP.BIRTH_DATE, 'MMDD') = TO_CHAR(SYSDATE,'MMDD')
                  AND EMP.RETIRED_FLAG                  = 'N'
                  AND EMP.STATUS                        = 'E'
                  UNION ALL
                  SELECT EMP.EMPLOYEE_ID,
                    EMP. FULL_NAME,
                    EMP.BIRTH_DATE,
                    TO_CHAR(EMP.BIRTH_DATE, 'fmddth Month') EMP_BIRTH_DATE,
                    EMP.PROFILE_PICTURE_ID,
                    EMP.BRANCH_ID,
                    EMP.POSITION_ID,
                    EMP.DESIGNATION_ID,
                    'UPCOMING' BIRTHDAYFOR
                  FROM HRIS_EMPLOYEES EMP
                  WHERE TO_CHAR(EMP.BIRTH_DATE, 'MMDD') > TO_CHAR(SYSDATE,'MMDD')
                  AND EMP.RETIRED_FLAG                  = 'N'
                  AND EMP.STATUS                        = 'E'
                  ) EMP,
                  HRIS_DESIGNATIONS DSG,
                  HRIS_EMPLOYEE_FILE EFL,
                  HRIS_BRANCHES B,
                  HRIS_POSITIONS P
                WHERE EMP.DESIGNATION_ID   = DSG.DESIGNATION_ID(+)
                AND EMP.PROFILE_PICTURE_ID = EFL.FILE_CODE(+)
                AND EMP.BRANCH_ID          = B.BRANCH_ID(+)
                AND EMP.POSITION_ID        =P.POSITION_ID(+)
                ORDER BY TO_CHAR(EMP.BIRTH_DATE,'MMDD')
                ";

        $statement = $this->adapter->query($sql);
        $result = $statement->execute();

        $birthdayResult = array();
        foreach ($result as $rs) {
            if ('TODAY' == strtoupper($rs['BIRTHDAYFOR'])) {
                $birthdayResult['TODAY'][$rs['EMPLOYEE_ID']] = $rs;
            }
            if ('UPCOMING' == strtoupper($rs['BIRTHDAYFOR'])) {
                $birthdayResult['UPCOMING'][$rs['EMPLOYEE_ID']] = $rs;
            }
        }

        return $birthdayResult;
    }

    public function fetchEmployeeCalendarData($employeeId, $startDate, $endDate) {
        $sql = "SELECT TO_CHAR(ATN.ATTENDANCE_DT, 'YYYY-MM-DD') MONTH_DAY,
                  ATN.EMPLOYEE_ID,
                  TO_CHAR(ATN.ATTENDANCE_DT, 'YYYY-MM-DD') ATTENDANCE_DT,
                  TO_CHAR(ATN.IN_TIME, 'HH24:MI') IN_TIME,
                  case when ATN.IN_TIME is null or ATN.OUT_TIME is null or TO_CHAR(ATN.IN_TIME, 'YYYY-MM-DD') = TO_CHAR(ATN.OUT_TIME, 'YYYY-MM-DD')
                  then  TO_CHAR(ATN.OUT_TIME, 'HH24:MI') else 
                  substr(numtodsinterval(ATN.TOTAL_HOUR, 'minute'),12,5)  end OUT_TIME,
                  (
                  CASE
                    WHEN ATN.OVERALL_STATUS = 'DO'
                    THEN 'Day Off'
                    WHEN ATN.OVERALL_STATUS ='HD'
                    THEN 'On Holiday ('
                      ||HMS.HOLIDAY_ENAME
                      ||')'
                    WHEN ATN.OVERALL_STATUS ='LV'
                    THEN 'On Leave ('
                      ||LMS.LEAVE_ENAME
                      || ')'
                    WHEN ATN.OVERALL_STATUS ='TV'
                    THEN 'On Travel ('
                      ||ETR.DESTINATION
                      ||')'
                    WHEN ATN.OVERALL_STATUS ='TN'
                    THEN (CASE 
                    WHEN TMS.SHOW_AS_TRAINING = 'Y' 
                        THEN 'On Training ('
                        || (CASE
                          WHEN ATN.TRAINING_TYPE = 'A'
                          THEN TMS.TRAINING_NAME
                          ELSE ETN.TITLE
                            END)
                        ||')'
                    ELSE
                       (CASE
                          WHEN ATN.TRAINING_TYPE = 'A'
                          THEN TMS.TRAINING_NAME
                          ELSE ETN.TITLE
                        END)
                      END)  
                    WHEN ATN.OVERALL_STATUS ='WD'
                    THEN 'Work On Dayoff'
                    WHEN ATN.OVERALL_STATUS ='WH'
                    THEN 'Work on Holiday ('
                      ||HMS.HOLIDAY_ENAME
                      ||')'
                    WHEN ATN.OVERALL_STATUS ='LP'
                    THEN 'On Partial Leave ('
                      ||LMS.LEAVE_ENAME
                      ||') '
                      ||LATE_STATUS_DESC(ATN.LATE_STATUS) 
                    WHEN ATN.OVERALL_STATUS ='VP'
                    THEN 'Work on Travel ('
                      ||ETR.DESTINATION
                      ||')'
                      ||LATE_STATUS_DESC(ATN.LATE_STATUS)
                    WHEN ATN.OVERALL_STATUS ='TP'
                    THEN 'Present ('
                      || TMS.TRAINING_NAME
                      ||')'
                      ||LATE_STATUS_DESC(ATN.LATE_STATUS)
                    WHEN ATN.OVERALL_STATUS ='PR'
                    THEN 'Present '
                      ||LATE_STATUS_DESC(ATN.LATE_STATUS)
                    WHEN ATN.OVERALL_STATUS ='AB'
                    THEN 'Absent'
                    WHEN ATN.OVERALL_STATUS ='BA'
                    THEN 'Present(Late In and Early Out)'
                    WHEN ATN.OVERALL_STATUS ='LA'
                    THEN 'Present(Late Penalty)'
                  END)AS ATTENDANCE_STATUS,
                  ATN.OVERALL_STATUS
                FROM HRIS_ATTENDANCE_DETAIL ATN
                LEFT JOIN HRIS_LEAVE_MASTER_SETUP LMS
                ON LMS.LEAVE_ID = ATN.LEAVE_ID
                LEFT JOIN HRIS_HOLIDAY_MASTER_SETUP HMS
                ON HMS.HOLIDAY_ID = ATN.HOLIDAY_ID
                LEFT JOIN HRIS_TRAINING_MASTER_SETUP TMS
                ON (TMS.TRAINING_ID = ATN.TRAINING_ID AND ATN.TRAINING_TYPE='A')
                LEFT JOIN HRIS_EMPLOYEE_TRAINING_REQUEST ETN
                ON (ETN.REQUEST_ID=ATN.TRAINING_ID AND ATN.TRAINING_TYPE ='R')
                LEFT JOIN HRIS_EMPLOYEE_TRAVEL_REQUEST ETR
                ON ETR.TRAVEL_ID = ATN.TRAVEL_ID
                WHERE 1          = 1
                AND (ATN.ATTENDANCE_DT BETWEEN TO_DATE('{$startDate}','YYYY-MM-DD') AND TO_DATE('{$endDate}','YYYY-MM-DD') )
                AND ATN.EMPLOYEE_ID = {$employeeId}
                ORDER BY ATN.ATTENDANCE_DT ASC";
        $statement = $this->adapter->query($sql);
        $result = $statement->execute();

        return Helper::extractDbData($result);
    }

    public function fetchUpcomingLeaves($employeeId) {
        $sql = "
            SELECT L.LEAVE_ENAME,
             TO_CHAR( EL.START_DATE, 'DD-MON-YYYY') AS START_DATE,
             TO_CHAR( EL.END_DATE,'DD-MON-YYYY' )   AS END_DATE,
             TRUNC(END_DATE)- TRUNC(START_DATE) AS DIFF
            FROM HRIS_EMPLOYEE_LEAVE_REQUEST EL
            JOIN HRIS_LEAVE_MASTER_SETUP L
            ON(EL.LEAVE_ID     = L.LEAVE_ID)
            WHERE EL.STATUS     ='AP'
            AND TRUNC(SYSDATE) < EL.START_DATE
            AND EL.EMPLOYEE_ID ={$employeeId}
            ";
        $statement = $this->adapter->query($sql);
        $result = $statement->execute();

        return Helper::extractDbData($result);
    }

    /*
     * END FOR EMPLOYEE DASHBOARD FUNCTIONS
     */

    /*
     * ADMIN DASHBOARD FUNCTIONS
     */

    public function fetchAdminDashboardDetail($employeeId, $date) {
        $sql = "
            SELECT EMPLOYEE_TBL.*,
              LATE_ATTEN_TBL.\"'L'\"+LATE_ATTEN_TBL.\"'B'\"+LATE_ATTEN_TBL.\"'Y'\" LATE_IN,
              LATE_ATTEN_TBL.\"'E'\"+LATE_ATTEN_TBL.\"'B'\" EARLY_OUT,
              LATE_ATTEN_TBL.\"'X'\"+LATE_ATTEN_TBL.\"'Y'\" MISSED_PUNCH,
              ATTEN_TBL.\"'PR'\"    +ATTEN_TBL.\"'WD'\"+ATTEN_TBL.\"'WH'\"+ ATTEN_TBL.\"'TP'\"+ ATTEN_TBL.\"'LP'\"+ATTEN_TBL.\"'VP'\" PRESENT_DAY,
              ATTEN_TBL.\"'AB'\"    +ATTEN_TBL.\"'BA'\"+ATTEN_TBL.\"'LA'\" ABSENT_DAY,
              ATTEN_TBL.\"'LV'\" LEAVE,
              ATTEN_TBL.\"'WH'\" WOH,
              ATTEN_TBL.\"'TV'\" TOUR,
              ATTEN_TBL.\"'TN'\"+ATTEN_TBL.\"'TP'\" TRAINING
            FROM
              (SELECT EMP.EMPLOYEE_ID,
                EMP.FULL_NAME,
                EMP.GENDER_ID,
                EMP.COMPANY_ID,
                EMP.BRANCH_ID,
                EMP.EMAIL_OFFICIAL,
                EMP.EMAIL_PERSONAL,
                TO_CHAR(EMP.JOIN_DATE, 'DD-MON-YYYY') JOIN_DATE,
                TRUNC(MONTHS_BETWEEN(SYSDATE, EMP.JOIN_DATE) / 12)                                        AS SERVICE_YEARS,
                TRUNC(MOD(MONTHS_BETWEEN(SYSDATE, EMP.JOIN_DATE), 12))                                    AS SERVICE_MONTHS,
                TRUNC(SYSDATE) - ADD_MONTHS(EMP.JOIN_DATE, TRUNC(MONTHS_BETWEEN(SYSDATE, EMP.JOIN_DATE))) AS SERVICE_DAYS,
                DSG.DESIGNATION_TITLE,
                EFL.FILE_PATH
              FROM HRIS_EMPLOYEES EMP,
                HRIS_DESIGNATIONS DSG,
                HRIS_EMPLOYEE_FILE EFL
              WHERE EMP.DESIGNATION_ID   = DSG.DESIGNATION_ID(+)
              AND EMP.PROFILE_PICTURE_ID = EFL.FILE_CODE(+)
              AND EMP.RETIRED_FLAG       = 'N'
              AND EMP.EMPLOYEE_ID        = {$employeeId}
              ) EMPLOYEE_TBL,
              (SELECT *
              FROM
                (SELECT OVERALL_STATUS
                FROM HRIS_ATTENDANCE_DETAIL
                WHERE (ATTENDANCE_DT                               = TRUNC(SYSDATE) )
                ) PIVOT (COUNT(OVERALL_STATUS) FOR OVERALL_STATUS IN ('DO','HD','LV','TV','TN','PR','AB','WD','WH','BA','LA','TP','LP','VP'))
              ) ATTEN_TBL,
              (SELECT *
              FROM
                (SELECT LATE_STATUS
                FROM HRIS_ATTENDANCE_DETAIL
                WHERE (ATTENDANCE_DT                         = TRUNC(SYSDATE-1))
                ) PIVOT (COUNT(LATE_STATUS) FOR LATE_STATUS                IN ('L','E','B','N','X','Y'))
              ) LATE_ATTEN_TBL   
           
";
        $statement = $this->adapter->query($sql);
        $result = $statement->execute()->current();
        return $result;
    }

    public function fetchAllEmployee($employeeId = null) {
        $sql = "SELECT EMP.EMPLOYEE_ID,
                  EMP.EMPLOYEE_CODE,
                  EMP.FIRST_NAME,
                  EMP.MIDDLE_NAME,
                  EMP.LAST_NAME,
                  EMP.FULL_NAME,
                  EMP.DESIGNATION_ID,
                  DSG.DESIGNATION_TITLE,
                  EMP.DEPARTMENT_ID,
                  DPT.DEPARTMENT_NAME
                FROM HRIS_EMPLOYEES EMP 
                JOIN HRIS_DESIGNATIONS DSG ON (EMP.DESIGNATION_ID = DSG.DESIGNATION_ID)
                JOIN HRIS_DEPARTMENTS DPT ON (EMP.DEPARTMENT_ID = DPT.DEPARTMENT_ID)
                LEFT JOIN HRIS_RECOMMENDER_APPROVER RA ON (EMP.EMPLOYEE_ID=RA.EMPLOYEE_ID)
                WHERE 1 = 1
                AND EMP.STATUS = 'E'
                AND EMP.RETIRED_FLAG = 'N'";

        if ($employeeId != null) {
            $sql .= " AND (RECOMMEND_BY=$employeeId OR APPROVED_BY = $employeeId)";
        }

        $sql .= " AND EMP.IS_ADMIN='N'
                ORDER BY UPPER(EMP.FULL_NAME)";

        $statement = $this->adapter->query($sql);
        $result = $statement->execute();

        return $result;
    }

    public function fetchGenderHeadCount() {
        $sql = "SELECT COUNT (*) HEAD_COUNT, HE.GENDER_ID, HG.GENDER_NAME
                    FROM HRIS_EMPLOYEES HE, HRIS_GENDERS HG
                   WHERE HE.GENDER_ID(+) = HG.GENDER_ID
                     AND HG.STATUS = 'E'
                     AND HE.RETIRED_FLAG = 'N'
                     --AND HE.COMPANY_ID = :V_COMPANY_ID
                GROUP BY HE.GENDER_ID, HG.GENDER_NAME
                ORDER BY UPPER(HG.GENDER_NAME)";

        $statement = $this->adapter->query($sql);
        $result = $statement->execute();

        return $result;
    }

    public function fetchDepartmentHeadCount() {
        $sql = "SELECT COUNT (*) HEAD_COUNT, HD.DEPARTMENT_ID , HD.DEPARTMENT_NAME
                    FROM HRIS_EMPLOYEES HE, HRIS_DEPARTMENTS HD
                   WHERE HE.DEPARTMENT_ID(+) = HD.DEPARTMENT_ID
                   AND HD.STATUS = 'E'
                   AND HE.RETIRED_FLAG = 'N'
                   --AND HE.COMPANY_ID = :V_COMPANY_ID
                GROUP BY HD.DEPARTMENT_ID, HD.DEPARTMENT_NAME
                ORDER BY UPPER(HD.DEPARTMENT_NAME)";

        $statement = $this->adapter->query($sql);
        $result = $statement->execute();

        return $result;
    }

    public function fetchLocationHeadCount() {
        $sql = "SELECT COUNT (*) HEAD_COUNT, HB.BRANCH_ID , HB.BRANCH_NAME
                    FROM HRIS_EMPLOYEES HE, HRIS_BRANCHES HB
                   WHERE HE.BRANCH_ID(+) = HB.BRANCH_ID
                   AND HB.STATUS = 'E'
                   AND HE.RETIRED_FLAG = 'N'
                   --AND HE.COMPANY_ID = :V_COMPANY_ID
                GROUP BY HB.BRANCH_ID, HB.BRANCH_NAME
                ORDER BY UPPER(HB.BRANCH_NAME)";

        $statement = $this->adapter->query($sql);
        $result = $statement->execute();

        return $result;
    }

    public function fetchDepartmentAttendance() {
        $sql = "SELECT * FROM (
                    SELECT HD.DEPARTMENT_CODE,
                           HD.DEPARTMENT_NAME,
                           'PRESENT' AS PRESENT_STATUS,
                           COUNT(*) ATTN_COUNT
                    FROM HRIS_DEPARTMENTS HD,
                         HRIS_ATTENDANCE_DETAIL HAD,
                         HRIS_EMPLOYEES HE
                    WHERE HD.DEPARTMENT_ID = HE.DEPARTMENT_ID
                      AND HE.EMPLOYEE_ID = HAD.EMPLOYEE_ID
                      AND TRUNC(HAD.ATTENDANCE_DT) = TRUNC(SYSDATE)
                      AND HAD.IN_TIME IS NOT NULL
                    GROUP BY HD.DEPARTMENT_CODE,
                             HD.DEPARTMENT_NAME,
                             'PRESENT'
                    UNION ALL
                    SELECT HD.DEPARTMENT_CODE,
                           HD.DEPARTMENT_NAME,
                           'ABSENT' AS PRESENT_STATUS,
                           COUNT(*) ATTN_COUNT
                    FROM HRIS_DEPARTMENTS HD,
                         HRIS_ATTENDANCE_DETAIL HAD,
                         HRIS_EMPLOYEES HE
                    WHERE HD.DEPARTMENT_ID = HE.DEPARTMENT_ID
                      AND HE.EMPLOYEE_ID = HAD.EMPLOYEE_ID
                      AND TRUNC(HAD.ATTENDANCE_DT) = TRUNC(SYSDATE)
                      AND HAD.IN_TIME IS NULL
                      AND HAD.OUT_TIME IS NULL
                      AND LEAVE_ID IS NULL
                      AND TRAINING_ID IS NULL
                      AND TRAVEL_ID IS NULL
                      AND HOLIDAY_ID IS NULL
                      AND HE.STATUS = 'E'
                      AND HE.IS_ADMIN= 'N'
                    GROUP BY HD.DEPARTMENT_CODE,
                             HD.DEPARTMENT_NAME,
                             'ABSENT'
                )
                ORDER BY UPPER(DEPARTMENT_NAME)";

        $statement = $this->adapter->query($sql);
        $result = $statement->execute();

        return $result;
    }

    public function fetchEmployeeContracts() {
        $sql = "
                SELECT EMP.EMPLOYEE_ID,
                  EMP.FULL_NAME,
                  EF.FILE_PATH,
                  D.DESIGNATION_TITLE,
                  S.END_DATE,
                  S.TYPE
                FROM HRIS_EMPLOYEES EMP
                JOIN
                  (SELECT JH.EMPLOYEE_ID,
                    TO_CHAR(JH.START_DATE,'DD-MON-YYYY') AS START_DATE,
                    TO_CHAR(JH.END_DATE,'DD-MON-YYYY') AS END_DATE,
                    JH.TO_DEPARTMENT_ID,
                    JH.TO_DESIGNATION_ID,
                    (
                    CASE
                      WHEN TRUNC(SYSDATE) > (select max(jh.end_date) from hris_job_history where employee_id = jh.employee_id)
                      THEN 'EXPIRED'
                      ELSE 'EXPIRING'
                    END ) AS TYPE
                  FROM HRIS_JOB_HISTORY JH,
                    (SELECT EMPLOYEE_ID,
                      MAX(START_DATE) AS LATEST_START_DATE
                    FROM HRIS_JOB_HISTORY
                    WHERE END_DATE IS NOT NULL
                    AND ABS(TRUNC(END_DATE)-TRUNC(SYSDATE))<=15
                    AND employee_id NOT IN (
                      SELECT
                          employee_id
                      FROM
                          hris_job_history
                      WHERE
                          service_event_type_id = 18 and end_date > trunc(sysdate) 
                  )
                    GROUP BY EMPLOYEE_ID
                    ) LH
                  WHERE JH.EMPLOYEE_ID =LH.EMPLOYEE_ID
                  AND JH.START_DATE    = LH.LATEST_START_DATE
                  ) S
                ON (EMP.EMPLOYEE_ID=S.EMPLOYEE_ID)
                LEFT JOIN HRIS_EMPLOYEE_FILE EF
                ON (EMP.PROFILE_PICTURE_ID=EF.FILE_CODE)
                LEFT JOIN HRIS_DESIGNATIONS D
                ON (S.TO_DESIGNATION_ID=D.DESIGNATION_ID )
                ORDER BY S.END_DATE ASC
                ";


        $statement = $this->adapter->query($sql);
        $result = $statement->execute();

        $employeeContract = array();
        foreach ($result as $rs) {
            if ('EXPIRED' == strtoupper($rs['TYPE'])) {
                $employeeContract['EXPIRED'][$rs['EMPLOYEE_ID']] = $rs;
            }
            if ('EXPIRING' == strtoupper($rs['TYPE'])) {
                $employeeContract['EXPIRING'][$rs['EMPLOYEE_ID']] = $rs;
            }
        }

        return $employeeContract;
    }

    public function fetchJoinedEmployees() {
        $sql = "
                SELECT E.FULL_NAME,
                  EF.FILE_PATH,
                  B.BRANCH_NAME,
                  P.POSITION_NAME,
                  D.DESIGNATION_TITLE,
                  E.JOIN_DATE
                FROM HRIS_EMPLOYEES E
                LEFT JOIN HRIS_EMPLOYEE_FILE EF
                ON (E.PROFILE_PICTURE_ID=EF.FILE_CODE)
                LEFT JOIN HRIS_DESIGNATIONS D
                ON (E.DESIGNATION_ID=D.DESIGNATION_ID )
                LEFT JOIN HRIS_BRANCHES B
                ON (E.BRANCH_ID=B.BRANCH_ID)
                LEFT JOIN HRIS_POSITIONS P
                ON (E.POSITION_ID=P.POSITION_ID),
                  (SELECT *
                  FROM HRIS_MONTH_CODE
                  WHERE TRUNC(SYSDATE) BETWEEN FROM_DATE AND TO_DATE
                  ) M
                WHERE E.JOIN_DATE BETWEEN M.FROM_DATE AND M.TO_DATE
                    ";
        $statement = $this->adapter->query($sql);
        $result = $statement->execute();
        return $result;
    }

    public function fetchLeftEmployees() {
        $sql = "
                SELECT E.FULL_NAME,
                  EF.FILE_PATH,
                  B.BRANCH_NAME,
                  P.POSITION_NAME,
                  D.DESIGNATION_TITLE,
                  R.EXIT_DATE,
                  E.JOIN_DATE
                FROM HRIS_EMPLOYEES E
                LEFT JOIN HRIS_EMPLOYEE_FILE EF
                ON (E.PROFILE_PICTURE_ID=EF.FILE_CODE)
                LEFT JOIN HRIS_DESIGNATIONS D
                ON (E.DESIGNATION_ID=D.DESIGNATION_ID )
                LEFT JOIN HRIS_BRANCHES B
                ON (E.BRANCH_ID=B.BRANCH_ID)
                LEFT JOIN HRIS_POSITIONS P
                ON (E.POSITION_ID=P.POSITION_ID),
                  (SELECT JH.EMPLOYEE_ID,
                    JH.START_DATE AS EXIT_DATE
                  FROM HRIS_JOB_HISTORY JH,
                    (SELECT *
                    FROM HRIS_MONTH_CODE
                    WHERE TRUNC(SYSDATE) BETWEEN FROM_DATE AND TO_DATE
                    ) M
                  WHERE (JH.START_DATE BETWEEN M.FROM_DATE AND M.TO_DATE)
                  AND JH.SERVICE_EVENT_TYPE_ID IN (14,5,8)
                  ) R
                WHERE E.EMPLOYEE_ID= R.EMPLOYEE_ID
";

        $statement = $this->adapter->query($sql);
        $result = $statement->execute();
        return $result;
    }

    /*
     * END FOR ADMIN DASHBOARD FUNCTIONS
     */

    /*
     * MANAGER DASHBOARD FUNCTIONS
     */

    public function fetchManagerDashboardDetail($employeeId, $date) {
        $sql = "
                SELECT EMPLOYEE_TBL.*,
                  NVL(JOINED_THIS_MONTH_TBL.JOINED_THIS_MONTH, 0) JOINED_THIS_MONTH
                FROM
                  (SELECT EMP.EMPLOYEE_ID,
                    EMP.FULL_NAME,
                    EMP.GENDER_ID,
                    EMP.COMPANY_ID,
                    EMP.BRANCH_ID,
                    EMP.EMAIL_OFFICIAL,
                    EMP.EMAIL_PERSONAL,
                    TO_CHAR(EMP.JOIN_DATE, 'DD-MON-YYYY') JOIN_DATE,
                    TRUNC(MONTHS_BETWEEN(SYSDATE, EMP.JOIN_DATE) / 12)                                        AS SERVICE_YEARS,
                    TRUNC(MOD(MONTHS_BETWEEN(SYSDATE, EMP.JOIN_DATE), 12))                                    AS SERVICE_MONTHS,
                    TRUNC(SYSDATE) - ADD_MONTHS(EMP.JOIN_DATE, TRUNC(MONTHS_BETWEEN(SYSDATE, EMP.JOIN_DATE))) AS SERVICE_DAYS,
                    DSG.DESIGNATION_TITLE,
                    EFL.FILE_PATH
                  FROM HRIS_EMPLOYEES EMP,
                    HRIS_DESIGNATIONS DSG,
                    HRIS_EMPLOYEE_FILE EFL
                  WHERE EMP.DESIGNATION_ID   = DSG.DESIGNATION_ID(+)
                  AND EMP.PROFILE_PICTURE_ID = EFL.FILE_CODE(+)
                  AND EMP.RETIRED_FLAG       = 'N'
                    -- AND EMP.COMPANY_ID = 2
                  AND EMP.EMPLOYEE_ID = {$employeeId}
                  ) EMPLOYEE_TBL
                  -- JOINED THIS MONTH
                LEFT JOIN
                  (SELECT EMPLOYEE_ID,
                    COUNT (*) JOINED_THIS_MONTH
                  FROM HRIS_EMPLOYEES
                  WHERE TO_CHAR(JOIN_DATE, 'YYYYMM') = TO_CHAR(SYSDATE, 'YYYYMM')
                  AND STATUS                         = 'E'
                  GROUP BY EMPLOYEE_ID
                  ) JOINED_THIS_MONTH_TBL
                ON JOINED_THIS_MONTH_TBL.EMPLOYEE_ID = EMPLOYEE_TBL.EMPLOYEE_ID               
";
        $statement = $this->adapter->query($sql);
        $result = $statement->execute()->current();
        return $result;
    }

    public function fetchManagerAttendanceDetail($employeeId) {
        $sql = "
            SELECT 
              LATE_ATTEN_TBL.\"'L'\"+LATE_ATTEN_TBL.\"'B'\"+LATE_ATTEN_TBL.\"'Y'\" LATE_IN,
              LATE_ATTEN_TBL.\"'E'\"+LATE_ATTEN_TBL.\"'B'\" EARLY_OUT,
              LATE_ATTEN_TBL.\"'X'\"+LATE_ATTEN_TBL.\"'Y'\" MISSED_PUNCH,
              ATTEN_TBL.\"'PR'\"    +ATTEN_TBL.\"'WD'\"+ATTEN_TBL.\"'WH'\"+ ATTEN_TBL.\"'TP'\"+ ATTEN_TBL.\"'LP'\"+ATTEN_TBL.\"'VP'\" PRESENT_DAY,
              ATTEN_TBL.\"'AB'\"    +ATTEN_TBL.\"'BA'\"+ATTEN_TBL.\"'LA'\" ABSENT_DAY,
              ATTEN_TBL.\"'LV'\" LEAVE,
              ATTEN_TBL.\"'WH'\" WOH,
              ATTEN_TBL.\"'TV'\" TOUR,
              ATTEN_TBL.\"'TN'\"+ATTEN_TBL.\"'TP'\" TRAINING
            FROM
              (SELECT *
              FROM
                (SELECT OVERALL_STATUS
                FROM HRIS_ATTENDANCE_DETAIL
                WHERE (ATTENDANCE_DT                               = TRUNC(SYSDATE) 
                AND EMPLOYEE_ID                    IN
                  (SELECT EMPLOYEE_ID
                  FROM HRIS_RECOMMENDER_APPROVER
                  WHERE RECOMMEND_BY={$employeeId}
                  OR APPROVED_BY    = {$employeeId}
                  ) )
                ) PIVOT (COUNT(OVERALL_STATUS) FOR OVERALL_STATUS IN ('DO','HD','LV','TV','TN','PR','AB','WD','WH','BA','LA','TP','LP','VP'))
              ) ATTEN_TBL,
              (SELECT *
              FROM
                (SELECT LATE_STATUS
                FROM HRIS_ATTENDANCE_DETAIL
                WHERE (ATTENDANCE_DT                         = TRUNC(SYSDATE-1) 
                AND EMPLOYEE_ID                    IN
                  (SELECT EMPLOYEE_ID
                  FROM HRIS_RECOMMENDER_APPROVER
                  WHERE RECOMMEND_BY={$employeeId}
                  OR APPROVED_BY    = {$employeeId}
                  ))
                ) PIVOT (COUNT(LATE_STATUS) FOR LATE_STATUS                IN ('L','E','B','N','X','Y'))
              ) LATE_ATTEN_TBL   
           
";
        $statement = $this->adapter->query($sql);
        $result = $statement->execute()->current();
        return $result;
    }

    /*
     * END FOR MANAGER DASHBOARD FUNCTIONS
     */

    public function fetchAllNews($employeeId) {
        $sql = "select * from HRIS_NEWS_TYPE WHERE STATUS='E' ORDER BY NEWS_TYPE_ID";
        $statement = $this->adapter->query($sql);
        $newsTypeResult = $statement->execute();

        $returnData = [];
        foreach ($newsTypeResult as $data) {
            $tempNewsData = $this->allNewsTypeWise($data['NEWS_TYPE_ID'], $employeeId);
            $data['news'] = $tempNewsData;
            array_push($returnData, $data);
        }

        return $returnData;
    }

    public function allNewsTypeWise($typeId, $employeeId) {
        $sql = "SELECT N.NEWS_ID,
                    N.NEWS_DATE,
                    N.NEWS_TYPE,
                    N.NEWS_TITLE,
                    N.NEWS_EXPIRY_DT,
                    N.STATUS
                  FROM HRIS_NEWS N
                  WHERE N.STATUS     ='E'
                  AND N.NEWS_TYPE    ={$typeId}
                  AND {$employeeId} IN
                    (SELECT NE.EMPLOYEE_ID FROM HRIS_NEWS_EMPLOYEE NE WHERE NE.NEWS_ID=N.NEWS_ID
                    )
                AND (TRUNC(SYSDATE) BETWEEN N.NEWS_DATE AND N.NEWS_EXPIRY_DT)
                ORDER BY N.NEWS_DATE DESC";
        $statement = $this->adapter->query($sql);
        $result = $statement->execute();
        return Helper::extractDbData($result);
    }
    
    
    public function empOnLeaveToday($companyId){
        $sql="SELECT E.FULL_NAME,LMS.LEAVE_ENAME,LR.START_DATE,LR.END_DATE,LR.ID,B.BRANCH_NAME
FROM HRIS_ATTENDANCE_DETAIL AD 
LEFT JOIN HRIS_EMPLOYEES E ON (E.EMPLOYEE_ID=AD.EMPLOYEE_ID)
LEFT JOIN HRIS_LEAVE_MASTER_SETUP LMS ON (LMS.LEAVE_ID=AD.LEAVE_ID)
LEFT JOIN HRIS_EMPLOYEE_LEAVE_REQUEST LR ON (LR.EMPLOYEE_ID=AD.EMPLOYEE_ID AND TRUNC(SYSDATE) BETWEEN LR.START_DATE AND LR.END_DATE )
LEFT JOIN HRIS_BRANCHES B ON (B.BRANCH_ID=E.BRANCH_ID)
WHERE AD.ATTENDANCE_DT=TRUNC(SYSDATE)
AND AD.OVERALL_STATUS='LV'
--AND E.COMPANY_ID={$companyId}";
        $statement = $this->adapter->query($sql);
        $result = $statement->execute();
        return Helper::extractDbData($result);
    }
    
    public function empOnTravelToday($companyId){
        $sql="SELECT E.FULL_NAME,TR.PURPOSE,TR.DESTINATION,TR.FROM_DATE,TR.TO_DATE,B.BRANCH_NAME
 --,FULL_NAME||TR.PURPOSE||TR.DESTINATION||TR.FROM_DATE||TR.TO_DATE AS TRAVEL_DETAIL
FROM HRIS_ATTENDANCE_DETAIL AD 
LEFT JOIN HRIS_EMPLOYEES E ON (E.EMPLOYEE_ID=AD.EMPLOYEE_ID)
LEFT JOIN HRIS_EMPLOYEE_TRAVEL_REQUEST TR ON (TR.TRAVEL_ID=AD.TRAVEL_ID)
LEFT JOIN HRIS_BRANCHES B ON (B.BRANCH_ID=E.BRANCH_ID)
WHERE AD.ATTENDANCE_DT=TRUNC(SYSDATE)
AND AD.OVERALL_STATUS='TV' 
--AND E.COMPANY_ID={$companyId}";
        $statement = $this->adapter->query($sql);
        $result = $statement->execute();
        return Helper::extractDbData($result);
    }
    

}
