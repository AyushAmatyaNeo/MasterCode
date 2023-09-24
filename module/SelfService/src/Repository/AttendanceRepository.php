<?php

namespace SelfService\Repository;

use Application\Helper\EntityHelper;
use Application\Model\Model;
use Application\Repository\RepositoryInterface;
use AttendanceManagement\Model\AttendanceDetail;
use Zend\Db\Adapter\AdapterInterface;
use Zend\Db\Sql\Expression;
use Zend\Db\Sql\Sql;
use Zend\Db\TableGateway\TableGateway;

class AttendanceRepository implements RepositoryInterface
{

    private $tableGateway;
    private $adapter;

    public function __construct(AdapterInterface $adapter)
    {
        $this->tableGateway = new TableGateway(AttendanceDetail::TABLE_NAME, $adapter);
        $this->adapter = $adapter;
    }

    public function add(Model $model)
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

    public function fetchByEmpId($id)
    {
        $sql = new Sql($this->adapter);
        $select = $sql->select();
        $select->columns([
            new Expression("INITCAP(TO_CHAR(A.ATTENDANCE_DT, 'DD-MON-YYYY')) AS ATTENDANCE_DT"),
            new Expression("INITCAP(TO_CHAR(A.IN_TIME, 'HH:MI AM')) AS IN_TIME"),
            new Expression("INITCAP(TO_CHAR(A.OUT_TIME, 'HH:MI AM')) AS OUT_TIME"),
            new Expression("E.EMPLOYEE_ID AS EMPLOYEE_ID"),
            new Expression("A.ID AS ID"),
            new Expression("A.IN_REMARKS AS IN_REMARKS"),
            new Expression("A.OUT_REMARKS AS OUT_REMARKS"),
            new Expression("A.TOTAL_HOUR AS TOTAL_HOUR")
        ], true);
        $select->from(['A' => AttendanceDetail::TABLE_NAME])
            ->join(['E' => 'HRIS_EMPLOYEES'], 'A.EMPLOYEE_ID=E.EMPLOYEE_ID', ["FIRST_NAME" => new Expression("INITCAP(E.FIRST_NAME)")], "left");
        $select->where(['A.EMPLOYEE_ID' => $id]);
        $select->order("A.ATTENDANCE_DT DESC");
        $statement = $sql->prepareStatementForSqlObject($select);
        $result = $statement->execute();
        return $result;
    }

