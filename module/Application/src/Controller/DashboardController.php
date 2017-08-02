<?php

namespace Application\Controller;

use Application\Custom\CustomViewModel;
use Application\Helper\Helper;
use Application\Repository\DashboardRepository;
use Application\Repository\MonthRepository;
use Application\Repository\TaskRepository;
use Exception;
use Interop\Container\ContainerInterface;
use Setup\Repository\EmployeeRepository;
use Zend\Authentication\AuthenticationService;
use Zend\Db\Adapter\AdapterInterface;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

class DashboardController extends AbstractActionController {

    private $container;
    private $dashboardItems;
    private $adapter;
    private $roleId;
    private $userId;
    private $employeeId;
    private $auth;

    public function __construct(ContainerInterface $container) {
        $this->container = $container;
        $this->dashboardItems = $container->get("config")['dashboard-items'];
        $this->adapter = $container->get(AdapterInterface::class);


        $this->auth = new AuthenticationService();
        $this->employeeId = $this->auth->getStorage()->read()['employee_id'];
    }

    public function indexAction() {
        $dashboardRepo = new DashboardRepository($this->adapter);
        $data = [
            "upcomingHolidays" => $dashboardRepo->fetchUpcomingHolidays($this->employeeId),
            "employeeNotice" => $dashboardRepo->fetchEmployeeNotice($this->employeeId),
            "employeeTask" => $dashboardRepo->fetchEmployeeTask($this->employeeId),
            "employeesBirthday" => $dashboardRepo->fetchEmployeesBirthday(),
            'todoList' => $this->getTodoList(),
            "newEmployees" => $dashboardRepo->fetchJoinedEmployees(),
            "leftEmployees" => $dashboardRepo->fetchLeftEmployees(),
        ];
        $view = new ViewModel(Helper::addFlashMessagesToArray($this, $data));
        $view->setTemplate("dashboard/employee");
        return $view;
    }

    public function adminAction() {
        $dashboardRepo = new DashboardRepository($this->adapter);

        $data = [
            "employeeNotice" => $dashboardRepo->fetchEmployeeNotice(),
            "employeeTask" => $dashboardRepo->fetchEmployeeTask($this->employeeId),
            "employeesBirthday" => $dashboardRepo->fetchEmployeesBirthday(),
            "employeeList" => $dashboardRepo->fetchAllEmployee(),
            "headCountGender" => $dashboardRepo->fetchGenderHeadCount(),
            "headCountDepartment" => $dashboardRepo->fetchDepartmentHeadCount(),
            "headCountLocation" => $dashboardRepo->fetchLocationHeadCount(),
            "departmentAttendance" => $dashboardRepo->fetchDepartmentAttendance(),
            'todoList' => $this->getTodoList(),
            "upcomingHolidays" => $dashboardRepo->fetchUpcomingHolidays(),
            "employeeContracts" => $dashboardRepo->fetchEmployeeContracts(),
            "newEmployees" => $dashboardRepo->fetchJoinedEmployees(),
            "leftEmployees" => $dashboardRepo->fetchLeftEmployees(),
        ];
        $view = new ViewModel(Helper::addFlashMessagesToArray($this, $data));
        $view->setTemplate("dashboard/hrm");
        return $view;
    }

    public function branchManagerAction() {

        $dashboardRepo = new DashboardRepository($this->adapter);
        $data = [
            "employeeNotice" => $dashboardRepo->fetchEmployeeNotice($this->employeeId),
            "employeeTask" => $dashboardRepo->fetchEmployeeTask($this->employeeId),
            "employeesBirthday" => $dashboardRepo->fetchEmployeesBirthday(),
            "employeeList" => $dashboardRepo->fetchAllEmployee($this->employeeId),
            "headCountGender" => $dashboardRepo->fetchGenderHeadCount(),
            "headCountDepartment" => $dashboardRepo->fetchDepartmentHeadCount(),
            "headCountLocation" => $dashboardRepo->fetchLocationHeadCount(),
            "departmentAttendance" => $dashboardRepo->fetchDepartmentAttendance(),
            'todoList' => $this->getTodoList(),
            "upcomingHolidays" => $dashboardRepo->fetchUpcomingHolidays($this->employeeId),
            "newEmployees" => $dashboardRepo->fetchJoinedEmployees(),
            "leftEmployees" => $dashboardRepo->fetchLeftEmployees(),
        ];
        $view = new ViewModel(Helper::addFlashMessagesToArray($this, $data));
        $view->setTemplate("dashboard/branch-manager");
        return $view;
    }

