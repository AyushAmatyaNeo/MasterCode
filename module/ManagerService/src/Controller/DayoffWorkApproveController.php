<?php

namespace ManagerService\Controller;

use Application\Controller\HrisController;
use Application\Custom\CustomViewModel;
use Application\Helper\EntityHelper;
use Application\Helper\Helper;
use Exception;
use ManagerService\Repository\DayoffWorkApproveRepository;
use Notification\Controller\HeadNotification;
use Notification\Model\NotificationEvents;
use SelfService\Form\WorkOnDayoffForm;
use SelfService\Model\WorkOnDayoff;
use SelfService\Repository\WorkOnDayoffRepository;
use WorkOnDayoff\Repository\WorkOnDayoffStatusRepository;
use Zend\Authentication\Storage\StorageInterface;
use Zend\Db\Adapter\AdapterInterface;
use Zend\View\Model\JsonModel;

class DayoffWorkApproveController extends HrisController
{

    public function __construct(AdapterInterface $adapter, StorageInterface $storage)
    {
        parent::__construct($adapter, $storage);
        $this->initializeRepository(DayoffWorkApproveRepository::class);
        $this->initializeForm(WorkOnDayoffForm::class);
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
        $id = (int) $this->params()->fromRoute('id');
        $role = $this->params()->fromRoute('role');

        if ($id === 0) {
            return $this->redirect()->toRoute("dayoffWorkApprove");
        }
        $workOnDayoffModel = new WorkOnDayoff();
        $request = $this->getRequest();

        $detail = $this->repository->fetchById($id);
        $status = $detail['STATUS'];
        $approvedDT = $detail['APPROVED_DATE'];

        $requestedEmployeeID = $detail['EMPLOYEE_ID'];
        $employeeName = $detail['FULL_NAME'];
        $authRecommender = $detail['RECOMMENDED_BY_NAME'] == null ? $detail['RECOMMENDER_NAME'] : $detail['RECOMMENDED_BY_NAME'];
        $authApprover = $detail['APPROVED_BY_NAME'] == null ? $detail['APPROVER_NAME'] : $detail['APPROVED_BY_NAME'];
        $recommenderId = $detail['RECOMMENDED_BY'] == null ? $detail['RECOMMENDER_ID'] : $detail['RECOMMENDED_BY'];
        if (!$request->isPost()) {
            $workOnDayoffModel->exchangeArrayFromDB($detail);
            $this->form->bind($workOnDayoffModel);
        } else {
            $getData = $request->getPost();
            $action = $getData->submit;

            if ($role == 2) {
                $workOnDayoffModel->recommendedDate = Helper::getcurrentExpressionDate();
                $workOnDayoffModel->recommendedBy = $this->employeeId;
                if ($action == "Reject") {
                    $workOnDayoffModel->status = "R";
                    $this->flashmessenger()->addMessage("Work on Day-off Request Rejected!!!");
                } else if ($action == "Approve") {
                    $workOnDayoffModel->status = "RC";
                    $this->flashmessenger()->addMessage("Work on Day-off Request Approved!!!");
                }
                $workOnDayoffModel->recommendedRemarks = $getData->recommendedRemarks;
                $this->repository->edit($workOnDayoffModel, $id);
                try {
                    $workOnDayoffModel->id = $id;
                    HeadNotification::pushNotification(($workOnDayoffModel->status == 'RC') ? NotificationEvents::WORKONDAYOFF_RECOMMEND_ACCEPTED : NotificationEvents::WORKONDAYOFF_RECOMMEND_REJECTED, $workOnDayoffModel, $this->adapter, $this);
                } catch (Exception $e) {
                    $this->flashmessenger()->addMessage($e->getMessage());
                }
            } else if ($role == 3 || $role == 4) {
                $workOnDayoffModel->approvedDate = Helper::getcurrentExpressionDate();
                $workOnDayoffModel->approvedBy = $this->employeeId;
                if ($action == "Reject") {
                    $workOnDayoffModel->status = "R";
                    $this->flashmessenger()->addMessage("Work on Day-off Request Rejected!!!");
                } else if ($action == "Approve") {
                    try {
                        $this->wodApproveAction($detail);
                        $this->flashmessenger()->addMessage("Work on Day-off Request Approved");
                    } catch (Exception $e) {
                        $this->flashmessenger()->addMessage("Work on Day-off Request Approved but reward is not provided as employee position is not set.");
                    }
                    $workOnDayoffModel->status = "AP";
                }
                if ($role == 4) {
                    $workOnDayoffModel->recommendedBy = $this->employeeId;
                    $workOnDayoffModel->recommendedDate = Helper::getcurrentExpressionDate();
                }

                $workOnDayoffModel->approvedRemarks = $getData->approvedRemarks;
                $this->repository->edit($workOnDayoffModel, $id);

                try {
                    $workOnDayoffModel->id = $id;
                    HeadNotification::pushNotification(($workOnDayoffModel->status == 'AP') ? NotificationEvents::WORKONDAYOFF_APPROVE_ACCEPTED : NotificationEvents::WORKONDAYOFF_APPROVE_REJECTED, $workOnDayoffModel, $this->adapter, $this);
                } catch (Exception $e) {
                    $this->flashmessenger()->addMessage($e->getMessage());
                }
            }
            return $this->redirect()->toRoute("dayoffWorkApprove");
        }
        return Helper::addFlashMessagesToArray($this, [
            'form' => $this->form,
            'id' => $id,
            'employeeName' => $employeeName,
            'requestedDate' => $detail['REQUESTED_DATE'],
            'role' => $role,
            'recommender' => $authRecommender,
            'approver' => $authApprover,
            'status' => $status,
            'recommendedBy' => $recommenderId,
            'approvedDT' => $approvedDT,
            'employeeId' => $this->employeeId,
            'requestedEmployeeId' => $requestedEmployeeID,
        ]);
    }

