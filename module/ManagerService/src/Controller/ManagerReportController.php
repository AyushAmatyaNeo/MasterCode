<?php

namespace ManagerService\Controller;

use Application\Custom\CustomViewModel;
use Application\Helper\Helper;
use ManagerService\Repository\ManagerReportRepo;
use Zend\Authentication\AuthenticationService;
use Zend\Db\Adapter\AdapterInterface;
use Zend\Form\Element\Select;
use Zend\Mvc\Controller\AbstractActionController;


class ManagerReportController extends AbstractActionController{
    
    private $repository;
    private $employeeId;
    
    public function __construct(AdapterInterface $adapter) {
        $this->repository = new ManagerReportRepo($adapter);
        $authService = new AuthenticationService();
        $detail = $authService->getIdentity();
        $this->employeeId = $detail['employee_id'];
    }
    
    
    public function pullAttendanceAction(){
        $request = $this->getRequest();
        $postedData = $request->getPost();
        
        $filtersDetail = $postedData['data'];
        $currentEmployeeId = $filtersDetail['currentEmployee'];
        $employeeId = $filtersDetail['employeeId'];
        $fromDate = $filtersDetail['fromDate'];
        $toDate = $filtersDetail['toDate'];
        $status = $filtersDetail['status'];
        $missPunchOnly = ((int) $filtersDetail['missPunchOnly'] == 1) ? true : false;
        
        $result=$this->repository->attendanceReport($currentEmployeeId,$fromDate, $toDate, $employeeId, $status, $missPunchOnly);
        
        $list = [];
        foreach ($result as $row) {
            array_push($list, $row);
        }
        
        
        return new CustomViewModel([
            "success" => true,
            'data' => $list
        ]);

    }
    
   
    public function indexAction(){
        $statusFormElement = new Select();
        $statusFormElement->setName("status");
        $status = array(
            "All" => "All Status",
            "P" => "Present Only",
            "A" => "Absent Only",
            "H" => "On Holiday",
            "L" => "On Leave",
            "T" => "On Training",
            "TVL" => "On Travel",
            "WOH" => "Work on Holiday",
            "WOD" => "Work on DAYOFF",
            "LI" => "Late In",
            "EO" => "Early Out"
        );
        
        $statusFormElement->setValueOptions($status);
        $statusFormElement->setAttributes(["id" => "statusId", "class" => "form-control reset-field"]);
        $statusFormElement->setLabel("Status");
        
        
        $employees= $this->repository->fetchAllEmployee($this->employeeId);
        
        $employeeFormElement= new Select();
        $employeeFormElement->setName('Employee');
        $employeeFormElement->setValueOptions($employees);
        $employeeFormElement->setAttributes(["id" => "employeeId", "class" => "form-control reset-field"]);
        $employeeFormElement->setLabel("Employee");

        return Helper::addFlashMessagesToArray($this, [
                    'employeeId' => $this->employeeId,
                    'status' => $statusFormElement,
                    'employeeFromElement'=>$employeeFormElement,
                    'currentEmployeeId'=>$this->employeeId,
            
        ]);
    }
    
}
