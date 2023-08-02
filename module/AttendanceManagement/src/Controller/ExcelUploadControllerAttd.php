<?php

namespace AttendanceManagement\Controller;

use Application\Controller\HrisController;
use Application\Helper\EntityHelper;
use Application\Model\FiscalYear;
use Application\Model\Months;
use Zend\Db\Adapter\AdapterInterface;
use Zend\Authentication\Storage\StorageInterface;
use Zend\View\Model\JsonModel;
use AttendanceManagement\Repository\ExcelUploadAttdRepository;

class ExcelUploadControllerAttd extends HrisController {

    public $adapter;

    public function __construct(AdapterInterface $adapter, StorageInterface $storage) {
        parent::__construct($adapter, $storage);
        $this->adapter = $adapter;
        $this->initializeRepository(ExcelUploadAttdRepository::class);
    }

    public function indexAction() {
        return $this->stickFlashMessagesTo([
                    'searchValues' => EntityHelper::getSearchData($this->adapter),
                    'acl' => $this->acl,
        ]);
    }

    public function insertAttendanceAction(){
        $excelData = $_POST['data'];
        $basedOn = $_POST['basedOn'];
        if($basedOn==3){
            foreach($excelData as $data){
                $count = EntityHelper::rawQueryResult($this->adapter, "select count(*) as TOT from hris_attendance where thumb_id = {$data['B']} and attendance_time = to_timestamp('{$data['E']}"." {$data['F']}"."', 'YYYY-MM_DD HH24:MI')")->current();
                if($count['TOT']==0){
                    $this->repository->insertRawAttendance($data['B'],$data['E'],$data['F']);
                }
            }
        }else{
            foreach ($excelData as $data) {
                if($basedOn == 2){ $data['A'] = EntityHelper::getEmployeeIdFromCode($this->adapter, $data['A']); }
                if($data['A'] == null || $data['A'] == ''){ continue; }
                $item['employeeId'] = $data['A'];
                $item['attendanceDt'] = $data['C'];
                if($data['D']){
                    $item['attendanceTime'] = $data['C'].' '.$data['D'];
                    $this->repository->insertAttendance($item);
                }
                if($data['E']){
                    $item['attendanceTime'] = $data['C'].' '.$data['E'];
                    $this->repository->insertAttendance($item);
                }
            }
        }
        return new JsonModel(['success' => true, 'error' => '']);
    }
}