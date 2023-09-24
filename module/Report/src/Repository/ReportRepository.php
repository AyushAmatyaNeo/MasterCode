<?php

namespace Report\Repository;

use Application\Helper\EntityHelper;
use Application\Helper\Helper;
use Application\Model\FiscalYear;
use Application\Model\Months;
use Application\Repository\HrisRepository;
use LeaveManagement\Model\LeaveMaster;
use Zend\Db\Sql\Expression;
use Zend\Db\Sql\Sql;

class ReportRepository extends HrisRepository
{

  public function employeeWiseDailyReport($employeeId)
  {
    $sql = <<<EOT
            SELECT R.*,
              M.MONTH_EDESC,
              TRUNC(R.ATTENDANCE_DT)-TRUNC(M.FROM_DATE)+1 AS DAY_COUNT
            FROM
              (SELECT AD.ATTENDANCE_DT                AS ATTENDANCE_DT,
                TO_CHAR(AD.ATTENDANCE_DT,'MONDDYYYY') AS FORMATTED_ATTENDANCE_DT,
                (SELECT M.MONTH_ID
                FROM HRIS_MONTH_CODE M
                WHERE AD.ATTENDANCE_DT BETWEEN M.FROM_DATE AND M.TO_DATE
                ) AS MONTH_ID,
                (
              CASE 
                WHEN AD.DAYOFF_FLAG ='N'
                AND AD.HOLIDAY_ID  IS NULL
                AND AD.TRAINING_ID IS NULL
                AND AD.TRAVEL_ID   IS NULL
                AND AD.IN_TIME     IS NULL
                AND AD.LEAVE_ID IS NOT NULL
                THEN 1
                ELSE 0
              END) AS ON_LEAVE,
                (
              CASE
                WHEN AD.DAYOFF_FLAG ='N'
                AND AD.LEAVE_ID    IS NULL
                AND AD.HOLIDAY_ID  IS NULL
                AND AD.TRAINING_ID IS NULL
                AND AD.TRAVEL_ID   IS NULL
                AND AD.IN_TIME     IS NOT NULL
                THEN 1
                ELSE 0
              END) AS IS_PRESENT,
                (
              CASE
                WHEN AD.DAYOFF_FLAG ='N'
                AND AD.LEAVE_ID   IS NULL
                AND AD.HOLIDAY_ID  IS NULL
                AND AD.TRAINING_ID IS NULL
                AND AD.TRAVEL_ID   IS NULL
                AND AD.IN_TIME     IS NULL
                THEN 1
                ELSE 0
              END) AS IS_ABSENT,
                (
              CASE
                WHEN AD.LEAVE_ID   IS NULL
                AND AD.HOLIDAY_ID  IS NULL
                AND AD.TRAINING_ID IS NULL
                AND AD.TRAVEL_ID   IS NULL
                AND AD.IN_TIME     IS NULL 
                  AND  AD.DAYOFF_FLAG='Y'
                THEN 1
                ELSE 0
              END) AS IS_DAYOFF
              FROM HRIS_ATTENDANCE_DETAIL AD
              WHERE AD.EMPLOYEE_ID = :employeeId
              ) R
            JOIN HRIS_MONTH_CODE M
            ON (M.MONTH_ID = R.MONTH_ID)
EOT;

    $boundedParameter = [];
    $boundedParameter['employeeId'] = $employeeId;
    return $this->rawQuery($sql, $boundedParameter);
    // $statement = $this->adapter->query($sql);
    // $result = $statement->execute();
    // return Helper::extractDbData($result);
  }

  public function departmentWiseDailyReport(int $monthId, int $departmentId = null, int $branchId = null)
  {
    $sql = <<<EOT
                      SELECT 
                      TRUNC(AD.ATTENDANCE_DT)-TRUNC(M.FROM_DATE)+1                              AS DAY_COUNT, 
                      E.EMPLOYEE_ID                                                             AS EMPLOYEE_ID ,
                      E.FIRST_NAME                                                                   AS FIRST_NAME,
                      E.EMPLOYEE_CODE                                                                   AS EMPLOYEE_CODE,
                      E.MIDDLE_NAME                                                                  AS MIDDLE_NAME,
                      E.LAST_NAME                                                                    AS LAST_NAME,
                      CONCAT(CONCAT(CONCAT(E.FIRST_NAME,' '),CONCAT(E.MIDDLE_NAME, '')),E.LAST_NAME) AS FULL_NAME,
                      AD.ATTENDANCE_DT                                                               AS ATTENDANCE_DT,
                      (
                      CASE 
                        WHEN AD.DAYOFF_FLAG ='N'
                        AND AD.HOLIDAY_ID  IS NULL
                        AND AD.TRAINING_ID IS NULL
                        AND AD.TRAVEL_ID   IS NULL
                        AND AD.IN_TIME     IS NULL
                        AND AD.LEAVE_ID IS NOT NULL
                        THEN 1
                        ELSE 0
                      END) AS ON_LEAVE,
                      (
                      CASE
                        WHEN AD.DAYOFF_FLAG ='N'
                        AND AD.LEAVE_ID    IS NULL
                        AND AD.HOLIDAY_ID  IS NULL
                        AND AD.TRAINING_ID IS NULL
                        AND AD.TRAVEL_ID   IS NULL
                        AND AD.IN_TIME     IS NOT NULL
                        THEN 1
                        ELSE 0
                      END) AS IS_PRESENT,
                      (
                      CASE
                        WHEN AD.DAYOFF_FLAG ='N'
                        AND AD.LEAVE_ID   IS NULL
                        AND AD.HOLIDAY_ID  IS NULL
                        AND AD.TRAINING_ID IS NULL
                        AND AD.TRAVEL_ID   IS NULL
                        AND AD.IN_TIME     IS NULL
                        THEN 1
                        ELSE 0
                      END) AS IS_ABSENT,
                      (
                      CASE
                        WHEN AD.LEAVE_ID   IS NULL
                        AND AD.HOLIDAY_ID  IS NULL
                        AND AD.TRAINING_ID IS NULL
                        AND AD.TRAVEL_ID   IS NULL
                        AND AD.IN_TIME     IS NULL 
                          AND  AD.DAYOFF_FLAG='Y'
                        THEN 1
                        ELSE 0
                      END) AS IS_DAYOFF
                    FROM HRIS_ATTENDANCE_DETAIL AD
                    JOIN HRIS_EMPLOYEES E
                    ON (AD.EMPLOYEE_ID = E.EMPLOYEE_ID),
                      ( SELECT FROM_DATE,TO_DATE FROM HRIS_MONTH_CODE WHERE MONTH_ID=:monthId
                      ) M
                    WHERE AD.ATTENDANCE_DT BETWEEN M.FROM_DATE AND M.TO_DATE
                    AND E.DEPARTMENT_ID=:departmentId
                    ORDER BY AD.ATTENDANCE_DT,
                      E.EMPLOYEE_ID
EOT;
    //        echo $sql;
    //        die();
    $boundedParameter = [];
    $boundedParameter['departmentId'] = $departmentId;
    $boundedParameter['monthId'] = $monthId;
    return $this->rawQuery($sql, $boundedParameter);
    // $statement = $this->adapter->query($sql);
    // $result = $statement->execute();
    // return Helper::extractDbData($result);
  }

  public function branchWiseEmployeeMonthReport($branchId)
  {
    $sql = <<<EOT
                SELECT J.*,
                  JE.FIRST_NAME AS FIRST_NAME,
                    JE.MIDDLE_NAME AS MIDDLE_NAME,
                    JE.LAST_NAME AS LAST_NAME,
                    CONCAT(CONCAT(CONCAT(JE.FIRST_NAME,' '),CONCAT(JE.MIDDLE_NAME, '')),JE.LAST_NAME) AS FULL_NAME,
                  JM.MONTH_EDESC
                FROM
                  (SELECT I.EMPLOYEE_ID,
                    I.MONTH_ID ,
                    SUM(I.ON_LEAVE)    AS ON_LEAVE,
                    SUM (I.IS_PRESENT) AS IS_PRESENT,
                    SUM(I.IS_ABSENT)   AS IS_ABSENT
                  FROM
                    (SELECT E.EMPLOYEE_ID AS EMPLOYEE_ID,
                      (SELECT M.MONTH_ID
                      FROM HRIS_MONTH_CODE M
                      WHERE AD.ATTENDANCE_DT BETWEEN M.FROM_DATE AND M.TO_DATE
                      ) AS MONTH_ID,
                      (
                  CASE 
                    WHEN AD.DAYOFF_FLAG ='N'
                    AND AD.HOLIDAY_ID  IS NULL
                    AND AD.TRAINING_ID IS NULL
                    AND AD.TRAVEL_ID   IS NULL
                    AND AD.IN_TIME     IS NULL
                    AND AD.LEAVE_ID IS NOT NULL
                    THEN 1
                    ELSE 0
                  END) AS ON_LEAVE,
                      (
                  CASE
                    WHEN AD.DAYOFF_FLAG ='N'
                    AND AD.LEAVE_ID    IS NULL
                    AND AD.HOLIDAY_ID  IS NULL
                    AND AD.TRAINING_ID IS NULL
                    AND AD.TRAVEL_ID   IS NULL
                    AND AD.IN_TIME     IS NOT NULL
                    THEN 1
                    ELSE 0
                  END) AS IS_PRESENT,
                     (
                  CASE
                    WHEN AD.DAYOFF_FLAG ='N'
                    AND AD.LEAVE_ID   IS NULL
                    AND AD.HOLIDAY_ID  IS NULL
                    AND AD.TRAINING_ID IS NULL
                    AND AD.TRAVEL_ID   IS NULL
                    AND AD.IN_TIME     IS NULL
                    THEN 1
                    ELSE 0
                  END) AS IS_ABSENT
                    FROM HRIS_ATTENDANCE_DETAIL AD
                    JOIN HRIS_EMPLOYEES E
                    ON (AD.EMPLOYEE_ID = E.EMPLOYEE_ID)
                    WHERE E.BRANCH_ID=:branchId
                    ) I
                  GROUP BY I.EMPLOYEE_ID,
                    I.MONTH_ID
                  ) J
                JOIN HRIS_EMPLOYEES JE
                ON (J.EMPLOYEE_ID = JE.EMPLOYEE_ID)
                JOIN HRIS_MONTH_CODE JM
                ON (J.MONTH_ID = JM.MONTH_ID)
EOT;
    $boundedParameter = [];
    $boundedParameter['branchId'] = $branchId;
    return $this->rawQuery($sql, $boundedParameter);
    // $statement = $this->adapter->query($sql);
    // $result = $statement->execute();
    // return Helper::extractDbData($result);
  }

  public function getCompanyBranchDepartment()
  {
    $sql = <<<EOT
            SELECT C.COMPANY_ID,
              C.COMPANY_NAME,
              B.BRANCH_ID,
              B.BRANCH_NAME,
              D.DEPARTMENT_ID,
              D.DEPARTMENT_NAME
            FROM HRIS_COMPANY C
            LEFT JOIN HRIS_BRANCHES B
            ON (C.COMPANY_ID =B.COMPANY_ID)
            LEFT  JOIN HRIS_DEPARTMENTS D
            ON (D.BRANCH_ID=B.BRANCH_ID)
EOT;
    $statement = $this->adapter->query($sql);
    $result = $statement->execute();
    return Helper::extractDbData($result);
  }

  public function getMonthList()
  {
    $sql = <<<EOT
            SELECT AM.MONTH_ID,M.MONTH_EDESC FROM
            (SELECT  UNIQUE (SELECT M.MONTH_ID
                FROM HRIS_MONTH_CODE M
                WHERE AD.ATTENDANCE_DT BETWEEN M.FROM_DATE AND M.TO_DATE
                ) AS MONTH_ID
            FROM HRIS_ATTENDANCE_DETAIL AD) AM JOIN HRIS_MONTH_CODE M ON (M.MONTH_ID=AM.MONTH_ID) 
EOT;
    $statement = $this->adapter->query($sql);
    $result = $statement->execute();
    return Helper::extractDbData($result);
  }

  public function getEmployeeList()
  {
    $sql = <<<EOT
            SELECT E.EMPLOYEE_ID                                                             AS EMPLOYEE_ID,
              E.FIRST_NAME                                                                   AS FIRST_NAME,
              E.MIDDLE_NAME                                                                  AS MIDDLE_NAME,
              E.LAST_NAME                                                                    AS LAST_NAME,
              EMPLOYEE_CODE||'-'||FULL_NAME                                                                      AS FULL_NAME,
              E.COMPANY_ID                                                                   AS COMPANY_ID,
              E.BRANCH_ID                                                                    AS BRANCH_ID,
              E.DEPARTMENT_ID                                                                AS DEPARTMENT_ID
            FROM HRIS_EMPLOYEES E
EOT;
    $statement = $this->adapter->query($sql);
    $result = $statement->execute();
    return Helper::extractDbData($result);
  }

  public function fetchAllLeave()
  {
    $sql = new Sql($this->adapter);
    $select = $sql->select();
    $select->columns(EntityHelper::getColumnNameArrayWithOracleFns(LeaveMaster::class, [LeaveMaster::LEAVE_ENAME], NULL, NULL, NULL, NULL, 'L', false, false, null, ["REPLACE(l.leave_ename, ' ', '') AS LEAVE_TRIM_ENAME"]), false);
    $select->from(['L' => LeaveMaster::TABLE_NAME]);
    $select->where(["L.STATUS='E'"]);
    $select->order(LeaveMaster::LEAVE_ID . " ASC");
    $statement = $sql->prepareStatementForSqlObject($select);
    $result = $statement->execute();
    return Helper::extractDbData($result);
    //        return $result;
  }

  private function leaveIn($allLeave)
  {
    $leaveCount = count($allLeave);

    $leaveIn = "";
    $i = 1;
    foreach ($allLeave as $leave) {
      $leaveIn .= $leave['LEAVE_ID'];
      if ($i < $leaveCount) {
        $leaveIn .= ',';
      }
      $i++;
    }
    return $leaveIn;
  }

  private function convertLeaveIdToName($allLeave, $leaveData, $name)
  {
    $columnData = [];
    foreach ($leaveData as $report) {
      $tempData = [
        //                'EMPLOYEE_ID' => $report['EMPLOYEE_ID'],
        'NAME' => $report[$name]
      ];
      foreach ($allLeave as $leave) {
        $tempData[$leave['LEAVE_TRIM_ENAME']] = $report[$leave['LEAVE_ID']];
      }
      array_push($columnData, $tempData);
    }
    //            print_r($columnData);
    //            die();
    return $columnData;
  }

  public function filterLeaveReportEmployee($data)
  {

    $allLeave = $this->fetchAllLeave();
    $leaveIn = $this->leaveIn($allLeave);

    $companyCondition = "";
    $branchCondition = "";
    $departmentCondition = "";
    $designationCondition = "";
    $positionCondition = "";
    $serviceTypeCondition = "";
    $serviceEventTypeConditon = "";
    $employeeCondition = "";
    $employeeTypeCondition = "";

    $fromCondition = "";
    $toCondition = "";

    if (isset($data['companyId']) && $data['companyId'] != null && $data['companyId'] != -1) {
      $companyCondition = "AND E.COMPANY_ID = {$data['companyId']}";
    }
    if (isset($data['branchId']) && $data['branchId'] != null && $data['branchId'] != -1) {
      $branchCondition = "AND E.BRANCH_ID = {$data['branchId']}";
    }
    if (isset($data['departmentId']) && $data['departmentId'] != null && $data['departmentId'] != -1) {
      $departmentCondition = "AND E.DEPARTMENT_ID = {$data['departmentId']}";
    }
    if (isset($data['designationId']) && $data['designationId'] != null && $data['designationId'] != -1) {
      $designationCondition = "AND E.DESIGNATION_ID = {$data['designationId']}";
    }
    if (isset($data['positionId']) && $data['positionId'] != null && $data['positionId'] != -1) {
      $positionCondition = "AND E.POSITION_ID = {$data['positionId']}";
    }
    if (isset($data['serviceTypeId']) && $data['serviceTypeId'] != null && $data['serviceTypeId'] != -1) {
      $serviceTypeCondition = "AND E.SERVICE_TYPE_ID = {$data['serviceTypeId']}";
    }
    if (isset($data['serviceEventTypeId']) && $data['serviceEventTypeId'] != null && $data['serviceEventTypeId'] != -1) {
      $serviceEventTypeConditon = "AND E.SERVICE_EVENT_TYPE_ID = {$data['serviceEventTypeId']}";
    }
    if (isset($data['employeeId']) && $data['employeeId'] != null && $data['employeeId'] != -1) {
      $employeeCondition = "AND E.EMPLOYEE_ID = {$data['employeeId']}";
    }
    if (isset($data['employeeTypeId']) && $data['employeeTypeId'] != null && $data['employeeTypeId'] != -1) {
      $employeeTypeCondition = "AND E.EMPLOYEE_TYPE = '{$data['employeeTypeId']}'";
    }
    $condition = $companyCondition . $branchCondition . $departmentCondition . $designationCondition . $positionCondition . $serviceTypeCondition . $serviceEventTypeConditon . $employeeCondition . $employeeTypeCondition;

    if (isset($data['fromDate']) && $data['fromDate'] != null && $data['fromDate'] != -1) {
      $fromDate = Helper::getExpressionDate($data['fromDate']);
      $fromCondition = "AND AD.ATTENDANCE_DT >= {$fromDate->getExpression()}";
    }
    if (isset($data['toDate']) && $data['toDate'] != null && $data['toDate'] != -1) {
      $toDate = Helper::getExpressionDate($data['toDate']);
      $toCondition = "AND AD.ATTENDANCE_DT <= {$toDate->getExpression()}";
    }
    $dateCondition = $fromCondition . $toCondition;
    $sql = <<<EOT
                
                            SELECT
                e.full_name,
                leave.*
            FROM
                hris_employees e
                LEFT JOIN (
                    select * from (SELECT
                        ad.employee_id,
                        ad.leave_id,
                        COUNT(ad.leave_id) AS leave_days
                    FROM
                        hris_attendance_detail ad
                    WHERE
                            ad.leave_id IS NOT NULL
                            {$dateCondition}
                    GROUP BY
                        ad.employee_id,
                        ad.leave_id)PIVOT ( SUM ( leave_days )
                        FOR leave_id
                        IN ( {$leaveIn})
                    )
                ) leave ON (
                    e.employee_id = leave.employee_id
                )
            WHERE
                1 = 1
              {$condition}
EOT;

    $statement = $this->adapter->query($sql);
    $result = $statement->execute();
    $extractedResult = Helper::extractDbData($result);
    return $this->convertLeaveIdToName($allLeave, $extractedResult, 'FULL_NAME');
  }

  public function filterLeaveReportBranch($data)
  {

    $allLeave = $this->fetchAllLeave();
    $leaveIn = $this->leaveIn($allLeave);
    $fromCondition = "";
    $toCondition = "";


    if (isset($data['fromDate']) && $data['fromDate'] != null && $data['fromDate'] != -1) {
      $fromDate = Helper::getExpressionDate($data['fromDate']);
      $fromCondition = "AND AD.ATTENDANCE_DT >= {$fromDate->getExpression()}";
    }
    if (isset($data['toDate']) && $data['toDate'] != null && $data['toDate'] != -1) {
      $toDate = Helper::getExpressionDate($data['toDate']);
      $toCondition = "AND AD.ATTENDANCE_DT <= {$toDate->getExpression()}";
    }
    $dateCondition = $fromCondition . $toCondition;
    $sql = <<<EOT
                
                SELECT BB.BRANCH_NAME,AA.* FROM (SELECT
                          *
                        FROM
                          (
                            SELECT
                              AD.LEAVE_ID,
                              B.BRANCH_ID,
                              COUNT(AD.LEAVE_ID) AS LEAVE_DAYS
                            FROM
                              HRIS_ATTENDANCE_DETAIL AD
                            JOIN HRIS_EMPLOYEES E ON (E.EMPLOYEE_ID=AD.EMPLOYEE_ID)
                            JOIN HRIS_BRANCHES B ON (B.BRANCH_ID=E.BRANCH_ID)
                            WHERE
                              AD.LEAVE_ID IS NOT NULL
                            {$dateCondition}
                               GROUP BY
                              B.BRANCH_ID,
                              AD.LEAVE_ID
                          )
                          PIVOT ( SUM ( LEAVE_DAYS )
                                                FOR leave_id
                                                IN ({$leaveIn}))) AA
                                                RIGHT JOIN HRIS_BRANCHES BB ON (AA.BRANCH_ID=BB.BRANCH_ID AND BB.STATUS='E')
    
                
                          
EOT;

    $statement = $this->adapter->query($sql);
    $result = $statement->execute();
    $extractedResult = Helper::extractDbData($result);
    return $this->convertLeaveIdToName($allLeave, $extractedResult, 'BRANCH_NAME');
  }

