<?php

namespace Payroll\Repository;

use Application\Helper\Helper;
use Application\Model\Model;
use Application\Model\Months;
use Application\Repository\HrisRepository;
use Payroll\Model\SalarySheet;
use Zend\Db\Adapter\AdapterInterface;
use Zend\Db\Sql\Select;
use Zend\Db\Sql\Sql;

class SalarySheetRepo extends HrisRepository {

    public function __construct(AdapterInterface $adapter, $tableName = null) {
        parent::__construct($adapter, SalarySheet::TABLE_NAME);
    }

    public function add(Model $model) {
        return $this->tableGateway->insert($model->getArrayCopyForDB());
    }

    public function delete($id) {
        return $this->tableGateway->delete([SalarySheet::MONTH_ID => $id]);
    }

    public function fetchAll() {
        return $this->tableGateway->select(function (Select $select) {
                    $select->columns(Helper::convertColumnDateFormat($this->adapter, new SalarySheet(), [
                                'startDate',
                                'endDate',
                            ]), false);
                });
    }

    public function fetchById($id) {
        return $this->tableGateway->select([SalarySheet::SHEET_NO => $id]);
    }

    public function fetchByIds(array $ids) {
        return $this->tableGateway->select($ids);
    }

    public function fetchOneBy(array $ids) {
        return $this->tableGateway->select($ids)->current();
    }

    public function joinWithMonth($monthId = null, $employeeJoinDate = null) {
        $sql = new Sql($this->adapter);
        $select = $sql->select();
        $select->columns([]);
        $select->from(['S' => SalarySheet::TABLE_NAME])
                ->join(['M' => Months::TABLE_NAME], 'S.' . SalarySheet::MONTH_ID . '=M.' . Months::MONTH_ID);
        if ($monthId != null) {
            $select->where([Months::MONTH_ID => $monthId]);
        }
        $select->where(["M." . Months::STATUS . " = " . "'E'"]);
        $select->where(["S." . SalarySheet::STATUS . " = " . "'E'"]);

        if ($employeeJoinDate != null) {
            $select->where(["'" . $employeeJoinDate . "'" . " <= " . "M." . Months::TO_DATE]);
        }

        $statement = $sql->prepareStatementForSqlObject($select);
        $result = $statement->execute();
        return $result;
    }

    public function generateSalShReport($sheetNo) {
        $boundedParameter = [];
        $boundedParameter['sheetNo'] = $sheetNo;
        $this->executeStatement("BEGIN
                            HRIS_GEN_SAL_SH_REPORT(:sheetNo);
                        END;", $boundedParameter);
    }

    public function updateLoanPaymentFlag($employeeId, $sheetNo) {
        $boundedParameter = [];
        $boundedParameter['sheetNo'] = $sheetNo;
        $boundedParameter['employeeId'] = $employeeId;
        $this->executeStatement("BEGIN
                            hris_loan_payment_flag_change(:employeeId,:sheetNo);
                        END;", $boundedParameter);
    }

    public function fetchAllSalaryType() {
        $sql = new Sql($this->adapter);
        $select = $sql->select();
        $select->columns(['*']);
        $select->from('HRIS_SALARY_TYPE');
        $statement = $sql->prepareStatementForSqlObject($select);
        $result = $statement->execute();
        return $result;
    }

    public function fetchEmployeeByGroup($monthId,$group,$salaryTypeId,$companyId) {
        $boundedParameter = [];

        $boundedParameter['salaryTypeId'] = $salaryTypeId;
        $boundedParameter['monthId'] = $monthId;
        // echo '<pre>';print_r($companyId);die;

        if ($companyId > 0){
            $sql = "SELECT 
            E.employee_id,E.employee_code,E.full_name,'Y' AS CHECKED_FLAG
            FROM HRIS_EMPLOYEES E
            JOIN (SELECT * FROM HRIS_MONTH_CODE WHERE MONTH_ID=:monthId) MC on (1=1)
            WHERE 
            E.STATUS='E'AND E.COMPANY_ID={$companyId} AND 
employee_id not in (SELECT employee_id FROM HRIS_SALARY_SHEET_EMP_DETAIL SED
JOIN HRIS_SALARY_SHEET SS ON (SS.SHEET_NO=SED.SHEET_NO) 
where SS.month_id=:monthId AND SS.SALARY_TYPE_ID=:salaryTypeId)  
AND  E.JOIN_DATE<MC.TO_DATE 
AND GROUP_ID IN ({$group})
";

        if($salaryTypeId>1){
            $sql.=" and E.employee_id in (
            select distinct employee_id from HRIS_SS_PAY_VALUE_MODIFIED 
            where 
            month_id=:monthId
            and SALARY_TYPE_ID=:salaryTypeId
            )";
        }
        }
        else{
            $sql = "SELECT 
            E.employee_id,E.employee_code,E.full_name,'Y' AS CHECKED_FLAG
            FROM HRIS_EMPLOYEES E
            JOIN (SELECT * FROM HRIS_MONTH_CODE WHERE MONTH_ID=:monthId) MC on (1=1)
            WHERE 
            E.STATUS='E' AND 
employee_id not in (SELECT employee_id FROM HRIS_SALARY_SHEET_EMP_DETAIL SED
JOIN HRIS_SALARY_SHEET SS ON (SS.SHEET_NO=SED.SHEET_NO) 
where SS.month_id=:monthId AND SS.SALARY_TYPE_ID=:salaryTypeId)  
AND  E.JOIN_DATE<MC.TO_DATE 
AND GROUP_ID IN ({$group})";

        if($salaryTypeId>1){
            $sql.=" and E.employee_id in (
            select distinct employee_id from HRIS_SS_PAY_VALUE_MODIFIED 
            where 
            month_id=:monthId
            and SALARY_TYPE_ID=:salaryTypeId
            )";
        }
    }

        $data = $this->rawQuery($sql, $boundedParameter);
        // echo '<pre>';print_r($data); die;

        return $data;
    }

