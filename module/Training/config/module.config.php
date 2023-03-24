<?php

namespace Training;

use Application\Controller\ControllerFactory;
use Training\Controller\TrainingApplyController;
use Training\Controller\TrainingAssignController;
use Training\Controller\EventAssignController;
use Training\Controller\TrainingAttendanceController;
use Training\Controller\TrainingStatusController;
use Training\Controller\EventStatusController;
use Zend\Router\Http\Segment;

return [
    'router' => [
        'routes' => [
            'trainingAssign' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/training/trainingAssign[/:action[/:employeeId][/:trainingId]]',
                    'constraints' => [
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'id' => '[0-9]+',
                    ],
                    'defaults' => [
                        'controller' => TrainingAssignController::class,
                        'action' => 'index'
                    ],
                ],
            ],
            'eventAssign' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/training/eventAssign[/:action[/:employeeId][/:eventId]]',
                    'constraints' => [
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'id' => '[0-9]+',
                    ],
                    'defaults' => [
                        'controller' => EventAssignController::class,
                        'action' => 'index'
                    ],
                ],
            ],
            'trainingStatus' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/training/trainingStatus[/:action[/:id]]',
                    'constraints' => [
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'id' => '[0-9]+',
                    ],
                    'defaults' => [
                        'controller' => TrainingStatusController::class,
                        'action' => 'index'
                    ],
                ],
            ],
            'eventStatus' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/training/eventStatus[/:action[/:id]]',
                    'constraints' => [
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'id' => '[0-9]+',
                    ],
                    'defaults' => [
                        'controller' => EventStatusController::class,
                        'action' => 'index'
                    ],
                ],
            ],
            'trainingAtt' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/training/trainingAtt[/:action[/:id]]',
                    'constraints' => [
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'id' => '[0-9]+',
                    ],
                    'defaults' => [
                        'controller' => TrainingAttendanceController::class,
                        'action' => 'index'
                    ],
                ],
            ],
            'trainingApply' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/training/trainingApply[/:action[/:id]]',
                    'constraints' => [
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'id' => '[0-9]+',
                    ],
                    'defaults' => [
                        'controller' => TrainingApplyController::class,
                        'action' => 'index'
                    ],
                ],
            ],
        ],
    ],
    'navigation' => [
        'trainingAssign' => [
            [
                'label' => "Training Assign",
                'route' => "trainingAssign"
            ],
            [
                'label' => "Training Assign",
                'route' => "trainingAssign",
                'pages' => [
                    [
                        'label' => 'List',
                        'route' => 'trainingAssign',
                        'action' => 'index',
                    ],
                    [
                        'label' => 'Add',
                        'route' => 'trainingAssign',
                        'action' => 'add',
                    ],
                    [
                        'label' => 'Detail',
                        'route' => 'trainingAssign',
                        'action' => 'view',
                    ],
                    [
                        'label' => 'Assign',
                        'route' => 'trainingAssign',
                        'action' => 'assign',
                    ],
                ],
            ],
        ],
        'eventAssign' => [
            [
                'label' => "Event Assign",
                'route' => "eventAssign"
            ],
            [
                'label' => "Event Assign",
                'route' => "eventAssign",
                'pages' => [
                    [
                        'label' => 'List',
                        'route' => 'eventAssign',
                        'action' => 'index',
                    ],
                    [
                        'label' => 'Add',
                        'route' => 'eventAssign',
                        'action' => 'add',
                    ],
                    [
                        'label' => 'Detail',
                        'route' => 'eventAssign',
                        'action' => 'view',
                    ],
                    [
                        'label' => 'Assign',
                        'route' => 'eventAssign',
                        'action' => 'assign',
                    ],
                ],
            ],
        ],
        'trainingStatus' => [
            [
                'label' => 'Training Request',
                'route' => 'trainingStatus',
            ],
            [
                'label' => 'Training Request',
                'route' => 'trainingStatus',
                'pages' => [
                    [
                        'label' => 'List',
                        'route' => 'trainingStatus',
                        'action' => 'index',
                    ],
                    [
                        'label' => 'View',
                        'route' => 'trainingStatus',
                        'action' => 'view',
                    ],
                ]
            ]
        ],
        'eventStatus' => [
            [
                'label' => 'Event Request',
                'route' => 'eventStatus',
            ],
            [
                'label' => 'Event Request',
                'route' => 'eventStatus',
                'pages' => [
                    [
                        'label' => 'List',
                        'route' => 'eventStatus',
                        'action' => 'index',
                    ],
                    [
                        'label' => 'View',
                        'route' => 'eventStatus',
                        'action' => 'view',
                    ],
                ]
            ]
        ],
        'trainingAtt' => [
            [
                'label' => "Training",
                'route' => "trainingAtt"
            ],
            [
                'label' => "Training",
                'route' => "trainingAtt",
                'pages' => [
                    [
                        'label' => 'Attendance',
                        'route' => 'trainingAtt',
                        'action' => 'index',
                    ],
                    [
                        'label' => 'Take Attendance',
                        'route' => 'trainingAtt',
                        'action' => 'attendance',
                    ],
                ],
            ],
        ],
        'trainingApply' => [
            [
                'label' => "Training",
                'route' => "trainingStatus"
            ],
            [
                'label' => "Training",
                'route' => "trainingStatus",
                'pages' => [
                    [
                        'label' => 'Apply',
                        'route' => 'trainingApply',
                        'action' => 'add',
                    ],
                ],
            ],
        ],
    ],
    'controllers' => [
        'factories' => [
            Controller\TrainingAssignController::class => ControllerFactory::class,
            Controller\EventAssignController::class => ControllerFactory::class,
            Controller\TrainingStatusController::class => ControllerFactory::class,
            Controller\EventStatusController::class => ControllerFactory::class,
            Controller\TrainingAttendanceController::class => ControllerFactory::class,
            Controller\TrainingApplyController::class => ControllerFactory::class,
        ],
    ],
    'view_manager' => [
        'template_path_stack' => [
            __DIR__ . '/../view',
        ],
    ],
];
