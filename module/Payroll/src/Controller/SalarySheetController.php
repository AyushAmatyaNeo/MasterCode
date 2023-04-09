<?php

namespace Payroll\Controller;

use Application\Controller\HrisController;
use Application\Helper\EntityHelper;
use Application\Helper\Helper;
use Application\Repository\MonthRepository;
use Exception;
use Payroll\Model\SalarySheet;
use Payroll\Model\SalarySheetDetail;
use Payroll\Model\TaxSheet;
use Payroll\Repository\PayrollRepository;
use Payroll\Repository\RulesRepository;
use Payroll\Repository\SalarySheetDetailRepo;
use Payroll\Repository\SalarySheetRepo;
use Payroll\Repository\SalSheEmpDetRepo;
use Payroll\Repository\SSPayValueModifiedRepo;
use Payroll\Repository\TaxSheetRepo;
use Payroll\Service\PayrollGenerator;
use Payroll\Service\SalarySheetService;
use Setup\Model\HrEmployees;
use Zend\Authentication\Storage\StorageInterface;
use Zend\Db\Adapter\AdapterInterface;
use Zend\View\Model\JsonModel;
use Application\Model\FiscalYear;
use Application\Model\Months;

class SalarySheetController extends HrisController
{

    private $salarySheetRepo;

    public function __construct(AdapterInterface $adapter, StorageInterface $storage)
    {
        parent::__construct($adapter, $storage);
        $this->initializeRepository(PayrollRepository::class);
        $this->salarySheetRepo = new SalarySheetRepo($adapter);
    }

    public function indexAction()
    {
        $ruleRepo = new RulesRepository($this->adapter);
        $data['salaryType'] = iterator_to_array($this->salarySheetRepo->fetchAllSalaryType(), false);
        $data['ruleList'] = iterator_to_array($ruleRepo->fetchAll(), false);
        $data['salarySheetList'] = iterator_to_array($this->salarySheetRepo->fetchAll(), false);
        $links['viewLink'] = $this->url()->fromRoute('salarySheet', ['action' => 'viewSalarySheet']);
        $links['generateLink'] = $this->url()->fromRoute('salarySheet', ['action' => 'generateSalarySheet']);
        $links['getSalarySheetListLink'] = $this->url()->fromRoute('salarySheet', ['action' => 'getSalarySheetList']);
        $links['getSearchDataLink'] = $this->url()->fromRoute('salarySheet', ['action' => 'getSearchData']);
        $links['getGroupListLink'] = $this->url()->fromRoute('salarySheet', ['action' => 'getGroupList']);
        $links['regenEmpSalSheLink'] = $this->url()->fromRoute('salarySheet', ['action' => 'regenEmpSalShe']);
        $links['deleteEmpSalSheLink'] = $this->url()->fromRoute('salarySheet', ['action' => 'deleteEmpSalShe']);
        $data['links'] = $links;
        $companyWiseGroup = null;
        if ($this->acl['CONTROL_VALUES']) {
            if ($this->acl['CONTROL_VALUES'][0]['CONTROL'] == 'C') {
                $companyWiseGroup = $ruleRepo->getCompanyWise($this->acl['CONTROL_VALUES'][0]['VAL']);
            } else {
                $companyWiseGroup = null;
            }
        }
        // echo '<pre>';print_r($companyWiseGroup);die;
        return Helper::addFlashMessagesToArray($this, [
            'data' => json_encode($data),
            'acl' => $this->acl,
            'companyWiseGroup' => $companyWiseGroup,
        ]);
        //return $this->stickFlashMessagesTo(['data' => json_encode($data)]);
    }

    public function getSalarySheetListAction()
    {
        try {
            $list = iterator_to_array($this->salarySheetRepo->fetchAll(), false);
            return new JsonModel(['success' => true, 'data' => $list, 'error' => '']);
        } catch (Exception $e) {
            return new JsonModel(['success' => false, 'data' => [], 'error' => ['message' => $e->getMessage(), 'trace' => $e->getTraceAsString()]]);
        }
    }