    public function recordFilter($fromDate, $toDate, $employeeId, $status, $onlyMisPunch)
    {
        $sql = new Sql($this->adapter);
        $select = $sql->select();

        $select->columns(EntityHelper::getColumnNameArrayWithOracleFns(AttendanceDetail::class, NULL, [
            AttendanceDetail::ATTENDANCE_DT
        ], [
            AttendanceDetail::IN_TIME,
            AttendanceDetail::OUT_TIME
        ], NULL, NULL, 'A'), false);

        $select->from(['A' => AttendanceDetail::TABLE_NAME])
            ->join(['E' => 'HRIS_EMPLOYEES'], 'A.EMPLOYEE_ID=E.EMPLOYEE_ID', ["FIRST_NAME" => new Expression('INITCAP(E.FIRST_NAME)'), "MIDDLE_NAME" => new Expression('INITCAP(E.MIDDLE_NAME)'), "LAST_NAME" => new Expression('INITCAP(E.LAST_NAME)')], "left")
            ->join(['H' => 'HRIS_HOLIDAY_MASTER_SETUP'], 'A.HOLIDAY_ID=H.HOLIDAY_ID', ["HOLIDAY_ENAME" => 'HOLIDAY_ENAME'], "left")
            ->join(['L' => 'HRIS_LEAVE_MASTER_SETUP'], 'A.LEAVE_ID=L.LEAVE_ID', ["LEAVE_ENAME" => 'LEAVE_ENAME'], "left")
            ->join(['T' => 'HRIS_TRAINING_MASTER_SETUP'], 'A.TRAINING_ID=T.TRAINING_ID', ["TRAINING_NAME" => 'TRAINING_NAME'], "left")
            ->join(['TVL' => 'HRIS_EMPLOYEE_TRAVEL_REQUEST'], 'A.TRAVEL_ID=TVL.TRAVEL_ID', ["TRAVEL_DESTINATION" => 'DESTINATION'], "left");

        $select->from(['A' => AttendanceDetail::TABLE_NAME]);
        if ($fromDate != null) {
            $startDate = " AND A.ATTENDANCE_DT>=TO_DATE('" . $fromDate . "','DD-MM-YYYY')";
        } else {
            $startDate = "";
        }
        if ($toDate != null) {
            $endDate = " AND A.ATTENDANCE_DT<=TO_DATE('" . $toDate . "','DD-MM-YYYY')";
        } else {
            $endDate = "";
        }
        $select->where([
            'A.EMPLOYEE_ID=' . $employeeId .
                $startDate . $endDate
        ]);

        if ($status != "All") {
            if ($status == "A") {
                $select->where(["A.IN_TIME IS NULL AND A.OUT_TIME IS NULL AND A.TRAINING_ID IS NULL AND A.HOLIDAY_ID IS NULL AND A.LEAVE_ID IS NULL AND A.DAYOFF_FLAG='N'"]);
            }

            if ($status == "H") {
                $select->where(["A.IN_TIME IS NULL AND A.OUT_TIME IS NULL AND A.TRAINING_ID IS NULL AND A.HOLIDAY_ID IS NOT NULL AND A.LEAVE_ID IS NULL"]);
            }

            if ($status == "L") {
                $select->where(["A.IN_TIME IS NULL AND A.OUT_TIME IS NULL AND A.TRAINING_ID IS NULL AND A.HOLIDAY_ID IS NULL AND A.LEAVE_ID IS NOT NULL"]);
            }

            if ($status == "P") {
                $select->where(["A.IN_TIME IS NOT NULL"]);
            }
            if ($status == "T") {
                $select->where(["A.TRAINING_ID IS NOT NULL"]);
            }
            if ($status == "TVL") {
                $select->where(["A.TRAVEL_ID IS NOT NULL"]);
            }
            if ($status == "WOH") {
                $select->where(["A.HOLIDAY_ID IS NOT NULL AND A.IN_TIME IS NOT NULL "]);
            }
            if ($status == "LI") {
                $select->where(["(A.LATE_STATUS='L' OR A.LATE_STATUS='B')"]);
            }
            if ($status == "EO") {
                $select->where(["(A.LATE_STATUS='E' OR A.LATE_STATUS='B')"]);
            }
            if ($status == "WODO") {
                $select->where(["A.DAYOFF_FLAG = 'Y' AND A.IN_TIME IS NOT NULL "]);
            }
        }

        if ($onlyMisPunch != false) {
            $select->where(["(A.LATE_STATUS='X' OR A.LATE_STATUS='Y')"]);
        }


        $select->order("A.ATTENDANCE_DT DESC");
        $statement = $sql->prepareStatementForSqlObject($select);
        $result = $statement->execute();
        return $result;
    }

