<?php

namespace SelfService\Controller;

use Application\Controller\HrisController;
use Application\Helper\EntityHelper;
use Application\Helper\Helper;
use Exception;
use LeaveManagement\Form\LeaveApplyForm;
use LeaveManagement\Model\LeaveApply;
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

class LeaveNotification extends HrisController {

    public function __construct(AdapterInterface $adapter, StorageInterface $storage) {
        parent::__construct($adapter, $storage);
        $this->initializeRepository(LeaveSubstituteRepository::class);
        $this->initializeForm(LeaveApplyForm::class);
    }

    public function indexAction() {
        $request = $this->getRequest();
        if ($request->isPost()) {
            try {
                $result = $this->repository->fetchByEmployeeId($this->employeeId);
                $list = Helper::extractDbData($result);

                return new JsonModel(['success' => true, 'data' => $list, 'error' => '']);
            } catch (Exception $e) {
                return new JsonModel(['success' => false, 'data' => [], 'error' => $e->getMessage()]);
            }
        }
        return Helper::addFlashMessagesToArray($this, []);
    }

    public function viewAction() {
        $id = (int) $this->params()->fromRoute('id', 0);
        if ($id === 0) {
            return $this->redirect()->toRoute("leaveNotification");
        }
        $request = $this->getRequest();

        $leaveApproveRepository = new LeaveApproveRepository($this->adapter);
        $leaveRequestRepository = new LeaveRequestRepository($this->adapter);

        $detail = $leaveApproveRepository->fetchById($id);
        $authRecommender = $detail['RECOMMENDED_BY_NAME'] == null ? $detail['RECOMMENDER_NAME'] : $detail['RECOMMENDED_BY_NAME'];
        $authApprover = $detail['APPROVED_BY_NAME'] == null ? $detail['APPROVER_NAME'] : $detail['APPROVED_BY_NAME'];

        //to get the previous balance of selected leave from assigned leave detail
        $result = $leaveApproveRepository->assignedLeaveDetail($detail['LEAVE_ID'], $detail['EMPLOYEE_ID'])->getArrayCopy();
        $preBalance = $result['BALANCE'];

        $leaveApply = new LeaveApply();
        if ($request->isPost()) {
            $leaveSubstitute = new LeaveSubstitute();
            $getData = $request->getPost();
            $action = $getData->submit;
            $leaveSubstitute->approvedDate = Helper::getcurrentExpressionDate();
            $leaveSubstitute->remarks = $getData->subRemarks;
            if ($action == 'Approve') {
                $leaveSubstitute->approvedFlag = "Y";
                $this->flashmessenger()->addMessage("Substitute Work Request Approved!!!");
            } else if ($action == 'Reject') {
                $leaveSubstitute->approvedFlag = "N";
                $leaveRequestRepository->cancelFromSubstitue($id);
                $this->flashmessenger()->addMessage("Substitute Work Request Rejected!!!");
            }
            $this->repository->edit($leaveSubstitute, $id);
            $leaveApply->id = $id;
            try {
                HeadNotification::pushNotification(($leaveSubstitute->approvedFlag == 'Y') ? NotificationEvents::LEAVE_SUBSTITUTE_ACCEPTED : NotificationEvents::LEAVE_SUBSTITUTE_REJECTED, $leaveApply, $this->adapter, $this);
            } catch (Exception $e) {
                $this->flashmessenger()->addMessage($e->getMessage());
            }
            if ($action == 'Approve') {
                try {
                    HeadNotification::pushNotification(NotificationEvents::LEAVE_APPLIED, $leaveApply, $this->adapter, $this);
                } catch (Exception $e) {
                    $this->flashmessenger()->addMessage($e->getMessage());
                }
            }
            $this->redirect()->toRoute('leaveNotification');
        }
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
                    'leave' => $leaveRequestRepository->getLeaveList($detail['EMPLOYEE_ID']),
                    'subEmployeeId' => $detail['SUB_EMPLOYEE_ID'],
                    'subRemarks' => $detail['SUB_REMARKS'],
                    'subApprovedFlag' => $detail['SUB_APPROVED_FLAG'],
                    'customRenderer' => Helper::renderCustomView(),
                    'employeeList' => EntityHelper::getTableKVListWithSortOption($this->adapter, HrEmployees::TABLE_NAME, HrEmployees::EMPLOYEE_ID, [HrEmployees::FIRST_NAME, HrEmployees::MIDDLE_NAME, HrEmployees::LAST_NAME], [HrEmployees::STATUS => "E", HrEmployees::RETIRED_FLAG => "N"], HrEmployees::FIRST_NAME, "ASC", " ", false, true)
        ]);
    }

}
