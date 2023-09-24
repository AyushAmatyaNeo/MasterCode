<?php

namespace Setup\Controller;

use Application\Controller\HrisController;
use Application\Custom\CustomViewModel;
use Application\Helper\EntityHelper;
use Application\Helper\Helper;
use Exception;
use Setup\Form\JobHistoryForm;
use Setup\Model\Branch;
use Setup\Model\Company;
use Setup\Model\Department;
use Setup\Model\Designation;
use Setup\Model\HrEmployees;
use Setup\Model\JobHistory;
use Setup\Model\Position;
use Setup\Model\ServiceEventType;
use Setup\Repository\EmployeeRepository;
use Setup\Repository\JobHistoryRepository;
use Zend\Authentication\Storage\StorageInterface;
use Zend\Db\Adapter\AdapterInterface;
use Zend\Db\Sql\Predicate\Predicate;

class JobHistoryController extends HrisController
{

    public function __construct(AdapterInterface $adapter, StorageInterface $storage)
    {
        parent::__construct($adapter, $storage);
        $this->initializeRepository(JobHistoryRepository::class);
        $this->initializeForm(JobHistoryForm::class);
    }

    public function indexAction()
    {
        $serviceEventTypes = EntityHelper::getTableKVListWithSortOption($this->adapter, ServiceEventType::TABLE_NAME, ServiceEventType::SERVICE_EVENT_TYPE_ID, [ServiceEventType::SERVICE_EVENT_TYPE_NAME], [ServiceEventType::STATUS => 'E'], "SERVICE_EVENT_TYPE_NAME", "ASC", null, [-1 => "All Service Event Type"], true);
        $serviceEventTypeSE = $this->getSelectElement([
            'name' => 'serviceEventType',
            "id" => "serviceEventTypeId1",
            "class" => "form-control",
            "label" => "Service Event Type"
        ], $serviceEventTypes);
        return $this->stickFlashMessagesTo([
            'serviceEventType' => $serviceEventTypeSE,
            'searchValues' => EntityHelper::getSearchData($this->adapter, true),
            'acl' => $this->acl,
            'employeeDetail' => $this->storageData['employee_detail'],
        ]);
    }

    public function addAction()
    {
        $request = $this->getRequest();

        if ($request->isPost()) {
            $this->form->setData($request->getPost());
            if ($this->form->isValid()) {

                $jobHistory = new JobHistory();
                $jobHistory->exchangeArrayFromForm($this->form->getData());
                $jobHistory->jobHistoryId = ((int) Helper::getMaxId($this->adapter, JobHistory::TABLE_NAME, JobHistory::JOB_HISTORY_ID)) + 1;
                $jobHistory->startDate = Helper::getExpressionDate($jobHistory->startDate);
                $jobHistory->endDate = Helper::getExpressionDate($jobHistory->endDate);
                $jobHistory->createdDt = Helper::getcurrentExpressionDate();
                $jobHistory->createdBy = $this->employeeId;
                $jobHistory->status = 'E';
                $jobHistory->retiredFlag = $jobHistory->retiredFlag ? 'Y' : 'N';
                $jobHistory->disabledFlag = $jobHistory->disabledFlag ? 'Y' : 'N';
                $jobHistory->eventDate = Helper::getExpressionDate($jobHistory->eventDate);
                $this->repository->add($jobHistory);
                // $this->repository->updateUser($jobHistory->employeeId,$jobHistory->disabledFlag);
                $this->flashmessenger()->addMessage("Job History Successfully added!!!");
                return $this->redirect()->toRoute("jobHistory");
            }
        }
        return Helper::addFlashMessagesToArray($this, [
            'form' => $this->form,
            'employees' => EntityHelper::getTableKVListWithSortOption($this->adapter, "HRIS_EMPLOYEES", "EMPLOYEE_ID", ["EMPLOYEE_CODE", "FULL_NAME"], ["STATUS" => "E", "RETIRED_FLAG" => "N"], "FIRST_NAME", "ASC", "-", false, true),
            'departments' => EntityHelper::getTableKVListWithSortOption($this->adapter, "HRIS_DEPARTMENTS", "DEPARTMENT_ID", ["DEPARTMENT_NAME"], ["STATUS" => 'E'], "DEPARTMENT_NAME", "ASC", null, true, true),
            'designations' => EntityHelper::getTableKVListWithSortOption($this->adapter, "HRIS_DESIGNATIONS", "DESIGNATION_ID", ["DESIGNATION_TITLE"], ["STATUS" => 'E'], "DESIGNATION_TITLE", "ASC", null, true, true),
            'companies' => EntityHelper::getTableKVListWithSortOption($this->adapter, "HRIS_COMPANY", "COMPANY_ID", ["COMPANY_NAME"], ["STATUS" => 'E'], "COMPANY_NAME", "ASC", null, true, true),
            'branches' => EntityHelper::getTableKVListWithSortOption($this->adapter, "HRIS_BRANCHES", "BRANCH_ID", ["BRANCH_NAME"], ["STATUS" => 'E'], "BRANCH_NAME", "ASC", null, true, true),
            'positions' => EntityHelper::getTableKVListWithSortOption($this->adapter, "HRIS_POSITIONS", "POSITION_ID", ["POSITION_NAME"], ["STATUS" => 'E'], "POSITION_NAME", "ASC", null, true, true),
            'serviceTypes' => EntityHelper::getTableKVListWithSortOption($this->adapter, "HRIS_SERVICE_TYPES", "SERVICE_TYPE_ID", ["SERVICE_TYPE_NAME"], ["STATUS" => 'E'], "SERVICE_TYPE_NAME", "ASC", null, true, true),
            'serviceEventTypes' => EntityHelper::getTableKVListWithSortOption($this->adapter, "HRIS_SERVICE_EVENT_TYPES", "SERVICE_EVENT_TYPE_ID", ["SERVICE_EVENT_TYPE_NAME"], ["STATUS" => 'E'], "SERVICE_EVENT_TYPE_NAME", "ASC", null, false, true),
        ]);
    }

