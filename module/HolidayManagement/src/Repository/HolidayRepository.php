<?php

namespace HolidayManagement\Repository;

use Application\Helper\EntityHelper;
use Application\Model\Model;
use Application\Repository\RepositoryInterface;
use Exception;
use Application\Helper\Helper;
use HolidayManagement\Model\EmployeeHoliday;
use HolidayManagement\Model\Holiday;
use Zend\Authentication\AuthenticationService;
use Zend\Db\Adapter\AdapterInterface;
use Zend\Db\Sql\Expression;
use Zend\Db\Sql\Sql;
use Zend\Db\TableGateway\TableGateway;
use Zend\Db\Sql\Select;

class HolidayRepository implements RepositoryInterface
{

    private $tableGateway;
    private $adapter;

    public function __construct(AdapterInterface $adapter)
    {
        $this->tableGateway = new TableGateway(Holiday::TABLE_NAME, $adapter);
        $this->adapter = $adapter;
        $auth = new AuthenticationService();
        $this->fiscalYr = $auth->getStorage()->read()['fiscal_year']['FISCAL_YEAR_ID'];
    }

    public function add(Model $model)
    {
        $this->tableGateway->insert($model->getArrayCopyForDB());
    }

    public function edit(Model $model, $id)
    {
        $this->tableGateway->update($model->getArrayCopyForDB(), [Holiday::HOLIDAY_ID => $id]);
    }

    public function fetchAll($today = null)
    {
        $sql = "
            SELECT  
            INITCAP(TO_CHAR(A.START_DATE, 'DD-MON-YYYY')) AS START_DATE,
            INITCAP(TO_CHAR(A.END_DATE, 'DD-MON-YYYY')) AS END_DATE,
            A.HOLIDAY_ID,
            A.HOLIDAY_CODE,
            INITCAP(A.HOLIDAY_ENAME) AS HOLIDAY_ENAME,
            INITCAP(A.HOLIDAY_LNAME) AS HOLIDAY_LNAME,
            A.HALFDAY
                FROM HRIS_HOLIDAY_MASTER_SETUP A 
                WHERE A.STATUS='E' ";
        if ($today != null) {
            $sql .= " AND (" . $today->getExpression() . " between A.START_DATE AND A.END_DATE) OR " . $today->getExpression() . " <= A.START_DATE";
        }
        $sql .= " ORDER BY A.START_DATE DESC";
        $statement = $this->adapter->query($sql);
        $result = $statement->execute();
        return $result;
    }

    public function fetchById($id)
    {
        $sql = new Sql($this->adapter);
        $select = $sql->select();
        $select->columns([
            new Expression("HOLIDAY_ID AS HOLIDAY_ID"),
            new Expression("HOLIDAY_CODE AS HOLIDAY_CODE"),
            new Expression("INITCAP(HOLIDAY_ENAME) AS HOLIDAY_ENAME"),
            new Expression("INITCAP(TO_CHAR(START_DATE, 'DD-MON-YYYY')) AS START_DATE"),
            new Expression("INITCAP(TO_CHAR(END_DATE, 'DD-MON-YYYY')) AS END_DATE"),
            new Expression("INITCAP(HOLIDAY_LNAME) AS HOLIDAY_LNAME"),
            new Expression("STATUS AS STATUS"),
            new Expression("HALFDAY AS HALFDAY"),
            new Expression("FISCAL_YEAR AS FISCAL_YEAR"),
            new Expression("ASSIGN_ON_EMPLOYEE_SETUP AS ASSIGN_ON_EMPLOYEE_SETUP"),
            new Expression("REMARKS AS REMARKS"),
            new Expression("COMPANY_ID AS COMPANY_ID"),
            new Expression("BRANCH_ID AS BRANCH_ID"),
            new Expression("DEPARTMENT_ID AS DEPARTMENT_ID"),
            new Expression("DESIGNATION_ID AS DESIGNATION_ID"),
            new Expression("POSITION_ID AS POSITION_ID"),
            new Expression("SERVICE_TYPE_ID AS SERVICE_TYPE_ID"),
            new Expression("EMPLOYEE_TYPE AS EMPLOYEE_TYPE"),
            new Expression("GENDER_ID AS GENDER_ID"),
            new Expression("EMPLOYEE_ID AS EMPLOYEE_ID"),
        ], true);

        $select->from(Holiday::TABLE_NAME);

        $select->where([
            Holiday::HOLIDAY_ID => $id,
            Holiday::STATUS => 'E'
        ]);
        $statement = $sql->prepareStatementForSqlObject($select);
        $result = $statement->execute();
        return $result->current();
    }

