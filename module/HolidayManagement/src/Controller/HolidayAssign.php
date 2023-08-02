<?php

namespace HolidayManagement\Controller;

use Application\Controller\HrisController;
use Application\Helper\EntityHelper;
use Application\Helper\Helper;
use Exception;
use HolidayManagement\Model\EmployeeHoliday;
use HolidayManagement\Repository\HolidayAssignRepository;
use HolidayManagement\Repository\HolidayRepository;
use Zend\Authentication\Storage\StorageInterface;
use Zend\Db\Adapter\AdapterInterface;
use Zend\View\Model\JsonModel;

class HolidayAssign extends HrisController {

    public function __construct(AdapterInterface $adapter, StorageInterface $storage) {
        parent::__construct($adapter, $storage);
        $this->initializeRepository(HolidayAssignRepository::class);
    }

    public function indexAction() {
        $holidayRepo = new HolidayRepository($this->adapter);
        $holidayList = $holidayRepo->fetchAll();
        return $this->stickFlashMessagesTo([
                    'searchValues' => EntityHelper::getSearchData($this->adapter),
                    'holidayList' => iterator_to_array($holidayList, false),
                    'acl' => $this->acl,
                    'employeeDetail' => $this->storageData['employee_detail']
        ]);
    }

    public function getEmployeeListAction() {
        try {
            $request = $this->getRequest();
            if ($request->isPost()) {
                $postedData = $request->getPost();

                $employeeId = $postedData['employeeId'];
                $branchId = $postedData['branchId'];
                $departmentId = $postedData['departmentId'];
                $designationId = $postedData['designationId'];
                $positionId = $postedData['positionId'];
                $serviceTypeId = $postedData['serviceTypeId'];
                $serviceEventTypeId = $postedData['serviceEventTypeId'];
                $companyId = $postedData['companyId'];
                $genderId = $postedData['genderId'];
                $employeeTypeId = $postedData['employeeTypeId'];

                $raw = $this->repository->filterEmployees($employeeId, $branchId, $departmentId, $designationId, $positionId, $serviceTypeId, $serviceEventTypeId, $companyId, $genderId, $employeeTypeId);
                $reportData = Helper::extractDbData($raw);
                return new JsonModel(['success' => true, 'data' => $reportData, 'error' => '']);
            } else {
                throw new Exception("The request should be of type post");
            }
        } catch (Exception $e) {
            return new JsonModel(['success' => false, 'data' => [], 'error' => $e->getMessage(), 'errorDetail' => $e->getTraceAsString()]);
        }
    }

    public function getHolidayAssignedEmployeesAction() {
        try {
            $request = $this->getRequest();
            if ($request->isPost()) {
                $postedData = $request->getPost();
                $holidayId = $postedData['holidayId'];

                $raw = EntityHelper::getTableKVList($this->adapter, EmployeeHoliday::TABLE_NAME, null, [EmployeeHoliday::EMPLOYEE_ID], [EmployeeHoliday::HOLIDAY_ID => $holidayId], null, null, null, null, TRUE);
                $reportData = Helper::extractDbData($raw);
                return new JsonModel(['success' => true, 'data' => $reportData, 'error' => '']);
            } else {
                throw new Exception("The request should be of type post");
            }
        } catch (Exception $e) {
            return new JsonModel(['success' => false, 'data' => [], 'error' => $e->getMessage()]);
        }
    }

    public function assignHolidayToEmployeesAction() {
        try {
            $request = $this->getRequest();
            if ($request->isPost()) {
                $postedData = $request->getPost();
                $holidayId = $postedData['holidayId'];
                if (!isset($postedData['employeeIdList'])) {
                    $employeeIdList = [];
                } else {
                    $employeeIdList = json_decode($postedData['employeeIdList']);
                }
                $reportData = $this->repository->multipleEmployeeAssignToHoliday($holidayId, $employeeIdList);
                return new JsonModel(['success' => true, 'data' => $reportData, 'error' => '']);
            } else {
                throw new Exception("The request should be of type post");
            }
        } catch (Exception $e) {
            return new JsonModel(['success' => false, 'data' => [], 'error' => $e->getMessage()]);
        }
    }

}
