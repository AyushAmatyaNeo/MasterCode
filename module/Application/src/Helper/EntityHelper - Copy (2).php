<?php

namespace Application\Helper;

use Exception;
use ReflectionClass;
use Setup\Model\Branch;
use Setup\Model\Company;
use Setup\Model\Department;
use Setup\Model\Designation;
use Setup\Model\FunctionalTypes;
use Setup\Model\Gender;
use Setup\Model\HrEmployees;
use Setup\Model\Location;
use Setup\Model\Position;
use Setup\Model\ServiceEventType;
use Setup\Model\ServiceType;
use Zend\Db\Adapter\AdapterInterface;
use Zend\Db\Sql\Expression;
use Zend\Db\Sql\Predicate\Predicate;
use Zend\Db\Sql\Select;
use Zend\Db\TableGateway\TableGateway;
use Zend\Form\Element\Select as Select2;

class EntityHelper {

    const STATUS_ENABLED = 'E';
    const STATUS_DISABLED = 'D';
    const ORACLE_FUNCTION_INITCAP = '';

    public static function getTableKVList(AdapterInterface $adapter, $tableName, $key = null, array $values, $where = null, $concatWith = null, $emptyColumn = false, $orderBy = null, $orderAs = null, $initCap = false) {
        $gateway = new TableGateway($tableName, $adapter);
        $resultset = $gateway->select(function(Select $select) use ($key, $values, $where, $orderBy, $orderAs, $initCap) {
            if ($key !== null) {
                array_push($values, $key);
            }
            if ($initCap) {
                $tempValues = [];
                foreach ($values as $key => $value) {
                    $tempValues[$key] = "({$value}) AS {$value}";
                }
                $values = $tempValues;
            }
            $select->columns($values, false);
            if ($where !== null) {
                $select->where($where);
            }

            if ($orderBy !== null) {
                $select->order([$orderBy => ($orderAs !== null) ? $orderAs : Select::ORDER_ASCENDING]);
            }
        });
        $concatWith = ($concatWith == null) ? " " : ($concatWith == null) ? "" : $concatWith;

        $entitiesArray = array();
        if ($emptyColumn) {
            if (gettype($emptyColumn) == 'array') {
                foreach ($emptyColumn as $k => $v) {
                    $entitiesArray[$k] = $v;
                }
            } else {
                $entitiesArray[null] = "----";
            }
        }
        foreach ($resultset as $result) {
            $concattedValue = "";
            for ($i = 0; $i < count($values); $i++) {
                if ($i == 0) {
                    $concattedValue = $result[$values[$i]];
                    continue;
                }
                $concattedValue = $concattedValue . $concatWith . $result[$values[$i]];
            }
            if ($key == null) {
                array_push($entitiesArray, $concattedValue);
            } else {
                $entitiesArray[$result[$key]] = $concattedValue;
            }
        }
        return $entitiesArray;
    }

    public static function getTableKVListWithSortOption(AdapterInterface $adapter, $tableName, $key, array $values, $where = null, $orderBy = null, $orderAs = null, $concatWith = null, $emptyColumn = false, $initCap = false, $employeeId = null) {
        //if($employeeId !== null){
        //    $where = self::applyRoleControl($adapter, $employeeId, $where);
       // }
        return self::getTableKVList($adapter, $tableName, $key, $values, $where, $concatWith, $emptyColumn, $orderBy, $orderAs, $initCap);
    }

    public static function rawQueryResult(AdapterInterface $adapter, string $sql,$boundedParameter=[]) {
        $statement = $adapter->query($sql);
        return $statement->execute($boundedParameter);
    }

    public static function getTableList(AdapterInterface $adapter, string $tableName, array $columnList, array $where = null, string $predicate = Predicate::OP_AND,$orderBy=null) {
        $gateway = new TableGateway($tableName, $adapter);
        $zendResult = $gateway->select(function(Select $select) use($columnList, $where, $predicate,$orderBy) {
            $select->columns($columnList, false);
            if ($where != null) {
                $select->where($where, $predicate);
            }
            if ($orderBy != null) {
            $select->order($orderBy);
            }
        });
        return Helper::extractDbData($zendResult, true);
    }

    public static function getColumnNameArrayWithOracleFns(string $requestedName, array $initCapColumnList = null, array $dateColumnList = null, array $timeColumnList = null, array $timeIntervalColumnList = null, array $otherColumnList = null, string $shortForm = null, $selectedOnly = false, $inStringForm = false, array $minuteToHourColumnList = null, array $customCols = null) {
        $refl = new ReflectionClass($requestedName);
        $table = $refl->newInstanceArgs();

        $objAttrs = array_keys(get_object_vars($table));
        $objCols = [];

        foreach ($objAttrs as $objAttr) {
            if ('mappings' === $objAttr) {
                continue;
            }
            $tempCol = $table->mappings[$objAttr];
            if ($initCapColumnList !== null && in_array($tempCol, $initCapColumnList)) {
                $initCapExpression = Helper::columnExpression($tempCol, $shortForm, self::ORACLE_FUNCTION_INITCAP);
                array_push($objCols, $inStringForm ? $initCapExpression->getExpression() : $initCapExpression);
                continue;
            }

            if ($dateColumnList !== null && in_array($tempCol, $dateColumnList)) {
                $dateExpression = self::formatColumn($tempCol, $shortForm, Helper::ORACLE_DATE_FORMAT);
                array_push($objCols, $inStringForm ? $dateExpression->getExpression() : $dateExpression);
                continue;
            }
            if ($timeColumnList !== null && in_array($tempCol, $timeColumnList)) {
                $timeExpression = self::formatColumn($tempCol, $shortForm, Helper::ORACLE_TIME_FORMAT);
                array_push($objCols, $inStringForm ? $timeExpression->getExpression() : $timeExpression);
                continue;
            }
            if ($timeIntervalColumnList !== null && in_array($tempCol, $timeIntervalColumnList)) {
                $timeIntervalExpression = self::formatColumn($tempCol, $shortForm, Helper::ORACLE_TIMESTAMP_FORMAT);
                array_push($objCols, $inStringForm ? $timeIntervalExpression->getExpression() : $timeIntervalExpression);
                continue;
            }
            if ($otherColumnList !== null && in_array($tempCol, $otherColumnList)) {
                array_push($objCols, $tempCol);
                continue;
            }
            if ($minuteToHourColumnList != null && in_array($tempCol, $minuteToHourColumnList)) {
                $minuteToHour = self::minuteToHourColumn($tempCol, $shortForm);
                array_push($objCols, $inStringForm ? $minuteToHour->getExpression() : $minuteToHour);
                continue;
            }

            if (!$selectedOnly) {
                array_push($objCols, Helper::columnExpression($tempCol, $shortForm));
            }
        }
        if ($customCols != null && sizeof($customCols) > 0) {
            $objCols = array_merge($objCols, $customCols);
        }
        return $objCols;
    }

