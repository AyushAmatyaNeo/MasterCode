<?php

namespace Training\Controller;

use Application\Controller\HrisController;
use Application\Helper\EntityHelper;
use Application\Helper\Helper;
use Exception;
use Notification\Controller\HeadNotification;
use Notification\Model\NotificationEvents;
use Setup\Model\Events;
use Setup\Repository\EmployeeRepository;
use Training\Form\EventAssignForm;
use Training\Model\EventAssign;
use Training\Repository\EventAssignRepository;
use Zend\Authentication\Storage\StorageInterface;
use Zend\Db\Adapter\AdapterInterface;
use Zend\View\Model\JsonModel;

class EventAssignController extends HrisController {

    public function __construct(AdapterInterface $adapter, StorageInterface $storage) {
        parent::__construct($adapter, $storage);
        $this->initializeRepository(EventAssignRepository::class);
        $this->initializeForm(EventAssignForm::class);
    }

    public function indexAction() {
        $request = $this->getRequest();
        if ($request->isPost()) {
            try {
                $data = $request->getPost();
                $rawList = $this->repository->filterRecords($data);
                // echo '<pre>';print_r($rawList);die;
                $list = Helper::extractDbData($rawList);
                return new JsonModel(['success' => true, 'data' => $list, 'error' => '']);
            } catch (Exception $e) {
                return new JsonModel(['success' => false, 'data' => [], 'error' => $e->getMessage()]);
            }
        }

        $eventS = EntityHelper::getTableKVListWithSortOption($this->adapter, Events::TABLE_NAME, Events::EVENT_ID, [Events::EVENT_NAME], [Events::STATUS => 'E'], "EVENT_NAME", "ASC", null, [-1 => "All Events"], true);
        $eventSE = $this->getSelectElement(['name' => 'eventId', 'id' => 'eventId', 'class' => 'form-control reset-field', 'label' => 'Events'], $eventS);
        return $this->stickFlashMessagesTo([
                    'events' => $eventSE,
                    'searchValues' => EntityHelper::getSearchData($this->adapter),
                    'acl' => $this->acl,
        ]);
    }

    public function assignAction() {
        $eventS = EntityHelper::getTableKVListWithSortOption($this->adapter, Events::TABLE_NAME, Events::EVENT_ID, [Events::EVENT_NAME], [Events::STATUS => 'E'], "EVENT_NAME", "ASC", null, [-1 => "Select Events"], true);
        $eventSE = $this->getSelectElement(['name' => 'eventId', 'id' => 'eventId', 'class' => 'form-control', 'label' => 'Events'], $eventS);
        // echo '<pre>';print_r($eventSE);die;
        return [
            'searchValues' => EntityHelper::getSearchData($this->adapter),
            'events' => $eventSE
        ];
    }

    public function deleteAction() {
        $employeeId = (int) $this->params()->fromRoute("employeeId");
        $eventId = (int) $this->params()->fromRoute('eventId');
        if (!$eventId && !$employeeId) {
            return $this->redirect()->toRoute('eventAssign');
        }
        $this->repository->delete([$employeeId, $eventId]);
        $model = new \Training\Model\EventAssign();
        $model->eventId = $eventId;
        $model->employeeId = $employeeId;
        $model->createdBy = $this->employeeId;
        $this->flashmessenger()->addMessage("Event  Assign Successfully Cancelled!!!");
        try {
            HeadNotification::pushNotification(NotificationEvents::EVENT_CANCELLED, $model, $this->adapter, $this);
        } catch (Exception $e) {
            $this->flashmessenger()->addMessage($e->getMessage());
        }
        return $this->redirect()->toRoute('eventAssign');
    }

    public function viewAction() {
        $employeeId = (int) $this->params()->fromRoute("employeeId");
        $eventId = (int) $this->params()->fromRoute("eventId");

        if (!$employeeId && !$eventId) {
            return $this->redirect()->toRoute('eventAssign');
        }

        $detail = $this->repository->getDetailByEmployeeID($employeeId, $eventId);

        return Helper::addFlashMessagesToArray($this, ['detail' => $detail]);
    }

