<?php

namespace SelfService\Controller;

use Application\Custom\CustomViewModel;
use Application\Helper\Helper;
use Application\Helper\LoanAdvanceHelper;
use Exception;
use Notification\Controller\HeadNotification;
use Notification\Model\NotificationEvents;
use SelfService\Form\LoanRequestForm;
use SelfService\Model\LoanRequest as LoanRequestModel;
use SelfService\Repository\LoanRequestRepository;
use Setup\Repository\EmployeeRepository;
use Setup\Repository\RecommendApproveRepository;
use Zend\Authentication\Storage\StorageInterface;
use Zend\Db\Adapter\AdapterInterface;
use Zend\Form\Annotation\AnnotationBuilder;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\JsonModel;

class LoanRequest extends AbstractActionController {

    private $form;
    private $adapter;
    private $repository;
    private $employeeId;
    private $recommender;
    private $approver;
    private $storageData;
    private $acl;

    public function __construct(AdapterInterface $adapter, StorageInterface $storage) {
        $this->adapter = $adapter;
        $this->repository = new LoanRequestRepository($adapter);
        $this->storageData = $storage->read();
        $this->employeeId = $this->storageData['employee_id'];
        $this->acl = $this->storageData['acl'];
    }

    public function initializeForm() {
        $builder = new AnnotationBuilder();
        $form = new LoanRequestForm();
        $this->form = $builder->createForm($form);
    }

    public function getRecommendApprover() {
        $recommendApproveRepository = new RecommendApproveRepository($this->adapter);
        $empRecommendApprove = $recommendApproveRepository->fetchById($this->employeeId);

        if ($empRecommendApprove != null) {
            $this->recommender = $empRecommendApprove['RECOMMEND_BY'];
            $this->approver = $empRecommendApprove['APPROVED_BY'];
        } else {
            $result = $this->recommendApproveList();
            if (count($result['recommender']) > 0) {
                $this->recommender = $result['recommender'][0]['id'];
            } else {
                $this->recommender = null;
            }
            if (count($result['approver']) > 0) {
                $this->approver = $result['approver'][0]['id'];
            } else {
                $this->approver = null;
            }
        }
    }

    public function indexAction() {
        $request = $this->getRequest();
        if ($request->isPost()) {
            try {
                $this->getRecommendApprover();
                $result = $this->repository->getAllByEmployeeId($this->employeeId);
                $fullName = function($id) {
                    $empRepository = new EmployeeRepository($this->adapter);
                    $empDtl = $empRepository->fetchById($id);
                    return $empDtl['FULL_NAME'];
                };

                $recommenderName = $fullName($this->recommender);
                $approverName = $fullName($this->approver);

                $list = [];
                $getValue = function($status) {
                    if ($status == "RQ") {
                        return "Pending";
                    } else if ($status == 'RC') {
                        return "Recommended";
                    } else if ($status == "R") {
                        return "Rejected";
                    } else if ($status == "AP") {
                        return "Approved";
                    } else if ($status == "C") {
                        return "Cancelled";
                    }
                };
                $getAction = function($status) {
                    if ($status == "RQ") {
                        return ["delete" => 'Cancel Request'];
                    } else {
                        return ["view" => 'View'];
                    }
                };
                foreach ($result as $row) {
                    $status = $getValue($row['STATUS']);
                    $action = $getAction($row['STATUS']);
                    $statusID = $row['STATUS'];
                    $approvedDT = $row['APPROVED_DATE'];
                    $MN1 = ($row['MN1'] != null) ? " " . $row['MN1'] . " " : " ";
                    $recommended_by = $row['FN1'] . $MN1 . $row['LN1'];
                    $MN2 = ($row['MN2'] != null) ? " " . $row['MN2'] . " " : " ";
                    $approved_by = $row['FN2'] . $MN2 . $row['LN2'];
                    $authRecommender = ($statusID == 'RQ' || $statusID == 'C') ? $recommenderName : $recommended_by;
                    $authApprover = ($statusID == 'RC' || $statusID == 'RQ' || $statusID == 'C' || ($statusID == 'R' && $approvedDT == null)) ? $approverName : $approved_by;

                    $new_row = array_merge($row, [
                        'RECOMMENDER_NAME' => $authRecommender,
                        'APPROVER_NAME' => $authApprover,
                        'STATUS' => $status,
                        'ACTION' => key($action),
                        'ACTION_TEXT' => $action[key($action)]
                    ]);
                    if ($statusID == 'RQ') {
                        $new_row['ALLOW_TO_EDIT'] = 1;
                    } else {
                        $new_row['ALLOW_TO_EDIT'] = 0;
                    }
                    array_push($list, $new_row);
                }

                return new CustomViewModel(['success' => true, 'data' => $list, 'error' => '']);
            } catch (Exception $e) {
                return new CustomViewModel(['success' => false, 'data' => [], 'error' => $e->getMessage()]);
            }
        }
        return Helper::addFlashMessagesToArray($this, []);
    }

