<?php
namespace AttendanceManagement\Repository;

use Application\Helper\EntityHelper;
use Application\Model\Model;
use Application\Repository\HrisRepository;
use SelfService\Model\AttendanceRequestModel;
use Setup\Model\HrEmployees;
use Zend\Db\Sql\Expression;
use Zend\Db\Sql\Sql;

class AttendanceStatusRepository extends HrisRepository {

    public function getAllRequest($status = null, $branchId = null, $employeeId = null) {
        $sql = new Sql($this->adapter);
        $select = $sql->select();
        $select->columns(EntityHelper::getColumnNameArrayWithOracleFns(AttendanceRequestModel::class, NULL, [
                AttendanceRequestModel::REQUESTED_DT,
                AttendanceRequestModel::APPROVED_DT,
                AttendanceRequestModel::ATTENDANCE_DT
                ], [
                AttendanceRequestModel::IN_TIME,
                AttendanceRequestModel::OUT_TIME
                ], NULL, NULL, 'AR'), false);

        $select->from(['AR' => AttendanceRequestModel::TABLE_NAME])
            ->join(['E' => "HRIS_EMPLOYEES"], "E.EMPLOYEE_ID=AR.EMPLOYEE_ID", ['FIRST_NAME' => new Expression('INITCAP(E.FIRST_NAME)'), 'MIDDLE_NAME' => new Expression('INITCAP(E.MIDDLE_NAME)'), 'LAST_NAME' => new Expression('INITCAP(E.LAST_NAME)')], "left")
            ->join(['E1' => "HRIS_EMPLOYEES"], "E1.EMPLOYEE_ID=AR.APPROVED_BY", ['FIRST_NAME1' => new Expression('INITCAP(E1.FIRST_NAME)'), 'MIDDLE_NAME1' => new Expression('INITCAP(E1.MIDDLE_NAME)'), 'LAST_NAME1' => new Expression('INITCAP(E1.LAST_NAME)')], "left");

        $select->where([
            "E.STATUS='E'",
            "E.RETIRED_FLAG='N'"
        ]);
        if ($status != null) {
            $where = "AR.STATUS ='" . $status . "'";
            $select->where([$where]);
        }

        if ($branchId != null) {
            $select->where(["E." . HrEmployees::EMPLOYEE_ID . " IN (SELECT " . HrEmployees::EMPLOYEE_ID . " FROM " . HrEmployees::TABLE_NAME . " WHERE " . HrEmployees::BRANCH_ID . "= $branchId)"]);
        }

        if ($employeeId != null) {
            $select->where(["E." . HrEmployees::EMPLOYEE_ID . " = $employeeId"]);
        }
        $select->order("E.FIRST_NAME ASC");
        $statement = $sql->prepareStatementForSqlObject($select);
        $result = $statement->execute();
        return $result;
    }

    public function fetchById($id) {
        $sql = new Sql($this->adapter);
        $select = $sql->select();
        $select->columns(EntityHelper::getColumnNameArrayWithOracleFns(AttendanceRequestModel::class, NULL, [
                AttendanceRequestModel::REQUESTED_DT,
                AttendanceRequestModel::ATTENDANCE_DT
                ], [
                AttendanceRequestModel::IN_TIME,
                AttendanceRequestModel::OUT_TIME
                ], NULL, NULL, 'A'), false);

        $select->from(['A' => AttendanceRequestModel::TABLE_NAME])
            ->join(['E' => 'HRIS_EMPLOYEES'], 'A.EMPLOYEE_ID=E.EMPLOYEE_ID', ['FIRST_NAME' => new Expression('INITCAP(E.FIRST_NAME)'), 'MIDDLE_NAME' => new Expression('INITCAP(E.MIDDLE_NAME)'), 'LAST_NAME' => new Expression('INITCAP(E.LAST_NAME)')], "left")
            ->join(['E1' => "HRIS_EMPLOYEES"], "E1.EMPLOYEE_ID=AR.APPROVED_BY", ['FIRST_NAME1' => new Expression('INITCAP(E1.FIRST_NAME)'), 'MIDDLE_NAME1' => new Expression('INITCAP(E1.MIDDLE_NAME)'), 'LAST_NAME1' => new Expression('INITCAP(E1.LAST_NAME)')], "left");

        $select->where([AttendanceRequestModel::ID => $id]);
        $statement = $sql->prepareStatementForSqlObject($select);
        $result = $statement->execute();
        return $result->current();
    }

