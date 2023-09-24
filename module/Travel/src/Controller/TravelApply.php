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

class TravelApply extends HrisController
{

    //    private $form;
    //    private $adapter;
    //    private $travelRequesteRepository;
    //    private $employeeId;
    //    private $preference;

    public function __construct(AdapterInterface $adapter)
    {
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

    public function indexAction()
    {
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

    public function addAction()
    {
        $this->initializeForm(TravelRequestForm::class);
        $request = $this->getRequest();

        $model = new TravelRequestModel();
        if ($request->isPost()) {
            $postData = $request->getPost();
            $travelSubstitute = $postData->travelSubstitute;
            $this->form->setData($postData);
            if ($this->form->isValid()) {
                $model->exchangeArrayFromForm($this->form->getData());
                $model->travelId = ((int) Helper::getMaxId($this->adapter, TravelRequestModel::TABLE_NAME, TravelRequestModel::TRAVEL_ID)) + 1;
                $model->requestedDate = Helper::getcurrentExpressionDate();
                //                $model->status = 'RQ';
                $model->deductOnSalary = 'Y';
                $model->status = ($postData['applyStatus'] == 'AP') ? 'AP' : 'RQ';

                if ($model->status == 'AP') {
                    $model->hardcopySignedFlag = 'Y';
                }

                $this->travelRequesteRepository->add($model);
                $this->flashmessenger()->addMessage("Travel Request Successfully added!!!");


                if ($travelSubstitute !== null) {
                    $travelSubstituteModel = new TravelSubstitute();
                    $travelSubstituteRepo = new TravelSubstituteRepository($this->adapter);

                    $travelSubstitute = $postData->travelSubstitute;

                    $travelSubstituteModel->travelId = $model->travelId;
                    $travelSubstituteModel->employeeId = $travelSubstitute;
                    $travelSubstituteModel->createdBy = $this->employeeId;
                    $travelSubstituteModel->createdDate = Helper::getcurrentExpressionDate();
                    $travelSubstituteModel->status = 'E';

                    if (isset($this->preference['travelSubCycle']) && $this->preference['travelSubCycle'] == 'N') {
                        $travelSubstituteModel->approvedFlag = 'Y';
                        $travelSubstituteModel->approvedDate = Helper::getcurrentExpressionDate();
                    }

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
                return $this->redirect()->toRoute("travelStatus");
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
