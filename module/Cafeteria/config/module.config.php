<?php
namespace Cafeteria;

use Application\Controller\ControllerFactory;
use Cafeteria\Controller\CafeteriaSetupController;
use Cafeteria\Controller\CafeteriaActivityController;
use Cafeteria\Controller\CafeteriaReportsController;
use Zend\Router\Http\Segment;

return [
    'router' => [
        'routes' => [
            'cafeteriasetup' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/cafeteria/setup[/:action[/:id]]',
                    'constraints' => [
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'id' => '[0-9]+',
                    ],
                    'defaults' => [
                        'controller' => CafeteriaSetupController::class,
                        'action' => 'index'
                    ],
                ],
            ],
            'cafeteriareports' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/cafeteria/reports[/:action[/:id]]',
                    'constraints' => [
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'id' => '[0-9]+',
                    ],
                    'defaults' => [
                        'controller' => CafeteriaReportsController::class,
                        'action' => 'index'
                    ],
                ],
            ],
            'cafeteria-activity' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/cafeteria[/:action[/:id]]',
                    'constraints' => [
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'id' => '[0-9]+',
                    ],
                    'defaults' => [
                        'controller' => CafeteriaActivityController::class,
                        'action' => 'index'
                    ],
                ],
            ],
        ],
    ],
    'controllers' => [
        'factories' => [
            Controller\CafeteriaSetupController::class => ControllerFactory::class,
            Controller\CafeteriaActivityController::class => ControllerFactory::class,
            Controller\CafeteriaReportsController::class => ControllerFactory::class
        ],
    ],
    'view_manager' => [
        'template_path_stack' => [
            __DIR__ . '/../view',
        ],
    ],
];
