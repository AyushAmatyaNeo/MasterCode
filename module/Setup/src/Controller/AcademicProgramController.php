<?php
/**
 * Created by PhpStorm.
 * User: root
 * Date: 11/10/16
 * Time: 4:40 PM
 */
namespace Setup\Controller;

use Application\Helper\Helper;
use Zend\Mvc\Controller\AbstractActionController;

class AcademicProgramController extends AbstractActionController {
    public function indexAction()
    {
        return Helper::addFlashMessagesToArray($this,['list'=>'hellow program']);
    }
}