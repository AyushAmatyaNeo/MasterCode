<?php

namespace KioskApi\Repository;

use phpDocumentor\Reflection\Types\Integer;
use Zend\Db\Adapter\AdapterInterface;
use Application\Helper\Helper;

class KioskPrintRepo {

    private $adapter;

    public function __construct(AdapterInterface $adapter) {
        $this->adapter = $adapter;
    }

    public function fetchCount($data, $employeeId) {
        $loanCondition = '';
        if($data['LoanId'] != null){
            $loanCondition = " AND LOAN_ID = {$data['LoanId']}";
        }

        $salaryTypeCondition = '';
        If($data['PrintType'] == 'PS'){
            //$salaryTypeCondition = " AND SALARY_TYPE_ID = {$data['SalaryTypeId']}";
        }

        $sql = "
            SELECT COUNT(*) AS COUNT
            FROM HRIS_KIOSK_PRINT_STAT
            WHERE EMPLOYEE_ID = {$employeeId}
            AND PRINT_TYPE    = '{$data['PrintType']}'
            AND MONTH_ID      = 13
            {$salaryTypeCondition}
            {$loanCondition}
            ";

        $statement = $this->adapter->query($sql);
        $result = $statement->execute();
        return Helper::extractDBData($result);
    }

    public function insertData($data, $employeeId){
        $loanKey = '';
        if($data['LoanId'] != null){
            $loanKey = " , LOAN_ID ";
        }
        $loanInsert = '';
        if($data['LoanId'] != null){
            $loanInsert = ", {$data['LoanId']}";
        }
        $salaryTypeKey = '';
        if($data['SalaryTypeId'] == 'PS'){
            $salaryTypeKey = " , SALARY_TYPE_ID ";
        }
        $salaryTypeInsert = '';
        if($data['SalaryTypeId'] == 'PS'){
            $salaryTypeInsert = ", {$data['SalaryTypeId']}";
        }

        $sql = "INSERT INTO HRIS_KIOSK_PRINT_STAT(PRINT_ID,MONTH_ID,EMPLOYEE_ID,PRINT_TYPE {$loanKey} {$salaryTypeKey}) VALUES ((SELECT NVL(MAX(PRINT_ID),0)+1 FROM HRIS_KIOSK_PRINT_STAT),(select MONTH_ID from HRIS_MONTH_CODE where TRUNC(SYSDATE) between from_date and to_date),{$employeeId},'{$data['PrintType']}'{$loanInsert}{$salaryTypeInsert})";

        $statement = $this->adapter->query($sql);
        $statement->execute();
    }

}
