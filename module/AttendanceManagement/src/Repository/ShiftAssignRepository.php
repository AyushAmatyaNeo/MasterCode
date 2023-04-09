<?php

namespace AttendanceManagement\Repository;

use Application\Helper\EntityHelper;
use Application\Helper\Helper;
use Application\Model\Model;
use Application\Repository\HrisRepository;
use AttendanceManagement\Model\ShiftAssign;
use Zend\Db\Adapter\AdapterInterface;

class ShiftAssignRepository extends HrisRepository {

     public function __construct(AdapterInterface $adapter) {
         parent::__construct($adapter, ShiftAssign::TABLE_NAME);
    }

    public function add(Model $model) {
        $this->tableGateway->insert($model->getArrayCopyForDB());
    }

    public function edit(Model $model, $id) {
        $this->tableGateway->update($model->getArrayCopyForDB(), [ShiftAssign::EMPLOYEE_ID . "=$id[0]", ShiftAssign::SHIFT_ID . " =$id[1]"]);
    }

    public function fetchAll() {
        return $this->tableGateway->select();
    }

    public function fetchById($id) {
        
    }

    public function delete($id) {
        
    }

    public function fetchByEmployeeId($employeeId) {
        $result = $this->tableGateway->select([ShiftAssign::EMPLOYEE_ID . "=" . $employeeId, ShiftAssign::STATUS => 'E']);
        // echo '<pre>';print_r($result);die;
        return $result->current();
    }

