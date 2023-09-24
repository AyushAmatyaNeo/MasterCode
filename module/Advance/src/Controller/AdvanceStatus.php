<?php

namespace Advance\Controller;

use Advance\Form\AdvanceRequestForm;
use Advance\Model\AdvancePayment;
use Advance\Model\AdvanceRequestModel;
use Advance\Model\AdvanceSetupModel;
use Advance\Repository\AdvanceApproveRepository;
use Advance\Repository\AdvancePaymentRepository;
use Advance\Repository\AdvanceStatusRepository;
use Application\Controller\HrisController;
use Application\Helper\EntityHelper;
use Application\Helper\Helper;
use Exception;
use Setup\Model\HrEmployees;
use Advance\Repository\AdvanceRequestRepository;
use Notification\Controller\HeadNotification;
use Notification\Model\NotificationEvents;
use Zend\Authentication\Storage\StorageInterface;
use Zend\Db\Adapter\AdapterInterface;
use Zend\View\Model\JsonModel;

class AdvanceStatus extends HrisController
{

    public function __construct(AdapterInterface $adapter, StorageInterface $storage)
    {
        parent::__construct($adapter, $storage);
        $this->initializeRepository(AdvanceStatusRepository::class);
        $this->initializeForm(AdvanceRequestForm::class);
    }

    public function indexAction()
    {
        $request = $this->getRequest();
        if ($request->isPost()) {
            try {
                $searchQuery = $request->getPost();
                $rawList = $this->repository->getFilteredRecord($searchQuery);
                $list = Helper::extractDbData($rawList);
                return new JsonModel(['success' => true, 'data' => $list, 'error' => '']);
            } catch (Exception $e) {
                return new JsonModel(['success' => false, 'data' => [], 'error' => $e->getMessage()]);
            }
        }

        $statusSE = $this->getStatusSelectElement(['name' => 'status', 'id' => 'status', 'class' => 'form-control reset-field', 'label' => 'Status']);
        return Helper::addFlashMessagesToArray($this, [
            'status' => $statusSE,
            'searchValues' => EntityHelper::getSearchData($this->adapter),
            'acl' => $this->acl,
            'employeeDetail' => $this->storageData['employee_detail'],
            'preference' => $this->preference
        ]);
    }

