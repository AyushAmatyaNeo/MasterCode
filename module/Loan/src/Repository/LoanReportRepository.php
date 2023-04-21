<?php

namespace Loan\Repository;

use Zend\Db\TableGateway\TableGateway;
use Zend\Db\Sql\Sql;
use Zend\Db\Sql\Select;
use Application\Repository\RepositoryInterface;
use Setup\Model\HrEmployees;
use Application\Helper\EntityHelper;

class LoanReportRepository implements RepositoryInterface {

    private $adapter;

    public function __construct(\Zend\Db\Adapter\AdapterInterface $adapter) {
        $this->adapter = $adapter;
    }

    public function add(\Application\Model\Model $model) {
        
    }

    public function delete($id) {
        
    }

    public function edit(\Application\Model\Model $model, $id) {
        
    }

    public function fetchAll() {
        
    }

    public function fetchById($id) {
        
    }

    public function fetchEmployeeLoanDetails($data){
        $fromDate = $data['fromDate'];
        $toDate = $data['toDate'];
        $employeeId = $data['employeeId'];
        $companyId = $data['companyId'];
        $branchId = $data['branchId'];
        $departmentId = $data['departmentId'];
        $designationId = $data['designationId'];
        $positionId = $data['positionId'];
        $serviceTypeId = $data['serviceTypeId'];
        $serviceEventTypeId = $data['serviceEventTypeId'];
        $loanId = $data['loanId'];
        $loanRequestStatusId = $data['loanRequestStatusId'];
        $employeeTypeId = $data['employeeTypeId'];
        $loanStatus = $data['loanStatus'];

        $sql = "SELECT DISTINCT
        e.employee_code,
        e.full_name,
        initcap(l.loan_name) AS loan_name,
        lr.requested_amount,
        lr.loan_request_id,
        NVL(ROUND((
            SELECT
                SUM(amount)
            FROM
                hris_loan_payment_detail
            WHERE
                    paid_flag = 'Y'
                AND
                    loan_request_id = lr.loan_request_id
        )), 0)AS paid_amount,
        ( 
            SELECT
                ROUND(SUM(amount))
            FROM
                hris_loan_payment_detail
            WHERE
                    paid_flag = 'N'
                AND
                    loan_request_id = lr.loan_request_id
        )  AS balance,
        (select amount from hris_loan_payment_detail where loan_request_id = lr.loan_request_id
        and from_date = trunc(sysdate,'month')) AS current_installment,
        initcap(TO_CHAR(lr.loan_date,'DD-MON-YYYY') ) AS loan_date_ad,
        bs_date(TO_CHAR(lr.loan_date,'DD-MON-YYYY') ) AS loan_date_bs,
        initcap(TO_CHAR(lr.requested_date,'DD-MON-YYYY') ) AS requested_date_ad,
        bs_date(TO_CHAR(lr.requested_date,'DD-MON-YYYY') ) AS requested_date_bs,
        lr.loan_status AS status,
        lr.employee_id AS employee_id
    FROM
        hris_employee_loan_request lr
        LEFT OUTER JOIN hris_loan_master_setup l ON l.loan_id = lr.loan_id
        LEFT OUTER JOIN hris_employees e ON e.employee_id = lr.employee_id
        LEFT OUTER JOIN hris_loan_payment_detail hlpd ON hlpd.loan_request_id = lr.loan_request_id
    WHERE
        1 = 1 ";
    
        if ($fromDate != null) {
            $sql .= " AND LR.LOAN_DATE>=TO_DATE('" . $fromDate . "','DD-MM-YYYY')";
        }

        if ($toDate != null) {
            $sql .= "AND LR.LOAN_DATE<=TO_DATE('" . $toDate . "','DD-MM-YYYY')";
        }

        if ($employeeTypeId != null && $employeeTypeId != -1) {
            $sql .= "AND E.EMPLOYEE_TYPE='" . $employeeTypeId . "' ";
        }

        if ($employeeId != -1) {
            $sql .= "AND E." . HrEmployees::EMPLOYEE_ID . " = $employeeId";
        }

        if ($companyId != -1) {
            $sql .= " AND E." . HrEmployees::EMPLOYEE_ID . " IN (SELECT " . HrEmployees::EMPLOYEE_ID . " FROM " . HrEmployees::TABLE_NAME . " WHERE " . HrEmployees::COMPANY_ID . "= $companyId)";
        }
        if ($branchId != -1) {
            $sql .= " AND E." . HrEmployees::EMPLOYEE_ID . " IN (SELECT " . HrEmployees::EMPLOYEE_ID . " FROM " . HrEmployees::TABLE_NAME . " WHERE " . HrEmployees::BRANCH_ID . "= $branchId)";
        }
        if ($departmentId != -1) {
            $sql .= " AND E." . HrEmployees::EMPLOYEE_ID . " IN (SELECT " . HrEmployees::EMPLOYEE_ID . " FROM " . HrEmployees::TABLE_NAME . " WHERE " . HrEmployees::DEPARTMENT_ID . "= $departmentId)";
        }
        if ($designationId != -1) {
            $sql .= " AND E." . HrEmployees::EMPLOYEE_ID . " IN (SELECT " . HrEmployees::EMPLOYEE_ID . " FROM " . HrEmployees::TABLE_NAME . " WHERE " . HrEmployees::DESIGNATION_ID . "= $designationId)";
        }
        if ($positionId != -1) {
            $sql .= " AND E." . HrEmployees::EMPLOYEE_ID . " IN (SELECT " . HrEmployees::EMPLOYEE_ID . " FROM " . HrEmployees::TABLE_NAME . " WHERE " . HrEmployees::POSITION_ID . "= $positionId)";
        }
        if ($serviceTypeId != -1) {
            $sql .= " AND E." . HrEmployees::EMPLOYEE_ID . " IN (SELECT " . HrEmployees::EMPLOYEE_ID . " FROM " . HrEmployees::TABLE_NAME . " WHERE " . HrEmployees::SERVICE_TYPE_ID . "= $serviceTypeId)";
        }
        if ($serviceEventTypeId != -1) {
            $sql .= " AND E." . HrEmployees::EMPLOYEE_ID . " IN (SELECT " . HrEmployees::EMPLOYEE_ID . " FROM " . HrEmployees::TABLE_NAME . " WHERE " . HrEmployees::SERVICE_EVENT_TYPE_ID . "= $serviceEventTypeId)";
        }
        if ($loanStatus != 'BOTH') {
            $sql .= " AND LR.LOAN_STATUS = '".$loanStatus."'";
        }

        $sql .= " ORDER BY LR.LOAN_REQUEST_ID DESC";
        $statement = $this->adapter->query($sql); 
        return $statement->execute();
    }
  
