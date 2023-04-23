<?php
namespace Loan\Controller;

use Application\Controller\HrisController;
use Zend\Db\Adapter\AdapterInterface;
use Application\Helper\Helper;
use Application\Helper\EntityHelper;
use Zend\Form\Annotation\AnnotationBuilder;
use SelfService\Form\LoanRequestForm;
use Loan\Form\LoanClosing AS LoanClosingForm;
use Setup\Model\HrEmployees;
use Zend\Authentication\Storage\StorageInterface;
use Loan\Model\LoanClosing AS LoanClosingModel;
use SelfService\Repository\LoanRequestRepository;
use Loan\Repository\LoanClosingRepository;
use SelfService\Model\LoanRequest as LoanRequestModel;
use Setup\Model\Loan;
use ManagerService\Repository\LoanApproveRepository;

class LoanApply extends HrisController{
    protected $form;
    protected $loanClosingForm;
    protected $adapter;
    protected $loanRequesteRepository;
    protected $loanClosingRepository;
	protected $loanApproveRepository;
    
    public function __construct(AdapterInterface $adapter, StorageInterface $storage) {
        parent::__construct($adapter, $storage);
        $this->loanRequesteRepository = new LoanRequestRepository($adapter);
        $this->loanClosingRepository = new LoanClosingRepository($adapter);
		$this->loanApproveRepository = new LoanApproveRepository($adapter);
    }
    public function initializeLoanForm(){
        $builder = new AnnotationBuilder();
        $form = new LoanRequestForm();
        $this->form = $builder->createForm($form);
    }
    public function initializeClosingForm(){
        $builder = new AnnotationBuilder();
        $loanClosingForm = new LoanClosingForm();
        $this->loanClosingForm = $builder->createForm($loanClosingForm);
    }
    
    public function indexAction() {
       return $this->redirect()->toRoute("loanStatus");
    }

    public function addAction() {
        $this->initializeLoanForm();
        $request = $this->getRequest();
        $model = new LoanRequestModel();  

        if ($request->isPost()) {
            $this->form->setData($request->getPost());
            if ($this->form->isValid()) {
                $model->exchangeArrayFromForm($this->form->getData());
                $model->loanRequestId = ((int) Helper::getMaxId($this->adapter, LoanRequestModel::TABLE_NAME, LoanRequestModel::LOAN_REQUEST_ID)) + 1;
                $model->requestedDate = Helper::getcurrentExpressionDate();
                $model->status = 'AP';
				$model->printFlag = 'Y';
                $model->deductOnSalary = 'Y';
				/*if($model->loanId = 6){
                    $model->interestRate = 0;   
                } */
                $this->loanRequesteRepository->add($model);
				$this->loanApproveRepository->addToDetails($model->loanRequestId);
                $this->flashmessenger()->addMessage("Loan Request Successfully added!!!");
                return $this->redirect()->toRoute("loanStatus");
            }
        }
        $rateDetails = $this->loanRequesteRepository->getLoanDetails();

        return Helper::addFlashMessagesToArray($this, [
                    'form' => $this->form,
                    'rateDetails' => Helper::extractDbData($rateDetails),
                    'employees'=> EntityHelper::getTableKVListWithSortOption($this->adapter, "HRIS_EMPLOYEES", "EMPLOYEE_ID", ["EMPLOYEE_CODE", "FIRST_NAME", "MIDDLE_NAME", "LAST_NAME"],["STATUS"=>'E','RETIRED_FLAG'=>'N'],"FIRST_NAME","ASC"," ",FALSE,TRUE, $this->employeeId),
                    'loans' => EntityHelper::getTableKVListWithSortOption($this->adapter, Loan::TABLE_NAME, Loan::LOAN_ID, [Loan::LOAN_NAME], [Loan::STATUS => "E"], Loan::LOAN_ID, "ASC",NULL,FALSE,TRUE)
        ]);
    }

