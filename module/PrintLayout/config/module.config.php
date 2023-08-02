<?php

namespace PrintLayout;

use Zend\Router\Http\Segment;
use Application\Controller\ControllerFactory;

return [
    'router' => [
        'routes' => [
            'printlayout' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/printlayout[/:action[/:id]]',
                    'defaults' => [
                        'controller' => Controller\PrintLayout::class,
                        'action' => 'index'
                    ]
                ]
            ]
        ]
    ],
    'controllers' => [
        'factories' => [
            Controller\PrintLayout::class => ControllerFactory::class,
        ],
    ],
    'view_manager' => [
        'template_path_stack' => [
            __DIR__ . '/../view',
        ],
    ],
];


