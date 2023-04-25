<?php

namespace Payroll\Controller;

use Application\Controller\HrisController;
use Application\Custom\CustomViewModel;
use Application\Helper\EntityHelper;
use Application\Helper\Helper;
use Application\Model\FiscalYear;
use Application\Model\Months;
use Exception;
use Payroll\Repository\PayrollReportRepo;
use Payroll\Repository\RulesRepository;
use Payroll\Repository\SalarySheetRepo;
use Zend\Authentication\Storage\StorageInterface;
use Zend\Db\Adapter\AdapterInterface;
use Zend\View\Model\JsonModel;

class PayrollReportController extends HrisController
{

    public function __construct(AdapterInterface $adapter, StorageInterface $storage)
    {
        parent::__construct($adapter, $storage);
        $this->initializeRepository(PayrollReportRepo::class);
        //        $this->initializeForm(Variance::class);
    }

    public function indexAction()
    {
        die();
        echo 'NO Action';
    }

    public function varianceAction()
    {
        $fiscalYears = EntityHelper::getTableList($this->adapter, FiscalYear::TABLE_NAME, [FiscalYear::FISCAL_YEAR_ID, FiscalYear::FISCAL_YEAR_NAME]);
        $months = EntityHelper::getTableList($this->adapter, Months::TABLE_NAME, [Months::MONTH_ID, Months::MONTH_EDESC, Months::FISCAL_YEAR_ID]);

        $columnsList = $this->repository->getVarianceColumns();
        return Helper::addFlashMessagesToArray($this, [
            'searchValues' => EntityHelper::getSearchDataPayroll($this->adapter),
            'fiscalYears' => $fiscalYears,
            'months' => $months,
            'columnsList' => $columnsList,
            'preference' => $this->preference,
            'acl' => $this->acl,
            'employeeDetail' => $this->storageData['employee_detail'],
        ]);
    }

    public function pullVarianceListAction()
    {
        try {
            $request = $this->getRequest();
            $data = $request->getPost();

            $results = $this->repository->getVarianceReprot($data);


            $result = [];
            $result['success'] = true;
            $result['data'] = Helper::extractDbData($results);
            $result['error'] = "";
            return new CustomViewModel($result);
        } catch (Exception $e) {
            return new CustomViewModel(['success' => false, 'data' => [], 'error' => $e->getMessage()]);
        }
    }

    public function gradeBasicAction()
    {
        $datas['otVariables'] = $this->repository->getGbVariables();
        $datas['monthList'] = $this->repository->getMonthList();

        return Helper::addFlashMessagesToArray($this, [
            'searchValues' => EntityHelper::getSearchDataPayroll($this->adapter),
            'datas' => $datas,
            'preference' => $this->preference,
            'acl' => $this->acl,
            'employeeDetail' => $this->storageData['employee_detail'],
        ]);
    }

    public function pullGradeBasicAction()
    {
        try {
            $request = $this->getRequest();
            $data = $request->getPost();
            $defaultColumnsList = $this->repository->getOtDefaultColumns();
            $reportType = $data['reportType'];
            if ($reportType == 'S') {
                $results = $this->repository->getGradeBasicSummary($data);
            } elseif ($reportType == 'D') {
                $results = $this->repository->getGradeBasicReport($data);
            } else {
                $results = $this->repository->getGradeBasicReport($data);
            }
            $result = [];
            $result['success'] = true;
            $result['data'] = Helper::extractDbData($results);
            $result['columns'] = $defaultColumnsList;
            $result['error'] = "";
            return new CustomViewModel($result);
        } catch (Exception $e) {
            return new CustomViewModel(['success' => false, 'data' => [], 'error' => $e->getMessage()]);
        }
    }

    public function basicMonthlyReportAction()
    {
        $otVariables = $this->repository->getGbVariables();

        return Helper::addFlashMessagesToArray($this, [
            'searchValues' => EntityHelper::getSearchDataPayroll($this->adapter),
            'otVariables' => $otVariables,
            'preference' => $this->preference,
            'acl' => $this->acl,
            'employeeDetail' => $this->storageData['employee_detail'],
        ]);
    }

