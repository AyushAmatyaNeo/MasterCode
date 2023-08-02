<?php

namespace Application\Helper;

use System\Model\RoleSetup;
use Zend\Mvc\Controller\AbstractActionController;

class ACLHelper {

    const ADD = 1;
    const UPDATE = 2;
    const DELETE = 3;

    public static function checkFor($accessId, $acl, AbstractActionController $context) {
        $key = null;
        switch ($accessId) {
            case self::ADD:
                $key = RoleSetup::ALLOW_ADD;
                break;
            case self::UPDATE:
                $key = RoleSetup::ALLOW_UPDATE;
                break;
            case self::DELETE:
                $key = RoleSetup::ALLOW_DELETE;
                break;
        }
        if ($acl[$key] == "N") {
            $context->layout('error/no_access');
            return false;
        } else {
            return true;
        }
    }

}
