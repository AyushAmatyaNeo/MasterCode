<?php

namespace SelfService\Repository;

use Application\Helper\Helper;
use Application\Model\Model;
use Application\Repository\RepositoryInterface;
use SelfService\Model\Overtime;
use Zend\Db\Adapter\AdapterInterface;
use Zend\Db\Sql\Expression;
use Zend\Db\Sql\Sql;
use Zend\Db\TableGateway\TableGateway;

class OvertimeRepository implements RepositoryInterface {

    private $tableGateway;
    private $adapter;

    public function __construct(AdapterInterface $adapter) {
        $this->adapter = $adapter;
        $this->tableGateway = new TableGateway(Overtime::TABLE_NAME, $adapter);
    }

    public function add(Model $model) {
        $this->tableGateway->insert($model->getArrayCopyForDB());
        return 1;
    }

    public function delete($id) {
        $currentDate = Helper::getcurrentExpressionDate();
        $this->tableGateway->update([Overtime::STATUS => 'C', Overtime::MODIFIED_DATE => $currentDate], [Overtime::OVERTIME_ID => $id]);
    }

    public function edit(Model $model, $id) {
        
    }

    public function fetchAll() {
        
    }

    public function fetchById($id) {
        $sql = new Sql($this->adapter);
        $select = $sql->select();
        $select->columns([
            new Expression("OT.OVERTIME_ID AS OVERTIME_ID"),
            new Expression("OT.EMPLOYEE_ID AS EMPLOYEE_ID"),
            new Expression("INITCAP(TO_CHAR(OT.OVERTIME_DATE, 'DD-MON-YYYY')) AS OVERTIME_DATE"),
            new Expression("INITCAP(TO_CHAR(OT.OVERTIME_DATE, 'DD-MON-YYYY')) AS OVERTIME_DATE_AD"),
            new Expression("BS_DATE(TO_CHAR(OT.OVERTIME_DATE, 'DD-MON-YYYY')) AS OVERTIME_DATE_BS"),
            new Expression("INITCAP(TO_CHAR(OT.REQUESTED_DATE, 'DD-MON-YYYY')) AS REQUESTED_DATE"),
            new Expression("INITCAP(TO_CHAR(OT.REQUESTED_DATE, 'DD-MON-YYYY')) AS REQUESTED_DATE_AD"),
            new Expression("BS_DATE(TO_CHAR(OT.REQUESTED_DATE, 'DD-MON-YYYY')) AS REQUESTED_DATE_BS"),
            new Expression("OT.DESCRIPTION AS IN_DESCRIPTION"),
            new Expression("OT.REMARKS AS REMARKS"),
            new Expression("OT.TOTAL_HOUR AS TOTAL_HOUR"),
            new Expression("MIN_TO_HOUR(OT.TOTAL_HOUR) AS TOTAL_HOUR_DETAIL"),
            new Expression("OT.STATUS AS STATUS"),
            new Expression("LEAVE_STATUS_DESC(OT.STATUS) AS STATUS_DETAIL"),
            new Expression("OT.RECOMMENDED_BY AS RECOMMENDED_BY"),
            new Expression("INITCAP(TO_CHAR(OT.RECOMMENDED_DATE, 'DD-MON-YYYY')) AS RECOMMENDED_DATE"),
            new Expression("OT.RECOMMENDED_REMARKS AS RECOMMENDED_REMARKS"),
            new Expression("OT.APPROVED_BY AS APPROVED_BY"),
            new Expression("INITCAP(TO_CHAR(OT.APPROVED_DATE, 'DD-MON-YYYY')) AS APPROVED_DATE"),
            new Expression("OT.APPROVED_REMARKS AS APPROVED_REMARKS")
        ]);
        $select->from(['OT' => Overtime::TABLE_NAME])
                ->join(['E' => "HRIS_EMPLOYEES"], "E.EMPLOYEE_ID=OT.EMPLOYEE_ID", ["FULL_NAME" => new Expression("INITCAP(E.FULL_NAME)")], "left")
                ->join(['E1' => "HRIS_EMPLOYEES"], "E1.EMPLOYEE_ID=OT.RECOMMENDED_BY", ['RECOMMENDED_BY_NAME' => new Expression("INITCAP(E1.FULL_NAME)")], "left")
                ->join(['E2' => "HRIS_EMPLOYEES"], "E2.EMPLOYEE_ID=OT.APPROVED_BY", ['APPROVED_BY_NAME' => new Expression("INITCAP(E2.FULL_NAME)")], "left")
                ->join(['RA' => "HRIS_RECOMMENDER_APPROVER"], "RA.EMPLOYEE_ID=OT.EMPLOYEE_ID", ['RECOMMENDER_ID' => 'RECOMMEND_BY', 'APPROVER_ID' => 'APPROVED_BY'], "left")
                ->join(['RECM' => "HRIS_EMPLOYEES"], "RECM.EMPLOYEE_ID=RA.RECOMMEND_BY", ['RECOMMENDER_NAME' => new Expression("INITCAP(RECM.FULL_NAME)")], "left")
                ->join(['APRV' => "HRIS_EMPLOYEES"], "APRV.EMPLOYEE_ID=RA.APPROVED_BY", ['APPROVER_NAME' => new Expression("INITCAP(APRV.FULL_NAME)")], "left");

        $select->where(["OT.OVERTIME_ID" => $id]);
        $select->order("OT.REQUESTED_DATE DESC");
        $statement = $sql->prepareStatementForSqlObject($select);
        $result = $statement->execute();
        // echo '<pre>';print_r($result->current());die;
        return $result->current();
    }

