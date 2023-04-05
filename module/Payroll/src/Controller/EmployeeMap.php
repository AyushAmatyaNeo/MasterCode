<?php

namespace Payroll\Controller;

use Application\Helper\EntityHelper;
use Application\Helper\Helper;
use Application\Controller\HrisController;
use Exception;
use Payroll\Repository\RulesRepository;
use Payroll\Repository\VoucherImpactMapRepo;
use Zend\Authentication\Storage\StorageInterface;
use Zend\Db\Adapter\AdapterInterface;
use Zend\View\Model\JsonModel;
use Payroll\Model\AccCodeMap;

class EmployeeMap extends HrisController
{

    public function __construct(AdapterInterface $adapter, StorageInterface $storage)
    {
        parent::__construct($adapter, $storage);
        //$this->initializeRepository(PayrollRepository::class);
        $this->repository = new VoucherImpactMapRepo($adapter);
    }

    // public function getRenderer() {
    //     if (null === $this->renderer) {
    //     $this->renderer = $this->getServiceLocator()->get('Zend\View\Renderer\RendererInterface');
    //     }
    //     return $this->renderer;
    // }
    public function indexAction()
    {
        // $ruleRepo = new RulesRepository($this->adapter);
        //$data['salaryType'] = iterator_to_array($this->salarySheetRepo->fetchAllSalaryType(), false);
        // $data['ruleList'] = iterator_to_array($ruleRepo->fetchAll(), false);
        // $data['salarySheetList'] = iterator_to_array($this->salarySheetRepo->fetchAll(), false);
        // $links['viewLink'] = $this->url()->fromRoute('salarySheet', ['action' => 'viewSalarySheet']);
        // $links['getSearchDataLink'] = $this->url()->fromRoute('salarySheet', ['action' => 'getSearchData']);
        // $links['getGroupListLink'] = $this->url()->fromRoute('salarySheet', ['action' => 'getGroupList']);
        // $links['regenEmpSalSheLink'] = $this->url()->fromRoute('salarySheet', ['action' => 'regenEmpSalShe']);
        // $data['links'] = $links;
        $accountList = EntityHelper::getTableKVListWithSortOption($this->adapter, "FA_CHART_OF_ACCOUNTS_SETUP", "ACC_CODE", ["ACC_EDESC"], ["DELETED_FLAG" => 'N'], "ACC_EDESC", "ASC", "-", false, true);
        // print_r($accountList);
        // die;
        $accountSE = $this->getSelectElement(['name' => 'account', 'id' => 'accHead', 'class' => 'form-control reset-field', 'label' => 'Type'], []);
        $branchSE = $this->getSelectElement(['name' => 'branch', 'id' => 'branchName', 'class' => 'form-control reset-field', 'label' => 'Type'], []);
        $accountListCompanyWise = $this->repository->getAccHeadList();
        $branchListCompanyWise = $this->repository->getBranchList();
        $ruleRepo = new RulesRepository($this->adapter);
        $empWiseCompany = null;
        if ($this->acl['CONTROL_VALUES']) {
            if ($this->acl['CONTROL_VALUES'][0]['CONTROL'] == 'C') {
                $empWiseCompanyDtl = $ruleRepo->getCompanyId($this->employeeId);
                $empWiseCompany = array();
                foreach ($empWiseCompanyDtl as $element) {
                    $empWiseCompany[$element['COMPANY_CODE']] = $element['COMPANY_NAME'];
                }
            }
        } else {
            $empWiseCompany = EntityHelper::getTableKVListWithSortOption($this->adapter, "HRIS_COMPANY", "COMPANY_CODE", ["COMPANY_NAME"], ["STATUS" => 'E'], "COMPANY_NAME", "ASC", "-", false, true);
        }

        return Helper::addFlashMessagesToArray($this, [
            'form' => $this->form,
            //'requestTypes' => $requestType,
            //'transportTypes' => $transportTypes,
            //'applyOption' => $applyOption,
            'companyList' => $empWiseCompany,
            'groupList' => EntityHelper::getTableKVListWithSortOption($this->adapter, "HRIS_SALARY_SHEET_GROUP", "GROUP_ID", ["GROUP_NAME"], [], "GROUP_NAME", "ASC", "-", false, true),
            'payHeadList' => EntityHelper::getTableKVListWithSortOption($this->adapter, "HRIS_PAY_SETUP", "PAY_ID", ["PAY_EDESC"], ["VOUCHER_IMPACT = 'Y'"], "PAY_EDESC", "ASC", "-", false, true),
            'accountListCompanyWise' => $accountListCompanyWise,
            'accounts' => $accountSE,
            'branchListCompanyWise' => $branchListCompanyWise,
            'branchs' => $branchSE,
            'acl' => $this->acl,
            'employeeDetail' => $this->storageData['employee_detail'],

        ]);
    }