    public function fetchEmployeeCalendarDataAction() {
        try {
            $request = $this->getRequest();
            if ($request->isPost()) {

                $auth = new AuthenticationService();
                $employeeId = $auth->getStorage()->read()['employee_id'];
                $dahsboardRepo = new DashboardRepository($this->adapter);

                $calendarData = $dahsboardRepo->fetchEmployeeCalendarData($employeeId);
                return new CustomViewModel(['success' => true, 'data' => $calendarData, 'error' => '']);
            } else {
                throw new Exception("The request should be of type post");
            }
        } catch (Exception $e) {
            return new CustomViewModel(['success' => false, 'data' => [], 'error' => $e->getMessage()]);
        }
    }

    public function fetchEmployeeCalendarJsonFeedAction() {
        try {
            $request = $this->getRequest();
            if ($request->isPost()) {

                $auth = new AuthenticationService();
                $employeeId = $auth->getStorage()->read()['employee_id'];
                $dahsboardRepo = new DashboardRepository($this->adapter);

                $startDate = $this->getRequest()->getPost('start');
                $endDate = $this->getRequest()->getPost('end');
                $calendarData = $dahsboardRepo->fetchEmployeeCalendarData($employeeId, $startDate, $endDate);
                $calendarJsonFeedArray = [];
                foreach ($calendarData as $eventData) {
                    if ('ABSENT' == $eventData['ATTENDANCE_STATUS']) {
                        $calendarJsonFeedArray[] = [
                            'title' => 'Absent',
                            'start' => $eventData['MONTH_DAY'],
                            'textColor' => '#cc0000',
                            'backgroundColor' => '#fff'
                        ];
                    }

                    if ($eventData['ATTENDANCE_DT']) {
                        $inOutTitle = "";
                        if ($eventData['IN_TIME']) {
                            $inOutTitle .= $eventData['IN_TIME'];
                        }
                        if ($eventData['OUT_TIME']) {
                            $inOutTitle .= ' ' . $eventData['OUT_TIME'];
                        }
                        // In/Out
                        $calendarJsonFeedArray[] = [
                            'title' => $inOutTitle,
                            'start' => $eventData['ATTENDANCE_DT'],
                            'textColor' => '#616161',
                            'backgroundColor' => '#fff'
                        ];

                        // Training
                        if ($eventData['TRAINING_NAME']) {
                            $calendarJsonFeedArray[] = [
                                'title' => $eventData['TRAINING_NAME'],
                                'start' => $eventData['TRAINING_START_DATE'],
                                'end' => $eventData['TRAINING_END_DATE'],
                                'textColor' => '#fff',
                                'backgroundColor' => '#39c7b8',
                            ];
                        }
                        // Leave
                        if ($eventData['LEAVE_ENAME']) {
                            $calendarJsonFeedArray[] = [
                                'title' => $eventData['LEAVE_ENAME'],
                                'start' => $eventData['ATTENDANCE_DT'],
                                'textColor' => '#fff',
                                'backgroundColor' => '#a7aeaf',
                            ];
                        }
                        // Tour
                        if ($eventData['TRAVEL_ID']) {
                            if ($eventData['MONTH_DAY'] >= $eventData['TRAVEL_FROM_DATE'] || $eventData['MONTH_DAY'] <= $eventData['TRAVEL_TO_DATE']) {
                                $calendarJsonFeedArray[] = [
                                    'title' => $eventData['DESTINATION'],
                                    'start' => $eventData['MONTH_DAY'],
                                    'textColor' => '#fff',
                                    'backgroundColor' => '#e89c0a',
                                ];
                            }
                        }

                        if ($eventData['HOLIDAY_ID']) {
                            $calendarJsonFeedArray[] = [
                                'title' => $eventData['HOLIDAY_ENAME'],
                                'start' => $eventData['MONTH_DAY'],
                                'textColor' => '#fff',
                                'backgroundColor' => '#eaea2a',
                            ];
                        }
                    }
                }
//                $upcomingLeaves = $dahsboardRepo->fetchUpcomingLeaves($employeeId);
//                foreach ($upcomingLeaves as $upcomingLeave) {
//                    for ($i = 0; $i < $upcomingLeave['DIFF']; $i++) {
//                        $d = \DateTime::createFromFormat(Helper::PHP_DATE_FORMAT, $upcomingLeave['START_DATE']);
//                        $d->add(new \DateInterval("P{$i}D"));
//                        $calendarJsonFeedArray[] = [
//                            'title' => $upcomingLeave['LEAVE_ENAME'],
//                            'start' => $d->format(Helper::PHP_DATE_FORMAT),
//                            'textColor' => '#fff',
//                            'backgroundColor' => '#a7aeaf',
//                        ];
//                    }
//                }


                //return new CustomViewModel(['success' => true, 'data' => $calendarJsonFeedArray, 'error' => '']);
                return new CustomViewModel($calendarJsonFeedArray);
            } else {
                throw new Exception("The request should be of type post");
            }
        } catch (Exception $e) {
            return new CustomViewModel(['success' => false, 'data' => [], 'error' => $e->getMessage()]);
        }
    }