    public function fetchShiftAssignWithDetail($data) {
        
        $employeeId = $data['employeeId'];
        $companyId = $data['companyId'];
        $branchId = $data['branchId'];
        $departmentId = $data['departmentId'];
        $designationId = $data['designationId'];
        $positionId = $data['positionId'];
        $serviceTypeId = $data['serviceTypeId'];
        $serviceEventTypeId = $data['serviceEventTypeId'];
        $employeeTypeId = $data['employeeTypeId'];

        $boundedParams = [];
        $searchCondition = EntityHelper::getSearchConditonBounded($companyId, $branchId, $departmentId, $positionId, $designationId, $serviceTypeId, $serviceEventTypeId, $employeeTypeId, $employeeId);
        $boundedParams = array_merge($boundedParams, $searchCondition['parameter']);
        
//        $companyCondition = "";
//        $branchCondition = "";
//        $departmentCondition = "";
//        $designationCondition = "";
//        $positionCondition = "";
//        $serviceTypeCondition = "";
//        $serviceEventTypeConditon = "";
//        $employeeCondition = "";
//        $employeeTypeCondition = "";

//        if (isset($data['companyId']) && $data['companyId'] != null && $data['companyId'] != -1) {
//            $companyCondition = "AND E.COMPANY_ID = {$data['companyId']}";
//        }
//        if (isset($data['branchId']) && $data['branchId'] != null && $data['branchId'] != -1) {
//            $branchCondition = "AND E.BRANCH_ID = {$data['branchId']}";
//        }
//        if (isset($data['departmentId']) && $data['departmentId'] != null && $data['departmentId'] != -1) {
//            $departmentCondition = "AND E.DEPARTMENT_ID in (SELECT DEPARTMENT_ID FROM
//                         HRIS_DEPARTMENTS 
//                        START WITH PARENT_DEPARTMENT in ({$data['departmentId']})
//                        CONNECT BY PARENT_DEPARTMENT= PRIOR DEPARTMENT_ID
//                        UNION 
//                        SELECT DEPARTMENT_ID FROM HRIS_DEPARTMENTS WHERE DEPARTMENT_ID IN ({$data['departmentId']})
//                        UNION
//                        SELECT  TO_NUMBER(TRIM(REGEXP_SUBSTR(EXCEPTIONAL,'[^,]+', 1, LEVEL) )) DEPARTMENT_ID
//  FROM (SELECT EXCEPTIONAL  FROM  HRIS_DEPARTMENTS WHERE DEPARTMENT_ID IN  ({$data['departmentId']}))
//   CONNECT BY  REGEXP_SUBSTR(EXCEPTIONAL, '[^,]+', 1, LEVEL) IS NOT NULL
//                        )";
//        }
//        if (isset($data['designationId']) && $data['designationId'] != null && $data['designationId'] != -1) {
//            $designationCondition = "AND E.DESIGNATION_ID = {$data['designationId']}";
//        }
//        if (isset($data['positionId']) && $data['positionId'] != null && $data['positionId'] != -1) {
//            $positionCondition = "AND E.POSITION_ID = {$data['positionId']}";
//        }
//        if (isset($data['serviceTypeId']) && $data['serviceTypeId'] != null && $data['serviceTypeId'] != -1) {
//            $serviceTypeCondition = "AND E.SERVICE_TYPE_ID = {$data['serviceTypeId']}";
//        }
//        if (isset($data['serviceEventTypeId']) && $data['serviceEventTypeId'] != null && $data['serviceEventTypeId'] != -1) {
//            $serviceEventTypeConditon = "AND E.SERVICE_EVENT_TYPE_ID = {$data['serviceEventTypeId']}";
//        }
//        if (isset($data['employeeId']) && $data['employeeId'] != null && $data['employeeId'] != -1) {
//            $employeeCondition = "AND E.EMPLOYEE_ID = {$data['employeeId']}";
//        }
//        if (isset($data['employeeTypeId']) && $data['employeeTypeId'] != null && $data['employeeTypeId'] != -1) {
//            $employeeTypeCondition = "AND E.EMPLOYEE_TYPE = '{$data['employeeTypeId']}'";
//        }
//        $condition = $companyCondition . $branchCondition . $departmentCondition . $designationCondition . $positionCondition . $serviceTypeCondition . $serviceEventTypeConditon . $employeeCondition . $employeeTypeCondition;
        $sql = <<<EOT
                SELECT C.COMPANY_NAME,
                  E.EMPLOYEE_CODE AS EMPLOYEE_CODE,
                  B.BRANCH_NAME,
                  DEP.DEPARTMENT_NAME,
                  P.POSITION_NAME,
                  DES.DESIGNATION_TITLE,
                  E.FULL_NAME,
                  S.SHIFT_ENAME,
                  TO_CHAR(SA.START_DATE,'DD-MON-YYYY') AS FROM_DATE_AD,
                  BS_DATE(SA.START_DATE)               AS FROM_DATE_BS,
                  TO_CHAR(SA.END_DATE,'DD-MON-YYYY')   AS TO_DATE_AD,
                  BS_DATE(SA.END_DATE)                 AS TO_DATE_BS,
                  SA.EMPLOYEE_ID ,
                  SA.SHIFT_ID,
                  SA.CREATED_DT,
                  SA.MODIFIED_DT,
                  SA.ID
                FROM HRIS_EMPLOYEE_SHIFT_ASSIGN SA
                JOIN HRIS_EMPLOYEES E
                ON (SA.EMPLOYEE_ID=E.EMPLOYEE_ID)
                LEFT JOIN HRIS_COMPANY C
                ON (E.COMPANY_ID=C.COMPANY_ID)
                LEFT JOIN HRIS_BRANCHES B
                ON (E.BRANCH_ID=B.BRANCH_ID)
                LEFT JOIN HRIS_DEPARTMENTS DEP
                ON (E.DEPARTMENT_ID=DEP.DEPARTMENT_ID)
                LEFT JOIN HRIS_POSITIONS P
                ON (E.POSITION_ID=P.POSITION_ID)
                LEFT JOIN HRIS_DESIGNATIONS DES
                ON (E.DESIGNATION_ID=DES.DESIGNATION_ID)
                LEFT JOIN HRIS_SHIFTS S
                ON (SA.SHIFT_ID=S.SHIFT_ID)
                WHERE 1        =1
                {$searchCondition['sql']}
                ORDER BY E.FULL_NAME,
                  SA.START_DATE
EOT;

        $statement = $this->adapter->query($sql);
        $result = $statement->execute($boundedParams);
        return Helper::extractDbData($result);
    }

