<?php

namespace ManagerService\Controller;

use Application\Controller\HrisController;
use Application\Helper\EntityHelper;
use Application\Helper\Helper;
use Exception;
use HolidayManagement\Model\Holiday;
use ManagerService\Repository\HolidayWorkApproveRepository;
use Notification\Controller\HeadNotification;
use Notification\Model\NotificationEvents;
use SelfService\Form\WorkOnHolidayForm;
use SelfService\Model\WorkOnHoliday;
use ManagerService\Repository\ManagerReportRepo;
use SelfService\Repository\WorkOnHolidayRepository;
use WorkOnHoliday\Repository\WorkOnHolidayStatusRepository;
use Zend\Authentication\Storage\StorageInterface;
use Zend\Db\Adapter\AdapterInterface;
use Zend\View\Model\JsonModel;

class HolidayWorkApproveController extends HrisController
{

    public function __construct(AdapterInterface $adapter, StorageInterface $storage)
    {
        parent::__construct($adapter, $storage);
        $this->initializeRepository(HolidayWorkApproveRepository::class);
        $this->initializeForm(WorkOnHolidayForm::class);
    }

    public function indexAction()
    {
        $request = $this->getRequest();
        if ($request->isPost()) {
            try {
                $rawList = $this->repository->getAllRequest($this->employeeId);
                $list = iterator_to_array($rawList, false);
                return new JsonModel(['success' => true, 'data' => $list, 'error' => '']);
            } catch (Exception $e) {
                return new JsonModel(['success' => false, 'data' => [], 'error' => $e->getMessage()]);
            }
        }
        return $this->stickFlashMessagesTo([]);
    }

    public function viewAction()
    {
        $id = (int) $this->params()->fromRoute('id');
        $role = $this->params()->fromRoute('role');
        if ($id === 0) {
            return $this->redirect()->toRoute("holidayWorkApprove");
        }
        $workOnHolidayModel = new WorkOnHoliday();
        $request = $this->getRequest();
        $detail = $this->repository->fetchById($id);

        if ($request->isPost()) {
            $postedData = (array) $request->getPost();
            $action = $postedData['submit'];
            $this->makeDecision($id, $role, $action == 'Approve', $postedData[$role == 2 ? 'recommendedRemarks' : 'approvedRemarks'], true);
            if (in_array($role, [3, 4]) && $action == 'Approve') {
                $this->repository->wohReward($detail['ID']);
            }
            return $this->redirect()->toRoute("holidayWorkApprove");
        }
        $workOnHolidayModel->exchangeArrayFromDB($detail);
        $this->form->bind($workOnHolidayModel);
        return Helper::addFlashMessagesToArray($this, [
            'form' => $this->form,
            'id' => $id,
            'role' => $role,
            'detail' => $detail
        ]);
    }

    public function statusAction()
    {
        $holidayList = EntityHelper::getTableKVListWithSortOption($this->adapter, Holiday::TABLE_NAME, Holiday::HOLIDAY_ID, [Holiday::HOLIDAY_ENAME], [Holiday::STATUS => 'E'], Holiday::HOLIDAY_ENAME, "ASC", NULL, [-1 => 'All Holiday'], TRUE);
        $holidaySE = $this->getSelectElement(['name' => 'holiday', 'id' => 'holidayId', 'class' => 'form-control reset-field', 'label' => 'Holiday'], $holidayList);
        $statusSE = $this->getStatusSelectElement(['name' => 'requestStatusId', 'id' => 'requestStatusId', 'class' => 'form-control reset-field', 'label' => 'Status']);
        $managerRepo = new ManagerReportRepo($this->adapter);
        $employee = $managerRepo->fetchAllEmployee($this->employeeId);
        $employees = [];
        foreach ($employee as $key => $value) {
            array_push($employees, ["id" => $key, "name" => $value]);
        }
        return Helper::addFlashMessagesToArray($this, [
            'holidays' => $holidaySE,
            'status' => $statusSE,
            'employees' => $employees,
            'recomApproveId' => $this->employeeId,
            'searchValues' => EntityHelper::getSearchData($this->adapter),
        ]);
    }

