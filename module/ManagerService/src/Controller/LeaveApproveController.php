<?php

namespace ManagerService\Controller;

use Application\Controller\HrisController;
use Application\Custom\CustomViewModel;
use Application\Helper\EntityHelper;
use Application\Helper\Helper;
use Exception;
use LeaveManagement\Form\LeaveApplyForm;
use LeaveManagement\Model\LeaveApply;
use LeaveManagement\Model\LeaveMaster;
use LeaveManagement\Repository\LeaveMasterRepository;
use LeaveManagement\Repository\LeaveStatusRepository;
use ManagerService\Repository\LeaveApproveRepository;
use Notification\Controller\HeadNotification;
use Notification\Model\NotificationEvents;
use SelfService\Repository\LeaveRequestRepository;
use Setup\Model\HrEmployees;
use Zend\Authentication\Storage\StorageInterface;
use Zend\Db\Adapter\AdapterInterface;
use Zend\Form\Element\Select;
use ManagerService\Repository\ManagerReportRepo;
use SelfService\Model\LeaveSubstitute;
use SelfService\Repository\LeaveSubstituteRepository;
use Zend\View\Model\JsonModel;
use LeaveManagement\Repository\LeaveApplyRepository;
use LeaveManagement\Repository\LeaveReportCardRepository;


class LeaveApproveController extends HrisController
{

    public function __construct(AdapterInterface $adapter, StorageInterface $storage)
    {
        parent::__construct($adapter, $storage);
        $this->initializeRepository(LeaveApproveRepository::class);
        $this->initializeForm(LeaveApplyForm::class);
    }

    public function indexAction()
    {
        $request = $this->getRequest();
        if ($request->isPost()) {
            try {
                $rawList = $this->repository->getAllRequest($this->employeeId);

                $list = Helper::extractDbData($rawList);
                return new JsonModel(['success' => true, 'data' => $list, 'error' => '']);
            } catch (Exception $e) {
                return new JsonModel(['success' => false, 'data' => [], 'error' => $e->getMessage()]);
            }
        }

        return $this->stickFlashMessagesTo([]);
    }

