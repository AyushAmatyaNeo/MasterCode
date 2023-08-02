<?php

/**
 * Created by PhpStorm.
 * User: shijan
 * Date: 2/5/20
 * Time: 10:53 AM
 */

namespace LeaveManagement\Controller;

use Application\Controller\HrisController;
use Application\Custom\CustomViewModel;
use Application\Helper\EntityHelper;
use Application\Helper\Helper;
use Exception;
use LeaveManagement\Form\LeaveDeductionForm;
use LeaveManagement\Model\LeaveDeduction as LeaveDeductionModel;
use LeaveManagement\Repository\LeaveDeductionRepository;
use LeaveManagement\Repository\LeaveStatusRepository;
use Notification\Controller\HeadNotification;
use Notification\Model\NotificationEvents;
use SelfService\Repository\LeaveRequestRepository;
use Zend\Authentication\Storage\StorageInterface;
use Zend\Db\Adapter\AdapterInterface;
use Zend\View\Model\JsonModel;

class LeaveDeduction extends HrisController {

    public function __construct(AdapterInterface $adapter, StorageInterface $storage) {
        parent::__construct($adapter, $storage);
        $this->initializeRepository(LeaveDeductionRepository::class);
        $this->initializeForm(LeaveDeductionForm::class);
    }

    public function indexAction() {
        $leaveYearList=EntityHelper::getTableKVList($this->adapter, "HRIS_LEAVE_YEARS", "LEAVE_YEAR_ID", ["LEAVE_YEAR_NAME"], null,null,false,'LEAVE_YEAR_ID','desc');
        $leaveYearSE = $this->getSelectElement(['name' => 'leaveYear', 'id' => 'leaveYear', 'class' => 'form-control ', 'label' => 'Type'], $leaveYearList);
        
        $leaveStatusReposotory = new LeaveStatusRepository($this->adapter);
                
        $allLeaveForReport= $leaveStatusReposotory->getMonthlyLeaveforReport();

        return $this->stickFlashMessagesTo([
            'searchValues' => EntityHelper::getSearchData($this->adapter),
            'acl' => $this->acl,
            'employeeDetail' => $this->storageData['employee_detail'],
            'preference' => $this->preference,
            'leaveYearSelect'  =>$leaveYearSE,
            'allLeaveForReport'  =>$allLeaveForReport,
        ]);
    }

    public function pullLeaveDeductionListAction() {
        try {
            $request = $this->getRequest();
            $data = $request->getPost();
            $recordList = $this->repository->fetchLeaveDeductionList($data);

//            if($this->preference['displayHrApproved'] == 'Y'){
//                for($i = 0; $i < count($recordList); $i++){
//                    if($recordList[$i]['HARDCOPY_SIGNED_FLAG'] == 'Y'){
//                        $recordList[$i]['APPROVER_ID'] = '-1';
//                        $recordList[$i]['APPROVER_NAME'] = 'HR';
//                        $recordList[$i]['RECOMMENDER_ID'] = '-1';
//                        $recordList[$i]['RECOMMENDER_NAME'] = 'HR';
//                    }
//                }
//            }

            return new JsonModel(["success" => "true", "data" => $recordList, "message" => null ]);
        } catch (Exception $e) {
            return new JsonModel(['success' => false, 'data' => null, 'message' => $e->getMessage()]);
        }
    }

    public function addAction() {
        $request = $this->getRequest();
        if ($request->isPost()) {
            $postedData = $request->getPost();
            $this->form->setData($postedData);
            if ($this->form->isValid()) {
                $leaveDeduction = new LeaveDeductionModel();
                $leaveDeduction->exchangeArrayFromForm($this->form->getData());
                $leaveDeduction->id = (int) Helper::getMaxId($this->adapter, LeaveDeductionModel::TABLE_NAME, LeaveDeductionModel::ID) + 1;
                $leaveDeduction->deductionDt = Helper::getExpressionDate($leaveDeduction->deductionDt);
                $leaveDeduction->createdDt = Helper::getcurrentExpressionDate();
                $leaveDeduction->createdBy = $this->employeeId;
                $leaveDeduction->status = "AP";

                $this->repository->add($leaveDeduction);
                $this->flashmessenger()->addMessage("Leave Deduction Successful !!!");

                return $this->redirect()->toRoute("leavededuction");
            }
        }

        return Helper::addFlashMessagesToArray($this, [
            'form' => $this->form,
            'employees' => EntityHelper::getTableKVListWithSortOption($this->adapter, "HRIS_EMPLOYEES", "EMPLOYEE_ID", ["EMPLOYEE_CODE", "FULL_NAME"], ["STATUS" => 'E', 'RETIRED_FLAG' => 'N', 'IS_ADMIN' => "N"], "FULL_NAME", "ASC", "-", FALSE, TRUE, $this->employeeId),
            'customRenderer' => Helper::renderCustomView(),
//            'applyOption' => $applyOption,
//            'subLeaveReference' => $subLeaveReference,
//            'subLeaveMaxDays' => $subLeaveMaxDays
        ]);
    }

    public function pullLeaveDetailWidEmployeeIdAction() {
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

    public function pullLeaveDetailAction() {
        try {
            $request = $this->getRequest();
            if ($request->isPost()) {
                $postedData = $request->getPost();
                $leaveRequestRepository = new LeaveRequestRepository($this->adapter);
                $leaveId = $postedData['leaveId'];
                $employeeId = $postedData['employeeId'];
                $startDate = $postedData['startDate'];
                $leaveDetail = $leaveRequestRepository->getLeaveDetail($employeeId, $leaveId, $startDate);
                
                $maxSubDays=500;
                if(isset($this->preference['subLeaveMaxDays'])){
                $maxSubDays=$this->preference['subLeaveMaxDays'];
                }
                
                $subtituteDetails = $leaveRequestRepository->getSubstituteList($leaveId, $employeeId,$maxSubDays);

                return new CustomViewModel(['success' => true, 'data' => $leaveDetail, 'subtituteDetails' => $subtituteDetails, 'error' => '']);
            } else {
                throw new Exception("The request should be of type post");
            }
        } catch (Exception $e) {
            return new CustomViewModel(['success' => false, 'data' => [], 'error' => $e->getMessage()]);
        }
    }

    public function bulkAction() {
        $request = $this->getRequest();

        try {
            $postData = $request->getPost();
            if ($postData['super_power'] == 'true') {
                $this->makeSuperDecision($postData['id'], $postData['action'] == "approve");
            } else {
            }
            return new JsonModel(['success' => true, 'data' => null]);
        } catch (Exception $e) {
            return new JsonModel(['success' => false, 'error' => $e->getMessage()]);
        }
    }


    private function makeSuperDecision($id, $approve, $remarks = null, $enableFlashNotification = false) {

        $detail = $this->repository->fetchById($id);

        if ($detail['STATUS'] == 'AP') {
            $model = new LeaveDeductionModel();
            $model->id = $id;
            $model->modifiedDt = Helper::getcurrentExpressionDate();
            $model->modifiedBy = $this->employeeId;
            $model->status = $approve ? "AP" : "C";

            $message = $approve ? "Leave Deduction Approved" : "Leave Deduction Cancelled";
            $notificationEvent = $approve ? NotificationEvents::LEAVE_DEDUCTION_APPROVED : NotificationEvents::LEAVE_DEDUCTION_REJECTED;
            $this->repository->edit($model, $id);
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