    public function issueNewLoanAsRequired($emp_id, $paymentAmount, $loan_id, $repaymentMonths, $loanAmount){
        $model = new LoanRequestModel();
        $model->loanRequestId = ((int) Helper::getMaxId($this->adapter, LoanRequestModel::TABLE_NAME, LoanRequestModel::LOAN_REQUEST_ID)) + 1;
        $model->requestedDate = Helper::getcurrentExpressionDate();
        $model->loanDate = '02-Aug-22';
        $model->status = 'RQ';
		$model->interestRate = Helper::extractDbData($this->loanClosingRepository->getRateByLoanId($loan_id))[0]['INTEREST_RATE'] ;
        $model->employeeId = $emp_id;
		
        $model->repaymentMonths = $repaymentMonths;
        $model->requestedAmount = $loanAmount;
        $model->reason = 'Issued for cash payment';
        $model->loanId = $loan_id;
        $model->deductOnSalary = 'Y';
		
        $this->loanRequesteRepository->add($model);
        $newLoanReqId = ((int) Helper::getMaxId($this->adapter, LoanRequestModel::TABLE_NAME, LoanRequestModel::LOAN_REQUEST_ID));
        $model = new LoanClosingModel();
        $loanApproveRepository = new LoanApproveRepository($this->adapter);
        $loanRequest = new LoanRequestModel();
        $model->newLoanReqId = $newLoanReqId;
        //$this->loanClosingRepository->edit($model, $old_loan_req_id);
        $loanRequest->status = "AP";
        $loanApproveRepository->addToDetails($newLoanReqId);
        $loanRequest->approvedBy = $this->employeeId;
        $loanRequest->approvedRemarks = 'Issued for cash payment';
        $loanApproveRepository->edit($loanRequest, $newLoanReqId);
        $this->flashmessenger()->addMessage("Loan Successfully closed with this payment 
        and new Loan has been issued and approved!!!");
        return $this->redirect()->toRoute("loanStatus", [
                'controller' => 'loanStatus',
                'action' => 'list'
            ]);
    }

    public function loanClosingAction() {
        $this->initializeClosingForm();
        $request = $this->getRequest();
        $model = new LoanClosingModel();
        $id = (int) $this->params()->fromRoute('id');
		$eid = (int) $this->params()->fromRoute('eid');
		
        if ($request->isPost()) {
            $this->loanClosingForm->setData($request->getPost());			
            if ($this->loanClosingForm->isValid()) {
                $model->exchangeArrayFromForm($this->loanClosingForm->getData());
                $model->id = ((int) Helper::getMaxId($this->adapter, LoanClosingModel::TABLE_NAME, LoanClosingModel::ID)) + 1;
                //$model->paymentDate = Helper::getcurrentExpressionDate();
                $model->loan_id = $id;
				$model->employee_id = $eid;
				$model->type = 'PRN';
				$paymentAmount = $model->paymentAmount;
	
				$this->loanClosingRepository->add($model);

                
                
				
				//insert into interest as another row
				$model->id = ((int) Helper::getMaxId($this->adapter, LoanClosingModel::TABLE_NAME, LoanClosingModel::ID)) + 1;
				$model->type = 'INT';
				$model->paymentAmount = $model->interest;
				$model->interest = '';
				$this->loanClosingRepository->add($model);
				
				$loanAmount = Helper::extractDbData($this->loanClosingRepository->getRemainingAmount($id, $eid, $paymentAmount));
           
				$loanAmount = $loanAmount[0]['REMAINING_AMOUNT'];
                $newLoanAmount = $loanAmount - $_POST['totalPaid'];
				$this->loanClosingRepository->updateLoanStatus($id, $eid);
                if(!empty($_POST['repaymentMonths'])){
                    $this->issueNewLoanAsRequired($eid, $paymentAmount, $id, $_POST['repaymentMonths'], $newLoanAmount);
                }
                else{
                    $this->flashmessenger()->addMessage("Loan Successfully closed with this payment!!!");
                }
                return $this->redirect()->toRoute("loanStatus");
            }
        }  
		
        return Helper::addFlashMessagesToArray($this, [
            'form' => $this->loanClosingForm,
            'id' => $id,
			'eid' => $eid,
            'employee'=> EntityHelper::getTableKVListWithSortOption($this->adapter, "HRIS_EMPLOYEES", "EMPLOYEE_ID", ["FIRST_NAME", "MIDDLE_NAME", "LAST_NAME"],["EMPLOYEE_ID"=>$eid,"STATUS"=>'E','RETIRED_FLAG'=>'N'],"FIRST_NAME","ASC"," ",FALSE,TRUE),
            'unpaidAmount'=>Helper::extractDbData($this->loanClosingRepository->getRemainingAmount($id, $eid, $paymentAmount))[0]['REMAINING_AMOUNT']
        ]);
    }
    public function changeInstallmentAction(){
        $request = $this->getRequest();
        $id = (int) $this->params()->fromRoute('id');
        $eid = (int) $this->params()->fromRoute('eid');

        if ($request->isPost()) {
            $data = $request->getPost();
            $model = new LoanRequestModel();
            $model->loanRequestId = ((int) Helper::getMaxId($this->adapter, LoanRequestModel::TABLE_NAME, LoanRequestModel::LOAN_REQUEST_ID)) + 1;
            $model->requestedDate = Helper::getcurrentExpressionDate();
            $model->loanDate = Helper::getcurrentExpressionDate();
            $model->status = 'AP';
            $model->interestRate = Helper::extractDbData($this->loanClosingRepository->getRateByLoanId($id))[0]['INTEREST_RATE'] ;
            $model->employeeId = $eid;
            
            $model->repaymentMonths = $data['months'];
            $model->requestedAmount = $data['unpaidTotal'];
            $model->reason = 'Installment Change';
            $model->loanId = $id;
            $model->deductOnSalary = 'Y';


            //Print_r($_POST);
            //exit;
            $this->loanClosingRepository->updateLoanStatus($id, $eid);
            $this->loanRequesteRepository->add($model);
            $loanApproveRepository = new LoanApproveRepository($this->adapter);
            $loanApproveRepository->addToDetails($model->loanRequestId);


            



            $this->flashmessenger()->addMessage("Loan Installment Changed!!!");
            return $this->redirect()->toRoute("loanStatus", [
                'controller' => 'loanStatus',
                'action' => 'list'
            ]);
        }
        
        return Helper::addFlashMessagesToArray($this, [
            'id' => $id,
            'eid' => $eid,
            'employee'=> EntityHelper::getTableKVListWithSortOption($this->adapter, "HRIS_EMPLOYEES", "EMPLOYEE_ID", ["FIRST_NAME", "MIDDLE_NAME", "LAST_NAME"],["EMPLOYEE_ID"=>$eid,"STATUS"=>'E','RETIRED_FLAG'=>'N'],"FIRST_NAME","ASC"," ",FALSE,TRUE),
            'unpaidAmount'=>Helper::extractDbData($this->loanClosingRepository->getUnpaidAmount($id, $eid))[0]['UNPAID_AMOUNT']
        ]);
    }

    public function rectifyAction() {
        $this->initializeClosingForm();
        $request = $this->getRequest();
        $id = (int) $this->params()->fromRoute('id');
        $model = new LoanClosingModel();
        $paymentId = Helper::extractDbData($this->loanClosingRepository->getPaymentId($id))[0]["ID"];
        if ($request->isPost()) {
            $data = $request->getPost();
            //echo '<pre>'; print_r($data); die;
            $this->loanClosingRepository->rectify($paymentId, $data);
            $this->flashmessenger()->addMessage("Amount has been rectified successfully!!!");
            return $this->redirect()->toRoute("loanStatus");
        }  
        $detail = $this->loanClosingRepository->fetchById($paymentId);
        $model->exchangeArrayFromDB($detail);
        $this->loanClosingForm->bind($model);
        $emp_id = $this->loanClosingRepository->getEmployeeByLoanRequestId($id);
        $emp_id = Helper::extractDbData($emp_id)[0]['EMPLOYEE_ID'];
        
        return Helper::addFlashMessagesToArray($this, [
            'form' => $this->loanClosingForm,
            'id' => $id,
            'rate' => Helper::extractDbData($this->loanClosingRepository->getRateByLoanReqId($id))[0]['INTEREST_RATE'],
            'employee'=> EntityHelper::getTableKVListWithSortOption($this->adapter, "HRIS_EMPLOYEES", "EMPLOYEE_ID", ["FIRST_NAME", "MIDDLE_NAME", "LAST_NAME"],["EMPLOYEE_ID"=>$emp_id,"STATUS"=>'E','RETIRED_FLAG'=>'N'],"FIRST_NAME","ASC"," ",FALSE,TRUE),
            'unpaidAmount'=>Helper::extractDbData($this->loanClosingRepository->getUnpaidAmount($id))[0]['UNPAID_AMOUNT']
        ]);
    }
}