    public function fetchGeneratedSheetByGroup($monthId,$group,$salaryTypeId,$companyId){
        $boundedParameter = [];

        $boundedParameter['salaryTypeId'] = $salaryTypeId;
        $boundedParameter['monthId'] = $monthId;
        if($companyId > 0){
            $sql="select 
            ss.sheet_no,ssg.Group_Name,Mc.Month_Edesc,St.Salary_Type_Name,
            ss.approved, ss.locked 
            from HRIS_SALARY_SHEET ss 
            join hris_salary_sheet_group ssg on (ssg.group_id=ss.group_id)
            join Hris_Month_Code mc on (mc.month_id=ss.month_id)
            join HRIS_SALARY_TYPE st on (St.Salary_Type_Id=Ss.Salary_Type_Id)
            where ss.Month_Id=:monthId and ss.salary_type_id=:salaryTypeId  and ss.Group_Id in ($group) and ss.company_id=$companyId
            order by ss.sheet_no asc";
        }else{
            $sql="select 
            ss.sheet_no,ssg.Group_Name,Mc.Month_Edesc,St.Salary_Type_Name,
            ss.approved, ss.locked 
            from HRIS_SALARY_SHEET ss 
            join hris_salary_sheet_group ssg on (ssg.group_id=ss.group_id)
            join Hris_Month_Code mc on (mc.month_id=ss.month_id)
            join HRIS_SALARY_TYPE st on (St.Salary_Type_Id=Ss.Salary_Type_Id)
            where ss.Month_Id=:monthId and ss.salary_type_id=:salaryTypeId  and ss.Group_Id in ($group)
            order by ss.sheet_no asc";
        }

        $data = $this->rawQuery($sql, $boundedParameter);
        // echo '<pre>';print_r($sql);die

        return $data;
    }

    public function insertPayrollEmp($empList,$monthId,$salaryTypeId) {
        $deleteSql = "delete from HRIS_PAYROLL_EMP_LIST";
        $this->executeStatement($deleteSql);
        $boundedParameter = [];
        $boundedParameter['salaryTypeId'] = $salaryTypeId;
        $boundedParameter['monthId'] = $monthId;
        foreach ($empList as $employeeId) {
            $boundedParameter['employeeId'] = $employeeId;
//            $tempSql = "INSERT INTO HRIS_PAYROLL_EMP_LIST VALUES ({$employeeId})";
            $tempSql = "INSERT INTO HRIS_PAYROLL_EMP_LIST 
                    select * from (select :employeeId as employee_id from dual) 
where employee_id not in (select employee_id from Hris_Salary_Sheet_Emp_Detail ssed
join HRIS_SALARY_SHEET ss on (ss.SHEET_NO=ssed.SHEET_NO)
where ss.month_id=:monthId and ss.SALARY_TYPE_ID=:salaryTypeId)";
            $this->executeStatement($tempSql, $boundedParameter);
        }
        $toGenerateGroupSql="select  distinct group_id 
                from 
                 hris_employees where employee_id in
                 (select employee_id from HRIS_PAYROLL_EMP_LIST)";
        $groupData = $this->rawQuery($toGenerateGroupSql);
        return $groupData;
    }

