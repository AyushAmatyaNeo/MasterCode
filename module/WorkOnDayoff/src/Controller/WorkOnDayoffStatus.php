<?php

namespace WorkOnDayoff\Controller;

use Application\Controller\HrisController;
use Application\Helper\EntityHelper;
use Application\Helper\Helper;
use Exception;
use ManagerService\Repository\DayoffWorkApproveRepository;
use Notification\Controller\HeadNotification;
use Notification\Model\NotificationEvents;
use SelfService\Form\WorkOnDayoffForm;
use SelfService\Model\WorkOnDayoff;
use WorkOnDayoff\Repository\WorkOnDayoffStatusRepository;
use Zend\Authentication\Storage\StorageInterface;
use Zend\Db\Adapter\AdapterInterface;
use Zend\View\Model\JsonModel;

class WorkOnDayoffStatus extends HrisController {

    private $dayoffWorkApproveRepository;
    private $workonDayoffStatusRepository;

    public function __construct(AdapterInterface $adapter, StorageInterface $storage) {
        parent::__construct($adapter, $storage);
        $this->dayoffWorkApproveRepository = new DayoffWorkApproveRepository($adapter);
        $this->workonDayoffStatusRepository = new WorkOnDayoffStatusRepository($adapter);
        $this->initializeForm(WorkOnDayoffForm::class);
    }

    public function indexAction() {
        $statusSE = $this->getStatusSelectElement(['name' => 'status', "id" => "requestStatusId", "class" => "form-control reset-field", 'label' => 'Status']);
        return $this->stickFlashMessagesTo([
                    'status' => $statusSE,
                    'searchValues' => EntityHelper::getSearchData($this->adapter),
                    'acl' => $this->acl,
                    'employeeDetail' => $this->storageData['employee_detail'],
                    'preference' => $this->preference
        ]);
    }

    public function viewAction() {
        $id = (int) $this->params()->fromRoute('id');

        if ($id === 0) {
            return $this->redirect()->toRoute("workOnDayoffStatus");
        }
        $workOnDayoffModel = new WorkOnDayoff();
        $request = $this->getRequest();

        $detail = $this->dayoffWorkApproveRepository->fetchById($id);
        $status = $detail['STATUS'];
        $employeeId = $detail['EMPLOYEE_ID'];

        $recommApprove = $detail['RECOMMENDER_ID'] == $detail['APPROVER_ID'] ? 1 : 0;

        $employeeName = $detail['FULL_NAME'];
        $authRecommender = $detail['RECOMMENDED_BY_NAME'] == null ? $detail['RECOMMENDER_NAME'] : $detail['RECOMMENDED_BY_NAME'];
        $authApprover = $detail['APPROVED_BY_NAME'] == null ? $detail['APPROVER_NAME'] : $detail['APPROVED_BY_NAME'];


        if (!$request->isPost()) {
            $workOnDayoffModel->exchangeArrayFromDB($detail);
            $this->form->bind($workOnDayoffModel);
        } else {
            $getData = $request->getPost();
            $reason = $getData->approvedRemarks;
            $action = $getData->submit;

            $workOnDayoffModel->approvedDate = Helper::getcurrentExpressionDate();
            if ($action == "Reject") {
                $workOnDayoffModel->status = "R";
                $this->flashmessenger()->addMessage("Work on Day-off Request Rejected!!!");
            } else if ($action == "Approve") {
                try {
                    $this->wodApproveAction($detail);
                    $this->flashmessenger()->addMessage("Work on Day-off Request Approved");
                } catch (Exception $e) {
                    $this->flashmessenger()->addMessage("Work on Day-off Request Approved but reward is not provided as employee position is not set.");
                }
                $workOnDayoffModel->status = "AP";
            }
            $workOnDayoffModel->approvedBy = $this->employeeId;
            $workOnDayoffModel->approvedRemarks = $reason;
            $workOnDayoffModel->employeeId = $detail['EMPLOYEE_ID'];
            $workOnDayoffModel->fromDate = $detail['FROM_DATE'];
            $workOnDayoffModel->toDate = $detail['TO_DATE'];
            // echo '<pre>';print_r($workOnDayoffModel);die;
            $this->dayoffWorkApproveRepository->edit($workOnDayoffModel, $id);

            return $this->redirect()->toRoute("workOnDayoffStatus");
        }
        return Helper::addFlashMessagesToArray($this, [
                    'form' => $this->form,
                    'id' => $id,
                    'employeeId' => $employeeId,
                    'employeeName' => $employeeName,
                    'requestedDt' => $detail['REQUESTED_DATE'],
                    'recommender' => $authRecommender,
                    'approvedDT' => $detail['APPROVED_DATE'],
                    'approver' => $authApprover,
                    'status' => $status,
                    'customRenderer' => Helper::renderCustomView(),
                    'recommApprove' => $recommApprove,
                    'acl' => $this->acl,
        ]);
    }

