<?php

namespace Report;

use Application\Controller\ControllerFactory;
use Report\Controller\AllReportController;
use Zend\Router\Http\Segment;

return [
    'router' => [
        'routes' => [
            'allreport' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/report[/:action[/:id1[/:id2]]]',
                    'constants' => [
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'id' => '[0-9]+',
                    ],
                    'defaults' => [
                        'controller' => AllReportController::class,
                        'action' => 'index',
                    ]
                ],
            ],
        ],
    ],
    'navigation' => [
        'allreport' => [
            [
                'label' => 'Reports',
                'route' => 'allreport',
            ], [
                'label' => 'Report',
                'route' => 'allreport',
                'pages' => [
                    [
                        'label' => 'Departments|Months',
                        'route' => 'allreport',
                        'action' => 'departmentAll',
                    ],
                    [
                        'label' => 'Department|Months',
                        'route' => 'allreport',
                        'action' => 'departmentWise',
                    ],
                    [
                        'label' => 'Department|Month',
                        'route' => 'allreport',
                        'action' => 'departmentWiseDaily',
                    ],
                    [
                        'label' => 'Employee|Months',
                        'route' => 'allreport',
                        'action' => 'employeeWise',
                    ],
                    [
                        'label' => 'With Overtime',
                        'route' => 'allreport',
                        'action' => 'withOvertime',
                    ],
                    [
                        'label' => 'Leave Report',
                        'route' => 'allreport',
                        'action' => 'leaveReport',
                    ],
                    [
                        'label' => 'Hire & Fire',
                        'route' => 'allreport',
                        'action' => 'HireAndFireReport',
                    ],
                    [
                        'label' => 'Branch Wise',
                        'route' => 'allreport',
                        'action' => 'branchWise',
                    ],
                    [
                        'label' => 'Branch Wise Daily',
                        'route' => 'allreport',
                        'action' => 'branchWiseDaily',
                    ],
                    [
                        'label' => 'Allowance Report',
                        'route' => 'monthlyAllowance',
                        'action' => 'branchWiseDaily',
                    ],
                    [
                        'label' => 'Department|Month',
                        'route' => 'allreport',
                        'action' => 'departmentWiseAttdReport',
                    ],
                    [
                        'label' => 'Training Report',
                        'route' => 'allreport',
                        'action' => 'trainingReport',
                    ],
                    [
                        'label' => 'Male and Female Ratio',
                        'route' => 'allreport',
                        'action' => 'maleFemaleRatio',
                    ],
                    [
                        'label' => 'Renumeration Report',
                        'route' => 'allreport',
                        'action' => 'renumerationReport',
                    ],
                    [
                        'label' => 'Turn Over',
                        'route' => 'allreport',
                        'action' => 'turnOverReport',
                    ],
                    [
                        'label' => 'Generation',
                        'route' => 'allreport',
                        'action' => 'ageGeneration',
                    ],
                ],
            ],
        ],
    ],
    'controllers' => [
        'factories' => [
            AllReportController::class => ControllerFactory::class,
        ],
    ],
    'view_manager' => [
        'template_path_stack' => [
            __DIR__ . '/../view',
        ],
    ],
];
