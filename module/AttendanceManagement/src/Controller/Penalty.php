<?php

namespace AttendanceManagement\Controller;

use Application\Controller\HrisController;
use Application\Helper\EntityHelper;
use Application\Helper\Helper;
use Application\Model\FiscalYear;
use Application\Model\Months;
use AttendanceManagement\Repository\PenaltyRepo;
use Exception;
use Zend\Authentication\Storage\StorageInterface;
use Zend\Db\Adapter\AdapterInterface;
use Zend\View\Model\JsonModel;

class Penalty extends HrisController
{

    public function __construct(AdapterInterface $adapter, StorageInterface $storage)
    {
        parent::__construct($adapter, $storage);
        $this->initializeRepository(PenaltyRepo::class);
    }

    public function indexAction()
    {
        $request = $this->getRequest();
        if ($request->isPost()) {
            try {
                $data = $request->getPost();
                $reportData = $this->repository->monthWiseReport($data);
                return new JsonModel(['success' => true, 'data' => $reportData, 'alreadyPenalized' => $this->repository->checkIfAlreadyDeducted($data['monthId']), 'error' => '']);
            } catch (Exception $e) {
                return new JsonModel(['success' => false, 'data' => [], 'error' => $e->getMessage()]);
            }
        }

        return $this->stickFlashMessagesTo([
            'searchValues' => EntityHelper::getSearchData($this->adapter),
            'fiscalYears' => EntityHelper::getTableList($this->adapter, FiscalYear::TABLE_NAME, [FiscalYear::FISCAL_YEAR_ID, FiscalYear::FISCAL_YEAR_NAME]),
            'months' => EntityHelper::getTableList($this->adapter, Months::TABLE_NAME, [Months::MONTH_ID, Months::MONTH_EDESC, Months::FISCAL_YEAR_ID]),
            'acl' => $this->acl,
            'employeeDetail' => $this->storageData['employee_detail'],
        ]);
    }

    public function penalizedMonthsAction()
    {
        $request = $this->getRequest();
        if ($request->isPost()) {
            try {
                $data = (array) $request->getPost();
                $reportData = $this->repository->penalizedMonthReport($data['fiscalYearId'], $data['fiscalYearMonthNo']);
                return new JsonModel(['success' => true, 'data' => $reportData, 'error' => '']);
            } catch (Exception $e) {
                return new JsonModel(['success' => false, 'data' => [], 'error' => $e->getMessage()]);
            }
        }
        return $this->stickFlashMessagesTo(['acl' => $this->acl, 'noOfDeductionDays' => $this->storageData['preference']['latePenaltyLeaveDeduction']]);
    }

    public function selfAction()
    {
        $id = (int) $this->params()->fromRoute("id", 0);
        $request = $this->getRequest();
        if ($request->isPost()) {
            try {
                $data = $request->getPost();
                $data['employeeId'] = $this->employeeId;
                $reportData = $this->repository->monthWiseReport($data);
                return new JsonModel(['success' => true, 'data' => $reportData, 'alreadyPenalized' => $this->repository->checkIfAlreadyDeducted($data['monthId']), 'error' => '']);
            } catch (Exception $e) {
                return new JsonModel(['success' => false, 'data' => [], 'error' => $e->getMessage()]);
            }
        }

        return $this->stickFlashMessagesTo([
            'fiscalYears' => EntityHelper::getTableList($this->adapter, FiscalYear::TABLE_NAME, [FiscalYear::FISCAL_YEAR_ID, FiscalYear::FISCAL_YEAR_NAME]),
            'months' => EntityHelper::getTableList($this->adapter, Months::TABLE_NAME, [Months::MONTH_ID, Months::MONTH_EDESC, Months::FISCAL_YEAR_ID]),
            'acl' => $this->acl,
            'employeeDetail' => $this->storageData['employee_detail'],
            'selectedMonthId' => ($id != 0) ? $id : -1
        ]);
    }

    public function penaltyDetailWSAction()
    {
        try {
            $request = $this->getRequest();
            if (!$request->isPost()) {
                throw new Exception("must be a post request.");
            }
            $data = $request->getPost();
            $reportData = $this->repository->penaltyDetail($data['employeeId'], Helper::getExpressionDate($data['attendanceDt']), $data['type']);
            return new JsonModel(['success' => true, 'data' => $reportData, 'error' => '']);
        } catch (Exception $e) {
            return new JsonModel(['success' => false, 'data' => [], 'error' => $e->getMessage()]);
        }
    }

