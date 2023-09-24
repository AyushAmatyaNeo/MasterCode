<?php

namespace Setup;

use Application\Controller\ControllerFactory;
use Setup\Controller\AcademicCourseController;
use Setup\Controller\AcademicDegreeController;
use Setup\Controller\AcademicProgramController;
use Setup\Controller\AcademicUniversityController;
use Setup\Controller\BranchController;
use Setup\Controller\CompanyController;
use Setup\Controller\DepartmentController;
use Setup\Controller\DesignationController;
use Setup\Controller\EmployeeController;
use Setup\Controller\ExperienceController;
use Setup\Controller\FileTypeController;
use Setup\Controller\FunctionalLevelsController;
use Setup\Controller\FunctionalTypesController;
use Setup\Controller\InstituteController;
use Setup\Controller\JobHistoryController;
use Setup\Controller\LoanController;
use Setup\Controller\LocationController;
use Setup\Controller\PositionController;
use Setup\Controller\RecommendApproveController;
use Setup\Controller\ServiceEventTypeController;
use Setup\Controller\ServiceQuestionController;
use Setup\Controller\ServiceTypeController;
use Setup\Controller\TrainingController;
use Setup\Controller\EventsAndConferenceController;
use Setup\Controller\ShiftGroupController;
use Setup\Controller\TravelCategoryController;
use Setup\Controller\TravelExpClassController;
use Setup\Controller\BankController;


use Zend\Router\Http\Segment;