    public function viewSalarySheetAction()
    {
        $request = $this->getRequest();
        $data = $request->getPost();
        //        $sheetNoList = $data['sheetNo'];
        $monthId = $data['monthId'];
        $sheetNo = $data['sheetNo'];
        $groupId = $data['groupId'];
        $salaryTypeId = $data['salaryTypeId'];
        $salarySheetController = new SalarySheetService($this->adapter);
        //        $salarySheetList = [];
        //        foreach ($sheetNoList as $sheetNo) {
        //            $salarySheet = $salarySheetController->viewSalarySheet($sheetNo);
        //            $salarySheetList = array_merge($salarySheetList, $salarySheet);
        //        }
        if ($this->acl['CONTROL_VALUES']) {
            if ($this->acl['CONTROL_VALUES'][0]['CONTROL'] == 'C') {
                $companyId = $this->acl['CONTROL_VALUES'][0]['VAL'];
                // echo '<pre>';print_r($valuesinCSV);die;
                $salarySheetList = $salarySheetController->viewSalarySheetByGroupSheet($monthId, $groupId, $sheetNo, $salaryTypeId, $companyId);
            } else {
            }
        } else {
            $companyId = 0;
            $salarySheetList = $salarySheetController->viewSalarySheetByGroupSheet($monthId, $groupId, $sheetNo, $salaryTypeId, $companyId);
        }

        return new JsonModel(['success' => true, 'data' => $salarySheetList, 'error' => '']);
    }

    public function generateSalarySheetAction()
    {
        $salarySheet = new SalarySheetService($this->adapter);
        $salarySheetDetailRepo = new SalarySheetDetailRepo($this->adapter);
        $taxSheetRepo = new TaxSheetRepo($this->adapter);
        try {
            $request = $this->getRequest();
            $data = $request->getPost();
            $stage = $data['stage'];
            if($data['overtime']!=null){
                foreach ($data['empList'] as $empId){
                    $this->salarySheetRepo->updateOValue($data,$empId);
                }
            }
            $returnData = null;
            switch ($stage) {
                case 1:
                    $monthId = $data['monthId'];
                    $year = $data['year'];
                    $monthNo = $data['monthNo'];
                    $fromDate = $data['fromDate'];
                    $toDate = $data['toDate'];
                    $companyIdList = $data['companyId'];
                    $groupIdList = $data['groupId'];
                    $salaryTypeId = $data['salaryTypeId'];
                    $empList = $data['empList'];
                    /*  */
                    /*  */
                    $returnData = [];
                    $groupListArray = $this->salarySheetRepo->insertPayrollEmp($empList, $monthId, $salaryTypeId);

                    $groupToGenerate = [];
                    foreach ($groupListArray as $list) {
                        array_push($groupToGenerate, $list['GROUP_ID']);
                    }
                    //                    foreach ($companyIdList as $companyId) {
                    //                        foreach ($groupIdList as $groupId) {
                    foreach ($groupToGenerate as $groupId) {
                        $companyId = $this->salarySheetRepo->fetchCompanyByGroup($groupId);
                        $sheetNo = $salarySheet->newSalarySheet($monthId, $year, $monthNo, $fromDate, $toDate, $companyId, $groupId, $salaryTypeId);
                        $this->salarySheetRepo->generateSalShReport($sheetNo);
                        //                            $salarySheetDetailRepo->delete($sheetNo);
                        //                            $taxSheetRepo->delete($sheetNo);
                        //                            $employeeList = $salarySheet->fetchEmployeeList($companyId, $groupId);
                        $employeeList = $salarySheet->fetchEmployeeListFiltered($companyId, $groupId);
                        //                            print_r($employeeList);
                        //                            die();
                        $data = null;
                        $data['sheetNo'] = $sheetNo;
                        $data['employeeList'] = $employeeList;
                        array_push($returnData, $data);
                    }
                    //                    }
                    break;
                case 2:

                    $employeeId = $data['employeeId'];
                    $monthId = $data['monthId'];
                    $sheetNo = $data['sheetNo'];
                    $payrollGenerator = new PayrollGenerator($this->adapter);
                    $returnData = $payrollGenerator->generate($employeeId, $monthId, $sheetNo);
                    // echo '<pre>';
                    // print_r($returnData);
                    // die;
                    $salarySheetDetail = new SalarySheetDetail();
                    $salarySheetDetail->sheetNo = $sheetNo;
                    $salarySheetDetail->employeeId = $employeeId;

                    foreach ($returnData['ruleValueKV'] as $key => $value) {
                        $salarySheetDetail->payId = $key;
                        $salarySheetDetail->val = $value;
                        $salarySheetDetailRepo->add($salarySheetDetail);
                    }

                    $taxSheet = new TaxSheet();
                    $taxSheet->sheetNo = $sheetNo;
                    $taxSheet->employeeId = $employeeId;
                    foreach ($returnData['ruleTaxValueKV'] as $key => $value) {
                        $taxSheet->payId = $key;
                        $taxSheet->val = $value;
                        $taxSheetRepo->add($taxSheet);
                    }
                    //to update loan Pyamnet Flag of employee start
                    $this->salarySheetRepo->updateLoanPaymentFlag($employeeId, $sheetNo);
                    //to update loan Pyamnet Flag of employee end
                    break;
                case 3:
                    break;
            }

            return new JsonModel(['success' => true, 'data' => $returnData, 'error' => '']);
        } catch (Exception $e) {
            return new JsonModel(['success' => false, 'data' => null, 'message' => $e->getMessage(), 'stackTrace' => $e->getTrace()]);
        }
    }

