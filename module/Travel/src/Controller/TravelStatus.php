<?php

namespace Travel\Controller;

use Application\Controller\HrisController;
use Application\Helper\EntityHelper;
use Application\Helper\Helper;
use Application\Helper\NumberHelper;
use Exception;
use ManagerService\Repository\TravelApproveRepository;
use Notification\Controller\HeadNotification;
use Notification\Model\NotificationEvents;
use SelfService\Form\TravelRequestForm;
use SelfService\Model\TravelRequest;
use SelfService\Model\TravelRequest as TravelRequestModel;
use SelfService\Repository\TravelExpenseDtlRepository;
use SelfService\Repository\TravelRequestRepository;
use Travel\Repository\TravelItnaryRepository;
use Travel\Repository\TravelStatusRepository;
use Zend\Authentication\Storage\StorageInterface;
use Zend\Db\Adapter\AdapterInterface;
use Zend\View\Model\JsonModel;

class TravelStatus extends HrisController {

    private $travelApproveRepository;
    private $travelStatusRepository;

    public function __construct(AdapterInterface $adapter, StorageInterface $storage) {
        parent::__construct($adapter, $storage);
        $this->initializeForm(TravelRequestForm::class);
        $this->travelApproveRepository = new TravelApproveRepository($adapter);
        $this->travelStatusRepository = new TravelStatusRepository($adapter);
        $this->travelRequestRepository = new TravelRequestRepository($adapter);
    }

    public function indexAction() {
        $request = $this->getRequest();
        if ($request->isPost()) {
            try {
                $search = $request->getPost();
                $list = $this->travelStatusRepository->getFilteredRecord($search);

                if($this->preference['displayHrApproved'] == 'Y'){
                    for($i = 0; $i < count($list); $i++){
                        if($list[$i]['HARDCOPY_SIGNED_FLAG'] == 'Y'){
                            $list[$i]['APPROVER_ID'] = '-1';
                            $list[$i]['APPROVER_NAME'] = 'HR';
                            $list[$i]['RECOMMENDER_ID'] = '-1';
                            $list[$i]['RECOMMENDER_NAME'] = 'HR';
                        }
                    }
                }

                return new JsonModel(['success' => true, 'data' => $list, 'error' => '']);
            } catch (Exception $e) {
                return new JsonModel(['success' => false, 'data' => [], 'error' => $e->getMessage()]);
            }
        }
        
        $statusSE = $this->getStatusSelectElement(['name' => 'status', "id" => "status", "class" => "form-control reset-field", 'label' => 'status']);
        return Helper::addFlashMessagesToArray($this, [
                    'travelStatus' => $statusSE,
                    'searchValues' => EntityHelper::getSearchData($this->adapter),
                    'acl' => $this->acl,
                    'employeeDetail' => $this->storageData['employee_detail'],
                    'preference' => $this->preference,
//                    'itnaryCodeList'=>[], 
                    'itnaryCodeList' =>EntityHelper::getTableList($this->adapter, 'HRIS_TRAVEL_ITNARY', ['ITNARY_ID','ITNARY_CODE'], ['STATUS' => "E"]),
        ]);
    }

    public function actionAction() {
        $id = (int) $this->params()->fromRoute('id');
        if ($id === 0) {
            return $this->redirect()->toRoute("travelStatus");
        }
        $request = $this->getRequest();
        if ($request->isPost()) {
            $detail = $this->travelApproveRepository->fetchById($id);
            $travelRequest = new TravelRequest();
            $getData = $request->getPost();
            $reason = $getData->approvedRemarks;
            $action = $getData->submit;
            $travelRequest->approvedDate = Helper::getcurrentExpressionDate();
            if ($action == "Reject") {
                $travelRequest->status = "R";
                $this->flashmessenger()->addMessage("Travel Request Rejected!!!");
            } else if ($action == "Approve") {
                $travelRequest->status = "AP";
                $this->flashmessenger()->addMessage("Travel Request Approved");
            }
            $travelRequest->approvedBy = $this->employeeId;
            $travelRequest->approvedRemarks = $reason;
            $travelRequest->employeeId = $detail['EMPLOYEE_ID'];
            $travelRequest->fromDate = $detail['FROM_DATE'];
            $travelRequest->toDate = $detail['TO_DATE'];
            // echo '<pre>';print_r($travelRequest);die;
            $this->travelApproveRepository->edit($travelRequest, $id);

            return $this->redirect()->toRoute("travelStatus");
        }
    }

