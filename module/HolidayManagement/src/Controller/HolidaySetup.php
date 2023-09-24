<?php

namespace HolidayManagement\Controller;

use Application\Controller\HrisController;
use Application\Custom\CustomViewModel;
use Application\Helper\EntityHelper;
use Application\Helper\EntityHelper as ApplicationEntityHelper;
use Application\Helper\Helper;
use Application\Model\FiscalYear;
use Exception;
use HolidayManagement\Form\HolidayForm;
use HolidayManagement\Model\Holiday;
use HolidayManagement\Repository\HolidayRepository;
use Setup\Model\HolidayDesignation;
use Zend\Authentication\Storage\StorageInterface;
use Zend\Db\Adapter\AdapterInterface;
use Zend\Form\Element\Select;
use Zend\View\Model\JsonModel;
use Zend\View\Model\ViewModel;

class HolidaySetup extends HrisController
{

    public function __construct(AdapterInterface $adapter, StorageInterface $storage)
    {
        parent::__construct($adapter, $storage);
        $this->initializeRepository(HolidayRepository::class);
        $this->initializeForm(HolidayForm::class);
    }

    public function indexAction()
    {
        $holidayFormElement = new Select();
        $holidayFormElement->setName("holiday");
        $holidays = ApplicationEntityHelper::getTableKVListWithSortOption($this->adapter, Holiday::TABLE_NAME, Holiday::HOLIDAY_ID, [Holiday::HOLIDAY_ENAME], ["STATUS" => "E"], Holiday::HOLIDAY_ENAME, "ASC", NULL, FALSE, TRUE);
        ksort($holidays);
        $holidayFormElement->setValueOptions($holidays);
        $holidayFormElement->setAttributes(["id" => "holidayId", "class" => "form-control"]);
        $holidayFormElement->setLabel("Holiday");
        $holidayList = $this->repository->fetchAll();
        $viewModel = new ViewModel(Helper::addFlashMessagesToArray($this, [
            'holidayList' => $holidayList,
            'holidayFormElement' => $holidayFormElement,
            'form' => $this->form,
            'customRenderer' => Helper::renderCustomView(),
        ]));
        return $viewModel;
    }

    public function addAction()
    {
        $request = $this->getRequest();
        if ($request->isPost()) {
            $postData = $request->getPost();
            $this->form->setData($postData);
            if ($this->form->isValid()) {
                $formData = $this->form->getData();
                $this->toCSV($formData, $postData);
                $holiday = new Holiday();
                $holiday->exchangeArrayFromForm($formData);
                $holiday->createdDt = Helper::getcurrentExpressionDate();
                $holiday->createdBy = $this->employeeId;
                $holiday->status = 'E';
                $holiday->holidayId = ((int) Helper::getMaxId($this->adapter, 'HRIS_HOLIDAY_MASTER_SETUP', 'HOLIDAY_ID')) + 1;
                $this->repository->add($holiday);
                $this->repository->holidayAssign($holiday->holidayId);
                $this->flashmessenger()->addMessage("Holiday Successfully added!!!");
                return $this->redirect()->toRoute("holidaysetup");
            }
        }
        $fiscalYearKV = EntityHelper::getTableKVList($this->adapter, FiscalYear::TABLE_NAME, FiscalYear::FISCAL_YEAR_ID, [FiscalYear::FISCAL_YEAR_NAME], null, null, null, FiscalYear::FISCAL_YEAR_ID, "DESC");
        return $this->stickFlashMessagesTo([
            'form' => $this->form,
            'customRenderer' => Helper::renderCustomView(),
            'searchValues' => EntityHelper::getSearchData($this->adapter),
            'fiscalYearKV' => $fiscalYearKV
        ]);
    }

    private function toCSV(&$out, $postData)
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


        $out['companyId'] = (isset($postData['company'])) ? $arrayToCSV($postData['company']) : '';
        $out['branchId'] = (isset($postData['branch'])) ? $arrayToCSV($postData['branch']) : '';
        $out['departmentId'] = (isset($postData['department'])) ? $arrayToCSV($postData['department']) : '';
        $out['designationId'] = (isset($postData['designation'])) ? $arrayToCSV($postData['designation']) : '';
        $out['positionId'] = (isset($postData['position'])) ? $arrayToCSV($postData['position']) : '';
        $out['serviceTypeId'] = isset($postData['serviceType']) ? $arrayToCSV($postData['serviceType']) : '';
        $out['employeeType'] = isset($postData['employeeType']) ? $arrayToCSV($postData['employeeType'], true) : '';
        $out['genderId'] = isset($postData['gender']) ? $arrayToCSV($postData['gender']) : '';
        $out['employeeId'] = isset($postData['employee']) ? $arrayToCSV($postData['employee']) : '';
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
        $id = (int) $this->params()->fromRoute("id", 0);

