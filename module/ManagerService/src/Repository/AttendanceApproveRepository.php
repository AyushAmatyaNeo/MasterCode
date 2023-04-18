<?php

namespace ManagerService\Repository;

use Application\Helper\EntityHelper;
use Application\Model\Model;
use Application\Repository\HrisRepository;
use SelfService\Model\AttendanceRequestModel;
use Traversable;
use Zend\Db\Adapter\AdapterInterface;
use Zend\Db\Sql\Expression;
use Zend\Db\Sql\Sql;

class AttendanceApproveRepository extends HrisRepository {

    public function __construct(AdapterInterface $adapter, $tableName = null) {
        if ($tableName == null) {
            $tableName = AttendanceRequestModel::TABLE_NAME;
        }
        parent::__construct($adapter, $tableName);
    }

    public function getAllRequest($id): Traversable {
        $boundedParams = [];
        $boundedParams['id'] = $id;
        $sql = new Sql($this->adapter);
        $select = $sql->select();
        $select->columns([
            new Expression("INITCAP(TO_CHAR(AR.REQUESTED_DT, 'DD-MON-YYYY')) AS REQUESTED_DT"),
            new Expression("BS_DATE(TO_CHAR(AR.REQUESTED_DT, 'DD-MON-YYYY')) AS REQUESTED_DT_BS"),
            new Expression("INITCAP(TO_CHAR(AR.ATTENDANCE_DT, 'DD-MON-YYYY')) AS ATTENDANCE_DT"),
            new Expression("BS_DATE(TO_CHAR(AR.ATTENDANCE_DT, 'DD-MON-YYYY')) AS ATTENDANCE_DT_BS"),
            new Expression("INITCAP(TO_CHAR(AR.APPROVED_DT, 'DD-MON-YYYY')) AS APPROVED_DT"),
            new Expression("AR.APPROVED_BY AS APPROVED_BY"),
            new Expression("AR.APPROVED_REMARKS AS APPROVED_REMARKS"),
            new Expression("AR.ID AS ID"),
            new Expression("INITCAP(TO_CHAR(AR.IN_TIME, 'HH:MI AM')) AS IN_TIME"),
            new Expression("INITCAP(TO_CHAR(AR.OUT_TIME, 'HH:MI AM')) AS OUT_TIME"),
            new Expression("AR.IN_REMARKS AS IN_REMARKS"),
            new Expression("AR.OUT_REMARKS AS OUT_REMARKS"),
            new Expression("AR.EMPLOYEE_ID AS EMPLOYEE_ID"),
            new Expression("E.EMPLOYEE_CODE AS EMPLOYEE_CODE"),
            new Expression("AR.TOTAL_HOUR AS TOTAL_HOUR"),
            new Expression("AR.STATUS AS STATUS"),
            new Expression("LEAVE_STATUS_DESC(AR.STATUS) AS STATUS_DETAIL"),
            new Expression("CASE WHEN ALR.R_A_ID IS NOT NULL THEN ALR.R_A_ID ELSE  RA.RECOMMEND_BY END AS RECOMMENDER_ID"),
            new Expression("CASE WHEN ALA.R_A_ID IS NOT NULL THEN ALA.R_A_ID ELSE  RA.RECOMMEND_BY END AS APPROVER_ID"),
            new Expression("CASE WHEN ALR_E.FULL_NAME IS NOT NULL THEN ALR_E.FULL_NAME ELSE  INITCAP(RECM.FULL_NAME) END AS RECOMMENDER_NAME"),
            new Expression("CASE WHEN ALA_E.FULL_NAME IS NOT NULL THEN ALA_E.FULL_NAME ELSE  INITCAP(APRV.FULL_NAME) END AS APPROVER_NAME"),
            new Expression("REC_APP_ROLE(:id,CASE WHEN ALR.R_A_ID IS NOT NULL THEN ALR.R_A_ID ELSE RA.RECOMMEND_BY END,
                  CASE WHEN ALA.R_A_ID IS NOT NULL THEN ALA.R_A_ID ELSE RA.APPROVED_BY END) AS ROLE"),
            new Expression("REC_APP_ROLE_NAME(:id,CASE WHEN ALR.R_A_ID IS NOT NULL THEN ALR.R_A_ID ELSE RA.RECOMMEND_BY END,
                  CASE WHEN ALA.R_A_ID IS NOT NULL THEN ALA.R_A_ID ELSE RA.APPROVED_BY END) AS YOUR_ROLE"),
                ], true);

        $select->from(['AR' => AttendanceRequestModel::TABLE_NAME])
                ->join(['E' => 'HRIS_EMPLOYEES'], 'E.EMPLOYEE_ID=AR.EMPLOYEE_ID', ["FULL_NAME" => new Expression("INITCAP(E.FULL_NAME)")], "left")
                ->join(['E2' => "HRIS_EMPLOYEES"], "E2.EMPLOYEE_ID=AR.RECOMMENDED_BY", ['RECOMMENDED_BY_NAME' => new Expression("INITCAP(E2.FULL_NAME)")], "left")
                ->join(['E3' => "HRIS_EMPLOYEES"], "E3.EMPLOYEE_ID=AR.APPROVED_BY", ['APPROVED_BY_NAME' => new Expression("INITCAP(E3.FULL_NAME)")], "left")
                ->join(['RA' => "HRIS_RECOMMENDER_APPROVER"], "RA.EMPLOYEE_ID=AR.EMPLOYEE_ID", [], "left")
                ->join(['RECM' => "HRIS_EMPLOYEES"], "RECM.EMPLOYEE_ID=RA.RECOMMEND_BY", [], "left")
                ->join(['APRV' => "HRIS_EMPLOYEES"], "APRV.EMPLOYEE_ID=RA.APPROVED_BY", [], "left")
                ->join(['ALR' => "HRIS_ALTERNATE_R_A"], "ALR.R_A_FLAG='R' AND ALR.EMPLOYEE_ID=ar.EMPLOYEE_ID AND ALR.R_A_ID= :id", [], "left")
                ->join(['ALA' => "HRIS_ALTERNATE_R_A"], "ALA.R_A_FLAG='A' AND ALA.EMPLOYEE_ID=ar.EMPLOYEE_ID AND ALR.R_A_ID= :id", [], "left")
                ->join(['ALR_E' => "HRIS_EMPLOYEES"], "ALR.R_A_ID=ALR_E.EMPLOYEE_ID", [], "left")
                ->join(['ALA_E' => "HRIS_EMPLOYEES"], "ALA.R_A_ID=ALA_E.EMPLOYEE_ID", [], "left");

        $select->where(["(((RA.RECOMMEND_BY= :id OR ALR.R_A_ID= :id) AND AR.STATUS='RQ') OR ((RA.APPROVED_BY= :id OR ALA.R_A_ID= :id) AND AR.STATUS='RC') )"]);

        $select->where([
            "E.STATUS='E'",
            "E.RETIRED_FLAG='N'",
        ]);
        $select->order("E.FIRST_NAME ASC");
        $statement = $sql->prepareStatementForSqlObject($select);
    // echo '<pre>';print_r($boundedParams);die;    
        $result = $statement->execute($boundedParams);
        return $result;
    }