    public function getFilteredRecord($data, $recomApproveId) {
        $employeeId = $data['employeeId'];
        $companyId = $data['companyId'];
        $branchId = $data['branchId'];
        $departmentId = $data['departmentId'];
        $designationId = $data['designationId'];
        $positionId = $data['positionId'];
        $serviceTypeId = $data['serviceTypeId'];
        $serviceEventTypeId = $data['serviceEventTypeId'];
        $employeeTypeId = $data['employeeTypeId'];

        $attendanceRequestStatusId = $data['attendanceRequestStatusId'];
        $fromDate = $data['fromDate'];
        $toDate = $data['toDate'];
        $searchCondition = $this->getSearchConditon($companyId, $branchId, $departmentId, $positionId, $designationId, $serviceTypeId, $serviceEventTypeId, $employeeTypeId, $employeeId);
        $statusCondition = "";
        $fromDateCondition = "";
        $toDateCondition = "";

        if ($attendanceRequestStatusId != -1) {
            $statusCondition = "AND AR.STATUS = '{$attendanceRequestStatusId}'";
        }

        if ($fromDate != null) {
            $fromDateCondition = " AND AR.ATTENDANCE_DT>=TO_DATE('{$fromDate}','DD-MM-YYYY')";
        }

        if ($toDate != null) {
            $toDateCondition = "AND AR.ATTENDANCE_DT<=TO_DATE('{$toDate}','DD-MM-YYYY')";
        }

        $sql = "SELECT DISTINCT AR.ID                                             AS ID,
                  AR.EMPLOYEE_ID                                                  AS EMPLOYEE_ID,
                  E.EMPLOYEE_CODE                                                  AS EMPLOYEE_CODE,
                  INITCAP(TO_CHAR(AR.ATTENDANCE_DT, 'DD-MON-YYYY'))               AS ATTENDANCE_DT_AD,
                  BS_DATE(TO_CHAR(AR.ATTENDANCE_DT, 'DD-MON-YYYY'))               AS ATTENDANCE_DT_BS,
                  INITCAP(TO_CHAR(AR.IN_TIME, 'HH:MI AM'))                        AS IN_TIME,
                  INITCAP(TO_CHAR(AR.OUT_TIME, 'HH:MI AM'))                       AS OUT_TIME,
                  AR.IN_REMARKS                                                   AS IN_REMARKS,
                  AR.OUT_REMARKS                                                  AS OUT_REMARKS,
                  AR.TOTAL_HOUR                                                   AS TOTAL_HOUR,
                  LEAVE_STATUS_DESC(AR.STATUS)                                    AS STATUS,
                  REC_APP_ROLE(U.EMPLOYEE_ID,
                  CASE WHEN ALR.R_A_ID IS NOT NULL THEN ALR.R_A_ID ELSE RA.RECOMMEND_BY END,
                  CASE WHEN ALA.R_A_ID IS NOT NULL THEN ALA.R_A_ID ELSE RA.APPROVED_BY END
                  )      AS ROLE,
                  REC_APP_ROLE_NAME(U.EMPLOYEE_ID,
                  CASE WHEN ALR.R_A_ID IS NOT NULL THEN ALR.R_A_ID ELSE RA.RECOMMEND_BY END,
                  CASE WHEN ALA.R_A_ID IS NOT NULL THEN ALA.R_A_ID ELSE RA.APPROVED_BY END
                  ) AS YOUR_ROLE,
                  AR.APPROVED_BY                                                  AS APPROVED_BY,
                  INITCAP(TO_CHAR(AR.APPROVED_DT, 'DD-MON-YYYY'))                 AS APPROVED_DT,
                  INITCAP(TO_CHAR(AR.REQUESTED_DT, 'DD-MON-YYYY'))                AS REQUESTED_DT_AD,
                  BS_DATE(TO_CHAR(AR.REQUESTED_DT, 'DD-MON-YYYY'))                AS REQUESTED_DT_BS,
                  AR.APPROVED_REMARKS                                             AS APPROVED_REMARKS,
                  AR.RECOMMENDED_BY                                               AS RECOMMENDED_BY,
                  AR.RECOMMENDED_REMARKS                                          AS RECOMMENDED_REMARKS,
                  INITCAP(TO_CHAR(AR.RECOMMENDED_DATE, 'DD-MON-YYYY'))            AS RECOMMENDED_DATE,
                  INITCAP(E.FULL_NAME)                                            AS FULL_NAME,
                  INITCAP(E1.FULL_NAME)                                           AS RECOMMENDED_BY_NAME,
                  INITCAP(E2.FULL_NAME)                                           AS APPROVED_BY_NAME,
                  CASE WHEN ALR.R_A_ID IS NOT NULL THEN ALR.R_A_ID ELSE  RA.RECOMMEND_BY END                                                 AS RECOMMENDER_ID,
                  CASE WHEN ALA.R_A_ID IS NOT NULL THEN ALA.R_A_ID ELSE  RA.RECOMMEND_BY END                                                 AS APPROVER_ID,
                  CASE WHEN ALR_E.FULL_NAME IS NOT NULL THEN ALR_E.FULL_NAME ELSE  INITCAP(RECM.FULL_NAME) END                               AS RECOMMENDER_NAME,
                  CASE WHEN ALA_E.FULL_NAME IS NOT NULL THEN ALA_E.FULL_NAME ELSE  INITCAP(APRV.FULL_NAME) END                               AS APPROVER_NAME
                FROM HRIS_ATTENDANCE_REQUEST AR
                LEFT JOIN HRIS_EMPLOYEES E
                ON E.EMPLOYEE_ID=AR.EMPLOYEE_ID
                LEFT JOIN HRIS_EMPLOYEES E1
                ON E1.EMPLOYEE_ID=AR.RECOMMENDED_BY
                LEFT JOIN HRIS_EMPLOYEES E2
                ON E2.EMPLOYEE_ID=AR.APPROVED_BY
                LEFT JOIN HRIS_RECOMMENDER_APPROVER RA
                ON AR.EMPLOYEE_ID = RA.EMPLOYEE_ID
                LEFT JOIN HRIS_EMPLOYEES RECM
                ON RECM.EMPLOYEE_ID = RA.RECOMMEND_BY
                LEFT JOIN HRIS_EMPLOYEES APRV
                ON APRV.EMPLOYEE_ID = RA.APPROVED_BY
                LEFT JOIN HRIS_ALTERNATE_R_A ALR
                ON(ALR.R_A_FLAG='R' AND ALR.EMPLOYEE_ID=AR.EMPLOYEE_ID AND ALR.R_A_ID={$recomApproveId})
                LEFT JOIN HRIS_ALTERNATE_R_A ALA
                ON(ALA.R_A_FLAG='A' AND ALA.EMPLOYEE_ID=AR.EMPLOYEE_ID AND ALA.R_A_ID={$recomApproveId})
                LEFT JOIN HRIS_EMPLOYEES ALR_E ON(ALR.R_A_ID=ALR_E.EMPLOYEE_ID)
                LEFT JOIN HRIS_EMPLOYEES ALA_E ON(ALA.R_A_ID=ALA_E.EMPLOYEE_ID)
                LEFT JOIN HRIS_EMPLOYEES U
                ON(U.EMPLOYEE_ID   = RA.RECOMMEND_BY
                OR U.EMPLOYEE_ID   =RA.APPROVED_BY
                OR U.EMPLOYEE_ID   =ALR.R_A_ID
                OR U.EMPLOYEE_ID   =ALA.R_A_ID
                )
                WHERE U.EMPLOYEE_ID={$recomApproveId}
                AND E.STATUS       ='E'
                AND E.RETIRED_FLAG ='N'
                AND (E1.STATUS     =
                  CASE
                    WHEN E1.STATUS IS NOT NULL
                    THEN ('E')
                  END
                OR E1.STATUS  IS NULL)
                AND (E2.STATUS =
                  CASE
                    WHEN E2.STATUS IS NOT NULL
                    THEN ('E')
                  END
                OR E2.STATUS    IS NULL)
                AND (RECM.STATUS =
                  CASE
                    WHEN RECM.STATUS IS NOT NULL
                    THEN ('E')
                  END
                OR RECM.STATUS  IS NULL)
                AND (APRV.STATUS =
                  CASE
                    WHEN APRV.STATUS IS NOT NULL
                    THEN ('E')
                  END
                OR APRV.STATUS IS NULL) {$searchCondition} {$statusCondition} {$fromDateCondition} {$toDateCondition}";
                
//                echo $sql;
//                die();
        $statement = $this->adapter->query($sql);
        $result = $statement->execute();
        return $result;
    }

