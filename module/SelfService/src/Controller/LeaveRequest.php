<?php

namespace SelfService\Controller;

use Application\Controller\HrisController;
use Application\Custom\CustomViewModel;
use Application\Helper\EntityHelper;
use Application\Helper\Helper;
use Application\Model\Preference;
use Exception;
use LeaveManagement\Form\LeaveApplyForm;
use LeaveManagement\Model\LeaveApply;
use LeaveManagement\Model\LeaveMaster;
use LeaveManagement\Repository\LeaveStatusRepository;
use ManagerService\Repository\LeaveApproveRepository;
use Notification\Controller\HeadNotification;
use Notification\Model\NotificationEvents;
use SelfService\Model\LeaveSubstitute;
use SelfService\Repository\LeaveRequestRepository;
use SelfService\Repository\LeaveSubstituteRepository;
use Setup\Model\HrEmployees;
use Zend\Authentication\Storage\StorageInterface;
use Zend\Db\Adapter\AdapterInterface;
use Zend\View\Model\JsonModel;

class LeaveRequest extends HrisController
{

    public function __construct(AdapterInterface $adapter, StorageInterface $storage)
    {
        parent::__construct($adapter, $storage);
        $this->initializeRepository(LeaveRequestRepository::class);
        $this->initializeForm(LeaveApplyForm::class);
    }