    public function viewAction()
    {
        $leaveRequestRepository = new LeaveRequestRepository($this->adapter);

        $id = (int) $this->params()->fromRoute('id');
        $role = $this->params()->fromRoute('role');

        if ($id === 0) {

            return $this->redirect()->toRoute("leaveapprove");
        }
        $leaveApply = new LeaveApply();
        $request = $this->getRequest();

        $detail = $this->repository->fetchByIdWithEmployeeId($id, $this->employeeId);
        $leaveId = $detail['LEAVE_ID'];
        $leaveRepository = new LeaveMasterRepository($this->adapter);
        $leaveDtl = $leaveRepository->fetchById($leaveId);

        if ($this->employeeId != $detail['RECOMMENDER_ID'] && $this->employeeId != $detail['APPROVER_ID']) {
            return $this->redirect()->toRoute("leaveapprove");
        }
        $requestedEmployeeID = $detail['EMPLOYEE_ID'];
        $employeeName = $detail['FULL_NAME'];
        $authRecommender = $detail['RECOMMENDED_BY_NAME'] == null ? $detail['RECOMMENDER_NAME'] : $detail['RECOMMENDED_BY_NAME'];
        $authApprover = $detail['APPROVED_BY_NAME'] == null ? $detail['APPROVER_NAME'] : $detail['APPROVED_BY_NAME'];
        $recommenderId = $detail['RECOMMENDED_BY'] == null ? $detail['RECOMMENDER_ID'] : $detail['RECOMMENDED_BY'];
        //to get the previous balance of selected leave from assigned leave detail
        $preBalance = $detail['BALANCE'];

        if (!$request->isPost()) {
            $leaveApply->exchangeArrayFromDB($detail);
            $this->form->bind($leaveApply);
            if ($detail['STATUS'] == 'CP' || $detail['STATUS'] == 'CR') {
                $recommenderId = $detail['RECOMMENDER_ID'];
            }
        } else {


            $getData = $request->getPost();
            $action = $getData->submit;

            $checkSameDateApproved = $this->repository->getSameDateApprovedStatus($detail['EMPLOYEE_ID'], $detail['START_DATE'], $detail['END_DATE']);
            if ($checkSameDateApproved['LEAVE_COUNT'] > 0 && $action == "Approve") {
                return $this->redirect()->toRoute("leaveapprove");
            }

            if ($detail['STATUS'] == 'RQ' || $detail['STATUS'] == 'RC') {
                if ($role == 2) {
                    $leaveApply->recommendedDt = Helper::getcurrentExpressionDate();
                    if ($action == "Reject") {
                        $leaveApply->status = "R";
                        $this->flashmessenger()->addMessage("Leave Request Rejected!!!");
                    } else if ($action == "Approve") {
                        $leaveApply->status = "RC";
                        $this->flashmessenger()->addMessage("Leave Request Approved!!!");
                    }
                    $leaveApply->recommendedBy = $this->employeeId;
                    $leaveApply->recommendedRemarks = $getData->recommendedRemarks;
                    $this->repository->edit($leaveApply, $id);

                    $leaveApply->id = $id;
                    $leaveApply->employeeId = $requestedEmployeeID;
                    $leaveApply->approvedBy = $detail['APPROVER_ID'];
                    try {
                        if ($leaveApply->status == 'RC') {
                            HeadNotification::pushNotification(NotificationEvents::LEAVE_RECOMMEND_ACCEPTED, $leaveApply, $this->adapter, $this);
                        } else {
                            HeadNotification::pushNotification(NotificationEvents::LEAVE_RECOMMEND_REJECTED, $leaveApply, $this->adapter, $this);
                        }
                    } catch (Exception $e) {
                        $this->flashmessenger()->addMessage($e->getMessage());
                    }
                } else if ($role == 3 || $role == 4) {
                    $leaveApply->approvedDt = Helper::getcurrentExpressionDate();
                    if ($action == "Reject") {
                        $leaveApply->status = "R";
                        $this->flashmessenger()->addMessage("Leave Request Rejected!!!");
                    } else if ($action == "Approve") {
                        $leaveApply->status = "AP";
                        $this->flashmessenger()->addMessage("Leave Request Approved");
                    }
                    unset($leaveApply->halfDay);
                    $leaveApply->approvedBy = $this->employeeId;
                    $leaveApply->approvedRemarks = $getData->approvedRemarks;

                    if ($role == 4) {
                        $leaveApply->recommendedBy = $this->employeeId;
                        $leaveApply->recommendedDt = Helper::getcurrentExpressionDate();
                    }
                    $this->repository->edit($leaveApply, $id);

                    $leaveApply->id = $id;
                    $leaveApply->employeeId = $requestedEmployeeID;



                    try {
                        HeadNotification::pushNotification(($leaveApply->status == 'AP') ? NotificationEvents::LEAVE_APPROVE_ACCEPTED : NotificationEvents::LEAVE_APPROVE_REJECTED, $leaveApply, $this->adapter, $this);
                    } catch (Exception $e) {
                        $this->flashmessenger()->addMessage($e->getMessage());
                    }
                }
            }
            return $this->redirect()->toRoute("leaveapprove");
        }
        $fileDetails = $this->repository->fetchAttachmentsById($id);


        return Helper::addFlashMessagesToArray($this, [
            'form' => $this->form,
            'id' => $id,
            'employeeName' => $employeeName,
            'requestedDt' => $detail['REQUESTED_DT'],
            'role' => $role,
            'availableDays' => $preBalance,
            'status' => $detail['STATUS'],
            'remarksDtl' => $detail['REMARKS'],
            'totalDays' => $detail['TOTAL_DAYS'],
            'recommendedBy' => $recommenderId,
            'recommender' => $authRecommender,
            'approver' => $authApprover,
            'approvedDT' => $detail['APPROVED_DT'],
            'employeeId' => $this->employeeId,
            'requestedEmployeeId' => $requestedEmployeeID,
            'allowHalfDay' => $leaveDtl['ALLOW_HALFDAY'],
            'leave' => $leaveRequestRepository->getLeaveList($detail['EMPLOYEE_ID']),
            'customRenderer' => Helper::renderCustomView(),
            'subEmployeeId' => $detail['SUB_EMPLOYEE_ID'],
            'subRemarks' => $detail['SUB_REMARKS'],
            'subApprovedFlag' => $detail['SUB_APPROVED_FLAG'],
            'employeeList' => EntityHelper::getTableKVListWithSortOption($this->adapter, HrEmployees::TABLE_NAME, HrEmployees::EMPLOYEE_ID, [HrEmployees::FIRST_NAME, HrEmployees::MIDDLE_NAME, HrEmployees::LAST_NAME], [HrEmployees::STATUS => "E", HrEmployees::RETIRED_FLAG => "N"], HrEmployees::FIRST_NAME, "ASC", " ", FALSE, TRUE), 'gp' => $detail['GRACE_PERIOD'],
            'files' => $fileDetails,
            'subLeaveName' => $detail['LEAVE_ENAME']
        ]);
    }