    public function deleteSheetBySheetNo($sheetNo){
        $sql="
        BEGIN            
        delete from HRIS_TAX_SHEET where sheet_no=:sheetNo;
        delete from HRIS_SALARY_SHEET_DETAIL where sheet_no=:sheetNo;
        delete from HRIS_SALARY_SHEET_EMP_DETAIL where sheet_no=:sheetNo;
        delete from HRIS_SALARY_SHEET where sheet_no=:sheetNo;
        END;
";
        $boundedParameter = [];
        $boundedParameter['sheetNo'] = $sheetNo;
        $this->executeStatement($sql, $boundedParameter);
        return true;
    }

    //here for deleting individual salary
	public function deleteEmployeeSalarySheet($sheetNo, $employeeId){
       $sql="
       BEGIN
       delete from HRIS_TAX_SHEET where sheet_no=:sheetNo and employee_id = :employee_id;
       delete from HRIS_SALARY_SHEET_DETAIL where sheet_no=:sheetNo and employee_id = :employee_id;
       delete from HRIS_SALARY_SHEET_EMP_DETAIL where sheet_no=:sheetNo and employee_id = :employee_id;
       END;
";
       $boundedParameter = [];
       $boundedParameter['sheetNo'] = $sheetNo;
       $boundedParameter['employee_id'] = $employeeId;
       $this->executeStatement($sql, $boundedParameter);
   }
//here for deleting individual salary

    public function bulkApproveLock($sheetNo, $col, $val){
        $sql = "UPDATE HRIS_SALARY_SHEET set $col = :val where sheet_no=:sheetNo";
        $boundedParameter = [];
        $boundedParameter['sheetNo'] = $sheetNo;
        //$boundedParameter['col'] = $col;
        $boundedParameter['val'] = $val;
        $this->executeStatement($sql, $boundedParameter);
        return true;
    }
	
