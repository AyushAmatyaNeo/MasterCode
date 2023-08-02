<?php

namespace AttendanceManagement;

use Application\Controller\ControllerFactory;
use Zend\Router\Http\Segment;

return [
    'router' => [
        'routes' => [
		'excelUploadAttd' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/attendance/excelUploadAttd[/:action[/:id]]',
                    'constants' => [
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'id' => '[0-9]+',
                    ],
                    'defaults' => [
                        'controller' => Controller\ExcelUploadControllerAttd::class,
                        'action' => 'index',
                    ]
                ],
            ],
			'shiftassign' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/attendance/shiftassign[/:action[/:id]]',
                    'constants' => [
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'id' => '[0-9]+',
                    ],
                    'defaults' => [
                        'controller' => Controller\ShiftAssign::class,
                        'action' => 'index',
                    ]
                ],
            ],
            'groupshiftassign' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/attendance/groupshiftassign[/:action[/:id]]',
                    'constants' => [
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'id' => '[0-9]+',
                    ],
                    'defaults' => [
                        'controller' => Controller\GroupShiftAssign::class,
                        'action' => 'index',
                    ]
                ],
            ],
            'whereabouts' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/attendance/whereabouts[/:action[/:id]]',
                    'constants' => [
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'id' => '[0-9]+',
                    ],
                    'defaults' => [
                        'controller' => Controller\Whereabouts::class,
                        'action' => 'index',
                    ]
                ],
            ],
            'attendancebyhr' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/attendance/attendancebyhr[/:action[/:id]]',
                    'constants' => [
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'id' => '[0-9]+',
                    ],
                    'defaults' => [
                        'controller' => Controller\AttendanceByHr::class,
                        'action' => 'index',
                    ]
                ],
            ],
            'shiftsetup' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/attendance/shiftsetup[/:action[/:id]]',
                    'constants' => [
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'id' => '[0-9]+',
                    ],
                    'defaults' => [
                        'controller' => Controller\ShiftSetup::class,
                        'action' => 'index',
                    ]
                ],
            ],
            'attendancestatus' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/attendance/attendancestatus[/:action[/:id]]',
                    'constants' => [
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'id' => '[0-9]+',
                    ],
                    'defaults' => [
                        'controller' => Controller\AttendanceStatus::class,
                        'action' => 'index',
                    ]
                ],
            ],
            'calculateOvertime' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/attendance/calculateOvertime[/:action[/:id]]',
                    'constants' => [
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'id' => '[0-9]+',
                    ],
                    'defaults' => [
                        'controller' => Controller\CalculateOvertime::class,
                        'action' => 'index',
                    ]
                ],
            ],
            'shiftAdjustment' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/attendance/shiftAdjustment[/:action[/:id]]',
                    'constants' => [
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'id' => '[0-9]+',
                    ],
                    'defaults' => [
                        'controller' => Controller\ShiftAdjustment::class,
                        'action' => 'index',
                    ]
                ],
            ],
            'penalty' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/attendance/penalty[/:action[/:id[/:fiscalYearId[/:fiscalYearMonthNo]]]]',
                    'constants' => [
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'id' => '[0-9]+',
                    ],
                    'defaults' => [
                        'controller' => Controller\Penalty::class,
                        'action' => 'index',
                    ]
                ],
            ],
            'roaster' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/attendance/roster[/:action[/:id]]',
                    'constants' => [
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'id' => '[0-9]+',
                    ],
                    'defaults' => [
                        'controller' => Controller\Roaster::class,
                        'action' => 'index',
                    ]
                ],
            ],
        ],
    ],
    'navigation' => [
        'shiftsetup' => [
            [
                'label' => 'Shift',
                'route' => 'shiftsetup',
            ],
            [
                'label' => 'Shift',
                'route' => 'shiftsetup',
                'pages' => [
                    [
                        'label' => 'List',
                        'route' => 'shiftsetup',
                        'action' => 'index',
                    ],
                    [
                        'label' => 'Add',
                        'route' => 'shiftsetup',
                        'action' => 'add',
                    ],
                    [
                        'label' => 'Edit',
                        'route' => 'shiftsetup',
                        'action' => 'edit',
                    ],
                ],
            ],
        ],
        'attendancebyhr' => [
            [
                'label' => 'Attendance',
                'route' => 'attendancebyhr',
            ],
            [
                'label' => 'Attendance',
                'route' => 'attendancebyhr',
                'pages' => [
                    [
                        'label' => 'List',
                        'route' => 'attendancebyhr',
                        'action' => 'index',
                    ],
                    [
                        'label' => 'Entry',
                        'route' => 'attendancebyhr',
                        'action' => 'add',
                    ],
                    [
                        'label' => 'Edit',
                        'route' => 'attendancebyhr',
                        'action' => 'edit',
                    ],
                    [
                        'label' => 'Manual',
                        'route' => 'attendancebyhr',
                        'action' => 'manual',
                    ],
                ],
            ],
        ],
        'shiftassign' => [
            [
                'label' => 'Shift Assign',
                'route' => 'shiftassign',
            ],
            [
                'label' => 'Shift Assign',
                'route' => 'shiftassign',
                'pages' => [
                    [
                        'label' => 'List',
                        'route' => 'shiftassign',
                        'action' => 'index',
                    ],
                    [
                        'label' => 'Add',
                        'route' => 'shiftassign',
                        'action' => 'add',
                    ],
                    [
                        'label' => 'Edit',
                        'route' => 'shiftassign',
                        'action' => 'edit',
                    ],
                ],
            ],
        ],
        'attendancestatus' => [
            [
                'label' => 'Attendance Request Status',
                'route' => 'attendancestatus',
            ],
            [
                'label' => 'Attendance Request Status',
                'route' => 'attendancestatus',
                'pages' => [
                    [
                        'label' => 'List',
                        'route' => 'attendancestatus',
                        'action' => 'index',
                    ],
                    [
                        'label' => 'Add',
                        'route' => 'attendancestatus',
                        'action' => 'add',
                    ],
                    [
                        'label' => 'Detail',
                        'route' => 'attendancestatus',
                        'action' => 'view',
                    ],
                ],
            ],
        ],
        'calculateOvertime' => [
            [
                'label' => 'Report With Overtime',
                'route' => 'calculateOvertime',
            ],
            [
                'label' => 'Report With Overtime',
                'route' => 'calculateOvertime',
                'pages' => [
                    [
                        'label' => 'List',
                        'route' => 'calculateOvertime',
                        'action' => 'index',
                    ],
                    [
                        'label' => 'Add',
                        'route' => 'calculateOvertime',
                        'action' => 'add',
                    ],
                    [
                        'label' => 'Detail',
                        'route' => 'calculateOvertime',
                        'action' => 'view',
                    ],
                ],
            ],
        ],
        'shiftAdjustment' => [
            [
                'label' => 'Shift Adjustment',
                'route' => 'shiftAdjustment',
            ],
            [
                'label' => 'Shift Adjustment',
                'route' => 'shiftAdjustment',
                'pages' => [
                    [
                        'label' => 'List',
                        'route' => 'shiftAdjustment',
                        'action' => 'index',
                    ],
                    [
                        'label' => 'Add',
                        'route' => 'shiftAdjustment',
                        'action' => 'add',
                    ],
                    [
                        'label' => 'Edit',
                        'route' => 'shiftAdjustment',
                        'action' => 'edit',
                    ],
                ],
            ],
        ],
        'penalty' => [
            [
                'label' => 'Penalty',
                'route' => 'penalty',
            ],
            [
                'label' => 'Penalty',
                'route' => 'penalty',
                'pages' => [
                    [
                        'label' => 'Report',
                        'route' => 'penalty',
                        'action' => 'index',
                    ],
                    [
                        'label' => 'Self',
                        'route' => 'penalty',
                        'action' => 'self',
                    ],
                    [
                        'label' => 'Action',
                        'route' => 'penalty',
                        'action' => 'penalizedMonths',
                    ],
                ],
            ],
        ],
        'roaster' => [
            [
                'label' => 'Roster',
                'route' => 'roaster',
            ],
            [
                'label' => 'Roster',
                'route' => 'roaster',
                'pages' => [
                    [
                        'label' => 'List',
                        'route' => 'roaster',
                        'action' => 'index',
                    ],
                ],
            ],
        ],
    ],
    'controllers' => [
        'factories' => [
            Controller\ShiftAssign::class => ControllerFactory::class,
            Controller\AttendanceByHr::class => ControllerFactory::class,
            Controller\ShiftSetup::class => ControllerFactory::class,
            Controller\AttendanceStatus::class => ControllerFactory::class,
            Controller\CalculateOvertime::class => ControllerFactory::class,
            Controller\ShiftAdjustment::class => ControllerFactory::class,
            Controller\Penalty::class => ControllerFactory::class,
            Controller\Roaster::class => ControllerFactory::class,
            Controller\GroupShiftAssign::class => ControllerFactory::class,
            Controller\Whereabouts::class => ControllerFactory::class,
			 Controller\ExcelUploadControllerAttd::class => ControllerFactory::class
        ],
    ],
    'view_manager' => [
        'template_path_stack' => [
            __DIR__ . '/../view',
        ]
    ]
];