    public function statusAction()
    {
        $leaveFormElement = new Select();
        $leaveFormElement->setName("leave");
        $leaves = EntityHelper::getTableKVListWithSortOption($this->adapter, LeaveMaster::TABLE_NAME, LeaveMaster::LEAVE_ID, [LeaveMaster::LEAVE_ENAME], [LeaveMaster::STATUS => 'E'], LeaveMaster::LEAVE_ENAME, "ASC", NULL, FALSE, TRUE);
        $leaves1 = [-1 => "All"] + $leaves;
        $leaveFormElement->setValueOptions($leaves1);
        $leaveFormElement->setAttributes(["id" => "leaveId", "class" => "form-control reset-field"]);
        $leaveFormElement->setLabel("Type");

        $leaveStatus = [
            '-1' => 'All Status',
            'RQ' => 'Pending',
            'RC' => 'Recommended',
            'AP' => 'Approved',
            'R' => 'Rejected',
            'C' => 'Cancelled',
            'CP' => 'Cancel Pending',
            'CR' => 'Cancel Recommended'
        ];
        $leaveStatusFormElement = new Select();
        $leaveStatusFormElement->setName("leaveStatus");
        $leaveStatusFormElement->setValueOptions($leaveStatus);
        $leaveStatusFormElement->setAttributes(["id" => "leaveRequestStatusId", "class" => "form-control reset-field"]);
        $leaveStatusFormElement->setLabel("Status");
        $managerRepo = new ManagerReportRepo($this->adapter);
        $employee = $managerRepo->fetchAllEmployee($this->employeeId);
        $employees = [];
        foreach ($employee as $key => $value) {
            array_push($employees, ["id" => $key, "name" => $value]);
        }
        return Helper::addFlashMessagesToArray($this, [
            'leaves' => $leaveFormElement,
            'employees' => $employees,
            'leaveStatus' => $leaveStatusFormElement,
            'recomApproveId' => $this->employeeId,
            'searchValues' => EntityHelper::getSearchData($this->adapter),
        ]);
    }

