<?php

namespace SelfService\Controller;

use Application\Controller\HrisController;
use Exception;
use Payroll\Model\SalarySheetEmpDetail;
use Application\Custom\CustomViewModel;
use Payroll\Repository\SalarySheetDetailRepo;
use Payroll\Repository\SalSheEmpDetRepo;
use Payroll\Repository\TaxSheetRepo;
use Application\Model\FiscalYear;
use Zend\Authentication\Storage\StorageInterface;
use Zend\Db\Adapter\AdapterInterface;
use Zend\View\Model\JsonModel;
use Payroll\Repository\PayrollReportRepo;
use Payroll\Repository\SalarySheetRepo;
use Application\Helper\Helper;
use Application\Helper\EntityHelper;
use Application\Model\Months;
use Zend\Form\Element\Month;

class Payroll extends HrisController
{

    private $salarySheetRepo;
    public function __construct(AdapterInterface $adapter, StorageInterface $storage)
    {
        parent::__construct($adapter, $storage);
        $this->initializeRepository(PayrollReportRepo::class);
        $this->salarySheetRepo = new SalarySheetRepo($adapter);
    }

    public function payslipAction()
    {
        $salaryType = iterator_to_array($this->salarySheetRepo->fetchAllSalaryType(), false);
        $request = $this->getRequest();
        if ($request->isPost()) {
            try {
                $postedData = $request->getPost();
                $salarySheetDetailRepo = new SalarySheetDetailRepo($this->adapter);
                $salSheEmpDetRepo = new SalSheEmpDetRepo($this->adapter);
                $data['pay-detail'] = $salarySheetDetailRepo->fetchEmployeePaySlip($postedData['monthId'], $postedData['employeeId'], $postedData['salaryTypeId']);
                $data['emp-detail'] = $salSheEmpDetRepo->fetchOneByWithEmpDetailsNew($postedData['monthId'], $postedData['employeeId'], $postedData['salaryTypeId']);

                //                $data['emp-detail'] = $salSheEmpDetRepo->fetchOneBy([
                //                    SalarySheetEmpDetail::MONTH_ID => $postedData['monthId'],
                //                    SalarySheetEmpDetail::EMPLOYEE_ID => $postedData['employeeId']
                //                ]);
                // echo '<pre>';print_r($data['pay-detail']);die;
                return new JsonModel(['success' => true, 'data' => $data, 'error' => '']);
            } catch (Exception $e) {
                return new JsonModel(['success' => false, 'data' => [], 'error' => $e->getMessage()]);
            }
        }
        return ['employeeId' => $this->employeeId, 'salaryType' => json_encode($salaryType)];
    }

    public function taxslipAction()
    {
        $request = $this->getRequest();
        if ($request->isPost()) {
            try {
                $postedData = $request->getPost();
                $taxSheetRepo = new TaxSheetRepo($this->adapter);
                $data = $taxSheetRepo->fetchEmployeeTaxSlip($postedData['monthId'], $postedData['employeeId']);
                return new JsonModel(['success' => true, 'data' => $data, 'error' => '']);
            } catch (Exception $e) {
                return new JsonModel(['success' => false, 'data' => [], 'error' => $e->getMessage()]);
            }
        }
        return ['employeeId' => $this->employeeId, 'salaryType' => json_encode($salaryType)];
    }

    public function taxYearlyAction()
    {

        $incomes = $this->repository->gettaxYearlyByHeads('IN');
        $taxExcemptions = $this->repository->gettaxYearlyByHeads('TE');
        $otherTax = $this->repository->gettaxYearlyByHeads('OT');
        $miscellaneous = $this->repository->gettaxYearlyByHeads('MI');
        $bMiscellaneou = $this->repository->gettaxYearlyByHeads('BM');
        $cMiscellaneou = $this->repository->gettaxYearlyByHeads('CM');
        $sumOfExemption = $this->repository->gettaxYearlyByHeads('SE', 'sin');
        $sumOfOtherTax = $this->repository->gettaxYearlyByHeads('ST', 'sin');

        $salarySheetRepo = new SalarySheetRepo($this->adapter);
        $salaryType = iterator_to_array($salarySheetRepo->fetchAllSalaryType(), false);

        return Helper::addFlashMessagesToArray($this, [
            'searchValues' => EntityHelper::getSearchData($this->adapter),
            'salaryType' => $salaryType,
            'preference' => $this->preference,
            'incomes' => $incomes,
            'taxExcemptions' => $taxExcemptions,
            'otherTax' => $otherTax,
            'miscellaneous' => $miscellaneous,
            'bMiscellaneou' => $bMiscellaneou,
            'cMiscellaneou' => $cMiscellaneou,
            'sumOfExemption' => $sumOfExemption,
            'sumOfOtherTax' => $sumOfOtherTax
        ]);
    }

    public function pulltaxYearlyAction()
    {
        try {
            $request = $this->getRequest();
            $data = $request->getPost();
            $data['employeeId'] = [$this->employeeId];
            $resultData = $this->repository->getTaxYearlyNew($data);
            $result = [];
            $result['success'] = true;
            $result['data']['employees'] = Helper::extractDbData($resultData);
            $result['error'] = "";
            return new CustomViewModel($result);
        } catch (Exception $e) {
            return new CustomViewModel(['success' => false, 'data' => [], 'error' => $e->getMessage()]);
        }
    }