    public function attendanceReport($employeeId, $fromDate, $toDate, $status, $presentStatus)
    {
        $employeeCondition = " AND A.EMPLOYEE_ID = {$employeeId}";
        $fromDateCondition = "";
        $toDateCondition = "";
        $statusCondition = '';
        $presentStatusCondition = '';


        if ($fromDate != null) {
            $fromDateCondition = " AND A.ATTENDANCE_DT>=TO_DATE('" . $fromDate . "','DD-MM-YYYY') ";
        }
        if ($toDate != null) {
            $toDateCondition = " AND A.ATTENDANCE_DT<=TO_DATE('" . $toDate . "','DD-MM-YYYY') ";
        }

        $statusMap = [
            "A" => "'AB'",
            "H" => "'HD','WH'",
            "L" => "'LV','LP'",
            "P" => "'PR','WD','WH','BA','LA','TP','LP','VP','EC','OT'",
            "T" => "'TN','TP'",
            "TVL" => "'TV','VP'",
            "WOH" => "'WH'",
            "WOD" => "'WD'",
        ];

        if ($status != null) {
            if (gettype($status) === 'array') {
                $q = "";
                for ($i = 0; $i < sizeof($status); $i++) {
                    if ($i == 0) {
                        $q = $statusMap[$status[$i]];
                    } else {
                        $q .= "," . $statusMap[$status[$i]];
                    }
                }
                $statusCondition = "AND A.OVERALL_STATUS IN ({$q})";
            } else {
                $statusCondition = "AND A.OVERALL_STATUS IN ({$statusMap[$status]})";
            }
        }

        $presentStatusMap = [
            "LI" => "'L','B','Y'",
            "EO" => "'E','B'",
            "MP" => "'X','Y'",
        ];
        if ($presentStatus != null) {
            if (gettype($presentStatus) === 'array') {
                $q = "";
                for ($i = 0; $i < sizeof($presentStatus); $i++) {
                    if ($i == 0) {
                        $q = $presentStatusMap[$presentStatus[$i]];
                    } else {
                        $q .= "," . $presentStatusMap[$presentStatus[$i]];
                    }
                }
                $presentStatusCondition = "AND A.LATE_STATUS IN ({$q})";
            } else {
                $presentStatusCondition = "AND A.LATE_STATUS IN ({$presentStatusMap[$presentStatus]})";
            }
        }
        $sql = "
                SELECT A.ID                                        AS ID,
                  A.EMPLOYEE_ID                                    AS EMPLOYEE_ID,
                  INITCAP(TO_CHAR(A.ATTENDANCE_DT, 'DD-MON-YYYY')) AS ATTENDANCE_DT_AD,
                  BS_DATE(TO_CHAR(A.ATTENDANCE_DT, 'DD-MON-YYYY')) AS ATTENDANCE_DT_BS,
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
                  A.OVERALL_STATUS                                    AS OVERALL_STATUS,
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
                    THEN (CASE 
                        WHEN T.SHOW_AS_TRAINING = 'Y' 
                        THEN 'On Training ('
                        || (CASE
                          WHEN A.TRAINING_TYPE = 'A'
                          THEN T.TRAINING_NAME
                          ELSE ETN.TITLE
                            END)
                        ||')'
                    ELSE
                      (CASE
                          WHEN A.TRAINING_TYPE = 'A'
                          THEN T.TRAINING_NAME
                          ELSE ETN.TITLE
                        END)
                      END)
                      WHEN A.OVERALL_STATUS ='EC'
                    THEN (CASE 
                        WHEN EMS.SHOW_AS_EVENT = 'Y' 
                        THEN 'On Event ('
                        || (CASE
                          WHEN A.EVENT_TYPE = 'A'
                          THEN EMS.EVENT_NAME
                          ELSE EEN.TITLE
                            END)
                        ||')'
                    ELSE
                    'On Event (' ||(CASE
                    WHEN A.EVENT_TYPE = 'A'
                    THEN EMS.EVENT_NAME
                    ELSE EEN.TITLE
                  END)
                 ||')'
                      END)  
                      WHEN A.OVERALL_STATUS = 'OT'
                    THEN 'On Overtime'
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
                LEFT JOIN HRIS_EVENT_MASTER_SETUP EMS
                ON (A.EVENT_ID=EMS.EVENT_ID AND A.EVENT_TYPE='A')
                LEFT JOIN HRIS_EMPLOYEE_EVENT_REQUEST EEN
                ON (EEN.EVENT_ID=A.EVENT_ID AND A.EVENT_TYPE ='R')
                LEFT JOIN HRIS_EMPLOYEE_TRAVEL_REQUEST TVL
                ON A.TRAVEL_ID      =TVL.TRAVEL_ID
                LEFT JOIN HRIS_SHIFTS SS ON (A.SHIFT_ID=SS.SHIFT_ID)
                WHERE 1=1
                {$employeeCondition}
                {$fromDateCondition}
                {$toDateCondition}
                {$statusCondition}
                {$presentStatusCondition}
                ORDER BY A.ATTENDANCE_DT DESC
                ";
        return EntityHelper::rawQueryResult($this->adapter, $sql);
    }

    public function delete($id)
    {
    }

    public function getCurrentNeplaiMonthStartDateEndDate()
    {
        $sql = "SELECT FISCAL_YEAR_ID,
                  MONTH_ID,
                  MONTH_EDESC,
                  INITCAP((TO_CHAR(FROM_DATE,'DD-MON-YYYY'))) AS FROM_DATE,
                  INITCAP((TO_CHAR(TO_DATE,'DD-MON-YYYY')))   AS TO_DATE
                FROM HRIS_MONTH_CODE
                WHERE TRUNC(SYSDATE) BETWEEN FROM_DATE AND TO_DATE";
        $statement = $this->adapter->query($sql);
        $result = $statement->execute();
        return $result->current();
    }
}
