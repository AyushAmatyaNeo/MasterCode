<?php

namespace System\Controller;

use Application\Controller\HrisController;
use Application\Helper\EntityHelper;
use Application\Helper\Helper;
use Exception;
use Setup\Model\HrEmployees;
use LeaveManagement\Model\LeaveMaster;
use System\Repository\SystemUtilityRepository;
use Zend\Authentication\Storage\StorageInterface;
use Zend\Db\Adapter\AdapterInterface;
use Zend\Db\Sql\Expression;
use Zend\View\Model\JsonModel;

class SystemUtility extends HrisController {

    protected $adapter;

    public function __construct(AdapterInterface $adapter, StorageInterface $storage) {
        $this->adapter = $adapter;
        parent::__construct($adapter, $storage);
        $this->initializeRepository(SystemUtilityRepository::class);
    }

    public function reAttendanceAction() {
        return $this->stickFlashMessagesTo([
                    'employeeList' => EntityHelper::getTableList($this->adapter, HrEmployees::TABLE_NAME, [new Expression(HrEmployees::EMPLOYEE_ID." AS ".HrEmployees::EMPLOYEE_ID),new Expression("EMPLOYEE_CODE||'-'||FULL_NAME AS FULL_NAME")], [HrEmployees::STATUS => "E", HrEmployees::RETIRED_FLAG => "N"]),
                    'searchValues' => EntityHelper::getSearchData($this->adapter)
        ]);
    }

    public function databaseBackupAction() {
        $request = $this->getRequest();
        if ($request->isPost()) {
            try {

                $databaseBackup = EntityHelper::rawQueryResult($this->adapter, "SELECT * FROM HRIS_DATABASE_BACKUP")->current();
                if ($databaseBackup) {
                    $userName = $databaseBackup['USER_NAME'];
                    $password = $databaseBackup['PASSWORD'];
                    $connectionString = $databaseBackup['CONNECTION_STRING'];
                    $oralceUser = $databaseBackup['ORACLE_USER'];
                    $directory = $databaseBackup['DIRECTORY_NAME'];

                    date_default_timezone_set('Asia/Kathmandu');
                    $todayDate = date("Ymd_g_i");

                    $query = "EXPDP " . $userName . "/" . $password . "@" . $connectionString . " DUMPFILE = " . $oralceUser . "_" . $todayDate . ".dmp SCHEMAS = " . $oralceUser . " DIRECTORY = " . $directory . " LOGFILE= " . $oralceUser . "_" . $todayDate . ".log VERSION = 10.2.0";
                    $this->execInBackground($query);
                } else {
                    throw new Exception('Backup Value not set in Database');
                }

                return new JsonModel(['success' => true, 'data' => null, 'message' => "dataBackup Sucessfull"]);
            } catch (Exception $e) {
                return new JsonModel(['success' => false, 'data' => null, 'message' => $e->getMessage()]);
            }
        }
    }

    function execInBackground($cmd) {
        if (substr(php_uname(), 0, 7) == "Windows") {
            pclose(popen("start /B " . $cmd, "r"));
        } else {
            exec($cmd . " > /dev/null &");
        }
    }

    public function pullEmployeeFilterAction() {
        try {
            $request = $this->getRequest();
            $postData = (array) $request->getPost();

            $companyId = $postData['companyId'];
            $branchId = $postData['branchId'];
            $departmentId = $postData['departmentId'];
            $designationId = $postData['designationId'];
            $positionId = $postData['positonId'];
            $employeeType = $postData['employeeType'];
            $serviceTypeId = $postData['serviceTypeId'];
            $genderId = $postData['genderId'];
            $serviceEventTypeId = $postData['serviceEventTypeId'];

            $repository = new SystemUtilityRepository($this->adapter);
            $employeeResult = $repository->filterRecords($branchId, $departmentId, $designationId, $positionId, $employeeType, $serviceTypeId, $companyId, $genderId, $serviceEventTypeId);

            return new JsonModel(['success' => true, 'data' => $employeeResult, 'message' => "success"]);
        } catch (Exception $e) {
            return new JsonModel(['success' => false, 'data' => null, 'message' => $e->getMessage()]);
        }
    }

    public function updateSeniorityAction() {
        $request = $this->getRequest();
        if ($request->isPost()) {
            $sql = "UPDATE HRIS_EMPLOYEES E  SET E.SENIORITY_LEVEL=(select  OE.SENIORITY_LEVEL 
                 from HR_EMPLOYEE_SETUP OE 
                    WHERE OE.EMPLOYEE_CODE=TO_CHAR(E.EMPLOYEE_ID)
                    )";
            $rawResult = EntityHelper::rawQueryResult($this->adapter, $sql);
            $this->flashmessenger()->addMessage("Updated Sucessfully");
            return $this->redirect()->toRoute("system-utility", ['action' => 'updateSeniority']);
        }
        return Helper::addFlashMessagesToArray($this, []);
    }

    public function queryAction() {
        $request = $this->getRequest();

        if ($request->isPost()) {
            echo 'Not available due to security reasons';
            die();
            try {
                $data = $_POST['query'];
                $queryResult = $this->repository->runQuery($data);
                return new JsonModel(['success' => true, 'data' => $queryResult, 'message' => 'Query Fired Successfully']);
            } catch (Exception $e) {
                return new JsonModel(['success' => false, 'data' => null, 'message' => $e->getMessage()]);
            }
        }

        return $this->stickFlashMessagesTo([
                    'acl' => $this->acl,
                    'employeeDetail' => $this->storageData['employee_detail'],
        ]);
    }

    public function reCalculateAction(){
        // print_r('dhbf');die;
        return $this->stickFlashMessagesTo([
            'employeeList' =>EntityHelper::getTableList($this->adapter,HrEmployees::TABLE_NAME,[HrEmployees::EMPLOYEE_ID,HrEmployees::FULL_NAME],[HrEmployees::STATUS=>'E',HrEmployees::RETIRED_FLAG=>"N"]),
            'leaveList'=>EntityHelper::getTableList($this->adapter,LeaveMaster::TABLE_NAME,[LeaveMaster::LEAVE_ID,LeaveMaster::LEAVE_ENAME],[LeaveMaster::STATUS=>'E'])
        ]);
    }


    public function reCalculateLeaveAction(){
        $request =$this->getRequest();

        if($request->isPost()){
            try{
                $data =$request->getPost();

                $checkFlag=$this->repository->checkMonthlyFlag($data['LEAVE_ID']); 

                if ($checkFlag['IS_MONTHLY']=='N'){
                    $this->repository->recalYearlyLeave($data['EMPLOYEE_ID'],$data['LEAVE_ID']);
                    return $this->redirect()->toRoute("");
                }else{
                    $this->repository->recalMonthlyLeave($data['EMPLOYEE_ID'],$data['LEAVE_ID']);
                    return $this->redirect()->toRoute("");
                }
     
            } catch (Exception $e){
                return new JsonModel(['success'=>false, 'data' =>null,'message' =>$e->getMessage()]);
            }
        }
    }

}