  public function filterLeaveReportDepartmnet($data)
  {
    $allLeave = $this->fetchAllLeave();
    $leaveIn = $this->leaveIn($allLeave);
    $fromCondition = "";
    $toCondition = "";


    if (isset($data['fromDate']) && $data['fromDate'] != null && $data['fromDate'] != -1) {
      $fromDate = Helper::getExpressionDate($data['fromDate']);
      $fromCondition = "AND AD.ATTENDANCE_DT >= {$fromDate->getExpression()}";
    }
    if (isset($data['toDate']) && $data['toDate'] != null && $data['toDate'] != -1) {
      $toDate = Helper::getExpressionDate($data['toDate']);
      $toCondition = "AND AD.ATTENDANCE_DT <= {$toDate->getExpression()}";
    }
    $dateCondition = $fromCondition . $toCondition;
    $sql = <<<EOT
                                SELECT
                  AA.*,BB.DEPARTMENT_NAME
                FROM (SELECT
                  *
                FROM
                  (
                    SELECT
                      AD.LEAVE_ID,
                      D.DEPARTMENT_ID,
                      COUNT(AD.LEAVE_ID) AS LEAVE_DAYS
                    FROM
                      HRIS_ATTENDANCE_DETAIL AD
                    JOIN HRIS_EMPLOYEES E ON (E.EMPLOYEE_ID=AD.EMPLOYEE_ID)
                    JOIN HRIS_DEPARTMENTS D ON (D.DEPARTMENT_ID=E.DEPARTMENT_ID)
                    WHERE
                      AD.LEAVE_ID IS NOT NULL
                        {$dateCondition}
                       GROUP BY
                      D.DEPARTMENT_ID,
                      AD.LEAVE_ID
                  )
                  PIVOT ( SUM ( LEAVE_DAYS )
                        FOR leave_id
                        IN ({$leaveIn}))) AA
                        RIGHT JOIN HRIS_DEPARTMENTS BB ON (AA.DEPARTMENT_ID=BB.DEPARTMENT_ID AND BB.STATUS='E')
    
                
                          
EOT;

    $statement = $this->adapter->query($sql);
    $result = $statement->execute();
    $extractedResult = Helper::extractDbData($result);
    return $this->convertLeaveIdToName($allLeave, $extractedResult, 'DEPARTMENT_NAME');
  }

  public function filterLeaveReportDesignation($data)
  {
    $allLeave = $this->fetchAllLeave();
    $leaveIn = $this->leaveIn($allLeave);
    $fromCondition = "";
    $toCondition = "";


    if (isset($data['fromDate']) && $data['fromDate'] != null && $data['fromDate'] != -1) {
      $fromDate = Helper::getExpressionDate($data['fromDate']);
      $fromCondition = "AND AD.ATTENDANCE_DT >= {$fromDate->getExpression()}";
    }
    if (isset($data['toDate']) && $data['toDate'] != null && $data['toDate'] != -1) {
      $toDate = Helper::getExpressionDate($data['toDate']);
      $toCondition = "AND AD.ATTENDANCE_DT <= {$toDate->getExpression()}";
    }
    $dateCondition = $fromCondition . $toCondition;
    $sql = <<<EOT
                
                SELECT
  AA.*,BB.DESIGNATION_TITLE
FROM (SELECT
  *
FROM
  (
    SELECT
      AD.LEAVE_ID,
      D.DESIGNATION_ID,
      COUNT(AD.LEAVE_ID) AS LEAVE_DAYS
    FROM
      HRIS_ATTENDANCE_DETAIL AD
    JOIN HRIS_EMPLOYEES E ON (E.EMPLOYEE_ID=AD.EMPLOYEE_ID)
    JOIN HRIS_DESIGNATIONS D ON (D.DESIGNATION_ID=E.DESIGNATION_ID)
    WHERE
      AD.LEAVE_ID IS NOT NULL
                {$dateCondition}
       GROUP BY
      D.DESIGNATION_ID,
      AD.LEAVE_ID
  )
  PIVOT ( SUM ( LEAVE_DAYS )
                        FOR leave_id
                        IN ({$leaveIn}))) AA
                        RIGHT JOIN HRIS_DESIGNATIONS BB ON (AA.DESIGNATION_ID=BB.DESIGNATION_ID AND BB.STATUS='E')
    
        
        
EOT;

    $statement = $this->adapter->query($sql);
    $result = $statement->execute();
    $extractedResult = Helper::extractDbData($result);
    return $this->convertLeaveIdToName($allLeave, $extractedResult, 'DESIGNATION_TITLE');
  }

  public function filterLeaveReportPosition($data)
  {
    $allLeave = $this->fetchAllLeave();
    $leaveIn = $this->leaveIn($allLeave);
    $fromCondition = "";
    $toCondition = "";


    if (isset($data['fromDate']) && $data['fromDate'] != null && $data['fromDate'] != -1) {
      $fromDate = Helper::getExpressionDate($data['fromDate']);
      $fromCondition = "AND AD.ATTENDANCE_DT >= {$fromDate->getExpression()}";
    }
    if (isset($data['toDate']) && $data['toDate'] != null && $data['toDate'] != -1) {
      $toDate = Helper::getExpressionDate($data['toDate']);
      $toCondition = "AND AD.ATTENDANCE_DT <= {$toDate->getExpression()}";
    }
    $dateCondition = $fromCondition . $toCondition;
    $sql = <<<EOT
                
                SELECT
  AA.*,BB.POSITION_NAME
FROM (SELECT
  *
FROM
  (
    SELECT
      AD.LEAVE_ID,
      P.POSITION_ID,
      COUNT(AD.LEAVE_ID) AS LEAVE_DAYS
    FROM
      HRIS_ATTENDANCE_DETAIL AD
    JOIN HRIS_EMPLOYEES E ON (E.EMPLOYEE_ID=AD.EMPLOYEE_ID)
    JOIN HRIS_POSITIONS P ON (P.POSITION_ID=E.POSITION_ID)
    WHERE
      AD.LEAVE_ID IS NOT NULL
        {$dateCondition}
       GROUP BY
      P.POSITION_ID,
      AD.LEAVE_ID
  )
  PIVOT ( SUM ( LEAVE_DAYS )
                        FOR leave_id
                        IN ({$leaveIn}))) AA
                        RIGHT JOIN HRIS_POSITIONS BB ON (AA.POSITION_ID=BB.POSITION_ID AND BB.STATUS='E')
        
        
EOT;

    $statement = $this->adapter->query($sql);
    $result = $statement->execute();
    $extractedResult = Helper::extractDbData($result);
    return $this->convertLeaveIdToName($allLeave, $extractedResult, 'POSITION_NAME');
  }

  public function FetchNepaliMonth()
  {
    $sql = new Sql($this->adapter);
    $select = $sql->select();
    $select->columns(EntityHelper::getColumnNameArrayWithOracleFns(Months::class, NULL, [Months::FROM_DATE, Months::TO_DATE], NULL, NULL, NULL, 'M', true), false);
    $select->from(['M' => Months::TABLE_NAME])
      ->join(['FY' => FiscalYear::TABLE_NAME], 'FY.' . FiscalYear::FISCAL_YEAR_ID . '=M.' . Months::FISCAL_YEAR_ID, ["MONTH_NAME" => new Expression('CONCAT(FY.FISCAL_YEAR_NAME,M.MONTH_EDESC)')], "left");
    $select->where(["M.STATUS='E'", "FY.STATUS='E'", "TRUNC(SYSDATE)>M.FROM_DATE"]);
    $select->order("M." . Months::FROM_DATE . " DESC");
    $statement = $sql->prepareStatementForSqlObject($select);

    $result = $statement->execute();
    return Helper::extractDbData($result);
  }

  private function totalHiredEmployees($fromDate, $toDate)
  {
    $sql = "select count(*)as TOTAL from hris_employees 
            where JOIN_DATE BETWEEN " . $fromDate->getExpression() . " and " . $toDate->getExpression() . " and status='E'";
    $statement = $this->adapter->query($sql);
    $result = $statement->execute();
    return $result->current();
  }

  public function CalculateHireEmployees($data)
  {
    $returnArr = [];
    foreach ($data as $details) {
      $name = $details->name;
      $tempData = [
        'NAME' => $name
      ];
      $fromDate = Helper::getExpressionDate($details->fromDate);
      $toDate = Helper::getExpressionDate($details->toDate);
      $total = $this->totalHiredEmployees($fromDate, $toDate);
      $tempData['TOTAL'] = $total['TOTAL'];
      $sql = "select full_name,JOIN_DATE from hris_employees 
            where JOIN_DATE BETWEEN " . $fromDate->getExpression() . " and " . $toDate->getExpression() . " and status='E'";
      $statement = $this->adapter->query($sql);
      $result = $statement->execute();
      $tempData['DATA'] = Helper::extractDbData($result);
      array_push($returnArr, $tempData);
    }
    return $returnArr;
  }

  public function branchWiseDailyReport($data)
  {

    $employeeId = $data['employeeId'];
    $companyId = $data['companyId'];
    $branchId = $data['branchId'];
    $departmentId = $data['departmentId'];
    $designationId = $data['designationId'];
    $positionId = $data['positionId'];
    $serviceTypeId = $data['serviceTypeId'];
    $serviceEventTypeId = $data['serviceEventTypeId'];
    $employeeTypeId = $data['employeeTypeId'];
    $functionalTypeId = $data['functionalTypeId'];

    $searchCondition = EntityHelper::getSearchConditonBounded($companyId, $branchId, $departmentId, $positionId, $designationId, $serviceTypeId, $serviceEventTypeId, $employeeTypeId, $employeeId, null, null, $functionalTypeId);

    $boundedParameter = [];
    $boundedParameter = array_merge($boundedParameter, $searchCondition['parameter']);
    $monthId = $data['monthId'];
    $boundedParameter['monthId'] = $monthId;

    $sql = <<<EOT
                      SELECT 
                      TRUNC(AD.ATTENDANCE_DT)-TRUNC(M.FROM_DATE)+1                              AS DAY_COUNT, 
                      E.EMPLOYEE_ID                                                             AS EMPLOYEE_ID ,
                      E.EMPLOYEE_CODE                                                               AS EMPLOYEE_CODE,
                      HD.DEPARTMENT_NAME                                                             AS DEPARTMENT_NAME,
                      E.FIRST_NAME                                                                   AS FIRST_NAME,
                      E.MIDDLE_NAME                                                                  AS MIDDLE_NAME,
                      E.LAST_NAME                                                                    AS LAST_NAME,
                      CONCAT(CONCAT(CONCAT(E.FIRST_NAME,' '),CONCAT(E.MIDDLE_NAME, '')),E.LAST_NAME) AS FULL_NAME,
                      AD.ATTENDANCE_DT                                                               AS ATTENDANCE_DT,
                      (
                      CASE 
                        WHEN AD.DAYOFF_FLAG ='N'
                        AND AD.HOLIDAY_ID  IS NULL
                        AND AD.TRAINING_ID IS NULL
                        AND AD.TRAVEL_ID   IS NULL
                        AND AD.IN_TIME     IS NULL
                        AND AD.LEAVE_ID IS NOT NULL
                        THEN 1
                        ELSE 0
                      END) AS ON_LEAVE,
                      (
                      CASE
                        WHEN AD.DAYOFF_FLAG ='N'
                        AND AD.LEAVE_ID    IS NULL
                        AND AD.HOLIDAY_ID  IS NULL
                        AND AD.TRAINING_ID IS NULL
                        AND AD.TRAVEL_ID   IS NULL
                        AND AD.IN_TIME     IS NOT NULL
                        THEN 1
                        ELSE 0
                      END) AS IS_PRESENT,
                      (
                      CASE
                        WHEN (AD.DAYOFF_FLAG ='N' OR (AD.DAYOFF_FLAG = 'Y' AND C.HD_DO_CHECK = 1))
                        AND AD.LEAVE_ID   IS NULL
                        AND (AD.HOLIDAY_ID  IS NULL OR (AD.HOLIDAY_ID IS NOT NULL AND C.HD_DO_CHECK = 1))
                        AND AD.TRAINING_ID IS NULL
                        AND AD.TRAVEL_ID   IS NULL
                        AND AD.IN_TIME     IS NULL
                        THEN 1
                        ELSE 0
                      END) AS IS_ABSENT,
                      (
                      CASE
                        WHEN AD.LEAVE_ID   IS NULL
                        AND AD.HOLIDAY_ID  IS NULL
                        AND AD.TRAINING_ID IS NULL
                        AND AD.TRAVEL_ID   IS NULL
                        AND AD.IN_TIME     IS NULL 
                        AND AD.DAYOFF_FLAG='Y'
                        AND (C.HD_DO_CHECK = 0)
                        THEN 1
                        ELSE 0
                      END) AS IS_DAYOFF,
                      (
                      CASE
                        WHEN AD.LEAVE_ID   IS NULL
                        AND AD.TRAINING_ID IS NULL
                        AND AD.TRAVEL_ID   IS NULL
                        AND AD.IN_TIME     IS NOT NULL 
                        AND (AD.DAYOFF_FLAG='Y'
                        OR AD.HOLIDAY_ID  IS NOT NULL)
                        THEN 1
                        ELSE 0
                      END) AS HOLIDAY_WORK,
                      (
                      CASE
                        WHEN AD.LEAVE_ID   IS NULL
                        AND AD.HOLIDAY_ID  IS NOT NULL
                        AND AD.TRAINING_ID IS NULL
                        AND AD.TRAVEL_ID   IS NULL
                        AND AD.IN_TIME     IS NULL 
                        AND  AD.DAYOFF_FLAG='N'
                        AND (C.HD_DO_CHECK = 0)
                        THEN 1
                        ELSE 0
                      END) AS HOLIDAY,
                      (
                      CASE
                        WHEN AD.LEAVE_ID   IS NULL
                        AND AD.HOLIDAY_ID  IS NULL
                        AND AD.TRAINING_ID IS NULL
                        AND AD.TRAVEL_ID   IS NOT NULL
                        AND AD.IN_TIME     IS NULL 
                        THEN 1
                        ELSE 0
                      END) AS TRAVEL,
                      TO_CHAR(AD.IN_TIME, 'HH24:mi') as IN_TIME,
                      TO_CHAR(AD.OUT_TIME, 'HH24:mi') as OUT_TIME,
                      MIN_TO_HOUR(AD.TOTAL_HOUR)      AS TOTAL_HOUR
                    FROM HRIS_ATTENDANCE_DETAIL AD
                    LEFT JOIN (select HRIS_CHECK_HOLIDAY_INBETWN(employee_id, attendance_dt) as HD_DO_CHECK,EMPLOYEE_ID,ATTENDANCE_DT from HRIS_ATTENDANCE_DETAIL) C 
                    ON (AD.EMPLOYEE_ID = C.EMPLOYEE_ID AND AD.ATTENDANCE_DT = C.ATTENDANCE_DT)
                    JOIN HRIS_EMPLOYEES E
                    ON (AD.EMPLOYEE_ID = E.EMPLOYEE_ID)
                    LEFT JOIN HRIS_DEPARTMENTS HD 
                    ON (HD.DEPARTMENT_ID = E.DEPARTMENT_ID),
                      ( SELECT FROM_DATE,TO_DATE FROM HRIS_MONTH_CODE WHERE MONTH_ID= :monthId
                      ) M
                    WHERE AD.ATTENDANCE_DT BETWEEN M.FROM_DATE AND M.TO_DATE
                    and E.EMPLOYEE_ID not in (select employee_id from hris_job_history where RETIRED_FLAG = 'Y' or DISABLED_FLAG = 'Y')
                    {$searchCondition['sql']}
                    ORDER BY DAY_COUNT asc,
                      TO_NUMBER(NVL(E.EMPLOYEE_CODE,'0'),'9999D99','nls_numeric_characters=,.') asc
EOT;
    $statement = $this->adapter->query($sql);
    $result = $statement->execute($boundedParameter);
    return Helper::extractDbData($result);
  }

  public function getDaysInMonth($monthId)
  {

    $sql = "SELECT TO_DATE - FROM_DATE +1 AS TOTAL_DAYS FROM HRIS_MONTH_CODE WHERE MONTH_ID =:monthId";
    $boundedParameter = [];
    $boundedParameter['monthId'] = $monthId;
    return $this->rawQuery($sql, $boundedParameter);
    // $statement = $this->adapter->query($sql);
    // $result = $statement->execute();
    // return Helper::extractDbData($result);
  }

  public function checkIfEmpowerTableExists()
  {
    return $this->checkIfTableExists('HR_MONTHLY_MODIFIED_PAY_VALUE');
  }

  public function loadData($fiscalYearId, $fiscalYearMonthNo)
  {
    $sql = "
            BEGIN
              HRIS_PREPARE_PAYROLL_DATA({$fiscalYearId},{$fiscalYearMonthNo});
            END;
            ";
    $this->executeStatement($sql);
  }

  public function reportWithOT($data)
  {
    $boundedParams = [];
    $fromCondition = "";
    $toCondition = "";

    $otFromCondition = "";
    $otToCondition = "";

    $condition = EntityHelper::getSearchConditonBounded($data['companyId'], $data['branchId'], $data['departmentId'], $data['positionId'], $data['designationId'], $data['serviceTypeId'], $data['serviceEventTypeId'], $data['employeeTypeId'], $data['employeeId'], $data['genderId'], $data['locationId'], $data['functionalTypeId']);
    $boundedParams = array_merge($boundedParams, $condition['parameter']);

    if (isset($data['fromDate']) && $data['fromDate'] != null && $data['fromDate'] != -1) {
      $fromDate = $data['fromDate'];
      $fromCondition = "AND A.ATTENDANCE_DT >= :fromDate";
      $otFromCondition = "AND OVERTIME_DATE >= :fromDate ";
      $boundedParams['fromDate'] = $fromDate;
    }
    if (isset($data['toDate']) && $data['toDate'] != null && $data['toDate'] != -1) {
      $toDate = $data['toDate'];
      $toCondition = "AND A.ATTENDANCE_DT <= :toDate";
      $otToCondition = "AND OVERTIME_DATE <= :toDate ";
      $boundedParams['toDate'] = $toDate;
    }

    $monthId = $data['monthId'];
    $boundedParams['monthId'] = $monthId;

    $sql = <<<EOT
            SELECT C.COMPANY_NAME,
              D.DEPARTMENT_NAME,
              A.EMPLOYEE_ID,
              E.EMPLOYEE_CODE,
              E.FULL_NAME,
              A.DAYOFF,
              A.PRESENT,
              A.HOLIDAY,
              A.EVENT_CONFERENCE,
              A.LEAVE,
              A.PAID_LEAVE,
              A.UNPAID_LEAVE,
              A.ABSENT,
              NVL(ROUND(A.TOTAL_MIN/60,2),0) + NVL(AD.ADDITION,0) - NVL(AD.DEDUCTION,0) AS OVERTIME_HOUR,
              A.TRAVEL,
              A.TRAINING,
              A.WORK_ON_HOLIDAY,
              A.WORK_ON_DAYOFF,
              AD.ADDITION,
              AD.DEDUCTION
            FROM
              (SELECT A.EMPLOYEE_ID,
                SUM(
                CASE
                  WHEN A.OVERALL_STATUS IN( 'DO','WD')
                  THEN 1
                  ELSE 0
                END) AS DAYOFF,
                SUM(
                CASE
                  WHEN A.OVERALL_STATUS IN ('PR','BA','LA','EC','TV','VP','TN','TP','LP')
                  THEN (
                    CASE
                      WHEN A.OVERALL_STATUS = 'LP'
                      AND A.HALFDAY_PERIOD IS NOT NULL
                      THEN 0.5
                      ELSE 1
                    END)
                  ELSE 0
                END) AS PRESENT,
                SUM(
                CASE
                  WHEN A.OVERALL_STATUS IN ('HD','WH')
                  THEN 1
                  ELSE 0
                END) AS HOLIDAY,
                SUM(
                  CASE
                    WHEN A.OVERALL_STATUS IN ('EC')
                    THEN 1
                    ELSE 0
                  END) AS EVENT_CONFERENCE,
                SUM(
                CASE
                  WHEN A.OVERALL_STATUS IN ('LV','LP')
                  AND A.GRACE_PERIOD    IS NULL
                  THEN (
                    CASE
                      WHEN A.OVERALL_STATUS = 'LP'
                      AND A.HALFDAY_PERIOD IS NOT NULL
                      THEN 0.5
                      ELSE 1
                    END)
                  ELSE 0
                END) AS LEAVE,
                SUM(
                CASE
                  WHEN A.OVERALL_STATUS IN ('LV','LP')
                  AND A.GRACE_PERIOD    IS NULL
                  AND L.PAID             = 'Y'
                  THEN (
                    CASE
                      WHEN A.OVERALL_STATUS = 'LP'
                      AND A.HALFDAY_PERIOD IS NOT NULL
                      THEN 0.5
                      ELSE 1
                    END)
                  ELSE 0
                END) AS PAID_LEAVE,
                SUM(
                CASE
                  WHEN A.OVERALL_STATUS IN ('LV','LP')
                  AND A.GRACE_PERIOD    IS NULL
                  AND L.PAID             = 'N'
                  THEN (
                    CASE
                      WHEN A.OVERALL_STATUS = 'LP'
                      AND A.HALFDAY_PERIOD IS NOT NULL
                      THEN 0.5
                      ELSE 1
                    END)
                  ELSE 0
                END) AS UNPAID_LEAVE,
                SUM(
                CASE
                  WHEN A.OVERALL_STATUS = 'AB'
                  THEN 1
                  ELSE 0
                END) AS ABSENT,
                SUM(
                CASE
                  WHEN A.OVERALL_STATUS= 'TV'
                  THEN 1
                  ELSE 0
                END) AS TRAVEL,
                SUM(
                CASE
                  WHEN A.OVERALL_STATUS ='TN'
                  THEN 1
                  ELSE 0
                END) AS TRAINING,
                SUM(
                CASE
                  WHEN A.OVERALL_STATUS = 'WH'
                  THEN 1
                  ELSE 0
                END) WORK_ON_HOLIDAY,
                SUM(
                CASE
                  WHEN A.OVERALL_STATUS ='WD'
                  THEN 1
                  ELSE 0
                END) WORK_ON_DAYOFF,
                 SUM(
                  CASE
                    WHEN OTM.OVERTIME_HOUR IS NULL
                    THEN OT.TOTAL_HOUR
                    ELSE OTM.OVERTIME_HOUR*60
                  END ) AS TOTAL_MIN
              FROM HRIS_ATTENDANCE_PAYROLL A
              LEFT JOIN (SELECT
    employee_id,
    overtime_date,
    SUM(total_hour) AS total_hour
FROM
    hris_overtime where status ='AP'
GROUP BY
    employee_id,
    overtime_date) OT
              ON (A.EMPLOYEE_ID   =OT.EMPLOYEE_ID
              AND A.ATTENDANCE_DT =OT.OVERTIME_DATE)
              LEFT JOIN HRIS_OVERTIME_MANUAL OTM
              ON (A.EMPLOYEE_ID   =OTM.EMPLOYEE_ID
              AND A.ATTENDANCE_DT =OTM.ATTENDANCE_DATE)
              LEFT JOIN HRIS_LEAVE_MASTER_SETUP L
              ON (A.LEAVE_ID= L.LEAVE_ID)
              WHERE 1       =1 {$fromCondition} {$toCondition}
              GROUP BY A.EMPLOYEE_ID
              ) A
            LEFT JOIN HRIS_EMPLOYEES E
            ON(A.EMPLOYEE_ID = E.EMPLOYEE_ID)
            LEFT JOIN HRIS_COMPANY C
            ON(E.COMPANY_ID= C.COMPANY_ID)
            LEFT JOIN HRIS_DEPARTMENTS D
            ON (E.DEPARTMENT_ID= D.DEPARTMENT_ID)
            LEFT JOIN HRIS_OVERTIME_A_D AD
            ON (A.EMPLOYEE_ID = AD.EMPLOYEE_ID AND AD.MONTH_ID = :monthId)
            WHERE 1            =1 {$condition['sql']}
            ORDER BY C.COMPANY_NAME,
              D.DEPARTMENT_NAME,
              E.FULL_NAME 
EOT;
    // echo '<pre>';print_r($sql);die;
    $statement = $this->adapter->query($sql);
    $result = $statement->execute($boundedParams);
    return Helper::extractDbData($result);
  }

