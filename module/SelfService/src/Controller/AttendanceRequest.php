<?php

namespace SelfService\Controller;

use Application\Controller\HrisController;
use Application\Helper\EntityHelper;
use Application\Helper\Helper;
use AttendanceManagement\Repository\AttendanceDetailRepository;
use Exception;
use Notification\Controller\HeadNotification;
use Notification\Model\NotificationEvents;
use SelfService\Form\AttendanceRequestForm;
use SelfService\Model\AttendanceRequestModel;
use SelfService\Repository\AttendanceRequestRepository;
use Zend\Authentication\Storage\StorageInterface;
use Zend\Db\Adapter\AdapterInterface;
use Zend\View\Model\JsonModel;

class AttendanceRequest extends HrisController {

    public function __construct(AdapterInterface $adapter, StorageInterface $storage) {
        parent::__construct($adapter, $storage);
        $this->initializeRepository(AttendanceRequestRepository::class);
        $this->initializeForm(AttendanceRequestForm::class);
        $this->initializeMiddleware('ALLOW_SYSTEM_ATTENDANCE', 'System attendance request is disabled');
    }

    public function indexAction() {
        $this->middleware();

        $statusSE = $this->getStatusSelectElement(['name' => 'attendanceStatus', "id" => "attendanceRequestStatusId", "class" => "form-control", 'label' => 'Status']);
        return $this->stickFlashMessagesTo([
                    'attendanceStatus' => $statusSE,
                    'employeeId' => $this->employeeId
        ]);
    }

    public function pullAttendanceRequestListAction() {
        $request = $this->getRequest();
        if ($request->isPost()) {
            try {
                $data = $request->getPost();
                $rawList = $this->repository->getFilterRecords($data);
                $attendanceList = Helper::extractDbData($rawList);
                return new JsonModel(['success' => true, 'data' => $attendanceList, 'error' => '']);
            } catch (Exception $e) {
                return new JsonModel(['success' => false, 'data' => [], 'error' => $e->getMessage()]);
            }
        }
    }

    public function addAction() {
        $this->middleware();

        $id = (int) $this->params()->fromRoute("id", 0);
        if ($id !== 0) {
            $attendanceDetailRepo = new AttendanceDetailRepository($this->adapter);
            $attendanceData = $attendanceDetailRepo->fetchById($id);
            $model = new AttendanceRequestModel();
            $model->attendanceDt = $attendanceData['ATTENDANCE_DT'];
            $model->inTime = $attendanceData['IN_TIME'];
            $model->outTime = $attendanceData['OUT_TIME'];
            $this->form->bind($model);
        }
        $request = $this->getRequest();

        if ($request->isPost()) {
            $this->form->setData($request->getPost());
            if ($this->form->isValid()) {
                // print_r('dhvdfh');die;
                $model = new AttendanceRequestModel();
                $model->exchangeArrayFromForm($this->form->getData());
                $model->employeeId = $this->employeeId;
                $model->attendanceDt = Helper::getExpressionDate($model->attendanceDt);
                $model->id = ((int) Helper::getMaxId($this->adapter, $model::TABLE_NAME, "ID")) + 1;
                $model->inTime = Helper::getExpressionTime($model->inTime);
                $model->outTime = Helper::getExpressionTime($model->outTime);
                $model->status = "RQ";
                $model->createdBy = $this->employeeId;
                if($request->getPost('nextDay')){
                    $model->nextDayOut='Y';
                }

                $this->repository->add($model);
                $this->flashmessenger()->addMessage("Attendance Request Submitted Successfully!!");
                try {
                    HeadNotification::pushNotification(NotificationEvents::ATTENDANCE_APPLIED, $model, $this->adapter, $this);
                } catch (Exception $e) {
                    $this->flashmessenger()->addMessage($e->getMessage());
                }

                return $this->redirect()->toRoute("attendancerequest");
            }
        }
        return Helper::addFlashMessagesToArray($this, [
                    'employeeId' => $this->employeeId,
                    'form' => $this->form
                        ]
        );
    }

    public function editAction() {
        $this->middleware();

        $id = (int) $this->params()->fromRoute("id");
        if ($id === 0) {
            return $this->redirect()->toRoute("attendancerequest");
        }

        $request = $this->getRequest();
        $model = new AttendanceRequestModel();
        if (!$request->isPost()) {
            $model->exchangeArrayFromDB($this->repository->fetchById($id));
            $this->form->bind($model);
        } else {
            $this->form->setData($request->getPost());
            if ($this->form->isValid()) {
                $model->exchangeArrayFromForm($this->form->getData());
                $model->attendanceDt = Helper::getExpressionDate($model->attendanceDt);
                $model->inTime = Helper::getExpressionTime($model->inTime);
                $model->outTime = Helper::getExpressionTime($model->outTime);
                $this->repository->edit($model, $id);
                $this->flashmessenger()->addMessage("Attendance Request Updated Successfully!!");
                return $this->redirect()->toRoute("attendancerequest");
            }
        }
        return Helper::addFlashMessagesToArray($this, [
                    'form' => $this->form,
                    'id' => $id,
                        ]
        );
    }