    public function bulkEdit($id, $shiftId, $fromDate, $toDate, $createdBy) {
        $boundedParams = [];
        $sql = <<<EOT
                BEGIN
                  HRIS_SHIFT_EDIT(:id,:shiftId,:fromDate,:toDate,:createdBy);
                END;
EOT;
        $boundedParams['id'] = $id;
        $boundedParams['shiftId'] = $shiftId;
        $boundedParams['fromDate'] = $fromDate;
        $boundedParams['toDate'] = $toDate;
        $boundedParams['createdBy'] = $createdBy;
        $statement = $this->adapter->query($sql);
        // echo '<pre>';print_r($boundedParams);die;
        $statement->execute($boundedParams);
    }

    public function bulkDelete($id) {
        $boundedParams = [];
        $sql = <<<EOT
                BEGIN
                  HRIS_SHIFT_DELETE(:id);
                END;
EOT;
        $boundedParams['id'] = $id;
        $statement = $this->adapter->query($sql);
        $statement->execute($boundedParams);
    }

    public function bulkAdd($employeeId, $shiftId, $fromDate, $toDate, $createdBy) {
         $boundedParams = [];
        $sql = <<<EOT
                BEGIN
                  HRIS_SHIFT_ADD(:employeeId,:shiftId,:fromDate,:toDate,:createdBy);
                END;
EOT;
        $boundedParams['employeeId'] = $employeeId;
        $boundedParams['shiftId'] = $shiftId;
        $boundedParams['fromDate'] = $fromDate;
        $boundedParams['toDate'] = $toDate;
        $boundedParams['createdBy'] = $createdBy;

        $statement = $this->adapter->query($sql);
        $statement->execute($boundedParams);
    }

