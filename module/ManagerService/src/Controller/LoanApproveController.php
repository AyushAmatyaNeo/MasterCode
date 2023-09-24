<?php

namespace ManagerService\Controller;

use Application\Custom\CustomViewModel;
use Application\Helper\EntityHelper;
use Application\Helper\Helper;
use Exception;
use Loan\Repository\LoanStatusRepository;
use ManagerService\Repository\LoanApproveRepository;
use Notification\Controller\HeadNotification;
use Notification\Model\NotificationEvents;
use SelfService\Form\LoanRequestForm;
use SelfService\Model\LoanRequest;
use SelfService\Repository\LoanRequestRepository;
use Setup\Model\Loan;
use SelfService\Model\LoanRequest as LoanRequestModel;
use Zend\Authentication\AuthenticationService;
use Zend\Db\Adapter\AdapterInterface;
use Zend\Form\Annotation\AnnotationBuilder;
use Zend\Form\Element\Select;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\JsonModel;

class LoanApproveController extends AbstractActionController
{

    private $loanApproveRepository;
    private $employeeId;
    private $adapter;
    private $form;

    public function __construct(AdapterInterface $adapter)
    {
        $this->adapter = $adapter;
        $this->loanApproveRepository = new LoanApproveRepository($adapter);
        $this->loanRequesteRepository = new LoanRequestRepository($adapter);
        $auth = new AuthenticationService();
        $this->employeeId = $auth->getStorage()->read()['employee_id'];
    }

    public function initializeForm()
    {
        $builder = new AnnotationBuilder();
        $form = new LoanRequestForm();
        $this->form = $builder->createForm($form);
    }

    public function indexAction()
    {
        $loanApprove = $this->getAllList();
        return Helper::addFlashMessagesToArray($this, ['loanApprove' => $loanApprove, 'id' => $this->employeeId]);
    }

