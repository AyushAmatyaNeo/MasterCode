<?php
namespace Payroll\Repository;

use Application\Helper\EntityHelper;
use Application\Helper\Helper;
use Application\Model\Model;
use Application\Repository\HrisRepository;
use Payroll\Model\AccCodeMap;
use Zend\Db\Adapter\AdapterInterface;

class VoucherImpactMapRepo extends HrisRepository {

    public function __construct(AdapterInterface $adapter, $tableName = null) {
        if ($tableName == null) {
            $tableName = AccCodeMap::TABLE_NAME;
        }
        parent::__construct($adapter, $tableName);
    }

    public function add(Model $model) {
        $temp = $model->getArrayCopyForDB();
		$sql= "DELETE FROM HRIS_ACC_CODE_MAP where pay_id = {$temp['PAY_ID']} and company_code = {$temp['COMPANY_CODE']} and branch_code = {$temp['BRANCH_CODE']} and group_id = {$temp['GROUP_ID']}";
		$statement = $this->adapter->query($sql);
        $result = $statement->execute();
        return $this->tableGateway->insert($model->getArrayCopyForDB());
    }
    public function convertCompanyCodeToId($companyCode){
        $sql = "select company_id from hris_company where company_code = {$companyCode}";
        $statement = $this->adapter->query($sql);
        $result = $statement->execute();
        return Helper::extractDbData($result)[0]['COMPANY_ID'];
    }
    public function fetchById($id) {
        return; //$this->tableGateway->select($id);
    }

    public function delete($id) {
        $sql="delete from  HRIS_ACC_CODE_MAP where ID = $id";
        $this->rawQuery($sql);
        return;
    }

    public function deleteBy($by) {
        // return $this->tableGateway->delete($by);
    }

    public function getEmployeeDataList($data){
        $sql = "SELECT
        e1.employee_code,
        e1.full_name,
        e1.employee_id,
        e1.company_id,
        b.branch_name
    FROM
        hris_employees   e1
        LEFT JOIN hris_branches b ON ( e1.branch_id = b.branch_id )
    WHERE
        employee_id NOT IN (
            SELECT
                e.employee_id
            FROM
                hris_employees e
                LEFT JOIN fa_sub_ledger_map   sla ON ( sla.sub_code = ( 'E'|| e.employee_id ) )
            WHERE
                e.status = 'E'
                AND sla.acc_code = {$data['accHead']}
                AND sla.company_code = '{$data['company']}'
        )
        AND e1.status = 'E'
        AND e1.company_id = (
            SELECT
                company_id
            FROM
                hris_company
            WHERE
                company_code = '{$data['company']}'
        ) ";
        $statement = $this->adapter->query($sql);
        
        $result = $statement->execute();
        return Helper::extractDbData($result);
    }
    public function getBranchList(){
        $sql = "select 
        fbs.BRANCH_CODE,
        fbs.COMPANY_CODE,
        fbs.BRANCH_EDESC
        from fa_branch_setup fbs
        where fbs.DELETED_FLAG = 'N'";
        $statement = $this->adapter->query($sql);
        $result = $statement->execute();

        $allBranchName = [];
        foreach ($result as $allBranch) {
            $tempId = $allBranch['COMPANY_CODE'];
            (!array_key_exists($tempId, $allBranchName)) ?
                            $allBranchName[$tempId][0] = $allBranch :
                            array_push($allBranchName[$tempId], $allBranch);
        }

        return $allBranchName;
    }
    public function getAccHeadList() {
        $sql = "select 
                fac.ACC_CODE,
                fac.COMPANY_CODE,
                fac.ACC_EDESC
                from FA_CHART_OF_ACCOUNTS_SETUP fac
                where fac.DELETED_FLAG = 'N' ";
                        // echo '<pre>';print_r($sql);die;
        $statement = $this->adapter->query($sql);
        $result = $statement->execute();

        $allAccHeads = [];
        foreach ($result as $allAcc) {
            $tempId = $allAcc['COMPANY_CODE'];
            (!array_key_exists($tempId, $allAccHeads)) ?
                            $allAccHeads[$tempId][0] = $allAcc :
                            array_push($allAccHeads[$tempId], $allAcc);
        }

        return $allAccHeads;
    }

    public function getMappedAccCode($data){
		// $dblinkSql = "select DBLINK_NAME from HRIS_COMPANYWISE_DBLINK where company_id = {$data['company']}";

		// $dblink = $this->rawQuery($dblinkSql);
		
		// if($dblink){
		// 	$dblinkName = $dblink[0]['DBLINK_NAME'];
		// }
		
		$sql = "SELECT
        acm.id,
        c.company_name,
        fbs.branch_edesc as BRANCH_NAME,
        ps.pay_edesc as PAY_HEAD,
        cas.acc_edesc as ACCOUNT_HEAD,
        CASE
            WHEN ps.pay_type_flag = 'D' THEN
                'CR'
            ELSE
                cas.transaction_type
        END AS transaction_type,
        SG.GROUP_NAME as SALARY_GROUP
    FROM
        hris_acc_code_map            acm
        LEFT JOIN hris_pay_setup               ps ON ( ps.pay_id = acm.pay_id )
        LEFT JOIN hris_company                 c ON ( c.company_code = acm.company_code )
        LEFT JOIN fa_branch_setup              fbs ON ( fbs.branch_code = acm.branch_code)
        LEFT JOIN fa_chart_of_accounts_setup   cas ON ( cas.acc_code = acm.acc_code and cas.company_code = acm.company_code
                                                      AND cas.deleted_flag = 'N'
                                                      AND cas.transaction_type IN (
            'DR',
            'CR'
        ) )
        left join hris_salary_sheet_group SG on (SG.group_id = acm.group_id)
    WHERE
        acm.company_code = {$data['company']}
    ORDER BY
    SG.group_id desc,
            CASE
                WHEN ps.pay_type_flag = 'D' THEN
                    'CR'
                ELSE
                    cas.transaction_type
            END
        DESC,
            ps.priority_index
		";
		// echo '<pre>';print_r($sql); die;
		$statement = $this->adapter->query($sql);
		
		$result = $statement->execute();
		return Helper::extractDbData($result);
	}
}
