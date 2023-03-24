<?php

namespace Advance\Controller;

use Advance\Form\AdvanceRequestForm;
use Advance\Model\AdvanceRequestModel;
use Advance\Model\AdvanceSetupModel;
use Advance\Repository\AdvancePaymentRepository;
use Advance\Repository\AdvanceRequestRepository;
use Application\Controller\HrisController;
use Application\Helper\EntityHelper;
use Application\Helper\Helper;
use Exception;
use Notification\Controller\HeadNotification;
use Notification\Model\NotificationEvents;
use Setup\Model\HrEmployees;
use Zend\Authentication\Storage\StorageInterface;
use Zend\Db\Adapter\AdapterInterface;
use Zend\View\Model\JsonModel;

class AdvanceRequest extends HrisController {

    public function __construct(AdapterInterface $adapter, StorageInterface $storage) {
        parent::__construct($adapter, $storage);
        $this->initializeRepository(AdvanceRequestRepository::class);
        $this->initializeForm(AdvanceRequestForm::class);
    }

    public function indexAction() {

        $request = $this->getRequest();
        if ($request->isPost()) {
            try {
                $data = $request->getPost();
                $rawList = $this->repository->getfilterRecords($data);
                $list = Helper::extractDbData($rawList);
                return new JsonModel(['success' => true, 'data' => $list, 'error' => '']);
            } catch (Exception $e) {
                return new JsonModel(['success' => false, 'data' => [], 'error' => $e->getMessage()]);
            }
        }


        return Helper::addFlashMessagesToArray($this, [
                    'employeeId' => $this->employeeId,
                    'acl' => $this->acl
        ]);
    }

    public function addAction() {
        $request = $this->getRequest();
        if ($request->isPost()) {
            $postData = $request->getPost();
            $this->form->setData($postData);

            if ($this->form->isValid()) {
                $advanceRequestModel = new AdvanceRequestModel();
                $advanceRequestModel->exchangeArrayFromForm($this->form->getData());

                $advanceRequestModel->deductionType = $postData['deductionType'];
                $advanceRequestModel->advanceRequestId = (int) Helper::getMaxId($this->adapter, AdvanceRequestModel::TABLE_NAME, AdvanceRequestModel::ADVANCE_REQUEST_ID) + 1;
                $advanceRequestModel->status = "RQ";

                $this->flashmessenger()->addMessage("Advance Request Successfully added!!!");

                $this->repository->add($advanceRequestModel);
                try {
                    HeadNotification::pushNotification(NotificationEvents::ADVANCE_APPLIED, $advanceRequestModel, $this->adapter, $this);
                } catch (Exception $e) {
                    $this->flashmessenger()->addMessage($e->getMessage());
                }

                return $this->redirect()->toRoute("advance-request");
            }
        }
        $advanceList = $this->repository->fetchAvailableAdvacenList($this->employeeId);

        $basicSalary = EntityHelper::getTableList($this->adapter, HrEmployees::TABLE_NAME, ['SALARY'], [HrEmployees::EMPLOYEE_ID => $this->employeeId]);
        $empSalary = $basicSalary[0]['SALARY'] ? $basicSalary[0]['SALARY'] : 0;
		return Helper::addFlashMessagesToArray($this, [
                    'form' => $this->form,
                    'employeeId' => $this->employeeId,
                    'advance' => $advanceList,
                    'customRenderer' => Helper::renderCustomView(),
                    'employeeList' => EntityHelper::getTableList($this->adapter, HrEmployees::TABLE_NAME, [HrEmployees::EMPLOYEE_ID, HrEmployees::FULL_NAME], [HrEmployees::STATUS => "E", HrEmployees::RETIRED_FLAG => "N"]),
                    'salary' => $empSalary
        ]);
    }

    public function viewAction() {
        $id = (int) $this->params()->fromRoute('id', 0);
        if ($id === 0) {
            return $this->redirect()->toRoute("advance-request");
        }
        $detail = $this->repository->fetchById($id);
        $authRecommender = $detail['RECOMMENDED_BY_NAME'] == null ? $detail['RECOMMENDER_NAME'] : $detail['RECOMMENDED_BY_NAME'];
        $authApprover = $detail['APPROVED_BY_NAME'] == null ? $detail['APPROVER_NAME'] : $detail['APPROVED_BY_NAME'];

        $advanceRequestmodel = new AdvanceRequestModel();
        $advanceRequestmodel->exchangeArrayFromDB($detail);
        $this->form->bind($advanceRequestmodel);

        return Helper::addFlashMessagesToArray($this, [
                    'form' => $this->form,
                    'id' => $id,
                    'employeeName' => $detail['FULL_NAME'],
                    'employeeId' => $detail['EMPLOYEE_ID'],
                    'status' => $detail['STATUS'],
                    'statusDetail' => $detail['STATUS_DETAIL'],
                    'requestedDate' => $detail['REQUESTED_DATE'],
                    'recommender' => $authRecommender,
                    'approver' => $authApprover,
//                    'advances' => EntityHelper::getTableList($this->adapter, AdvanceSetupModel::TABLE_NAME, ['*'], [AdvanceSetupModel::STATUS => 'E']),
                    'advances' => EntityHelper::getTableKVListWithSortOption($this->adapter, AdvanceSetupModel::TABLE_NAME, AdvanceSetupModel::ADVANCE_ID, [AdvanceSetupModel::ADVANCE_ENAME], ["STATUS" => 'E'], AdvanceSetupModel::ADVANCE_ENAME, "ASC", " ", FALSE, TRUE),
                    'advanceRequestData' => $detail
        ]);
    }

    public function deleteAction() {
        $id = (int) $this->params()->fromRoute("id");
        if (!$id) {
            return $this->redirect()->toRoute('advance-request');
        }
        $this->repository->delete($id);
        $this->flashmessenger()->addMessage("Advance Request Successfully Cancelled!!!");
        return $this->redirect()->toRoute('advance-request');
    }

    public function paymentViewAction() {
        $id = (int) $this->params()->fromRoute('id', 0);
        if ($id === 0) {
            return $this->redirect()->toRoute("advance-request");
        }

        $request = $this->getRequest();
        if ($request->isPost()) {
            try {
                $data = $request->getPost();
                $paymentRepository = new AdvancePaymentRepository($this->adapter);
                $rawList = $paymentRepository->getPaymentStatus($id);
                $list = Helper::extractDbData($rawList);
                return new JsonModel(['success' => true, 'data' => $list, 'error' => '']);
            } catch (Exception $e) {
                return new JsonModel(['success' => false, 'data' => [], 'error' => $e->getMessage()]);
            }
        }

        return Helper::addFlashMessagesToArray($this, [
                    'acl' => $this->acl
        ]);
    }

}
