<?php

namespace SelfService\Repository;

use Application\Model\Model;
use Application\Repository\RepositoryInterface;
use SelfService\Model\AttendanceRequestModel;
use Zend\Db\Adapter\AdapterInterface;
use Zend\Db\Sql\Expression;
use Zend\Db\Sql\Sql;
use Zend\Db\TableGateway\TableGateway;

class AttendanceRequestRepository implements RepositoryInterface {

    private $adapter;
    private $tableGateway;

    public function __construct(AdapterInterface $adapter) {
        $this->tableGateway = new TableGateway(AttendanceRequestModel::TABLE_NAME, $adapter);
        $this->adapter = $adapter;
    }

    public function add(Model $model) {
        $this->tableGateway->insert($model->getArrayCopyForDB());
    }

    public function edit(Model $model, $id) {
        $array = $model->getArrayCopyForDB();
        $this->tableGateway->update($array, [AttendanceRequestModel::ID => $id]);
    }

    public function fetchAll() {
        $sql = new Sql($this->adapter);
        $select = $sql->select();
        $select->columns([
            new Expression("INITCAP(TO_CHAR(A.ATTENDANCE_DT, 'DD-MON-YYYY')) AS ATTENDANCE_DT"),
            new Expression("INITCAP(TO_CHAR(A.IN_TIME, 'HH:MI AM')) AS IN_TIME"),
            new Expression("INITCAP(TO_CHAR(A.OUT_TIME, 'HH:MI AM')) AS OUT_TIME"),
            new Expression("E.EMPLOYEE_ID AS EMPLOYEE_ID"),
            new Expression("A.ID AS ID"),
            new Expression("A.IN_REMARKS AS IN_REMARKS"),
            new Expression("A.OUT_REMARKS AS OUT_REMARKS")
                ], true);
        $select->from(['A' => AttendanceRequestModel::TABLE_NAME])
                ->join(['E' => 'HRIS_EMPLOYEES'], 'A.EMPLOYEE_ID=E.EMPLOYEE_ID', ["FIRST_NAME" => new Expression("INITCAP(E.FIRST_NAME)"), "MIDDLE_NAME" => new Expression("INITCAP(E.MIDDLE_NAME)"), "LAST_NAME" => new Expression("INITCAP(E.LAST_NAME)")], "left");
        $select->order("A.REQUESTED_DT DESC");
        $statement = $sql->prepareStatementForSqlObject($select);
        $result = $statement->execute();
        return $result;
    }

    public function fetchById($id) {
        $sql = new Sql($this->adapter);
        $select = $sql->select();
        $select->columns([
            new Expression("INITCAP(TO_CHAR(A.ATTENDANCE_DT, 'DD-MON-YYYY')) AS ATTENDANCE_DT"),
            new Expression("INITCAP(TO_CHAR(A.IN_TIME, 'HH:MI AM')) AS IN_TIME"),
            new Expression("INITCAP(TO_CHAR(A.OUT_TIME, 'HH:MI AM')) AS OUT_TIME"),
            new Expression("E.EMPLOYEE_ID AS EMPLOYEE_ID"),
            new Expression("A.ID AS ID"),
            new Expression("A.IN_REMARKS AS IN_REMARKS"),
            new Expression("A.STATUS AS STATUS"),
            new Expression("A.APPROVED_BY AS APPROVED_BY"),
            new Expression("A.RECOMMENDED_BY AS RECOMMENDED_BY"),
            new Expression("A.RECOMMENDED_REMARKS AS RECOMMENDED_REMARKS"),
            new Expression("A.OUT_REMARKS AS OUT_REMARKS"),
            new Expression("INITCAP(TO_CHAR(A.REQUESTED_DT, 'DD-MON-YYYY')) AS REQUESTED_DT"),
            new Expression("INITCAP(TO_CHAR(A.APPROVED_DT, 'DD-MON-YYYY')) AS APPROVED_DT"),
            new Expression("A.APPROVED_REMARKS AS APPROVED_REMARKS"),
            new Expression("A.TOTAL_HOUR AS TOTAL_HOUR")], true);
        $select->from(['A' => AttendanceRequestModel::TABLE_NAME])
                ->join(['E' => 'HRIS_EMPLOYEES'], 'A.EMPLOYEE_ID=E.EMPLOYEE_ID', ["FULL_NAME" => new Expression("INITCAP(E.FULL_NAME)")], "left")
                ->join(['E2' => "HRIS_EMPLOYEES"], "E2.EMPLOYEE_ID=A.RECOMMENDED_BY", ['RECOMMENDED_BY_NAME' => new Expression("INITCAP(E2.FULL_NAME)")], "left")
                ->join(['E3' => "HRIS_EMPLOYEES"], "E3.EMPLOYEE_ID=A.APPROVED_BY", ['APPROVED_BY_NAME' => new Expression("INITCAP(E3.FULL_NAME)")], "left")
                ->join(['RA' => "HRIS_RECOMMENDER_APPROVER"], "RA.EMPLOYEE_ID=A.EMPLOYEE_ID", ['RECOMMENDER_ID' => 'RECOMMEND_BY', 'APPROVER_ID' => 'APPROVED_BY'], "left")
                ->join(['RECM' => "HRIS_EMPLOYEES"], "RECM.EMPLOYEE_ID=RA.RECOMMEND_BY", ['RECOMMENDER_NAME' => new Expression("INITCAP(RECM.FULL_NAME)")], "left")
                ->join(['APRV' => "HRIS_EMPLOYEES"], "APRV.EMPLOYEE_ID=RA.APPROVED_BY", ['APPROVER_NAME' => new Expression("INITCAP(APRV.FULL_NAME)")], "left");

        $select->where([AttendanceRequestModel::ID => $id]);
        $statement = $sql->prepareStatementForSqlObject($select);
        $result = $statement->execute();
        return $result->current();
    }

