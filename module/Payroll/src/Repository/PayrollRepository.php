<?php

namespace Payroll\Repository;

use Application\Helper\EntityHelper;
use Application\Helper\Helper;
use Application\Repository\HrisRepository;
use Exception;
use Zend\Db\Adapter\AdapterInterface;

class PayrollRepository extends HrisRepository {

    public function __construct(AdapterInterface $adapter, $tableName = null) {
        parent::__construct($adapter, $tableName);
    }

    public function fetchBasicSalary($employeeId, $sheetNo) {
        $boundedParameter = [];
        $boundedParameter['employeeId'] = $employeeId;
        $boundedParameter['sheetNo'] = $sheetNo;
        $sql = "
                SELECT NVL(SALARY,0) AS SALARY
                FROM HRIS_SALARY_SHEET_EMP_DETAIL
                WHERE EMPLOYEE_ID=:employeeId
                AND SHEET_NO = :sheetNo
                ";
        $resultList = $this->rawQuery($sql, $boundedParameter);
        if (!(sizeof($resultList) == 1)) {
            throw new Exception('No Report Found.');
        }
        return $resultList[0]['SALARY'];
    }

    public function getMonthDays($employeeId, $sheetNo) {
        $boundedParameter = [];
        $boundedParameter['employeeId'] = $employeeId;
        $boundedParameter['sheetNo'] = $sheetNo;
        $sql = "
            SELECT TOTAL_DAYS AS MONTH_DAYS
            FROM HRIS_SALARY_SHEET_EMP_DETAIL
            WHERE SHEET_NO=:sheetNo AND EMPLOYEE_ID=:employeeId
                ";
        $resultList = $this->rawQuery($sql, $boundedParameter);
        if (!(sizeof($resultList) == 1)) {
            throw new Exception('No Report Found.');
        }
        return $resultList[0]['MONTH_DAYS'];
    }

    public function getPresentDays($employeeId, $sheetNo) {
        $boundedParameter = [];
        $boundedParameter['employeeId'] = $employeeId;
        $boundedParameter['sheetNo'] = $sheetNo;
        $sql = "SELECT PRESENT AS PRESENT_DAYS
                FROM HRIS_SALARY_SHEET_EMP_DETAIL WHERE EMPLOYEE_ID = :employeeId AND SHEET_NO = :sheetNo";
        $resultList = $this->rawQuery($sql, $boundedParameter);
        if (!(sizeof($resultList) == 1)) {
            throw new Exception('Result not found.');
        }
        return $resultList[0]['PRESENT_DAYS'];
    }

    public function getAbsentDays($employeeId, $sheetNo) {
        $boundedParameter = [];
        $boundedParameter['employeeId'] = $employeeId;
        $boundedParameter['sheetNo'] = $sheetNo;
        $sql = "SELECT ABSENT AS ABSENT_DAYS
                FROM HRIS_SALARY_SHEET_EMP_DETAIL WHERE EMPLOYEE_ID = :employeeId AND SHEET_NO = :sheetNo";
        $resultList = $this->rawQuery($sql, $boundedParameter);
        if (!(sizeof($resultList) == 1)) {
            throw new Exception('Result not found.');
        }
        return $resultList[0]['ABSENT_DAYS'];
    }

    public function getPaidLeaves($employeeId, $sheetNo) {
        $boundedParameter = [];
        $boundedParameter['employeeId'] = $employeeId;
        $boundedParameter['sheetNo'] = $sheetNo;
        $sql = "SELECT PAID_LEAVE AS PAID_LEAVE
                FROM HRIS_SALARY_SHEET_EMP_DETAIL WHERE EMPLOYEE_ID = :employeeId AND SHEET_NO = :sheetNo";
        $resultList = $this->rawQuery($sql, $boundedParameter);
        if (!(sizeof($resultList) == 1)) {
            throw new Exception('Result not found.');
        }
        return $resultList[0]['PAID_LEAVE'];
    }

    public function getUnpaidLeaves($employeeId, $sheetNo) {
        $boundedParameter = [];
        $boundedParameter['employeeId'] = $employeeId;
        $boundedParameter['sheetNo'] = $sheetNo;
        $sql = "SELECT UNPAID_LEAVE AS UNPAID_LEAVE
                FROM HRIS_SALARY_SHEET_EMP_DETAIL WHERE EMPLOYEE_ID = :employeeId AND SHEET_NO = :sheetNo";
        $resultList = $this->rawQuery($sql, $boundedParameter);
        if (!(sizeof($resultList) == 1)) {
            throw new Exception('Result not found.');
        }
        return $resultList[0]['UNPAID_LEAVE'];
    }

