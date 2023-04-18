<?php

namespace SelfService\Controller;

use Application\Controller\HrisController;
use Application\Helper\Helper;
use Exception;
use Notification\Controller\HeadNotification;
use Notification\Model\NotificationEvents;
use SelfService\Form\WorkOnDayoffForm;
use SelfService\Model\WorkOnDayoff as WorkOnDayoffModel;
use SelfService\Repository\WorkOnDayoffRepository;
use Zend\Authentication\Storage\StorageInterface;
use Zend\Db\Adapter\AdapterInterface;
use Zend\View\Model\JsonModel;

class WorkOnDayoff extends HrisController {

    public function __construct(AdapterInterface $adapter, StorageInterface $storage) {
        parent::__construct($adapter, $storage);
        $this->initializeRepository(WorkOnDayoffRepository::class);
        $this->initializeForm(WorkOnDayoffForm::class);
    }

    public function indexAction() {
        $request = $this->getRequest();
        if ($request->isPost()) {
            try {
                $rawList = $this->repository->getAllByEmployeeId($this->employeeId);
                $list = Helper::extractDbData($rawList);
                return new JsonModel(['success' => true, 'data' => $list, 'error' => '']);
            } catch (Exception $e) {
                return new JsonModel(['success' => false, 'data' => [], 'error' => $e->getMessage()]);
            }
        }

        return $this->stickFlashMessagesTo([]);
    }

    public function addAction() {
        $request = $this->getRequest();

        $model = new WorkOnDayoffModel();
        if ($request->isPost()) {
            $this->form->setData($request->getPost());
            if ($this->form->isValid()) {
                $model->exchangeArrayFromForm($this->form->getData());
                $model->id = ((int) Helper::getMaxId($this->adapter, WorkOnDayoffModel::TABLE_NAME, WorkOnDayoffModel::ID)) + 1;
                $model->employeeId = $this->employeeId;
                $model->requestedDate = Helper::getcurrentExpressionDate();
                $model->status = 'RQ';
                $this->repository->add($model);
                $this->flashmessenger()->addMessage("Work on Day-off Request Successfully added!!!");
                try {
                    HeadNotification::pushNotification(NotificationEvents::WORKONDAYOFF_APPLIED, $model, $this->adapter, $this);
                } catch (Exception $e) {
                    $this->flashmessenger()->addMessage($e->getMessage());
                }
                return $this->redirect()->toRoute("workOnDayoff");
            }
        }
        return Helper::addFlashMessagesToArray($this, [
                    'form' => $this->form,
                    'employeeId' => $this->employeeId
        ]);
    }

    public function deleteAction() {
        $id = (int) $this->params()->fromRoute("id");
        if (!$id) {
            return $this->redirect()->toRoute('workOnDayoff');
        }
        $this->repository->delete($id);
        $this->flashmessenger()->addMessage("Work on Day-off Request Successfully Cancelled!!!");
        return $this->redirect()->toRoute('workOnDayoff');
    }

    public function viewAction() {
        $id = (int) $this->params()->fromRoute('id', 0);
        if ($id === 0) {
            return $this->redirect()->toRoute("workOnDayoff");
        }
        $detail = $this->repository->fetchById($id);

        $model = new WorkOnDayoffModel();
        $model->exchangeArrayFromDB($detail);
        $this->form->bind($model);
        return Helper::addFlashMessagesToArray($this, [
                    'form' => $this->form,
                    'employeeName' => $detail['FULL_NAME'],
                    'status' => $detail['STATUS'],
                    'statusDetail' => $detail['STATUS_DETAIL'],
                    'requestedDate' => $detail['REQUESTED_DATE'],
                    'recommender' => $detail['RECOMMENDED_BY_NAME'] ? $detail['RECOMMENDED_BY_NAME'] : $detail['RECOMMENDER_NAME'],
                    'approver' => $detail['APPROVED_BY_NAME'] ? $detail['APPROVED_BY_NAME'] : $detail['APPROVER_NAME'],
        ]);
    }

    public function validateWODRequestAction() {
        try {
            $request = $this->getRequest();
            if ($request->isPost()) {
                $postedData = $request->getPost();
                $error = $this->repository->validateWODRequest(Helper::getExpressionDate($postedData['startDate'])->getExpression(), Helper::getExpressionDate($postedData['endDate'])->getExpression(), $postedData['employeeId']);
                // echo '<pre>';print_r($error);die;
                return new JsonModel(['success' => true, 'data' => $error, 'error' => '']);
            } else {
                throw new Exception("The request should be of type post");
            }
        } catch (Exception $e) {
            return new JsonModel(['success' => false, 'data' => [], 'error' => $e->getMessage()]);
        }
    }

}