    public function fetchEmployeeList($data) {
        
        $employeeId = $data['employeeId'];
        $companyId = $data['companyId'];
        $branchId = $data['branchId'];
        $departmentId = $data['departmentId'];
        $designationId = $data['designationId'];
        $positionId = $data['positionId'];
        $serviceTypeId = $data['serviceTypeId'];
        $serviceEventTypeId = $data['serviceEventTypeId'];
        $employeeTypeId = $data['employeeTypeId'];

        $boundedParams = [];
        $searchCondition = EntityHelper::getSearchConditonBounded($companyId, $branchId, $departmentId, $positionId, $designationId, $serviceTypeId, $serviceEventTypeId, $employeeTypeId, $employeeId);
        $boundedParams = array_merge($boundedParams, $searchCondition['parameter']);

//        $companyCondition = "";
//        $branchCondition = "";
//        $departmentCondition = "";
//        $designationCondition = "";
//        $positionCondition = "";
//        $serviceTypeCondition = "";
//        $serviceEventTypeConditon = "";
//        $employeeCondition = "";
//        $employeeTypeCondition = "";
//
//        if (isset($data['companyId']) && $data['companyId'] != null && $data['companyId'] != -1) {
//            $companyCondition = "AND E.COMPANY_ID = {$data['companyId']}";
//        }
//        if (isset($data['branchId']) && $data['branchId'] != null && $data['branchId'] != -1) {
//            $branchCondition = "AND E.BRANCH_ID = {$data['branchId']}";
//        }
//        if (isset($data['departmentId']) && $data['departmentId'] != null && $data['departmentId'] != -1) {
//            $departmentCondition = "AND E.DEPARTMENT_ID = {$data['departmentId']}";
//        }
//        if (isset($data['designationId']) && $data['designationId'] != null && $data['designationId'] != -1) {
//            $designationCondition = "AND E.DESIGNATION_ID = {$data['designationId']}";
//        }
//        if (isset($data['positionId']) && $data['positionId'] != null && $data['positionId'] != -1) {
//            $positionCondition = "AND E.POSITION_ID = {$data['positionId']}";
//        }
//        if (isset($data['serviceTypeId']) && $data['serviceTypeId'] != null && $data['serviceTypeId'] != -1) {
//            $serviceTypeCondition = "AND E.SERVICE_TYPE_ID = {$data['serviceTypeId']}";
//        }
//        if (isset($data['serviceEventTypeId']) && $data['serviceEventTypeId'] != null && $data['serviceEventTypeId'] != -1) {
//            $serviceEventTypeConditon = "AND E.SERVICE_EVENT_TYPE_ID = {$data['serviceEventTypeId']}";
//        }
//        if (isset($data['employeeId']) && $data['employeeId'] != null && $data['employeeId'] != -1) {
//            $employeeCondition = "AND E.EMPLOYEE_ID = {$data['employeeId']}";
//        }
//        if (isset($data['employeeTypeId']) && $data['employeeTypeId'] != null && $data['employeeTypeId'] != -1) {
//            $employeeTypeCondition = "AND E.EMPLOYEE_TYPE = '{$data['employeeTypeId']}'";
//        }
//        $condition = $companyCondition . $branchCondition . $departmentCondition . $designationCondition . $positionCondition . $serviceTypeCondition . $serviceEventTypeConditon . $employeeCondition . $employeeTypeCondition;
        $sql = <<<EOT
                SELECT C.COMPANY_NAME,
                  B.BRANCH_NAME,
                  DEP.DEPARTMENT_NAME,
                  P.POSITION_NAME,
                  DES.DESIGNATION_TITLE,
                  ST.SERVICE_TYPE_NAME,
                  (
                  CASE
                    WHEN E.EMPLOYEE_TYPE='R'
                    THEN 'Employee'
                    ELSE 'Worker'
                  END) AS EMPLOYEE_TYPE,
                  E.FULL_NAME,
                  E.EMPLOYEE_ID
                FROM HRIS_EMPLOYEES E
                LEFT JOIN HRIS_COMPANY C
                ON (E.COMPANY_ID=C.COMPANY_ID)
                LEFT JOIN HRIS_BRANCHES B
                ON (E.BRANCH_ID=B.BRANCH_ID)
                LEFT JOIN HRIS_DEPARTMENTS DEP
                ON (E.DEPARTMENT_ID=DEP.DEPARTMENT_ID)
                LEFT JOIN HRIS_POSITIONS P
                ON (E.POSITION_ID=P.POSITION_ID)
                LEFT JOIN HRIS_DESIGNATIONS DES
                ON (E.DESIGNATION_ID=DES.DESIGNATION_ID)
                LEFT JOIN HRIS_SERVICE_TYPES ST
                ON (ST.SERVICE_TYPE_ID=E.SERVICE_TYPE_ID)
                WHERE 1               =1 AND E.STATUS='E' 
                {$searchCondition['sql']}
                ORDER BY E.FULL_NAME               
EOT;

        $statement = $this->adapter->query($sql);
        $result = $statement->execute($boundedParams);
        return Helper::extractDbData($result);
    }

    public function fetchEmployeeShifts($employeeId) {
         $boundedParams = [];
        $sql = <<<EOT
                SELECT S.SHIFT_ENAME,
                  TO_CHAR(SA.START_DATE,'DD-MON-YYYY') AS FROM_DATE_AD,
                  BS_DATE(SA.START_DATE)               AS FROM_DATE_BS,
                  TO_CHAR(SA.END_DATE,'DD-MON-YYYY')   AS TO_DATE_AD,
                  BS_DATE(SA.END_DATE)                 AS TO_DATE_BS
                FROM HRIS_EMPLOYEE_SHIFT_ASSIGN SA
                LEFT JOIN HRIS_SHIFTS S
                ON SA.SHIFT_ID       = S.SHIFT_ID
                WHERE SA.EMPLOYEE_ID = :employeeId
                ORDER BY SA.START_DATE ASC,
                  SA.END_DATE DESC       
EOT;
        $boundedParams['employeeId'] = $employeeId;

        $statement = $this->adapter->query($sql);
        $result = $statement->execute($boundedParams);
        return Helper::extractDbData($result);
    }

}
