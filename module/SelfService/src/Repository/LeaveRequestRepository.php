<?php

namespace SelfService\Repository;

use Application\Helper\EntityHelper;
use Application\Helper\Helper;
use Application\Model\Model;
use Application\Repository\RepositoryInterface;
use LeaveManagement\Model\LeaveApply;
use LeaveManagement\Model\LeaveAssign;
use Zend\Db\Adapter\AdapterInterface;
use Zend\Db\Sql\Expression;
use Zend\Db\Sql\Sql;
use Zend\Db\TableGateway\TableGateway;

class LeaveRequestRepository implements RepositoryInterface
{

    private $tableGateway;
    private $adapter;

    public function __construct(AdapterInterface $adapter)
    {
        $this->tableGateway = new TableGateway(LeaveApply::TABLE_NAME, $adapter);
        $this->tableGatewayLeaveAssign = new TableGateway(LeaveAssign::TABLE_NAME, $adapter);
        $this->adapter = $adapter;
    }

    public function pushFileLink($data)
    {
        $fileName = $data['fileName'];
        $fileInDir = $data['filePath'];
        $sql = "INSERT INTO HRIS_LEAVE_FILES(FILE_ID, FILE_NAME, FILE_IN_DIR_NAME, LEAVE_ID) VALUES((SELECT MAX(FILE_ID)+1 FROM HRIS_LEAVE_FILES), '$fileName', '$fileInDir', null)";
        $statement = $this->adapter->query($sql);
        $statement->execute();
        $sql = "SELECT * FROM HRIS_LEAVE_FILES WHERE FILE_ID IN (SELECT MAX(FILE_ID) AS FILE_ID FROM HRIS_LEAVE_FILES)";
        $statement = $this->adapter->query($sql);
        return Helper::extractDbData($statement->execute());
    }

    public function linkLeaveWithFiles()
    {
        if (!empty($_POST['fileUploadList'])) {
            $filesList = $_POST['fileUploadList'];
            $filesList = implode(',', $filesList);

            $sql = "UPDATE HRIS_LEAVE_FILES SET LEAVE_ID = (SELECT MAX(ID) FROM HRIS_EMPLOYEE_LEAVE_REQUEST) 
                    WHERE FILE_ID IN($filesList)";
            $statement = $this->adapter->query($sql);
            $statement->execute();
        }
    }

    public function add(Model $model)
    {
        $this->tableGateway->insert($model->getArrayCopyForDB());
        $this->linkLeaveWithFiles();
    }

    public function edit(Model $model, $id)
    {
        $currentDate = Helper::getcurrentExpressionDate();
        $this->tableGateway->update([LeaveApply::MODIFIED_DT => $currentDate], [LeaveApply::ID => $id]);
    }


    public function fetchAll()
    {
        // TODO: Implement fetchAll() method.
    }

    //to get the all applied leave request list
    public function selectAll($employeeId)
    {

        $sql = new Sql($this->adapter);
        $select = $sql->select();
        $select->columns([
            new Expression("INITCAP(TO_CHAR(LA.START_DATE, 'DD-MON-YYYY')) AS FROM_DATE"),
            new Expression("INITCAP(TO_CHAR(LA.END_DATE, 'DD-MON-YYYY')) AS TO_DATE"),
            new Expression("LA.STATUS AS STATUS"),
            new Expression("INITCAP(TO_CHAR(LA.REQUESTED_DT, 'DD-MON-YYYY')) AS REQUESTED_DT"),
            new Expression("LA.NO_OF_DAYS AS NO_OF_DAYS"),
            new Expression("LA.ID AS ID"),
        ], true);

        $select->from(['LA' => LeaveApply::TABLE_NAME])
            ->join(['E' => "HRIS_EMPLOYEES"], "E.EMPLOYEE_ID=LA.EMPLOYEE_ID", ["FIRST_NAME" => new Expression("INITCAP(E.FIRST_NAME)"), "MIDDLE_NAME" => new Expression("INITCAP(E.MIDDLE_NAME)"), "LAST_NAME" => new Expression("INITCAP(E.LAST_NAME)")])
            ->join(['L' => 'HRIS_LEAVE_MASTER_SETUP'], "L.LEAVE_ID=LA.LEAVE_ID", ['LEAVE_CODE', 'LEAVE_ENAME' => new Expression("INITCAP(L.LEAVE_ENAME)")]);

        $select->where([
            "L.STATUS='E'",
            "E.EMPLOYEE_ID=" . $employeeId
        ]);
        $select->order("LA.REQUESTED_DT DESC");
        $statement = $sql->prepareStatementForSqlObject($select);
        $result = $statement->execute();
        return $result;
    }

