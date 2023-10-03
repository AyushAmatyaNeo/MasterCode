<?php

namespace LeaveManagement\Repository;

use Application\Helper\EntityHelper;
use Application\Model\Model;
use Application\Repository\HrisRepository;
use LeaveManagement\Model\LeaveAssign;
use Zend\Db\Adapter\AdapterInterface;
use Zend\Db\Sql\Sql;
use Setup\Repository\BranchRepository;
use Setup\Model\Logs;
use Zend\Db\TableGateway\TableGateway;


class LeaveAssignRepository extends HrisRepository
{
    private $logTable;

    public function __construct(AdapterInterface $adapter, $tableName = null)
    {
        if ($tableName == null) {
            $tableName = LeaveAssign::TABLE_NAME;
            $this->logTable = new TableGateway(Logs::TABLE_NAME, $adapter);
        }
        parent::__construct($adapter, $tableName);
    }

    public function add(Model $model)
    {
        $array = $model->getArrayCopyForDB();
        $this->tableGateway->insert($model->getArrayCopyForDB());
        $branch = new BranchRepository($this->adapter);
        $logs = new Logs();
        $logs->module = 'Leave Assign';
        $logs->operation = 'A';
        $logs->createdBy = $array['CREATED_BY'];
        $logs->createdDesc = 'leave id - ' . $array['LEAVE_ID'] . ',' . 'Employee id-' . $array['EMPLOYEE_ID'];
        $logs->tableDesc = 'HRIS_EMPLOYEE_LEAVE_ASSIGN';
        $branch->insertLogs($logs);
    }

    public function edit(Model $model, $id)
    {
        $array = $model->getArrayCopyForDB();
        $this->tableGateway->update($model->getArrayCopyForDB(), [LeaveAssign::LEAVE_ID => $id[0], LeaveAssign::EMPLOYEE_ID => $id[1]]);

        $boundedParameter = [];
        $boundedParameter['leaveId'] = $id[0];
        $boundedParameter['employeeId'] = $id[1];
        $sql = "BEGIN
              HRIS_RECALCULATE_LEAVE(:employeeId,:leaveId);
            END;";
        $this->executeStatement($sql, $boundedParameter);
        $branch = new BranchRepository($this->adapter);
        $logs = new Logs();
        $logs->module = 'Leave Assign';
        $logs->operation = 'U';
        $logs->modifiedBy = $array['MODIFIED_BY'];
        $logs->modifiedDesc = 'leave id - ' . $id[0] . ',' . 'Employee id-' . $id[1];
        $logs->tableDesc = 'HRIS_EMPLOYEE_LEAVE_ASSIGN';

        $branch->updateLogs($logs);
    }

    public function fetchAll()
    {
        return $this->tableGateway->select();
    }

    public function fetchByEmployeeId($id)
    {
        $sql = new Sql($this->adapter);
        $select = $sql->select();
        $select->from(['A' => LeaveAssign::TABLE_NAME])
            ->join(['S' => 'HRIS_LEAVE_MASTER_SETUP'], 'A.LEAVE_ID=S.LEAVE_ID');
        $statement = $sql->prepareStatementForSqlObject($select);
        $result = $statement->execute();
        return $result;
    }

    public function filter($branchId, $departmentId, $genderId, $designationId, $serviceTypeId, $employeeId, $companyId, $positionId, $employeeTypeId, $leaveId): array
    {

        $searchCondition = EntityHelper::getSearchConditonBounded($companyId, $branchId, $departmentId, $positionId, $designationId, $serviceTypeId, null, $employeeTypeId, $employeeId, $genderId);

        $boundedParameter = [];
        $boundedParameter = array_merge($boundedParameter, $searchCondition['parameter']);

        $orderByString = EntityHelper::getOrderBy('E.FULL_NAME ASC', null, 'E.SENIORITY_LEVEL', 'P.LEVEL_NO', 'E.JOIN_DATE', 'DES.ORDER_NO', 'E.FULL_NAME');

        $sql = "SELECT C.COMPANY_NAME,
                  B.BRANCH_NAME,
                  DEP.DEPARTMENT_NAME,
                  E.EMPLOYEE_ID,
                  E.EMPLOYEE_CODE,
                  E.FULL_NAME,
                  ELA.LEAVE_ID,
                  ELA.PREVIOUS_YEAR_BAL,
                  ELA.TOTAL_DAYS,
                  ELA.BALANCE,
                  LS.IS_MONTHLY,
                  MC.MONTH_EDESC
                FROM HRIS_EMPLOYEES E
                LEFT JOIN (SELECT * FROM HRIS_EMPLOYEE_LEAVE_ASSIGN WHERE LEAVE_ID   = :leaveId) ELA
                ON (E.EMPLOYEE_ID = ELA.EMPLOYEE_ID)
                LEFT JOIN HRIS_POSITIONS P
                ON E.POSITION_ID=P.POSITION_ID
                LEFT JOIN HRIS_LEAVE_MASTER_SETUP LS
                ON(LS.LEAVE_ID= :leaveId)
                LEFT JOIN HRIS_DESIGNATIONS DES
                ON E.DESIGNATION_ID=DES.DESIGNATION_ID
               LEFT JOIN HRIS_LEAVE_MONTH_CODE MC ON
               (MC.LEAVE_YEAR_MONTH_NO=ELA.FISCAL_YEAR_MONTH_NO AND LS.LEAVE_YEAR=MC.LEAVE_YEAR_ID)
                LEFT JOIN HRIS_COMPANY C
                ON (E.COMPANY_ID=C.COMPANY_ID)
                LEFT JOIN HRIS_BRANCHES B
                ON (E.BRANCH_ID=B.BRANCH_ID)
                LEFT JOIN HRIS_DEPARTMENTS DEP
                ON (E.DEPARTMENT_ID=DEP.DEPARTMENT_ID)
                WHERE 1            =1 AND E.STATUS='E'
                {$searchCondition['sql']}
                    AND (CASE 
           WHEN ELA.FISCAL_YEAR_MONTH_NO IS NOT NULL THEN 
         (SELECT LEAVE_YEAR_MONTH_NO FROM HRIS_LEAVE_MONTH_CODE WHERE 
         (select 
            case when trunc(sysdate)>max(to_date) then
            max(to_date)
            else 
            trunc(sysdate)
            end
            from HRIS_LEAVE_MONTH_CODE) 
            BETWEEN FROM_DATE AND TO_DATE)
           END=ELA.FISCAL_YEAR_MONTH_NO 
          OR 
           CASE 
           WHEN ELA.FISCAL_YEAR_MONTH_NO IS  NULL THEN 
         1
         ELSE
         2
           END=1
          )
                {$orderByString}";
        $boundedParameter['leaveId'] = $leaveId;
        return $this->rawQuery($sql, $boundedParameter);
    }