  public function toEmpower($fiscalYearId, $fiscalYearMonthNo)
  {
    $sql = "BEGIN HRIS_TO_EMPOWER({$fiscalYearId},{$fiscalYearMonthNo}); END;";
    $this->executeStatement($sql);
  }

  public function departmentMonthReport($fiscalYearId)
  {
    $sql = <<<EOT
            SELECT D.DEPARTMENT_NAME,
              R.*
            FROM
              (SELECT *
              FROM
                (SELECT EMC.FISCAL_YEAR_MONTH_NO,
                  EMC.DEPARTMENT_ID,
                  SUM(
                  CASE
                    WHEN A.OVERALL_STATUS IN( 'DO','WD')
                    THEN 1
                    ELSE 0
                  END) AS DAYOFF,
                  SUM(
                  CASE
                    WHEN A.OVERALL_STATUS IN ('PR','BA','LA','TV','VP','TN','TP','LP')
                    THEN (
                      CASE
                        WHEN A.OVERALL_STATUS = 'LP'
                        AND A.HALFDAY_PERIOD IS NOT NULL
                        THEN 0.5
                        ELSE 1
                      END)
                    ELSE 0
                  END) AS PRESENT,
                  SUM(
                  CASE
                    WHEN A.OVERALL_STATUS IN ('HD','WH')
                    THEN 1
                    ELSE 0
                  END) AS HOLIDAY,
                  SUM(
                  CASE
                    WHEN A.OVERALL_STATUS IN ('LV','LP')
                    AND A.GRACE_PERIOD    IS NULL
                    THEN (
                      CASE
                        WHEN A.OVERALL_STATUS = 'LP'
                        AND A.HALFDAY_PERIOD IS NOT NULL
                        THEN 0.5
                        ELSE 1
                      END)
                    ELSE 0
                  END) AS LEAVE,
                  SUM(
                  CASE
                    WHEN A.OVERALL_STATUS = 'AB'
                    THEN 1
                    ELSE 0
                  END) AS ABSENT,
                  SUM(
                  CASE
                    WHEN A.OVERALL_STATUS = 'WH'
                    THEN 1
                    ELSE 0
                  END) WORK_ON_HOLIDAY,
                  SUM(
                  CASE
                    WHEN A.OVERALL_STATUS ='WD'
                    THEN 1
                    ELSE 0
                  END) WORK_ON_DAYOFF
                FROM
                  (SELECT * FROM HRIS_MONTH_CODE,HRIS_EMPLOYEES
                  ) EMC
                LEFT JOIN HRIS_ATTENDANCE_DETAIL A
                ON ((A.ATTENDANCE_DT BETWEEN EMC.FROM_DATE AND EMC.TO_DATE)
                AND (EMC.EMPLOYEE_ID=A.EMPLOYEE_ID))
                LEFT JOIN HRIS_LEAVE_MASTER_SETUP L
                ON (A.LEAVE_ID          = L.LEAVE_ID)
                WHERE EMC.FISCAL_YEAR_ID=:fiscalYearId
                GROUP BY EMC.DEPARTMENT_ID,
                  EMC.FISCAL_YEAR_MONTH_NO
                ) PIVOT (MAX(PRESENT) AS PRESENT,MAX(ABSENT) AS ABSENT,MAX(LEAVE) AS LEAVE,MAX(DAYOFF) AS DAYOFF,MAX(HOLIDAY) AS HOLIDAY,MAX(WORK_ON_HOLIDAY) AS WOH,MAX(WORK_ON_DAYOFF) AS WOD FOR FISCAL_YEAR_MONTH_NO IN (1 AS one,2 AS two,3 AS three,4 AS four,5 AS five,6 AS six,7 AS seven,8 AS eight,9 AS nine,10 AS ten,11 AS eleven,12 AS twelve))
              ) R
            JOIN HRIS_DEPARTMENTS D
            ON (R.DEPARTMENT_ID=D.DEPARTMENT_ID)       
EOT;
    $boundedParameter['fiscalYearId'] = $fiscalYearId;
    return $this->rawQuery($sql, $boundedParameter);
    //$result = $statement->execute();
    //return Helper::extractDbData($result);
  }

  public function employeeMonthlyReport($searchQuery)
  {
    $searchCondition = EntityHelper::getSearchConditonBounded($searchQuery['companyId'], $searchQuery['branchId'], $searchQuery['departmentId'], $searchQuery['positionId'], $searchQuery['designationId'], $searchQuery['serviceTypeId'], $searchQuery['serviceEventTypeId'], $searchQuery['employeeTypeId'], $searchQuery['employeeId'], null, null, $searchQuery['functionalTypeId']);
    $boundedParameter = [];
    $boundedParameter = array_merge($boundedParameter, $searchCondition['parameter']);
    $sql = <<<EOT
                SELECT D.FULL_NAME, D.EMPLOYEE_CODE,
                  R.*
                FROM
                  (SELECT *
                  FROM
                    (SELECT EMC.FISCAL_YEAR_MONTH_NO,
                      EMC.EMPLOYEE_ID,
                      SUM(
                      CASE
                        WHEN A.OVERALL_STATUS IN( 'DO','WD')
                        THEN 1
                        ELSE 0
                      END) AS DAYOFF,
                      SUM(
                      CASE
                        WHEN A.OVERALL_STATUS IN ('PR','BA','LA','TV','VP','TN','TP','LP')
                        THEN (
                          CASE
                            WHEN A.OVERALL_STATUS = 'LP'
                            AND A.HALFDAY_PERIOD IS NOT NULL
                            THEN 0.5
                            ELSE 1
                          END)
                        ELSE 0
                      END) AS PRESENT,
                      SUM(
                      CASE
                        WHEN A.OVERALL_STATUS IN ('HD','WH')
                        THEN 1
                        ELSE 0
                      END) AS HOLIDAY,
                      SUM(
                      CASE
                        WHEN A.OVERALL_STATUS IN ('LV','LP')
                        AND A.GRACE_PERIOD    IS NULL
                        THEN (
                          CASE
                            WHEN A.OVERALL_STATUS = 'LP'
                            AND A.HALFDAY_PERIOD IS NOT NULL
                            THEN 0.5
                            ELSE 1
                          END)
                        ELSE 0
                      END) AS LEAVE,
                      SUM(
                      CASE
                        WHEN A.OVERALL_STATUS = 'AB'
                        THEN 1
                        ELSE 0
                      END) AS ABSENT,
                      SUM(
                      CASE
                        WHEN A.OVERALL_STATUS = 'WH'
                        THEN 1
                        ELSE 0
                      END) WORK_ON_HOLIDAY,
                      SUM(
                      CASE
                        WHEN A.OVERALL_STATUS ='WD'
                        THEN 1
                        ELSE 0
                      END) WORK_ON_DAYOFF
                    FROM
                      (SELECT * FROM HRIS_MONTH_CODE MC,HRIS_EMPLOYEES E
                            WHERE 1=1 
                            {$searchCondition['sql']}
                      ) EMC
                    LEFT JOIN HRIS_ATTENDANCE_DETAIL A
                    ON ((A.ATTENDANCE_DT BETWEEN EMC.FROM_DATE AND EMC.TO_DATE)
                    AND (EMC.EMPLOYEE_ID=A.EMPLOYEE_ID))
                    LEFT JOIN HRIS_LEAVE_MASTER_SETUP L
                    ON (A.LEAVE_ID          = L.LEAVE_ID)
                    WHERE EMC.FISCAL_YEAR_ID=:fiscalYearId
                    GROUP BY EMC.EMPLOYEE_ID,
                      EMC.FISCAL_YEAR_MONTH_NO
                    ) PIVOT (MAX(PRESENT) AS PRESENT,MAX(ABSENT) AS ABSENT,MAX(LEAVE) AS LEAVE,MAX(DAYOFF) AS DAYOFF,MAX(HOLIDAY) AS HOLIDAY,MAX(WORK_ON_HOLIDAY) AS WOH,MAX(WORK_ON_DAYOFF) AS WOD FOR FISCAL_YEAR_MONTH_NO IN (1 AS one,2 AS two,3 AS three,4 AS four,5 AS five,6 AS six,7 AS seven,8 AS eight,9 AS nine,10 AS ten,11 AS eleven,12 AS twelve))
                  ) R
                JOIN HRIS_EMPLOYEES D
                ON (R.EMPLOYEE_ID=D.EMPLOYEE_ID)                   
EOT;

    $boundedParameter['fiscalYearId'] = $searchQuery['fiscalYearId'];
    return $this->rawQuery($sql, $boundedParameter);
    //$statement = $this->adapter->query($sql);
    //$result = $statement->execute();
    //return Helper::extractDbData($result);
  }

