<?php

namespace AttendanceManagement\Repository;

use Application\Helper\EntityHelper;
use Application\Model\Model;
use Application\Repository\RepositoryInterface;
use AttendanceManagement\Model\Attendance;
use Zend\Db\Adapter\AdapterInterface;
use Zend\Db\Sql\Select;
use Zend\Db\TableGateway\TableGateway;
use Setup\Repository\BranchRepository;
use Setup\Model\Logs;

class AttendanceRepository implements RepositoryInterface
{

  private $tableGateway;
  private $adapter;
  private $logTable;

  public function __construct(AdapterInterface $adapter)
  {
    $this->tableGateway = new TableGateway(Attendance::TABLE_NAME, $adapter);
    $this->logTable = new TableGateway(Logs::TABLE_NAME, $adapter);
    $this->adapter = $adapter;
  }

  public function add(Model $model)
  {
    $this->tableGateway->insert($model->getArrayCopyForDB());
    $this->attendanceAfterInsert($model);
  }

  public function delete($id)
  {
  }

  public function edit(Model $model, $combo)
  {
    $tempArray = $model->getArrayCopyForDB();
    unset($tempArray[Attendance::EMPLOYEE_ID]);
    unset($tempArray[Attendance::ATTENDANCE_DT]);
    $this->tableGateway->update($tempArray, [
      Attendance::EMPLOYEE_ID => $combo['employeeId'],
      Attendance::ATTENDANCE_DT => $combo['attendanceDt']
    ]);
  }

  public function fetchAll()
  {
  }

  public function fetchById($combo)
  {
    $result = $this->tableGateway->select(
      [
        Attendance::EMPLOYEE_ID => $combo['employeeId'],
        Attendance::ATTENDANCE_DT => $combo['attendanceDt']
      ]
    );
    return $result->current();
  }

  public function fetchAllByEmpIdAttendanceDt($employeeId, $attendanceDt)
  {
    $result = $this->tableGateway->select(function (Select $select) use ($employeeId, $attendanceDt) {
      $select->columns(EntityHelper::getColumnNameArrayWithOracleFns(Attendance::class, null, [Attendance::ATTENDANCE_DT], [Attendance::ATTENDANCE_TIME]), false);
      $select->where([
        Attendance::EMPLOYEE_ID => $employeeId,
        Attendance::ATTENDANCE_DT . " = TO_DATE('" . $attendanceDt . "','DD-MON-YYYY')"
      ]);
      $select->order(Attendance::ATTENDANCE_TIME . " ASC");
    });
    return $result;
  }

  public function getTotalByEmpIdAttendanceDt($employeeId, $attendanceDt)
  {
    $sql = " SELECT ROUND(TOTAL_MINS/60,0)
                  ||':'
                  ||MOD(TOTAL_MINS,60) TOTAL_HRS,
                  TOTAL_MINS,
                  HR_TYPE
                FROM
                  (SELECT
                    CASE MOD(RNUM,2)
                      WHEN 0
                      THEN 'WORKING'
                      ELSE 'NON-WORKING'
                    END AS HR_TYPE,
                    SUM(ABS(EXTRACT( HOUR FROM DIFF ))*60 + ABS(EXTRACT( MINUTE FROM DIFF ))) TOTAL_MINS
                  FROM
                    (SELECT ROW_NUMBER() OVER ( ORDER BY A.ATTENDANCE_TIME )    AS RNUM,
                      MOD((ROW_NUMBER() OVER ( ORDER BY A.ATTENDANCE_TIME )),2) AS NUM,
                      A.EMPLOYEE_ID,
                      A.IP_ADDRESS,
                      A.ATTENDANCE_DT,
                      A.ATTENDANCE_TIME,
                      (A.ATTENDANCE_TIME - LAG(A.ATTENDANCE_TIME) OVER (ORDER BY A.ATTENDANCE_TIME)) AS DIFF
                    FROM HRIS_ATTENDANCE A
                    WHERE A.EMPLOYEE_ID = " . $employeeId . "
                    AND A.ATTENDANCE_DT = TO_DATE('" . $attendanceDt . "','DD-MON-YYYY')
                    )
                  GROUP BY MOD(RNUM,2)
                  )";
    $statement = $this->adapter->query($sql);
    $result = $statement->execute();
    foreach ($result as $row) {
      $list[$row['HR_TYPE']] = $row;
    }
    return $list;
  }

