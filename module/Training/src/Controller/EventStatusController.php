<?php

namespace Training\Controller;

use Application\Controller\HrisController;
use Application\Helper\EntityHelper;
use Application\Helper\Helper;
use Exception;
use ManagerService\Repository\EventApproveRepository;
use Notification\Controller\HeadNotification;
use Notification\Model\NotificationEvents;
use SelfService\Form\EventRequestForm;
use SelfService\Model\EventRequest;
use Zend\Authentication\Storage\StorageInterface;
use Zend\Db\Adapter\AdapterInterface;
use Zend\View\Model\JsonModel;

class EventStatusController extends HrisController
{

    public function __construct(AdapterInterface $adapter, StorageInterface $storage)
    {
        parent::__construct($adapter, $storage);
        $this->initializeRepository(EventApproveRepository::class);
        $this->initializeForm(EventRequestForm::class);
    }

    public function indexAction()
    {
        $request = $this->getRequest();
        if ($request->isPost()) {
            try {
                $searchQuery = $request->getPost();
                $list = $this->repository->getListAdmin((array)$searchQuery);
                return new JsonModel(['success' => true, 'data' => $list, 'error' => '']);
            } catch (Exception $e) {
                return new JsonModel(['success' => false, 'data' => [], 'error' => $e->getMessage()]);
            }
        }
        $statusSE = $this->getStatusSelectElement(['name' => 'status', 'id' => 'status', 'class' => 'form-control reset-field', 'label' => 'Status']);
        return $this->stickFlashMessagesTo([
            'status' => $statusSE,
            'searchValues' => EntityHelper::getSearchData($this->adapter),
            'acl' => $this->acl,
            'employeeDetail' => $this->storageData['employee_detail'],
            'preference' => $this->preference
        ]);
    }

    public function viewAction()
    {
        $id = (int)$this->params()->fromRoute('id');
        if ($id === 0) {
            return $this->redirect()->toRoute("eventApprove");
        }
        $eventRequestModel = new EventRequest();
        $request = $this->getRequest();

        $detail = $this->repository->fetchById($id);
        if ($request->isPost()) {
            $getData = $request->getPost();
            $action = $getData->submit;
            $eventRequestModel->recommendedBy = $this->employeeId;
            $eventRequestModel->recommendedDate = Helper::getcurrentExpressionDate();
            $eventRequestModel->approvedDate = Helper::getcurrentExpressionDate();
            $eventRequestModel->approvedBy = $this->employeeId;
            if ($action == "Reject") {
                $eventRequestModel->status = "R";
                $this->flashmessenger()->addMessage("Event Request Rejected!!!");
            } else if ($action == "Approve") {
                $eventRequestModel->status = "AP";
                $this->flashmessenger()->addMessage("Event Request Approved");
            }
            $this->repository->edit($eventRequestModel, $id);
            $eventRequestModel->requestId = $id;
            try {
                // HeadNotification::pushNotification(($eventRequestModel->status == 'AP') ? NotificationEvents::TRAINING_APPROVE_ACCEPTED : NotificationEvents::TRAINING_APPROVE_REJECTED, $eventRequestModel, $this->adapter, $this);
            } catch (Exception $e) {
                $this->flashmessenger()->addMessage($e->getMessage());
            }
            return $this->redirect()->toRoute("eventStatus");
        }
        $eventRequestModel->exchangeArrayFromDB($detail);
        $this->form->bind($eventRequestModel);
        return Helper::addFlashMessagesToArray($this, [
            'form' => $this->form,
            'id' => $id,
            'detail' => $detail,
            'acl' => $this->acl,
            'customRenderer' => Helper::renderCustomView()
        ]);
    }

    public function bulkAction()
    {
        $request = $this->getRequest();
        try {
            $postData = $request->getPost();
            if ($postData['super_power'] == 'true') {
                $this->makeSuperDecision($postData['id'], $postData['action'] == "approve");
            } else {
                $this->makeDecision($postData['id'], $postData['action'] == "approve");
            }
            return new JsonModel(['success' => true, 'data' => null]);
        } catch (Exception $e) {
            return new JsonModel(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    private function makeDecision($id, $approve, $remarks = null, $enableFlashNotification = false)
    {
        $detail = $this->repository->fetchById($id);

        if ($detail['STATUS'] == 'RQ' || $detail['STATUS'] == 'RC') {
            $model = new EventRequest();
            $model->requestId = $id;
            $model->recommendedDate = Helper::getcurrentExpressionDate();
            $model->recommendedBy = $this->employeeId;
            $model->approvedRemarks = $remarks;
            $model->approvedDate = Helper::getcurrentExpressionDate();
            $model->approvedBy = $this->employeeId;
            $model->status = $approve ? "AP" : "R";
            $message = $approve ? "Event Request Approved" : "Event Request Rejected";
            // $notificationEvent = $approve ? NotificationEvents::TRAINING_APPROVE_ACCEPTED : NotificationEvents::TRAINING_APPROVE_REJECTED;
            $this->repository->edit($model, $id);
            if ($enableFlashNotification) {
                $this->flashmessenger()->addMessage($message);
            }
            try {
                // HeadNotification::pushNotification($notificationEvent, $model, $this->adapter, $this);
            } catch (Exception $e) {
                $this->flashmessenger()->addMessage($e->getMessage());
            }
        }
    }

    private function makeSuperDecision($id, $approve, $remarks = null, $enableFlashNotification = false)
    {

        $detail = $this->repository->fetchById($id);

        if ($detail['STATUS'] == 'AP') {
            $model = new EventRequest();
            $model->requestId = $id;
            $model->recommendedDate = Helper::getcurrentExpressionDate();
            $model->recommendedBy = $this->employeeId;
            $model->approvedRemarks = $remarks;
            $model->approvedDate = Helper::getcurrentExpressionDate();
            $model->approvedBy = $this->employeeId;
            $model->status = $approve ? "AP" : "R";
            $message = $approve ? "Event Request Approved" : "Event Request Rejected";
            // $notificationEvent = $approve ? NotificationEvents::TRAINING_APPROVE_ACCEPTED : NotificationEvents::TRAINING_APPROVE_REJECTED;
            $this->repository->edit($model, $id);
            if ($enableFlashNotification) {
                $this->flashmessenger()->addMessage($message);
            }
            try {
                // HeadNotification::pushNotification($notificationEvent, $model, $this->adapter, $this);
            } catch (Exception $e) {
                $this->flashmessenger()->addMessage($e->getMessage());
            }
        }
    }

}