    private function getTodoList() {
        $taskRepo = new TaskRepository($this->adapter);
        $result = $taskRepo->fetchEmployeeTask($this->employeeId);
        $list = [];
        foreach ($result as $row) {
            $nrow['id'] = $row['TASK_ID'];
            $nrow['title'] = $row['TASK_TITLE'];
            $nrow['description'] = $row['TASK_EDESC'];
            $nrow['dueDate'] = $row['END_DATE'];
            if ($row['STATUS'] == 'C') {
                $done = true;
            } else {
                $done = false;
            }
            $nrow['done'] = $done;
            array_push($list, $nrow);
        }
        return $list;
    }

    public function fetchEmployeeDashBoardDetailsAction() {
        try {
            $request = $this->getRequest();
            if ($request->isPost()) {
                $monthRepo = new MonthRepository($this->adapter);
                $month = $monthRepo->fetchByDate(Helper::getcurrentExpressionDate());
                $dashboardRepo = new DashboardRepository($this->adapter);
                $employeeDetail = $dashboardRepo->fetchEmployeeDashboardDetail($this->employeeId, $month->FROM_DATE, Helper::getCurrentDate());
                return new CustomViewModel(['success' => true, 'data' => $employeeDetail, 'error' => '']);
            } else {
                throw new Exception("The request should be of type post");
            }
        } catch (Exception $e) {
            return new CustomViewModel(['success' => false, 'data' => [], 'error' => $e->getMessage()]);
        }
    }

    public function fetchAdminDashBoardDetailsAction() {
        try {
            $request = $this->getRequest();
            if ($request->isPost()) {

                $dashboardRepo = new DashboardRepository($this->adapter);
                $employeeDetail = $dashboardRepo->fetchAdminDashboardDetail($this->employeeId, Helper::getCurrentDate());

                return new CustomViewModel(['success' => true, 'data' => $employeeDetail, 'error' => '']);
            } else {
                throw new Exception("The request should be of type post");
            }
        } catch (Exception $e) {
            return new CustomViewModel(['success' => false, 'data' => [], 'error' => $e->getMessage()]);
        }
    }

    public function fetchManagerDashBoardDetailsAction() {
        try {
            $request = $this->getRequest();
            if ($request->isPost()) {

                $dashboardRepo = new DashboardRepository($this->adapter);

                $managetDashboardDetails = $dashboardRepo->fetchManagerAttendanceDetail($this->employeeId);

                $employeeDetail = $dashboardRepo->fetchManagerDashboardDetail($this->employeeId, Helper::getCurrentDate());


                return new CustomViewModel(['success' => true, 'data' => [$managetDashboardDetails, $employeeDetail], 'error' => '']);
            } else {
                throw new Exception("The request should be of type post");
            }
        } catch (Exception $e) {
            return new CustomViewModel(['success' => false, 'data' => [], 'error' => $e->getMessage()]);
        }
    }

}