    // public function deductAction()
    // {
    //     $companyId = (int) $this->params()->fromRoute("id", 0);
    //     $fiscalYearId = (int) $this->params()->fromRoute("fiscalYearId", 0);
    //     $fiscalYearMonthNo = (int) $this->params()->fromRoute("fiscalYearMonthNo", 0);
    //     try {
    //         $request = $this->getRequest();
    //         if (!$request->isPost()) {
    //             throw new Exception("must be a post request.");
    //         }
    //         $data = $request->getPost();
    //         $data['employeeId'] = $this->employeeId;
    //         $data['companyId'] = $companyId;
    //         $data['fiscalYearId'] = $fiscalYearId;
    //         $data['fiscalYearMonthNo'] = $fiscalYearMonthNo;

    //         $reportData = $this->repository->deduct($data);
    //         return new JsonModel(['success' => true, 'data' => $data, 'error' => '']);
    //     } catch (Exception $e) {
    //         return new JsonModel(['success' => false, 'data' => [], 'error' => $e->getMessage()]);
    //     }
    // }


    public function getMonthlyReportAction()
    {
        $request = $this->getRequest();
        if ($request->isPost()) {
            try {
                $data = $request->getPost();
                $reportData = $this->repository->getMonthlyReport($data);
                return new JsonModel(['success' => true, 'data' => $reportData, 'error' => '']);
            } catch (Exception $e) {
                return new JsonModel(['success' => false, 'data' => [], 'error' => $e->getMessage()]);
            }
        }
    }


    public function getLeaveDeductedDetailAction()
    {
        try {
            $request = $this->getRequest();
            if (!$request->isPost()) {
                throw new Exception("must be a post request.");
            }
            $data = $request->getPost();
            $reportData = $this->repository->getLeaveDeductedDetail($data['employeeId'], $data['monthId']);
            return new JsonModel(['success' => true, 'data' => $reportData, 'error' => '']);
        } catch (Exception $e) {
            return new JsonModel(['success' => false, 'data' => [], 'error' => $e->getMessage()]);
        }
    }

    public function deductAction()
    {
        $companyId = (int) $this->params()->fromRoute("id", 0);
        $fiscalYearId = (int) $this->params()->fromRoute("fiscalYearId", 0);
        $fiscalYearMonthNo = (int) $this->params()->fromRoute("fiscalYearMonthNo", 0);
        try {
            $request = $this->getRequest();
            if (!$request->isPost()) {
                throw new Exception("must be a post request.");
            }
            $data = $request->getPost();
            $data['employeeId'] = $this->employeeId;
            $data['companyId'] = $companyId;
            $data['fiscalYearId'] = $fiscalYearId;
            $data['fiscalYearMonthNo'] = $fiscalYearMonthNo;

            $reportData = $this->repository->deduct($data);
            return new JsonModel(['success' => true, 'data' => $data, 'error' => '']);
        } catch (Exception $e) {
            return new JsonModel(['success' => false, 'data' => [], 'error' => $e->getMessage()]);
        }
    }

    public function monthlyReportAction()
    {
        $request = $this->getRequest();
        if ($request->isPost()) {
            try {
                $data = $request->getPost();
                $reportData = $this->repository->monthWiseReport($data);
                return new JsonModel(['success' => true, 'data' => $reportData, 'alreadyPenalized' => $this->repository->checkIfAlreadyDeducted($data['monthId']), 'error' => '']);
            } catch (Exception $e) {
                return new JsonModel(['success' => false, 'data' => [], 'error' => $e->getMessage()]);
            }
        }

        return $this->stickFlashMessagesTo([
            'searchValues' => EntityHelper::getSearchData($this->adapter),
            'fiscalYears' => EntityHelper::getTableList($this->adapter, FiscalYear::TABLE_NAME, [FiscalYear::FISCAL_YEAR_ID, FiscalYear::FISCAL_YEAR_NAME]),
            'months' => EntityHelper::getTableList($this->adapter, Months::TABLE_NAME, [Months::MONTH_ID, Months::MONTH_EDESC, Months::FISCAL_YEAR_ID]),
            'acl' => $this->acl,
            'employeeDetail' => $this->storageData['employee_detail'],
        ]);
    }
}