        if ($id === 0) {
            return $this->redirect()->toRoute("holidaysetup");
        }
        $holiday = new Holiday();
        $request = $this->getRequest();
        if ($request->isPost()) {
            $postedData = $request->getPost();
            $this->form->setData($postedData);
            if ($this->form->isValid()) {
                $formData = $this->form->getData();
                $this->toCSV($formData, $postedData);
                $holiday->exchangeArrayFromForm($formData);
                $holiday->modifiedDt = Helper::getcurrentExpressionDate();
                $holiday->modifiedBy = $this->employeeId;
                $this->repository->edit($holiday, $id);
                $this->repository->holidayAssign($id);
                $this->flashmessenger()->addMessage("Holiday Successfuly Edited.");
                return $this->redirect()->toRoute("holidaysetup");
            }
        }
        $resultSet = (array) $this->repository->fetchById($id);
        $holiday->exchangeArrayFromDB($resultSet);
        $this->form->bind($holiday);
        $searchSelectedValues = $this->csvToArray($resultSet);
        $fiscalYearKV = EntityHelper::getTableKVList($this->adapter, FiscalYear::TABLE_NAME, FiscalYear::FISCAL_YEAR_ID, [FiscalYear::FISCAL_YEAR_NAME]);

        return $this->stickFlashMessagesTo([
            'id' => $id,
            'form' => $this->form,
            'customRenderer' => Helper::renderCustomView(),
            'searchValues' => EntityHelper::getSearchData($this->adapter),
            'searchSelectedValues' => $searchSelectedValues,
            'fiscalYearKV' => $fiscalYearKV
        ]);
    }

    public function deleteAction()
    {
        $id = (int) $this->params()->fromRoute("id");
        if ($id === 0) {
            return $this->redirect()->toRoute("holidaysetup");
        }
        $this->repository->delete($id);
        $this->repository->holidayAssign($id);
        $this->flashmessenger()->addMessage("Holiday Successfully Deleted!!!");
        return $this->redirect()->toRoute('holidaysetup');
    }

    public function listAction()
    {
        $list = $this->repository->fetchAll();

        return Helper::addFlashMessagesToArray($this, [
            'holidayList' => $list,
        ]);
    }

    public function branchListAction()
    {
        try {
            $request = $this->getRequest();
            if (!$request->isPost()) {
                throw new Exception('Request should be post');
            }
            $id = $request->getPost()->id;
            $data = $this->repository->getBranchListWithHolidayId($id);
            return new CustomViewModel([
                'success' => true,
                'data' => $data,
                'error' => null
            ]);
        } catch (Exception $e) {
            return new CustomViewModel([
                'success' => false,
                'data' => null,
                'error' => $e->getMessage()
            ]);
        }
    }

    public function designationListAction()
    {
        try {
            $request = $this->getRequest();
            if (!$request->isPost()) {
                throw new Exception('Request should be post');
            }
            $id = $request->getPost()->id;
            $designationList = ApplicationEntityHelper::getTableKVList($this->adapter, HolidayDesignation::TABLE_NAME, HolidayDesignation::DESIGNATION_ID, [HolidayDesignation::DESIGNATION_ID], [HolidayDesignation::HOLIDAY_ID => $id]);
            return new CustomViewModel(['success' => true, 'data' => $designationList, 'error' => null]);
        } catch (Exception $e) {
            return new CustomViewModel(['success' => true, 'data' => null, 'error' => $e->getMessage()]);
        }
    }

    public function pullHolidayListAction()
    {
        try {
            $request = $this->getRequest();
            $data = $request->getPost();


            $fromDate = $data['fromDate'];
            $toDate = $data['toDate'];


            $holidayRepository = new HolidayRepository($this->adapter);
            $rawList = $holidayRepository->filterRecords($fromDate, $toDate);
            $list = Helper::extractDbData($rawList);

            return new JsonModel(['success' => true, 'data' => $list, 'message' => null]);
        } catch (Exception $e) {
            return new JsonModel(['success' => false, 'data' => null, 'message' => $e->getMessage()]);
        }
    }
}