    public function batchApproveRejectAction()
    {
        $request = $this->getRequest();
        try {
            $postData = $request->getPost();
            // echo '<pre>';print_r($postData);die;
            $this->makeDecision($postData['id'], $postData['role'], $postData['btnAction'] == "btnApprove");
            if (in_array($postData['role'], [3, 4]) && $postData['btnAction'] == "btnApprove") {
                // echo '<pre>';print_r($postData['id']);die;
                $this->repository->wohReward($postData['id']);
            }
            return new JsonModel(['success' => true, 'data' => null]);
        } catch (Exception $e) {
            return new JsonModel(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    public function pullHoliayWorkRequestStatusListAction()
    {
        try {
            $request = $this->getRequest();
            $data = $request->getPost();
            $holidayWorkStatusRepo = new WorkOnHolidayStatusRepository($this->adapter);
            $result = $holidayWorkStatusRepo->getFilteredRecord($data, $data['recomApproveId']);
            $recordList = Helper::extractDbData($result);
            return new JsonModel([
                "success" => "true",
                "data" => $recordList,
            ]);
        } catch (Exception $e) {
            return new JsonModel(['success' => false, 'data' => null, 'message' => $e->getMessage()]);
        }
    }

    private function makeDecision($id, $role, $approve, $remarks = null, $enableFlashNotification = false)
    {
        $notificationEvent = null;
        $message = null;
        $model = new WorkOnHoliday();
        $model->id = $id;
        switch ($role) {
            case 2:
                $model->recommendedRemarks = $remarks;
                $model->recommendedDate = Helper::getcurrentExpressionDate();
                $model->recommendedBy = $this->employeeId;
                $model->status = $approve ? "RC" : "R";
                $message = $approve ? "Work on Holiday Request Recommended" : "Training Request Rejected";
                $notificationEvent = $approve ? NotificationEvents::WORKONHOLIDAY_RECOMMEND_ACCEPTED : NotificationEvents::WORKONHOLIDAY_RECOMMEND_REJECTED;
                break;
            case 4:
                $model->recommendedDate = Helper::getcurrentExpressionDate();
                $model->recommendedBy = $this->employeeId;
            case 3:
                $model->approvedRemarks = $remarks;
                $model->approvedDate = Helper::getcurrentExpressionDate();
                $model->approvedBy = $this->employeeId;
                $model->status = $approve ? "AP" : "R";
                $message = $approve ? "Work on Holiday Request Approved" : "Work on Holiday Request Rejected";
                $notificationEvent = $approve ? NotificationEvents::WORKONHOLIDAY_APPROVE_ACCEPTED : NotificationEvents::WORKONHOLIDAY_APPROVE_REJECTED;
                break;
        }
        $this->repository->edit($model, $id);
        $this->repository->wohReward($id);
        if ($enableFlashNotification) {
            $this->flashmessenger()->addMessage($message);
        }
        try {
            HeadNotification::pushNotification($notificationEvent, $model, $this->adapter, $this);
        } catch (Exception $e) {
            $this->flashmessenger()->addMessage($e->getMessage());
        }
    }

    public function addAction()
    {
        $this->initializeForm(WorkOnHolidayForm::class);
        $request = $this->getRequest();

        $model = new WorkOnHoliday();
        if ($request->isPost()) {
            $postData = $request->getPost();
            $this->form->setData($request->getPost());
            if ($this->form->isValid()) {
                $workOnHolidayRepository = new WorkOnHolidayRepository($this->adapter);
                $model->exchangeArrayFromForm($this->form->getData());
                $model->id = ((int) Helper::getMaxId($this->adapter, WorkOnHoliday::TABLE_NAME, WorkOnHoliday::ID)) + 1;
                $model->requestedDate = Helper::getcurrentExpressionDate();
                //                $model->status = 'RQ';
                $model->status = ($postData['applyStatus'] == 'AP') ? 'AP' : 'RQ';
                $workOnHolidayRepository->add($model);
                if ($model->status == 'RQ') {
                    $this->flashmessenger()->addMessage("Work on Holiday Request Successfully added!!!");
                    try {
                        HeadNotification::pushNotification(NotificationEvents::WORKONHOLIDAY_APPLIED, $model, $this->adapter, $this);
                    } catch (Exception $e) {
                        $this->flashmessenger()->addMessage($e->getMessage());
                    }
                } else {
                    $this->repository->wohReward($model->id);
                    $this->flashmessenger()->addMessage("Work on Holiday Approval Successfully added!!!");
                }
                return $this->redirect()->toRoute("holidayWorkApprove", [
                    'Controller' => 'HolidayWorkApproveController',
                    'action' => 'status'
                ]);
            }
        }

        $applyOptionValues = [
            'RQ' => 'Pending',
            'AP' => 'Approved'
        ];
        $applyOption = $this->getSelectElement(['name' => 'applyStatus', 'id' => 'applyStatus', 'class' => 'form-control', 'label' => 'Type'], $applyOptionValues);
        $employees = EntityHelper::getRAWiseEmployeeList($this->adapter, $this->employeeId);

        return Helper::addFlashMessagesToArray($this, [
            'form' => $this->form,
            'applyOption' => $applyOption,
            'employees' => $employees['data'],
            'approvers' => $employees['approver'],
            'holidays' => EntityHelper::getTableKVListWithSortOption($this->adapter, Holiday::TABLE_NAME, Holiday::HOLIDAY_ID, [Holiday::HOLIDAY_ENAME], ["STATUS" => 'E'], "HOLIDAY_ENAME", "ASC", null, false, true)
        ]);
    }

    public function pullHolidaysForEmployeeAction()
    {
        try {
            $request = $this->getRequest();
            $data = $request->getPost();

            $employeeId = $data['employeeId'];
            $holidayRepo = new WorkOnHolidayStatusRepository($this->adapter);
            $holidayResult = Helper::extractDbData($holidayRepo->getAttendedHolidayList($employeeId));

            return new JsonModel(['success' => true, 'data' => $holidayResult, 'message' => null]);
        } catch (Exception $e) {
            return new JsonModel(['success' => false, 'data' => null, 'message' => $e->getMessage()]);
        }
    }
}
