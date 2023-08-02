<?php

namespace MobileApi\Controller;

use Application\Factory\ConfigInterface;
use Exception;
use MobileApi\Repository\EmployeeRepository;
use Zend\Db\Adapter\AdapterInterface;
use Zend\Http\Request;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\JsonModel;

class Employee extends AbstractActionController {

    private $adapter;
    private $config;
    private $employeeId;

    public function __construct(AdapterInterface $adapter, ConfigInterface $config) {
        $this->adapter = $adapter;
        $this->config = $config->getApplicationConfig();
    }

    public function substituteAction() {
        try {
            $request = $this->getRequest();
            $this->employeeId = $request->getHeader('Employee-Id')->getFieldValue();

            $requestType = $request->getMethod();
//            $data = json_decode($request->getContent());
//            $id = $this->params()->fromRoute('id');
            $responseDate = [];

            switch ($requestType) {
                case Request::METHOD_POST:
                    throw new Exception("Unavailable Request.");
                    break;
                case Request::METHOD_GET:
                    $responseDate = $this->substititeEmployeeGet($this->employeeId);
                    break;

                case Request::METHOD_PUT:
                    throw new Exception("Unavailable Request.");
                    break;

                case Request::METHOD_DELETE:
                    throw new Exception("Unavailable Request.");
                    break;

                default:
                    throw new Exception('the request  is unknown');
            }
            return new JsonModel(['success' => true, 'data' => $responseDate, 'message' => null]);
        } catch (Exception $e) {
            return new JsonModel(['success' => false, 'data' => null, 'message' => $e->getMessage()]);
        }
    }

    private function substititeEmployeeGet($employeeId) {
        $employeeRepo = new EmployeeRepository($this->adapter);
        return $employeeRepo->getApproval($employeeId);
    }

}