	public function pivot($sheetNum){
        
        $pivotColumnSql = "select listagg(pay_id,', ') within group(order by pay_id) csv
        from hris_pay_setup WHERE VOUCHER_IMPACT = 'Y'";
        //$pivotCol = $this->rawQuery($pivotColumnSql);

        $statement = $this->adapter->query($pivotColumnSql);
        $result = $statement->execute();
        $pivotCol =  $result->current();

        $sql = "select * from (SELECT
                    ssd.employee_id,
                    ssd.pay_id,
                    ssd.val
                FROM
                    hris_salary_sheet_detail ssd
                    left join HRIS_EMPLOYEES E ON (E.EMPLOYEE_ID = ssd.EMPLOYEE_ID )
                    LEFT join HRIS_COMPANY C on (E.COMPANY_ID = C.COMPANY_ID )
                WHERE
                    sheet_no = $sheetNum 
                    AND ssd.pay_id IN ( SELECT pay_id FROM hris_pay_setup WHERE VOUCHER_IMPACT = 'Y' ) ) 
                pivot ( max(val) for pay_id in ( {$pivotCol['CSV']}  ))
                ORDER BY
                    employee_id";
        //echo('<pre>');print_r($sql);die;
        return $this->rawQuery($sql);
    }
    public function getAccCode($payId, $data, $branchCode){
        $companyCode = "select c.company_code from HRIS_SALARY_SHEET ss
        left join HRIS_COMPANY C ON( ss.company_id = c.company_id)
        where ss.sheet_no = {$data}";

        $getAccCodeSql = "select acc_code
        from HRIS_ACC_CODE_MAP WHERE pay_id = $payId and company_code = ({$companyCode}) and branch_code = {$branchCode}";
        $statement = $this->adapter->query($getAccCodeSql);
        $result = $statement->execute();
        return  $result->current()['ACC_CODE'];
    }
    public function checkTF($employeeId,$branchCode, $accCode, $sheetNo){
        // $getAccCodeSql = "select acc_code
        // from HRIS_ACC_CODE_MAP WHERE pay_id = $payId";

        // $statement = $this->adapter->query($getAccCodeSql);
        // $result = $statement->execute();
        // $accCode =  $result->current()['ACC_CODE'];
        $companyCode = "select c.company_code from HRIS_SALARY_SHEET ss
        left join HRIS_COMPANY C ON( ss.company_id = c.company_id)
        where ss.sheet_no = {$sheetNo}";

        $checkSql = "select * from fa_sub_ledger_map SLM left join fa_chart_of_accounts_setup CAS ON (SLM.company_code = CAS.company_code) 
        where SLM.sub_code = 'E' || '{$employeeId}' and SLM.deleted_flag = 'N' and SLM.acc_code = {$accCode} and cas.company_code=({$companyCode}) and cas.acc_code={$accCode}";//and CAS.branch_code = {$branchCode}
        $statement = $this->adapter->query($checkSql);
        $result = $statement->execute();
        $check =  $result->current();
        if($check == null){
            $checkResult = false;
        }else{
            $checkResult = true;
        }
        return $checkResult;
    }
    public function getMapPayIdList($data,$branchCode, $groupId){
        $companyCode = "select c.company_code from HRIS_SALARY_SHEET ss
        left join HRIS_COMPANY C ON( ss.company_id = c.company_id)
        where ss.sheet_no = {$data}";

        $sql = "select distinct pay_id from HRIS_ACC_CODE_MAP where company_code = ({$companyCode}) and branch_code = '{$branchCode}' and group_id = {$groupId}";
        return $this->rawQuery($sql);
    }

    public function checkApproveLock($sheetNo){
        $sql = "SELECT SHEET_NO, APPROVED, LOCKED FROM HRIS_SALARY_SHEET WHERE SHEET_NO = :sheetNo";
        $boundedParameter = [];
        $boundedParameter['sheetNo'] = $sheetNo;
        return $this->rawQuery($sql, $boundedParameter);
        //return Helper::extractDbData($data);
    }

    public function fetchSheetWiseEmployeeList($sheetNo){
        $sql = "select SS.SHEET_NO,SS.MONTH_ID,SS.SALARY_TYPE_ID,SSED.EMPLOYEE_ID from HRIS_SALARY_SHEET SS
JOIN HRIS_SALARY_SHEET_EMP_DETAIL SSED ON (SS.SHEET_NO=SSED.SHEET_NO)
where SS.SHEET_NO=:sheetNo";
        $boundedParameter = [];
        $boundedParameter['sheetNo'] = $sheetNo;
        return $this->rawQuery($sql, $boundedParameter);
        //return Helper::extractDbData($data);
    }

    public function fetchCompanyByGroup($groupId) {

        $sql = "select company_id from HRIS_EMPLOYEES where GROUP_ID = :groupId  and ROWNUM = 1";
        $boundedParameter = [];
        $boundedParameter['groupId'] = $groupId;
        $data = $this->rawQuery($sql, $boundedParameter);
        return $data[0]['COMPANY_ID'];
//        return $this->rawQuery($sql, $boundedParameter);
    }
	
	public function getDataForVoucherSubDetail($sheetNo,$employeeId,$accCode, $branchCode){
		
        $companyCode = "select c.company_code from HRIS_SALARY_SHEET ss
        left join HRIS_COMPANY C ON( ss.company_id = c.company_id)
        where ss.sheet_no = {$sheetNo}";

        $sql="select FN_NEW_VOUCHER_NO(({$companyCode}), 107, trunc(sysdate), 'FA_DOUBLE_VOUCHER') as VOUCHER_NO, 
           A.pay_id,  
           acm.acc_code, 
           ps.pay_edesc, 
           A.employee_id, 
           round(A.total,2) as total, 
           case when ps.pay_type_flag = 'D' then 'CR' when ps.pay_type_flag = 'A' then 'DR'
           else fac.transaction_type end as transaction_type, 
           acm.company_code, 
           acm.branch_code, 
           107 as form_code
        from (select employee_id, pay_id, val as total from hris_salary_sheet_detail 
        where pay_id in (select pay_id from hris_acc_code_map ) 
        and sheet_no = {$sheetNo} 
        ) A 
        left join hris_acc_code_map ACM on (ACM.pay_id = A.pay_id)
        left join hris_pay_setup PS on (PS.pay_id = a.pay_id)
        left join fa_chart_of_accounts_setup fac on (fac.acc_code = ACM.acc_code and fac.company_code = acm.company_code)
        where fac.company_code = ({$companyCode}) and a.employee_id = {$employeeId} AND a.total <> 0 
        and acm.acc_code = {$accCode} and acm.branch_code = {$branchCode}";
//echo('<pre>');print_r($sql);
        $data = $this->rawQuery($sql);
//print_r($data[0]);die;
if($data){
	return $data[0];
}else{
return;
}
        
    }
    public function getBranchesFromCompany($data, $groupId){
        $companyCode = "select c.company_code from HRIS_SALARY_SHEET ss
        left join HRIS_COMPANY C ON( ss.company_id = c.company_id)
        where ss.sheet_no = {$data}";

        $sql = "select distinct branch_code from hris_acc_code_map where company_code = ({$companyCode}) and group_id = $groupId";
        $data = $this->rawQuery($sql);
        return $data;
    }
    public function getDataForDoubleVoucher($data, $branchCode, $groupId){
		//print_r('adsf');die;
        $companyCode = "select c.company_code from HRIS_SALARY_SHEET ss
        left join HRIS_COMPANY C ON( ss.company_id = c.company_id)
        where ss.sheet_no = {$data}";
        
        $companyId = "select company_id from hris_company where company_code = ({$companyCode})";

        $sql = "
        select d.*, v.order_no from (select distinct FN_NEW_VOUCHER_NO(({$companyCode}), 107, trunc(sysdate), 'FA_DOUBLE_VOUCHER') as VOUCHER_NO, 
        A.pay_id, 
        round(A.total,2) as amount, 
        acm.acc_code, 
        ps.pay_edesc, 
        case when ps.pay_type_flag = 'D' then 'CR' when ps.pay_type_flag = 'A' then 'DR'
        else fac.transaction_type end as transaction_type,
        acm.company_code, 
        acm.branch_code, 
        107 as form_code
        from (
            select ssd.pay_id, sum(ssd.val) as total, hacm.branch_code from hris_salary_sheet_detail ssd
            left join hris_acc_code_map hacm on (ssd.pay_id = hacm.pay_id) 
            where hacm.company_code=({$companyCode}) 
			and hacm.branch_code = '{$branchCode}' and hacm.group_id = {$groupId}
			and hacm.deleted_flag = 'N'
            and ssd.sheet_no = {$data} 
           -- and ssd.employee_id in (
            --                        select distinct e.employee_id from hris_employees e
             --                       left join hr_employee_setup es on (e.employee_id = es.employee_code)
             --                       
             --                       where es.branch_code = '{$branchCode}'
             --                       
             --                       )
            group by ssd.pay_id, hacm.branch_code) A 
        left join hris_acc_code_map ACM on (ACM.pay_id = A.pay_id)
        left join hris_pay_setup PS on (PS.pay_id = a.pay_id)
        left join fa_chart_of_accounts_setup fac on (fac.acc_code = ACM.acc_code and fac.company_code = acm.company_code)
        where fac.company_code = ({$companyCode}) and acm.branch_code = '{$branchCode}' and acm.group_id = {$groupId} and A.total <> 0 ) d
        left join hris_variance_payhead vp on (vp.pay_id = d.pay_id)
        left join hris_variance v on (v.variance_id = vp.variance_id and v.variable_type = 'S')
        where v.status = 'E'
        order by  d.transaction_type desc ,v.order_no asc";
		//echo '<pre>';print_r($sql);die;
        $result = $this->rawQuery($sql);
        return $result;
    }
    public function getDataForMasterTransection($voucher_no){

        $sql = "select voucher_no, sum(amount) as GROSS_AMOUNT, company_code, branch_code, form_code from fa_double_voucher where voucher_no = '{$voucher_no}' and transaction_type = 'CR'
        group by transaction_type, voucher_no, company_code, branch_code, form_code";
//echo '<pre>';print_r($sql);die;
        $data = $this->rawQuery($sql);
        return $data;
    }
    public function insertIntoFaSubLedger($singleSubDetailData){
        

        $sql = "insert into fa_sub_ledger
        (TRANSACTION_NO, FORM_CODE, VOUCHER_DATE, VOUCHER_NO, SUB_CODE, ACC_CODE, TRANSACTION_TYPE, DR_AMOUNT, CR_AMOUNT, BALANCE_AMOUNT, 
        BRANCH_CODE, COMPANY_CODE, CREATED_BY, CREATED_DATE, DELETED_FLAG, SERIAL_NO, CURRENCY_CODE, EXCHANGE_RATE, PARTICULARS )
        values(
        fn_transaction_unit_id({$singleSubDetailData['COMPANY_CODE']},'{$singleSubDetailData['FORM_CODE']}', trunc(sysdate)), 
        '{$singleSubDetailData['FORM_CODE']}',
        TRUNC(SYSDATE),
        '{$singleSubDetailData['VOUCHER_NO']}',
        '{$singleSubDetailData['SUB_CODE']}',
        '{$singleSubDetailData['ACC_CODE']}',
        '{$singleSubDetailData['TRANSACTION_TYPE']}',
        {$singleSubDetailData['DR_AMOUNT']},
        {$singleSubDetailData['CR_AMOUNT']},
        {$singleSubDetailData['DR_AMOUNT']} - {$singleSubDetailData['CR_AMOUNT']},
        '{$singleSubDetailData['BRANCH_CODE']}',
        '{$singleSubDetailData['COMPANY_CODE']}',
        '{$singleSubDetailData['CREATED_BY']}',
        '{$singleSubDetailData['CREATED_DATE']}',
        '{$singleSubDetailData['DELETED_FLAG']}',
        {$singleSubDetailData['SERIAL_NO']},
        'NRS',
        1,
        '{$singleSubDetailData['PARTICULARS']}'
        )
        ";
        return $this->rawQuery($sql);
    }
    public function insertIntoFaGeneralLedger($singleDoubleVoucherData,$generalVoucherblncAmt){
        if($singleDoubleVoucherData['TRANSACTION_TYPE'] == 'CR'){
            $sql = "insert into fa_general_ledger
            (TRANSACTION_NO, FORM_CODE, VOUCHER_DATE, VOUCHER_NO, ACC_CODE, TRANSACTION_TYPE, DR_AMOUNT, CR_AMOUNT, BALANCE_AMOUNT, 
            COMPANY_CODE, BRANCH_CODE, CREATED_BY, CREATED_DATE, DELETED_FLAG, SERIAL_NO, CURRENCY_CODE, EXCHANGE_RATE, PARTICULARS )
            values(
            fn_transaction_unit_id({$singleDoubleVoucherData['COMPANY_CODE']},'{$singleDoubleVoucherData['FORM_CODE']}', trunc(sysdate)), 
            '{$singleDoubleVoucherData['FORM_CODE']}',
            TRUNC(SYSDATE),
            '{$singleDoubleVoucherData['VOUCHER_NO']}',
            '{$singleDoubleVoucherData['ACC_CODE']}',
            '{$singleDoubleVoucherData['TRANSACTION_TYPE']}',
            0,
            {$singleDoubleVoucherData['AMOUNT']},
            {$generalVoucherblncAmt},
            '{$singleDoubleVoucherData['COMPANY_CODE']}',
            '{$singleDoubleVoucherData['BRANCH_CODE']}',            
            '{$singleDoubleVoucherData['CREATED_BY']}',
            '{$singleDoubleVoucherData['CREATED_DATE']}',
            '{$singleDoubleVoucherData['DELETED_FLAG']}',
            {$singleDoubleVoucherData['SERIAL_NO']},
            'NRS',
            1,
            '{$singleDoubleVoucherData['PARTICULARS']}'
            )
            ";
        }else{
            $sql = "insert into fa_general_ledger
            (TRANSACTION_NO, FORM_CODE, VOUCHER_DATE, VOUCHER_NO, ACC_CODE, TRANSACTION_TYPE, DR_AMOUNT, CR_AMOUNT, BALANCE_AMOUNT, 
            COMPANY_CODE, BRANCH_CODE, CREATED_BY, CREATED_DATE, DELETED_FLAG, SERIAL_NO, CURRENCY_CODE, EXCHANGE_RATE, PARTICULARS )
            values(
            fn_transaction_unit_id({$singleDoubleVoucherData['COMPANY_CODE']},'{$singleDoubleVoucherData['FORM_CODE']}', trunc(sysdate)), 
            '{$singleDoubleVoucherData['FORM_CODE']}',
            TRUNC(SYSDATE),
            '{$singleDoubleVoucherData['VOUCHER_NO']}',
            '{$singleDoubleVoucherData['ACC_CODE']}',
            '{$singleDoubleVoucherData['TRANSACTION_TYPE']}',
            {$singleDoubleVoucherData['AMOUNT']},
            0,
            {$generalVoucherblncAmt},
            '{$singleDoubleVoucherData['COMPANY_CODE']}',
            '{$singleDoubleVoucherData['BRANCH_CODE']}',            
            '{$singleDoubleVoucherData['CREATED_BY']}',
            '{$singleDoubleVoucherData['CREATED_DATE']}',
            '{$singleDoubleVoucherData['DELETED_FLAG']}',
            {$singleDoubleVoucherData['SERIAL_NO']},
            'NRS',
            1,
            '{$singleDoubleVoucherData['PARTICULARS']}'
            )
            ";
        }
        return $this->rawQuery($sql);        
    }
    public function insertIntoVoucherSubDetail($data,$employeeId,$indiEmployee){

        $voucherNo = $data['VOUCHER_NO'];
        $accCode = $data['ACC_CODE'];
        $serialNo = "select serial_no from fa_double_voucher where voucher_no = '{$voucherNo}' and acc_code = {$accCode}" ;
        $transactionType = $data['TRANSACTION_TYPE'];
        $amount = $data['TOTAL'];
        $formCode = $data['FORM_CODE'];
        $companyCode = $data['COMPANY_CODE'];
        $branchCode = $data['BRANCH_CODE'];
        $createdBy = "select upper(SUBSTR(first_name, 0, 1)) || upper(LAST_NAME) from HRIS_EMPLOYEES where employee_id = {$indiEmployee}";
        $particulars = "select particulars from fa_double_voucher where voucher_no = '{$voucherNo}' and acc_code = '{$accCode}'";

        if($transactionType == 'CR'){
								//echo '<pre>';print_r($data);die;
            $sql = "insert into fa_voucher_sub_detail 
            (VOUCHER_NO, COMPANY_CODE, BRANCH_CODE, FORM_CODE, SERIAL_NO, ACC_CODE, SUB_CODE, TRANSACTION_TYPE, DR_AMOUNT, CR_AMOUNT, CREATED_BY, CREATED_DATE, DELETED_FLAG, CURRENCY_CODE, EXCHANGE_RATE, PARTICULARS) 
            values
            ('{$voucherNo}', '{$companyCode}','{$companyCode}'||'.01','{$formCode}', ({$serialNo}), '{$accCode}', 'E' || '{$indiEmployee}', '{$transactionType}', 0, {$amount}, ({$createdBy}), trunc(sysdate), 'N', 'NRS', 1, ({$particulars}))";
        }else{

            $sql = "insert into fa_voucher_sub_detail 
            (VOUCHER_NO, COMPANY_CODE, BRANCH_CODE, FORM_CODE, SERIAL_NO, ACC_CODE, SUB_CODE, TRANSACTION_TYPE, DR_AMOUNT, CR_AMOUNT, CREATED_BY, CREATED_DATE, DELETED_FLAG, CURRENCY_CODE, EXCHANGE_RATE) 
            values
            ('{$voucherNo}', '{$companyCode}','{$companyCode}'||'.01','{$formCode}', ({$serialNo}), '{$accCode}', 'E' || '{$indiEmployee}', '{$transactionType}', {$amount}, 0, ({$createdBy}), trunc(sysdate), 'N', 'NRS', 1 )";
            }
							//echo '<pre>';print_r($sql);die;

        return $this->rawQuery($sql);
    }
    public function getDataOfSubDetails($voucherNum){
        $sql = "select * from fa_voucher_sub_detail where voucher_no = '{$voucherNum}'";
        return $this->rawQuery($sql);
    }
    public function getDataForPostedTransaction($voucherNum){
        $sql = "select * from master_transaction where voucher_no = '{$voucherNum}'";
        return $this->rawQuery($sql);
    }
    public function getDataOfDoubleVoucher($voucherNum){
        $sql = "select * from fa_double_voucher where voucher_no = '{$voucherNum}'";
        return $this->rawQuery($sql);
    }
    public function insertIntoDoubleVoucher($data, $employeeId, $sheetDetails){
        $voucherNo = $data['VOUCHER_NO'];
        $accCode = $data['ACC_CODE'];
        $payEdesc = $data['PAY_EDESC'];
        $serialNo = $data['SERIAL_NO'];
        $transactionType = $data['TRANSACTION_TYPE'];
        $amount = $data['AMOUNT'];
        $formCode = $data['FORM_CODE'];
        $companyCode = $data['COMPANY_CODE'];
        $branchCode = $data['BRANCH_CODE'];
        $createdBy = "select upper(SUBSTR(first_name, 0, 1)) || upper(LAST_NAME) from HRIS_EMPLOYEES where employee_id = {$employeeId}";

        $sql = "insert into FA_DOUBLE_VOUCHER 
        (VOUCHER_NO, VOUCHER_DATE, SERIAL_NO, ACC_CODE, TRANSACTION_TYPE, AMOUNT, FORM_CODE, COMPANY_CODE, BRANCH_CODE, CREATED_BY, CREATED_DATE,DELETED_FLAG, PARTICULARS, BUDGET_FLAG, MANUAL_NO) 
        values
        ('{$voucherNo}', trunc(sysdate),'{$serialNo}','{$accCode}', '{$transactionType}', {$amount}, '{$formCode}', '{$companyCode}', '{$companyCode}'||'.01', ({$createdBy}), trunc(sysdate), 'N', 'Salary Sheet ' || ' {$sheetDetails['SHEET_NO']} ' || '(' || '{$sheetDetails['SALARY_TYPE_NAME']}' || ' Salary) of ' || '{$sheetDetails['MONTH_EDESC']}' || ' ' || '{$sheetDetails['FISCAL_YEAR_NAME']}' || ' ' || '{$payEdesc}', 'E', '{$sheetDetails['SALARY_TYPE_NAME']}' || ' Salary')";
        // print_r($sql);die;
        return $this->rawQuery($sql);
    }
    public function insertIntoPostedTransaction($postedTransactioData){
        $sql = "insert into FA_POSTED_TRANSACTION
        (VOUCHER_NO, FORM_CODE, COMPANY_CODE, BRANCH_CODE, CREATED_BY, CREATED_DATE, DELETED_FLAG)
        values(
        '{$postedTransactioData['VOUCHER_NO']}',
        '{$postedTransactioData['FORM_CODE']}',
        '{$postedTransactioData['COMPANY_CODE']}',
        '{$postedTransactioData['BRANCH_CODE']}',
        '{$postedTransactioData['CREATED_BY']}',
        '{$postedTransactioData['CREATED_DATE']}',
        '{$postedTransactioData['DELETED_FLAG']}'
        )
        ";
        return $this->rawQuery($sql);
    }
    public function insertIntoMasterTransaction($masterTransactionData, $employeeId){

        $voucherNo = $masterTransactionData['VOUCHER_NO']; 
        $amount = $masterTransactionData['GROSS_AMOUNT'];
        $companyCode = $masterTransactionData['COMPANY_CODE'];
        $branchCode = $masterTransactionData['BRANCH_CODE'];
        $formCode = $masterTransactionData['FORM_CODE'];
        $createdBy = "select upper(SUBSTR(first_name, 0, 1)) || upper(LAST_NAME) from HRIS_EMPLOYEES where employee_id = {$employeeId}";

        $sql = "INSERT INTO master_transaction (
            voucher_no,
            voucher_amount,
            form_code,
            company_code,
            branch_code,
            created_by,
            created_date,
            deleted_flag,
            voucher_date,
            currency_code,
            exchange_rate,
            is_sync_with_ird,
            is_real_time
        ) VALUES (
            '{$voucherNo}',
            {$amount},
            '{$formCode}',
            '{$companyCode}',
            '{$companyCode}'||'.01',
            ({$createdBy}),
            TRUNC(SYSDATE),
            'N',
            TRUNC(SYSDATE),
            'NRS',
            1,
            'N',
            'N'
        )";
		//echo '<pre>';print_r($sql);die;

        return $this->rawQuery($sql);
    }
    public function getEmpName($empId){
        $sql = "select full_name from hris_employees where employee_id = {$empId}";
        $statement = $this->adapter->query($sql);
        $result = $statement->execute();
        return Helper::extractDbData($result)[0]['FULL_NAME'];
    }
    public function getAccDetails($accCode, $companyCode){
        $sql = "select fac.acc_edesc, 
                case when ps.pay_type_flag = 'D' then 'CR'
                else fac.transaction_type end as transaction_type
                from fa_chart_of_accounts_setup fac 
                left join HRIS_ACC_CODE_MAP acm
                on fac.acc_code = acm.acc_code
                left join HRIS_PAY_SETUP ps 
                on ps.pay_id = acm.pay_id where fac.acc_code = {$accCode} and fac.company_code = '{$companyCode}'";

        $statement = $this->adapter->query($sql);
        $result = $statement->execute();
        return $result->current();
    }

    public function getCompanyCode($data){
        $sql = "select c.company_code from HRIS_SALARY_SHEET ss
        left join HRIS_COMPANY C ON( ss.company_id = c.company_id)
        where ss.sheet_no = {$data}";

        $statement = $this->adapter->query($sql);
        $result = $statement->execute();

        return $result->current()['COMPANY_CODE'];
    }

    public function getGroupId($data){
        $sql = "select group_id from HRIS_SALARY_SHEET 
        where sheet_no = {$data}";

        $statement = $this->adapter->query($sql);
        $result = $statement->execute();

        return $result->current()['GROUP_ID'];
    }

    public function getSheetDetails($data){
        $sql = "select hss.sheet_no, hst.salary_type_name, hmc.month_edesc, hfy.fiscal_year_name from hris_salary_sheet hss
        left join hris_salary_type hst on (hss.salary_type_id = hst.salary_type_id)
        left join hris_month_code hmc on (hss.month_id = hmc.month_id)
        left join hris_fiscal_years hfy on (hmc.fiscal_year_id = hfy.fiscal_year_id)
        where hss.sheet_no = {$data}";

        return $this->rawQuery($sql);
    }

    public function checkValOfUnmapped($empId,$sheetNum,$payId){
        $sql = "select * from hris_salary_sheet_detail where sheet_no = {$sheetNum} and val <> 0 and employee_id = {$empId} and pay_id = {$payId}";
        
        $statement = $this->adapter->query($sql);
        $result = $statement->execute();
        $check =  $result->current();
        
        if ($check == null){
            return true;
        }else{
            return false;
        }
    }

    public function updateOtValue($detail,$empId){

        $sql="DELETE
        FROM
            hris_monthly_value_detail
        WHERE
                mth_value = 12
            AND fiscal_year_id = (select fiscal_year_id from hris_month_code where month_id=$detail[monthId])
            AND month_id = $detail[monthId]
            AND employee_id = $empId";
            $statement=$this->adapter->query($sql);
            $statement->execute();
        $sql="INSERT INTO hris_monthly_value_detail (
            mth_id,
            employee_id,
            mth_value,
            created_dt,
            modified_dt,
            fiscal_year_id,
            month_id
        ) VALUES (
            12,$empId,$detail[overtime],trunc(sysdate),NULL,(SELECT fiscal_year_id FROM hris_month_code WHERE month_id = $detail[monthId]),$detail[monthId])";

        $statement=$this->adapter->query($sql);
        $statement->execute();
    }

}