    public function viewAction() {
        $id = (int) $this->params()->fromRoute('id');
        if ($id === 0) {
            return $this->redirect()->toRoute("travelStatus");
        }
        $travelRequestModel = new TravelRequest();
        $detail = $this->travelApproveRepository->fetchById($id);

        if($this->preference['displayHrApproved'] == 'Y' && $detail['HARDCOPY_SIGNED_FLAG'] == 'Y'){
            $detail['APPROVER_ID'] = '-1';
            $detail['APPROVER_NAME'] = 'HR';
            $detail['RECOMMENDER_ID'] = '-1';
            $detail['RECOMMENDER_NAME'] = 'HR';
            $detail['RECOMMENDED_BY_NAME'] = 'HR';
            $detail['APPROVED_BY_NAME'] = 'HR';
        }
        //$fileDetails = $this->travelApproveRepository->fetchAttachmentsById($id);
        $travelRequestModel->exchangeArrayFromDB($detail);
        $this->form->bind($travelRequestModel);
        $numberInWord = new NumberHelper();
        $advanceAmount = $numberInWord->toText($detail['REQUESTED_AMOUNT']);
        
         $travelItnaryDet = [];
        $travelItnaryMemDet = [];
        if ($detail['ITNARY_ID']) {
            $travelItnaryRepo = new TravelItnaryRepository($this->adapter);
            $travelItnaryDet = $travelItnaryRepo->fetchItnaryDetails($detail['ITNARY_ID']);
            $travelItnaryMemDet = $travelItnaryRepo->fetchItnaryMembers($detail['ITNARY_ID']);
        }

        return Helper::addFlashMessagesToArray($this, [
                    'id' => $id,
                    'form' => $this->form,
                    'recommender' => $detail['RECOMMENDED_BY_NAME'] == null ? $detail['RECOMMENDER_NAME'] : $detail['RECOMMENDED_BY_NAME'],
                    'approver' => $detail['APPROVED_BY_NAME'] == null ? $detail['APPROVER_NAME'] : $detail['APPROVED_BY_NAME'],
                    'detail' => $detail,
                    'todayDate' => date('d-M-Y'),
                    'advanceAmount' => $advanceAmount,
                    'itnaryId' => $detail['ITNARY_ID'],
                    'travelItnaryDet' => $travelItnaryDet,
                    'travelItnaryMemDet' => $travelItnaryMemDet,
                    'acl' => $this->acl
                        //'files' => $fileDetails
        ]);
    }

    public function editAction() {
        $request = $this->getRequest();

        $id = (int) $this->params()->fromRoute('id');
        if ($id === 0) {
            return $this->redirect()->toRoute("travelRequest");
        }
        if ($this->travelRequestRepository->checkAllowEdit($id) == 'N') {
            return $this->redirect()->toRoute("travelRequest");
        }

        if ($request->isPost()) {
            $travelRequest = new TravelRequestModel();
            $postedData = $request->getPost();
            $this->form->setData($postedData);

            if ($this->form->isValid()) {
                $travelRequest->exchangeArrayFromForm($this->form->getData());
                $travelRequest->modifiedDt = Helper::getcurrentExpressionDate();
                $travelRequest->employeeId = $this->employeeId;
                $this->travelRequestRepository->edit($travelRequest, $id);
                $this->flashmessenger()->addMessage("Travel Request Successfully Edited!!!");
                return $this->redirect()->toRoute("travelApply");
            }
        }

        $detail = $this->travelRequestRepository->fetchById($id);
        //$fileDetails = $this->repository->fetchAttachmentsById($id);
        $model = new TravelRequestModel();
        $model->exchangeArrayFromDB($detail);
        $this->form->bind($model);

        $numberInWord = new NumberHelper();
        $advanceAmount = $numberInWord->toText($detail['REQUESTED_AMOUNT']);

        return Helper::addFlashMessagesToArray($this, [
                    'form' => $this->form,
                    'recommender' => $detail['RECOMMENDED_BY_NAME'] == null ? $detail['RECOMMENDER_NAME'] : $detail['RECOMMENDED_BY_NAME'],
                    'approver' => $detail['APPROVED_BY_NAME'] == null ? $detail['APPROVER_NAME'] : $detail['APPROVED_BY_NAME'],
                    'detail' => $detail,
                    'todayDate' => date('d-M-Y'),
                    'advanceAmount' => $advanceAmount
                        //'files' => $fileDetails
        ]);
    }