    public function fetchLoanVoucher($emp_id, $fromDate, $toDate, $loanId){

        $employeeCondition = $emp_id == '' || $emp_id == null || $emp_id == -1? '' : " AND HELR.EMPLOYEE_ID = $emp_id";
        $loanCondition = $loanId == '' || $loanId == null || $loanId == -1? ' AND HELR.LOAN_ID IN (1,2,3,7) ' : " AND  HELR.LOAN_ID = $loanId" ;
        $loanRequestCondition = $emp_id == '' || $emp_id == null || $emp_id == -1? '' : " AND EMPLOYEE_ID = $emp_id" ;
        $loanRequestCondition2 = $loanId == '' || $loanId == null || $loanId == -1? ' AND LOAN_ID IN (1,2,3,7) ' : " AND loan_id = $loanId" ;
        $employeeCondition2 = $emp_id == '' || $emp_id == null || $emp_id == -1? '' : " AND EMPLOYEE_ID = $emp_id";

        $sql = "SELECT DT, particulars, debit_amount, credit_amount, balance FROM(
        SELECT LAST_DAY(HLPD.FROM_DATE) AS DT, 'Interest Due' as PARTICULARS,
        TRUNC(SUM(HLPD.INTEREST_AMOUNT), 2) AS DEBIT_AMOUNT, 
        0 AS CREDIT_AMOUNT, 0 AS BALANCE
        FROM 
        HRIS_LOAN_PAYMENT_DETAIL HLPD JOIN 
        HRIS_EMPLOYEE_LOAN_REQUEST HELR ON(HELR.LOAN_REQUEST_ID = HLPD.LOAN_REQUEST_ID)
            WHERE hlpd.PAID_FLAG = 'Y' {$loanCondition} AND trunc(HLPD.FROM_DATE, 'month') IN(
        select trunc(add_Months('{$fromDate}', level-1),'month') result
        from DUAL
        connect by level <= MONTHS_BETWEEN('{$toDate}', '{$fromDate}')+1
        ) {$employeeCondition}
        and (helr.loan_status = 'OPEN' or helr.modified_date >= '{$fromDate}' or 
        helr.employee_id in 
	(select employee_id from hris_loan_cash_payment where 1=1 {$loanRequestCondition2} {$employeeCondition2})
             ) 
        GROUP BY HLPD.FROM_DATE
        
        UNION ALL
        
        SELECT LAST_DAY(HLPD.FROM_DATE) AS DT, 'Interest Paid' as PARTICULARS,
            0 AS DEBIT_AMOUNT,
            TRUNC(SUM(HLPD.INTEREST_AMOUNT), 2) AS CREDIT_AMOUNT, 0 AS BALANCE
        FROM 
        HRIS_LOAN_PAYMENT_DETAIL HLPD JOIN 
        HRIS_EMPLOYEE_LOAN_REQUEST HELR ON(HELR.LOAN_REQUEST_ID = HLPD.LOAN_REQUEST_ID)
            WHERE hlpd.PAID_FLAG = 'Y' {$loanCondition} AND trunc(HLPD.FROM_DATE, 'month') IN(
        select trunc(add_Months('{$fromDate}', level-1),'month') result
        from DUAL
        connect by level <= MONTHS_BETWEEN('{$toDate}', '{$fromDate}')+1
        ) {$employeeCondition}
        and (helr.loan_status = 'OPEN' or helr.modified_date >= '{$fromDate}' or 
        helr.employee_id in 
            (select employee_id from hris_loan_cash_payment where 1=1 {$loanRequestCondition2} {$employeeCondition2})
             )
        GROUP BY HLPD.FROM_DATE
        
        UNION ALL
        
        SELECT LAST_DAY(HLPD.FROM_DATE) AS DT, 'Amount Paid' as PARTICULARS,
            0 AS DEBIT_AMOUNT,
            TRUNC(SUM(HLPD.AMOUNT), 2) AS CREDIT_AMOUNT, 0 AS BALANCE
        FROM 
        HRIS_LOAN_PAYMENT_DETAIL HLPD JOIN 
        HRIS_EMPLOYEE_LOAN_REQUEST HELR ON(HELR.LOAN_REQUEST_ID = HLPD.LOAN_REQUEST_ID)
        WHERE hlpd.PAID_FLAG = 'Y' {$loanCondition} AND trunc(HLPD.FROM_DATE, 'month') IN(
        select trunc(add_Months('{$fromDate}', level-1),'month') result
        from DUAL
        connect by level <= MONTHS_BETWEEN('{$toDate}', '{$fromDate}')+1
        ) {$employeeCondition}
        and (helr.loan_status = 'OPEN' or helr.modified_date >= '{$fromDate}' 
        or helr.employee_id in 
            (select employee_id from hris_loan_cash_payment where 1=1 {$loanRequestCondition2} {$employeeCondition2})
              )
        GROUP BY HLPD.FROM_DATE
        ORDER BY DT, DEBIT_AMOUNT DESC, CREDIT_AMOUNT DESC)
                