    public function getAttenReqList($data): array {
        $employeeId = $data['employeeId'];
        $companyId = $data['companyId'];
        $branchId = $data['branchId'];
        $departmentId = $data['departmentId'];
        $designationId = $data['designationId'];
        $positionId = $data['positionId'];
        $serviceTypeId = $data['serviceTypeId'];
        $serviceEventTypeId = $data['serviceEventTypeId'];
        $employeeTypeId = $data['employeeTypeId'];
        $functionalTypeId = $data['functionalTypeId'];
        $attendanceRequestStatusId = $data['attendanceRequestStatusId'];
        $fromDate = $data['fromDate'];
        $toDate = $data['toDate'];

        $boundedParams = [];
        $searchCondition = EntityHelper::getSearchConditonBounded($companyId, $branchId, $departmentId, $positionId, $designationId, $serviceTypeId, $serviceEventTypeId, $employeeTypeId, $employeeId, null, null, $functionalTypeId);
        $boundedParams = array_merge($boundedParams, $searchCondition['parameter']);

        $statusCondition = "";
        $fromDateCondition = "";
        $toDateCondition = "";

        if ($attendanceRequestStatusId != -1) {
            $statusCondition = "AND AR.STATUS = :attendanceRequestStatusId";
            $boundedParams['attendanceRequestStatusId'] = $attendanceRequestStatusId;
        }

        if ($fromDate != null) {
            $fromDateCondition = " AND AR.ATTENDANCE_DT>= :fromDate";
            $boundedParams['fromDate'] = $fromDate;
        }

        if ($toDate != null) {
            $toDateCondition = "AND AR.ATTENDANCE_DT<= :toDate";
            $boundedParams['toDate'] = $toDate;
        }

        $sql = "SELECT AR.ID                                           AS ID,
                  AR.EMPLOYEE_ID                                       AS EMPLOYEE_ID,
                  INITCAP(TO_CHAR(AR.ATTENDANCE_DT, 'DD-MON-YYYY'))    AS ATTENDANCE_DT_AD,
                  BS_DATE(TO_CHAR(AR.ATTENDANCE_DT, 'DD-MON-YYYY'))    AS ATTENDANCE_DT_BS,
                  INITCAP(TO_CHAR(AR.IN_TIME, 'HH:MI AM'))             AS IN_TIME,
                  INITCAP(TO_CHAR(AR.OUT_TIME, 'HH:MI AM'))            AS OUT_TIME,
                  AR.IN_REMARKS                                        AS IN_REMARKS,
                  AR.OUT_REMARKS                                       AS OUT_REMARKS,
                  AR.TOTAL_HOUR                                        AS TOTAL_HOUR,
                  LEAVE_STATUS_DESC(AR.STATUS)                         AS STATUS,
                  AR.APPROVED_BY                                       AS APPROVED_BY,
                  INITCAP(TO_CHAR(AR.APPROVED_DT, 'DD-MON-YYYY'))      AS APPROVED_DT,
                  INITCAP(TO_CHAR(AR.REQUESTED_DT, 'DD-MON-YYYY'))     AS REQUESTED_DT_AD,
                  BS_DATE(TO_CHAR(AR.REQUESTED_DT, 'DD-MON-YYYY'))     AS REQUESTED_DT_BS,
                  AR.APPROVED_REMARKS                                  AS APPROVED_REMARKS,
                  AR.RECOMMENDED_BY                                    AS RECOMMENDED_BY,
                  AR.RECOMMENDED_REMARKS                               AS RECOMMENDED_REMARKS,
                  INITCAP(TO_CHAR(AR.RECOMMENDED_DATE, 'DD-MON-YYYY')) AS RECOMMENDED_DATE,
                  E.EMPLOYEE_CODE                                      AS EMPLOYEE_CODE,                  
                  INITCAP(E.FULL_NAME)                                 AS FULL_NAME,
                  INITCAP(E1.FULL_NAME)                                AS RECOMMENDED_BY_NAME,
                  INITCAP(E2.FULL_NAME)                                AS APPROVED_BY_NAME,
                  RA.RECOMMEND_BY                                      AS RECOMMENDER_ID,
                  RA.APPROVED_BY                                       AS APPROVER_ID,
                  INITCAP(RECM.FULL_NAME)                              AS RECOMMENDER_NAME,
                  INITCAP(APRV.FULL_NAME)                              AS APPROVER_NAME
                FROM HRIS_ATTENDANCE_REQUEST AR
                LEFT OUTER JOIN HRIS_EMPLOYEES E
                ON E.EMPLOYEE_ID=AR.EMPLOYEE_ID
                LEFT OUTER JOIN HRIS_EMPLOYEES E1
                ON E1.EMPLOYEE_ID=AR.RECOMMENDED_BY
                LEFT OUTER JOIN HRIS_EMPLOYEES E2
                ON E2.EMPLOYEE_ID=AR.APPROVED_BY
                LEFT OUTER JOIN HRIS_RECOMMENDER_APPROVER RA
                ON AR.EMPLOYEE_ID = RA.EMPLOYEE_ID
                LEFT OUTER JOIN HRIS_EMPLOYEES RECM
                ON RECM.EMPLOYEE_ID = RA.RECOMMEND_BY
                LEFT OUTER JOIN HRIS_EMPLOYEES APRV
                ON APRV.EMPLOYEE_ID = RA.APPROVED_BY
                WHERE E.STATUS      ='E'
                AND E.RETIRED_FLAG  ='N'
                AND (E1.STATUS      =
                  CASE
                    WHEN E1.STATUS IS NOT NULL
                    THEN ('E')
                  END
                OR E1.STATUS  IS NULL)
                AND (E2.STATUS =
                  CASE
                    WHEN E2.STATUS IS NOT NULL
                    THEN ('E')
                  END
                OR E2.STATUS    IS NULL)
                AND (RECM.STATUS =
                  CASE
                    WHEN RECM.STATUS IS NOT NULL
                    THEN ('E')
                  END
                OR RECM.STATUS  IS NULL)
                AND (APRV.STATUS =
                  CASE
                    WHEN APRV.STATUS IS NOT NULL
                    THEN ('E')
                  END
                OR APRV.STATUS IS NULL) {$searchCondition['sql']} {$statusCondition} {$fromDateCondition} {$toDateCondition}";
        $finalQuery = $this->getPrefReportQuery($sql);
        return $this->rawQuery($finalQuery, $boundedParams);
    }
}