    public function editAction()
    {
        $id = (int) $this->params()->fromRoute("id");
        if ($id === 0) {
            return $this->redirect()->toRoute('jobHistory');
        }

        $request = $this->getRequest();

        $jobHistory = new JobHistory();
        if ($request->isPost()) {
            $this->form->setData($request->getPost());
            if ($this->form->isValid()) {
                $jobHistory->exchangeArrayFromForm($this->form->getData());
                $jobHistory->startDate = Helper::getExpressionDate($jobHistory->startDate);
                $jobHistory->endDate = Helper::getExpressionDate($jobHistory->endDate);
                $jobHistory->modifiedDt = Helper::getcurrentExpressionDate();
                $jobHistory->modifiedBy = $this->employeeId;
                $jobHistory->status = 'E';
                $jobHistory->retiredFlag = $jobHistory->retiredFlag ? 'Y' : 'N';
                $jobHistory->disabledFlag = $jobHistory->disabledFlag ? 'Y' : 'N';
                $jobHistory->eventDate = Helper::getExpressionDate($jobHistory->eventDate);
                // echo '<pre>';print_r(jobHistory);die;
                $this->repository->edit($jobHistory, $id);
                $this->flashmessenger()->addMessage("Job History Successfully Updated!!!");
                return $this->redirect()->toRoute("jobHistory");
            }
        }
        $jobHistoryDetail = $this->repository->fetchById($id);
        $jobHistory->exchangeArrayFromDb($jobHistoryDetail);
        $this->form->bind($jobHistory);

        return Helper::addFlashMessagesToArray(
            $this,
            [
                'id' => $id,
                'form' => $this->form,
                'employees' => EntityHelper::getTableKVListWithSortOption($this->adapter, "HRIS_EMPLOYEES", "EMPLOYEE_ID", ["FIRST_NAME", "MIDDLE_NAME", "LAST_NAME"], null, "FIRST_NAME", "ASC", " ", false, true),
                'departments' => EntityHelper::getTableKVListWithSortOption($this->adapter, "HRIS_DEPARTMENTS", "DEPARTMENT_ID", ["DEPARTMENT_NAME"], ["STATUS" => 'E'], "DEPARTMENT_NAME", "ASC", null, true, true),
                'designations' => EntityHelper::getTableKVListWithSortOption($this->adapter, "HRIS_DESIGNATIONS", "DESIGNATION_ID", ["DESIGNATION_TITLE"], ["STATUS" => 'E'], "DESIGNATION_TITLE", "ASC", null, true, true),
                'companies' => EntityHelper::getTableKVListWithSortOption($this->adapter, "HRIS_COMPANY", "COMPANY_ID", ["COMPANY_NAME"], ["STATUS" => 'E'], "COMPANY_NAME", "ASC", null, true, true),
                'branches' => EntityHelper::getTableKVListWithSortOption($this->adapter, "HRIS_BRANCHES", "BRANCH_ID", ["BRANCH_NAME"], ["STATUS" => 'E'], "BRANCH_NAME", "ASC", null, true, true),
                'positions' => EntityHelper::getTableKVListWithSortOption($this->adapter, "HRIS_POSITIONS", "POSITION_ID", ["POSITION_NAME"], ["STATUS" => 'E'], "POSITION_NAME", "ASC", null, true, true),
                'serviceTypes' => EntityHelper::getTableKVListWithSortOption($this->adapter, "HRIS_SERVICE_TYPES", "SERVICE_TYPE_ID", ["SERVICE_TYPE_NAME"], ["STATUS" => 'E'], "SERVICE_TYPE_NAME", "ASC", null, true, true),
                'serviceEventTypes' => EntityHelper::getTableKVListWithSortOption($this->adapter, "HRIS_SERVICE_EVENT_TYPES", "SERVICE_EVENT_TYPE_ID", ["SERVICE_EVENT_TYPE_NAME"], ["STATUS" => 'E'], "SERVICE_EVENT_TYPE_NAME", "ASC", null, false, true),
                'retiredFlagValue' => $jobHistoryDetail['RETIRED_FLAG'],
                'disabledFlagValue' => $jobHistoryDetail['DISABLED_FLAG'],
            ]
        );
    }

