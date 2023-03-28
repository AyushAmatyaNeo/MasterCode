<?php

namespace LeaveManagement\Repository;

use Application\Helper\EntityHelper;
use Application\Model\Model;
use LeaveManagement\Model\LeaveAssign;
use LeaveManagement\Model\LeaveMaster;
use Setup\Model\HrEmployees;
use Zend\Db\Adapter\AdapterInterface;
use Zend\Db\Sql\Expression;
use Zend\Db\Sql\Sql;
use Zend\Db\TableGateway\TableGateway;
use Application\Helper\Helper;
use Zend\Db\Sql\Select;
use LeaveManagement\Model\LeaveMonths;
use Application\Repository\HrisRepository;

class LeaveBalanceRepository extends HrisRepository {

    protected $adapter;
    protected $tableGateway;
    protected $leaveTableGateway;
    protected $employeeTableGateway;
    protected $leaveMonthTableGateway;

    public function __construct(AdapterInterface $adapter) {
        $this->adapter = $adapter;
        $this->tableGateway = new TableGateway(LeaveAssign::TABLE_NAME, $adapter);
        $this->leaveTableGateway = new TableGateway(LeaveMaster::TABLE_NAME, $adapter);
        $this->employeeTableGateway = new TableGateway("HRIS_EMPLOYEES", $adapter);
        $this->leaveMonthTableGateway = new TableGateway("HRIS_LEAVE_MONTH_CODE", $adapter);
    }
    
    

    public function getAllLeave($isMonthly = false, $leaveId = null,$leaveYear=null) {
        $boundedParameter = [];
        $leaveCondition = " AND REPORT_SHOW_DEFAULT='Y'";
        if($leaveId != null && $leaveId != ''){
            $leaveData=$this->getBoundedForArray($leaveId,'leaveId');
            $boundedParameter=array_merge($boundedParameter,$leaveData['parameter']);
            $leaveCondition = " and leave_id in ({$leaveData['sql']})";
        }
        if($leaveYear!=null){
            $boundedParameter['leaveYear'] = $leaveYear;
            $leaveYearStatusCondition="( ( STATUS ='E' OR OLD_LEAVE='Y' ) AND LEAVE_YEAR= :leaveYear )";
        }else{
            $leaveYearStatusCondition="STATUS ='E'";
        }
        $condition = $isMonthly ? " AND IS_MONTHLY = 'Y' " : "  AND IS_MONTHLY = 'N' ";
        $sql = "SELECT LEAVE_ID,INITCAP(LEAVE_ENAME) AS LEAVE_ENAME FROM HRIS_LEAVE_MASTER_SETUP WHERE {$leaveYearStatusCondition} {$condition} {$leaveCondition} ORDER BY VIEW_ORDER,LEAVE_ENAME";
        $statement = $this->adapter->query($sql);
        return $statement->execute($boundedParameter);
    }

    public function getAllEmployee($emplyoeeId, $companyId, $branchId, $departmentId, $designationId, $positionId, $serviceTypeId, $serviceEventTypeId, $employeeTypeId) {
        $sql = new Sql($this->adapter);
        $select = $sql->select();
        $select->columns(EntityHelper::getColumnNameArrayWithOracleFns(HrEmployees::class, [HrEmployees::FIRST_NAME, HrEmployees::MIDDLE_NAME, HrEmployees::LAST_NAME], NULL, NULL, NULL, NULL, 'E'), false);

        $select->from(['E' => "HRIS_EMPLOYEES"]);

        $select->where([
            "E.STATUS='E'"
        ]);

        if ($serviceEventTypeId == 5 || $serviceEventTypeId == 8 || $serviceEventTypeId == 14) {
            $select->where(["E.RETIRED_FLAG='Y'"]);
        } else {
            $select->where(["E.RETIRED_FLAG='N'"]);
        }


        if ($employeeTypeId != null && $employeeTypeId != -1) {
            $select->where([
                "E.EMPLOYEE_TYPE= '{$employeeTypeId}'"
            ]);
        }

        if ($emplyoeeId != -1) {
            $select->where([
                "E.EMPLOYEE_ID=" . $emplyoeeId
            ]);
        }
        if ($companyId != -1) {
            $select->where([
                "E.COMPANY_ID=" . $companyId
            ]);
        }
        if ($branchId != -1) {
            $select->where([
                "E.BRANCH_ID=" . $branchId
            ]);
        }
        if ($departmentId != -1) {
            $select->where([
                "E.DEPARTMENT_ID=" . $departmentId
            ]);
        }
        if ($designationId != -1) {
            $select->where([
                "E.DESIGNATION_ID=" . $designationId
            ]);
        }
        if ($positionId != -1) {
            $select->where([
                "E.POSITION_ID=" . $positionId
            ]);
        }
        if ($serviceTypeId != -1) {
            $select->where([
                "E.SERVICE_TYPE_ID=" . $serviceTypeId
            ]);
        }
        if ($serviceEventTypeId != -1) {
            $select->where([
                "E.SERVICE_EVENT_TYPE_ID=" . $serviceEventTypeId
            ]);
        }
        $select->order("E.FIRST_NAME ASC");
        $statement = $sql->prepareStatementForSqlObject($select);
        $result = $statement->execute();
        return $result;
    }

