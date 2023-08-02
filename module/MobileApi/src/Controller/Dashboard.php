<?php

namespace MobileApi\Controller;

use Exception;
use MobileApi\Repository\DashboardRepository;
use Zend\Db\Adapter\AdapterInterface;
use Zend\Http\Request;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\JsonModel;

class Dashboard extends AbstractActionController {

    private $adapter;
    private $employeeId;
//    private $startDate;
//    private $endDate;

    public function __construct(AdapterInterface $adapter) {
        $this->adapter = $adapter;
    }
  public function statusAction() {
//     echo'asdf';
//      die();
      try {
            $request = $this->getRequest();
            $this->employeeId = $request->getHeader('Employee-Id')->getFieldValue();
//            $startDate = $request->getHeader('Start-Date')->getFieldValue();
//            $endDate = $request->getHeader('End-date')->getFieldValue();
//            print_r($this->employeeId);
//            print_r($startDate);
//            print_r($endDate);
//            die();
           
            $requestType = $request->getMethod();
            $responseDate = [];
            switch ($requestType) {
                case Request::METHOD_GET:
                   
                    $responseDate = $this->getStatus($this->employeeId);
                    break;
                default:
                    throw new Exception('the request  is unknown');
            }
            return new JsonModel(['success' => true, 'data' => $responseDate, 'message' => null]);
        } catch (Exception $e) {
            return new JsonModel(['success' => false, 'data' => null, 'message' => $e->getMessage()]);
        }
  }
 private function getStatus($employeeId) {
        $StatusRepo = new DashboardRepository($this->adapter);
         $getdate=$StatusRepo-> getMonthDate();
//                    print_r($getdate);
//                    die();
        
        return $StatusRepo->fetchEmployeeDashboardDetail($employeeId, $getdate['FROM_DATE'],$getdate['TO_DATE'] );
    }
  
  
}