    public function salarySheetAction()
    {
        $request = $this->getRequest();
        if ($request->isPost()) {
            try {
                $request = $this->getRequest();
                $data = $request->getPost();
                $resultData = [];
                $data['employeeId'] = $this->employeeId;
                $groupId = $this->repository->getGroupId($this->employeeId);
                $data['groupId'] = $groupId['GROUP_ID'];
                // echo '<pre>';print_r($data);die;
                $defaultColumnsList = $this->repository->getDefaultColumnsempWise('S', $data);
                $resultData = $this->repository->getEmployeeWiseGroupReport('S', $data);
                $result = [];

                $result['success'] = true;
                $result['data'] = Helper::extractDbData($resultData);
                $result['columns'] = $defaultColumnsList;
                $result['error'] = "";
                return new CustomViewModel($result);
            } catch (Exception $e) {
                return new CustomViewModel(['success' => false, 'data' => [], 'error' => $e->getMessage()]);
            }
        }
        $fiscalYears = EntityHelper::getTableKVListWithSortOption($this->adapter, FiscalYear::TABLE_NAME, FiscalYear::FISCAL_YEAR_ID, [FiscalYear::FISCAL_YEAR_NAME], [FiscalYear::STATUS => 'E'], FiscalYear::FISCAL_YEAR_ID,  "DESC");
        // echo '<pre>';print_r($fiscalYears);die;
        return Helper::addFlashMessagesToArray($this, [
            'preference' => $this->preference,
            'fiscalYears' => $fiscalYears

        ]);
    }

    public function taxSheetAction()
    {

        $incomes = $this->repository->gettaxYearlyByHeads('IN');
        $taxExcemptions = $this->repository->gettaxYearlyByHeads('TE');
        $otherTax = $this->repository->gettaxYearlyByHeads('OT');
        $miscellaneous = $this->repository->gettaxYearlyByHeads('MI');
        $bMiscellaneou = $this->repository->gettaxYearlyByHeads('BM');
        $cMiscellaneou = $this->repository->gettaxYearlyByHeads('CM');
        $sumOfExemption = $this->repository->gettaxYearlyByHeads('SE', 'sin');
        $sumOfOtherTax = $this->repository->gettaxYearlyByHeads('ST', 'sin');

        $salaryType = iterator_to_array($this->salarySheetRepo->fetchAllSalaryType(), false);
        $request = $this->getRequest();
        if ($request->isPost()) {
            try {
                $postedData = $request->getPost();
                // echo '<pre>';print_r($postedData);die;
                $salarySheetDetailRepo = new SalarySheetDetailRepo($this->adapter);
                $salSheEmpDetRepo = new SalSheEmpDetRepo($this->adapter);
                $data['pay-detail'] = $salarySheetDetailRepo->fetchEmployeePaySlip($postedData['monthId'], $postedData['employeeId'], $postedData['salaryTypeId']);
                $data['emp-detail'] = $salSheEmpDetRepo->fetchOneByWithEmpDetailsNew($postedData['monthId'], $postedData['employeeId'], $postedData['salaryTypeId']);

                //                $data['emp-detail'] = $salSheEmpDetRepo->fetchOneBy([
                //                    SalarySheetEmpDetail::MONTH_ID => $postedData['monthId'],
                //                    SalarySheetEmpDetail::EMPLOYEE_ID => $postedData['employeeId']
                //                ]);
                return new JsonModel(['success' => true, 'data' => $data, 'error' => '']);
            } catch (Exception $e) {
                return new JsonModel(['success' => false, 'data' => [], 'error' => $e->getMessage()]);
            }
        }
        // echo '<pre>';print_r($taxExcemptions);die;

        return Helper::addFlashMessagesToArray($this, [
            'searchValues' => EntityHelper::getSearchDataPayroll($this->adapter),
            'salaryType' => $salaryType,
            'preference' => $this->preference,
            'incomes' => $incomes,
            'taxExcemptions' => $taxExcemptions,
            'otherTax' => $otherTax,
            'miscellaneous' => $miscellaneous,
            'bMiscellaneou' => $bMiscellaneou,
            'cMiscellaneou' => $cMiscellaneou,
            'sumOfExemption' => $sumOfExemption,
            'sumOfOtherTax' => $sumOfOtherTax,
            'acl' => $this->acl,
            'employeeDetail' => $this->storageData['employee_detail'],
        ]);
    }

    public function pulltaxYearlyEmpWiseAction()
    {
        try {
            $request = $this->getRequest();
            $data = $request->getPost();
            $data['employeeId'] = [$this->employeeId];
            $resultData = $this->repository->getTaxYearlyNew($data);
            $defaultColumnsList = $this->repository->getDefaultColumnsForTaxSheet();


            $result = [];
            $result['success'] = true;
            $result['data']['employees'] = Helper::extractDbData($resultData);
            $result['error'] = "";
            $result['columns'] = $defaultColumnsList;
            // echo '<pre>';print_r($result);die;

            return new CustomViewModel($result);
        } catch (Exception $e) {
            return new CustomViewModel(['success' => false, 'data' => [], 'error' => $e->getMessage()]);
        }
    }
}
