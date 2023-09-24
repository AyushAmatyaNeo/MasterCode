<?php

namespace AttendanceManagement\Repository;

use Application\Helper\EntityHelper;
use Application\Helper\Helper;
use Application\Model\Model;
use Application\Repository\RepositoryInterface;
use AttendanceManagement\Model\Attendance;
use AttendanceManagement\Model\AttendanceDetail;
use Setup\Model\HrEmployees;
use Zend\Db\Adapter\AdapterInterface;
use Zend\Db\Sql\Expression;
use Zend\Db\Sql\Sql;
use Zend\Db\TableGateway\TableGateway;

class AttendanceDetailRepository implements RepositoryInterface
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
        $this->tableGateway->insert($model->getArrayCopyForDB());
    }

    public function edit(Model $model, $id)
    {
        $array = $model->getArrayCopyForDB();
        $this->tableGateway->update($array, [AttendanceDetail::ID => $id]);
    }

    public function editWith(Model $model, $where)
    {
        $array = $model->getArrayCopyForDB();
        $this->tableGateway->update($array, $where);
    }

    public function fetchAll()
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
            ->join(['E' => 'HRIS_EMPLOYEES'], 'A.EMPLOYEE_ID=E.EMPLOYEE_ID', ["FIRST_NAME" => new Expression('INITCAP(E.FIRST_NAME)'), "MIDDLE_NAME" => new Expression('INITCAP(E.MIDDLE_NAME)'), "LAST_NAME" => new Expression('INITCAP(E.LAST_NAME)')], "left");
        $select->where(["E.STATUS='E'"]);
        $select->where(["E.RETIRED_FLAG='N'"]);
        $select->order("E.FIRST_NAME,A.ATTENDANCE_DT DESC");
        $statement = $sql->prepareStatementForSqlObject($select);
        $result = $statement->execute();
        return $result;
    }

    public function filterRecord($companyId, $branchId, $departmentId, $designationId, $positionId, $serviceTypeId, $serviceEventTypeId, $employeeTypeId, $genderId, $functionalTypeId, $employeeId, $fromDate = null, $toDate = null, $status = null, $presentStatus = null, $min = null, $max = null, $presentType = null)
    {
        $boundedParams = [];
        $searchConditon = EntityHelper::getSearchConditonBounded($companyId, $branchId, $departmentId, $positionId, $designationId, $serviceTypeId, $serviceEventTypeId, $employeeTypeId, $employeeId, $genderId, null, $functionalTypeId);
        $boundedParams = array_merge($boundedParams, $searchConditon['parameter']);
        $fromDateCondition = "";
        $toDateCondition = "";
        $statusCondition = '';
        $presentStatusCondition = '';
        $presentTypeCondition = "";
        $rowNums = '';
        if ($fromDate != null) {
            $fromDateCondition = " AND A.ATTENDANCE_DT >= :fromDate";
            $boundedParams['fromDate'] = $fromDate;
        }
        if ($toDate != null) {
            $toDateCondition = " AND A.ATTENDANCE_DT <= :toDate";
            $boundedParams['toDate'] = $toDate;
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

        if ($min != null && $max != null) {
            $rowNums = "WHERE (Q.R BETWEEN {$min} AND {$max})";
        }

        if ($presentType == "P") {
            $presentTypeCondition = " AND A.IN_TIME IS NOT NULL AND A.OUT_TIME IS NULL ";
        }
        $orderByString = EntityHelper::getOrderBy('A.ATTENDANCE_DT DESC ,A.IN_TIME ASC', 'A.ATTENDANCE_DT DESC ,A.IN_TIME ASC', 'E.SENIORITY_LEVEL', 'P.LEVEL_NO', 'E.JOIN_DATE', 'DES.ORDER_NO', 'E.FULL_NAME');
        $sql = "
               SELECT ROWNUM AS SN,Q.* FROM (SELECT 
                  ROWNUM                                           AS R,
                  A.ID                                             AS ID,
                  A.EMPLOYEE_ID                                    AS EMPLOYEE_ID,
                  E.EMPLOYEE_CODE                                    AS EMPLOYEE_CODE,
                  INITCAP(TO_CHAR(A.ATTENDANCE_DT, 'DD-MON-YYYY')) AS ATTENDANCE_DT,
                  BS_DATE(TO_CHAR(A.ATTENDANCE_DT, 'DD-MON-YYYY')) AS ATTENDANCE_DT_N,
                  INITCAP(TO_CHAR(A.IN_TIME, 'HH:MI AM'))          AS IN_TIME,
                  INITCAP(TO_CHAR(A.OUT_TIME, 'HH:MI AM'))         AS OUT_TIME,
                  A.IN_REMARKS                                     AS IN_REMARKS,
                  A.OUT_REMARKS                                    AS OUT_REMARKS,
				  MIN_TO_HOUR(A.OT_MINUTES)						   AS OT_HOUR, 
                  MIN_TO_HOUR(A.TOTAL_HOUR)                        AS TOTAL_HOUR,
                  A.LEAVE_ID                                       AS LEAVE_ID,
                  A.HOLIDAY_ID                                     AS HOLIDAY_ID,
                  A.TRAINING_ID                                    AS TRAINING_ID,
                  A.TRAVEL_ID                                      AS TRAVEL_ID,
                  A.SHIFT_ID                                       AS SHIFT_ID,
                  A.DAYOFF_FLAG                                    AS DAYOFF_FLAG,
                  A.LATE_STATUS                                    AS LATE_STATUS,
                  COM.COMPANY_NAME                                 AS COMPANY_NAME,
                  HRIS_GET_BRANCH_JH(A.EMPLOYEE_ID,A.ATTENDANCE_DT,BR.BRANCH_ID)
                   AS BRANCH_NAME, 
                  DEP.DEPARTMENT_NAME                              AS DEPARTMENT_NAME,
                  INITCAP(E.FULL_NAME)                             AS EMPLOYEE_NAME,
                  H.HOLIDAY_ENAME                                  AS HOLIDAY_ENAME,
                  L.LEAVE_ENAME                                    AS LEAVE_ENAME,
                  T.TRAINING_NAME                                  AS TRAINING_NAME,
                  TVL.DESTINATION                                  AS TRAVEL_DESTINATION,
                  P.POSITION_NAME                                  AS POSITION_NAME,
                  BP.PROVINCE_NAME                                 AS BRANCH_PROVINCE,
                  (
                  CASE
                    WHEN A.OVERALL_STATUS = 'DO'
                    THEN 'Day Off'
                    
                    WHEN A.OVERALL_STATUS ='HD'
                    THEN 'On Holiday ('
                      ||H.HOLIDAY_ENAME
                      ||')'
                    WHEN A.OVERALL_STATUS ='LV'
                    THEN 'On Leave ('
                      ||L.LEAVE_ENAME
                      || ')'
                    WHEN A.OVERALL_STATUS ='TV'
                    THEN 'On Travel ('
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
                      WHEN A.OVERALL_STATUS ='TV'
                    THEN 'On Travel ('
                      ||TVL.DESTINATION
                      ||')'
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
                    THEN 'Work on Holiday ('
                      ||H.HOLIDAY_ENAME
                      ||')'
                    WHEN A.OVERALL_STATUS ='LP'
                    THEN 'On Partial Leave ('
                      ||L.LEAVE_ENAME
                      ||') '
                      ||LATE_STATUS_DESC(A.LATE_STATUS) 
                    WHEN A.OVERALL_STATUS ='VP'
                    THEN 'Work on Travel ('
                      ||TVL.DESTINATION
                      ||')'
                      ||LATE_STATUS_DESC(A.LATE_STATUS)
                    WHEN A.OVERALL_STATUS ='TP'
                    THEN 'Present ('
                      ||T.TRAINING_NAME
                      ||')'
                      ||LATE_STATUS_DESC(A.LATE_STATUS)
                    WHEN A.OVERALL_STATUS ='PR'
                    THEN 'Present '
                      ||LATE_STATUS_DESC(A.LATE_STATUS)
                    WHEN A.OVERALL_STATUS ='AB'
                    THEN 'Absent'
                    WHEN A.OVERALL_STATUS ='BA'
                    THEN 'Present(Late In and Early Out)'
                    WHEN A.OVERALL_STATUS ='LA'
                    THEN 'Present(Late Penalty)'
                  END) AS STATUS,
                   S.SHIFT_ENAME,
                  TO_CHAR(S.START_TIME,'HH:MI AM') AS START_TIME,
                  TO_CHAR(S.END_TIME,'HH:MI AM')   AS END_TIME,
                   CASE WHEN A.OT_MINUTES>0
                   THEN 
                   MIN_TO_HOUR(A.OT_MINUTES)
                   ELSE ''
                   END
                   AS SYSTEM_OVERTIME,
                  CASE WHEN A.OM.OVERTIME_HOUR is not null
                   THEN 
                  MIN_TO_HOUR(A.OM.OVERTIME_HOUR*60)
                   ELSE ''
                   END AS MANUAL_OVERTIME,
               FUNT.FUNCTIONAL_TYPE_EDESC                                        AS FUNCTIONAL_TYPE_EDESC
                FROM HRIS_ATTENDANCE_DETAIL A
                LEFT JOIN HRIS_EMPLOYEES E
                ON A.EMPLOYEE_ID=E.EMPLOYEE_ID
                LEFT JOIN HRIS_COMPANY COM
                ON E.COMPANY_ID=COM.COMPANY_ID
                LEFT JOIN HRIS_DEPARTMENTS DEP
                ON E.DEPARTMENT_ID = DEP.DEPARTMENT_ID
                LEFT JOIN HRIS_BRANCHES BR
                ON E.BRANCH_ID = BR.BRANCH_ID
                LEFT JOIN HRIS_POSITIONS P
                ON E.POSITION_ID=P.POSITION_ID
                LEFT JOIN HRIS_DESIGNATIONS DES
                ON E.DESIGNATION_ID=DES.DESIGNATION_ID
                LEFT JOIN HRIS_FUNCTIONAL_TYPES FUNT
                ON E.FUNCTIONAL_TYPE_ID=FUNT.FUNCTIONAL_TYPE_ID
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
                LEFT JOIN HRIS_SHIFTS S
                ON A.SHIFT_ID=S.SHIFT_ID
                LEFT JOIN  HRIS_OVERTIME_MANUAL OM
                ON (OM.ATTENDANCE_DATE=A.ATTENDANCE_DT AND OM.EMPLOYEE_ID=A.EMPLOYEE_ID)
                LEFT JOIN HRIS_PROVINCES BP on (BP.PROVINCE_ID=BR.PROVINCE_ID)
                WHERE 1=1 {$presentTypeCondition} 
                {$searchConditon['sql']}
                {$fromDateCondition}
                {$toDateCondition}
                {$statusCondition}
                {$presentStatusCondition}
                {$orderByString}
                ) Q
                {$rowNums}
                ";
        $statement = $this->adapter->query($sql);
        $result = $statement->execute($boundedParams);
        return Helper::extractDbData($result);
    }


    public function filterRecordCount($employeeId = null, $branchId = null, $departmentId = null, $positionId = null, $designationId = null, $serviceTypeId = null, $serviceEventTypeId = null, $fromDate = null, $toDate = null, $status = null, $companyId = null, $employeeTypeId = null, $widOvertime = false, $missPunchOnly = false)
    {
        $fromDateCondition = "";
        $toDateCondition = "";
        $employeeCondition = '';
        $branchCondition = '';
        $companyCondition = '';
        $departmentCondition = '';
        $positionCondition = '';
        $designationCondition = '';
        $serviceTypeCondition = '';
        $serviceEventtypeCondition = '';
        $employeeTypeCondition = '';
        $statusCondition = '';
        $missPunchOnlyCondition = '';
        if ($fromDate != null) {
            $fromDateCondition = " AND A.ATTENDANCE_DT>=TO_DATE('" . $fromDate . "','DD-MM-YYYY') ";
        }
        if ($toDate != null) {
            $toDateCondition = " AND A.ATTENDANCE_DT<=TO_DATE('" . $toDate . "','DD-MM-YYYY') ";
        }
        if ($employeeId != null && $employeeId != -1) {
            $employeeCondition = " AND A.EMPLOYEE_ID ={$employeeId} ";
        }
        if ($companyId != null && $companyId != -1) {
            $companyCondition = " AND E.COMPANY_ID ={$companyId} ";
        }
        if ($branchId != null && $branchId != -1) {
            $branchCondition = " AND E.BRANCH_ID ={$branchId} ";
        }
        if ($departmentId != null && $departmentId != -1) {
            $departmentCondition = " AND E.DEPARTMENT_ID ={$departmentId} ";
        }
        if ($positionId != null && $positionId != -1) {
            $positionCondition = " AND E.POSITION_ID ={$positionId} ";
        }
        if ($designationId != null && $designationId != -1) {
            $designationCondition = " AND E.DESIGNATION_ID ={$designationId} ";
        }
        if ($serviceTypeId != null && $serviceTypeId != -1) {
            $serviceTypeCondition = " AND E.SERVICE_TYPE_ID ={$serviceTypeId} ";
        }
        if ($serviceEventTypeId != null && $serviceEventTypeId != -1) {
            $serviceEventtypeCondition = " AND E.SERVICE_EVENT_TYPE_ID ={$serviceEventTypeId} ";
        }
        if ($employeeTypeId != null && $employeeTypeId != -1) {
            $employeeTypeCondition = " AND E.EMPLOYEE_TYPE = '{$employeeTypeId}' ";
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
            $statusCondition = "AND A.OVERALL_STATUS = 'WH'";
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
               SELECT COUNT(*) AS TOTAL FROM  (SELECT A.ID                                        AS ID,
                  A.EMPLOYEE_ID                                    AS EMPLOYEE_ID,
                  INITCAP(TO_CHAR(A.ATTENDANCE_DT, 'DD-MON-YYYY')) AS ATTENDANCE_DT,
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
                  INITCAP(E.FULL_NAME)                             AS EMPLOYEE_NAME,
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
                  END)AS STATUS
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
                WHERE 1=1
                {$employeeCondition}
                {$companyCondition}
                {$branchCondition}
                {$departmentCondition}
                {$positionCondition}
                {$designationCondition}
                {$serviceTypeCondition}
                {$serviceEventtypeCondition}
                {$employeeTypeCondition}
                {$fromDateCondition}
                {$toDateCondition}
                {$statusCondition}
                {$missPunchOnlyCondition}
                ORDER BY A.ATTENDANCE_DT DESC ,A.IN_TIME ASC)
                ";
        return EntityHelper::rawQueryResult($this->adapter, $sql)->current();
    }

    public function filterRecordForMisPunch($employeeId, $branchId, $departmentId, $positionId, $designationId, $serviceTypeId, $serviceEventTypeId, $fromDate, $toDate, $status, $companyId, $employeeTypeId)
    {
    }

    //may need changes
    public function fetchById($id)
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
            ->join(['E' => 'HRIS_EMPLOYEES'], 'A.EMPLOYEE_ID=E.EMPLOYEE_ID', ["FIRST_NAME" => new Expression('INITCAP(E.FIRST_NAME)')], "left");
        $select->where([AttendanceDetail::ID => $id]);
        $statement = $sql->prepareStatementForSqlObject($select);
        $result = $statement->execute();
        return $result->current();
    }

    public function delete($id)
    {
    }

    //no problem with changes
    public function getDtlWidEmpIdDate($employeeId, $attendanceDt)
    {
        $sql = new Sql($this->adapter);
        $select = $sql->select();
        $select->columns(EntityHelper::getColumnNameArrayWithOracleFns(AttendanceDetail::class, NULL, [
            AttendanceDetail::ATTENDANCE_DT
        ], [
            AttendanceDetail::IN_TIME,
            AttendanceDetail::OUT_TIME
        ], NULL, NULL, 'A'), false);
        //        $select->columns([
        //            new Expression("TO_CHAR(A.ATTENDANCE_DT, 'DD-MON-YYYY') AS ATTENDANCE_DT"),
        //            new Expression("TO_CHAR(A.IN_TIME, 'HH:MI AM') AS IN_TIME"),
        //            new Expression("TO_CHAR(A.OUT_TIME, 'HH:MI AM') AS OUT_TIME"),
        //            new Expression("E.EMPLOYEE_ID AS EMPLOYEE_ID"),
        //            new Expression("A.ID AS ID"),
        //            new Expression("A.IN_REMARKS AS IN_REMARKS"),
        //            new Expression("A.OUT_REMARKS AS OUT_REMARKS")], true);
        $select->from(['A' => AttendanceDetail::TABLE_NAME])
            ->join(['E' => 'HRIS_EMPLOYEES'], 'A.EMPLOYEE_ID=E.EMPLOYEE_ID', ["FIRST_NAME" => new Expression('INITCAP(E.FIRST_NAME)'), "MIDDLE_NAME" => new Expression('INITCAP(E.MIDDLE_NAME)'), "LAST_NAME" => new Expression('INITCAP(E.LAST_NAME)')], "left");
        $select->where([
            'A.EMPLOYEE_ID=' . $employeeId,
            "A.ATTENDANCE_DT=TO_DATE('" . $attendanceDt . "','DD-MM-YYYY')"
        ]);
        $statement = $sql->prepareStatementForSqlObject($select);
        $result = $statement->execute();
        return $result->current();
    }

    //ok
    public function addAttendance($model)
    {
        $attendanceTableGateway = new TableGateway(Attendance::TABLE_NAME, $this->adapter);
        return $attendanceTableGateway->insert($model->getArrayCopyForDB());
    }

    // no problem with changes    
    public function getNoOfDaysInDayInterval(int $employeeId, $startDate, $endDate, $includeHoliday = true)
    {
        $sql = new Sql($this->adapter);
        $select = $sql->select();
        $select->from(['A' => AttendanceDetail::TABLE_NAME]);
        $select->where(['A.' . AttendanceDetail::EMPLOYEE_ID . "=$employeeId"]);
        $select->where(['A.' . AttendanceDetail::ATTENDANCE_DT . " BETWEEN " . $startDate->getExpression() . " AND " . $endDate->getExpression()]);

        if ($includeHoliday) {
            $select->where(['A.' . AttendanceDetail::HOLIDAY_ID . " IS NULL"]);
        }

        $statement = $sql->prepareStatementForSqlObject($select);
        $result = $statement->execute();

        return $result->count();
    }

    //    public function getNoOfDaysAbsent(int $employeeId, Expression $startDate, Expression $endDate) {
    //        $sql = new Sql($this->adapter);
    //        $select = $sql->select();
    //        $select->from(['A' => AttendanceDetail::TABLE_NAME]);
    //        $select->where(['A.' . AttendanceDetail::EMPLOYEE_ID . "=$employeeId"]);
    //        $select->where(['A.' . AttendanceDetail::ATTENDANCE_DT . " BETWEEN " . $startDate->getExpression() . " AND " . $endDate->getExpression()]);
    //        $select->where(['A.' . AttendanceDetail::LEAVE_ID . " IS NOT NULL"]);
    //
    //        $statement = $sql->prepareStatementForSqlObject($select);
    //        $result = $statement->execute();
    //
    //        return $result->count();
    //    }
    // need change with changes    
    public function getNoOfDaysAbsent(int $employeeId, Expression $startDate, Expression $endDate)
    {
        $startDt = $startDate->getExpression();
        $endDt = $endDate->getExpression();
        $sql = "SELECT SUM(LEAVE.LEAVE_COUNT) LEAVE_COUNT FROM (
                (SELECT COUNT(LR.EMPLOYEE_ID) AS LEAVE_COUNT FROM HRIS_EMPLOYEE_LEAVE_REQUEST LR,
                (SELECT  HAD.EMPLOYEE_ID, HAD.LEAVE_ID,HAD.ATTENDANCE_DT FROM HRIS_ATTENDANCE_DETAIL HAD
                WHERE HAD.EMPLOYEE_ID=$employeeId 
                AND (HAD.ATTENDANCE_DT BETWEEN 
                $startDt AND $endDt)
                AND HAD.LEAVE_ID IS NOT NULL
                ) AD
                WHERE
                LR.EMPLOYEE_ID = AD.EMPLOYEE_ID AND 
                LR.LEAVE_ID= AD.LEAVE_ID AND 
                LR.HALF_DAY = 'N') UNION (SELECT COUNT(LR.EMPLOYEE_ID)/2 AS LEAVE_COUNT FROM HRIS_EMPLOYEE_LEAVE_REQUEST LR,
                (SELECT  HAD.EMPLOYEE_ID, HAD.LEAVE_ID,HAD.ATTENDANCE_DT FROM HRIS_ATTENDANCE_DETAIL HAD
                WHERE HAD.EMPLOYEE_ID=7 
                AND (HAD.ATTENDANCE_DT BETWEEN 
                $startDt AND $endDt)
                AND HAD.LEAVE_ID IS NOT NULL
                ) AD
                WHERE
                LR.EMPLOYEE_ID = AD.EMPLOYEE_ID AND 
                LR.LEAVE_ID= AD.LEAVE_ID AND 
                LR.HALF_DAY != 'N') 
                ) LEAVE";

        $statement = $this->adapter->query($sql);
        $result = $statement->execute();
        $extractedRes = Helper::extractDbData($result);
        if (sizeof($extractedRes) > 0) {
            return $extractedRes[0]['LEAVE_COUNT'];
        } else {
            return 0;
        }
    }

    // problem with changes    
    public function getNoOfDaysPresent(int $employeeId, Expression $startDate, Expression $endDate)
    {
        return $this->getNoOfDaysInDayInterval($employeeId, $startDate, $endDate) - $this->getNoOfDaysAbsent($employeeId, $startDate, $endDate);
    }

    // problem with changes | MODIFIED   
    public function getEmployeesAttendanceByDate($date, bool $presentFlag, $branchId = null)
    {
        $sql = new Sql($this->adapter);
        $select = $sql->select();
        $select->columns([Helper::timeExpression(AttendanceDetail::IN_TIME, 'A'), new Expression("A.ID AS ID")], true);
        $select->from(['A' => AttendanceDetail::TABLE_NAME]);
        $select->join(['E' => HrEmployees::TABLE_NAME], "A." . AttendanceDetail::EMPLOYEE_ID . "=" . "E." . HrEmployees::EMPLOYEE_ID, [HrEmployees::FIRST_NAME, HrEmployees::MIDDLE_NAME, HrEmployees::LAST_NAME]);
        $select->where(['A.' . AttendanceDetail::LEAVE_ID . " IS NULL", 'A.' . AttendanceDetail::HOLIDAY_ID . " IS NULL", 'A.' . AttendanceDetail::TRAINING_ID . " IS NULL"]);

        $select->where(['A.' . AttendanceDetail::ATTENDANCE_DT . " = " . $date->getExpression()]);

        $select->where(['A.' . AttendanceDetail::DAYOFF_FLAG . " = 'N'"]);
        if ($presentFlag) {
            $select->where(['A.' . AttendanceDetail::IN_TIME . " IS NOT NULL"]);
        } else {
            $select->where(['A.' . AttendanceDetail::IN_TIME . " IS NULL"]);
        }

        if ($branchId != null) {
            $select->where(["E." . AttendanceDetail::EMPLOYEE_ID . " IN (SELECT " . HrEmployees::EMPLOYEE_ID . " FROM " . HrEmployees::TABLE_NAME . " WHERE " . HrEmployees::BRANCH_ID . "= $branchId)"]);
        }

        $statement = $sql->prepareStatementForSqlObject($select);
        return $statement->execute();
    }

    //may need change
    public function getleaveIdCount(int $employeeId, Expression $startDate, Expression $endDate)
    {
        $sql = new Sql($this->adapter);
        $select = $sql->select();
        $select->from(['A' => AttendanceDetail::TABLE_NAME]);
        $select->columns([Helper::columnExpression(AttendanceDetail::LEAVE_ID, "A", "COUNT", AttendanceDetail::LEAVE_ID . "_NO"), AttendanceDetail::LEAVE_ID], true);
        $select->where(['A.' . AttendanceDetail::EMPLOYEE_ID . "=$employeeId"]);
        $select->where(['A.' . AttendanceDetail::ATTENDANCE_DT . " BETWEEN " . $startDate->getExpression() . " AND " . $endDate->getExpression()]);
        $select->where(['A.' . AttendanceDetail::LEAVE_ID . " IS NOT NULL"]);
        $select->group(['A.' . AttendanceDetail::LEAVE_ID]);
        $statement = $sql->prepareStatementForSqlObject($select);
        $result = $statement->execute();
        return $result;
    }

    //need change
    public function getTotalNoOfWorkingDays(Expression $startDate, Expression $endDate)
    {
        $sql = new Sql($this->adapter);
        $select = $sql->select();
        $select->from(['A' => AttendanceDetail::TABLE_NAME]);
        $select->columns([Helper::columnExpression(AttendanceDetail::ATTENDANCE_DT, "DISTINCT  A", null, null)]);
        $select->where(['A.' . AttendanceDetail::ATTENDANCE_DT . " BETWEEN " . $startDate->getExpression() . " AND " . $endDate->getExpression()]);
        //        $select->where(['A.' . AttendanceDetail::HOLIDAY_ID . " IS NULL"]);
        //        $select->group(['A.' . AttendanceDetail::ATTENDANCE_DT]);

        $statement = $sql->prepareStatementForSqlObject($select);
        $result = $statement->execute();

        return $result->count();
    }

    public function checkAndUpdateLeaves(Expression $date)
    {
    }

    //may need changes
    public function fetchByEmpIdAttendanceDT($employeeId, $attendanceDt)
    {
        $sql = new Sql($this->adapter);
        $select = $sql->select();
        $select->columns(EntityHelper::getColumnNameArrayWithOracleFns(AttendanceDetail::class, NULL, [
            AttendanceDetail::ATTENDANCE_DT
        ], [
            AttendanceDetail::IN_TIME,
            AttendanceDetail::OUT_TIME
        ], NULL, NULL, 'A'), false);
        //        $select->columns([new Expression("TO_CHAR(A.ATTENDANCE_DT, 'DD-MON-YYYY') AS ATTENDANCE_DT"), new Expression("TO_CHAR(A.IN_TIME, 'HH:MI AM') AS IN_TIME"), new Expression("TO_CHAR(A.OUT_TIME, 'HH:MI AM') AS OUT_TIME"), new Expression("E.EMPLOYEE_ID AS EMPLOYEE_ID"), new Expression("A.ID AS ID"), new Expression("A.IN_REMARKS AS IN_REMARKS"), new Expression("A.OUT_REMARKS AS OUT_REMARKS")], true);
        $select->from(['A' => AttendanceDetail::TABLE_NAME])
            ->join(['E' => 'HRIS_EMPLOYEES'], 'A.EMPLOYEE_ID=E.EMPLOYEE_ID', ["FIRST_NAME" => new Expression('INITCAP(E.FIRST_NAME)'), "MIDDLE_NAME" => new Expression('INITCAP(E.MIDDLE_NAME)'), "LAST_NAME" => new Expression('INITCAP(E.LAST_NAME)')], "left");
        $select->where([
            'A.EMPLOYEE_ID=' . $employeeId,
            "A.ATTENDANCE_DT=" . $attendanceDt
        ]);
        $statement = $sql->prepareStatementForSqlObject($select);
        //        print_r($statement->getSql()); die();
        $result = $statement->execute();
        return $result->current();
    }

    public function fetchEmployeeShfitDetails($employeeId)
    {
        $sql = "SELECT INITCAP(TO_CHAR(SYSDATE, 'HH:MI AM')) AS CURRENT_TIME,
            INITCAP(TO_CHAR(S.START_TIME+((S.LATE_IN*60)/86400), 'HH:MI AM')) AS CHECKIN_TIME, 
            INITCAP(TO_CHAR(S.END_TIME-((S.EARLY_OUT*60)/86400), 'HH:MI AM')) AS CHECKOUT_TIME,
            INITCAP(TO_CHAR(S.HALF_DAY_END_TIME-((S.EARLY_OUT*60)/86400), 'HH:MI AM')) AS HALF_DAY_CHECKOUT_TIME,
            ESA.*,S.* FROM HRIS_EMPLOYEES E
                join HRIS_EMPLOYEE_SHIFT_ASSIGN ESA on (ESA.EMPLOYEE_ID=E.EMPLOYEE_ID)
                JOIN HRIS_SHIFTS S ON (S.SHIFT_ID=ESA.SHIFT_ID)
                WHERE E.EMPLOYEE_ID=$employeeId AND ESA.STATUS='E' AND ESA.MODIFIED_DT IS NULL
                AND (TO_DATE(TRUNC(SYSDATE), 'DD-MON-YY') BETWEEN TO_DATE(S.START_DATE, 'DD-MON-YY') AND TO_DATE(S.END_DATE, 'DD-MON-YY'))";

        $statement = $this->adapter->query($sql);
        $result = $statement->execute();
        return $result->current();
    }

    public function fetchEmployeeDefaultShift()
    {
        $sql = "SELECT INITCAP(TO_CHAR(SYSDATE, 'HH:MI AM')) AS CURRENT_TIME,
      INITCAP(TO_CHAR(S.START_TIME+((S.LATE_IN*60)/86400), 'HH:MI AM'))   AS CHECKIN_TIME,
      INITCAP(TO_CHAR(S.END_TIME-((S.EARLY_OUT*60)/86400), 'HH:MI AM')) AS CHECKOUT_TIME,
      INITCAP(TO_CHAR(S.HALF_DAY_END_TIME-((S.EARLY_OUT*60)/86400), 'HH:MI AM')) AS HALF_DAY_CHECKOUT_TIME,      
      S.*
            FROM HRIS_SHIFTS S
            WHERE S.DEFAULT_SHIFT='Y'
            AND (TO_DATE(TRUNC(SYSDATE), 'DD-MON-YY') BETWEEN TO_DATE(S.START_DATE, 'DD-MON-YY') AND TO_DATE(S.END_DATE, 'DD-MON-YY'))";
        $statement = $this->adapter->query($sql);
        $result = $statement->execute();
        return $result->current();
    }

    public function manualAttendance($employeeId, $attendanceDt, $action, $impactOtherDays, $shiftId = null, $in_time = null, $out_time = null, $outNextDay = false)
    {
        //        if($outNextDay){
        //            
        //        }else{
        //            
        //        }
        if ($impactOtherDays) {
            $sql = "BEGIN
                  HRIS_MANUAL_ATTENDANCE_ALL({$employeeId},{$attendanceDt},'{$action}', {$shiftId}, {$in_time}, {$out_time});
                END;";
        } else {
            $sql = "BEGIN
                  HRIS_MANUAL_ATTENDANCE({$employeeId},{$attendanceDt},'{$action}', {$shiftId}, {$in_time}, {$out_time});
                END;";
        }
        // echo '<pre>';
        // print_r($sql);
        // die;
        $statement = $this->adapter->query($sql);
        $statement->execute();
    }

    public function filterRecordWithLocation($employeeId = null, $branchId = null, $departmentId = null, $positionId = null, $designationId = null, $serviceTypeId = null, $serviceEventTypeId = null, $fromDate = null, $toDate = null, $status = null, $companyId = null, $employeeTypeId = null, $presentStatus, $min = null, $max = null)
    {
        $searchConditon = EntityHelper::getSearchConditon($companyId, $branchId, $departmentId, $positionId, $designationId, $serviceTypeId, $serviceEventTypeId, $employeeTypeId, $employeeId);
        $fromDateCondition = "";
        $toDateCondition = "";
        $statusCondition = '';
        $presentStatusCondition = '';
        $rowNums = '';
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
            "P" => "'PR','WD','WH','BA','LA','TP','LP','VP'",
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

        if ($min != null && $max != null) {
            $rowNums = "WHERE (Q.R BETWEEN {$min} AND {$max})";
        }
        $orderByString = EntityHelper::getOrderBy('ATTENDANCE_DT DESC ,IN_TIME ASC', 'ATTENDANCE_DT DESC ,IN_TIME ASC', 'SENIORITY_LEVEL', 'LEVEL_NO', 'JOIN_DATE', 'ORDER_NO', 'EMPLOYEE_NAME');
        $sql = "
               SELECT 
DISTINCT
			   EMPLOYEE_ID,ATTENDANCE_DT ,
                 LEVEL_NO ,JOIN_DATE ,ORDER_NO,SENIORITY_LEVEL,
                IN_DEVICE_NAME,
                  OUT_DEVICE_NAME,
                  ID,
                  ATTENDANCE_DT_N,
                  IN_TIME,
                  OUT_TIME,
                  IN_REMARKS,
                  OUT_REMARKS,
                  TOTAL_HOUR,
                  LEAVE_ID,
                  HOLIDAY_ID,
                  TRAINING_ID,
                  TRAVEL_ID,
                  SHIFT_ID,
                  DAYOFF_FLAG,
                  LATE_STATUS,
                  COMPANY_NAME,
                 DEPARTMENT_NAME,
                  EMPLOYEE_NAME,
                  HOLIDAY_ENAME,
                  LEAVE_ENAME,
                  TRAINING_NAME,
                  TRAVEL_DESTINATION,
                  STATUS,
               
                SHIFT_ENAME,
                  START_TIME,
                  END_TIME               

FROM (SELECT 
                  ROWNUM                                           AS R,
                  ADMSIN.DEVICE_LOCATION AS IN_DEVICE_NAME,
                  ADMSOUT.DEVICE_LOCATION AS OUT_DEVICE_NAME,
                  A.ID                                             AS ID,
                  A.EMPLOYEE_ID                                    AS EMPLOYEE_ID,
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
                  COM.COMPANY_NAME                                 AS COMPANY_NAME,
                  DEP.DEPARTMENT_NAME                              AS DEPARTMENT_NAME,
                  INITCAP(E.FULL_NAME)                             AS EMPLOYEE_NAME,
                  H.HOLIDAY_ENAME                                  AS HOLIDAY_ENAME,
                  L.LEAVE_ENAME                                    AS LEAVE_ENAME,
                  T.TRAINING_NAME                                  AS TRAINING_NAME,
                  TVL.DESTINATION                                  AS TRAVEL_DESTINATION,
                   P.LEVEL_NO ,E.JOIN_DATE ,DES.ORDER_NO,E.SENIORITY_LEVEL,
                  (
                  CASE
                    WHEN A.OVERALL_STATUS = 'DO'
                    THEN 'Day Off'
                    WHEN A.OVERALL_STATUS ='HD'
                    THEN 'On Holiday ('
                      ||H.HOLIDAY_ENAME
                      ||')'
                    WHEN A.OVERALL_STATUS ='LV'
                    THEN 'On Leave ('
                      ||L.LEAVE_ENAME
                      || ')'
                    WHEN A.OVERALL_STATUS ='TV'
                    THEN 'On Travel ('
                      ||TVL.DESTINATION
                      ||')'
                    WHEN A.OVERALL_STATUS ='TN'
                    THEN 'On Training ('
                      || (CASE WHEN A.TRAINING_TYPE = 'A' THEN T.TRAINING_NAME ELSE ETN.TITLE END)
                      ||')'
                    WHEN A.OVERALL_STATUS ='WD'
                    THEN 'Work On Dayoff'
                    WHEN A.OVERALL_STATUS ='WH'
                    THEN 'Work on Holiday ('
                      ||H.HOLIDAY_ENAME
                      ||')'
                    WHEN A.OVERALL_STATUS ='LP'
                    THEN 'On Partial Leave ('
                      ||L.LEAVE_ENAME
                      ||') '
                      ||LATE_STATUS_DESC(A.LATE_STATUS) 
                    WHEN A.OVERALL_STATUS ='VP'
                    THEN 'Work on Travel ('
                      ||TVL.DESTINATION
                      ||')'
                      ||LATE_STATUS_DESC(A.LATE_STATUS)
                    WHEN A.OVERALL_STATUS ='TP'
                    THEN 'Present ('
                      ||T.TRAINING_NAME
                      ||')'
                      ||LATE_STATUS_DESC(A.LATE_STATUS)
                    WHEN A.OVERALL_STATUS ='PR'
                    THEN 'Present '
                      ||LATE_STATUS_DESC(A.LATE_STATUS)
                    WHEN A.OVERALL_STATUS ='AB'
                    THEN 'Absent'
                    WHEN A.OVERALL_STATUS ='BA'
                    THEN 'Present(Late In and Early Out)'
                    WHEN A.OVERALL_STATUS ='LA'
                    THEN 'Present(Late Penalty)'
                  END) AS STATUS,
                   S.SHIFT_ENAME,
                  TO_CHAR(S.START_TIME,'HH:MI AM') AS START_TIME,
                  TO_CHAR(S.END_TIME,'HH:MI AM')   AS END_TIME
                FROM HRIS_ATTENDANCE_DETAIL A
                LEFT JOIN HRIS_EMPLOYEES E
                ON A.EMPLOYEE_ID=E.EMPLOYEE_ID
                LEFT JOIN HRIS_COMPANY COM
                ON E.COMPANY_ID=COM.COMPANY_ID
                LEFT JOIN HRIS_DEPARTMENTS DEP
                ON E.DEPARTMENT_ID = DEP.DEPARTMENT_ID
                LEFT JOIN HRIS_POSITIONS P
                ON E.POSITION_ID=P.POSITION_ID
                LEFT JOIN HRIS_DESIGNATIONS DES
                ON E.DESIGNATION_ID=DES.DESIGNATION_ID
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
                LEFT JOIN HRIS_SHIFTS S
                ON A.SHIFT_ID=S.SHIFT_ID
                LEFT JOIN HRIS_ATTENDANCE AIN
                ON (AIN.ATTENDANCE_DT=A.ATTENDANCE_DT 
                AND AIN.EMPLOYEE_ID=A.EMPLOYEE_ID 
                AND TO_CHAR(AIN.ATTENDANCE_TIME, 'HH:MI AM')=TO_CHAR(A.IN_TIME, 'HH:MI AM')
                )
                LEFT JOIN HRIS_ATTENDANCE AOUT
                ON (AOUT.ATTENDANCE_DT=A.ATTENDANCE_DT 
                AND AOUT.EMPLOYEE_ID=A.EMPLOYEE_ID 
                AND TO_CHAR(AOUT.ATTENDANCE_TIME, 'HH:MI AM')=TO_CHAR(A.OUT_TIME, 'HH:MI AM')
                )
                LEFT JOIN HRIS_ATTD_DEVICE_MASTER ADMSIN
                ON (ADMSIN.DEVICE_IP=AIN.IP_ADDRESS)
                LEFT JOIN HRIS_ATTD_DEVICE_MASTER ADMSOUT
                ON (ADMSOUT.DEVICE_IP=AOUT.IP_ADDRESS)
                WHERE 1=1
                {$searchConditon}
                {$fromDateCondition}
                {$toDateCondition}
                {$statusCondition}
                {$presentStatusCondition}
                ) Q {$orderByString}
                {$rowNums}
                ";
        return EntityHelper::rawQueryResult($this->adapter, $sql);
    }

    public function fetchDailyPerformanceReport($data)
    {
        $condition = EntityHelper::getSearchConditon($data['companyId'], $data['branchId'], $data['departmentId'], $data['positionId'], $data['designationId'], $data['serviceTypeId'], $data['serviceEventTypeId'], $data['employeeTypeId'], $data['employeeId'], $data['genderId'], $data['locationId']);

        $fromDate = $data['fromDate'];
        $toDate = $data['toDate'];

        $sql = "select
            e.full_name as full_name,
            e.employee_code as employee_code,
            D.Department_Name as department_name
            ,to_char(s.start_time,'HH:MI AM') as shift_start_time
            ,to_char(s.end_time,'HH:MI AM') as shift_end_time
            ,min_to_hour(s.total_working_hr) as total_working_hr
            ,min_to_hour(s.actual_working_hr) as actual_working_hr
            , case when ad.ot_minutes>0
            then
            trunc(ad.ot_minutes/60,2)
            else
            0
            end
            as OT
            ,ad.employee_id,
            ad.Attendance_Dt,
            to_char(ad.in_time,'HH:MI AM') as in_time,
            lunch_in_time(ad.employee_id,ad.Attendance_Dt,ad.Shift_Id,ad.in_time,ad.out_time) as lunch_in_time
            ,lunch_out_time(ad.employee_id,ad.Attendance_Dt,ad.Shift_Id,ad.in_time,ad.out_time) as lunch_out_time
            , to_char(ad.Out_Time,'HH:MI AM') as Out_Time
            from Hris_Attendance_detail ad 
            left join hris_shifts s on (s.shift_id=ad.shift_id)
            left join hris_employees e on (e.employee_id=ad.employee_id)
            left join Hris_Departments d on (d.department_id=e.department_id)
            where 
            ad.Attendance_Dt between ";

        $sql .= $data['fromDate'] == null ? "trunc(sysdate) " : " '$fromDate' ";
        $sql .= " and ";
        $sql .= $data['toDate'] == null ? "trunc(sysdate) " : " '$toDate' ";

        $sql .= " {$condition} ";

        return EntityHelper::rawQueryResult($this->adapter, $sql);
    }

    public function filterRecordBot($companyId, $branchId, $departmentId, $designationId, $positionId, $serviceTypeId, $serviceEventTypeId, $employeeTypeId, $genderId, $functionalTypeId, $employeeId, $fromDate = null, $toDate = null, $status = null, $presentStatus = null, $min = null, $max = null)
    {
        $searchConditon = EntityHelper::getSearchConditon($companyId, $branchId, $departmentId, $positionId, $designationId, $serviceTypeId, $serviceEventTypeId, $employeeTypeId, $employeeId, $genderId, null, $functionalTypeId);
        $fromDateCondition = "";
        $toDateCondition = "";
        $statusCondition = '';
        $presentStatusCondition = '';
        $rowNums = '';
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
            "P" => "'PR','WD','WH','BA','LA','TP','LP','VP'",
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

        if ($min != null && $max != null) {
            $rowNums = "WHERE (Q.R BETWEEN {$min} AND {$max})";
        }
        $orderByString = EntityHelper::getOrderBy('A.ATTENDANCE_DT DESC ,A.IN_TIME ASC', 'A.ATTENDANCE_DT DESC ,A.IN_TIME ASC', 'E.SENIORITY_LEVEL', 'P.LEVEL_NO', 'E.JOIN_DATE', 'DES.ORDER_NO', 'E.FULL_NAME');
        $sql = "
               SELECT * FROM (SELECT 
                  ROWNUM                                           AS R,
                  A.ID                                             AS ID,
                  A.EMPLOYEE_ID                                    AS EMPLOYEE_ID,
                  E.EMPLOYEE_CODE                                  AS EMPLOYEE_CODE,
                  initcap(TO_CHAR(attendance_dt, 'DAY')) AS DAY,
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
                  COM.COMPANY_NAME                                 AS COMPANY_NAME,
                  DEP.DEPARTMENT_NAME                              AS DEPARTMENT_NAME,
                  INITCAP(E.FULL_NAME)                             AS EMPLOYEE_NAME,
                  H.HOLIDAY_ENAME                                  AS HOLIDAY_ENAME,
                  L.LEAVE_ENAME                                    AS LEAVE_ENAME,
                  T.TRAINING_NAME                                  AS TRAINING_NAME,
                  TVL.DESTINATION                                  AS TRAVEL_DESTINATION,
                  (
                  CASE
                    WHEN A.OVERALL_STATUS = 'DO'
                    THEN 'Weekly Off'
                    WHEN A.OVERALL_STATUS ='HD'
                    THEN 'On Holiday ('
                      ||H.HOLIDAY_ENAME
                      ||')'
                    WHEN A.OVERALL_STATUS ='LV'
                    THEN 'On Leave ('
                      ||L.LEAVE_ENAME
                      || ')'
                    WHEN A.OVERALL_STATUS ='TV'
                    THEN 'On Travel ('
                      ||TVL.DESTINATION
                      ||')'
                    WHEN A.OVERALL_STATUS ='TN'
                    THEN 'On Training ('
                      || (CASE WHEN A.TRAINING_TYPE = 'A' THEN T.TRAINING_NAME ELSE ETN.TITLE END)
                      ||')'
                    WHEN A.OVERALL_STATUS ='WD'
                    THEN 'Work On Weeklyoff'
                    WHEN A.OVERALL_STATUS ='WH'
                    THEN 'Work on Holiday ('
                      ||H.HOLIDAY_ENAME
                      ||')'
                    WHEN A.OVERALL_STATUS ='LP'
                    THEN 'On Partial Leave ('
                      ||L.LEAVE_ENAME
                      ||') '
                      ||LATE_STATUS_DESC(A.LATE_STATUS) 
                    WHEN A.OVERALL_STATUS ='VP'
                    THEN 'Work on Travel ('
                      ||TVL.DESTINATION
                      ||')'
                      ||LATE_STATUS_DESC(A.LATE_STATUS)
                    WHEN A.OVERALL_STATUS ='TP'
                    THEN 'Present ('
                      ||T.TRAINING_NAME
                      ||')'
                      ||LATE_STATUS_DESC(A.LATE_STATUS)
                    WHEN A.OVERALL_STATUS ='PR'
                    THEN 'Present '
                      ||LATE_STATUS_DESC(A.LATE_STATUS)
                    WHEN A.OVERALL_STATUS ='AB'
                    THEN 'Absent'
                    WHEN A.OVERALL_STATUS ='BA'
                    THEN 'Present(Late In and Early Out)'
                    WHEN A.OVERALL_STATUS ='LA'
                    THEN 'Present(Late Penalty)'
                  END) AS STATUS,
                   S.SHIFT_ENAME,
                  TO_CHAR(S.START_TIME,'HH:MI AM') AS START_TIME,
                  TO_CHAR(S.END_TIME,'HH:MI AM')   AS END_TIME,
                   CASE WHEN A.OT_MINUTES>0
                   THEN 
                   MIN_TO_HOUR(A.OT_MINUTES)
                   ELSE ''
                   END
                   AS SYSTEM_OVERTIME,
                  CASE WHEN A.OM.OVERTIME_HOUR is not null
                   THEN 
                  MIN_TO_HOUR(A.OM.OVERTIME_HOUR*60)
                   ELSE ''
                   END AS MANUAL_OVERTIME,
               FUNT.FUNCTIONAL_TYPE_EDESC                                        AS FUNCTIONAL_TYPE_EDESC
                FROM HRIS_ATTENDANCE_DETAIL A
                LEFT JOIN HRIS_EMPLOYEES E
                ON A.EMPLOYEE_ID=E.EMPLOYEE_ID
                LEFT JOIN HRIS_COMPANY COM
                ON E.COMPANY_ID=COM.COMPANY_ID
                LEFT JOIN HRIS_DEPARTMENTS DEP
                ON E.DEPARTMENT_ID = DEP.DEPARTMENT_ID
                LEFT JOIN HRIS_POSITIONS P
                ON E.POSITION_ID=P.POSITION_ID
                LEFT JOIN HRIS_DESIGNATIONS DES
                ON E.DESIGNATION_ID=DES.DESIGNATION_ID
                LEFT JOIN HRIS_FUNCTIONAL_TYPES FUNT
                ON E.FUNCTIONAL_TYPE_ID=FUNT.FUNCTIONAL_TYPE_ID
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
                LEFT JOIN HRIS_SHIFTS S
                ON A.SHIFT_ID=S.SHIFT_ID
                LEFT JOIN  HRIS_OVERTIME_MANUAL OM
                ON (OM.ATTENDANCE_DATE=A.ATTENDANCE_DT AND OM.EMPLOYEE_ID=A.EMPLOYEE_ID)
                WHERE 1=1
                {$searchConditon}
                {$fromDateCondition}
                {$toDateCondition}
                {$statusCondition}
                {$presentStatusCondition}
                {$orderByString}
                ) Q
                {$rowNums}
                ";
        return EntityHelper::rawQueryResult($this->adapter, $sql);
    }

    public function filterRecordRaw($employeeId, $fromDate = null, $toDate = null)
    {
        $searchConditon = "";

        if ($employeeId != null && $employeeId != -1) {
            $employeeId = implode(',', $employeeId);
            $searchConditon .= " AND A.EMPLOYEE_ID IN ($employeeId) ";
        }
        if ($fromDate != null) {
            $searchConditon .= " AND A.ATTENDANCE_DT>=TO_DATE('" . $fromDate . "','DD-MM-YYYY') ";
        }
        $searchConditon .= $toDate != null ? "AND A.ATTENDANCE_DT<=TO_DATE('" . $toDate . "','DD-MM-YYYY')" : "AND A.ATTENDANCE_DT <= trunc(sysdate) ";
        $sql = "SELECT
    e.employee_code,
    e.full_name,
    a.attendance_dt,
    BS_DATE(TO_CHAR(A.ATTENDANCE_DT, 'DD-MON-YYYY')) AS ATTENDANCE_DT_N,
    TO_CHAR(A.ATTENDANCE_DT, 'DAY') AS DAY,
    INITCAP(TO_CHAR(A.attendance_time, 'HH:MI AM'))   attendance_time,
    (case when adm.purpose is null then 'APP' else adm.purpose end) purpose
FROM
    hris_employees e
    JOIN hris_attendance a ON ( e.employee_id = a.employee_id )
    left join hris_attd_device_master adm on (a.ip_address = adm.device_ip)
WHERE
    1=1 {$searchConditon} order by e.employee_code, a.attendance_time asc";
        return EntityHelper::rawQueryResult($this->adapter, $sql);
    }
}
