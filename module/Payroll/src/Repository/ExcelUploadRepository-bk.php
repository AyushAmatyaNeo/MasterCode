<?php

namespace Payroll\Repository;

use Application\Helper\EntityHelper;
use Application\Model\Model;
use Zend\Db\Adapter\AdapterInterface;
use Application\Repository\HrisRepository;

class ExcelUploadRepository extends HrisRepository{

    protected $adapter;

    public function __construct(AdapterInterface $adapter) {
      $this->adapter = $adapter;
    }

    public function updateEmployeeSalary($id, $salary){
        $boundedParameter = [];
        $boundedParameter['id'] = $id;
        $boundedParameter['salary'] = $salary;
        $sql = "UPDATE HRIS_EMPLOYEES SET SALARY = :salary WHERE EMPLOYEE_ID = :id"; 

        $statement = $this->adapter->query($sql);
        $statement->execute($boundedParameter);
    }
    
    public function postPayValuesModifiedDetail($data) {
        $boundedParameter = [];
        $boundedParameter['monthId'] = $data['monthId'];
        $boundedParameter['employeeId'] = $data['employeeId'];
        $boundedParameter['payId'] = $data['payId'];
        $boundedParameter['val'] = $data['val'];
        $boundedParameter['salaryTypeId'] = $data['salaryTypeId'];
        $sql = "
                DECLARE
                  V_MONTH_ID HRIS_SS_PAY_VALUE_MODIFIED.MONTH_ID%TYPE := :monthId;
                  V_EMPLOYEE_ID HRIS_SS_PAY_VALUE_MODIFIED.EMPLOYEE_ID%TYPE := :employeeId;
                  V_PAY_ID HRIS_SS_PAY_VALUE_MODIFIED.PAY_ID%TYPE := :payId;
                  V_VAL HRIS_SS_PAY_VALUE_MODIFIED.VAL%TYPE := :val;
                  V_SALARY_TYPE_ID HRIS_SS_PAY_VALUE_MODIFIED.SALARY_TYPE_ID%TYPE := :salaryTypeId;
                BEGIN
                  SELECT VAL
                  INTO V_VAL
                  FROM HRIS_SS_PAY_VALUE_MODIFIED
                  WHERE MONTH_ID       = V_MONTH_ID
                  AND PAY_ID = V_PAY_ID
                  AND SALARY_TYPE_ID = V_SALARY_TYPE_ID
                  AND EMPLOYEE_ID    = V_EMPLOYEE_ID;
                  
                  UPDATE HRIS_SS_PAY_VALUE_MODIFIED
                  SET VAL      = V_VAL
                  WHERE MONTH_ID       = V_MONTH_ID
                  AND EMPLOYEE_ID    = V_EMPLOYEE_ID
                  AND PAY_ID = V_PAY_ID
                  AND SALARY_TYPE_ID       = V_SALARY_TYPE_ID;
                EXCEPTION
                WHEN NO_DATA_FOUND THEN
                  INSERT
                  INTO HRIS_SS_PAY_VALUE_MODIFIED
                    (
                      MONTH_ID,
                      EMPLOYEE_ID,
                      PAY_ID,
                      VAL,
                      SALARY_TYPE_ID
                    )
                    VALUES
                    (
                      V_MONTH_ID,
                      V_EMPLOYEE_ID,
                      V_PAY_ID,
                      V_VAL,
                      V_SALARY_TYPE_ID
                    );
                END;
";
                  //echo $sql; die;
        $statement = $this->adapter->query($sql);
        return $statement->execute($boundedParameter);
    }
    
    public function getSalaryTypes(){
        $sql = "SELECT SALARY_TYPE_ID, SALARY_TYPE_NAME FROM HRIS_SALARY_TYPE";
        $statement = $this->adapter->query($sql);
        return $statement->execute();
    }
}
