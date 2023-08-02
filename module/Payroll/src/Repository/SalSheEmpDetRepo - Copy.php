<?php
namespace Payroll\Repository;

use Application\Repository\HrisRepository;
use Payroll\Model\SalarySheetEmpDetail;
use Zend\Db\Adapter\AdapterInterface;

class SalSheEmpDetRepo extends HrisRepository {

    public function __construct(AdapterInterface $adapter) {
        parent::__construct($adapter, SalarySheetEmpDetail::TABLE_NAME);
    }

    public function fetchOneBy($by) {
        return $this->tableGateway->select($by)->current();
    }
    
    public function fetchOneByWithEmpDetails($monthId,$employeeId){
        $sql="SELECT 
        SSD.*
        ,E.ID_PROVIDENT_FUND_NO
        ,E.ID_PAN_NO
        ,E.ID_RETIREMENT_NO
        FROM HRIS_SALARY_SHEET_EMP_DETAIL SSD
        LEFT JOIN HRIS_EMPLOYEES E ON SSD.EMPLOYEE_ID=E.EMPLOYEE_ID
        WHERE
        SSD.MONTH_ID=:monthId AND SSD.EMPLOYEE_ID=:employeeId";
        $boundedParameter = [];
        $boundedParameter['monthId'] = $monthId;
        $boundedParameter['employeeId'] = $employeeId;
        $statement = $this->adapter->query($sql);
        $result=$statement->execute($boundedParameter);
        return $result->current();
    }
    
    
}