    public function employeeListOfCompanyAction()
    {
        $request = $this->getRequest();

        if ($request->isPost()) {
            try {
                $data = $request->getPost();
                $employeeDataList = $this->repository->getEmployeeDataList($data);

                return new JsonModel(['success' => true, 'data' => $employeeDataList, 'error' => '']);
            } catch (Exception $e) {
                return new JsonModel(['success' => false, 'data' => [], 'error' => $e->getMessage()]);
            }
        }

        return;
    }
    public function insertIntoAccCodeMapAction()
    {
        $request = $this->getRequest();
        $accCodeMapData = new AccCodeMap();
        if ($request->isPost()) {
            try {
                $data = $request->getPost();
                $accCodeMapData->payId = $data['payHead'];
                $accCodeMapData->accCode = $data['accHead'];
                $accCodeMapData->deleteFlag = 'N';
                $accCodeMapData->Id = ((int) Helper::getMaxId($this->adapter, "HRIS_ACC_CODE_MAP", "ID")) + 1;
                //$company_id = $this->repository->convertCompanyCodeToId($data['company']);
                $accCodeMapData->companyCode = $data['company'];
                $accCodeMapData->branchCode =  $data['branchName'];
                $accCodeMapData->groupId = $data['groupId'];
                $this->repository->add($accCodeMapData);
                $this->flashmessenger()->addMessage("Sucessfully Mapped!!!");
                // return $this->redirect()->toRoute("employeeMap");
                return new JsonModel(['success' => true, 'data' => '', 'error' => '']);
            } catch (Exception $e) {
                $this->flashmessenger()->addMessage("Error!!!");
                return $this->redirect()->toRoute("employeeMap");
            }
        }
        return $this->redirect()->toRoute("employeeMap");
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

            $employeeList = $this->salarySheetRepo->fetchEmployeeByGroup($monthId, $valuesinCSV, $salaryTypeId);
            $sheetList = $this->salarySheetRepo->fetchGeneratedSheetByGroup($monthId, $valuesinCSV, $salaryTypeId);

            return new JsonModel(['success' => true, 'data' => $employeeList, 'sheetData' => $sheetList, 'message' => null]);
        } catch (Exception $e) {
            return new JsonModel(['success' => false, 'data' => null, 'message' => $e->getMessage()]);
        }
    }

    public function bulkApproveLockAction()
    {
        $data = $_POST['data'];
        $action = $_POST['action'];
        $col = null;
        $val = null;
        if ($action == 'A') {
            $col = 'APPROVED';
            $val = 'Y';
        }
        if ($action == 'NA') {
            $col = 'APPROVED';
            $val = 'N';
        }
        if ($action == 'L') {
            $col = 'LOCKED';
            $val = 'Y';
        }
        if ($action == 'UL') {
            $col = 'LOCKED';
            $val = 'N';
        }
        foreach ($data as $key) {
            $checkData = $this->salarySheetRepo->checkApproveLock($key);
            if ($checkData[0]['LOCKED'] == 'Y') {
                continue;
            }
            $this->salarySheetRepo->bulkApproveLock($key, $col, $val);
        }

        return new JSONModel(['success' => true]);
    }

    public function generateVoucherAction()
    {
        $data = $_POST['data'];
        $payIdMap = $this->salarySheetRepo->getMapPayIdList();

        $payIdList = [];
        foreach ($payIdMap as $eachPayIdMapped) {
            array_push($payIdList, $eachPayIdMapped['PAY_ID']);
        }

        $pivotData = $this->salarySheetRepo->pivot($data[0]);

        $voucherData = $this->salarySheetRepo->getDataForDoubleVoucher($data[0]);
        $i = 1;
        foreach ($voucherData as $vData) {
            $vData['SERIAL_NO'] = $i;

            $this->salarySheetRepo->insertIntoDoubleVoucher($vData, $this->employeeId);
            $i++;
        }
        foreach ($pivotData as $eachEmployeePivotData) {
            $allPayId = array_keys($eachEmployeePivotData);
            $i = 1;
            foreach ($allPayId as $payId) {
                if (in_array($payId, $payIdList)) {

                    $accCode = $this->salarySheetRepo->getAccCode($payId);
                    $checkTF = $this->salarySheetRepo->checkTF($eachEmployeePivotData['EMPLOYEE_ID'], $payId, $accCode, $data[0]);
                    if ($checkTF) {
                        $voucherSubDetailData = $this->salarySheetRepo->getDataForVoucherSubDetail($data[0], $eachEmployeePivotData['EMPLOYEE_ID'], $accCode);

                        foreach ($voucherSubDetailData as $vSubDetailData) {
                            $vSubDetailData['SERIAL_NO'] = $i;
                            $this->salarySheetRepo->insertIntoVoucherSubDetail($vSubDetailData, $eachEmployeePivotData['EMPLOYEE_ID']);
                            $i++;
                        }
                    }
                }
            }
        }

        $masterTransactionData = $this->salarySheetRepo->getDataForMasterTransection($voucherData[0]['VOUCHER_NO']);
        $this->salarySheetRepo->insertIntoMasterTransaction($masterTransactionData[0], $this->employeeId);

        $subDetailsData = $this->salarySheetRepo->getDataOfSubDetails($voucherData[0]['VOUCHER_NO']);

        foreach ($subDetailsData as $singleSubDetailData) {
            $this->salarySheetRepo->insertIntoFaSubLedger($singleSubDetailData);
        }

        $doubleVoucherData = $this->salarySheetRepo->getDataOfDoubleVoucher($voucherData[0]['VOUCHER_NO']);
        $generalVoucherblncAmt = 0;
        foreach ($doubleVoucherData as $singleDoubleVoucherData) {
            if ($singleDoubleVoucherData['TRANSACTION_TYPE'] == 'DR') {
                $generalVoucherblncAmt += $singleDoubleVoucherData['AMOUNT'];
            } else {
                $generalVoucherblncAmt -= $singleDoubleVoucherData['AMOUNT'];
            }
        }
        foreach ($doubleVoucherData as $singleDoubleVoucherData) {
            $this->salarySheetRepo->insertIntoFaGeneralLedger($singleDoubleVoucherData, $generalVoucherblncAmt);
        }
        $postedTransactioData = $this->salarySheetRepo->getDataForPostedTransaction($voucherData[0]['VOUCHER_NO']);
        $this->salarySheetRepo->insertIntoPostedTransaction($postedTransactioData[0]);
    }

    public function getMappedAccCodeAction()
    {
        $request = $this->getRequest();

        if ($request->isPost()) {
            try {
                $data = $request->getPost();
                //print_r($data);die;
                $employeeDataList = $this->repository->getMappedAccCode($data);

                return new JsonModel(['success' => true, 'data' => $employeeDataList, 'error' => '']);
            } catch (Exception $e) {
                return new JsonModel(['success' => false, 'data' => [], 'error' => $e->getMessage()]);
            }
        }

        return;
    }

    public function deleteAction()
    {
        $id = (int) $this->params()->fromRoute("id");
        if (!$id) {
            return $this->redirect()->toRoute('employeeMap');
        }
        $this->repository->delete($id);
        $this->flashmessenger()->addMessage("Account Mapping Successfully Cancelled!!!");
        return $this->redirect()->toRoute('employeeMap');
    }
}
