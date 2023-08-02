<?php

namespace System\Model;

use Application\Model\Model;

class DashboardDetail extends Model {

    const TABLE_NAME = "HRIS_DASHBOARD_DETAIL";
    const ROLE_ID = "ROLE_ID";
    const DASHBOARD = "DASHBOARD";
    const ROLE_TYPE = "ROLE_TYPE";

    public $roleId;
    public $dashboard;
    public $roleType;

    public $mappings = [
        'roleId' => self::ROLE_ID,
        'dashboard' => self::DASHBOARD,
        'roleType' => self::ROLE_TYPE
    ];

}
