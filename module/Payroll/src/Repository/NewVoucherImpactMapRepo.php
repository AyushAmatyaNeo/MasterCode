<?php

namespace Payroll\Repository;

use Application\Helper\EntityHelper;
use Application\Helper\Helper;
use Application\Model\Model;
use Application\Repository\HrisRepository;
use Payroll\Model\AccCodeMap;
use Zend\Db\Adapter\AdapterInterface;

class NewVoucherImpactMapRepo extends HrisRepository
{

    public function __construct(AdapterInterface $adapter, $tableName = null)
    {
        if ($tableName == null) {
            $tableName = AccCodeMap::TABLE_NAME;
        }
        parent::__construct($adapter, $tableName);
    }

    public function add(Model $model)
    {
        $temp = $model->getArrayCopyForDB();
        $sql = "DELETE FROM HRIS_ACC_CODE_MAP where pay_id = {$temp['PAY_ID']} and company_code = {$temp['COMPANY_CODE']} and branch_code = {$temp['BRANCH_CODE']} and functional_type_id = {$temp['FUNCTIONAL_TYPE_ID']}";
        // print_r($model->getArrayCopyForDB());die;
        $statement = $this->adapter->query($sql);
        $result = $statement->execute();
        return $this->tableGateway->insert($model->getArrayCopyForDB());
    }
    public function convertCompanyCodeToId($companyCode)
    {
        $sql = "select company_id from hris_company where company_code = {$companyCode}";
        $statement = $this->adapter->query($sql);
        $result = $statement->execute();
        return Helper::extractDbData($result)[0]['COMPANY_ID'];
    }
    public function fetchById($id)
    {
        return; //$this->tableGateway->select($id);
    }

    public function delete($id)
    {
        $sql = "delete from  HRIS_ACC_CODE_MAP where ID = $id";
        $this->rawQuery($sql);
        return;
    }

    public function deleteBy($by)
    {
        // return $this->tableGateway->delete($by);
    }

    public function getEmployeeDataList($data)
    {
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
                AND sla.company_code = {$data['company']}
        )
        AND e1.status = 'E'
        AND e1.company_id = (
            SELECT
                company_id
            FROM
                hris_company
            WHERE
                company_code = {$data['company']}
        ) ";
        // print_r($sql); die;
        $statement = $this->adapter->query($sql);

        $result = $statement->execute();
        return Helper::extractDbData($result);
    }
    public function getBranchList()
    {
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
    public function getAccHeadList()
    {

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

    public function getMappedAccCode($data)
    {
        // $dblinkSql = "select DBLINK_NAME from HRIS_COMPANYWISE_DBLINK where company_id = {$data['company']}";

        // $dblink = $this->rawQuery($dblinkSql);

        // if($dblink){
        // 	$dblinkName = $dblink[0]['DBLINK_NAME'];
        // }

        $sql = "select ACM.ID,C.COMPANY_NAME, FBS.BRANCH_EDESC, PS.PAY_EDESC, CAS.acc_edesc, 
        case when ps.pay_type_flag = 'D' then 'CR'
        else cas.transaction_type end as transaction_type,
        nvl(FT.functional_type_edesc,'N/A') as functional_type_edesc,
    case when acm.show_voucher_sub_detail = 'Y' THEN 'Yes' else 'No' end as show_voucher_sub_detail from hris_acc_code_map ACM
    left join hris_functional_types FT on (FT.functional_type_id = acm.functional_type_id)
			left join HRIS_PAY_SETUP PS on (PS.pay_id = ACM.pay_id)
			left join hris_company C on (C.company_code = ACM.company_code)
			left join fa_branch_setup FBS on (FBS.branch_code = ACM.branch_code)
			left join FA_CHART_OF_ACCOUNTS_SETUP CAS on (CAS.acc_code = ACM.acc_code and CAS.deleted_flag='N' and CAS.transaction_type in ('DR','CR'))
			where ACM.company_code = {$data['company']}  and CAS.company_code = {$data['company']} and C.status='E' order by case when ps.pay_type_flag = 'D' then 'CR'
            else cas.transaction_type end desc, ps.priority_index
		";
        // print_r($sql); die;
        $statement = $this->adapter->query($sql);

        $result = $statement->execute();
        return Helper::extractDbData($result);
    }
}
