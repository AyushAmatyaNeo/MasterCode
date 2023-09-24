<?php

namespace Setup\Repository;

use Application\Helper\EntityHelper;
use Application\Helper\Helper;
use Application\Model\Model;
use Application\Repository\RepositoryInterface;
use Setup\Model\RecommendApprove;
use Zend\Db\Adapter\AdapterInterface;
use Zend\Db\Sql\Expression;
use Zend\Db\Sql\Sql;
use Zend\Db\TableGateway\TableGateway;

class RecommendApproveRepository implements RepositoryInterface
{

  private $tableGateway;
  private $adapter;

  public function __construct(AdapterInterface $adapter)
  {
    $this->tableGateway = new TableGateway(RecommendApprove::TABLE_NAME, $adapter);
    $this->employeeTableGateway = new TableGateway("HRIS_EMPLOYEES", $adapter);
    $this->adapter = $adapter;
  }

  public function getDesignationList($employeeId)
  {
    $boundedParams = [];
    $sql = "SELECT  DESIGNATION_ID, (DESIGNATION_TITLE) AS DESIGNATION_TITLE, PARENT_DESIGNATION, WITHIN_BRANCH, WITHIN_DEPARTMENT, LEVEL 
                FROM HRIS_DESIGNATIONS WHERE (LEVEL=2 OR LEVEL=3)
                START WITH DESIGNATION_ID = (SELECT E.DESIGNATION_ID FROM HRIS_EMPLOYEES E WHERE E.EMPLOYEE_ID=  :employeeId )
                CONNECT BY PRIOR  PARENT_DESIGNATION=DESIGNATION_ID";
    $boundedParams['employeeId'] = $employeeId;
    $statement = $this->adapter->query($sql);
    return $statement->execute($boundedParams);
  }

  //to get recommender and approver based on designation and branch id
  public function getEmployeeList($withinBranch, $withinDepartment, $designationId, $branchId, $departmentId)
  {
    $boundedParams = [];
    $sql = "SELECT EMPLOYEE_ID,INITCAP(FIRST_NAME) AS FIRST_NAME,INITCAP(MIDDLE_NAME) AS MIDDLE_NAME,INITCAP(LAST_NAME) AS LAST_NAME FROM HRIS_EMPLOYEES WHERE STATUS='E' AND RETIRED_FLAG='N' AND DESIGNATION_ID= :designationId ";

    if ($withinBranch != null && $withinBranch != "N") {
      $sql .= " AND BRANCH_ID= :branchId ";
    }

    if ($withinDepartment != null && $withinDepartment != "N") {
      $sql .= " AND DEPARTMENT_ID= :departmentId ";
    }
    $boundedParams['designationId'] = $designationId;
    $boundedParams['branchId'] = $branchId;

    $statement = $this->adapter->query($sql);
    return $statement->execute();
  }

  public function add(Model $model)
  {
    $this->tableGateway->insert($model->getArrayCopyForDB());
  }

