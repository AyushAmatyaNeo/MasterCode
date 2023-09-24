<?php

namespace AttendanceManagement\Controller;

use Application\Controller\HrisController;
use Application\Helper\EntityHelper;
use Application\Helper\Helper;
use AttendanceManagement\Model\ShiftSetup;
use AttendanceManagement\Repository\ShiftAssignRepository;
use AttendanceManagement\Repository\ShiftRepository;
use Exception;
use Zend\Authentication\Storage\StorageInterface;
use Zend\Db\Adapter\AdapterInterface;
use Zend\View\Model\JsonModel;
use Zend\View\Model\ViewModel;

class ShiftAssign extends HrisController
{

    public function __construct(AdapterInterface $adapter, StorageInterface $storage)
    {
        parent::__construct($adapter, $storage);
        $this->initializeRepository(ShiftAssignRepository::class);
    }

    public function indexAction()
    {
        $shiftRepo = new ShiftRepository($this->adapter);
        $shiftList = iterator_to_array($shiftRepo->fetchAll(), false);
        return new ViewModel([
            'shiftList' => $shiftList,
            'searchValues' => EntityHelper::getSearchData($this->adapter),
            'acl' => $this->acl,
            'employeeDetail' => $this->storageData['employee_detail']
        ]);
    }

    public function addAction()
    {
        $shiftRepo = new ShiftRepository($this->adapter);
        $shiftList = iterator_to_array($shiftRepo->fetchAll(), false);
        return new ViewModel([
            'shiftList' => $shiftList,
            'searchValues' => EntityHelper::getSearchData($this->adapter),
            'acl' => $this->acl,
            'employeeDetail' => $this->storageData['employee_detail']
        ]);
    }

    public function listWSAction()
    {
        try {
            $request = $this->getRequest();
            if (!$request->isPost()) {
                throw new Exception("The request should be of type post");
            }
            $data = $request->getPost();
            $list = $this->repository->fetchShiftAssignWithDetail($data);
            return new JsonModel(['success' => true, 'data' => $list, 'error' => '']);
        } catch (Exception $e) {
            return new JsonModel(['success' => false, 'data' => [], 'error' => $e->getMessage()]);
        }
    }

    public function employeeListWSAction()
    {
        try {
            $request = $this->getRequest();
            if (!$request->isPost()) {
                throw new Exception("The request should be of type post");
            }
            $data = $request->getPost();
            $list = $this->repository->fetchEmployeeList($data);
            return new JsonModel(['success' => true, 'data' => $list, 'error' => '']);
        } catch (Exception $e) {
            return new JsonModel(['success' => false, 'data' => [], 'error' => $e->getMessage()]);
        }
    }

    public function addWsAction()
    {
        try {
            $request = $this->getRequest();
            if (!$request->isPost()) {
                throw new Exception("The request should be of type post");
            }
            $data = $request->getPost();
            $employeeIds = $data['employeeIds'];
            $shiftId = $data['shiftId'];
            $fromDate = $data['fromDate'];
            $toDate = $data['toDate'];

            foreach ($employeeIds as $employeeId) {
                $this->repository->bulkAdd($employeeId, $shiftId, $fromDate, $toDate, $this->employeeId);
            }

            return new JsonModel(['success' => true, 'data' => null, 'error' => '']);
        } catch (Exception $e) {
            return new JsonModel(['success' => false, 'data' => [], 'error' => $e->getMessage()]);
        }
    }

    public function editWsAction()
    {
        try {
            $request = $this->getRequest();
            if (!$request->isPost()) {
                throw new Exception("The request should be of type post");
            }
            $data = $request->getPost();
            $ids = $data['shiftAssignIds'];
            $shiftId = $data['shiftId'];
            $fromDate = $data['fromDate'];
            $toDate = $data['toDate'];

            foreach ($ids as $id) {
                $this->repository->bulkEdit($id, $shiftId, $fromDate, $toDate, $this->employeeId);
            }

            return new JsonModel(['success' => true, 'data' => null, 'error' => '']);
        } catch (Exception $e) {
            return new JsonModel(['success' => false, 'data' => [], 'error' => $e->getMessage()]);
        }
    }

    public function deleteWsAction()
    {
        try {
            $request = $this->getRequest();
            if (!$request->isPost()) {
                throw new Exception("The request should be of type post");
            }
            $data = $request->getPost();
            $this->repository->bulkDelete($data['shiftAssignId']);

            return new JsonModel(['success' => true, 'data' => null, 'error' => '']);
        } catch (Exception $e) {
            return new JsonModel(['success' => false, 'data' => [], 'error' => $e->getMessage()]);
        }
    }

    public function employeeShiftsWSAction()
    {
        try {
            $request = $this->getRequest();
            if (!$request->isPost()) {
                throw new Exception("The request should be of type post");
            }
            $data = $request->getPost();
            $employeeShifts = $this->repository->fetchEmployeeShifts($data['employeeId']);
            return new JsonModel(['success' => true, 'data' => $employeeShifts, 'error' => '']);
        } catch (Exception $e) {
            return new JsonModel(['success' => false, 'data' => [], 'error' => $e->getMessage()]);
        }
    }
}
