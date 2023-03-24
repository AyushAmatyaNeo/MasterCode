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

class PayrollReportController extends HrisController {

    public function __construct(AdapterInterface $adapter, StorageInterface $storage) {
        parent::__construct($adapter, $storage);
        $this->initializeRepository(PayrollReportRepo::class);
//        $this->initializeForm(Variance::class);
    }

    public function indexAction() {
        die();
        echo 'NO Action';
    }

    public function varianceAction() {
        $fiscalYears = EntityHelper::getTableList($this->adapter, FiscalYear::TABLE_NAME, [FiscalYear::FISCAL_YEAR_ID, FiscalYear::FISCAL_YEAR_NAME]);
        $months = EntityHelper::getTableList($this->adapter, Months::TABLE_NAME, [Months::MONTH_ID, Months::MONTH_EDESC, Months::FISCAL_YEAR_ID]);

        $columnsList = $this->repository->getVarianceColumns();
        return Helper::addFlashMessagesToArray($this, [
                    'searchValues' => EntityHelper::getSearchData($this->adapter),
                    'fiscalYears' => $fiscalYears,
                    'months' => $months,
                    'columnsList' => $columnsList,
                    'preference' => $this->preference,
					'acl' => $this->acl
        ]);
    }

    public function pullVarianceListAction() {
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

    public function gradeBasicAction() {
        $datas['otVariables'] = $this->repository->getGbVariables();
        $datas['monthList'] = $this->repository->getMonthList();

        return Helper::addFlashMessagesToArray($this, [
                    'searchValues' => EntityHelper::getSearchData($this->adapter),
                    'datas' => $datas,
                    'preference' => $this->preference,
					'acl' => $this->acl
        ]);
    }

    public function pullGradeBasicAction() {
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

    public function basicMonthlyReportAction() {
        $otVariables = $this->repository->getGbVariables();

        return Helper::addFlashMessagesToArray($this, [
                    'searchValues' => EntityHelper::getSearchData($this->adapter),
                    'otVariables' => $otVariables,
                    'preference' => $this->preference,
					'acl' => $this->acl
        ]);
    }

    public function basicMonthlyAction() {
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

    public function specialMonthlyReportAction() {
        $request = $this->getRequest();
        if ($request->isPost()) {
            $data = $request->getPost();
            $result = Helper::extractDbData($this->repository->getSpecialMonthly($data));
            return new JsonModel(['success' => true, 'data' => $result, 'message' => null]);
        }

        $otVariables = $this->repository->getGbVariables();

        return Helper::addFlashMessagesToArray($this, [
                    'searchValues' => EntityHelper::getSearchData($this->adapter),
                    'otVariables' => $otVariables,
                    'preference' => $this->preference
        ]);
    }

    // menu for this action not inserted
    public function groupSheetAction() {
        $nonDefaultList = $this->repository->getSalaryGroupColumns('S', 'N');
        $groupVariables = $this->repository->getSalaryGroupColumns('S');

        $salarySheetRepo = new SalarySheetRepo($this->adapter);
        $salaryType = iterator_to_array($salarySheetRepo->fetchAllSalaryType(), false);

        $data['salarySheetList'] = iterator_to_array($salarySheetRepo->fetchAll(), false);
        $links['getGroupListLink'] = $this->url()->fromRoute('payrollReport', ['action' => 'getGroupList']);
        $data['links'] = $links;

        return Helper::addFlashMessagesToArray($this, [
                    'searchValues' => EntityHelper::getSearchData($this->adapter),
                    'salaryType' => $salaryType,
//                    'fiscalYears' => $fiscalYears,
//                    'months' => $months,
                    'nonDefaultList' => $nonDefaultList,
                    'groupVariables' => $groupVariables,
                    'preference' => $this->preference,
                    'data' => json_encode($data),
					'acl' => $this->acl
        ]);
    }

    public function pullGroupSheetAction() {
        try {
            $request = $this->getRequest();
            $data = $request->getPost();
            $resultData = [];
            $reportType = $data['reportType'];
            $groupVariable = $data['groupVariable'];
            if ($reportType == "GS") {
                $defaultColumnsList = $this->repository->getDefaultColumns('S');
                $resultData = $this->repository->getGroupReport('S', $data);
            } elseif ($reportType == "GD") {
                $defaultColumnsList = $this->repository->getVarianceDetailColumns($groupVariable);
                $resultData = $this->repository->getGroupDetailReport($data);
            }
            
            $monthDetails=EntityHelper::rawQueryResult($this->adapter, "SELECT MONTH_EDESC,YEAR FROM HRIS_MONTH_CODE WHERE MONTH_ID=:monthId",['monthId'=>$data['monthId']])->current();
            
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
	
	public function employeeWiseGroupSheetAction() {
        $nonDefaultList = $this->repository->getSalaryGroupColumns('S', 'N');
        $groupVariables = $this->repository->getSalaryGroupColumns('S');

        $salarySheetRepo = new SalarySheetRepo($this->adapter);
        $salaryType = iterator_to_array($salarySheetRepo->fetchAllSalaryType(), false);

        $data['salarySheetList'] = iterator_to_array($salarySheetRepo->fetchAll(), false);
        $links['getGroupListLink'] = $this->url()->fromRoute('payrollReport', ['action' => 'getGroupList']);
        $data['links'] = $links;

        $fiscalYears = EntityHelper::getTableKVListWithSortOption($this->adapter, FiscalYear::TABLE_NAME,FiscalYear::FISCAL_YEAR_ID, [FiscalYear::FISCAL_YEAR_NAME], [FiscalYear::STATUS => 'E'], FiscalYear::FISCAL_YEAR_ID,  "DESC");
        return Helper::addFlashMessagesToArray($this, [
            'searchValues' => EntityHelper::getSearchData($this->adapter),
            'salaryType' => $salaryType,
            'fiscalYears' => $fiscalYears,
            'nonDefaultList' => $nonDefaultList,
            'groupVariables' => $groupVariables,
            'preference' => $this->preference,
            'data' => json_encode($data),
			'acl' => $this->acl
        ]);
    }

    public function pullemployeeWiseGroupSheetAction() {
        try {
            $request = $this->getRequest();
            $data = $request->getPost();
            $resultData = [];
            $groupVariable = $data['groupVariable'];

            $defaultColumnsList = $this->repository->getDefaultColumns('S');
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

    public function groupTaxReportAction() {
        $nonDefaultList = $this->repository->getSalaryGroupColumns('T', 'N');
        $groupVariables = $this->repository->getSalaryGroupColumns('T');

        $salarySheetRepo = new SalarySheetRepo($this->adapter);
        $salaryType = iterator_to_array($salarySheetRepo->fetchAllSalaryType(), false);

        return Helper::addFlashMessagesToArray($this, [
                    'searchValues' => EntityHelper::getSearchData($this->adapter),
                    'salaryType' => $salaryType,
//                    'fiscalYears' => $fiscalYears,
//                    'months' => $months,
                    'nonDefaultList' => $nonDefaultList,
                    'groupVariables' => $groupVariables,
                    'preference' => $this->preference,
                    'acl' => $this->acl,
        ]);
    }

    public function pullGroupTaxReportAction() {
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

    public function monthlySummaryAction() {
        $salarySheetRepo = new SalarySheetRepo($this->adapter);
        $salaryType = iterator_to_array($salarySheetRepo->fetchAllSalaryType(), false);
        return Helper::addFlashMessagesToArray($this, [
                    'searchValues' => EntityHelper::getSearchData($this->adapter),
                    'salaryType' => $salaryType,
                    'preference' => $this->preference,
					'acl' => $this->acl
        ]);
    }

    public function pullMonthlySummaryAction() {
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

    public function departmentWiseAction() {
        $ruleRepo = new RulesRepository($this->adapter);
        $ruleList = iterator_to_array($ruleRepo->fetchAll(), false);

        $salarySheetRepo = new SalarySheetRepo($this->adapter);
        $salaryType = iterator_to_array($salarySheetRepo->fetchAllSalaryType(), false);

        return Helper::addFlashMessagesToArray($this, [
                    'searchValues' => EntityHelper::getSearchData($this->adapter),
                    'preference' => $this->preference,
                    'ruleList' => $ruleList,
                    'salaryType' => $salaryType
        ]);
    }

    public function pulldepartmentWiseAction() {
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

    public function jvReportAction() {
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
                    'searchValues' => EntityHelper::getSearchData($this->adapter),
                    'preference' => $this->preference,
                    'ruleList' => $ruleList,
                    'salaryType' => $salaryType
        ]);
    }

    public function taxYearlyAction() {

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
                    'sumOfOtherTax' => $sumOfOtherTax,
                    'acl' => $this->acl
        ]);
    }

    public function pulltaxYearlyAction() {
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
	
	public function getGroupListAction() {
        $request = $this->getRequest();
        if ($request->isPost()) {
            try {
                $data = EntityHelper::getTableList($this->adapter, "HRIS_SALARY_SHEET_GROUP", ["GROUP_ID", "GROUP_NAME"]);
                return new JsonModel(['success' => true, 'data' => $data, 'error' => '']);
            } catch (Exception $e) {
                return new JsonModel(['success' => false, 'data' => [], 'error' => $e->getMessage()]);
            }
        }
    }

    public function pullGroupAction() {
        $salarySheetRepo = new SalarySheetRepo($this->adapter);
        try {
            $request = $this->getRequest();
            $data = $request->getPost();
            $group=$data['group'];
            $monthId=$data['monthId'];
            $salaryTypeId=$data['salaryTypeId'];

            $valuesinCSV = "";
            for ($i = 0; $i < sizeof($group); $i++) {
                $value= $group[$i];
//                $value = isString ? "'{$group[$i]}'" : $group[$i];
                if ($i + 1 == sizeof($group)) {
                    $valuesinCSV .= "{$value}";
                } else {
                    $valuesinCSV .= "{$value},";
                }
            }

            $sheetList= $salarySheetRepo->fetchGeneratedSheetByGroup($monthId,$valuesinCSV,$salaryTypeId);

            return new JsonModel(['success' => true, 'data' => $employeeList, 'sheetData' => $sheetList, 'message' => null]);
        } catch (Exception $e) {
            return new JsonModel(['success' => false, 'data' => null, 'message' => $e->getMessage()]);
        }
    }

    public function taxSheetAction() {

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
                    'sumOfOtherTax' => $sumOfOtherTax,
                    'acl' => $this->acl
        ]);
    }

    public function pulltaxSheetAction() {
        try {
            $request = $this->getRequest();
            $data = $request->getPost();
            $resultData = $this->repository->getTaxYearly($data);
            $defaultColumnsList = $this->repository->getDefaultColumnsForTaxSheet();


            $result = [];
            $result['success'] = true;
            $result['data']['employees'] = Helper::extractDbData($resultData);
            $result['error'] = "";
            $result['columns']=$defaultColumnsList;
            return new CustomViewModel($result);
        } catch (Exception $e) {
            return new CustomViewModel(['success' => false, 'data' => [], 'error' => $e->getMessage()]);
        }
    }

    public function annualSalarySheetAction() {
        $nonDefaultList = $this->repository->getSalaryGroupColumns('N', 'N');
        $groupVariables = $this->repository->getSalaryGroupColumns('N');

        $salarySheetRepo = new SalarySheetRepo($this->adapter);
        $salaryType = iterator_to_array($salarySheetRepo->fetchAllSalaryType(), false);

        $data['salarySheetList'] = iterator_to_array($salarySheetRepo->fetchAll(), false);
        $links['getGroupListLink'] = $this->url()->fromRoute('payrollReport', ['action' => 'getGroupList']);
        $data['links'] = $links;

        return Helper::addFlashMessagesToArray($this, [
                    'searchValues' => EntityHelper::getSearchData($this->adapter),
                    'salaryType' => $salaryType,
//                    'fiscalYears' => $fiscalYears,
//                    'months' => $months,
                    'nonDefaultList' => $nonDefaultList,
                    'groupVariables' => $groupVariables,
                    'preference' => $this->preference,
                    'data' => json_encode($data),
					'acl' => $this->acl
        ]);
    }

    public function pullAnnualSalarySheetAction() {
        try {
            $request = $this->getRequest();
            $data = $request->getPost();
            $resultData = [];
            $reportType = $data['reportType'];
            $groupVariable = $data['groupVariable'];
            if ($reportType == "GS") {
                $defaultColumnsList = $this->repository->getDefaultColumns('N');
                $resultData = $this->repository->getGroupReport('N', $data);
            } elseif ($reportType == "GD") {
                $defaultColumnsList = $this->repository->getVarianceDetailColumns($groupVariable);
                $resultData = $this->repository->getGroupDetailReport($data);
            }
            
            $monthDetails=EntityHelper::rawQueryResult($this->adapter, "SELECT MONTH_EDESC,YEAR FROM HRIS_MONTH_CODE WHERE MONTH_ID=:monthId",['monthId'=>$data['monthId']])->current();
            
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

    public function pullAnnualAction() {
        $salarySheetRepo = new SalarySheetRepo($this->adapter);
        try {
            $request = $this->getRequest();
            $data = $request->getPost();
            $group=$data['group'];
            $monthId=$data['monthId'];
            $salaryTypeId=$data['salaryTypeId'];

            $valuesinCSV = "";
            for ($i = 0; $i < sizeof($group); $i++) {
                $value= $group[$i];
//                $value = isString ? "'{$group[$i]}'" : $group[$i];
                if ($i + 1 == sizeof($group)) {
                    $valuesinCSV .= "{$value}";
                } else {
                    $valuesinCSV .= "{$value},";
                }
            }

            $sheetList= $salarySheetRepo->fetchGeneratedSheetByGroup($monthId,$valuesinCSV,$salaryTypeId);

            return new JsonModel(['success' => true, 'data' => $employeeList, 'sheetData' => $sheetList, 'message' => null]);
        } catch (Exception $e) {
            return new JsonModel(['success' => false, 'data' => null, 'message' => $e->getMessage()]);
        }
    }

    public function letterToBankAction() {

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
                    'sumOfOtherTax' => $sumOfOtherTax,
                    'bankType' => $bankType,
                    'acl' => $this->acl
        ]);
    }

    public function pullLetterToBankDetailAction() {
        try {
            $request = $this->getRequest();
            $data = $request->getPost();
            $resultData = $this->repository->getBankWiseEmployeeNet($data);

            $result = [];
            $result['success'] = true;
            $result['data']['employees'] = Helper::extractDbData($resultData);
            $result['error'] = "";
            return new CustomViewModel($result);
        } catch (Exception $e) {
            return new CustomViewModel(['success' => false, 'data' => [], 'error' => $e->getMessage()]);
        }
    }

}