    public function expenseDetailAction() {
        $id = (int) $this->params()->fromRoute('id');
        if ($id === 0) {
            return $this->redirect()->toRoute("travelApprove");
        }
        $detail = $this->travelApproveRepository->fetchById($id);
      //  echo '<pre>';print_r($detail);die;

        
        $allCategoryList = EntityHelper::getTableKVList($this->adapter, "HRIS_TRAVELS_EXPENSES_CATEGORY", "ID", ["CATEGORY_NAME"], null,null,true,'ID','ASC');
        $categoryWisePercentage = EntityHelper::getTableKVList($this->adapter, "HRIS_TRAVELS_EXPENSES_CATEGORY", "ID", ["ALLOWANCE_PERCENTAGE"], null,null,false,'ID','ASC');
        
        // echo '<pre>';print_r($detail);die;

        $authRecommender = $detail['RECOMMENDED_BY_NAME'] == null ? $detail['RECOMMENDER_NAME'] : $detail['RECOMMENDED_BY_NAME'];
        $authApprover = $detail['APPROVED_BY_NAME'] == null ? $detail['APPROVER_NAME'] : $detail['APPROVED_BY_NAME'];
        $recommenderId = $detail['RECOMMENDED_BY'] == null ? $detail['RECOMMENDER_ID'] : $detail['RECOMMENDED_BY'];


        $expenseDtlRepo = new TravelExpenseDtlRepository($this->adapter);
        
        $result = $expenseDtlRepo->fetchByTravelId($id);
        // echo '<pre>';print_r($result);die;
      
        $expenseDtlList = [];
       
        $totalAmount = 0;
        foreach ($result as $row) {
            $totalAmount += $row['TOTAL_AMOUNT'];
            array_push($expenseDtlList, $row);
        }
        $transportType = [
            "AP" => "Aeroplane",
            "OV" => "Office Vehicles",
            "TI" => "Taxi",
            "BS" => "Bus",
            "OF" => "On Foot"
        ];
        $numberInWord = new NumberHelper();
        $totalAmountInWords = $numberInWord->toText($totalAmount);
        $balance = $detail['REQUESTED_AMOUNT'] - $totalAmount;
    // echo '<pre>';print_r($expenseDtlList);die;
        
        return Helper::addFlashMessagesToArray($this, [
                    'form' => $this->form,
                    'id' => $id,
                    'recommender' => $authRecommender,
                    'approver' => $authApprover,
                    'recommendedBy' => $recommenderId,
                    'employeeId' => $this->employeeId,
                    'expenseDtlList' => $expenseDtlList,
                    'TravelClass'=>$allCategoryList,
                    'categoryWisePercentage' => $categoryWisePercentage,
                    'transportType' => $transportType,
                    'todayDate' => date('d-M-Y'),
                    'detail' => $detail,
                    'totalAmount' => $totalAmount,
                    'totalAmountInWords' => $totalAmountInWords,
                    'balance' => $balance
                        ]
        );
    }

    public function settlementReportAction() {
        $request = $this->getRequest();
        if ($request->isPost()) {
            try {
                $list = $this->travelStatusRepository->notSettled();
                return new JsonModel(['success' => true, 'data' => $list, 'error' => '']);
            } catch (Exception $e) {
                return new JsonModel(['success' => false, 'data' => [], 'error' => $e->getMessage()]);
            }
        }

        return [];
    }

    public function bulkAction() {
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

    private function makeDecision($id, $approve, $remarks = null, $enableFlashNotification = false) {

        $detail = $this->travelApproveRepository->fetchById($id);
        // echo '<pre>';print_r($detail);die;

        if ($detail['STATUS'] == 'RQ' || $detail['STATUS'] == 'RC') {
            $model = new TravelRequest();
            $model->travelId = $id;
            $model->employeeId = $detail['EMPLOYEE_ID'];
            $model->fromDate = $detail['FROM_DATE'];
            $model->toDate = $detail['TO_DATE'];
            $model->recommendedDate = Helper::getcurrentExpressionDate();
            $model->recommendedBy = $this->employeeId;
            $model->approvedRemarks = $remarks;
            $model->approvedDate = Helper::getcurrentExpressionDate();
            $model->approvedBy = $this->employeeId;
            $model->status = $approve ? "AP" : "R";
            $message = $approve ? "Travel Request Approved" : "Travel Request Rejected";
            $notificationEvent = $approve ? NotificationEvents::TRAVEL_APPROVE_ACCEPTED : NotificationEvents::TRAVEL_APPROVE_REJECTED;
            $this->travelApproveRepository->edit($model, $id);
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

    private function makeSuperDecision($id, $approve, $remarks = null, $enableFlashNotification = false) {

        $detail = $this->travelApproveRepository->fetchById($id);
       // echo '<pre>';print_r($detail);die;

        if ($detail['STATUS'] == 'AP') {
            $model = new TravelRequest();
            $model->travelId = $id;
            $model->recommendedDate = Helper::getcurrentExpressionDate();
            $model->recommendedBy = $this->employeeId;
            $model->approvedRemarks = $remarks;
            $model->approvedDate = Helper::getcurrentExpressionDate();
            $model->approvedBy = $this->employeeId;
            $model->status = $approve ? "AP" : "R";
            $message = $approve ? "Travel Request Approved" : "Travel Request Rejected";
            $notificationEvent = $approve ? NotificationEvents::TRAVEL_APPROVE_ACCEPTED : NotificationEvents::TRAVEL_APPROVE_REJECTED;
            $this->travelApproveRepository->edit($model, $id);
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