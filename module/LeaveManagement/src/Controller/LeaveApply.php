<?php

namespace LeaveManagement\Controller;

use Application\Controller\HrisController;
use Application\Custom\CustomViewModel;
use Application\Helper\EntityHelper;
use Application\Helper\Helper;
use Exception;
use LeaveManagement\Form\LeaveApplyForm;
use LeaveManagement\Model\LeaveApply as LeaveApplyModel;
use LeaveManagement\Repository\LeaveApplyRepository;
use Notification\Controller\HeadNotification;
use Notification\Model\NotificationEvents;
use SelfService\Model\LeaveSubstitute;
use SelfService\Repository\LeaveRequestRepository;
use SelfService\Repository\LeaveSubstituteRepository;
use Zend\Authentication\Storage\StorageInterface;
use Zend\Db\Adapter\AdapterInterface;
use Zend\View\Model\JsonModel;

class LeaveApply extends HrisController
{

    public function __construct(AdapterInterface $adapter, StorageInterface $storage)
    {
        parent::__construct($adapter, $storage);
        $this->initializeRepository(LeaveApplyRepository::class);
        $this->initializeForm(LeaveApplyForm::class);
    }

    public function fileUploadAction()
    {
        $request = $this->getRequest();
        $responseData = [];
        $files = $request->getFiles()->toArray();
        try {
            if (sizeof($files) > 0) {
                $ext = pathinfo($files['file']['name'], PATHINFO_EXTENSION);
                $fileName = pathinfo($files['file']['name'], PATHINFO_FILENAME);
                $unique = Helper::generateUniqueName();
                $newFileName = $unique . "." . $ext;
                $success = move_uploaded_file($files['file']['tmp_name'], Helper::UPLOAD_DIR . "/leave_documents/" . $newFileName);
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

    public function pushLeaveFileLinkAction()
    {
        try {
            $newsId = $this->params()->fromRoute('id');
            $request = $this->getRequest();
            $data = $request->getPost();
            $returnData = $this->repository->pushFileLink($data);
            return new JsonModel(['success' => true, 'data' => $returnData[0], 'message' => null]);
        } catch (Exception $e) {
            return new JsonModel(['success' => false, 'data' => null, 'message' => $e->getMessage()]);
        }
    }

    public function addAction()
    {
        $request = $this->getRequest();
        if ($request->isPost()) {
            $postedData = $request->getPost();
            $this->form->setData($postedData);
            $leaveSubstitute = $postedData->leaveSubstitute;
            if ($this->form->isValid()) {
                $leaveRequest = new LeaveApplyModel();
                $leaveRequest->exchangeArrayFromForm($this->form->getData());
                $leaveRequest->id = (int) Helper::getMaxId($this->adapter, LeaveApplyModel::TABLE_NAME, LeaveApplyModel::ID) + 1;
                $leaveRequest->startDate = Helper::getExpressionDate($leaveRequest->startDate);
                $leaveRequest->endDate = Helper::getExpressionDate($leaveRequest->endDate);
                $leaveRequest->requestedDt = Helper::getcurrentExpressionDate();
                $leaveRequest->status = "RQ";
                $leaveRequest->createdBy = $this->employeeId;

                if (isset($postedData['subRefId']) && $postedData['subRefId'] != ' ') {
                    $leaveRequest->subRefId = $postedData['subRefId'];
                }
                $leaveRequest->status = ($postedData['applyStatus'] == 'AP') ? 'AP' : 'RQ';

                if ($leaveRequest->status == 'AP') {
                    $leaveRequest->hardcopySignedFlag = 'Y';
                    $leaveRequest->recommendedBy = $this->employeeId;
                    $leaveRequest->recommendedDt = Helper::getcurrentExpressionDate();
                    $leaveRequest->approvedBy = $this->employeeId;
                    $leaveRequest->approvedDt = Helper::getcurrentExpressionDate();
                    $leaveRequest->approvedRemarks = 'Auto Approved by Hr';
                }

                $this->repository->add($leaveRequest);
                $this->flashmessenger()->addMessage("Leave Request Successfully added!!!");
                if ($leaveRequest->status == 'RQ') {

                    if ($leaveSubstitute !== null && $leaveSubstitute !== "") {
                        $leaveSubstituteModel = new LeaveSubstitute();
                        $leaveSubstituteRepo = new LeaveSubstituteRepository($this->adapter);
                        $leaveSubstituteModel->id = (int) Helper::getMaxId($this->adapter, LeaveSubstitute::TABLE_NAME, LeaveSubstitute::ID) + 1;
                        $leaveSubstituteModel->leaveRequestId = $leaveRequest->id;
                        $leaveSubstituteModel->employeeId = $leaveSubstitute;
                        $leaveSubstituteModel->createdBy = $this->employeeId;
                        $leaveSubstituteModel->createdDate = Helper::getcurrentExpressionDate();
                        $leaveSubstituteModel->status = 'E';

                        $leaveSubstituteRepo->add($leaveSubstituteModel);
                        try {
                            HeadNotification::pushNotification(NotificationEvents::LEAVE_SUBSTITUTE_APPLIED, $leaveRequest, $this->adapter, $this);
                        } catch (Exception $e) {
                            $this->flashmessenger()->addMessage($e->getMessage());
                        }
                    } else {
                        try {
                            HeadNotification::pushNotification(NotificationEvents::LEAVE_APPLIED, $leaveRequest, $this->adapter, $this);
                        } catch (Exception $e) {
                            $this->flashmessenger()->addMessage($e->getMessage());
                        }
                    }
                }

                return $this->redirect()->toRoute('leaveapply', array(
                    'controller' => 'LeaveApply',
                    'action' => 'add'
                ));
                //                return $this->redirect()->toRoute("leavestatus");
            }
        }

        if ($this->acl['HR_APPROVE'] == 'Y') {
            $applyOptionValues = [
                'RQ' => 'Pending',
                'AP' => 'Approved'
            ];
        } else {
            $applyOptionValues = [
                'RQ' => 'Pending',
            ];
        }

        $applyOption = $this->getSelectElement(['name' => 'applyStatus', 'id' => 'applyStatus', 'class' => 'form-control', 'label' => 'Type'], $applyOptionValues);

        $subLeaveReference = 'N';
        if (isset($this->preference['subLeaveReference'])) {
            $subLeaveReference = $this->preference['subLeaveReference'];
        }
        $subLeaveMaxDays = '500';
        if (isset($this->preference['subLeaveMaxDays'])) {
            $subLeaveMaxDays = $this->preference['subLeaveMaxDays'];
        }
        // $data = EntityHelper::getTableKVListWithSortOption($this->adapter, "HRIS_EMPLOYEES", "EMPLOYEE_ID", ["EMPLOYEE_CODE", "FULL_NAME"], ["STATUS" => 'E', 'RETIRED_FLAG' => 'N', 'IS_ADMIN' => "N"], "FULL_NAME", "ASC", "-", FALSE, TRUE, $this->employeeId);
        // echo '<pre>';
        // echo count($data);
        // print_r($data);
        // die;
        return Helper::addFlashMessagesToArray($this, [
            'form' => $this->form,
            'employees' => EntityHelper::getTableKVListWithSortOption($this->adapter, "HRIS_EMPLOYEES", "EMPLOYEE_ID", ["EMPLOYEE_CODE", "FULL_NAME"], ["STATUS" => 'E', 'RETIRED_FLAG' => 'N', 'IS_ADMIN' => "N"], "FULL_NAME", "ASC", "-", FALSE, TRUE, $this->employeeId),
            'customRenderer' => Helper::renderCustomView(),
            'applyOption' => $applyOption,
            'subLeaveReference' => $subLeaveReference,
            'subLeaveMaxDays' => $subLeaveMaxDays
        ]);
    }

    public function pullLeaveDetailWidEmployeeIdAction()
    {
        try {
            $request = $this->getRequest();
            if ($request->isPost()) {
                $postedData = $request->getPost();

                $leaveRequestRepository = new LeaveRequestRepository($this->adapter);
                $employeeId = $postedData['employeeId'];
                $leaveList = $leaveRequestRepository->getLeaveList($employeeId);

                $leaveRow = [];
                foreach ($leaveList as $key => $value) {
                    array_push($leaveRow, ["id" => $key, "name" => $value]);
                }
                return new CustomViewModel(['success' => true, 'data' => $leaveRow, 'error' => '']);
            } else {
                throw new Exception("The request should be of type post");
            }
        } catch (Exception $e) {
            return new CustomViewModel(['success' => false, 'data' => [], 'error' => $e->getMessage()]);
        }
    }

    public function pullLeaveDetailAction()
    {
        try {
            $request = $this->getRequest();
            if ($request->isPost()) {
                $postedData = $request->getPost();
                $leaveRequestRepository = new LeaveRequestRepository($this->adapter);
                $leaveId = $postedData['leaveId'];
                $employeeId = $postedData['employeeId'];
                $startDate = $postedData['startDate'];
                $leaveDetail = $leaveRequestRepository->getLeaveDetail($employeeId, $leaveId, $startDate);

                $maxSubDays = 500;
                if (isset($this->preference['subLeaveMaxDays'])) {
                    $maxSubDays = $this->preference['subLeaveMaxDays'];
                }

                $subtituteDetails = $leaveRequestRepository->getSubstituteList($leaveId, $employeeId, $maxSubDays);

                return new CustomViewModel(['success' => true, 'data' => $leaveDetail, 'subtituteDetails' => $subtituteDetails, 'error' => '']);
            } else {
                throw new Exception("The request should be of type post");
            }
        } catch (Exception $e) {
            return new CustomViewModel(['success' => false, 'data' => [], 'error' => $e->getMessage()]);
        }
    }

    public function fetchAvailableDaysAction()
    {
        try {
            $request = $this->getRequest();
            if ($request->isPost()) {
                $postedData = $request->getPost();
                $leaveRequestRepository = new LeaveRequestRepository($this->adapter);
                $availableDays = $leaveRequestRepository->fetchAvailableDays($postedData['startDate'], $postedData['endDate'], $postedData['employeeId'], $postedData['halfDay'], $postedData['leaveId']);
                return new CustomViewModel(['success' => true, 'data' => $availableDays, 'error' => '']);
            } else {
                throw new Exception("The request should be of type post");
            }
        } catch (Exception $e) {
            return new CustomViewModel(['success' => false, 'data' => [], 'error' => $e->getMessage()]);
        }
    }

    public function validateLeaveRequestAction()
    {
        try {
            $request = $this->getRequest();
            if ($request->isPost()) {
                $postedData = $request->getPost();
                $leaveRequestRepository = new LeaveRequestRepository($this->adapter);
                $error = $leaveRequestRepository->validateLeaveRequest($postedData['startDate'], $postedData['endDate'], $postedData['employeeId']);
                return new CustomViewModel(['success' => true, 'data' => $error, 'error' => '']);
            } else {
                throw new Exception("The request should be of type post");
            }
        } catch (Exception $e) {
            return new CustomViewModel(['success' => false, 'data' => [], 'error' => $e->getMessage()]);
        }
    }
}