  public function fetchAll()
  {
    $sql = new Sql($this->adapter);
    $select = $sql->select();
    $select->columns([
      new Expression("RA.STATUS AS STATUS"),
      new Expression("RA.EMPLOYEE_ID AS EMPLOYEE_ID"),
      new Expression("RA.RECOMMEND_BY AS RECOMMEND_BY"),
      new Expression("RA.APPROVED_BY AS APPROVED_BY"),
    ], true);
    $select->from(['RA' => RecommendApprove::TABLE_NAME])
      ->join(['E' => "HRIS_EMPLOYEES"], "E.EMPLOYEE_ID=RA.EMPLOYEE_ID", ['FIRST_NAME' => new Expression("INITCAP(E.FIRST_NAME)"), 'MIDDLE_NAME' => new Expression("INITCAP(E.MIDDLE_NAME)"), 'LAST_NAME' => new Expression("INITCAP(E.LAST_NAME)")], "left")
      ->join(['E1' => "HRIS_EMPLOYEES"], "E1.EMPLOYEE_ID=RA.RECOMMEND_BY", ['FIRST_NAME_R' => new Expression("INITCAP(E1.FIRST_NAME)"), "MIDDLE_NAME_R" => new Expression("INITCAP(E1.MIDDLE_NAME)"), "LAST_NAME_R" => new Expression("INITCAP(E1.LAST_NAME)"), "RETIRED_R" => "RETIRED_FLAG", "STATUS_R" => "STATUS"], "left")
      ->join(['E2' => "HRIS_EMPLOYEES"], "E2.EMPLOYEE_ID=RA.APPROVED_BY", ['FIRST_NAME_A' => new Expression("INITCAP(E2.FIRST_NAME)"), "MIDDLE_NAME_A" => new Expression("INITCAP(E2.MIDDLE_NAME)"), "LAST_NAME_A" => new Expression("INITCAP(E2.LAST_NAME)"), "RETIRED_A" => "RETIRED_FLAG", "STATUS_A" => "STATUS"], "left");

    $select->where([
      "RA.STATUS='E'",
      "E.STATUS='E'",
      "E.RETIRED_FLAG='N' AND
              (((E1.STATUS =
                CASE
                  WHEN E1.STATUS IS NOT NULL
                  THEN ('E')
                END
              OR E1.STATUS IS NULL)
              AND
              (E1.RETIRED_FLAG =
                CASE
                  WHEN E1.RETIRED_FLAG IS NOT NULL
                  THEN ('N')
                END
              OR E1.RETIRED_FLAG IS NULL))
            OR
              ((E2.STATUS =
                CASE
                  WHEN E2.STATUS IS NOT NULL
                  THEN ('E')
                END
              OR E2.STATUS IS NULL)
            AND
              (E2.RETIRED_FLAG =
                CASE
                  WHEN E2.RETIRED_FLAG IS NOT NULL
                  THEN ('N')
                END
              OR E2.RETIRED_FLAG IS NULL)))"
    ]);
    $select->order("E.FIRST_NAME ASC");
    $statement = $sql->prepareStatementForSqlObject($select);
    return $statement->execute();
  }

  //to get the employee list for select option
  public function getEmployees($id = null)
  {
    $entitiesArray = array();
    if ($id != null) {
      $empresult = $this->employeeTableGateway->select(['EMPLOYEE_ID' => $id])->current();
      $entitiesArray[$empresult['EMPLOYEE_ID']] = $empresult['FIRST_NAME'] . " " . $empresult['MIDDLE_NAME'] . " " . $empresult['LAST_NAME'];
    }
    $sql = "SELECT EMPLOYEE_ID,INITCAP(FIRST_NAME) AS FIRST_NAME,INITCAP(MIDDLE_NAME) AS MIDDLE_NAME,INITCAP(LAST_NAME) AS LAST_NAME FROM 
                HRIS_EMPLOYEES WHERE STATUS='E' AND RETIRED_FLAG='N'
                AND EMPLOYEE_ID NOT IN 
                (SELECT EMPLOYEE_ID FROM HRIS_RECOMMENDER_APPROVER WHERE STATUS='E')";

    $statement = $this->adapter->query($sql);
    $resultset = $statement->execute();

    foreach ($resultset as $result) {
      $entitiesArray[$result['EMPLOYEE_ID']] = $result['FIRST_NAME'] . " " . $result['MIDDLE_NAME'] . " " . $result['LAST_NAME'];
    }
    return $entitiesArray;
  }

  public function edit(Model $model, $id)
  {
    $array = $model->getArrayCopyForDB();
    $this->tableGateway->update($array, [RecommendApprove::EMPLOYEE_ID => $id]);
  }

  public function delete($id)
  {
    $this->tableGateway->update([RecommendApprove::STATUS => 'D'], [RecommendApprove::EMPLOYEE_ID => $id]);
  }

