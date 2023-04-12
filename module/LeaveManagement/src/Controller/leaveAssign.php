<?php
namespace LeaveManagement\Controller;

use Application\Controller\HrisController;
use Application\Helper\EntityHelper;
use Application\Helper\Helper;
use Application\Model\HrisQuery;
use Exception;
use LeaveManagement\Model\LeaveAssign as LeaveAssignModel;
use LeaveManagement\Model\LeaveMaster;
use LeaveManagement\Repository\LeaveAssignRepository;
use LeaveManagement\Repository\LeaveMasterRepository;
use Zend\Authentication\Storage\StorageInterface;
use Zend\Db\Adapter\AdapterInterface;
use Zend\Db\Sql\Select;
use Zend\View\Model\JsonModel;

class leaveAssign extends HrisController {

    public function __construct(AdapterInterface $adapter, StorageInterface $storage, LeaveAssignRepository $repository) {
        parent::__construct($adapter, $storage);
        $this->repository = $repository;
    }

    public function assignAction() {
        $leaveList = HrisQuery::singleton()
            ->setAdapter($this->adapter)
            ->setTableName(LeaveMaster::TABLE_NAME)
            ->setColumnList([LeaveMaster::LEAVE_ID, LeaveMaster::LEAVE_ENAME])
            ->setWhere([LeaveMaster::STATUS => 'E'])
            ->setOrder([LeaveMaster::VIEW_ORDER => Select::ORDER_ASCENDING,LeaveMaster::LEAVE_ENAME => Select::ORDER_ASCENDING])
            ->setKeyValue(LeaveMaster::LEAVE_ID, LeaveMaster::LEAVE_ENAME)
            ->result();
        
        $config = [
            'name' => 'leave',
            'id' => 'leaveId',
            'class' => 'form-control reset-field',
            'label' => 'Type'
        ];
        $leaveSE = $this->getSelectElement($config, $leaveList);
        
         $leaveYearData = HrisQuery::singleton()
            ->setAdapter($this->adapter)
            ->setTableName('HRIS_LEAVE_YEARS')
            ->setColumnList(['LEAVE_YEAR_ID', 'LEAVE_YEAR_NAME'])
            ->setWhere(['STATUS' => 'E'])
            ->setOrder(['LEAVE_YEAR_ID' => Select::ORDER_DESCENDING])
            ->setKeyValue('LEAVE_YEAR_ID', 'LEAVE_YEAR_NAME')
            ->result();
        
         $leaveYearConfig = [
            'name' => 'leaveYear',
            'id' => 'leaveYear',
            'class' => 'form-control reset-field',
            'label' => 'Leave Year'
        ];
         $leaveYearSE = $this->getSelectElement($leaveYearConfig, $leaveYearData);
        return [
            'leaveFormElement' => $leaveSE,
            'leaveYearFormElement' => $leaveYearSE,
            'acl' => $this->acl,
            'employeeDetail' => $this->storageData['employee_detail']
                ];
    }

    public function pullEmployeeLeaveAction() {
        try {
            $request = $this->getRequest();
            $data = $request->getPost();
            $leaveAssign = new LeaveAssignRepository($this->adapter);
            $temp = $leaveAssign->filter($data['branchId'], $data['departmentId'], $data['genderId'], $data['designationId'], $data['serviceTypeId'], $data['employeeId'], $data['companyId'], $data['positionId'], $data['employeeTypeId'], $data['leaveId']);

            $list = [];
            foreach ($temp as $item) {
                $item["BALANCE"] = (float) $item["BALANCE"];
                $item["TOTAL_DAYS"] = (float) $item["TOTAL_DAYS"];
                $item["PREVIOUS_YEAR_BAL"] = (float) $item["PREVIOUS_YEAR_BAL"];
                array_push($list, $item);
            }
            $leaveMonthDataSql="SELECT * FROM HRIS_LEAVE_MONTH_CODE 
                    WHERE LEAVE_YEAR_ID=( SELECT Max(LEAVE_YEAR_ID) from HRIS_LEAVE_YEARS ) ORDER BY LEAVE_YEAR_MONTH_NO";
            $leaveMonthData= EntityHelper::rawQueryResult($this->adapter, $leaveMonthDataSql);
			//echo count($list); die;
            return new JsonModel([
                "success" => "true",
                "data" => $list,
                "leaveMonthData"=>Helper::extractDbData($leaveMonthData)
            ]);
        } catch (Exception $e) {
            return new JsonModel(['success' => false, 'data' => null, 'message' => $e->getMessage()]);
        }
    }

    public function pushEmployeeLeaveAction() {
        try {
            $request = $this->getRequest();
            $data = $request->getPost();
            $leaveAssign = new LeaveAssignModel();
            $leaveAssign->totalDays = $data['balance'];
            $leaveAssign->balance = $data['balance'];
            $leaveAssign->employeeId = $data['employeeId'];
            $leaveAssign->leaveId = $data['leave'];
            $leaveAssign->previousYearBalance = $data['previousYearBal'];
//            $leaveAssign->fiscalYear = $data['leaveYear'];
            
                $leaveSetupRepo=new LeaveMasterRepository($this->adapter);
                $leaveDetails=$leaveSetupRepo->fetchById($leaveAssign->leaveId);
                
            $leaveAssignRepo = new LeaveAssignRepository($this->adapter);
            if (empty($data['leaveId'])) {
                $leaveAssign->createdDt = Helper::getcurrentExpressionDate();
                $leaveAssign->createdBy = $this->employeeId;
                $leaveAssign->balance = $data['balance']+$leaveAssign->previousYearBalance;
                
                
                ($leaveDetails['IS_MONTHLY']=='N')?
                $leaveAssignRepo->add($leaveAssign)
                :$leaveAssignRepo->editMonthlyLeave($leaveAssign->employeeId,$leaveDetails,$data['month'],$leaveAssign->totalDays,$leaveAssign->previousYearBalance);
            } else {
                $leaveAssign->modifiedDt = Helper::getcurrentExpressionDate();
                $leaveAssign->modifiedBy = $this->employeeId;
                unset($leaveAssign->employeeId);
                unset($leaveAssign->leaveId);
                ($leaveDetails['IS_MONTHLY']=='N')?
                $leaveAssignRepo->edit($leaveAssign, [$data['leaveId'], $data['employeeId']])
                :$leaveAssignRepo->editMonthlyLeave($data['employeeId'],$leaveDetails,$data['month'],$leaveAssign->totalDays,$leaveAssign->previousYearBalance);
                
            }

            return new JsonModel(["success" => "true", "data" => null,]);
        } catch (Exception $e) {
            return new JsonModel(['success' => false, 'data' => null, 'message' => $e->getMessage()]);
        }
    }
}
