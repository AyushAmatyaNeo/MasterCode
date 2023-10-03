<?php

namespace LeaveManagement\Controller;

use Application\Controller\HrisController;
use Application\Helper\ACLHelper;
use Application\Helper\EntityHelper;
use Application\Helper\Helper;
use Exception;
use LeaveManagement\Form\LeaveMasterForm;
use LeaveManagement\Model\LeaveMaster;
use LeaveManagement\Repository\LeaveMasterRepository;
use Setup\Model\Company;
use Zend\Authentication\Storage\StorageInterface;
use Zend\Db\Adapter\AdapterInterface;
use Zend\View\Model\JsonModel;
use Zend\View\Model\ViewModel;

class LeaveSetup extends HrisController
{

    public function __construct(AdapterInterface $adapter, StorageInterface $storage)
    {
        parent::__construct($adapter, $storage);
        $this->initializeRepository(LeaveMasterRepository::class);
        $this->initializeForm(LeaveMasterForm::class);
    }

    public function indexAction()
    {
        $request = $this->getRequest();
        if ($request->isPost()) {
            try {
                $result = $this->repository->fetchAll();
                $leaveList = iterator_to_array($result, false);
                return new JsonModel(['success' => true, 'data' => $leaveList, 'error' => '']);
            } catch (Exception $e) {
                return new JsonModel(['success' => false, 'data' => [], 'error' => $e->getMessage()]);
            }
        }
        return $this->stickFlashMessagesTo(['acl' => $this->acl]);
    }

    public function addAction()
    {
        $request = $this->getRequest();
        if ($request->isPost()) {
            $postData = $request->getPost();
            $this->form->setData($postData);
            if ($this->form->isValid()) {
                $leaveMaster = new LeaveMaster();
                $leaveMaster->exchangeArrayFromForm($this->form->getData());
                $leaveMaster->leaveId = ((int) Helper::getMaxId($this->adapter, LeaveMaster::TABLE_NAME, LeaveMaster::LEAVE_ID)) + 1;
                $leaveMaster->createdDt = Helper::getcurrentExpressionDate();
                $leaveMaster->createdBy = $this->employeeId;
                $leaveMaster->status = 'E';
                $leaveMaster->leaveYear = $leaveMaster->fiscalYear;
                $this->arrayToCSV($leaveMaster, $postData);
                $this->repository->add($leaveMaster);
                $this->flashmessenger()->addMessage("Leave Successfully added!!!");
                return $this->redirect()->toRoute("leavesetup");
            }
        }
        return new ViewModel(
            Helper::addFlashMessagesToArray(
                $this,
                [
                    'form' => $this->form,
                    'customRenderer' => Helper::renderCustomView(),
                    'companies' => EntityHelper::getTableKVListWithSortOption($this->adapter, Company::TABLE_NAME, Company::COMPANY_ID, [Company::COMPANY_NAME], ["STATUS" => "E"], Company::COMPANY_NAME, "ASC", NULL, TRUE, TRUE),
                    'fiscalYears' => EntityHelper::getTableKVList($this->adapter, "HRIS_LEAVE_YEARS", "LEAVE_YEAR_ID", ["LEAVE_YEAR_NAME"], null, null, null, "LEAVE_YEAR_ID", 'DESC'),
                    'searchValues' => EntityHelper::getSearchData($this->adapter),
                ]
            )
        );
    }

    private function arrayToCSV(LeaveMaster &$model, $postData)
    {
        $arrayToCSV = function (array $list, $isString = false) {
            $valuesinCSV = "";
            for ($i = 0; $i < sizeof($list); $i++) {
                $value = $isString ? "'{$list[$i]}'" : $list[$i];
                if ($i + 1 == sizeof($list)) {
                    $valuesinCSV .= "{$value}";
                } else {
                    $valuesinCSV .= "{$value},";
                }
            }
            return $valuesinCSV;
        };


        if (isset($postData['company'])) {
            $model->companyId = $arrayToCSV($postData['company']);
        }
        if (isset($postData['branch'])) {
            $model->branchId = $arrayToCSV($postData['branch']);
        }
        if (isset($postData['department'])) {
            $model->departmentId = $arrayToCSV($postData['department']);
        }
        if (isset($postData['designation'])) {
            $model->designationId = $arrayToCSV($postData['designation']);
        }
        if (isset($postData['position'])) {
            $model->positionId = $arrayToCSV($postData['position']);
        }
        $model->serviceTypeId = isset($postData['serviceType']) ? $arrayToCSV($postData['serviceType']) : '';
        $model->employeeType = isset($postData['employeeType']) ? $arrayToCSV($postData['employeeType'], true) : '';
        $model->genderId = isset($postData['gender']) ? $arrayToCSV($postData['gender']) : '';
        $model->employeeId = isset($postData['employee']) ? $arrayToCSV($postData['employee']) : '';
    }

