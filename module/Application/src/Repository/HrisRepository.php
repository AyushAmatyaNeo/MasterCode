<?php
namespace Application\Repository;

use Zend\Db\Adapter\AdapterInterface;
use Zend\Db\TableGateway\TableGateway;

class HrisRepository {

    protected $adapter;
    protected $tableGateway;

    public function __construct(AdapterInterface $adapter, $tableName = null) {
        $this->adapter = $adapter;
        if ($tableName !== null) {
            $this->tableGateway = new TableGateway($tableName, $adapter);
        }
    }

    protected function rawQuery($sql): array {
        $statement = $this->adapter->query($sql);
        $iterator = $statement->execute();
        return iterator_to_array($iterator, false);
    }

    protected function executeStatement($sql) {
        $statement = $this->adapter->query($sql);
        $statement->execute();
    }

    protected function checkIfTableExists($tableName): bool {
        $sql = "SELECT * FROM USER_TABLES WHERE TABLE_NAME ='{$tableName}'";
        $statement = $this->adapter->query($sql);
        $iterator = $statement->execute();
        return $iterator->count() > 0;
    }

    private function conditionBuilder($colValue, $colName, $conditonType, $isString = false, $parentQuery = false) {
        if (gettype($colValue) === "array") {
            $valuesinCSV = "";
            for ($i = 0; $i < sizeof($colValue); $i++) {
                $value = $isString ? "'{$colValue[$i]}'" : $colValue[$i];
                if ($i + 1 == sizeof($colValue)) {
                    $valuesinCSV .= "{$value}";
                } else {
                    $valuesinCSV .= "{$value},";
                }
            }
            if ($parentQuery) {
                $valuesinCSV = str_replace('INVALUES', $valuesinCSV, $parentQuery);
            }
            return " {$conditonType} {$colName} IN ({$valuesinCSV})";
        } else {
            $value = $isString ? "'{$colValue}'" : $colValue;
            if($parentQuery){
            $value=str_replace('INVALUES', $value, $parentQuery);
             return " {$conditonType} {$colName} IN ({$value})";
            }
            return " {$conditonType} {$colName} = {$value}";
        }
    }

    protected function getSearchConditon($companyId, $branchId, $departmentId, $positionId, $designationId, $serviceTypeId, $serviceEventTypeId, $employeeTypeId, $employeeId, $genderId = null, $locationId = null, $functionalTypeId = null) {
        $conditon = "";
        if ($companyId != null && $companyId != -1) {
            $conditon .= $this->conditionBuilder($companyId, "E.COMPANY_ID", "AND");
        }
        if ($branchId != null && $branchId != -1) {
            $conditon .= $this->conditionBuilder($branchId, "E.BRANCH_ID", "AND");
        }
        if ($departmentId != null && $departmentId != -1) {
            $parentQuery = "(SELECT DEPARTMENT_ID FROM
                         HRIS_DEPARTMENTS 
                        START WITH PARENT_DEPARTMENT in (INVALUES)
                        CONNECT BY PARENT_DEPARTMENT= PRIOR DEPARTMENT_ID
                        UNION 
                        SELECT DEPARTMENT_ID FROM HRIS_DEPARTMENTS WHERE DEPARTMENT_ID IN (INVALUES)
                        UNION
                        SELECT  TO_NUMBER(TRIM(REGEXP_SUBSTR(EXCEPTIONAL,'[^,]+', 1, LEVEL) )) DEPARTMENT_ID
  FROM (SELECT EXCEPTIONAL  FROM  HRIS_DEPARTMENTS WHERE DEPARTMENT_ID IN  (INVALUES))
   CONNECT BY  REGEXP_SUBSTR(EXCEPTIONAL, '[^,]+', 1, LEVEL) IS NOT NULL
                        )";
            $conditon .= $this->conditionBuilder($departmentId, "E.DEPARTMENT_ID", "AND", false, $parentQuery);
        }
        if ($positionId != null && $positionId != -1) {
            $conditon .= $this->conditionBuilder($positionId, "E.POSITION_ID", "AND");
        }
        if ($designationId != null && $designationId != -1) {
            $conditon .= $this->conditionBuilder($designationId, "E.DESIGNATION_ID", "AND");
        }
        if ($serviceTypeId != null && $serviceTypeId != -1) {
            $conditon .= $this->conditionBuilder($serviceTypeId, "E.SERVICE_TYPE_ID", "AND");
        } else {
            $conditon .= " AND (E.SERVICE_TYPE_ID IN (SELECT SERVICE_TYPE_ID FROM HRIS_SERVICE_TYPES WHERE TYPE NOT IN ('RESIGNED','RETIRED')) OR E.SERVICE_TYPE_ID IS NULL)";
        }
        if ($serviceEventTypeId != null && $serviceEventTypeId != -1) {
            $conditon .= $this->conditionBuilder($serviceEventTypeId, "E.SERVICE_EVENT_TYPE_ID", "AND");
        }
        if ($employeeTypeId != null && $employeeTypeId != -1) {
            $conditon .= $this->conditionBuilder($employeeTypeId, "E.EMPLOYEE_TYPE", "AND", true);
        }
        if ($employeeId != null && $employeeId != -1) {
            $conditon .= $this->conditionBuilder($employeeId, "E.EMPLOYEE_ID", "AND");
        }
        if ($genderId != null && $genderId != -1) {
            $conditon .= $this->conditionBuilder($genderId, "E.GENDER_ID", "AND");
        }
        if ($locationId != null && $locationId != -1) {
            $conditon .= $this->conditionBuilder($locationId, "E.LOCATION_ID", "AND");
        }
        if ($functionalTypeId != null && $functionalTypeId != -1) {
            $conditon .= self::conditionBuilder($functionalTypeId, "E.FUNCTIONAL_TYPE_ID", "AND");
        }
        return $conditon;
    }
    
    protected  function getPrefReportQuery($sql){
        $finalSql="SELECT R.*,
                  C.COMPANY_NAME,
                  B.BRANCH_NAME
                FROM ({$sql}) R
                LEFT JOIN HRIS_EMPLOYEES E
                ON (R.EMPLOYEE_ID=E.EMPLOYEE_ID)
                LEFT JOIN HRIS_COMPANY C
                ON (E.COMPANY_ID=C.COMPANY_ID)
                LEFT JOIN HRIS_BRANCHES B
                ON (E.BRANCH_ID = B.BRANCH_ID)";
        return $finalSql;
    }
}