    public function basicMonthlyAction()
    {
        try {
            $request = $this->getRequest();
            $data = $request->getPost();
            $defaultColumnsList = $this->repository->getOtMonthlyDefaultColumns($data['fiscalId']);
            $results = $this->repository->getBasicMonthly($data, $defaultColumnsList);
            $result = [];
            $result['success'] = true;
            $result['data'] = Helper::extractDbData($results);
            $result['columns'] = $defaultColumnsList;
            $result['error'] = "";
            return new CustomViewModel($result);
        } catch (Exception $e) {
            return new CustomViewModel(['success' => false, 'data' => [], 'error' => $e->getMessage()]);
        }
    }

    public function specialMonthlyReportAction()
    {
        $request = $this->getRequest();
        if ($request->isPost()) {
            $data = $request->getPost();
            $result = Helper::extractDbData($this->repository->getSpecialMonthly($data));
            return new JsonModel(['success' => true, 'data' => $result, 'message' => null]);
        }

        $otVariables = $this->repository->getGbVariables();

        return Helper::addFlashMessagesToArray($this, [
            'searchValues' => EntityHelper::getSearchDataPayroll($this->adapter),
            'otVariables' => $otVariables,
            'preference' => $this->preference,
            'acl' => $this->acl,
            'employeeDetail' => $this->storageData['employee_detail'],
        ]);
    }

    // menu for this action not inserted
    public function groupSheetAction()
    {
        $ruleRepo = new RulesRepository($this->adapter);
        $nonDefaultList = $this->repository->getSalaryGroupColumns('S', 'N');
        $groupVariables = $this->repository->getSalaryGroupColumns('S');

        $salarySheetRepo = new SalarySheetRepo($this->adapter);
        $salaryType = iterator_to_array($salarySheetRepo->fetchAllSalaryType(), false);

        $data['salarySheetList'] = iterator_to_array($salarySheetRepo->fetchAll(), false);
        $links['getGroupListLink'] = $this->url()->fromRoute('payrollReport', ['action' => 'getGroupList']);
        $data['links'] = $links;

        $companyWiseGroup = null;
        if ($this->acl['CONTROL_VALUES']) {
            if ($this->acl['CONTROL_VALUES'][0]['CONTROL'] == 'C') {
                $companyWiseGroup = $ruleRepo->getCompanyWise($this->acl['CONTROL_VALUES'][0]['VAL']);
            } else {
                $companyWiseGroup = null;
            }
        }
        // echo('<pre>');print_r(EntityHelper::getSearchDataPayroll($this->adapter));die;
        return Helper::addFlashMessagesToArray($this, [
            'searchValues' => EntityHelper::getSearchDataPayroll($this->adapter),
            'salaryType' => $salaryType,
            //                    'fiscalYears' => $fiscalYears,
            //                    'months' => $months,
            'nonDefaultList' => $nonDefaultList,
            'groupVariables' => $groupVariables,
            'preference' => $this->preference,
            'data' => json_encode($data),
            'acl' => $this->acl,
            'companyWiseGroup' => $companyWiseGroup,
            'employeeDetail' => $this->storageData['employee_detail'],
        ]);
    }

    public function pullGroupSheetAction()
    {
        try {
            $request = $this->getRequest();
            $data = $request->getPost();
            $resultData = [];
            $reportType = $data['reportType'];
            $groupVariable = $data['groupVariable'];
            if ($reportType == "GS") {
                // $defaultColumnsList = $this->repository->getDefaultColumns('S');
                $defaultColumnsList = $this->repository->getDefaultColumnsNew('S', $data['monthId'], $data['groupId'], $data['salaryTypeId']);
                $resultData = $this->repository->getGroupReport('S', $data);
            } elseif ($reportType == "GD") {
                $defaultColumnsList = $this->repository->getVarianceDetailColumns($groupVariable);
                $resultData = $this->repository->getGroupDetailReport($data);
            }

            $monthDetails = EntityHelper::rawQueryResult($this->adapter, "SELECT MONTH_EDESC,YEAR FROM HRIS_MONTH_CODE WHERE MONTH_ID=:monthId", ['monthId' => $data['monthId']])->current();

            $result = [];
            $result['success'] = true;
            $result['data'] = Helper::extractDbData($resultData);
            $result['columns'] = $defaultColumnsList;
            $result['monthDetails'] = $monthDetails;
            $result['error'] = "";
            return new CustomViewModel($result);
        } catch (Exception $e) {
            return new CustomViewModel(['success' => false, 'data' => [], 'error' => $e->getMessage()]);
        }
    }