    public function viewAction()
    {
        $this->initializeForm();

        $id = (int) $this->params()->fromRoute('id');
        $role = $this->params()->fromRoute('role');

        if ($id === 0) {
            return $this->redirect()->toRoute("loanApprove");
        }
        $loanRequestModel = new LoanRequest();
        $request = $this->getRequest();

        $detail = $this->loanApproveRepository->fetchById($id);
        $status = $detail['STATUS'];
        $approvedDT = $detail['APPROVED_DATE'];

        $requestedEmployeeID = $detail['EMPLOYEE_ID'];
        $employeeName = $detail['FULL_NAME'];
        $authRecommender = $detail['RECOMMENDED_BY_NAME'] == null ? $detail['RECOMMENDER_NAME'] : $detail['RECOMMENDED_BY_NAME'];
        $authApprover = $detail['APPROVED_BY_NAME'] == null ? $detail['APPROVER_NAME'] : $detail['APPROVED_BY_NAME'];
        $recommenderId = $detail['RECOMMENDED_BY'] == null ? $detail['RECOMMENDER_ID'] : $detail['RECOMMENDED_BY'];
        if (!$request->isPost()) {
            $loanRequestModel->exchangeArrayFromDB($detail);
            $this->form->bind($loanRequestModel);
        } else {
            $getData = $request->getPost();
            $action = $getData->submit;

            if ($role == 2) {
                $loanRequestModel->recommendedDate = Helper::getcurrentExpressionDate();
                $loanRequestModel->recommendedBy = $this->employeeId;
                if ($action == "Reject") {
                    $loanRequestModel->status = "R";
                    $this->flashmessenger()->addMessage("Loan Request Rejected!!!");
                } else if ($action == "Approve") {
                    $loanRequestModel->status = "RC";
                    $this->flashmessenger()->addMessage("Loan Request Approved!!!");
                    $this->loanApproveRepository->addToDetails($id);
                }
                $loanRequestModel->recommendedRemarks = $getData->recommendedRemarks;
                $this->loanApproveRepository->edit($loanRequestModel, $id);
                $loanRequestModel->loanRequestId = $id;
                try {
                    HeadNotification::pushNotification(($loanRequestModel->status == 'RC') ? NotificationEvents::LOAN_RECOMMEND_ACCEPTED : NotificationEvents::LOAN_RECOMMEND_REJECTED, $loanRequestModel, $this->adapter, $this);
                } catch (Exception $e) {
                    $this->flashmessenger()->addMessage($e->getMessage());
                }
            } else if ($role == 3 || $role == 4) {
                $loanRequestModel->approvedDate = Helper::getcurrentExpressionDate();
                $loanRequestModel->approvedBy = $this->employeeId;
                if ($action == "Reject") {
                    $loanRequestModel->status = "R";
                    $this->flashmessenger()->addMessage("Loan Request Rejected!!!");
                } else if ($action == "Approve") {
                    $loanRequestModel->status = "AP";
                    $this->flashmessenger()->addMessage("Loan Request Approved");
                    $this->loanApproveRepository->addToDetails($id);
                }
                if ($role == 4) {
                    $loanRequestModel->recommendedBy = $this->employeeId;
                    $loanRequestModel->recommendedDate = Helper::getcurrentExpressionDate();
                }
                $loanRequestModel->approvedRemarks = $getData->approvedRemarks;
                $this->loanApproveRepository->edit($loanRequestModel, $id);
                $loanRequestModel->loanRequestId = $id;
                try {
                    HeadNotification::pushNotification(($loanRequestModel->status == 'AP') ? NotificationEvents::LOAN_APPROVE_ACCEPTED : NotificationEvents::LOAN_APPROVE_REJECTED, $loanRequestModel, $this->adapter, $this);
                } catch (Exception $e) {
                    $this->flashmessenger()->addMessage($e->getMessage());
                }
            }
            return $this->redirect()->toRoute("loanApprove");
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
            'loans' => EntityHelper::getTableKVListWithSortOption($this->adapter, Loan::TABLE_NAME, Loan::LOAN_ID, [Loan::LOAN_NAME], [Loan::STATUS => "E"], Loan::LOAN_ID, "ASC", null, false, true)
        ]);
    }

    public function statusAction()
    {
        $loanFormElement = new Select();
        $loanFormElement->setName("loan");
        $loans = EntityHelper::getTableKVListWithSortOption($this->adapter, Loan::TABLE_NAME, Loan::LOAN_ID, [Loan::LOAN_NAME], [Loan::STATUS => 'E'], null, null, null, false, true);
        $loans1 = [-1 => "All"] + $loans;
        $loanFormElement->setValueOptions($loans1);
        $loanFormElement->setAttributes(["id" => "loanId", "class" => "form-control reset-field"]);
        $loanFormElement->setLabel("Loan Type");

        $loanStatus = [
            '-1' => 'All Status',
            'RQ' => 'Pending',
            'RC' => 'Recommended',
            'AP' => 'Approved',
            'R' => 'Rejected'
        ];
        $loanStatusFormElement = new Select();
        $loanStatusFormElement->setName("loanStatus");
        $loanStatusFormElement->setValueOptions($loanStatus);
        $loanStatusFormElement->setAttributes(["id" => "loanRequestStatusId", "class" => "form-control reset-field"]);
        $loanStatusFormElement->setLabel("Status");

        return Helper::addFlashMessagesToArray($this, [
            'loans' => $loanFormElement,
            'loanStatus' => $loanStatusFormElement,
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
            $action;
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
                $this->adapter->getDriver()->getConnection()->beginTransaction();
                try {
                    $loanRequestRepository = new LoanRequestRepository($this->adapter);

                    foreach ($postData as $data) {
                        $loanRequestModel = new LoanRequest();
                        $id = $data['id'];
                        $role = $data['role'];

                        if ($role == 2) {
                            $loanRequestModel->recommendedDate = Helper::getcurrentExpressionDate();
                            $loanRequestModel->recommendedBy = $this->employeeId;
                            if ($action == "Reject") {
                                $loanRequestModel->status = "R";
                            } else if ($action == "Approve") {
                                $loanRequestModel->status = "RC";
                            }

                            $this->loanApproveRepository->edit($loanRequestModel, $id);
                            $loanRequestModel->loanRequestId = $id;
                            try {
                                HeadNotification::pushNotification(($loanRequestModel->status == 'RC') ? NotificationEvents::LOAN_RECOMMEND_ACCEPTED : NotificationEvents::LOAN_RECOMMEND_REJECTED, $loanRequestModel, $this->adapter, $this);
                            } catch (Exception $e) {
                            }
                        } else if ($role == 3 || $role == 4) {
                            $loanRequestModel->approvedDate = Helper::getcurrentExpressionDate();
                            $loanRequestModel->approvedBy = $this->employeeId;
                            if ($action == "Reject") {
                                $loanRequestModel->status = "R";
                            } else if ($action == "Approve") {
                                $loanRequestModel->status = "AP";
                            }
                            if ($role == 4) {
                                $loanRequestModel->recommendedBy = $this->employeeId;
                                $loanRequestModel->recommendedDate = Helper::getcurrentExpressionDate();
                            }
                            $this->loanApproveRepository->edit($loanRequestModel, $id);
                            $loanRequestModel->loanRequestId = $id;
                            try {
                                HeadNotification::pushNotification(($loanRequestModel->status == 'AP') ? NotificationEvents::LOAN_APPROVE_ACCEPTED : NotificationEvents::LOAN_APPROVE_REJECTED, $loanRequestModel, $this->adapter, $this);
                            } catch (Exception $e) {
                            }
                        }
                    }
                    $this->adapter->getDriver()->getConnection()->commit();
                } catch (Exception $ex) {
                    $this->adapter->getDriver()->getConnection()->rollback();
                }
            }
            $listData = $this->getAllList();
            return new CustomViewModel(['success' => true, 'data' => $listData]);
        } catch (Exception $e) {
            return new CustomViewModel(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    public function getAllList()
    {
        $list = $this->loanApproveRepository->getAllRequest($this->employeeId);
        return Helper::extractDbData($list);
    }

    public function pullLoanRequestStatusListAction()
    {
        try {
            $request = $this->getRequest();
            $data = $request->getPost();
            $data['recomApproveId'] = $this->employeeId;
            $loanStatusRepository = new LoanStatusRepository($this->adapter);
            $result = $loanStatusRepository->getFilteredRecord($data, $data['recomApproveId']);

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
        $this->initializeForm();
        $request = $this->getRequest();
        $model = new LoanRequestModel();

        if ($request->isPost()) {
            $this->form->setData($request->getPost());
            if ($this->form->isValid()) {
                $model->exchangeArrayFromForm($this->form->getData());
                $model->loanRequestId = ((int) Helper::getMaxId($this->adapter, LoanRequestModel::TABLE_NAME, LoanRequestModel::LOAN_REQUEST_ID)) + 1;
                $model->requestedDate = Helper::getcurrentExpressionDate();
                $model->status = 'AP';
                $model->printFlag = 'Y';
                $model->deductOnSalary = 'Y';
                /*if($model->loanId = 6){
                    $model->interestRate = 0;   
                } */
                $this->loanRequesteRepository->add($model);
                $this->loanApproveRepository->addToDetails($model->loanRequestId);
                $this->flashmessenger()->addMessage("Loan Request Successfully added!!!");
                return $this->redirect()->toRoute('loanApprove', [
                    'controller' => 'LoanApproveController',
                    'action' =>  'status'
                ]);
            }
        }
        $rateDetails = $this->loanRequesteRepository->getLoanDetails();
        $employees = EntityHelper::getRAWiseEmployeeList($this->adapter, $this->employeeId);

        return Helper::addFlashMessagesToArray($this, [
            'form' => $this->form,
            'rateDetails' => Helper::extractDbData($rateDetails),
            'employees' => $employees['data'],
            // 'employees' => EntityHelper::getTableKVListWithSortOption($this->adapter, "HRIS_EMPLOYEES", "EMPLOYEE_ID", ["EMPLOYEE_CODE", "FIRST_NAME", "MIDDLE_NAME", "LAST_NAME"], ["STATUS" => 'E', 'RETIRED_FLAG' => 'N'], "FIRST_NAME", "ASC", " ", FALSE, TRUE, $this->employeeId),
            'loans' => EntityHelper::getTableKVListWithSortOption($this->adapter, Loan::TABLE_NAME, Loan::LOAN_ID, [Loan::LOAN_NAME], [Loan::STATUS => "E"], Loan::LOAN_ID, "ASC", NULL, FALSE, TRUE)
        ]);
    }
}