    public function fetchByEmpId($id) {
        $sql = new Sql($this->adapter);
        $select = $sql->select();
        $select->columns([
            new Expression("INITCAP(TO_CHAR(A.ATTENDANCE_DT, 'DD-MON-YYYY')) AS ATTENDANCE_DT"),
            new Expression("INITCAP(TO_CHAR(A.IN_TIME, 'HH:MI AM')) AS IN_TIME"),
            new Expression("INITCAP(TO_CHAR(A.OUT_TIME, 'HH:MI AM')) AS OUT_TIME"),
            new Expression("E.EMPLOYEE_ID AS EMPLOYEE_ID"),
            new Expression("A.ID AS ID"),
            new Expression("A.IN_REMARKS AS IN_REMARKS"),
            new Expression("A.OUT_REMARKS AS OUT_REMARKS"),
            new Expression("A.TOTAL_HOUR AS TOTAL_HOUR"),
            new Expression("A.STATUS AS STATUS")
                ], true);
        $select->from(['A' => AttendanceRequestModel::TABLE_NAME])
                ->join(['E' => 'HRIS_EMPLOYEES'], 'A.EMPLOYEE_ID=E.EMPLOYEE_ID', ["FIRST_NAME" => new Expression("INITCAP(E.FIRST_NAME)")], "left");
        $select->where(['A.EMPLOYEE_ID' => $id]);
        $statement = $sql->prepareStatementForSqlObject($select);
        $result = $statement->execute();
        return $result;
    }

    public function delete($id) {
        $this->tableGateway->update([AttendanceRequestModel::STATUS => 'C'], [AttendanceRequestModel::ID => $id]);
    }