  public function employeeDailyReport($searchQuery)
  {
    $fromDate = $searchQuery['fromDate'];
    $toDate = $searchQuery['toDate'];
    $monthDetail = $this->getMonthDetailsByDate($fromDate, $toDate);

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
    $boundedParameter = [];
    $boundedParameter['fromDate'] = $fromDate;
    $boundedParameter['toDate'] = $toDate;
    $searchCondition = EntityHelper::getSearchConditonBounded($searchQuery['companyId'], $searchQuery['branchId'], $searchQuery['departmentId'], $searchQuery['positionId'], $searchQuery['designationId'], $searchQuery['serviceTypeId'], $searchQuery['serviceEventTypeId'], $searchQuery['employeeTypeId'], $searchQuery['employeeId'], null, null, $searchQuery['functionalTypeId']);
    $boundedParameter = array_merge($boundedParameter, $searchCondition['parameter']);

    $sql = <<<EOT
                SELECT PL.*,MLD.*,
         CL.PRESENT,
         CL.ABSENT,
         CL.LEAVE,
         CL.DAYOFF,
         CL.HOLIDAY,
         CL.WORK_DAYOFF,
         CL.WORK_HOLIDAY,
         (CL.PRESENT+CL.ABSENT+CL.LEAVE+CL.DAYOFF+CL.HOLIDAY+CL.WORK_DAYOFF+CL.WORK_HOLIDAY) AS TOTAL
       FROM
         (SELECT *
         FROM
           (SELECT E.FULL_NAME,
             AD.EMPLOYEE_ID,
             E.EMPLOYEE_CODE,
             CASE
               WHEN AD.OVERALL_STATUS IN ('TV','TN','PR','BA','LA','TP','LP','VP')
               THEN 'PR'
               WHEN AD.OVERALL_STATUS = 'LV' AND AD.HALFDAY_FLAG='N' THEN 'L'||'-'||LMS.LEAVE_CODE
               WHEN AD.OVERALL_STATUS = 'LV' AND AD.HALFDAY_FLAG!='N' THEN 'HL'||'-'||LMS.LEAVE_CODE
               ELSE AD.OVERALL_STATUS
             END AS OVERALL_STATUS,
             --AD.ATTENDANCE_DT,
             (AD.ATTENDANCE_DT-TO_DATE(:fromDate)+1) AS DAY_COUNT
           FROM HRIS_ATTENDANCE_DETAIL AD
           LEFT JOIN HRIS_LEAVE_MASTER_SETUP LMS ON (AD.LEAVE_ID=LMS.LEAVE_ID)
           JOIN HRIS_EMPLOYEES E
           ON (E.EMPLOYEE_ID =AD.EMPLOYEE_ID)
           WHERE (AD.ATTENDANCE_DT BETWEEN TO_DATE(:fromDate) AND TO_DATE(:toDate) )
       {$searchCondition['sql']}
           ) PIVOT (MAX (OVERALL_STATUS) FOR DAY_COUNT IN ({$pivotString})) 
         ) PL
       LEFT JOIN
         (SELECT EMPLOYEE_ID,
           COUNT(
           CASE
             WHEN OVERALL_STATUS IN ('TV','TN','PR','BA','LA','TP','LP','VP')
             THEN 1
           END) AS PRESENT,
           COUNT(
           CASE OVERALL_STATUS
             WHEN 'AB'
             THEN 1
           END) AS ABSENT,
           COUNT(
           CASE OVERALL_STATUS
             WHEN 'LV'
             THEN 1
           END) AS LEAVE,
           COUNT(
           CASE OVERALL_STATUS
             WHEN 'DO'
             THEN 1
           END) AS DAYOFF,
           COUNT(
           CASE OVERALL_STATUS
             WHEN 'HD'
             THEN 1
           END) AS HOLIDAY,
           COUNT(
           CASE OVERALL_STATUS
             WHEN 'WD'
             THEN 1
           END) AS WORK_DAYOFF,
           COUNT(
           CASE OVERALL_STATUS
             WHEN 'WH'
             THEN 1
           END) AS WORK_HOLIDAY
         FROM HRIS_ATTENDANCE_DETAIL
         WHERE ATTENDANCE_DT BETWEEN TO_DATE(:fromDate,'DD-MON-YY') AND TO_DATE(:toDate,'DD-MON-YY')
         GROUP BY EMPLOYEE_ID
         )CL
       ON (PL.EMPLOYEE_ID=CL.EMPLOYEE_ID)
          LEFT JOIN
         (
         select 
 *
 from
 (select 
AD.employee_id,
AD.leave_id,
 sum(
 case AD.HALFDAY_FLAG
 when 'N'  then 1
 else 0.5 end
 ) as LTBM
from HRIS_ATTENDANCE_DETAIL AD
  WHERE 
 leave_id  is not null and
   (AD.ATTENDANCE_DT BETWEEN TO_DATE(:fromDate) AND TO_DATE(:toDate) )
   group by AD.employee_id,AD.leave_id
   )
   PIVOT ( MAX (LTBM) FOR LEAVE_ID IN (
   {$leavePivotString}
   )
   )
         ) MLD
       ON (PL.EMPLOYEE_ID=MLD.EMPLOYEE_ID)
                 
EOT;

    //   echo $sql;
    //   die();
    $statement = $this->adapter->query($sql);
    $result = $statement->execute($boundedParameter);
    return ['leaveDetails' => $leaveDetails, 'monthDetail' => $monthDetail, 'kendoDetails' => $kendoDetails, 'data' => Helper::extractDbData($result)];
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

  public function employeeYearlyReport($employeeId, $fiscalYearId)
  {
    $sql = <<<EOT
                
                SELECT PL.*,
           CL.PRESENT,
                CL.ABSENT,
                CL.LEAVE,
                CL.DAYOFF,
                CL.HOLIDAY,
                CL.WORK_DAYOFF,
                CL.WORK_HOLIDAY,
                 (CL.PRESENT+CL.ABSENT+CL.LEAVE+CL.DAYOFF+CL.HOLIDAY+CL.WORK_DAYOFF+CL.WORK_HOLIDAY) as TOTAL
           FROM  (SELECT * FROM (SELECT  
E.FULL_NAME,
AD.EMPLOYEE_ID,
CASE WHEN AD.OVERALL_STATUS IN ('TV','TN','PR','BA','LA','TP','LP','VP')
THEN 'PR' 
WHEN AD.OVERALL_STATUS = 'LV' AND AD.HALFDAY_FLAG='N' THEN 'L'||'-'||LMS.LEAVE_CODE
WHEN AD.OVERALL_STATUS = 'LV' AND AD.HALFDAY_FLAG!='N' THEN 'HL'||'-'||LMS.LEAVE_CODE
ELSE AD.OVERALL_STATUS
END 
AS OVERALL_STATUS,
                MC.MONTH_ID,
                MC.YEAR||MC.MONTH_EDESC AS MONTH_DTL,
                MC.FISCAL_YEAR_MONTH_NO,
(AD.ATTENDANCE_DT-MC.FROM_DATE+1) AS DAY_COUNT
FROM HRIS_ATTENDANCE_DETAIL AD
LEFT JOIN HRIS_LEAVE_MASTER_SETUP LMS ON (AD.LEAVE_ID = LMS.LEAVE_ID)
LEFT JOIN HRIS_EMPLOYEES E ON (E.EMPLOYEE_ID=AD.EMPLOYEE_ID)
JOIN (SELECT * FROM HRIS_MONTH_CODE WHERE FISCAL_YEAR_ID={$fiscalYearId} ) MC ON (AD.ATTENDANCE_DT BETWEEN MC.FROM_DATE AND MC.TO_DATE)
WHERE AD.EMPLOYEE_ID = {$employeeId})
PIVOT (MAX(OVERALL_STATUS) FOR DAY_COUNT
                        IN (1 AS D1, 2 AS D2, 3 AS D3, 4 AS D4, 5 AS D5, 6 AS D6, 7 AS D7, 8 AS D8, 9 AS D9, 10 AS D10, 11 AS D11, 12 AS D12, 13 AS D13, 14 AS D14, 15 AS D15, 16 AS D16, 17 AS D17, 18 AS D18, 19 AS D19, 20 AS D20, 21 AS D21, 22 AS D22, 23 AS D23, 24 AS D24, 25 AS D25, 26 AS D26, 27 AS D27, 28 AS D28, 29 AS D29, 30 AS D30, 31 AS D31,
                        32 AS D32)
                        ) ORDER BY FISCAL_YEAR_MONTH_NO) PL
                        LEFT JOIN 
                        (SELECT 
CAD.EMPLOYEE_ID,CMC.MONTH_ID,
    COUNT(case  when CAD.OVERALL_STATUS  IN ('TV','TN','PR','BA','LA','TP','LP','VP') then 1 end) AS PRESENT,
    COUNT(case OVERALL_STATUS when 'AB' then 1 end) AS ABSENT,
    COUNT(case OVERALL_STATUS when 'LV' then 1 end) AS LEAVE,
    COUNT(case OVERALL_STATUS when 'DO' then 1 end) AS DAYOFF,
    COUNT(case OVERALL_STATUS when 'HD' then 1 end) AS HOLIDAY,
    COUNT(case OVERALL_STATUS when 'WD' then 1 end) AS WORK_DAYOFF,
    COUNT(case OVERALL_STATUS when 'WH' then 1 end) AS WORK_HOLIDAY
FROM HRIS_ATTENDANCE_DETAIL CAD
JOIN HRIS_MONTH_CODE CMC ON (CMC.FISCAL_YEAR_ID={$fiscalYearId} AND CAD.ATTENDANCE_DT BETWEEN CMC.FROM_DATE AND CMC.TO_DATE)
WHERE EMPLOYEE_ID={$employeeId} 
GROUP BY CAD.EMPLOYEE_ID,CMC.MONTH_ID)CL ON (PL.MONTH_ID=CL.MONTH_ID)

           
EOT;
    $statement = $this->adapter->query($sql);
    $result = $statement->execute();
    return Helper::extractDbData($result);
  }

  public function getMonthlyAllowance($searchQuery)
  {
    $fromDate = $searchQuery['fromDate'];
    $toDate = $searchQuery['toDate'];

    $searchConditon = EntityHelper::getSearchConditon($searchQuery['companyId'], $searchQuery['branchId'], $searchQuery['departmentId'], $searchQuery['positionId'], $searchQuery['designationId'], $searchQuery['serviceTypeId'], $searchQuery['serviceEventTypeId'], $searchQuery['employeeTypeId'], $searchQuery['employeeId']);
    $sql = "SELECT  
EMPLOYEE_ID,
EMPLOYEE_CODE,
FULL_NAME,
        COMPANY_NAME,
        BRANCH_NAME,
        DEPARTMENT_NAME,
        DESIGNATION_TITLE,
        POSITION_NAME,
        SUM(SYSTEM_OVERTIME) AS SYSTEM_OVERTIME,
        SUM(MANUAL_OVERTIME) AS MANUAL_OVERTIME,
        SUM(FOOD_ALLOWANCE) AS FOOD_ALLOWANCE,
        SUM(SHIFT_ALLOWANCE) AS SHIFT_ALLOWANCE,
        SUM(NIGHT_SHIFT_ALLOWANCE) AS NIGHT_SHIFT_ALLOWANCE,
        SUM(HOLIDAY_COUNT) AS HOLIDAY_COUNT
FROM 
(SELECT
E.EMPLOYEE_ID,
E.EMPLOYEE_CODE,
        E.FULL_NAME,
        C.COMPANY_NAME,
        B.BRANCH_NAME,
        D.DEPARTMENT_NAME,
        DES.DESIGNATION_TITLE,
        P.POSITION_NAME,
        CASE WHEN 
        AD.OT_MINUTES >=0 then ROUND(AD.OT_MINUTES/60,2)
        ELSE
        0
        END AS SYSTEM_OVERTIME,
        CASE WHEN 
        OM.OVERTIME_HOUR IS NOT NULL THEN
        ROUND(OM.OVERTIME_HOUR,2)
        WHEN AD.OT_MINUTES >=0 then ROUND(AD.OT_MINUTES/60,2)
        ELSE
        0
        END
        AS MANUAL_OVERTIME,
        AD.FOOD_ALLOWANCE,
        AD.SHIFT_ALLOWANCE,
        AD.NIGHT_SHIFT_ALLOWANCE,
        AD.HOLIDAY_COUNT
        FROM HRIS_ATTENDANCE_DETAIL AD
        LEFT JOIN HRIS_OVERTIME_MANUAL OM ON (OM.EMPLOYEE_ID=AD.EMPLOYEE_ID AND OM.ATTENDANCE_DATE=AD.ATTENDANCE_DT)
        LEFT JOIN HRIS_EMPLOYEES E ON (AD.EMPLOYEE_ID=E.EMPLOYEE_ID)
        LEFT JOIN HRIS_COMPANY C ON (C.COMPANY_ID=E.COMPANY_ID)
        LEFT JOIN HRIS_BRANCHES B ON (B.BRANCH_ID=E.BRANCH_ID)
        LEFT JOIN HRIS_DEPARTMENTS D ON (D.DEPARTMENT_ID=E.DEPARTMENT_ID)
        LEFT JOIN HRIS_DESIGNATIONS DES ON (DES.DESIGNATION_ID=E.DESIGNATION_ID)
        LEFT JOIN HRIS_POSITIONS P ON (P.POSITION_ID=E.POSITION_ID)
        WHERE AD.Attendance_Dt
        BETWEEN TO_DATE('{$fromDate}','DD-MON-YYYY') AND TO_DATE('{$toDate}','DD-MON-YYYY') {$searchConditon}
        ) TAB_A
        GROUP BY EMPLOYEE_ID,
EMPLOYEE_CODE,
FULL_NAME,
        COMPANY_NAME,
        BRANCH_NAME, 
        DEPARTMENT_NAME,
        DESIGNATION_TITLE,
        POSITION_NAME ";
    $statement = $this->adapter->query($sql);
    $result = $statement->execute();
    return Helper::extractDbData($result);
  }

  public function departmentWiseAttdReport($companyId, $date1, $date2)
  {
    if ($companyId == 0) {
      $sql = <<<EOT
          SELECT *
          FROM (SELECT 
          DEPARTMENT_NAME,
          OVERALL_STATUS,
          COUNT(OVERALL_STATUS) AS TOTAL
          FROM (select
          HE.DEPARTMENT_ID,
          HD.DEPARTMENT_NAME,
          CASE 
          WHEN HED.OVERALL_STATUS 
          IN ('TV','TN','PR','BA','LA','TP','LP','VP')
          THEN 'PR' 
          ELSE HED.OVERALL_STATUS END AS OVERALL_STATUS
          from HRIS_ATTENDANCE_DETAIL HED 
          JOIN HRIS_EMPLOYEES HE ON (HE.EMPLOYEE_ID=HED.EMPLOYEE_ID)
          JOIN HRIS_DEPARTMENTS HD ON(HD.DEPARTMENT_ID=HE.DEPARTMENT_ID)
          FULL OUTER JOIN HRIS_COMPANY HC ON(HC.COMPANY_ID=HD.COMPANY_ID) 
          WHERE HED.ATTENDANCE_DT BETWEEN '$date1' and '$date2'
          )
          GROUP BY OVERALL_STATUS,DEPARTMENT_NAME)
          PIVOT (
          MAX(TOTAL) FOR OVERALL_STATUS IN ('PR' as PR,'WD' as WD,'HD' as HD,'LV' as LV,'WH' as WH,'DO' as DO,'AB' as AB)
          )
EOT;
    } else {
      $sql = <<<EOT
        SELECT *
        FROM (SELECT 
        DEPARTMENT_NAME,
        OVERALL_STATUS,
        COUNT(OVERALL_STATUS) AS TOTAL
        FROM (select
        HE.DEPARTMENT_ID,
        HD.DEPARTMENT_NAME,
        CASE 
        WHEN HED.OVERALL_STATUS 
        IN ('TV','TN','PR','BA','LA','TP','LP','VP')
        THEN 'PR' 
        ELSE HED.OVERALL_STATUS END AS OVERALL_STATUS
        from HRIS_ATTENDANCE_DETAIL HED 
        JOIN HRIS_EMPLOYEES HE ON (HE.EMPLOYEE_ID=HED.EMPLOYEE_ID)
        JOIN HRIS_DEPARTMENTS HD ON(HD.DEPARTMENT_ID=HE.DEPARTMENT_ID)
        FULL OUTER JOIN HRIS_COMPANY HC ON(HC.COMPANY_ID=HD.COMPANY_ID) 
        WHERE HED.ATTENDANCE_DT BETWEEN '$date1' and '$date2' 
        AND HE.COMPANY_ID = $companyId
        )
        GROUP BY OVERALL_STATUS,DEPARTMENT_NAME)
        PIVOT (
        MAX(TOTAL) FOR OVERALL_STATUS IN ('PR' as PR,'WD' as WD,'HD' as HD,'LV' as LV,'WH' as WH,'DO' as DO,'AB' as AB)
        )
EOT;
    }

    //        echo $sql;
    //        die();
    $statement = $this->adapter->query($sql);
    $result = $statement->execute();
    return Helper::extractDbData($result);
  }

  public function getAllCompanies()
  {
    $sql = "SELECT COMPANY_ID, COMPANY_NAME FROM HRIS_COMPANY";

    $statement = $this->adapter->query($sql);
    $result = $statement->execute();
    return Helper::extractDbData($result);
  }

  public function fetchBirthdays($by)
  {
    $orderByString = EntityHelper::getOrderBy('E.FULL_NAME ASC', null, 'E.SENIORITY_LEVEL', 'P.LEVEL_NO', 'E.JOIN_DATE', 'DES.ORDER_NO', 'E.FULL_NAME');
    $columIfSynergy = "";
    $joinIfSyngery = "";
    if ($this->checkIfTableExists("FA_CHART_OF_ACCOUNTS_SETUP")) {
      $columIfSynergy = "FCAS.ACC_EDESC AS BANK_ACCOUNT,";
      $joinIfSyngery = "LEFT JOIN FA_CHART_OF_ACCOUNTS_SETUP FCAS 
              ON(FCAS.ACC_CODE=E.ID_ACC_CODE AND C.COMPANY_CODE=FCAS.COMPANY_CODE)";
    }
    $fromDate = !empty($_POST['fromDate']) ? $_POST['fromDate'] : '01-Jan-2019';
    $toDate = !empty($_POST['toDate']) ? $_POST['toDate'] : '31-Dec-2019';

    $condition = EntityHelper::getSearchConditonBounded($by['companyId'], $by['branchId'], $by['departmentId'], $by['positionId'], $by['designationId'], $by['serviceTypeId'], $by['serviceEventTypeId'], $by['employeeTypeId'], $by['employeeId'], $by['genderId'], $by['locationId'], $by['functionalTypeId']);
    $boundedParameter = [];
    $boundedParameter = array_merge($boundedParameter, $condition['parameter']);
    $sql = "SELECT  
          {$columIfSynergy}
              E.ID_ACCOUNT_NO  AS ID_ACCOUNT_NO,
                E.EMPLOYEE_ID                                                AS EMPLOYEE_ID,
                E.EMPLOYEE_CODE                                                   AS EMPLOYEE_CODE,
                INITCAP(E.FULL_NAME)                                              AS FULL_NAME,
                INITCAP(G.GENDER_NAME)                                            AS GENDER_NAME,
                TO_CHAR(E.BIRTH_DATE, 'DD-MON-YYYY')                              AS BIRTH_DATE_AD,
                BS_DATE(E.BIRTH_DATE)                                             AS BIRTH_DATE_BS,
                TO_CHAR(E.JOIN_DATE, 'DD-MON-YYYY')                               AS JOIN_DATE_AD,
                BS_DATE(E.JOIN_DATE)                                              AS JOIN_DATE_BS,
                INITCAP(CN.COUNTRY_NAME)                                          AS COUNTRY_NAME,
                RG.RELIGION_NAME                                                  AS RELIGION_NAME,
                BG.BLOOD_GROUP_CODE                                               AS BLOOD_GROUP_CODE,
                E.MOBILE_NO                                                       AS MOBILE_NO,
                E.TELEPHONE_NO                                                    AS TELEPHONE_NO,
                E.SOCIAL_ACTIVITY                                                 AS SOCIAL_ACTIVITY,
                E.EXTENSION_NO                                                    AS EXTENSION_NO,
                E.EMAIL_OFFICIAL                                                  AS EMAIL_OFFICIAL,
                E.EMAIL_PERSONAL                                                  AS EMAIL_PERSONAL,
                E.SOCIAL_NETWORK                                                  AS SOCIAL_NETWORK,
                E.ADDR_PERM_HOUSE_NO                                              AS ADDR_PERM_HOUSE_NO,
                E.ADDR_PERM_WARD_NO                                               AS ADDR_PERM_WARD_NO,
                E.ADDR_PERM_STREET_ADDRESS                                        AS ADDR_PERM_STREET_ADDRESS,
                CNP.COUNTRY_NAME                                                  AS ADDR_PERM_COUNTRY_NAME,
                ZP.ZONE_NAME                                                      AS ADDR_PERM_ZONE_NAME,
                DP.DISTRICT_NAME                                                  AS ADDR_PERM_DISTRICT_NAME,
                INITCAP(VMP.VDC_MUNICIPALITY_NAME)                                AS VDC_MUNICIPALITY_NAME_PERM,
                E.ADDR_TEMP_HOUSE_NO                                              AS ADDR_TEMP_HOUSE_NO,
                E.ADDR_TEMP_WARD_NO                                               AS ADDR_TEMP_WARD_NO,
                E.ADDR_TEMP_STREET_ADDRESS                                        AS ADDR_TEMP_STREET_ADDRESS,
                CNT.COUNTRY_NAME                                                  AS ADDR_TEMP_COUNTRY_NAME,
                ZT.ZONE_NAME                                                      AS ADDR_TEMP_ZONE_NAME,
                DT.DISTRICT_NAME                                                  AS ADDR_TEMP_DISTRICT_NAME,
                VMT.VDC_MUNICIPALITY_NAME                                         AS VDC_MUNICIPALITY_NAME_TEMP,
                E.EMRG_CONTACT_NAME                                               AS EMRG_CONTACT_NAME,
                E.EMERG_CONTACT_RELATIONSHIP                                      AS EMERG_CONTACT_RELATIONSHIP,
                E.EMERG_CONTACT_ADDRESS                                           AS EMERG_CONTACT_ADDRESS,
                E.EMERG_CONTACT_NO                                                AS EMERG_CONTACT_NO,
                E.FAM_FATHER_NAME                                                 AS FAM_FATHER_NAME,
                E.FAM_FATHER_OCCUPATION                                           AS FAM_FATHER_OCCUPATION,
                E.FAM_MOTHER_NAME                                                 AS FAM_MOTHER_NAME,
                E.FAM_MOTHER_OCCUPATION                                           AS FAM_MOTHER_OCCUPATION,
                E.FAM_GRAND_FATHER_NAME                                           AS FAM_GRAND_FATHER_NAME,
                E.FAM_GRAND_MOTHER_NAME                                           AS FAM_GRAND_MOTHER_NAME,
                E.MARITAL_STATUS                                                  AS MARITAL_STATUS,
                E.FAM_SPOUSE_NAME                                                 AS FAM_SPOUSE_NAME,
                E.FAM_SPOUSE_OCCUPATION                                           AS FAM_SPOUSE_OCCUPATION,
                INITCAP(TO_CHAR(E.FAM_SPOUSE_BIRTH_DATE, 'DD-MON-YYYY'))          AS FAM_SPOUSE_BIRTH_DATE,
                INITCAP(TO_CHAR(E.FAM_SPOUSE_WEDDING_ANNIVERSARY, 'DD-MON-YYYY')) AS FAM_SPOUSE_WEDDING_ANNIVERSARY,
                E.ID_CARD_NO                                                      AS ID_CARD_NO,
                E.ID_LBRF                                                         AS ID_LBRF,
                E.ID_BAR_CODE                                                     AS ID_BAR_CODE,
                E.ID_PROVIDENT_FUND_NO                                            AS ID_PROVIDENT_FUND_NO,
                E.ID_DRIVING_LICENCE_NO                                           AS ID_DRIVING_LICENCE_NO,
                E.ID_DRIVING_LICENCE_TYPE                                         AS ID_DRIVING_LICENCE_TYPE,
                INITCAP(TO_CHAR(E.ID_DRIVING_LICENCE_EXPIRY, 'DD-MON-YYYY'))      AS ID_DRIVING_LICENCE_EXPIRY,
                E.ID_THUMB_ID                                                     AS ID_THUMB_ID,
                E.ID_PAN_NO                                                       AS ID_PAN_NO,
                E.ID_ACCOUNT_NO                                                   AS ID_ACCOUNT_NO,
                E.ID_RETIREMENT_NO                                                AS ID_RETIREMENT_NO,
                E.ID_CITIZENSHIP_NO                                               AS ID_CITIZENSHIP_NO,
                INITCAP(TO_CHAR(E.ID_CITIZENSHIP_ISSUE_DATE, 'DD-MON-YYYY'))      AS ID_CITIZENSHIP_ISSUE_DATE,
                E.ID_CITIZENSHIP_ISSUE_PLACE                                      AS ID_CITIZENSHIP_ISSUE_PLACE,
                E.ID_PASSPORT_NO                                                  AS ID_PASSPORT_NO,
                INITCAP(TO_CHAR(E.ID_PASSPORT_EXPIRY, 'DD-MON-YYYY'))             AS ID_PASSPORT_EXPIRY,
                C.COMPANY_NAME                                                    AS COMPANY_NAME,
                B.BRANCH_NAME                                                     AS BRANCH_NAME,
                D.DEPARTMENT_NAME                                                 AS DEPARTMENT_NAME,
                DES.DESIGNATION_TITLE                                             AS DESIGNATION_TITLE,
                P.POSITION_NAME                                                   AS POSITION_NAME,
                P.LEVEL_NO                                                        AS LEVEL_NO,
                INITCAP(ST.SERVICE_TYPE_NAME)                                     AS SERVICE_TYPE_NAME,
                (CASE WHEN E.EMPLOYEE_TYPE='R' THEN 'REGULAR' ELSE 'WORKER' END)  AS EMPLOYEE_TYPE,
                LOC.LOCATION_EDESC                                                AS LOCATION_EDESC,
                FUNT.FUNCTIONAL_TYPE_EDESC                                        AS FUNCTIONAL_TYPE_EDESC,
                FUNL.FUNCTIONAL_LEVEL_NO                                          AS FUNCTIONAL_LEVEL_NO,
                FUNL.FUNCTIONAL_LEVEL_EDESC                                       AS FUNCTIONAL_LEVEL_EDESC,
                E.SALARY                                                          AS SALARY,
                E.SALARY_PF                                                       AS SALARY_PF,
                E.REMARKS                                                         AS REMARKS
              FROM HRIS_EMPLOYEES E
              LEFT JOIN HRIS_COMPANY C
              ON E.COMPANY_ID=C.COMPANY_ID
              LEFT JOIN HRIS_BRANCHES B
              ON E.BRANCH_ID=B.BRANCH_ID
              LEFT JOIN HRIS_DEPARTMENTS D
              ON E.DEPARTMENT_ID=D.DEPARTMENT_ID
              LEFT JOIN HRIS_DESIGNATIONS DES
              ON E.DESIGNATION_ID=DES.DESIGNATION_ID
              LEFT JOIN HRIS_POSITIONS P
              ON E.POSITION_ID=P.POSITION_ID
              LEFT JOIN HRIS_SERVICE_TYPES ST
              ON E.SERVICE_TYPE_ID=ST.SERVICE_TYPE_ID
              LEFT JOIN HRIS_GENDERS G
              ON E.GENDER_ID=G.GENDER_ID
              LEFT JOIN HRIS_BLOOD_GROUPS BG
              ON E.BLOOD_GROUP_ID=BG.BLOOD_GROUP_ID
              LEFT JOIN HRIS_RELIGIONS RG
              ON E.RELIGION_ID=RG.RELIGION_ID
              LEFT JOIN HRIS_COUNTRIES CN
              ON E.COUNTRY_ID=CN.COUNTRY_ID
              LEFT JOIN HRIS_COUNTRIES CNP
              ON (E.ADDR_PERM_COUNTRY_ID=CNP.COUNTRY_ID)
              LEFT JOIN HRIS_ZONES ZP
              ON (E.ADDR_PERM_ZONE_ID=ZP.ZONE_ID)
              LEFT JOIN HRIS_DISTRICTS DP
              ON (E.ADDR_PERM_DISTRICT_ID=DP.DISTRICT_ID)
              LEFT JOIN HRIS_VDC_MUNICIPALITIES VMP
              ON E.ADDR_PERM_VDC_MUNICIPALITY_ID=VMP.VDC_MUNICIPALITY_ID
              LEFT JOIN HRIS_COUNTRIES CNT
              ON (E.ADDR_TEMP_COUNTRY_ID=CNT.COUNTRY_ID)
              LEFT JOIN HRIS_ZONES ZT
              ON (E.ADDR_TEMP_ZONE_ID=ZT.ZONE_ID)
              LEFT JOIN HRIS_DISTRICTS DT
              ON (E.ADDR_TEMP_DISTRICT_ID=DT.DISTRICT_ID)
              LEFT JOIN HRIS_VDC_MUNICIPALITIES VMT
              ON E.ADDR_TEMP_VDC_MUNICIPALITY_ID=VMT.VDC_MUNICIPALITY_ID
              LEFT JOIN HRIS_LOCATIONS LOC
              ON E.LOCATION_ID=LOC.LOCATION_ID
              LEFT JOIN HRIS_FUNCTIONAL_TYPES FUNT
              ON E.FUNCTIONAL_TYPE_ID=FUNT.FUNCTIONAL_TYPE_ID
              LEFT JOIN HRIS_FUNCTIONAL_LEVELS FUNL
              ON E.FUNCTIONAL_LEVEL_ID=FUNL.FUNCTIONAL_LEVEL_ID
              {$joinIfSyngery}
              WHERE 1=1 AND 
              to_number(to_char(TO_DATE(E.BIRTH_DATE, 'DD-MON-YY'), 'MMDD')) 
              BETWEEN to_number(to_char(TO_DATE('{$fromDate}', 'DD-MON-YY') , 'MMDD'))
              AND to_number(to_char(TO_DATE('{$toDate}', 'DD-MON-YY') , 'MMDD'))
              AND E.STATUS='E' 
              {$condition['sql']}
              {$orderByString}";

    return $this->rawQuery($sql, $boundedParameter);
  }

  public function fetchJobDurationReport($by)
  {
    $orderByString = EntityHelper::getOrderBy('E.FULL_NAME ASC', null, 'E.SENIORITY_LEVEL', 'P.LEVEL_NO', 'E.JOIN_DATE', 'DES.ORDER_NO', 'E.FULL_NAME');

    $condition = EntityHelper::getSearchConditon($by['companyId'], $by['branchId'], $by['departmentId'], $by['positionId'], $by['designationId'], $by['serviceTypeId'], $by['serviceEventTypeId'], $by['employeeTypeId'], $by['employeeId'], $by['genderId'], $by['locationId'], $by['functionalTypeId']);
    //         $sql = "SELECT E.EMPLOYEE_CODE, E.FULL_NAME, E.JOIN_DATE DOJ, E.BIRTH_DATE DOB,
    // P.Position_Name,
    //     Des.Designation_Title,
    //     D.Department_Name,
    //     Funt.Functional_Type_Edesc,        
    //      St.Service_Type_Name,
    //      aaa.Basic,aaa.Grade,aaa.Allowance,aaa.Gross,
    //     TRUNC((SYSDATE-BIRTH_DATE)/365)||' Years '||TRUNC(((SYSDATE-BIRTH_DATE)/365-TRUNC((SYSDATE-BIRTH_DATE)/365))*365)||' Days' AGE ,
    //     TRUNC((SYSDATE-JOIN_DATE)/365)||' Years '||TRUNC(((SYSDATE-JOIN_DATE)/365-TRUNC((SYSDATE-JOIN_DATE)/365))*365)||' Days' SERVICE_DURATION
    //     FROM HRIS_EMPLOYEES E 
    //     LEFT JOIN HRIS_DESIGNATIONS DES
    //       ON E.DESIGNATION_ID=DES.DESIGNATION_ID 
    //       LEFT JOIN HRIS_POSITIONS P
    //       ON E.POSITION_ID=P.POSITION_ID
    //       LEFT JOIN hris_departments d on d.department_id=e.department_id
    //     left join Hris_Functional_Types funt on funt.Functional_Type_Id=e.Functional_Type_Id
    //     left join Hris_Service_Types st on (st.service_type_id=E.Service_Type_Id)
    //     left join 
    //     (select 
    // *
    //  from 
    //  (select 
    //   bb.employee_id,bb.sheet_no,
    //   bb.variable_type,bb.total
    //  from 
    // (SELECT 
    // aa.employee_id,aa.sheet_no,v.variance_id,v.variable_type,sum(ssd.val) as total
    // FROM (select a.*,b.sheet_no from (select  max(month_id) as month_id,employee_id from
    // Hris_Salary_Sheet_Emp_Detail group by employee_id) a
    // left join Hris_Salary_Sheet_Emp_Detail b on (a.employee_id=b.employee_id and a.month_id=b.month_id)
    // ) aa
    // left join Hris_Salary_Sheet_Detail ssd on (aa.sheet_no=ssd.sheet_no and aa.employee_id=ssd.employee_id)
    // left join (select * from HRIS_VARIANCE where variable_type in 
    // ('B','C','A','G')
    // ) v  on (1=1)
    //   join hris_variance_payhead vp on (v.variance_id=vp.variance_id and ssd.pay_id=vp.pay_id)
    // group by aa.employee_id,aa.sheet_no,v.variance_id,v.variable_type
    // )bb) 
    // PIVOT ( 
    // SUM(total) FOR variable_type 
    //                 IN ('B' as Basic,'C' as Grade
    //                 ,'A' as Allowance,'G' as Gross))) aaa on (aaa.employee_id=e.employee_id)
    //       WHERE E.STATUS='E' AND E.RETIRED_FLAG='N' AND E.RESIGNED_FLAG='N'
    //     AND 1=1  
    //             {$condition}
    //             {$orderByString}";

    $sql = "SELECT E.EMPLOYEE_CODE, E.FULL_NAME, E.JOIN_DATE DOJ, E.BIRTH_DATE DOB,
P.Position_Name, e.salary, e.allowance, (nvl(e.salary, 0)+nvl(e.allowance, 0)) gross,
    Des.Designation_Title,
    D.Department_Name,
    Funt.Functional_Type_Edesc,        
     St.Service_Type_Name,
    TRUNC((SYSDATE-BIRTH_DATE)/365)||' Years '||TRUNC(((SYSDATE-BIRTH_DATE)/365-TRUNC((SYSDATE-BIRTH_DATE)/365))*365)||' Days' AGE ,
    TRUNC((SYSDATE-JOIN_DATE)/365)||' Years '||TRUNC(((SYSDATE-JOIN_DATE)/365-TRUNC((SYSDATE-JOIN_DATE)/365))*365)||' Days' SERVICE_DURATION
    FROM HRIS_EMPLOYEES E 
    LEFT JOIN HRIS_DESIGNATIONS DES
      ON E.DESIGNATION_ID=DES.DESIGNATION_ID 
      LEFT JOIN HRIS_POSITIONS P
      ON E.POSITION_ID=P.POSITION_ID
      LEFT JOIN hris_departments d on d.department_id=e.department_id
    left join Hris_Functional_Types funt on funt.Functional_Type_Id=e.Functional_Type_Id
    left join Hris_Service_Types st on (st.service_type_id=E.Service_Type_Id)
      WHERE E.STATUS='E' AND E.RETIRED_FLAG='N' AND E.RESIGNED_FLAG='N'
    AND 1=1  
            {$condition}
            {$orderByString}";
    //echo $sql; die;
    return $this->rawQuery($sql);
  }

  public function fetchWeeklyWorkingHoursReport($by)
  {
    $condition = EntityHelper::getSearchConditon($by['companyId'], $by['branchId'], $by['departmentId'], $by['positionId'], $by['designationId'], $by['serviceTypeId'], $by['serviceEventTypeId'], $by['employeeTypeId'], $by['employeeId'], $by['genderId'], $by['locationId'], $by['functionalTypeId']);

    $toDate = !empty($_POST['toDate']) ? $_POST['toDate'] : date('d-M-y', strtotime('now'));
    $toDate = date('d-M-y', strtotime($toDate));
    $fromDate = strtotime($toDate);
    $fromDate = strtotime("-6 day", $fromDate);
    $fromDate = date('d-M-y', $fromDate);

    $sql = "select * from (SELECT E.EMPLOYEE_CODE,AD.EMPLOYEE_ID,E.FULL_NAME,
    TO_CHAR(ATTENDANCE_DT,'DY') AS WEEKNAME,
    E.DEPARTMENT_ID,
    D.DEPARTMENT_NAME,
      CASE WHEN AD.OVERALL_STATUS='DO'
      THEN
      0
      ELSE
      HS.TOTAL_WORKING_HR/60 
      END AS ASSIGNED_HOUR ,
         CASE WHEN AD.TOTAL_HOUR IS NOT NULL THEN
         ROUND (AD.TOTAL_HOUR / 60)
         ELSE
         0
         END
         AS WORKED_HOUR,
         AD.OVERALL_STATUS
       --  AD.ATTENDANCE_DT
    FROM HRIS_ATTENDANCE_DETAIL AD
     LEFT JOIN  HRIS_EMPLOYEES E ON (AD.EMPLOYEE_ID=E.EMPLOYEE_ID)
     LEFT JOIN  HRIS_SHIFTS HS ON (AD.SHIFT_ID = HS.SHIFT_ID)
    LEFT JOIN HRIS_DEPARTMENTS D  ON (D.DEPARTMENT_ID=E.DEPARTMENT_ID)
    LEFT JOIN HRIS_DESIGNATIONS DES ON (E.DESIGNATION_ID=DES.DESIGNATION_ID) 
      LEFT JOIN HRIS_POSITIONS P ON (E.POSITION_ID=P.POSITION_ID)
    WHERE 
     E.STATUS='E'
    AND E.RETIRED_FLAG='N' {$condition} 
    AND E.RESIGNED_FLAG='N' 
    AND ATTENDANCE_DT BETWEEN TO_DATE('{$fromDate}', 'DD-MON-YY') 
    AND TO_DATE('{$toDate}', 'DD-MON-YY')
    ORDER BY DEPARTMENT_ID,FULL_NAME, ATTENDANCE_DT)
    PIVOT ( MAX( ASSIGNED_HOUR ) AS AH, MAX( WORKED_HOUR ) AS WH,MAX( OVERALL_STATUS ) AS OS
    FOR WEEKNAME 
    IN ( 'TUE' AS TUE,'WED' AS WED,'THU' AS THU,'FRI' AS FRI,'SAT' AS SAT,'SUN' AS SUN,'MON' AS MON)
    )";

    return $this->rawQuery($sql);
  }