    public static function formatColumn($columnName, $shortForm = null, $format) {
        $pre = "";
        if ($shortForm != null && strlen($shortForm) != 0) {
            $pre = $shortForm . ".";
        }
        return "INITCAP(TO_CHAR({$pre}{$columnName}, '{$format}')) AS {$columnName}";
    }

    public static function minuteToHourColumn($columnName, $shortForm = null) {
        $pre = "";
        if ($shortForm != null && strlen($shortForm) != 0) {
            $pre = $shortForm . ".";
        }
        return "NVL2({$pre}{$columnName},LPAD(TRUNC({$pre}{$columnName}/60,0),2, 0)||':'||LPAD(MOD({$pre}{$columnName},60),2, 0),null) AS {$columnName}";
    }

    public static function getSearchData($adapter, $getDisabled = false) {
        $employeeWhere = (!$getDisabled) ? [HrEmployees::STATUS => "E"] : ["status='E' or employee_id in (select employee_id from HRIS_JOB_HISTORY)"];
        $companyList = self::getTableList($adapter, Company::TABLE_NAME, [Company::COMPANY_ID, Company::COMPANY_NAME], [Company::STATUS => "E"]);
        $branchList = self::getTableList($adapter, Branch::TABLE_NAME, [Branch::BRANCH_ID, Branch::BRANCH_NAME, Branch::COMPANY_ID], [Branch::STATUS => "E"],"","BRANCH_NAME ASC");
        $departmentList = self::getTableList($adapter, Department::TABLE_NAME, [Department::DEPARTMENT_ID, Department::DEPARTMENT_NAME, Department::COMPANY_ID, Department::BRANCH_ID], [Department::STATUS => "E"],"","DEPARTMENT_NAME ASC");
        $designationList = self::getTableList($adapter, Designation::TABLE_NAME, [Designation::DESIGNATION_ID, Designation::DESIGNATION_TITLE, Designation::COMPANY_ID], [Designation::STATUS => 'E'],"","DESIGNATION_TITLE ASC");
        $positionList = self::getTableList($adapter, Position::TABLE_NAME, [Position::POSITION_ID, Position::POSITION_NAME, Position::COMPANY_ID], [Position::STATUS => "E"],"","POSITION_NAME ASC");
        $serviceTypeList = self::getTableList($adapter, ServiceType::TABLE_NAME, [ServiceType::SERVICE_TYPE_ID, ServiceType::SERVICE_TYPE_NAME], [ServiceType::STATUS => "E"],"","SERVICE_TYPE_NAME ASC");
        $serviceEventTypeList = self::getTableList($adapter, ServiceEventType::TABLE_NAME, [ServiceEventType::SERVICE_EVENT_TYPE_ID, ServiceEventType::SERVICE_EVENT_TYPE_NAME], [ServiceEventType::STATUS => "E"],"","SERVICE_EVENT_TYPE_NAME ASC");
        $genderList = self::getTableList($adapter, Gender::TABLE_NAME, [Gender::GENDER_ID, Gender::GENDER_NAME], [Gender::STATUS => "E"]);
        $locationList = self::getTableList($adapter, Location::TABLE_NAME, [Location::LOCATION_ID, Location::LOCATION_EDESC], [Location::STATUS => "E"]);
        $functionalTypeList = self::getTableList($adapter, FunctionalTypes::TABLE_NAME, [FunctionalTypes::FUNCTIONAL_TYPE_ID, FunctionalTypes::FUNCTIONAL_TYPE_EDESC], [FunctionalTypes::STATUS=> "E"],"","FUNCTIONAL_TYPE_EDESC ASC");
        $employeeList = self::getTableList($adapter, HrEmployees::TABLE_NAME, [
                    new Expression(HrEmployees::EMPLOYEE_ID." AS ".HrEmployees::EMPLOYEE_ID),
                    new Expression(HrEmployees::EMPLOYEE_CODE." AS ".HrEmployees::EMPLOYEE_CODE),
                    new Expression("EMPLOYEE_CODE||'-'||FULL_NAME AS FULL_NAME"),
                    new Expression(HrEmployees::FULL_NAME." AS FULL_NAME_SCIENTIFIC"),
                    new Expression(HrEmployees::COMPANY_ID." AS ".HrEmployees::COMPANY_ID),
                    new Expression(HrEmployees::BRANCH_ID." AS ".HrEmployees::BRANCH_ID),
                    new Expression(HrEmployees::DEPARTMENT_ID." AS ".HrEmployees::DEPARTMENT_ID),
                    new Expression(HrEmployees::DESIGNATION_ID." AS ".HrEmployees::DESIGNATION_ID),
                    new Expression(HrEmployees::POSITION_ID." AS ".HrEmployees::POSITION_ID),
                    new Expression(HrEmployees::SERVICE_TYPE_ID." AS ".HrEmployees::SERVICE_TYPE_ID),
                    new Expression(HrEmployees::SERVICE_EVENT_TYPE_ID." AS ".HrEmployees::SERVICE_EVENT_TYPE_ID),
                    new Expression(HrEmployees::GENDER_ID." AS ".HrEmployees::GENDER_ID),
                    new Expression(HrEmployees::EMPLOYEE_TYPE." AS ".HrEmployees::EMPLOYEE_TYPE),
                    new Expression(HrEmployees::GROUP_ID." AS ".HrEmployees::GROUP_ID),
                    new Expression(HrEmployees::FUNCTIONAL_TYPE_ID." AS ".HrEmployees::FUNCTIONAL_TYPE_ID),
//                    HrEmployees::EMPLOYEE_ID,
//                    HrEmployees::EMPLOYEE_CODE,
//                    HrEmployees::FULL_NAME,
//                    HrEmployees::COMPANY_ID,
//                    HrEmployees::BRANCH_ID,
//                    HrEmployees::DEPARTMENT_ID,
//                    HrEmployees::DESIGNATION_ID,
//                    HrEmployees::POSITION_ID,
//                    HrEmployees::SERVICE_TYPE_ID,
//                    HrEmployees::SERVICE_EVENT_TYPE_ID,
//                    HrEmployees::GENDER_ID,
//                    HrEmployees::EMPLOYEE_TYPE,
//                    HrEmployees::GROUP_ID,
                        ], $employeeWhere,"","FULL_NAME_SCIENTIFIC ASC");
        // print_r($employeeList);die;
        $searchValues = [
            'company' => $companyList,
            'branch' => $branchList,
            'department' => $departmentList,
            'designation' => $designationList,
            'position' => $positionList,
            'serviceType' => $serviceTypeList,
            'serviceEventType' => $serviceEventTypeList,
            'gender' => $genderList,
            'employeeType' => [['EMPLOYEE_TYPE_KEY' => 'R', 'EMPLOYEE_TYPE_VALUE' => 'Employee'], ['EMPLOYEE_TYPE_KEY' => 'C', 'EMPLOYEE_TYPE_VALUE' => 'Worker']],
            'employee' => $employeeList,
            'location' => $locationList,
            'functionalType' => $functionalTypeList,
        ];
        /* end of search values */

        return $searchValues;
    }

