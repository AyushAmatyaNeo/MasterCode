<?php

namespace SelfService\Controller;

use Application\Controller\HrisController;
use Application\Custom\CustomViewModel;
use Application\Helper\EntityHelper;
use Application\Helper\Helper;
use Application\Helper\NumberHelper;
use Exception;
use Notification\Controller\HeadNotification;
use Notification\Model\NotificationEvents;
use SelfService\Form\TravelRequestForm;
use SelfService\Repository\LeaveRequestRepository;
use SelfService\Model\TravelExpenseDetail;
use SelfService\Model\TravelRequest as TravelRequestModel;
use SelfService\Model\TravelSubstitute;
use SelfService\Repository\TravelExpenseDtlRepository;
use SelfService\Repository\TravelRequestRepository;
use SelfService\Repository\TravelSubstituteRepository;
use Setup\Model\HrEmployees;
use Setup\Model\TravelCategory;
use Travel\Repository\TravelItnaryRepository;
use Zend\Authentication\Storage\StorageInterface;
use Zend\Db\Adapter\AdapterInterface;
use Zend\View\Model\JsonModel;

class TravelRequest extends HrisController
{

    public function __construct(AdapterInterface $adapter, StorageInterface $storage)
    {
        parent::__construct($adapter, $storage);
        $this->initializeRepository(TravelRequestRepository::class);
        $this->initializeForm(TravelRequestForm::class);
    }