        UNION ALL

        (SELECT
        LOAN_DATE AS dt,
        'Loan Taken' AS particulars,
        REQUESTED_AMOUNT AS debit_amount,
        0 AS credit_amount,
        0 AS balance
    FROM
        hris_employee_loan_request
        WHERE
        1 = 1
        {$loanRequestCondition2}
        AND ( loan_date BETWEEN '{$fromDate}' AND '{$toDate}' )
        --AND to_char(TO_DATE(loan_date, 'dd-mon-yy'), 'mm') <> 7
        {$employeeCondition2}
        and print_flag = 'Y')

            UNION ALL

        (SELECT
        PAYMENT_DATE AS dt,
        'Cash Interest Paid' AS particulars,
        PAYMENT_AMOUNT AS debit_amount,
        0 AS credit_amount,
        0 AS balance
        FROM
        HRIS_LOAN_CASH_PAYMENT
            WHERE PAYMENT_AMOUNT > 0 and type = 'INT'
            {$loanRequestCondition2} {$employeeCondition2}
        AND
            PAYMENT_DATE BETWEEN '{$fromDate}' AND '{$toDate}'    
        )

        UNION ALL

        (SELECT
        PAYMENT_DATE AS dt,
        'Cash Interest Paid' AS particulars,
        0 AS debit_amount,
        PAYMENT_AMOUNT AS credit_amount,
        0 AS balance
        FROM
        HRIS_LOAN_CASH_PAYMENT
            WHERE PAYMENT_AMOUNT > 0 and type = 'INT'
            {$loanRequestCondition2} {$employeeCondition2}
        AND
            PAYMENT_DATE BETWEEN '{$fromDate}' AND '{$toDate}'    
        )