    public function getByEmpIdLeaveId($employeeId, $leaveId) {
        $sql = new Sql($this->adapter);
        $select = $sql->select();
        $select->columns([
            new Expression("LA.TOTAL_DAYS AS TOTAL_DAYS"),
            new Expression("LA.BALANCE AS BALANCE"),
            new Expression("LA.LEAVE_ID AS LEAVE_ID"),
            new Expression("LA.EMPLOYEE_ID AS EMPLOYEE_ID"),
                ], true);

        $select->from(['LA' => LeaveAssign::TABLE_NAME])
                ->join(['E' => "HRIS_EMPLOYEES"], "E.EMPLOYEE_ID=LA.EMPLOYEE_ID", ['FIRST_NAME' => new Expression('INITCAP(E.FIRST_NAME)'), 'MIDDLE_NAME' => new Expression('INITCAP(E.MIDDLE_NAME)'), 'LAST_NAME' => new Expression('INITCAP(E.LAST_NAME)'), 'SERVICE_EVENT_TYPE_ID'], "left")
                ->join(['L' => 'HRIS_LEAVE_MASTER_SETUP'], "L.LEAVE_ID=LA.LEAVE_ID", ['LEAVE_CODE', 'LEAVE_ENAME' => new Expression('INITCAP(L.LEAVE_ENAME)')], "left");

        $select->where([
            "L.STATUS='E'",
            "E.STATUS='E'",
            "LA.EMPLOYEE_ID=" . $employeeId,
            "LA.LEAVE_ID=" . $leaveId
        ]);
        $select->order(['L.LEAVE_ID']);

        $statement = $sql->prepareStatementForSqlObject($select);
        $result = $statement->execute();
        return $result->current();
    }

    public function getOnlyCarryForwardedRecord() {
        $sql = new Sql($this->adapter);
        $select = $sql->select();
        $select->columns([
            new Expression("LA.BALANCE AS BALANCE"),
            new Expression("LA.TOTAL_DAYS AS TOTAL_DAYS"),
            new Expression("LA.PREVIOUS_YEAR_BAL AS PREVIOUS_YEAR_BAL"),
            new Expression("LA.LEAVE_ID AS LEAVE_ID"),
            new Expression("LA.EMPLOYEE_ID AS EMPLOYEE_ID")
                ], true);

        $select->from(['LA' => LeaveAssign::TABLE_NAME])
                ->join(['E' => "HRIS_EMPLOYEES"], "E.EMPLOYEE_ID=LA.EMPLOYEE_ID", ['FIRST_NAME' => new Expression('INITCAP(E.FIRST_NAME)'), 'MIDDLE_NAME' => new Expression('INITCAP(E.MIDDLE_NAME)'), 'LAST_NAME' => new Expression('INITCAP(E.LAST_NAME)')], "left")
                ->join(['L' => 'HRIS_LEAVE_MASTER_SETUP'], "L.LEAVE_ID=LA.LEAVE_ID", ['LEAVE_CODE', 'LEAVE_ENAME' => new Expression('INITCAP(LEAVE_ENAME)')], "left");

        $select->where([
            "L.STATUS='E'",
            "E.STATUS='E'",
            "E.RETIRED_FLAG='N'",
            "L.CARRY_FORWARD='Y'"
        ]);
        $select->order(['LA.EMPLOYEE_ID']);

        $statement = $sql->prepareStatementForSqlObject($select);
        $result = $statement->execute();


        $record = [];
        foreach ($result as $row) {
            array_push($record, [
                'EMPLOYEE_ID' => $row['EMPLOYEE_ID'],
                'LEAVE_ID' => $row['LEAVE_ID'],
                'PREVIOUS_YEAR_BAL' => $row['PREVIOUS_YEAR_BAL'],
                'TOTAL_DAYS' => $row['TOTAL_DAYS'],
                'BALANCE' => $row['BALANCE'],
            ]);
        }
        return $record;
    }