    public function getDayoffs($employeeId, $sheetNo) {
        $boundedParameter = [];
        $boundedParameter['employeeId'] = $employeeId;
        $boundedParameter['sheetNo'] = $sheetNo;
        $sql = "SELECT DAYOFF AS DAYOFF
                FROM HRIS_SALARY_SHEET_EMP_DETAIL WHERE EMPLOYEE_ID = :employeeId AND SHEET_NO = :sheetNo";
        $resultList = $this->rawQuery($sql, $boundedParameter);
        if (!(sizeof($resultList) == 1)) {
            throw new Exception('Result not found.');
        }
        return $resultList[0]['DAYOFF'];
    }

    public function getHolidays($employeeId, $sheetNo) {
        $boundedParameter = [];
        $boundedParameter['employeeId'] = $employeeId;
        $boundedParameter['sheetNo'] = $sheetNo;
        $sql = "SELECT HOLIDAY
                FROM HRIS_SALARY_SHEET_EMP_DETAIL WHERE EMPLOYEE_ID = :employeeId AND SHEET_NO = :sheetNo";
        $resultList = $this->rawQuery($sql, $boundedParameter);
        if (!(sizeof($resultList) == 1)) {
            throw new Exception('Result not found.');
        }
        return $resultList[0]['HOLIDAY'];
    }

    public function getDaysFromJoinDate($employeeId, $sheetNo) {
        $boundedParameter = [];
        $boundedParameter['employeeId'] = $employeeId;
        $boundedParameter['sheetNo'] = $sheetNo;
        $sql = "SELECT (TRUNC(START_DATE)-TRUNC(JOIN_DATE))+1 AS DAYS_FROM_JOIN_DATE
                FROM HRIS_SALARY_SHEET_EMP_DETAIL WHERE EMPLOYEE_ID = :employeeId AND SHEET_NO = :sheetNo";
        $resultList = $this->rawQuery($sql, $boundedParameter);
        if (!(sizeof($resultList) == 1)) {
            throw new Exception('Result not found.');
        }
        return $resultList[0]['DAYS_FROM_JOIN_DATE'];
    }

    public function getDaysFromPermanentDate($employeeId, $monthId) {
        $boundedParameter = [];
        $boundedParameter['employeeId'] = $employeeId;
        $boundedParameter['monthId'] = $monthId;
        $sql = "
                SELECT (TRUNC(M.FROM_DATE)- TRUNC(PERMANENT_DATE)) AS DAYS_FROM_PERMANENT_DATE
                FROM HRIS_EMPLOYEES E,
                  (SELECT FROM_DATE,TO_DATE FROM HRIS_MONTH_CODE WHERE MONTH_ID=:monthId
                  ) M WHERE E.EMPLOYEE_ID=:employeeId
                ";
        $rawResult = $this->rawQuery($sql, $boundedParameter);
        return $rawResult[0]['DAYS_FROM_PERMANENT_DATE'];
    }

    public function isMale($employeeId, $sheetNo) {
        $boundedParameter = [];
        $boundedParameter['employeeId'] = $employeeId;
        $boundedParameter['sheetNo'] = $sheetNo;
        $sql = "SELECT (CASE WHEN GENDER_CODE = 'M' THEN 1 ELSE 0 END) AS IS_MALE
                FROM HRIS_SALARY_SHEET_EMP_DETAIL WHERE EMPLOYEE_ID = :employeeId AND SHEET_NO = :sheetNo";
        $resultList = $this->rawQuery($sql, $boundedParameter);
        if (!(sizeof($resultList) == 1)) {
            throw new Exception('Result not found.');
        }
        return $resultList[0]['IS_MALE'];
    }

    public function isFemale($employeeId, $sheetNo) {
        $boundedParameter = [];
        $boundedParameter['employeeId'] = $employeeId;
        $boundedParameter['sheetNo'] = $sheetNo;
        $sql = "SELECT (CASE WHEN GENDER_CODE = 'F' THEN 1 ELSE 0 END) AS IS_FEMALE
                FROM HRIS_SALARY_SHEET_EMP_DETAIL WHERE EMPLOYEE_ID = :employeeId AND SHEET_NO = :sheetNo";
        $resultList = $this->rawQuery($sql, $boundedParameter);
        if (!(sizeof($resultList) == 1)) {
            throw new Exception('Result not found.');
        }
        return $resultList[0]['IS_FEMALE'];
    }

