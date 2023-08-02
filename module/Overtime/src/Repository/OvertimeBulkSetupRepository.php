<?php

namespace Overtime\Repository;

use Application\Helper\EntityHelper;
use Overtime\Model\CompulsoryOvertime;
use Zend\Db\Adapter\AdapterInterface;
use Zend\Db\Sql\Select;
use Zend\Db\Sql\Sql;
use Application\Repository\HrisRepository;
use Zend\Db\TableGateway\TableGateway;

class OvertimeBulkSetupRepository extends HrisRepository
{

    protected $adapter;
    protected $tableGateway;

    public function __construct(AdapterInterface $adapter)
    {
        $this->adapter = $adapter;
    }

    public function getEmployeeList($data)
    {

        $condition = EntityHelper::getSearchConditonBounded($data['companyId'], $data['branchId'], $data['departmentId'], $data['positionId'], $data['designationId'], $data['serviceTypeId'], $data['serviceEventTypeId'], $data['employeeTypeId'], $data['employeeId']);
        $boundedParameter = [];
        $boundedParameter=array_merge($boundedParameter, $condition['parameter']);
        $wohCondition = '';

        if ($data['wohReward'] != null) {
            if ($data['wohReward'] == 'O') {
                $wohCondition = "AND E.WOH_FLAG = 'O' ";
            } elseif ($data['wohReward'] == 'L') {
                $wohCondition = "AND E.WOH_FLAG = 'L' ";
            }
        }
        $sql = "SELECT 
                  E.FULL_NAME,
                  E.OVERTIME_ELIGIBLE,
                  case when E.OVERTIME_ELIGIBLE IN 'Y' then 'Y'
                  else 'N' END AS ASSIGNED,
                  C.COMPANY_NAME,
                  B.BRANCH_NAME,
                  DEP.DEPARTMENT_NAME,
                  E.EMPLOYEE_ID,
                  E.EMPLOYEE_CODE,
                  P.POSITION_NAME,
                  DES.DESIGNATION_TITLE,
                  E.WOH_FLAG,
                  case when E.WOH_FLAG in ('O','L') 
                  then 
                  (case when E.WOH_FLAG ='O' 
                  then 'Overtime' 
                  else 'Substitute Leave' 
                  END)
                  ELSE '-' END as WOH_REWARD
                FROM HRIS_EMPLOYEES E
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
                {$condition['sql']} {$wohCondition}
                ORDER BY E.FULL_NAME";

                return $this->rawQuery($sql, $boundedParameter);
        // $statement = $this->adapter->query($sql);
        // $result = $statement->execute();
        // return $result;
    }

    public function updateOvertime($employeeId, $updateData)
    {
        $sql = '';

        if ($updateData['updateValue'] == 'O') {
            $sql = "UPDATE HRIS_EMPLOYEES set OVERTIME_ELIGIBLE = '{$updateData['overtimeEligible']}' where EMPLOYEE_ID = {$employeeId} ";
        } elseif ($updateData['updateValue'] == 'W') {
            $sql = "UPDATE HRIS_EMPLOYEES set WOH_FLAG = '{$updateData['wohFlag']}' where EMPLOYEE_ID = {$employeeId} ";
        }

        $statement = $this->adapter->query($sql);
        $result = $statement->execute();
        return;
    }

    public function makeNull($employeeId)
    {
        $sql = "UPDATE HRIS_EMPLOYEES set OVERTIME_ELIGIBLE = NULL, WOH_FLAG = NULL where EMPLOYEE_ID = {$employeeId}";

        $statement = $this->adapter->query($sql);
        $result = $statement->execute();
        return;
    }

}