    public function employeeWiseGroupSheetAction()
    {
        $ruleRepo = new RulesRepository($this->adapter);
        $nonDefaultList = $this->repository->getSalaryGroupColumns('S', 'N');
        $groupVariables = $this->repository->getSalaryGroupColumns('S');

        $salarySheetRepo = new SalarySheetRepo($this->adapter);
        $salaryType = iterator_to_array($salarySheetRepo->fetchAllSalaryType(), false);

        $data['salarySheetList'] = iterator_to_array($salarySheetRepo->fetchAll(), false);
        $links['getGroupListLink'] = $this->url()->fromRoute('payrollReport', ['action' => 'getGroupList']);
        $data['links'] = $links;

        $fiscalYears = EntityHelper::getTableKVListWithSortOption($this->adapter, FiscalYear::TABLE_NAME, FiscalYear::FISCAL_YEAR_ID, [FiscalYear::FISCAL_YEAR_NAME], [FiscalYear::STATUS => 'E'], FiscalYear::FISCAL_YEAR_ID,  "DESC");

        $companyWiseGroup = null;
        if ($this->acl['CONTROL_VALUES']) {
            // print_r($this->acl['CONTROL_VALUES']);die;
            if ($this->acl['CONTROL_VALUES'][0]['CONTROL'] == 'C') {
                $companyWiseGroup = $ruleRepo->getCompanyWise($this->acl['CONTROL_VALUES'][0]['VAL']);
            } else {
                $companyWiseGroup = null;
            }
        }
        // echo '<pre>';print_r($companyWiseGroup);die;
        return Helper::addFlashMessagesToArray($this, [
            'searchValues' => EntityHelper::getSearchDataPayroll($this->adapter),
            'salaryType' => $salaryType,
            'fiscalYears' => $fiscalYears,
            'nonDefaultList' => $nonDefaultList,
            'groupVariables' => $groupVariables,
            'preference' => $this->preference,
            'data' => json_encode($data),
            'acl' => $this->acl,
            'employeeDetail' => $this->storageData['employee_detail'],
            'companyWiseGroup' => $companyWiseGroup,
        ]);
    }

