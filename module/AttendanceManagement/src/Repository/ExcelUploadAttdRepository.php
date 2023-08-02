<?php
namespace AttendanceManagement\Repository;

use Application\Helper\EntityHelper;
use Application\Helper\Helper;
use Application\Model\Model;
use Application\Repository\RepositoryInterface;
use AttendanceManagement\Model\Attendance;
use AttendanceManagement\Model\AttendanceDetail;
use Setup\Model\HrEmployees;
use Zend\Db\Adapter\AdapterInterface;
use Zend\Db\Sql\Expression;
use Zend\Db\Sql\Sql;
use Zend\Db\TableGateway\TableGateway;

class ExcelUploadAttdRepository implements RepositoryInterface {

    private $tableGateway;
    private $adapter;

    public function __construct(AdapterInterface $adapter) {
        $this->tableGateway = new TableGateway(AttendanceDetail::TABLE_NAME, $adapter);
        $this->adapter = $adapter;
    }

    public function add(Model $model) {
        return;
    }
    public function edit(Model $model, $id) {
        return;
    }
    public function fetchAll() {
        return;
    }
    public function fetchById($id) {
        return;
    }
    public function delete($id) {
        return;
    }
    public function insertAttendance($data){
        $sql = "INSERT INTO HRIS_ATTENDANCE(EMPLOYEE_ID, ATTENDANCE_DT, ATTENDANCE_FROM, ATTENDANCE_TIME, REMARKS, THUMB_ID, CHECKED)
        VALUES ( {$data['employeeId']}, to_date('{$data['attendanceDt']}', 'YYYY-MM-DD'), 'SYSTEM', to_timestamp('{$data['attendanceTime']}', 'YYYY-MM_DD HH:MI AM'), 'EXCEL UPLOAD BY HR', (SELECT ID_THUMB_ID FROM HRIS_EMPLOYEES WHERE EMPLOYEE_ID = {$data['employeeId']}), 'N')";
        $statement = $this->adapter->query($sql);
        $statement->execute();  

        $sql = "BEGIN HRIS_REATTENDANCE (to_date('{$data['attendanceDt']}', 'YYYY-MM-DD'), {$data['employeeId']}, to_date('{$data['attendanceDt']}', 'YYYY-MM-DD'));END;";
        $statement = $this->adapter->query($sql);
        $statement->execute(); 
    }

    public function insertRawAttendance($thumbId,$attDt,$time){
        // $sql="select count(*) from hris_attendance where thumb_id = $thumbId and attendance_time = to_timestamp('$attDt"." $time"."', 'YYYY-MM_DD HH24:MI')";
        // $count = $this->rawQuery($sql);
        // print_r($count);die;
        $sql = "INSERT INTO HRIS_ATTENDANCE(ATTENDANCE_DT, ATTENDANCE_FROM, ATTENDANCE_TIME, REMARKS, THUMB_ID, CHECKED)
        VALUES ( to_date('$attDt', 'YYYY-MM-DD'), 'Excel Upload', to_timestamp('$attDt"." $time"."', 'YYYY-MM_DD HH24:MI'), 'EXCEL UPLOAD BY HR', $thumbId, 'N')";
        $statement = $this->adapter->query($sql);
        $statement->execute(); 

		$sql="begin
		for LIST IN (
		select * from  HRIS_ATTENDANCE where CHECKED='N'
		and thumb_id in ($thumbId) and attendance_dt = to_date('$attDt', 'YYYY-MM-DD')
		ORDER BY ATTENDANCE_TIME
		)
		LOOP
		BEGIN
		hris_attd_insert_exe(LIST.THUMB_ID,LIST.ATTENDANCE_DT,LIST.IP_ADDRESS,LIST.ATTENDANCE_FROM,LIST.ATTENDANCE_TIME,LIST.REMARKS);
		END;
		END LOOP;
		end;";
		//echo('<pre>');print_r($sql);die;
		$statement = $this->adapter->query($sql);
        $statement->execute();
	}
}
