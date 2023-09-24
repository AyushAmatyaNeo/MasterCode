<?php

namespace Travel;

use Application\Controller\ControllerFactory;
use Travel\Controller\TravelStatus;
use Travel\Controller\NewTravelStatus;
use Travel\Controller\TravelApply;
use Travel\Controller\NewTravelApply;
use Zend\Router\Http\Segment;

return [
    'router' => [
        'routes' => [
            'newTravelStatus' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/newtravel/status[/:action[/:id]]',
                    'constraints' => [
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'id' => '[0-9]+',
                    ],
                    'defaults' => [
                        'controller' => NewTravelStatus::class,
                        'action' => 'index'
                    ],
                ],
            ],
            'travelStatus' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/travel/status[/:action[/:id]]',
                    'constraints' => [
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'id' => '[0-9]+',
                    ],
                    'defaults' => [
                        'controller' => TravelStatus::class,
                        'action' => 'index'
                    ],
                ],
            ],
            'travelApply' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/travel/apply[/:action[/:id]]',
                    'constraints' => [
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'id' => '[0-9]+',
                    ],
                    'defaults' => [
                        'controller' => TravelApply::class,
                        'action' => 'index'
                    ],
                ],
            ],
            'newtravelApply' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/newtravel/apply[/:action[/:id]]',
                    'constraints' => [
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'id' => '[0-9]+',
                    ],
                    'defaults' => [
                        'controller' => NewTravelApply::class,
                        'action' => 'index'
                    ],
                ],
            ],
            'travelItnary' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/travel/itnary[/:action[/:id]]',
                    'constraints' => [
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'id' => '[0-9]+',
                    ],
                    'defaults' => [
                        'controller' => Controller\TravelItnaryRequest::class,
                        'action' => 'index'
                    ],
                ],
            ],
        ],
    ],
    'navigation' => [
        'travelStatus' => [
            [
                'label' => "Travel Request",
                'route' => "travelStatus"
            ],
            [
                'label' => "Travel Request",
                'route' => "travelStatus",
                'pages' => [
                    [
                        'label' => 'List',
                        'route' => 'travelStatus',
                        'action' => 'index',
                    ],
                    [
                        'label' => 'Add',
                        'route' => 'travelStatus',
                        'action' => 'add',
                    ],
                    [
                        'label' => 'Detail',
                        'route' => 'travelStatus',
                        'action' => 'view',
                    ],
                    [
                        'label' => 'Detail',
                        'route' => 'travelStatus',
                        'action' => 'expenseDetail',
                    ],
                ],
            ],
        ],
        'newTravelStatus' => [
            [
                'label' => "New Travel Request",
                'route' => "newTravelStatus"
            ],
            [
                'label' => "New Travel Request",
                'route' => "newTravelStatus",
                'pages' => [
                    [
                        'label' => 'List',
                        'route' => 'newTravelStatus',
                        'action' => 'index',
                    ],
                    [
                        'label' => 'Add',
                        'route' => 'newTravelStatus',
                        'action' => 'add',
                    ],
                    [
                        'label' => 'Detail',
                        'route' => 'newTravelStatus',
                        'action' => 'view',
                    ],
                    [
                        'label' => 'Detail',
                        'route' => 'newTravelStatus',
                        'action' => 'expenseDetail',
                    ],
                ],
            ],
        ],
        'travelApply' => [
            [
                'label' => "Travel Apply",
                'route' => "travelApply"
            ],
            [
                'label' => "Travel Apply",
                'route' => "travelApply",
                'pages' => [
                    [
                        'label' => 'List',
                        'route' => 'travelApply',
                        'action' => 'index',
                    ],
                    [
                        'label' => 'Add',
                        'route' => 'travelApply',
                        'action' => 'add',
                    ],
                    [
                        'label' => 'Edit',
                        'route' => 'travelApply',
                        'action' => 'edit',
                    ],
                ],
            ],
        ],
        'newtravelApply' => [
            [
                'label' => "Travel Apply",
                'route' => "newtravelApply"
            ],
            [
                'label' => "Travel Apply",
                'route' => "newtravelApply",
                'pages' => [
                    [
                        'label' => 'List',
                        'route' => 'newtravelApply',
                        'action' => 'index',
                    ],
                    [
                        'label' => 'Add',
                        'route' => 'newtravelApply',
                        'action' => 'add',
                    ],
                    [
                        'label' => 'Edit',
                        'route' => 'newtravelApply',
                        'action' => 'edit',
                    ],
                ],
            ],
        ],
    ],

    'controllers' => [
        'factories' => [
            Controller\TravelStatus::class => ControllerFactory::class,
            Controller\NewTravelStatus::class => ControllerFactory::class,
            Controller\TravelApply::class => ControllerFactory::class,
            Controller\NewTravelApply::class => ControllerFactory::class,
            Controller\TravelItnaryRequest::class => ControllerFactory::class
        ],
    ],
    'view_manager' => [
        'template_path_stack' => [
            __DIR__ . '/../view',
        ],
    ],
];