  public function getDays()
  {
    $toDate = !empty($_POST['toDate']) ? $_POST['toDate'] : date('d-M-y', strtotime('now'));
    $toDate = "TO_DATE('{$toDate}')";

    $sql = "SELECT   trunc($toDate-6) + ROWNUM -1  AS DATES,
    ROWNUM AS DAY_COUNT,
    trunc($toDate-6) AS FROM_DATE,
    TO_CHAR(trunc($toDate-6) + ROWNUM -1,'D') AS WEEKDAY,
    TO_CHAR(trunc($toDate-6) + ROWNUM -1,'DAY') AS WEEKNAME
    FROM dual d
    CONNECT BY  rownum <=  $toDate -  trunc($toDate-6) + 1";

    return $this->rawQuery($sql);
  }

  //   public function fetchRosterReport($data, $dates)
  //   {
  //     $employeeId = $data['employeeId'];
  //     $companyId = $data['companyId'];
  //     $branchId = $data['branchId'];
  //     $departmentId = $data['departmentId'];
  //     $designationId = $data['designationId'];
  //     $positionId = $data['positionId'];
  //     $serviceTypeId = $data['serviceTypeId'];
  //     $serviceEventTypeId = $data['serviceEventTypeId'];
  //     $employeeTypeId = $data['employeeTypeId'];

  //     $searchCondition = EntityHelper::getSearchConditonBounded($companyId, $branchId, $departmentId, $positionId, $designationId, $serviceTypeId, $serviceEventTypeId, $employeeTypeId, $employeeId);

  //     $boundedParameter = [];
  //     $boundedParameter = array_merge($boundedParameter, $searchCondition['parameter']);

  //     $datesIn = "'";
  //     for ($i = 0; $i < count($dates); $i++) {
  //       $i == 0 ? $datesIn .= $dates[$i] . "' as DATE_" . str_replace('-', '_', $dates[$i]) : $datesIn .= ",'" . $dates[$i] . "' as DATE_" . str_replace('-', '_', $dates[$i]);
  //     }
  //     $sql = "
  // SELECT *
  // FROM
  //   (SELECT E.FULL_NAME,
  //     E.EMPLOYEE_CODE,
  //     R.FOR_DATE,
  //     S.SHIFT_ENAME as SHIFT_NAME
  //   FROM HRIS_EMPLOYEE_SHIFT_ROASTER R
  //   JOIN HRIS_SHIFTS S
  //   ON (S.SHIFT_ID = R.SHIFT_ID)
  //   FULL OUTER JOIN HRIS_EMPLOYEES E
  //   ON (E.EMPLOYEE_ID = R.EMPLOYEE_ID)
  //   WHERE 1=1 {$searchCondition['sql']}
  //   ) PIVOT ( MAX( SHIFT_NAME ) FOR FOR_DATE IN ($datesIn))";

  //     return $this->rawQuery($sql);
  //   }
  public function fetchRosterReport($data, $dates)
  {

    $employeeId = $data['employeeId'];
    $companyId = $data['companyId'];
    $branchId = $data['branchId'];
    $departmentId = $data['departmentId'];
    $designationId = $data['designationId'];
    $positionId = $data['positionId'];
    $serviceTypeId = $data['serviceTypeId'];
    $serviceEventTypeId = $data['serviceEventTypeId'];
    $employeeTypeId = $data['employeeTypeId'];

    $searchCondition = $this->getSearchConditon($companyId, $branchId, $departmentId, $positionId, $designationId, $serviceTypeId, $serviceEventTypeId, $employeeTypeId, $employeeId);

    $datesIn = "'";
    for ($i = 0; $i < count($dates); $i++) {
      $i == 0 ? $datesIn .= $dates[$i] . "' as DATE_" . str_replace('-', '_', $dates[$i]) : $datesIn .= ",'" . $dates[$i] . "' as DATE_" . str_replace('-', '_', $dates[$i]);
    }
    $sql = "
SELECT *
FROM
(SELECT E.FULL_NAME,
E.EMPLOYEE_CODE,
R.FOR_DATE,
S.SHIFT_ENAME as SHIFT_NAME
FROM HRIS_EMPLOYEE_SHIFT_ROASTER R
JOIN HRIS_SHIFTS S
ON (S.SHIFT_ID = R.SHIFT_ID)
FULL OUTER JOIN HRIS_EMPLOYEES E
ON (E.EMPLOYEE_ID = R.EMPLOYEE_ID)
WHERE 1=1 {$searchCondition}
) PIVOT ( MAX( SHIFT_NAME ) FOR FOR_DATE IN ($datesIn))";

    return $this->rawQuery($sql);
  }

  public function reportWithOTforShivam($data)
  {
    $fromCondition = "";
    $toCondition = "";

    $otFromCondition = "";
    $otToCondition = "";

    $condition = EntityHelper::getSearchConditonBounded($data['companyId'], $data['branchId'], $data['departmentId'], $data['positionId'], $data['designationId'], $data['serviceTypeId'], $data['serviceEventTypeId'], $data['employeeTypeId'], $data['employeeId'], $data['genderId'], $data['locationId']);

    $boundedParameter = [];
    $boundedParameter = array_merge($boundedParameter, $condition['parameter']);

    if (isset($data['fromDate']) && $data['fromDate'] != null && $data['fromDate'] != -1) {
      $fromDate = Helper::getExpressionDate($data['fromDate']);
      $fromCondition = "AND A.ATTENDANCE_DT >= :fromDate";
      $otFromCondition = "AND OVERTIME_DATE >= :fromDate";
      $boundedParameter['fromDate'] = $fromDate;
    }
    if (isset($data['toDate']) && $data['toDate'] != null && $data['toDate'] != -1) {
      $toDate = Helper::getExpressionDate($data['toDate']);
      $toCondition = "AND A.ATTENDANCE_DT <= :toDate";
      $otToCondition = "AND OVERTIME_DATE <= :toDate";
      $boundedParameter['todate'] = $toDate;
    }

    $monthId = $data['monthId'];
    $boundedParameter['monthId'] = $monthId;

    $sql = <<<EOT
            SELECT C.COMPANY_NAME,
              D.DEPARTMENT_NAME,
              A.EMPLOYEE_ID,
              E.EMPLOYEE_CODE,
              E.FULL_NAME,
              A.DAYOFF,
              A.PRESENT,
              A.HOLIDAY,
              A.LEAVE,
              A.PAID_LEAVE,
              A.UNPAID_LEAVE,
              A.ABSENT,
              NVL(ROUND(A.TOTAL_MIN/60,2),0) + NVL(AD.ADDITION,0) - NVL(AD.DEDUCTION,0) AS OVERTIME_HOUR,
              A.TRAVEL,
              A.TRAINING,
              A.WORK_ON_HOLIDAY,
              A.WORK_ON_DAYOFF,
              A.NIGHT_SHIFT_6,
              A.NIGHT_SHIFT_8,
              A.C_SHIFT,
              AD.ADDITION,
              AD.DEDUCTION
              ,ABDH
              ,LBDH
            FROM
              (SELECT A.EMPLOYEE_ID,
                SUM(
                CASE
                  WHEN A.OVERALL_STATUS IN( 'DO','WD')
                  THEN 1
                  ELSE 0
                END) AS DAYOFF,
                SUM(
                CASE
                  WHEN A.OVERALL_STATUS IN ('PR','BA','LA','TV','VP','TN','TP','LP')
                  THEN (
                    CASE
                      WHEN A.OVERALL_STATUS = 'LP'
                      AND A.HALFDAY_PERIOD IS NOT NULL
                      THEN 0.5
                      ELSE 1
                    END)
                  ELSE 0
                END) AS PRESENT,
                SUM(
                CASE
                  WHEN A.OVERALL_STATUS IN ('HD','WH')
                  THEN 1
                  ELSE 0
                END) AS HOLIDAY,
                SUM(
                CASE
                  WHEN A.OVERALL_STATUS IN ('LV','LP')
                  AND A.GRACE_PERIOD    IS NULL
                  THEN (
                    CASE
                      WHEN A.OVERALL_STATUS = 'LP'
                      AND A.HALFDAY_PERIOD IS NOT NULL
                      THEN 0.5
                      ELSE 1
                    END)
                  ELSE 0
                END) AS LEAVE,
                SUM(
                CASE
                  WHEN A.OVERALL_STATUS IN ('LV','LP')
                  AND A.GRACE_PERIOD    IS NULL
                  AND L.PAID             = 'Y'
                  THEN (
                    CASE
                      WHEN A.OVERALL_STATUS = 'LP'
                      AND A.HALFDAY_PERIOD IS NOT NULL
                      THEN 0.5
                      ELSE 1
                    END)
                  ELSE 0
                END) AS PAID_LEAVE,
                SUM(
                CASE
                  WHEN A.OVERALL_STATUS IN ('LV','LP')
                  AND A.GRACE_PERIOD    IS NULL
                  AND L.PAID             = 'N'
                  THEN (
                    CASE
                      WHEN A.OVERALL_STATUS = 'LP'
                      AND A.HALFDAY_PERIOD IS NOT NULL
                      THEN 0.5
                      ELSE 1
                    END)
                  ELSE 0
                END) AS UNPAID_LEAVE,
                SUM(
                CASE
                  WHEN A.SHIFT_ID = 35 AND OVERALL_STATUS IN ('TV','TN','PR','BA','LA','TP','LP','VP')
                  THEN 1
                  ELSE 0
                END) AS NIGHT_SHIFT_6,
                SUM(
                CASE
                  WHEN A.SHIFT_ID = 37 AND OVERALL_STATUS IN ('TV','TN','PR','BA','LA','TP','LP','VP')
                  THEN 1
                  ELSE 0
                END) AS NIGHT_SHIFT_8,
                SUM(
                CASE
                  WHEN A.SHIFT_ID = 32 AND OVERALL_STATUS IN ('TV','TN','PR','BA','LA','TP','LP','VP')
                  THEN 1
                  ELSE 0
                END) AS C_SHIFT,
                SUM(
                CASE
                  WHEN A.OVERALL_STATUS = 'AB'
                  THEN 1
                  ELSE 0
                END) AS ABSENT,
                SUM(
                CASE
                  WHEN A.OVERALL_STATUS= 'TV'
                  THEN 1
                  ELSE 0
                END) AS TRAVEL,
                SUM(
                CASE
                  WHEN A.OVERALL_STATUS ='TN'
                  THEN 1
                  ELSE 0
                END) AS TRAINING,
                SUM(
                CASE
                  WHEN A.OVERALL_STATUS = 'WH'
                  THEN 1
                  ELSE 0
                END) WORK_ON_HOLIDAY,
                SUM(
                CASE
                  WHEN A.OVERALL_STATUS ='WD'
                  THEN 1
                  ELSE 0
                END) WORK_ON_DAYOFF,
                 SUM(
                  CASE
                    WHEN OTM.OVERTIME_HOUR IS NULL
                    THEN OT.TOTAL_HOUR
                    ELSE OTM.OVERTIME_HOUR*60
                  END ) AS TOTAL_MIN
                ,sum(
                  case when A.OVERALL_STATUS in('DO','HD') and APY.OVERALL_STATUS='AB' and APT.OVERALL_STATUS='AB'
                  then 1 
                  end 
                  )as ABDH
                 ,sum(
                 case when A.OVERALL_STATUS in('DO','HD') and APY.OVERALL_STATUS='LV' and APT.OVERALL_STATUS='LV'
                 then 1 end
                 ) as LBDH
              FROM HRIS_ATTENDANCE_PAYROLL A
              LEFT JOIN HRIS_ATTENDANCE_PAYROLL APY on (A.ATTENDANCE_DT=APY.ATTENDANCE_DT-1 and A.employee_id=APY.EMPLOYEE_ID)
              LEFT JOIN HRIS_ATTENDANCE_PAYROLL APT on (A.ATTENDANCE_DT=APT.ATTENDANCE_DT+1 and A.employee_id=APT.EMPLOYEE_ID)
              LEFT JOIN (SELECT
    employee_id,
    overtime_date,
    SUM(total_hour) AS total_hour
FROM
    hris_overtime where status ='AP'
GROUP BY
    employee_id,
    overtime_date) OT
              ON (A.EMPLOYEE_ID   =OT.EMPLOYEE_ID
              AND A.ATTENDANCE_DT =OT.OVERTIME_DATE)
              LEFT JOIN HRIS_OVERTIME_MANUAL OTM
              ON (A.EMPLOYEE_ID   =OTM.EMPLOYEE_ID
              AND A.ATTENDANCE_DT =OTM.ATTENDANCE_DATE)
              LEFT JOIN HRIS_LEAVE_MASTER_SETUP L
              ON (A.LEAVE_ID= L.LEAVE_ID)
              WHERE 1       =1 {$fromCondition} {$toCondition}
              GROUP BY A.EMPLOYEE_ID
              ) A
            LEFT JOIN HRIS_EMPLOYEES E
            ON(A.EMPLOYEE_ID = E.EMPLOYEE_ID)
            LEFT JOIN HRIS_COMPANY C
            ON(E.COMPANY_ID= C.COMPANY_ID)
            LEFT JOIN HRIS_DEPARTMENTS D
            ON (E.DEPARTMENT_ID= D.DEPARTMENT_ID)
            LEFT JOIN HRIS_OVERTIME_A_D AD
            ON (A.EMPLOYEE_ID = AD.EMPLOYEE_ID AND AD.MONTH_ID = :monthId)
            WHERE 1 = 1 {$condition['sql']}
            ORDER BY C.COMPANY_NAME,
              D.DEPARTMENT_NAME,
              E.FULL_NAME 
EOT;
    return $this->rawQuery($sql, $boundedParameter);
    // $statement = $this->adapter->query($sql);
    // $result = $statement->execute();
    // return Helper::extractDbData($result);
  }