    public function getFilterRecords($data) {
        $employeeId = $data['employeeId'];
        $attendanceRequestStatusId = $data['attendanceRequestStatusId'];
        $fromDate = $data['fromDate'];
        $toDate = $data['toDate'];

        $sql = new Sql($this->adapter);
        $select = $sql->select();
        $select->columns([
            new Expression("A.ID AS ID"),
            new Expression("E.EMPLOYEE_ID AS EMPLOYEE_ID"),
            new Expression("INITCAP(TO_CHAR(A.ATTENDANCE_DT, 'DD-MON-YYYY')) AS ATTENDANCE_DT_AD"),
            new Expression("BS_DATE(TO_CHAR(A.ATTENDANCE_DT, 'DD-MON-YYYY')) AS ATTENDANCE_DT_BS"),
            new Expression("TO_CHAR(A.IN_TIME, 'HH:MI AM') AS IN_TIME"),
            new Expression("TO_CHAR(A.OUT_TIME, 'HH:MI AM') AS OUT_TIME"),
            new Expression("A.IN_REMARKS AS IN_REMARKS"),
            new Expression("A.OUT_REMARKS AS OUT_REMARKS"),
            new Expression("A.TOTAL_HOUR AS TOTAL_HOUR"),
            new Expression("INITCAP(TO_CHAR(A.REQUESTED_DT, 'DD-MON-YYYY')) AS REQUESTED_DT_AD"),
            new Expression("BS_DATE(TO_CHAR(A.REQUESTED_DT, 'DD-MON-YYYY')) AS REQUESTED_DT_BS"),
            new Expression("LEAVE_STATUS_DESC(A.STATUS) AS STATUS"),
            new Expression("A.RECOMMENDED_BY AS RECOMMENDED_BY"),
            new Expression("INITCAP(TO_CHAR(A.RECOMMENDED_DATE, 'DD-MON-YYYY')) AS RECOMMENDED_DATE"),
            new Expression("A.RECOMMENDED_REMARKS AS RECOMMENDED_REMARKS"),
            new Expression("A.APPROVED_BY AS APPROVED_BY"),
            new Expression("INITCAP(TO_CHAR(A.APPROVED_DT, 'DD-MON-YYYY')) AS APPROVED_DT"),
            new Expression("A.APPROVED_REMARKS AS APPROVED_REMARKS"),
            new Expression("(CASE WHEN A.STATUS = 'XX' THEN 'Y' ELSE 'N' END) AS ALLOW_EDIT"),
            new Expression("(CASE WHEN A.STATUS IN ('RQ','RC') THEN 'Y' ELSE 'N' END) AS ALLOW_DELETE"),
                ], true);
        $select->from(['A' => AttendanceRequestModel::TABLE_NAME])
                ->join(['E' => "HRIS_EMPLOYEES"], "E.EMPLOYEE_ID=A.EMPLOYEE_ID", ["FULL_NAME" => new Expression("INITCAP(E.FULL_NAME)")], "left")
                ->join(['E1' => "HRIS_EMPLOYEES"], "E1.EMPLOYEE_ID=A.RECOMMENDED_BY", ['RECOMMENDED_BY_NAME' => new Expression("INITCAP(E1.FULL_NAME)")], "left")
                ->join(['E2' => "HRIS_EMPLOYEES"], "E2.EMPLOYEE_ID=A.APPROVED_BY", ['APPROVED_BY_NAME' => new Expression("INITCAP(E2.FULL_NAME)")], "left")
                ->join(['RA' => "HRIS_RECOMMENDER_APPROVER"], "RA.EMPLOYEE_ID=A.EMPLOYEE_ID", ['RECOMMENDER_ID' => 'RECOMMEND_BY', 'APPROVER_ID' => 'APPROVED_BY'], "left")
                ->join(['RECM' => "HRIS_EMPLOYEES"], "RECM.EMPLOYEE_ID=RA.RECOMMEND_BY", ['RECOMMENDER_NAME' => new Expression("INITCAP(RECM.FULL_NAME)")], "left")
                ->join(['APRV' => "HRIS_EMPLOYEES"], "APRV.EMPLOYEE_ID=RA.APPROVED_BY", ['APPROVER_NAME' => new Expression("INITCAP(APRV.FULL_NAME)")], "left");

        $select->where(['A.EMPLOYEE_ID' => $employeeId]);

        if ($attendanceRequestStatusId != -1) {
            $select->where([
                "A.STATUS='{$attendanceRequestStatusId}'"
            ]);
        }
        if ($attendanceRequestStatusId != 'C') {
            $select->where([
                "(TRUNC(SYSDATE)- A.ATTENDANCE_DT) < (
                      CASE
                        WHEN A.STATUS = 'C'
                        THEN 20
                        ELSE 365
                      END)"
            ]);
        }

        if ($fromDate != null) {
            $select->where([
                "A.ATTENDANCE_DT>=TO_DATE('" . $fromDate . "','DD-MM-YYYY')"
            ]);
        }
        if ($toDate != null) {
            $select->where([
                "A.ATTENDANCE_DT<=TO_DATE('" . $toDate . "','DD-MM-YYYY')"
            ]);
        }

