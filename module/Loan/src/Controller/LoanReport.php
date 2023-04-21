<?php

namespace Loan\Controller; 

use Application\Controller\HrisController;
use Application\Custom\CustomViewModel;
use Application\Helper\EntityHelper;
use Application\Helper\Helper;
use Application\Model\FiscalYear;
use Application\Model\HrisQuery;
use Exception;
use Setup\Model\Loan;
use Loan\Repository\LoanReportRepository;
use Zend\Authentication\Storage\StorageInterface;
use Zend\Db\Adapter\AdapterInterface;
use Zend\Db\Sql\Select as Select2;
use Zend\Form\Element\Select; 
use Zend\View\Model\JsonModel;
use Application\Helper\EntityHelper as ApplicationHelper;

class LoanReport extends HrisController {

    public function __construct(AdapterInterface $adapter, StorageInterface $storage) {
        parent::__construct($adapter, $storage);
        $this->initializeRepository(LoanReportRepository::class);
    }

    public function indexAction(){
        $request = $this->getRequest();
        if ($request->isPost()){
            try {
                $data = $request->getPost();
                $result = $this->repository->fetchEmployeeLoanDetails($data);
                $loanDetails = Helper::extractDbData($result);
                return new JsonModel(['success' => true, 'data' => $loanDetails, 'message' => null]);
            } catch (Exception $e) {
                return new JsonModel(['success' => false, 'data' => null, 'message' => $e->getMessage()]);
            }
        } 
        
        return $this->stickFlashMessagesTo([
                'searchValues' => ApplicationHelper::getSearchData($this->adapter),
                'acl' => $this->acl,
                'employeeDetail' => $this->storageData['employee_detail'],
                'preference' => $this->preference
        ]);
    }

    public function loanVoucherAction(){
        $request = $this->getRequest();
        if ($request->isPost()){
            try {
                $emp_id = !empty($_POST['emp_id']) ? $_POST['emp_id'] : null ;
                $fromDate = !empty($_POST['fromDate']) ? $_POST['fromDate'] : null ;
                $toDate = !empty($_POST['toDate']) ? $_POST['toDate'] : null ;
                $loanId = !empty($_POST['loanId']) ? $_POST['loanId'] : null ;
                //$loan_id = !empty($_POST['loan_id']) ? $_POST['loan_id'] : null ;
                $result = $this->repository->fetchLoanVoucher($emp_id, $fromDate, $toDate, $loanId);
				
                $loanVoucherDetails = Helper::extractDbData($result);
                
                $fromDate = '1-Jul-'.date('Y', strtotime($fromDate));
                $result = $this->repository->fetchOpeningBalance($emp_id, $fromDate, $loanId);
				
                $openingBalanceDetails = Helper::extractDbData($result);
                $openingBalanceDetails[0]["OPENING_BALANCE"] = $openingBalanceDetails[0]["OPENING_BALANCE"] == null ? "0.00" : $openingBalanceDetails[0]["OPENING_BALANCE"];
                array_unshift($loanVoucherDetails, ["DT" => $fromDate, 
                "PARTICULARS" => "Opening Balance", 
                "DEBIT_AMOUNT" =>  $openingBalanceDetails[0]["OPENING_BALANCE"],
                "CREDIT_AMOUNT" => "0",
                "BALANCE" => "0"]);
                return new JsonModel(['success' => true, 'data' => $loanVoucherDetails, 'balanceData' => $openingBalanceDetails, 'message' => null]);
            } catch (Exception $e) {
                return new JsonModel(['success' => false, 'data' => null, 'message' => $e->getMessage()]);
            }
        } 
        $loanList = $this->repository->getLoanlist();
        $loanList = Helper::extractDbData($loanList);
		
		$searchValues = ApplicationHelper::getSearchData($this->adapter);

        $allEmployees = Helper::extractDbData($this->repository->getAllEmployees());

        $searchValues['employee'] = $allEmployees;
        
        return $this->stickFlashMessagesTo([
                'searchValues' => $searchValues,
                'acl' => $this->acl,
                'employeeDetail' => $this->storageData['employee_detail'],
                'loanList' => $loanList,
                'preference' => $this->preference
        ]);
    }