    public function isMarried($employeeId, $sheetNo) {
        $boundedParameter = [];
        $boundedParameter['employeeId'] = $employeeId;
        $boundedParameter['sheetNo'] = $sheetNo;
        $sql = "SELECT (CASE WHEN MARITAL_STATUS = 'M' THEN 1 ELSE 0 END) AS IS_MARRIED
                FROM HRIS_SALARY_SHEET_EMP_DETAIL WHERE EMPLOYEE_ID = :employeeId AND SHEET_NO = :sheetNo";
        $resultList = $this->rawQuery($sql, $boundedParameter);
        if (!(sizeof($resultList) == 1)) {
            throw new Exception('Result not found.');
        }
        return $resultList[0]['IS_MARRIED'];
    }

    public function isPermanent($employeeId, $sheetNo) {
        $boundedParameter = [];
        $boundedParameter['employeeId'] = $employeeId;
        $boundedParameter['sheetNo'] = $sheetNo;
        $sql = "SELECT (
                  CASE
                    WHEN (PERMANENT_FLAG ='Y'
                    AND ( PERMANENT_DATE IS NULL OR PERMANENT_DATE <= START_DATE))
                    THEN 1
                    ELSE 0
                  END) AS IS_PERMANENT
                FROM HRIS_SALARY_SHEET_EMP_DETAIL
                WHERE EMPLOYEE_ID = :employeeId
                AND SHEET_NO      = :sheetNo";
        $resultList = $this->rawQuery($sql, $boundedParameter);
        if (!(sizeof($resultList) == 1)) {
            throw new Exception('Result not found.');
        }
        return $resultList[0]['IS_PERMANENT'];
    }

    public function isProbation($employeeId, $monthId) {
        $boundedParameter = [];
        $boundedParameter['employeeId'] = $employeeId;
        $boundedParameter['monthId'] = $monthId;
        $sql = "
                SELECT (
                  CASE
                    WHEN TO_SERVICE_TYPE_ID =2
                    THEN 1
                    ELSE 0
                  END) AS IS_PERMANENT
                FROM
                  (SELECT *
                  FROM
                    (SELECT JH.*
                    FROM HRIS_JOB_HISTORY JH,
                      (SELECT * FROM HRIS_MONTH_CODE WHERE MONTH_ID = :monthId
                      ) M
                    WHERE JH.EMPLOYEE_ID = :employeeId
                    AND JH.START_DATE   <= M.FROM_DATE
                    ORDER BY JH.START_DATE DESC
                    )
                  WHERE ROWNUM =1
                  )           
                ";
        $rawResult = $this->rawQuery($sql, $boundedParameter);
        $result = $rawResult[0];
        if ($result == null) {
            return 0;
        }
        return $result['IS_PERMANENT'];
    }

    public function isContract($employeeId, $monthId) {
        $boundedParameter = [];
        $boundedParameter['employeeId'] = $employeeId;
        $boundedParameter['monthId'] = $monthId;
        $sql = "
                SELECT (
                  CASE
                    WHEN TYPE ='CONTRACT'
                    THEN 1
                    ELSE 0
                  END) AS IS_PERMANENT
                FROM
                  (SELECT *
                  FROM
                    (SELECT JH.*,ST.TYPE
                    FROM HRIS_JOB_HISTORY JH
                     left join Hris_Service_Types ST ON (ST.SERVICE_TYPE_ID=JH.TO_SERVICE_TYPE_ID),
                      (SELECT * FROM HRIS_MONTH_CODE WHERE MONTH_ID = :monthId
                      ) M
                    WHERE JH.EMPLOYEE_ID = :employeeId
                    AND JH.START_DATE   <= M.FROM_DATE
                    ORDER BY JH.START_DATE DESC
                    )
                  WHERE ROWNUM =1
                  )           
                ";
        $rawResult = $this->rawQuery($sql, $boundedParameter);
        $result = $rawResult[0];
        if ($result == null) {
            return 0;
        }
        return $result['IS_PERMANENT'];
    }

    public function isTemporary($employeeId, $monthId) {
        $boundedParameter = [];
        $boundedParameter['employeeId'] = $employeeId;
        $boundedParameter['monthId'] = $monthId;
        $sql = "
                SELECT (
                  CASE
                    WHEN TO_SERVICE_TYPE_ID =4
                    THEN 1
                    ELSE 0
                  END) AS IS_PERMANENT
                FROM
                  (SELECT *
                  FROM
                    (SELECT JH.*
                    FROM HRIS_JOB_HISTORY JH,
                      (SELECT * FROM HRIS_MONTH_CODE WHERE MONTH_ID = :monthId
                      ) M
                    WHERE JH.EMPLOYEE_ID = :employeeId
                    AND JH.START_DATE   <= M.FROM_DATE
                    ORDER BY JH.START_DATE DESC
                    )
                  WHERE ROWNUM =1
                  )           
                ";
        $rawResult = $this->rawQuery($sql, $boundedParameter);
        $result = $rawResult[0];
        if ($result == null) {
            return 0;
        }
        return $result['IS_PERMANENT'];
    }

    public function getWorkedDays($employeeId, $sheetNo) {
        $boundedParameter = [];
        $boundedParameter['employeeId'] = $employeeId;
        $boundedParameter['sheetNo'] = $sheetNo;
        $sql = "SELECT PRESENT+DAYOFF+HOLIDAY+PAID_LEAVE+TRAVEL+TRAINING AS WORKED_DAYS
                FROM HRIS_SALARY_SHEET_EMP_DETAIL WHERE EMPLOYEE_ID = :employeeId AND SHEET_NO = :sheetNo";
        $resultList = $this->rawQuery($sql, $boundedParameter);
        if (!(sizeof($resultList) == 1)) {
            throw new Exception('Result not found.');
        }
        return $resultList[0]['WORKED_DAYS'];
    }

    public function fetchEmployeeList() {
        $sql = "
                SELECT E.EMPLOYEE_ID, E.GROUP_ID,
                  E.EMPLOYEE_CODE || '-' || CONCAT(CONCAT(CONCAT(INITCAP(TRIM(E.FIRST_NAME)),' '),
                  CASE
                    WHEN E.MIDDLE_NAME IS NOT NULL
                    THEN CONCAT(INITCAP(TRIM(E.MIDDLE_NAME)), ' ')
                    ELSE ''
                  END ),INITCAP(TRIM(E.LAST_NAME))) AS FULL_NAME
                FROM HRIS_EMPLOYEES E
                WHERE E.JOIN_DATE <= TRUNC(SYSDATE)
                AND E.RETIRED_FLAG ='N'
                AND IS_ADMIN       ='N'
                AND STATUS         ='E'
                ";
        $employeeListRaw = EntityHelper::rawQueryResult($this->adapter, $sql);
        return Helper::extractDbData($employeeListRaw);
    }

    public function getBranchAllowance($employeeId) {
        $boundedParameter = [];
        $boundedParameter['employeeId'] = $employeeId;
        $sql = "SELECT ALLOWANCE FROM HRIS_BRANCHES WHERE 
                BRANCH_ID=(SELECT  BRANCH_ID FROM HRIS_EMPLOYEES WHERE EMPLOYEE_ID=:employeeId)";
        $resultList = $this->rawQuery($sql, $boundedParameter);
        if (!(sizeof($resultList) == 1)) {
            throw new Exception('No Report Found.');
        }
        return $resultList[0]['ALLOWANCE'];
    }

    public function getMonthNo($monthId) {
        $boundedParameter = [];
        $boundedParameter['monthId'] = $monthId;
        $sql = "select FISCAL_YEAR_MONTH_NO from hris_month_code where month_id=:monthId";
        $resultList = $this->rawQuery($sql, $boundedParameter);
        if (!(sizeof($resultList) == 1)) {
            throw new Exception('Result not found.');
        }
        return $resultList[0]['FISCAL_YEAR_ID'];
    }

    
     public function getBranch($employeeId) {
        $boundedParameter = [];
        $boundedParameter['employeeId'] = $employeeId;
        $sql = "SELECT BRANCH_ID FROM HRIS_EMPLOYEES WHERE  EMPLOYEE_ID=:employeeId";
        $resultList = $this->rawQuery($sql, $boundedParameter);
        if (!(sizeof($resultList) == 1)) {
            throw new Exception('No Report Found.');
        }
        return $resultList[0]['BRANCH_ID'];
    }

    public function getCafeMealPrevious($employeeId, $monthId){
        $boundedParameter = [];
        $boundedParameter['employeeId'] = $employeeId;
        $boundedParameter['monthId'] = $monthId;
        $sql = "select case
when sum(total_amount) is not null
then sum(total_amount)
else 0 END as AMT
 from (SELECT
    hcms.menu_name AS menu_name,
    e.employee_id AS employee_id,
    e.full_name AS full_name,
    SUM(held.quantity) AS quantity,
    SUM(held.total_amount) AS total_amount
FROM
    hris_cafeteria_log_detail held
    JOIN hris_employees e ON (
        e.employee_id = held.employee_id
    )
    JOIN hris_cafeteria_menu_setup hcms ON (
        held.menu_code = hcms.menu_id
    )
    left join (select * from 
(
select to_char( add_months (from_date,-1),'DD-Mon-YY') as from_date
, to_char( add_months (to_date,-1),'DD-Mon-YY') as to_date
from hris_month_code where month_id=:monthId
)) mc on (1=1)
WHERE
held.log_date BETWEEN mc.from_date AND mc.to_date and 
e.employee_id=:employeeId
GROUP BY
    hcms.menu_name,
    e.employee_id,
    e.full_name)";
        $resultList = $this->rawQuery($sql, $boundedParameter);
        if (!(sizeof($resultList) == 1)) {
            throw new Exception('No Report Found.');
        }
        return $resultList[0]['AMT'];
    }
    
    public function getCafeMealCurrent($employeeId, $monthId){
        $boundedParameter = [];
        $boundedParameter['employeeId'] = $employeeId;
        $boundedParameter['monthId'] = $monthId;
        $sql = "select case
when sum(total_amount) is not null
then sum(total_amount)
else 0 END as AMT
 from (SELECT
    hcms.menu_name AS menu_name,
    e.employee_id AS employee_id,
    e.full_name AS full_name,
    SUM(held.quantity) AS quantity,
    SUM(held.total_amount) AS total_amount
FROM
    hris_cafeteria_log_detail held
    JOIN hris_employees e ON (
        e.employee_id = held.employee_id
    )
    JOIN hris_cafeteria_menu_setup hcms ON (
        held.menu_code = hcms.menu_id
    )
    left join hris_month_code mc on (month_id=:monthId)
WHERE
held.log_date BETWEEN mc.from_date AND mc.to_date and 
e.employee_id=:employeeId
GROUP BY
    hcms.menu_name,
    e.employee_id,
    e.full_name)";
        $resultList = $this->rawQuery($sql, $boundedParameter);
        if (!(sizeof($resultList) == 1)) {
            throw new Exception('No Report Found.');
        }
        return $resultList[0]['AMT'];
    }
    
    
    public function getPayEmpType($employeeId){
        $boundedParameter = [];
        $boundedParameter['employeeId'] = $employeeId;
        $sql = "SELECT PAY_EMP_TYPE FROM HRIS_EMPLOYEES WHERE  EMPLOYEE_ID=:employeeId";
        $resultList = $this->rawQuery($sql, $boundedParameter);
        if (!(sizeof($resultList) == 1)) {
            throw new Exception('No Report Found.');
        }
        return $resultList[0]['PAY_EMP_TYPE'];
    }
    
     public function getEmployeeServiceId($employeeId, $sheetNo){
        $boundedParameter = [];
        $boundedParameter['employeeId'] = $employeeId;
        $boundedParameter['sheetNo'] = $sheetNo;
        $sql = "SELECT NVL(SERVICE_TYPE_ID,-1) AS SERVICE_TYPE_ID
            FROM HRIS_SALARY_SHEET_EMP_DETAIL
            WHERE SHEET_NO= :sheetNo AND EMPLOYEE_ID=:employeeId";
        $resultList = $this->rawQuery($sql, $boundedParameter);
        if (!(sizeof($resultList) == 1)) {
            throw new Exception('No Report Found.');
        }
        return $resultList[0]['SERVICE_TYPE_ID'];
    }
    
    public function getserviceTypePf($employeeId, $sheetNo){
        $boundedParameter = [];
        $boundedParameter['employeeId'] = $employeeId;
//        $boundedParameter['sheetNo'] = $sheetNo;
        $sql = "select CASE WHEN 
E.SERVICE_TYPE_ID IS NOT NULL 
THEN S.PF_ELIGIBLE
ELSE 
'N'
END AS PF_ELIGIBLE
FROM hris_employees E
LEFT JOIN HRIS_SERVICE_TYPES S ON (E.SERVICE_TYPE_ID=S.SERVICE_TYPE_ID)
WHERE E.EMPLOYEE_ID=:employeeId";
        $resultList = $this->rawQuery($sql, $boundedParameter);
        if (!(sizeof($resultList) == 1)) {
            throw new Exception('No Report Found.');
        }
        return $resultList[0]['PF_ELIGIBLE'];
    }
    
    public function getDisablePersonFlag($employeeId){
        $boundedParameter = [];
        $boundedParameter['employeeId'] = $employeeId;
        $sql = "SELECT 
CASE WHEN
DISABLED_FLAG ='Y'
THEN
1
ELSE
0
END AS DISABLED_FLAG FROM HRIS_EMPLOYEES WHERE  EMPLOYEE_ID=:employeeId";
        $resultList = $this->rawQuery($sql, $boundedParameter);
        if (!(sizeof($resultList) == 1)) {
            throw new Exception('No Report Found.');
        }
        return $resultList[0]['DISABLED_FLAG'];
    }
    
    public function getPreviousMonthDays($monthId){
        $boundedParameter = [];
        $boundedParameter['monthId'] = $monthId;
        $sql = "SELECT 
(TO_DATE-FROM_DATE) +1 as PRE_MONTH_DAYS
FROM HRIS_MONTH_CODE  where MONTH_ID=(:monthId-1)";
        $resultList = $this->rawQuery($sql, $boundedParameter);
        if (!(sizeof($resultList) == 1)) {
            throw new Exception('No Report Found.');
        }
        return $resultList[0]['PRE_MONTH_DAYS'];
        
    }
    
    public function getBranchAllowanceRebate($employeeId){
         $boundedParameter = [];
        $boundedParameter['employeeId'] = $employeeId;
        $sql = "SELECT NVL(ALLOWANCE_REBATE,0) AS ALLOWANCE_REBATE FROM HRIS_BRANCHES WHERE 
                BRANCH_ID=(SELECT  BRANCH_ID FROM HRIS_EMPLOYEES WHERE EMPLOYEE_ID=:employeeId)";
        $resultList = $this->rawQuery($sql, $boundedParameter);
        if (!(sizeof($resultList) == 1)) {
            throw new Exception('No Report Found.');
        }
        return $resultList[0]['ALLOWANCE_REBATE'];
    }
    
    public function getRemoteBranch($employeeId){
        $boundedParameter = [];
        $boundedParameter['employeeId'] = $employeeId;
        $sql = "SELECT IS_REMOTE FROM HRIS_BRANCHES WHERE 
                BRANCH_ID=(SELECT  BRANCH_ID FROM HRIS_EMPLOYEES WHERE EMPLOYEE_ID=:employeeId)";
        $resultList = $this->rawQuery($sql, $boundedParameter);
        if (!(sizeof($resultList) == 1)) {
            throw new Exception('No Report Found.');
        }
        return $resultList[0]['IS_REMOTE'];
    }

    public function getAge($employeeId){
        $boundedParameter = [];
        $boundedParameter['employeeId'] = $employeeId;
        $sql = "SELECT 
                TO_CHAR(BIRTH_DATE, 'yyyy-MON-dd')           AS BIRTH_DATE,
                TRUNC(months_between(sysdate,BIRTH_DATE)/12) AS AGE
                FROM HRIS_EMPLOYEES 
                WHERE EMPLOYEE_ID=:employeeId";
        $resultList = $this->rawQuery($sql, $boundedParameter);
        if (!(sizeof($resultList) == 1)) {
            throw new Exception('No Report Found.');
        }
        return $resultList[0]['AGE'];
    }
	
	public function getSalaryDays($employeeId, $monthId){
		$boundedParameter = [];
        $boundedParameter['employeeId'] = $employeeId;
        $sql = "select count(*) as SALARY_DAYS from hris_attendance_detail where employee_id = $employeeId
and attendance_dt between (select from_date from hris_month_code where month_id = $monthId)
and (select to_date from hris_month_code where month_id = $monthId)
and overall_status not in ('AB')";
//print_r($sql);die;
        $resultList = $this->rawQuery($sql);
        if (!(sizeof($resultList) == 1)) {
            throw new Exception('No Report Found.');
        }
        return $resultList[0]['SALARY_DAYS'];
	}

    public function getMonthlyOTHours($employeeId,$sheetNo,$monthId)
    {
        $sql="select nvl(overtime_hour,0) as OT_WORKED_HOURS from Hris_Salary_Sheet_Emp_Detail where employee_id=$employeeId and 
        month_id=$monthId and sheet_no=$sheetNo
        ";
        $resultList = $this->rawQuery($sql);
        if (!(sizeof($resultList) == 1)) {
            throw new Exception('No Report Found.');
        }
        return $resultList[0]['OT_WORKED_HOURS'];
    }
    
}