        $select->order("A.REQUESTED_DT DESC");
        $statement = $sql->prepareStatementForSqlObject($select);
        $result = $statement->execute();
        return $result;
    }
    
    
    public function fetchByIdWithEmployeeId($id,$employeeId) {
        $sql = new Sql($this->adapter);
        $select = $sql->select();
        $select->columns([
            new Expression("INITCAP(TO_CHAR(A.ATTENDANCE_DT, 'DD-MON-YYYY')) AS ATTENDANCE_DT"),
            new Expression("INITCAP(TO_CHAR(A.IN_TIME, 'HH:MI AM')) AS IN_TIME"),
            new Expression("INITCAP(TO_CHAR(A.OUT_TIME, 'HH:MI AM')) AS OUT_TIME"),
            new Expression("E.EMPLOYEE_ID AS EMPLOYEE_ID"),
            new Expression("A.ID AS ID"),
            new Expression("A.IN_REMARKS AS IN_REMARKS"),
            new Expression("A.STATUS AS STATUS"),
            new Expression("A.APPROVED_BY AS APPROVED_BY"),
            new Expression("A.RECOMMENDED_BY AS RECOMMENDED_BY"),
            new Expression("A.RECOMMENDED_REMARKS AS RECOMMENDED_REMARKS"),
            new Expression("A.OUT_REMARKS AS OUT_REMARKS"),
            new Expression("INITCAP(TO_CHAR(A.REQUESTED_DT, 'DD-MON-YYYY')) AS REQUESTED_DT"),
            new Expression("INITCAP(TO_CHAR(A.APPROVED_DT, 'DD-MON-YYYY')) AS APPROVED_DT"),
            new Expression("A.APPROVED_REMARKS AS APPROVED_REMARKS"),
            new Expression("CASE WHEN ALR.R_A_ID IS NOT NULL THEN ALR.R_A_ID ELSE  RA.RECOMMEND_BY END AS RECOMMENDER_ID"),
            new Expression("CASE WHEN ALA.R_A_ID IS NOT NULL THEN ALA.R_A_ID ELSE  RA.APPROVED_BY END AS APPROVER_ID"),
            new Expression("CASE WHEN ALR_E.FULL_NAME IS NOT NULL THEN ALR_E.FULL_NAME ELSE  INITCAP(RECM.FULL_NAME) END AS RECOMMENDER_NAME"),
            new Expression("CASE WHEN ALA_E.FULL_NAME IS NOT NULL THEN ALA_E.FULL_NAME ELSE  INITCAP(APRV.FULL_NAME) END AS APPROVER_NAME"),
            new Expression("A.TOTAL_HOUR AS TOTAL_HOUR")], true);
        $select->from(['A' => AttendanceRequestModel::TABLE_NAME])
                ->join(['E' => 'HRIS_EMPLOYEES'], 'A.EMPLOYEE_ID=E.EMPLOYEE_ID', ["FULL_NAME" => new Expression("INITCAP(E.FULL_NAME)")], "left")
                ->join(['E2' => "HRIS_EMPLOYEES"], "E2.EMPLOYEE_ID=A.RECOMMENDED_BY", ['RECOMMENDED_BY_NAME' => new Expression("INITCAP(E2.FULL_NAME)")], "left")
                ->join(['E3' => "HRIS_EMPLOYEES"], "E3.EMPLOYEE_ID=A.APPROVED_BY", ['APPROVED_BY_NAME' => new Expression("INITCAP(E3.FULL_NAME)")], "left")
                ->join(['RA' => "HRIS_RECOMMENDER_APPROVER"], "RA.EMPLOYEE_ID=A.EMPLOYEE_ID", [], "left")
                ->join(['RECM' => "HRIS_EMPLOYEES"], "RECM.EMPLOYEE_ID=RA.RECOMMEND_BY", [], "left")
                ->join(['APRV' => "HRIS_EMPLOYEES"], "APRV.EMPLOYEE_ID=RA.APPROVED_BY", [], "left")
                ->join(['ALR' => "HRIS_ALTERNATE_R_A"], "ALR.R_A_FLAG='R' AND ALR.EMPLOYEE_ID=A.EMPLOYEE_ID AND ALR.R_A_ID=".$employeeId, [], "left")
                ->join(['ALA' => "HRIS_ALTERNATE_R_A"], "ALA.R_A_FLAG='A' AND ALA.EMPLOYEE_ID=A.EMPLOYEE_ID AND ALR.R_A_ID=".$employeeId, [], "left")
                ->join(['ALR_E' => "HRIS_EMPLOYEES"], "ALR.R_A_ID=ALR_E.EMPLOYEE_ID", [], "left")
                ->join(['ALA_E' => "HRIS_EMPLOYEES"], "ALA.R_A_ID=ALA_E.EMPLOYEE_ID", [], "left");

        $select->where([AttendanceRequestModel::ID => $id]);
        $statement = $sql->prepareStatementForSqlObject($select);
        $result = $statement->execute();
        return $result->current();
    }
    
    

}