    public function edit(Model $model, $id) {
        $temp = $model->getArrayCopyForDB();
        $this->tableGateway->update($temp, [AttendanceRequestModel::ID => $id]);
        $this->backdateAttendance($id);
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
            new Expression("A.STATUS AS STATUS"),
            new Expression("A.IN_REMARKS AS IN_REMARKS"),
            new Expression("A.OUT_REMARKS AS OUT_REMARKS"),
            new Expression("A.TOTAL_HOUR AS TOTAL_HOUR"),
            new Expression("A.REQUESTED_DT AS REQUESTED_DT"),
            new Expression("A.APPROVED_REMARKS AS APPROVED_REMARKS")
                ], true); 
        $select->from(['A' => AttendanceRequestModel::TABLE_NAME])
                ->join(['E' => 'HRIS_EMPLOYEES'], 'E.EMPLOYEE_ID=A.EMPLOYEE_ID', ["FULL_NAME" => new Expression("INITCAP(E.FULL_NAME)")], "left")
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

    public function backdateAttendance($id) {
        //$boundedParams = [];
        $sql = "
                BEGIN
                  HRIS_BACKDATE_ATTENDANCE({$id});
                END;";

        //$boundedParams['id'] = $id;
        EntityHelper::rawQueryResult($this->adapter, $sql);
        //$this->executeStatement($sql,$boundedParams);
    }

}
