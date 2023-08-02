<?php

namespace SelfService\Controller;

use Application\Controller\HrisController;
use Application\Custom\CustomViewModel;
use Application\Helper\EntityHelper;
use Application\Helper\Helper;
use Application\Repository\MonthRepository;
use Exception;
use SelfService\Repository\AttendanceRepository;
use Zend\Authentication\Storage\StorageInterface;
use Zend\Db\Adapter\AdapterInterface;

class MyAttendance extends HrisController {

    public function __construct(AdapterInterface $adapter, StorageInterface $storage) {
        parent::__construct($adapter, $storage);
        $this->initializeRepository(AttendanceRepository::class);
    }

    public function indexAction() {
        $monthRepo = new MonthRepository($this->adapter);
        $statusSelectElement = EntityHelper::getAttendanceStatusSelectElement();
        $presentStatusSelectElement = EntityHelper::getAttendancePresentStatusSelectElement();
        return Helper::addFlashMessagesToArray($this, [
                    'employeeId' => $this->employeeId,
                    'status' => $statusSelectElement,
                    'presentStatus' => $presentStatusSelectElement,
//                    'fiscalYear' => $this->storageData['fiscal_year']
                    'fiscalYear' => $monthRepo->getCurrentMonth()
        ]);
    }

    public function pullAttendanceListAction() {
        $request = $this->getRequest();
        if ($request->isPost()) {
            try {
                $postedData = $request->getPost();
                $attendanceRepository = new AttendanceRepository($this->adapter);
                $filtersDetail = $postedData->data;
                $employeeId = $filtersDetail['employeeId'];
                $fromDate = $filtersDetail['fromDate'];
                $toDate = $filtersDetail['toDate'];
                $status = $filtersDetail['status'];
                $presentStatus = $filtersDetail['presentStatus'];

                $result = $attendanceRepository->attendanceReport($employeeId, $fromDate, $toDate, $status, $presentStatus);
                $itemList = Helper::extractDbData($result);

                return new CustomViewModel(['success' => true, 'data' => $itemList, 'error' => '']);
            } catch (Exception $e) {
                return new CustomViewModel(['success' => false, 'data' => [], 'error' => $e->getMessage()]);
            }
        }
    }

}