  public function fetchById($id)
  {
    $row = $this->tableGateway->select([RecommendApprove::EMPLOYEE_ID => $id]);
    return $row->current();
  }

  public function getDetailByEmployeeID($employeeId, $recommenderId = null, $approverId = null)
  {
    $sql = new Sql($this->adapter);
    $select = $sql->select();
    $select->columns([
      new Expression("RA.STATUS AS STATUS"),
      new Expression("RA.EMPLOYEE_ID AS EMPLOYEE_ID"),
      new Expression("RA.RECOMMEND_BY AS RECOMMEND_BY"),
      new Expression("RA.APPROVED_BY AS APPROVED_BY"),
    ], true);
    $select->from(['RA' => RecommendApprove::TABLE_NAME])
      ->join(['E' => "HRIS_EMPLOYEES"], "E.EMPLOYEE_ID=RA.EMPLOYEE_ID", ['FIRST_NAME' => new Expression("INITCAP(E.FIRST_NAME)"), 'MIDDLE_NAME' => new Expression("INITCAP(E.MIDDLE_NAME)"), 'LAST_NAME' => new Expression("INITCAP(E.LAST_NAME)")], "left")
      ->join(['E1' => "HRIS_EMPLOYEES"], "E1.EMPLOYEE_ID=RA.RECOMMEND_BY", ['FIRST_NAME_R' => new Expression("INITCAP(E1.FIRST_NAME)"), "MIDDLE_NAME_R" => new Expression("INITCAP(E1.MIDDLE_NAME)"), "LAST_NAME_R" => new Expression("INITCAP(E1.LAST_NAME)"), "RETIRED_R" => "RETIRED_FLAG", "STATUS_R" => "STATUS"], "left")
      ->join(['E2' => "HRIS_EMPLOYEES"], "E2.EMPLOYEE_ID=RA.APPROVED_BY", ['FIRST_NAME_A' => new Expression("INITCAP(E2.FIRST_NAME)"), "MIDDLE_NAME_A" => new Expression("INITCAP(E2.MIDDLE_NAME)"), "LAST_NAME_A" => new Expression("INITCAP(E2.LAST_NAME)"), "RETIRED_A" => "RETIRED_FLAG", "STATUS_A" => "STATUS"], "left");

    $select->where([
      "RA.STATUS='E'",
      "E.STATUS='E'",
      "E.RETIRED_FLAG='N'",
      "RA.EMPLOYEE_ID" =>  $employeeId,
      " 
              (((E1.STATUS =
                CASE
                  WHEN E1.STATUS IS NOT NULL
                  THEN ('E')
                END
              OR E1.STATUS IS NULL)
              AND
              (E1.RETIRED_FLAG =
                CASE
                  WHEN E1.RETIRED_FLAG IS NOT NULL
                  THEN ('N')
                END
              OR E1.RETIRED_FLAG IS NULL))
            OR
              ((E2.STATUS =
                CASE
                  WHEN E2.STATUS IS NOT NULL
                  THEN ('E')
                END
              OR E2.STATUS IS NULL)
            AND
              (E2.RETIRED_FLAG =
                CASE
                  WHEN E2.RETIRED_FLAG IS NOT NULL
                  THEN ('N')
                END
              OR E2.RETIRED_FLAG IS NULL)))"
    ]);

    if ($recommenderId != null && $recommenderId != -1) {
      $select->where([
        "RA.RECOMMEND_BY" => $recommenderId
      ]);
    }

    if ($approverId != null && $approverId != -1) {
      $select->where([
        "RA.APPROVED_BY" => $approverId
      ]);
    }

    $select->order("E.FIRST_NAME ASC");
    $statement = $sql->prepareStatementForSqlObject($select);
    $result = $statement->execute();
    return $result->current();
  }