  public function employeeDailyReportShivam($searchQuery)
  {
    $monthDetail = $this->getMonthDetails($searchQuery['monthCodeId']);

    $pivotString = '';
    for ($i = 1; $i <= $monthDetail['DAYS']; $i++) {
      if ($i != $monthDetail['DAYS']) {
        $pivotString .= $i . ' AS ' . 'D' . $i . ', ';
      } else {
        $pivotString .= $i . ' AS ' . 'D' . $i;
      }
    }


    $leaveDetails = $this->getLeaveList();
    $leavePivotString = $this->getLeaveCodePivot($leaveDetails);
    $searchConditon = EntityHelper::getSearchConditon($searchQuery['companyId'], $searchQuery['branchId'], $searchQuery['departmentId'], $searchQuery['positionId'], $searchQuery['designationId'], $searchQuery['serviceTypeId'], $searchQuery['serviceEventTypeId'], $searchQuery['employeeTypeId'], $searchQuery['employeeId']);
    $sql = <<<EOT
                SELECT PL.*,MLD.*,
         CL.PRESENT,
         CL.ABSENT,
         CL.LEAVE,
         CL.DAYOFF,
         CL.HOLIDAY,
         CL.WORK_DAYOFF,
         CL.WORK_HOLIDAY,
         CL.NIGHT_SHIFT_6,
         CL.NIGHT_SHIFT_8,
         CL.C_SHIFT,
         (CL.PRESENT+CL.ABSENT+CL.LEAVE+CL.DAYOFF+CL.HOLIDAY+CL.WORK_DAYOFF+CL.WORK_HOLIDAY) AS TOTAL
       FROM
         (SELECT *
         FROM
           (SELECT E.FULL_NAME,
             AD.EMPLOYEE_ID,
             E.EMPLOYEE_CODE,
             CASE
               WHEN AD.OVERALL_STATUS IN ('TV','TN','PR','BA','LA','TP','LP','VP')
               THEN 'PR'
               WHEN AD.OVERALL_STATUS = 'LV' AND AD.HALFDAY_FLAG='N' THEN 'L'||'-'||LMS.LEAVE_CODE
               WHEN AD.OVERALL_STATUS = 'LV' AND AD.HALFDAY_FLAG!='N' THEN 'HL'||'-'||LMS.LEAVE_CODE
               ELSE AD.OVERALL_STATUS
             END AS OVERALL_STATUS,
             --AD.ATTENDANCE_DT,
             (AD.ATTENDANCE_DT-MC.FROM_DATE+1) AS DAY_COUNT
           FROM HRIS_ATTENDANCE_DETAIL AD
           LEFT JOIN HRIS_LEAVE_MASTER_SETUP LMS ON (AD.LEAVE_ID=LMS.LEAVE_ID)
           LEFT JOIN HRIS_MONTH_CODE MC
           ON (AD.ATTENDANCE_DT BETWEEN MC.FROM_DATE AND MC.TO_DATE)
           JOIN HRIS_EMPLOYEES E
           ON (E.EMPLOYEE_ID =AD.EMPLOYEE_ID)
           WHERE MC.MONTH_ID = {$searchQuery['monthCodeId']}
       {$searchConditon}
           ) PIVOT (MAX (OVERALL_STATUS) FOR DAY_COUNT IN ({$pivotString})) 
         ) PL
       LEFT JOIN
         (SELECT EMPLOYEE_ID,
           COUNT(
           CASE
             WHEN OVERALL_STATUS IN ('TV','TN','PR','BA','LA','TP','LP','VP')
             THEN 1
           END) AS PRESENT,
           COUNT(
           CASE OVERALL_STATUS
             WHEN 'AB'
             THEN 1
           END) AS ABSENT,
           COUNT(
           CASE OVERALL_STATUS
             WHEN 'LV'
             THEN 1
           END) AS LEAVE,
           COUNT(
           CASE OVERALL_STATUS
             WHEN 'DO'
             THEN 1
           END) AS DAYOFF,
           COUNT(
           CASE OVERALL_STATUS
             WHEN 'HD'
             THEN 1
           END) AS HOLIDAY,
           COUNT(
           CASE OVERALL_STATUS
             WHEN 'WD'
             THEN 1
           END) AS WORK_DAYOFF,
           COUNT(
           CASE OVERALL_STATUS
             WHEN 'WH'
             THEN 1
           END) AS WORK_HOLIDAY,
           COUNT(
           CASE 
           WHEN SHIFT_ID = 35 AND OVERALL_STATUS IN ('TV','TN','PR','BA','LA','TP','LP','VP')
             THEN 1
           END) AS NIGHT_SHIFT_6,
           COUNT(
           CASE 
           WHEN SHIFT_ID = 37 AND OVERALL_STATUS IN ('TV','TN','PR','BA','LA','TP','LP','VP')
             THEN 1
           END) AS NIGHT_SHIFT_8,
           COUNT(
           CASE 
           WHEN SHIFT_ID = 32 AND OVERALL_STATUS IN ('TV','TN','PR','BA','LA','TP','LP','VP')
             THEN 1
           END) AS C_SHIFT
         FROM HRIS_ATTENDANCE_DETAIL
         WHERE ATTENDANCE_DT BETWEEN TO_DATE('{$monthDetail['FROM_DATE']}','DD-MON-YY') AND TO_DATE('{$monthDetail['TO_DATE']}','DD-MON-YY')
         GROUP BY EMPLOYEE_ID
         )CL
       ON (PL.EMPLOYEE_ID=CL.EMPLOYEE_ID)
          LEFT JOIN
         (
         select 
 *
 from
 (select 
AD.employee_id,
AD.leave_id,
 sum(
 case AD.HALFDAY_FLAG
 when 'N'  then 1
 else 0.5 end
 ) as LTBM
from HRIS_ATTENDANCE_DETAIL AD
 LEFT JOIN HRIS_MONTH_CODE MC  ON (AD.ATTENDANCE_DT BETWEEN MC.FROM_DATE AND MC.TO_DATE)  
  WHERE 
 leave_id  is not null and
   MC.MONTH_ID = {$searchQuery['monthCodeId']}
   group by AD.employee_id,AD.leave_id
   )
   PIVOT ( MAX (LTBM) FOR LEAVE_ID IN (
   {$leavePivotString}
   )
   )
         ) MLD
       ON (PL.EMPLOYEE_ID=MLD.EMPLOYEE_ID)
                 
EOT;

    $statement = $this->adapter->query($sql);
    $result = $statement->execute();
    return ['leaveDetails' => $leaveDetails, 'monthDetail' => $monthDetail, 'data' => Helper::extractDbData($result)];
  }

  public function checkAge($data)
  {
    $greaterCondition = "";
    $lessCondition = "";
    $bothCondition = "";

    $boundedParameter = [];
    if ($data['greaterThan'] != null && $data['lessThan'] == null) {
      $greaterCondition = "AND E.AGE >= :greaterThan";
      $boundedParameter['greaterThan'] = $data['greaterThan'];
    }
    if ($data['greaterThan'] == null && $data['lessThan'] != null) {
      $lessCondition = "AND E.AGE <= :lessThan ";
      $boundedParameter['lessThan'] = $data['lessThan'];
    }
    if ($data['greaterThan'] != null && $data['lessThan'] != null) {
      $bothCondition = "AND E.AGE between :greaterThan and :lessThan ";
      $boundedParameter['greaterThan'] = $data['greaterThan'];
      $boundedParameter['lessThan'] = $data['lessThan'];
    }

    $sql = <<<EOT
                 SELECT E.EMPLOYEE_CODE,
                E.FULL_NAME,
                E.BIRTH_DATE,
                E.AGE,
                D.DEPARTMENT_NAME
              FROM
                (SELECT EMPLOYEE_CODE,
                  FULL_NAME,
                  DEPARTMENT_ID,
                  TO_CHAR(BIRTH_DATE, 'yyyy-MON-dd')           AS BIRTH_DATE,
                  TRUNC(months_between(sysdate,BIRTH_DATE)/12) AS AGE,
                  STATUS
                FROM HRIS_EMPLOYEES
                )E
              LEFT JOIN HRIS_DEPARTMENTS D
              ON (E.DEPARTMENT_ID = D.DEPARTMENT_ID) WHERE E.STATUS = 'E' {$greaterCondition} {$lessCondition} {$bothCondition}
EOT;

    return $this->rawQuery($sql, $boundedParameter);
    // $statement = $this->adapter->query($sql);
    // $result = $statement->execute();
    // return Helper::extractDbData($result);
  }

  public function checkContract($searchQuery)
  {
    $fromDate = $searchQuery['fromDate'];
    $toDate = $searchQuery['toDate'];
    $fromDateCondition = "";
    $toDateCondition = "";

    $boundedParameter = [];

    if ($fromDate != null) {
      $fromDateCondition = " AND (S.END_DATE >= :fromDate OR S.END_DATE IS NULL)";
      $boundedParameter['fromDate'] = $fromDate;
    }

    if ($toDate != null) {
      $toDateCondition = "AND (S.END_DATE <= :toDate OR S.END_DATE IS NULL) ";
      $boundedParameter['toDate'] = $toDate;
    }

    $searchCondition = EntityHelper::getSearchConditonBounded($searchQuery['companyId'], $searchQuery['branchId'], $searchQuery['departmentId'], $searchQuery['positionId'], $searchQuery['designationId'], $searchQuery['serviceTypeId'], $searchQuery['serviceEventTypeId'], $searchQuery['employeeTypeId'], $searchQuery['employeeId']);
    $boundedParameter = array_merge($boundedParameter, $searchCondition['parameter']);
    $sql = "SELECT S.*,
                E.FULL_NAME,
                E.EMPLOYEE_CODE,
                D.DEPARTMENT_NAME,
                B.BRANCH_NAME,
                Case WHEN
                (S.END_DATE >= TRUNC(SYSDATE) OR S.END_DATE IS NULL)
                THEN 'Not Expired'
                WHEN
                S.END_DATE < TRUNC(SYSDATE)
                THEN 'Expired'
                END AS CONTRACT_STATUS
              FROM
                (SELECT S1.*
                FROM
                  (SELECT JH.EMPLOYEE_ID,
                    JH.START_DATE,
                    JH.END_DATE,
                    TYPE,
                    TRUNC(months_between(END_DATE,sysdate)) AS REMAINING_MONTHS
                  FROM HRIS_JOB_HISTORY JH
                  JOIN HRIS_SERVICE_TYPES ST
                  ON JH.TO_SERVICE_TYPE_ID = ST.SERVICE_TYPE_ID
                  WHERE ST.TYPE            = 'CONTRACT'
                  AND JH.STATUS            = 'E'
                  ) S1
                INNER JOIN
                  (SELECT MAX(START_DATE) START_DATE,
                    EMPLOYEE_ID
                  FROM
                    (SELECT JH.EMPLOYEE_ID,
                      JH.START_DATE,
                      JH.END_DATE,
                      TYPE,
                      TRUNC(months_between(END_DATE,sysdate)) AS REMAINING_MONTHS
                    FROM HRIS_JOB_HISTORY JH
                    JOIN HRIS_SERVICE_TYPES ST
                    ON JH.TO_SERVICE_TYPE_ID = ST.SERVICE_TYPE_ID
                    WHERE ST.TYPE            = 'CONTRACT'
                    AND JH.STATUS            = 'E'
                    )
                  GROUP BY EMPLOYEE_ID
                  )S2 ON S1.EMPLOYEE_ID = S2.EMPLOYEE_ID
                AND S1.START_DATE       = S2.START_DATE
                ) S
              LEFT JOIN HRIS_EMPLOYEES E
              ON S.EMPLOYEE_ID = E.EMPLOYEE_ID
              LEFT JOIN HRIS_DEPARTMENTS D
              ON E.DEPARTMENT_ID = D.DEPARTMENT_ID
              LEFT JOIN HRIS_BRANCHES B
              ON E.BRANCH_ID = B.BRANCH_ID
              WHERE E.STATUS = 'E'
              {$searchCondition['sql']} {$fromDateCondition} {$toDateCondition}";

    $statement = $this->adapter->query($sql);
    $result = $statement->execute($boundedParameter);
    return Helper::extractDbData($result);
  }