return [
    'router' => [
        'routes' => [
            'employee' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/setup/employee[/:action[/:id[/:tab]]]',
                    'defaults' => [
                        'controller' => EmployeeController::class,
                        'action' => 'index'
                    ]
                ]
            ],
            'designation' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/setup/designation[/:action[/:id]]',
                    'defaults' => [
                        'controller' => DesignationController::class,
                        'action' => 'index'
                    ]
                ]
            ],
            'travelCategory' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/setup/travelCategory[/:action[/:id]]',
                    'defaults' => [
                        'controller' => TravelCategoryController::class,
                        'action' => 'index'
                    ],
                ],
            ],
            'travelExpenseClass' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/setup/travelExpenseClass[/:action[/:id]]',
                    'defaults' => [
                        'controller' => TravelExpClassController::class,
                        'action' => 'index'
                    ],
                ],
            ],
            'company' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/setup/company[/:action[/:id]]',
                    'constraints' => [
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'id' => '[0-9]+',
                    ],
                    'defaults' => [
                        'controller' => CompanyController::class,
                        'action' => 'index',
                    ],
                ],
            ],
            'branch' => [
                'type' => segment::class,
                'options' => [
                    'route' => '/setup/branch[/:action[/:id]]',
                    'constraints' => [
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'id' => '[0-9]+',
                    ],
                    'defaults' => [
                        'controller' => BranchController::class,
                        'action' => 'index',
                    ]
                ],
            ],
            'department' => [
                'type' => segment::class,
                'options' => [
                    'route' => '/setup/department[/:action[/:id]]',
                    'constants' => [
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'id' => '[0-9]+',
                    ],
                    'defaults' => [
                        'controller' => DepartmentController::class,
                        'action' => 'index',
                    ]
                ],
            ],
            'position' => [
                'type' => segment::class,
                'options' => [
                    'route' => '/setup/position[/:action[/:id]]',
                    'constants' => [
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'id' => '[0-9]+',
                    ],
                    'defaults' => [
                        'controller' => PositionController::class,
                        'action' => 'index',
                    ]
                ],
            ],
            'serviceType' => [
                'type' => segment::class,
                'options' => [
                    'route' => '/setup/serviceType[/:action[/:id]]',
                    'constants' => [
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'id' => '[0-9]+',
                    ],
                    'defaults' => [
                        'controller' => ServiceTypeController::class,
                        'action' => 'index',
                    ]
                ],
            ],
            'jobHistory' => [
                'type' => segment::class,
                'options' => [
                    'route' => '/history/jobHistory[/:action[/:id]]',
                    'constant' => [
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'id' => '[0-9]+',
                    ],
                    'defaults' => [
                        'controller' => JobHistoryController::class,
                        'action' => 'index',
                    ]
                ],
            ],
            'recommendapprove' => [
                'type' => segment::class,
                'options' => [
                    'route' => '/setup/recommendapprove[/:action[/:id]]',
                    'constants' => [
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'id' => '[0-9]+',
                    ],
                    'defaults' => [
                        'controller' => RecommendApproveController::class,
                        'action' => 'index',
                    ]
                ],
            ],
            'serviceEventType' => [
                'type' => segment::class,
                'options' => [
                    'route' => '/setup/serviceEventType[/:action[/:id]]',
                    'constants' => [
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'id' => '[0-9]+',
                    ],
                    'defaults' => [
                        'controller' => ServiceEventTypeController::class,
                        'action' => 'index',
                    ]
                ],
            ],
            'academicDegree' => [
                'type' => segment::class,
                'options' => [
                    'route' => '/setup/academicDegree[/:action[/:id]]',
                    'constants' => [
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'id' => '[0-9]+',
                    ],
                    'defaults' => [
                        'controller' => AcademicDegreeController::class,
                        'action' => 'index',
                    ]
                ],
            ],
            'academicUniversity' => [
                'type' => segment::class,
                'options' => [
                    'route' => '/setup/academicUniversity[/:action[/:id]]',
                    'constants' => [
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'id' => '[0-9]+',
                    ],
                    'defaults' => [
                        'controller' => AcademicUniversityController::class,
                        'action' => 'index',
                    ]
                ],
            ],
            'academicProgram' => [
                'type' => segment::class,
                'options' => [
                    'route' => '/setup/academicProgram[/:action[/:id]]',
                    'constants' => [
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'id' => '[0-9]+',
                    ],
                    'defaults' => [
                        'controller' => AcademicProgramController::class,
                        'action' => 'index',
                    ]
                ],
            ],
            'academicCourse' => [
                'type' => segment::class,
                'options' => [
                    'route' => '/setup/academicCourse[/:action[/:id]]',
                    'constants' => [
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'id' => '[0-9]+',
                    ],
                    'defaults' => [
                        'controller' => AcademicCourseController::class,
                        'action' => 'index',
                    ]
                ],
            ],
            'training' => [
                'type' => segment::class,
                'options' => [
                    'route' => '/setup/training[/:action[/:id]]',
                    'constants' => [
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'id' => '[0-9]+',
                    ],
                    'defaults' => [
                        'controller' => TrainingController::class,
                        'action' => 'index',
                    ]
                ],
            ],
            'loan' => [
                'type' => segment::class,
                'options' => [
                    'route' => '/setup/loan[/:action[/:id]]',
                    'constants' => [
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'id' => '[0-9]+',
                    ],
                    'defaults' => [
                        'controller' => LoanController::class,
                        'action' => 'index',
                    ]
                ],
            ],
            'institute' => [
                'type' => segment::class,
                'options' => [
                    'route' => '/setup/institute[/:action[/:id]]',
                    'constants' => [
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'id' => '[0-9]+',
                    ],
                    'defaults' => [
                        'controller' => InstituteController::class,
                        'action' => 'index',
                    ]
                ],
            ],
            'experience' => [
                'type' => segment::class,
                'options' => [
                    'route' => '/setup/experience[/:action[/:id]]',
                    'constants' => [
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'id' => '[0-9]+',
                    ],
                    'defaults' => [
                        'controller' => ExperienceController::class,
                        'action' => 'index',
                    ]
                ],
            ],
            'serviceQuestion' => [
                'type' => segment::class,
                'options' => [
                    'route' => '/setup/serviceQuestion[/:action[/:id]]',
                    'constants' => [
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'id' => '[0-9]+',
                    ],
                    'defaults' => [
                        'controller' => ServiceQuestionController::class,
                        'action' => 'index',
                    ]
                ],
            ],
            'location' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/setup/location[/:action[/:id]]',
                    'defaults' => [
                        'controller' => LocationController::class,
                        'action' => 'index'
                    ]
                ]
            ],
            'functionalTypes' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/setup/functionalTypes[/:action[/:id]]',
                    'defaults' => [
                        'controller' => FunctionalTypesController::class,
                        'action' => 'index'
                    ]
                ]
            ],
            'functionalLevels' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/setup/functionalLevels[/:action[/:id]]',
                    'defaults' => [
                        'controller' => FunctionalLevelsController::class,
                        'action' => 'index'
                    ]
                ]
            ],
            'fileType' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/setup/fileType[/:action[/:id]]',
                    'defaults' => [
                        'controller' => FileTypeController::class,
                        'action' => 'index'
                    ]
                ]
            ],
            'shiftGroup' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/setup/shiftGroup[/:action[/:id]]',
                    'defaults' => [
                        'controller' => ShiftGroupController::class,
                        'action' => 'index'
                    ]
                ]
            ],
            'bank' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/setup/bank[/:action[/:id]]',
                    'defaults' => [
                        'controller' => BankController::class,
                        'action' => 'index'
                    ]
                ]
            ],
            'events' => [
                'type' => segment::class,
                'options' => [
                    'route' => '/setup/events[/:action[/:id]]',
                    'constants' => [
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'id' => '[0-9]+',
                    ],
                    'defaults' => [
                        'controller' => EventsAndConferenceController::class,
                        'action' => 'index',
                    ]
                ],
            ],
        ]
    ],
    'navigation' => [
        'employee' => [
            [
                'label' => 'Employee',
                'route' => 'employee',
            ],
            [
                'label' => 'Employee',
                'route' => 'employee',
                'pages' => [
                    [
                        'label' => 'List',
                        'route' => 'employee',
                        'action' => 'index',
                    ],
                    [
                        'label' => 'Add',
                        'route' => 'employee',
                        'action' => 'add',
                    ],
                    [
                        'label' => 'Edit',
                        'route' => 'employee',
                        'action' => 'edit',
                    ],
                    [
                        'label' => 'Detail',
                        'route' => 'employee',
                        'action' => 'view',
                    ],
                ]
            ]
        ],
        'designation' => [
            [
                'label' => 'Designation',
                'route' => 'designation',
            ],
            [
                'label' => 'Designation',
                'route' => 'designation',
                'pages' => [
                    [
                        'label' => 'List',
                        'route' => 'designation',
                        'action' => 'index',
                    ],
                    [
                        'label' => 'Add',
                        'route' => 'designation',
                        'action' => 'add',
                    ],
                    [
                        'label' => 'Edit',
                        'route' => 'designation',
                        'action' => 'edit',
                    ],
                ]
            ]
        ],
        'company' => [
            [
                'label' => 'Company',
                'route' => 'company',
            ],
            [
                'label' => 'Company',
                'route' => 'company',
                'pages' => [
                    [
                        'label' => 'List',
                        'route' => 'company',
                        'action' => 'index',
                    ],
                    [
                        'label' => 'Add',
                        'route' => 'company',
                        'action' => 'add',
                    ],
                    [
                        'label' => 'Edit',
                        'route' => 'company',
                        'action' => 'edit',
                    ],
                ]
            ]
        ],
        'branch' => [
            [
                'label' => 'Branch',
                'route' => 'branch',
            ],
            [
                'label' => 'Branch',
                'route' => 'branch',
                'pages' => [
                    [
                        'label' => 'List',
                        'route' => 'branch',
                        'action' => 'index',
                    ],
                    [
                        'label' => 'Add',
                        'route' => 'branch',
                        'action' => 'add',
                    ],
                    [
                        'label' => 'Edit',
                        'route' => 'branch',
                        'action' => 'edit',
                    ],
                ]
            ]
        ],
        'department' => [
            [
                'label' => 'Department',
                'route' => 'department',
            ],
            [
                'label' => 'Department',
                'route' => 'department',
                'pages' => [
                    [
                        'label' => 'List',
                        'route' => 'department',
                        'action' => 'index',
                    ],
                    [
                        'label' => 'Add',
                        'route' => 'department',
                        'action' => 'add',
                    ],
                    [
                        'label' => 'Edit',
                        'route' => 'department',
                        'action' => 'edit',
                    ],
                ]
            ]
        ],
        'position' => [
            [
                'label' => 'Position',
                'route' => 'position',
            ],
            [
                'label' => 'Position',
                'route' => 'position',
                'pages' => [
                    [
                        'label' => 'List',
                        'route' => 'position',
                        'action' => 'index',
                    ],
                    [
                        'label' => 'Add',
                        'route' => 'position',
                        'action' => 'add',
                    ],
                    [
                        'label' => 'Edit',
                        'route' => 'position',
                        'action' => 'edit',
                    ],
                ]
            ]
        ],
        'serviceType' => [
            [
                'label' => 'Service Type',
                'route' => 'serviceType',
            ],
            [
                'label' => 'Service Type',
                'route' => 'serviceType',
                'pages' => [
                    [
                        'label' => 'List',
                        'route' => 'serviceType',
                        'action' => 'index',
                    ],
                    [
                        'label' => 'Add',
                        'route' => 'serviceType',
                        'action' => 'add',
                    ],
                    [
                        'label' => 'Edit',
                        'route' => 'serviceType',
                        'action' => 'edit',
                    ],
                ]
            ]
        ],
        'jobHistory' => [
            [
                'label' => 'Service Status Update',
                'route' => 'jobHistory',
            ],
            [
                'label' => 'Service Status Update',
                'route' => 'jobHistory',
                'pages' => [
                    [
                        'label' => 'List',
                        'route' => 'jobHistory',
                        'action' => 'index',
                    ],
                    [
                        'label' => 'Add',
                        'route' => 'jobHistory',
                        'action' => 'add',
                    ],
                    [
                        'label' => 'Edit',
                        'route' => 'jobHistory',
                        'action' => 'edit',
                    ],
                    [
                        'label' => 'New Employees',
                        'route' => 'jobHistory',
                        'action' => 'newEmployee',
                    ],
                    [
                        'label' => 'Resigned and Retired Employees',
                        'route' => 'jobHistory',
                        'action' => 'resignedRetired',
                    ],
                ]
            ]
        ],
        'recommendapprove' => [
            [
                'label' => 'Reporting Hierarchy',
                'route' => 'recommendapprove',
            ],
            [
                'label' => 'Reporting Hierarchy',
                'route' => 'recommendapprove',
                'pages' => [
                    [
                        'label' => 'List',
                        'route' => 'recommendapprove',
                        'action' => 'index',
                    ],
                    [
                        'label' => 'Edit',
                        'route' => 'recommendapprove',
                        'action' => 'edit',
                    ],
                    [
                        'label' => 'Group Assign',
                        'route' => 'recommendapprove',
                        'action' => 'groupAssign',
                    ],
                    [
                        'label' => 'Override',
                        'route' => 'recommendapprove',
                        'action' => 'override',
                    ],
                ]
            ]
        ],
        'serviceEventType' => [
            [
                'label' => 'Service Event Type',
                'route' => 'serviceEventType',
            ],
            [
                'label' => 'Service Event Type',
                'route' => 'serviceEventType',
                'pages' => [
                    [
                        'label' => 'List',
                        'route' => 'serviceEventType',
                        'action' => 'index',
                    ],
                    [
                        'label' => 'Add',
                        'route' => 'serviceEventType',
                        'action' => 'add',
                    ],
                    [
                        'label' => 'Edit',
                        'route' => 'serviceEventType',
                        'action' => 'edit',
                    ],
                ]
            ]
        ],
        'academicDegree' => [
            [
                'label' => 'Academic Degree',
                'route' => 'academicDegree',
            ],
            [
                'label' => 'Academic Degree',
                'route' => 'academicDegree',
                'pages' => [
                    [
                        'label' => 'List',
                        'route' => 'academicDegree',
                        'action' => 'index',
                    ],
                    [
                        'label' => 'Add',
                        'route' => 'academicDegree',
                        'action' => 'add',
                    ],
                    [
                        'label' => 'Edit',
                        'route' => 'academicDegree',
                        'action' => 'edit',
                    ],
                ]
            ]
        ],
        'academicUniversity' => [
            [
                'label' => 'Academic University',
                'route' => 'academicUniversity',
            ],
            [
                'label' => 'Academic University',
                'route' => 'academicUniversity',
                'pages' => [
                    [
                        'label' => 'List',
                        'route' => 'academicUniversity',
                        'action' => 'index',
                    ],
                    [
                        'label' => 'Add',
                        'route' => 'academicUniversity',
                        'action' => 'add',
                    ],
                    [
                        'label' => 'Edit',
                        'route' => 'academicUniversity',
                        'action' => 'edit',
                    ],
                ]
            ]
        ],
        'academicProgram' => [
            [
                'label' => 'Academic Program',
                'route' => 'academicProgram',
            ],
            [
                'label' => 'Academic Program',
                'route' => 'academicProgram',
                'pages' => [
                    [
                        'label' => 'List',
                        'route' => 'academicProgram',
                        'action' => 'index',
                    ],
                    [
                        'label' => 'Add',
                        'route' => 'academicProgram',
                        'action' => 'add',
                    ],
                    [
                        'label' => 'Edit',
                        'route' => 'academicProgram',
                        'action' => 'edit',
                    ],
                ]
            ]
        ],
        'academicCourse' => [
            [
                'label' => 'Academic Course',
                'route' => 'academicCourse',
            ],
            [
                'label' => 'Academic Course',
                'route' => 'academicCourse',
                'pages' => [
                    [
                        'label' => 'List',
                        'route' => 'academicCourse',
                        'action' => 'index',
                    ],
                    [
                        'label' => 'Add',
                        'route' => 'academicCourse',
                        'action' => 'add',
                    ],
                    [
                        'label' => 'Edit',
                        'route' => 'academicCourse',
                        'action' => 'edit',
                    ],
                ]
            ]
        ],
        'training' => [
            [
                'label' => 'Training',
                'route' => 'training',
            ],
            [
                'label' => 'Training',
                'route' => 'training',
                'pages' => [
                    [
                        'label' => 'List',
                        'route' => 'training',
                        'action' => 'index',
                    ],
                    [
                        'label' => 'Add',
                        'route' => 'training',
                        'action' => 'add',
                    ],
                    [
                        'label' => 'Edit',
                        'route' => 'training',
                        'action' => 'edit',
                    ],
                ]
            ]
        ],
        'loan' => [
            [
                'label' => 'Loan',
                'route' => 'loan',
            ],
            [
                'label' => 'Loan',
                'route' => 'loan',
                'pages' => [
                    [
                        'label' => 'List',
                        'route' => 'loan',
                        'action' => 'index',
                    ],
                    [
                        'label' => 'Add',
                        'route' => 'loan',
                        'action' => 'add',
                    ],
                    [
                        'label' => 'Edit',
                        'route' => 'loan',
                        'action' => 'edit',
                    ],
                ]
            ]
        ],
        'institute' => [
            [
                'label' => 'Institute',
                'route' => 'institute',
            ],
            [
                'label' => 'Institute',
                'route' => 'institute',
                'pages' => [
                    [
                        'label' => 'List',
                        'route' => 'institute',
                        'action' => 'index',
                    ],
                    [
                        'label' => 'Add',
                        'route' => 'institute',
                        'action' => 'add',
                    ],
                    [
                        'label' => 'Edit',
                        'route' => 'institute',
                        'action' => 'edit',
                    ],
                ]
            ]
        ],
        'experience' => [
            [
                'label' => 'Experience',
                'route' => 'experience',
            ],
            [
                'label' => 'Experience',
                'route' => 'experience',
                'pages' => [
                    [
                        'label' => 'List',
                        'route' => 'experience',
                        'action' => 'index',
                    ],
                    [
                        'label' => 'Add',
                        'route' => 'experience',
                        'action' => 'add',
                    ],
                    [
                        'label' => 'Edit',
                        'route' => 'experience',
                        'action' => 'edit',
                    ],
                ]
            ]
        ],
        'serviceQuestion' => [
            [
                'label' => 'Service Question',
                'route' => 'serviceQuestion',
            ],
            [
                'label' => 'Service Question',
                'route' => 'serviceQuestion',
                'pages' => [
                    [
                        'label' => 'List',
                        'route' => 'serviceQuestion',
                        'action' => 'index',
                    ],
                    [
                        'label' => 'Add',
                        'route' => 'serviceQuestion',
                        'action' => 'add',
                    ],
                    [
                        'label' => 'Edit',
                        'route' => 'serviceQuestion',
                        'action' => 'edit',
                    ],
                ]
            ]
        ],
        'location' => [
            [
                'label' => 'Location',
                'route' => 'location',
            ],
            [
                'label' => 'Location',
                'route' => 'location',
                'pages' => [
                    [
                        'label' => 'List',
                        'route' => 'location',
                        'action' => 'index',
                    ],
                    [
                        'label' => 'Add',
                        'route' => 'location',
                        'action' => 'add',
                    ],
                    [
                        'label' => 'Edit',
                        'route' => 'location',
                        'action' => 'edit',
                    ],
                ]
            ]
        ],
        'functionalTypes' => [
            [
                'label' => 'FunctionalTypes',
                'route' => 'functionalTypes',
            ],
            [
                'label' => 'FunctionalTypes',
                'route' => 'functionalTypes',
                'pages' => [
                    [
                        'label' => 'List',
                        'route' => 'location',
                        'action' => 'index',
                    ],
                    [
                        'label' => 'Add',
                        'route' => 'location',
                        'action' => 'add',
                    ],
                    [
                        'label' => 'Edit',
                        'route' => 'location',
                        'action' => 'edit',
                    ],
                ]
            ]
        ],
        'functionalLevels' => [
            [
                'label' => 'FunctionalLevels',
                'route' => 'functionalLevels',
            ],
            [
                'label' => 'FunctionalLevels',
                'route' => 'functionalLevels',
                'pages' => [
                    [
                        'label' => 'List',
                        'route' => 'functionalLevels',
                        'action' => 'index',
                    ],
                    [
                        'label' => 'Add',
                        'route' => 'functionalLevels',
                        'action' => 'add',
                    ],
                    [
                        'label' => 'Edit',
                        'route' => 'functionalLevels',
                        'action' => 'edit',
                    ],
                ]
            ]
        ],
        'travelCategory' => [
            [
                'label' => "Travel Category",
                'route' => "travelCategory"
            ],
            [
                'label' => "Travel Category",
                'route' => "travelCategory",
                'pages' => [
                    [
                        'label' => 'List',
                        'route' => 'travelCategory',
                        'action' => 'index',
                    ],
                    [
                        'label' => 'Add',
                        'route' => 'travelCategory',
                        'action' => 'add',
                    ],
                    [
                        'label' => 'Detail',
                        'route' => 'travelCategory',
                        'action' => 'view',
                    ],
                    [
                        'label' => 'Edit',
                        'route' => 'travelCategory',
                        'action' => 'edit',
                    ],
                    [
                        'label' => 'Delete',
                        'route' => 'travelCategory',
                        'action' => 'delete',
                    ],
                ],
            ],
        ],
        'travelExpenseClass' => [
            [
                'label' => "Travel Expense",
                'route' => "travelExpenseClass"
            ],
            [
                'label' => "Travel Expense",
                'route' => "travelExpenseClass",
                'pages' => [
                    [
                        'label' => 'List',
                        'route' => 'travelExpenseClass',
                        'action' => 'index',
                    ],
                    [
                        'label' => 'Add',
                        'route' => 'travelExpenseClass',
                        'action' => 'add',
                    ],

                    [
                        'label' => 'Edit',
                        'route' => 'travelExpenseClass',
                        'action' => 'edit',
                    ],
                    [
                        'label' => 'Delete',
                        'route' => 'travelExpenseClass',
                        'action' => 'delete',
                    ],
                ],
            ],
        ],

        'fileType' => [
            [
                'label' => 'File Type',
                'route' => 'fileType',
            ],
            [
                'label' => 'File Type',
                'route' => 'fileType',
                'pages' => [
                    [
                        'label' => 'List',
                        'route' => 'fileType',
                        'action' => 'index',
                    ],
                    [
                        'label' => 'Add',
                        'route' => 'fileType',
                        'action' => 'add',
                    ],
                    [
                        'label' => 'Edit',
                        'route' => 'fileType',
                        'action' => 'edit',
                    ],
                ]
            ]
        ],
        'shiftGroup' => [
            [
                'label' => 'Best Shift Group',
                'route' => 'shiftGroup',
            ],
            [
                'label' => 'Best Shift Group',
                'route' => 'shiftGroup',
                'pages' => [
                    [
                        'label' => 'List',
                        'route' => 'bestShiftGroup',
                        'action' => 'index',
                    ],
                    [
                        'label' => 'Add',
                        'route' => 'bestShiftGroup',
                        'action' => 'add',
                    ],
                    [
                        'label' => 'Edit',
                        'route' => 'bestShiftGroup',
                        'action' => 'edit',
                    ],
                ]
            ]
        ],
        'bank' => [
            [
                'label' => 'Bank',
                'route' => 'bank',
            ],
            [
                'label' => 'Bank',
                'route' => 'bank',
                'pages' => [
                    [
                        'label' => 'List',
                        'route' => 'bank',
                        'action' => 'index',
                    ],
                    [
                        'label' => 'Add',
                        'route' => 'bank',
                        'action' => 'add',
                    ],
                    [
                        'label' => 'Edit',
                        'route' => 'bank',
                        'action' => 'edit',
                    ],
                ]
            ]
        ],
        'events' => [
            [
                'label' => 'Event and Conference',
                'route' => 'events',
            ],
            [
                'label' => 'Event and Conference',
                'route' => 'events',
                'pages' => [
                    [
                        'label' => 'List',
                        'route' => 'events',
                        'action' => 'index',
                    ],
                    [
                        'label' => 'Add',
                        'route' => 'events',
                        'action' => 'add',
                    ],
                    [
                        'label' => 'Edit',
                        'route' => 'events',
                        'action' => 'edit',
                    ],
                ]
            ]
        ],
    ],
    'controllers' => [
        'factories' => [
            EmployeeController::class => ControllerFactory::class,
            DesignationController::class => ControllerFactory::class,
            CompanyController::class => ControllerFactory::class,
            BranchController::class => ControllerFactory::class,
            DepartmentController::class => ControllerFactory::class,
            PositionController::class => ControllerFactory::class,
            ServiceTypeController::class => ControllerFactory::class,
            JobHistoryController::class => ControllerFactory::class,
            RecommendApproveController::class => ControllerFactory::class,
            ServiceEventTypeController::class => ControllerFactory::class,
            AcademicDegreeController::class => ControllerFactory::class,
            AcademicUniversityController::class => ControllerFactory::class,
            AcademicProgramController::class => ControllerFactory::class,
            AcademicCourseController::class => ControllerFactory::class,
            TrainingController::class => ControllerFactory::class,
            LoanController::class => ControllerFactory::class,
            InstituteController::class => ControllerFactory::class,
            ExperienceController::class => ControllerFactory::class,
            ServiceQuestionController::class => ControllerFactory::class,
            LocationController::class => ControllerFactory::class,
            FunctionalTypesController::class => ControllerFactory::class,
            FunctionalLevelsController::class => ControllerFactory::class,
            FileTypeController::class => ControllerFactory::class,
            ShiftGroupController::class => ControllerFactory::class,
            TravelCategoryController::class => ControllerFactory::class,
            TravelExpClassController::class => ControllerFactory::class,
            EventsAndConferenceController::class => ControllerFactory::class,
            BankController::class => ControllerFactory::class
        ],
    ],
    'view_manager' => [
        'template_path_stack' => [
            __DIR__ . '/../view',
        ],
    ],
];
