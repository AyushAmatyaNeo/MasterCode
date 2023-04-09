<?php

namespace SelfService\Controller;

use Application\Controller\HrisController;
use Application\Custom\CustomViewModel;
use Application\Helper\EntityHelper;
use Application\Helper\Helper;
use Application\Helper\NumberHelper;
use Exception;
use SelfService\Model\TRAVELFILES;
use Notification\Controller\HeadNotification;
use Notification\Model\NotificationEvents;
use SelfService\Form\TravelRequestForm;
use SelfService\Model\TravelExpenseDetail;
use SelfService\Model\TravelRequest as TravelRequestModel;
use SelfService\Model\TravelSubstitute;
use SelfService\Repository\TravelExpenseDtlRepository;
use SelfService\Repository\TravelRequestRepository;
use SelfService\Repository\TravelSubstituteRepository;
use Setup\Model\HrEmployees;
use Travel\Repository\TravelItnaryRepository;
use Zend\Authentication\Storage\StorageInterface;
use Zend\Db\Adapter\AdapterInterface;
use Zend\View\Model\JsonModel;
use SelfService\Model\TravelExpenses as TravelExpensesModel;
use SelfService\Model\TravelExpRequest;
use SelfService\Repository\NewTravelRequestRepository;
use Zend\Filter\File\LowerCase;
use SelfService\Repository\TravelExpensesRepository;
// use SelfService\Repository\TravelExpensesRepository;

class NewTravelRequest extends HrisController {

    public function __construct(AdapterInterface $adapter, StorageInterface $storage) {
        parent::__construct($adapter, $storage);
        $this->initializeRepository(NewTravelRequestRepository::class);
        $this->initializeForm(TravelRequestForm::class);
        $this->travelRequestRepository = new TravelRequestRepository($adapter);
    }