    public function indexAction()
    {
        $request = $this->getRequest();
        $leaveYearList = EntityHelper::getTableKVList($this->adapter, "HRIS_LEAVE_YEARS", "LEAVE_YEAR_ID", ["LEAVE_YEAR_NAME"], null, null, false, 'LEAVE_YEAR_ID', 'desc');
        $leaveYearSE = $this->getSelectElement(['name' => 'leaveYear', 'id' => 'leaveYear', 'class' => 'form-control ', 'label' => 'Type'], $leaveYearList);
        $leaveStatusReposotory = new LeaveStatusRepository($this->adapter);
        $allLeaveForReport = $leaveStatusReposotory->getMonthlyLeaveforReport();
        if ($request->isPost()) {
            try {
                $data = $request->getPost();
                $rawList = $this->repository->getfilterRecords($data);
                $list = Helper::extractDbData($rawList);

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
        $leaveList = EntityHelper::getTableKVListWithSortOption($this->adapter, LeaveMaster::TABLE_NAME, LeaveMaster::LEAVE_ID, [LeaveMaster::LEAVE_ENAME], [LeaveMaster::STATUS => 'E'], LeaveMaster::LEAVE_ENAME, "ASC", null, true);
        $leaveSE = $this->getSelectElement(['name' => 'leave', 'id' => 'leaveId', 'class' => 'form-control', 'label' => 'Leave Type'], $leaveList);
        $leaveStatusFE = $this->getStatusSelectElement(['name' => 'leaveStatus', 'id' => 'leaveRequestStatusId', 'class' => 'form-control', 'label' => 'Leave Request Status']);

        return Helper::addFlashMessagesToArray($this, [
            'leaves' => $leaveSE,
            'leaveStatus' => $leaveStatusFE,
            'employeeId' => $this->employeeId,
            'leaveYearSelect'  => $leaveYearSE,
            'allLeaveForReport'  => $allLeaveForReport,
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
                $fileName = pathinfo($files['file']['name'], PATHINFO_FILENAME);
                $unique = Helper::generateUniqueName();
                $newFileName = $unique . "." . $ext;
                // $success = move_uploaded_file($files['file']['tmp_name'], Helper::UPLOAD_DIR . "/leave_documents/" . $newFileName);
                $success = move_uploaded_file($files['file']['tmp_name'], Helper::UPLOAD_DIR . "/leave_documents/" . $newFileName);
                // echo '<pre>';print_r($success);die; 
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
            $postData = $request->getPost();
            // print_r($postData); die;
            $this->form->setData($postData);
            $leaveSubstitute = $postData->leaveSubstitute;
            if ($this->form->isValid()) {
                $leaveRequest = new LeaveApply();
                $leaveRequest->exchangeArrayFromForm($this->form->getData());

                $leaveRequest->id = (int) Helper::getMaxId($this->adapter, LeaveApply::TABLE_NAME, LeaveApply::ID) + 1;
                $leaveRequest->employeeId = $this->employeeId;
                $leaveRequest->startDate = Helper::getExpressionDate($leaveRequest->startDate);
                $leaveRequest->endDate = Helper::getExpressionDate($leaveRequest->endDate);
                $leaveRequest->requestedDt = Helper::getcurrentExpressionDate();
                $leaveRequest->status = "RQ";
                if (isset($postData['subRefId'])  && $postData['subRefId'] != ' ') {
                    $leaveRequest->subRefId = $postData['subRefId'];
                }
                $this->repository->add($leaveRequest);
                $this->flashmessenger()->addMessage("Leave Request Successfully added!!!");

                if ($leaveSubstitute !== null && $leaveSubstitute !== "") {
                    $leaveSubstituteModel = new LeaveSubstitute();
                    $leaveSubstituteRepo = new LeaveSubstituteRepository($this->adapter);


                    $leaveSubstituteModel->leaveRequestId = $leaveRequest->id;
                    $leaveSubstituteModel->employeeId = $leaveSubstitute;
                    $leaveSubstituteModel->createdBy = $this->employeeId;
                    $leaveSubstituteModel->createdDate = Helper::getcurrentExpressionDate();
                    $leaveSubstituteModel->status = 'E';

                    // echo '<pre>';print_r($this);die;
                    $leaveSubstituteRepo->add($leaveSubstituteModel);
                    try {
                        HeadNotification::pushNotification(NotificationEvents::LEAVE_SUBSTITUTE_APPLIED, $leaveRequest, $this->adapter, $this);
                    } catch (Exception $e) {
                        $this->flashmessenger()->addMessage($e->getMessage());
                    }
                } else {
                    try {
                        // echo '<pre>';print_r($this);die;
                        HeadNotification::pushNotification(NotificationEvents::LEAVE_APPLIED, $leaveRequest, $this->adapter, $this);
                    } catch (Exception $e) {
                        $this->flashmessenger()->addMessage($e->getMessage());
                    }
                }
                return $this->redirect()->toRoute("leaverequest");
            }
        }

        $subLeaveReference = 'N';
        if (isset($this->preference['subLeaveReference'])) {
            $subLeaveReference = $this->preference['subLeaveReference'];
        }

        $subLeaveMaxDays = '500';
        if (isset($this->preference['subLeaveMaxDays'])) {
            $subLeaveMaxDays = $this->preference['subLeaveMaxDays'];
        }
        $selfBranch = "branch_id=(select branch_id from hris_employees where employee_id=" . $this->employeeId . ")";

        $allowPrevMnthLeave = EntityHelper::getPreferenceValue($this->adapter, Preference::ENABLE_PREV_MTH_LEAVE_REQ);

        return Helper::addFlashMessagesToArray($this, [
            'form' => $this->form,
            'employeeId' => $this->employeeId,
            'leave' => $this->repository->getLeaveList($this->employeeId, 'Y'),
            'customRenderer' => Helper::renderCustomView(),
            'employeeList' => EntityHelper::getTableKVListWithSortOption($this->adapter, HrEmployees::TABLE_NAME, HrEmployees::EMPLOYEE_ID, [HrEmployees::FIRST_NAME, HrEmployees::MIDDLE_NAME, HrEmployees::LAST_NAME], [HrEmployees::STATUS => "E", HrEmployees::RETIRED_FLAG => "N"], HrEmployees::FIRST_NAME, "ASC", " ", false, true),
            'subLeaveReference' => $subLeaveReference,
            'subLeaveMaxDays' => $subLeaveMaxDays,
            'allowPrevMnthLeave' => $allowPrevMnthLeave
        ]);
    }

    public function deleteAction()
    {
        $id = (int) $this->params()->fromRoute("id");
        if (!$id) {
            return $this->redirect()->toRoute('leaverequest', ['action' => 'cancel']);
        }
        $this->repository->delete($id);
        $this->flashmessenger()->addMessage("Leave Request Successfully Cancelled!!!");
        $leaveRequest = new LeaveApply();
        $leaveRequestDetail = $this->repository->fetchById($id);
        $leaveRequest->exchangeArrayFromDB($leaveRequestDetail);

        if ($leaveRequest->status == 'CP') {
            try {
                HeadNotification::pushNotification(NotificationEvents::LEAVE_CANCELLED, $leaveRequest, $this->adapter, $this);
            } catch (Exception $e) {
                $this->flashmessenger()->addMessage($e->getMessage());
            }
        }
        return $this->redirect()->toRoute('leaverequest', ['action' => 'cancel']);
    }

    public function viewAction()
    {
        $id = (int) $this->params()->fromRoute('id', 0);
        if ($id === 0) {
            return $this->redirect()->toRoute("leaveapprove");
        }
        $leaveApproveRepository = new LeaveApproveRepository($this->adapter);

        $detail = $leaveApproveRepository->fetchById($id);

        if ($this->preference['displayHrApproved'] == 'Y' && $detail['HR_APPROVED'] == 'Y') {
            $detail['APPROVER_ID'] = '-1';
            $detail['APPROVER_NAME'] = 'HR';
            $detail['RECOMMENDER_ID'] = '-1';
            $detail['RECOMMENDER_NAME'] = 'HR';
            $detail['RECOMMENDED_BY_NAME'] = 'HR';
            $detail['APPROVED_BY_NAME'] = 'HR';
        }

        $fileDetails = $leaveApproveRepository->fetchAttachmentsById($id);
        $authRecommender = $detail['RECOMMENDED_BY_NAME'] == null ? $detail['RECOMMENDER_NAME'] : $detail['RECOMMENDED_BY_NAME'];
        $authApprover = $detail['APPROVED_BY_NAME'] == null ? $detail['APPROVER_NAME'] : $detail['APPROVED_BY_NAME'];



        //to get the previous balance of selected leave from assigned leave detail
        $result = $leaveApproveRepository->assignedLeaveDetail($detail['LEAVE_ID'], $detail['EMPLOYEE_ID']);
        $preBalance = $result['BALANCE'];

        $actualDays = ($detail['ACTUAL_DAYS'] < 1) ? '0' + $detail['ACTUAL_DAYS'] : $detail['ACTUAL_DAYS'];
        $halfDayDetail = $detail['HALF_DAY_DETAIL'];

        $leaveApply = new LeaveApply();
        $leaveApply->exchangeArrayFromDB($detail);
        $this->form->bind($leaveApply);

        return Helper::addFlashMessagesToArray($this, [
            'form' => $this->form,
            'id' => $id,
            'employeeName' => $detail['FULL_NAME'],
            'requestedDt' => $detail['REQUESTED_DT'],
            'availableDays' => $preBalance,
            'status' => $detail['STATUS'],
            'recommender' => $authRecommender,
            'approver' => $authApprover,
            'remarksDtl' => $detail['REMARKS'],
            'totalDays' => $result['TOTAL_DAYS'],
            'recommendedBy' => $detail['RECOMMENDED_BY'],
            'employeeId' => $this->employeeId,
            'allowHalfDay' => $detail['ALLOW_HALFDAY'],
            'leave' => $this->repository->getLeaveList($detail['EMPLOYEE_ID']),
            'customRenderer' => Helper::renderCustomView(),
            'subEmployeeId' => $detail['SUB_EMPLOYEE_ID'],
            'subRemarks' => $detail['SUB_REMARKS'],
            'subApprovedFlag' => $detail['SUB_APPROVED_FLAG'],
            'employeeList' => EntityHelper::getTableKVListWithSortOption($this->adapter, HrEmployees::TABLE_NAME, HrEmployees::EMPLOYEE_ID, [HrEmployees::FIRST_NAME, HrEmployees::MIDDLE_NAME, HrEmployees::LAST_NAME], [HrEmployees::STATUS => "E", HrEmployees::RETIRED_FLAG => "N"], HrEmployees::FIRST_NAME, "ASC", " ", false, true),
            'gp' => $detail['GRACE_PERIOD'],
            'files' => $fileDetails,
            'actualDays' => $actualDays,
            'halfdayDetail' => $halfDayDetail
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
                $endDate = $postedData['endDate'];
                $leaveDetail = $leaveRequestRepository->getLeaveDetail($employeeId, $leaveId, $startDate);
                $leaveDetail['BALANCE'] = $leaveRequestRepository->checkSubstitueBalance($employeeId, $leaveId, $endDate, $leaveDetail['BALANCE']);

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
                $travelError = $this->repository->validateLeaveTravelRequest(Helper::getExpressionDate($postedData['startDate'])->getExpression(), Helper::getExpressionDate($postedData['endDate'])->getExpression(), $postedData['employeeId']);
                // echo '<pre>';print_r($travelError);die;
                return new CustomViewModel([
                    'success' => true, 'data' => $error, 'error' => '',
                    'travelError' => $travelError
                ]);
                // return new CustomViewModel(['success' => true, 'data' => $error, 'error' => '']);
            } else {
                throw new Exception("The request should be of type post");
            }
        } catch (Exception $e) {
            return new CustomViewModel(['success' => false, 'data' => [], 'error' => $e->getMessage()]);
        }
    }

    public function cancelAction()
    {
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
        $leaveList = EntityHelper::getTableKVListWithSortOption($this->adapter, LeaveMaster::TABLE_NAME, LeaveMaster::LEAVE_ID, [LeaveMaster::LEAVE_ENAME], [LeaveMaster::STATUS => 'E'], LeaveMaster::LEAVE_ENAME, "ASC", null, true);
        $leaveSE = $this->getSelectElement(['name' => 'leave', 'id' => 'leaveId', 'class' => 'form-control', 'label' => 'Leave Type'], $leaveList);
        $leaveStatusFE = $this->getStatusSelectElement(['name' => 'leaveStatus', 'id' => 'leaveRequestStatusId', 'class' => 'form-control', 'label' => 'Leave Request Status']);

        return Helper::addFlashMessagesToArray($this, [
            'leaves' => $leaveSE,
            'leaveStatus' => $leaveStatusFE,
            'employeeId' => $this->employeeId,
        ]);
    }
}
