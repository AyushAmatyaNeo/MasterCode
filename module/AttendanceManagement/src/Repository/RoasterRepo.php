<?php

namespace AttendanceManagement\Repository;

use Application\Helper\EntityHelper;
use Application\Helper\Helper;
use Application\Model\Model;
use Application\Repository\HrisRepository;
use Application\Repository\RepositoryInterface;
use AttendanceManagement\Model\RoasterModel;
use Zend\Db\Adapter\AdapterInterface;
use Zend\Db\TableGateway\TableGateway;

class RoasterRepo extends HrisRepository implements RepositoryInterface
{

  //    private $adapter;
  private $gateway;

  public function __construct(AdapterInterface $adapter)
  {
    parent::__construct($adapter);
    //        $this->adapter = $adapter;
    $this->gateway = new TableGateway(RoasterModel::TABLE_NAME, $adapter);
  }

  public function add(Model $model)
  {
    $this->gateway->insert($model->getArrayCopyForDB());
  }

  public function delete($id)
  {
  }

  public function edit(Model $model, $id)
  {
  }

  public function fetchAll()
  {
    $raw = EntityHelper::rawQueryResult($this->adapter, "
                SELECT EMPLOYEE_ID,
                  SHIFT_ID,
                  TO_CHAR(FOR_DATE,'DD-MON-YYYY') AS FOR_DATE,
                  TO_CHAR(FOR_DATE,'YYYY-MM-DD')  AS FOR_DATE_FORMATTED,
                  trim(LEADING '0' FROM TO_CHAR(FOR_DATE, 'DD'))||TO_CHAR(FOR_DATE, '-MON-YYYY') AS FOR_CHECK
                FROM HRIS_EMPLOYEE_SHIFT_ROASTER");
    return Helper::extractDbData($raw);
  }

  public function fetchById($id)
  {
  }

  public function merge($employeeId, $forDate, $shiftId, $createdBy)
  {
    EntityHelper::rawQueryResult($this->adapter, "
                DECLARE
                  V_EMPLOYEE_ID  NUMBER :={$employeeId};
                  V_FOR_DATE     DATE   :=TO_DATE('{$forDate}','DD-MON-YYYY');
                  V_SHIFT_ID_NEW NUMBER :={$shiftId};
                  V_CREATED_BY  NUMBER := {$createdBy};
                  V_SHIFT_ID_OLD NUMBER;
                BEGIN
                BEGIN
                  SELECT SHIFT_ID
                  INTO V_SHIFT_ID_OLD
                  FROM HRIS_EMPLOYEE_SHIFT_ROASTER
                  WHERE EMPLOYEE_ID = V_EMPLOYEE_ID
                  AND FOR_DATE      =V_FOR_DATE;
                   EXCEPTION
                WHEN NO_DATA_FOUND THEN
                IF(V_SHIFT_ID_NEW!=-1) THEN
                  INSERT
                  INTO HRIS_EMPLOYEE_SHIFT_ROASTER
                    (
                      EMPLOYEE_ID,
                      FOR_DATE,
                      SHIFT_ID,
                      CREATED_BY,
                      CREATED_DT
                    )
                    VALUES
                    (
                      V_EMPLOYEE_ID,
                      V_FOR_DATE,
                      V_SHIFT_ID_NEW,
                      V_CREATED_BY,
                      TRUNC(SYSDATE)
                    );
                    END IF;
                    END;
                  IF(V_SHIFT_ID_NEW=-1)
                THEN
                DELETE FROM HRIS_EMPLOYEE_SHIFT_ROASTER WHERE EMPLOYEE_ID =V_EMPLOYEE_ID AND FOR_DATE=V_FOR_DATE;
                ELSE
                  UPDATE HRIS_EMPLOYEE_SHIFT_ROASTER
                  SET SHIFT_ID     = V_SHIFT_ID_NEW,MODIFIED_BY=V_CREATED_BY,MODIFED_DT=TRUNC(SYSDATE)
                  WHERE EMPLOYEE_ID=V_EMPLOYEE_ID
                  AND FOR_DATE     =V_FOR_DATE;
                END IF;
                    IF( V_FOR_DATE<=TRUNC(SYSDATE)  AND ((V_SHIFT_ID_OLD!=V_SHIFT_ID_NEW)
                    OR (V_SHIFT_ID_OLD IS NULL AND V_SHIFT_ID_NEW!=-1 ))  )
                    THEN
                    Hris_Reattendance(V_FOR_DATE,V_EMPLOYEE_ID,V_FOR_DATE);
                    END IF;
                    COMMIT;
                END;");
  }

  public function getshiftDetail($data)
  {
    $shiftId = $data['shiftId'];
    $fromDate = $data['fromDate'];
    $toDate = $data['toDate'];
    $selectedDate = $data['selectedDate'];



    $sql = "SELECT
TO_CHAR(DATES,'DD')||TO_CHAR(DATES,'-MON-YYYY') AS DATES,
WEEK_NO,
DAY_OFF,
DAY,
{$shiftId} AS SHIFT_ID
FROM
(SELECT * FROM 
       (select rownum - 1 + to_date('{$selectedDate}', 'dd-mon-yyyy') AS dates,
        TO_CHAR(rownum - 1 + to_date('{$selectedDate}', 'dd-mon-yyyy'),'D') AS WEEK_NO
        from all_objects 
        where rownum < to_date('{$toDate}', 'dd-mon-yyyy') -
       to_date('{$selectedDate}', 'dd-mon-yyyy') + 2) DATE_LIST) DL
       JOIN (select 
       TO_CHAR(to_date('{$selectedDate}'),'D') DAY,
       CASE 
       WHEN TO_CHAR(to_date('{$selectedDate}'),'D')=1
       THEN
       WEEKDAY1
       WHEN TO_CHAR(to_date('{$selectedDate}'),'D')=2
       THEN
       WEEKDAY2
       WHEN TO_CHAR(to_date('{$selectedDate}'),'D')=3
       THEN
       WEEKDAY3
       WHEN TO_CHAR(to_date('{$selectedDate}'),'D')=4
       THEN
       WEEKDAY4
       WHEN TO_CHAR(to_date('{$selectedDate}'),'D')=5
       THEN
       WEEKDAY5
       WHEN TO_CHAR(to_date('{$selectedDate}'),'D')=6
       THEN
       WEEKDAY6
       WHEN TO_CHAR(to_date('{$selectedDate}'),'D')=7
       THEN
       WEEKDAY7
       END AS DAY_OFF
       from hris_shifts where shift_id={$shiftId}) SD  ON (1=1)";

    $raw = EntityHelper::rawQueryResult($this->adapter, $sql);
    return Helper::extractDbData($raw);
  }


  public function getRosterDetailList($data)
  {
    $employeeId = $data['employeeId'];
    $companyId = $data['companyId'];
    $branchId = $data['branchId'];
    $departmentId = $data['departmentId'];
    $designationId = $data['designationId'];
    $positionId = $data['positionId'];
    $serviceTypeId = $data['serviceTypeId'];
    $serviceEventTypeId = $data['serviceEventTypeId'];
    $employeeTypeId = $data['employeeTypeId'];
    $fromDate = $data['fromDate'];
    $toDate = $data['toDate'];


    $getAllDates = EntityHelper::rawQueryResult($this->adapter, "
                SELECT  
TO_CHAR(TO_DATE('{$fromDate}','DD-MON-YYYY') + ROWNUM -1,'DD-MON-YYYY')  AS DATES,
'F'||TO_CHAR((TO_DATE('{$fromDate}','DD-MON-YYYY') + ROWNUM -1),'YYYYMMDD') AS FORMATE_DATE
        FROM dual D
        CONNECT BY  rownum <=  TO_DATE('{$toDate}','DD-MON-YYYY') -  TO_DATE('{$fromDate}','DD-MON-YYYY') + 1
                ");



    $pivotString = '';
    $i = 0;
    foreach ($getAllDates as $list) {
      if ($i == 0) {
        $pivotString .= '\'' . $list['DATES'] . '\' AS ' . $list['FORMATE_DATE'];
      } else {
        $pivotString .= ', \'' . $list['DATES'] . '\' AS ' . $list['FORMATE_DATE'];
      }
      $i++;
    }


    $searchCondition = $this->getSearchConditon($companyId, $branchId, $departmentId, $positionId, $designationId, $serviceTypeId, $serviceEventTypeId, $employeeTypeId, $employeeId);



    $sql = "
            SELECT * FROM (select E.employee_code,E.employee_id,E.full_name,
D.*,
ER.SHIFT_ID
from hris_employees E
left JOIN 
(SELECT  
TO_CHAR(TO_DATE('{$fromDate}','DD-MON-YYYY') + ROWNUM -1,'DD-MON-YYYY')  AS DATES
        FROM dual D
        CONNECT BY  rownum <=  TO_DATE('{$toDate}','DD-MON-YYYY') -  TO_DATE('{$fromDate}','DD-MON-YYYY') + 1) D on (1=1)
        LEFT JOIN HRIS_EMPLOYEE_SHIFT_ROASTER ER ON (D.DATES=ER.FOR_DATE AND E.EMPLOYEE_ID=ER.EMPLOYEE_ID)
         WHERE 1=1 AND E.STATUS='E' {$searchCondition}
)
PIVOT(
 MAX (DATES) AS D ,
  MAX (SHIFT_ID) AS S
 FOR DATES IN ({$pivotString})) ORDER BY FULL_NAME
                ";

    $statement = $this->adapter->query($sql);
    $result = $statement->execute();
    return Helper::extractDbData($result);
  }

  public function getWeeklyRosterDetailList($data)
  {
    $employeeId = $data['employeeId'];
    $companyId = $data['companyId'];
    $branchId = $data['branchId'];
    $departmentId = $data['departmentId'];
    $designationId = $data['designationId'];
    $positionId = $data['positionId'];
    $serviceTypeId = $data['serviceTypeId'];
    $serviceEventTypeId = $data['serviceEventTypeId'];
    $employeeTypeId = $data['employeeTypeId'];

    $searchCondition = $this->getSearchConditon($companyId, $branchId, $departmentId, $positionId, $designationId, $serviceTypeId, $serviceEventTypeId, $employeeTypeId, $employeeId);

    $sql = "
            SELECT *
FROM
  (SELECT E.employee_code,
    E.employee_id,
    E.full_name,
    case when ER.SUN is null then 0 else ER.SUN end as SUN,
    case when ER.MON is null then 0 else ER.MON end as MON,
    case when ER.TUE is null then 0 else ER.TUE end as TUE,
    case when ER.WED is null then 0 else ER.WED end as WED,
    case when ER.THU is null then 0 else ER.THU end as THU,
    case when ER.FRI is null then 0 else ER.FRI end as FRI,
    case when ER.SAT is null then 0 else ER.SAT end as SAT,
    case when s1.shift_ename is null then 'select shift' else s1.shift_ename end as SUN_NAME,
    case when s2.shift_ename is null then 'select shift' else s2.shift_ename end as MON_NAME,
    case when s3.shift_ename is null then 'select shift' else s3.shift_ename end as TUE_NAME,
    case when s4.shift_ename is null then 'select shift' else s4.shift_ename end as WED_NAME,
    case when s5.shift_ename is null then 'select shift' else s5.shift_ename end as THU_NAME,
    case when s6.shift_ename is null then 'select shift' else s6.shift_ename end as FRI_NAME,
    case when s7.shift_ename is null then 'select shift' else s7.shift_ename end as SAT_NAME
  
  FROM hris_employees E
  LEFT JOIN HRIS_WEEKLY_ROASTER ER
  left join hris_shifts s1 on (s1.shift_id=er.sun) 
            left join hris_shifts s2 on (s2.shift_id=er.mon) 
            left join hris_shifts s3 on (s3.shift_id=er.tue)  
            left join hris_shifts s4 on (s4.shift_id=er.wed) 
            left join hris_shifts s5 on (s5.shift_id=er.thu) 
            left join hris_shifts s6 on (s6.shift_id=er.fri)
            left join hris_shifts s7 on (s7.shift_id=er.sat)
  ON(E.EMPLOYEE_ID = ER.EMPLOYEE_ID)
  WHERE 1=1 AND E.STATUS='E' {$searchCondition}
  )
  ";

    $statement = $this->adapter->query($sql);
    $result = $statement->execute();
    //        
    $retData = [];
    foreach ($result as $data) {
      $tempArr = $data;
      $tempArr['SUNARR'] = array('SHIFT_ID' => $data['SUN'], 'SHIFT_ENAME' => $data['SUN_NAME']);
      $tempArr['MONARR'] = array('SHIFT_ID' => $data['MON'], 'SHIFT_ENAME' => $data['MON_NAME']);
      $tempArr['TUEARR'] = array('SHIFT_ID' => $data['TUE'], 'SHIFT_ENAME' => $data['TUE_NAME']);
      $tempArr['WEDARR'] = array('SHIFT_ID' => $data['WED'], 'SHIFT_ENAME' => $data['WED_NAME']);
      $tempArr['THUARR'] = array('SHIFT_ID' => $data['THU'], 'SHIFT_ENAME' => $data['THU_NAME']);
      $tempArr['FRIARR'] = array('SHIFT_ID' => $data['FRI'], 'SHIFT_ENAME' => $data['FRI_NAME']);
      $tempArr['SATARR'] = array('SHIFT_ID' => $data['SAT'], 'SHIFT_ENAME' => $data['SAT_NAME']);
      array_push($retData, $tempArr);
    }

    return $retData;
  }

  public function getWeeklyShiftDetail()
  {
    $shiftId = $data['shiftId'];
    $selectedDay = $data['selectedDay'];


    $sql = "SELECT
TO_CHAR(DATES,'DD')||TO_CHAR(DATES,'-MON-YYYY') AS DATES,
WEEK_NO,
DAY_OFF,
DAY,
{$shiftId} AS SHIFT_ID
FROM
(SELECT * FROM 
       (select rownum - 1 + to_date('{$selectedDate}', 'dd-mon-yyyy') AS dates,
        TO_CHAR(rownum - 1 + to_date('{$selectedDate}', 'dd-mon-yyyy'),'D') AS WEEK_NO
        from all_objects 
        where rownum < to_date('{$toDate}', 'dd-mon-yyyy') -
       to_date('{$selectedDate}', 'dd-mon-yyyy') + 2) DATE_LIST) DL
       JOIN (select 
       TO_CHAR(to_date('{$selectedDate}'),'D') DAY,
       CASE 
       WHEN TO_CHAR(to_date('{$selectedDate}'),'D')=1
       THEN
       WEEKDAY1
       WHEN TO_CHAR(to_date('{$selectedDate}'),'D')=2
       THEN
       WEEKDAY2
       WHEN TO_CHAR(to_date('{$selectedDate}'),'D')=3
       THEN
       WEEKDAY3
       WHEN TO_CHAR(to_date('{$selectedDate}'),'D')=4
       THEN
       WEEKDAY4
       WHEN TO_CHAR(to_date('{$selectedDate}'),'D')=5
       THEN
       WEEKDAY5
       WHEN TO_CHAR(to_date('{$selectedDate}'),'D')=6
       THEN
       WEEKDAY6
       WHEN TO_CHAR(to_date('{$selectedDate}'),'D')=7
       THEN
       WEEKDAY7
       END AS DAY_OFF
       from hris_shifts where shift_id={$shiftId}) SD  ON (1=1)";

    $raw = EntityHelper::rawQueryResult($this->adapter, $sql);
    return Helper::extractDbData($raw);
  }

  public function getFullName($employeeId)
  {
    $sql = "select full_name from hris_employees where status='E' and employee_id=$employeeId";
    $statement = $this->adapter->query($sql);
    $result = $statement->execute()->current();
    return $result;
  }
}