  public function getFilteredList($search)
  {
    $condition = "";
    $condition .= EntityHelper::getSearchConditon($search['companyId'], $search['branchId'], $search['departmentId'], $search['positionId'], $search['designationId'], $search['serviceTypeId'], $search['serviceEventTypeId'], $search['employeeTypeId'], $search['employeeId'], null, null, $search['functionalTypeId']);
    if (isset($search['recommenderId']) && $search['recommenderId'] != null && $search['recommenderId'] != -1) {
      if (gettype($search['recommenderId']) === 'array') {
        $csv = "";
        for ($i = 0; $i < sizeof($search['recommenderId']); $i++) {
          if ($i == 0) {
            $csv = "'{$search['recommenderId'][$i]}'";
          } else {
            $csv .= ",'{$search['recommenderId'][$i]}'";
          }
        }
        $condition .= "AND RA.RECOMMEND_BY IN ({$csv})";
      } else {
        $condition .= "AND RA.RECOMMEND_BY IN ('{$search['recommenderId']}')";
      }
    }
    if (isset($search['approverId']) && $search['approverId'] != null && $search['approverId'] != -1) {
      if (gettype($search['approverId']) === 'array') {
        $csv = "";
        for ($i = 0; $i < sizeof($search['approverId']); $i++) {
          if ($i == 0) {
            $csv = "'{$search['approverId'][$i]}'";
          } else {
            $csv .= ",'{$search['approverId'][$i]}'";
          }
        }
        $condition .= "AND RA.APPROVED_BY IN ({$csv})";
      } else {
        $condition .= "AND RA.APPROVED_BY IN ('{$search['approverId']}')";
      }
    }

    $sql = "  
            SELECT AR.A_R_ID,AR.A_R_NAME,AA.A_A_ID,AA.A_A_NAME,
            EC.COMPANY_NAME, 
                E.EMPLOYEE_ID,
                E.FULL_NAME    AS EMPLOYEE_NAME,
                E.EMPLOYEE_CODE AS EMPLOYEE_CODE,
                RE.EMPLOYEE_ID AS RECOMMENDER_ID,
                RE.FULL_NAME   AS RECOMMENDER_NAME,
                AE.EMPLOYEE_ID AS APPROVER_ID,
                AE.FULL_NAME   AS APPROVER_NAME
              FROM HRIS_RECOMMENDER_APPROVER RA
              LEFT JOIN HRIS_EMPLOYEES E
              ON (E.EMPLOYEE_ID=RA.EMPLOYEE_ID)
              LEFT JOIN HRIS_EMPLOYEES RE
              ON (RE.EMPLOYEE_ID = RA.RECOMMEND_BY)
              LEFT JOIN HRIS_EMPLOYEES AE
              ON (AE.EMPLOYEE_ID=RA.APPROVED_BY)
              LEFT JOIN HRIS_COMPANY EC
              ON (EC.COMPANY_ID=E.COMPANY_ID)
              

LEFT JOIN (
            SELECT 
IARA.EMPLOYEE_ID,
LISTAGG(IARA.R_A_ID, ',') WITHIN GROUP (ORDER BY IARAE.FULL_NAME) AS A_R_ID,
LISTAGG(IARAE.FULL_NAME, ',') WITHIN GROUP (ORDER BY IARAE.FULL_NAME) AS A_R_NAME
FROM HRIS_ALTERNATE_R_A IARA
JOIN HRIS_EMPLOYEES IARAE ON (IARA.R_A_ID=IARAE.EMPLOYEE_ID AND IARA.R_A_FLAG='R') where IARAE.STATUS='E'
GROUP BY IARA.EMPLOYEE_ID) AR ON (AR.EMPLOYEE_ID=E.EMPLOYEE_ID)
LEFT JOIN (
            SELECT 
IARA.EMPLOYEE_ID,
LISTAGG(IARA.R_A_ID, ',') WITHIN GROUP (ORDER BY IARAE.FULL_NAME) AS A_A_ID,
LISTAGG(IARAE.FULL_NAME, ',') WITHIN GROUP (ORDER BY IARAE.FULL_NAME) AS A_A_NAME
FROM HRIS_ALTERNATE_R_A IARA
JOIN HRIS_EMPLOYEES IARAE ON (IARA.R_A_ID=IARAE.EMPLOYEE_ID AND IARA.R_A_FLAG='A') where IARAE.STATUS='E'
GROUP BY IARA.EMPLOYEE_ID) AA ON (AA.EMPLOYEE_ID=E.EMPLOYEE_ID)

              WHERE 1           =1
              AND RA.STATUS        = 'E' AND E.STATUS='E' AND RE.STATUS='E' AND AE.STATUS='E'
              {$condition}";
    return EntityHelper::rawQueryResult($this->adapter, $sql);
  }

