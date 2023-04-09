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
    
    public function fetchOneByWithEmpDetailsNew($monthId,$employeeId,$salaryTypeId){
        $sql="SELECT 
        SSD.*
        ,E.ID_PROVIDENT_FUND_NO
        ,E.ID_PAN_NO
        ,E.ID_RETIREMENT_NO
        ,E.ID_ACCOUNT_NO
        ,sd.val as USE_PRESENT
        ,fd.flat_value as Allowance
        ,ssd.total_days - sd.val as USE_ABSENT
        FROM HRIS_SALARY_SHEET_EMP_DETAIL SSD
        LEFT JOIN HRIS_EMPLOYEES E ON SSD.EMPLOYEE_ID=E.EMPLOYEE_ID
        left join hris_salary_sheet ss on (ssd.sheet_no = ss.sheet_no and approved='Y')
        left join hris_salary_sheet_detail sd on (ssd.employee_id = sd.employee_id and sd.sheet_no = ssd.sheet_no)
        left join hris_flat_value_detail fd on (fd.employee_id=ssd.employee_id and fd.flat_id=2 and fiscal_year_id in(select fiscal_year_id  from hris_month_code where month_id=:monthId))
        WHERE
        SS.MONTH_ID=:monthId AND SSD.EMPLOYEE_ID=:employeeId and
        sd.pay_id = 3 
        and ss.salary_type_id =:salaryTypeId";
        $boundedParameter = [];
        $boundedParameter['monthId'] = $monthId;
        $boundedParameter['employeeId'] = $employeeId;
        $boundedParameter['salaryTypeId'] = $salaryTypeId;
        $statement = $this->adapter->query($sql);
        // echo '<pre>';print_r($sql);die;
        $result=$statement->execute($boundedParameter);
        return $result->current();
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
