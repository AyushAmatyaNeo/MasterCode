<?php

namespace Payroll\Controller;

use Application\Controller\HrisController;
use Application\Helper\EntityHelper;
use Application\Helper\Helper;
use Application\Repository\MonthRepository;
use Exception;
use Payroll\Repository\SalarySheetDetailRepo;
use Payroll\Repository\SalSheEmpDetRepo;
use Notification\Controller\HeadNotification;
use Notification\Model\NotificationEvents;
use Payroll\Model\PaySlipEmail;
use Notification\Model\PaySlipDetailsModel;
use Payroll\Repository\PayrollReportRepo;
use Payroll\Repository\PayrollRepository;
use Payroll\Repository\RulesRepository;
use Payroll\Repository\SalarySheetRepo;
use Zend\Authentication\Storage\StorageInterface;
use Zend\Db\Adapter\AdapterInterface;
use Zend\View\Model\JsonModel;

class SalarySheetLockController extends HrisController
{

    private $salarySheetRepo;

    public function __construct(AdapterInterface $adapter, StorageInterface $storage)
    {
        parent::__construct($adapter, $storage);
        //$this->initializeRepository(PayrollRepository::class);
        $this->salarySheetRepo = new SalarySheetRepo($adapter);
    }

    public function indexAction()
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
        return Helper::addFlashMessagesToArray($this, [
            'data' => json_encode($data),
            'acl' => $this->acl,
            'employeeDetail' => $this->storageData['employee_detail'],
            'companyWiseGroup' => $companyWiseGroup,
        ]);
        return $this->stickFlashMessagesTo(['data' => json_encode($data)]);
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
                    $employeeList = $this->salarySheetRepo->fetchEmployeeByGroup($monthId, $valuesinCSV, $salaryTypeId, $companyId);
                    $sheetList = $this->salarySheetRepo->fetchGeneratedSheetByGroup($monthId, $valuesinCSV, $salaryTypeId, $companyId);
                } else {
                }
            } else {
                $companyId = 0;
                $employeeList = $this->salarySheetRepo->fetchEmployeeByGroup($monthId, $valuesinCSV, $salaryTypeId, $companyId);
                $sheetList = $this->salarySheetRepo->fetchGeneratedSheetByGroup($monthId, $valuesinCSV, $salaryTypeId, $companyId);
            }


            //echo '<pre>';print_r($sheetList);die;
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
        $sheetDetails = $this->salarySheetRepo->getSheetDetails($data[0]);
        $totalUnmappedList = [];
        $individualUnMappedList = [];

        //print_r($this->preference['doNotInsertSubDetailFlag']);die;
        $pivotData = $this->salarySheetRepo->pivot($data[0]);
        //print_r($pivotData);die;
        $companyCode = $this->salarySheetRepo->getCompanyCode($data[0]);
        $groupId = $this->salarySheetRepo->getGroupId($data[0]);
        $branchesList = $this->salarySheetRepo->getBranchesFromCompany($data[0], $groupId);
        // print_r($groupId);die;
        $i = 1;
        //print_r($branchesList);die;
        foreach ($branchesList as $branchCode) {
            $payIdMap = $this->salarySheetRepo->getMapPayIdList($data[0], $branchCode['BRANCH_CODE'], $groupId);

            $payIdList = [];
            foreach ($payIdMap as $eachPayIdMapped) {
                array_push($payIdList, $eachPayIdMapped['PAY_ID']);
            }
            $voucherData = $this->salarySheetRepo->getDataForDoubleVoucher($data[0], $branchCode['BRANCH_CODE'], $groupId);
            //print_r($voucherData);die;
            foreach ($voucherData as $vData) {

                $vData['SERIAL_NO'] = $i;

                $this->salarySheetRepo->insertIntoDoubleVoucher($vData, $this->employeeId, $sheetDetails[0]);
                $i++;
            }
            // $j = 1;
            foreach ($pivotData as $eachEmployeePivotData) {
                $allPayId = array_keys($eachEmployeePivotData);
                //echo('<pre>');print_r($allPayId);
                foreach ($allPayId as $payId) {
                    if (in_array($payId, $payIdList)) {
                        //print_r('asdf');die;
                        $accCode = $this->salarySheetRepo->getAccCode($payId, $data[0], $branchCode['BRANCH_CODE'], $groupId);
                        $checkTF = $this->salarySheetRepo->checkTF($eachEmployeePivotData['EMPLOYEE_ID'], $branchCode['BRANCH_CODE'], $accCode, $data[0]);

                        if ($checkTF) {
                            $vSubDetailData = $this->salarySheetRepo->getDataForVoucherSubDetail($data[0], $eachEmployeePivotData['EMPLOYEE_ID'], $accCode, $branchCode['BRANCH_CODE'], $groupId);
                            //print_r('abd');die;
                            // print_r($vSubDetailData); die;
                            // foreach ($voucherSubDetailData as $vSubDetailData) {
                            // print_r($vSubDetailData); die;

                            // $vSubDetailData['SERIAL_NO'] = $j;

                            if ($vSubDetailData['TOTAL'] != 0) {
                                if ($vSubDetailData['TRANSACTION_TYPE'] == 'DR' && ($this->preference['doNotInsertSubDetailFlag'] != 'D' && $this->preference['doNotInsertSubDetailFlag'] != 'B')) {
                                    //  $this->salarySheetRepo->insertIntoVoucherSubDetail($vSubDetailData, $this->employeeId, $eachEmployeePivotData['EMPLOYEE_ID']);
                                }
                                if ($vSubDetailData['TRANSACTION_TYPE'] == 'CR' && $this->preference['doNotInsertSubDetailFlag'] != 'C' && $this->preference['doNotInsertSubDetailFlag'] != 'B') {
                                    $this->salarySheetRepo->insertIntoVoucherSubDetail($vSubDetailData, $this->employeeId, $eachEmployeePivotData['EMPLOYEE_ID']);
                                }
                            }
                            // $j++;
                            // }

                        } else {
                            $valnull = $this->salarySheetRepo->checkValOfUnmapped($eachEmployeePivotData['EMPLOYEE_ID'], $data[0], $payId);

                            if (!$valnull) {
                                // print_r("lol");die;
                                $accDetails = $this->salarySheetRepo->getAccDetails($accCode, $companyCode);
                                $individualUnMappedList['EMPLOYEE_ID'] = $eachEmployeePivotData['EMPLOYEE_ID'];
                                $individualUnMappedList['ACC_CODE'] = $accCode;
                                $individualUnMappedList['FULL_NAME'] = $this->salarySheetRepo->getEmpName($eachEmployeePivotData['EMPLOYEE_ID']);
                                $individualUnMappedList['ACC_NAME'] = $accDetails['ACC_EDESC'];
                                if ($accDetails['TRANSACTION_TYPE'] == 'CR') {
                                    array_push($totalUnmappedList, $individualUnMappedList);
                                }
                            }
                        }
                    }
                }
                //print_r('asdf22');die;
                //print_r($totalUnmappedList); die;
            }
            //print_r('asdf');die;
            $masterTransactionData = $this->salarySheetRepo->getDataForMasterTransection($voucherData[0]['VOUCHER_NO']);
            $this->salarySheetRepo->insertIntoMasterTransaction($masterTransactionData[0], $this->employeeId);

            // $subDetailsData = $this->salarySheetRepo->getDataOfSubDetails($voucherData[0]['VOUCHER_NO']);

            // foreach ($subDetailsData as $singleSubDetailData){
            //     $this->salarySheetRepo->insertIntoFaSubLedger($singleSubDetailData);
            // }
            // $doubleVoucherData = $this->salarySheetRepo->getDataOfDoubleVoucher($voucherData[0]['VOUCHER_NO']);
            // $generalVoucherblncAmt = 0;
            // foreach ($doubleVoucherData as $singleDoubleVoucherData){
            //     if ($singleDoubleVoucherData['TRANSACTION_TYPE'] == 'DR'){
            //         $generalVoucherblncAmt += $singleDoubleVoucherData['AMOUNT'];
            //     }else{
            //         $generalVoucherblncAmt -= $singleDoubleVoucherData['AMOUNT'];
            //     }
            // }
            // foreach ($doubleVoucherData as $singleDoubleVoucherData){            
            //     $this->salarySheetRepo->insertIntoFaGeneralLedger($singleDoubleVoucherData,$generalVoucherblncAmt);
            // }
            // $postedTransactioData = $this->salarySheetRepo->getDataForPostedTransaction($voucherData[0]['VOUCHER_NO']);
            // $this->salarySheetRepo->insertIntoPostedTransaction($postedTransactioData[0]); 
        }
        return new JSONModel(['success' => true, 'unmapped' => $totalUnmappedList]);
    }

    public function sendPyslipEmailAction()
    {
        $payRoll = new PayrollReportRepo($this->adapter);
        $incomes = $payRoll->gettaxYearlyByHeads('IN');
        $taxExcemptions = $payRoll->gettaxYearlyByHeads('TE');
        $otherTax = $payRoll->gettaxYearlyByHeads('OT');
        $miscellaneous = $payRoll->gettaxYearlyByHeads('MI');
        $bMiscellaneou = $payRoll->gettaxYearlyByHeads('BM');
        $cMiscellaneou = $payRoll->gettaxYearlyByHeads('CM');
        $sumOfExemption = $payRoll->gettaxYearlyByHeads('SE', 'sin');
        $sumOfOtherTax = $payRoll->gettaxYearlyByHeads('ST', 'sin');
        $data = $_POST['data'];
        try {
            foreach ($data as $key) {
                $payRepo = new PayrollRepository($this->adapter);
                $sendPayslipEmail = $payRepo->getEmailNoti();
                $allowPayslipInEmail = $sendPayslipEmail['VALUE'];
                //$ExchangeRate = $payRepo->getExchnageRate();
                // $allowExchangeRate = $ExchangeRate['VALUE'];

                $payslipData = $this->salarySheetRepo->getPayslipData($key);

                if (empty($payslipData)) {
                    die;
                }

                $batchSize = 200; // Set the batch size
                $index = 0;
                $totalEmployees = count($payslipData);

                while ($index < $totalEmployees) {
                    $batchPayslipData = array_slice($payslipData, $index, $batchSize);
                    foreach ($batchPayslipData as $data) {
                        try {
                            $salarySheetDetailRepo = new SalarySheetDetailRepo($this->adapter);
                            $salSheEmpDetRepo = new SalSheEmpDetRepo($this->adapter);
                            $model = new PaySlipDetailsModel();
                            $payslipDetails['emp-detail'] = $salSheEmpDetRepo->fetchOneByWithEmpDetailsNew($data['MONTH_ID'], $data['EMPLOYEE_ID'], $data['SALARY_TYPE_ID']);
                            $payslipDetails['pay-detail'] = $salarySheetDetailRepo->fetchEmployeePaySlip($data['MONTH_ID'], $data['EMPLOYEE_ID'], $data['SALARY_TYPE_ID']);
                            $payslipDetails['annual-detail'] = $salarySheetDetailRepo->fetchEmployeeAnnualPaySlip($data['MONTH_ID'], $data['EMPLOYEE_ID'], $data['SALARY_TYPE_ID']);
                            if ($payslipDetails['emp-detail']['MARITAL_STATUS'] == 'U') {
                                $payslipDetails['tax-detail'] = $salarySheetDetailRepo->fetchUnmarriedTaxDetail();
                            } else {
                                $payslipDetails['tax-detail'] = $salarySheetDetailRepo->fetchMarriedTaxDetail();
                            }
                            $payslipDetails['tax-detail'][0]['AMOUNT'] = $payslipDetails['annual-detail'][0]['V42'];
                            $payslipDetails['tax-detail'][1]['AMOUNT'] = $payslipDetails['annual-detail'][0]['V44'];
                            $payslipDetails['tax-detail'][2]['AMOUNT'] = $payslipDetails['annual-detail'][0]['V46'];
                            $payslipDetails['tax-detail'][3]['AMOUNT'] = $payslipDetails['annual-detail'][0]['V48'];
                            $payslipDetails['tax-detail'][4]['AMOUNT'] = $payslipDetails['annual-detail'][0]['V50'];
                            $payslipDetails['tax-detail'][5]['AMOUNT'] = $payslipDetails['annual-detail'][0]['V91'];
                            $model->setProperty1 = ($payslipDetails['emp-detail']);
                            $model->setProperty2 = ($payslipDetails['pay-detail']);
                            $model->setProperty3 = ($payslipDetails['annual-detail']);
                            $model->setProperty4 = ($payslipDetails['tax-detail']);
                            $model->incomes = $incomes;
                            $model->taxExcemptions = $taxExcemptions;
                            $model->otherTax = $otherTax;
                            $model->miscellaneous = $miscellaneous;
                            $model->bMiscellaneou = $bMiscellaneou;
                            $model->cMiscellaneou = $cMiscellaneou;
                            $model->sumOfExemption = $payslipDetails['annual-detail'][0]['V55'] + $payslipDetails['annual-detail'][0]['V57'];
                            $model->sumOfOtherTax = $sumOfOtherTax;
                            HeadNotification::pushNotification(NotificationEvents::PAYSLIP_EMAIL, $model, $this->adapter, $this);
                            $id = ((int) Helper::getMaxId($this->adapter, PaySlipEmail::TABLE_NAME, PaySlipEmail::ID)) + 1;
                            $mployeeId = $data['EMPLOYEE_ID'];
                            $this->salarySheetRepo->addSendPayslip($id, $mployeeId, $this->employeeId, 'P');
                            $this->salarySheetRepo->updateSalEmpDet($payslipDetails['emp-detail']);
                            sleep(2);
                        } catch (Exception $e) {
                            $this->flashmessenger()->addMessage($e->getMessage());
                        }
                    }
                    // sleep(2);
                    $index += $batchSize;
                }
            }
            return new JsonModel(['success' => true, 'data' =>  $payslipDetails['emp-detail'], 'error' => '']);
        } catch (Exception $e) {
            return new JsonModel(['success' => false, 'data' => [], 'error' => $e->getMessage()]);
        }
        return $this->stickFlashMessagesTo([]);
    }

    public function sendSalaryEmailAction()
    {
        $payRoll = new PayrollReportRepo($this->adapter);
        $data = $_POST['data'];
        $data['groupId'] = $data['groupId'][0];

        $employees  = $this->salarySheetRepo->getPayslipData($data['selectedValues'][0]);
        try {
            $batchSize = 200; // Set the batch size
            $index = 0;
            $totalEmployees = count($employees);

            while ($index < $totalEmployees) {
                $batchPayslipData = array_slice($employees, $index, $batchSize);
                $data['employeeId'] = '';
                $hasEmptyEmpDetail = false;
                foreach ($batchPayslipData as $data1) {
                    try {
                        $model = new PaySlipDetailsModel();
                        $payslipDetails['variable-detail'] = $payRoll->getDefaultColumnsEmployeeWise('S', $data);
                        $data['employeeId'] = $data1['EMPLOYEE_ID'];

                        $payslipDetails['emp-detail'] = $payRoll->getEmployeeWiseGroupReport('S', $data);

                        if (empty($payslipDetails['emp-detail'])) {
                            $hasEmptyEmpDetail = true;
                            continue; // Move to the next iteration of the inner loop
                        }

                        $model->setProperty1 = ($payslipDetails['variable-detail']);
                        $model->setProperty2 = ($payslipDetails['emp-detail']);

                        HeadNotification::pushNotification(NotificationEvents::SALARY_EMAIL, $model, $this->adapter, $this);
                        $id = ((int) Helper::getMaxId($this->adapter, PaySlipEmail::TABLE_NAME, PaySlipEmail::ID)) + 1;
                        $mployeeId = $data1['EMPLOYEE_ID'];

                        $this->salarySheetRepo->addSendPayslip($id, $mployeeId, $this->employeeId, 'S');
                        $this->salarySheetRepo->updateSalEmpDetSalary($payslipDetails['emp-detail'][0]);
                    } catch (Exception $e) {
                        $this->flashmessenger()->addMessage($e->getMessage());
                    }
                }
                if ($hasEmptyEmpDetail) {
                    continue;
                }
                // sleep(2);
                $index += 1;
            }
            return new JsonModel(['success' => true, 'data' =>  $payslipDetails['emp-detail'], 'error' => '']);
        } catch (Exception $e) {
            return new JsonModel(['success' => false, 'data' => [], 'error' => $e->getMessage()]);
        }
        return $this->stickFlashMessagesTo([]);
    }
}