  public function fetchInOutTimeList($employeeId, $attendanceDt)
  {
    $sql = "SELECT IN_TIME_QUERY.IN_TIME,
                  OUT_TIME_QUERY.OUT_TIME  FROM
                  (SELECT INITCAP(TO_CHAR(ATTENDANCE_TIME,'HH:MI AM')) AS OUT_TIME,
                    ATTENDANCE_DT,
                    EMPLOYEE_ID,
                    OUT_REMARKS,
                    ROW_NUMBER() OVER ( ORDER BY ATTENDANCE_TIME ) AS RNUM1
                  FROM
                    (SELECT A.*,
                      ROW_NUMBER() OVER ( ORDER BY A.ATTENDANCE_TIME ) AS RNUM,
                      AD.OUT_REMARKS
                    FROM HRIS_ATTENDANCE A
                    LEFT JOIN HRIS_ATTENDANCE_DETAIL AD
                    ON A.ATTENDANCE_DT =AD.ATTENDANCE_DT
                    AND A.EMPLOYEE_ID  =AD.EMPLOYEE_ID
                    WHERE A.EMPLOYEE_ID=" . $employeeId . "
                    AND A.ATTENDANCE_DT=TO_DATE('" . $attendanceDt . "','DD-MON-YYYY')
                    ORDER BY A.ATTENDANCE_TIME
                    )
                  WHERE mod(RNUM,2)=0
                  ) OUT_TIME_QUERY
                  FULL OUTER JOIN
                  (SELECT INITCAP(TO_CHAR(ATTENDANCE_TIME,'HH:MI AM')) AS IN_TIME ,
                    ATTENDANCE_DT,
                    EMPLOYEE_ID ,
                    ROW_NUMBER() OVER ( ORDER BY ATTENDANCE_TIME ) AS RNUM1
                  FROM
                    (SELECT A.*,
                      ROW_NUMBER() OVER ( ORDER BY A.ATTENDANCE_TIME ) AS RNUM
                    FROM HRIS_ATTENDANCE A
                     WHERE A.EMPLOYEE_ID=" . $employeeId . "
                    AND A.ATTENDANCE_DT=TO_DATE('" . $attendanceDt . "','DD-MON-YYYY')
                    ORDER BY A.ATTENDANCE_TIME
                    )
                  WHERE mod(RNUM,2)=1
                  )IN_TIME_QUERY
                ON
                IN_TIME_QUERY.RNUM1 = OUT_TIME_QUERY.RNUM1
";
    $statement = $this->adapter->query($sql);
    return $statement->execute();
  }

  public function fetchEmployeeShfitDetails($employeeId)
  {
    $sql = "SELECT TO_CHAR(SYSDATE, 'HH24:MI:SS') AS CURRENT_TIME,
            TO_CHAR(S.END_TIME, 'HH24:MI:SS') AS CHECKOUT_TIME, 
            ESA.*,S.* FROM HRIS_EMPLOYEES E
                join HRIS_EMPLOYEE_SHIFT_ASSIGN ESA on (ESA.EMPLOYEE_ID=E.EMPLOYEE_ID)
                JOIN HRIS_SHIFTS S ON (S.SHIFT_ID=ESA.SHIFT_ID)
                WHERE E.EMPLOYEE_ID=$employeeId AND ESA.STATUS='E' AND ESA.MODIFIED_DT IS NULL
                AND (TO_DATE(TRUNC(SYSDATE), 'DD-MON-YY') BETWEEN TO_DATE(S.START_DATE, 'DD-MON-YY') AND TO_DATE(S.END_DATE, 'DD-MON-YY'))";

    $statement = $this->adapter->query($sql);
    $result = $statement->execute();
    return $result->current();
  }

  private function attendanceAfterInsert($attendance)
  {
    $sql = "BEGIN HRIS_ATTENDANCE_AFTER_INSERT({$attendance->employeeId},{$attendance->attendanceDt->getExpression()},{$attendance->attendanceTime->getExpression()},'{{$attendance->remarks}}'); END;";
    $statement = $this->adapter->query($sql);
    $statement->execute();
  }

