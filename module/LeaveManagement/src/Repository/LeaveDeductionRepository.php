<?php

/**
 * Created by PhpStorm.
 * User: shijan
 * Date: 2/5/20
 * Time: 10:53 AM
 */

namespace LeaveManagement\Repository;

use Application\Helper\EntityHelper;
use Application\Helper\Helper;
use Application\Model\Model;
use Application\Repository\HrisRepository;
use Application\Repository\RepositoryInterface;
use Zend\Db\Adapter\AdapterInterface;
use Zend\Db\TableGateway\TableGateway;
use LeaveManagement\Model\LeaveDeduction;

class LeaveDeductionRepository  extends HrisRepository implements RepositoryInterface {

    protected $gateway;

    public function __construct(AdapterInterface $adapter) {
        parent::__construct($adapter);
        $this->gateway = new TableGateway(LeaveDeduction::TABLE_NAME, $adapter);
    }

    public function add(Model $model) {
        $this->gateway->insert($model->getArrayCopyForDB());
    }

    public function delete($id) {

    }

    public function edit(Model $model, $id) {
        $temp = $model->getArrayCopyForDB();
        $this->gateway->update($temp, [LeaveDeduction::ID => $id]);
    }

    public function fetchAll() {

    }

    public function fetchById($id) {

        $sql = "SELECT 
              LD.ID             AS ID,
              LD.EMPLOYEE_ID    AS EMPLOYEE_ID,
              LD.DEDUCTION_DT   AS DEDUCTION_DT,
              LD.NO_OF_DAYS     AS NO_OF_DAYS,
              LD.STATUS         AS STATUS,
              LMS.LEAVE_ENAME   AS LEAVE_ENAME
            FROM HRIS_EMPLOYEE_LEAVE_DEDUCTION LD
            LEFT JOIN HRIS_LEAVE_MASTER_SETUP LMS
            ON (LD.LEAVE_ID = LMS.LEAVE_ID)
            WHERE LD.ID     = :id";

            $boundedParameter = [];
            $boundedParameter['id'] = $id;

            return $this->rawQuery($sql, $boundedParameter)[0];
        // $statement = $this->adapter->query($sql);
        // $result = $statement->execute();
        // return $result->current();
    }

    public function fetchLeaveDeductionList($data) {
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

        $fromDate = $data['fromDate'];
        $toDate = $data['toDate'];
        $leaveId = $data['leaveId'];
        $leaveYear = $data['leaveYear'];

        $leaveCondition = "";
        if ($leaveId != null && $leaveId != '') {
            $leaveId = implode($leaveId, ',');
            $leaveCondition .= " AND L.LEAVE_ID IN ($leaveId) ";
        }
        if ($leaveYear != null) {
            $leaveYearStatusCondition = "( ( L.STATUS ='E' OR L.OLD_LEAVE='Y' ) AND L.LEAVE_YEAR= {$leaveYear} )";
        } else {
            $leaveYearStatusCondition = "L.STATUS ='E'";
        }

        $searchCondition = EntityHelper::getSearchConditonBounded($companyId, $branchId, $departmentId, $positionId, $designationId, $serviceTypeId, $serviceEventTypeId, $employeeTypeId, $employeeId);

        $boundedParameter = [];
        $boundedParameter=array_merge($boundedParameter, $searchCondition['parameter']);

        $fromDateCondition = "";
        $toDateCondition = "";
        
        if ($fromDate != null) {
            $fromDateCondition = " AND LA.START_DATE>=TO_DATE(:fromDate,'DD-MM-YYYY')";
            $boundedParameter['fromDate'] = $fromDate;
        }

        if ($toDate != null) {
            $toDateCondition = " AND LA.END_DATE<=TO_DATE(:toDate,'DD-MM-YYYY')";
            $boundedParameter['toDate'] = $toDate;
        }

        $sql = "SELECT FUNT.FUNCTIONAL_TYPE_EDESC AS FUNCTIONAL_TYPE_EDESC,
          L.LEAVE_ENAME AS LEAVE_ENAME,
          L.LEAVE_CODE,
          LD.NO_OF_DAYS,
          INITCAP(TO_CHAR(LD.DEDUCTION_DT, 'DD-MON-YYYY'))   AS DEDUCTION_DT_AD,
          BS_DATE(TO_CHAR(LD.DEDUCTION_DT, 'DD-MON-YYYY'))   AS DEDUCTION_DT_BS,
          LEAVE_STATUS_DESC(LD.STATUS)                       AS STATUS,
          LD.ID                                              AS ID,
          LD.EMPLOYEE_ID                                     AS EMPLOYEE_ID,
          E.EMPLOYEE_CODE                                    AS EMPLOYEE_CODE,
          INITCAP(E.FULL_NAME)                               AS FULL_NAME,
          LD.MODIFIED_DT                                     AS MODIFIED_DT,
          LD.REMARKS                                         AS REMARKS
        FROM HRIS_EMPLOYEE_LEAVE_DEDUCTION LD
        LEFT OUTER JOIN HRIS_LEAVE_MASTER_SETUP L
        ON L.LEAVE_ID=LD.LEAVE_ID
        LEFT OUTER JOIN HRIS_EMPLOYEES E
        ON E.EMPLOYEE_ID=LD.EMPLOYEE_ID
        LEFT JOIN HRIS_FUNCTIONAL_TYPES FUNT
        ON E.FUNCTIONAL_TYPE_ID=FUNT.FUNCTIONAL_TYPE_ID
        WHERE {$leaveYearStatusCondition} {$leaveCondition}
        AND E.STATUS           ='E'
               {$searchCondition['sql']} {$fromDateCondition} {$toDateCondition}
                ORDER BY LD.MODIFIED_DT DESC";

        return $this->rawQuery($sql, $boundedParameter);
               
        // $statement = $this->adapter->query($sql);
        // $result = $statement->execute();
        // return Helper::extractDbData($result);
    }

}