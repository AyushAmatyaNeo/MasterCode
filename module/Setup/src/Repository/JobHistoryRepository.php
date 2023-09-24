<?php

namespace Setup\Repository;

use Application\Helper\EntityHelper;
use Application\Helper\Helper;
use Application\Model\Model;
use Application\Repository\RepositoryInterface;
use Setup\Model\JobHistory;
use Zend\Db\Adapter\AdapterInterface;
use Zend\Db\Sql\Expression;
use Zend\Db\Sql\Sql;
use Zend\Db\TableGateway\TableGateway;

class JobHistoryRepository implements RepositoryInterface
{

    private $tableGateway;
    private $adapter;

    public function __construct(AdapterInterface $adapter)
    {
        $this->adapter = $adapter;
        $this->tableGateway = new TableGateway(JobHistory::TABLE_NAME, $adapter);
    }

    public function add(Model $model)
    {
        $history = $model->getArrayCopyForDB();
        if (!isset($history['TO_SALARY'])) {
            $history['TO_SALARY'] = 0;
        }
        $this->tableGateway->insert($history);
        $this->updateEmployeeProfile($history['JOB_HISTORY_ID']);
    }

    public function edit(Model $model, $id)
    {
        $history = $model->getArrayCopyForDB();
        if (!isset($history['TO_SALARY'])) {
            $history['TO_SALARY'] = 0;
        }
        $this->tableGateway->update($history, [JobHistory::JOB_HISTORY_ID => $id]);
        $this->updateEmployeeProfile($id);
    }

    public function delete($id)
    {
        $this->tableGateway->update([JobHistory::STATUS => 'D'], [JobHistory::JOB_HISTORY_ID => $id]);
    }

    public function fetchAll()
    {
        $sql = new Sql($this->adapter);
        $select = $sql->select();
        $select->columns([
            new Expression("INITCAP(TO_CHAR(H.START_DATE, 'DD-MON-YYYY')) AS START_DATE"),
            new Expression("INITCAP(TO_CHAR(H.END_DATE, 'DD-MON-YYYY')) AS END_DATE"),
            new Expression("H.EMPLOYEE_ID AS EMPLOYEE_ID"),
            new Expression("H.JOB_HISTORY_ID AS JOB_HISTORY_ID")
        ], true);
        $select->from(['H' => "HRIS_JOB_HISTORY"])
            ->join(['E' => 'HRIS_EMPLOYEES'], 'H.EMPLOYEE_ID=E.EMPLOYEE_ID', ["FIRST_NAME" => new Expression("INITCAP(E.FIRST_NAME)")], "left")
            ->join(['ST' => 'HRIS_SERVICE_EVENT_TYPES'], 'H.SERVICE_EVENT_TYPE_ID=ST.SERVICE_EVENT_TYPE_ID', ['SERVICE_EVENT_TYPE_NAME' => new Expression("INITCAP(ST.SERVICE_EVENT_TYPE_NAME)")], "left")
            ->join(['ST2' => 'HRIS_SERVICE_TYPES'], 'ST2.SERVICE_TYPE_ID=H.TO_SERVICE_TYPE_ID', ['TO_SERVICE_NAME' => new Expression("INITCAP(ST2.SERVICE_TYPE_NAME)")], "left")
            ->join(['P2' => 'HRIS_POSITIONS'], 'P2.POSITION_ID=H.TO_POSITION_ID', ['TO_POSITION_NAME' => new Expression("(P2.POSITION_NAME)")], "left")
            ->join(['D2' => 'HRIS_DESIGNATIONS'], 'D2.DESIGNATION_ID=H.TO_DESIGNATION_ID', ['TO_DESIGNATION_TITLE' => new Expression("(D2.DESIGNATION_TITLE)")], "left")
            ->join(['DES2' => 'HRIS_DEPARTMENTS'], 'DES2.DEPARTMENT_ID=H.TO_DEPARTMENT_ID', ['TO_DEPARTMENT_NAME' => new Expression("(DES2.DEPARTMENT_NAME)")], "left")
            ->join(['B2' => 'HRIS_BRANCHES'], 'B2.BRANCH_ID=H.TO_BRANCH_ID', ['TO_BRANCH_NAME' => new Expression("(B2.BRANCH_NAME)")], "left");

        $statement = $sql->prepareStatementForSqlObject($select);
        $result = $statement->execute();
        return $result;
    }