    public static function getSearchDataCompanyWise($adapter, $getDisabled = false, $companyId) {
     
        $employeeWhere = (!$getDisabled) ? [HrEmployees::STATUS => "E"] : ["status='E' and company_id = $companyId"];
        $companyList = self::getTableList($adapter, Company::TABLE_NAME, [Company::COMPANY_ID, Company::COMPANY_NAME], [Company::STATUS => "E"]);
        $branchList = self::getTableList($adapter, Branch::TABLE_NAME, [Branch::BRANCH_ID, Branch::BRANCH_NAME, Branch::COMPANY_ID], [Branch::STATUS => "E"],"","BRANCH_NAME ASC");
        $departmentList = self::getTableList($adapter, Department::TABLE_NAME, [Department::DEPARTMENT_ID, Department::DEPARTMENT_NAME, Department::COMPANY_ID, Department::BRANCH_ID], [Department::STATUS => "E"],"","DEPARTMENT_NAME ASC");
        $designationList = self::getTableList($adapter, Designation::TABLE_NAME, [Designation::DESIGNATION_ID, Designation::DESIGNATION_TITLE, Designation::COMPANY_ID], [Designation::STATUS => 'E'],"","DESIGNATION_TITLE ASC");
        $positionList = self::getTableList($adapter, Position::TABLE_NAME, [Position::POSITION_ID, Position::POSITION_NAME, Position::COMPANY_ID], [Position::STATUS => "E"],"","POSITION_NAME ASC");
        $serviceTypeList = self::getTableList($adapter, ServiceType::TABLE_NAME, [ServiceType::SERVICE_TYPE_ID, ServiceType::SERVICE_TYPE_NAME], [ServiceType::STATUS => "E"],"","SERVICE_TYPE_NAME ASC");
        $serviceEventTypeList = self::getTableList($adapter, ServiceEventType::TABLE_NAME, [ServiceEventType::SERVICE_EVENT_TYPE_ID, ServiceEventType::SERVICE_EVENT_TYPE_NAME], [ServiceEventType::STATUS => "E"],"","SERVICE_EVENT_TYPE_NAME ASC");
        $genderList = self::getTableList($adapter, Gender::TABLE_NAME, [Gender::GENDER_ID, Gender::GENDER_NAME], [Gender::STATUS => "E"]);
        $locationList = self::getTableList($adapter, Location::TABLE_NAME, [Location::LOCATION_ID, Location::LOCATION_EDESC], [Location::STATUS => "E"]);
        $functionalTypeList = self::getTableList($adapter, FunctionalTypes::TABLE_NAME, [FunctionalTypes::FUNCTIONAL_TYPE_ID, FunctionalTypes::FUNCTIONAL_TYPE_EDESC], [FunctionalTypes::STATUS=> "E"],"","FUNCTIONAL_TYPE_EDESC ASC");
        $employeeList = self::getTableList($adapter, HrEmployees::TABLE_NAME, [
                    new Expression(HrEmployees::EMPLOYEE_ID." AS ".HrEmployees::EMPLOYEE_ID),
                    new Expression(HrEmployees::EMPLOYEE_CODE." AS ".HrEmployees::EMPLOYEE_CODE),
                    new Expression("EMPLOYEE_CODE||'-'||FULL_NAME AS FULL_NAME"),
                    new Expression(HrEmployees::FULL_NAME." AS FULL_NAME_SCIENTIFIC"),
                    new Expression(HrEmployees::COMPANY_ID." AS ".HrEmployees::COMPANY_ID),
                    new Expression(HrEmployees::BRANCH_ID." AS ".HrEmployees::BRANCH_ID),
                    new Expression(HrEmployees::DEPARTMENT_ID." AS ".HrEmployees::DEPARTMENT_ID),
                    new Expression(HrEmployees::DESIGNATION_ID." AS ".HrEmployees::DESIGNATION_ID),
                    new Expression(HrEmployees::POSITION_ID." AS ".HrEmployees::POSITION_ID),
                    new Expression(HrEmployees::SERVICE_TYPE_ID." AS ".HrEmployees::SERVICE_TYPE_ID),
                    new Expression(HrEmployees::SERVICE_EVENT_TYPE_ID." AS ".HrEmployees::SERVICE_EVENT_TYPE_ID),
                    new Expression(HrEmployees::GENDER_ID." AS ".HrEmployees::GENDER_ID),
                    new Expression(HrEmployees::EMPLOYEE_TYPE." AS ".HrEmployees::EMPLOYEE_TYPE),
                    new Expression(HrEmployees::GROUP_ID." AS ".HrEmployees::GROUP_ID),
                    new Expression(HrEmployees::FUNCTIONAL_TYPE_ID." AS ".HrEmployees::FUNCTIONAL_TYPE_ID),
//                    HrEmployees::EMPLOYEE_ID,
//                    HrEmployees::EMPLOYEE_CODE,
//                    HrEmployees::FULL_NAME,
//                    HrEmployees::COMPANY_ID,
//                    HrEmployees::BRANCH_ID,
//                    HrEmployees::DEPARTMENT_ID,
//                    HrEmployees::DESIGNATION_ID,
//                    HrEmployees::POSITION_ID,
//                    HrEmployees::SERVICE_TYPE_ID,
//                    HrEmployees::SERVICE_EVENT_TYPE_ID,
//                    HrEmployees::GENDER_ID,
//                    HrEmployees::EMPLOYEE_TYPE,
//                    HrEmployees::GROUP_ID,
                        ], $employeeWhere,"","FULL_NAME_SCIENTIFIC ASC");
                      
        $searchValues = [
            'company' => $companyList,
            'branch' => $branchList,
            'department' => $departmentList,
            'designation' => $designationList,
            'position' => $positionList,
            'serviceType' => $serviceTypeList,
            'serviceEventType' => $serviceEventTypeList,
            'gender' => $genderList,
            'employeeType' => [['EMPLOYEE_TYPE_KEY' => 'R', 'EMPLOYEE_TYPE_VALUE' => 'Employee'], ['EMPLOYEE_TYPE_KEY' => 'C', 'EMPLOYEE_TYPE_VALUE' => 'Worker']],
            'employee' => $employeeList,
            'location' => $locationList,
            'functionalType' => $functionalTypeList,
        ];
        /* end of search values */

        return $searchValues;
    }

