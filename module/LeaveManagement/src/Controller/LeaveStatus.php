<?php

namespace LeaveManagement\Controller;

use Application\Controller\HrisController;
use Application\Helper\EntityHelper;
use Application\Helper\Helper;
use Exception;
use LeaveManagement\Form\LeaveApplyForm;
use LeaveManagement\Model\LeaveApply;
use LeaveManagement\Model\LeaveMaster;
use LeaveManagement\Repository\LeaveStatusRepository;
use ManagerService\Repository\LeaveApproveRepository;
use Notification\Controller\HeadNotification;
use Notification\Model\NotificationEvents;
use SelfService\Repository\LeaveRequestRepository;
use Setup\Model\HrEmployees;
use Zend\Authentication\Storage\StorageInterface;
use Zend\Db\Adapter\AdapterInterface;
use Zend\View\Model\JsonModel;

class LeaveStatus extends HrisController {

    public function __construct(AdapterInterface $adapter, StorageInterface $storage) {
        parent::__construct($adapter, $storage);
        $this->initializeRepository(LeaveStatusRepository::class);
        $this->initializeForm(LeaveApplyForm::class);
    }

    public function indexAction() {
        $leaveList = EntityHelper::getTableKVListWithSortOption($this->adapter, LeaveMaster::TABLE_NAME, LeaveMaster::LEAVE_ID, [LeaveMaster::LEAVE_ENAME], [LeaveMaster::STATUS => 'E'], LeaveMaster::LEAVE_ENAME, "ASC", NULL, ['-1' => 'All Leaves'], TRUE);
        $leaveSE = $this->getSelectElement(['name' => 'leave', 'id' => 'leaveId', 'class' => 'form-control reset-field', 'label' => 'Type'], $leaveList);
        $leaveStatusSE = $this->getStatusSelectElement(['name' => 'leaveStatus', 'id' => 'leaveRequestStatusId', 'class' => 'form-control ', 'label' => 'Status']);

        $leaveYearList=EntityHelper::getTableKVList($this->adapter, "HRIS_LEAVE_YEARS", "LEAVE_YEAR_ID", ["LEAVE_YEAR_NAME"], null,null,false,'LEAVE_YEAR_ID','desc');
        $leaveYearSE = $this->getSelectElement(['name' => 'leaveYear', 'id' => 'leaveYear', 'class' => 'form-control ', 'label' => 'Type'], $leaveYearList);
        
        $allLeaveForReport= $this->repository->getAllLeaveforReport();
        
        return $this->stickFlashMessagesTo([
                    'leaves' => $leaveSE,
                    'leaveStatus' => $leaveStatusSE,
                    'searchValues' => EntityHelper::getSearchData($this->adapter),
                    'acl' => $this->acl,
                    'employeeDetail' => $this->storageData['employee_detail'],
                    'preference' => $this->preference,
                    'leaveYearSelect'  =>$leaveYearSE,
                    'allLeaveForReport'  =>$allLeaveForReport,
        ]);
    }

