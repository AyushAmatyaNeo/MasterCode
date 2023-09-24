<?php

namespace Report\Controller;

use Application\Controller\HrisController;
use Application\Custom\CustomViewModel;
use Application\Helper\EntityHelper;
use Application\Helper\EntityHelper as ApplicationHelper;
use Application\Helper\Helper;
use Application\Model\FiscalYear;
use Application\Model\HrisQuery;
use Application\Repository\DashboardRepository;
use Exception;
use Setup\Model\Training;
use Report\Repository\ReportRepository;
use Setup\Model\Branch;
use Zend\Authentication\Storage\StorageInterface;
use Zend\Db\Adapter\AdapterInterface;
use Zend\Db\Sql\Select as Select2;
use Zend\Form\Element\Select;
use Zend\View\Model\JsonModel;

class AllReportController extends HrisController
{

    public function __construct(AdapterInterface $adapter, StorageInterface $storage)
    {
        parent::__construct($adapter, $storage);
        $this->initializeRepository(ReportRepository::class);
    }

    public function branchWiseDailyAction()
    {
        $monthId = (int) $this->params()->fromRoute('id1');
        $branchId = (int) $this->params()->fromRoute('id2');

        return Helper::addFlashMessagesToArray($this, [
            'comBraList' => [
                'BRANCH_LIST' => EntityHelper::getTableList($this->adapter, Branch::TABLE_NAME, [Branch::BRANCH_ID, Branch::BRANCH_NAME, Branch::COMPANY_ID], [Branch::STATUS => "E"])
            ],
            'monthId' => $monthId,
            'branchId' => $branchId,
            'searchValues' => EntityHelper::getSearchData($this->adapter),
            'acl' => $this->acl,
            'employeeDetail' => $this->storageData['employee_detail'],
            'preference' => $this->preference,
            'name' => $this->storageData['employee_detail']['EMPLOYEE_CODE'] . '-' . $this->storageData['employee_detail']['FULL_NAME'],
            'companyLogo' => $this->storageData['employee_detail']['COMPANY_FILE_PATH'],
        ]);
    }

    public function branchWiseDailyInOutAction()
    {
        $monthId = (int) $this->params()->fromRoute('id1');
        $branchId = (int) $this->params()->fromRoute('id2');

        return Helper::addFlashMessagesToArray($this, [
            //                    'comBraList' => [
            //                        'BRANCH_LIST' => EntityHelper::getTableList($this->adapter, Branch::TABLE_NAME, [Branch::BRANCH_ID, Branch::BRANCH_NAME, Branch::COMPANY_ID], [Branch::STATUS => "E"])
            //                    ],
            'monthId' => $monthId,
            //                    'branchId' => $branchId,
            'preference' => $this->preference,
            'searchValues' => EntityHelper::getSearchData($this->adapter),
            'acl' => $this->acl,
            'employeeDetail' => $this->storageData['employee_detail'],
            'preference' => $this->preference,
            'name' => $this->storageData['employee_detail']['EMPLOYEE_CODE'] . '-' . $this->storageData['employee_detail']['FULL_NAME'],
            'companyLogo' => $this->storageData['employee_detail']['COMPANY_FILE_PATH'],
        ]);
    }

    public function branchWiseDailyReportAction()
    {
        try {
            $request = $this->getRequest();
            if ($request->isPost()) {
                $postedData = $request->getPost();
                $branchId = $postedData['branchId'];
                if (!isset($branchId)) {
                    throw new Exception("parameter branchId is required");
                }
                $monthId = $postedData['monthId'];
                if (!isset($monthId)) {
                    throw new Exception("parameter monthId is required");
                }

                $reportData = $this->repository->branchWiseDailyReport($postedData);
                $branchName = -1;
                if ($postedData['branchId'] != null) {
                    $branchName = $this->repository->getBranchName($postedData['branchId'][0]);
                }
                $days = $this->repository->getDaysInMonth($monthId);
                $dates = $this->repository->getDates($monthId);

                return new JsonModel(['success' => true, 'data' => $reportData, 'days' => $days, 'branchName' => $branchName, 'dates' => $dates, 'error' => '']);
            } else {
                throw new Exception("The request should be of type post");
            }
        } catch (Exception $e) {
            return new JsonModel(['success' => false, 'data' => [], 'error' => $e->getMessage()]);
        }
    }