    //to get the leave detail based on assigned employee id
    public function getLeaveDetail($employeeId, $leaveId, $startDate = null)
    {
        EntityHelper::rawQueryResult($this->adapter, "
        BEGIN
            Declare
            v_is_monthly char(1);
            v_leave_id decimal(7):=$leaveId;
            v_employee_id decimal(7) :=$employeeId;
            begin
            select is_monthly into v_is_monthly from hris_leave_master_setup where leave_id = v_leave_id;
            if(v_is_monthly='Y') then
                hris_recalc_monthly_leaves(v_employee_id, v_leave_id);
            else
                hris_recalculate_leave(v_employee_id, v_leave_id);    
            end if;
            end;
        END;
        ");
        $date = "TRUNC(SYSDATE)";
        if ($startDate != null) {
            $date = "TO_DATE('{$startDate}','DD-MON-YYYY')";
        }
        // $sql = "SELECT LA.EMPLOYEE_ID       AS EMPLOYEE_ID,
        //           LA.BALANCE - 
        //         (select 
        //         nvl(sum(
        //         case when half_day in ('F','S')
        //         then
        //         NO_OF_DAYS/2
        //         else
        //         no_of_days
        //         end
        //         ),0)
        //         from hris_employee_leave_request where status in ('RQ','RC') 
        //         and  leave_id={$leaveId} and employee_id={$employeeId})                  AS BALANCE,
        //           LA.FISCAL_YEAR            AS FISCAL_YEAR,
        //           LA.FISCAL_YEAR_MONTH_NO   AS FISCAL_YEAR_MONTH_NO,
        //           LA.LEAVE_ID               AS LEAVE_ID,
        //           L.DOCUMENT_REQUIRED       AS DOCUMENT_REQUIRED,
        //           L.DOCS_COMP_DAYS          AS DOCS_COMP_DAYS,
        //           L.LEAVE_CODE              AS LEAVE_CODE,
        //           INITCAP(L.LEAVE_ENAME)    AS LEAVE_ENAME,
        //           L.ALLOW_HALFDAY           AS ALLOW_HALFDAY,
        //           L.ALLOW_GRACE_LEAVE       AS ALLOW_GRACE_LEAVE,
        //            CASE WHEN L.IS_SUBSTITUTE_MANDATORY='Y' AND LBP.BYPASS='N' THEN 
        //           'Y'
        //           ELSE
        //           'N'
        //           END AS IS_SUBSTITUTE_MANDATORY,
        //           L.ENABLE_SUBSTITUTE       AS ENABLE_SUBSTITUTE
        //           ,L.IS_SUBSTITUTE
        //           ,L.APPLY_LIMIT
        //         FROM HRIS_EMPLOYEE_LEAVE_ASSIGN LA
        //         INNER JOIN HRIS_LEAVE_MASTER_SETUP L
        //         ON L.LEAVE_ID                =LA.LEAVE_ID
        //          LEFT JOIN (
        //         SELECT CASE NVL(MIN(LEAVE_ID),'0') WHEN 0 
        //         THEN 'N'
        //         ELSE 'Y' 
        //         END AS BYPASS FROM HRIS_SUB_MAN_BYPASS 
        //         WHERE LEAVE_ID={$leaveId} AND EMPLOYEE_ID={$employeeId}
        //         ) LBP ON (1=1)
        //         LEFT JOIN (SELECT * FROM HRIS_LEAVE_YEARS WHERE 
        //          TRUNC(SYSDATE)  BETWEEN START_DATE AND END_DATE) LY  
        //          ON(1=1)
        //         WHERE L.STATUS               ='E'
        //         AND LA.EMPLOYEE_ID           ={$employeeId}
        //         AND L.LEAVE_ID               ={$leaveId}
        //         AND (LA.FISCAL_YEAR_MONTH_NO =
        //           CASE
        //             WHEN (L.IS_MONTHLY='Y')
        //             THEN
        //               (SELECT LEAVE_YEAR_MONTH_NO
        //               FROM HRIS_LEAVE_MONTH_CODE
        //               WHERE {$date} BETWEEN FROM_DATE AND TO_DATE
        //               )
        //           END
        //         OR LA.FISCAL_YEAR_MONTH_NO IS NULL ) 
        //         ";

        ///////////cnange by aa/////////////////
        // $sql = "select employee_id,
        // case when (balance + leave_added) > max_balance then 
        //  max_balance 
        // else balance + leave_added end as balance,
        // fiscal_year,fiscal_year_month_no, leave_id, document_required, docs_comp_days, leave_code,leave_ename,
        // allow_halfday, allow_grace_leave,is_substitute_mandatory,enable_substitute,is_substitute,apply_limit
        // from (SELECT LA.EMPLOYEE_ID       AS EMPLOYEE_ID,
        // LA.BALANCE - 
        // (select 
        // nvl(sum(
        // case when half_day in ('F','S')
        // then
        // NO_OF_DAYS/2
        // else
        // no_of_days
        // end
        // ),0)
        // from hris_employee_leave_request where status in ('RQ','RC') 
        // and  leave_id={$leaveId} and employee_id={$employeeId})                  AS BALANCE,
        // CASE
        // WHEN (L.IS_MONTHLY='Y')
        // THEN (select max(total_days) from hris_employee_leave_assign where employee_id ={$employeeId} and leave_id = {$leaveId}) - LA.total_days else 0 end as leave_added,
        // LA.total_days as max_balance,
        // LA.FISCAL_YEAR            AS FISCAL_YEAR,
        // LA.FISCAL_YEAR_MONTH_NO   AS FISCAL_YEAR_MONTH_NO,
        // LA.LEAVE_ID               AS LEAVE_ID,
        // L.DOCUMENT_REQUIRED       AS DOCUMENT_REQUIRED,
        // L.DOCS_COMP_DAYS          AS DOCS_COMP_DAYS,
        // L.LEAVE_CODE              AS LEAVE_CODE,
        // INITCAP(L.LEAVE_ENAME)    AS LEAVE_ENAME,
        // L.ALLOW_HALFDAY           AS ALLOW_HALFDAY,
        // L.ALLOW_GRACE_LEAVE       AS ALLOW_GRACE_LEAVE,
        //  CASE WHEN L.IS_SUBSTITUTE_MANDATORY='Y' AND LBP.BYPASS='N' THEN 
        // 'Y'
        // ELSE
        // 'N'
        // END AS IS_SUBSTITUTE_MANDATORY,
        // L.ENABLE_SUBSTITUTE       AS ENABLE_SUBSTITUTE
        // ,L.IS_SUBSTITUTE
        // ,L.APPLY_LIMIT
        // FROM HRIS_EMPLOYEE_LEAVE_ASSIGN LA
        // INNER JOIN HRIS_LEAVE_MASTER_SETUP L
        // ON L.LEAVE_ID                =LA.LEAVE_ID
        // LEFT JOIN (
        // SELECT CASE NVL(MIN(LEAVE_ID),'0') WHEN 0 
        // THEN 'N'
        // ELSE 'Y' 
        // END AS BYPASS FROM HRIS_SUB_MAN_BYPASS 
        // WHERE LEAVE_ID={$leaveId} AND EMPLOYEE_ID={$employeeId}
        // ) LBP ON (1=1)
        // LEFT JOIN (SELECT * FROM HRIS_LEAVE_YEARS WHERE 
        // TRUNC(SYSDATE)  BETWEEN START_DATE AND END_DATE) LY  
        // ON(1=1)
        // WHERE L.STATUS               ='E'
        // AND LA.EMPLOYEE_ID           ={$employeeId}
        // AND L.LEAVE_ID               ={$leaveId}
        // AND (LA.FISCAL_YEAR_MONTH_NO =
        // CASE
        //   WHEN (L.IS_MONTHLY='Y')
        //   THEN
        //     (SELECT LEAVE_YEAR_MONTH_NO
        //     FROM HRIS_LEAVE_MONTH_CODE
        //     WHERE {$date} BETWEEN FROM_DATE AND TO_DATE
        //     )
        // END
        // OR LA.FISCAL_YEAR_MONTH_NO IS NULL ) )
        // ";

        $sql = "SELECT
employee_id,
CASE
    WHEN ( total_days - leave_taken_till_this_month ) > max_balance THEN
            CASE
                WHEN max_balance < 0 THEN
                    0
                ELSE
                    max_balance
                -- ACTUAL_BALANCE
            END
    ELSE
        CASE
            WHEN ( total_days + previous_balance - leave_taken_till_this_month ) < 0 THEN
                    0
            ELSE
                ( total_days - leave_taken_till_this_month + previous_balance )
            -- ACTUAL_BALANCE
        END
END AS balance,
fiscal_year,
fiscal_year_month_no,
leave_id,
document_required,
docs_comp_days,
leave_code,
leave_ename,
allow_halfday,
allow_grace_leave,
is_substitute_mandatory,
enable_substitute,
is_substitute,
is_sub_leave,
apply_limit
from (SELECT LA.EMPLOYEE_ID       AS EMPLOYEE_ID,
LA.BALANCE - 
(select 
nvl(sum(
case when half_day in ('F','S')
then
NO_OF_DAYS/2
else
no_of_days
end
),0)
from hris_employee_leave_request where status in ('RQ','RC') 
and  leave_id={$leaveId} and employee_id={$employeeId})                  AS ACTUAL_BALANCE,
(SELECT
            nvl(SUM(
                CASE
                    WHEN half_day IN('F', 'S') THEN
                        no_of_days / 2
                    ELSE
                        no_of_days
                END
            ),
                0)
        FROM
            hris_employee_leave_request
        WHERE
            status IN ( 'AP','RQ','RC')
            AND leave_id = {$leaveId}
            AND employee_id = {$employeeId}
            and start_date <= (select to_date from hris_leave_month_code where {$date} between from_date and to_date))
            AS leave_taken_till_this_month,
(SELECT
                MAX(la.balance)
            FROM
                hris_employee_leave_assign la
                left join hris_leave_master_setup l on (l.leave_id=la.leave_id)
            WHERE
                    la.employee_id =  {$employeeId}
                AND la.leave_id = {$leaveId}
                AND (la.fiscal_year_month_no <= (
CASE
    WHEN l.is_monthly = 'Y' THEN
        (
            SELECT
                leave_year_month_no
            FROM
                hris_leave_month_code
            WHERE
                CASE
                    WHEN trunc(TO_DATE(trunc(sysdate))) <= trunc(sysdate) THEN
                        trunc(sysdate)
                    ELSE
                        trunc(TO_DATE(trunc(sysdate)))
                END BETWEEN from_date AND to_date
        )
END 
) OR la.fiscal_year_month_no IS NULL)) 
as max_balance,
LA.total_days as total_days,
LA.previous_year_bal    AS previous_balance,
LA.FISCAL_YEAR            AS FISCAL_YEAR,
LA.FISCAL_YEAR_MONTH_NO   AS FISCAL_YEAR_MONTH_NO,
LA.LEAVE_ID               AS LEAVE_ID,
L.DOCUMENT_REQUIRED       AS DOCUMENT_REQUIRED,
L.DOCS_COMP_DAYS          AS DOCS_COMP_DAYS,
L.LEAVE_CODE              AS LEAVE_CODE,
INITCAP(L.LEAVE_ENAME)    AS LEAVE_ENAME,
L.ALLOW_HALFDAY           AS ALLOW_HALFDAY,
L.ALLOW_GRACE_LEAVE       AS ALLOW_GRACE_LEAVE,
CASE WHEN L.IS_SUBSTITUTE_MANDATORY='Y' AND LBP.BYPASS='N' THEN 
'Y'
ELSE
'N'
END AS IS_SUBSTITUTE_MANDATORY,
L.ENABLE_SUBSTITUTE       AS ENABLE_SUBSTITUTE
,L.IS_SUBSTITUTE
,L.APPLY_LIMIT
,L.IS_SUB_LEAVE
FROM HRIS_EMPLOYEE_LEAVE_ASSIGN LA
INNER JOIN HRIS_LEAVE_MASTER_SETUP L
ON L.LEAVE_ID                =LA.LEAVE_ID
LEFT JOIN (
SELECT CASE NVL(MIN(LEAVE_ID),'0') WHEN 0 
THEN 'N'
ELSE 'Y' 
END AS BYPASS FROM HRIS_SUB_MAN_BYPASS 
WHERE LEAVE_ID={$leaveId} AND EMPLOYEE_ID={$employeeId}
) LBP ON (1=1)
LEFT JOIN (SELECT * FROM HRIS_LEAVE_YEARS WHERE 
TRUNC(SYSDATE)  BETWEEN START_DATE AND END_DATE) LY  
ON(1=1)
WHERE L.STATUS               ='E'
AND LA.EMPLOYEE_ID           ={$employeeId}
AND L.LEAVE_ID               ={$leaveId}
AND (LA.FISCAL_YEAR_MONTH_NO =
CASE
WHEN (L.IS_MONTHLY='Y')
THEN
(SELECT LEAVE_YEAR_MONTH_NO
FROM HRIS_LEAVE_MONTH_CODE
WHERE {$date} BETWEEN FROM_DATE AND TO_DATE
)
end OR la.fiscal_year_month_no IS NULL ) )";
        $statement = $this->adapter->query($sql);
        return $statement->execute()->current();
    }

    //to get the leave list based on assigned employee id for select option
    public function getLeaveList($employeeId, $selfRequest = 'N')
    {
        $selfRequestCondition = "1=1";
        if ($selfRequest == 'Y') {
            $selfRequestCondition = "L.HR_ONLY = 'N'";
        }
        $sql = new Sql($this->adapter);
        $select = $sql->select();
        $select->from(['LA' => LeaveAssign::TABLE_NAME])
            ->join(['L' => 'HRIS_LEAVE_MASTER_SETUP'], "L.LEAVE_ID=LA.LEAVE_ID", ['LEAVE_CODE', 'LEAVE_ENAME' => new Expression("INITCAP(L.LEAVE_ENAME)")]);
        $select->where([
            "L.STATUS='E'",
            "LA.EMPLOYEE_ID" => $employeeId,
            $selfRequestCondition
        ]);

        $statement = $sql->prepareStatementForSqlObject($select);

        $resultset = $statement->execute();

        $entitiesArray = array();
        foreach ($resultset as $result) {
            $entitiesArray[$result['LEAVE_ID']] = $result['LEAVE_ENAME'];
        }
        //echo '<pre>'; print_r( $resultset); die;
        return $entitiesArray;
    }

    public function fetchById($id)
    {

        // TODO: Implement fetchById() method.

        $sql = new Sql($this->adapter);
        $select = $sql->select();
        $select->from(LeaveApply::TABLE_NAME);
        $select->where([
            "ID=" . $id
        ]);
        $statement = $sql->prepareStatementForSqlObject($select);
        $resultset = $statement->execute();
        return $resultset->current();
    }

    public function delete($id)
    {
        $leaveStatus = $this->getLeaveFrontOrBack($id);
        $currentDate = Helper::getcurrentExpressionDate();
        $leaveStatusAction = $leaveStatus['CANCEL_ACTION'];

        $this->tableGateway->update([LeaveApply::STATUS => $leaveStatusAction, LeaveApply::MODIFIED_DT => $currentDate], [LeaveApply::ID => $id]);
        $boundedParameter = [];
        $boundedParameter['id'] = $id;
        EntityHelper::rawQueryResult($this->adapter, "
                   DECLARE
                      V_ID HRIS_EMPLOYEE_LEAVE_REQUEST.ID%TYPE;
                      V_STATUS HRIS_EMPLOYEE_LEAVE_REQUEST.STATUS%TYPE;
                      V_START_DATE HRIS_EMPLOYEE_LEAVE_REQUEST.START_DATE%TYPE;
                      V_END_DATE HRIS_EMPLOYEE_LEAVE_REQUEST.END_DATE%TYPE;
                      V_EMPLOYEE_ID HRIS_EMPLOYEE_LEAVE_REQUEST.EMPLOYEE_ID%TYPE;
                    BEGIN
                      SELECT ID,
                        STATUS,
                        START_DATE,
                        END_DATE,
                        EMPLOYEE_ID
                      INTO V_ID,
                        V_STATUS,
                        V_START_DATE,
                        V_END_DATE,
                        V_EMPLOYEE_ID
                      FROM HRIS_EMPLOYEE_LEAVE_REQUEST
                      WHERE ID                                    = :id;
                      IF(V_STATUS IN ('AP','C') AND V_START_DATE <=TRUNC(SYSDATE)) THEN
                        HRIS_REATTENDANCE(V_START_DATE,V_EMPLOYEE_ID,V_END_DATE);
                      END IF;
                    END;
    ", $boundedParameter);
    }

    public function checkEmployeeLeave($employeeId, $date)
    {
        $sql = new Sql($this->adapter);
        $select = $sql->select();
        $select->from(['L' => LeaveApply::TABLE_NAME]);
        $select->where(["L." . LeaveApply::EMPLOYEE_ID . "=$employeeId"]);
        $select->where([$date->getExpression() . " BETWEEN " . "L." . LeaveApply::START_DATE . " AND L." . LeaveApply::END_DATE]);
        $select->where(['L.' . LeaveApply::STATUS . " = " . "'AP'"]);
        $statement = $sql->prepareStatementForSqlObject($select);
        $result = $statement->execute();
        return $result->current();
    }

    public function getfilterRecords($data)
    {
        $employeeId = $data['employeeId'];
        $leaveId = $data['leaveId'];
        $leaveRequestStatusId = $data['leaveRequestStatusId'];
        $fromDate = $data['fromDate'];
        $toDate = $data['toDate'];
        $leaveYear = $data['leaveYear'];

        $sql = new Sql($this->adapter);
        $select = $sql->select();
        $select->columns([
            new Expression("LA.ID AS ID"),
            new Expression("LA.EMPLOYEE_ID AS EMPLOYEE_ID"),
            new Expression("LA.LEAVE_ID AS LEAVE_ID"),
            new Expression("LA.HARDCOPY_SIGNED_FLAG AS HARDCOPY_SIGNED_FLAG"),
            new Expression("INITCAP(TO_CHAR(LA.START_DATE, 'DD-MON-YYYY')) AS FROM_DATE_AD"),
            new Expression("BS_DATE(TO_CHAR(LA.START_DATE, 'DD-MON-YYYY')) AS FROM_DATE_BS"),
            new Expression("INITCAP(TO_CHAR(LA.END_DATE, 'DD-MON-YYYY')) AS TO_DATE_AD"),
            new Expression("BS_DATE(TO_CHAR(LA.END_DATE, 'DD-MON-YYYY')) AS TO_DATE_BS"),
            new Expression("LA.HALF_DAY AS HALF_DAY"),
            new Expression("(CASE WHEN (LA.HALF_DAY IS NULL OR LA.HALF_DAY = 'N') THEN 'Full Day' WHEN (LA.HALF_DAY = 'F') THEN 'First Half' ELSE 'Second Half' END) AS HALF_DAY_DETAIL"),
            new Expression("LA.GRACE_PERIOD AS GRACE_PERIOD"),
            new Expression("(CASE WHEN LA.GRACE_PERIOD = 'E' THEN 'Early' WHEN LA.GRACE_PERIOD = 'L' THEN 'Late' ELSE '-' END) AS GRACE_PERIOD_DETAIL"),
            new Expression("(CASE WHEN (LA.HALF_DAY IS NULL OR  LA.HALF_DAY = 'N') THEN LA.NO_OF_DAYS else LA.NO_OF_DAYS/2 END) AS NO_OF_DAYS"),
            new Expression("INITCAP(TO_CHAR(LA.REQUESTED_DT, 'DD-MON-YYYY')) AS REQUESTED_DT_AD"),
            new Expression("BS_DATE(TO_CHAR(LA.REQUESTED_DT, 'DD-MON-YYYY')) AS REQUESTED_DT_BS"),
            new Expression("LA.REMARKS AS REMARKS"),
            new Expression("LA.STATUS AS STATUS"),
            new Expression("LEAVE_STATUS_DESC(LA.STATUS) AS STATUS_DETAIL"),
            new Expression("LA.RECOMMENDED_BY AS RECOMMENDED_BY"),
            new Expression("INITCAP(TO_CHAR(LA.RECOMMENDED_DT, 'DD-MON-YYYY')) AS RECOMMENDED_DT"),
            new Expression("LA.RECOMMENDED_REMARKS AS RECOMMENDED_REMARKS"),
            new Expression("LA.APPROVED_BY AS APPROVED_BY"),
            new Expression("INITCAP(TO_CHAR(LA.APPROVED_DT, 'DD-MON-YYYY')) AS APPROVED_DT"),
            new Expression("LA.APPROVED_REMARKS AS APPROVED_REMARKS"),
            new Expression("(CASE WHEN LA.STATUS = 'XX' THEN 'Y' ELSE 'N' END) AS ALLOW_EDIT"),
            new Expression("(CASE WHEN LA.STATUS IN ('RQ','RC','AP') THEN 'Y' ELSE 'N' END) AS ALLOW_DELETE"),
        ], true);

        $select->from(['LA' => LeaveApply::TABLE_NAME])
            ->join(['L' => 'HRIS_LEAVE_MASTER_SETUP'], "L.LEAVE_ID=LA.LEAVE_ID", ['LEAVE_CODE', 'LEAVE_ENAME' => new Expression("CASE WHEN SUB_REF_ID IS NULL THEN 
INITCAP(L.LEAVE_ENAME)
ELSE
INITCAP(L.LEAVE_ENAME)||'('||SLR.SUB_NAME||')'
END")])
            ->join(['E' => 'HRIS_EMPLOYEES'], 'LA.EMPLOYEE_ID=E.EMPLOYEE_ID', ["FULL_NAME" => new Expression("INITCAP(E.FULL_NAME)")], "left")
            ->join(['E2' => "HRIS_EMPLOYEES"], "E2.EMPLOYEE_ID=LA.RECOMMENDED_BY", ['RECOMMENDED_BY_NAME' => new Expression("INITCAP(E2.FULL_NAME)")], "left")
            ->join(['E3' => "HRIS_EMPLOYEES"], "E3.EMPLOYEE_ID=LA.APPROVED_BY", ['APPROVED_BY_NAME' => new Expression("INITCAP(E3.FULL_NAME)")], "left")
            ->join(['RA' => "HRIS_RECOMMENDER_APPROVER"], "RA.EMPLOYEE_ID=LA.EMPLOYEE_ID", ['RECOMMENDER_ID' => 'RECOMMEND_BY', 'APPROVER_ID' => 'APPROVED_BY'], "left")
            ->join(['RECM' => "HRIS_EMPLOYEES"], "RECM.EMPLOYEE_ID=RA.RECOMMEND_BY", ['RECOMMENDER_NAME' => new Expression("INITCAP(RECM.FULL_NAME)")], "left")
            ->join(['APRV' => "HRIS_EMPLOYEES"], "APRV.EMPLOYEE_ID=RA.APPROVED_BY", ['APPROVER_NAME' => new Expression("INITCAP(APRV.FULL_NAME)")], "left")
            ->join(['SLR' => "(SELECT 
WOD_ID AS ID
,LA.EMPLOYEE_ID
,NO_OF_DAYS
,WD.FROM_DATE||' - '||WD.TO_DATE AS SUB_NAME
from 
HRIS_EMPLOYEE_LEAVE_ADDITION LA
JOIN Hris_Employee_Work_Dayoff WD ON (LA.WOD_ID=WD.ID)
UNION
SELECT 
WOH_ID AS ID
,LA.EMPLOYEE_ID
,NO_OF_DAYS
,H.Holiday_Ename||'-'||WH.FROM_DATE||' - '||WH.TO_DATE AS SUB_NAME
from 
HRIS_EMPLOYEE_LEAVE_ADDITION LA
JOIN Hris_Employee_Work_Holiday WH ON (LA.WOH_ID=WH.ID)
LEFT JOIN Hris_Holiday_Master_Setup H ON (WH.HOLIDAY_ID=H.HOLIDAY_ID))"], "SLR.ID=LA.SUB_REF_ID AND SLR.EMPLOYEE_ID=LA.EMPLOYEE_ID", [], "left");

        if ($leaveYear != null) {
            $select->where([
                "(( L.STATUS ='E' OR L.OLD_LEAVE='Y' )",
                "L.LEAVE_YEAR" => $leaveYear,
                "1=1)"
            ]);
        } else {
            $select->where([
                "L.STATUS='E'"
            ]);
        }

        $select->where([
            "E.EMPLOYEE_ID" =>  $employeeId
        ]);

        if ($leaveId != null && $leaveId != -1) {
            $select->where(["LA.LEAVE_ID" => $leaveId]);
        }
        if ($leaveRequestStatusId != -1) {
            $select->where(["LA.STATUS" => $leaveRequestStatusId]);
        }
        if ($leaveRequestStatusId != 'C') {
            $select->where([
                "(TRUNC(SYSDATE)- LA.REQUESTED_DT) < (
                      CASE
                        WHEN LA.STATUS = 'C'
                        THEN 20
                        ELSE 1000
                      END)"
            ]);
        }

        if ($fromDate != null) {
            $select->where->greaterThanOrEqualTo("LA.START_DATE", $fromDate);
        }
        if ($toDate != null) {
            $select->where->lessThanOrEqualTo("LA.END_DATE", $toDate);
        }
        $select->order("LA.START_DATE DESC");
        $statement = $sql->prepareStatementForSqlObject($select);
        $result = $statement->execute();
        return $result;
    }

    public function fetchAvailableDays($fromDate, $toDate, $employeeId, $halfDay, $leaveId)
    {
        $boundedParameter = [];
        $boundedParameter['fromDate'] = $fromDate;
        $boundedParameter['toDate'] = $toDate;
        $boundedParameter['employeeId'] = $employeeId;
        $boundedParameter['leaveId'] = $leaveId;
        $boundedParameter['halfDay'] = $halfDay;
        $rawResult = EntityHelper::rawQueryResult($this->adapter, "SELECT HRIS_AVAILABLE_LEAVE_DAYS(:fromDate,:toDate,:employeeId,:leaveId,:halfDay) AS AVAILABLE_DAYS FROM DUAL", $boundedParameter);
        return $rawResult->current();
    }

    public function validateLeaveRequest($fromDate, $toDate, $employeeId)
    {
        $boundedParameter = [];
        $boundedParameter['fromDate'] = $fromDate;
        $boundedParameter['toDate'] = $toDate;
        $boundedParameter['employeeId'] = $employeeId;
        $rawResult = EntityHelper::rawQueryResult($this->adapter, "SELECT HRIS_VALIDATE_LEAVE_REQUEST(:fromDate,:toDate,:employeeId) AS ERROR FROM DUAL", $boundedParameter);
        return $rawResult->current();
    }

    public function fetchByEmpId($employeeId)
    {
        $sql = new Sql($this->adapter);
        $select = $sql->select();
        $select->from(['L' => LeaveApply::TABLE_NAME]);
        $select->where(["L." . LeaveApply::EMPLOYEE_ID . "=$employeeId"]);
        $statement = $sql->prepareStatementForSqlObject($select);
        $result = $statement->execute();
        return $result;
    }
    public function getLeaveFrontOrBack($id)
    {
        $boundedParameter = [];
        $boundedParameter['id'] = $id;
        $sql = "SELECT START_DATE,TRUNC(SYSDATE) AS CURDATE,
            CASE WHEN
            STATUS IN ('RQ','RC') THEN 'NA'
            ELSE STATUS
            END
            AS LEAVE_STATUS,
            START_DATE-TRUNC(SYSDATE) AS DIFF,
                CASE  WHEN 
                (START_DATE-TRUNC(SYSDATE))>0
                THEN
                'FD'
                ELSE
                'BD'
                END AS DATE_STATUS,
                CASE 
                WHEN STATUS IN ('RQ','RC') THEN
                'C'
                WHEN STATUS IN ('AP','CR') and (START_DATE-TRUNC(SYSDATE)<=0) THEN
                'CP'
                 WHEN STATUS IN ('AP','CR') and (START_DATE-TRUNC(SYSDATE)>0) THEN
                'C'
                ELSE
                STATUS
                END AS CANCEL_ACTION
                FROM HRIS_EMPLOYEE_LEAVE_REQUEST WHERE ID=:id";
        $statement = $this->adapter->query($sql);
        return $statement->execute($boundedParameter)->current();
    }

    public function getSubstituteList($leaveId, $employeeId, $maxSubDays = 500)
    {
        $boundedParameter = [];
        $boundedParameter['leaveId'] = $leaveId;
        $boundedParameter['employeeId'] = $employeeId;
        $boundedParameter['maxSubDays'] = $maxSubDays;
        $sql = " 
        SELECT 
sl.*
        ,lt.*
        ,sl.no_of_days -NVL(lt.Taken_Days,0)
        as AVAILABLE_DAYS        
FROM (select 
WOD_ID AS ID
,LA.EMPLOYEE_ID
,NO_OF_DAYS
,WD.FROM_DATE||' - '||WD.TO_DATE AS SUB_NAME
,WD.TO_DATE AS SUB_END_DATE
,WD.TO_DATE+:maxSubDays AS SUB_VALIDATE_DAYS
--,WD.* 
from 
HRIS_EMPLOYEE_LEAVE_ADDITION LA
JOIN Hris_Employee_Work_Dayoff WD ON (LA.WOD_ID=WD.ID) 
where 
LA.employee_id=:employeeId
and LA.leave_id=:leaveId
AND WD.STATUS='AP'
--AND WD.TO_DATE>TRUNC(SYSDATE-:maxSubDays)
UNION
select 
WOH_ID AS ID
,LA.EMPLOYEE_ID
,NO_OF_DAYS
,WH.FROM_DATE||' - '||WH.TO_DATE AS SUB_NAME
,WH.TO_DATE AS SUB_END_DATE
,WH.TO_DATE+:maxSubDays AS SUB_VALIDATE_DAYS
--,WH.* 
from 
HRIS_EMPLOYEE_LEAVE_ADDITION LA
JOIN Hris_Employee_Work_Holiday WH ON (LA.WOH_ID=WH.ID) 
where 
LA.employee_id=:employeeId
and LA.leave_id=:leaveId
AND WH.STATUS='AP'
--AND WH.TO_DATE>TRUNC(SYSDATE-:maxSubDays)
) sl
left join (
SELECT Sub_Ref_Id,
SUM(
CASE 
WHEN HALF_DAY IN ('F','S')
THEN NO_OF_DAYS/2
ELSE NO_OF_DAYS
END) AS TAKEN_DAYS
FROM HRIS_EMPLOYEE_LEAVE_REQUEST
WHERE EMPLOYEE_ID=:employeeId
AND LEAVE_ID=:leaveId
AND STATUS IN ('AP','RQ','RC','CP','CR')
and Sub_Ref_Id is not null
 group by Sub_Ref_Id) lt on (lt.Sub_Ref_Id=sl.id)
            
            ";
        // echo('<pre>');print_r($boundedParameter);
        // echo '<pre>'; print_r($sql);die;
        $statement = $this->adapter->query($sql);
        $result = $statement->execute($boundedParameter);
        return Helper::extractDbData($result);
    }

    public function cancelFromSubstitue($id)
    {
        $currentDate = Helper::getcurrentExpressionDate();
        $this->tableGateway->update([LeaveApply::STATUS => 'RQ', LeaveApply::MODIFIED_DT => $currentDate], [LeaveApply::ID => $id]);
    }

    public function checkSubstitueBalance($employeeId, $leaveId, $endDate, $currentBal)
    {
        $sql = "
        select 
        case when $leaveId in (select leave_id from hris_leave_master_setup where status='E' and is_substitute='Y') and '$endDate'>=trunc(sysdate) then
            new_bal
            else $currentBal END as REVISED_BALANCE
            from 
            (
        SELECT
        sum(no_of_days) as new_bal
        FROM
            (
                SELECT
                    employee_id,
                    leave_id,
                    no_of_days,
                    remarks,
                    created_date,
                    wod_id,
                    woh_id,
                    training_id,
                    travel_id,
                    CASE
                        WHEN wod_id IS NOT NULL THEN
                            (
                                SELECT
                                    to_date
                                FROM
                                    hris_employee_work_dayoff
                                WHERE
                                    id = wod_id
                            )
                        WHEN woh_id IS NOT NULL THEN
                            (
                                SELECT
                                    to_date
                                FROM
                                    hris_employee_work_holiday
                                WHERE
                                    id = woh_id
                            )
                        WHEN training_id IS NOT NULL THEN
                            (
                                SELECT
                                    created_date
                                FROM
                                    hris_employee_training_assign
                                WHERE
                                    training_id = ela.training_id
                                    AND employee_id = ela.employee_id
                            )
                        WHEN travel_id IS NOT NULL THEN
                            (
                                SELECT
                                    to_date
                                FROM
                                    hris_employee_travel_request
                                WHERE
                                    travel_id = ela.travel_id
                            )
                    END AS reward_date
                FROM
                    hris_employee_leave_addition ela
                WHERE
                    employee_id = $employeeId
                    AND leave_id = $leaveId
            )
        WHERE
            reward_date >= ( trunc(to_date('$endDate') - 21) - (
                SELECT
                    COUNT(*)
                FROM
                    hris_attendance_detail
                WHERE
                    employee_id = $employeeId
                    AND attendance_dt BETWEEN reward_date AND trunc(to_date('$endDate'))
                    AND overall_status IN (
                        'HD',
                        'WD',
                        'WH',
                        'DO'
                    )
            ) )
        ORDER BY
            reward_date DESC)
        ";

        $resultList =  EntityHelper::rawQueryResult($this->adapter, $sql)->current();
        if ($resultList['REVISED_BALANCE']) {
            return $resultList['REVISED_BALANCE'];
        } else {
            return 0;
        }
    }

    public function validateLeaveTravelRequest($fromDate, $toDate, $employeeId)
    {
        $rawResult = EntityHelper::rawQueryResult($this->adapter, "SELECT HRIS_LEAVE_TRAVEL_REQUEST({$fromDate},{$toDate},{$employeeId}) AS ERROR FROM DUAL");
        return $rawResult->current();
    }
    public function getCurrentAttd($empId)
    {
        $sql = "
        select recommend_by from hris_recommender_approver where employee_id=$empId";
        $statement = $this->adapter->query($sql);
        $result = $statement->execute()->current();
        $sql = "SELECT
        had.overall_status
    FROM
        hris_attendance_detail    had,
        hris_recommender_approver hra,
        hris_employees            he,
        hris_employees            he1
    WHERE
            had.employee_id = he.employee_id
        AND he1.employee_id = hra.recommend_by
        AND had.employee_id = hra.employee_id and had.attendance_dt=trunc(sysdate)
        AND had.employee_id = $result[RECOMMEND_BY]
    ORDER BY
        had.attendance_dt DESC";
        $statement = $this->adapter->query($sql);
        $result = $statement->execute()->current();
        return $result;
    }
}