    private function wodApproveAction($detail) {
        $this->dayoffWorkApproveRepository->wodReward($detail['ID']);
    }

    public function pullDayoffWorkRequestStatusListAction() {
        try {
            $request = $this->getRequest();
            $data = $request->getPost();
            $dayoffWorkStatusRepo = new WorkOnDayoffStatusRepository($this->adapter);
            $recordList = Helper::extractDbData($dayoffWorkStatusRepo->getWODReqList($data));
            return new JsonModel([
                "success" => "true",
                "data" => $recordList,
            ]);
        } catch (Exception $e) {
            return new JsonModel(['success' => false, 'data' => null, 'message' => $e->getMessage()]);
        }
    }

    public function bulkAction() {
        $request = $this->getRequest();
        try {
            $postData = $request->getPost();
            if ($postData['super_power'] == 'true') {
                $this->makeSuperDecision($postData['id'], $postData['action'] == "approve");
            } else {
                if ($postData['status'] == 'Rejected' || $postData['status'] == 'Cancelled' || $postData['status'] == 'Approved') {
                    
                } else {
                    $this->makeDecision($postData['id'], $postData['action'] == "approve");
                }
            }
            return new JsonModel(['success' => true, 'data' => null]);
        } catch (Exception $e) {
            return new JsonModel(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    private function makeDecision($id, $approve, $remarks = null, $enableFlashNotification = false) {
        $model = new WorkOnDayoff();
        $model->id = $id;
        $model->recommendedDate = Helper::getcurrentExpressionDate();
        $model->recommendedBy = $this->employeeId;
        $model->approvedRemarks = $remarks;
        $model->approvedDate = Helper::getcurrentExpressionDate();
        $model->approvedBy = $this->employeeId;
        $model->status = $approve ? "AP" : "R";
        $message = $approve ? "WOD Request Approved" : "WOD Request Rejected";
        $notificationEvent = $approve ? NotificationEvents::WORKONDAYOFF_APPROVE_ACCEPTED : NotificationEvents::WORKONDAYOFF_APPROVE_REJECTED;
        // echo '<pre>';print_r($model);die;
        $this->dayoffWorkApproveRepository->edit($model, $id);
        if ($enableFlashNotification) {
            $this->flashmessenger()->addMessage($message);
        }
        try {
            HeadNotification::pushNotification($notificationEvent, $model, $this->adapter, $this);
        } catch (Exception $e) {
            $this->flashmessenger()->addMessage($e->getMessage());
        }
    }

    private function makeSuperDecision($id, $approve, $remarks = null, $enableFlashNotification = false) {
        $dayoffWorkApproveRepository = new DayoffWorkApproveRepository($this->adapter);
        $detail = $dayoffWorkApproveRepository->fetchById($id);
        if ($detail['STATUS'] == 'AP') {
            $model = new WorkOnDayoff();
            $model->id = $id;
            $model->recommendedDate = Helper::getcurrentExpressionDate();
            $model->recommendedBy = $this->employeeId;
            $model->approvedRemarks = $remarks;
            $model->approvedDate = Helper::getcurrentExpressionDate();
            $model->approvedBy = $this->employeeId;
            $model->status = $approve ? "AP" : "R";
            $message = $approve ? "WOD Request Approved" : "WOD Request Rejected";
            $notificationEvent = $approve ? NotificationEvents::WORKONDAYOFF_APPROVE_ACCEPTED : NotificationEvents::WORKONDAYOFF_APPROVE_REJECTED;
            $this->dayoffWorkApproveRepository->edit($model, $id);
            if ($enableFlashNotification) {
                $this->flashmessenger()->addMessage($message);
            }
            try {
                HeadNotification::pushNotification($notificationEvent, $model, $this->adapter, $this);
            } catch (Exception $e) {
                $this->flashmessenger()->addMessage($e->getMessage());
            }
        }
    }

}