    public function viewAction() {
        $id = (int) $this->params()->fromRoute('id');

        if ($id === 0) {
            return $this->redirect()->toRoute("leavestatus");
        }
        $request = $this->getRequest();
        $leaveRequestRepository = new LeaveRequestRepository($this->adapter);
        $leaveApproveRepository = new LeaveApproveRepository($this->adapter);


        $detail = $leaveApproveRepository->fetchById($id);
        
        if($this->preference['displayHrApproved'] == 'Y' && $detail['HR_APPROVED'] == 'Y'){
            $detail['APPROVER_ID'] = '-1';
            $detail['APPROVER_NAME'] = 'HR';
            $detail['RECOMMENDER_ID'] = '-1';
            $detail['RECOMMENDER_NAME'] = 'HR';
            $detail['RECOMMENDED_BY_NAME'] = 'HR';
            $detail['APPROVED_BY_NAME'] = 'HR';
        }
        
        $fileDetails = $leaveApproveRepository->fetchAttachmentsById($id);

        $status = $detail['STATUS'];

        $requestedEmployeeID = $detail['EMPLOYEE_ID'];
        $recommApprove = $detail['RECOMMENDER_ID'] == $detail['APPROVER_ID'] ? 1 : 0;

        $employeeName = $detail['FULL_NAME'];
        $authRecommender = $detail['RECOMMENDED_BY_NAME'] == null ? $detail['RECOMMENDER_NAME'] : $detail['RECOMMENDED_BY_NAME'];
        $authApprover = $detail['APPROVED_BY_NAME'] == null ? $detail['APPROVER_NAME'] : $detail['APPROVED_BY_NAME'];

        //to get the previous balance of selected leave from assigned leave detail
        $preBalance = $detail['BALANCE'];
        
        $actualDays = ($detail['ACTUAL_DAYS']<1)?'0'+$detail['ACTUAL_DAYS']:$detail['ACTUAL_DAYS'];

        $leaveApply = new LeaveApply();
        if ($request->isPost() && $detail['SETUP_STATUS']=='E') {
            $getData = $request->getPost();
            $reason = $getData->approvedRemarks;
            $action = $getData->submit;

            if ($detail['STATUS'] == 'RQ' || $detail['STATUS'] == 'RC') {
                
                $checkSameDateApproved = $this->repository->getSameDateApprovedStatus($detail['EMPLOYEE_ID'],$detail['START_DATE'],$detail['END_DATE']);
                if($checkSameDateApproved['LEAVE_COUNT']>0 && $action == "Approve"){
                    return $this->redirect()->toRoute("leavestatus");
                }
                
                $leaveApply->approvedDt = Helper::getcurrentExpressionDate();
                if ($action == "Reject") {
                    $leaveApply->status = "R";
                    $this->flashmessenger()->addMessage("Leave Request Rejected!!!");
                } else if ($action == "Approve") {
                    $leaveApply->status = "AP";
                    $this->flashmessenger()->addMessage("Leave Request Approved");
                }
                unset($leaveApply->halfDay);
                $leaveApply->approvedRemarks = $reason;
                $leaveApply->approvedBy = $this->employeeId;
                $leaveApproveRepository->edit($leaveApply, $id);
                $leaveApply->id = $id;
                $leaveApply->employeeId = $requestedEmployeeID;
                try {
                    HeadNotification::pushNotification(($leaveApply->status == 'AP') ? NotificationEvents::LEAVE_APPROVE_ACCEPTED : NotificationEvents::LEAVE_APPROVE_REJECTED, $leaveApply, $this->adapter, $this);
                } catch (Exception $e) {
                    $this->flashmessenger()->addMessage($e->getMessage());
                }
            }

            if ($detail['STATUS'] == 'CP' || $detail['STATUS'] == 'CR') {
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
                $leaveApproveRepository->edit($leaveApply, $id);
                $leaveApply->id = $id;
                $leaveApply->employeeId = $requestedEmployeeID;
                try {
                    HeadNotification::pushNotification(($leaveApply->status == 'C') ? NotificationEvents::LEAVE_CANCELLED_APPROVE_ACCEPTED : NotificationEvents::LEAVE_CANCELLED_APPROVE_REJECTED, $leaveApply, $this->adapter, $this);
                } catch (Exception $e) {
                    $this->flashmessenger()->addMessage($e->getMessage());
                }
            }

            return $this->redirect()->toRoute("leavestatus");
        }
        $leaveApply->exchangeArrayFromDB($detail);
        $this->form->bind($leaveApply);
        return Helper::addFlashMessagesToArray($this, [
                    'form' => $this->form,
                    'id' => $id,
                    'employeeId' => $requestedEmployeeID,
                    'employeeName' => $employeeName,
                    'requestedDt' => $detail['REQUESTED_DT'],
                    'availableDays' => $preBalance,
                    'totalDays' => $detail['TOTAL_DAYS'],
                    'recommender' => $authRecommender,
                    'approver' => $authApprover,
                    'approvedDT' => $detail['APPROVED_DT'],
                    'remarkDtl' => $detail['REMARKS'],
                    'status' => $status,
                    'allowHalfDay' => $detail['ALLOW_HALFDAY'],
                    'leave' => $leaveRequestRepository->getLeaveList($detail['EMPLOYEE_ID']),
                    'customRenderer' => Helper::renderCustomView(),
                    'recommApprove' => $recommApprove,
                    'subEmployeeId' => $detail['SUB_EMPLOYEE_ID'],
                    'subRemarks' => $detail['SUB_REMARKS'],
                    'subApprovedFlag' => $detail['SUB_APPROVED_FLAG'],
                    'employeeList' => EntityHelper::getTableKVListWithSortOption($this->adapter, HrEmployees::TABLE_NAME, HrEmployees::EMPLOYEE_ID, [HrEmployees::FIRST_NAME, HrEmployees::MIDDLE_NAME, HrEmployees::LAST_NAME], [HrEmployees::STATUS => "E", HrEmployees::RETIRED_FLAG => "N"], HrEmployees::FIRST_NAME, "ASC", " ", FALSE, TRUE),
                    'gp' => $detail['GRACE_PERIOD'],
                    'acl' => $this->acl,
                    'files' => $fileDetails,
                    'actualDays' => $actualDays,
                    'subLeaveName' => $detail['LEAVE_ENAME'],
                    'setupStatus' => $detail['SETUP_STATUS']
        ]);
    }

    public function pullLeaveRequestStatusListAction() {
        try {
            $request = $this->getRequest();
            $data = $request->getPost();
            $recordList = $this->repository->getLeaveRequestList($data);

            if($this->preference['displayHrApproved'] == 'Y'){
                for($i = 0; $i < count($recordList); $i++){
                    if($recordList[$i]['HARDCOPY_SIGNED_FLAG'] == 'Y'){
                        $recordList[$i]['APPROVER_ID'] = '-1';
                        $recordList[$i]['APPROVER_NAME'] = 'HR';
                        $recordList[$i]['RECOMMENDER_ID'] = '-1';
                        $recordList[$i]['RECOMMENDER_NAME'] = 'HR';
                    }
                }
            }

            return new JsonModel([
                "success" => "true",
                "data" => $recordList,
                "message" => null
            ]);
        } catch (Exception $e) {
            return new JsonModel(['success' => false, 'data' => null, 'message' => $e->getMessage()]);
        }
    }