  function insertAttendance($data)
  {

    $inTime = $data['inTime'] == null ? 'null' : "TO_DATE('{$data['attendanceDt']} {$data['inTime']}', 'DD-MON-YYYY HH:MI AM')";
    if ($data['nextDay']) {
      $outNextDay = 'Y';
      $outTime = $data['outTime'] == null ? 'null' : "(TO_DATE('{$data['attendanceDt']} {$data['outTime']}', 'DD-MON-YYYY HH:MI AM'))+1";
    } else {
      $outNextDay = 'N';
      $outTime = $data['outTime'] == null ? 'null' : "TO_DATE('{$data['attendanceDt']} {$data['outTime']}', 'DD-MON-YYYY HH:MI AM')";
    }
    if ($data->totalHour == null) {
      $data->totalHour = 'NULL';
    }
    $sql = "
      DECLARE 
      V_REQ_ID NUMBER := {$data['requestId']};
      V_IN_TIME TIMESTAMP;
      V_OUT_TIME TIMESTAMP;
      V_EMPLOYEE_ID HRIS_ATTENDANCE.EMPLOYEE_ID%TYPE;
      V_ATTENDANCE_DT HRIS_ATTENDANCE.ATTENDANCE_DT%TYPE;
      V_IN_REMARKS VARCHAR2(200);
      V_OUT_REMARKS VARCHAR2(200);
      V_OUT_NEXT_DAY CHAR(1):='{$outNextDay}';
      BEGIN
      INSERT INTO HRIS_ATTENDANCE_REQUEST (ID, EMPLOYEE_ID, ATTENDANCE_DT, IN_TIME, OUT_TIME, 
      IN_REMARKS, OUT_REMARKS, TOTAL_HOUR, STATUS, APPROVED_BY, APPROVED_DT, REQUESTED_DT, APPROVED_REMARKS,NEXT_DAY_OUT,CREATED_BY)
      VALUES ({$data['requestId']}, {$data['employeeId']}, TO_DATE('{$data['attendanceDt']}', 'DD-MON-YYYY'), $inTime,
      $outTime,
      '{$data['inRemarks']}', '{$data['outRemarks']}', {$data['totalHour']}, '{$data['status']}', {$data['approvedBy']},
      trunc(sysdate), trunc(sysdate), '{$data['approvedRemarks']}',V_OUT_NEXT_DAY,{$data['employeeId']});

      SELECT IN_TIME, OUT_TIME, EMPLOYEE_ID, ATTENDANCE_DT, IN_REMARKS, OUT_REMARKS INTO 
      V_IN_TIME, V_OUT_TIME, V_EMPLOYEE_ID, V_ATTENDANCE_DT, V_IN_REMARKS, V_OUT_REMARKS
      FROM HRIS_ATTENDANCE_REQUEST WHERE ID = V_REQ_ID;

      IF V_IN_TIME IS NOT NULL THEN
      INSERT INTO HRIS_ATTENDANCE (EMPLOYEE_ID, ATTENDANCE_DT, IP_ADDRESS, ATTENDANCE_FROM, 
      ATTENDANCE_TIME, REMARKS, THUMB_ID, CHECKED) 
      VALUES (V_EMPLOYEE_ID, V_ATTENDANCE_DT, 'IN', 'SYSTEM', V_IN_TIME, 
      V_IN_REMARKS, (SELECT ID_THUMB_ID FROM HRIS_EMPLOYEES WHERE EMPLOYEE_ID = V_EMPLOYEE_ID),
      'Y');
      END IF;

      IF V_OUT_TIME IS NOT NULL THEN
      INSERT INTO HRIS_ATTENDANCE (EMPLOYEE_ID, ATTENDANCE_DT, IP_ADDRESS, ATTENDANCE_FROM, 
      ATTENDANCE_TIME, REMARKS, THUMB_ID, CHECKED) 
      VALUES (V_EMPLOYEE_ID, case when V_OUT_NEXT_DAY='Y' then  V_ATTENDANCE_DT +1 else V_ATTENDANCE_DT END, 'OUT', 'SYSTEM', V_OUT_TIME, 
      V_OUT_REMARKS, (SELECT ID_THUMB_ID FROM HRIS_EMPLOYEES WHERE EMPLOYEE_ID = V_EMPLOYEE_ID),
      'Y');
      END IF;

      HRIS_REATTENDANCE(V_ATTENDANCE_DT, V_EMPLOYEE_ID, V_ATTENDANCE_DT);      
      END;";
    $statement = $this->adapter->query($sql);
    $statement->execute();
    $branch = new BranchRepository($this->adapter);
    $logs = new Logs();
    $logs->module = 'Attendance request';
    $logs->operation = 'I';
    $logs->createdBy = $data['createdBy'];
    $logs->createdDesc = 'Employee id - ' . $data['employeeId'];
    $logs->tableDesc = 'HRIS_ATTENDANCE_REQUEST';
    $branch->insertLogs($logs);
  }
}
