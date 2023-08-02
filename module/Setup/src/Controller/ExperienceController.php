<?php
namespace Setup\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Application\Helper\Helper;

class ExperienceController extends AbstractActionController {

    public function __construct() {
        
    }

    public function indexAction() {
        return Helper::addFlashMessagesToArray($this, ['list' => 'hellow']);
    }
}
