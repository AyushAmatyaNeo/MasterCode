<?php

namespace Setup\Controller;

use Application\Helper\Helper;
use LeaveManagement\Model\LeaveAssign;
use LeaveManagement\Repository\LeaveAssignRepository;
use Setup\Helper\EntityHelper;
use Zend\Db\Adapter\AdapterInterface;
use Zend\EventManager\EventManagerInterface;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\JsonModel;
use Application\Helper\EntityHelper as ApplicationEntityHelper;
use HolidayManagement\Repository\HolidayRepository;
use HolidayManagement\Model\Holiday;
use HolidayManagement\Model\HolidayBranch;

class WebServiceController extends AbstractActionController
{
    private $adapter;

    public function __construct(AdapterInterface $adapter)
    {
        $this->adapter = $adapter;
    }

    public function indexAction()
    {
        $request = $this->getRequest();
        $responseData = [];
        if ($request->isPost()) {
            $postedData = $request->getPost();
            switch ($postedData->action) {
                case "assignedLeaves":
                    $leaveAssignRepo = new LeaveAssignRepository($this->adapter);
                    $result = $leaveAssignRepo->fetchByEmployeeId($postedData->id);
                    $tempArray = [];
                    foreach ($result as $item) {
                        array_push($tempArray, $item);
                    }
                    $responseData = [
                        "success" => true,
                        "data" => $tempArray
                    ];
                    break;
                case "assignList":
                    $assignRepo = new LeaveAssignRepository($this->adapter);
                    $assignList = $assignRepo->fetchByEmployeeId($postedData->id);
                    $tempArray = [];
                    foreach ($assignList as $item) {
                        array_push($tempArray, $item);
                    }
                    $responseData = [
                        "success" => true,
                        "data" => $tempArray
                    ];
                    break;

                case "pullEmployeeLeave":
                    $leaveAssign = new LeaveAssignRepository($this->adapter);
                    $ids = $postedData->id;
                    $temp = $leaveAssign->filter($ids['branchId'], $ids['departmentId'], $ids['genderId'], $ids['designationId']);

                    $tempArray = [];
                    foreach ($temp as $item) {
                        $tmp = $leaveAssign->filterByLeaveEmployeeId($ids['leaveId'], $item['EMPLOYEE_ID']);
                        if($tmp!=null){
                            $item["BALANCE"]=$tmp->BALANCE;
                            $item["LEAVE_ID"]=$tmp->LEAVE_ID;
                        }else{
                            $item["BALANCE"]="";
                            $item["LEAVE_ID"]="";
                        }
                        array_push($tempArray, $item);
                    }
                    $responseData = [
                        "success" => true,
                        "data" => $tempArray
                    ];
                    break;

                case "pushEmployeeLeave":
                    $data = $postedData->data;
                    $leaveAssign = new LeaveAssign();
                    $leaveAssign->totalDays = $data['balance'];
                    $leaveAssign->balance = $data['balance'];
                    $leaveAssign->employeeId = $data['employeeId'];
                    $leaveAssign->leaveId = $data['leave'];

                    $leaveAssignRepo = new LeaveAssignRepository($this->adapter);
                    if (empty($data['leaveId'])) {
                        $leaveAssign->createdDt = Helper::getcurrentExpressionDate();
                        $leaveAssignRepo->add($leaveAssign);
                    } else {
                        $leaveAssign->modifiedDt = Helper::getcurrentExpressionDate();
                        unset($leaveAssign->employeeId);
                        unset($leaveAssign->leaveId);
                        $leaveAssignRepo->edit($leaveAssign, [$data['leaveId'], $data['employeeId']]);
                    }

                    $responseData = [
                        "success" => true,
                        "data" => $postedData
                    ];
                    break;
                case "pullHolidayList":
                    $holidayRepository = new HolidayRepository($this->adapter);
                    $filtersId = $postedData->id;
                    $resultSet = $holidayRepository->filterRecords($filtersId['holidayId'],$filtersId['branchId'],$filtersId['genderId']);

                    $tempArray = [];
                    foreach ($resultSet as $item) {
                        array_push($tempArray, $item);
                    }
                    $responseData = [
                        "success" => true,
                        "data" => $tempArray
                    ];
                    break;
                case "pullHolidayDetail":
                    $holidayRepository = new HolidayRepository($this->adapter);
                    $filtersId = $postedData->id;
                    $resultSet = $holidayRepository->fetchById($filtersId);

                    $responseData = [
                        "success" => true,
                        "data" => $resultSet
                    ];
                    break;
                case "updateHolidayDetail":
                    $holidayModel = new Holiday();
                    $holidayBranchModel = new HolidayBranch();
                    $holidayRepository = new HolidayRepository($this->adapter);
                    $filtersId = $postedData->data;
                    $branchIds = $filtersId['branchIds'];
                    $data = $filtersId['dataArray'];
                    $holidayModel->holidayCode=$data['holidayCode'];
                    if($data['genderId']=='-1'){
                        $holidayModel->genderId = "";
                    }else {
                        $holidayModel->genderId = $data['genderId'];
                    }
                    $holidayModel->holidayEname=$data['holidayEname'];
                    $holidayModel->holidayLname=$data['holidayLname'];
                    $holidayModel->startDate=$data['startDate'];
                    $holidayModel->endDate=$data['endDate'];
                    $holidayModel->halfday=$data['halfday'];
                    $holidayModel->remarks=$data['remarks'];
                    $holidayModel->modifiedDt = Helper::getcurrentExpressionDate();
                    $resultSet = $holidayRepository->edit($holidayModel,$filtersId['holidayId']);

                    $holidayBranchResult = $holidayRepository->selectHolidayBranch($filtersId['holidayId']);

                    // delete database record if database record doesn't exist on submitted value
                    $branchTemp = [];
                    foreach ($holidayBranchResult as $holidayBranchList){
                        $branchId = $holidayBranchList['BRANCH_ID'];
                        if(!in_array($branchId,$branchIds)){
                            $holidayRepository->deleteHolidayBranch($filtersId['holidayId'],$branchId);
                        }
                        array_push($branchTemp,$branchId);
                    }

                    // insert database record if submitted value doesn't exist on database
                    foreach($branchIds as $branchIdList){
                        if(!in_array($branchIdList,$branchTemp)){
                            $holidayBranchModel->branchId=$branchIdList;
                            $holidayBranchModel->holidayId=$filtersId['holidayId'];
                            $holidayRepository->addHolidayBranch($holidayBranchModel);
                        }
                    }

                    $responseData = [
                        "data1"=>$holidayModel,
                        "success" => true,
                        "data"=>"Holiday Successfully Updated!!"
                    ];
                    break;
                default:
                    $responseData = [
                        "success" => false
                    ];
                    break;
            }
        } else {
            $responseData = [
                "success" => false
            ];
        }
        return new JsonModel(['data' => $responseData]);
    }

    public function districtAction()
    {
        $request = $this->getRequest();
        if ($request->isPost()) {
            $id = $request->getPost()->id;
            $jsonModel = new JsonModel([
                'data' => EntityHelper::getTableKVList($this->adapter, EntityHelper::HR_DISTRICTS, ["ZONE_ID" => $id])
            ]);
            return $jsonModel;
        } else {

        }
    }

    public function municipalityAction()
    {
        $request = $this->getRequest();
        if ($request->isPost()) {
            $id = $request->getPost()->id;
            return new JsonModel([
                'data' => EntityHelper::getTableKVList($this->adapter, EntityHelper::HR_VDC_MUNICIPALITY, ["DISTRICT_ID" => $id])
            ]);
        } else {

        }
    }

    public function branchListAction(){
        $request = $this->getRequest();
        if($request->isPost()){
            $id = $request->getPost()->id;
            return new JsonModel([
                'data'=>ApplicationEntityHelper::getColumnsList($this->adapter,$id,"BRANCH_ID", ["BRANCH_NAME"])
            ]);
        }
    }
}