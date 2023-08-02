<?php

namespace Travel\Controller;

use Application\Helper\EntityHelper;
use Application\Helper\Helper;
use Exception;
use Notification\Controller\HeadNotification;
use Notification\Model\NotificationEvents;
use SelfService\Form\TravelRequestForm;
use SelfService\Model\TravelRequest as TravelRequestModel;
use SelfService\Model\TravelSubstitute;
use SelfService\Repository\TravelRequestRepository;
use SelfService\Repository\TravelSubstituteRepository;
use Zend\Authentication\AuthenticationService;
use Zend\Db\Adapter\AdapterInterface;
use Zend\Form\Annotation\AnnotationBuilder;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\JsonModel;
use Application\Controller\HrisController;

class TravelApply extends HrisController {

//    private $form;
//    private $adapter;
//    private $travelRequesteRepository;
//    private $employeeId;
//    private $preference;

    public function __construct(AdapterInterface $adapter) {
        $this->adapter = $adapter;
        $this->travelRequesteRepository = new TravelRequestRepository($adapter);
        $auth = new AuthenticationService();
        $this->employeeId = $auth->getStorage()->read()['employee_id'];
        $this->preference = $auth->getStorage()->read()['preference'];
    }

//    public function initializeForm(string $formClass) {
//        $builder = new AnnotationBuilder();
//        $form = new TravelRequestForm();
//        $this->form = $builder->createForm($form);
//    }

    public function indexAction() {
        return $this->redirect()->toRoute("travelStatus");
    }

    /*
      public function fileUploadAction() {
      $request = $this->getRequest();
      $responseData = [];
      $files = $request->getFiles()->toArray();
      try {
      if (sizeof($files) > 0) {
      $ext = pathinfo($files['file']['name'], PATHINFO_EXTENSION);
      $fileName = pathinfo($files['file']['name'], PATHINFO_FILENAME);
      $unique = Helper::generateUniqueName();
      $newFileName = $unique . "." . $ext;
      $success = move_uploaded_file($files['file']['tmp_name'], Helper::UPLOAD_DIR . "/travel_documents/" . $newFileName);
      if (!$success) {
      throw new Exception("Upload unsuccessful.");
      }
      $responseData = ["success" => true, "data" => ["fileName" => $newFileName, "oldFileName" => $fileName . "." . $ext]];
      }
      } catch (Exception $e) {
      $responseData = [
      "success" => false,
      "message" => $e->getMessage(),
      "traceAsString" => $e->getTraceAsString(),
      "line" => $e->getLine()
      ];
      }
      return new JsonModel($responseData);
      }

      public function pushTravelFileLinkAction() {
      try {
      $newsId = $this->params()->fromRoute('id');
      $request = $this->getRequest();
      $data = $request->getPost();
      $returnData = $this->travelRequesteRepository->pushFileLink($data);
      return new JsonModel(['success' => true, 'data' => $returnData[0], 'message' => null]);
      } catch (Exception $e) {
      return new JsonModel(['success' => false, 'data' => null, 'message' => $e->getMessage()]);
      }
      }
     */

    public function addAction() {
        $this->initializeForm(TravelRequestForm::class);
        $request = $this->getRequest();
        $model = new TravelRequestModel();
        if ($request->isPost()) {
            // var_dump('here'); die;
            $postData = $request->getPost();
            $postFiles = $request->getFiles();
            $this->form->setData($postData);
            

            if ($this->form->isValid()) {
                // echo '<pre>'; print_r('$postData'); die;
				
                $model->exchangeArrayFromForm($this->form->getData());
                $model->requestedAmount = ($postData->requestedAmount == null) ? 0 : $postData->requestedAmount;
                $model->travelId = ((int) Helper::getMaxId($this->adapter, TravelRequestModel::TABLE_NAME, TravelRequestModel::TRAVEL_ID)) + 1;
                $model->employeeId = $postData['employeeId'];
                $model->requestedDate = Helper::getcurrentExpressionDate();
                $model->status = ($postData['applyStatus'] == 'AP') ? 'AP' : 'RQ';
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
                if($model->status == 'AP'){
                    $model->hardcopySignedFlag = 'Y';
                }

                $this->travelRequesteRepository->add($model);
				
                $this->flashmessenger()->addMessage("Travel Request Successfully added!!!");
                    try {
                        HeadNotification::pushNotification(NotificationEvents::TRAVEL_APPLIED, $model, $this->adapter, $this);
                    } catch (Exception $e) {
                        $this->flashmessenger()->addMessage($e->getMessage());
                    }
                // }
				
                return $this->redirect()->toRoute("travelStatus");
            }else{
                echo '<pre>'; print_r('not'); die;
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
            'OF'  => 'On Foot',
            'OT'=>'Others',
            'VV'=>'Own-Vehicle'
        );
        
        $applyOptionValues = [
            'RQ' => 'Pending',
            'AP' => 'Approved'
        ];
        $applyOption = $this->getSelectElement(['name' => 'applyStatus', 'id' => 'applyStatus', 'class' => 'form-control', 'label' => 'Type'], $applyOptionValues);

        return Helper::addFlashMessagesToArray($this, [
                    'form' => $this->form,
                    'requestTypes' => $requestType,
                    'transportTypes' => $transportTypes,
                    'applyOption' => $applyOption,
                    'employees' => EntityHelper::getTableKVListWithSortOption($this->adapter, "HRIS_EMPLOYEES", "EMPLOYEE_ID", ["EMPLOYEE_CODE", "FULL_NAME"], ["STATUS" => 'E', 'RETIRED_FLAG' => 'N'], "FULL_NAME", "ASC", "-", false, true, $this->employeeId)
        ]);
    }

}