    public function filterByLeaveEmployeeId($leaveId, $employeeId)
    {
        $result = $this->tableGateway->select([LeaveAssign::LEAVE_ID => $leaveId, LeaveAssign::EMPLOYEE_ID => $employeeId]);
        return $result->current();
    }

    public function fetchById($id)
    {
        $rowset = $this->tableGateway->select([LeaveAssign::EMPLOYEE_LEAVE_ASSIGN_ID => $id]);
        return $rowset->current();
    }

    public function delete($id)
    {
        $this->tableGateway->delete([LeaveAssign::EMPLOYEE_LEAVE_ASSIGN_ID => $id]);
    }

    public function updatePreYrBalance($employeeId, $leaveId, $preYrBalance, $totalDays, $balance)
    {
        $this->tableGateway->update([LeaveAssign::PREVIOUS_YEAR_BAL => $preYrBalance, LeaveAssign::TOTAL_DAYS => $totalDays, LeaveAssign::BALANCE => $balance], [LeaveAssign::EMPLOYEE_ID => $employeeId, LeaveAssign::LEAVE_ID => $leaveId]);
    }



    public function editMonthlyLeave($employeeId, $leaveDetails, $monthId, $totalDays = null, $previousBalance = null, $createdBy)
    {
        $monthlyDays = ($totalDays != null) ? $totalDays : $leaveDetails['DEFAULT_DAYS'];
        $sql = "DECLARE
            V_DEFAULT_LEAVE_DAYS NUMBER:={$monthlyDays};
            V_LEAVE_ID NUMBER:={$leaveDetails['LEAVE_ID']};
            V_MONTH_ID NUMBER:={$monthId};
            V_CREATED_BY NUMBER:={$createdBy};
     V_COUNT NUMBER;
     V_EMPLOYEE_ID NUMBER:={$employeeId};
         V_MONTH_COUNT NUMBER:=1;
         V_CARRY_FORWARD CHAR(1 BYTE):='{$leaveDetails['CARRY_FORWARD']}';
         V_PREVIOUS_YEAR_BAL NUMBER:={$previousBalance};
    BEGIN
    
            IF(V_PREVIOUS_YEAR_BAL IS NULL)
            THEN
            V_PREVIOUS_YEAR_BAL:=0;
            END IF;
    
        DELETE  FROM HRIS_EMPLOYEE_LEAVE_ASSIGN WHERE 
        EMPLOYEE_ID=V_EMPLOYEE_ID 
        AND LEAVE_ID=V_LEAVE_ID;

    FOR i IN V_MONTH_ID..12
            LOOP
              
                INSERT
                INTO HRIS_EMPLOYEE_LEAVE_ASSIGN
                  (
                    EMPLOYEE_ID,
                    LEAVE_ID,
                    PREVIOUS_YEAR_BAL,
                    TOTAL_DAYS,
                    BALANCE,
                    FISCAL_YEAR_MONTH_NO,
                    CREATED_DT,
                    CREATED_BY
                  )
                  VALUES
                  (
                    V_EMPLOYEE_ID,
                    V_LEAVE_ID,
                    V_PREVIOUS_YEAR_BAL,
                    (V_DEFAULT_LEAVE_DAYS*V_MONTH_COUNT)+V_PREVIOUS_YEAR_BAL,
                    (V_DEFAULT_LEAVE_DAYS*V_MONTH_COUNT)+V_PREVIOUS_YEAR_BAL,
                    i,
                    TRUNC(SYSDATE),
                    V_CREATED_BY
                  );
              
              IF(V_CARRY_FORWARD='Y') THEN
              V_MONTH_COUNT:=V_MONTH_COUNT+1;
              END IF;
              
            END LOOP;
            
         BEGIN
         HRIS_RECALC_MONTHLY_LEAVES(V_EMPLOYEE_ID,V_LEAVE_ID);
        END;
        END;";
        $this->executeStatement($sql);

        $branch = new BranchRepository($this->adapter);
        $logs = new Logs();
        $logs->module = 'Leave Assign';
        $logs->operation = 'U';
        $logs->modifiedBy = $createdBy;
        $logs->modifiedDesc = 'leave id - ' . $leaveDetails['LEAVE_ID'] . ',' . 'Employee id-' . $employeeId;
        $logs->tableDesc = 'HRIS_EMPLOYEE_LEAVE_ASSIGN';
        $branch->updateLogs($logs);
    }
}