    public function cashPaymentReportAction(){
        $request = $this->getRequest();

        if ($request->isPost()) {
            $data = $request->getPost();
            $result = $this->repository->getCashPaymentsList($data);
            $list = Helper::extractDbData($result);
            return new JsonModel([
                "success" => "true",
                "data" => $list
            ]);
        }

        $loanFormElement = new Select();
        $loanFormElement->setName("loan");
        $loans = EntityHelper::getTableKVListWithSortOption($this->adapter, Loan::TABLE_NAME, Loan::LOAN_ID, [Loan::LOAN_NAME], [Loan::STATUS => 'E'], Loan::LOAN_NAME, "ASC", NULL, FALSE, TRUE);
        //$loans1 = [-1 => "All Loans"] + $loans;
        $loanFormElement->setValueOptions($loans);
        $loanFormElement->setAttributes(["id" => "loanId", "multiple" => "multiple", "class" => "form-control reset-field"]);
        $loanFormElement->setLabel("Loan Type");

        $loanStatus = [
            'BOTH' => 'BOTH',
            'OPEN' => 'OPEN',
            'CLOSED' => 'CLOSED'
        ];
        $loanStatusFormElement = new Select();
        $loanStatusFormElement->setName("loanStatus");
        $loanStatusFormElement->setValueOptions($loanStatus);
        $loanStatusFormElement->setAttributes(["id" => "loanRequestStatusId", "class" => "form-control reset-field"]);
        $loanStatusFormElement->setLabel("Status");

        return $this->stickFlashMessagesTo([
            'loans' => $loanFormElement,
            'loanStatus' => $loanStatusFormElement,
            'searchValues' => EntityHelper::getSearchData($this->adapter),
            'acl' => $this->acl,
            'employeeDetail' => $this->storageData['employee_detail'],
            'preference' => $this->preference
        ]);
    }

    public function loanSummaryAction(){
        $request = $this->getRequest();
        if ($request->isPost()){
            // print_r($_POST);die;
            try {
                $emp_id = !empty($_POST['emp_id']) ? $_POST['emp_id'] : null ;
                $fromDate = !empty($_POST['fromDate']) ? $_POST['fromDate'] : null ;
                $toDate = !empty($_POST['toDate']) ? $_POST['toDate'] : null ;
                $loanId = !empty($_POST['loanId']) ? $_POST['loanId'] : null ;
                $data = [];
                
				if($emp_id == null){
					$emp_id = [];
					$emp_id_temp = Helper::extractDbData($this->repository->getAllEmployeeId());
					foreach($emp_id_temp as $eid){						
						array_push($emp_id, $eid["EMPLOYEE_ID"]);
					}
				}
				// print_r($emp_id);die;
                foreach($emp_id as $eid){
                    $result = $this->repository->fetchLoanSummary($eid, $fromDate, $toDate, $loanId);
                    $loanSummary = Helper::extractDbData($result);			
					if($loanSummary[0]['OPENING_BALANCE'] == 0 && $loanSummary[0]['DR_SALARY'] == 0 && $loanSummary[0]['DR_INTEREST'] == 0 && 
					$loanSummary[0]['CR_SALARY'] == 0 && $loanSummary[0]['CR_INTEREST'] == 0){ continue; }
                    array_push($data, $loanSummary[0]);
                }
                
                return new JsonModel(['success' => true, 'data' => $data, 'message' => null]);
            } catch (Exception $e) {
                return new JsonModel(['success' => false, 'data' => null, 'message' => $e->getMessage()]);
            }
        } 
        $loanList = $this->repository->getLoanlist();
        $loanList = Helper::extractDbData($loanList);
        
        $searchValues = ApplicationHelper::getSearchData($this->adapter);

        $allEmployees = Helper::extractDbData($this->repository->getAllEmployees());

        $searchValues['employee'] = $allEmployees;

        return $this->stickFlashMessagesTo([
                'searchValues' => $searchValues,
                'acl' => $this->acl,
                'employeeDetail' => $this->storageData['employee_detail'],
                'loanList' => $loanList,
                'preference' => $this->preference
        ]);
    }
}