  public function workingSummaryBetnDateReport($searchQuery)
  {
    $fromDate = $searchQuery['fromDate'];
    $toDate = $searchQuery['toDate'];

    $boundedParameter = [];
    $searchCondition = EntityHelper::getSearchConditonBounded($searchQuery['companyId'], $searchQuery['branchId'], $searchQuery['departmentId'], $searchQuery['positionId'], $searchQuery['designationId'], $searchQuery['serviceTypeId'], $searchQuery['serviceEventTypeId'], $searchQuery['employeeTypeId'], $searchQuery['employeeId']);
    $boundedParameter = array_merge($boundedParameter, $searchCondition['parameter']);
    $sql = "
            SELECT C.COMPANY_NAME,
              D.DEPARTMENT_NAME,
              A.EMPLOYEE_ID,
              E.EMPLOYEE_CODE,
              E.FULL_NAME,
              A.DAYOFF,
              A.PRESENT,
              A.HOLIDAY,
              A.LEAVE,
              A.PAID_LEAVE,
              A.UNPAID_LEAVE,
              A.ABSENT,
              NVL(ROUND(A.TOTAL_MIN/60,2),0) AS OVERTIME_HOUR,
              A.TRAVEL,
              A.TRAINING,
              A.WORK_ON_HOLIDAY,
              A.WORK_ON_DAYOFF,
              Min_To_Hour(A.TOTAL_WORKED_MINUTES) AS TOTAL_WORKED_HOUR
            FROM
              (select 
A.EMPLOYEE_ID,
                SUM(
                CASE
                  WHEN A.OVERALL_STATUS IN( 'DO','WD')
                  THEN 1
                  ELSE 0
                END) AS DAYOFF,
                SUM(
                CASE
                  WHEN A.OVERALL_STATUS IN ('PR','BA','LA','TV','VP','TN','TP','LP')
                  THEN (
                    CASE
                      WHEN A.OVERALL_STATUS = 'LP'
                      AND A.HALFDAY_PERIOD IS NOT NULL
                      THEN 0.5
                      ELSE 1
                    END)
                  ELSE 0
                END) AS PRESENT,
                SUM(
                CASE
                  WHEN A.OVERALL_STATUS IN ('HD','WH')
                  THEN 1
                  ELSE 0
                END) AS HOLIDAY,
                SUM(
                CASE
                  WHEN A.OVERALL_STATUS IN ('LV','LP')
                  AND A.GRACE_PERIOD    IS NULL
                  THEN (
                    CASE
                      WHEN A.OVERALL_STATUS = 'LP'
                      AND A.HALFDAY_PERIOD IS NOT NULL
                      THEN 0.5
                      ELSE 1
                    END)
                  ELSE 0
                END) AS LEAVE,
                SUM(
                CASE
                  WHEN A.OVERALL_STATUS IN ('LV','LP')
                  AND A.GRACE_PERIOD    IS NULL
                  AND L.PAID             = 'Y'
                  THEN (
                    CASE
                      WHEN A.OVERALL_STATUS = 'LP'
                      AND A.HALFDAY_PERIOD IS NOT NULL
                      THEN 0.5
                      ELSE 1
                    END)
                  ELSE 0
                END) AS PAID_LEAVE,
                SUM(
                CASE
                  WHEN A.OVERALL_STATUS IN ('LV','LP')
                  AND A.GRACE_PERIOD    IS NULL
                  AND L.PAID             = 'N'
                  THEN (
                    CASE
                      WHEN A.OVERALL_STATUS = 'LP'
                      AND A.HALFDAY_PERIOD IS NOT NULL
                      THEN 0.5
                      ELSE 1
                    END)
                  ELSE 0
                END) AS UNPAID_LEAVE,
                SUM(
                CASE
                  WHEN A.OVERALL_STATUS = 'AB'
                  THEN 1
                  ELSE 0
                END) AS ABSENT,
                SUM(
                CASE
                  WHEN A.OVERALL_STATUS= 'TV'
                  THEN 1
                  ELSE 0
                END) AS TRAVEL,
                SUM(
                CASE
                  WHEN A.OVERALL_STATUS ='TN'
                  THEN 1
                  ELSE 0
                END) AS TRAINING,
                SUM(
                CASE
                  WHEN A.OVERALL_STATUS = 'WH'
                  THEN 1
                  ELSE 0
                END) WORK_ON_HOLIDAY,
                SUM(
                CASE
                  WHEN A.OVERALL_STATUS ='WD'
                  THEN 1
                  ELSE 0
                END) WORK_ON_DAYOFF,
                 SUM(
                  CASE
                    WHEN OTM.OVERTIME_HOUR IS NULL
                    THEN OT.TOTAL_HOUR
                    ELSE OTM.OVERTIME_HOUR*60
                  END ) AS TOTAL_MIN
                  ,SUM(A.TOTAL_HOUR) TOTAL_WORKED_MINUTES
from Hris_Attendance_Detail A
LEFT JOIN (SELECT
    employee_id,
    overtime_date,
    SUM(total_hour) AS total_hour
FROM
    hris_overtime where status ='AP'
GROUP BY
    employee_id,
    overtime_date) OT
              ON (A.EMPLOYEE_ID   =OT.EMPLOYEE_ID
              AND A.ATTENDANCE_DT =OT.OVERTIME_DATE)
              LEFT JOIN HRIS_OVERTIME_MANUAL OTM
              ON (A.EMPLOYEE_ID   =OTM.EMPLOYEE_ID
              AND A.ATTENDANCE_DT =OTM.ATTENDANCE_DATE)
              LEFT JOIN HRIS_LEAVE_MASTER_SETUP L
              ON (A.LEAVE_ID= L.LEAVE_ID)
where  A.Attendance_Dt  
between  :fromDate and :toDate
  GROUP BY A.EMPLOYEE_ID) A
    LEFT JOIN HRIS_EMPLOYEES E
            ON(A.EMPLOYEE_ID = E.EMPLOYEE_ID)
            LEFT JOIN HRIS_COMPANY C
            ON(E.COMPANY_ID= C.COMPANY_ID)
            LEFT JOIN HRIS_DEPARTMENTS D
            ON (E.DEPARTMENT_ID= D.DEPARTMENT_ID)
            WHERE 1 = 1 {$searchCondition['sql']}
            and E.EMPLOYEE_ID not in (select employee_id from hris_job_history where RETIRED_FLAG = 'Y' or DISABLED_FLAG = 'Y')
            ORDER BY C.COMPANY_NAME,
              D.DEPARTMENT_NAME,
              E.FULL_NAME 
            ";

    $boundedParameter['fromDate'] = $fromDate;
    $boundedParameter['toDate'] = $toDate;

    $statement = $this->adapter->query($sql);
    $result = $statement->execute($boundedParameter);
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

  public function reportWithOTforBot($data)
  {
    $fromCondition = "";
    $toCondition = "";

    $otFromCondition = "";
    $otToCondition = "";

    $condition = EntityHelper::getSearchConditon($data['companyId'], $data['branchId'], $data['departmentId'], $data['positionId'], $data['designationId'], $data['serviceTypeId'], $data['serviceEventTypeId'], $data['employeeTypeId'], $data['employeeId'], $data['genderId'], $data['locationId']);

    if (isset($data['fromDate']) && $data['fromDate'] != null && $data['fromDate'] != -1) {
      $fromDate = Helper::getExpressionDate($data['fromDate']);
      $fromCondition = "AND A.ATTENDANCE_DT >= {$fromDate->getExpression()}";
      $otFromCondition = "AND OVERTIME_DATE >= {$fromDate->getExpression()} ";
    }
    if (isset($data['toDate']) && $data['toDate'] != null && $data['toDate'] != -1) {
      $toDate = Helper::getExpressionDate($data['toDate']);
      $toCondition = "AND A.ATTENDANCE_DT <= {$toDate->getExpression()}";
      $otToCondition = "AND OVERTIME_DATE <= {$toDate->getExpression()} ";
    }

    $headerCondition = "0 AS TOTAL_DAYS,";
    if (isset($data['fromDate']) && $data['fromDate'] != null && $data['fromDate'] != -1 && isset($data['toDate']) && $data['toDate'] != null && $data['toDate'] != -1) {
      $fromDate = Helper::getExpressionDate($data['fromDate']);
      $toDate = Helper::getExpressionDate($data['toDate']);
      $headerCondition = " {$toDate->getExpression()}-{$fromDate->getExpression()}+1 AS TOTAL_DAYS,";
    }

    $sql = <<<EOT
            SELECT 
   $headerCondition             
   C.COMPANY_NAME,
              D.DEPARTMENT_NAME,
              A.EMPLOYEE_ID,
			  E.EMPLOYEE_CODE,
              E.FULL_NAME,
              A.DAYOFF,
              A.PRESENT,
              A.HOLIDAY,
              A.LEAVE,
              A.PAID_LEAVE,
              A.UNPAID_LEAVE,
              A.ABSENT,
              NVL(ROUND(A.TOTAL_MIN/60,2),0) AS OVERTIME_HOUR,
              A.TRAVEL,
              A.TRAINING,
              A.WORK_ON_HOLIDAY,
              A.WORK_ON_DAYOFF
            FROM
              (SELECT A.EMPLOYEE_ID,
                SUM(
                CASE
                  WHEN A.OVERALL_STATUS IN('DO', 'WD')
                  THEN 1
                  ELSE 0
                END) AS DAYOFF,
                SUM(
                CASE
                  WHEN A.OVERALL_STATUS IN ('PR','BA','LA','VP','TN','TP','LP')
                  THEN (
                    CASE
                      WHEN A.OVERALL_STATUS = 'LP'
                      AND A.HALFDAY_PERIOD IS NOT NULL
                      THEN 0.5
                      ELSE 1
                    END)
                  ELSE 0
                END) AS PRESENT,
                SUM(
                CASE
                  WHEN A.OVERALL_STATUS IN ('HD','WH')
                  THEN 1
                  ELSE 0
                END) AS HOLIDAY,
                SUM(
                CASE
                  WHEN A.OVERALL_STATUS IN ('LV','LP')
                  AND A.GRACE_PERIOD    IS NULL
                  THEN (
                    CASE
                      WHEN A.OVERALL_STATUS = 'LP'
                      AND A.HALFDAY_PERIOD IS NOT NULL
                      THEN 0.5
                      ELSE 1
                    END)
                  ELSE 0
                END) AS LEAVE,
                SUM(
                CASE
                  WHEN A.OVERALL_STATUS IN ('LV','LP')
                  AND A.GRACE_PERIOD    IS NULL
                  AND L.PAID             = 'Y'
                  THEN (
                    CASE
                      WHEN A.OVERALL_STATUS = 'LP'
                      AND A.HALFDAY_PERIOD IS NOT NULL
                      THEN 0.5
                      ELSE 1
                    END)
                  ELSE 0
                END) AS PAID_LEAVE,
                SUM(
                CASE
                  WHEN A.OVERALL_STATUS IN ('LV','LP')
                  AND A.GRACE_PERIOD    IS NULL
                  AND L.PAID             = 'N'
                  THEN (
                    CASE
                      WHEN A.OVERALL_STATUS = 'LP'
                      AND A.HALFDAY_PERIOD IS NOT NULL
                      THEN 0
                      ELSE 0
                    END)
                  ELSE 0
                END) AS UNPAID_LEAVE,
                SUM(
                CASE
                  WHEN A.OVERALL_STATUS = 'AB'
                  THEN 1
                  ELSE 0
                END) AS ABSENT,
                SUM(
                CASE
                  WHEN A.OVERALL_STATUS= 'TV'
                  THEN 1
                  ELSE 0
                END) AS TRAVEL,
                SUM(
                CASE
                  WHEN A.OVERALL_STATUS ='TN'
                  THEN 1
                  ELSE 0
                END) AS TRAINING,
                SUM(
                CASE
                  WHEN A.OVERALL_STATUS = 'WH'
                  THEN 1
                  ELSE 0
                END) WORK_ON_HOLIDAY,
                SUM(
                CASE
                  WHEN A.OVERALL_STATUS ='WD'
                  THEN 1
                  ELSE 0
                END) WORK_ON_DAYOFF,
                 SUM(
                  CASE
                    WHEN OTM.OVERTIME_HOUR IS NULL
                    THEN OT.TOTAL_HOUR
                    ELSE OTM.OVERTIME_HOUR*60
                  END ) AS TOTAL_MIN
              FROM HRIS_ATTENDANCE_DETAIL A
              LEFT JOIN (SELECT
    employee_id,
    overtime_date,
    SUM(total_hour) AS total_hour
FROM
    hris_overtime where status ='AP'
GROUP BY
    employee_id,
    overtime_date) OT
              ON (A.EMPLOYEE_ID   =OT.EMPLOYEE_ID
              AND A.ATTENDANCE_DT =OT.OVERTIME_DATE)
              LEFT JOIN HRIS_OVERTIME_MANUAL OTM
              ON (A.EMPLOYEE_ID   =OTM.EMPLOYEE_ID
              AND A.ATTENDANCE_DT =OTM.ATTENDANCE_DATE)
              LEFT JOIN HRIS_LEAVE_MASTER_SETUP L
              ON (A.LEAVE_ID= L.LEAVE_ID)
              WHERE 1       =1 {$fromCondition} {$toCondition}
              GROUP BY A.EMPLOYEE_ID
              ) A
            LEFT JOIN HRIS_EMPLOYEES E
            ON(A.EMPLOYEE_ID = E.EMPLOYEE_ID)
            LEFT JOIN HRIS_COMPANY C
            ON(E.COMPANY_ID= C.COMPANY_ID)
            LEFT JOIN HRIS_DEPARTMENTS D
            ON (E.DEPARTMENT_ID= D.DEPARTMENT_ID)
            WHERE 1            =1 {$condition}
            ORDER BY C.COMPANY_NAME,
              D.DEPARTMENT_NAME,
              E.FULL_NAME 
EOT;

    $statement = $this->adapter->query($sql);
    $result = $statement->execute();
    return Helper::extractDbData($result);
  }

  public function whereaboutsReport($data)
  {

    $pivotString = '';
    //        $pivotCaseString='PL.FULL_NAME,
    //             PL.EMPLOYEE_ID,
    //             PL.EMPLOYEE_CODE, ';
    for ($i = 1; $i <= $data['days']; $i++) {
      if ($i != $data['days']) {
        $pivotString .= $i . ' AS ' . 'D' . $i . ', ';
      } else {
        $pivotString .= $i . ' AS ' . 'D' . $i;
      }
    }

    $leaveDetails = $this->getLeaveList();
    $leavePivotString = $this->getLeaveCodePivot($leaveDetails);

    $boundedParameter = [];
    $searchConditon = EntityHelper::getSearchConditonBounded($data['companyId'], $data['branchId'], $data['departmentId'], $data['positionId'], $data['designationId'], $data['serviceTypeId'], $data['serviceEventTypeId'], $data['employeeTypeId'], $data['employeeId'], null, null, $data['functionalTypeId']);
    $boundedParameter = array_merge($boundedParameter, $searchConditon['parameter']);
    $sql = <<<EOT
                SELECT PL.*,
  MLD.*,
  CL.IS_PRESENT,
  CL.IS_ABSENT,
  CL.ON_LEAVE,
  CL.IS_DAYOFF,
  CL.HOLIDAY,
  CL.IS_DAYOFF,
  CL.HOLIDAY_WORK,
  CL.TRAVEL,
  (CL.IS_PRESENT+CL.IS_ABSENT+CL.ON_LEAVE+CL.IS_DAYOFF+CL.HOLIDAY+CL.HOLIDAY_WORK+CL.TRAVEL) AS TOTAL
FROM
  (SELECT *
  FROM
    (
    SELECT 
 FIT.BRANCH_NAME,
 FIT.DESIGNATION_TITLE,   
 FIT.FULL_NAME,
 FIT.EMPLOYEE_ID,
 FIT.EMPLOYEE_CODE,
 FIT.DAY_COUNT,
 CASE 
 WHEN FIT.OVERALL_STATUS IS NOT NULL THEN FIT.OVERALL_STATUS
 WHEN FIT.OVERALL_STATUS IS NULL AND FIT.DO_STATUS IS NULL  THEN 'O'  
 ELSE
 FIT.DO_STATUS
 END
 AS OVERALL_STATUS
 FROM
 ( SELECT 
ASS.dates,
ASS.DAY_COUNT,
ass.employee_id,
CASE 
WHEN S.WEEKDAY1 = 'DAY_OFF' and TO_CHAR(ASS.DATES,'D') = 1 then 'DO' 
WHEN S.WEEKDAY2 = 'DAY_OFF' and TO_CHAR(ASS.DATES,'D') = 2 then 'DO' 
WHEN S.WEEKDAY3 = 'DAY_OFF' and TO_CHAR(ASS.DATES,'D') = 3 then 'DO' 
WHEN S.WEEKDAY4 = 'DAY_OFF' and TO_CHAR(ASS.DATES,'D') = 4 then 'DO' 
WHEN S.WEEKDAY5 = 'DAY_OFF' and TO_CHAR(ASS.DATES,'D') = 5 then 'DO' 
WHEN S.WEEKDAY6 = 'DAY_OFF' and TO_CHAR(ASS.DATES,'D') = 6 then 'DO' 
WHEN S.WEEKDAY7 = 'DAY_OFF' and TO_CHAR(ASS.DATES,'D') = 7 then 'DO' 
END as do_status

,E.FULL_NAME,
      E.EMPLOYEE_CODE,
      B.BRANCH_NAME,
      D.DESIGNATION_TITLE,
      CASE
        WHEN AD.OVERALL_STATUS IN ('TN','PR','BA','LA','LP','VP')
        THEN 'PR'
        WHEN AD.OVERALL_STATUS = 'LV'
        AND AD.HALFDAY_FLAG    ='N'
        THEN 'L'
          ||'-'
          ||LMS.LEAVE_CODE
        WHEN AD.OVERALL_STATUS = 'LV'
        AND AD.HALFDAY_FLAG!   ='N'
        THEN 'HL'
          ||'-'
          ||LMS.LEAVE_CODE
        WHEN AD.OVERALL_STATUS IN ('AB')
        AND AD.ATTENDANCE_DT    > TRUNC(SYSDATE)
        THEN 'O'
        ELSE AD.OVERALL_STATUS
      END                                                         AS OVERALL_STATUS,
      (AD.ATTENDANCE_DT- :fromDate +1) AS DAY_COUNT_ATTD
      ,AD.ATTENDANCE_DT

FROM 
(select 
AD.*,
EWA.EMPLOYEE_ID,
CASE 
WHEN ER.SHIFT_ID IS NOT NULL THEN ER.SHIFT_ID
WHEN ESA.SHIFT_ID IS NOT NULL THEN ESA.SHIFT_ID
ELSE DS.SHIFT_ID END AS ASSUMED_SHIFT_ID
from
(SELECT TO_DATE(:fromDate, 'DD-MON-YY') + RowNum - 1 AS DATES,
  RowNum                                            AS DAY_COUNT,
  :fromDate               AS FROM_DATE
FROM dual d
  CONNECT BY RowNum <= TO_DATE(:toDate, 'DD-MON-YY')
  - TO_DATE(:fromDate, 'DD-MON-YY') + 1 ) AD
  LEFT JOIN HRIS_EMP_WHEREABOUT_ASN EWA ON (1=1)
  LEFT JOIN HRIS_EMPLOYEE_SHIFT_ROASTER ER ON (ER.FOR_DATE=AD.DATES AND ER.EMPLOYEE_ID=EWA.EMPLOYEE_ID)
  LEFT JOIN HRIS_EMPLOYEE_SHIFT_ASSIGN ESA ON (ER.EMPLOYEE_ID=ESA.EMPLOYEE_ID AND AD.DATES BETWEEN START_DATE AND END_DATE )
  LEFT JOIN (SELECT SHIFT_ID FROM HRIS_SHIFTS WHERE DEFAULT_SHIFT='Y' AND ROWNUM=1) DS ON (1=1)
  WHERE EWA.STATUS = 'E'
  ) ASS
  LEFT JOIN HRIS_SHIFTS S ON (S.SHIFT_ID=ASS.ASSUMED_SHIFT_ID)
  LEFT JOIN HRIS_ATTENDANCE_DETAIL AD ON (AD.ATTENDANCE_DT=ASS.DATES and AD.EMPLOYEE_ID=ASS.EMPLOYEE_ID)
  LEFT JOIN HRIS_LEAVE_MASTER_SETUP LMS ON (AD.LEAVE_ID=LMS.LEAVE_ID)
  left JOIN HRIS_EMPLOYEES E ON (E.EMPLOYEE_ID       =ASS.EMPLOYEE_ID)
  left JOIN HRIS_BRANCHES B ON (E.BRANCH_ID = B.BRANCH_ID)
  left JOIN HRIS_DESIGNATIONS D ON (E.DESIGNATION_ID = D.DESIGNATION_ID)
  
  where 1=1 {$searchConditon['sql']}
  )
  FIT
    
    
    ) PIVOT (MAX (OVERALL_STATUS) FOR DAY_COUNT IN ({$pivotString})) 
  ) PL
LEFT JOIN
  (SELECT EMPLOYEE_ID,
    COUNT(
    CASE
      WHEN OVERALL_STATUS IN ('TN','PR','BA','LA','LP','VP')
      THEN 1
    END) AS IS_PRESENT,
    COUNT(
    CASE OVERALL_STATUS
      WHEN 'AB'
      THEN 1
    END) AS IS_ABSENT,
    COUNT(
    CASE OVERALL_STATUS
      WHEN 'LV'
      THEN 1
    END) AS ON_LEAVE,
    COUNT(
    CASE OVERALL_STATUS
      WHEN 'DO'
      THEN 1
    END) AS IS_DAYOFF,
    COUNT(
    CASE OVERALL_STATUS
      WHEN 'HD'
      THEN 1
    END) AS HOLIDAY,
    COUNT(
    CASE OVERALL_STATUS
      WHEN 'WH'
      THEN 1
    END) AS HOLIDAY_WORK,
    COUNT(
    CASE OVERALL_STATUS
      WHEN 'TV'
      THEN 1
    END) AS TRAVEL
  FROM HRIS_ATTENDANCE_DETAIL
  WHERE ATTENDANCE_DT BETWEEN to_date(:fromDate,'DD-Mon-YYYY') AND to_date(:toDate,'DD-Mon-YYYY')
  GROUP BY EMPLOYEE_ID
  )CL
ON (PL.EMPLOYEE_ID=CL.EMPLOYEE_ID)
LEFT JOIN
  (SELECT *
  FROM
    (SELECT AD.employee_id AS E_ID,
      AD.leave_id,
      SUM(
      CASE AD.HALFDAY_FLAG
        WHEN 'N'
        THEN 1
        ELSE 0.5
      END ) AS LTBM
    FROM HRIS_ATTENDANCE_DETAIL AD
    WHERE leave_id IS NOT NULL 
    AND AD.ATTENDANCE_DT BETWEEN to_date(:fromDate,'DD-Mon-YYYY') AND to_date(:toDate,'DD-Mon-YYYY')
    GROUP BY AD.employee_id,
      AD.leave_id
    ) PIVOT ( MAX (LTBM) FOR LEAVE_ID IN ( {$leavePivotString} ) )
  ) MLD
ON (PL.EMPLOYEE_ID=MLD.E_ID)
LEFT JOIN HRIS_EMP_WHEREABOUT_ASN W
ON (PL.EMPLOYEE_ID = W.EMPLOYEE_ID)
ORDER BY W.ORDER_BY
EOT;
    $boundedParameter['fromDate'] = $data['fromDate'];
    $boundedParameter['toDate'] = $data['toDate'];
    $statement = $this->adapter->query($sql);
    $result = $statement->execute($boundedParameter);
    return ['leaveDetails' => $leaveDetails, 'data' => Helper::extractDbData($result)];
  }

  public function getBranchName($branchId)
  {

    $boundedParam = [];
    $sql = "select BRANCH_NAME from HRIS_BRANCHES where BRANCH_ID = :branchId";
    $boundedParam['branchId'] = $branchId;
    $statement = $this->adapter->query($sql);
    $result = $statement->execute($boundedParam);
    return Helper::extractDbData($result);
  }

  public function getDates($monthId)
  {
    $boundedParam = [];
    $sql = "SELECT TO_DATE, FROM_DATE, MONTH_EDESC FROM HRIS_MONTH_CODE WHERE MONTH_ID = :monthId";
    $boundedParam['monthId'] = $monthId;
    $statement = $this->adapter->query($sql);
    $result = $statement->execute($boundedParam);
    return Helper::extractDbData($result);
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

    //        echo $sql;
    //        DIE();
    $statement = $this->adapter->query($sql);
    $result = $statement->execute($boundedParam);
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
  public function trainingReport($trainingId)
  {
    $trainingCondition = "";
    if (isset($trainingId) && $trainingId != null && $trainingId != -1) {
      $trainingCondition = "and tr.training_id=$trainingId ";
    }
    $trainingAssignCondition = "";
    if (isset($trainingId) && $trainingId != null && $trainingId != -1) {
      $trainingAssignCondition = "and ta.training_id=$trainingId ";
    }
    $sql = "select * from (SELECT 
    TR.EMPLOYEE_ID,
    E.EMPLOYEE_CODE,
    E.FULL_NAME                                        AS FULL_NAME,
    INITCAP(TO_CHAR(TR.REQUESTED_DATE, 'DD-MON-YYYY')) AS REQUESTED_DATE,
    BS_DATE(TO_CHAR(TR.REQUESTED_DATE, 'DD-MON-YYYY')) AS REQUESTED_DATE_BS,
    TR.REMARKS,
    (
    CASE
      WHEN TR.TRAINING_ID IS NULL
      THEN TR.DURATION
      ELSE T.DURATION
    END) AS DURATION ,
    INITCAP(
    CASE
      WHEN TR.TRAINING_ID IS NULL
      THEN TR.TITLE
      ELSE T.TRAINING_NAME
    END) AS TITLE,
    TR.TRAINING_ID,
    TRAINING_TYPE_DESC(
    CASE
      WHEN TR.TRAINING_ID IS NULL
      THEN TR.TRAINING_TYPE
      ELSE T.TRAINING_TYPE
    END) AS TRAINING_TYPE,
 
    (
    CASE
      WHEN TR.TRAINING_ID IS NULL
      THEN INITCAP(TO_CHAR(TR.START_DATE, 'DD-MON-YYYY'))
      ELSE INITCAP(TO_CHAR(T.START_DATE, 'DD-MON-YYYY'))
    END) AS START_DATE,
    (
    CASE
      WHEN TR.TRAINING_ID IS NULL
      THEN BS_DATE(TR.START_DATE)
      ELSE BS_DATE(T.START_DATE)
    END) AS START_DATE_BS,
    (
    CASE
      WHEN TR.TRAINING_ID IS NULL
      THEN INITCAP(TO_CHAR(TR.END_DATE, 'DD-MON-YYYY'))
      ELSE INITCAP(TO_CHAR(T.END_DATE, 'DD-MON-YYYY'))
    END) AS END_DATE,
    (
    CASE
      WHEN TR.TRAINING_ID IS NULL
      THEN BS_DATE(TR.END_DATE)
      ELSE BS_DATE(T.END_DATE)
    END)                                                 AS END_DATE_BS,
    TR.RECOMMENDED_BY                                    AS RECOMMENDED_BY,
    RE.FULL_NAME                                         AS RECOMMENDED_BY_NAME,
    INITCAP(TO_CHAR(TR.RECOMMENDED_DATE, 'DD-MON-YYYY')) AS RECOMMENDED_DATE,
    TR.APPROVED_BY                                       AS APPROVED_BY,
    AE.FULL_NAME                                         AS APPROVED_BY_NAME,
    INITCAP(TO_CHAR(TR.APPROVED_DATE, 'DD-MON-YYYY'))    AS APPROVED_DATE,
    INITCAP(TO_CHAR(TR.MODIFIED_DATE, 'DD-MON-YYYY'))    AS MODIFIED_DATE,
    RAR.EMPLOYEE_ID                                      AS RECOMMENDER_ID,
    RAR.FULL_NAME                                        AS RECOMMENDER_NAME,
    RAA.EMPLOYEE_ID                                      AS APPROVER_ID,
    RAA.FULL_NAME                                        AS APPROVER_NAME,
    TR.STATUS                                            AS STATUS ,
    LEAVE_STATUS_DESC(TR.STATUS)                         AS STATUS_DETAIL,
    C.COMPANY_NAME                                       AS COMPANY_NAME,
    T.INSTRUCTOR_NAME                                    AS INSTRUCTOR,
    G.GENDER_NAME                                        AS GENDER,
    D.DESIGNATION_TITLE                                  AS DESIGNATION,
    DE.DEPARTMENT_NAME                                   AS DEPARTMENT,
    T.DAILY_TRAINING_HOUR                                AS TRAINING_HOUR,
    TD.ATTENDANCE_STATUS                                 AS ATTD_STATUS
  FROM HRIS_EMPLOYEE_TRAINING_REQUEST TR
  LEFT JOIN HRIS_TRAINING_MASTER_SETUP T
  ON T.TRAINING_ID=TR.TRAINING_ID
  LEFT JOIN HRIS_EMPLOYEES E
  ON E.EMPLOYEE_ID=TR.EMPLOYEE_ID
  LEFT JOIN HRIS_EMPLOYEES RE
  ON(RE.EMPLOYEE_ID =TR.RECOMMENDED_BY)
  LEFT JOIN HRIS_COMPANY C
  ON C.COMPANY_ID=T.COMPANY_ID
  LEFT JOIN HRIS_GENDERS G
  ON G.GENDER_ID=E.GENDER_ID
   LEFT JOIN HRIS_DESIGNATIONS D
  ON D.DESIGNATION_ID=E.DESIGNATION_ID
   LEFT JOIN HRIS_DEPARTMENTS DE
  ON DE.DEPARTMENT_ID=E.DEPARTMENT_ID
  LEFT JOIN HRIS_EMPLOYEES AE
  ON (AE.EMPLOYEE_ID =TR.APPROVED_BY)
  LEFT JOIN HRIS_RECOMMENDER_APPROVER RA
  ON E.EMPLOYEE_ID =RA.EMPLOYEE_ID
  LEFT JOIN HRIS_EMPLOYEES RAR
  ON (RA.RECOMMEND_BY=RAR.EMPLOYEE_ID)
  LEFT JOIN HRIS_EMPLOYEES RAA
  ON(RA.APPROVED_BY=RAA.EMPLOYEE_ID)
  LEFT JOIN hris_emp_training_attendance TD
  ON (TD.TRAINING_ID=TR.TRAINING_ID  AND TD.EMPLOYEE_ID=TR.EMPLOYEE_ID AND TD.TRAINING_DT=TR.START_DATE)
  WHERE 1          =1   {$trainingCondition}
  union all 
  SELECT 
    TA.EMPLOYEE_ID,
    E.EMPLOYEE_CODE,
    E.FULL_NAME                                        AS FULL_NAME,
    INITCAP(TO_CHAR(TA.CREATED_DATE, 'DD-MON-YYYY')) AS REQUESTED_DATE,
    BS_DATE(TO_CHAR(TA.CREATED_DATE, 'DD-MON-YYYY')) AS REQUESTED_DATE_BS,
    TA.REMARKS,
    (
    CASE
      WHEN TA.TRAINING_ID IS NOT NULL
      THEN T.DURATION
    END) AS DURATION ,
    INITCAP(
    CASE
      WHEN TA.TRAINING_ID IS not NULL
      THEN  T.TRAINING_NAME
    END) AS TITLE,
    TA.TRAINING_ID,
    TRAINING_TYPE_DESC(
    CASE
      WHEN TA.TRAINING_ID IS not NULL
      THEN  T.TRAINING_TYPE
    END) AS TRAINING_TYPE,

    (
    CASE
      WHEN TA.TRAINING_ID IS NOT NULL
      THEN  INITCAP(TO_CHAR(T.START_DATE, 'DD-MON-YYYY'))
    END) AS START_DATE,
    (
    CASE
      WHEN TA.TRAINING_ID IS NOT  NULL
      THEN  BS_DATE(T.START_DATE)
    END) AS START_DATE_BS,
    (
    CASE
      WHEN TA.TRAINING_ID IS NOT NULL
      THEN  INITCAP(TO_CHAR(T.END_DATE, 'DD-MON-YYYY'))
    END) AS END_DATE,
    (
    CASE
      WHEN TA.TRAINING_ID IS NOT NULL
      THEN  BS_DATE(T.END_DATE)
    END)                                                 AS END_DATE_BS,
    TA.CREATED_BY                                    AS RECOMMENDED_BY,
    RE.FULL_NAME                                         AS RECOMMENDED_BY_NAME,
    INITCAP(TO_CHAR(TA.CREATED_DATE, 'DD-MON-YYYY')) AS RECOMMENDED_DATE,
    TA.CREATED_BY                                       AS APPROVED_BY,
    AE.FULL_NAME                                         AS APPROVED_BY_NAME,
    INITCAP(TO_CHAR(TA.CREATED_DATE, 'DD-MON-YYYY'))    AS APPROVED_DATE,
    INITCAP(TO_CHAR(TA.MODIFIED_DATE, 'DD-MON-YYYY'))    AS MODIFIED_DATE,
    RAR.EMPLOYEE_ID                                      AS RECOMMENDER_ID,
    RAR.FULL_NAME                                        AS RECOMMENDER_NAME,
    RAA.EMPLOYEE_ID                                      AS APPROVER_ID,
    RAA.FULL_NAME                                        AS APPROVER_NAME,
    (CASE WHEN TA.STATUS='E' THEN 'AP' END  )            AS STATUS ,
    (CASE WHEN TA.STATUS='E' THEN 'Approved' END  )      AS STATUS_DETAIL,
    C.COMPANY_NAME                                       AS COMPANY_NAME,
    T.INSTRUCTOR_NAME                                    AS INSTRUCTOR,
    G.GENDER_NAME                                        AS GENDER,
    D.DESIGNATION_TITLE                                  AS DESIGNATION,
    DE.DEPARTMENT_NAME                                   AS DEPARTMENT,
    T.DAILY_TRAINING_HOUR                                AS TRAINING_HOUR,
    TD.ATTENDANCE_STATUS                                 AS ATTD_STATUS
  FROM hris_employee_training_assign ta
  LEFT JOIN HRIS_TRAINING_MASTER_SETUP T
  ON T.TRAINING_ID=ta.TRAINING_ID
  LEFT JOIN HRIS_EMPLOYEES E
  ON E.EMPLOYEE_ID=ta.EMPLOYEE_ID
  LEFT JOIN HRIS_EMPLOYEES RE
  ON(RE.EMPLOYEE_ID =ta.created_by)
  LEFT JOIN HRIS_COMPANY C
  ON C.COMPANY_ID=T.COMPANY_ID
  LEFT JOIN HRIS_GENDERS G
  ON G.GENDER_ID=E.GENDER_ID
   LEFT JOIN HRIS_DESIGNATIONS D
  ON D.DESIGNATION_ID=E.DESIGNATION_ID
   LEFT JOIN HRIS_DEPARTMENTS DE
  ON DE.DEPARTMENT_ID=E.DEPARTMENT_ID
  LEFT JOIN HRIS_EMPLOYEES AE
  ON (AE.EMPLOYEE_ID =ta.created_by)
  LEFT JOIN HRIS_RECOMMENDER_APPROVER RA
  ON E.EMPLOYEE_ID =RA.EMPLOYEE_ID
  LEFT JOIN HRIS_EMPLOYEES RAR
  ON (RA.RECOMMEND_BY=RAR.EMPLOYEE_ID)
  LEFT JOIN HRIS_EMPLOYEES RAA
  ON(RA.APPROVED_BY=RAA.EMPLOYEE_ID)
  LEFT JOIN hris_emp_training_attendance TD
  ON (TD.TRAINING_ID=ta.TRAINING_ID  AND TD.EMPLOYEE_ID=ta.EMPLOYEE_ID)
  WHERE 1          =1  {$trainingAssignCondition})TRA order by TRA.training_id desc";
    $statement = $this->adapter->query($sql);
    $result = $statement->execute();
    return Helper::extractDbData($result);
  }

  public function trainingHours($trainingId)
  {
    $sql = "select daily_training_hour from hris_training_master_setup where training_id=$trainingId";
    $statement = $this->adapter->query($sql);
    $result = $statement->execute();
    return $result->current();
  }

  public function maleFemaleReport($data)
  {
    $employeeId = $data['employeeId'];
    $companyId = $data['companyId'];
    $branchId = $data['branchId'];
    $departmentId = $data['departmentId'];
    $designationId = $data['designationId'];
    $positionId = $data['positionId'];
    $serviceTypeId = $data['serviceTypeId'];
    $serviceEventTypeId = $data['serviceEventTypeId'];
    $employeeTypeId = $data['employeeTypeId'];
    $functionalTypeId = $data['functionalTypeId'];

    $searchCondition = EntityHelper::getSearchConditonBounded($companyId, $branchId, $departmentId, $positionId, $designationId, $serviceTypeId, $serviceEventTypeId, $employeeTypeId, $employeeId, null, null, $functionalTypeId);
    $boundedParameter = [];
    $boundedParameter = array_merge($boundedParameter, $searchCondition['parameter']);

    $sql = "SELECT
    e.employee_code,
    e.full_name,
    g.gender_name
FROM
    hris_employees e,hris_genders g
WHERE 1                 =1 and e.gender_id=g.gender_id and
    e.status = 'E' AND e.retired_flag='N' and e.resigned_flag='N' {$searchCondition['sql']} order by e.employee_code desc";
    // echo '<pre>';
    // print_r($sql);
    // die;
    return $this->rawQuery($sql, $boundedParameter);
  }
  public function fetchGenderHeadCount($data)
  {

    $employeeId = $data['employeeId'];
    $companyId = $data['companyId'];
    $branchId = $data['branchId'];
    $departmentId = $data['departmentId'];
    $designationId = $data['designationId'];
    $positionId = $data['positionId'];
    $serviceTypeId = $data['serviceTypeId'];
    $serviceEventTypeId = $data['serviceEventTypeId'];
    $employeeTypeId = $data['employeeTypeId'];
    $functionalTypeId = $data['functionalTypeId'];

    $searchCondition = EntityHelper::getSearchConditonBounded($companyId, $branchId, $departmentId, $positionId, $designationId, $serviceTypeId, $serviceEventTypeId, $employeeTypeId, $employeeId, null, null, $functionalTypeId);
    $boundedParameter = [];
    $boundedParameter = array_merge($boundedParameter, $searchCondition['parameter']);

    $sql = "SELECT
    COUNT(*) AS HEAD_COUNT,
    E.GENDER_ID,
    HG.GENDER_NAME,
    ROUND(COUNT(*) * 100.0 / SUM(COUNT(*)) OVER (), 2) AS PERCENTAGE
FROM HRIS_EMPLOYEES E
INNER JOIN HRIS_GENDERS HG ON E.GENDER_ID = HG.GENDER_ID
WHERE HG.STATUS = 'E' AND E.RETIRED_FLAG = 'N' AND E.RESIGNED_FLAG = 'N'  {$searchCondition['sql']} AND E.STATUS = 'E'
--AND HE.COMPANY_ID = :V_COMPANY_ID
GROUP BY E.GENDER_ID, HG.GENDER_NAME
ORDER BY UPPER(HG.GENDER_NAME)";

    return $this->rawQuery($sql, $boundedParameter);
  }

  public function renumerationReport($data)
  {
    $employeeId = $data['employeeId'];
    $companyId = $data['companyId'];
    $branchId = $data['branchId'];
    $departmentId = $data['departmentId'];
    $designationId = $data['designationId'];
    $positionId = $data['positionId'];
    $serviceTypeId = $data['serviceTypeId'];
    $serviceEventTypeId = $data['serviceEventTypeId'];
    $employeeTypeId = $data['employeeTypeId'];
    $functionalTypeId = $data['functionalTypeId'];

    $monthId = $data['month'];
    $year = $data['year'];
    $searchCondition = EntityHelper::getSearchConditonBounded($companyId, $branchId, $departmentId, $positionId, $designationId, $serviceTypeId, $serviceEventTypeId, $employeeTypeId, $employeeId, null, null, $functionalTypeId);
    $boundedParameter = [];
    $boundedParameter = array_merge($boundedParameter, $searchCondition['parameter']);
    $sql = "select hssed.full_name,hssd1.val as basic_salary ,hssd2.val as allowance,hssd3.val as gross_amount,hssd4.val as ssf, ROUND(hssd5.val / 12, 2) as total_renumeration  from hris_salary_sheet_emp_detail hssed
    left join hris_employees e on (e.employee_id=hssed.employee_id)
    left join hris_salary_sheet_detail hssd1 on (hssd1.employee_id=hssed.employee_id and hssd1.sheet_no=hssed.sheet_no and hssd1.pay_id in (select pay_id from hris_pay_setup where pay_code='BA01'))
    left join hris_salary_sheet_detail hssd2 on (hssd2.employee_id=hssed.employee_id and hssd2.sheet_no=hssed.sheet_no and hssd2.pay_id in (select pay_id from hris_pay_setup where pay_code='R003'))
    left join hris_salary_sheet_detail hssd3 on (hssd3.employee_id=hssed.employee_id and hssd3.sheet_no=hssed.sheet_no and hssd3.pay_id in (select pay_id from hris_pay_setup where pay_code='GA01'))
    left join hris_salary_sheet_detail hssd4 on (hssd4.employee_id=hssed.employee_id and hssd4.sheet_no=hssed.sheet_no and hssd4.pay_id in (select pay_id from hris_pay_setup where pay_code='SSF01'))
    left join hris_salary_sheet_detail hssd5 on (hssd5.employee_id=hssed.employee_id and hssd5.sheet_no=hssed.sheet_no and hssd5.pay_id in (select pay_id from hris_pay_setup where pay_code='RT01'))
    where hssed.month_id=$monthId {$searchCondition['sql']} and e.status='E'";
    return $this->rawQuery($sql, $boundedParameter);
  }

  public function turnOverReport($data)
  {
    $employeeId = $data['employeeId'];
    $companyId = $data['companyId'];
    $branchId = $data['branchId'];
    $departmentId = $data['departmentId'];
    $designationId = $data['designationId'];
    $positionId = $data['positionId'];
    $serviceTypeId = $data['serviceTypeId'];
    $serviceEventTypeId = $data['serviceEventTypeId'];
    $employeeTypeId = $data['employeeTypeId'];
    $functionalTypeId = $data['functionalTypeId'];


    $monthId = $data['month'];
    $year = $data['year'];
    $searchCondition = EntityHelper::getSearchConditonBounded($companyId, $branchId, $departmentId, $positionId, $designationId, $serviceTypeId, $serviceEventTypeId, $employeeTypeId, $employeeId, null, null, $functionalTypeId);
    $searchCondition1 = EntityHelper::getSearchConditonBoundedJGI($companyId, $branchId, $departmentId, $positionId, $designationId, $serviceTypeId, $serviceEventTypeId, $employeeTypeId, $employeeId, null, null, $functionalTypeId);
    $boundedParameter = [];
    $boundedParameter = array_merge($boundedParameter, $searchCondition['parameter']);
    $monthYrsCondition1 = "";
    $monthYrsCondition2 = "";
    $monthId = $data['month'];
    $year = $data['year'];
    if ($monthId != null) {
      $monthYrsCondition1 = "(select from_date from hris_month_code where month_id=$monthId)";
      $monthYrsCondition2 = "(select to_date from hris_month_code where month_id=$monthId)";
    } else {
      $monthYrsCondition1 = "(select start_date from hris_fiscal_years where fiscal_year_id=$year)";
      $monthYrsCondition2 = "(select end_date from hris_fiscal_years where fiscal_year_id=$year)";
    }
    $sql = "select company_name,company_code, Beginning,Ending,Real_Separation,average,to_char(round((Real_Separation*100/average),2), 'FM9990.00')||'%' as employee_turnover from (select company_code,
    company_name,(Beginning+Real_Separation) as Beginning,Ending,Real_Separation,to_char(round(((Beginning+Ending+Real_Separation)/2),2), 'FM9990.00') as average from(SELECT
    company_code,
    company_name,
    SUM(CASE WHEN employee_status = 'Beginning' THEN ATTN_COUNT ELSE 0 END) AS Beginning,
    SUM(CASE WHEN employee_status = 'Ending' THEN ATTN_COUNT ELSE 0 END) AS Ending,
    SUM(CASE WHEN employee_status = 'Real Separation' THEN ATTN_COUNT ELSE 0 END) AS Real_Separation
FROM(
      select c.company_code,c.company_name ,
        'Beginning' as employee_status ,
        COUNT(*) ATTN_COUNT from hris_employees e,
        hris_company c where  (e.company_id=c.company_id) 
        --and e.status='E' and e.retired_flag='N' and e.resigned_flag='N'
        and e.join_date < $monthYrsCondition1 {$searchCondition['sql']} and e.status='E'
        group by c.company_code,c.company_name ,'Beginning'
        union all
         select c.company_code,c.company_name ,
        'Ending' as employee_status ,
        COUNT(*) ATTN_COUNT from hris_employees e,
        hris_company c where  (e.company_id=c.company_id) 
        -- and e.status='E' and e.retired_flag='N' and e.resigned_flag='N'
        and e.join_date < $monthYrsCondition2 {$searchCondition['sql']} and e.status='E'
        group by c.company_code,c.company_name ,'Ending'
        union all
         select c.company_code,c.company_name ,
        'Real Separation' as employee_status ,
        COUNT(*) ATTN_COUNT from hris_employees e,hris_job_history he,
        hris_company c where  (e.company_id=c.company_id)  and (e.employee_id=he.employee_id) {$searchCondition1['sql']}
        and he.event_date  between $monthYrsCondition1 and $monthYrsCondition2 
        group by c.company_code,c.company_name ,'Real Separation' )
GROUP BY
    company_code,
    company_name))
ORDER BY
    UPPER(company_name)";

    return $this->rawQuery($sql, $boundedParameter);
  }

