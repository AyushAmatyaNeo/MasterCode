<?php

namespace ManagerService;

use Application\Controller\ControllerFactory;
use ManagerService\Controller\MapsController;
use ManagerService\Controller\AppraisalEvaluation;
use ManagerService\Controller\AppraisalFinalReview;
use ManagerService\Controller\AppraisalReview;
use ManagerService\Controller\AttendanceApproveController;
use ManagerService\Controller\DayoffWorkApproveController;
use ManagerService\Controller\HolidayWorkApproveController;
use ManagerService\Controller\LeaveApproveController;
use ManagerService\Controller\LoanApproveController;
use ManagerService\Controller\ManagerReportController;
use ManagerService\Controller\OvertimeApproveController;
use ManagerService\Controller\Subordinate;
use ManagerService\Controller\TrainingApproveController;
use ManagerService\Controller\TravelApproveController;
use Zend\Router\Http\Segment;

return [
    'router' => [
        'routes' => [
            'leaveapprove' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/managerservice/leaveapprove[/:action[/:id][/:role]]',
                    'defaults' => [
                        'controller' => LeaveApproveController::class,
                        'action' => 'index'
                    ]
                ]
            ],
            'attedanceapprove' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/managerservice/attendanceapprove[/:action[/:id][/:role]]',
                    'defaults' => [
                        'controller' => AttendanceApproveController::class,
                        'action' => 'index'
                    ]
                ]
            ],
            'loanApprove' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/managerservice/loanApprove[/:action[/:id][/:role]]',
                    'defaults' => [
                        'controller' => LoanApproveController::class,
                        'action' => 'index'
                    ]
                ]
            ],
            'travelApprove' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/managerservice/travelApprove[/:action[/:id][/:role]]',
                    'defaults' => [
                        'controller' => TravelApproveController::class,
                        'action' => 'index'
                    ]
                ]
            ],
            'dayoffWorkApprove' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/managerservice/dayoffWorkApprove[/:action[/:id][/:role]]',
                    'defaults' => [
                        'controller' => DayoffWorkApproveController::class,
                        'action' => 'index'
                    ]
                ]
            ],
            'holidayWorkApprove' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/managerservice/holidayWorkApprove[/:action[/:id][/:role]]',
                    'defaults' => [
                        'controller' => HolidayWorkApproveController::class,
                        'action' => 'index'
                    ]
                ]
            ],
            'trainingApprove' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/managerservice/trainingApprove[/:action[/:id][/:role]]',
                    'defaults' => [
                        'controller' => TrainingApproveController::class,
                        'action' => 'index'
                    ]
                ]
            ],
            'overtimeApprove' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/managerservice/overtimeApprove[/:action[/:id][/:role]]',
                    'defaults' => [
                        'controller' => OvertimeApproveController::class,
                        'action' => 'index'
                    ]
                ]
            ],
            'appraisal-evaluation' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/managerservice/appraisalEvaluation[/:action[/:appraisalId][/:employeeId][/:tab]]',
                    'constants' => [
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'id' => '[0-9]+',
                    ],
                    'defaults' => [
                        'controller' => AppraisalEvaluation::class,
                        'action' => 'index',
                    ]
                ],
            ],
            'appraisal-review' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/managerservice/appraisalReview[/:action[/:appraisalId][/:employeeId][/:tab]]',
                    'constants' => [
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'id' => '[0-9]+',
                    ],
                    'defaults' => [
                        'controller' => AppraisalReview::class,
                        'action' => 'index',
                    ]
                ],
            ],
            'appraisal-final-review' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/managerservice/appraisalFinalReview[/:action[/:appraisalId][/:employeeId][/:tab]]',
                    'constants' => [
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'id' => '[0-9]+',
                    ],
                    'defaults' => [
                        'controller' => AppraisalFinalReview::class,
                        'action' => 'index',
                    ]
                ],
            ],
            'managerReport' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/managerservice/managerReport[/:action[/:id]]',
                    'constants' => [
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'id' => '[0-9]+',
                    ],
                    'defaults' => [
                        'controller' => ManagerReportController::class,
                        'action' => 'index',
                    ]
                ],
            ],
            'subordinate' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/managerservice/subordinate[/:action[/:id][/:tab]]',
                    'defaults' => [
                        'controller' => Subordinate::class,
                        'action' => 'index'
                    ]
                ]
            ],
            'location' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/managerservice/location[/:action]',
                    'defaults' => [
                        'controller' => MapsController::class,
                        'action' => 'index'
                    ]
                ]
            ],
        ]
    ],
    'navigation' => [
        'leaveapprove' => [
            [
                'label' => 'Leave Request',
                'route' => 'leaveapprove',
            ],
            [
                'label' => 'Leave Request',
                'route' => 'leaveapprove',
                'pages' => [
                    [
                        'label' => 'List',
                        'route' => 'leaveapprove',
                        'action' => 'index',
                    ],
                    [
                        'label' => 'List',
                        'route' => 'leaveapprove',
                        'action' => 'status',
                    ],
                    [
                        'label' => 'Edit',
                        'route' => 'leaveapprove',
                        'action' => 'edit',
                    ],
                    [
                        'label' => 'View',
                        'route' => 'leaveapprove',
                        'action' => 'view',
                    ],
                ]
            ]
        ],
        'attedanceapprove' => [
            [
                'label' => 'Attendance Request',
                'route' => 'attedanceapprove',
            ],
            [
                'label' => 'Attendance Request',
                'route' => 'attedanceapprove',
                'pages' => [
                    [
                        'label' => 'List',
                        'route' => 'attedanceapprove',
                        'action' => 'index',
                    ],
                    [
                        'label' => 'List',
                        'route' => 'attedanceapprove',
                        'action' => 'status',
                    ],
                    [
                        'label' => 'View',
                        'route' => 'attedanceapprove',
                        'action' => 'view',
                    ],
                ]
            ]
        ],
        'loanApprove' => [
            [
                'label' => 'Loan Request',
                'route' => 'loanApprove',
            ],
            [
                'label' => 'Loan Request',
                'route' => 'loanApprove',
                'pages' => [
                    [
                        'label' => 'List',
                        'route' => 'loanApprove',
                        'action' => 'index',
                    ],
                    [
                        'label' => 'List',
                        'route' => 'loanApprove',
                        'action' => 'status',
                    ],
                    [
                        'label' => 'View',
                        'route' => 'loanApprove',
                        'action' => 'view',
                    ],
                ]
            ]
        ],
        'salaryReview' => [
            [
                'label' => 'SalaryReview',
                'route' => 'salaryReview',
            ],
            [
                'label' => 'SalaryReview',
                'route' => 'salaryReview',
                'pages' => [
                    [
                        'label' => 'List',
                        'route' => 'salaryReview',
                        'action' => 'index',
                    ], [
                        'label' => 'Add',
                        'route' => 'salaryReview',
                        'action' => 'add',
                    ],
                    [
                        'label' => 'Edit',
                        'route' => 'loanApprove',
                        'action' => 'edit',
                    ],
                ]
            ]
        ],
        'travelApprove' => [
            [
                'label' => 'Travel Request',
                'route' => 'travelApprove',
            ],
            [
                'label' => 'Travel Request',
                'route' => 'travelApprove',
                'pages' => [
                    [
                        'label' => 'List',
                        'route' => 'travelApprove',
                        'action' => 'index',
                    ],
                    [
                        'label' => 'List',
                        'route' => 'travelApprove',
                        'action' => 'status',
                    ],
                    [
                        'label' => 'View',
                        'route' => 'travelApprove',
                        'action' => 'view',
                    ],
                    [
                        'label' => 'View',
                        'route' => 'travelApprove',
                        'action' => 'expenseDetail',
                    ],
                ]
            ]
        ],
        'dayoffWorkApprove' => [
            [
                'label' => 'Work on Day-off Request',
                'route' => 'dayoffWorkApprove',
            ],
            [
                'label' => 'Work on Day-off Request',
                'route' => 'dayoffWorkApprove',
                'pages' => [
                    [
                        'label' => 'List',
                        'route' => 'dayoffWorkApprove',
                        'action' => 'index',
                    ],
                    [
                        'label' => 'List',
                        'route' => 'dayoffWorkApprove',
                        'action' => 'status',
                    ],
                    [
                        'label' => 'View',
                        'route' => 'dayoffWorkApprove',
                        'action' => 'view',
                    ],
                ]
            ]
        ],
        'holidayWorkApprove' => [
            [
                'label' => 'Work on Holiday Request',
                'route' => 'holidayWorkApprove',
            ],
            [
                'label' => 'Work on Holiday Request',
                'route' => 'holidayWorkApprove',
                'pages' => [
                    [
                        'label' => 'List',
                        'route' => 'holidayWorkApprove',
                        'action' => 'index',
                    ],
                    [
                        'label' => 'List',
                        'route' => 'holidayWorkApprove',
                        'action' => 'status',
                    ],
                    [
                        'label' => 'View',
                        'route' => 'holidayWorkApprove',
                        'action' => 'view',
                    ],
                ]
            ]
        ],
        'trainingApprove' => [
            [
                'label' => 'Training Request',
                'route' => 'trainingApprove',
            ],
            [
                'label' => 'Training Request',
                'route' => 'trainingApprove',
                'pages' => [
                    [
                        'label' => 'List',
                        'route' => 'trainingApprove',
                        'action' => 'index',
                    ],
                    [
                        'label' => 'List',
                        'route' => 'trainingApprove',
                        'action' => 'status',
                    ],
                    [
                        'label' => 'View',
                        'route' => 'trainingApprove',
                        'action' => 'view',
                    ],
                ]
            ]
        ],
        'salaryReview' => [
            [
                'label' => 'Salary Review',
                'route' => 'salaryReview',
            ],
            [
                'label' => 'Salary Review',
                'route' => 'salaryReview',
                'pages' => [
                    [
                        'label' => 'List',
                        'route' => 'salaryReview',
                        'action' => 'index',
                    ],
                    [
                        'label' => 'Add',
                        'route' => 'salaryReview',
                        'action' => 'add',
                    ],
                    [
                        'label' => 'Edit',
                        'route' => 'salaryReview',
                        'action' => 'edit',
                    ],
                ]
            ]
        ],
        'overtimeApprove' => [
            [
                'label' => 'Overtime Request',
                'route' => 'overtimeApprove',
            ],
            [
                'label' => 'Overtime Request',
                'route' => 'overtimeApprove',
                'pages' => [
                    [
                        'label' => 'List',
                        'route' => 'overtimeApprove',
                        'action' => 'index',
                    ],
                    [
                        'label' => 'List',
                        'route' => 'overtimeApprove',
                        'action' => 'status',
                    ],
                    [
                        'label' => 'View',
                        'route' => 'overtimeApprove',
                        'action' => 'view',
                    ],
                ]
            ]
        ],
        'appraisal-evaluation' => [
            [
                'label' => 'Appraisal Evaluation',
                'route' => 'appraisal-evaluation',
            ],
            [
                'label' => 'Appraisal Evaluation',
                'route' => 'appraisal-evaluation',
                'pages' => [
                    [
                        'label' => 'List',
                        'route' => 'appraisal-evaluation',
                        'action' => 'index',
                    ],
                    [
                        'label' => 'View',
                        'route' => 'appraisal-evaluation',
                        'action' => 'view',
                    ],
                ]
            ]
        ],
        'appraisal-review' => [
            [
                'label' => 'Appraisal Review',
                'route' => 'appraisal-review',
            ],
            [
                'label' => 'Appraisal Review',
                'route' => 'appraisal-review',
                'pages' => [
                    [
                        'label' => 'List',
                        'route' => 'appraisal-review',
                        'action' => 'index',
                    ],
                    [
                        'label' => 'View',
                        'route' => 'appraisal-review',
                        'action' => 'view',
                    ],
                ]
            ]
        ],
        'appraisal-final-review' => [
            [
                'label' => 'Appraisal Final Review',
                'route' => 'appraisal-final-review',
            ],
            [
                'label' => 'Appraisal Final Review',
                'route' => 'appraisal-final-review',
                'pages' => [
                    [
                        'label' => 'List',
                        'route' => 'appraisal-final-review',
                        'action' => 'index',
                    ],
                    [
                        'label' => 'View',
                        'route' => 'appraisal-final-review',
                        'action' => 'view',
                    ],
                ]
            ]
        ],
        'subordinate' => [
            [
                'label' => 'Subordinate',
                'route' => 'subordinate',
            ],
            [
                'label' => 'Subordinate',
                'route' => 'subordinate',
                'pages' => [
                    [
                        'label' => 'List',
                        'route' => 'subordinate',
                        'action' => 'index',
                    ],
                    [
                        'label' => 'Edit',
                        'route' => 'subordinate',
                        'action' => 'edit',
                    ],
                ],
            ],
        ],
    ],
    'controllers' => [
        'factories' => [
            LeaveApproveController::class => ControllerFactory::class,
            AttendanceApproveController::class => ControllerFactory::class,
            LoanApproveController::class => ControllerFactory::class,
            Controller\SalaryReviewController::class => ControllerFactory::class,
            TravelApproveController::class => ControllerFactory::class,
            DayoffWorkApproveController::class => ControllerFactory::class,
            HolidayWorkApproveController::class => ControllerFactory::class,
            TrainingApproveController::class => ControllerFactory::class,
            OvertimeApproveController::class => ControllerFactory::class,
            AppraisalEvaluation::class => ControllerFactory::class,
            AppraisalReview::class => ControllerFactory::class,
            AppraisalFinalReview::class => ControllerFactory::class,
            ManagerReportController::class => ControllerFactory::class,
            Subordinate::class => ControllerFactory::class,
            MapsController::class => ControllerFactory::class
        ],
    ],
    'view_manager' => [
        'template_path_stack' => [
            __DIR__ . '/../view',
        ],
    ],
];


