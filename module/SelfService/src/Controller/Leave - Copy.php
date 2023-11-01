<?php

namespace SelfService\Controller;

use Application\Controller\HrisController;
use Application\Helper\EntityHelper;
use Application\Helper\Helper;
use Exception;
use SelfService\Repository\LeaveRepository;
use Zend\Authentication\Storage\StorageInterface;
use Zend\Db\Adapter\AdapterInterface;
use Zend\View\Model\JsonModel;
use Zend\View\Model\ViewModel;

class Leave extends HrisController {

    public function __construct(AdapterInterface $adapter, StorageInterface $storage) {
        parent::__construct($adapter, $storage);
        $this->initializeRepository(LeaveRepository::class);
    }

    public function indexAction() {
        $request = $this->getRequest();
        if ($request->isPost()) {
            try {
                $postData = (array) $request->getPost();
                if (isset($postData['fiscalYearMonthNo'])) {
                    $leaveList = $this->repository->monthlyLeaveStatus($this->employeeId, $postData['fiscalYearMonthNo']);
                } else {
                    $leaveList = $this->repository->selectAll($this->employeeId);
                }
                $leaves = iterator_to_array($leaveList, false);
                return new JsonModel(['success' => true, 'data' => $leaves, 'error' => '']);
            } catch (Exception $e) {
                return new JsonModel(['success' => false, 'data' => [], 'error' => $e->getMessage()]);
            }
        }
        $leaveMonthDataSql = "SELECT * FROM HRIS_LEAVE_MONTH_CODE 
                    WHERE LEAVE_YEAR_ID=(SELECT max(LEAVE_YEAR_ID) from HRIS_LEAVE_YEARS) ORDER BY LEAVE_YEAR_MONTH_NO";
        $leaveMonthData = EntityHelper::rawQueryResult($this->adapter, $leaveMonthDataSql);
        $currentMonth = Helper::extractDbData(EntityHelper::rawQueryResult($this->adapter, "SELECT NVL(MAX(LEAVE_YEAR_MONTH_NO),0) AS MONTH_NO FROM HRIS_LEAVE_MONTH_CODE WHERE TRUNC(SYSDATE) BETWEEN FROM_DATE AND TO_DATE"));
        return new ViewModel([
            "leaveMonthData" => Helper::extractDbData($leaveMonthData),
            "currentMonth" => $currentMonth[0]['MONTH_NO']
        ]);
    }

}
