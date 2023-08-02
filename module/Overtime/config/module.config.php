<?php
namespace Overtime;

use Application\Controller\ControllerFactory;
use Overtime\Controller\OvertimeApply;
use Overtime\Controller\OvertimeAutomation;
use Overtime\Controller\OvertimeReport;
use Overtime\Controller\OvertimeStatus;
use Overtime\Controller\OvertimeBulkSetup;
use Zend\Router\Http\Segment;

return [
    'router' => [
        'routes' => [
            'overtimeStatus' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/overtime/status[/:action[/:id]]',
                    'constraints' => [
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'id' => '[0-9]+',
                    ],
                    'defaults' => [
                        'controller' => OvertimeStatus::class,
                        'action' => 'index'
                    ],
                ],
            ],
            'overtimeApply' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/overtime/apply[/:action[/:id]]',
                    'constraints' => [
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'id' => '[0-9]+',
                    ],
                    'defaults' => [
                        'controller' => OvertimeApply::class,
                        'action' => 'index'
                    ],
                ],
            ],
            'overtimeAutomation' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/overtime/automation[/:action[/:id]]',
                    'constraints' => [
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'id' => '[0-9]+',
                    ],
                    'defaults' => [
                        'controller' => OvertimeAutomation::class,
                        'action' => 'index'
                    ],
                ],
            ],
            'overtime-report' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/overtime/report[/:action[/:id]]',
                    'constraints' => [
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'id' => '[0-9]+',
                    ],
                    'defaults' => [
                        'controller' => OvertimeReport::class,
                        'action' => 'index'
                    ],
                ],
            ],
            'overtime-bulk-setup' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/overtime/bulkSetup[/:action[/:id]]',
                    'constraints' => [
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'id' => '[0-9]+',
                    ],
                    'defaults' => [
                        'controller' => OvertimeBulkSetup::class,
                        'action' => 'index'
                    ],
                ],
            ],
        ],
    ],
    'navigation' => [
        'overtimeStatus' => [
            [
                'label' => "Overtime Request",
                'route' => "overtimeStatus"
            ],
            [
                'label' => "Overtime Request",
                'route' => "overtimeStatus",
                'pages' => [
                    [
                        'label' => 'List',
                        'route' => 'overtimeStatus',
                        'action' => 'index',
                    ],
                    [
                        'label' => 'Add',
                        'route' => 'overtimeStatus',
                        'action' => 'add',
                    ],
                    [
                        'label' => 'Detail',
                        'route' => 'overtimeStatus',
                        'action' => 'view',
                    ],
                ],
            ],
        ],
        'overtimeApply' => [
            [
                'label' => "Overtime Apply",
                'route' => "overtimeApply"
            ],
            [
                'label' => "Overtime Apply",
                'route' => "overtimeApply",
                'pages' => [
                    [
                        'label' => 'List',
                        'route' => 'overtimeApply',
                        'action' => 'index',
                    ],
                    [
                        'label' => 'Add',
                        'route' => 'overtimeApply',
                        'action' => 'add',
                    ],
                    [
                        'label' => 'Edit',
                        'route' => 'overtimeApply',
                        'action' => 'edit',
                    ],
                ],
            ],
        ],
        'overtimeAutomation' => [
            [
                'label' => "Overtime",
                'route' => "overtimeAutomation"
            ],
            [
                'label' => "Overtime Automation",
                'route' => "overtimeAutomation",
                'pages' => [
                    [
                        'label' => 'List',
                        'route' => 'overtimeAutomation',
                        'action' => 'index',
                    ],
                    [
                        'label' => 'Wizard',
                        'route' => 'overtimeAutomation',
                        'action' => 'wizard',
                    ],
                ],
            ],
        ],
        'overtime-report' => [
            [
                'label' => "Overtime",
                'route' => "overtime-report"
            ],
            [
                'label' => "Overtime",
                'route' => "overtime-report",
                'pages' => [
                    [
                        'label' => 'Report',
                        'route' => 'overtime-report',
                        'action' => 'index',
                    ],
                ],
            ],
        ],
    ],
    'controllers' => [
        'factories' => [
            OvertimeStatus::class => ControllerFactory::class,
            OvertimeApply::class => ControllerFactory::class,
            OvertimeAutomation::class => ControllerFactory::class,
            OvertimeReport::class => ControllerFactory::class,
            OvertimeBulkSetup::class => ControllerFactory::class,
        ],
    ],
    'view_manager' => [
        'template_path_stack' => [
            __DIR__ . '/../view',
        ],
    ],
];