    public function delete($id)
    {
        $this->tableGateway->update([Holiday::STATUS => 'D'], [Holiday::HOLIDAY_ID => $id]);
    }

    public function filterRecords($fromDate, $toDate)
    {
        $boundedParameter = [];
        $sql = "
                SELECT INITCAP(TO_CHAR(A.START_DATE, 'DD-MON-YYYY')) AS START_DATE,
                 BS_DATE(TO_CHAR(A.START_DATE, 'DD-MON-YYYY')) AS START_DATE_N,
                  INITCAP(TO_CHAR(A.END_DATE, 'DD-MON-YYYY'))  AS END_DATE,
                  BS_DATE(TO_CHAR(A.END_DATE, 'DD-MON-YYYY'))  AS END_DATE_N,
                  A.HOLIDAY_ID,
                  A.HOLIDAY_CODE,
                  INITCAP(A.HOLIDAY_ENAME) AS HOLIDAY_ENAME,
                  INITCAP(A.HOLIDAY_LNAME) AS HOLIDAY_LNAME,
                  CASE
                    WHEN A.HALFDAY = 'F'
                    THEN 'First Half'
                    ELSE
                      CASE
                        WHEN A.HALFDAY='S'
                        THEN 'Second Half'
                        ELSE 'Full Day'
                      END
                  END AS HALFDAY
                FROM HRIS_HOLIDAY_MASTER_SETUP A
                WHERE A.STATUS ='E'";

        if ($fromDate != null) {
            $sql .= " AND A.START_DATE>=TO_DATE(:fromDate,'DD-MM-YYYY')";
            $boundedParameter['fromDate'] = $fromDate;
        }

        if ($toDate != null) {
            $sql .= " AND A.END_DATE<=TO_DATE(:toDate,'DD-MM-YYYY')";
            $boundedParameter['toDate'] = $toDate;
        }
        //        if ($fromDate == null && $toDate == null) {
        //            $sql .= " AND A.FISCAL_YEAR=" . $this->fiscalYr;
        //        }

        $sql .= " ORDER BY A.START_DATE DESC";
        $statement = $this->adapter->query($sql);
        $result = $statement->execute($boundedParameter);
        return $result;
    }

    public function filter($branchId, $genderId, Expression $date)
    {
        throw new Exception("HolidayRepository => filter is changed. plz use ");
    }

    public function filterHoliday($employeeId, Expression $afterDate = null)
    {
        $sql = new Sql($this->adapter);
        $select = $sql->select();

        $select->from(["H" => Holiday::TABLE_NAME]);

        if ($employeeId != null) {
            $select->join(["EH" => EmployeeHoliday::TABLE_NAME], "H." . Holiday::HOLIDAY_ID . " = EH." . EmployeeHoliday::HOLIDAY_ID);
            $select->where(["EH." . EmployeeHoliday::EMPLOYEE_ID . "= $employeeId"]);
        }

        if ($afterDate != null) {
            $select->where(["H." . Holiday::START_DATE . ">= " . $afterDate->getExpression()]);
        }

        $statement = $sql->prepareStatementForSqlObject($select);
        return $statement->execute();
    }

    public function holidayAssign(int $holidayId)
    {
        EntityHelper::rawQueryResult($this->adapter, "BEGIN HRIS_HOLIDAY_ASSIGN_AUTO({$holidayId}); END;");
        $sql = "SELECT
        hr.start_date,
        hr.end_date,
        he.employee_id
    FROM
        hris_holiday_master_setup hr
        left join hris_employee_holiday he on (hr.holiday_id=he.holiday_id)
    WHERE
        hr.holiday_id = $holidayId";
        $statement = $this->adapter->query($sql);
        $result = Helper::extractDbData($statement->execute());
        $length = count($result);
        for ($j = 0; $j < $length; $j++) {
            $sql = "BEGIN 
              HRIS_REATTENDANCE('{$result[$j]['START_DATE']}',{$result[$j]['EMPLOYEE_ID']},'{$result[$j]['END_DATE']}');
                 END; ";
            //  echo '<pre>';print_r($sql);die;
            EntityHelper::rawQueryResult($this->adapter, $sql);
        }
    }
}
