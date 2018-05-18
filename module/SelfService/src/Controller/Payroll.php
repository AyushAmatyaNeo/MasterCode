<?php

namespace SelfService\Controller;

use Application\Controller\HrisController;
use Exception;
use Payroll\Repository\SalarySheetDetailRepo;
use Payroll\Repository\TaxSheetRepo;
use Zend\Authentication\Storage\StorageInterface;
use Zend\Db\Adapter\AdapterInterface;
use Zend\View\Model\JsonModel;

class Payroll extends HrisController {

    public function __construct(AdapterInterface $adapter, StorageInterface $storage) {
        parent::__construct($adapter, $storage);
    }

    public function payslipAction() {
        $request = $this->getRequest();
        if ($request->isPost()) {
            try {
                $postedData = $request->getPost();
                $salarySheetDetailRepo = new SalarySheetDetailRepo($this->adapter);
                $data = $salarySheetDetailRepo->fetchEmployeePaySlip($postedData['monthId'], $postedData['employeeId']);
                return new JsonModel(['success' => true, 'data' => $data, 'error' => '']);
            } catch (Exception $e) {
                return new JsonModel(['success' => false, 'data' => [], 'error' => $e->getMessage()]);
            }
        }
        return ['employeeId' => $this->employeeId];
    }

    public function taxslipAction() {
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
        return ['employeeId' => $this->employeeId];
    }

}