    private function csvToArray($csvList)
    {
        $array['companyId'] = str_getcsv($csvList['COMPANY_ID']);
        $array['branchId'] = str_getcsv($csvList['BRANCH_ID']);
        $array['departmentId'] = str_getcsv($csvList['DEPARTMENT_ID']);
        $array['designationId'] = str_getcsv($csvList['DESIGNATION_ID']);
        $array['positionId'] = str_getcsv($csvList['POSITION_ID']);
        $array['serviceTypeId'] = str_getcsv($csvList['SERVICE_TYPE_ID']);
        $array['employeeType'] = str_getcsv($csvList['EMPLOYEE_TYPE']);
        $array['genderId'] = str_getcsv($csvList['GENDER_ID']);
        $array['employeeId'] = str_getcsv($csvList['EMPLOYEE_ID']);
        return $array;
    }

    public function editAction()
    {
        $id = (int) $this->params()->fromRoute("id");

        if ($id === 0) {
            return $this->redirect()->toRoute("shift");
        }
        $request = $this->getRequest();
        $leaveMaster = new LeaveMaster();
        if ($request->isPost()) {
            $postData = $request->getPost();
            $this->form->setData($postData);
            if ($this->form->isValid()) {
                $leaveMaster->exchangeArrayFromForm($this->form->getData());
                $leaveMaster->modifiedDt = Helper::getcurrentExpressionDate();
                $leaveMaster->modifiedBy = $this->employeeId;
                $leaveMaster->leaveYear = $leaveMaster->fiscalYear;
                $this->arrayToCSV($leaveMaster, $postData);
                $this->repository->edit($leaveMaster, $id);
                $this->flashmessenger()->addMessage("Leave Successfuly Updated!!!");
                return $this->redirect()->toRoute("leavesetup");
            }
        }
        $leaveMasterArray = $this->repository->fetchById($id)->getArrayCopy();
        $leaveMaster->exchangeArrayFromDB($leaveMasterArray);
        $this->form->bind($leaveMaster);
        $searchSelectedValues = $this->csvToArray($leaveMasterArray);

        return new ViewModel(
            Helper::addFlashMessagesToArray(
                $this,
                [
                    'form' => $this->form,
                    'id' => $id,
                    'customRenderer' => Helper::renderCustomView(),
                    'companies' => EntityHelper::getTableKVListWithSortOption($this->adapter, Company::TABLE_NAME, Company::COMPANY_ID, [Company::COMPANY_NAME], ["STATUS" => "E"], Company::COMPANY_NAME, "ASC", NULL, TRUE, TRUE),
                    'fiscalYears' => EntityHelper::getTableKVList($this->adapter, "HRIS_LEAVE_YEARS", "LEAVE_YEAR_ID", ["LEAVE_YEAR_NAME"], null),
                    'searchValues' => EntityHelper::getSearchData($this->adapter),
                    'searchSelectedValues' => $searchSelectedValues
                ]
            )
        );
    }

    public function deleteAction()
    {
        if (!ACLHelper::checkFor(ACLHelper::DELETE, $this->acl, $this)) {
            return;
        };
        $id = (int) $this->params()->fromRoute("id");
        if (!$id) {
            return $this->redirect()->toRoute('leavesetup');
        }
        $this->repository->deleteLeave($id, $this->employeeId);
        // $this->repository->delete($id);
        $this->flashmessenger()->addMessage("Leave Successfully Deleted!!!");
        return $this->redirect()->toRoute('leavesetup');
    }
}