    public function filter($fromDate, $toDate, $employeeId, $serviceEventTypeId = null, $companyId = null, $branchId = null, $departmentId = null, $designationId = null, $positionId = null, $serviceTypeId = null, $employeeTypeId = null, $functionalTypeId = null)
    {
        $boundedParams = [];

        $sql = new Sql($this->adapter);
        $select = $sql->select();
        $select->columns([
            new Expression("INITCAP(TO_CHAR(H.START_DATE, 'DD-MON-YYYY')) AS START_DATE_AD"),
            new Expression("BS_DATE(H.START_DATE) AS START_DATE_BS"),
            new Expression("INITCAP(TO_CHAR(H.END_DATE, 'DD-MON-YYYY')) AS END_DATE_AD"),
            new Expression("BS_DATE(H.END_DATE) AS END_DATE_BS"),
            new Expression("INITCAP(TO_CHAR(H.EVENT_DATE, 'DD-MON-YYYY')) AS EVENT_DATE_AD"),
            new Expression("BS_DATE(TRUNC(H.EVENT_DATE)) AS EVENT_DATE_BS"),
            new Expression("H.EMPLOYEE_ID AS EMPLOYEE_ID"),
            new Expression("E.EMPLOYEE_CODE AS EMPLOYEE_CODE"),
            new Expression("H.JOB_HISTORY_ID AS JOB_HISTORY_ID")
        ], true);
        $select->from(['H' => "HRIS_JOB_HISTORY"])
            ->join(['E' => 'HRIS_EMPLOYEES'], 'H.EMPLOYEE_ID=E.EMPLOYEE_ID', ["FIRST_NAME" => new Expression("INITCAP(E.FIRST_NAME)"), "MIDDLE_NAME" => new Expression("INITCAP(E.MIDDLE_NAME)"), "LAST_NAME" => new Expression("INITCAP(E.LAST_NAME)"), "FULL_NAME" => new Expression("INITCAP(E.FULL_NAME)")], "left")
            ->join(['ST' => 'HRIS_SERVICE_EVENT_TYPES'], 'H.SERVICE_EVENT_TYPE_ID=ST.SERVICE_EVENT_TYPE_ID', ['SERVICE_EVENT_TYPE_NAME' => new Expression("INITCAP(ST.SERVICE_EVENT_TYPE_NAME)")], "left")
            ->join(['ST2' => 'HRIS_SERVICE_TYPES'], 'ST2.SERVICE_TYPE_ID=H.TO_SERVICE_TYPE_ID', ['TO_SERVICE_NAME' => new Expression("INITCAP(ST2.SERVICE_TYPE_NAME)")], "left")
            ->join(['P2' => 'HRIS_POSITIONS'], 'P2.POSITION_ID=H.TO_POSITION_ID', ['TO_POSITION_NAME' => new Expression("(P2.POSITION_NAME)")], "left")
            ->join(['D2' => 'HRIS_DESIGNATIONS'], 'D2.DESIGNATION_ID=H.TO_DESIGNATION_ID', ['TO_DESIGNATION_TITLE' => new Expression("(D2.DESIGNATION_TITLE)")], "left")
            ->join(['DES2' => 'HRIS_DEPARTMENTS'], 'DES2.DEPARTMENT_ID=H.TO_DEPARTMENT_ID', ['TO_DEPARTMENT_NAME' => new Expression("(DES2.DEPARTMENT_NAME)")], "left")
            ->join(['B2' => 'HRIS_BRANCHES'], 'B2.BRANCH_ID=H.TO_BRANCH_ID', ['TO_BRANCH_NAME' => new Expression("(B2.BRANCH_NAME)")], "left")
            ->join(['F2' => 'hris_functional_types'], 'F2.functional_type_id=E.functional_type_id', ['functional_type_edesc' => new Expression("(F2.functional_type_edesc)")], "left");

        $select->where("H.STATUS = 'E'");

        if ($fromDate != null) {
            $select->where([
                "H.START_DATE>= :fromDate"
            ]);
            $boundedParams['fromDate'] = $fromDate;
        }

        if ($toDate != null) {
            $select->where([
                "H.END_DATE<= :toDate"
            ]);
            $boundedParams['toDate'] = $toDate;
        }

        if ($employeeTypeId != null && $employeeTypeId != -1) {
            $conditonDetail = EntityHelper::conditionBuilderBounded($employeeTypeId, "E.EMPLOYEE_TYPE", "");
            $conditon = $conditonDetail['sql'];
            $boundedParams = array_merge($boundedParams, $conditonDetail['parameter']);
            $select->where([
                $conditon
            ]);
        }

        if ($employeeId != -1 && $employeeId != null) {
            $conditonDetail = EntityHelper::conditionBuilderBounded($employeeId, "E.EMPLOYEE_ID", "");
            $conditon = $conditonDetail['sql'];
            $boundedParams = array_merge($boundedParams, $conditonDetail['parameter']);
            $select->where([
                $conditon
            ]);
        }

        if ($serviceEventTypeId != null && $serviceEventTypeId != -1) {
            $conditonDetail = EntityHelper::conditionBuilderBounded($serviceEventTypeId, "H.SERVICE_EVENT_TYPE_ID", "");
            $conditon = $conditonDetail['sql'];
            $boundedParams = array_merge($boundedParams, $conditonDetail['parameter']);
            $select->where([
                $conditon
            ]);
        }
        if ($companyId != null && $companyId != -1) {
            $conditonDetail = EntityHelper::conditionBuilderBounded($companyId, "E.COMPANY_ID", "");
            $conditon = $conditonDetail['sql'];
            $boundedParams = array_merge($boundedParams, $conditonDetail['parameter']);
            $select->where([
                $conditon
            ]);
        }
        if ($branchId != null && $branchId != -1) {
            $conditonDetail = EntityHelper::conditionBuilderBounded($branchId, "E.BRANCH_ID", "");
            $conditon = $conditonDetail['sql'];
            $boundedParams = array_merge($boundedParams, $conditonDetail['parameter']);
            $select->where([
                $conditon
            ]);
        }
        if ($departmentId != null && $departmentId != -1) {
            $parentQuery = "(SELECT DEPARTMENT_ID FROM
                         HRIS_DEPARTMENTS 
                        START WITH PARENT_DEPARTMENT in (INVALUES)
                        CONNECT BY PARENT_DEPARTMENT= PRIOR DEPARTMENT_ID
                        UNION 
                        SELECT DEPARTMENT_ID FROM HRIS_DEPARTMENTS WHERE DEPARTMENT_ID IN (INVALUES)
                        UNION
                        SELECT  TO_NUMBER(TRIM(REGEXP_SUBSTR(EXCEPTIONAL,'[^,]+', 1, LEVEL) )) DEPARTMENT_ID
  FROM (SELECT EXCEPTIONAL  FROM  HRIS_DEPARTMENTS WHERE DEPARTMENT_ID IN  (INVALUES))
   CONNECT BY  REGEXP_SUBSTR(EXCEPTIONAL, '[^,]+', 1, LEVEL) IS NOT NULL
                        )";
            $conditonDetail = EntityHelper::conditionBuilderBounded($departmentId, "E.DEPARTMENT_ID", "", false, $parentQuery);
            $conditon = $conditonDetail['sql'];
            $boundedParams = array_merge($boundedParams, $conditonDetail['parameter']);
            $select->where([
                $conditon
            ]);
        }
        if ($designationId != null && $designationId != -1) {
            $conditonDetail = EntityHelper::conditionBuilderBounded($designationId, "E.DESIGNATION_ID", "");
            $conditon = $conditonDetail['sql'];
            $boundedParams = array_merge($boundedParams, $conditonDetail['parameter']);
            $select->where([
                $conditon
            ]);
        }
        if ($positionId != null && $positionId != -1) {
            $conditonDetail = EntityHelper::conditionBuilderBounded($positionId, "E.POSITION_ID", "");
            $conditon = $conditonDetail['sql'];
            $boundedParams = array_merge($boundedParams, $conditonDetail['parameter']);
            $select->where([
                $conditon
            ]);
        }
        if ($serviceTypeId != null && $serviceTypeId != -1) {
            $conditonDetail = EntityHelper::conditionBuilderBounded($serviceTypeId, "E.SERVICE_TYPE_ID", "");
            $conditon = $conditonDetail['sql'];
            $boundedParams = array_merge($boundedParams, $conditonDetail['parameter']);
            $select->where([
                $conditon
            ]);
        }
        if ($functionalTypeId != null && $functionalTypeId != -1) {
            $conditonDetail = EntityHelper::conditionBuilderBounded($functionalTypeId, "E.functional_type_id", "");
            $conditon = $conditonDetail['sql'];
            $boundedParams = array_merge($boundedParams, $conditonDetail['parameter']);
            $select->where([
                $conditon
            ]);
        }
        $select->order("E.FIRST_NAME,H.START_DATE ASC");
        $statement = $sql->prepareStatementForSqlObject($select);
        // echo '<pre>';print_r($statement);die;
        $result = $statement->execute($boundedParams);
        return $result;
    }

    public function fetchById($id)
    {
        $sql = new Sql($this->adapter);
        $select = $sql->select();
        $select->columns([
            new Expression("INITCAP(TO_CHAR(H.START_DATE, 'DD-MON-YYYY')) AS START_DATE"),
            new Expression("INITCAP(TO_CHAR(H.END_DATE, 'DD-MON-YYYY')) AS END_DATE"),
            new Expression("H.EMPLOYEE_ID AS EMPLOYEE_ID"),
            new Expression("H.JOB_HISTORY_ID AS JOB_HISTORY_ID"),
            new Expression("H.SERVICE_EVENT_TYPE_ID AS SERVICE_EVENT_TYPE_ID"),
            new Expression("H.TO_BRANCH_ID AS TO_BRANCH_ID"),
            new Expression("H.TO_DEPARTMENT_ID AS TO_DEPARTMENT_ID"),
            new Expression("H.TO_DESIGNATION_ID AS TO_DESIGNATION_ID"),
            new Expression("H.TO_POSITION_ID AS TO_POSITION_ID"),
            new Expression("H.TO_SERVICE_TYPE_ID AS TO_SERVICE_TYPE_ID"),
            new Expression("H.TO_COMPANY_ID AS TO_COMPANY_ID"),
            new Expression("H.TO_SALARY AS TO_SALARY"),
            new Expression("H.RETIRED_FLAG AS RETIRED_FLAG"),
            new Expression("H.DISABLED_FLAG AS DISABLED_FLAG"),
            new Expression("INITCAP(TO_CHAR(H.EVENT_DATE, 'DD-MON-YYYY')) AS EVENT_DATE"),
            new Expression("H.FILE_ID AS FILE_ID"),
        ], true);
        $select->from(['H' => "HRIS_JOB_HISTORY"]);
        $select->where(['H.JOB_HISTORY_ID' => $id]);
        $statement = $sql->prepareStatementForSqlObject($select);
        $result = $statement->execute();
        return $result->current();
    }

    public function fetchHistoryNotReview($empId)
    {
        $sql = "SELECT JH.JOB_HISTORY_ID,JH.START_DATE,SE.SERVICE_EVENT_TYPE_NAME
                FROM HRIS_JOB_HISTORY JH 
                JOIN HRIS_SERVICE_EVENT_TYPES SE
                ON (JH.SERVICE_EVENT_TYPE_ID=SE.SERVICE_EVENT_TYPE_ID)

                WHERE EMPLOYEE_ID=$empId
                AND JOB_HISTORY_ID NOT IN
                  (SELECT JOB_HISTORY_ID FROM HRIS_SALARY_DETAIL WHERE EMPLOYEE_ID=:empId
                  )";

        $boundedParameter = [];
        $boundedParameter['empId'] = $empId;
        return $this->rawQuery($sql, $boundedParameter);
        // $statement = $this->adapter->query($sql);
        // $result = $statement->execute();
        // $result = Helper::extractDbData($result);
        // return $result;
    }

    function fetchLatestJobHistory($employeeId)
    {
        $boundedParams = [];
        $boundedParams['employeeId'] = $employeeId;
        return Helper::extractDbData(EntityHelper::rawQueryResult($this->adapter, " 
                        SELECT TO_BRANCH_ID,
                          TO_DEPARTMENT_ID,
                          TO_DESIGNATION_ID,
                          TO_POSITION_ID,
                          TO_SERVICE_TYPE_ID,
                          TO_COMPANY_ID,
                          START_DATE_FORMATTED AS START_DATE
                        FROM
                          (SELECT TO_BRANCH_ID,
                            TO_DEPARTMENT_ID,
                            TO_DESIGNATION_ID,
                            TO_POSITION_ID,
                            TO_SERVICE_TYPE_ID,
                            TO_COMPANY_ID,
                            INITCAP(TO_CHAR(START_DATE,'DD-MON-YYYY')) AS START_DATE_FORMATTED
                          FROM HRIS_JOB_HISTORY
                          WHERE EMPLOYEE_ID = :employeeId
                          AND STATUS        ='E'
                          ORDER BY START_DATE DESC
                          )
                        WHERE ROWNUM=1", $boundedParams));
    }

    function fetchBeforeJobHistory($historyId)
    {
        $boundedParams = [];
        $boundedParams['historyId'] = $historyId;
        return Helper::extractDbData(EntityHelper::rawQueryResult($this->adapter, "
                    SELECT INITCAP(TO_CHAR(H.START_DATE, 'DD-MON-YYYY')) AS START_DATE,
                      INITCAP(TO_CHAR(H.END_DATE, 'DD-MON-YYYY'))        AS END_DATE,
                      H.EMPLOYEE_ID                                      AS EMPLOYEE_ID,
                      H.JOB_HISTORY_ID                                   AS JOB_HISTORY_ID,
                      H.SERVICE_EVENT_TYPE_ID                            AS SERVICE_EVENT_TYPE_ID,
                      H.TO_BRANCH_ID                                     AS TO_BRANCH_ID,
                      H.TO_DEPARTMENT_ID                                 AS TO_DEPARTMENT_ID,
                      H.TO_DESIGNATION_ID                                AS TO_DESIGNATION_ID,
                      H.TO_POSITION_ID                                   AS TO_POSITION_ID,
                      H.TO_SERVICE_TYPE_ID                               AS TO_SERVICE_TYPE_ID
                    FROM HRIS_JOB_HISTORY H,
                      (SELECT START_DATE FROM HRIS_JOB_HISTORY WHERE JOB_HISTORY_ID= :historyId
                      ) PH
                    WHERE H.START_DATE<PH.START_DATE
                    AND ROWNUM        =1", $boundedParams));
    }

    function fetchAfterJobHistory($historyId)
    {
        $boundedParams = [];
        $boundedParams['historyId'] = $historyId;
        return Helper::extractDbData(EntityHelper::rawQueryResult($this->adapter, "
                SELECT INITCAP(TO_CHAR(H.START_DATE, 'DD-MON-YYYY')) AS START_DATE,
                  INITCAP(TO_CHAR(H.END_DATE, 'DD-MON-YYYY'))        AS END_DATE,
                  H.EMPLOYEE_ID                                      AS EMPLOYEE_ID,
                  H.JOB_HISTORY_ID                                   AS JOB_HISTORY_ID,
                  H.SERVICE_EVENT_TYPE_ID                            AS SERVICE_EVENT_TYPE_ID,
                  H.TO_BRANCH_ID                                     AS TO_BRANCH_ID,
                  H.TO_DEPARTMENT_ID                                 AS TO_DEPARTMENT_ID,
                  H.TO_DESIGNATION_ID                                AS TO_DESIGNATION_ID,
                  H.TO_POSITION_ID                                   AS TO_POSITION_ID,
                  H.TO_SERVICE_TYPE_ID                               AS TO_SERVICE_TYPE_ID
                FROM HRIS_JOB_HISTORY H,
                  (SELECT START_DATE FROM HRIS_JOB_HISTORY WHERE JOB_HISTORY_ID= :historyId
                  ) PH
                WHERE H.START_DATE>PH.START_DATE
                AND ROWNUM        =1", $boundedParams));
    }

    function fetchAfterStartDate($date)
    {
        $boundedParams = [];
        $boundedParams['date'] = $date;
        return Helper::extractDbData(EntityHelper::rawQueryResult($this->adapter, "
            SELECT INITCAP(TO_CHAR(H.START_DATE, 'DD-MON-YYYY')) AS START_DATE,
              INITCAP(TO_CHAR(H.END_DATE, 'DD-MON-YYYY'))        AS END_DATE,
              H.EMPLOYEE_ID                                      AS EMPLOYEE_ID,
              H.JOB_HISTORY_ID                                   AS JOB_HISTORY_ID,
              H.SERVICE_EVENT_TYPE_ID                            AS SERVICE_EVENT_TYPE_ID,
              H.TO_BRANCH_ID                                     AS TO_BRANCH_ID,
              H.TO_DEPARTMENT_ID                                 AS TO_DEPARTMENT_ID,
              H.TO_DESIGNATION_ID                                AS TO_DESIGNATION_ID,
              H.TO_POSITION_ID                                   AS TO_POSITION_ID,
              H.TO_SERVICE_TYPE_ID                               AS TO_SERVICE_TYPE_ID
            FROM HRIS_JOB_HISTORY H
            WHERE H.START_DATE> :date
            AND ROWNUM        =1", $boundedParams));
    }

    function fetchBeforeStartDate($date, $employeeId)
    {
        $boundedParams = [];
        $boundedParams['date'] = $date;
        $boundedParams['employeeId'] = $employeeId;
        $result = EntityHelper::rawQueryResult($this->adapter, "
            SELECT * FROM
            (SELECT INITCAP(TO_CHAR(H.START_DATE, 'DD-MON-YYYY')) AS START_DATE,
              INITCAP(TO_CHAR(H.END_DATE, 'DD-MON-YYYY'))        AS END_DATE,
              H.EMPLOYEE_ID                                      AS EMPLOYEE_ID,
              H.JOB_HISTORY_ID                                   AS JOB_HISTORY_ID,
              H.SERVICE_EVENT_TYPE_ID                            AS SERVICE_EVENT_TYPE_ID,
              H.TO_COMPANY_ID                                    AS TO_COMPANY_ID,
              H.TO_BRANCH_ID                                     AS TO_BRANCH_ID,
              H.TO_DEPARTMENT_ID                                 AS TO_DEPARTMENT_ID,
              H.TO_DESIGNATION_ID                                AS TO_DESIGNATION_ID,
              H.TO_POSITION_ID                                   AS TO_POSITION_ID,
              H.TO_SERVICE_TYPE_ID                               AS TO_SERVICE_TYPE_ID,
              H.TO_SALARY                                        AS TO_SALARY,
              H.RETIRED_FLAG,
              H.DISABLED_FLAG
            FROM HRIS_JOB_HISTORY H
            WHERE H.START_DATE< :date
            AND H.EMPLOYEE_ID = :employeeId
            ORDER BY H.START_DATE DESC)
            WHERE ROWNUM        =1", $boundedParams);

        return $result->current();
    }

    function fetchByEmployeeId($employeeId)
    {
        $boundedParams = [];
        $boundedParams['employeeId'] = $employeeId;
        $result = EntityHelper::rawQueryResult($this->adapter, "
            SELECT INITCAP(TO_CHAR(H.START_DATE, 'YYYY-MM-DD')) AS START_DATE,
              INITCAP(TO_CHAR(H.END_DATE, 'YYYY-MM-DD'))        AS END_DATE,
              H.EMPLOYEE_ID                                      AS EMPLOYEE_ID,
              H.JOB_HISTORY_ID                                   AS JOB_HISTORY_ID,
              H.SERVICE_EVENT_TYPE_ID                            AS SERVICE_EVENT_TYPE_ID,
              SE.SERVICE_EVENT_TYPE_NAME                        AS SERVICE_EVENT_TYPE_NAME,
              H.TO_COMPANY_ID                                    AS TO_COMPANY_ID,
              C.COMPANY_NAME                                     AS COMPANY_NAME,
              H.TO_BRANCH_ID                                     AS TO_BRANCH_ID,
              B.BRANCH_NAME                                      AS BRANCH_NAME,
              H.TO_DEPARTMENT_ID                                 AS TO_DEPARTMENT_ID,
              DEP.DEPARTMENT_NAME                                AS DEPARTMENT_NAME,
              H.TO_DESIGNATION_ID                                AS TO_DESIGNATION_ID,
              DES.DESIGNATION_TITLE                              AS DESIGNATION_TITLE,
              H.TO_POSITION_ID                                   AS TO_POSITION_ID,
              P.POSITION_NAME                                    AS POSITION_NAME,
              H.TO_SERVICE_TYPE_ID                               AS TO_SERVICE_TYPE_ID,
              ST.SERVICE_TYPE_NAME                               AS SERVICE_TYPE_NAME,
              H.TO_SALARY                                        AS TO_SALARY
            FROM HRIS_JOB_HISTORY H 
            JOIN HRIS_SERVICE_EVENT_TYPES SE ON (H.SERVICE_EVENT_TYPE_ID = SE.SERVICE_EVENT_TYPE_ID)
            JOIN HRIS_COMPANY C ON (H.TO_COMPANY_ID = C.COMPANY_ID)
            JOIN HRIS_BRANCHES B ON (H.TO_BRANCH_ID = B.BRANCH_ID)
            JOIN HRIS_DEPARTMENTS DEP ON (H.TO_DEPARTMENT_ID = DEP.DEPARTMENT_ID)
            JOIN HRIS_DESIGNATIONS DES ON (H.TO_DESIGNATION_ID = DES.DESIGNATION_ID)
            JOIN HRIS_POSITIONS P ON (H.TO_POSITION_ID = P.POSITION_ID)
            JOIN HRIS_SERVICE_TYPES ST ON (H.TO_SERVICE_TYPE_ID = ST.SERVICE_TYPE_ID)
            WHERE H.EMPLOYEE_ID = :employeeId
            ORDER BY H.START_DATE DESC", $boundedParams);

        return Helper::extractDbData($result);
    }

    function updateEmployeeProfile($jobHistoryId)
    {
        $boundedParams = [];
        $boundedParams['jobHistoryId'] = $jobHistoryId;
        // $salary = isset($j->toSalary) ? $j->toSalary : 0;
        EntityHelper::rawQueryResult($this->adapter, "
            BEGIN
              HRIS_UPDATE_EMPLOYEE_SERVICE(:jobHistoryId);
            END;", $boundedParams);
    }

    function displayAutoNotification()
    {
        EntityHelper::rawQueryResult($this->adapter, "");
    }

    public function filterNewEmp($data)
    {
        $boundedParameter = [];
        $condition = EntityHelper::getSearchConditonBounded($data['companyId'], $data['branchId'], $data['departmentId'], $data['positionId'], $data['designationId'], $data['serviceTypeId'], $data['serviceEventTypeId'], $data['employeeTypeId'], $data['employeeId'], $data['genderId'], $data['locationId'], $data['functionalTypeId']);
        $boundedParameter = array_merge($boundedParameter, $condition['parameter']);
        $sql = "SELECT
        e.employee_code,
        c.company_name,
        d.designation_title,
        hd.department_name,
        p.position_name,
        b.branch_name,
        e.mobile_no,
        initcap(g.gender_name)               AS gender_name,
        to_char(e.birth_date, 'DD-MON-YYYY') AS birth_date_ad,
        bs_date(e.birth_date)                AS birth_date_bs,
        to_char(e.join_date, 'DD-MON-YYYY')  AS join_date_ad,
        bs_date(e.join_date)                 AS join_date_bs,
        e.full_name
    FROM
        hris_employees    e
        LEFT JOIN hris_company      c ON ( e.company_id = c.company_id )
        LEFT JOIN hris_designations d ON ( d.designation_id = e.designation_id )
        LEFT JOIN hris_departments  hd ON ( hd.department_id = e.department_id )
        LEFT JOIN hris_positions    p ON ( p.position_id = e.position_id )
        LEFT JOIN hris_branches     b ON ( b.branch_id = e.branch_id )
        left join hris_genders      g on (g.gender_id=e.gender_id)
    WHERE join_date BETWEEN '$data[fromDate]' AND '$data[toDate]' {$condition['sql']}
    and e.status='E' order by join_date desc";
        $statement = $this->adapter->query($sql);
        return $statement->execute($boundedParameter);
    }
}