    public function bulkAction() {
        $request = $this->getRequest();
        try {
            $postData = $request->getPost();
            if ($postData['super_power'] == 'true') {
                $this->makeSuperDecision($postData['id'], $postData['action'] == "approve");
            } else {
                $this->makeDecision($postData['id'], $postData['action']);
            }
            return new JsonModel(['success' => true, 'data' => null]);
        } catch (Exception $e) {
            return new JsonModel(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    private function makeDecision($id, $approve, $remarks = null, $enableFlashNotification = false) {

        $leaveApproveRepository = new LeaveApproveRepository($this->adapter);
        $detail = $leaveApproveRepository->fetchById($id);
        // echo '<pre>';print_r($detail);die;
        if($detail['SETUP_STATUS']!='E'){
            return;
        }
        if ($detail['STATUS'] == 'RQ' || $detail['STATUS'] == 'RC' || $detail['STATUS'] == 'AP' || $detail['STATUS'] == 'R') {
            $checkSameDateApproved = $this->repository->getSameDateApprovedStatus($detail['EMPLOYEE_ID'],$detail['START_DATE'],$detail['END_DATE']);
            if($checkSameDateApproved['LEAVE_COUNT']>0 && $approve){
                throw new Exception('Leave Overlap Detected');
            }
            $model = new LeaveApply();
            $model->id = $id;
            $model->recommendedDate = Helper::getcurrentExpressionDate();
            $model->recommendedBy = $this->employeeId;
            $model->approvedRemarks = $remarks;
            $model->approvedDate = Helper::getcurrentExpressionDate();
            $model->approvedBy = $this->employeeId;
            $model->status = ($approve == 'approve' )? "AP" : "R";
            $message = ($approve == 'approve' )? "Leave Request Approved" : "Leave Request Rejected";
            $notificationEvent = $approve ? NotificationEvents::LEAVE_APPROVE_ACCEPTED : NotificationEvents::LEAVE_APPROVE_REJECTED;
            // echo '<pre>';print_r($model);die;
            $leaveApproveRepository->edit($model, $id);
            if ($enableFlashNotification) {
                $this->flashmessenger()->addMessage($message);
            }
            try {
                HeadNotification::pushNotification($notificationEvent, $model, $this->adapter, $this);
            } catch (Exception $e) {
                $this->flashmessenger()->addMessage($e->getMessage());
            }
        }
        // cancel leave request starts here

        if ($detail['STATUS'] == 'CP' || $detail['STATUS'] == 'CR') {
            $model = new LeaveApply();
            $model->id = $id;
            $model->cancelAppDt = Helper::getcurrentExpressionDate();
            $model->cancelAppBy = $this->employeeId;
            $model->status = $approve ? "C" : "AP";
            $message = $approve ? "Leave Cancel Request Approved" : "Leave Cancel Request Rejected";
            $notificationEvent = $approve ? NotificationEvents::LEAVE_CANCELLED_APPROVE_ACCEPTED : NotificationEvents::LEAVE_CANCELLED_APPROVE_REJECTED;
            $leaveApproveRepository->edit($model, $id);
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

        $leaveApproveRepository = new LeaveApproveRepository($this->adapter);
        $detail = $leaveApproveRepository->fetchById($id);
        
        if($detail['SETUP_STATUS']!='E'){
            return;
        }
        if ($detail['STATUS'] == 'AP') {
            $model = new LeaveApply();
            $model->id = $id;
            $model->recommendedDate = Helper::getcurrentExpressionDate();
            $model->recommendedBy = $this->employeeId;
            $model->approvedRemarks = $remarks;
            $model->approvedDate = Helper::getcurrentExpressionDate();
            $model->approvedBy = $this->employeeId;
            $model->status = $approve ? "AP" : "R";
            
            $message = $approve ? "Leave Request Approved" : "Leave Request Rejected";
            $notificationEvent = $approve ? NotificationEvents::LEAVE_APPROVE_ACCEPTED : NotificationEvents::LEAVE_APPROVE_REJECTED;
            $leaveApproveRepository->edit($model, $id);
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

    public function applyLFCAction() {
        $id =  $this->params()->fromRoute('id');

        if ($id === 0) {
            return $this->redirect()->toRoute("leavestatus");
        }

        $request = $this->getRequest();

        $data = $this->repository->getLfcData($id);


        return $this->stickFlashMessagesTo([
            'acl' => $this->acl,
            'employees' => EntityHelper::getTableKVListWithSortOption($this->adapter, "HRIS_EMPLOYEES", "EMPLOYEE_ID", ["EMPLOYEE_CODE", "FULL_NAME"], ["STATUS" => 'E', 'RETIRED_FLAG' => 'N', 'IS_ADMIN' => "N"], "FULL_NAME", "ASC", "-", FALSE, TRUE),
            'preference' => $this->preference,
            'data' => $data
        ]);
    }

}
