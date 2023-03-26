<?php

namespace System;

use Application\Controller\ControllerFactory;
use System\Controller\AttendanceDeviceController;
use System\Controller\DashboardController;
use System\Controller\MenuSetupController;
use System\Controller\PreferenceSetup;
use System\Controller\RoleSetupController;
use System\Controller\SettingController;
use System\Controller\UserSetupController;
use System\Controller\MapsController;
use Zend\Router\Http\Segment;

return [
    'router' => [
        'routes' => [
            'rolesetup' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/system/rolesetup[/:action[/:id][/:role]]',
                    'constraints' => [
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'id' => '[0-9]+',
                    ],
                    'defaults' => [
                        'controller' => RoleSetupController::class,
                        'action' => 'index'
                    ],
                ],
            ],
            'showlocation' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/system/location[/:action]',
                    'constraints' => [
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'id' => '[0-9]+',
                    ],
                    'defaults' => [
                        'controller' => MapsController::class,
                        'action' => 'index'
                    ],
                ],
            ],
            'usersetup' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/system/usersetup[/:action[/:id][/:role]]',
                    'constraints' => [
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'id' => '[0-9]+',
                    ],
                    'defaults' => [
                        'controller' => UserSetupController::class,
                        'action' => 'index'
                    ],
                ],
            ],
            'menusetup' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/system/menusetup[/:action[/:id][/:role]]',
                    'constraints' => [
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'id' => '[0-9]+',
                    ],
                    'defaults' => [
                        'controller' => MenuSetupController::class,
                        'action' => 'index'
                    ],
                ],
            ],
            'dashboardsetup' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/system/dashboard[/:action[/:id]]',
                    'constraint' => [
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'id' => '[0-9]+',
                    ],
                    'defaults' => [
                        'controller' => DashboardController::class,
                        'action' => 'index'
                    ]
                ]
            ],
            'user-setting' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/system/user-setting[/:action[/:id]]',
                    'constraint' => [
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'id' => '[0-9]+',
                    ],
                    'defaults' => [
                        'controller' => SettingController::class,
                        'action' => 'index'
                    ]
                ]
            ],
            'AttendanceDevice' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/system/AttendanceDevice[/:action[/:id]]',
                    'constraint' => [
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'id' => '[0-9]+',
                    ],
                    'defaults' => [
                        'controller' => AttendanceDeviceController::class,
                        'action' => 'index'
                    ]
                ]
            ],
            'preferenceSetup' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/system/preferenceSetup[/:action[/:id]]',
                    'constraint' => [
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'id' => '[0-9]+',
                    ],
                    'defaults' => [
                        'controller' => PreferenceSetup::class,
                        'action' => 'index'
                    ]
                ]
            ],
            'menu-report' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/system/menu-report[/:action[/:id]]',
                    'constraint' => [
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'id' => '[0-9]+',
                    ],
                    'defaults' => [
                        'controller' => Controller\MenuReport::class,
                        'action' => 'index'
                    ]
                ]
            ],
            'system-setting' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/system/setting[/:action[/:id]]',
                    'constraint' => [
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'id' => '[0-9]+',
                    ],
                    'defaults' => [
                        'controller' => Controller\SystemSetting::class,
                        'action' => 'index'
                    ]
                ]
            ],
            'system-utility' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/system/utility[/:action[/:id]]',
                    'constraint' => [
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'id' => '[0-9]+',
                    ],
                    'defaults' => [
                        'controller' => Controller\SystemUtility::class,
                        'action' => 'index'
                    ]
                ]
            ],
        ],
    ],
    'navigation' => [
        'rolesetup' => [
            [
                'label' => "Role Setup",
                'route' => "rolesetup"
            ],
            [
                'label' => "Role Setup",
                'route' => "rolesetup",
                'pages' => [
                    [
                        'label' => 'List',
                        'route' => 'rolesetup',
                        'action' => 'index',
                    ],
                    [
                        'label' => 'Add',
                        'route' => 'rolesetup',
                        'action' => 'add',
                    ],
                    [
                        'label' => 'Edit',
                        'route' => 'rolesetup',
                        'action' => 'edit',
                    ],
                ],
            ],
        ],
        'showlocation' => [
            [
                'label' => "Show Location",
                'route' => "location"
            ],
            [
                'label' => "Show Location",
                'route' => "location",
                'pages' => [
                    [
                        'label' => 'show',
                        'route' => 'location',
                        'action' => 'index',
                    ],
                ],
            ],
        ],
        'usersetup' => [
            [
                'label' => "User Setup",
                'route' => "usersetup"
            ],
            [
                'label' => "User Setup",
                'route' => "usersetup",
                'pages' => [
                    [
                        'label' => 'List',
                        'route' => 'usersetup',
                        'action' => 'index',
                    ],
                    [
                        'label' => 'Add',
                        'route' => 'usersetup',
                        'action' => 'add',
                    ],
                    [
                        'label' => 'Edit',
                        'route' => 'usersetup',
                        'action' => 'edit',
                    ],
                ],
            ],
        ],
        'menusetup' => [
            [
                'label' => "Menu Setup",
                'route' => "menusetup"
            ],
            [
                'label' => "Menu Setup",
                'route' => "menusetup",
                'pages' => [
                    [
                        'label' => 'List',
                        'route' => 'menusetup',
                        'action' => 'index',
                    ],
                    [
                        'label' => 'Add',
                        'route' => 'menusetup',
                        'action' => 'add',
                    ],
                    [
                        'label' => 'Edit',
                        'route' => 'menusetup',
                        'action' => 'edit',
                    ],
                ],
            ],
        ],
        'dashboardsetup' => [
            [
                'label' => "Dashboard Setup",
                'route' => "dashboardsetup"
            ],
            [
                'label' => "Dashboard Setup",
                'route' => "dashboardsetup",
                'pages' => [
                    [
                        'label' => 'Assign Dashboard',
                        'route' => 'dashboardsetup',
                        'action' => 'index',
                    ],
                ],
            ],
        ],
        'AttendanceDevice' => [
            [
                'label' => "Attendance Device",
                'route' => "AttendanceDevice"
            ],
            [
                'label' => "Attendance Device",
                'route' => "AttendanceDevice",
                'pages' => [
                    [
                        'label' => 'List',
                        'route' => 'AttendanceDevice',
                        'action' => 'index',
                    ],
                    [
                        'label' => 'Add',
                        'route' => 'AttendanceDevice',
                        'action' => 'add',
                    ],
                    [
                        'label' => 'Edit',
                        'route' => 'AttendanceDevice',
                        'action' => 'edit',
                    ],
                    [
                        'label' => 'Log',
                        'route' => 'AttendanceDevice',
                        'action' => 'attendanceLog',
                    ],
                ],
            ],
        ],
        'preferenceSetup' => [
            [
                'label' => "Preference Setup",
                'route' => "preferenceSetup"
            ],
            [
                'label' => "Preference Setup",
                'route' => "preferenceSetup",
                'pages' => [
                    [
                        'label' => 'List',
                        'route' => 'preferenceSetup',
                        'action' => 'index',
                    ],
                    [
                        'label' => 'Add',
                        'route' => 'preferenceSetup',
                        'action' => 'add',
                    ],
                    [
                        'label' => 'Edit',
                        'route' => 'preferenceSetup',
                        'action' => 'edit',
                    ],
                ],
            ],
        ],
        'menu-report' => [
            [
                'label' => "Menu Report",
                'route' => "menu-report"
            ],
            [
                'label' => "Menu Report",
                'route' => "menu-report",
                'pages' => [
                    [
                        'label' => 'Role Wise',
                        'route' => 'menu-report',
                        'action' => 'index',
                    ],
                ],
            ],
        ],
        'system-setting' => [
            [
                'label' => "Setting",
                'route' => "system-setting"
            ],
            [
                'label' => "Setting",
                'route' => "system-setting",
                'pages' => [
                    [
                        'label' => 'Setting',
                        'route' => 'system-setting',
                        'action' => 'index',
                    ],
                ],
            ],
        ],
        'system-utility' => [
            [
                'label' => "Utility",
                'route' => "system-utility"
            ],
            [
                'label' => "Utility",
                'route' => "system-utility",
                'pages' => [
                    [
                        'label' => 'Re Attendnace',
                        'route' => 'system-utility',
                        'action' => 'reAttendance',
                    ],
                    [
                        'label' => 'Database Backup',
                        'route' => 'system-utility',
                        'action' => 'databaseBackup',
                    ],
                    [
                        'label' => 'Update Seniority',
                        'route' => 'system-utility',
                        'action' => 'updateSeniority',
                    ],
                    [
                        'label' => 'Recalculate Leave',
                        'route' => 'system-utility',
                        'action' => 'reCalculate',
                    ],
                ],
            ],
        ]
    ],
    'controllers' => [
        'factories' => [
            RoleSetupController::class => ControllerFactory::class,
            UserSetupController::class => ControllerFactory::class,
            MenuSetupController::class => ControllerFactory::class,
            DashboardController::class => ControllerFactory::class,
            SettingController::class => ControllerFactory::class,
            AttendanceDeviceController::class => ControllerFactory::class,
            PreferenceSetup::class => ControllerFactory::class,
            Controller\MenuReport::class => ControllerFactory::class,
            Controller\SystemSetting::class => ControllerFactory::class,
            Controller\SystemUtility::class => ControllerFactory::class,
            Controller\MapsController::class => ControllerFactory::class,
        ],
    ],
    'view_manager' => [
        'template_path_stack' => [
            __DIR__ . '/../view',
        ],
    ],
];
