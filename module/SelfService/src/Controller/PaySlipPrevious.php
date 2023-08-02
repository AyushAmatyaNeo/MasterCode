<?php

namespace SelfService\Controller;

use Application\Controller\HrisController;
use Exception;
use SelfService\Repository\PayslipPreviousRepository;
use Zend\Authentication\Storage\StorageInterface;
use Zend\Db\Adapter\AdapterInterface;
use Zend\View\Model\JsonModel;
use Zend\View\Model\ViewModel;

class PaySlipPrevious extends HrisController {

    private $viewType;
    private $queryToList;

    public function __construct(AdapterInterface $adapter, StorageInterface $storage) {
        parent::__construct($adapter, $storage);
        $this->initializeRepository(PayslipPreviousRepository::class);
        $this->viewType = $this->storageData['preference']['oldPayslipType'];

        $this->queryToList = function($sql) {
            $statement = $this->adapter->query($sql);
            $iterator = $statement->execute();
            return iterator_to_array($iterator);
        };
    }

    public function payslipAction() {
        $toView = null;
        $template = null;
        switch ($this->viewType) {
            case "M":
                $toView = [
                    'employeeCode' => $this->storageData['employee_detail']['EMPLOYEE_CODE'],
                ];
                $template = "mysql/payslip";
                break;
            case "O":
                $request = $this->getRequest();
                if ($request->isPost()) {
                    try {
                        $data = (array) $request->getPost();
                        $list = $this->repository->getPayslipDetail($this->storageData['company_detail']['COMPANY_CODE'], $this->employeeId, $data['PERIOD_DT_CODE'], $data['SALARY_TYPE']);
                        $salSheetDetail = $this->repository->getSalarySheetDetail($this->storageData['company_detail']['COMPANY_CODE'], $this->employeeId, $data['PERIOD_DT_CODE'], $data['SALARY_TYPE']);
                        return new JsonModel(['success' => true, 'data' => ['paySlip' => $list, 'salarySheetDetail' => $salSheetDetail], 'error' => '']);
                    } catch (Exception $e) {
                        return new JsonModel(['success' => false, 'data' => [], 'error' => $e->getMessage()]);
                    }
                }
                $periodList = $this->repository->getPeriodList($this->storageData['company_detail']['COMPANY_CODE']);
                $arrearsListRaw = $this->repository->getArrearsList($this->storageData['company_detail']['COMPANY_CODE']);
                $arrearsList = array_merge([0 => 'Default'], $this->listValueToKV($arrearsListRaw, "ARREARS_CODE", "ARREARS_DESC"));
                $monthSE = $this->getSelectElement(['name' => 'Month', 'id' => 'mcode', 'class' => 'form-control', 'label' => 'Month'], $this->listValueToKV($periodList, "MCODE", "MNAME"));
                $arrearsSE = $this->getSelectElement(['name' => 'salaryType', 'id' => 'salaryType', 'class' => 'form-control', 'label' => 'Salary Type'], $arrearsList);
                $toView = [
                    'employeeId' => $this->employeeId,
                    'employeeCode' => $this->storageData['employee_detail']['EMPLOYEE_CODE'],
                    'monthSE' => $monthSE,
                    'arrearsSE' => $arrearsSE
                ];
                $template = "oracle/payslip";
                break;
            case "N":
                print "Not Available.";
                exit;
                break;
        }

        $view = new ViewModel($this->stickFlashMessagesTo($toView));
        $view->setTemplate($template);
        return $view;
    }

    public function printPayslipAction() {
        $template = "";
        switch ($this->viewType) {
            case "M":
                $template = "mysql/print-payslip";
                break;
            case "O":
                $template = "oracle/print-payslip";
                break;
            case "N":
                print "Not Available.";
                exit;
                break;
        }
        $employeeid = $this->params()->fromRoute('id');
        $mcode = $this->params()->fromRoute('mcode');
        $view = new ViewModel($this->stickFlashMessagesTo(['employeeId' => $employeeid, 'mcode' => $mcode, 'adapter' => $this->adapter]));
        $view->setTemplate($template);
        return $view;
    }

    public function taxsheetAction() {
        $toView = null;
        $template = null;
        switch ($this->viewType) {
            case "M":
                $toView = [
                    'employeeCode' => $this->storageData['employee_detail']['EMPLOYEE_CODE'],
                ];
                $template = "mysql/taxsheet";
                break;
            case "O":
                $toView = [
                    'employeeId' => $this->employeeId,
                    'employeeCode' => $this->storageData['employee_detail']['EMPLOYEE_CODE'],
                    'adapter' => $this->adapter
                ];
                $template = "oracle/taxsheet";
                break;
            case "N":
                print "Not Available.";
                exit;
                break;
        }
        $view = new ViewModel($toView);
        $view->setTemplate($template);
        return $view;
    }

}