        UNION ALL

        (SELECT
        PAYMENT_DATE AS dt,
        'Cash Amount Paid' AS particulars,
        0 AS debit_amount,
        PAYMENT_AMOUNT AS credit_amount,
        0 AS balance
        FROM
        HRIS_LOAN_CASH_PAYMENT
        WHERE PAYMENT_AMOUNT > 0 and type = 'PRN'
		{$loanRequestCondition2} {$employeeCondition2}
        AND
            PAYMENT_DATE BETWEEN '{$fromDate}' AND '{$toDate}'    
        )

    ORDER BY
        dt,
        debit_amount DESC,
        credit_amount DESC
                ";        
        //echo $sql; die;
        $statement = $this->adapter->query($sql); 
        return $statement->execute();
    }
    
    public function getLoanlist(){
        $sql = "SELECT LOAN_ID, LOAN_NAME FROM HRIS_LOAN_MASTER_SETUP ORDER BY LOAN_ID";
        $statement = $this->adapter->query($sql); 
        return $statement->execute();
    }
      
    public function fetchOpeningBalance($emp_id, $fromDate, $loanId){
        $employeeCondition = $emp_id == '' || $emp_id == null || $emp_id == -1? '' : " AND HELR.EMPLOYEE_ID = $emp_id";
        $loanCondition = $loanId == '' || $loanId == null || $loanId == -1? ' AND HELR.LOAN_ID IN (1,2,3,7) ' : " AND  HELR.LOAN_ID = $loanId" ;
        $loanRequestCondition = $emp_id == '' || $emp_id == null || $emp_id == -1? '' : " AND EMPLOYEE_ID = $emp_id" ;
        $loanRequestCondition2 = $loanId == '' || $loanId == null || $loanId == -1? ' AND LOAN_ID IN (1,2,3,7) ' : " AND loan_id = $loanId" ;
        $employeeCondition2 = $emp_id == '' || $emp_id == null || $emp_id == -1? '' : " AND EMPLOYEE_ID = $emp_id";

        $sql = "SELECT
            trunc(SUM(Opening_Amount), 2) as opening_balance,
            0 AS dr_salary,
            0 AS dr_interest,
            0 AS cr_salary
        FROM
            Hris_Loan_Opening_Balance
        WHERE
            Opening_Date >= trunc(to_date('$fromDate'))
            {$loanRequestCondition2}
            {$employeeCondition2}      
        ";
        //echo $sql; die;
        $statement = $this->adapter->query($sql); 
        return $statement->execute();
    }

    public function getCashPaymentsList($searchQuery) {
        $searchConditon = EntityHelper::getSearchConditon($searchQuery['companyId'], $searchQuery['branchId'], $searchQuery['departmentId'], $searchQuery['positionId'], $searchQuery['designationId'], $searchQuery['serviceTypeId'], $searchQuery['serviceEventTypeId'], $searchQuery['employeeTypeId'], $searchQuery['employeeId'], $searchQuery['genderId'], $searchQuery['locationId'], $searchQuery['functionalTypeId']);

        $fromDate = $data['fromDate'];
        $toDate = $data['toDate'];
        $loanId = $data['loanId'];
        
        $sql = "SELECT
                e.employee_code AS employee_code,
                e.FULL_NAME AS FULL_NAME,
                HLCP.id,
                initcap(l.loan_name) AS LOAN_NAME,
                lr.requested_amount AS TOTAL_AMOUNT,
                HLCP.PAYMENT_AMOUNT AS PAID_AMOUNT,
                INITCAP(TO_CHAR(HLCP.payment_date, 'DD-MON-YYYY'))        AS PAID_DATE_AD,
                BS_DATE(TO_CHAR(HLCP.payment_date, 'DD-MON-YYYY'))        AS PAID_DATE_BS,
                HLCP.REMARKS AS REMARKS
                             
                FROM HRIS_EMPLOYEE_LOAN_REQUEST LR
                JOIN HRIS_LOAN_CASH_PAYMENT HLCP 
                ON LR.LOAN_REQUEST_ID = HLCP.LOAN_REQ_ID
                LEFT OUTER JOIN HRIS_LOAN_MASTER_SETUP L
                ON L.LOAN_ID=LR.LOAN_ID
                LEFT OUTER JOIN HRIS_EMPLOYEES E
                ON E.EMPLOYEE_ID=LR.EMPLOYEE_ID
                WHERE L.STATUS   ='E'
                AND E.STATUS     ='E' {$searchConditon}";

        if ($loanId != -1 && $loanId != null && $loanId != '') {
            $sql .= " AND LR.LOAN_ID ='" . $loanId . "'";
        }

        if ($fromDate != null) {
            $sql .= " AND LR.LOAN_DATE>=TO_DATE('" . $fromDate . "','DD-MM-YYYY')";
        }

        if ($toDate != null) {
            $sql .= "AND LR.LOAN_DATE<=TO_DATE('" . $toDate . "','DD-MM-YYYY')";
        }

        $sql .= " ORDER BY LR.LOAN_REQUEST_ID DESC";
        //echo $sql; die;
        $statement = $this->adapter->query($sql);
        $result = $statement->execute();
        return $result;
    }

    public function fetchLoanSummary($emp_id, $fromDate, $toDate, $loanId){
        $employeeCondition = $emp_id == '' || $emp_id == null? '' : " AND HELR.EMPLOYEE_ID = $emp_id";
        $loanCondition = $loanId == '' || $loanId == null || $loanId == -1? ' AND HELR.LOAN_ID IN (1,2,3,7) ' : " AND  HELR.LOAN_ID = $loanId" ;
       $loanRequestCondition = $emp_id == '' || $emp_id == null? '' : " AND EMPLOYEE_ID = $emp_id" ;
     $loanRequestCondition2 = $loanId == '' || $loanId == null || $loanId == -1? ' AND LOAN_ID IN (1,2,3,7) ' : " AND loan_id = $loanId" ;
        $employeeCondition2 = $emp_id == '' || $emp_id == null? '' : " AND EMPLOYEE_ID = $emp_id";


        $sql = "select e.employee_code, e.employee, opening_balance, dr_salary, dr_interest, cr_salary, dr_interest as cr_interest,
        (opening_balance + dr_salary - cr_salary) as balance
        from (
        select 
        nvl(sum(opening_balance), 0) as opening_balance,
        sum(dr_salary) as dr_salary,
        sum(dr_interest) as dr_interest,
        sum(cr_salary) as cr_salary
        from (
        SELECT
        0 as opening_balance,
        0 AS dr_salary,
        trunc(SUM(hlpd.interest_amount), 2) AS dr_interest,
        0 AS cr_salary
        FROM
        hris_loan_payment_detail     hlpd
        JOIN hris_employee_loan_request   helr ON ( helr.loan_request_id = hlpd.loan_request_id )
        WHERE
        hlpd.paid_flag = 'Y'
        {$loanCondition}
        AND trunc(hlpd.from_date, 'month') IN (
        SELECT
        trunc(add_months('{$fromDate}', level - 1), 'month') result
        FROM
        dual
        CONNECT BY
        level <= months_between('{$toDate}', '{$fromDate}') + 1
        )
        {$employeeCondition}
        and hlpd.voucher_no is  null
        GROUP BY
        hlpd.from_date
        
        union all
        
        SELECT
        0 as opening_balance,
        0 AS dr_salary,
        0 AS dr_interest,
        trunc(SUM(hlpd.amount), 2) AS cr_salary
        FROM
        hris_loan_payment_detail     hlpd
        JOIN hris_employee_loan_request   helr ON ( helr.loan_request_id = hlpd.loan_request_id )
        WHERE
        hlpd.paid_flag = 'Y'
        {$loanCondition}
        AND trunc(hlpd.from_date, 'month') IN (
        SELECT
        trunc(add_months('{$fromDate}', level - 1), 'month') result
        FROM
        dual
        CONNECT BY
        level <= months_between('{$toDate}', '{$fromDate}') + 1
        )
        {$employeeCondition}
        and hlpd.voucher_no is  null
        GROUP BY
        hlpd.from_date
        
        union all
        
        SELECT
        0 as opening_balance,
        requested_amount AS dr_salary,
        0 AS dr_interest,
        0 AS cr_salary
        
        FROM
        hris_employee_loan_request
        WHERE
        1 = 1
        {$loanRequestCondition2}
        AND ( loan_date BETWEEN '{$fromDate}' AND '{$toDate}' )
        --AND to_char(TO_DATE(loan_date, 'dd-mon-yy'), 'mm') <> 7
        {$employeeCondition2}
        and print_flag = 'Y'
		
		union all
        
		select 0 as opening_balance,
        0 AS dr_salary,
        0 AS dr_interest,
		trunc(sum(payment_amount), 2) as cr_salary from hris_loan_cash_payment
		where 1=1 and type = 'PRN' {$employeeCondition2} {$loanRequestCondition2}
		
		union all
		
		select 0 as opening_balance,
        0 AS dr_salary,
        trunc(sum(payment_amount), 2) AS dr_interest,
		0 as cr_salary from hris_loan_cash_payment
		where 1=1 and type = 'INT' {$employeeCondition2} {$loanRequestCondition2}
        
        union all
        
        SELECT
            trunc(SUM(Opening_Amount), 2) as opening_balance,
            0 AS dr_salary,
            0 AS dr_interest,
            0 AS cr_salary
        FROM
            Hris_Loan_Opening_Balance
        WHERE
            Opening_Date >= trunc(to_date('$fromDate'))
            {$loanRequestCondition2}
            {$employeeCondition2}           
        )) join 
        (select employee_id, full_name, employee_code ,full_name as employee 
        from hris_employees where employee_id = {$emp_id}) e
        on 1=1 order by e.full_name
        ";
        //echo $sql; die;
        $statement = $this->adapter->query($sql); 
        return $statement->execute();
    }

    public function getAllEmployees(){
        $sql = "SELECT EMPLOYEE_ID, EMPLOYEE_CODE, EMPLOYEE_CODE || '-' || FULL_NAME AS FULL_NAME, FULL_NAME AS FULL_NAME_SCIENTIFIC, COMPANY_ID, 
        BRANCH_ID, DEPARTMENT_ID, 
        DESIGNATION_ID, POSITION_ID, SERVICE_TYPE_ID, SERVICE_EVENT_TYPE_ID, GENDER_ID, EMPLOYEE_TYPE, 
        GROUP_ID, FUNCTIONAL_TYPE_ID FROM HRIS_EMPLOYEES";
    
        $statement = $this->adapter->query($sql); 
        return $statement->execute();
    }
	
	public function getAllEmployeeId(){
		$sql = "SELECT EMPLOYEE_ID FROM HRIS_EMPLOYEES where group_id in(1,2,3,4,8,9,10,19) and status = 'E'";
    // print_r($sql);die;
        $statement = $this->adapter->query($sql); 
        return $statement->execute();
	}
}