    public function indexAction() {
        $request = $this->getRequest();
       
        if ($request->isPost()) {
            try {
                $data = (array) $request->getPost();
                $data['employeeId'] = $this->employeeId;  # passes value when user log in
                $data['requestedType'] = 'ad';

                $rawList = $this->repository->getFilteredRecords($data);
                // echo '<pre>';print_r($rawList);die;
                $list = iterator_to_array($rawList, false);

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
        // echo '<pre>';print_r($this->travelType);die;
        $statusSE = $this->getStatusSelectElement(['name' => 'status', 'id' => 'statusId', 'class' => 'form-control reset-field', 'label' => 'Status']);
        return $this->stickFlashMessagesTo([
                    'status' => $statusSE,
                    'employeeId' => $this->employeeId,
        ]);
    }

    public function addAction() {
        $request = $this->getRequest();
        $employeeId = $this->employeeId;
        $employeeDetails = $this->repository->getEmployeeData($employeeId);
        $model = new TravelRequestModel();
        if ($request->isPost()) {
            $postData = $request->getPost();
            $postFiles = $request->getFiles();


            $travelSubstitute = null;//$postData->travelSubstitute;
            $this->form->setData($postData);

            if ($this->form->isValid()) {
                $model->exchangeArrayFromForm($this->form->getData());
                $model->requestedAmount = ($postData->requestedAmount == null) ? 0 : $postData->requestedAmount;
                $model->travelId = ((int) Helper::getMaxId($this->adapter, TravelRequestModel::TABLE_NAME, TravelRequestModel::TRAVEL_ID)) + 1;
                $model->employeeId = $this->employeeId;
                $model->requestedDate = Helper::getcurrentExpressionDate();
                $model->status = 'RQ';
                $model->requestedType = 'ad';
                if($postData['travelType'] == 'LTR'){
                    $model->currencyname = 'NPR';
                }else {
                    if($postData['requestedAmount'] == ''){
                        $model->currencyname = 'NPR';
                    }else{
                        $model->currencyname = $postData['currency'];
                        $model->conversionrate = $postData['conversionrate'];
                    }
                }
                $model->fromDate = Helper::getExpressionDate($model->fromDate);
                $model->toDate = Helper::getExpressionDate($model->toDate);
                $model->traveltype = $postData['travelType'];

                $this->repository->add($model);
                if ($postData['travelType'] == 'LTR') {
                    try {
                        HeadNotification::pushNotification(NotificationEvents::TRAVEL_APPLIED, $model, $this->adapter, $this);
                    } catch (Exception $e) {
                        $this->flashmessenger()->addMessage($e->getMessage());
                    }
                } else {
                    try {
                        // var_dump('here'); die;
                        HeadNotification::pushNotification(NotificationEvents::TRAVEL_APPLIED, $model, $this->adapter, $this);
                    } catch (Exception $e) {
                        $this->flashmessenger()->addMessage($e->getMessage());
                    }
                }
                
                
                $this->flashmessenger()->addMessage("Travel Request Successfully added!!!");
                // if ($travelSubstitute != null) {
                //     $travelSubstituteModel = new TravelSubstitute();
                //     $travelSubstituteRepo = new TravelSubstituteRepository($this->adapter);

                //     $travelSubstitute = $postData->travelSubstitute;

                //     if (isset($this->preference['travelSubCycle']) && $this->preference['travelSubCycle'] == 'N') {
                //         $travelSubstituteModel->approvedFlag = 'Y';
                //         $travelSubstituteModel->approvedDate = Helper::getcurrentExpressionDate();
                //     }
                //     $travelSubstituteModel->travelId = $model->travelId;
                //     $travelSubstituteModel->employeeId = $travelSubstitute;
                //     $travelSubstituteModel->createdBy = $this->employeeId;
                //     $travelSubstituteModel->createdDate = Helper::getcurrentExpressionDate();
                //     $travelSubstituteModel->status = 'E';

                //     $travelSubstituteRepo->add($travelSubstituteModel);

                //     if (!isset($this->preference['travelSubCycle']) OR ( isset($this->preference['travelSubCycle']) && $this->preference['travelSubCycle'] == 'Y')) {
                //         try {
                //             HeadNotification::pushNotification(NotificationEvents::TRAVEL_SUBSTITUTE_APPLIED, $model, $this->adapter, $this);
                //         } catch (Exception $e) {
                //             $this->flashmessenger()->addMessage($e->getMessage());
                //         }
                //     } else {
                //         try {
                //             HeadNotification::pushNotification(NotificationEvents::TRAVEL_APPLIED, $model, $this->adapter, $this);
                //         } catch (Exception $e) {
                //             $this->flashmessenger()->addMessage($e->getMessage());
                //         }
                //     }
                // } else {
                //     $travelSubstituteModel = new TravelSubstitute();
                //     $travelSubstituteRepo = new TravelSubstituteRepository($this->adapter);

                //     $travelSubstitute = $postData->travelSubstitute;
                //     $travelSubstituteModel->approvedFlag = 'Y';
                //     $travelSubstituteModel->approvedDate = Helper::getcurrentExpressionDate();
                //     $travelSubstituteModel->travelId = $model->travelId;
                //     $travelSubstituteModel->employeeId = $travelSubstitute;
                //     $travelSubstituteModel->createdBy = $this->employeeId;
                //     $travelSubstituteModel->createdDate = Helper::getcurrentExpressionDate();
                //     $travelSubstituteModel->status = 'E';

                //     //$travelSubstituteRepo->add($travelSubstituteModel);
                //     try {
                //         HeadNotification::pushNotification(NotificationEvents::TRAVEL_APPLIED, $model, $this->adapter, $this);
                //     } catch (Exception $e) {
                //         $this->flashmessenger()->addMessage($e->getMessage());
                //     }
                // }


                if(count($postFiles['files']) > 0){
                //   echo '<pre>';  print_r($postFiles['files']); die;
                    foreach ($postFiles['files'] as $value) {
                        if ($value['name'] != null) {
                            $fileDir = getcwd() . '/public/uploads/documents/travel-documents';
                      
                            if (!file_exists($fileDir)) {
                                mkdir($fileDir, 0777, true);
                            }
    
                            $newImageName = time().$value['name'];
                            $path = $fileDir. "/" . $newImageName;
                            move_uploaded_file( $value['tmp_name'], $path);
                            $data = array(
                               'FILE_ID' => ((int) Helper::getMaxId($this->adapter, TravelRequestModel::TABLE_NAME, TravelRequestModel::TRAVEL_ID)) + 1,
                               'FILE_NAME' => $newImageName,
                               'TRAVEL_ID' => $model->travelId,
                               'FILE_IN_DIR_NAME' =>  $path,
                               'UPLOADED_DATE' => '',
                            );
                            $this->repository->addFiles($data);
                        }
                    }
                }
                return $this->redirect()->toRoute("newtravelrequest");
            }
        }
        $requestType = array(
            'ad' => 'Advance'
        );
        $transportTypes = array(
            null => '------------',
            'AP' => 'Aeroplane',
            'OV' => 'Office Vehicles',
            'TI' => 'Taxi',
            'BS' => 'Bus',
            'OF'  => 'On Foot',
            'OT'=>'Others',
            'VV'=>'Own-Vehicle'
        );
        return Helper::addFlashMessagesToArray($this, [
                    'form' => $this->form,
                    'employeeDetails' => $employeeDetails,
                    'employeeId' => $this->employeeId,
                    'requestTypes' => $requestType,
                    'transportTypes' => $transportTypes,
                    'employeeList' => EntityHelper::getTableKVListWithSortOption($this->adapter, HrEmployees::TABLE_NAME, HrEmployees::EMPLOYEE_ID, [HrEmployees::FIRST_NAME, HrEmployees::MIDDLE_NAME, HrEmployees::LAST_NAME], [HrEmployees::STATUS => "E", HrEmployees::RETIRED_FLAG => "N"], HrEmployees::FIRST_NAME, "ASC", " ", false, true)
        ]);
    }

    public function editTravelAction() {
        $request = $this->getRequest();

        $id = (int) $this->params()->fromRoute('id');
        if ($id === 0) {
            return $this->redirect()->toRoute("newtravelrequest");
        }
        
        if ($this->repository->checkAllowEdit($id) == 'N') {
            return $this->redirect()->toRoute("newtravelrequest");
        }

        if ($request->isPost()) {
            $travelRequest = new TravelRequestModel();
            $postedData = $request->getPost();
            $postFiles= $request->getFiles();
            $this->form->setData($postedData);

            if ($this->form->isValid()) {
                $travelRequest->exchangeArrayFromForm($this->form->getData());
                // var_dump($travelRequest); die;
                $travelRequest->modifiedDt = Helper::getcurrentExpressionDate();
                $travelRequest->employeeId = $this->employeeId;
                $travelRequest->fromDate = Helper::getExpressionDate($travelRequest->fromDate);
                $travelRequest->toDate = Helper::getExpressionDate($travelRequest->toDate);
                $travelRequest->traveltype = $postedData['travelType'];
                
                    $travelRequest->requestedType = 'ad';
               
                
                // echo '<pre>'; print_r($travelRequest); die;
                $this->repository->edit($travelRequest, $id);    
                $this->flashmessenger()->addMessage("Travel Request Successfully Edited!!!");
                if(count($postFiles['files']) > 0){
                    //   echo '<pre>';  print_r($postFiles['files']); die;
                        foreach ($postFiles['files'] as $value) {
                            if ($value['name'] != null) {
                                $fileDir = getcwd() . '/public/uploads/documents/travel-documents';
                          
                                if (!file_exists($fileDir)) {
                                    mkdir($fileDir, 0777, true);
                                }
        
                                $newImageName = time().$value['name'];
                                $path = $fileDir. "/" . $newImageName;
                                move_uploaded_file( $value['tmp_name'], $path);
                                $data = array(
                                   'FILE_ID' => ((int) Helper::getMaxId($this->adapter, TRAVELFILES::TABLE_NAME, TRAVELFILES::FILE_ID)) + 1,
                                   'FILE_NAME' => $newImageName,
                                   'TRAVEL_ID' => $id,
                                   'FILE_IN_DIR_NAME' =>  $path,
                                   'UPLOADED_DATE' => '',
                                );
                                // echo '<pre>';print_r($data);die;

                                $this->travelRequestRepository->updateFiles($data);
                            }
                        }
                    }
                return $this->redirect()->toRoute("newtravelrequest");
            }
            
        }

        $detail = $this->repository->fetchById($id);
        $fileDetails = $this->repository->fetchAttachmentsById($id);
                // echo '<pre>'; print_r($fileDetails); die;

        $model = new TravelRequestModel();
        $model->exchangeArrayFromDB($detail);
        $this->form->bind($model);

        $numberInWord = new NumberHelper();
        $advanceAmount = $numberInWord->toText($detail['REQUESTED_AMOUNT']);

        $transportTypes = array(
            null => '------------',
            'AP' => 'Aeroplane',
            'OV' => 'Office Vehicles',
            'TI' => 'Taxi',
            'BS' => 'Bus',
            'OF'  => 'On Foot',
            'OT'=>'Others',
            'VV'=>'Own-Vehicle'
        );
// print_r($fileDetails);die;
        return Helper::addFlashMessagesToArray($this, [
                    'form' => $this->form,
                    'recommender' => $detail['RECOMMENDED_BY_NAME'] == null ? $detail['RECOMMENDER_NAME'] : $detail['RECOMMENDED_BY_NAME'],
                    'approver' => $detail['APPROVED_BY_NAME'] == null ? $detail['APPROVER_NAME'] : $detail['APPROVED_BY_NAME'],
                    'detail' => $detail,
                    'todayDate' => date('d-M-Y'),
                    'advanceAmount' => $advanceAmount,
                    'transportTypes' => $transportTypes,
                    'files' => $fileDetails
        ]);
    }

    public function expenseAddAction() {
        $request = $this->getRequest();
        $model = new TravelExpensesModel();
        $reqModel = new TravelRequestModel();
        $repo = new TravelExpensesRepository($this->adapter);
        $employeeId = $this->employeeId;
        $employeeDetails = $this->repository->getEmployeeData($employeeId);
        $travelId = (int) $this->params()->fromRoute('id');
        // var_dump($travelId); die;
        if ($request->isPost()) {
            
            $travelNewId = ((int) Helper::getMaxId($this->adapter, TravelRequestModel::TABLE_NAME, TravelRequestModel::TRAVEL_ID)) + 1;
            $travelId = (int) $this->params()->fromRoute('id');
            
            $detail = $this->repository->fetchById($travelId);
            $postData = $request->getPost()->getArrayCopy();
            $departureDate = $postData['departureDate'];
            $returnedDate = $postData['returnedDate'];
           
            if ($postData['erTypeL'][0] != null){
               
                for ($i = 0; $i < count($postData['erTypeL']); $i++){
                    // var_dump($classId); die;
                    $model->travelExpenseId = ((int) Helper::getMaxId($this->adapter, "hris_travel_expense", "TRAVEL_EXPENSE_ID")) + 1;
                    $model->travelId= $travelNewId;
                    $model->amount= $postData['amountExp'][$i];
                    $model->exchangeRate=1;
                    $model->expenseDate=Helper::getcurrentExpressionDate();
                    $model->status='E';
                    $model->remarks=$postData['detRemarks'][$i];
                    
                    $model->createdDt=Helper::getcurrentExpressionDate();
                    $model->departure_Place = $postData['locFrom'][$i];
                    $model->arraival_DT = Helper::getExpressionDate($postData['arrDate'][$i]);
                    $model->erType = $postData['erTypeL'][$i];
                    $model->billNo = $postData['ticketNo'][$i];
                    $model->expenseHead = $postData['expenseHead'][$i];
                    $model->currency = 'NPR';
                    $repo->add($model);
                }
            }
            if ($postData['erTypeI'][0] != null){
            // echo '<pre>'; print_r($postData); die;
           
                for ($j = 0; $j < count($postData['erTypeI']); $j++){
                   
                    // $d = $postData['amountExp'][$j] * $postData['exchangeRateInternational'][$j];
                    $model->travelExpenseId = ((int) Helper::getMaxId($this->adapter, "hris_travel_expense", "TRAVEL_EXPENSE_ID")) + 1;
                    $model->travelId= $travelNewId;
                    $model->amount= $postData['amountExp'][$j] * $postData['exchangeRateInternational'][$j];
                    $model->exchangeRate=$postData['exchangeRateInternational'][$j];
                    
                    $model->expenseDate=Helper::getcurrentExpressionDate();
                    $model->status='E';
                    $model->remarks=$postData['detRemarks'][$j];
                    $model->createdDt=Helper::getcurrentExpressionDate();
                    $model->departure_Place = $postData['locFrom'][$j];
                    $model->arraival_DT = Helper::getExpressionDate($postData['arrDate'][$j]);
                    $model->erType = $postData['erTypeL'][$j];
                    $model->billNo = $postData['ticketNo'][$j];
                    $model->expenseHead = $postData['expenseHead'][$j];
                    $model->currency = $postData['currency'][$j];
                    // var_dump($d); die;
                   
                    $repo->add($model);
                }
            }
            // echo '<pre>'; print_r($postData); die;
            $reqModel->travelId = ((int) Helper::getMaxId($this->adapter, TravelRequestModel::TABLE_NAME, TravelRequestModel::TRAVEL_ID)) + 1;
            $reqModel->employeeId = $this->employeeId;
            $reqModel->requestedDate = Helper::getcurrentExpressionDate();
            $reqModel->status = 'RQ';
            $reqModel->fromDate = $detail['FROM_DATE'];
            $reqModel->toDate = $detail['TO_DATE'];
            $reqModel->destination = $detail['DESTINATION'];
            $reqModel->departure = $detail ['DEPARTURE'];
            $reqModel->purpose = $detail['PURPOSE'];
            $reqModel->travelCode = $detail['TRAVEL_CODE'];
            $reqModel->requestedType = 'ep';
            $reqModel->requestedAmount = $this->repository->getTotalExpenseAmount($travelNewId);
            // $reqModel->referenceTravelId = $travelId;
            $reqModel->departureDate = Helper::getExpressionDate($departureDate);
            $reqModel->returnedDate = Helper::getExpressionDate($returnedDate);
            $reqModel->fromDate = Helper::getExpressionDate($reqModel->fromDate);
            $reqModel->toDate = Helper::getExpressionDate($reqModel->toDate);
            if ($postData['erTypeL'][0] != null){
                $reqModel->traveltype = 'LTR';
            }
            if ($postData['erTypeI'][0] != null){
                $reqModel->traveltype = 'ITR';
            }
            $this->repository->add($reqModel);

            $error = "";
            try {
                if(isset($this->preference['travelSingleApprover']) && $this->preference['travelSingleApprover'] == 'Y'){
                    HeadNotification::pushNotification(NotificationEvents::TRAVEL_EXPENSE_APPLIED, $reqModel, $this->adapter, $this);
                }else{
                    HeadNotification::pushNotification(NotificationEvents::TRAVEL_APPLIED, $reqModel, $this->adapter, $this);
                }
            } catch (Exception $e) {
                $error = $e->getMessage();
            }
            $this->flashmessenger()->addMessage("Successfully Added!!!");
                return $this->redirect()->toRoute("newtravelrequest");
        }
        $transportTypes = array(
            null => '------------',
            'AP' => 'Aeroplane',
            'OV' => 'Office Vehicles',
            'TI' => 'Taxi',
            'BS' => 'Bus',
            'OF'  => 'On Foot',
            'OT'=>'Others',
            'VV'=>'Own-Vehicle'
        );
        $expenseHeads = array(
           array('gl' => null, 'name'  => '---select expense heads---'),
            array('gl' => 'Accommodation', 'name' => 'Accommodation'),
            array('gl' => 'Airfare', 'name' => 'Airfare'),
            array('gl' => 'Airport Taxes and visas etc', 'name' => 'Airport Taxes and visas etc'),
            array('gl' => 'Auto Maintenance', 'name' => 'Auto Maintenance'),
            array('gl' => 'Books Newspaper & Subscription', 'name' => 'Books Newspaper & Subscription'),
            array('gl' => 'Black Berry (Monthly Usage Bill)', 'name' => 'Black Berry (Monthly Usage Bill)'),
            array('gl' => 'Car hire', 'name' => 'Car hire'),
            array('gl' => 'Cafeteria Expenses-admin', 'name' => 'Cafeteria Expenses-admin'),
            array('gl' => 'Club Membership Fee', 'name' => 'Club Membership Fee'),
            array('gl' => 'Conveyance- Car Rental', 'name' => 'Conveyance- Car Rental'),
            array('gl' => 'Conveyance - Other', 'name' => 'Conveyance - Other'),
            array('gl' => 'Daily Allowance', 'name' => 'Daily Allowance'),
            array('gl' => 'Data Card', 'name' => 'Data Card'),
            array('gl' => 'Entertainment', 'name' => 'Entertainment'),
            array('gl' => 'Expatriate Benefits', 'name' => 'Expatriate Benefits'),
            array('gl' => 'Expatriate CLA Expenses', 'name' => 'Expatriate CLA Expenses'),
            array('gl' => 'Gasoline Diesel Fuel Oil', 'name' => 'Gasoline Diesel Fuel Oil'),
            array('gl' => 'Guest House Expenses (incl electricity, maint & ot', 'name' => 'Guest House Expenses (incl electricity, maint & ot'),
            array('gl' => 'Hotel Meals (inclusive of Tips)', 'name' => 'Hotel Meals (inclusive of Tips)'),
            array('gl' => 'IS Information (others)', 'name' => 'IS Information (others)'),
            array('gl' => 'Lab Testing and Certification Cost', 'name' => 'Lab Testing and Certification Cost'),
            array('gl' => 'Laundry', 'name' => 'Laundry'),
            array('gl' => 'Lodging', 'name' => 'Lodging'),
            array('gl' => 'Meeting', 'name' => 'Meeting'),
            array('gl' => 'Miscellaneous Expenses', 'name' => 'Miscellaneous Expenses'),
            array('gl' => 'Mobile Handset Reimbursement', 'name' => 'Mobile Handset Reimbursement'),
            array('gl' => 'Mobile Phone Expenses', 'name' => 'Mobile Phone Expenses'),
            array('gl' => 'Other Tips', 'name' => 'Other Tips'),
            array('gl' => 'Postage & Courier Charge', 'name' => 'Postage & Courier Charge'),
            array('gl' => 'Reimb on Stamp Paper', 'name' => 'Reimb on Stamp Paper'),
            array('gl' => 'Relocation Expenses', 'name' => 'Relocation Expenses'),
            array('gl' => 'Stationary', 'name' => 'Stationary'),
            array('gl' => 'Supplies General', 'name' => 'Supplies General'),
            array('gl' => 'Ticket - Others', 'name' => 'Ticket - Others'),
            array('gl' => 'Toll Fees', 'name' => 'Toll Fees'),
            array('gl' => 'Taxi/Bus/Car rental (inc fuel & conv allow)', 'name' => 'Taxi/Bus/Car rental (inc fuel & conv allow)'),
            array('gl' => 'Telephone/Fax Expenses', 'name' => 'Telephone/Fax Expenses'),
            array('gl' => 'Train Fare/Bus Fare', 'name' => 'Train Fare/Bus Fare'),
            array('gl' => 'Training Expenses', 'name' => 'Training Expenses'),
            array('gl' => 'Uniforms and Towels -Admin', 'name' => 'Uniforms and Towels -Admin'),
        );
        $erTypes = array(
            null => '------------',
            'EP' => 'Employee Paid',
            'CP' => 'Company Paid',
        );
        // if ($details) {
            
        // } else {
            
        // }
        
        $expenseHead = array(
            null => '------------',
            'EP' => 'Employee Paid',
            'CP' => 'Company Paid',
        );
        $id = (int) $this->params()->fromRoute('id');
        if ($id === 0) {
            return $this->redirect()->toRoute("newtravelrequest");
        }
        $detail = $this->repository->fetchById($id);
        // echo '<pre>';print_r($detail);die;
        return Helper::addFlashMessagesToArray($this, [
                    'form' => $this->form,
                    'detail' => $detail,
                    'id' => $id,
                    'transportTypes' => $transportTypes,
                    'employeeDetails' => $employeeDetails,
                    'erTypes' => $erTypes,
                    'expenseHeads' => $expenseHeads
        ]);
    }
    public function addTravelExpenseAction()
    {
        $employeeId = $this->employeeId;
        // print_r($employeeId); die;
        $model = new TravelExpensesModel();
        $reqModel = new TravelRequestModel();
        $repo = new TravelExpensesRepository($this->adapter);
        $localATravels = $this->repository->getLTravel($employeeId);
        // echo '<pre>'; print_r($localATravels);die;
        $IntATravels = $this->repository->getITravel($employeeId);
        //  echo '<pre>'; print_r($IntATravels);die;
        $employeeDetails = $this->repository->getEmployeeData($employeeId);
        $request = $this->getRequest();
        if ($request->isPost()) {
            $postData = $request->getPost()->getArrayCopy();
            if($postData['traveltype'] == 'LTR' || $postData['traveltype'] == 'ITR'){
                $postData['travelIdToInsert']=1;
            }
            // echo '<pre>';print_r($postData);die;
            $travelNewId = ((int) Helper::getMaxId($this->adapter, TravelRequestModel::TABLE_NAME, TravelRequestModel::TRAVEL_ID)) + 1;

            if($postData['submit']=='Submit'){

            if ($postData['travelIdToInsert'] != '') {
               
                $travelId = $postData['travelIdToInsert'];
                $detail = $this->repository->fetchById($travelId);

                $traveltype = '';
                if ($postData['erTypeL'][0] != -1 && $postData['traveltype'] == 'LTR' &&  $postData['travelIdToInsert'] != ''){
                    for ($i = 0; $i < count($postData['erTypeL']); $i++){
                        $model->travelExpenseId = ((int) Helper::getMaxId($this->adapter, "hris_travel_expense", "TRAVEL_EXPENSE_ID")) + 1;
                        $model->travelId= $travelNewId;
                        $model->amount= $postData['amountExpL'][$i];
                        $model->exchangeRate=1;
                        $model->expenseDate=Helper::getcurrentExpressionDate();
                        $model->status='E';
                        $model->remarks=$postData['detRemarksL'][$i];
                        $model->createdDt=Helper::getcurrentExpressionDate();
                        $model->departure_Place = $postData['locFromL'][$i];
                        $model->arraival_DT = Helper::getExpressionDate($postData['arrDateL'][$i]);
                        $model->erType = $postData['erTypeL'][$i];
                        $model->billNo = $postData['ticketNoL'][$i];
                        $model->expenseHead = $postData['expenseHeadL'][$i];
                        $model->currency = 'NPR';
                        // echo '<pre>';print_r($model);die;

                        $repo->add($model);
                       
                    }
                    $traveltype = 'LTR'; 
                }
                if ($postData['erTypeI'][0] != -1 && $postData['traveltype'] == 'ITR' &&  $postData['travelIdToInsert'] != ''){
                    for ($j = 0; $j < count($postData['erTypeI']); $j++){
                        // $d = $postData['amountExp'][$j] * $postData['exchangeRateInternational'][$j];
                        $model->travelExpenseId = ((int) Helper::getMaxId($this->adapter, "hris_travel_expense", "TRAVEL_EXPENSE_ID")) + 1;
                        $model->travelId= $travelNewId;
                        $model->amount= $postData['amountExp'][$j] * $postData['exchangeRateInternational'][$j];
                        $model->exchangeRate=$postData['exchangeRateInternational'][$j];
                        
                        $model->expenseDate=Helper::getcurrentExpressionDate();
                        $model->status='E';
                        $model->remarks=$postData['detRemarks'][$j];
                        $model->createdDt=Helper::getcurrentExpressionDate();
                        $model->departure_Place = $postData['locFrom'][$j];
                        $model->arraival_DT = Helper::getExpressionDate($postData['arrDate'][$j]);
                        $model->erType = $postData['erTypeI'][$j];
                        $model->billNo = $postData['ticketNo'][$j];
                        $model->expenseHead = $postData['expenseHead'][$j];
                        $model->currency = $postData['currency'][$j];
                    // echo '<pre>';print_r($model);die;
                        $repo->add($model);
                    }
                    $traveltype = 'ITR'; 

                }


                $reqModel->travelId = ((int) Helper::getMaxId($this->adapter, TravelRequestModel::TABLE_NAME, TravelRequestModel::TRAVEL_ID)) + 1;
                $reqModel->employeeId = $this->employeeId;
                $reqModel->requestedDate = Helper::getcurrentExpressionDate();
                $reqModel->status = 'RQ';
                $reqModel->travelCode = $postData['travelcode'];
                $reqModel->requestedType = 'ep';
                $reqModel->requestedAmount = $this->repository->getTotalExpenseAmount($travelNewId);
                // $reqModel->referenceTravelId = $travelId;
                $reqModel->departureDate = $postData['FROM_DATE'];
                $reqModel->returnedDate = $postData['TO_DATE'];
                $reqModel->currencyname = 'NPR';
                $reqModel->fromDate = $postData['fromDate'];
                $reqModel->toDate = $postData['toDate'];
                $reqModel->destination = $postData['destination'];
                $reqModel->departure = $postData ['departure'];
                $reqModel->purpose = $postData['purpose'];
                $reqModel->fromDate = Helper::getExpressionDate($reqModel->fromDate);
                $reqModel->toDate = Helper::getExpressionDate($reqModel->toDate);
                $reqModel->traveltype = $traveltype;
                $reqModel->travelCode =((int) Helper::getMaxId($this->adapter, TravelRequestModel::TABLE_NAME, TravelRequestModel::TRAVEL_CODE)) + 1;
                // echo '<pre>'; print_r($reqModel); die;
                $this->repository->add($reqModel);
            } else {
                if ($postData['erTypeL'][0] != -1){
                    for ($i = 0; $i < count($postData['erTypeL']); $i++){
                        // var_dump($classId); die;
                        $model->travelExpenseId = ((int) Helper::getMaxId($this->adapter, "hris_travel_expense", "TRAVEL_EXPENSE_ID")) + 1;
                        $model->travelId= $travelNewId;
                        $model->amount= $postData['amountExpL'][$i];
                        $model->exchangeRate=1;
                        $model->expenseDate=Helper::getcurrentExpressionDate();
                        $model->status='E';
                        $model->remarks=$postData['detRemarksL'][$i];
                        
                        $model->createdDt=Helper::getcurrentExpressionDate();
                        $model->departure_Place = $postData['locFromL'][$i];
                        $model->arraival_DT = Helper::getExpressionDate($postData['arrDateL'][$i]);
                        $model->erType = $postData['erTypeL'][$i];
                        $model->billNo = $postData['ticketNoL'][$i];
                        $model->expenseHead = $postData['expenseHeadL'][$i];
                        $model->currency = 'NPR';
                        $repo->add($model);
                    }

                    $reqModel->travelId = ((int) Helper::getMaxId($this->adapter, TravelRequestModel::TABLE_NAME, TravelRequestModel::TRAVEL_ID)) + 1;
                    $reqModel->employeeId = $this->employeeId;
                    $reqModel->requestedDate = Helper::getcurrentExpressionDate();
                    $reqModel->status = 'RQ';
                    $reqModel->purpose = $postData['purpose'];
                    $reqModel->departure =$postData['departure'];
                    $reqModel->purpose = '-';
                    $reqModel->requestedType = 'ep';
                    $reqModel->requestedAmount = $this->repository->getTotalExpenseAmount($travelNewId);
                    $reqModel->currencyname = 'NPR';
                    $reqModel->traveltype = 'DT';
                    $reqModel->travelCode =((int) Helper::getMaxId($this->adapter, TravelRequestModel::TABLE_NAME, TravelRequestModel::TRAVEL_CODE)) + 1;
                    // $reqModel->referenceTravelId = $travelId;
                    $reqModel->fromDate = Helper::getExpressionDate($postData['arrDateL'][0]);
                    $reqModel->toDate = Helper::getExpressionDate($postData['arrDateL'][0]);
                    $this->repository->add($reqModel);

                }
            }
            $error = "";
            $preference=$this->repository->getPreferenceData();
                        // echo '<pre>';print_r($preference[0]);die;
            try {
                // if(isset($this->preference['travelSingleApprover']) && $this->preference['travelSingleApprover'] == 'Y'){
                if(isset($preference[18]['KEY']) && $preference[18]['VALUE'] == 'Y'){
                    HeadNotification::pushNotification(NotificationEvents::TRAVEL_EXPENSE_APPLIED, $reqModel, $this->adapter, $this);
                }else{
                    HeadNotification::pushNotification(NotificationEvents::TRAVEL_APPLIED, $reqModel, $this->adapter, $this);
                } 
            } catch (Exception $e) {
                $error = $e->getMessage();
            }
        }else{
            if ($postData['travelIdToInsert'] != '') {
               
                $travelId = $postData['travelIdToInsert'];
                $detail = $this->repository->fetchById($travelId);
                    // echo '<pre>';print_r();die;
                $traveltype = '';
                if ($postData['erTypeL'][0] != -1 && $postData['traveltype'] == 'LTR' &&  $postData['travelIdToInsert'] != ''){
                    for ($i = 0; $i < count($postData['erTypeL']); $i++){
                        $model->travelExpenseId = ((int) Helper::getMaxId($this->adapter, "hris_travel_expense", "TRAVEL_EXPENSE_ID")) + 1;
                        $model->travelId= $travelNewId;
                        $model->amount= $postData['amountExpL'][$i];
                        $model->exchangeRate=1;
                        $model->expenseDate=Helper::getcurrentExpressionDate();
                        $model->status='E';
                        $model->remarks=$postData['detRemarksL'][$i];
                        $model->createdDt=Helper::getcurrentExpressionDate();
                        $model->departure_Place = $postData['locFromL'][$i];
                        $model->arraival_DT = Helper::getExpressionDate($postData['arrDateL'][$i]);
                        $model->erType = $postData['erTypeL'][$i];
                        $model->billNo = $postData['ticketNoL'][$i];
                        $model->expenseHead = $postData['expenseHeadL'][$i];
                        $model->currency = 'NPR';
                        // echo '<pre>';print_r($model);die;

                        $repo->add($model);
                       
                    }
                    $traveltype = 'LTR'; 
                }
                if ($postData['erTypeI'][0] != -1 && $postData['traveltype'] == 'ITR' &&  $postData['travelIdToInsert'] != ''){
                    for ($j = 0; $j < count($postData['erTypeI']); $j++){
                        // $d = $postData['amountExp'][$j] * $postData['exchangeRateInternational'][$j];
                        $model->travelExpenseId = ((int) Helper::getMaxId($this->adapter, "hris_travel_expense", "TRAVEL_EXPENSE_ID")) + 1;
                        $model->travelId= $travelNewId;
                        $model->amount= $postData['amountExp'][$j] * $postData['exchangeRateInternational'][$j];
                        $model->exchangeRate=$postData['exchangeRateInternational'][$j];
                        
                        $model->expenseDate=Helper::getcurrentExpressionDate();
                        $model->status='E';
                        $model->remarks=$postData['detRemarks'][$j];
                        $model->createdDt=Helper::getcurrentExpressionDate();
                        $model->departure_Place = $postData['locFrom'][$j];
                        $model->arraival_DT = Helper::getExpressionDate($postData['arrDate'][$j]);
                        $model->erType = $postData['erTypeI'][$j];
                        $model->billNo = $postData['ticketNo'][$j];
                        $model->expenseHead = $postData['expenseHead'][$j];
                        $model->currency = $postData['currency'][$j];
                    // echo '<pre>';print_r($model);die;
                        $repo->add($model);
                    }
                    $traveltype = 'ITR'; 

                }


                $reqModel->travelId = ((int) Helper::getMaxId($this->adapter, TravelRequestModel::TABLE_NAME, TravelRequestModel::TRAVEL_ID)) + 1;
                $reqModel->employeeId = $this->employeeId;
                $reqModel->requestedDate = Helper::getcurrentExpressionDate();
                $reqModel->status = 'SV';
                $reqModel->travelCode = $postData['travelcode'];
                $reqModel->requestedType = 'ep';
                $reqModel->requestedAmount = $this->repository->getTotalExpenseAmount($travelNewId);
                // $reqModel->referenceTravelId = $travelId;
                $reqModel->departureDate = $postData['FROM_DATE'];
                $reqModel->returnedDate = $postData['TO_DATE'];
                $reqModel->currencyname = 'NPR';
                $reqModel->fromDate = $postData['fromDate'];
                $reqModel->toDate = $postData['toDate'];
                $reqModel->destination = $postData['destination'];
                $reqModel->departure = $postData ['departure'];
                $reqModel->purpose = $postData['purpose'];
                $reqModel->travelCode =((int) Helper::getMaxId($this->adapter, TravelRequestModel::TABLE_NAME, TravelRequestModel::TRAVEL_CODE)) + 1;
                $reqModel->fromDate = Helper::getExpressionDate($reqModel->fromDate);
                $reqModel->toDate = Helper::getExpressionDate($reqModel->toDate);
                $reqModel->traveltype = $traveltype;
                // echo '<pre>'; print_r($reqModel); die;
                $this->repository->add($reqModel);
            } else {
                if ($postData['erTypeL'][0] != -1){
                    for ($i = 0; $i < count($postData['erTypeL']); $i++){
                        // var_dump($classId); die;
                        $model->travelExpenseId = ((int) Helper::getMaxId($this->adapter, "hris_travel_expense", "TRAVEL_EXPENSE_ID")) + 1;
                        $model->travelId= $travelNewId;
                        $model->amount= $postData['amountExpL'][$i];
                        $model->exchangeRate=1;
                        $model->expenseDate=Helper::getcurrentExpressionDate();
                        $model->status='E';
                        $model->remarks=$postData['detRemarksL'][$i];
                        
                        $model->createdDt=Helper::getcurrentExpressionDate();
                        $model->departure_Place = $postData['locFromL'][$i];
                        $model->arraival_DT = Helper::getExpressionDate($postData['arrDateL'][$i]);
                        $model->erType = $postData['erTypeL'][$i];
                        $model->billNo = $postData['ticketNoL'][$i];
                        $model->expenseHead = $postData['expenseHeadL'][$i];
                        $model->currency = 'NPR';
                        $repo->add($model);
                    }

                    $reqModel->travelId = ((int) Helper::getMaxId($this->adapter, TravelRequestModel::TABLE_NAME, TravelRequestModel::TRAVEL_ID)) + 1;
                    $reqModel->employeeId = $this->employeeId;
                    $reqModel->requestedDate = Helper::getcurrentExpressionDate();
                    $reqModel->status = 'SV';
                    $reqModel->purpose = $postData['purpose'];
                    // $reqModel->departure =$postData['departure'];
                    $reqModel->requestedType = 'ep';
                    $reqModel->requestedAmount = $this->repository->getTotalExpenseAmount($travelNewId);
                    $reqModel->currencyname = 'NPR';
                    $reqModel->traveltype = 'DT';
                    $reqModel->travelCode =((int) Helper::getMaxId($this->adapter, TravelRequestModel::TABLE_NAME, TravelRequestModel::TRAVEL_CODE)) + 1;
                    $reqModel->fromDate = Helper::getExpressionDate($postData['arrDateL'][0]);
                    $reqModel->toDate = Helper::getExpressionDate($postData['arrDateL'][0]);
                    // echo '<pre>';print_r($postData);die;
                    $this->repository->add($reqModel);
                }
            }
        }
            $this->flashmessenger()->addMessage("Successfully Added!!!");
                return $this->redirect()->toRoute('newtravelrequest', ['action'=>'expense']);
            
        }
        $transportTypes = array(
            null => '------------',
            'AP' => 'Aeroplane',
            'OV' => 'Office Vehicles',
            'TI' => 'Taxi',
            'BS' => 'Bus',
            'OF'  => 'On Foot',
            'OT'=>'Others',
            'VV'=>'Own-Vehicle'
        );
        $expenseHeads = array(
            array('gl' => null, 'name'  => '---select expense heads---'),
            array('gl' => 'Accommodation', 'name' => 'Accommodation'),
            array('gl' => 'Airfare', 'name' => 'Airfare'),
            array('gl' => 'Airport Taxes and visas etc', 'name' => 'Airport Taxes and visas etc'),
            array('gl' => 'Auto Maintenance', 'name' => 'Auto Maintenance'),
            array('gl' => 'Books Newspaper & Subscription', 'name' => 'Books Newspaper & Subscription'),
            array('gl' => 'Black Berry (Monthly Usage Bill)', 'name' => 'Black Berry (Monthly Usage Bill)'),
            array('gl' => 'Car hire', 'name' => 'Car hire'),
            array('gl' => 'Cafeteria Expenses-admin', 'name' => 'Cafeteria Expenses-admin'),
            array('gl' => 'Club Membership Fee', 'name' => 'Club Membership Fee'),
            array('gl' => 'Conveyance- Car Rental', 'name' => 'Conveyance- Car Rental'),
            array('gl' => 'Conveyance - Other', 'name' => 'Conveyance - Other'),
            array('gl' => 'Daily Allowance', 'name' => 'Daily Allowance'),
            array('gl' => 'Data Card', 'name' => 'Data Card'),
            array('gl' => 'Entertainment', 'name' => 'Entertainment'),
            array('gl' => 'Expatriate Benefits', 'name' => 'Expatriate Benefits'),
            array('gl' => 'Expatriate CLA Expenses', 'name' => 'Expatriate CLA Expenses'),
            array('gl' => 'Gasoline Diesel Fuel Oil', 'name' => 'Gasoline Diesel Fuel Oil'),
            array('gl' => 'Guest House Expenses (incl electricity, maint & ot', 'name' => 'Guest House Expenses (incl electricity, maint & ot'),
            array('gl' => 'Hotel Meals (inclusive of Tips)', 'name' => 'Hotel Meals (inclusive of Tips)'),
            array('gl' => 'IS Information (others)', 'name' => 'IS Information (others)'),
            array('gl' => 'Lab Testing and Certification Cost', 'name' => 'Lab Testing and Certification Cost'),
            array('gl' => 'Laundry', 'name' => 'Laundry'),
            array('gl' => 'Lodging', 'name' => 'Lodging'),
            array('gl' => 'Meeting', 'name' => 'Meeting'),
            array('gl' => 'Miscellaneous Expenses', 'name' => 'Miscellaneous Expenses'),
            array('gl' => 'Mobile Handset Reimbursement', 'name' => 'Mobile Handset Reimbursement'),
            array('gl' => 'Mobile Phone Expenses', 'name' => 'Mobile Phone Expenses'),
            array('gl' => 'Other Tips', 'name' => 'Other Tips'),
            array('gl' => 'Postage & Courier Charge', 'name' => 'Postage & Courier Charge'),
            array('gl' => 'Reimb on Stamp Paper', 'name' => 'Reimb on Stamp Paper'),
            array('gl' => 'Relocation Expenses', 'name' => 'Relocation Expenses'),
            array('gl' => 'Stationary', 'name' => 'Stationary'),
            array('gl' => 'Supplies General', 'name' => 'Supplies General'),
            array('gl' => 'Ticket - Others', 'name' => 'Ticket - Others'),
            array('gl' => 'Toll Fees', 'name' => 'Toll Fees'),
            array('gl' => 'Taxi/Bus/Car rental (inc fuel & conv allow)', 'name' => 'Taxi/Bus/Car rental (inc fuel & conv allow)'),
            array('gl' => 'Telephone/Fax Expenses', 'name' => 'Telephone/Fax Expenses'),
            array('gl' => 'Train Fare/Bus Fare', 'name' => 'Train Fare/Bus Fare'),
            array('gl' => 'Training Expenses', 'name' => 'Training Expenses'),
            array('gl' => 'Uniforms and Towels -Admin', 'name' => 'Uniforms and Towels -Admin'),
        );

        $erTypes = array(
            null => '------------',
            'EP' => 'Employee Paid',
            'CP' => 'Company Paid',
        );
        $expenseHead = array(
            null => '------------',
            'EP' => 'Employee Paid',
            'CP' => 'Company Paid',
        );
        // echo'<pre>';print_r(transportTypes);die;
        return Helper::addFlashMessagesToArray($this, [
            'form' => $this->form,
            'transportTypes' => $transportTypes,
            'erTypes' => $erTypes,
            'expenseHeads' => $expenseHeads,
            'employeeDetails' => $employeeDetails,
            'destinationsL' => $localATravels,
            'IntATravels'=> $IntATravels,
        ]);
    }

    public function internationalTravelRequestAction()
    {
        $request = $this->getRequest();
        if ($request->isPost()) {
            try {
                $data = (array) $request->getPost();
                $data['employeeId'] = $this->employeeId;
                $data['requestedType'] = 'ad';  
                $data['travelType'] = "ITR";   
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

    // request for international Advance
    public function advanceAddInternationalAction()
    {
        $request = $this->getRequest();
        $id = (int) $this->params()->fromRoute('id');
        if ($id === 0) {
            return $this->redirect()->toRoute("newtravelrequest");
        }
        if ($request->isPost()) {
            $travelRequest = new TravelRequestModel();
            $postedData = $request->getPost();
            //  echo '<pre>'; print_r($postedData); die;
            $this->form->setData($postedData);
            $model = new TravelRequestModel();
            if ($this->form->isValid()) {
                $model->exchangeArrayFromForm($this->form->getData());
                $model->requestedAmount = ($postedData->requestedAmount == null) ? 0 : $postedData->requestedAmount;
                $model->travelId = ((int) Helper::getMaxId($this->adapter, TravelRequestModel::TABLE_NAME, TravelRequestModel::TRAVEL_ID)) + 1;
                $model->employeeId = $this->employeeId;
                $model->requestedDate = Helper::getcurrentExpressionDate();
                $model->status = 'RQ';
                $model->requestedType = 'ad';
                $model->fromDate = Helper::getExpressionDate($model->fromDate);
                $model->toDate = Helper::getExpressionDate($model->toDate);
                $model->traveltype = $postedData['travelType'];
                $model->conversionrate = $postedData['conversionrate'];
                $model->currencyname = $postedData['currency'];
                // $model->referenceTravelId = $id;
                // $addData=$model->getArrayCopyForDB();
                // echo '<pre>'; print_r($model); die;
                $this->repository->add($model);    
                $this->flashmessenger()->addMessage("International Travel Advance Request Successfully Created!!!");
                return $this->redirect()->toRoute("newtravelrequest");
            }
            
        }

        $detail = $this->repository->fetchById($id);
        //$fileDetails = $this->repository->fetchAttachmentsById($id);
        // echo '<pre>'; print_r($detail); die;
        $model = new TravelRequestModel();
        $model->exchangeArrayFromDB($detail);
        $this->form->bind($model);

        $numberInWord = new NumberHelper();
        $advanceAmount = $numberInWord->toText($detail['REQUESTED_AMOUNT']);

        $transportTypes = array(
            null => '------------',
            'AP' => 'Aeroplane',
            'OV' => 'Office Vehicles',
            'TI' => 'Taxi',
            'BS' => 'Bus',
            'OF'  => 'On Foot',
            'OT'=>'Others',
            'VV'=>'Own-Vehicle'
        );

        return Helper::addFlashMessagesToArray($this, [
                    'form' => $this->form,
                    'recommender' => $detail['RECOMMENDED_BY_NAME'] == null ? $detail['RECOMMENDER_NAME'] : $detail['RECOMMENDED_BY_NAME'],
                    'approver' => $detail['APPROVED_BY_NAME'] == null ? $detail['APPROVER_NAME'] : $detail['APPROVED_BY_NAME'],
                    'detail' => $detail,
                    'todayDate' => date('d-M-Y'),
                    'advanceAmount' => $advanceAmount,
                    'transportTypes' => $transportTypes
                        //'files' => $fileDetails
        ]);
    }

    public function fileUploadAction()
    {
        $request = $this->getRequest();
        $responseData = [];
        $files = $request->getFiles()->toArray();
        try {
            if (sizeof($files) > 0) {
                $ext = pathinfo($files['file']['name'], PATHINFO_EXTENSION);
                if (strtolower($ext)== 'txt' || strtolower($ext) == 'pdf' || strtolower($ext) == 'jpg' || strtolower($ext) == 'jpeg' || strtolower($ext) == 'png' || strtolower($ext)=='docx' || strtolower($ext)=='odt' || strtolower($ext)=='doc' ) {
                    $fileName = pathinfo($files['file']['name'], PATHINFO_FILENAME);
                    $unique = Helper::generateUniqueName();
                    $newFileName = $unique . "." . $ext;
                    $success = move_uploaded_file($files['file']['tmp_name'], Helper::UPLOAD_DIR . "/travel-documents/" . $newFileName);
                    $responseData = ["success" => true, "data" => ["fileName" => $newFileName, "oldFileName" => $fileName . "." . $ext]];
                } else { 
                    throw new Exception("Upload unsuccessful.");
                    //$this->flashmessenger()->addMessage("Employee Successfully Deleted!!!");
                    // echo '<script>alert("Welcome to Geeks for Geeks")</script>';
                    // echo ("<script type='text/javascript'>alert('We welcome the New World');</script>");
                    // echo ('<script language="javascript">alert("hello")</script>');
                    ///============================== 
                }
            }
        } catch (Exception $e) {
            $responseData = [
                "success" => false,
                "message" => $e->getMessage(),
                "traceAsString" => $e->getTraceAsString(),
                "line" => $e->getLine(),
            ];
        }
        return new JsonModel($responseData);
    }

    public function deleteExpenseDetailAction() {
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

    public function expenseAction() {
        $request = $this->getRequest();
        if ($request->isPost()) {
            try {
                $data = (array) $request->getPost();
                $data['employeeId'] = $this->employeeId;
                $data['requestedType'] = 'ep';
                $rawList = $this->repository->getFilteredRecords($data);
                $list = iterator_to_array($rawList, false);
                // echo '<pre>'; print_r($list); die;
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

    public function expenseViewAction() {
        //  var_dump('dvd'); die;
        $id = (int) $this->params()->fromRoute('id');

        if ($id === 0) {
            return $this->redirect()->toRoute("newtravelrequest");
        }
        // $detailxdc = $this->repository->fetchById($id);
        
        //  if ($detailxdc['REFERENCE_TRAVEL_ID'] != null) {
        //      # code..
        //      $detail = $this->repository->fetchById($detailxdc['REFERENCE_TRAVEL_ID']);
        //  }else{
            $detail = $this->repository->fetchById($id);
        //  }

        $model = new TravelRequestModel();
        $model->exchangeArrayFromDB($detail);
        $this->form->bind($model);

        $expenseDtlRepo = new TravelExpenseDtlRepository($this->adapter);
        $result = $expenseDtlRepo->fetchByTravelId($id);
        // echo '<pre>';print_r($detail);die;
        
        $totalAmount = 0;
        $balance = $detail['REQUESTED_AMOUNT'] - $totalAmount;
        $numberInWord = new NumberHelper();
        $advanceAmount = $numberInWord->toText($detail['REQUESTED_AMOUNT']);
        $totalAmountSum =  $expenseDtlRepo->sumTotalAmount($id);
        $totalExpenseInWords = $numberInWord->toText($totalAmountSum['TOTAL']);
        // echo '<pre>'; print_r($result); die;
        return Helper::addFlashMessagesToArray($this, [
                    'form' => $this->form,
                    'recommender' => $detail['RECOMMENDED_BY_NAME'] == null ? $detail['RECOMMENDER_NAME'] : $detail['RECOMMENDED_BY_NAME'],
                    'approver' => $detail['APPROVED_BY_NAME'] == null ? $detail['APPROVER_NAME'] : $detail['APPROVED_BY_NAME'],
                    'detail' => $detail,
                    'expenseDtlList' => $result,
                    'todayDate' => date('d-M-Y'),
                    'advanceAmount' => $advanceAmount,
                    'totalExpenseInWords' => $totalExpenseInWords,
                    'totalExpense' => $totalAmount,
                    'totalAmountSum' => $totalAmountSum,
                    'balance' => $balance,
                    // 'detailxdc'=> $detailxdc
        ]);
    }

    public function viewAction() {
        $id = (int) $this->params()->fromRoute('id');
        if ($id === 0) {
            return $this->redirect()->toRoute("newtravelrequest");
        }

        $detail = $this->repository->fetchById($id);


        // echo '<pre>'; print_r( $detail['FROM_DATE_BS']); die;
        if($this->preference['displayHrApproved'] == 'Y' && $detail['HARDCOPY_SIGNED_FLAG'] == 'Y'){
            $detail['APPROVER_ID'] = '-1';
            $detail['APPROVER_NAME'] = 'HR';
            $detail['RECOMMENDER_ID'] = '-1';
            $detail['RECOMMENDER_NAME'] = 'HR';
        }
        $fileDetails = $this->repository->fetchFilesById($id);
        // echo '<pre>'; print_r($detail); die;
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
                    'advanceAmount' => $advanceAmount,
                    'filesnew' => $fileDetails
        ]);
    }

    public function deleteAction() {
        $id = (int) $this->params()->fromRoute("id");
        if (!$id) {
            return $this->redirect()->toRoute('newtravelrequest');
        }
        $this->repository->delete($id);
        $this->flashmessenger()->addMessage("Travel Request Successfully Cancelled!!!");
        return $this->redirect()->toRoute('newtravelrequest');
    }

    public function getTravelDetailAction()
    {
        $request = $this->getRequest();
        $data =$request->getPost();

        if ($data['type'] == 'LTR') {
            $localATravels = $this->repository->getLTraveldetailsId($data['travelId']);
        } elseif($data['type'] == 'ITR') {
            $localATravels = $this->repository->getLTraveldetailsIdInternational($data['travelId']);
        }
        try {
            return new JsonModel(['success' => true, 'data' =>$localATravels, 'error' => '']);
        } catch (Exception $e) {
            return new JsonModel(['success' => false, 'data' => [], 'error' => $e->getMessage()]);
        }
    }

    
    public function editAction() {
        $request = $this->getRequest();
        $employeeId = $this->employeeId;
        // print_r($employeeId); die;
        $employeeDetails = $this->repository->getEmployeeData($employeeId);
        $id = (int) $this->params()->fromRoute('id');

        if ($id === 0) {
            return $this->redirect()->toRoute("newtravelrequest");
        }
        $detailxdc = $this->repository->fetchById($id);
        

        //  if ($detailxdc['REFERENCE_TRAVEL_ID'] != null) {
        //      # code..
        //      $detail = $this->repository->fetchById($detailxdc['REFERENCE_TRAVEL_ID']);
        //  }else{
            $detail = $this->repository->fetchById($id);
        //  }

        $model =$model = new TravelExpensesModel();
        $model->exchangeArrayFromDB($detail);
        $this->form->bind($model);
        
        $expenseDtlRepo = new TravelExpenseDtlRepository($this->adapter);
        $result = $expenseDtlRepo->fetchByTravelId($id);
        $repo = new TravelExpensesRepository($this->adapter);
        $reqModel = new TravelRequestModel();

        $totalAmount = 0;
        $balance = $detail['REQUESTED_AMOUNT'] - $totalAmount;
        $numberInWord = new NumberHelper();
        $advanceAmount = $numberInWord->toText($detail['REQUESTED_AMOUNT']);
        $totalAmountSum =  $expenseDtlRepo->sumTotalAmount($id);
        $totalExpenseInWords = $numberInWord->toText($totalAmountSum['TOTAL']);
        if ($request->isPost()) {
            $result = $expenseDtlRepo->fetchByTravelIdDelete($id);

            $postData = $request->getPost()->getArrayCopy();
           if($postData['submit']=='Submit'){
           if( isset($postData['erTypeL']) == true 
        //    && $detailxdc['REFERENCE_TRAVEL_ID'] != ''
           ){
            for ($i = 0; $i < count($postData['erTypeL']); $i++){
                // var_dump('hrhf'); die;
                //  echo '<pre>'; print_r($postData); die;
                $model->travelExpenseId = ((int) Helper::getMaxId($this->adapter, "hris_travel_expense", "TRAVEL_EXPENSE_ID")) + 1;
                $model->travelId= $detailxdc['TRAVEL_ID'];
                $model->amount= $postData['amountExp'][$i];
                $model->exchangeRate=1;
                $model->expenseDate=Helper::getcurrentExpressionDate();
                $model->status='E';
                $model->remarks=$postData['detRemarks'][$i];
                $model->createdDt=Helper::getcurrentExpressionDate();
                $model->departure_Place = $postData['locFrom'][$i];
                $model->arraival_DT = Helper::getExpressionDate($postData['arrDate'][$i]);
                $model->erType = $postData['erTypeL'][$i];
                $model->billNo = $postData['ticketNo'][$i];
                $model->expenseHead = $postData['expenseHead'][$i];
                $model->currency = 'NPR';
            //   echo '<pre>'; print_r($model);die;
                $repo->add($model);
               
            }
            $reqModel->fromDate = $postData['departureDate'];
            $reqModel->toDate = $postData['returnedDate'];
            $reqModel->destination = $postData['destination'];
            $reqModel->departure = $postData ['departure'];
            $reqModel->purpose = $postData['purpose'];
            $reqModel->status = 'RQ';
            $reqModel->fromDate = Helper::getExpressionDate($reqModel->fromDate);
            $reqModel->toDate = Helper::getExpressionDate($reqModel->toDate);
            $reqModel->requestedDate = Helper::getcurrentExpressionDate();
            // echo("<pre>");print_r($reqModel);die;

            $this->repository->edit($reqModel,$id);


           }
          elseif( isset($postData['erTypeI']) == true){
                for ($j = 0; $j < count($postData['erTypeI']); $j++){
                    //    var_dump('herer'); die;
                    // $d = $postData['amountExp'][$j] * $postData['exchangeRateInternational'][$j];
                    $model->travelExpenseId = ((int) Helper::getMaxId($this->adapter, "hris_travel_expense", "TRAVEL_EXPENSE_ID")) + 1;
                    $model->travelId= $detailxdc['TRAVEL_ID'];
                    $model->amount= $postData['amountExp'][$j] * $postData['exchangeRateInternational'][$j];
                    $model->exchangeRate=$postData['exchangeRateInternational'][$j];
                    
                    $model->expenseDate=Helper::getcurrentExpressionDate();
                    $model->status='E';
                    $model->remarks=$postData['detRemarks'][$j];
                    $model->createdDt=Helper::getcurrentExpressionDate();
                    $model->departure_Place = $postData['locFrom'][$j];
                    $model->arraival_DT = Helper::getExpressionDate($postData['arrDate'][$j]);
                    $model->erType = $postData['erTypeI'][$j];
                    $model->billNo = $postData['ticketNo'][$j];
                    $model->expenseHead = $postData['expenseHead'][$j];
                    $model->currency = $postData['currency'][$j];
                    // $model->trave
                    // var_dump($d); die;
                    // echo '<pre>'; print_r($model); die;
                    $repo->add($model);
                }
                $reqModel->fromDate = $postData['departureDate'];
                $reqModel->toDate = $postData['returnedDate'];
                $reqModel->destination = $postData['destination'];
                $reqModel->departure = $postData ['departure'];
                $reqModel->purpose = $postData['purpose'];
                $reqModel->status = 'RQ';
                $reqModel->fromDate = Helper::getExpressionDate($reqModel->fromDate);
                $reqModel->toDate = Helper::getExpressionDate($reqModel->toDate);
                $reqModel->requestedDate = Helper::getcurrentExpressionDate();
                // echo("<pre>");print_r($reqModel);die;
    
                $this->repository->edit($reqModel,$id);
            }else{
                for ($i = 0; $i < count($postData['erTypeL']); $i++){
                    // var_dump('$classId'); die;
                    $model->travelExpenseId = ((int) Helper::getMaxId($this->adapter, "hris_travel_expense", "TRAVEL_EXPENSE_ID")) + 1;
                    $model->travelId= $detailxdc['TRAVEL_ID'];
                    $model->amount= $postData['amountExp'][$i];
                    $model->exchangeRate=1;
                    $model->expenseDate=Helper::getcurrentExpressionDate();
                    $model->status='E';
                    $model->remarks=$postData['detRemarks'][$i];
                    
                    $model->createdDt=Helper::getcurrentExpressionDate();
                    $model->departure_Place = $postData['locFrom'][$i];
                    $model->arraival_DT = Helper::getExpressionDate($postData['arrDate'][$i]);
                    $model->erType = $postData['erTypeL'][$i];
                    $model->billNo = $postData['ticketNo'][$i];
                    $model->expenseHead = $postData['expenseHead'][$i];
                    $model->currency = 'NPR';
                    // echo '<pre>'; print_r($model); die;
                    $repo->add($model);
                }
                $reqModel->fromDate = $postData['departureDate'];
                $reqModel->toDate = $postData['returnedDate'];
                $reqModel->destination = $postData['destination'];
                $reqModel->departure = $postData ['departure'];
                $reqModel->purpose = $postData['purpose'];
                $reqModel->status = 'RQ';
                $reqModel->fromDate = Helper::getExpressionDate($reqModel->fromDate);
                $reqModel->toDate = Helper::getExpressionDate($reqModel->toDate);
                $reqModel->requestedDate = Helper::getcurrentExpressionDate();
                // echo("<pre>");print_r($reqModel);die;
    
                $this->repository->edit($reqModel,$id);
            }
            $totalamountfortravle = $this->repository->getTotalExpenseAmount($detailxdc['TRAVEL_ID']);
            // echo '<pre>'; print_r($totalamountfortravle); die;

            $addTotAMOUNT = $expenseDtlRepo->updateByTravelIdDelete($detailxdc['TRAVEL_ID'],$totalamountfortravle);

            $this->flashmessenger()->addMessage("Successfully Updated!!!");
                return $this->redirect()->toRoute("newtravelrequest", ["action" => "expense"]);
        } else{
            if( isset($postData['erTypeL']) == true 
           ){
            for ($i = 0; $i < count($postData['erTypeL']); $i++){
                $model->travelExpenseId = ((int) Helper::getMaxId($this->adapter, "hris_travel_expense", "TRAVEL_EXPENSE_ID")) + 1;
                $model->travelId= $detailxdc['TRAVEL_ID'];
                $model->amount= $postData['amountExp'][$i];
                $model->exchangeRate=1;
                $model->expenseDate=Helper::getcurrentExpressionDate();
                $model->status='E';
                $model->remarks=$postData['detRemarks'][$i];
                $model->createdDt=Helper::getcurrentExpressionDate();
                $model->departure_Place = $postData['locFrom'][$i];
                $model->arraival_DT = Helper::getExpressionDate($postData['arrDate'][$i]);
                $model->erType = $postData['erTypeL'][$i];
                $model->billNo = $postData['ticketNo'][$i];
                $model->expenseHead = $postData['expenseHead'][$i];
                $model->currency = 'NPR';
            //   echo '<pre>'; print_r($model);die;
                $repo->add($model);
               
            }
            $reqModel->fromDate = $postData['departureDate'];
            $reqModel->toDate = $postData['returnedDate'];
            $reqModel->destination = $postData['destination'];
            $reqModel->departure = $postData ['departure'];
            $reqModel->purpose = $postData['purpose'];
            $reqModel->status = 'SV';
            $reqModel->fromDate = Helper::getExpressionDate($reqModel->fromDate);
            $reqModel->toDate = Helper::getExpressionDate($reqModel->toDate);
            $reqModel->requestedDate = Helper::getcurrentExpressionDate();
            // echo("<pre>");print_r($reqModel);die;

            $this->repository->edit($reqModel,$id);


           }
          elseif( isset($postData['erTypeI']) == true){
                for ($j = 0; $j < count($postData['erTypeI']); $j++){
                    //    var_dump('herer'); die;
                    // $d = $postData['amountExp'][$j] * $postData['exchangeRateInternational'][$j];
                    $model->travelExpenseId = ((int) Helper::getMaxId($this->adapter, "hris_travel_expense", "TRAVEL_EXPENSE_ID")) + 1;
                    $model->travelId= $detailxdc['TRAVEL_ID'];
                    $model->amount= $postData['amountExp'][$j] * $postData['exchangeRateInternational'][$j];
                    $model->exchangeRate=$postData['exchangeRateInternational'][$j];
                    
                    $model->expenseDate=Helper::getcurrentExpressionDate();
                    $model->status='E';
                    $model->remarks=$postData['detRemarks'][$j];
                    $model->createdDt=Helper::getcurrentExpressionDate();
                    $model->departure_Place = $postData['locFrom'][$j];
                    $model->arraival_DT = Helper::getExpressionDate($postData['arrDate'][$j]);
                    $model->erType = $postData['erTypeI'][$j];
                    $model->billNo = $postData['ticketNo'][$j];
                    $model->expenseHead = $postData['expenseHead'][$j];
                    $model->currency = $postData['currency'][$j];
                    // $model->trave
                    // var_dump($d); die;
                    // echo '<pre>'; print_r($model); die;
                    $repo->add($model);
                }
                $reqModel->fromDate = $postData['departureDate'];
                $reqModel->toDate = $postData['returnedDate'];
                $reqModel->destination = $postData['destination'];
                $reqModel->departure = $postData ['departure'];
                $reqModel->purpose = $postData['purpose'];
                $reqModel->status = 'SV';
                $reqModel->fromDate = Helper::getExpressionDate($reqModel->fromDate);
                $reqModel->toDate = Helper::getExpressionDate($reqModel->toDate);
                $reqModel->requestedDate = Helper::getcurrentExpressionDate();    
                $this->repository->edit($reqModel,$id);
            }else{
                for ($i = 0; $i < count($postData['erTypeL']); $i++){
                    $model->travelExpenseId = ((int) Helper::getMaxId($this->adapter, "hris_travel_expense", "TRAVEL_EXPENSE_ID")) + 1;
                    $model->travelId= $detailxdc['TRAVEL_ID'];
                    $model->amount= $postData['amountExp'][$i];
                    $model->exchangeRate=1;
                    $model->expenseDate=Helper::getcurrentExpressionDate();
                    $model->status='E';
                    $model->remarks=$postData['detRemarks'][$i];
                    
                    $model->createdDt=Helper::getcurrentExpressionDate();
                    $model->departure_Place = $postData['locFrom'][$i];
                    $model->arraival_DT = Helper::getExpressionDate($postData['arrDate'][$i]);
                    $model->erType = $postData['erTypeL'][$i];
                    $model->billNo = $postData['ticketNo'][$i];
                    $model->expenseHead = $postData['expenseHead'][$i];
                    $model->currency = 'NPR';
                    // echo '<pre>'; print_r($model); die;
                    $repo->add($model);
                }
                $reqModel->fromDate = $postData['departureDate'];
                $reqModel->toDate = $postData['returnedDate'];
                $reqModel->destination = $postData['destination'];
                $reqModel->departure = $postData ['departure'];
                $reqModel->purpose = $postData['purpose'];
                $reqModel->status = 'SV';
                $reqModel->fromDate = Helper::getExpressionDate($reqModel->fromDate);
                $reqModel->toDate = Helper::getExpressionDate($reqModel->toDate);
                $reqModel->requestedDate = Helper::getcurrentExpressionDate();
    
                $this->repository->edit($reqModel,$id);
            }
            $totalamountfortravle = $this->repository->getTotalExpenseAmount($detailxdc['TRAVEL_ID']);
            $addTotAMOUNT = $expenseDtlRepo->updateByTravelIdDelete($detailxdc['TRAVEL_ID'],$totalamountfortravle);

            $this->flashmessenger()->addMessage("Successfully Updated!!!");
                return $this->redirect()->toRoute("newtravelrequest", ["action" => "expense"]);

        }
    }

        $transportTypes = array(
            null => '------------',
            'AP' => 'Aeroplane',
            'OV' => 'Office Vehicles',
            'TI' => 'Taxi',
            'BS' => 'Bus',
            'OF'  => 'On Foot',
            'OT'=>'Others',
            'VV'=>'Own-Vehicle'
        );
        $expenseHeads = array(
            array('gl' => null, 'name'  => '---select expense heads---'),
            array('gl' => 'Accommodation', 'name' => 'Accommodation'),
            array('gl' => 'Airfare', 'name' => 'Airfare'),
            array('gl' => 'Airport Taxes and visas etc', 'name' => 'Airport Taxes and visas etc'),
            array('gl' => 'Auto Maintenance', 'name' => 'Auto Maintenance'),
            array('gl' => 'Books Newspaper & Subscription', 'name' => 'Books Newspaper & Subscription'),
            array('gl' => 'Black Berry (Monthly Usage Bill)', 'name' => 'Black Berry (Monthly Usage Bill)'),
            array('gl' => 'Car hire', 'name' => 'Car hire'),
            array('gl' => 'Cafeteria Expenses-admin', 'name' => 'Cafeteria Expenses-admin'),
            array('gl' => 'Club Membership Fee', 'name' => 'Club Membership Fee'),
            array('gl' => 'Conveyance- Car Rental', 'name' => 'Conveyance- Car Rental'),
            array('gl' => 'Conveyance - Other', 'name' => 'Conveyance - Other'),
            array('gl' => 'Daily Allowance', 'name' => 'Daily Allowance'),
            array('gl' => 'Data Card', 'name' => 'Data Card'),
            array('gl' => 'Entertainment', 'name' => 'Entertainment'),
            array('gl' => 'Expatriate Benefits', 'name' => 'Expatriate Benefits'),
            array('gl' => 'Expatriate CLA Expenses', 'name' => 'Expatriate CLA Expenses'),
            array('gl' => 'Gasoline Diesel Fuel Oil', 'name' => 'Gasoline Diesel Fuel Oil'),
            array('gl' => 'Guest House Expenses (incl electricity, maint & ot', 'name' => 'Guest House Expenses (incl electricity, maint & ot'),
            array('gl' => 'Hotel Meals (inclusive of Tips)', 'name' => 'Hotel Meals (inclusive of Tips)'),
            array('gl' => 'IS Information (others)', 'name' => 'IS Information (others)'),
            array('gl' => 'Lab Testing and Certification Cost', 'name' => 'Lab Testing and Certification Cost'),
            array('gl' => 'Laundry', 'name' => 'Laundry'),
            array('gl' => 'Lodging', 'name' => 'Lodging'),
            array('gl' => 'Meeting', 'name' => 'Meeting'),
            array('gl' => 'Miscellaneous Expenses', 'name' => 'Miscellaneous Expenses'),
            array('gl' => 'Mobile Handset Reimbursement', 'name' => 'Mobile Handset Reimbursement'),
            array('gl' => 'Mobile Phone Expenses', 'name' => 'Mobile Phone Expenses'),
            array('gl' => 'Other Tips', 'name' => 'Other Tips'),
            array('gl' => 'Postage & Courier Charge', 'name' => 'Postage & Courier Charge'),
            array('gl' => 'Reimb on Stamp Paper', 'name' => 'Reimb on Stamp Paper'),
            array('gl' => 'Relocation Expenses', 'name' => 'Relocation Expenses'),
            array('gl' => 'Stationary', 'name' => 'Stationary'),
            array('gl' => 'Supplies General', 'name' => 'Supplies General'),
            array('gl' => 'Ticket - Others', 'name' => 'Ticket - Others'),
            array('gl' => 'Toll Fees', 'name' => 'Toll Fees'),
            array('gl' => 'Taxi/Bus/Car rental (inc fuel & conv allow)', 'name' => 'Taxi/Bus/Car rental (inc fuel & conv allow)'),
            array('gl' => 'Telephone/Fax Expenses', 'name' => 'Telephone/Fax Expenses'),
            array('gl' => 'Train Fare/Bus Fare', 'name' => 'Train Fare/Bus Fare'),
            array('gl' => 'Training Expenses', 'name' => 'Training Expenses'),
            array('gl' => 'Uniforms and Towels -Admin', 'name' => 'Uniforms and Towels -Admin'),
        );
        // echo '<pre>'; print_r($result); die;
        return Helper::addFlashMessagesToArray($this, [
                    'form' => $this->form,
                    'recommender' => $detail['RECOMMENDED_BY_NAME'] == null ? $detail['RECOMMENDER_NAME'] : $detail['RECOMMENDED_BY_NAME'],
                    'approver' => $detail['APPROVED_BY_NAME'] == null ? $detail['APPROVER_NAME'] : $detail['APPROVED_BY_NAME'],
                    'detail' => $detail,
                    'expenseDtlList' => $result,
                    'todayDate' => date('d-M-Y'),
                    'advanceAmount' => $advanceAmount,
                    'expenseHeads' => $expenseHeads,
                    'totalExpenseInWords' => $totalExpenseInWords,
                    'totalExpense' => $totalAmount,
                    'totalAmountSum' => $totalAmountSum['TOTAL'],
                    'balance' => $balance,
                    'detailxdc'=> $detailxdc
        ]);
    }

}
