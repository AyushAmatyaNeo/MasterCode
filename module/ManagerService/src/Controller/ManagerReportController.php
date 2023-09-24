<?php

namespace ManagerService\Controller;

use Application\Custom\CustomViewModel;
use Application\Helper\Helper;
use ManagerService\Repository\ManagerReportRepo;
use Zend\Authentication\AuthenticationService;
use Zend\Db\Adapter\AdapterInterface;
use Zend\Mvc\Controller\AbstractActionController;
use Application\Controller\HrisController;
use Zend\Form\Element\Select;
use Zend\View\Model\JsonModel;
use Application\Helper\EntityHelper;
use Exception;
use Zend\Db\Sql\Select as Select2;
use Application\Model\HrisQuery;
use Application\Model\FiscalYear;
use Zend\Authentication\Storage\StorageInterface;

class ManagerReportController extends HrisController
{

    public function __construct(AdapterInterface $adapter, StorageInterface $storage)
    {
        parent::__construct($adapter, $storage);
        $this->initializeRepository(ManagerReportRepo::class);
    }

    public function pullAttendanceAction()
    {
        $request = $this->getRequest();
        $postedData = $request->getPost();

        $filtersDetail = $postedData['data'];
        $currentEmployeeId = $filtersDetail['currentEmployee'];
        $employeeId = $filtersDetail['employeeId'];
        $fromDate = $filtersDetail['fromDate'];
        $toDate = $filtersDetail['toDate'];
        $status = $filtersDetail['status'];
        $missPunchOnly = ((int) $filtersDetail['missPunchOnly'] == 1) ? true : false;

        $result = $this->repository->attendanceReport($currentEmployeeId, $fromDate, $toDate, $employeeId, $status, $missPunchOnly);

        $list = [];
        foreach ($result as $row) {
            array_push($list, $row);
        }


        return new CustomViewModel([
            "success" => true,
            'data' => $list
        ]);
    }


    public function indexAction()
    {
        $statusFormElement = new Select();
        $statusFormElement->setName("status");
        $status = array(
            "All" => "All Status",
            "P" => "Present Only",
            "A" => "Absent Only",
            "H" => "On Holiday",
            "L" => "On Leave",
            "T" => "On Training",
            "TVL" => "On Travel",
            "WOH" => "Work on Holiday",
            "WOD" => "Work on DAYOFF",
            "LI" => "Late In",
            "EO" => "Early Out"
        );

        $statusFormElement->setValueOptions($status);
        $statusFormElement->setAttributes(["id" => "statusId", "class" => "form-control reset-field"]);
        $statusFormElement->setLabel("Status");


        $employees = $this->repository->fetchAllEmployee($this->employeeId);

        $employeeFormElement = new Select();
        $employeeFormElement->setName('Employee');
        $employeeFormElement->setValueOptions($employees);
        $employeeFormElement->setAttributes(["id" => "employeeId", "class" => "form-control reset-field"]);
        $employeeFormElement->setLabel("Employee");

        return Helper::addFlashMessagesToArray($this, [
            'employeeId' => $this->employeeId,
            'status' => $statusFormElement,
            'employeeFromElement' => $employeeFormElement,
            'currentEmployeeId' => $this->employeeId,

        ]);
    }
    public function newDepartmentWiseDailyAction()
    {

        $request = $this->getRequest();
        if ($request->isPost()) {
            try {
                $data = $request->getPost();
                $postedData = $request->getPost();
                // $data = $this->repository->employeeDailyReport($postedData);

                $monthData = $this->repository->getMonthDetails($postedData['monthCodeId']);
                $data = $this->repository->newEmployeeDailyReport($postedData, $this->employeeId);

                $data['monthData'] = $monthData;
                return new JsonModel(['success' => true, 'data' => $data, 'error' => '']);
            } catch (Exception $e) {
                return new JsonModel(['success' => false, 'data' => [], 'error' => $e->getMessage()]);
            }
        }

        $employee = $this->repository->fetchAllEmployee($this->employeeId);
        $employees = [];
        foreach ($employee as $key => $value) {
            array_push($employees, ["id" => $key, "name" => $value]);
        }
        return $this->stickFlashMessagesTo([
            //                'comBraDepList' => [
            //                    'DEPARTMENT_LIST' => EntityHelper::getTableList($this->adapter, Department::TABLE_NAME, [Department::DEPARTMENT_ID, Department::DEPARTMENT_NAME, Department::COMPANY_ID, Department::BRANCH_ID], [Department::STATUS => "E"])
            //                ],
            //                'monthList' => $monthList,
            //                'monthId' => $monthId,
            //                'departmentId' => $departmentId,
            'fiscalYearSE' => $this->getFiscalYearSE(),
            'preference' => $this->preference,
            'employees' => $employees,
            'searchValues' => EntityHelper::getSearchData($this->adapter),
            'acl' => $this->acl,
            'employeeDetail' => $this->storageData['employee_detail']
        ]);
    }
    private function getFiscalYearSE()
    {
        $fiscalYearList = HrisQuery::singleton()
            ->setAdapter($this->adapter)
            ->setTableName(FiscalYear::TABLE_NAME)
            ->setColumnList([FiscalYear::FISCAL_YEAR_ID, FiscalYear::FISCAL_YEAR_NAME])
            ->setWhere([FiscalYear::STATUS => 'E'])
            ->setOrder([FiscalYear::START_DATE => Select2::ORDER_DESCENDING])
            ->setKeyValue(FiscalYear::FISCAL_YEAR_ID, FiscalYear::FISCAL_YEAR_NAME)
            ->result();
        $config = [
            'name' => 'fiscalYear',
            'id' => 'fiscalYearId',
            'class' => 'form-control',
            'label' => 'Type'
        ];

        return $this->getSelectElement($config, $fiscalYearList);
    }
}