    public function viewAction()
    {
        $id = (int) $this->params()->fromRoute("id");
        if ($id === 0) {
            return $this->redirect()->toRoute('jobHistory');
        }

        $prevAndNextHistory = [];
        if ($id !== 0) {
            $jobHistoryRepo = new JobHistoryRepository($this->adapter);

            $prevJobHistory = $jobHistoryRepo->fetchBeforeJobHistory($id);

            if (sizeof($prevJobHistory) > 0) {
                $prevAndNextHistory['prev'] = $prevJobHistory[0];
            }

            $nextJobHistory = $jobHistoryRepo->fetchAfterJobHistory($id);
            if (sizeof($nextJobHistory) > 0) {
                $prevAndNextHistory['next'] = $nextJobHistory[0];
            }
        }

        $jobHistory = new JobHistory();
        $jobHistoryDetail = $this->repository->fetchById($id);
        $jobHistory->exchangeArrayFromDb($jobHistoryDetail);
        $this->form->bind($jobHistory);

        return Helper::addFlashMessagesToArray(
            $this,
            [
                'form' => $this->form,
                'id' => $id,
                'empId' => EntityHelper::getTableKVList($this->adapter, JobHistory::TABLE_NAME, JobHistory::JOB_HISTORY_ID, [JobHistory::EMPLOYEE_ID], [JobHistory::JOB_HISTORY_ID => $id], null)[$id],
                'messages' => $this->flashmessenger()->getMessages(),
                'employeesAll' => EntityHelper::getTableKVListWithSortOption($this->adapter, "HRIS_EMPLOYEES", "EMPLOYEE_ID", ["FIRST_NAME", "MIDDLE_NAME", "LAST_NAME"], ["STATUS" => "E"], "FIRST_NAME", "ASC", " ", false, true),
                'departments' => EntityHelper::getTableKVListWithSortOption($this->adapter, "HRIS_DEPARTMENTS", "DEPARTMENT_ID", ["DEPARTMENT_NAME"], ["STATUS" => 'E'], "DEPARTMENT_NAME", "ASC", null, true, true),
                'designations' => EntityHelper::getTableKVListWithSortOption($this->adapter, "HRIS_DESIGNATIONS", "DESIGNATION_ID", ["DESIGNATION_TITLE"], ["STATUS" => 'E'], "DESIGNATION_TITLE", "ASC", null, true, true),
                'companies' => EntityHelper::getTableKVListWithSortOption($this->adapter, "HRIS_COMPANY", "COMPANY_ID", ["COMPANY_NAME"], ["STATUS" => 'E'], "COMPANY_NAME", "ASC", null, true, true),
                'branches' => EntityHelper::getTableKVListWithSortOption($this->adapter, "HRIS_BRANCHES", "BRANCH_ID", ["BRANCH_NAME"], ["STATUS" => 'E'], "BRANCH_NAME", "ASC", null, true, true),
                'positions' => EntityHelper::getTableKVListWithSortOption($this->adapter, "HRIS_POSITIONS", "POSITION_ID", ["POSITION_NAME"], ["STATUS" => 'E'], "POSITION_NAME", "ASC", null, true, true),
                'serviceTypes' => EntityHelper::getTableKVListWithSortOption($this->adapter, "HRIS_SERVICE_TYPES", "SERVICE_TYPE_ID", ["SERVICE_TYPE_NAME"], ["STATUS" => 'E'], "SERVICE_TYPE_NAME", "ASC", null, true, true),
                'serviceEventTypes' => EntityHelper::getTableKVListWithSortOption($this->adapter, "HRIS_SERVICE_EVENT_TYPES", "SERVICE_EVENT_TYPE_ID", ["SERVICE_EVENT_TYPE_NAME"], ["STATUS" => 'E'], "SERVICE_EVENT_TYPE_NAME", "ASC", null, false, true),
                'prevAndNextHistory' => $prevAndNextHistory
            ]
        );
    }

