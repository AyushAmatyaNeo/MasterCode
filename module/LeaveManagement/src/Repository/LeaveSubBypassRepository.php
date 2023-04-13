<?php

namespace LeaveManagement\Repository;

use Application\Helper\EntityHelper;
use Application\Model\Model;
use Application\Repository\HrisRepository;
use LeaveManagement\Model\LeaveSubManBypass;
use Zend\Db\Adapter\AdapterInterface;

class LeaveSubBypassRepository extends HrisRepository {
    
    
    public function __construct(AdapterInterface $adapter) {
         parent::__construct($adapter, 'HRIS_SUB_MAN_BYPASS');
    }

    public function getEmployeeList($data) {


        $condition = EntityHelper::getSearchConditonBounded($data['companyId'], $data['branchId'], $data['departmentId'], $data['positionId'], $data['designationId'], $data['serviceTypeId'], $data['serviceEventTypeId'], $data['employeeTypeId'], $data['employeeId']);

        $boundedParameter = [];
        $boundedParameter=array_merge($boundedParameter, $condition['parameter']);

        $orderByString = EntityHelper::getOrderBy('E.FULL_NAME ASC', null, 'E.SENIORITY_LEVEL', 'P.LEVEL_NO', 'E.JOIN_DATE', 'DES.ORDER_NO', 'E.FULL_NAME');

        $sql = "SELECT 
                  E.FULL_NAME,
                  ELA.LEAVE_ID ,
                  case when ELA.LEAVE_ID is  null then 'N'
                  else 'Y' END AS ASSIGNED,
                  C.COMPANY_NAME,
                  B.BRANCH_NAME,
                  DEP.DEPARTMENT_NAME,
                  E.EMPLOYEE_ID,
                  E.EMPLOYEE_CODE,
                  P.POSITION_NAME,
                  DES.DESIGNATION_TITLE
                FROM HRIS_EMPLOYEES E
                LEFT JOIN (select * from HRIS_SUB_MAN_BYPASS where leave_id=   {$data['leave']}) ELA
                ON (E.EMPLOYEE_ID = ELA.EMPLOYEE_ID)
                LEFT JOIN HRIS_COMPANY C
                ON (E.COMPANY_ID=C.COMPANY_ID)
                LEFT JOIN HRIS_BRANCHES B
                ON (E.BRANCH_ID=B.BRANCH_ID)
                LEFT JOIN HRIS_DEPARTMENTS DEP
                ON (E.DEPARTMENT_ID=DEP.DEPARTMENT_ID)
                LEFT JOIN HRIS_POSITIONS P 
                ON (P.POSITION_ID=E.POSITION_ID)
                LEFT JOIN HRIS_DESIGNATIONS DES
                ON (DES.DESIGNATION_ID=E.DESIGNATION_ID)
                WHERE 1            =1 
                 AND E.STATUS ='E'
                {$condition['sql']}
                {$orderByString}";

                return $this->rawQuery($sql, $boundedParameter);
        // $statement = $this->adapter->query($sql);
        // $result = $statement->execute();
        // return $result;
    }
    
    public function add(Model $model) {
        $this->tableGateway->insert($model->getArrayCopyForDB());
    }
    
    public function delete($employeeId,$leaveId){
        $this->tableGateway->delete([LeaveSubManBypass::EMPLOYEE_ID => $employeeId,LeaveSubManBypass::LEAVE_ID => $leaveId]);
    }
    

}