  public function getAlternateRecmApprover($employee_id, $rA)
  {
    $sql = "  
            SELECT R_A_ID FROM HRIS_ALTERNATE_R_A WHERE R_A_FLAG='{$rA}' and employee_id={$employee_id}";
    return Helper::extractDbData(EntityHelper::rawQueryResult($this->adapter, $sql));
  }

  public function getEmployeeForOverride($data)
  {
    $boundedParams = [];
    $condition = EntityHelper::getSearchConditonBounded($data['companyId'], $data['branchId'], $data['departmentId'], $data['positionId'], $data['designationId'], $data['serviceTypeId'], $data['serviceEventTypeId'], $data['employeeTypeId'], $data['employeeId']);
    $boundedParams = array_merge($boundedParams, $condition['parameter']);

    $typeCondition = "";
    if ($data['type'] != null || $data['type'] != "") {
      $typeCondition = "AND TYPE = :type";
      $boundedParams['type'] = $data['type'];
    }

    $leaveTypeCondition = "";
    if ($data['type'] == 'LV' && ($data['typeId'] != null || $data['typeId'] != -1)) {
      $leaveTypeCondition = "AND TYPE_ID = :typeId";
      $boundedParams['typeId'] = $data['typeId'];
    }

    if ($typeCondition == "" || $typeCondition == null) {
      $sql = "";
    } else {

      $sql = "SELECT E.EMPLOYEE_CODE AS EMPLOYEE_CODE, 
                E.FULL_NAME AS EMPLOYEE_NAME,
                E.EMPLOYEE_ID AS EMPLOYEE_ID,
                NN.* FROM 
                (SELECT RAO.EMPLOYEE_ID AS EMPLOYEE_ID1,
                E1.FULL_NAME AS RECOMMENDER,
                E2.FULL_NAME AS APPROVER,
                RAO.TYPE AS TYPE,
                RAO.TYPE_ID AS TYPE_ID,
                case when RAO.STATUS = 'E' then 'Y'
                  else 'N' END AS ASSIGNED,
                CASE 
                when RAO.TYPE = 'LV'
                then LMS.LEAVE_ENAME
                when RAO.TYPE = 'TR'
                then TMS.TRAINING_NAME
                END as TYPE_NAME 
                from HRIS_REC_APP_OVERRIDE RAO 
                LEFT JOIN HRIS_EMPLOYEES E1
                ON (E1.EMPLOYEE_ID = RAO.RECOMMENDER)
                LEFT JOIN HRIS_EMPLOYEES E2
                ON (E2.EMPLOYEE_ID = RAO.APPROVER)
                LEFT JOIN HRIS_LEAVE_MASTER_SETUP LMS 
                ON (RAO.TYPE = 'LV' AND RAO.TYPE_ID = LMS.LEAVE_ID)
                LEFT JOIN HRIS_TRAINING_MASTER_SETUP TMS 
                ON (RAO.TYPE = 'TR' AND RAO.TYPE_ID = TMS.TRAINING_ID)
                where RAO.STATUS = 'E'
                {$typeCondition} {$leaveTypeCondition}
                ) NN
                RIGHT JOIN HRIS_EMPLOYEES E
                ON (E.EMPLOYEE_ID = NN.EMPLOYEE_ID1) 
                where 1=1 AND E.STATUS = 'E'
                {$condition['sql']} order by type_id
                ";
    }
    $statement = $this->adapter->query($sql);
    return $statement->execute($boundedParams);
  }

