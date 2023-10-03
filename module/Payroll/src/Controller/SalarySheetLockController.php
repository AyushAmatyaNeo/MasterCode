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
use Payroll\Repository\PayrollRepository;
use Payroll\Repository\RulesRepository;
use Notification\Model\PaySlipDetailsModel;
use Payroll\Repository\SalarySheetRepo;
use Zend\Authentication\Storage\StorageInterface;
use Zend\Db\Adapter\AdapterInterface;
use Payroll\Model\SalaryVoucherDetails;
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
            // if($checkData[0]['LOCKED'] == 'Y'){ continue; }
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
    public function testVoucherAction()
    {
        $data = $_POST['data'];
        // $branchesList = $this->salarySheetRepo->getBranchesFromCompany($data[0]);
        // foreach ($branchesList as $branchCode){
        $voucherData = $this->salarySheetRepo->getDataForDoubleVoucher($data[0], '01.01');
        // } 
        return new JSONModel(['success' => true, 'voucherData' => $voucherData]);
    }

    public function regenerateVoucherAction()
    {
        $data = $_POST['data'];
        $voucherDate = $_POST['voucherDate'];
        $oldVoucherNo = $this->salarySheetRepo->getOldVoucherNo($data[0]);
        // print_r($oldVoucherNo);die;
        // $salaryVoucherDetails = new SalaryVoucherDetails();
        // $salaryVoucherDetails->id = (int) Helper::getMaxId($this->adapter, SalaryVoucherDetails::TABLE_NAME, SalaryVoucherDetails::ID) + 1;
        // $salaryVoucherDetails->createdBy = $this->employeeId;
        // $salaryVoucherDetails->createdDt = Helper::getcurrentExpressionDate();
        // $salaryVoucherDetails->completedFlag='N';


        // $salaryVoucherDetails->sheetNo=$data[0];
        // $this->salarySheetRepo->insertSalaryVoucherDetail($salaryVoucherDetails);

        $sheetDetails = $this->salarySheetRepo->getSheetDetails($data[0]);
        $totalUnmappedList = [];
        $individualUnMappedList = [];

        $pivotData = $this->salarySheetRepo->pivot($data[0]);

        $branchesList = $this->salarySheetRepo->getBranchesFromCompany($data[0]);
        $companyCode = $this->salarySheetRepo->getCompanyCode($data[0]);
        $i = 1;
        // foreach ($branchesList as $branchCode){
        $branchCode['BRANCH_CODE'] = '01.01';
        $payIdMap = $this->salarySheetRepo->getMapPayIdList($data[0], $branchCode['BRANCH_CODE']);

        $payIdForSubDetail = $this->salarySheetRepo->getpayIdForSubDetail($data[0], $branchCode['BRANCH_CODE']);

        $payIdList = [];
        foreach ($payIdMap as $eachPayIdMapped) {
            array_push($payIdList, $eachPayIdMapped['PAY_ID']);
        }

        $payIdListForSubDetail = [];
        foreach ($payIdForSubDetail as $eachPayIdForSubDetail) {
            array_push($payIdListForSubDetail, $eachPayIdForSubDetail['PAY_ID']);
        }

        $voucherData = $this->salarySheetRepo->getDataForDoubleVoucher($data[0], $branchCode['BRANCH_CODE']);
        $this->salarySheetRepo->clearIncompleteData($oldVoucherNo, $data[0]);
        foreach ($voucherData as $vData) {
            $vData['SERIAL_NO'] = $i;
            $this->salarySheetRepo->reInsertIntoDoubleVoucher($vData, $this->employeeId, $sheetDetails[0], $data[0], $oldVoucherNo, $voucherDate);
            $i++;
        }
        foreach ($pivotData as $eachEmployeePivotData) {
            $allPayId = array_keys($eachEmployeePivotData);

            foreach ($allPayId as $payId) {
                if (in_array($payId, $payIdListForSubDetail)) {
                    $accCode = $this->salarySheetRepo->getAccCode($payId, $data[0], $branchCode['BRANCH_CODE']);
                    $checkTF = $this->salarySheetRepo->checkTF($eachEmployeePivotData['EMPLOYEE_ID'], $branchCode['BRANCH_CODE'], $accCode, $data[0]);
                    if ($checkTF) {
                        $vSubDetailData = $this->salarySheetRepo->getDataForVoucherSubDetail($data[0], $eachEmployeePivotData['EMPLOYEE_ID'], $accCode, $branchCode['BRANCH_CODE']);
                        if ($vSubDetailData['TOTAL'] != 0) {
                            $this->salarySheetRepo->reInsertIntoVoucherSubDetail($vSubDetailData, $this->employeeId, $eachEmployeePivotData['EMPLOYEE_ID'], $data[0], $oldVoucherNo);
                        }
                    } else {
                        $valnull = $this->salarySheetRepo->checkValOfUnmapped($eachEmployeePivotData['EMPLOYEE_ID'], $data[0], $payId);

                        if (!$valnull) {
                            $accDetails = $this->salarySheetRepo->getAccDetails($accCode, $companyCode, $data[0]);
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
        }

        $masterTransactionData = $this->salarySheetRepo->getDataForMasterTransection($oldVoucherNo, $data[0]);
        $this->salarySheetRepo->reInsertIntoMasterTransaction($masterTransactionData[0], $this->employeeId, $data[0], $oldVoucherNo, $voucherDate);
        // } 
        return new JSONModel(['success' => true, 'unmapped' => $totalUnmappedList]);
    }

    public function sendPyslipEmailAction()
    {
        $data = $_POST['data'];
        try {
            foreach ($data as $key) {
                $payRepo = new PayrollRepository($this->adapter);
                $sendPayslipEmail = $payRepo->getEmailNoti();
                $allowPayslipInEmail = $sendPayslipEmail['VALUE'];
                $ExchangeRate = $payRepo->getExchnageRate();
                $allowExchangeRate = $ExchangeRate['VALUE'];

                if ($allowPayslipInEmail == 'Y' && $allowExchangeRate == 'Y') {
                    $payslipData = $this->salarySheetRepo->getPayslipData($key);
                    if (empty($payslipData)) {
                        die;
                    }

                    $batchSize = 40; // Set the batch size
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
                                foreach ($payslipDetails['pay-detail'] as &$row) {
                                    $exchangeRate = $salarySheetDetailRepo->fetchExchangeRate($row['SHEET_NO']);
                                    $val = str_replace(',', '', $row['VAL']);
                                    $result = $val * $exchangeRate['EXCHANGE_RATE'];
                                    $row['VAL'] = number_format($result, 2, '.', ',');
                                }
                                $model->setProperty1 = ($payslipDetails['emp-detail']);
                                $model->setProperty2 = ($payslipDetails['pay-detail']);
                                HeadNotification::pushNotification(NotificationEvents::PAYSLIP_EMAIL, $model, $this->adapter, $this);
                                $id = ((int) Helper::getMaxId($this->adapter, PaySlipEmail::TABLE_NAME, PaySlipEmail::ID)) + 1;
                                $mployeeId = $data['EMPLOYEE_ID'];
                                $this->salarySheetRepo->addSendPayslip($id, $mployeeId, $this->employeeId);
                            } catch (Exception $e) {
                                $this->flashmessenger()->addMessage($e->getMessage());
                            }
                        }
                        sleep(2);
                        $index += $batchSize;
                    }
                } else if ($allowPayslipInEmail == 'Y' && $allowExchangeRate == 'N') {
                    $payslipData = $this->salarySheetRepo->getPayslipData($key);
                    if (empty($payslipData)) {
                        die;
                    }

                    $batchSize = 40; // Set the batch size
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
                                // foreach ($payslipDetails['pay-detail'] as &$row) {
                                //     $exchangeRate = $salarySheetDetailRepo->fetchExchangeRate($row['SHEET_NO']);
                                //     $val = str_replace(',', '', $row['VAL']);
                                //     $result = $val * $exchangeRate['EXCHANGE_RATE'];
                                //     $row['VAL'] = number_format($result, 2, '.', ',');
                                // }
                                $model->setProperty1 = ($payslipDetails['emp-detail']);
                                $model->setProperty2 = ($payslipDetails['pay-detail']);
                                HeadNotification::pushNotification(NotificationEvents::PAYSLIP_EMAIL, $model, $this->adapter, $this);
                                $id = ((int) Helper::getMaxId($this->adapter, PaySlipEmail::TABLE_NAME, PaySlipEmail::ID)) + 1;
                                $mployeeId = $data['EMPLOYEE_ID'];
                                $this->salarySheetRepo->addSendPayslip($id, $mployeeId, $this->employeeId);
                            } catch (Exception $e) {
                                $this->flashmessenger()->addMessage($e->getMessage());
                            }
                        }
                        sleep(2);
                        $index += $batchSize;
                    }
                }
            }
            return new JsonModel(['success' => true, 'data' =>  $payslipDetails['emp-detail'], 'error' => '']);
        } catch (Exception $e) {
            return new JsonModel(['success' => false, 'data' => [], 'error' => $e->getMessage()]);
        }
        return $this->stickFlashMessagesTo([]);
    }
}