    public function getAllByEmployeeId($employeeId, $overtimeDate = null, $status = null) {
        $sql = new Sql($this->adapter);
        $select = $sql->select();
        $select->columns([
            new Expression("OT.OVERTIME_ID AS OVERTIME_ID"),
            new Expression("OT.EMPLOYEE_ID AS EMPLOYEE_ID"),
            new Expression("INITCAP(TO_CHAR(OT.OVERTIME_DATE, 'DD-MON-YYYY')) AS OVERTIME_DATE_AD"),
            new Expression("BS_DATE(TO_CHAR(OT.OVERTIME_DATE, 'DD-MON-YYYY')) AS OVERTIME_DATE_BS"),
            new Expression("INITCAP(TO_CHAR(OT.REQUESTED_DATE, 'DD-MON-YYYY')) AS REQUESTED_DATE_AD"),
            new Expression("BS_DATE(TO_CHAR(OT.REQUESTED_DATE, 'DD-MON-YYYY')) AS REQUESTED_DATE_BS"),
            new Expression("OT.DESCRIPTION AS IN_DESCRIPTION"),
            new Expression("OT.REMARKS AS REMARKS"),
            new Expression("OT.TOTAL_HOUR AS TOTAL_MIN"),
            new Expression("TRUNC(OT.TOTAL_HOUR/60,2) AS TOTAL_HOUR"),
            new Expression("MIN_TO_HOUR(OT.TOTAL_HOUR) AS TOTAL_HOUR_DETAIL"),
            new Expression("LEAVE_STATUS_DESC(OT.STATUS) AS STATUS"),
            new Expression("OT.RECOMMENDED_BY AS RECOMMENDED_BY"),
            new Expression("INITCAP(TO_CHAR(OT.RECOMMENDED_DATE, 'DD-MON-YYYY')) AS RECOMMENDED_DATE"),
            new Expression("OT.RECOMMENDED_REMARKS AS RECOMMENDED_REMARKS"),
            new Expression("OT.APPROVED_BY AS APPROVED_BY"),
            new Expression("INITCAP(TO_CHAR(OT.APPROVED_DATE, 'DD-MON-YYYY')) AS APPROVED_DATE"),
            new Expression("OT.APPROVED_REMARKS AS APPROVED_REMARKS"),
            new Expression("(CASE WHEN OT.STATUS = 'RQ' THEN 'Y' ELSE 'N' END) AS ALLOW_EDIT"),
            new Expression("(CASE WHEN OT.STATUS IN ('RQ','RC','AP') THEN 'Y' ELSE 'N' END) AS ALLOW_DELETE"),
                ], true);
        $select->from(['OT' => Overtime::TABLE_NAME])
                ->join(['E' => "HRIS_EMPLOYEES"], "E.EMPLOYEE_ID=OT.EMPLOYEE_ID", ["FULL_NAME" => new Expression("INITCAP(E.FULL_NAME)")], "left")
                ->join(['E1' => "HRIS_EMPLOYEES"], "E1.EMPLOYEE_ID=OT.RECOMMENDED_BY", ['RECOMMENDED_BY_NAME' => new Expression("INITCAP(E1.FULL_NAME)")], "left")
                ->join(['E2' => "HRIS_EMPLOYEES"], "E2.EMPLOYEE_ID=OT.APPROVED_BY", ['APPROVED_BY_NAME' => new Expression("INITCAP(E2.FULL_NAME)")], "left")
                ->join(['RA' => "HRIS_RECOMMENDER_APPROVER"], "RA.EMPLOYEE_ID=OT.EMPLOYEE_ID", ['RECOMMENDER_ID' => 'RECOMMEND_BY', 'APPROVER_ID' => 'APPROVED_BY'], "left")
                ->join(['RECM' => "HRIS_EMPLOYEES"], "RECM.EMPLOYEE_ID=RA.RECOMMEND_BY", ['RECOMMENDER_NAME' => new Expression("INITCAP(RECM.FULL_NAME)")], "left")
                ->join(['APRV' => "HRIS_EMPLOYEES"], "APRV.EMPLOYEE_ID=RA.APPROVED_BY", ['APPROVER_NAME' => new Expression("INITCAP(APRV.FULL_NAME)")], "left");

        $select->where(["E.EMPLOYEE_ID" => $employeeId]);
        if ($overtimeDate != null) {
            $select->where([
                "OT." . Overtime::OVERTIME_DATE . "=TO_DATE('" . $overtimeDate . "','DD-MON-YYYY')"
            ]);
        }
        if ($status != null && $status != -1) {
            $select->where([
                "OT." . Overtime::STATUS . "='" . $status . "'"
            ]);
        }
        $select->where([
            "(TRUNC(SYSDATE)- OT.REQUESTED_DATE) < (
                      CASE
                        WHEN OT.STATUS = 'C'
                        THEN 20
                        ELSE 365
                      END)"
        ]);
        $select->order("OT.REQUESTED_DATE DESC");
        $statement = $sql->prepareStatementForSqlObject($select);
        $result = $statement->execute();
        return $result;
    }

    public function executeProcedure($overtimeDate) {
        $dbAdapter = $this->tableGateway->getAdapter();
        $stmt = $dbAdapter->createStatement();
        $stmt->prepare("CALL HRIS_OVERTIME_AUTOMATION(TRUNC(TO_DATE('" . $overtimeDate . "','DD-MON-YYYY')))");
        $stmt->execute();
    }

    public function fetchAttendanceDetail($employeeId, $date) {
        $sql = "SELECT 
        TO_CHAR(IN_TIME, 'HH:MI AM')   AS IN_TIME,
        TO_CHAR(OUT_TIME, 'HH:MI AM')  AS OUT_TIME,
        TOTAL_HOUR,
        TOTAL_HOUR - 480 as OT_MINUTES
        FROM HRIS_ATTENDANCE_DETAIL 
        WHERE EMPLOYEE_ID = :employeeId 
        and ATTENDANCE_DT = TO_DATE(:date, 'DD-MON-YY')";

        $boundedParameter = [];
        $boundedParameter['employeeId'] = $employeeId;
        $boundedParameter['date'] = $date;
        return $this->rawQuery($sql, $boundedParameter);
    }

}
