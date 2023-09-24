<?php

namespace SelfService\Repository;

use Application\Repository\HrisRepository;
use LeaveManagement\Model\LeaveAssign;
use Traversable;
use Zend\Db\Adapter\AdapterInterface;

class LeaveRepository extends HrisRepository
{

  public function __construct(AdapterInterface $adapter, $tableName = null)
  {
    if ($tableName == null) {
      $tableName = LeaveAssign::TABLE_NAME;
    }
    parent::__construct($adapter, $tableName);
  }

  function selectAll($employeeId): Traversable
  {
    $sql = "SELECT * FROM (SELECT LA.LEAVE_ID,
                  LMS.LEAVE_CODE,
                  LMS.LEAVE_ENAME,
                  LA.PREVIOUS_YEAR_BAL,
                  LA.TOTAL_DAYS,
                  LA.BALANCE,
                  (SELECT SUM(ELR.NO_OF_DAYS/(
                    CASE
                      WHEN ELR.HALF_DAY IN ('F','S')
                      THEN 2
                      ELSE 1
                    END))
                  FROM HRIS_EMPLOYEE_LEAVE_REQUEST ELR
                   LEFT JOIN (SELECT * FROM HRIS_LEAVE_YEARS  WHERE TRUNC(SYSDATE) BETWEEN START_DATE AND END_DATE ) LY ON (1=1)
                  WHERE ELR.LEAVE_ID =LA.LEAVE_ID
                  AND ELR.EMPLOYEE_ID=LA.EMPLOYEE_ID
                  AND ELR.STATUS IN ('AP','CP','CR')
                   AND ELR.START_DATE BETWEEN LY.START_DATE AND LY.END_DATE
                  ) AS LEAVE_TAKEN,
                  (SELECT SUM(HS.ENCASH_DAYS)
                  FROM HRIS_EMP_SELF_LEAVE_CLOSING HS
                  WHERE HS.EMPLOYEE_ID=LA.EMPLOYEE_ID
                  AND HS.LEAVE_ID     =LA.LEAVE_ID
                  ) AS ENCASHED,
                  (SELECT SUM(EPD.NO_OF_DAYS)
                  FROM HRIS_EMPLOYEE_PENALTY_DAYS EPD
                  WHERE EPD.EMPLOYEE_ID=LA.EMPLOYEE_ID
                  AND EPD.LEAVE_ID     =LA.LEAVE_ID
                  ) AS LEAVE_DEDUCTED,
                  (SELECT SUM(ELA.NO_OF_DAYS)
                  FROM HRIS_EMPLOYEE_LEAVE_ADDITION ELA
                  WHERE ELA.EMPLOYEE_ID=LA.EMPLOYEE_ID
                  AND ELA.LEAVE_ID     =LA.LEAVE_ID
                  ) AS LEAVE_ADDED
                FROM HRIS_EMPLOYEE_LEAVE_ASSIGN LA
                LEFT JOIN HRIS_LEAVE_MASTER_SETUP LMS
                ON (LA.LEAVE_ID     =LMS.LEAVE_ID)
                WHERE LA.EMPLOYEE_ID=:employeeId AND LMS.STATUS ='E' AND LMS.IS_MONTHLY = 'N' ORDER BY LMS.LEAVE_ENAME ASC)";
    //echo $sql; die;
    $boundedParameter = [];
    $boundedParameter['employeeId'] = $employeeId;
    // echo '<pre>';print_r($sql);die;
    $statement = $this->adapter->query($sql);
    return $statement->execute($boundedParameter);
  }

  function monthlyLeaveStatus($employeeId, $fiscalYearMonthNo)
  {
    $sql = "SELECT * FROM (SELECT LA.LEAVE_ID,
                  LMS.LEAVE_CODE,
                  LMS.LEAVE_ENAME,
                  LA.TOTAL_DAYS,
                  LA.PREVIOUS_YEAR_BAL,
                  LA.BALANCE,
                  (SELECT SUM(ELR.NO_OF_DAYS/(
                    CASE
                      WHEN ELR.HALF_DAY IN ('F','S')
                      THEN 2
                      ELSE 1
                    END))
                  FROM HRIS_EMPLOYEE_LEAVE_REQUEST ELR
                  WHERE ELR.LEAVE_ID =LA.LEAVE_ID
                  AND ELR.EMPLOYEE_ID=LA.EMPLOYEE_ID
                  AND ELR.STATUS     ='AP'
                  AND ELR.START_DATE BETWEEN MTH.FROM_DATE AND MTH.TO_DATE
                  ) AS LEAVE_TAKEN
                FROM HRIS_EMPLOYEE_LEAVE_ASSIGN LA
                LEFT JOIN HRIS_LEAVE_MASTER_SETUP LMS
                ON (LA.LEAVE_ID =LMS.LEAVE_ID)
                LEFT JOIN (SELECT * FROM HRIS_LEAVE_MONTH_CODE WHERE 
                LEAVE_YEAR_ID=(SELECT LEAVE_YEAR_ID FROM HRIS_LEAVE_YEARS 
                WHERE TRUNC(SYSDATE) BETWEEN START_DATE AND END_DATE)) MTH
                ON (MTH.LEAVE_YEAR_MONTH_NO= LA.FISCAL_YEAR_MONTH_NO)
                WHERE LA.EMPLOYEE_ID        =:employeeId
                AND LA.FISCAL_YEAR_MONTH_NO =:fiscalYearMonthNo
                AND LMS.STATUS              ='E'
                AND LMS.IS_MONTHLY          = 'Y'
                AND LMS.CARRY_FORWARD          = 'N'
                ORDER BY LMS.LEAVE_ENAME ASC)
                UNION ALL
                SELECT * FROM (SELECT LA.LEAVE_ID,
                  LMS.LEAVE_CODE,
                  LMS.LEAVE_ENAME,
                  LA.TOTAL_DAYS,
                  LA.PREVIOUS_YEAR_BAL,
                  LA.BALANCE,
                  (SELECT SUM(ELR.NO_OF_DAYS/(
                    CASE
                      WHEN ELR.HALF_DAY IN ('F','S')
                      THEN 2
                      ELSE 1
                    END))
                  FROM HRIS_EMPLOYEE_LEAVE_REQUEST ELR
                  WHERE ELR.LEAVE_ID =LA.LEAVE_ID
                  AND ELR.EMPLOYEE_ID=LA.EMPLOYEE_ID
                  AND ELR.STATUS     ='AP'
                  AND ELR.START_DATE <= MTH.TO_DATE
                  ) AS LEAVE_TAKEN
                FROM HRIS_EMPLOYEE_LEAVE_ASSIGN LA
                LEFT JOIN HRIS_LEAVE_MASTER_SETUP LMS
                ON (LA.LEAVE_ID =LMS.LEAVE_ID)
                LEFT JOIN (SELECT * FROM HRIS_LEAVE_MONTH_CODE WHERE 
                LEAVE_YEAR_ID=(SELECT LEAVE_YEAR_ID FROM HRIS_LEAVE_YEARS 
                WHERE TRUNC(SYSDATE) BETWEEN START_DATE AND END_DATE)) MTH
                ON (MTH.LEAVE_YEAR_MONTH_NO= LA.FISCAL_YEAR_MONTH_NO)
                WHERE LA.EMPLOYEE_ID        =:employeeId
                AND LA.FISCAL_YEAR_MONTH_NO =:fiscalYearMonthNo
                AND LMS.STATUS              ='E'
                AND LMS.IS_MONTHLY          = 'Y'
                AND LMS.CARRY_FORWARD          = 'Y'
                ORDER BY LMS.LEAVE_ENAME ASC) ";

    $boundedParameter = [];
    $boundedParameter['employeeId'] = $employeeId;
    $boundedParameter['fiscalYearMonthNo'] = $fiscalYearMonthNo;

    $statement = $this->adapter->query($sql);
    return $statement->execute($boundedParameter);
  }
}