    public function statusAction()
    {
        $statusSE = $this->getStatusSelectElement(['name' => 'status', "id" => "requestStatusId", "class" => "form-control reset-field", 'label' => 'Status']);
        return $this->stickFlashMessagesTo([
            'status' => $statusSE,
            'recomApproveId' => $this->employeeId,
            'searchValues' => EntityHelper::getSearchData($this->adapter),
        ]);
    }

    private function wodApproveAction($detail)
    {
        $this->repository->wodReward($detail['ID']);
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
                    $workOnDayoffModel = new WorkOnDayoff();
                    $id = $data['id'];
                    $role = $data['role'];
                    $detail = $this->repository->fetchById($id);

                    if ($role == 2) {
                        $workOnDayoffModel->recommendedDate = Helper::getcurrentExpressionDate();
                        $workOnDayoffModel->recommendedBy = $this->employeeId;
                        if ($action == "Reject") {
                            $workOnDayoffModel->status = "R";
                        } else if ($action == "Approve") {
                            $workOnDayoffModel->status = "RC";
                        }
                        $this->repository->edit($workOnDayoffModel, $id);
                        try {
                            $workOnDayoffModel->id = $id;
                            HeadNotification::pushNotification(($workOnDayoffModel->status == 'RC') ? NotificationEvents::WORKONDAYOFF_RECOMMEND_ACCEPTED : NotificationEvents::WORKONDAYOFF_RECOMMEND_REJECTED, $workOnDayoffModel, $this->adapter, $this);
                        } catch (Exception $e) {
                            throw new Exception($e->getMessage());
                        }
                    } else if ($role == 3 || $role == 4) {
                        $workOnDayoffModel->approvedDate = Helper::getcurrentExpressionDate();
                        $workOnDayoffModel->approvedBy = $this->employeeId;
                        if ($action == "Reject") {
                            $workOnDayoffModel->status = "R";
                        } else if ($action == "Approve") {
                            try {
                                $this->wodApproveAction($detail);
                            } catch (Exception $e) {
                            }
                            $workOnDayoffModel->status = "AP";
                        }
                        if ($role == 4) {
                            $workOnDayoffModel->recommendedBy = $this->employeeId;
                            $workOnDayoffModel->recommendedDate = Helper::getcurrentExpressionDate();
                        }

                        $this->repository->edit($workOnDayoffModel, $id);

                        try {
                            $workOnDayoffModel->id = $id;
                            HeadNotification::pushNotification(($workOnDayoffModel->status == 'AP') ? NotificationEvents::WORKONDAYOFF_APPROVE_ACCEPTED : NotificationEvents::WORKONDAYOFF_APPROVE_REJECTED, $workOnDayoffModel, $this->adapter, $this);
                        } catch (Exception $e) {
                            $this->flashmessenger()->addMessage($e->getMessage());
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

    public function pullDayoffWorkRequestStatusListAction()
    {
        try {
            $request = $this->getRequest();
            $data = $request->getPost();


            $dayoffWorkStatusRepo = new WorkOnDayoffStatusRepository($this->adapter);
            $result = $dayoffWorkStatusRepo->getFilteredRecord($data, $data['recomApproveId']);
            $recordList = Helper::extractDbData($result);

            return new JsonModel([
                "success" => "true",
                "data" => $recordList,
            ]);
        } catch (Exception $e) {
            return new JsonModel(['success' => false, 'data' => null, 'message' => $e->getMessage()]);
        }
    }

    public function addAction()
    {
        $this->initializeForm(WorkOnDayoffForm::class);
        $request = $this->getRequest();

        $model = new WorkOnDayoff();
        if ($request->isPost()) {
            $postData = $request->getPost();
            $this->form->setData($request->getPost());
            if ($this->form->isValid()) {
                $model->exchangeArrayFromForm($this->form->getData());
                $model->id = ((int) Helper::getMaxId($this->adapter, WorkOnDayoff::TABLE_NAME, WorkOnDayoff::ID)) + 1;
                $model->requestedDate = Helper::getcurrentExpressionDate();
                //                $model->status = 'RQ';
                $model->status = ($postData['applyStatus'] == 'AP') ? 'AP' : 'RQ';
                $workOnDayoffRepository = new WorkOnDayoffRepository($this->adapter);
                $workOnDayoffRepository->add($model);

                if ($model->status == 'RQ') {
                    $this->flashmessenger()->addMessage("Work on Day-off Request Successfully added!!!");
                    try {
                        HeadNotification::pushNotification(NotificationEvents::WORKONDAYOFF_APPLIED, $model, $this->adapter, $this);
                    } catch (Exception $e) {
                        $this->flashmessenger()->addMessage($e->getMessage());
                    }
                } else {
                    $this->repository->wodReward($model->id);
                    $this->flashmessenger()->addMessage("Work on Day-off Approval Successfully added!!!");
                }
                return $this->redirect()->toRoute("dayoffWorkApprove", [
                    'Controller' => 'DayoffWorkApproveController',
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
            'approvers' => $employees['approver']
        ]);
    }
}