    public function assignEmployeeEventAction() {
        try {
            $request = $this->getRequest();
            $data = $request->getPost();


            if (!isset($data['eventId']) || $data['eventId'] == '' || $data['eventId'] == -1) {
                throw new Exception('Invalid Event selection.');
            }
            $eventAssignRepo = new EventAssignRepository($this->adapter);
            $eventAssignModel = new EventAssign();

            $eventAssignModel->employeeId = $data['employeeId'];
            $eventAssignModel->eventId = $data['eventId'];

            $empEventAssignedList = $eventAssignRepo->getAllDetailByEmployeeID($data['employeeId'], $data['eventId']);
            $empEventAssignedDatail = $empEventAssignedList->current();
            if ($empEventAssignedDatail != null) {
                if ($empEventAssignedDatail['STATUS'] == EntityHelper::STATUS_ENABLED) {
                    throw new Exception('Already Assigned');
                }
                $eventAssignClone = clone $eventAssignModel;
                unset($eventAssignClone->employeeId);
                unset($eventAssignClone->eventId);
                unset($eventAssignClone->createdDt);

                $eventAssignClone->status = 'E';
                $eventAssignClone->modifiedDt = Helper::getcurrentExpressionDate();
                $eventAssignClone->modifiedBy = $this->employeeId;
                $eventAssignRepo->edit($eventAssignClone, [$data['employeeId'], $data['eventId']]);
            } else {
                $eventAssignModel->createdDt = Helper::getcurrentExpressionDate();
                $eventAssignModel->createdBy = $this->employeeId;
                $eventAssignModel->status = 'E';
                            // echo '<pre>';print_r($eventAssignModel);die;
                $eventAssignRepo->add($eventAssignModel);
            }
            try {
                HeadNotification::pushNotification(NotificationEvents::EVENT_ASSIGNED, $eventAssignModel, $this->adapter, $this);
            } catch (Exception $e) {
                return new JsonModel([
                    "success" => true,
                    "data" => null,
                    "message" => "Event assigned successfully with following error : " . $e->getMessage()
                ]);
            }
            return new JsonModel(['success' => true, 'data' => null, 'message' => "Event assigned successfully."]);
        } catch (Exception $e) {
            return new JsonModel(['success' => false, 'data' => null, 'message' => $e->getMessage()]);
        }
    }

    public function pullEmployeeForEventAssignAction() {
        // print_r('sdhsd');die;
        try {
            $request = $this->getRequest();
            $data = $request->getPost();


            $employeeId = $data['employeeId'];
            $branchId = $data['branchId'];
            $departmentId = $data['departmentId'];
            $designationId = $data['designationId'];
            $positionId = $data['positionId'];
            $serviceTypeId = $data['serviceTypeId'];
            $eventId = (int) $data['eventId'];
            $companyId = $data['companyId'];
            $employeeRepository = new EmployeeRepository($this->adapter);
            $eventAssignRepo = new EventAssignRepository($this->adapter);
            $employeeTypeId = $data['employeeTypeId'];

            $employeeResult = $employeeRepository->filterRecords($employeeId, $branchId, $departmentId, $designationId, $positionId, $serviceTypeId, -1, 1, $companyId, $employeeTypeId);

            $employeeList = [];
            foreach ($employeeResult as $employeeRow) {
                $employeeId = $employeeRow['EMPLOYEE_ID'];
                if ($eventId != -1) {
                    $eventAssignList = $eventAssignRepo->getDetailByEmployeeID($employeeId, $eventId);
                } else {
                    $eventAssignList = null;
                }
                if ($eventAssignList != null && $eventAssignList['STATUS'] == 'E') {
                    $employeeRow['EVENT_NAME'] = $eventAssignList['EVENT_NAME'];
                    $employeeRow['EVENT_ID'] = $eventAssignList['EVENT_ID'];
                    $employeeRow['START_DATE'] = $eventAssignList['START_DATE'];
                    $employeeRow['END_DATE'] = $eventAssignList['END_DATE'];
                    $employeeRow['INSTITUTE_NAME'] = $eventAssignList['INSTITUTE_NAME'];
                    $employeeRow['LOCATION'] = $eventAssignList['LOCATION'];
                } else {
                    $employeeRow['EVENT_NAME'] = "";
                    $employeeRow['EVENT_ID'] = "";
                    $employeeRow['START_DATE'] = "";
                    $employeeRow['END_DATE'] = "";
                    $employeeRow['INSTITUTE_NAME'] = "";
                    $employeeRow['LOCATION'] = "";
                }
                array_push($employeeList, $employeeRow);
            }
            // echo '<pre>';print_r($employeeList);die;

            return new JsonModel(['success' => true, 'data' => $employeeList, 'message' => null]);
        } catch (Exception $e) {
            return new JsonModel(['success' => false, 'data' => null, 'message' => $e->getMessage()]);
        }
    }

}
