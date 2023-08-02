<?php

namespace SelfService;

use Application\Controller\ControllerFactory;
use SelfService\Controller\AttendanceRequest;
use SelfService\Controller\Birthday;
use SelfService\Controller\Holiday;
use SelfService\Controller\Leave;
use SelfService\Controller\LeaveNotification;
use SelfService\Controller\LeaveRequest;
use SelfService\Controller\LoanRequest;
use SelfService\Controller\MyAttendance;
use SelfService\Controller\OvertimeRequest;
use SelfService\Controller\Payroll;
use SelfService\Controller\PaySlipPrevious;
use SelfService\Controller\PerformanceAppraisal;
use SelfService\Controller\Profile;
use SelfService\Controller\Service;
use SelfService\Controller\SubordinatesReview;
use SelfService\Controller\TrainingList;
use SelfService\Controller\TrainingRequest;
use SelfService\Controller\TravelNotification;
use SelfService\Controller\TravelRequest;
use SelfService\Controller\WorkOnDayoff;
use SelfService\Controller\WorkOnHoliday;
use SelfService\Controller\RoleTransfer;
use Zend\Router\Http\Segment;

return [
    'router' => [
        'routes' => [
            'myattendance' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/selfservice/myattendance[/:action[/:id]]',
                    'constants' => [
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'id' => '[0-9]+',
                    ],
                    'defaults' => [
                        'controller' => MyAttendance::class,
                        'action' => 'index',
                    ]
                ],
            ],
            'holiday' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/selfservice/holiday[/:action[/:id]]',
                    'constants' => [
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'id' => '[0-9]+',
                    ],
                    'defaults' => [
                        'controller' => Holiday::class,
                        'action' => 'index',
                    ]
                ],
            ],
            'leave' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/selfservice/leave[/:action[/:id]]',
                    'constants' => [
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'id' => '[0-9]+',
                    ],
                    'defaults' => [
                        'controller' => Leave::class,
                        'action' => 'index',
                    ]
                ],
            ],
            'leaverequest' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/selfservice/leaverequest[/:action[/:id]]',
                    'constants' => [
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'id' => '[0-9]+',
                    ],
                    'defaults' => [
                        'controller' => LeaveRequest::class,
                        'action' => 'index',
                    ]
                ],
            ],
            'attendancerequest' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/selfservice/attendancerequest[/:action[/:id]]',
                    'constants' => [
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'id' => '[0-9]+',
                    ],
                    'defaults' => [
                        'controller' => AttendanceRequest::class,
                        'action' => 'index',
                    ]
                ],
            ],
            'service' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/selfservice/service[/:action[/:id]]',
                    'constants' => [
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'id' => '[0-9]+',
                    ],
                    'defaults' => [
                        'controller' => Service::class,
                        'action' => 'index',
                    ]
                ],
            ],
            'profile' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/selfservice/profile[/:action[/:id[/:tab]]]',
                    'constants' => [
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'id' => '[0-9]+',
                    ],
                    'defaults' => [
                        'controller' => Profile::class,
                        'action' => 'index',
                    ]
                ],
            ],
            'payroll' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/selfservice/payroll[/:action]',
                    'constants' => [
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                    ],
                    'defaults' => [
                        'controller' => Payroll::class,
                        'action' => 'payslip',
                    ]
                ],
            ],
            'loanRequest' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/selfservice/loanRequest[/:action[/:id]]',
                    'constants' => [
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'id' => '[0-9]+',
                    ],
                    'defaults' => [
                        'controller' => LoanRequest::class,
                        'action' => 'index',
                    ]
                ],
            ],
            'trainingList' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/selfservice/trainingList[/:action[/:employeeId][/:trainingId]]',
                    'constants' => [
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'id' => '[0-9]+',
                    ],
                    'defaults' => [
                        'controller' => TrainingList::class,
                        'action' => 'index',
                    ]
                ],
            ],
            'travelRequest' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/selfservice/travelRequest[/:action[/:id]]',
                    'constants' => [
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'id' => '[0-9]+',
                    ],
                    'defaults' => [
                        'controller' => TravelRequest::class,
                        'action' => 'index',
                    ]
                ],
            ],
            'workOnHoliday' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/selfservice/workOnHoliday[/:action[/:id]]',
                    'constants' => [
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'id' => '[0-9]+',
                    ],
                    'defaults' => [
                        'controller' => WorkOnHoliday::class,
                        'action' => 'index',
                    ]
                ],
            ],
            'workOnDayoff' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/selfservice/workOnDayoff[/:action[/:id]]',
                    'constants' => [
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'id' => '[0-9]+',
                    ],
                    'defaults' => [
                        'controller' => WorkOnDayoff::class,
                        'action' => 'index',
                    ]
                ],
            ],
            'performanceAppraisal' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/selfservice/performanceAppraisal[/:action[/:appraisalId]]',
                    'constants' => [
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'id' => '[0-9]+',
                    ],
                    'defaults' => [
                        'controller' => PerformanceAppraisal::class,
                        'action' => 'index',
                    ]
                ],
            ],
            'leaveNotification' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/selfservice/leaveNotification[/:action[/:id]]',
                    'constants' => [
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'id' => '[0-9]+',
                    ],
                    'defaults' => [
                        'controller' => LeaveNotification::class,
                        'action' => 'index',
                    ]
                ],
            ],
            'travelNotification' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/selfservice/travelNotification[/:action[/:id]]',
                    'constants' => [
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'id' => '[0-9]+',
                    ],
                    'defaults' => [
                        'controller' => TravelNotification::class,
                        'action' => 'index',
                    ]
                ],
            ],
            'trainingRequest' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/selfservice/trainingRequest[/:action[/:id]]',
                    'constants' => [
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'id' => '[0-9]+',
                    ],
                    'defaults' => [
                        'controller' => TrainingRequest::class,
                        'action' => 'index',
                    ]
                ],
            ],
            'overtimeRequest' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/selfservice/overtimeRequest[/:action[/:id]]',
                    'constants' => [
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'id' => '[0-9]+',
                    ],
                    'defaults' => [
                        'controller' => OvertimeRequest::class,
                        'action' => 'index',
                    ]
                ],
            ],
            'subordinatesReview' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/selfservice/subordinatesReview[/:action[/:appraisalId]]',
                    'constants' => [
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'id' => '[0-9]+',
                    ],
                    'defaults' => [
                        'controller' => SubordinatesReview::class,
                    ]
                ],
            ],
            'birthday' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/selfservice/birthday[/:action[/:id]]',
                    'constants' => [
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'id' => '[0-9]+',
                    ],
                    'defaults' => [
                        'controller' => Birthday::class,
                        'action' => 'index',
                    ]
                ],
            ],
            'payslip-previous' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/selfservice/payslip-previous[/:action[/:id[/:mcode]]]',
                    'constants' => [
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'id' => '[0-9]+',
                    ],
                    'defaults' => [
                        'controller' => PaySlipPrevious::class,
                        'action' => 'index',
                    ]
                ],
            ],
            'roleTransfer' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/selfservice/roleTransfer[/:action[/:id]]',
                    'constants' => [
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'id' => '[0-9]+',
                    ],
                    'defaults' => [
                        'controller' => RoleTransfer::class,
                        'action' => 'index',
                    ]
                ],
            ],
        ],
    ],
    'navigation' => [
        'myattendance' => [
            [
                'label' => 'Attendance',
                'route' => 'myattendance',
            ],
            [
                'label' => 'Attendance',
                'route' => 'myattendance',
                'pages' => [
                    [
                        'label' => 'List',
                        'route' => 'myattendance',
                        'action' => 'index',
                    ],
                    [
                        'label' => 'Entry',
                        'route' => 'myattendance',
                        'action' => 'add',
                    ],
                    [
                        'label' => 'Edit',
                        'route' => 'myattendance',
                        'action' => 'edit',
                    ],
                ],
            ],
        ],
        'holiday' => [
            [
                'label' => 'Holiday',
                'route' => 'holiday',
            ],
            [
                'label' => 'Holiday',
                'route' => 'holiday',
                'pages' => [
                    [
                        'label' => 'List',
                        'route' => 'holiday',
                        'action' => 'index',
                    ],
                    [
                        'label' => 'Add',
                        'route' => 'holiday',
                        'action' => 'add',
                    ],
                    [
                        'label' => 'Edit',
                        'route' => 'holiday',
                        'action' => 'edit',
                    ],
                ],
            ],
        ],
        'leave' => [
            [
                'label' => 'Leave',
                'route' => 'leave',
            ],
            [
                'label' => 'Leave',
                'route' => 'leave',
                'pages' => [
                    [
                        'label' => 'List',
                        'route' => 'leave',
                        'action' => 'index',
                    ],
                    [
                        'label' => 'Add',
                        'route' => 'leave',
                        'action' => 'add',
                    ],
                    [
                        'label' => 'Edit',
                        'route' => 'leave',
                        'action' => 'edit',
                    ],
                ],
            ],
        ],
        'leaverequest' => [
            [
                'label' => 'Leave Request',
                'route' => 'leaverequest',
            ],
            [
                'label' => 'Leave Request',
                'route' => 'leaverequest',
                'pages' => [
                    [
                        'label' => 'List',
                        'route' => 'leaverequest',
                        'action' => 'index',
                    ],
                    [
                        'label' => 'Add',
                        'route' => 'leaverequest',
                        'action' => 'add',
                    ],
                    [
                        'label' => 'Edit',
                        'route' => 'leaverequest',
                        'action' => 'edit',
                    ],
                    [
                        'label' => 'Detail',
                        'route' => 'leaverequest',
                        'action' => 'view',
                    ],
                ],
            ],
        ],
        'attendancerequest' => [
            [
                'label' => 'Attendance Request',
                'route' => 'attendancerequest',
            ],
            [
                'label' => 'Attendance Request',
                'route' => 'attendancerequest',
                'pages' => [
                    [
                        'label' => 'List',
                        'route' => 'attendancerequest',
                        'action' => 'index',
                    ],
                    [
                        'label' => 'Add',
                        'route' => 'attendancerequest',
                        'action' => 'add',
                    ],
                    [
                        'label' => 'Edit',
                        'route' => 'attendancerequest',
                        'action' => 'edit',
                    ],
                    [
                        'label' => 'Detail',
                        'route' => 'attendancerequest',
                        'action' => 'view',
                    ],
                ],
            ],
        ],
        'service' => [
            [
                'label' => 'Service',
                'route' => 'service',
            ],
            [
                'label' => 'Service',
                'route' => 'service',
                'pages' => [
                    [
                        'label' => 'History',
                        'route' => 'service',
                        'action' => 'index',
                    ],
                    [
                        'label' => 'Detail',
                        'route' => 'service',
                        'action' => 'view',
                    ],
                    [
                        'label' => 'Edit',
                        'route' => 'service',
                        'action' => 'edit',
                    ],
                ],
            ],
        ],
        'profile' => [
            [
                'label' => 'Profile',
                'route' => 'profile',
            ],
            [
                'label' => 'Profile',
                'route' => 'profile',
                'pages' => [
                    [
                        'label' => 'Detail',
                        'route' => 'profile',
                        'action' => 'index',
                    ],
                    [
                        'label' => 'Add',
                        'route' => 'profile',
                        'action' => 'add',
                    ],
                    [
                        'label' => 'Edit',
                        'route' => 'profile',
                        'action' => 'edit',
                    ],
                ],
            ],
        ],
        'payroll' => [
            [
                'label' => 'Payroll',
                'route' => 'payroll',
            ],
            [
                'label' => 'Payroll',
                'route' => 'payroll',
                'pages' => [
                    [
                        'label' => 'Payslip',
                        'route' => 'payroll',
                        'action' => 'payslip',
                    ],
                    [
                        'label' => 'Taxslip',
                        'route' => 'payroll',
                        'action' => 'taxslip',
                    ],
                    [
                        'label' => 'Salary Sheet',
                        'route' => 'payroll',
                        'action' => 'salarySheet',
                    ],
                    [
                        'label' => 'Tax Sheet',
                        'route' => 'payroll',
                        'action' => 'taxSheet',
                    ],
                    [
                        'label' => 'Tax Sheet Yearly',
                        'route' => 'payroll',
                        'action' => 'taxYearly',
                    ],
                ],
            ],
        ],
        'loanRequest' => [
            [
                'label' => 'Loan Request',
                'route' => 'loanRequest',
            ],
            [
                'label' => 'Loan Request',
                'route' => 'loanRequest',
                'pages' => [
                    [
                        'label' => 'Detail',
                        'route' => 'loanRequest',
                        'action' => 'index',
                    ],
                    [
                        'label' => 'Add',
                        'route' => 'loanRequest',
                        'action' => 'add',
                    ],
                    [
                        'label' => 'Edit',
                        'route' => 'loanRequest',
                        'action' => 'edit',
                    ],
                    [
                        'label' => 'Detail',
                        'route' => 'loanRequest',
                        'action' => 'view',
                    ],
                ],
            ],
        ],
        'trainingList' => [
            [
                'label' => 'Training List',
                'route' => 'trainingList',
            ],
            [
                'label' => 'Training List',
                'route' => 'trainingList',
                'pages' => [
                    [
                        'label' => 'List',
                        'route' => 'trainingList',
                        'action' => 'index',
                    ],
                    [
                        'label' => 'Add',
                        'route' => 'trainingList',
                        'action' => 'add',
                    ],
                    [
                        'label' => 'Detail',
                        'route' => 'trainingList',
                        'action' => 'view',
                    ],
                ],
            ],
        ],
        'travelRequest' => [
            [
                'label' => 'Travel Request',
                'route' => 'travelRequest',
            ],
            [
                'label' => 'Travel Request',
                'route' => 'travelRequest',
                'pages' => [
                    [
                        'label' => 'Advance List',
                        'route' => 'travelRequest',
                        'action' => 'index',
                    ],
                    [
                        'label' => 'Add Advance',
                        'route' => 'travelRequest',
                        'action' => 'add',
                    ],
                    [
                        'label' => 'Edit Advance',
                        'route' => 'travelRequest',
                        'action' => 'edit',
                    ],
                    [
                        'label' => 'Advance Detail',
                        'route' => 'travelRequest',
                        'action' => 'view',
                    ],
                    [
                        'label' => 'Expense List',
                        'route' => 'travelRequest',
                        'action' => 'expense',
                    ],
                    [
                        'label' => 'Add Expense',
                        'route' => 'travelRequest',
                        'action' => 'expenseAdd',
                    ],
                    [
                        'label' => 'Expense Detail',
                        'route' => 'travelRequest',
                        'action' => 'expenseView',
                    ],
                ],
            ],
        ],
        'workOnHoliday' => [
            [
                'label' => 'Work on Holiday Request',
                'route' => 'workOnHoliday',
            ],
            [
                'label' => 'Work on Holiday Request',
                'route' => 'workOnHoliday',
                'pages' => [
                    [
                        'label' => 'List',
                        'route' => 'workOnHoliday',
                        'action' => 'index',
                    ],
                    [
                        'label' => 'Add',
                        'route' => 'workOnHoliday',
                        'action' => 'add',
                    ],
                    [
                        'label' => 'Detail',
                        'route' => 'workOnHoliday',
                        'action' => 'view',
                    ],
                ],
            ],
        ],
        'workOnDayoff' => [
            [
                'label' => 'Work on Day-off Request',
                'route' => 'workOnDayoff',
            ],
            [
                'label' => 'Work on Day-off Request',
                'route' => 'workOnDayoff',
                'pages' => [
                    [
                        'label' => 'List',
                        'route' => 'workOnDayoff',
                        'action' => 'index',
                    ],
                    [
                        'label' => 'Add',
                        'route' => 'workOnDayoff',
                        'action' => 'add',
                    ],
                    [
                        'label' => 'Detail',
                        'route' => 'workOnDayoff',
                        'action' => 'view',
                    ],
                ],
            ],
        ],
        'performanceAppraisal' => [
            [
                'label' => 'Performance Appraisal',
                'route' => 'performanceAppraisal',
            ],
            [
                'label' => 'Performance Appraisal',
                'route' => 'performanceAppraisal',
                'pages' => [
                    [
                        'label' => 'List',
                        'route' => 'performanceAppraisal',
                        'action' => 'index',
                    ],
                    [
                        'label' => 'Add',
                        'route' => 'performanceAppraisal',
                        'action' => 'add',
                    ],
                    [
                        'label' => 'Detail',
                        'route' => 'performanceAppraisal',
                        'action' => 'view',
                    ],
                ],
            ],
        ],
        'leaveNotification' => [
            [
                'label' => 'Leave Notification',
                'route' => 'leaveNotification',
            ],
            [
                'label' => 'Leave Notification',
                'route' => 'leaveNotification',
                'pages' => [
                    [
                        'label' => 'List',
                        'route' => 'leaveNotification',
                        'action' => 'index',
                    ],
                    [
                        'label' => 'Add',
                        'route' => 'leaveNotification',
                        'action' => 'add',
                    ],
                    [
                        'label' => 'Detail',
                        'route' => 'leaveNotification',
                        'action' => 'view',
                    ],
                ],
            ],
        ],
        'travelNotification' => [
            [
                'label' => 'Travel Notification',
                'route' => 'travelNotification',
            ],
            [
                'label' => 'Travel Notification',
                'route' => 'travelNotification',
                'pages' => [
                    [
                        'label' => 'List',
                        'route' => 'travelNotification',
                        'action' => 'index',
                    ],
                    [
                        'label' => 'Add',
                        'route' => 'travelNotification',
                        'action' => 'add',
                    ],
                    [
                        'label' => 'Detail',
                        'route' => 'travelNotification',
                        'action' => 'view',
                    ],
                ],
            ],
        ],
        'trainingRequest' => [
            [
                'label' => 'Training Request',
                'route' => 'trainingRequest',
            ],
            [
                'label' => 'Training Request',
                'route' => 'trainingRequest',
                'pages' => [
                    [
                        'label' => 'List',
                        'route' => 'trainingRequest',
                        'action' => 'index',
                    ],
                    [
                        'label' => 'Add',
                        'route' => 'trainingRequest',
                        'action' => 'add',
                    ],
                    [
                        'label' => 'Detail',
                        'route' => 'trainingRequest',
                        'action' => 'view',
                    ],
                ],
            ],
        ],
        'overtimeRequest' => [
            [
                'label' => 'Overtime Request',
                'route' => 'overtimeRequest',
            ],
            [
                'label' => 'Overtime Request',
                'route' => 'overtimeRequest',
                'pages' => [
                    [
                        'label' => 'List',
                        'route' => 'overtimeRequest',
                        'action' => 'index',
                    ],
                    [
                        'label' => 'Add',
                        'route' => 'overtimeRequest',
                        'action' => 'add',
                    ],
                    [
                        'label' => 'Detail',
                        'route' => 'overtimeRequest',
                        'action' => 'view',
                    ],
                ],
            ],
        ],
        'birthday' => [
            [
                'label' => 'Birthdays',
                'route' => 'birthday',
            ],
            [
                'label' => 'Birthdays',
                'route' => 'birthday',
                'pages' => [
                    [
                        'label' => 'List',
                        'route' => 'birthday',
                        'action' => 'index',
                    ],
                    [
                        'label' => 'Wish',
                        'route' => 'birthday',
                        'action' => 'wish',
                    ],
                ],
            ],
        ],
        'payslip-previous' => [
            [
                'label' => 'Payslip-previous',
                'route' => 'payslip-previous',
            ],
            [
                'label' => 'Payslip-previous',
                'route' => 'payslip-previous',
                'pages' => [
                    [
                        'label' => 'Taxsheet',
                        'route' => 'payslip-previous',
                        'action' => 'taxsheet',
                    ],
                    [
                        'label' => 'Payslip',
                        'route' => 'payslip-previous',
                        'action' => 'payslip',
                    ],
                ],
            ],
        ],
        'roleTransfer' => [
            [
                'label' => 'Role Transfer',
                'route' => 'roleTransfer',
                'action' => 'index',
            ],
            [
                'label' => 'Role Transfer',
                'route' => 'roleTransfer',
                'pages' => [
                    [
                        'label' => 'Role Transfer',
                        'route' => 'roleTransfer',
                        'action' => 'index',
                    ],
                ],
            ],
        ],
    ],
    'controllers' => [
        'factories' => [
            MyAttendance::class => ControllerFactory::class,
            Holiday::class => ControllerFactory::class,
            Leave::class => ControllerFactory::class,
            LeaveRequest::class => ControllerFactory::class,
            AttendanceRequest::class => ControllerFactory::class,
            Profile::class => ControllerFactory::class,
            Service::class => ControllerFactory::class,
            Payroll::class => ControllerFactory::class,
            LoanRequest::class => ControllerFactory::class,
            TrainingList::class => ControllerFactory::class,
            TravelRequest::class => ControllerFactory::class,
            WorkOnHoliday::class => ControllerFactory::class,
            WorkOnDayoff::class => ControllerFactory::class,
            PerformanceAppraisal::class => ControllerFactory::class,
            LeaveNotification::class => ControllerFactory::class,
            TravelNotification::class => ControllerFactory::class,
            TrainingRequest::class => ControllerFactory::class,
            OvertimeRequest::class => ControllerFactory::class,
            SubordinatesReview::class => ControllerFactory::class,
            Birthday::class => ControllerFactory::class,
            PaySlipPrevious::class => ControllerFactory::class,
            RoleTransfer::class => ControllerFactory::class,
        ],
    ],
    'view_manager' => [
        'template_map' => [
            'mysql/payslip' => __DIR__ . '/../view/self-service/pay-slip-previous/payslip.phtml',
            'mysql/print-payslip' => __DIR__ . '/../view/self-service/pay-slip-previous/print-payslip.phtml',
            'mysql/taxsheet' => __DIR__ . '/../view/self-service/pay-slip-previous/taxsheet.phtml',
            'oracle/payslip' => __DIR__ . '/../view/self-service/pay-slip-previous/payslip-oci.phtml',
            'oracle/print-payslip' => __DIR__ . '/../view/self-service/pay-slip-previous/print-payslip-oci.phtml',
            'oracle/taxsheet' => __DIR__ . '/../view/self-service/pay-slip-previous/taxsheet-oci.phtml',
        ],
        'template_path_stack' => [
            __DIR__ . '/../view',
        ]
    ]
];
