<?php
namespace Payroll\Service;

use Application\Helper\EntityHelper;
use Application\Helper\Helper;
use Payroll\Model\Rules;
use Payroll\Model\SalarySheet as SalarySheetModel;
use Payroll\Model\SalarySheetDetail as SalarySheetDetailModel;
use Payroll\Repository\RulesRepository;
use Payroll\Repository\SalarySheetDetailRepo;
use Payroll\Repository\SalarySheetRepo;
use Setup\Model\HrEmployees;

class SalarySheetService {

    private $adapter;
    private $salarySheetRepo;
    private $salarySheetDetailRepo;

    public function __construct($adapter) {
        $this->adapter = $adapter;
        $this->salarySheetRepo = new SalarySheetRepo($this->adapter);
        $this->salarySheetDetailRepo = new SalarySheetDetailRepo($this->adapter);
    }

    public function addSalarySheet(int $monthId) {
        $salarySheetModal = new SalarySheetModel();
        $salarySheetModal->sheetNo = ((int) Helper::getMaxId($this->adapter, SalarySheetModel::TABLE_NAME, SalarySheetModel::SHEET_NO)) + 1;
        $salarySheetModal->monthId = $monthId;
        $salarySheetModal->createdDt = Helper::getcurrentExpressionDate();
        $salarySheetModal->status = 'CR';

        if ($this->salarySheetRepo->add($salarySheetModal)) {
            return [SalarySheetModel::SHEET_NO => $salarySheetModal->sheetNo];
        } else {
            return null;
        }
    }

    public function deleteSalarySheet(int $monthId) {
        return $this->salarySheetRepo->delete($monthId);
    }

    public function addSalarySheetDetail(int $monthId, array $salarySheetDetails, int $salarySheet) {
        foreach ($salarySheetDetails as $empId => $salarySheetDetail) {
            $salarySheetDetailModel = new SalarySheetDetailModel($this->adapter);
            $salarySheetDetailModel->employeeId = $empId;
            $salarySheetDetailModel->sheetNo = $salarySheet;

            $payRepo = new RulesRepository($this->adapter);
            $payListRaw = $payRepo->fetchAll();

            $payList = Helper::extractDbData($payListRaw);

            foreach ($payList as $pay) {
                $payId = $pay[Rules::PAY_ID];
                $salarySheetDetailModel->payId = $payId;
                $salarySheetDetailModel->val = isset($salarySheetDetail['ruleValueKV'][$payId]) ? $salarySheetDetail['ruleValueKV'][$payId] : 0;
                $this->salarySheetDetailRepo->add($salarySheetDetailModel);
            }
        }
    }

    public function deleteSalarySheetDetail(int $monthId) {
        return $this->salarySheetDetailRepo->delete($monthId);
    }

    public function viewSalarySheet(int $sheetId) {
        return Helper::extractDbData($this->salarySheetDetailRepo->fetchSalarySheetDetail($sheetId));
    }

    public function viewSalarySheetEmp($monthId, $employeeId) {
        return $this->salarySheetDetailRepo->fetchSalarySheetEmp($monthId, $employeeId)->current();
    }

    public function checkIfGenerated(int $monthId) {
        $salarySheets = $this->salarySheetRepo->fetchByIds([SalarySheetModel::MONTH_ID => $monthId]);
        if ($salarySheets->count() > 0) {
            return true;
        } else {
            return false;
        }
    }

    private function findSalarySheetNo($monthId, $year, $monthNo, $fromDate, $toDate, $companyId, $groupId,$salaryTypeId) {
        $by = [
            SalarySheetModel::MONTH_ID => $monthId,
            SalarySheetModel::COMPANY_ID => $companyId,
            SalarySheetModel::GROUP_ID => $groupId,
            SalarySheetModel::SALARY_TYPE_ID => $salaryTypeId,
        ];
        $data = $this->salarySheetRepo->fetchOneBy($by);

        return $data == null ? null : $data[SalarySheetModel::SHEET_NO];
    }

    public function newSalarySheet($monthId, $year, $monthNo, $fromDate, $toDate, $companyId, $groupId,$salaryTypeId) {
        $sheetNo = $this->findSalarySheetNo($monthId, $year, $monthNo, $fromDate, $toDate, $companyId, $groupId,$salaryTypeId);
        if ($sheetNo != null) {
            return $sheetNo;
        }

        $salarySheetModal = new SalarySheetModel();
        $salarySheetModal->sheetNo = ((int) Helper::getMaxId($this->adapter, SalarySheetModel::TABLE_NAME, SalarySheetModel::SHEET_NO)) + 1;
        $salarySheetModal->monthId = $monthId;
        $salarySheetModal->year = $year;
        $salarySheetModal->monthNo = $monthNo;
        $salarySheetModal->startDate = Helper::getExpressionDate($fromDate);
        $salarySheetModal->endDate = Helper::getExpressionDate($toDate);
        $salarySheetModal->createdDt = Helper::getcurrentExpressionDate();
        $salarySheetModal->status = 'CR';
        $salarySheetModal->companyId = $companyId;
        $salarySheetModal->groupId = $groupId;
        $salarySheetModal->salaryTypeId = $salaryTypeId;

        $this->salarySheetRepo->add($salarySheetModal);
        return $salarySheetModal->sheetNo;
    }

    public function fetchEmployeeList($companyId, $groupId) {
        $rawList = EntityHelper::getTableList($this->adapter, HrEmployees::TABLE_NAME, [HrEmployees::EMPLOYEE_ID, HrEmployees::FULL_NAME], [HrEmployees::STATUS => EntityHelper::STATUS_ENABLED, HrEmployees::COMPANY_ID => $companyId, HrEmployees::GROUP_ID => $groupId]);
        return Helper::extractDbData($rawList);
    }
    
    public function fetchEmployeeListFiltered($companyId, $groupId) {
        $sql = "select employee_id,full_name from hris_employees where 
            status='E' and 
            group_id={$groupId} 
          
            and employee_id in (select EMPLOYEE_ID from HRIS_PAYROLL_EMP_LIST)";
        $statement = $this->adapter->query($sql);
        $iterator = $statement->execute();
        return iterator_to_array($iterator, false);
    }
    
    public function viewSalarySheetByGroupSheet($monthId,$groupId, $sheetNo,$salaryTypeId) {
        return Helper::extractDbData($this->salarySheetDetailRepo->fetchSalarySheetByGroupSheet($monthId,$groupId, $sheetNo,$salaryTypeId));
    }

}
