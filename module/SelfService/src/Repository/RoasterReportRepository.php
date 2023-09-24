<?php

namespace SelfService\Repository;

use Application\Helper\EntityHelper;
use Application\Helper\Helper;
use Application\Model\FiscalYear;
use Application\Model\Months;
use Application\Repository\HrisRepository;
use LeaveManagement\Model\LeaveMaster;
use Zend\Db\Sql\Expression;
use Zend\Db\Sql\Sql;

class RoasterReportRepository extends HrisRepository
{

    public function __construct(AdapterInterface $adapter)
    {
        $this->tableGateway = new TableGateway(Holiday::TABLE_NAME, $adapter);
        $this->adapter = $adapter;
    }

    function add(Model $model)
    {
        // TODO: Implement add() method.
    }

    function edit(Model $model, $id)
    {
        // TODO: Implement edit() method.
    }

    function delete($id)
    {
        // TODO: Implement delete() method.
    }

    function fetchAll()
    {
    }

    function selectAll($employeeId)
    {
        $sql = new Sql($this->adapter);
        $select = $sql->select();
        $select->columns([
            new Expression("INITCAP(TO_CHAR(H.START_DATE, 'DD-MON-YYYY')) AS START_DATE"),
            new Expression("INITCAP(TO_CHAR(H.START_DATE, 'DD-MON-YYYY')) AS START_DATE_AD"),
            new Expression("BS_DATE(TO_CHAR(H.START_DATE, 'DD-MON-YYYY')) AS START_DATE_BS"),
            new Expression("INITCAP(TO_CHAR(H.END_DATE, 'DD-MON-YYYY')) AS END_DATE"),
            new Expression("INITCAP(TO_CHAR(H.END_DATE, 'DD-MON-YYYY')) AS END_DATE_AD"),
            new Expression("BS_DATE(TO_CHAR(H.END_DATE, 'DD-MON-YYYY')) AS END_DATE_BS"),
            new Expression("H.HOLIDAY_ID AS HOLIDAY_ID"),
            new Expression("H.HOLIDAY_CODE AS HOLIDAY_CODE"),
            new Expression("INITCAP(H.HOLIDAY_ENAME) AS HOLIDAY_ENAME"),
            new Expression("H.HALFDAY AS HALFDAY"),
            new Expression("(
                              CASE
                                WHEN (H.HALFDAY IS NULL
                                OR H.HALFDAY     = 'N')
                                THEN 'Full Day'
                                WHEN H.HALFDAY = 'F'
                                THEN 'First Half'
                                ELSE 'Second Half'
                              END) AS HALFDAY_DETAIL"),
            new Expression("H.FISCAL_YEAR AS FISCAL_YEAR"),
            new Expression("H.REMARKS AS REMARKS"),
        ], true);

        $select->from(['H' => Holiday::TABLE_NAME])
            ->join(['EH' => EmployeeHoliday::TABLE_NAME], "EH.HOLIDAY_ID=H.HOLIDAY_ID", ['EMPLOYEE_ID'], "left");
        $select->where(["H.STATUS" => 'E', "EH.EMPLOYEE_ID" => $employeeId,]);
        $select->order(["H.START_DATE" => Select::ORDER_DESCENDING]);
        $statement = $sql->prepareStatementForSqlObject($select);
        // echo '<pre>';print_r($statement);die;
        $result = $statement->execute();
        return $result;
    }

    function fetchById($id)
    {
        // TODO: Implement fetchById() method.
    }
}