  public function ageByGenerationReport($data)
  {
    $age = $data['ageGeneration'];
    $employeeId = $data['employeeId'];
    $companyId = $data['companyId'];
    $branchId = $data['branchId'];
    $departmentId = $data['departmentId'];
    $designationId = $data['designationId'];
    $positionId = $data['positionId'];
    $serviceTypeId = $data['serviceTypeId'];
    $serviceEventTypeId = $data['serviceEventTypeId'];
    $employeeTypeId = $data['employeeTypeId'];
    $functionalTypeId = $data['functionalTypeId'];

    $searchCondition = EntityHelper::getSearchConditonBounded($companyId, $branchId, $departmentId, $positionId, $designationId, $serviceTypeId, $serviceEventTypeId, $employeeTypeId, $employeeId, null, null, $functionalTypeId);
    $boundedParameter = [];
    $boundedParameter = array_merge($boundedParameter, $searchCondition['parameter']);
    $sql = "";
    if ($age == 0) {
      $sql = "select * from (select 'Gen Z' as generation,'1997-2012' as born,'11-26' as current_age,count(*) as total from hris_employees E where E.status='E' and  E.birth_date between '01-jan-1997' and trunc(sysdate) {$searchCondition['sql']}
      union all 
      select 'Millennials' as generation,'1981-1996' as born,'27-42' as current_age,count(*) as total from hris_employees E where E.status='E' and  E.birth_date between '01-jan-1981' and '31-dec-1996' {$searchCondition['sql']}
      union all
      select 'Gen X' as generation,'1965-1980' as born,'43-58' as current_age,count(*) as total from hris_employees E where E.status='E' and   E.birth_date between '01-jan-1965' and '31-dec-1980' {$searchCondition['sql']}
      union all 
      select 'Boomers II (a/k/a Generation Jones)*' as generation,'1955-1964' as born,'59-68' as current_age,count(*) as total from hris_employees E where E.status='E' and   E.birth_date between '01-jan-1955' and '31-dec-1964'
       {$searchCondition['sql']}
      union all
      select 'Boomers I*' as generation,'1946-1954' as born,'69-77' as current_age,count(*) as total from hris_employees E where E.status='E' and   E.birth_date between '01-jan-1946' and '31-dec-1954'  {$searchCondition['sql']})";
    } elseif ($age == 1) {
      $sql = "select 'Gen Z' as generation,'1997-2012' as born,'11-26' as current_age,count(*) as total from hris_employees E where E.status='E' and  E.birth_date between '01-jan-1997' and trunc(sysdate) {$searchCondition['sql']}";
    } elseif ($age == 2) {
      $sql = "select 'Millennials' as generation,'1981-1996' as born,'27-42' as current_age,count(*) as total from hris_employees E where E.status='E' and   E.birth_date between '01-jan-1981' and '31-dec-1996' {$searchCondition['sql']}";
    } elseif ($age == 3) {
      $sql = "select 'Gen X' as generation,'1965-1980' as born,'43-58' as current_age,count(*) as total from hris_employees E where E.status='E' and   E.birth_date between '01-jan-1965' and '31-dec-1980' {$searchCondition['sql']}";
    } elseif ($age == 4) {
      $sql = "select 'Boomers II (a/k/a Generation Jones)*' as generation,'1955-1964' as born,'59-68' as current_age,count(*) as total from hris_employees E where E.status='E' and  E.birth_date between '01-jan-1955' and '31-dec-1964' {$searchCondition['sql']}";
    } else {
      $sql = "select 'Boomers I*' as generation,'1946-1954' as born,'69-77' as current_age,count(*) as total from hris_employees E where E.status='E' and  E.birth_date between '01-jan-1946' and '31-dec-1954' {$searchCondition['sql']} ";
    }

    return $this->rawQuery($sql, $boundedParameter);
  }
  public function newEmployeeDailyReport($searchQuery)
  {
    $fromDate = $searchQuery['fromDate'];
    $toDate = $searchQuery['toDate'];
    $monthDetail = $this->getMonthDetailsByDate($fromDate, $toDate);

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
( CASE
WHEN ad.total_hour IS NULL and ad.overall_status in ('PR','LA') THEN '0'
WHEN ad.total_hour IS NULL and ad.overall_status NOT in ('PR') THEN ad.overall_status
ELSE EXTRACT(MINUTE FROM NUMTODSINTERVAL(ad.total_hour, 'SECOND')) || '.' || 
EXTRACT(SECOND FROM NUMTODSINTERVAL(ad.total_hour, 'SECOND'))
END) AS total_hour,
 --AD.ATTENDANCE_DT,
 (AD.ATTENDANCE_DT-TO_DATE('{$fromDate}')+1) AS DAY_COUNT
FROM HRIS_ATTENDANCE_DETAIL AD
LEFT JOIN HRIS_LEAVE_MASTER_SETUP LMS ON (AD.LEAVE_ID=LMS.LEAVE_ID)
JOIN HRIS_EMPLOYEES E ON (E.EMPLOYEE_ID =AD.EMPLOYEE_ID)
LEFT JOIN HRIS_COMPANY C ON (C.COMPANY_ID=E.COMPANY_ID)
LEFT JOIN HRIS_DEPARTMENTS D ON (D.DEPARTMENT_ID=E.DEPARTMENT_ID)
WHERE (AD.ATTENDANCE_DT BETWEEN TO_DATE('{$fromDate}') AND TO_DATE('{$toDate}') )
{$searchConditon}
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
END )AS total_hour_sum
FROM
hris_attendance_detail ad
LEFT JOIN
hris_shifts hs
ON
ad.shift_id = hs.shift_id
WHERE ad.ATTENDANCE_DT BETWEEN TO_DATE('{$monthDetail['FROM_DATE']}','DD-MON-YY') AND TO_DATE('{$monthDetail['TO_DATE']}','DD-MON-YY')
GROUP BY EMPLOYEE_ID
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
                       case when (Previous_year_bal+total-taken)<0 then 0 else  Previous_year_bal+total-taken end as balance,
                        encashed, taken as taken from 
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
     
EOT;

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
}
