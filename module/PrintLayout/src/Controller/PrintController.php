<?php

namespace PrintLayout\Controller;

use Application\Controller\HrisController;
use Zend\Authentication\Storage\StorageInterface;
use Zend\Db\Adapter\AdapterInterface;
use Zend\View\Model\ViewModel;
use Application\Helper\Helper;
use Zend\View\Model\JsonModel;
use Exception;
use Zend\Db\Sql\Expression;
use KioskApi\Controller\Authentication;
use Application\Custom\CustomViewModel;
use Zend\Authentication\AuthenticationService;
use Zend\Mvc\Controller\AbstractActionController;
use Application\Helper\EntityHelper;
use PrintLayout\Repository\PrintLayoutRepo;
use PrintLayout\Repository\PrintRepository;
use PrintLayout\Model\PrintLayoutTemplate;
use PrintLayout\Model\ReportEvent;
use PrintLayout\Model\ReportModel;
use Setup\Repository\JobHistoryRepository;
use Setup\Model\JobHistory;
use PrintLayout\Model\TravelFormModel;
use SelfService\Model\TravelRequest;
use Application\Model\Model;
use Setup\Repository\EmployeeRepository;
use Setup\Repository\ServiceEventTypeRepository;
// use Application\Controller\HrisController;


class PrintController extends HrisController {
    
    public function __construct(AdapterInterface $adapter, StorageInterface $storage) {
        parent::__construct($adapter, $storage);
        $this->adapter = $adapter;
        $this->templateRepo = new PrintRepository($adapter);
        $this->serviceEventRepo = new ServiceEventTypeRepository($adapter);
    }

    public function print(ReportModel $model, $type){
        $template = $this->templateRepo->getByCode($type)[0];
        $template['BODY'] = $template['BODY']->load();
        $finalLayout = "";
        $finalLayout .= $model->processString($template['BODY']);
        
        return $finalLayout;
        
        // print_r($finalLayout); die;
    }
    // public function printAction(){
    //     return new ViewModel();
    // }
    private function initializePrintModel($model, $class) {
        // print_r($model->jobHistoryId); die;
        $employeeRepo = new EmployeeRepository($this->adapter);
        $fromEmployee = $employeeRepo->fetchById($model->employeeId);
        $data = $this->templateRepo->getServiceEventVariables($model->jobHistoryId);
        $printModel = new $class();
        $printModel->employeeName = $fromEmployee['FIRST_NAME'] . " " . $fromEmployee['MIDDLE_NAME'] . " " . $fromEmployee['LAST_NAME'];
        $printModel->date = date("Y/m/d");
        // print_r($data[0]['COMPANY_NAME']); die;
        $printModel->company = $data[0]['COMPANY_NAME'];
        $printModel->startDate = $data[0]['START_DATE'];
        $printModel->endDate = $data[0]['END_DATE'];
        $printModel->branch = $data[0]['BRANCH_NAME'];
        $printModel->department = $data[0]['DEPARTMENT_NAME'];
        $printModel->designation = $data[0]['DESIGNATION_TITLE'];
        $printModel->position = $data[0]['POSITION_NAME'];
        $printModel->serviceEvent = $data[0]['SERVICE_EVENT_TYPE_NAME'];
        $printModel->salary = $data[0]['TO_SALARY'];
        $printModel->eventDate = $data[0]['EVENT_DATE'];
        $printModel->serviceName = $data[0]['SERVICE_TYPE_NAME'];
        // print_r($printModel); die;
        // ->date = date("Y/m/d");
        // $notification->setHonorific();
        return $printModel;
    }

    private function serviceEventLetter(JobHistory $model){
        $print = self::initializePrintModel($model, \PrintLayout\Model\ServiceEventModel::class);
        $serviceEventCode = $this->serviceEventRepo->fetchById($model->serviceEventTypeId);
        return self::print($print, $serviceEventCode['SERVICE_EVENT_TYPE_CODE']);
    }

    public function printServiceEvent($model){
        return self::serviceEventLetter($model);
    }

    public function printTravelForm($model){
        return self::travelForm($model);
    }

    private function travelForm(TravelRequest $model){
        $print = self::initializeTravelPrintModel($model, \PrintLayout\Model\TravelFormModel::class);
        return self::printTravel($print);
    }

    private function initializeTravelPrintModel($model, $class) {
        // print_r($model);die;
        // print_r((strtotime($model->toDate)-strtotime($model->fromDate))/86400 +1 ); die; //, $model->toDate
        
        $employeeRepo = new EmployeeRepository($this->adapter);
        $fromEmployee = $employeeRepo->fetchById($model->employeeId);
        $approvedBy = $employeeRepo->fetchById($model->approvedBy);
        $recommendedBy = $employeeRepo->fetchById($model->recommendedBy);
        $printedBy = $employeeRepo->fetchById($this->employeeId);
        // $data = $this->templateRepo->getServiceEventVariables($model->jobHistoryId);
        $printModel = new $class();
        $printModel->employeeName = $fromEmployee['FIRST_NAME'] . " " . $fromEmployee['MIDDLE_NAME'] . " " . $fromEmployee['LAST_NAME'];
        $printModel->travelRequestDate = $model->requestedDate;
        $printModel->startDate = $model->fromDate;
        $printModel->endDate = $model->toDate;
        $printModel->printedDate = date("Y/m/d");
        $printModel->travelToOrForm = $model->destination;
        $printModel->authorizedBy = $recommendedBy['FIRST_NAME'] . " " . $recommendedBy['MIDDLE_NAME'] . " " . $recommendedBy['LAST_NAME'];
        $printModel->reviewedBy = $approvedBy['FIRST_NAME'] . " " . $approvedBy['MIDDLE_NAME'] . " " . $approvedBy['LAST_NAME'];
        $printModel->submittedBy = $printModel->employeeName;
        $printModel->propose = $model->purpose;
        $printModel->printedBy = $printedBy['FIRST_NAME'] . " " . $printedBy['MIDDLE_NAME'] . " " . $printedBy['LAST_NAME'];
        $printModel->findingsActions = $model->recommendedRemarks;
        $printModel->requiredFollowUpAction = $model->approvedRemarks;
        $printModel->summaryOfTravel = (strtotime($model->toDate)-strtotime($model->fromDate))/86400 +1;
        // print_r($printModel);die;//destination
        // print_r($data[0]['COMPANY_NAME']); die;
        // print_r($printModel); die;
        // ->date = date("Y/m/d");
        // $notification->setHonorific();
        return $printModel;
    }

    public function printTravel(ReportModel $model){
        $template = $this->templateRepo->getByCode("TRF")[0];
        $template['BODY'] = $template['BODY']->load();
        $finalLayout = "";
        $finalLayout .= $model->processString($template['BODY']);
        // print_r($finalLayout);die;
        return $finalLayout;
        
        // print_r($finalLayout); die;
    }
}



    