    public static function batchSQL($fn) {
        self::rawQueryResult($this->adapter, "SAVEPOINT multipleEmployeeAssign");
        try {
            $fn();
        } catch (Exception $e) {
            self::rawQueryResult($this->adapter, "ROLLBACK TO SAVEPOINT multipleEmployeeAssign");
        }
        self::rawQueryResult($this->adapter, "COMMIT");
    }

    public static function employeesIn($companyId = null, $branchId = null, $departmentId = null, $positionId = null, $designationId = null, $serviceTypeId = null, $serviceEventTypeId = null, $employeeTypeId = null, $employeeId = null) {
        $companyCondition = '';
        $branchCondition = '';
        $departmentCondition = '';
        $designationCondition = '';
        $positionCondition = '';
        $serviceTypeCondition = '';
        $serviceEventtypeCondition = '';
        $employeeTypeCondition = '';
        $employeeCondition = '';

        if ($companyId != null && $companyId != -1) {
            $companyCondition = " AND E.COMPANY_ID ={$companyId} ";
        }
        if ($branchId != null && $branchId != -1) {
            $branchCondition = " AND E.BRANCH_ID ={$branchId} ";
        }
        if ($departmentId != null && $departmentId != -1) {
            $departmentCondition = " AND E.DEPARTMENT_ID ={$departmentId} ";
        }
        if ($designationId != null && $designationId != -1) {
            $designationCondition = " AND E.DESIGNATION_ID ={$designationId} ";
        }
        if ($positionId != null && $positionId != -1) {
            $positionCondition = " AND E.POSITION_ID ={$positionId} ";
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
        if ($employeeId != null && $employeeId != -1) {
            $employeeCondition = " AND A.EMPLOYEE_ID ={$employeeId} ";
        }

        return "SELECT E.EMPLOYEE_ID FROM HRIS_EMPLOYEES E WHERE 1=1 {$companyCondition}{$branchCondition}{$departmentCondition}{$designationCondition}{$positionCondition}{$serviceTypeCondition}{$serviceEventtypeCondition}{$employeeTypeCondition}{$employeeCondition}";
    }

    public static function conditionBuilder($colValue, $colName, $conditonType, $isString = false, $parentQuery = false) {
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
    
    public static function conditionBuilderBounded($colValue, $colName, $conditonType, $isString = false, $parentQuery = false) {
        $returnData=[];
       $parameterData=[];
        if (gettype($colValue) === "array") {
             $valuesinCSV = "";
            for ($i = 0; $i < sizeof($colValue); $i++) {
                $tempname=$colName.$i;
                $tempname=str_replace('.', '', $tempname);
                $value = $isString ? "'{$colValue[$i]}'" : $colValue[$i];
                if ($i + 1 == sizeof($colValue)) {
                    $valuesinCSV .= ":{$tempname}";
                } else {
                    $valuesinCSV .= ":{$tempname},";
                }
                $parameterData[$tempname]=$colValue[$i];
            }
            if ($parentQuery) {
                $valuesinCSV = str_replace('INVALUES', $valuesinCSV, $parentQuery);
            }
            
            $sql="{$conditonType} {$colName} in ({$valuesinCSV})";
            $returnData['sql']=$sql;
            $returnData['parameter']=$parameterData;
            return $returnData;
        } else {
            $value = $isString ? "'{$colValue}'" : $colValue;
            $tempname=str_replace('.', '', $colName);
            $parameterData[$tempname]=$colValue;
            if ($parentQuery) {
                $value = str_replace('INVALUES', $value, $parentQuery);
                return " {$conditonType} {$colName} IN (:{$tempname})";
            } else {
                $sql = "{$conditonType} {$colName} = :{$tempname} ";
            }
            $returnData['sql']=$sql;
            $returnData['parameter']=$parameterData;
            return $returnData;
        }
    }
    

    public static function getSearchConditon($companyId, $branchId, $departmentId, $positionId, $designationId, $serviceTypeId, $serviceEventTypeId, $employeeTypeId, $employeeId, $genderId = null, $locationId = null, $functionalTypeId = null) {
        $conditon = "";
        if ($companyId != null && $companyId != -1) {
            $conditon .= self::conditionBuilder($companyId, "E.COMPANY_ID", "AND");
        }
        if ($branchId != null && $branchId != -1) {
            $conditon .= self::conditionBuilder($branchId, "E.BRANCH_ID", "AND");
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
            $conditon .= self::conditionBuilder($departmentId, "E.DEPARTMENT_ID", "AND", false, $parentQuery);
        }
        if ($positionId != null && $positionId != -1) {
            $conditon .= self::conditionBuilder($positionId, "E.POSITION_ID", "AND");
        }
        if ($designationId != null && $designationId != -1) {
            $conditon .= self::conditionBuilder($designationId, "E.DESIGNATION_ID", "AND");
        }
        if ($serviceTypeId != null && $serviceTypeId != -1) {
            $conditon .= self::conditionBuilder($serviceTypeId, "E.SERVICE_TYPE_ID", "AND");
        } else {
            $conditon .= " AND (E.SERVICE_TYPE_ID IN (SELECT SERVICE_TYPE_ID FROM HRIS_SERVICE_TYPES WHERE TYPE NOT IN ('RESIGNED','RETIRED')) OR E.SERVICE_TYPE_ID IS NULL)";
        }
        if ($serviceEventTypeId != null && $serviceEventTypeId != -1) {
            $conditon .= self::conditionBuilder($serviceEventTypeId, "E.SERVICE_EVENT_TYPE_ID", "AND");
        }
        if ($employeeTypeId != null && $employeeTypeId != -1) {
            $conditon .= self::conditionBuilder($employeeTypeId, "E.EMPLOYEE_TYPE", "AND", true);
        }
        if ($employeeId != null && $employeeId != -1) {
            $conditon .= self::conditionBuilder($employeeId, "E.EMPLOYEE_ID", "AND");
        }
        if ($genderId != null && $genderId != -1) {
            $conditon .= self::conditionBuilder($genderId, "E.GENDER_ID", "AND");
        }
        if ($locationId != null && $locationId != -1) {
            $conditon .= self::conditionBuilder($locationId, "E.LOCATION_ID", "AND");
        }
        if ($functionalTypeId != null && $functionalTypeId != -1) {
            $conditon .= self::conditionBuilder($functionalTypeId, "E.FUNCTIONAL_TYPE_ID", "AND");
        }
        return $conditon;
    }
    
    public static function getSearchConditonBounded($companyId, $branchId, $departmentId, $positionId, $designationId, $serviceTypeId, $serviceEventTypeId, $employeeTypeId, $employeeId, $genderId = null, $locationId = null, $functionalTypeId = null) {
        $conditon = "";
        $allParameters=[];
        if ($companyId != null && $companyId != -1) {
            $employeeConditon = self::conditionBuilderBounded($companyId, "E.COMPANY_ID", "AND");
            $conditon .=$employeeConditon['sql'];
            $allParameters=array_merge($allParameters,$employeeConditon['parameter']);
        }
        if ($branchId != null && $branchId != -1) {
            $employeeConditon = self::conditionBuilderBounded($branchId, "E.BRANCH_ID", "AND");
            $conditon .=$employeeConditon['sql'];
            $allParameters=array_merge($allParameters,$employeeConditon['parameter']);
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
            $employeeConditon = self::conditionBuilderBounded($departmentId, "E.DEPARTMENT_ID", "AND", false, $parentQuery);
            $conditon .=$employeeConditon['sql'];
            $allParameters=array_merge($allParameters,$employeeConditon['parameter']);
        }
        if ($positionId != null && $positionId != -1) {
            $employeeConditon = self::conditionBuilderBounded($positionId, "E.POSITION_ID", "AND");
            $conditon .=$employeeConditon['sql'];
            $allParameters=array_merge($allParameters,$employeeConditon['parameter']);
        }
        if ($designationId != null && $designationId != -1) {
            $employeeConditon = self::conditionBuilderBounded($designationId, "E.DESIGNATION_ID", "AND");
            $conditon .=$employeeConditon['sql'];
            $allParameters=array_merge($allParameters,$employeeConditon['parameter']);
        }
        if ($serviceTypeId != null && $serviceTypeId != -1) {
            $employeeConditon = self::conditionBuilderBounded($serviceTypeId, "E.SERVICE_TYPE_ID", "AND");
            $conditon .=$employeeConditon['sql'];
            $allParameters=array_merge($allParameters,$employeeConditon['parameter']);
        } else {
            $conditon .= " AND (E.SERVICE_TYPE_ID IN (SELECT SERVICE_TYPE_ID FROM HRIS_SERVICE_TYPES WHERE TYPE NOT IN ('RESIGNED','RETIRED')) OR E.SERVICE_TYPE_ID IS NULL)";
        }
        if ($serviceEventTypeId != null && $serviceEventTypeId != -1) {
            $employeeConditon = self::conditionBuilderBounded($serviceEventTypeId, "E.SERVICE_EVENT_TYPE_ID", "AND");
            $conditon .=$employeeConditon['sql'];
            $allParameters=array_merge($allParameters,$employeeConditon['parameter']);
        }
        if ($employeeTypeId != null && $employeeTypeId != -1) {
            $employeeConditon = self::conditionBuilderBounded($employeeTypeId, "E.EMPLOYEE_TYPE", "AND", true);
            $conditon .=$employeeConditon['sql'];
            $allParameters=array_merge($allParameters,$employeeConditon['parameter']);
        }
        if ($employeeId != null && $employeeId != -1) {
            $employeeConditon = self::conditionBuilderBounded($employeeId, "E.EMPLOYEE_ID", "AND");
            $conditon .=$employeeConditon['sql'];
            $allParameters=array_merge($allParameters,$employeeConditon['parameter']);
        }
        if ($genderId != null && $genderId != -1) {
            $employeeConditon = self::conditionBuilderBounded($genderId, "E.GENDER_ID", "AND");
            $conditon .=$employeeConditon['sql'];
            $allParameters=array_merge($allParameters,$employeeConditon['parameter']);
        }
        if ($locationId != null && $locationId != -1) {
            $employeeConditon = self::conditionBuilderBounded($locationId, "E.LOCATION_ID", "AND");
            $conditon .=$employeeConditon['sql'];
            $allParameters=array_merge($allParameters,$employeeConditon['parameter']);
        }
        if ($functionalTypeId != null && $functionalTypeId != -1) {
            $employeeConditon = self::conditionBuilderBounded($functionalTypeId, "E.FUNCTIONAL_TYPE_ID", "AND");
            $conditon .=$employeeConditon['sql'];
            $allParameters=array_merge($allParameters,$employeeConditon['parameter']);
        }
        $boundedconditon['sql']=$conditon;
        $boundedconditon['parameter']=$allParameters;
        return $boundedconditon;
    }

    public static function getAttendanceStatusSelectElement() {
        $statusFormElement = new Select2();
        $statusFormElement->setName("status");
        $status = array(
            "P" => "Present Only",
            "A" => "Absent Only",
            "H" => "On Holiday",
            "L" => "On Leave",
            "T" => "On Training",
            "TVL" => "On Travel",
            "WOH" => "Work on Holiday",
            "WOD" => "Work on DAYOFF",
        );
        $statusFormElement->setValueOptions($status);
        $statusFormElement->setAttributes(["id" => "statusId", "class" => "form-control", "multiple" => "multiple"]);
        $statusFormElement->setLabel("Status");
        return $statusFormElement;
    }

    public static function getAttendancePresentStatusSelectElement() {
        $statusFormElement = new Select2();
        $statusFormElement->setName("presentStatus");
        $status = array(
            "LI" => "Late In",
            "EO" => "Early Out",
            "MP" => "Missed Punched",
        );
        $statusFormElement->setValueOptions($status);
        $statusFormElement->setAttributes(["id" => "presentStatusId", "class" => "form-control", "multiple" => "multiple"]);
        $statusFormElement->setLabel("Present Status");
        return $statusFormElement;
    }

    public static function getOrderBy($default,$mandatory=null,$seniorityLevel=null,$position=null,$joinDate=null,$designation=null,$name=null) {
        $auth = new \Zend\Authentication\AuthenticationService();
        $preference = $auth->getStorage()->read()['preference'];
        $orderByString = '';
        
        if($preference['orderBySeniority']=='Y' && $seniorityLevel!=null){
            $orderByString.=(empty($orderByString))?' '.$seniorityLevel.'  ASC':','.$seniorityLevel.'  ASC';
        }
        
        if ($preference['orderByPosition']=='Y' && $position!=null) {
            $orderByString.=(empty($orderByString))?' '.$position.'  ASC':','.$position.'  ASC';
        }
        if ($preference['orderByJoinDate']=='Y' && $joinDate!=null) {
            $orderByString.=(empty($orderByString))?' '.$joinDate.'  ASC':','.$joinDate.'  ASC';
        }
        if ($preference['orderByDesignation']=='Y' && $designation!=null) {
            $orderByString.=(empty($orderByString))?' '.$designation.'  ASC':','.$designation.'  ASC';
        }
        if ($preference['orderByName']=='Y' && $name!=null) {
            $orderByString.=(empty($orderByString))?' '.$name.'  ASC':','.$name.'  ASC';
        }
        if($mandatory!=null){
            $orderByString.=(empty($orderByString))?' '.$mandatory:','.$mandatory;
        }
        if(empty($orderByString)){
            $orderByString=$default;
        }
        return ' ORDER BY '.$orderByString;
    }
    
    
     public static function getSearchConditonPayroll($companyId, $branchId, $departmentId, $positionId, $designationId, $serviceTypeId, $serviceEventTypeId, $employeeTypeId, $employeeId, $genderId = null, $locationId = null, $functionalTypeId = null) {
        $conditon = "";
        if ($companyId != null && $companyId != -1) {
            $conditon .= self::conditionBuilder($companyId, "SSED.COMPANY_ID", "AND");
        }
        if ($branchId != null && $branchId != -1) {
            $conditon .= self::conditionBuilder($branchId, "SSED.BRANCH_ID", "AND");
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
            $conditon .= self::conditionBuilder($departmentId, "SSED.DEPARTMENT_ID", "AND", false, $parentQuery);
        }
        if ($positionId != null && $positionId != -1) {
            $conditon .= self::conditionBuilder($positionId, "SSED.POSITION_ID", "AND");
        }
        if ($designationId != null && $designationId != -1) {
            $conditon .= self::conditionBuilder($designationId, "SSED.DESIGNATION_ID", "AND");
        }
        if ($serviceTypeId != null && $serviceTypeId != -1) {
            $conditon .= self::conditionBuilder($serviceTypeId, "SSED.SERVICE_TYPE_ID", "AND");
        }
//        if ($serviceEventTypeId != null && $serviceEventTypeId != -1) {
//            $conditon .= self::conditionBuilder($serviceEventTypeId, "E.SERVICE_EVENT_TYPE_ID", "AND");
//        }
//        if ($employeeTypeId != null && $employeeTypeId != -1) {
//            $conditon .= self::conditionBuilder($employeeTypeId, "E.EMPLOYEE_TYPE", "AND", true);
//        }
        if ($employeeId != null && $employeeId != -1) {
            $conditon .= self::conditionBuilder($employeeId, "SSED.EMPLOYEE_ID", "AND");
        }
        if ($genderId != null && $genderId != -1) {
            $conditon .= self::conditionBuilder($genderId, "SSED.GENDER_ID", "AND");
        }
//        if ($locationId != null && $locationId != -1) {
//            $conditon .= self::conditionBuilder($locationId, "E.LOCATION_ID", "AND");
//        }
        if ($functionalTypeId != null && $functionalTypeId != -1) {
            $conditon .= self::conditionBuilder($functionalTypeId, "SSED.FUNCTIONAL_TYPE_ID", "AND");
        }
        return $conditon;
    }
	
	public static function getSearchConditonBoundedPayroll($companyId, $branchId, $departmentId, $positionId, $designationId, $serviceTypeId, $serviceEventTypeId, $employeeTypeId, $employeeId, $genderId = null, $locationId = null, $functionalTypeId = null) {
        $conditon = "";
        $allParameters=[];
        if ($companyId != null && $companyId != -1) {
            $employeeConditon = self::conditionBuilderBounded($companyId, "E.COMPANY_ID", "AND");
            $conditon .=$employeeConditon['sql'];
            $allParameters=array_merge($allParameters,$employeeConditon['parameter']);
        }
        if ($branchId != null && $branchId != -1) {
            $employeeConditon = self::conditionBuilderBounded($branchId, "E.BRANCH_ID", "AND");
            $conditon .=$employeeConditon['sql'];
            $allParameters=array_merge($allParameters,$employeeConditon['parameter']);
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
            $employeeConditon = self::conditionBuilderBounded($departmentId, "E.DEPARTMENT_ID", "AND", false, $parentQuery);
            $conditon .=$employeeConditon['sql'];
            $allParameters=array_merge($allParameters,$employeeConditon['parameter']);
        }
        if ($positionId != null && $positionId != -1) {
            $employeeConditon = self::conditionBuilderBounded($positionId, "E.POSITION_ID", "AND");
            $conditon .=$employeeConditon['sql'];
            $allParameters=array_merge($allParameters,$employeeConditon['parameter']);
        }
        if ($designationId != null && $designationId != -1) {
            $employeeConditon = self::conditionBuilderBounded($designationId, "E.DESIGNATION_ID", "AND");
            $conditon .=$employeeConditon['sql'];
            $allParameters=array_merge($allParameters,$employeeConditon['parameter']);
        }
        if ($serviceTypeId != null && $serviceTypeId != -1) {
            $employeeConditon = self::conditionBuilderBounded($serviceTypeId, "E.SERVICE_TYPE_ID", "AND");
            $conditon .=$employeeConditon['sql'];
            $allParameters=array_merge($allParameters,$employeeConditon['parameter']);
        } 
        if ($serviceEventTypeId != null && $serviceEventTypeId != -1) {
            $employeeConditon = self::conditionBuilderBounded($serviceEventTypeId, "E.SERVICE_EVENT_TYPE_ID", "AND");
            $conditon .=$employeeConditon['sql'];
            $allParameters=array_merge($allParameters,$employeeConditon['parameter']);
        }
        if ($employeeTypeId != null && $employeeTypeId != -1) {
            $employeeConditon = self::conditionBuilderBounded($employeeTypeId, "E.EMPLOYEE_TYPE", "AND", true);
            $conditon .=$employeeConditon['sql'];
            $allParameters=array_merge($allParameters,$employeeConditon['parameter']);
        }
        if ($employeeId != null && $employeeId != -1) {
            $employeeConditon = self::conditionBuilderBounded($employeeId, "E.EMPLOYEE_ID", "AND");
            $conditon .=$employeeConditon['sql'];
            $allParameters=array_merge($allParameters,$employeeConditon['parameter']);
        }
        if ($genderId != null && $genderId != -1) {
            $employeeConditon = self::conditionBuilderBounded($genderId, "E.GENDER_ID", "AND");
            $conditon .=$employeeConditon['sql'];
            $allParameters=array_merge($allParameters,$employeeConditon['parameter']);
        }
        if ($locationId != null && $locationId != -1) {
            $employeeConditon = self::conditionBuilderBounded($locationId, "E.LOCATION_ID", "AND");
            $conditon .=$employeeConditon['sql'];
            $allParameters=array_merge($allParameters,$employeeConditon['parameter']);
        }
        if ($functionalTypeId != null && $functionalTypeId != -1) {
            $employeeConditon = self::conditionBuilderBounded($functionalTypeId, "E.FUNCTIONAL_TYPE_ID", "AND");
            $conditon .=$employeeConditon['sql'];
            $allParameters=array_merge($allParameters,$employeeConditon['parameter']);
        }
        $boundedconditon['sql']=$conditon;
        $boundedconditon['parameter']=$allParameters;
        return $boundedconditon;
    }

    public static function applyRoleControl($adapter, $employeeId, $where){
        $sql = "SELECT CONTROL FROM HRIS_ROLES WHERE ROLE_ID = (SELECT ROLE_ID FROM HRIS_USERS WHERE EMPLOYEE_ID = $employeeId)";
        $roleControl = Helper::extractDbData(self::rawQueryResult($adapter, $sql))[0]['CONTROL'];
        
        switch ($roleControl) {
            case 'B': 
            $controlData = Helper::extractDbData(self::rawQueryResult($adapter, "SELECT VAL FROM HRIS_ROLE_CONTROL WHERE ROLE_ID = (SELECT ROLE_ID FROM HRIS_USERS WHERE EMPLOYEE_ID = $employeeId) AND CONTROL = 'B'"));
            if(count($controlData) == 0){
                $where['BRANCH_ID'] = Helper::extractDbData(self::rawQueryResult($adapter, "SELECT BRANCH_ID FROM HRIS_EMPLOYEES WHERE EMPLOYEE_ID = $employeeId"))[0]['BRANCH_ID'];
            }
            else{
                $where['BRANCH_ID'] = [];
                foreach ($controlData as $data) {
                    array_push($where['BRANCH_ID'], $data['VAL']);
                }
            }
            break;

            case 'C': 
            $controlData = Helper::extractDbData(self::rawQueryResult($adapter, "SELECT VAL FROM HRIS_ROLE_CONTROL WHERE ROLE_ID = (SELECT ROLE_ID FROM HRIS_USERS WHERE EMPLOYEE_ID = $employeeId) AND CONTROL = 'C'"));
            if(count($controlData) == 0){
                $where['COMPANY_ID'] = Helper::extractDbData(self::rawQueryResult($adapter, "SELECT COMPANY_ID FROM HRIS_EMPLOYEES WHERE EMPLOYEE_ID = $employeeId"))[0]['COMPANY_ID'];
            }
            else{
                $where['COMPANY_ID'] = [];
                foreach ($controlData as $data) {
                    array_push($where['COMPANY_ID'], $data['VAL']);
                }
            }
            break;

            case 'DP': 
            $controlData = Helper::extractDbData(self::rawQueryResult($adapter, "SELECT VAL FROM HRIS_ROLE_CONTROL WHERE ROLE_ID = (SELECT ROLE_ID FROM HRIS_USERS WHERE EMPLOYEE_ID = $employeeId) AND CONTROL = 'DP'"));
            if(count($controlData) == 0){
                $where['DEPARTMENT_ID'] = Helper::extractDbData(self::rawQueryResult($adapter, "SELECT DEPARTMENT_ID FROM HRIS_EMPLOYEES WHERE EMPLOYEE_ID = $employeeId"))[0]['DEPARTMENT_ID'];
            }
            else{
                $depVal;
                $depCounter=1;
                foreach ($controlData as $data) {
                    if($depCounter==1){
                    $depVal.=$data['VAL'];
                    }else{
                    $depVal.=",";
                    $depVal.=$data['VAL'];
                    }
                    $depCounter++;
                }
                    array_push($where,"DEPARTMENT_ID in (SELECT DEPARTMENT_ID FROM
                         HRIS_DEPARTMENTS 
                        START WITH PARENT_DEPARTMENT in ({$depVal})
                        CONNECT BY PARENT_DEPARTMENT= PRIOR DEPARTMENT_ID
                        UNION 
                        SELECT DEPARTMENT_ID FROM HRIS_DEPARTMENTS WHERE DEPARTMENT_ID IN ({$depVal})
                        UNION
                        SELECT  TO_NUMBER(TRIM(REGEXP_SUBSTR(EXCEPTIONAL,'[^,]+', 1, LEVEL) )) DEPARTMENT_ID
  FROM (SELECT EXCEPTIONAL  FROM  HRIS_DEPARTMENTS WHERE DEPARTMENT_ID IN  ({$depVal}))
   CONNECT BY  REGEXP_SUBSTR(EXCEPTIONAL, '[^,]+', 1, LEVEL) IS NOT NULL)");
            }
            break;

            case 'DS': 
            $controlData = Helper::extractDbData(self::rawQueryResult($adapter, "SELECT VAL FROM HRIS_ROLE_CONTROL WHERE ROLE_ID = (SELECT ROLE_ID FROM HRIS_USERS WHERE EMPLOYEE_ID = $employeeId) AND CONTROL = 'DS'"));
            if(count($controlData) == 0){
                $where['DESIGNATION_ID'] = Helper::extractDbData(self::rawQueryResult($adapter, "SELECT DESIGNATION_ID FROM HRIS_EMPLOYEES WHERE EMPLOYEE_ID = $employeeId"))[0]['DESIGNATION_ID'];
            }
            else{
                $where['DESIGNATION_ID'] = [];
                foreach ($controlData as $data) {
                    array_push($where['DESIGNATION_ID'], $data['VAL']);
                }
            }
            break;

            case 'P': 
            $controlData = Helper::extractDbData(self::rawQueryResult($adapter, "SELECT VAL FROM HRIS_ROLE_CONTROL WHERE ROLE_ID = (SELECT ROLE_ID FROM HRIS_USERS WHERE EMPLOYEE_ID = $employeeId) AND CONTROL = 'P'"));
            if(count($controlData) == 0){
                $where['POSITION_ID'] = Helper::extractDbData(self::rawQueryResult($adapter, "SELECT POSITION_ID FROM HRIS_EMPLOYEES WHERE EMPLOYEE_ID = $employeeId"))[0]['POSITION_ID'];
            }
            else{
                $where['POSITION_ID'] = [];
                foreach ($controlData as $data) {
                    array_push($where['POSITION_ID'], $data['VAL']);
                }
            }
            break;
        }
        return $where;
    }

    public function getEmployeeIdFromCode($adapter, $code){
        $sql = "SELECT EMPLOYEE_ID FROM HRIS_EMPLOYEES WHERE EMPLOYEE_CODE = '{$code}'";
        $result = self::rawQueryResult($adapter, $sql);
        return Helper::extractDbData($result)[0]['EMPLOYEE_ID'];
    }

    public static function getProvinceList($adapter) {
        return self::getTableKVListWithSortOption($adapter, "HRIS_PROVINCES", "PROVINCE_ID", ["PROVINCE_NAME"], ["STATUS" => 'E'], "PROVINCE_ID", "ASC", "-");
    }

    public static function getBranchFromProvince($adapter) {
        return self::getTableKVList($adapter,'HRIS_BRANCHES','BRANCH_ID',['PROVINCE_ID'],"STATUS = 'E'");
    }
}