    public function deleteAction()
    {
        $id = (int) $this->params()->fromRoute("id");
        if (!$id) {
            return $this->redirect()->toRoute('jobHistory');
        }
        $this->repository->delete($id);
        $this->flashmessenger()->addMessage("Job History Successfully Deleted!!!");
        return $this->redirect()->toRoute("jobHistory");
    }

    public function pullEmployeeDetailWithOptionsAction()
    {
        try {
            $request = $this->getRequest();
            if ($request->isPost()) {
                $postedData = $request->getPost();

                $employeeId = $postedData['employeeId'];
                if (!isset($employeeId)) {
                    throw new Exception("parameter employeeId is required");
                }

                $employeeRepo = new EmployeeRepository($this->adapter);
                $employee = $employeeRepo->fetchById($employeeId);

                $jobHistoryRepo = new JobHistoryRepository($this->adapter);
                $latestJobHistory = $jobHistoryRepo->fetchLatestJobHistory($employeeId);
                if (sizeof($latestJobHistory) != 0) {
                    $employee[HrEmployees::COMPANY_ID] = $latestJobHistory[0][JobHistory::TO_COMPANY_ID];
                    $employee[HrEmployees::BRANCH_ID] = $latestJobHistory[0][JobHistory::TO_BRANCH_ID];
                    $employee[HrEmployees::DEPARTMENT_ID] = $latestJobHistory[0][JobHistory::TO_DEPARTMENT_ID];
                    $employee[HrEmployees::DESIGNATION_ID] = $latestJobHistory[0][JobHistory::TO_DESIGNATION_ID];
                    $employee[HrEmployees::POSITION_ID] = $latestJobHistory[0][JobHistory::TO_POSITION_ID];
                    $employee[HrEmployees::SERVICE_TYPE_ID] = $latestJobHistory[0][JobHistory::TO_SERVICE_TYPE_ID];
                    $employee["LAST_EVENT_DATE"] = $latestJobHistory[0][JobHistory::START_DATE];
                }

                $companyList = EntityHelper::getTableList($this->adapter, Company::TABLE_NAME, [Company::COMPANY_ID, Company::COMPANY_NAME], [Company::COMPANY_ID => $employee[HrEmployees::COMPANY_ID], "1=1"], Predicate::OP_OR);
                $branchList = EntityHelper::getTableList($this->adapter, Branch::TABLE_NAME, [Branch::BRANCH_ID, Branch::BRANCH_NAME], [Branch::COMPANY_ID => $employee[HrEmployees::COMPANY_ID], "1=1"], Predicate::OP_OR);
                $departmentList = EntityHelper::getTableList($this->adapter, Department::TABLE_NAME, [Department::BRANCH_ID, Department::DEPARTMENT_ID, Department::DEPARTMENT_NAME], [Department::COMPANY_ID => $employee[HrEmployees::COMPANY_ID], "1=1"], Predicate::OP_OR);
                $designationList = EntityHelper::getTableList($this->adapter, Designation::TABLE_NAME, [Designation::DESIGNATION_ID, Designation::DESIGNATION_TITLE], [Designation::COMPANY_ID => $employee[HrEmployees::COMPANY_ID], "1=1"], Predicate::OP_OR);
                $positionList = EntityHelper::getTableList($this->adapter, Position::TABLE_NAME, [Position::POSITION_ID, Position::POSITION_NAME], [Position::COMPANY_ID => $employee[HrEmployees::COMPANY_ID], "1=1"], Predicate::OP_OR);

                $data = [
                    'employeeDetail' => $employee,
                    'companyList' => $companyList,
                    'branchList' => $branchList,
                    'departmentList' => $departmentList,
                    'designationList' => $designationList,
                    'positionList' => $positionList
                ];


                return new CustomViewModel(['success' => true, 'data' => $data, 'error' => '']);
            } else {
                throw new Exception("The request should be of type post");
            }
        } catch (Exception $e) {
            return new CustomViewModel(['success' => false, 'data' => [], 'error' => $e->getMessage()]);
        }
    }