    public function deleteAction() {
        $this->middleware();

        $id = (int) $this->params()->fromRoute("id");

        if (!$id) {
            return $this->redirect()->toRoute('attendancerequest');
        }
        $this->repository->delete($id);
        $this->flashmessenger()->addMessage("Attendance Request Successfully Cancelled!!!");
        return $this->redirect()->toRoute('attendancerequest');
    }

    public function viewAction() {
        $this->middleware();

        $id = (int) $this->params()->fromRoute('id', 0);
        if ($id === 0) {
            return $this->redirect()->toRoute("attedanceapprove");
        }

        $request = $this->getRequest();
        $model = new AttendanceRequestModel();
        $detail = $this->repository->fetchById($id);
        $employeeName = $detail['FULL_NAME'];

        $authRecommender = $detail['RECOMMENDED_BY_NAME'] == null ? $detail['RECOMMENDER_NAME'] : $detail['RECOMMENDED_BY_NAME'];
        $authApprover = $detail['APPROVED_BY_NAME'] == null ? $detail['APPROVER_NAME'] : $detail['APPROVED_BY_NAME'];


        if (!$request->isPost()) {
            $model->exchangeArrayFromDB($detail);
            $this->form->bind($model);
        }
        return Helper::addFlashMessagesToArray($this, [
                    'form' => $this->form,
                    'id' => $id,
                    'recommender' => $authRecommender,
                    'approver' => $authApprover,
                    'status' => $detail['STATUS'],
                    'employeeName' => $employeeName,
                    'requestedDt' => $detail['REQUESTED_DT'],
        ]);
    }
    
    public function checkInAction() {
        $this->middleware();
        
        $id = (int) $this->params()->fromRoute("id", 0);
        if ($id !== 0) {
            $attendanceDetailRepo = new AttendanceDetailRepository($this->adapter);
            $attendanceData = $attendanceDetailRepo->fetchById($id);
            $model = new AttendanceRequestModel();
            $model->attendanceDt = $attendanceData['ATTENDANCE_DT'];
            $model->inTime = $attendanceData['IN_TIME'];
            $this->form->bind($model);
        }
        $request = $this->getRequest();

        if ($request->isPost()) {
            $this->form->setData($request->getPost());
            if ($this->form->isValid()) {
                $model = new AttendanceRequestModel();
                $model->exchangeArrayFromForm($this->form->getData());
                $model->employeeId = $this->employeeId;
                $model->attendanceDt = Helper::getExpressionDate($model->attendanceDt);
                $model->id = ((int) Helper::getMaxId($this->adapter, $model::TABLE_NAME, "ID")) + 1;
                $model->inTime = Helper::getExpressionTime($model->inTime);
                $model->status = "RQ";
                $model->createdBy = $this->employeeId;
                
                $this->repository->add($model);
                $this->flashmessenger()->addMessage("Attendance Request Submitted Successfully!!");
                try {
                    HeadNotification::pushNotification(NotificationEvents::ATTENDANCE_APPLIED, $model, $this->adapter, $this);
                } catch (Exception $e) {
                    $this->flashmessenger()->addMessage($e->getMessage());
                }

                return $this->redirect()->toRoute("attendancerequest");
            }
        }
        return Helper::addFlashMessagesToArray($this, [
                    'employeeId' => $this->employeeId,
                    'form' => $this->form
                        ]
        );
    }
    
    public function checkOutAction() {
        $this->middleware();

        $id = (int) $this->params()->fromRoute("id", 0);
        if ($id !== 0) {
            $attendanceDetailRepo = new AttendanceDetailRepository($this->adapter);
            $attendanceData = $attendanceDetailRepo->fetchById($id);
            $model = new AttendanceRequestModel();
            $model->attendanceDt = $attendanceData['ATTENDANCE_DT'];
            $model->outTime = $attendanceData['OUT_TIME'];
            $this->form->bind($model);
        }
        $request = $this->getRequest();

        if ($request->isPost()) {
            $this->form->setData($request->getPost());
            if ($this->form->isValid()) {
                $model = new AttendanceRequestModel();
                $model->exchangeArrayFromForm($this->form->getData());
                $model->employeeId = $this->employeeId;
                $model->attendanceDt = Helper::getExpressionDate($model->attendanceDt);
                $model->id = ((int) Helper::getMaxId($this->adapter, $model::TABLE_NAME, "ID")) + 1;
                $model->outTime = Helper::getExpressionTime($model->outTime);
                $model->status = "RQ";
                $model->createdBy = $this->employeeId;
                if($request->getPost('nextDay')){
                    $model->nextDayOut='Y';
                }

                $this->repository->add($model);
                $this->flashmessenger()->addMessage("Attendance Request Submitted Successfully!!");
                try {
                    HeadNotification::pushNotification(NotificationEvents::ATTENDANCE_APPLIED, $model, $this->adapter, $this);
                } catch (Exception $e) {
                    $this->flashmessenger()->addMessage($e->getMessage());
                }

                return $this->redirect()->toRoute("attendancerequest");
            }
        }
        return Helper::addFlashMessagesToArray($this, [
                    'employeeId' => $this->employeeId,
                    'form' => $this->form
                        ]
        );
    }

}
