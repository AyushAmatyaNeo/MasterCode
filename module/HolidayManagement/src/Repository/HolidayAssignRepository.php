<?php

namespace HolidayManagement\Repository;

use Application\Helper\EntityHelper;
use Application\Helper\Helper;
use Zend\Db\Adapter\AdapterInterface;

class HolidayAssignRepository {

    private $adapter;

    public function __construct(AdapterInterface $adapter) {
        $this->adapter = $adapter;
    }

    public function filterEmployees($employeeId, $branchId, $departmentId, $designationId, $positionId, $serviceTypeId, $serviceEventTypeId, $companyId, $genderId = null, $employeeTypeId = null) {
        $boundedParams = [];
        $searchCondition = EntityHelper::getSearchConditonBounded($companyId, $branchId, $departmentId, $positionId, $designationId, $serviceTypeId, $serviceEventTypeId, $employeeTypeId, $employeeId,$genderId);
        $boundedParams = array_merge($boundedParams, $searchCondition['parameter']);

        $orderByString = EntityHelper::getOrderBy('E.FULL_NAME ASC', null, 'E.SENIORITY_LEVEL', 'P.LEVEL_NO', 'E.JOIN_DATE', 'DES.ORDER_NO', 'E.FULL_NAME');

        $sql = "SELECT 
                  E.EMPLOYEE_ID                                                AS EMPLOYEE_ID,
                  E.EMPLOYEE_CODE                                                   AS EMPLOYEE_CODE,
                  INITCAP(E.FIRST_NAME)                                              AS MIDDLE_NAME,
                  INITCAP(E.MIDDLE_NAME)                                              AS FULL_NAME,
                  INITCAP(E.LAST_NAME)                                              AS LAST_NAME,
                  INITCAP(E.FULL_NAME)                                              AS FULL_NAME,
                  INITCAP(G.GENDER_NAME)                                            AS GENDER_NAME,
                  (C.COMPANY_NAME)                                           AS COMPANY_NAME,
                  (B.BRANCH_NAME)                                            AS BRANCH_NAME,
                  (D.DEPARTMENT_NAME)                                        AS DEPARTMENT_NAME,
                  (DES.DESIGNATION_TITLE)                                    AS DESIGNATION_TITLE,
                  (P.POSITION_NAME)                                          AS POSITION_NAME,
                  P.LEVEL_NO                                                        AS LEVEL_NO,
                  INITCAP(ST.SERVICE_TYPE_NAME)                                     AS SERVICE_TYPE_NAME,
                  (CASE WHEN E.EMPLOYEE_TYPE='R' THEN 'REGULAR' ELSE 'WORKER' END)  AS EMPLOYEE_TYPE
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
                WHERE 1                 =1 AND E.STATUS='E' 
                {$searchCondition['sql']} {$orderByString}";
                
                $statement = $this->adapter->query($sql);
                $result = $statement->execute($boundedParams);
                return Helper::extractDbData($result);
    }

    public function getHolidayAssignedEmployees($holidayId) {
        return EntityHelper::rawQueryResult($this->adapter, "SELECT EMPLOYEE_ID FROM HRIS_EMPLOYEE_HOLIDAY WHERE HOLIDAY_ID='{$holidayId}'");
    }

    public function multipleEmployeeAssignToHoliday($holidayId, $employeeIdList) {
        foreach ($employeeIdList as $empId) {
            $employeeId = $empId[0]->id;
            $status = $empId[0]->s;
            $boundedParams = [];
            $boundedParams['holidayId'] = $holidayId;
            $boundedParams['employeeId'] = $employeeId;
            EntityHelper::rawQueryResult($this->adapter, "DELETE FROM HRIS_EMPLOYEE_HOLIDAY WHERE HOLIDAY_ID= :holidayId AND EMPLOYEE_ID= :employeeId", $boundedParams);
            if ($status == 'A') {
                EntityHelper::rawQueryResult($this->adapter, "INSERT INTO HRIS_EMPLOYEE_HOLIDAY(HOLIDAY_ID,EMPLOYEE_ID) VALUES(:holidayId,:employeeId)", $boundedParams);
            }
        }
    }

}
