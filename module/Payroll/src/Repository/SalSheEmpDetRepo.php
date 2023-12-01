<?php

namespace Payroll\Repository;

use Application\Repository\HrisRepository;
use Payroll\Model\SalarySheetEmpDetail;
use Zend\Db\Adapter\AdapterInterface;

class SalSheEmpDetRepo extends HrisRepository
{

    public function __construct(AdapterInterface $adapter)
    {
        parent::__construct($adapter, SalarySheetEmpDetail::TABLE_NAME);
    }

    public function fetchOneBy($by)
    {
        return $this->tableGateway->select($by)->current();
    }

    public function fetchOneByWithEmpDetailsNew($monthId, $employeeId, $salaryTypeId)
    {
        $sql = "SELECT 
        SSD.*,
        HFY.FISCAL_YEAR_NAME,
        MC.MONTH_EDESC,
        E.ID_PROVIDENT_FUND_NO,
        E.ID_PAN_NO,
        E.ID_RETIREMENT_NO,
        E.ID_ACCOUNT_NO,
        SD.VAL AS USE_PRESENT,
        HEF.FILE_PATH,
        FD2.FLAT_VALUE AS ALLOWANCE,
        FD1.FLAT_VALUE AS MONTHLY_SALARY,
        SSD.TOTAL_DAYS - SD.VAL AS USE_ABSENT,
        HB.BANK_NAME,HC.ADDRESS,e.ssf_no,SSD.TOTAL_DAYS-SSD.ABSENT AS PAY_DAYS
    FROM HRIS_SALARY_SHEET_EMP_DETAIL SSD
    LEFT JOIN HRIS_EMPLOYEES E ON SSD.EMPLOYEE_ID = E.EMPLOYEE_ID
    LEFT JOIN HRIS_COMPANY HC ON SSD.COMPANY_ID=HC.COMPANY_ID
    LEFT JOIN HRIS_BANKS HB ON (HB.BANK_ID=E.BANK_ID OR E.BANK_ID IS NULL)
    LEFT JOIN HRIS_MONTH_CODE MC ON (MC.MONTH_ID=SSD.MONTH_ID )
    LEFT JOIN HRIS_FISCAL_YEARS HFY ON (MC.FISCAL_YEAR_ID=HFY.FISCAL_YEAR_ID)
    left join HRIS_EMPLOYEE_FILE HEF ON (HEF.FILE_CODE=HC.LOGO OR HC.LOGO IS NULL)
    LEFT JOIN HRIS_SALARY_SHEET SS ON (SSD.SHEET_NO = SS.SHEET_NO AND APPROVED = 'Y')
    LEFT JOIN HRIS_SALARY_SHEET_DETAIL SD ON (SSD.EMPLOYEE_ID = SD.EMPLOYEE_ID AND SD.SHEET_NO = SSD.SHEET_NO)
    LEFT JOIN HRIS_FLAT_VALUE_DETAIL FD1 ON (FD1.EMPLOYEE_ID = SSD.EMPLOYEE_ID AND FD1.FLAT_ID = 18 AND FD1.FISCAL_YEAR_ID IN (SELECT FISCAL_YEAR_ID FROM HRIS_MONTH_CODE WHERE MONTH_ID = :monthId))
    LEFT JOIN HRIS_FLAT_VALUE_DETAIL FD2 ON (FD2.EMPLOYEE_ID = SSD.EMPLOYEE_ID AND FD2.FLAT_ID = 2 AND FD2.FISCAL_YEAR_ID IN (SELECT FISCAL_YEAR_ID FROM HRIS_MONTH_CODE WHERE MONTH_ID = :monthId))
    WHERE SS.MONTH_ID = :monthId
        AND SSD.EMPLOYEE_ID = :employeeId 
        AND SD.PAY_ID = 3  
        AND SS.SALARY_TYPE_ID = :salaryTypeId 
    ";
        $boundedParameter = [];
        $boundedParameter['monthId'] = $monthId;
        $boundedParameter['employeeId'] = $employeeId;
        $boundedParameter['salaryTypeId'] = $salaryTypeId;
        $statement = $this->adapter->query($sql);
        $result = $statement->execute($boundedParameter);
        return $result->current();
    }

    public function fetchOneByWithEmpDetails($monthId, $employeeId)
    {
        $sql = "SELECT 
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
        $result = $statement->execute($boundedParameter);
        return $result->current();
    }
}
