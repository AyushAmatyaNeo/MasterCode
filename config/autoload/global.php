<?php

return [
    'db' => [
        'driver' => 'oci8',
        'connection_string' => '(DESCRIPTION =
        (ADDRESS = (PROTOCOL = TCP)(HOST = localhost)(PORT = 1521))
        (CONNECT_DATA =
        (SERVER = DEDICATED)
        (SERVICE_NAME = JGI)
        )
        )',
        'username' => 'JGI_7980',
        'password' => 'JGI7980',
        'platform_options' => ['quote_identifiers' => false]
    ],
    'service_manager' => [
        'factories' => [
            'Zend\Db\Adapter\Adapter' => 'Zend\Db\Adapter\AdapterServiceFactory',
        ],
    ],
];