    public function pullemployeeWiseGroupSheetAction()
    {
        try {
            $request = $this->getRequest();
            $data = $request->getPost();
            $resultData = [];
            $groupVariable = $data['groupVariable'];
            // echo '<pre>';print_r($data);die;
            $defaultColumnsList = $this->repository->getDefaultColumnsEmployeeWise('S', $data);
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

    public function groupTaxReportAction()
    {
        $nonDefaultList = $this->repository->getSalaryGroupColumns('T', 'N');
        $groupVariables = $this->repository->getSalaryGroupColumns('T');

        $salarySheetRepo = new SalarySheetRepo($this->adapter);
        $salaryType = iterator_to_array($salarySheetRepo->fetchAllSalaryType(), false);

        return Helper::addFlashMessagesToArray($this, [
            'searchValues' => EntityHelper::getSearchDataPayroll($this->adapter),
            'salaryType' => $salaryType,
            //                    'fiscalYears' => $fiscalYears,
            //                    'months' => $months,
            'nonDefaultList' => $nonDefaultList,
            'groupVariables' => $groupVariables,
            'preference' => $this->preference,
            'acl' => $this->acl,
            'employeeDetail' => $this->storageData['employee_detail'],
        ]);
    }

    public function pullGroupTaxReportAction()
    {
        try {
            $request = $this->getRequest();
            $data = $request->getPost();
            $resultData = [];
            $reportType = $data['reportType'];
            $groupVariable = $data['groupVariable'];

            if ($reportType == "GS") {
                $defaultColumnsList = $this->repository->getDefaultColumns('T');
                $resultData = $this->repository->getGroupReport('T', $data);
            } elseif ($reportType == "GD") {
                $defaultColumnsList = $this->repository->getVarianceDetailColumns($groupVariable);
                $resultData = $this->repository->getGroupDetailReport($data);
            }
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

    public function monthlySummaryAction()
    {
        $salarySheetRepo = new SalarySheetRepo($this->adapter);
        $salaryType = iterator_to_array($salarySheetRepo->fetchAllSalaryType(), false);
        return Helper::addFlashMessagesToArray($this, [
            'searchValues' => EntityHelper::getSearchDataPayroll($this->adapter),
            'salaryType' => $salaryType,
            'preference' => $this->preference,
            'acl' => $this->acl,
            'employeeDetail' => $this->storageData['employee_detail'],
        ]);
    }

    public function pullMonthlySummaryAction()
    {
        try {
            $request = $this->getRequest();
            $data = $request->getPost();
            $resultData = [];
            $resultData['additionDetail'] = $this->repository->fetchMonthlySummary('A', $data);
            $resultData['deductionDetail'] = $this->repository->fetchMonthlySummary('D', $data);

            $result = [];
            $result['success'] = true;
            $result['data'] = $resultData;
            //            $result['columns'] = $defaultColumnsList;
            $result['error'] = "";
            return new CustomViewModel($result);
        } catch (Exception $e) {
            return new CustomViewModel(['success' => false, 'data' => [], 'error' => $e->getMessage()]);
        }
    }

    public function departmentWiseAction()
    {
        $ruleRepo = new RulesRepository($this->adapter);
        $ruleList = iterator_to_array($ruleRepo->fetchAll(), false);

        $salarySheetRepo = new SalarySheetRepo($this->adapter);
        $salaryType = iterator_to_array($salarySheetRepo->fetchAllSalaryType(), false);

        return Helper::addFlashMessagesToArray($this, [
            'searchValues' => EntityHelper::getSearchDataPayroll($this->adapter),
            'preference' => $this->preference,
            'ruleList' => $ruleList,
            'salaryType' => $salaryType,
            'acl' => $this->acl,
            'employeeDetail' => $this->storageData['employee_detail'],
        ]);
    }

    public function pulldepartmentWiseAction()
    {
        try {
            $request = $this->getRequest();
            $data = $request->getPost();
            //            $resultData = [];
            $resultData = $this->repository->pulldepartmentWise($data);
            //            $resultData['deductionDetail'] = $this->repository->fetchMonthlySummary('D', $data);

            $result = [];
            $result['success'] = true;
            $result['data'] = $resultData;
            //           $result['columns'] = $defaultColumnsList;
            $result['error'] = "";
            return new CustomViewModel($result);
        } catch (Exception $e) {
            return new CustomViewModel(['success' => false, 'data' => [], 'error' => $e->getMessage()]);
        }
    }

    public function jvReportAction()
    {
        $ruleRepo = new RulesRepository($this->adapter);
        $ruleList = iterator_to_array($ruleRepo->fetchAll(), false);

        $salarySheetRepo = new SalarySheetRepo($this->adapter);
        $salaryType = iterator_to_array($salarySheetRepo->fetchAllSalaryType(), false);
        $request = $this->getRequest();
        if ($request->isPost()) {
            try {
                $data = $request->getPost();
                $resultData = $this->repository->getJvReport($data);
                return new JSONModel(['success' => true, 'data' => $resultData, 'error' => '']);
            } catch (Exception $e) {
                return new JSONModel(['success' => false, 'data' => [], 'error' => '']);
            }
        }
        return Helper::addFlashMessagesToArray($this, [
            'searchValues' => EntityHelper::getSearchDataPayroll($this->adapter),
            'preference' => $this->preference,
            'ruleList' => $ruleList,
            'salaryType' => $salaryType,
            'acl' => $this->acl,
            'employeeDetail' => $this->storageData['employee_detail'],
        ]);
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

    public function taxYearlyNewAction()
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

    public function pulltaxYearlyAction()
    {
        try {
            $request = $this->getRequest();
            $data = $request->getPost();
            $resultData = $this->repository->getTaxYearly($data);


            $result = [];
            $result['success'] = true;
            $result['data']['employees'] = Helper::extractDbData($resultData);
            $result['error'] = "";
            return new CustomViewModel($result);
        } catch (Exception $e) {
            return new CustomViewModel(['success' => false, 'data' => [], 'error' => $e->getMessage()]);
        }
    }

    public function pulltaxYearlyNewAction()
    {
        try {
            $request = $this->getRequest();
            $data = $request->getPost();
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

    public function getGroupListAction()
    {
        $request = $this->getRequest();

        if ($request->isPost()) {
            try {
                $data = EntityHelper::getTableList($this->adapter, "HRIS_SALARY_SHEET_GROUP", ["GROUP_ID", "GROUP_NAME"]);

                // echo '<pre>';print_r($companyWiseGroup);die;         
                return new JsonModel(['success' => true, 'data' => $data, 'error' => '']);
            } catch (Exception $e) {
                return new JsonModel(['success' => false, 'data' => [], 'error' => $e->getMessage()]);
            }
        }
    }

    public function pullGroupAction()
    {
        $salarySheetRepo = new SalarySheetRepo($this->adapter);
        try {
            $request = $this->getRequest();
            $data = $request->getPost();
            $group = $data['group'];
            $monthId = $data['monthId'];
            $salaryTypeId = $data['salaryTypeId'];

            $valuesinCSV = "";
            for ($i = 0; $i < sizeof($group); $i++) {
                $value = $group[$i];
                //                $value = isString ? "'{$group[$i]}'" : $group[$i];
                if ($i + 1 == sizeof($group)) {
                    $valuesinCSV .= "{$value}";
                } else {
                    $valuesinCSV .= "{$value},";
                }
            }

            if ($this->acl['CONTROL_VALUES']) {
                if ($this->acl['CONTROL_VALUES'][0]['CONTROL'] == 'C') {
                    $companyId = $this->acl['CONTROL_VALUES'][0]['VAL'];
                    $sheetList = $salarySheetRepo->fetchGeneratedSheetByGroup($monthId, $valuesinCSV, $salaryTypeId, $companyId);
                } else {
                }
            } else {
                $companyId = 0;
                $sheetList = $salarySheetRepo->fetchGeneratedSheetByGroup($monthId, $valuesinCSV, $salaryTypeId, $companyId);
            }
            return new JsonModel(['success' => true, 'data' => $employeeList, 'sheetData' => $sheetList, 'message' => null]);
        } catch (Exception $e) {
            return new JsonModel(['success' => false, 'data' => null, 'message' => $e->getMessage()]);
        }
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



        $salarySheetRepo = new SalarySheetRepo($this->adapter);
        $salaryType = iterator_to_array($salarySheetRepo->fetchAllSalaryType(), false);

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

    public function taxSheetNewAction()
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

    public function pulltaxSheetAction()
    {
        try {
            $request = $this->getRequest();
            $data = $request->getPost();
            $resultData = $this->repository->getTaxYearly($data);
            $defaultColumnsList = $this->repository->getDefaultColumnsForTaxSheet();


            $result = [];
            $result['success'] = true;
            $result['data']['employees'] = Helper::extractDbData($resultData);
            $result['error'] = "";
            $result['columns'] = $defaultColumnsList;
            return new CustomViewModel($result);
        } catch (Exception $e) {
            return new CustomViewModel(['success' => false, 'data' => [], 'error' => $e->getMessage()]);
        }
    }

    public function pulltaxSheetNewAction()
    {
        try {
            $request = $this->getRequest();
            $data = $request->getPost();
            $resultData = $this->repository->getTaxYearlyNew($data);
            $defaultColumnsList = $this->repository->getDefaultColumnsForTaxSheet();


            $result = [];
            $result['success'] = true;
            $result['data']['employees'] = Helper::extractDbData($resultData);
            $result['error'] = "";
            $result['columns'] = $defaultColumnsList;
            return new CustomViewModel($result);
        } catch (Exception $e) {
            return new CustomViewModel(['success' => false, 'data' => [], 'error' => $e->getMessage()]);
        }
    }

    public function annualSalarySheetAction()
    {
        $nonDefaultList = $this->repository->getSalaryGroupColumns('N', 'N');
        $groupVariables = $this->repository->getSalaryGroupColumns('N');

        $salarySheetRepo = new SalarySheetRepo($this->adapter);
        $salaryType = iterator_to_array($salarySheetRepo->fetchAllSalaryType(), false);

        $data['salarySheetList'] = iterator_to_array($salarySheetRepo->fetchAll(), false);
        $links['getGroupListLink'] = $this->url()->fromRoute('payrollReport', ['action' => 'getGroupList']);
        $data['links'] = $links;
        $ruleRepo = new RulesRepository($this->adapter);

        $companyWiseGroup = null;
        if ($this->acl['CONTROL_VALUES']) {
            if ($this->acl['CONTROL_VALUES'][0]['CONTROL'] == 'C') {
                $companyWiseGroup = $ruleRepo->getCompanyWise($this->acl['CONTROL_VALUES'][0]['VAL']);
            } else {
                $companyWiseGroup = null;
            }
        }
        // echo '<pre>';print_r(EntityHelper::getSearchDataPayroll($this->adapter));die;
        return Helper::addFlashMessagesToArray($this, [
            'searchValues' => EntityHelper::getSearchDataPayroll($this->adapter),
            'salaryType' => $salaryType,
            //                    'fiscalYears' => $fiscalYears,
            //                    'months' => $months,
            'nonDefaultList' => $nonDefaultList,
            'groupVariables' => $groupVariables,
            'preference' => $this->preference,
            'data' => json_encode($data),
            'acl' => $this->acl,
            'companyWiseGroup' => $companyWiseGroup,
            'employeeDetail' => $this->storageData['employee_detail'],
        ]);
    }

    public function pullAnnualSalarySheetAction()
    {
        try {
            $request = $this->getRequest();
            $data = $request->getPost();
            $resultData = [];
            $reportType = $data['reportType'];
            $groupVariable = $data['groupVariable'];
            $returnList = [];
            if ($reportType == "GS") {
                $defaultColumnsList = [];
                $resultData = $this->repository->getAnnualSheetReport('N', $data);
                // echo '<pre>';print_r($resultData);die;

                $final = [];
                $employeeIdList = [];
                foreach ($resultData as $data) {
                    if (!in_array($data['EMPLOYEE_ID'], $employeeIdList)) {
                        array_push($employeeIdList, $data['EMPLOYEE_ID']);
                    }
                }

                foreach ($employeeIdList as $eId) {
                    $tempList = [];
                    $tempList['EMPLOYEE_ID'] = $eId;
                    foreach ($resultData as $data) {
                        if ($data['EMPLOYEE_ID'] == $eId) {
                            $tempList['ID_ACCOUNT_NO'] = $data['ID_ACCOUNT_NO'];
                            $tempList['EMPLOYEE_CODE'] = $data['EMPLOYEE_CODE'];
                            $tempList['COMPANY_NAME'] = $data['COMPANY_NAME'];
                            $tempList['BANK_NAME'] = $data['BANK_NAME'];
                            $tempList['FULL_NAME'] = $data['FULL_NAME'];
                            $tempList[$data['PAY_EDESC']] = $data['AMOUNT'];
                        }
                    }
                    array_push($final, $tempList);
                }

                foreach ($final as $data) {
                    $keys = array_keys($data);
                    foreach ($keys as $k) {
                        if ($k != 'EMPLOYEE_ID' && $k != 'ID_ACCOUNT_NO' && $k != 'EMPLOYEE_CODE' && $k != 'COMPANY_NAME' && $k != 'BANK_NAME' && $k != 'FULL_NAME')
                            if (!in_array(str_replace('/', '_', str_replace(' ', '', str_replace('(', '', str_replace(')', '', $k)))), $defaultColumnsList)) {
                                $defaultColumnsList[str_replace('/', '_', str_replace(' ', '', str_replace('(', '', str_replace(')', '', $k))))] = $k;
                            }
                    }
                }
                foreach ($final as $data) {
                    $keys = array_keys($data);
                    foreach ($defaultColumnsList as $defaultColumn) {
                        if (!in_array($defaultColumn, $keys)) {
                            $data[$defaultColumn] = 0;
                        }
                    }
                    array_push($returnList, $data);
                }
                $tempFinal = [];
                foreach ($returnList as $f) {
                    $tempFinalRow = [];
                    $tempFinalRow['EMPLOYEE_ID'] = $f['EMPLOYEE_ID'];
                    $tempFinalRow['ID_ACCOUNT_NO'] = $f['ID_ACCOUNT_NO'];
                    $tempFinalRow['EMPLOYEE_CODE'] = $f['EMPLOYEE_CODE'];
                    $tempFinalRow['COMPANY_NAME'] = $f['COMPANY_NAME'];
                    $tempFinalRow['BANK_NAME'] = $f['BANK_NAME'];
                    $tempFinalRow['FULL_NAME'] = $f['FULL_NAME'];
                    foreach ($defaultColumnsList as $k => $v) {
                        $tempFinalRow[$k] = $f[$v];
                    }
                    array_push($tempFinal, $tempFinalRow);
                }


                $returnList = $tempFinal;
                unset($tempFinal);
            } elseif ($reportType == "GD") {
                $defaultColumnsList = $this->repository->getVarianceDetailColumns($groupVariable);
                $resultData = $this->repository->getGroupDetailReport($data);
            }

            // $monthDetails=EntityHelper::rawQueryResult($this->adapter, "SELECT MONTH_EDESC,YEAR FROM HRIS_MONTH_CODE WHERE MONTH_ID=:monthId",['monthId'=>$data['monthId']])->current();
            $result = [];
            $result['success'] = true;
            $result['data'] = Helper::extractDbData($returnList);
            $result['columns'] = $defaultColumnsList;
            // $result['monthDetails'] = $monthDetails;
            $result['error'] = "";

            return new CustomViewModel($result);
        } catch (Exception $e) {
            return new CustomViewModel(['success' => false, 'data' => [], 'error' => $e->getMessage()]);
        }
    }

    public function pullAnnualAction()
    {
        $salarySheetRepo = new SalarySheetRepo($this->adapter);
        try {
            $request = $this->getRequest();
            $data = $request->getPost();
            $group = $data['group'];
            $monthId = $data['monthId'];
            $salaryTypeId = $data['salaryTypeId'];

            $valuesinCSV = "";
            for ($i = 0; $i < sizeof($group); $i++) {
                $value = $group[$i];
                //                $value = isString ? "'{$group[$i]}'" : $group[$i];
                if ($i + 1 == sizeof($group)) {
                    $valuesinCSV .= "{$value}";
                } else {
                    $valuesinCSV .= "{$value},";
                }
            }
            if ($this->acl['CONTROL_VALUES']) {
                if ($this->acl['CONTROL_VALUES'][0]['CONTROL'] == 'C') {
                    $companyId = $this->acl['CONTROL_VALUES'][0]['VAL'];
                    $sheetList = $salarySheetRepo->fetchGeneratedSheetByGroup($monthId, $valuesinCSV, $salaryTypeId, $companyId);
                } else {
                }
            } else {
                $companyId = 0;
                $sheetList = $salarySheetRepo->fetchGeneratedSheetByGroup($monthId, $valuesinCSV, $salaryTypeId, $companyId);
            }
            // echo '<pre>';print_r($sheetList);die;
            return new JsonModel(['success' => true, 'data' => $employeeList, 'sheetData' => $sheetList, 'message' => null]);
        } catch (Exception $e) {
            return new JsonModel(['success' => false, 'data' => null, 'message' => $e->getMessage()]);
        }
    }

    public function letterToBankAction()
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
        $bankType = $this->repository->getBankType();

        $controlValue = $this->acl['CONTROL_VALUES'][0]['CONTROL'];

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
            'bankType' => $bankType,
            'acl' => $this->acl,
            'employeeDetail' => $this->storageData['employee_detail'],
            'controlValues' => $controlValue
        ]);
    }

    public function pullLetterToBankDetailAction()
    {
        try {
            $request = $this->getRequest();
            $data = $request->getPost();
            $ruleRepo = new RulesRepository($this->adapter);
            $companyWiseGroup = null;
            if ($this->acl['CONTROL_VALUES']) {
                if ($this->acl['CONTROL_VALUES'][0]['CONTROL'] == 'C') {
                    $empId = $this->employeeId;
                    $companyDetail = $ruleRepo->getCompany($this->acl['CONTROL_VALUES'][0]['VAL']);
                    $data['companyId'] = $companyDetail[0]['COMPANY_ID'];
                    $resultData = $this->repository->getBankWiseEmployeeNet($data);
                } else {
                    $companyDetail = null;
                }
            }else {
                $data['companyId'] = $data['companyId'];
                $resultData = $this->repository->getBankWiseEmployeeNet($data);
            }
            $result = [];
            $result['success'] = true;
            $result['data']['employees'] = Helper::extractDbData($resultData);
            $result['error'] = "";
            $result['companyDetail'] = $companyDetail;
            // echo '<pre>';print_r($result);die;
            return new CustomViewModel($result);
        } catch (Exception $e) {
            return new CustomViewModel(['success' => false, 'data' => [], 'error' => $e->getMessage()]);
        }
    }
}