    public function branchWiseAction()
    {
        $branchId = (int) $this->params()->fromRoute('id1');
        return $this->stickFlashMessagesTo([
            'comBraDepList' => [
                'BRANCH_LIST' => EntityHelper::getTableList($this->adapter, Branch::TABLE_NAME, [Branch::BRANCH_ID, Branch::BRANCH_NAME], [Branch::STATUS => "E"])
            ],
            'branchId' => $branchId,
            'preference' => $this->preference,
            'acl' => $this->acl,
            'employeeDetail' => $this->storageData['employee_detail']
        ]);
    }

    public function branchWiseMonthReportAction()
    {
        try {
            $request = $this->getRequest();
            if ($request->isPost()) {
                $postedData = $request->getPost();

                $branchId = $postedData['branchId'];
                if (!isset($branchId)) {
                    throw new Exception("parameter branchId is required");
                }
                $reportData = $this->repository->branchWiseEmployeeMonthReport($branchId);
                return new JsonModel(['success' => true, 'data' => $reportData, 'error' => '']);
            } else {
                throw new Exception("The request should be of type post");
            }
        } catch (Exception $e) {
            return new JsonModel(['success' => false, 'data' => [], 'error' => $e->getMessage()]);
        }
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

    public function departmentAllAction()
    {
        $request = $this->getRequest();
        if ($request->isPost()) {
            try {
                $postedData = $request->getPost();
                $data = $this->repository->departmentMonthReport($postedData['fiscalYearId']);
                return new JsonModel(['success' => true, 'data' => $data, 'error' => null]);
            } catch (Exception $e) {
                return new JsonModel(['success' => false, 'data' => null, 'message' => $e->getMessage()]);
            }
        }


        return [
            'fiscalYearSE' => $this->getFiscalYearSE(), 'calenderType' => $this->getCanderType(),
            'preference' => $this->preference,
            'acl' => $this->acl,
            'employeeDetail' => $this->storageData['employee_detail']
        ];
    }

    public function departmentWiseAction()
    {
        $request = $this->getRequest();
        if ($request->isPost()) {
            try {
                $postedData = $request->getPost();
                $data = $this->repository->employeeMonthlyReport($postedData);
                return new JsonModel(['success' => true, 'data' => $data, 'error' => null]);
            } catch (Exception $e) {
                return new JsonModel(['success' => false, 'data' => null, 'message' => $e->getMessage()]);
            }
        }

        return [
            'fiscalYearSE' => $this->getFiscalYearSE(),
            'calenderType' => $this->getCanderType(),
            'preference' => $this->preference,
            'acl' => $this->acl,
            'employeeDetail' => $this->storageData['employee_detail']
        ];
    }

    public function departmentWiseDailyAction()
    {
        //        $monthId = (int) $this->params()->fromRoute('id1');
        //        $departmentId = (int) $this->params()->fromRoute('id2');
        //        $monthList = $this->repository->getMonthList();
        $request = $this->getRequest();
        if ($request->isPost()) {
            try {
                $data = $request->getPost();
                $postedData = $request->getPost();
                $data = $this->repository->employeeDailyReport($postedData);
                return new JsonModel(['success' => true, 'data' => $data, 'error' => '']);
            } catch (Exception $e) {
                return new JsonModel(['success' => false, 'data' => [], 'error' => $e->getMessage()]);
            }
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
            'acl' => $this->acl,
            'employeeDetail' => $this->storageData['employee_detail']
        ]);
    }

