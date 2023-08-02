<?php

namespace Customer;

use Application\Controller\ControllerFactory;
use Customer\Controller\ContractAbsentDetails;
use Customer\Controller\ContractAttendance;
use Customer\Controller\ContractEmpAddedDetails;
use Customer\Controller\ContractEmployees;
use Customer\Controller\CustomerContract;
use Customer\Controller\CustomerContractDetails;
use Customer\Controller\CustomerLocation;
use Customer\Controller\CustomerSetup;
use Customer\Controller\DutyType;
use Customer\Controller\ServiceEmployeeSetup;
use Zend\Router\Http\Segment;

return [
    'router' => [
        'routes' => [
            'customer-setup' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/customer/setup[/:action[/:id]]',
                    'constraints' => [
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'id' => '[0-9]+',
                    ],
                    'defaults' => [
                        'controller' => CustomerSetup::class,
                        'action' => 'index'
                    ],
                ],
            ],
            'customer-location' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/customer/location[/:action[/:id]]',
                    'constraints' => [
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'id' => '[0-9]+',
                    ],
                    'defaults' => [
                        'controller' => CustomerLocation::class,
                        'action' => 'index'
                    ],
                ],
            ],
            'customer-contract' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/customer/contract[/:action[/:id]]',
                    'constraints' => [
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'id' => '[0-9]+',
                    ],
                    'defaults' => [
                        'controller' => CustomerContract::class,
                        'action' => 'index'
                    ],
                ],
            ],
            'customer-contract-details' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/customer/contract/details[/:action[/:id]]',
                    'constraints' => [
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'id' => '[0-9]+',
                    ],
                    'defaults' => [
                        'controller' => CustomerContractDetails::class,
                        'action' => 'index'
                    ],
                ],
            ],
            'service-employee' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/customer/service/employee[/:action[/:id]]',
                    'constraints' => [
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'id' => '[0-9]+',
                    ],
                    'defaults' => [
                        'controller' => ServiceEmployeeSetup::class,
                        'action' => 'index'
                    ],
                ],
            ],
            'contract-attendance' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/customer/contract/attendance[/:action[/:id][/:monthId]]',
                    'constraints' => [
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'id' => '[0-9]+',
                    ],
                    'defaults' => [
                        'controller' => ContractAttendance::class,
                        'action' => 'index'
                    ],
                ],
            ],
            'contract-employees' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/customer/contract/employees[/:action[/:id]]',
                    'constraints' => [
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'id' => '[0-9]+',
                    ],
                    'defaults' => [
                        'controller' => ContractEmployees::class,
                        'action' => 'index'
                    ],
                ],
            ],
            'duty-type' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/duty/type[/:action[/:id]]',
                    'constraints' => [
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'id' => '[0-9]+',
                    ],
                    'defaults' => [
                        'controller' => DutyType::class,
                        'action' => 'index'
                    ],
                ],
            ],
        ],
    ],
    'navigation' => [
        'customer-setup' => [
            [
                'label' => "Customer",
                'route' => "customer-setup"
            ],
            [
                'label' => "Customer",
                'route' => "customer-setup",
                'pages' => [
                    [
                        'label' => 'List',
                        'route' => 'customer-setup',
                        'action' => 'index',
                    ],
                    [
                        'label' => 'Add',
                        'route' => 'customer-setup',
                        'action' => 'add',
                    ],
                    [
                        'label' => 'Edit',
                        'route' => 'customer-setup',
                        'action' => 'edit',
                    ],
                ],
            ],
        ],
        'customer-contract' => [
            [
                'label' => "Customer Contract",
                'route' => "customer-contract"
            ],
            [
                'label' => "Customer Contract",
                'route' => "customer-contract",
                'pages' => [
                    [
                        'label' => 'List',
                        'route' => 'customer-contract',
                        'action' => 'index',
                    ],
                    [
                        'label' => 'Add',
                        'route' => 'customer-contract',
                        'action' => 'add',
                    ],
                    [
                        'label' => 'Edit',
                        'route' => 'customer-contract',
                        'action' => 'edit',
                    ],
                ],
            ],
        ],
        'service-employee' => [
            [
                'label' => "Waged Employee",
                'route' => "service-employee"
            ],
            [
                'label' => "Waged Employee",
                'route' => "service-employee",
                'pages' => [
                    [
                        'label' => 'List',
                        'route' => 'service-employee',
                        'action' => 'index',
                    ],
                    [
                        'label' => 'Add',
                        'route' => 'service-employee',
                        'action' => 'add',
                    ],
                    [
                        'label' => 'Edit',
                        'route' => 'service-employee',
                        'action' => 'edit',
                    ],
                ],
            ],
        ],
        'duty-type' => [
            [
                'label' => "Duty Type",
                'route' => "duty-type"
            ],
            [
                'label' => "Duty Type",
                'route' => "duty-type",
                'pages' => [
                    [
                        'label' => 'List',
                        'route' => 'duty-type',
                        'action' => 'index',
                    ],
                    [
                        'label' => 'Add',
                        'route' => 'duty-type',
                        'action' => 'add',
                    ],
                    [
                        'label' => 'Edit',
                        'route' => 'duty-type',
                        'action' => 'edit',
                    ],
                ],
            ],
        ],
    ],
    'controllers' => [
        'factories' => [
            CustomerSetup::class => ControllerFactory::class,
            CustomerLocation::class => ControllerFactory::class,
            CustomerContract::class => ControllerFactory::class,
            CustomerContractDetails::class => ControllerFactory::class,
            ServiceEmployeeSetup::class => ControllerFactory::class,
            ContractAttendance::class => ControllerFactory::class,
            ContractEmployees::class => ControllerFactory::class,
            ContractAbsentDetails::class => ControllerFactory::class,
            ContractEmpAddedDetails::class => ControllerFactory::class,
            DutyType::class => ControllerFactory::class,
        ],
    ],
    'view_manager' => [
        'template_path_stack' => [
            __DIR__ . '/../view',
        ],
    ],
];