    public function batchApproveRejectAction()
    {
        $request = $this->getRequest();
        try {
            if (!$request->ispost()) {
                throw new Exception('the request is not post');
            }
            $postData = $request->getPost()['data'];
            $postBtnAction = $request->getPost()['btnAction'];
            if ($postBtnAction == 'btnApprove') {
                $action = 'Approve';
            } elseif ($postBtnAction == 'btnReject') {
                $action = 'Reject';
            } else {
                throw new Exception('no action defined');
            }
            if ($postData == null) {
                throw new Exception('no selected rows');
            } else {

                foreach ($postData as $data) {
                    $leaveApply = new LeaveApply();
                    $id = $data['id'];
                    $role = $data['role'];

                    $detail = $this->repository->fetchById($id);
                    $requestedEmployeeID = $detail['EMPLOYEE_ID'];

                    $checkSameDateApproved = $this->repository->getSameDateApprovedStatus($detail['EMPLOYEE_ID'], $detail['START_DATE'], $detail['END_DATE']);
                    if ($checkSameDateApproved['LEAVE_COUNT'] > 0 && $action == "Approve") {
                        continue;
                    }

                    if ($detail['STATUS'] == 'RQ' || $detail['STATUS'] == 'RC') {
                        if ($role == 2) {
                            $leaveApply->recommendedDt = Helper::getcurrentExpressionDate();
                            if ($action == "Reject") {
                                $leaveApply->status = "R";
                            } else if ($action == "Approve") {
                                $leaveApply->status = "RC";
                            }
                            $leaveApply->recommendedBy = $this->employeeId;
                            $this->repository->edit($leaveApply, $id);


                            $leaveApply->id = $id;
                            $leaveApply->employeeId = $requestedEmployeeID;
                            $leaveApply->approvedBy = $detail['APPROVER'];

                            try {
                                if ($leaveApply->status == 'RC') {
                                    HeadNotification::pushNotification(NotificationEvents::LEAVE_RECOMMEND_ACCEPTED, $leaveApply, $this->adapter, $this);
                                } else {
                                    HeadNotification::pushNotification(NotificationEvents::LEAVE_RECOMMEND_REJECTED, $leaveApply, $this->adapter, $this);
                                }
                            } catch (Exception $e) {
                            }
                        } else if ($role == 3 || $role == 4) {
                            $leaveApply->approvedDt = Helper::getcurrentExpressionDate();
                            if ($action == "Reject") {
                                $leaveApply->status = "R";
                            } else if ($action == "Approve") {
                                $leaveApply->status = "AP";
                            }
                            unset($leaveApply->halfDay);
                            $leaveApply->approvedBy = $this->employeeId;

                            if ($role == 4) {
                                $leaveApply->recommendedBy = $this->employeeId;
                                $leaveApply->recommendedDt = Helper::getcurrentExpressionDate();
                            }
                            $this->repository->edit($leaveApply, $id);
                            $leaveApply->id = $id;
                            $leaveApply->employeeId = $requestedEmployeeID;
                            try {
                                HeadNotification::pushNotification(($leaveApply->status == 'AP') ? NotificationEvents::LEAVE_APPROVE_ACCEPTED : NotificationEvents::LEAVE_APPROVE_REJECTED, $leaveApply, $this->adapter, $this);
                            } catch (Exception $e) {
                            }
                        }
                    }
                }
            }
            $listData = $this->getAllList();
            return new CustomViewModel(['success' => true, 'data' => $listData]);
        } catch (Exception $e) {
            return new CustomViewModel(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    public function pullLeaveRequestStatusListAction()
    {
        try {
            $request = $this->getRequest();
            $data = $request->getPost();

            $leaveStatusRepository = new LeaveStatusRepository($this->adapter);
            $result = $leaveStatusRepository->getFilteredRecord($data, $data['recomApproveId']);

            $recordList = Helper::extractDbData($result);

            return new JsonModel([
                "success" => "true",
                "data" => $recordList,
            ]);
        } catch (Exception $e) {
            return new JsonModel(['success' => false, 'data' => null, 'message' => $e->getMessage()]);
        }
    }


    public function cancelListAction()
    {
        $request = $this->getRequest();
        if ($request->isPost()) {
            try {
                $rawList = $this->repository->getAllCancelRequest($this->employeeId);
                $list = Helper::extractDbData($rawList);
                return new JsonModel(['success' => true, 'data' => $list, 'error' => '']);
            } catch (Exception $e) {
                return new JsonModel(['success' => false, 'data' => [], 'error' => $e->getMessage()]);
            }
        }

        return $this->stickFlashMessagesTo([]);
    }

    public function cancelViewAction()
    {
        $leaveRequestRepository = new LeaveRequestRepository($this->adapter);

        $id = (int) $this->params()->fromRoute('id');
        $role = $this->params()->fromRoute('role');

        if ($id === 0) {
            return $this->redirect()->toRoute("leaveapprove");
        }
        $leaveApply = new LeaveApply();
        $request = $this->getRequest();

        $detail = $this->repository->fetchById($id);
        $leaveId = $detail['LEAVE_ID'];
        $leaveRepository = new LeaveMasterRepository($this->adapter);
        $leaveDtl = $leaveRepository->fetchById($leaveId);

        if ($this->employeeId != $detail['RECOMMENDER_ID'] && $this->employeeId != $detail['APPROVER_ID']) {
            return $this->redirect()->toRoute("leaveapprove");
        }
        $requestedEmployeeID = $detail['EMPLOYEE_ID'];
        $employeeName = $detail['FULL_NAME'];
        $authRecommender = $detail['RECOMMENDED_BY_NAME'] == null ? $detail['RECOMMENDER_NAME'] : $detail['RECOMMENDED_BY_NAME'];
        $authApprover = $detail['APPROVED_BY_NAME'] == null ? $detail['APPROVER_NAME'] : $detail['APPROVED_BY_NAME'];
        $recommenderId = $detail['RECOMMENDED_BY'] == null ? $detail['RECOMMENDER_ID'] : $detail['RECOMMENDED_BY'];
        //to get the previous balance of selected leave from assigned leave detail
        $preBalance = $detail['BALANCE'];

        if (!$request->isPost()) {
            $leaveApply->exchangeArrayFromDB($detail);
            $this->form->bind($leaveApply);
            if ($detail['STATUS'] == 'CP' || $detail['STATUS'] == 'CR') {
                $recommenderId = $detail['RECOMMENDER_ID'];
            }
        } else {
            $getData = $request->getPost();
            $action = $getData->submit;



            //for cancelling leave

            if ($detail['STATUS'] == 'CP' || $detail['STATUS'] == 'CR') {

                if ($role == 2) {
                    $leaveApply->cancelRecDt = Helper::getcurrentExpressionDate();
                    if ($action == "Reject") {
                        $leaveApply->status = "AP";
                        $this->flashmessenger()->addMessage("Leave Cancel Request Rejected!!!");
                    } else if ($action == "Approve") {
                        $leaveApply->status = "CR";
                        $this->flashmessenger()->addMessage("Leave Cancel Request Approved!!!");
                    }
                    $leaveApply->cancelRecBy = $this->employeeId;
                    $leaveApply->recommendedRemarks = $getData->recommendedRemarks;
                    $this->repository->edit($leaveApply, $id);

                    $leaveApply->id = $id;
                    $leaveApply->employeeId = $requestedEmployeeID;
                    $leaveApply->approvedBy = $detail['APPROVER_ID'];
                    try {
                        if ($leaveApply->status == 'CR') {
                            HeadNotification::pushNotification(NotificationEvents::LEAVE_CANCELLED_RECOMMEND_ACCEPTED, $leaveApply, $this->adapter, $this);
                        } else {
                            HeadNotification::pushNotification(NotificationEvents::LEAVE_CANCELLED_RECOMMEND_REJECTED, $leaveApply, $this->adapter, $this);
                        }
                    } catch (Exception $e) {
                        $this->flashmessenger()->addMessage($e->getMessage());
                    }
                } else if ($role == 3 || $role == 4) {
                    $leaveApply->cancelAppDt = Helper::getcurrentExpressionDate();
                    if ($action == "Reject") {
                        $leaveApply->status = "AP";
                        $this->flashmessenger()->addMessage("Leave Cancel Request Rejected!!!");
                    } else if ($action == "Approve") {
                        $leaveApply->status = "C";
                        $this->flashmessenger()->addMessage("Leave Cancel Request Approved");
                    }
                    unset($leaveApply->halfDay);
                    $leaveApply->cancelAppBy = $this->employeeId;
                    $leaveApply->approvedRemarks = $getData->approvedRemarks;

                    if ($role == 4) {
                        $leaveApply->cancelRecBy = $this->employeeId;
                        $leaveApply->cancelRecDt = Helper::getcurrentExpressionDate();
                    }
                    $this->repository->edit($leaveApply, $id);
                    $leaveApply->id = $id;
                    $leaveApply->employeeId = $requestedEmployeeID;
                    try {
                        HeadNotification::pushNotification(($leaveApply->status == 'C') ? NotificationEvents::LEAVE_CANCELLED_APPROVE_ACCEPTED : NotificationEvents::LEAVE_CANCELLED_APPROVE_REJECTED, $leaveApply, $this->adapter, $this);
                    } catch (Exception $e) {
                        $this->flashmessenger()->addMessage($e->getMessage());
                    }
                }
            }





            return $this->redirect()->toRoute("leaveapprove");
        }
        return Helper::addFlashMessagesToArray($this, [
            'form' => $this->form,
            'id' => $id,
            'employeeName' => $employeeName,
            'requestedDt' => $detail['REQUESTED_DT'],
            'role' => $role,
            'availableDays' => $preBalance,
            'status' => $detail['STATUS'],
            'remarksDtl' => $detail['REMARKS'],
            'totalDays' => $detail['TOTAL_DAYS'],
            'recommendedBy' => $recommenderId,
            'recommender' => $authRecommender,
            'approver' => $authApprover,
            'approvedDT' => $detail['APPROVED_DT'],
            'employeeId' => $this->employeeId,
            'requestedEmployeeId' => $requestedEmployeeID,
            'allowHalfDay' => $leaveDtl['ALLOW_HALFDAY'],
            'leave' => $leaveRequestRepository->getLeaveList($detail['EMPLOYEE_ID']),
            'customRenderer' => Helper::renderCustomView(),
            'subEmployeeId' => $detail['SUB_EMPLOYEE_ID'],
            'subRemarks' => $detail['SUB_REMARKS'],
            'subApprovedFlag' => $detail['SUB_APPROVED_FLAG'],
            'employeeList' => EntityHelper::getTableKVListWithSortOption($this->adapter, HrEmployees::TABLE_NAME, HrEmployees::EMPLOYEE_ID, [HrEmployees::FIRST_NAME, HrEmployees::MIDDLE_NAME, HrEmployees::LAST_NAME], [HrEmployees::STATUS => "E", HrEmployees::RETIRED_FLAG => "N"], HrEmployees::FIRST_NAME, "ASC", " ", FALSE, TRUE), 'gp' => $detail['GRACE_PERIOD']
        ]);
    }


    public function batchCancelApproveRejectAction()
    {
        $request = $this->getRequest();
        try {
            if (!$request->ispost()) {
                throw new Exception('the request is not post');
            }
            $postData = $request->getPost()['data'];
            $postBtnAction = $request->getPost()['btnAction'];
            if ($postBtnAction == 'btnApprove') {
                $action = 'Approve';
            } elseif ($postBtnAction == 'btnReject') {
                $action = 'Reject';
            } else {
                throw new Exception('no action defined');
            }
            if ($postData == null) {
                throw new Exception('no selected rows');
            } else {

                foreach ($postData as $data) {
                    $leaveApply = new LeaveApply();
                    $id = $data['id'];
                    $role = $data['role'];

                    $detail = $this->repository->fetchById($id);
                    $requestedEmployeeID = $detail['EMPLOYEE_ID'];

                    if ($detail['STATUS'] == 'CP' || $detail['STATUS'] == 'CR') {
                        if ($role == 2) {
                            $leaveApply->cancelRecDt = Helper::getcurrentExpressionDate();
                            if ($action == "Reject") {
                                $leaveApply->status = "AP";
                            } else if ($action == "Approve") {
                                $leaveApply->status = "CR";
                            }
                            $leaveApply->cancelRecBy = $this->employeeId;
                            $this->repository->edit($leaveApply, $id);

                            $leaveApply->id = $id;
                            $leaveApply->employeeId = $requestedEmployeeID;
                            $leaveApply->approvedBy = $detail['APPROVER_ID'];
                            try {
                                if ($leaveApply->status == 'CR') {
                                    HeadNotification::pushNotification(NotificationEvents::LEAVE_CANCELLED_RECOMMEND_ACCEPTED, $leaveApply, $this->adapter, $this);
                                } else {
                                    HeadNotification::pushNotification(NotificationEvents::LEAVE_CANCELLED_RECOMMEND_REJECTED, $leaveApply, $this->adapter, $this);
                                }
                            } catch (Exception $e) {
                            }
                        } else if ($role == 3 || $role == 4) {
                            $leaveApply->cancelAppDt = Helper::getcurrentExpressionDate();
                            if ($action == "Reject") {
                                $leaveApply->status = "AP";
                            } else if ($action == "Approve") {
                                $leaveApply->status = "C";
                            }
                            unset($leaveApply->halfDay);
                            $leaveApply->cancelAppBy = $this->employeeId;
                            if ($role == 4) {
                                $leaveApply->cancelRecBy = $this->employeeId;
                                $leaveApply->cancelRecDt = Helper::getcurrentExpressionDate();
                            }
                            $this->repository->edit($leaveApply, $id);
                            $leaveApply->id = $id;
                            $leaveApply->employeeId = $requestedEmployeeID;
                            try {
                                HeadNotification::pushNotification(($leaveApply->status == 'C') ? NotificationEvents::LEAVE_CANCELLED_APPROVE_ACCEPTED : NotificationEvents::LEAVE_CANCELLED_APPROVE_REJECTED, $leaveApply, $this->adapter, $this);
                            } catch (Exception $e) {
                                $this->flashmessenger()->addMessage($e->getMessage());
                            }
                        }
                    }
                }
            }
            $listData = $this->getAllList();
            return new CustomViewModel(['success' => true, 'data' => $listData]);
        } catch (Exception $e) {
            return new CustomViewModel(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    public function addAction()
    {
        $request = $this->getRequest();
        if ($request->isPost()) {
            $postedData = $request->getPost();
            $this->form->setData($postedData);
            $leaveSubstitute = $postedData->leaveSubstitute;
            $leaveRequestRepo = new LeaverequestRepository($this->adapter);
            $checkAttendance = $leaveRequestRepo->getCurrentAttd($postedData['employeeId']);
            if ($this->form->isValid()) {
                $leaveRequest = new LeaveApply();
                $leaveRequest->exchangeArrayFromForm($this->form->getData());

                $leaveRequest->id = (int) Helper::getMaxId($this->adapter, LeaveApply::TABLE_NAME, LeaveApply::ID) + 1;
                $leaveRequest->startDate = Helper::getExpressionDate($leaveRequest->startDate);
                $leaveRequest->endDate = Helper::getExpressionDate($leaveRequest->endDate);
                $leaveRequest->requestedDt = Helper::getcurrentExpressionDate();
                $leaveRequest->createdBy = $this->employeeId;
                $leaveRequest->status = "RQ";

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
                }
                if ($leaveRequest->status == 'RQ') {
                    if ($checkAttendance['OVERALL_STATUS'] != 'PR') {
                        $leaveRequest->status = "RC";
                    } else {
                        $leaveRequest->status = "RQ";
                    }
                }
                //echo '<pre>'; print_r($leaveRequest); die;
                $LeaveApplyRepository = new LeaveApplyRepository($this->adapter);
                $LeaveApplyRepository->add($leaveRequest);
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
                if ($leaveRequest->status == 'RC') {
                    try {
                        HeadNotification::pushNotification(NotificationEvents::LEAVE_APPLIED, $leaveRequest, $this->adapter, $this);
                    } catch (Exception $e) {
                        $this->flashmessenger()->addMessage($e->getMessage());
                    }
                }
                return $this->redirect()->toRoute('leaveapprove', [
                    'controller' => 'LeaveApproveController',
                    'action' =>  'status'
                ]);
                //return $this->redirect()->toRoute("leavestatus");
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
        //$data = EntityHelper::getTableKVListWithSortOption($this->adapter, "HRIS_EMPLOYEES", "EMPLOYEE_ID", ["EMPLOYEE_CODE", "FULL_NAME"], ["STATUS" => 'E', 'RETIRED_FLAG' => 'N', 'IS_ADMIN' => "N"], "FULL_NAME", "ASC", "-", FALSE, TRUE, $this->employeeId);
        // echo '<pre>';
        // echo count($data);
        // print_r($data);
        // die;
        //print_r(EntityHelper::getRAWiseEmployeeList($this->adapter, $this->employeeId)); die;
        $employees = EntityHelper::getRAWiseEmployeeList($this->adapter, $this->employeeId);
        return Helper::addFlashMessagesToArray($this, [
            'form' => $this->form,
            'employees' => $employees['data'],
            'approvers' => $employees['approver'],
            'customRenderer' => Helper::renderCustomView(),
            'applyOption' => $applyOption,
            'subLeaveReference' => $subLeaveReference,
            'subLeaveMaxDays' => $subLeaveMaxDays
        ]);
    }
    public function leaveReportCardAction()
    {
        $leaveList = EntityHelper::getTableKVListWithSortOption($this->adapter, LeaveMaster::TABLE_NAME, LeaveMaster::LEAVE_ID, [LeaveMaster::LEAVE_ENAME], [LeaveMaster::STATUS => 'E'], LeaveMaster::LEAVE_ENAME, "ASC", NULL, ['-1' => 'All Leaves'], TRUE);
        $leaveSE = $this->getSelectElement(['name' => 'leave', 'id' => 'leaveId', 'class' => 'form-control reset-field', 'label' => 'Type'], $leaveList);
        $leaveSE->setAttribute('multiple', 'multiple');

        $request = $this->getRequest();
        if ($request->isPost()) {
            try {
                $data = $request->getPost();
                $employee = $data['data']['employeeId'];
                $reportCardRepo = new LeaveReportCardRepository($this->adapter);
                $rawList = $reportCardRepo->fetchLeaveReportCard($data);
                $list = Helper::extractDbData($rawList);

                if ($list == null || count($list) == 0) {
                    $rawList = $this->repository->fetchIfEmpty($data);
                    $list = Helper::extractDbData($rawList);
                    $list[0]['FROM_DATE_AD'] = '-';
                    $list[0]['FROM_DATE_BS'] = '-';
                    $list[0]['TO_DATE_AD'] = '-';
                    $list[0]['TO_DATE_BS'] = '-';
                    $list[0]['HALF_DAY_DETAIL'] = '-';
                    $list[0]['NO_OF_DAYS'] = '-';
                    $list[0]['REMARKS'] = '-';
                    $list[0]['STATUS'] = '-';
                    $list[0]['RECOMMENDED_BY'] = '-';
                    $list[0]['RECOMMENDED_DT'] = '-';
                    $list[0]['LEAVE_ENAME'] = '-';
                    $list[0]['LEAVE_CODE'] = '-';
                    $list[0]['RECOMMENDED_BY_NAME'] = '-';
                    $list[0]['APPROVED_BY_NAME'] = '-';
                }

                $rawLeaves = $reportCardRepo->fetchLeaves($employee, $data['data']['leaveId']);
                $leaves = Helper::extractDbData($rawLeaves);

                return new JsonModel([
                    "success" => true,
                    "data" => $list,
                    "leaves" => $leaves,
                    "message" => null,
                ]);
            } catch (Exception $e) {
                return new JsonModel(['success' => false, 'data' => null, 'message' => $e->getMessage()]);
            }
        }
        $searchValues = EntityHelper::getSearchData($this->adapter);
        $employees = EntityHelper::getRAWiseEmployeeList($this->adapter, $this->employeeId, 1);
        array_push($employees, ['EMPLOYEE_ID' => $this->employeeId, 'FULL_NAME' => '---Self---', 'IS_APPROVER' => 'Y']);
        $searchValues['employee'] = $employees;

        return Helper::addFlashMessagesToArray($this, [
            'searchValues' => $searchValues,
            'acl' => $this->acl,
            'leaves' => $leaveSE,
            'employeeDetail' => $this->storageData['employee_detail'],
            'preference' => $this->preference
        ]);
    }
}