    public function departmentWiseDailyShivamAction()
    {

        //        $monthId = (int) $this->params()->fromRoute('id1');
        //        $departmentId = (int) $this->params()->fromRoute('id2');
        //        $monthList = $this->repository->getMonthList();
        $request = $this->getRequest();
        if ($request->isPost()) {
            try {
                $data = $request->getPost();
                $postedData = $request->getPost();
                $data = $this->repository->employeeDailyReportShivam($postedData);
                return new JsonModel(['success' => true, 'data' => $data, 'error' => '']);
            } catch (Exception $e) {
                return new JsonModel(['success' => false, 'data' => [], 'error' => $e->getMessage()]);
            }
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
            'acl' => $this->acl,
            'employeeDetail' => $this->storageData['employee_detail']
        ]);
    }

    public function employeeWiseAction()
    {

        $employeeId = (int) $this->params()->fromRoute('id1');
        $employeeList = $this->repository->getEmployeeList();

        $request = $this->getRequest();
        if ($request->isPost()) {
            try {
                //                $data = $request->getPost();
                $postedData = $request->getPost();
                $employeeId = $postedData['employeeId'];
                $fiscalYearId = $postedData['fiscalYearId'];
                $data = $this->repository->employeeYearlyReport($employeeId, $fiscalYearId);
                return new JsonModel(['success' => true, 'data' => $data, 'error' => '']);
            } catch (Exception $e) {
                return new JsonModel(['success' => false, 'data' => [], 'error' => $e->getMessage()]);
            }
        }


        return $this->stickFlashMessagesTo([
            'fiscalYearSE' => $this->getFiscalYearSE(),
            'employeeList' => $employeeList,
            'preference' => $this->preference,
            'acl' => $this->acl,
            'employeeDetail' => $this->storageData['employee_detail']
        ]);
    }

    public function withOvertimeAction()
    {
        $request = $this->getRequest();
        if ($request->isPost()) {
            try {
                $data = $request->getPost();
                $reportData = $this->repository->reportWithOT($data);
                return new JsonModel(['success' => true, 'data' => $reportData, 'error' => '']);
            } catch (Exception $e) {
                return new JsonModel(['success' => false, 'data' => [], 'error' => $e->getMessage()]);
            }
        }

        return $this->stickFlashMessagesTo([
            'searchValues' => EntityHelper::getSearchData($this->adapter),
            'linkToEmpower' => $this->repository->checkIfEmpowerTableExists() ? 1 : 0,
            'preference' => $this->preference,
            'acl' => $this->acl,
            'employeeDetail' => $this->storageData['employee_detail']
        ]);
    }

    public function toEmpowerAction()
    {
        try {
            $request = $this->getRequest();
            if ($request->isPost()) {
                $postedData = $request->getPost();
                $fiscalYearMonthNo = $postedData['fiscalYearMonthNo'];
                $fiscalYearId = $postedData['fiscalYearId'];
                $this->repository->toEmpower($fiscalYearId, $fiscalYearMonthNo);
                return new JsonModel(['success' => true, 'data' => null, 'error' => '']);
            } else {
                throw new Exception("The request should be of type post");
            }
        } catch (Exception $e) {
            return new JsonModel(['success' => false, 'data' => [], 'error' => $e->getMessage()]);
        }
    }

    public function loadDataAction()
    {
        try {
            $request = $this->getRequest();
            if ($request->isPost()) {
                $postedData = $request->getPost();
                $fiscalYearMonthNo = $postedData['fiscalYearMonthNo'];
                $fiscalYearId = $postedData['fiscalYearId'];
                $this->repository->loadData($fiscalYearId, $fiscalYearMonthNo);
                return new JsonModel(['success' => true, 'data' => null, 'error' => '']);
            } else {
                throw new Exception("The request should be of type post");
            }
        } catch (Exception $e) {
            return new JsonModel(['success' => false, 'data' => [], 'error' => $e->getMessage()]);
        }
    }

    public function employeeWiseDailyReportAction()
    {
        try {
            $request = $this->getRequest();
            if ($request->isPost()) {
                $postedData = $request->getPost();

                $employeeId = $postedData['employeeId'];
                if (!isset($employeeId)) {
                    throw new Exception("parameter employeeId is required");
                }

                $reportData = $this->repository->employeeWiseDailyReport($employeeId);
                return new JsonModel(['success' => true, 'data' => $reportData, 'error' => '']);
            } else {
                throw new Exception("The request should be of type post");
            }
        } catch (Exception $e) {
            return new JsonModel(['success' => false, 'data' => [], 'error' => $e->getMessage()]);
        }
    }

    public function departmentWiseDailyReportAction()
    {
        try {
            $request = $this->getRequest();
            if ($request->isPost()) {
                $postedData = $request->getPost();

                $departmentId = $postedData['departmentId'];
                if (!isset($departmentId)) {
                    throw new Exception("parameter departmentId is required");
                }
                $monthId = $postedData['monthId'];
                if (!isset($monthId)) {
                    throw new Exception("parameter monthId is required");
                }

                $reportData = $this->repository->departmentWiseDailyReport($monthId, $departmentId);
                return new JsonModel(['success' => true, 'data' => $reportData, 'error' => '']);
            } else {
                throw new Exception("The request should be of type post");
            }
        } catch (Exception $e) {
            return new JsonModel(['success' => false, 'data' => [], 'error' => $e->getMessage()]);
        }
    }

    private function getComBraDepList()
    {
        $cbd = $this->repository->getCompanyBranchDepartment();
        $comBraDepList = [];
        foreach ($cbd as $row) {
            if (isset($comBraDepList[$row['COMPANY_ID']])) {
                if (isset($comBraDepList[$row['COMPANY_ID']]['BRANCH_LIST'][$row['BRANCH_ID']])) {
                    $comBraDepList[$row['COMPANY_ID']]['BRANCH_LIST'][$row['BRANCH_ID']]['DEPARTMENT_LIST'][$row['DEPARTMENT_ID']] = [
                        'DEPARTMENT_ID' => $row['DEPARTMENT_ID'],
                        'DEPARTMENT_NAME' => $row['DEPARTMENT_NAME']
                    ];
                } else {
                    $comBraDepList[$row['COMPANY_ID']]['BRANCH_LIST'][$row['BRANCH_ID']] = [
                        'BRANCH_ID' => $row['BRANCH_ID'],
                        'BRANCH_NAME' => $row['BRANCH_NAME'],
                        'DEPARTMENT_LIST' => [
                            $row['DEPARTMENT_ID'] => [
                                'DEPARTMENT_ID' => $row['DEPARTMENT_ID'],
                                'DEPARTMENT_NAME' => $row['DEPARTMENT_ID']
                            ]
                        ]
                    ];
                }
            } else {
                $comBraDepList[$row['COMPANY_ID']] = [
                    'COMPANY_ID' => $row['COMPANY_ID'],
                    'COMPANY_NAME' => $row['COMPANY_NAME'],
                    'BRANCH_LIST' => [
                        $row['BRANCH_ID'] => [
                            'BRANCH_ID' => $row['BRANCH_ID'],
                            'BRANCH_NAME' => $row['BRANCH_NAME'],
                            'DEPARTMENT_LIST' => [
                                $row['DEPARTMENT_ID'] => [
                                    'DEPARTMENT_ID' => $row['DEPARTMENT_ID'],
                                    'DEPARTMENT_NAME' => $row['DEPARTMENT_ID']
                                ]
                            ]
                        ]
                    ]
                ];
            }
        }
        return $comBraDepList;
    }

    public function leaveReportAction()
    {

        $customFormElement = new Select();
        $customFormElement->setName("status");
        $custom = array(
            "EMP" => "Employee Wise",
            "BRA" => "Branch Wise",
            "DEP" => "Department Wise",
            "DES" => "Designation Wise",
            "POS" => "Position Wise",
        );
        $customFormElement->setValueOptions($custom);
        $customFormElement->setAttributes(["id" => "customWise", "class" => "form-control reset-field"]);
        $customFormElement->setLabel("Custom");

        $allLeave = $this->repository->fetchAllLeave();
        return Helper::addFlashMessagesToArray($this, [
            'customWise' => $customFormElement,
            'searchValues' => EntityHelper::getSearchData($this->adapter),
            'allLeave' => $allLeave,
            'preference' => $this->preference
        ]);
    }

    public function HireAndFireReportAction()
    {
        $nepaliMonth = $this->repository->FetchNepaliMonth();
        return Helper::addFlashMessagesToArray($this, [
            'nepaliMonth' => $nepaliMonth
        ]);
    }

    public function getLeaveReportWSAction()
    {
        try {
            $request = $this->getRequest();
            if (!$request->isPost()) {
                throw new Exception("must be a post request.");
            }
            $data = $request->getPost();

            $customWise = $data['customWise'];


            switch ($customWise) {
                case 'EMP':
                    $reportData = $this->repository->filterLeaveReportEmployee($data);
                    break;
                case 'BRA':
                    $reportData = $this->repository->filterLeaveReportBranch($data);
                    break;
                case 'DEP':
                    $reportData = $this->repository->filterLeaveReportDepartmnet($data);
                    break;
                case 'DES':
                    $reportData = $this->repository->filterLeaveReportDesignation($data);
                    break;
                case 'POS':
                    $reportData = $this->repository->filterLeaveReportPosition($data);
                    break;
            }

            return new CustomViewModel(['success' => true, 'data' => $reportData, 'error' => '']);
        } catch (Exception $e) {
            return new CustomViewModel(['success' => false, 'data' => [], 'error' => $e->getMessage()]);
        }
    }

    public function getHireFireReportAction()
    {
        try {
            $request = $this->getRequest();
            $Postdata = $request->getPost();
            $data = json_decode($Postdata['data']);
            $HireReport = $this->repository->CalculateHireEmployees($data);
            return new CustomViewModel(['success' => true, 'data' => $HireReport, 'error' => '']);
        } catch (Exception $e) {
            return new CustomViewModel(['success' => false, 'data' => [], 'error' => $e->getMessage()]);
        }
    }

    public function monthlyAllowanceAction()
    {
        $request = $this->getRequest();
        if ($request->isPost()) {
            try {
                $data = $request->getPost();
                $postedData = $request->getPost();
                $data = $this->repository->getMonthlyAllowance($postedData);
                return new JsonModel(['success' => true, 'data' => $data, 'error' => '']);
            } catch (Exception $e) {
                return new JsonModel(['success' => false, 'data' => [], 'error' => $e->getMessage()]);
            }
        }
        return $this->stickFlashMessagesTo([
            'fiscalYearSE' => $this->getFiscalYearSE(),
            'searchValues' => EntityHelper::getSearchData($this->adapter),
            'acl' => $this->acl,
            'employeeDetail' => $this->storageData['employee_detail'],
            'preference' => $this->preference
        ]);
    }

    public function departmentWiseAttdReportAction()
    {
        try {
            $request = $this->getRequest();
            if ($request->isPost()) {
                $postedData = $request->getPost();

                $date1 = $postedData['date1'];
                if (!isset($date1)) {
                    throw new Exception("parameter from_date is required");
                }
                $date2 = $postedData['date2'];
                if (!isset($date2)) {
                    throw new Exception("parameter to_date is required");
                }

                $companyId = $postedData['company'];

                if ($date2 == '' || $date2 == null) {
                    $date2 = $date1;
                }

                $reportData = $this->repository->departmentWiseAttdReport($companyId, $date1, $date2);
                return new JsonModel(['success' => true, 'data' => $reportData, 'error' => '']);
            } else {
                $companies = $this->repository->getAllCompanies();

                return $this->stickFlashMessagesTo([
                    'searchValues' => EntityHelper::getSearchData($this->adapter),
                    'acl' => $this->acl,
                    'employeeDetail' => $this->storageData['employee_detail'],
                    'companies' => $companies,
                    'preference' => $this->preference
                ]);
            }
        } catch (Exception $e) {
            return new JsonModel(['success' => false, 'data' => [], 'error' => $e->getMessage()]);
        }
    }

    public function getCanderType()
    {
        $calenderType = 'N';
        if (isset($this->preference['calendarView'])) {
            $calenderType = $this->preference['calendarView'];
        }
        return $calenderType;
    }

    public function birthdayReportAction()
    {
        $request = $this->getRequest();
        if ($request->isPost()) {
            try {
                $data = $request->getPost();
                $list = $this->repository->fetchBirthdays($data);
                return new JsonModel(['success' => true, 'data' => $list, 'message' => null]);
            } catch (Exception $e) {
                return new JsonModel(['success' => false, 'data' => null, 'message' => $e->getMessage()]);
            }
        }

        return $this->stickFlashMessagesTo([
            'searchValues' => ApplicationHelper::getSearchData($this->adapter),
            'acl' => $this->acl,
            'employeeDetail' => $this->storageData['employee_detail'],
            'preference' => $this->preference
        ]);
    }

    public function jobDurationReportAction()
    {
        $request = $this->getRequest();
        if ($request->isPost()) {
            try {
                $data = $request->getPost();
                $list = $this->repository->fetchJobDurationReport($data);
                return new JsonModel(['success' => true, 'data' => $list, 'message' => null]);
            } catch (Exception $e) {
                return new JsonModel(['success' => false, 'data' => null, 'message' => $e->getMessage()]);
            }
        }

        return $this->stickFlashMessagesTo([
            'searchValues' => ApplicationHelper::getSearchData($this->adapter),
            'acl' => $this->acl,
            'employeeDetail' => $this->storageData['employee_detail'],
            'preference' => $this->preference
        ]);
    }

    public function weeklyWorkingHoursReportAction()
    {
        $request = $this->getRequest();
        if ($request->isPost()) {
            try {
                $data = $request->getPost();
                $list = $this->repository->fetchWeeklyWorkingHoursReport($data);
                $days = $this->repository->getDays();

                return new JsonModel(['success' => true, 'data' => $list, 'days' => $days, 'message' => null]);
            } catch (Exception $e) {
                return new JsonModel(['success' => false, 'data' => null, 'message' => $e->getMessage()]);
            }
        }

        return $this->stickFlashMessagesTo([
            'searchValues' => ApplicationHelper::getSearchData($this->adapter),
            'acl' => $this->acl,
            'employeeDetail' => $this->storageData['employee_detail'],
            'preference' => $this->preference
        ]);
    }

    public function rosterReportAction()
    {

        $request = $this->getRequest();
        if ($request->isPost()) {
            try {
                $postedData = $request->getPost();
                $from_date = date("d-M-y", strtotime($postedData['fromDate']));
                $to_date = date("d-M-y", strtotime($postedData['toDate']));
                $begin = new \DateTime($from_date);
                $end = new \DateTime($to_date);
                $end->modify('+1 day');

                $interval = \DateInterval::createFromDateString('1 day');
                $period = new \DatePeriod($begin, $interval, $end);

                $dates = array();

                foreach ($period as $dt) {
                    array_push($dates, $dt->format("d-M-y"));
                }
                $data = $this->repository->fetchRosterReport($postedData, $dates);
                return new JsonModel(['success' => true, 'data' => $data, 'dates' => $dates, 'error' => '']);
            } catch (Exception $e) {
                return new JsonModel(['success' => false, 'data' => [], 'dates' => $dates, 'error' => $e->getMessage()]);
            }
        }

        return [
            'searchValues' => EntityHelper::getSearchData($this->adapter),
            'acl' => $this->acl,
            'employeeDetail' => $this->storageData['employee_detail']
        ];
    }

    public function withOvertimeShivamAction()
    {
        $request = $this->getRequest();
        if ($request->isPost()) {
            try {
                $data = $request->getPost();
                $reportData = $this->repository->reportWithOTforShivam($data);
                return new JsonModel(['success' => true, 'data' => $reportData, 'error' => '']);
            } catch (Exception $e) {
                return new JsonModel(['success' => false, 'data' => [], 'error' => $e->getMessage()]);
            }
        }

        return $this->stickFlashMessagesTo([
            'searchValues' => EntityHelper::getSearchData($this->adapter),
            'linkToEmpower' => $this->repository->checkIfEmpowerTableExists() ? 1 : 0,
            'preference' => $this->preference,
            'acl' => $this->acl,
            'employeeDetail' => $this->storageData['employee_detail'],
        ]);
    }

    public function withOvertimeBotAction()
    {
        $request = $this->getRequest();
        if ($request->isPost()) {
            try {
                $data = $request->getPost();
                $reportData = $this->repository->reportWithOTforBot($data);
                return new JsonModel(['success' => true, 'data' => $reportData, 'error' => '']);
            } catch (Exception $e) {
                return new JsonModel(['success' => false, 'data' => [], 'error' => $e->getMessage()]);
            }
        }

        return $this->stickFlashMessagesTo([
            'searchValues' => EntityHelper::getSearchData($this->adapter),
            'linkToEmpower' => $this->repository->checkIfEmpowerTableExists() ? 1 : 0,
            'preference' => $this->preference,
            'acl' => $this->acl,
            'employeeDetail' => $this->storageData['employee_detail'],
        ]);
    }

    public function ageReportAction()
    {
        $request = $this->getRequest();

        if ($request->isPost()) {
            try {
                $data = $request->getPost();
                $reportData = $this->repository->checkAge($data);

                return new JsonModel(['success' => true, 'data' => $reportData, 'error' => '']);
            } catch (Exception $e) {
                return new JsonModel(['success' => false, 'data' => [], 'error' => $e->getMessage()]);
            }
        }

        return $this->stickFlashMessagesTo([
            'searchValues' => EntityHelper::getSearchData($this->adapter),
            'preference' => $this->preference,
            'acl' => $this->acl,
            'employeeDetail' => $this->storageData['employee_detail']
        ]);
    }

    public function contractExpiryReportAction()
    {
        $request = $this->getRequest();

        if ($request->isPost()) {
            try {
                $data = $request->getPost();
                $reportData = $this->repository->checkContract($data);

                return new JsonModel(['success' => true, 'data' => $reportData, 'error' => '']);
            } catch (Exception $e) {
                return new JsonModel(['success' => false, 'data' => [], 'error' => $e->getMessage()]);
            }
        }

        return $this->stickFlashMessagesTo([
            'searchValues' => EntityHelper::getSearchData($this->adapter),
            'preference' => $this->preference,
            'acl' => $this->acl,
            'employeeDetail' => $this->storageData['employee_detail']
        ]);
    }


    public function calendarReportAction()
    {
        $empRawList = EntityHelper::rawQueryResult($this->adapter, "select 
employee_id,employee_code||'-'||full_name as full_name
from hris_employees where status='E'
and Retired_Flag!='Y' and Resigned_Flag!='Y'");
        $empList = Helper::extractDbData($empRawList);

        $empRawProfileList = EntityHelper::rawQueryResult($this->adapter, "select 
e.employee_id
,e.PROFILE_PICTURE_ID
,ef.file_path
from
hris_employees e
left join HRIS_EMPLOYEE_FILE ef on (ef.file_code=e.PROFILE_PICTURE_ID)");

        $empProfile = array();
        foreach ($empRawProfileList as $empProList) {
            $empProfile[$empProList['EMPLOYEE_ID']] = $empProList['FILE_PATH'];
        }

        return $this->stickFlashMessagesTo([
            'searchValues' => EntityHelper::getSearchData($this->adapter),
            'preference' => $this->preference,
            'acl' => $this->acl,
            'employeeDetail' => $this->storageData['employee_detail'],
            "calendarType" => $this->storageData['preference']['calendarView'],
            'empList' => $empList,
            'empProfile' => $empProfile
        ]);
    }

    public function fetchEmployeeCalendarJsonFeedAction()
    {
        try {
            $request = $this->getRequest();
            if ($request->isPost()) {

                $employeeId = $this->employeeId;

                $dahsboardRepo = new DashboardRepository($this->adapter);

                $startDate = $this->getRequest()->getPost('startDate');
                $endDate = $this->getRequest()->getPost('endDate');
                $selEmp = $this->getRequest()->getPost('selEmp');
                if ($selEmp > 0) {
                    $employeeId = $selEmp;
                }
                $calendarData = $dahsboardRepo->fetchEmployeeCalendarData($employeeId, $startDate, $endDate);
                $calendarJsonFeedArray = Helper::extractDbData($calendarData);
                return new CustomViewModel($calendarJsonFeedArray);
            } else {
                throw new Exception("The request should be of type post");
            }
        } catch (Exception $e) {
            return new CustomViewModel(['success' => false, 'data' => [], 'error' => $e->getMessage()]);
        }
    }


    public function employeeWSBetnDateAction()
    {
        $request = $this->getRequest();
        if ($request->isPost()) {
            try {
                $data = $request->getPost();
                $reportData = $this->repository->workingSummaryBetnDateReport($data);
                return new JsonModel(['success' => true, 'data' => $reportData, 'error' => '']);
            } catch (Exception $e) {
                return new JsonModel(['success' => false, 'data' => [], 'error' => $e->getMessage()]);
            }
        }

        return $this->stickFlashMessagesTo([
            'searchValues' => EntityHelper::getSearchData($this->adapter),
            'preference' => $this->preference,
            'acl' => $this->acl,
            'employeeDetail' => $this->storageData['employee_detail']
        ]);
    }

    public function whereaboutsAction()
    {
        $request = $this->getRequest();
        if ($request->isPost()) {
            try {
                $data = $request->getPost();
                $reportData = $this->repository->whereaboutsReport($data);
                return new JsonModel(['success' => true, 'data' => $reportData, 'error' => '']);
            } catch (Exception $e) {
                return new JsonModel(['success' => false, 'data' => [], 'error' => $e->getMessage()]);
            }
        }

        return $this->stickFlashMessagesTo([
            'searchValues' => EntityHelper::getSearchData($this->adapter),
            'preference' => $this->preference,
            'acl' => $this->acl,
            'employeeDetail' => $this->storageData['employee_detail']
        ]);
    }

    public function workForceAction()
    {
        return $this->stickFlashMessagesTo([
            'preference' => $this->preference,
            'acl' => $this->acl,
            'employeeDetail' => $this->storageData['employee_detail']
        ]);
    }

    public function trainingReportAction()
    {
        $request = $this->getRequest();
        if ($request->isPost()) {
            try {
                $postedData = $request->getPost();
                $data = $this->repository->trainingReport($postedData['trainingId']);
                foreach ($data as &$item) { // loop through each element and add the key-value pair
                    $item["lDProgram"] = 'Training';
                }
                return new JsonModel(['success' => true, 'data' => $data, 'error' => null]);
            } catch (Exception $e) {
                return new JsonModel(['success' => false, 'data' => null, 'message' => $e->getMessage()]);
            }
        }

        $trainings = EntityHelper::getTableKVListWithSortOption($this->adapter, Training::TABLE_NAME, Training::TRAINING_ID, [Training::TRAINING_NAME], [Training::STATUS => 'E'], "TRAINING_NAME", "ASC", null, [-1 => "All Training"], true);
        $trainingSE = $this->getSelectElement(['name' => 'trainingId', 'id' => 'trainingId', 'class' => 'form-control reset-field', 'label' => 'Training'], $trainings);
        return [
            'fiscalYearSE' => $this->getFiscalYearSE(),
            'calenderType' => $this->getCanderType(),
            'preference' => $this->preference,
            'trainings' => $trainingSE,
            'acl' => $this->acl,
            'employeeDetail' => $this->storageData['employee_detail']
        ];
    }

    public function getHoursAction()
    {
        $request = $this->getRequest();
        try {
            $postedData = $request->getPost();
            $hourPerDay = $this->repository->trainingHours($postedData['trainingId']);
            $data = $hourPerDay['DAILY_TRAINING_HOUR'];
            return new JsonModel(['success' => true, 'data' => $data, 'error' => null]);
        } catch (Exception $e) {
            return new JsonModel(['success' => false, 'data' => null, 'message' => $e->getMessage()]);
        }
    }

    public  function maleFemaleRatioAction()
    {
        $request = $this->getRequest();
        if ($request->isPost()) {
            try {
                $postedData = $request->getPost();
                $data = $this->repository->maleFemaleReport($postedData);
                $headCountGender = $this->repository->fetchGenderHeadCount($postedData);
                return new JsonModel(['success' => true, 'data' => $data, 'headCountGender' => $headCountGender, 'error' => null]);
            } catch (Exception $e) {
                return new JsonModel(['success' => false, 'data' => null, 'message' => $e->getMessage()]);
            }
        }
        return [
            'preference' => $this->preference,
            'acl' => $this->acl,
            'employeeDetail' => $this->storageData['employee_detail']
        ];
    }
    public  function renumerationReportAction()
    {
        $request = $this->getRequest();
        if ($request->isPost()) {
            try {
                $postedData = $request->getPost();
                $data = $this->repository->renumerationReport($postedData);
                return new JsonModel(['success' => true, 'data' => $data, 'error' => null]);
            } catch (Exception $e) {
                return new JsonModel(['success' => false, 'data' => null, 'message' => $e->getMessage()]);
            }
        }
        return [
            'fiscalYearSE' => $this->getFiscalYearSE(),
            'preference' => $this->preference,
            'acl' => $this->acl,
            'employeeDetail' => $this->storageData['employee_detail']
        ];
    }

    public function turnOverReportAction()
    {
        $request = $this->getRequest();
        if ($request->isPost()) {
            try {
                $postedData = $request->getPost();
                $data = $this->repository->turnOverReport($postedData);
                return new JsonModel(['success' => true, 'data' => $data, 'error' => null]);
            } catch (Exception $e) {
                return new JsonModel(['success' => false, 'data' => null, 'message' => $e->getMessage()]);
            }
        }
        return [
            'fiscalYearSE' => $this->getFiscalYearSE(),
            'preference' => $this->preference,
            'acl' => $this->acl,
            'employeeDetail' => $this->storageData['employee_detail']
        ];
    }

    public function ageGenerationAction()
    {
        $request = $this->getRequest();
        if ($request->isPost()) {
            try {
                $postedData = $request->getPost();
                $data = $this->repository->ageByGenerationReport($postedData);
                return new JsonModel(['success' => true, 'data' => $data, 'error' => null]);
            } catch (Exception $e) {
                return new JsonModel(['success' => false, 'data' => null, 'message' => $e->getMessage()]);
            }
        }
        return [
            'preference' => $this->preference,
            'acl' => $this->acl,
            'employeeDetail' => $this->storageData['employee_detail']
        ];
    }
}