    public function getEmployeeLatestServiceAction()
    {
        try {
            $request = $this->getRequest();
            if (!$request->isPost()) {
                throw new Exception("The request should be of type post");
            }

            $data = $request->getPost();

            $fromDate = $data['fromDate'];
            $toDate = $data['toDate'];
            $employeeId = $data['employeeId'];
            $companyId = $data['companyId'];
            $branchId = $data['branchId'];
            $departmentId = $data['departmentId'];
            $designationId = $data['designationId'];
            $positionId = $data['positionId'];
            $serviceTypeId = $data['serviceTypeId'];
            $functionalTypeId = $data['functionalTypeId'];
            $serviceEventTypeId = $data['serviceEventTypeId'];
            $employeeTypeId = $data['employeeTypeId'];

            $result = $this->repository->filter($fromDate, $toDate, $employeeId, $serviceEventTypeId, $companyId, $branchId, $departmentId, $designationId, $positionId, $serviceTypeId, $employeeTypeId, $functionalTypeId);

            $jobHistoryRecord = Helper::extractDbData($result);

            return new CustomViewModel(['success' => true, 'data' => $jobHistoryRecord, 'error' => '']);
        } catch (Exception $e) {
            return new CustomViewModel(['success' => false, 'data' => [], 'error' => $e->getMessage()]);
        }
    }

    public function getPreviousHistoryAction()
    {
        try {
            $request = $this->getRequest();
            if (!$request->isPost()) {
                throw new Exception("The request should be of type post");
            }

            $data = $request->getPost();

            $startDate = $data['startDate'];
            $employeeId = $data['employeeId'];

            $result = $this->repository->fetchBeforeStartDate(Helper::getExpressionDate($startDate)->getExpression(), $employeeId);

            return new CustomViewModel(['success' => true, 'data' => $result, 'error' => '']);
        } catch (Exception $e) {
            return new CustomViewModel(['success' => false, 'data' => [], 'error' => $e->getMessage()]);
        }
    }

    public function getHistoryListAction()
    {
        try {
            $request = $this->getRequest();
            if (!$request->isPost()) {
                throw new Exception("The request should be of type post");
            }

            $data = $request->getPost();

            $employeeId = $data['employeeId'];

            $result = $this->repository->fetchByEmployeeId($employeeId);

            return new CustomViewModel(['success' => true, 'data' => $result, 'error' => '']);
        } catch (Exception $e) {
            return new CustomViewModel(['success' => false, 'data' => [], 'error' => $e->getMessage()]);
        }
    }

    public function newEmployeeAction()
    {
        $serviceEventTypes = EntityHelper::getTableKVListWithSortOption($this->adapter, ServiceEventType::TABLE_NAME, ServiceEventType::SERVICE_EVENT_TYPE_ID, [ServiceEventType::SERVICE_EVENT_TYPE_NAME], [ServiceEventType::STATUS => 'E'], "SERVICE_EVENT_TYPE_NAME", "ASC", null, [-1 => "All Service Event Type"], true);
        $serviceEventTypeSE = $this->getSelectElement([
            'name' => 'serviceEventType',
            "id" => "serviceEventTypeId1",
            "class" => "form-control",
            "label" => "Service Event Type"
        ], $serviceEventTypes);
        return $this->stickFlashMessagesTo([
            'serviceEventType' => $serviceEventTypeSE,
            'searchValues' => EntityHelper::getSearchData($this->adapter, true),
            'acl' => $this->acl,
            'employeeDetail' => $this->storageData['employee_detail'],
        ]);
    }

    public function getNewEmpListAction()
    {
        try {
            $request = $this->getRequest();
            if (!$request->isPost()) {
                throw new Exception("The request should be of type post");
            }

            $data = $request->getPost();

            $result = $this->repository->filterNewEmp($data);

            $newEmpList = Helper::extractDbData($result);
            // echo '<pre>';print_r($newEmpList);die;
            return new CustomViewModel(['success' => true, 'data' => $newEmpList, 'error' => '']);
        } catch (Exception $e) {
            return new CustomViewModel(['success' => false, 'data' => [], 'error' => $e->getMessage()]);
        }
    }
}