    public function indexAction()
    {
        $request = $this->getRequest();
        if ($request->isPost()) {
            try {
                $data = (array) $request->getPost();
                $data['employeeId'] = $this->employeeId;
                $data['requestedType'] = 'ad';
                $rawList = $this->repository->getFilteredRecords($data);
                $list = iterator_to_array($rawList, false);
                // echo '<pre>';print_r($list);die;

                if ($this->preference['displayHrApproved'] == 'Y') {
                    for ($i = 0; $i < count($list); $i++) {
                        if ($list[$i]['HARDCOPY_SIGNED_FLAG'] == 'Y') {
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
        $statusSE = $this->getStatusSelectElement(['name' => 'status', 'id' => 'statusId', 'class' => 'form-control reset-field', 'label' => 'Status']);
        return $this->stickFlashMessagesTo([
            'status' => $statusSE,
            'employeeId' => $this->employeeId
        ]);
    }

    public function addAction()
    {
        $request = $this->getRequest();

        $model = new TravelRequestModel();
        if ($request->isPost()) {
            $postData = $request->getPost();
            $travelSubstitute = $postData->travelSubstitute;
            $this->form->setData($postData);

            if ($this->form->isValid()) {
                $model->exchangeArrayFromForm($this->form->getData());
                $model->requestedAmount = ($model->requestedAmount == null) ? 0 : $model->requestedAmount;
                $model->travelId = ((int) Helper::getMaxId($this->adapter, TravelRequestModel::TABLE_NAME, TravelRequestModel::TRAVEL_ID)) + 1;
                $model->employeeId = $this->employeeId;
                $model->createdBy = $this->employeeId;
                $model->requestedDate = Helper::getcurrentExpressionDate();
                $model->status = 'RQ';
                // echo '<pre>';print_r($model);die;

                $this->repository->add($model);
                $this->flashmessenger()->addMessage("Travel Request Successfully added!!!");

                if ($travelSubstitute != null) {
                    $travelSubstituteModel = new TravelSubstitute();
                    $travelSubstituteRepo = new TravelSubstituteRepository($this->adapter);

                    $travelSubstitute = $postData->travelSubstitute;

                    if (isset($this->preference['travelSubCycle']) && $this->preference['travelSubCycle'] == 'N') {
                        $travelSubstituteModel->approvedFlag = 'Y';
                        $travelSubstituteModel->approvedDate = Helper::getcurrentExpressionDate();
                    }
                    $travelSubstituteModel->travelId = $model->travelId;
                    $travelSubstituteModel->employeeId = $travelSubstitute;
                    $travelSubstituteModel->createdBy = $this->employeeId;
                    $travelSubstituteModel->createdDate = Helper::getcurrentExpressionDate();
                    $travelSubstituteModel->status = 'E';

                    $travelSubstituteRepo->add($travelSubstituteModel);

                    if (!isset($this->preference['travelSubCycle']) or (isset($this->preference['travelSubCycle']) && $this->preference['travelSubCycle'] == 'Y')) {
                        try {
                            HeadNotification::pushNotification(NotificationEvents::TRAVEL_SUBSTITUTE_APPLIED, $model, $this->adapter, $this);
                        } catch (Exception $e) {
                            $this->flashmessenger()->addMessage($e->getMessage());
                        }
                    } else {
                        try {
                            HeadNotification::pushNotification(NotificationEvents::TRAVEL_APPLIED, $model, $this->adapter, $this);
                        } catch (Exception $e) {
                            $this->flashmessenger()->addMessage($e->getMessage());
                        }
                    }
                } else {
                    try {
                        HeadNotification::pushNotification(NotificationEvents::TRAVEL_APPLIED, $model, $this->adapter, $this);
                    } catch (Exception $e) {
                        $this->flashmessenger()->addMessage($e->getMessage());
                    }
                }
                return $this->redirect()->toRoute("travelRequest");
            }
        }
        $requestType = array(
            'ad' => 'Advance'
        );
        $transportTypes = array(
            'AP' => 'Aeroplane',
            'OV' => 'Office Vehicles',
            'TI' => 'Taxi',
            'BS' => 'Bus',
            'OF'  => 'On Foot'
        );
        $travelCategory = $this->repository->travelCategory($this->employeeId);
        //  echo '<pre>';print_r($travelCategory);die;
        return Helper::addFlashMessagesToArray($this, [
            'form' => $this->form,
            'employeeId' => $this->employeeId,
            'requestTypes' => $requestType,
            'transportTypes' => $transportTypes,
            'employeeList' => EntityHelper::getTableKVListWithSortOption($this->adapter, HrEmployees::TABLE_NAME, HrEmployees::EMPLOYEE_ID, [HrEmployees::FIRST_NAME, HrEmployees::MIDDLE_NAME, HrEmployees::LAST_NAME], [HrEmployees::STATUS => "E", HrEmployees::RETIRED_FLAG => "N"], HrEmployees::FIRST_NAME, "ASC", " ", false, true),
            'travelCategoryList' => $travelCategory['LEVEL_NO']
        ]);
    }

    public function travelCategoryAction()
    {
        $request = $this->getRequest();
        if ($request->isPost()) {
            try {
                $data = $request->getPost();
                $id = $data['id'];
                $rawList = $this->repository->getTravelRecords($id);
                // echo '<pre>';print_r($rawList);die;
                return new JsonModel(['success' => true, 'data' => $rawList, 'error' => '']);
            } catch (Exception $e) {
                return new JsonModel(['success' => false, 'data' => [], 'error' => $e->getMessage()]);
            }
        }
    }


    public function deleteAction()
    {
        $id = (int) $this->params()->fromRoute("id");
        if (!$id) {
            return $this->redirect()->toRoute('travelRequest');
        }
        $this->repository->deleteTravel($id, $this->employeeId);
        $this->flashmessenger()->addMessage("Travel Request Successfully Cancelled!!!");
        return $this->redirect()->toRoute('travelRequest');
    }

    public function expenseAction()
    {
        $request = $this->getRequest();
        if ($request->isPost()) {
            try {
                $data = (array) $request->getPost();
                $data['employeeId'] = $this->employeeId;
                $data['requestedType'] = 'ep';
                $rawList = $this->repository->getFilteredRecords($data);
                $list = iterator_to_array($rawList, false);
                return new JsonModel(['success' => true, 'data' => $list, 'error' => '']);
            } catch (Exception $e) {
                return new JsonModel(['success' => false, 'data' => [], 'error' => $e->getMessage()]);
            }
        }
        $statusSE = $this->getStatusSelectElement(['name' => 'status', 'id' => 'statusId', 'class' => 'form-control reset-field', 'label' => 'Status']);
        return $this->stickFlashMessagesTo([
            'status' => $statusSE,
            'employeeId' => $this->employeeId
        ]);
    }

    public function expenseAddAction()
    {
        $request = $this->getRequest();
        $model = new TravelRequestModel();
        if ($request->isPost()) {

            $postData = $request->getPost()->getArrayCopy();

            $lastIndex = count($postData['data']['expenseDtlList']) - 1;
            // $postData['data']['expenseDtlList'][$lastIndex]['allowance'] = $postData['data']['expenseDtlList'][$lastIndex]['allowance'] / 2;
            //  echo '<pre>';print_r($postData);die;
            $expenseDtlList = $postData['data']['expenseDtlList'];
            $departureDate = $postData['data']['departureDate'];
            $returnedDate = $postData['data']['returnedDate'];
            $travelId = (int) $postData['data']['travelId'];
            $sumAllTotal = (float) $postData['data']['sumAllTotal'];
            $detail = $this->repository->fetchById($travelId);
            $expenseDtlRepo = new TravelExpenseDtlRepository($this->adapter);
            $expenseDtlModel = new TravelExpenseDetail();

            $requestedAmt = $sumAllTotal;
            $model->travelId = ((int) Helper::getMaxId($this->adapter, TravelRequestModel::TABLE_NAME, TravelRequestModel::TRAVEL_ID)) + 1;
            $model->employeeId = $this->employeeId;
            $model->requestedDate = Helper::getcurrentExpressionDate();
            $model->status = 'RQ';
            $model->fromDate = $detail['FROM_DATE'];
            $model->toDate = $detail['TO_DATE'];
            $model->destination = $detail['DESTINATION'];
            $model->departure = $detail['DEPARTURE'];
            $model->purpose = $detail['PURPOSE'];
            $model->travelCode = $detail['TRAVEL_CODE'];
            $model->requestedType = 'ep';
            $model->requestedAmount = $detail['REQUESTED_AMOUNT'];
            $model->referenceTravelId = $travelId;

            $model->departureDate = Helper::getExpressionDate($departureDate);
            $model->returnedDate = Helper::getExpressionDate($returnedDate);


            $this->repository->add($model);


            foreach ($expenseDtlList as $expenseDtl) {
                $transportType = $expenseDtl['transportType'];

                $expenseDtlModel->id = ((int) Helper::getMaxId($this->adapter, TravelExpenseDetail::TABLE_NAME, TravelExpenseDetail::ID)) + 1;
                $expenseDtlModel->travelId = $model->travelId;
                $expenseDtlModel->departureDate = Helper::getExpressionDate($expenseDtl['departureDate']);
                $expenseDtlModel->departurePlace = $expenseDtl['departurePlace'];
                $expenseDtlModel->departureTime = Helper::getExpressionTime($expenseDtl['departureTime']);
                $expenseDtlModel->destinationDate = Helper::getExpressionDate($expenseDtl['destinationDate']);
                $expenseDtlModel->destinationPlace = $expenseDtl['destinationPlace'];
                $expenseDtlModel->destinationTime = Helper::getExpressionTime($expenseDtl['destinationTime']);
                $expenseDtlModel->transportType = $transportType['id'];
                $expenseDtlModel->fare = (float) $expenseDtl['fare'];
                $expenseDtlModel->allowance = ($expenseDtl['allowance'] != null) ? (float) $expenseDtl['allowance'] : null;
                $expenseDtlModel->localConveyence = ($expenseDtl['localConveyence'] != null) ? (float) $expenseDtl['localConveyence'] : null;
                $expenseDtlModel->miscExpenses = ($expenseDtl['miscExpense'] != null) ? (float) $expenseDtl['miscExpense'] : null;
                $expenseDtlModel->currency = $expenseDtl['currencyType']['code'];
                $expenseDtlModel->standardExchangeRate = ($expenseDtl['standardExchangeRate'] != null) ? (float) $expenseDtl['standardExchangeRate'] : null;
                $expenseDtlModel->exchangeRate = ($expenseDtl['exchangeRate'] != null) ? (float) $expenseDtl['exchangeRate'] : null;
                $expenseDtlModel->totalAmount = (float) $expenseDtl['total'];
                $expenseDtlModel->remarks = ($expenseDtl['remarks'] != null) ? $expenseDtl['remarks'] : null;
                $expenseDtlModel->status = 'E';

                $expenseDtlModel->createdBy = $this->employeeId;
                $expenseDtlModel->createdDate = Helper::getcurrentExpressionDate();
                $expenseDtlRepo->add($expenseDtlModel);
            }
            $error = "";
            try {
                HeadNotification::pushNotification(NotificationEvents::TRAVEL_APPLIED, $model, $this->adapter, $this);
            } catch (Exception $e) {
                $error = $e->getMessage();
            }

            return new JsonModel(['success' => true, 'data' => null, 'error' => $error]);
        }



        $id = (int) $this->params()->fromRoute('id');
        if ($id === 0) {
            return $this->redirect()->toRoute("travelRequest");
        }
        $detail = $this->repository->fetchById($id);

        $allCategoryList = EntityHelper::getTableKVList($this->adapter, "HRIS_TRAVELS_EXPENSES_CATEGORY", "ID", ["CATEGORY_NAME"], null, null, true, 'ID', 'ASC');
        $categoryWisePercentage = EntityHelper::getTableKVList($this->adapter, "HRIS_TRAVELS_EXPENSES_CATEGORY", "ID", ["ALLOWANCE_PERCENTAGE"], null, null, false, 'ID', 'ASC');
        return Helper::addFlashMessagesToArray($this, [
            'form' => $this->form,
            'detail' => $detail,
            'TravelClass' => $allCategoryList,
            'categoryWisePercentage' => $categoryWisePercentage,
            'id' => $id,
            'dailyAllowance' => $detail['DAILY_ALLOWANCE'],
            'dailyAllowanceRet' => $detail['DAILY_ALLOWANCE_RETURN'],

        ]);
    }

    public function viewAction()
    {
        $id = (int) $this->params()->fromRoute('id');
        if ($id === 0) {
            return $this->redirect()->toRoute("travelRequest");
        }
        $detail = $this->repository->fetchById($id);
        // echo '<pre>';print_r($detail);die;
        if ($this->preference['displayHrApproved'] == 'Y' && $detail['HARDCOPY_SIGNED_FLAG'] == 'Y') {
            $detail['APPROVER_ID'] = '-1';
            $detail['APPROVER_NAME'] = 'HR';
            $detail['RECOMMENDER_ID'] = '-1';
            $detail['RECOMMENDER_NAME'] = 'HR';
        }
        //$fileDetails = $this->repository->fetchAttachmentsById($id);
        $model = new TravelRequestModel();
        $model->exchangeArrayFromDB($detail);
        $this->form->bind($model);

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
            'form' => $this->form,
            'recommender' => $detail['RECOMMENDED_BY_NAME'] == null ? $detail['RECOMMENDER_NAME'] : $detail['RECOMMENDED_BY_NAME'],
            'approver' => $detail['APPROVED_BY_NAME'] == null ? $detail['APPROVER_NAME'] : $detail['APPROVED_BY_NAME'],
            'detail' => $detail,
            'todayDate' => date('d-M-Y'),
            'advanceAmount' => $advanceAmount,
            //'files' => $fileDetails
            'itnaryId' => $detail['ITNARY_ID'],
            'travelItnaryDet' => $travelItnaryDet,
            'travelItnaryMemDet' => $travelItnaryMemDet
        ]);
    }

    public function editAction()
    {
        $request = $this->getRequest();

        $id = (int) $this->params()->fromRoute('id');
        if ($id === 0) {
            return $this->redirect()->toRoute("travelRequest");
        }
        if ($this->repository->checkAllowEdit($id) == 'N') {
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
                $travelRequest->createdBy = $this->employeeId;
                $this->repository->edit($travelRequest, $id);
                $this->flashmessenger()->addMessage("Travel Request Successfully Edited!!!");
                return $this->redirect()->toRoute("travelRequest");
            }
        }

        $detail = $this->repository->fetchById($id);
        $model = new TravelRequestModel();
        $model->exchangeArrayFromDB($detail);
        $this->form->bind($model);

        $numberInWord = new NumberHelper();
        $advanceAmount = $numberInWord->toText($detail['REQUESTED_AMOUNT']);
        return Helper::addFlashMessagesToArray($this, [
            'form' => $this->form,
            'advanceAmt' => $detail['ADVANCE_AMOUNT'],
            'recommender' => $detail['RECOMMENDED_BY_NAME'] == null ? $detail['RECOMMENDER_NAME'] : $detail['RECOMMENDED_BY_NAME'],
            'approver' => $detail['APPROVED_BY_NAME'] == null ? $detail['APPROVER_NAME'] : $detail['APPROVED_BY_NAME'],
            'detail' => $detail,
            'todayDate' => date('d-M-Y'),
            'advanceAmount' => $advanceAmount,
            'travelCategory' => $detail['LEVEL_NO'],


        ]);
    }

    public function expenseViewAction()
    {
        $id = (int) $this->params()->fromRoute('id');

        if ($id === 0) {
            return $this->redirect()->toRoute("travelRequest");
        }

        $detail = $this->repository->fetchById($id);
        // echo '<pre>';print_r($detail);die;
        $model = new TravelRequestModel();
        $model->exchangeArrayFromDB($detail);
        $this->form->bind($model);

        $expenseDtlRepo = new TravelExpenseDtlRepository($this->adapter);
        //echo '<pre>';print_r($expenseDtlRepo);die;
        $expenseDtlList = [];

        $result = $expenseDtlRepo->fetchByTravel($id);
        // echo '<pre>';print_r($result);die;
        $totalAmount = 0;
        foreach ($result as $row) {
            $totalAmount += $row['TOTAL_AMOUNT'];
            array_push($expenseDtlList, $row);
        }
        $balance = $detail['REQUESTED_AMOUNT'] - $totalAmount;

        $numberInWord = new NumberHelper();
        $advanceAmount = $numberInWord->toText($detail['REQUESTED_AMOUNT']);
        $totalExpenseInWords = $numberInWord->toText($totalAmount);

        // echo '<pre>';print_r($expenseDtlList);die;
        return Helper::addFlashMessagesToArray($this, [
            'form' => $this->form,
            'recommender' => $detail['RECOMMENDED_BY_NAME'] == null ? $detail['RECOMMENDER_NAME'] : $detail['RECOMMENDED_BY_NAME'],
            'approver' => $detail['APPROVED_BY_NAME'] == null ? $detail['APPROVER_NAME'] : $detail['APPROVED_BY_NAME'],
            'detail' => $detail,
            'expenseDtlList' => $expenseDtlList,
            'todayDate' => date('d-M-Y'),
            'advanceAmount' => $advanceAmount,
            'totalExpenseInWords' => $totalExpenseInWords,
            'totalExpense' => $totalAmount,
            'balance' => $balance,
        ]);
    }

    public function deleteExpenseDetailAction()
    {
        $request = $this->getRequest();
        if ($request->isPost()) {
            $postData = $request->getPost()->getArrayCopy();
            $id = $postData['data']['id'];
            $repository = new TravelExpenseDtlRepository($this->adapter);
            $repository->delete($id);
            $responseData = [
                "success" => true,
                "data" => "Expense Detail Successfully Removed"
            ];
        } else {
            $responseData = [
                "success" => false,
            ];
        }
        return new CustomViewModel($responseData);
    }

    public function ExpenseDetailListAction()
    {
        $request = $this->getRequest();
        if ($request->isPost()) {
            $request = $this->getRequest();
            $data = $request->getPost();
            $travelId = (int) $data['travelId'];

            $travelDetail = $this->repository->fetchById($travelId);
            $expenseDtlRepo = new TravelExpenseDtlRepository($this->adapter);
            $expenseDtlList = [];
            $result = $expenseDtlRepo->fetchByTravel($travelId);
            foreach ($result as $row) {
                array_push($expenseDtlList, $row);
            }
            return new JsonModel([
                'success' => true,
                'data' => [
                    'travelDetail' => $travelDetail,
                    'expenseDtlList' => $expenseDtlList,
                    'numExpenseDtlList' => count($expenseDtlList)
                ]
            ]);
        } else {
            return new JsonModel(['success' => false]);
        }
    }

    public function expenseEditAction()
    {
        $request = $this->getRequest();
        $model = new TravelRequestModel();

        if ($request->isPost()) {

            $postData = $request->getPost()->getArrayCopy();
            $lastIndex = count($postData['data']['expenseDtlList']) - 1;
            // $postData['data']['expenseDtlList'][$lastIndex]['allowance'] = $postData['data']['expenseDtlList'][$lastIndex]['allowance'] / 2;
            $expenseDtlList = $postData['data']['expenseDtlList'];
            $departureDate = $postData['data']['departureDate'];
            $returnedDate = $postData['data']['returnedDate'];
            $travelId = (int) $postData['data']['travelId'];
            $sumAllTotal = (float) $postData['data']['sumAllTotal'];
            $detail = $this->repository->fetchById($travelId);
            $expenseDtlRepo = new TravelExpenseDtlRepository($this->adapter);
            $expenseDtlModel = new TravelExpenseDetail();

            $requestedAmt = $sumAllTotal;
            $model->travelId = $travelId;
            $model->employeeId = $this->employeeId;
            $model->requestedDate = Helper::getcurrentExpressionDate();
            $model->status = 'RQ';
            $model->fromDate = $detail['FROM_DATE'];
            $model->toDate = $detail['TO_DATE'];
            $model->destination = $detail['DESTINATION'];
            $model->departure = $detail['DEPARTURE'];
            $model->purpose = $detail['PURPOSE'];
            $model->travelCode = $detail['TRAVEL_CODE'];
            $model->requestedType = 'ep';
            $model->requestedAmount = $detail['REQUESTED_AMOUNT'];
            $model->referenceTravelId = $travelId;

            $model->departureDate = Helper::getExpressionDate($departureDate);
            $model->returnedDate = Helper::getExpressionDate($returnedDate);

            $this->repository->edit($model, $travelId);
            $expenseDtlRepo->deleteExp($travelId);

            foreach ($expenseDtlList as $expenseDtl) {
                $transportType = $expenseDtl['transportType'];

                $expenseDtlModel->id = ((int) Helper::getMaxId($this->adapter, TravelExpenseDetail::TABLE_NAME, TravelExpenseDetail::ID)) + 1;
                $expenseDtlModel->travelId = $model->travelId;
                $expenseDtlModel->departureDate = Helper::getExpressionDate($expenseDtl['departureDate']);
                $expenseDtlModel->departurePlace = $expenseDtl['departurePlace'];
                $expenseDtlModel->departureTime = Helper::getExpressionTime($expenseDtl['departureTime']);
                $expenseDtlModel->destinationDate = Helper::getExpressionDate($expenseDtl['destinationDate']);
                $expenseDtlModel->destinationPlace = $expenseDtl['destinationPlace'];
                $expenseDtlModel->destinationTime = Helper::getExpressionTime($expenseDtl['destinationTime']);
                $expenseDtlModel->transportType = $transportType['id'];
                $expenseDtlModel->fare = (float) $expenseDtl['fare'];
                $expenseDtlModel->allowance = ($expenseDtl['allowance'] != null) ? (float) $expenseDtl['allowance'] : null;
                $expenseDtlModel->localConveyence = ($expenseDtl['localConveyence'] != null) ? (float) $expenseDtl['localConveyence'] : null;
                $expenseDtlModel->miscExpenses = ($expenseDtl['miscExpense'] != null) ? (float) $expenseDtl['miscExpense'] : null;
                $expenseDtlModel->currency = $expenseDtl['currencyType']['code'];
                $expenseDtlModel->standardExchangeRate = ($expenseDtl['standardExchangeRate'] != null) ? (float) $expenseDtl['standardExchangeRate'] : null;
                $expenseDtlModel->exchangeRate = ($expenseDtl['exchangeRate'] != null) ? (float) $expenseDtl['exchangeRate'] : null;
                $expenseDtlModel->totalAmount = (float) $expenseDtl['total'];
                $expenseDtlModel->remarks = ($expenseDtl['remarks'] != null) ? $expenseDtl['remarks'] : null;
                $expenseDtlModel->status = 'E';

                $expenseDtlModel->createdBy = $this->employeeId;
                $expenseDtlModel->createdDate = Helper::getcurrentExpressionDate();
                $expenseDtlRepo->add($expenseDtlModel);
            }
            $error = "";
            try {
                HeadNotification::pushNotification(NotificationEvents::TRAVEL_APPLIED, $model, $this->adapter, $this);
            } catch (Exception $e) {
                $error = $e->getMessage();
            }

            return new JsonModel(['success' => true, 'data' => null, 'error' => $error]);
        }

        $id = (int) $this->params()->fromRoute('id');

        if ($id === 0) {
            return $this->redirect()->toRoute("travelRequest");
        }
        $detail = $this->repository->fetchById($id);
        // echo '<pre>';print_r($detail);die;
        $model = new TravelRequestModel();
        $model->exchangeArrayFromDB($detail);
        $this->form->bind($model);

        $expenseDtlRepo = new TravelExpenseDtlRepository($this->adapter);
        //echo '<pre>';print_r($expenseDtlRepo);die;
        $expenseDtlList = [];

        $result = $expenseDtlRepo->fetchByTravel($id);
        $totalAmount = 0;
        foreach ($result as $row) {
            $totalAmount += $row['TOTAL_AMOUNT'];
            array_push($expenseDtlList, $row);
        }
        $balance = $detail['REQUESTED_AMOUNT'] - $totalAmount;

        $numberInWord = new NumberHelper();
        $advanceAmount = $numberInWord->toText($detail['REQUESTED_AMOUNT']);
        $totalExpenseInWords = $numberInWord->toText($totalAmount);


        return Helper::addFlashMessagesToArray($this, [
            'form' => $this->form,
            'recommender' => $detail['RECOMMENDED_BY_NAME'] == null ? $detail['RECOMMENDER_NAME'] : $detail['RECOMMENDED_BY_NAME'],
            'approver' => $detail['APPROVED_BY_NAME'] == null ? $detail['APPROVER_NAME'] : $detail['APPROVED_BY_NAME'],
            'detail' => $detail,
            "id" => $id,
            'expenseDtlList' => $expenseDtlList,
            'todayDate' => date('d-M-Y'),
            'advanceAmount' => $advanceAmount,
            'totalExpenseInWords' => $totalExpenseInWords,
            'totalExpense' => $totalAmount,
            'balance' => $balance,

        ]);
    }

    public function validateTravelRequestAction()
    {
        try {
            $request = $this->getRequest();
            if ($request->isPost()) {
                $postedData = $request->getPost();
                $leaveRequestRepository = new LeaveRequestRepository($this->adapter);
                $error = $this->repository->validateTravelRequest(Helper::getExpressionDate($postedData['startDate'])->getExpression(), Helper::getExpressionDate($postedData['endDate'])->getExpression(), $postedData['employeeId']);
                $travelLeaveError = $this->repository->validateTravelLeaveRequest(Helper::getExpressionDate($postedData['startDate'])->getExpression(), Helper::getExpressionDate($postedData['endDate'])->getExpression(), $postedData['employeeId']);
                $WODError = $this->repository->validateWODRequest(Helper::getExpressionDate($postedData['startDate'])->getExpression(), Helper::getExpressionDate($postedData['endDate'])->getExpression(), $postedData['employeeId']);
                $WOHError = $this->repository->validateWOHRequest(Helper::getExpressionDate($postedData['startDate'])->getExpression(), Helper::getExpressionDate($postedData['endDate'])->getExpression(), $postedData['employeeId']);
                // echo '<pre>';print_r($error);die;
                return new CustomViewModel([
                    'success' => true, 'data' => $error, 'WODError' => $WODError, 'WOHError' => $WOHError,
                    'travelError' => $travelLeaveError
                ]);
            } else {
                throw new Exception("The request should be of type post");
            }
        } catch (Exception $e) {
            return new CustomViewModel(['success' => false, 'data' => [], 'error' => $e->getMessage()]);
        }
    }
}