    public function regenEmpSalSheAction()
    {
        try {
            $salarySheetDetailRepo = new SalarySheetDetailRepo($this->adapter);
            $taxSheetRepo = new TaxSheetRepo($this->adapter);
            $request = $this->getRequest();
            $data = $request->getPost();
            $employeeId = $data['employeeId'];
            $monthId = $data['monthId'];
            $sheetNo = $data['sheetNo'];

            $checkData = $this->salarySheetRepo->checkApproveLock($sheetNo);
            if ($checkData[0]['LOCKED'] == 'Y' || $checkData[0]['APPROVED'] == 'Y') {
                throw new Exception('Cant Regenerate approved or locked');
            }

            $salarySheetDetailRepo->deleteBy([SalarySheetDetail::SHEET_NO => $sheetNo, SalarySheetDetail::EMPLOYEE_ID => $employeeId]);
            $taxSheetRepo->deleteBy([TaxSheet::SHEET_NO => $sheetNo, TaxSheet::EMPLOYEE_ID => $employeeId]);
            $payrollGenerator = new PayrollGenerator($this->adapter);

            EntityHelper::rawQueryResult($this->adapter, "
                    BEGIN 
                        HRIS_UPDATE_SAL_EMP_DTL({$sheetNo}, {$employeeId});
                    END;
                ");
            $returnData = $payrollGenerator->generate($employeeId, $monthId, $sheetNo);

            $salarySheetDetail = new SalarySheetDetail();
            $salarySheetDetail->sheetNo = $sheetNo;
            $salarySheetDetail->employeeId = $employeeId;

            foreach ($returnData['ruleValueKV'] as $key => $value) {
                $salarySheetDetail->payId = $key;
                $salarySheetDetail->val = ($value) ? $value : 0;
                $salarySheetDetailRepo->add($salarySheetDetail);
            }

            $taxSheet = new TaxSheet();
            $taxSheet->sheetNo = $sheetNo;
            $taxSheet->employeeId = $employeeId;
            foreach ($returnData['ruleTaxValueKV'] as $key => $value) {
                $taxSheet->payId = $key;
                $taxSheet->val = $value;
                $taxSheetRepo->add($taxSheet);
            }

            return new JsonModel(['success' => true, 'data' => $returnData, 'error' => '']);
        } catch (Exception $e) {
            return new JsonModel(['success' => false, 'data' => null, 'message' => $e->getMessage(), 'stackTrace' => $e->getTrace()]);
        }
    }

    public function generateMonthlySheetAction()
    {
        try {
            $request = $this->getRequest();
            $data = $request->getPost();

            $monthId = $data['month'];
            $regenerateFlag = ($data['regenerateFlag'] == "true") ? 1 : 0;

            $monthRepo = new MonthRepository($this->adapter);
            $monthDetail = $monthRepo->fetchByMonthId($monthId);

            $salarySheetController = new SalarySheetService($this->adapter);
            $payrollRepo = new PayrollRepository($this->adapter);
            $employeeList = $payrollRepo->fetchEmployeeList();

            if ($regenerateFlag) {
                $salarySheetController->deleteSalarySheetDetail($monthId);
                $salarySheetController->deleteSalarySheet($monthId);
            }
            if (!($salarySheetController->checkIfGenerated($monthId))) {
                $salarySheetDetails = [];
                foreach ($employeeList as $employee) {
                    $generateMonthlySheet = new PayrollGenerator($this->adapter, $monthId);
                    $result = $generateMonthlySheet->generate($employee['EMPLOYEE_ID']);
                    $salarySheetDetails[$employee['EMPLOYEE_ID']] = $result;
                }
                $addSalarySheetRes = $salarySheetController->addSalarySheet($monthId);
                if ($addSalarySheetRes == null) {
                    throw new Exception('Salary Sheet is null');
                }
                $salarySheetController->addSalarySheetDetail($monthId, $salarySheetDetails, $addSalarySheetRes[SalarySheet::SHEET_NO]);
            }
            $results = $salarySheetController->viewSalarySheet($monthId, $employeeList);

            return new JsonModel(['success' => true, 'data' => $results, 'error' => '']);
        } catch (Exception $e) {
            return new JsonModel(['success' => false, 'data' => [], 'error' => ['message' => $e->getMessage(), 'trace' => $e->getTraceAsString()]]);
        }
    }

    public function pullPayRollGeneratedMonthsAction()
    {
        try {
            $request = $this->getRequest();
            $data = $request->getPost();


            $employeeId = null;
            $joinDate = null;
            if (isset($data['employeeId'])) {
                $employeeId = $data['employeeId'];
            }
            if ($employeeId != null) {
                $result = EntityHelper::getTableKVList($this->adapter, HrEmployees::TABLE_NAME, null, [HrEmployees::JOIN_DATE], [HrEmployees::EMPLOYEE_ID => $employeeId], null, null);
                if (sizeof($result) > 0) {
                    $joinDate = $result[0];
                }
            }
            $salarySheetRepo = new SalarySheetRepo($this->adapter);
            $generatedSalarySheets = Helper::extractDbData($salarySheetRepo->joinWithMonth(null, $joinDate));

            return new JsonModel(['success' => true, 'data' => $generatedSalarySheets, 'message' => null]);
        } catch (Exception $e) {
            return new JsonModel(['success' => false, 'data' => null, 'message' => $e->getMessage()]);
        }
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
                $data['emp-detail'] = $salSheEmpDetRepo->fetchOneByWithEmpDetails($postedData['monthId'], $postedData['employeeId']);
                return new JsonModel(['success' => true, 'data' => $data, 'error' => '']);
            } catch (Exception $e) {
                return new JsonModel(['success' => false, 'data' => [], 'error' => $e->getMessage()]);
            }
        }
        return $this->stickFlashMessagesTo(['salaryType' => json_encode($salaryType)]);
    }

    public function getGroupListAction()
    {
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

    public function payValueModifiedAction()
    {
        $data['getSearchDataLink'] = $this->url()->fromRoute('salarySheet', ['action' => 'getSearchData']);
        $data['getGroupListLink'] = $this->url()->fromRoute('salarySheet', ['action' => 'getGroupList']);
        $data['getFiscalYearMonthLink'] = $this->url()->fromRoute('salarySheet', ['action' => 'getFiscalYearMonth']);
        $data['pvmReadLink'] = $this->url()->fromRoute('salarySheet', ['action' => 'pvmRead']);
        $data['pvmUpdateLink'] = $this->url()->fromRoute('salarySheet', ['action' => 'pvmUpdate']);

        $rulesRepo = new RulesRepository($this->adapter);
        $data['ruleList'] = $rulesRepo->fetchSSRules();
        $companyWiseGroup = null;
        if ($this->acl['CONTROL_VALUES']) {
            if ($this->acl['CONTROL_VALUES'][0]['CONTROL'] == 'C') {
                $companyWiseGroup = $rulesRepo->getCompanyWise($this->acl['CONTROL_VALUES'][0]['VAL']);
            } else {
                $companyWiseGroup = null;
            }
        }
        // echo '<pre>';print_r($companyWiseGroup);die;
        return $this->stickFlashMessagesTo([
            'data' => json_encode($data),
            'acl' => $this->acl,
            'companyWiseGroup' => $companyWiseGroup,
        ]);
        // return ['data' => json_encode($data)];
    }

    public function pvmReadAction()
    {
        $request = $this->getRequest();
        $postData = $request->getPost();
        $sspvmRepo = new SSPayValueModifiedRepo($this->adapter);
        $data = $sspvmRepo->filter($postData['monthId'], $postData['companyId'], $postData['groupId']);

        return new JsonModel($data);
    }

    public function pvmUpdateAction()
    {
        $request = $this->getRequest();
        $postData = $request->getPost();

        $monthId = $postData->monthId;
        $data = json_decode($postData->models);

        $dataToUpdate = [];
        foreach ($data as $value) {
            $item = (array) $value;
            $common = ['EMPLOYEE_ID' => $item['EMPLOYEE_ID'], 'MONTH_ID' => $monthId];
            foreach ($item as $k => $v) {
                if (!in_array($k, ['EMPLOYEE_ID', 'FULL_NAME', 'COMPANY_ID', 'COMPANY_NAME', 'GROUP_ID', 'GROUP_NAME', 'MONTH_ID', 'E_ID'])) {
                    if ($v != null) {
                        $payId = str_replace('H_', '', $k);
                        $dataUnit = array_merge($common, []);
                        $dataUnit['PAY_ID'] = $payId;
                        $dataUnit['VAL'] = $v;
                        array_push($dataToUpdate, $dataUnit);
                    }
                }
            }
        }
        $sspvmRepo = new SSPayValueModifiedRepo($this->adapter);
        $sspvmRepo->bulkEdit($dataToUpdate);
        return new JsonModel($data);
    }

    public function pullGroupEmployeeAction()
    {
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
                    // echo '<pre>';print_r($valuesinCSV);die;
                    $employeeList = $this->salarySheetRepo->fetchEmployeeByGroup($monthId, $valuesinCSV, $salaryTypeId, $companyId);
                    $sheetList = $this->salarySheetRepo->fetchGeneratedSheetByGroup($monthId, $valuesinCSV, $salaryTypeId, $companyId);
                } else {
                }
            } else {
                $companyId = 0;
                $employeeList = $this->salarySheetRepo->fetchEmployeeByGroup($monthId, $valuesinCSV, $salaryTypeId, $companyId);
                $sheetList = $this->salarySheetRepo->fetchGeneratedSheetByGroup($monthId, $valuesinCSV, $salaryTypeId, $companyId);
            }

            // echo '<pre>';print_r($sheetList);die;


            return new JsonModel(['success' => true, 'data' => $employeeList, 'sheetData' => $sheetList, 'message' => null]);
        } catch (Exception $e) {
            return new JsonModel(['success' => false, 'data' => null, 'message' => $e->getMessage()]);
        }
    }

    public function deleteSheetAction()
    {
        $id = $this->params()->fromRoute('id');
        if ($id == 0) {
            $this->redirect()->toRoute('salarySheet');
        }
        $this->salarySheetRepo->deleteSheetBySheetNo($id);
        $this->flashmessenger()->addMessage("Sheet Successfully Deleted!!!");
        return $this->redirect()->toRoute("salarySheet", ['action' => 'sheetWise']);
    }
    //here for deleting individual salary
    public function deleteEmpSalSheAction()
    {
        $request = $this->getRequest();
        $data = $request->getPost();
        $employeeId = $data['employeeId'];
        $sheetNo = $data['sheetNo'];
        $checkData = $this->salarySheetRepo->checkApproveLock($sheetNo);
        if ($checkData[0]['LOCKED'] == 'Y' || $checkData[0]['APPROVED'] == 'Y') {
            return new JsonModel(['success' => false, 'data' => 'Approved or locked sheet cannot be deleted.', 'error' => '']);
        } else {
            $this->salarySheetRepo->deleteEmployeeSalarySheet($sheetNo, $employeeId);
            $this->flashmessenger()->addMessage("Employee Salary Successfully Deleted!!!");
            return new JsonModel(['success' => true, 'data' => '', 'error' => '']);
        }
    }
    //here for deleting individual salary

    public function sheetWiseAction()
    {
        $ruleRepo = new RulesRepository($this->adapter);
        $data['salaryType'] = iterator_to_array($this->salarySheetRepo->fetchAllSalaryType(), false);
        $data['ruleList'] = iterator_to_array($ruleRepo->fetchAll(), false);
        $data['salarySheetList'] = iterator_to_array($this->salarySheetRepo->fetchAll(), false);
        $links['viewLink'] = $this->url()->fromRoute('salarySheet', ['action' => 'viewSalarySheet']);
        $links['getSearchDataLink'] = $this->url()->fromRoute('salarySheet', ['action' => 'getSearchData']);
        $links['getGroupListLink'] = $this->url()->fromRoute('salarySheet', ['action' => 'getGroupList']);
        $links['regenEmpSalSheLink'] = $this->url()->fromRoute('salarySheet', ['action' => 'regenEmpSalShe']);
        $data['links'] = $links;
        $companyWiseGroup = null;
        if ($this->acl['CONTROL_VALUES']) {
            if ($this->acl['CONTROL_VALUES'][0]['CONTROL'] == 'C') {
                $companyWiseGroup = $ruleRepo->getCompanyWise($this->acl['CONTROL_VALUES'][0]['VAL']);
            } else {
                $companyWiseGroup = null;
            }
        }
        return $this->stickFlashMessagesTo([
            'data' => json_encode($data),
            'acl' => $this->acl,
            'companyWiseGroup' => $companyWiseGroup,
            'employeeDetail' => $this->storageData['employee_detail']
        ]);
        // return $this->stickFlashMessagesTo(['data' => json_encode($data)]);
    }

    public function deleteSheetInBulkAction()
    {
        $data = $_POST['data'];
        foreach ($data as $key) {
            $checkData = $this->salarySheetRepo->checkApproveLock($key);
            if ($checkData[0]['LOCKED'] == 'Y' || $checkData[0]['APPROVED'] == 'Y') {
                continue;
            }
            $this->salarySheetRepo->deleteSheetBySheetNo($key);
        }
        return new JSONModel(['success' => true]);
    }

    public function getEmployeeSheetWiseAction()
    {
        try {
            $request = $this->getRequest();
            $data = $request->getPost();
            $sheetNo = $data['sheetNo'];
            $employeeList = $this->salarySheetRepo->fetchSheetWiseEmployeeList($sheetNo);
            return new JsonModel(['success' => true, 'data' => $employeeList, 'message' => null]);
        } catch (Exception $e) {
            return new JsonModel(['success' => false, 'data' => null, 'message' => $e->getMessage()]);
        }
    }

    public function payValueModifiedModernAction()
    {
        $data['getSearchDataLink'] = $this->url()->fromRoute('salarySheet', ['action' => 'getSearchData']);
        $data['getGroupListLink'] = $this->url()->fromRoute('salarySheet', ['action' => 'getGroupList']);
        $fiscalYears = EntityHelper::getTableList($this->adapter, FiscalYear::TABLE_NAME, [FiscalYear::FISCAL_YEAR_ID, FiscalYear::FISCAL_YEAR_NAME]);
        $payrollRepo = new PayrollRepository($this->adapter);
        $employeeList = $payrollRepo->fetchEmployeeList();
        $months = EntityHelper::getTableList($this->adapter, Months::TABLE_NAME, [Months::MONTH_ID, Months::MONTH_EDESC, Months::FISCAL_YEAR_ID], null, '', 'FISCAL_YEAR_MONTH_NO');
        $rulesRepo = new RulesRepository($this->adapter);
        $payHeads = $rulesRepo->fetchSSRules();
        $companyWiseGroup = null;
        if ($this->acl['CONTROL_VALUES']) {
            if ($this->acl['CONTROL_VALUES'][0]['CONTROL'] == 'C') {
                $companyWiseGroup = $rulesRepo->getCompanyWise($this->acl['CONTROL_VALUES'][0]['VAL']);
            } else {
                $companyWiseGroup = null;
            }
        }

        return $this->stickFlashMessagesTo([
            'payHeads' => $payHeads,
            'fiscalYears' => $fiscalYears,
            'months' => $months,
            'data' => $data,
            'salaryTypes' => iterator_to_array($this->salarySheetRepo->fetchAllSalaryType(), false),
            'employees' => $employeeList,
            'acl' => $this->acl,
            'companyWiseGroup' => $companyWiseGroup,
            'employeeDetail' => $this->storageData['employee_detail'],
        ]);
    }

    public function getPayValueDetailAction()
    {
        try {
            $request = $this->getRequest();
            if (!$request->isPost()) {
                throw new Exception("The request should be of type post");
            }
            $postData = $request->getPost();
            $payId = $_POST['payHeadId'];
            $pivotString = '';
            for ($i = 0; $i < count($payId); $i++) {
                if ($i != 0) {
                    $pivotString .= ',';
                }
                $pivotString .= $payId[$i] . ' AS H_' . $payId[$i];
            }
            $sspvmRepo = new SSPayValueModifiedRepo($this->adapter);
            $data = $sspvmRepo->modernFilter($postData['monthId'], $postData['companyId'], $postData['groupId'], $pivotString, $postData['employeeId'], $postData['salaryTypeId']);
            $columns = $sspvmRepo->getColumns($_POST['payHeadId']);
            return new JsonModel(['success' => true, 'data' => Helper::extractDbData($data), 'columns' => Helper::extractDbData($columns), 'error' => '']);
        } catch (Exception $e) {
            return new JsonModel(['success' => false, 'data' => [], 'error' => $e->getMessage()]);
        }
    }

    public function postPayValueDetailAction()
    {
        try {
            $request = $this->getRequest();
            if (!$request->isPost()) {
                throw new Exception("The request should be of type post");
            }
            $postedData = $request->getPost();
            $data = $postedData['data'];
            $monthId = $_POST['monthId'];
            $salaryTypeId = $_POST['salaryTypeId'];
            $detailRepo = new SSPayValueModifiedRepo($this->adapter);
            foreach ($data as $item) {
                if ($item['employeeId'] == null || $item['employeeId'] == '') {
                    continue;
                }
                $detailRepo->setModifiedPayValue($item, $monthId, $salaryTypeId);
            }
            return new JsonModel(['success' => true, 'data' => $data, 'error' => '']);
        } catch (Exception $e) {
            return new JsonModel(['success' => false, 'data' => [], 'error' => $e->getMessage()]);
        }
    }

    public function payValueModifiedEmployeeWiseAction()
    {
        $data['getSearchDataLink'] = $this->url()->fromRoute('salarySheet', ['action' => 'getSearchData']);
        $data['getGroupListLink'] = $this->url()->fromRoute('salarySheet', ['action' => 'getGroupList']);
        $fiscalYears = EntityHelper::getTableList($this->adapter, FiscalYear::TABLE_NAME, [FiscalYear::FISCAL_YEAR_ID, FiscalYear::FISCAL_YEAR_NAME]);
        $payrollRepo = new PayrollRepository($this->adapter);
        $employeeList = $payrollRepo->fetchEmployeeList();
        $months = EntityHelper::getTableList($this->adapter, Months::TABLE_NAME, [Months::MONTH_ID, Months::MONTH_EDESC, Months::FISCAL_YEAR_ID], null, '', 'FISCAL_YEAR_MONTH_NO');
        $rulesRepo = new RulesRepository($this->adapter);
        $payHeads = $rulesRepo->fetchSSRules();

        return $this->stickFlashMessagesTo([
            'payHeads' => $payHeads,
            'fiscalYears' => $fiscalYears,
            'months' => $months,
            'data' => $data,
            'salaryTypes' => iterator_to_array($this->salarySheetRepo->fetchAllSalaryType(), false),
            'employees' => $employeeList,
            'acl' => $this->acl,
            'employeeDetail' => $this->storageData['employee_detail']
        ]);
    }

    public function getPayValueDetailEmployeeWiseAction()
    {
        try {
            $request = $this->getRequest();
            if (!$request->isPost()) {
                throw new Exception("The request should be of type post");
            }
            $postData = $request->getPost();
            $sspvmRepo = new SSPayValueModifiedRepo($this->adapter);
            $data = $sspvmRepo->modernFilterEmployeeWise($postData['monthId'], $postData['payHeadId'], $postData['employeeId'], $postData['salaryTypeId']);
            return new JsonModel(['success' => true, 'data' => Helper::extractDbData($data),     'error' => '']);
        } catch (Exception $e) {
            return new JsonModel(['success' => false, 'data' => [], 'error' => $e->getMessage()]);
        }
    }

    public function postPayValueDetailEmployeeWiseAction()
    {
        try {
            $request = $this->getRequest();
            if (!$request->isPost()) {
                throw new Exception("The request should be of type post");
            }
            $postedData = $request->getPost();
            $data = $postedData['data'];
            $monthId = $_POST['monthId'];
            $employeeId = $_POST['employeeId'];
            $salaryTypeId = $_POST['salaryTypeId'];
            $detailRepo = new SSPayValueModifiedRepo($this->adapter);
            foreach ($data as $item) {
                $detailRepo->setModifiedPayValueEmployeeWise($item, $monthId, $salaryTypeId, $employeeId);
            }
            return new JsonModel(['success' => true, 'data' => $data, 'error' => '']);
        } catch (Exception $e) {
            return new JsonModel(['success' => false, 'data' => [], 'error' => $e->getMessage()]);
        }
    }

    public function getSearchDataAction()
    {
        $acl =  $this->acl;
        if ($acl['CONTROL'][0] == 'C') {
            $searchValues = EntityHelper::getSearchDataCompanyWise($this->adapter, true, $acl['CONTROL_VALUES'][0]['VAL']);
        } else {
            $searchValues = EntityHelper::getSearchDataCompanyWise($this->adapter, false, null);
        }
        return new JsonModel(['success' => true, 'data' => $searchValues, 'error' => '']);
    }
}
