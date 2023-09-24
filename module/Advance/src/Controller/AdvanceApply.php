<?php

namespace Advance\Controller;

use Advance\Form\AdvanceRequestForm;
use Advance\Model\AdvanceRequestModel;
use Advance\Model\AdvanceSetupModel;
use Advance\Repository\AdvanceRequestRepository;
use Application\Controller\HrisController;
use Application\Helper\EntityHelper;
use Application\Helper\Helper;
use Exception;
use Notification\Controller\HeadNotification;
use Notification\Model\NotificationEvents;
use Setup\Model\HrEmployees;
use Zend\Authentication\Storage\StorageInterface;
use Zend\Db\Adapter\AdapterInterface;
use Zend\View\Model\JsonModel;

class AdvanceApply extends HrisController
{

    public function __construct(AdapterInterface $adapter, StorageInterface $storage)
    {
        parent::__construct($adapter, $storage);
        $this->initializeRepository(AdvanceRequestRepository::class);
        $this->initializeForm(AdvanceRequestForm::class);
    }

    public function indexAction()
    {
        return $this->redirect()->toRoute("advanceStatus");
    }

    public function addAction()
    {
        $request = $this->getRequest();

        if ($request->isPost()) {
            $postData = $request->getPost();
            $this->form->setData($postData);


            if ($this->form->isValid()) {
                $advanceRequestModel = new AdvanceRequestModel();
                $advanceRequestModel->exchangeArrayFromForm($this->form->getData());

                $advanceRequestModel->deductionType = $postData['deductionType'];
                $advanceRequestModel->advanceRequestId = (int) Helper::getMaxId($this->adapter, AdvanceRequestModel::TABLE_NAME, AdvanceRequestModel::ADVANCE_REQUEST_ID) + 1;
                $advanceRequestModel->status = "RQ";

                $this->repository->add($advanceRequestModel);
                $this->flashmessenger()->addMessage("Advance Request Successfully added!!!");
                try {
                    HeadNotification::pushNotification(NotificationEvents::ADVANCE_APPLIED, $advanceRequestModel, $this->adapter, $this);
                } catch (Exception $e) {
                    $this->flashmessenger()->addMessage($e->getMessage());
                }

                return $this->redirect()->toRoute("advanceStatus");
            }
        }
        //     $employeeListWithCode = EntityHelper::rawQueryResult($this->adapter, "SELECT
        //     E.EMPLOYEE_ID,
        //     E.EMPLOYEE_CODE || '-' || E.FULL_NAME AS FULL_NAME,
        //     E.SALARY,
        //     H.AMOUNT AS SALHISTORY
        // FROM HRIS_EMPLOYEES E
        // LEFT JOIN hris_employee_salary_history H ON H.EMPLOYEE_ID = E.EMPLOYEE_ID AND H.PAY_ID = 1 AND H.IS_ENABLE = 'Y'
        // WHERE E.STATUS = 'E' AND E.RETIRED_FLAG = 'N'");

        return Helper::addFlashMessagesToArray($this, [
            'form' => $this->form,
            'advance' => EntityHelper::getTableList($this->adapter, AdvanceSetupModel::TABLE_NAME, ['*'], [AdvanceSetupModel::STATUS => 'E']),
            'customRenderer' => Helper::renderCustomView(),
            'employeeList' => EntityHelper::getTableList($this->adapter, HrEmployees::TABLE_NAME, [HrEmployees::EMPLOYEE_ID, HrEmployees::FULL_NAME], [HrEmployees::STATUS => "E", HrEmployees::RETIRED_FLAG => "N"]),
        ]);
    }

    public function pullEmployeeAdvanceAction()
    {
        try {
            $request = $this->getRequest();
            $employeeId = $request->getPost('employeeId');

            $historySalary = $this->repository->getSalaryHistory($employeeId);
            $payrollSalary = $this->repository->getPayrollBasicSalary($employeeId);
            $basicSalary = EntityHelper::getTableList($this->adapter, HrEmployees::TABLE_NAME, ['SALARY'], [HrEmployees::EMPLOYEE_ID => $this->employeeId]);
            $empSalary = $historySalary['SALARY'] ? $historySalary['SALARY'] : ($payrollSalary['FLAT_VALUE'] ? $payrollSalary['FLAT_VALUE'] : $basicSalary[0]['SALARY']);
            $advanceList = $this->repository->fetchAvailableAdvacenList($employeeId);

            return new JsonModel(['success' => true, 'data' => $advanceList, 'salary' => $empSalary, 'error' => '']);
        } catch (Exception $e) {
            return new JsonModel(['success' => false, 'data' => [], 'error' => $e->getMessage()]);
        }
    }
}