    public function viewAction()
    {
        $id = (int) $this->params()->fromRoute('id');
        if ($id === 0) {
            return $this->redirect()->toRoute("advanceStatus");
        }

        $request = $this->getRequest();
        $advanceRequestModel = new AdvanceRequestModel();
        $advanceApproveRepository = new AdvanceApproveRepository($this->adapter);

        $detail = $advanceApproveRepository->fetchById($id);

        if ($request->isPost()) {
            $getData = $request->getPost();
            $action = $getData->submit;

            $advanceRequestModel->recommendedDate = Helper::getcurrentExpressionDate();
            $advanceRequestModel->approvedDate = Helper::getcurrentExpressionDate();
            if ($action == "Reject") {
                $advanceRequestModel->status = "R";
                $this->flashmessenger()->addMessage("Advance Request Rejected!!!");
            } else if ($action == "Approve") {
                $advanceRequestModel->status = "AP";
                $this->flashmessenger()->addMessage("Advamce Request Approved");
            }
            $advanceRequestModel->recommendedBy = $this->employeeId;
            $advanceRequestModel->recommendedRemarks = $getData->approvedRemarks;
            $advanceRequestModel->approvedBy = $this->employeeId;
            $advanceRequestModel->approvedRemarks = $getData->approvedRemarks;

            $this->advancePaymentAdd($detail);
            $advanceApproveRepository->edit($advanceRequestModel, $id);

            try {
                $advanceRequestModel->advanceRequestId = $id;
                HeadNotification::pushNotification(($advanceRequestModel->status == 'AP') ? NotificationEvents::ADVANCE_APPROVE_ACCEPTED : NotificationEvents::ADVANCE_APPROVE_REJECTED, $advanceRequestModel, $this->adapter, $this);
            } catch (Exception $e) {
                $this->flashmessenger()->addMessage($e->getMessage());
            }

            return $this->redirect()->toRoute("advanceStatus");
        }

        $recommApprove = ($detail['RECOMMENDER_ID'] == $detail['APPROVER_ID']) ? 1 : 0;
        //
        $authRecommender = $detail['RECOMMENDED_BY_NAME'] == null ? $detail['RECOMMENDER_NAME'] : $detail['RECOMMENDED_BY_NAME'];
        $authApprover = $detail['APPROVED_BY_NAME'] == null ? $detail['APPROVER_NAME'] : $detail['APPROVED_BY_NAME'];
        //
        $advanceRequestModel->exchangeArrayFromDB($detail);
        $this->form->bind($advanceRequestModel);

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
            'advances' => EntityHelper::getTableKVListWithSortOption($this->adapter, AdvanceSetupModel::TABLE_NAME, AdvanceSetupModel::ADVANCE_ID, [AdvanceSetupModel::ADVANCE_ENAME], ["STATUS" => 'E'], AdvanceSetupModel::ADVANCE_ENAME, "ASC", " ", FALSE, TRUE),
            'advanceRequestData' => $detail,
            'recommApprove' => $recommApprove
        ]);
    }

    public function advancePaymentAdd($details)
    {

        $advancePaymentRepository = new AdvancePaymentRepository($this->adapter);
        $advanceRequestRepo = new AdvanceRequestRepository($this->adapter);
        $advanceRequestId = $details['ADVANCE_REQUEST_ID'];
        $requestedAmt = $details['REQUESTED_AMOUNT'];
        // $employeeSalary = $details['SALARY'];
        $historySalary = $advanceRequestRepo->getSalaryHistory($details['EMPLOYEE_ID']);
        $payrollSalary = $advanceRequestRepo->getPayrollBasicSalary($details['EMPLOYEE_ID']);
        $basicSalary = EntityHelper::getTableList($this->adapter, HrEmployees::TABLE_NAME, ['SALARY'], [HrEmployees::EMPLOYEE_ID => $details['EMPLOYEE_ID']]);
        $employeeSalary  = $historySalary['SALARY'] ? $historySalary['SALARY'] : ($payrollSalary['FLAT_VALUE'] ? $payrollSalary['FLAT_VALUE'] : $basicSalary[0]['SALARY']);
        $monthlyDeductionRate = $details['DEDUCTION_RATE'];
        $advanceDate = $details['DATE_OF_ADVANCE'];
        $monthlyDedeuctionAmt = ($monthlyDeductionRate / 100) * $employeeSalary;
        $monthCodeDetails = $advancePaymentRepository->getMonthCode($advanceDate);

        $nepYear = $monthCodeDetails['YEAR'];
        $nepMonth = $monthCodeDetails['MONTH_ID'];


        $actualPyamentMonths = ceil($requestedAmt / $monthlyDedeuctionAmt);

        $advancePayment = new AdvancePayment();
        $advancePayment->advanceRequestId = $advanceRequestId;
        $advancePayment->createdBy = $this->employeeId;
        $advancePayment->status = 'PE';

        for ($i = 1; $i <= $actualPyamentMonths; $i++) {
            $advancePayment->id = (int) Helper::getMaxId($this->adapter, AdvancePayment::TABLE_NAME, AdvancePayment::ID) + 1;
            $advancePayment->amount = ($requestedAmt > $monthlyDedeuctionAmt) ? $monthlyDedeuctionAmt : $requestedAmt;
            $advancePayment->nepYear = $nepYear;
            $advancePayment->nepMonth = $nepMonth;

            $requestedAmt = $requestedAmt - $monthlyDedeuctionAmt;
            if ($nepMonth == 12) {
                $nepYear = $nepYear + 1;
                $nepMonth = 1;
            } else {
                $nepMonth = $nepMonth + 1;
            }
            $advancePaymentRepository->add($advancePayment);
        }
    }

    public function paymentViewAction()
    {
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
            'id' => $id,
            'acl' => $this->acl
        ]);
    }

    public function skipAdvanceAction()
    {
        $request = $this->getRequest();
        if ($request->isPost()) {
            try {
                $data = $request->getPost();
                $id = (int) $this->params()->fromRoute('id', 0);

                $paymentRepository = new AdvancePaymentRepository($this->adapter);
                // $paymentRepository->skipAdvance($data['year'], $data['month'], $id, $this->employeeId);
                return new JsonModel(['success' => true, 'data' => $data, 'error' => '']);
            } catch (Exception $e) {
                return new JsonModel(['success' => false, 'data' => [], 'error' => $e->getMessage()]);
            }
        }
    }

    public function skipAction()
    {
        $id = (int) $this->params()->fromRoute('id', 0);
        $paymentRepository = new AdvancePaymentRepository($this->adapter);

        $data = $paymentRepository->getYearMonth($id);

        $paymentRepository->skipAdvance($data['NEP_YEAR'], $data['NEP_MONTH'], $id, $data['ADVANCE_REQUEST_ID'], $this->employeeId);
        // return new JsonModel(['success' => true, 'data' => $data, 'error' => '']);
        return $this->redirect()->toRoute("advanceStatus");
    }

    public function bulkAction()
    {
        $request = $this->getRequest();
        try {
            $postData = $request->getPost();
            $this->makeDecision($postData['id'], $postData['action'] == "approve");
            return new JsonModel(['success' => true, 'data' => null]);
        } catch (Exception $e) {
            return new JsonModel(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    private function makeDecision($id, $approve, $remarks = null, $enableFlashNotification = false)
    {
        $model = new AdvanceRequestModel();
        $advanceApproveRepository = new AdvanceApproveRepository($this->adapter);
        $detail = $advanceApproveRepository->fetchById($id);
        $model->advanceRequestId = $id;
        $model->recommendedDate = Helper::getcurrentExpressionDate();
        $model->recommendedBy = $this->employeeId;
        $model->approvedRemarks = $remarks;
        $model->approvedDate = Helper::getcurrentExpressionDate();
        $model->approvedBy = $this->employeeId;
        $model->status = $approve ? "AP" : "R";
        $message = $approve ? "WOD Request Approved" : "WOD Request Rejected";
        $notificationEvent = $approve ? NotificationEvents::ADVANCE_APPROVE_ACCEPTED : NotificationEvents::ADVANCE_APPROVE_REJECTED;
        $this->advancePaymentAdd($detail);
        $advanceApproveRepository->edit($model, $id);
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