    public function getPivotedList($searchQuery, $isMonthly = false) {
        $boundedParameter = [];
        $leaveId = $searchQuery['leaveId'];
        $leaveCondition = "";
        
        $leaveData=[];
        if($leaveId != null && $leaveId != ''){
            $leaveData=$this->getBoundedForArray($leaveId,'leaveId');
            $boundedParameter=array_merge($boundedParameter,$leaveData['parameter']);
            $leaveCondition = " AND HA.LEAVE_ID IN ({$leaveData['sql']})";
        }
        
//        print_r($boundedParameter);
//        die();
        
//        if($leaveId != null && $leaveId != ''){
//            $leaveId = implode($leaveId, ',');
//            $leaveCondition .= " AND HA.LEAVE_ID IN ($leaveId) ";
//        }
        $leaveYear = $searchQuery['leaveYear'];
        $searchCondition = EntityHelper::getSearchConditonBounded($searchQuery['companyId'], $searchQuery['branchId'], $searchQuery['departmentId'], $searchQuery['positionId'], $searchQuery['designationId'], $searchQuery['serviceTypeId'], $searchQuery['serviceEventTypeId'], $searchQuery['employeeTypeId'], $searchQuery['employeeId'], $searchQuery['genderId'], $searchQuery['locationId'], $searchQuery['functionalTypeId']);

        $boundedParameter=array_merge($boundedParameter, $searchCondition['parameter']);
        
        if($isMonthly){
        $boundedParameter['FISCAL_YEAR_MONTH_NO']=$searchQuery['leaveYearMonthNo'];
        $monthlyCondition =  " AND FISCAL_YEAR_MONTH_NO =:FISCAL_YEAR_MONTH_NO ";
        }else{
        $monthlyCondition = "";
        }

        $leaveArrayDb = $this->fetchLeaveAsDbArray($isMonthly, $leaveData,$leaveYear);
        $includePreviousBalance = $isMonthly ? "0" : " HA.PREVIOUS_YEAR_BAL ";
        
//         $sql = "
//            SELECT LA.*,E.FULL_NAME, E.EMPLOYEE_CODE AS EMPLOYEE_CODE
// ,D.Department_Name,
//     Funt.Functional_Type_Edesc        
//     ,BP.PROVINCE_NAME                                 AS BRANCH_PROVINCE,
//      BR.BRANCH_NAME
//      ,P.POSITION_NAME
// FROM (SELECT *
//             FROM
//               (SELECT 
//               HA.EMPLOYEE_ID,
//                     NVL(HA.PREVIOUS_YEAR_BAL,0) as PREVIOUS_YEAR_BAL,
//                     HA.LEAVE_ID,
//                     HA.TOTAL_DAYS AS CURR,
//                     NVL({$includePreviousBalance}, 0) + HA.TOTAL_DAYS AS TOTAL,
//                     HA.BALANCE,
//                     HS.ENCASH_DAYS as ENCASHED,
//                     ( NVL({$includePreviousBalance}, 0) + ha.total_days - ha.balance - (case when
//                     HS.ENCASH_DAYS is null then 0 else HS.ENCASH_DAYS end) - nvl(EPD.penalty_days,0)) AS taken,
//                     EPD.penalty_days as DEDUCTED
//               FROM 
//               HRIS_EMPLOYEE_LEAVE_ASSIGN HA
//                     left JOIN 
//                     HRIS_EMP_SELF_LEAVE_CLOSING HS
//                     on (HA.EMPLOYEE_ID = HS.EMPLOYEE_ID and HA.leave_id = HS.leave_id)
//                     left join (
//                     select employee_id,leave_id,sum(no_of_days) as penalty_days
//                     from HRIS_EMPLOYEE_PENALTY_DAYS
//                     group by employee_id,leave_id) EPD
//                     on (EPD.EMPLOYEE_ID = HA.EMPLOYEE_ID AND EPD.LEAVE_ID = HA.LEAVE_ID)
//               WHERE ha.EMPLOYEE_ID IN
//                 ( SELECT E.EMPLOYEE_ID FROM HRIS_EMPLOYEES E WHERE 1=1 AND E.STATUS='E' {$searchCondition['sql']}
//                 ){$monthlyCondition} {$leaveCondition}
//               ) PIVOT (sum ( ENCASHED ) AS ENCASHED, sum ( DEDUCTED ) AS DEDUCTED, MAX(PREVIOUS_YEAR_BAL) AS PREVIOUS_YEAR_BAL,MAX(BALANCE) AS BALANCE,MAX(CURR) AS CURR,MAX(TOTAL) AS TOTAL,MAX(TAKEN) AS TAKEN FOR LEAVE_ID IN ({$leaveArrayDb}) )
//             ) LA LEFT JOIN HRIS_EMPLOYEES E ON (LA.EMPLOYEE_ID=E.EMPLOYEE_ID)
//             LEFT JOIN HRIS_DESIGNATIONS DES
//       ON E.DESIGNATION_ID=DES.DESIGNATION_ID 
//       LEFT JOIN HRIS_POSITIONS P
//       ON E.POSITION_ID=P.POSITION_ID
//       LEFT JOIN hris_departments d on d.department_id=e.department_id
//     left join Hris_Functional_Types funt on funt.Functional_Type_Id=e.Functional_Type_Id
//     left join Hris_Service_Types st on (st.service_type_id=E.Service_Type_Id)
//     LEFT JOIN HRIS_BRANCHES BR ON (E.BRANCH_ID = BR.BRANCH_ID)
//     LEFT JOIN HRIS_PROVINCES BP on (BP.PROVINCE_ID=BR.PROVINCE_ID)
// ";
if($isMonthly){
    $sql = "
    SELECT LA.*,E.FULL_NAME, E.EMPLOYEE_CODE AS EMPLOYEE_CODE
    ,D.Department_Name,
        Funt.Functional_Type_Edesc,
    P.Position_Name	
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
and employee_id = ha.employee_id and start_date between 
(select start_date from hris_leave_years where leave_year_id = (select fiscal_year from hris_leave_master_setup where leave_id = HA.leave_id))
and (select to_date from hris_leave_month_code where leave_year_month_no = {$searchQuery['leaveYearMonthNo']} and leave_year_id = (select fiscal_year from hris_leave_master_setup where leave_id = HA.leave_id))
)  - (case when
                        HS.ENCASH_DAYS is null then 0 else HS.ENCASH_DAYS end) as takenNew
                FROM 
                HRIS_EMPLOYEE_LEAVE_ASSIGN HA
                        left JOIN 
                        HRIS_EMP_SELF_LEAVE_CLOSING HS
                        on (HA.EMPLOYEE_ID = HS.EMPLOYEE_ID and HA.leave_id = HS.leave_id)
                WHERE ha.EMPLOYEE_ID IN
                    ( SELECT E.EMPLOYEE_ID FROM HRIS_EMPLOYEES E WHERE 1=1 AND E.STATUS='E' {$searchCondition['sql']}
                    ){$monthlyCondition} {$leaveCondition} )
                ) PIVOT (sum ( ENCASHED ) AS ENCASHED, MAX(PREVIOUS_YEAR_BAL) AS PREVIOUS_YEAR_BAL,MAX(BALANCE) AS BALANCE,MAX(TOTAL) AS TOTAL,MAX(TAKEN) AS TAKEN FOR LEAVE_ID IN ({$leaveArrayDb}) )
                ) LA LEFT JOIN HRIS_EMPLOYEES E ON (LA.EMPLOYEE_ID=E.EMPLOYEE_ID)
                LEFT JOIN HRIS_DESIGNATIONS DES
        ON E.DESIGNATION_ID=DES.DESIGNATION_ID 
        LEFT JOIN HRIS_POSITIONS P
        ON E.POSITION_ID=P.POSITION_ID
        LEFT JOIN hris_departments d on d.department_id=e.department_id
        left join Hris_Functional_Types funt on funt.Functional_Type_Id=e.Functional_Type_Id
        left join Hris_Service_Types st on (st.service_type_id=E.Service_Type_Id)
    ";
}else{
    $sql = "
    SELECT LA.*,E.FULL_NAME, E.EMPLOYEE_CODE AS EMPLOYEE_CODE
    ,D.Department_Name,
        Funt.Functional_Type_Edesc,
    P.Position_Name	
    FROM (SELECT *
                FROM
                (SELECT 
                HA.EMPLOYEE_ID,
                        HA.PREVIOUS_YEAR_BAL,
                        HA.LEAVE_ID,
                        HA.TOTAL_DAYS AS TOTAL,
                        HA.BALANCE,
                        HS.ENCASH_DAYS as ENCASHED,
                        ( HA.PREVIOUS_YEAR_BAL + ha.total_days - ha.balance - (case when
                        HS.ENCASH_DAYS is null then 0 else HS.ENCASH_DAYS end)) AS taken
                FROM 
                HRIS_EMPLOYEE_LEAVE_ASSIGN HA
                        left JOIN 
                        HRIS_EMP_SELF_LEAVE_CLOSING HS
                        on (HA.EMPLOYEE_ID = HS.EMPLOYEE_ID and HA.leave_id = HS.leave_id)
                WHERE ha.EMPLOYEE_ID IN
                    ( SELECT E.EMPLOYEE_ID FROM HRIS_EMPLOYEES E WHERE 1=1 AND E.STATUS='E' {$searchCondition['sql']}
                    ){$monthlyCondition} {$leaveCondition}
                ) PIVOT (sum ( ENCASHED ) AS ENCASHED, MAX(PREVIOUS_YEAR_BAL) AS PREVIOUS_YEAR_BAL,MAX(BALANCE) AS BALANCE,MAX(TOTAL) AS TOTAL,MAX(TAKEN) AS TAKEN FOR LEAVE_ID IN ({$leaveArrayDb}) )
                ) LA LEFT JOIN HRIS_EMPLOYEES E ON (LA.EMPLOYEE_ID=E.EMPLOYEE_ID)
                LEFT JOIN HRIS_DESIGNATIONS DES
        ON E.DESIGNATION_ID=DES.DESIGNATION_ID 
        LEFT JOIN HRIS_POSITIONS P
        ON E.POSITION_ID=P.POSITION_ID
        LEFT JOIN hris_departments d on d.department_id=e.department_id
        left join Hris_Functional_Types funt on funt.Functional_Type_Id=e.Functional_Type_Id
        left join Hris_Service_Types st on (st.service_type_id=E.Service_Type_Id)
    ";
}
    // echo '<pre>';print_r($sql);die;
        return $this->rawQuery($sql, $boundedParameter);
    }

    private function fetchLeaveAsDbArray($isMonthly = false, $leaveId = '',$leaveYear=null) {
        $condition = $isMonthly ? " AND IS_MONTHLY = 'Y' " : " AND IS_MONTHLY = 'N' ";
        $leaveCondition = "";
        $boundedParameter = [];
        if($leaveId != null && $leaveId != ''){
            if (gettype($leaveId) === "array") {
                $boundedParameter=array_merge($boundedParameter,$leaveId['parameter']);
                $leaveCondition .= " AND LEAVE_ID IN ({$leaveId['sql']}) ";
            } else {
                $leaveCondition .= " AND LEAVE_ID IN ($leaveId) ";
            }
        }
        if($leaveYear!=null){
            $boundedParameter['leaveYear']=$leaveYear;
            $leaveYearStatusCondition="( ( STATUS ='E' OR OLD_LEAVE='Y' ) AND LEAVE_YEAR= :leaveYear)";
        }else{
            $leaveYearStatusCondition="STATUS ='E'";
        }
    $rawList = EntityHelper::rawQueryResult($this->adapter, "SELECT LEAVE_ID FROM HRIS_LEAVE_MASTER_SETUP WHERE {$leaveYearStatusCondition} {$condition} {$leaveCondition}",$boundedParameter);
        $dbArray = "";
        foreach ($rawList as $key => $row) {
            if ($key == sizeof($rawList)) {
                $dbArray .= "{$row['LEAVE_ID']} AS L{$row['LEAVE_ID']}";
            } else {
                $dbArray .= "{$row['LEAVE_ID']} AS L{$row['LEAVE_ID']},";
            }
        }
        return $dbArray;
    }

    public function getPivotedListBetnDates($searchQuery, $isMonthly = false) {
        $boundedParameter=[];
        $orderByString = EntityHelper::getOrderBy('E.FULL_NAME ASC', null, 'E.SENIORITY_LEVEL', 'P.LEVEL_NO', 'E.JOIN_DATE', 'DES.ORDER_NO', 'E.FULL_NAME');
        $searchCondition = EntityHelper::getSearchConditonBounded($searchQuery['companyId'], $searchQuery['branchId'], $searchQuery['departmentId'], $searchQuery['positionId'], $searchQuery['designationId'], $searchQuery['serviceTypeId'], $searchQuery['serviceEventTypeId'], $searchQuery['employeeTypeId'], $searchQuery['employeeId'], $searchQuery['genderId'], $searchQuery['locationId'], $searchQuery['functionalTypeId']);
        $boundedParameter=array_merge($boundedParameter, $searchCondition['parameter']);
        
        $leaveYear = $searchQuery['leaveYear'];
        $leaveArrayDb = $this->fetchLeaveAsDbArray($isMonthly, null,$leaveYear);
        $boundedParameter['fromDate']=$searchQuery['fromDate'];
        $boundedParameter['toDate']=$searchQuery['toDate'];
        $fromDate = "to_date(:fromDate)";
        $toDate = "to_date(:toDate)";

        $sql = "SELECT LB.*,E.FULL_NAME,
            E.EMPLOYEE_CODE AS EMPLOYEE_CODE,
            (D.DEPARTMENT_NAME)                                        AS DEPARTMENT_NAME,
            (DES.DESIGNATION_TITLE)                                    AS DESIGNATION_TITLE,
            (P.POSITION_NAME)                                          AS POSITION_NAME,
            FUNT.FUNCTIONAL_TYPE_EDESC                                        AS FUNCTIONAL_TYPE_EDESC
            FROM (SELECT * FROM
(SELECT la.employee_id,
    la.leave_id,
    LTBD.lEAVE_TAKEN_BETWEEN_DATES AS TAKEN,
    case when ltad.leave_taken_after_dates is not null
    then 
    la.balance+ltad.leave_taken_after_dates
    else
    la.balance
    end AS CALCULATED_BALANCE
              FROM HRIS_EMPLOYEE_LEAVE_ASSIGN LA
              LEFT JOIN 
              (SELECT 
EMPLOYEE_ID,LEAVE_ID
,SUM(CASE WHEN HALF_DAY='F' OR HALF_DAY='S' THEN 0.5 ELSE 1 END) AS lEAVE_TAKEN_BETWEEN_DATES 
FROM (SELECT * FROM HRIS_EMPLOYEE_LEAVE_REQUEST WHERE STATUS IN ('AP','CP','CR')) LR
  JOIN 
(SELECT   {$fromDate} + ROWNUM -1  AS DATES
    FROM dual d
    CONNECT BY  rownum <=  {$toDate} -  {$fromDate} + 1) ADT ON (ADT.DATES Between START_DATE AND END_DATE)
    WHERE  ADT.DATES BETWEEN  {$fromDate} AND {$toDate}
   GROUP BY EMPLOYEE_ID,LEAVE_ID) LTBD ON (LTBD.LEAVE_ID=LA.LEAVE_ID AND LTBD.EMPLOYEE_ID=LA.EMPLOYEE_ID)
   LEFT JOIN (
   select EMPLOYEE_ID,LEAVE_ID
,SUM(CASE WHEN HALF_DAY='F' OR HALF_DAY='S' THEN leave_days/0.5 ELSE leave_days END) AS lEAVE_TAKEN_AFTER_DATES 
from (SELECT EMPLOYEE_ID,LEAVE_ID,START_DATE,END_DATE,NO_OF_DAYS,HALF_DAY,
CASE WHEN
    half_day = 'F'
OR
    half_day = 'S'
THEN (end_date - {$toDate})/2
ELSE end_date - {$toDate}
END as leave_days 
    FROM HRIS_EMPLOYEE_LEAVE_REQUEST WHERE STATUS IN ('AP','CP','CR') 
    AND END_DATE>{$toDate}
    AND START_DATE<={$toDate}
        UNION ALL
        SELECT
    EMPLOYEE_ID,LEAVE_ID,START_DATE,END_DATE,NO_OF_DAYS,
HALF_DAY,
CASE
WHEN
    half_day = 'F'
OR
    half_day = 'S'
THEN no_of_days/2
ELSE no_of_days
END as leave_days
    FROM HRIS_EMPLOYEE_LEAVE_REQUEST WHERE STATUS IN ('AP','CP','CR') 
    AND START_DATE>{$toDate}
    )
    GROUP BY EMPLOYEE_ID,LEAVE_ID) LTAD ON (LTAD.LEAVE_ID=LA.LEAVE_ID AND LTAD.EMPLOYEE_ID=LA.EMPLOYEE_ID)
    )PIVOT (MAX(TAKEN) AS TAKEN, MAX(CALCULATED_BALANCE) AS BALANCE 
    FOR LEAVE_ID
    IN ({$leaveArrayDb}) )
    )LB LEFT JOIN HRIS_EMPLOYEES E ON (LB.EMPLOYEE_ID=E.EMPLOYEE_ID)
    LEFT JOIN HRIS_DEPARTMENTS D
    ON E.DEPARTMENT_ID=D.DEPARTMENT_ID
    LEFT JOIN HRIS_DESIGNATIONS DES
    ON E.DESIGNATION_ID=DES.DESIGNATION_ID
    LEFT JOIN HRIS_POSITIONS P
    ON E.POSITION_ID=P.POSITION_ID
    LEFT JOIN HRIS_FUNCTIONAL_TYPES FUNT
    ON E.FUNCTIONAL_TYPE_ID=FUNT.FUNCTIONAL_TYPE_ID
    WHERE E.STATUS='E' {$searchCondition['sql']} {$orderByString}";
//    echo $sql;
//    die();
        return EntityHelper::rawQueryResult($this->adapter, $sql,$boundedParameter);
    }

    public function getLeaveTypes() {

        $sql = " SELECT DISTINCT ELA.LEAVE_ID,
  LMS.LEAVE_ENAME
FROM HRIS_EMPLOYEE_LEAVE_ADDITION ELA
LEFT JOIN HRIS_LEAVE_MASTER_SETUP LMS
ON (ELA.LEAVE_ID = LMS.LEAVE_ID)
where LMS.STATUS = 'E' ";

        $statement = $this->adapter->query($sql);
        return $statement->execute();
    }

    public function fetchLeaveAddition($searchQuery) {

        $fromDate = $searchQuery['fromDate'];
        $toDate = $searchQuery['toDate'];
        $leaveId = $searchQuery['leave_id'];
        
        $leaveCondition = " ";
        
        $boundedParameter = [];
        if($leaveId != null){
        $boundedParameter['leaveId']=$leaveId[0];
            $leaveCondition = " AND ELA.LEAVE_ID = :leaveId ";
        }
        
//        $fromCondition = "";
//        $toCondition = "";
//        
//        if($fromDate != null){
//            $fromCondition = " AND ";
//        }

        $searchCondition = EntityHelper::getSearchConditonBounded($searchQuery['companyId'], $searchQuery['branchId'], $searchQuery['departmentId'], $searchQuery['positionId'], $searchQuery['designationId'], $searchQuery['serviceTypeId'], $searchQuery['serviceEventTypeId'], $searchQuery['employeeTypeId'], $searchQuery['employeeId']);

        $boundedParameter=array_merge($boundedParameter, $searchCondition['parameter']);

        $sql = " SELECT E.EMPLOYEE_CODE, E.FULL_NAME, D.DEPARTMENT_NAME, B.BRANCH_NAME, LMS.LEAVE_ENAME, ELA.*,
  CASE
    WHEN ELA.WOD_ID IS NOT NULL
    THEN WD.FROM_DATE ||' - '|| WD.TO_DATE
    WHEN ELA.WOH_ID IS NOT NULL
    THEN WH.FROM_DATE ||' - '|| WH.TO_DATE
    WHEN ELA.TRAVEL_ID IS NOT NULL
    THEN T.FROM_DATE ||' - '|| T.TO_DATE
    WHEN ELA.TRAINING_ID IS NOT NULL
    THEN TR.START_DATE ||' - '|| TR.END_DATE
  END as LEAVE_DATE
FROM HRIS_EMPLOYEE_LEAVE_ADDITION ELA
LEFT JOIN HRIS_EMPLOYEE_WORK_DAYOFF WD
ON (ELA.WOD_ID = WD.ID)
LEFT JOIN HRIS_EMPLOYEE_WORK_HOLIDAY WH
ON (ELA.WOH_ID = WH.ID)
LEFT JOIN HRIS_EMPLOYEE_TRAVEL_REQUEST T
ON (ELA.TRAVEL_ID = T.TRAVEL_ID)
LEFT JOIN HRIS_EMPLOYEE_TRAINING_REQUEST TR
ON (ELA.TRAINING_ID = TR.REQUEST_ID)
LEFT JOIN HRIS_EMPLOYEES E
ON (ELA.EMPLOYEE_ID = E.EMPLOYEE_ID)
LEFT JOIN HRIS_LEAVE_MASTER_SETUP LMS
ON (ELA.LEAVE_ID = LMS.LEAVE_ID)
LEFT JOIN HRIS_DEPARTMENTS D
ON (E.DEPARTMENT_ID = D.DEPARTMENT_ID)
LEFT JOIN HRIS_BRANCHES B
ON (E.BRANCH_ID = B.BRANCH_ID)
where 1=1  {$leaveCondition} {$searchCondition['sql']} and E.STATUS = 'E' 
";

        return $this->rawQuery($sql, $boundedParameter);
        // $statement = $this->adapter->query($sql);
        // $result = $statement->execute();

        // return Helper::extractDbData($result);
    }

    public function fetchLeaveYearMonth() {
        $rowset = $this->leaveMonthTableGateway->select(function (Select $select) {
            $select->columns(Helper::convertColumnDateFormat($this->adapter, new LeaveMonths(), [
                        'fromDate',
                        'toDate',
                    ]), false);

            $select->where([LeaveMonths::STATUS => 'E']);
            $select->order("LEAVE_YEAR_MONTH_NO ASC");
        });
        return $rowset;
    }

    public function getCurrentLeaveMonth() {
        $sql = <<<EOT
            SELECT MONTH_ID,
              LEAVE_YEAR_ID,
              LEAVE_YEAR_MONTH_NO,
              YEAR,
              MONTH_NO,
              MONTH_EDESC,
              MONTH_NDESC,
              FROM_DATE,
              INITCAP(TO_CHAR(FROM_DATE,'DD-MON-YYYY')) AS FROM_DATE_AD,
              BS_DATE(FROM_DATE) AS FROM_DATE_BS,
              TO_DATE ,
              INITCAP(TO_CHAR(TO_DATE,'DD-MON-YYYY')) AS TO_DATE_AD,
              BS_DATE(TO_DATE) AS TO_DATE_BS
            FROM HRIS_LEAVE_MONTH_CODE
            WHERE (select 
            case when trunc(sysdate)>max(to_date) then max(to_date) else trunc(sysdate)
            end
            from HRIS_LEAVE_MONTH_CODE) BETWEEN FROM_DATE AND TO_DATE
EOT;
        $statement = $this->adapter->query($sql);
        $result = $statement->execute();
        return $result->current();
    }

}