  public function updateStatus($employeeId, $updateData)
  {
    $boundedParams = [];
    $leaveTypeCheck = "";
    if ($updateData['type'] == 'LV') {
      $leaveTypeCheck = "AND TYPE_ID = :leaveType";
      $boundedParams['leaveType'] = $updateData['leaveType'];
    }
    $updateSql = "UPDATE HRIS_REC_APP_OVERRIDE SET STATUS = 'D' where employee_id = :employeeId and TYPE = :type " . $leaveTypeCheck;
    $boundedParams['employeeId'] = $employeeId;
    $boundedParams['type'] = $updateData['type'];
    EntityHelper::rawQueryResult($this->adapter, $updateSql, $boundedParams);
    return;
  }

  public function updateOverride($employeeId, $updateData)
  {
    $sql = '';
    $boundedParams = [];
    $leaveTypeCheck = "";
    if ($updateData['type'] == 'LV') {
      $leaveTypeCheck = "AND TYPE_ID = :leaveType";
      $boundedParams['leaveType'] = $updateData['leaveType'];
    }

    if ($updateData['recommender'] == "NULL" && $updateData['approver'] == "NULL") {
      $sql = "DELETE FROM HRIS_REC_APP_OVERRIDE WHERE EMPLOYEE_ID = :employeeId and TYPE = 'type' {$leaveTypeCheck}";
      $boundedParams['employeeId'] = $employeeId;
      $boundedParams['type'] = $updateData['type'];
    } else {
      $sql = "
            DECLARE
                  p_employee_id   NUMBER := :employeeId;
                  p_type          VARCHAR2(5) := :type;
                  p_type_id       NUMBER := :leaveType;
                  p_recommender   NUMBER := :recommender;  
                  p_approver      NUMBER := :approver;
                  v_update        NUMBER := 1;
                  v_employee_id   NUMBER;
                BEGIN
                  BEGIN
                    SELECT employee_id
                    INTO v_employee_id
                    FROM HRIS_REC_APP_OVERRIDE
                    WHERE employee_id = p_employee_id 
                    AND TYPE = p_type {$leaveTypeCheck};
                  EXCEPTION
                  WHEN no_data_found THEN
                    INSERT INTO hris_rec_app_override (
                        employee_id,
                        recommender,
                        approver,
                        type,
                        type_id,
                        status,
                        created_dt,
                        modified_dt,
                        created_by,
                        modified_by
                    ) VALUES (
                        p_employee_id,
                        p_recommender,
                        p_approver,
                        p_type,
                        p_type_id,
                        'E',
                        trunc(sysdate),
                        trunc(sysdate),
                        NULL,
                        NULL
                    );
                    v_update := 0;
                  END;
                  IF ( v_update = 1 ) THEN
                    UPDATE HRIS_REC_APP_OVERRIDE
                    SET recommender      = p_recommender, 
                    approver = p_approver, status = 'E'
                    WHERE employee_id = p_employee_id and TYPE = p_type {$leaveTypeCheck};
                  END IF;
                  COMMIT;
                END;
            ";
      $boundedParams['employeeId'] = $employeeId;
      $boundedParams['type'] = $updateData['type'];
      $boundedParams['leaveType'] = $updateData['leaveType'];
      $boundedParams['recommender'] = $updateData['recommender'];
      $boundedParams['approver'] = $updateData['approver'];
    }
    EntityHelper::rawQueryResult($this->adapter, $sql, $boundedParams);
    return;
  }
}
