<?php

namespace KioskApi\Controller;

use Exception;
use KioskApi\Repository\LoanlistRepository;
use Zend\Db\Adapter\AdapterInterface;
use Zend\Http\Request;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\JsonModel;

class Loanlist extends AbstractActionController {

    private $adapter;
    private $employeeId;

    public function __construct(AdapterInterface $adapter) {
        $this->adapter = $adapter;
    }

    public function indexAction() {

    }

    public function statusAction() {

        try {
            $request = $this->getRequest();

            //$postedData = $request->getPost();
            $this->employeeId = $request->getHeader('EmployeeId')->getFieldValue();

            $requestType = $request->getMethod();

            $responseData = [];

            switch ($requestType) {
                case Request::METHOD_GET:
                    $responseData = $this->getStatus($this->employeeId);
                    if ($responseData == NULL) {
                        return new JsonModel(['success' => true, 'data' => $responseData, 'message' => 'No record found']);
                    }
                    break;
                default:
                    throw new Exception('the request is unknown');
            }
            return new JsonModel(['success' => true, 'data' => $responseData, 'message' => $requestType]);
        } catch (Exception $e) {
            return new JsonModel(['success' => false, 'data' => $responseData, 'message' => $e->getMessage()]);
        }
    }

    private function getStatus($employeeId) {
        $StatusRepo = new LoanlistRepository($this->adapter);

        return $StatusRepo->fetchLoanList($employeeId);
    }

}