    public function addAction() {
        $this->initializeForm();
        $request = $this->getRequest();

        $model = new LoanRequestModel();
        if ($request->isPost()) {
            $this->form->setData($request->getPost());
            if ($this->form->isValid()) {
                $model->exchangeArrayFromForm($this->form->getData());
                $model->loanRequestId = ((int) Helper::getMaxId($this->adapter, LoanRequestModel::TABLE_NAME, LoanRequestModel::LOAN_REQUEST_ID)) + 1;
                $model->employeeId = $this->employeeId;
                $model->requestedDate = Helper::getcurrentExpressionDate();
                $model->status = 'RQ';
                $model->deductOnSalary = 'Y';
                $this->repository->add($model);
                $this->flashmessenger()->addMessage("Loan Request Successfully added!!!");
                try {
                    HeadNotification::pushNotification(NotificationEvents::LOAN_APPLIED, $model, $this->adapter, $this);
                } catch (Exception $e) {
                    $this->flashmessenger()->addMessage($e->getMessage());
                }
                return $this->redirect()->toRoute("loanRequest");
            }
        }
        return Helper::addFlashMessagesToArray($this, [
                    'employeeId' => $this->employeeId,
                    'form' => $this->form,
                    'loans' => LoanAdvanceHelper::getLoanList($this->adapter, $this->employeeId)
        ]);
    }

    public function deleteAction() {
        $id = (int) $this->params()->fromRoute("id");
        if (!$id) {
            return $this->redirect()->toRoute('loanRequest');
        }
        $this->repository->delete($id);
        $this->flashmessenger()->addMessage("Loan Request Successfully Cancelled!!!");
        return $this->redirect()->toRoute('loanRequest');
    }

    public function viewAction() {
        $this->initializeForm();
        $this->getRecommendApprover();
        $id = (int) $this->params()->fromRoute('id');

        if ($id === 0) {
            return $this->redirect()->toRoute("loanRequest");
        }
        $fullName = function($id) {
            $empRepository = new EmployeeRepository($this->adapter);
            $empDtl = $empRepository->fetchById($id);
            $empMiddleName = ($empDtl['MIDDLE_NAME'] != null) ? " " . $empDtl['MIDDLE_NAME'] . " " : " ";
            return $empDtl['FIRST_NAME'] . $empMiddleName . $empDtl['LAST_NAME'];
        };

        $recommenderName = $fullName($this->recommender);
        $approverName = $fullName($this->approver);

        $model = new LoanRequestModel();
        $detail = $this->repository->fetchById($id);
        $status = $detail['STATUS'];
        $approvedDT = $detail['APPROVED_DATE'];
        $recommended_by = $fullName($detail['RECOMMENDED_BY']);
        $approved_by = $fullName($detail['APPROVED_BY']);
        $authRecommender = ($status == 'RQ' || $status == 'C') ? $recommenderName : $recommended_by;
        $authApprover = ($status == 'RC' || $status == 'RQ' || $status == 'C' || ($status == 'R' && $approvedDT == null)) ? $approverName : $approved_by;

        $model->exchangeArrayFromDB($detail);
        $this->form->bind($model);

        $employeeName = $fullName($detail['EMPLOYEE_ID']);

        return Helper::addFlashMessagesToArray($this, [
                    'form' => $this->form,
                    'employeeName' => $employeeName,
                    'status' => $detail['STATUS'],
                    'requestedDate' => $detail['REQUESTED_DATE'],
                    'recommender' => $authRecommender,
                    'approver' => $authApprover,
                    'loans' => LoanAdvanceHelper::getLoanList($this->adapter, $this->employeeId)
        ]);
    }

    public function recommendApproveList() {
        $employeeRepository = new EmployeeRepository($this->adapter);
        $recommendApproveRepository = new RecommendApproveRepository($this->adapter);
        $employeeId = $this->employeeId;
        $employeeDetail = $employeeRepository->fetchById($employeeId);
        $branchId = $employeeDetail['BRANCH_ID'];
        $departmentId = $employeeDetail['DEPARTMENT_ID'];
        $designations = $recommendApproveRepository->getDesignationList($employeeId);

        $recommender = array();
        $approver = array();
        foreach ($designations as $key => $designationList) {
            $withinBranch = $designationList['WITHIN_BRANCH'];
            $withinDepartment = $designationList['WITHIN_DEPARTMENT'];
            $designationId = $designationList['DESIGNATION_ID'];
            $employees = $recommendApproveRepository->getEmployeeList($withinBranch, $withinDepartment, $designationId, $branchId, $departmentId);

            if ($key == 1) {
                $i = 0;
                foreach ($employees as $employeeList) {
                    // array_push($recommender,$employeeList);
                    $recommender [$i]["id"] = $employeeList['EMPLOYEE_ID'];
                    $recommender [$i]["name"] = $employeeList['FIRST_NAME'] . " " . $employeeList['MIDDLE_NAME'] . " " . $employeeList['LAST_NAME'];
                    $i++;
                }
            } else if ($key == 2) {
                $i = 0;
                foreach ($employees as $employeeList) {
                    //array_push($approver,$employeeList);
                    $approver [$i]["id"] = $employeeList['EMPLOYEE_ID'];
                    $approver [$i]["name"] = $employeeList['FIRST_NAME'] . " " . $employeeList['MIDDLE_NAME'] . " " . $employeeList['LAST_NAME'];
                    $i++;
                }
            }
        }
        $responseData = [
            "recommender" => $recommender,
            "approver" => $approver
        ];
        return $responseData;
    }

    public function pullLoanListAction() {
        try {
            $request = $this->getRequest();
            $data = $request->getPost();

            $employeeId = $data['employeeId'];
            $loanList = LoanAdvanceHelper::getLoanList($this->adapter, $employeeId);

            return new JsonModel(['success' => true, 'data' => $loanList, 'message' => null]);
        } catch (Exception $e) {
            return new JsonModel(['success' => false, 'data' => null, 'message' => $e->getMessage()]);
        }
    }

}
