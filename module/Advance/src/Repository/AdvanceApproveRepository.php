<?php

namespace Advance\Repository;

use Advance\Model\AdvanceRequestModel;
use Advance\Model\AdvanceSetupModel;
use Application\Model\Model;
use Application\Repository\RepositoryInterface;
use Zend\Db\Adapter\AdapterInterface;
use Zend\Db\Sql\Expression;
use Zend\Db\Sql\Sql;
use Zend\Db\TableGateway\TableGateway;

class AdvanceApproveRepository implements RepositoryInterface {

    private $adapter;
    private $tableGateway;

    public function __construct(AdapterInterface $adapter) {
        $this->tableGateway = new TableGateway(AdvanceRequestModel::TABLE_NAME, $adapter);
        $this->adapter = $adapter;
    }

    public function add(Model $model) {
        
    }

    public function delete($id) {
        
    }

    public function edit(Model $model, $id) {
        $editData = $model->getArrayCopyForDB();
        $this->tableGateway->update($editData, [AdvanceRequestModel::ADVANCE_REQUEST_ID => $id]);
        if ($editData['STATUS'] == 'AP') {
            $this->hris_advance_request_proc($id);
        }
    }

    private function hris_advance_request_proc($id) {
        $sql = "BEGIN
                  HRIS_ADVANCE_REQUEST_PROC({$id},'Y');
                END;";
        $statement = $this->adapter->query($sql);
        $result = $statement->execute();
		
		//print_r($result);die;
		
        return $result;
    }

    public function fetchAll() {
        
    }

    public function fetchById($id) {
        $sql = new Sql($this->adapter);
        $select = $sql->select();

        $select->columns([
            new Expression("AR.ADVANCE_REQUEST_ID AS ADVANCE_REQUEST_ID"),
            new Expression("AR.ADVANCE_ID AS ADVANCE_ID"),
            new Expression("AR.EMPLOYEE_ID AS EMPLOYEE_ID"),
            new Expression("INITCAP(TO_CHAR(AR.REQUESTED_DATE, 'DD-MON-YYYY')) AS REQUESTED_DATE"),
            new Expression("INITCAP(TO_CHAR(AR.DATE_OF_ADVANCE, 'DD-MON-YYYY')) AS DATE_OF_ADVANCE"),
            new Expression("INITCAP(TO_CHAR(AR.RECOMMENDED_DATE, 'DD-MON-YYYY')) AS RECOMMENDED_DATE"),
            new Expression("INITCAP(TO_CHAR(AR.APPROVED_DATE, 'DD-MON-YYYY')) AS APPROVED_DATE"),
            new Expression("AR.REQUESTED_AMOUNT AS REQUESTED_AMOUNT"),
            new Expression("AR.REASON AS REASON"),
            new Expression("AR.REASON AS REASON"),
            new Expression("AR.STATUS AS STATUS"),
            new Expression("LEAVE_STATUS_DESC(TRIM(AR.STATUS)) AS STATUS_DETAIL"),
            new Expression("AR.RECOMMENDED_REMARKS AS RECOMMENDED_REMARKS"),
            new Expression("AR.APPROVED_REMARKS AS APPROVED_REMARKS"),
            new Expression("AR.DEDUCTION_TYPE AS DEDUCTION_TYPE"),
            new Expression("AR.DEDUCTION_RATE AS DEDUCTION_RATE"),
            new Expression("AR.DEDUCTION_IN AS DEDUCTION_IN"),
            new Expression("AR.DEDUCTION_TYPE AS DEDUCTION_TYPE"),
            new Expression("AR.OVERRIDE_RECOMMENDER_ID AS OVERRIDE_RECOMMENDER_ID"),
            new Expression("AR.OVERRIDE_APPROVER_ID AS OVERRIDE_APPROVER_ID"),
            new Expression("(CASE WHEN AR.DEDUCTION_TYPE = 'M' THEN 'MONTH' ELSE 'SALARY' END) AS DEDUCTION_TYPE_NAME"),
            new Expression("(CASE
              WHEN AR.OVERRIDE_RECOMMENDER_ID IS NOT NULL THEN OVR.FULL_NAME
              ELSE RECM.FULL_NAME
              END) AS RECOMMENDER_NAME"),
            new Expression("(CASE
              WHEN AR.OVERRIDE_RECOMMENDER_ID IS NOT NULL THEN OVA.FULL_NAME
              ELSE APRV.FULL_NAME
              END) AS APPROVER_NAME"),
                ], true);

        $select->from(['AR' => AdvanceRequestModel::TABLE_NAME])
                ->join(['A' => AdvanceSetupModel::TABLE_NAME], "A.ADVANCE_ID=AR.ADVANCE_ID", ['ADVANCE_CODE', 'ADVANCE_ENAME' => new Expression("INITCAP(A.ADVANCE_ENAME)")])
                ->join(['E' => 'HRIS_EMPLOYEES'], 'AR.EMPLOYEE_ID=E.EMPLOYEE_ID', ["FULL_NAME" => new Expression("INITCAP(E.FULL_NAME)"), "SALARY" => "SALARY"], "left")
                ->join(['E2' => "HRIS_EMPLOYEES"], "E2.EMPLOYEE_ID=AR.RECOMMENDED_BY", ['RECOMMENDED_BY_NAME' => new Expression("INITCAP(E2.FULL_NAME)")], "left")
                ->join(['E3' => "HRIS_EMPLOYEES"], "E3.EMPLOYEE_ID=AR.APPROVED_BY", ['APPROVED_BY_NAME' => new Expression("INITCAP(E3.FULL_NAME)")], "left")
                ->join(['RA' => "HRIS_RECOMMENDER_APPROVER"], "RA.EMPLOYEE_ID=AR.EMPLOYEE_ID", ['RECOMMENDER_ID' => 'RECOMMEND_BY', 'APPROVER_ID' => 'APPROVED_BY'], "left")
                ->join(['RECM' => "HRIS_EMPLOYEES"], "RECM.EMPLOYEE_ID=RA.RECOMMEND_BY", ['DEFAULT_RECOMMENDER_NAME' => new Expression("INITCAP(RECM.FULL_NAME)")], "left")
                ->join(['APRV' => "HRIS_EMPLOYEES"], "APRV.EMPLOYEE_ID=RA.APPROVED_BY", ['DEFAULT_APPROVER_NAME' => new Expression("INITCAP(APRV.FULL_NAME)")], "left")
                ->join(['OVR' => "HRIS_EMPLOYEES"], "OVR.EMPLOYEE_ID=AR.OVERRIDE_RECOMMENDER_ID", ['OV_RECOMMENDER_NAME' => new Expression("INITCAP(OVR.FULL_NAME)")], "left")
                ->join(['OVA' => "HRIS_EMPLOYEES"], "OVA.EMPLOYEE_ID=AR.OVERRIDE_APPROVER_ID", ['OV_APPROVER_NAME' => new Expression("INITCAP(OVA.FULL_NAME)")], "left");

        $select->where([
            "AR.ADVANCE_REQUEST_ID=" . $id
        ]);

        $statement = $sql->prepareStatementForSqlObject($select);
        $result = $statement->execute();
        return $result->current();
    }

    public function getAllFiltered($search) {

        $condition = "";

        if (isset($search['fromDate']) && $search['fromDate'] != null) {
            $condition .= " AND AR.REQUESTED_DATE>=TO_DATE('{$search['fromDate']}','DD-MM-YYYY') ";
        }
        if (isset($search['fromDate']) && $search['toDate'] != null) {
            $condition .= " AND AR.REQUESTED_DATE<=TO_DATE('{$search['toDate']}','DD-MM-YYYY') ";
        }

        $employeeId = $search['employeeId'];


        if (isset($search['status']) && $search['status'] != null && $search['status'] != -1) {
            if (gettype($search['status']) === 'array') {
                $csv = "";
                for ($i = 0; $i < sizeof($search['status']); $i++) {
                    if ($i == 0) {
                        $csv = "'{$search['status'][$i]}'";
                    } else {
                        $csv .= ",'{$search['status'][$i]}'";
                    }
                }
                $condition .= "AND AR.STATUS IN ({$csv})";
            } else if ($search['status'] == 'OVERRIDE') {
                $condition .= " AND (
                    (
                        U.EMPLOYEE_ID=(CASE WHEN OVERRIDE_RECOMMENDER_ID IS NOT NULL 
                        THEN OVERRIDE_RECOMMENDER_ID ELSE
                        RA.RECOMMEND_BY END)
                      AND
                        AR.STATUS = 'RQ'
                    ) OR (
                        U.EMPLOYEE_ID=(CASE WHEN OVERRIDE_APPROVER_ID IS NOT NULL 
                        THEN OVERRIDE_APPROVER_ID ELSE
                        RA.APPROVED_BY END)
                      AND
                        AR.STATUS = 'RC'
                    )
                  )";
            } else {
                $condition .= "AND AR.STATUS IN ('{$search['status']}')";
            }
        }

        $sql = "SELECT
          AR.ADVANCE_REQUEST_ID AS ADVANCE_REQUEST_ID,
          INITCAP(TO_CHAR(AR.REQUESTED_DATE,'DD-MON-YYYY') ) AS REQUESTED_DATE,
          INITCAP(TO_CHAR(AR.REQUESTED_DATE,'DD-MON-YYYY') ) AS REQUESTED_DATE_AD,
          BS_DATE(TO_CHAR(AR.REQUESTED_DATE,'DD-MON-YYYY') ) AS REQUESTED_DATE_BS,
          INITCAP(TO_CHAR(AR.DATE_OF_ADVANCE,'DD-MON-YYYY') ) AS DATE_OF_ADVANCE,
          INITCAP(TO_CHAR(AR.DATE_OF_ADVANCE,'DD-MON-YYYY') ) AS DATE_OF_ADVANCE_AD,
          BS_DATE(TO_CHAR(AR.DATE_OF_ADVANCE,'DD-MON-YYYY') ) AS DATE_OF_ADVANCE_BS,
          INITCAP(TO_CHAR(AR.RECOMMENDED_DATE,'DD-MON-YYYY') ) AS RECOMMENDED_DATE,
          INITCAP(TO_CHAR(AR.APPROVED_DATE,'DD-MON-YYYY') ) AS APPROVED_DATE,
          AR.REQUESTED_AMOUNT AS REQUESTED_AMOUNT,
          AR.REASON AS REASON,
          E.EMPLOYEE_CODE AS EMPLOYEE_CODE,
          AR.REASON AS REASON,
          AR.STATUS AS STATUS,
          AR.RECOMMENDED_REMARKS AS RECOMMENDED_REMARKS,
          AR.APPROVED_REMARKS AS APPROVED_REMARKS,
          AR.DEDUCTION_TYPE AS DEDUCTION_TYPE,
          AR.DEDUCTION_RATE AS DEDUCTION_RATE,
          AR.DEDUCTION_IN AS DEDUCTION_IN,
          AR.DEDUCTION_TYPE AS DEDUCTION_TYPE,
          (
            CASE
              WHEN AR.DEDUCTION_TYPE = 'M' THEN 'MONTH'
              ELSE 'SALARY'
            END
          ) AS DEDUCTION_TYPE_NAME,
          A.ADVANCE_CODE AS ADVANCE_CODE,
          INITCAP(A.ADVANCE_ENAME) AS ADVANCE_ENAME,
          INITCAP(E.FULL_NAME) AS EMPLOYEE_NAME,
          INITCAP(E2.FULL_NAME) AS RECOMMENDED_BY_NAME,
          INITCAP(E3.FULL_NAME) AS APPROVED_BY_NAME,
          (
            CASE
              WHEN AR.OVERRIDE_RECOMMENDER_ID IS NOT NULL THEN AR.OVERRIDE_RECOMMENDER_ID
              ELSE RA.RECOMMEND_BY
            END
          ) AS RECOMMENDER_ID,
          (
            CASE
              WHEN AR.OVERRIDE_APPROVER_ID IS NOT NULL THEN AR.OVERRIDE_APPROVER_ID
              ELSE RA.APPROVED_BY
            END
          ) AS APPROVER_ID,
          INITCAP(
            CASE
              WHEN
                AR.OVERRIDE_RECOMMENDER_ID
              IS NOT NULL THEN
                OVR.FULL_NAME
              ELSE
                RECM.FULL_NAME
            END
          ) AS RECOMMENDER_NAME,
          INITCAP(
            CASE
              WHEN
                AR.OVERRIDE_APPROVER_ID
              IS NOT NULL THEN
                OVA.FULL_NAME
              ELSE
                APRV.FULL_NAME
            END
          ) AS APPROVER_NAME,
          LEAVE_STATUS_DESC(TRIM(AR.STATUS)) AS STATUS_DETAIL,
          AR.OVERRIDE_RECOMMENDER_ID AS OVERRIDE_RECOMMENDER_ID,
          AR.OVERRIDE_APPROVER_ID AS OVERRIDE_APPROVER_ID,
          REC_APP_ROLE(
            U.EMPLOYEE_ID,
            (
              CASE
                WHEN AR.OVERRIDE_RECOMMENDER_ID IS NOT NULL THEN AR.OVERRIDE_RECOMMENDER_ID
                ELSE RA.RECOMMEND_BY
              END
            ),
            (
              CASE
                WHEN AR.OVERRIDE_APPROVER_ID IS NOT NULL THEN AR.OVERRIDE_APPROVER_ID
                ELSE RA.APPROVED_BY
              END
            )
          ) AS ROLE,
          REC_APP_ROLE_NAME(U.EMPLOYEE_ID,(
        CASE
          WHEN AR.OVERRIDE_RECOMMENDER_ID IS NOT NULL THEN AR.OVERRIDE_RECOMMENDER_ID
          ELSE RA.RECOMMEND_BY
        END
      ),
      (
      CASE
        WHEN AR.OVERRIDE_APPROVER_ID IS NOT NULL THEN AR.OVERRIDE_APPROVER_ID
        ELSE RA.APPROVED_BY
      END
    )) AS YOUR_ROLE
        FROM
          HRIS_EMPLOYEE_ADVANCE_REQUEST AR
          INNER JOIN HRIS_ADVANCE_MASTER_SETUP A ON A.ADVANCE_ID = AR.ADVANCE_ID
          LEFT JOIN HRIS_EMPLOYEES E ON AR.EMPLOYEE_ID = E.EMPLOYEE_ID
          LEFT JOIN HRIS_EMPLOYEES E2 ON E2.EMPLOYEE_ID = AR.RECOMMENDED_BY
          LEFT JOIN HRIS_EMPLOYEES E3 ON E3.EMPLOYEE_ID = AR.APPROVED_BY
          LEFT JOIN HRIS_RECOMMENDER_APPROVER RA ON RA.EMPLOYEE_ID = AR.EMPLOYEE_ID
          LEFT JOIN HRIS_EMPLOYEES RECM ON RECM.EMPLOYEE_ID = RA.RECOMMEND_BY
          LEFT JOIN HRIS_EMPLOYEES APRV ON APRV.EMPLOYEE_ID = RA.APPROVED_BY
          LEFT JOIN HRIS_EMPLOYEES OVR ON OVR.EMPLOYEE_ID = AR.OVERRIDE_RECOMMENDER_ID
          LEFT JOIN HRIS_EMPLOYEES OVA ON OVA.EMPLOYEE_ID = AR.OVERRIDE_APPROVER_ID
          LEFT JOIN HRIS_EMPLOYEES U ON (
              U.EMPLOYEE_ID = (
                CASE
                  WHEN AR.OVERRIDE_RECOMMENDER_ID IS NOT NULL THEN AR.OVERRIDE_RECOMMENDER_ID
                  ELSE RA.RECOMMEND_BY
                END
              )
            OR
              U.EMPLOYEE_ID = (
                CASE
                  WHEN AR.OVERRIDE_APPROVER_ID IS NOT NULL THEN AR.OVERRIDE_APPROVER_ID
                  ELSE RA.APPROVED_BY
                END
              )
          )
        WHERE
            U.EMPLOYEE_ID = $employeeId
          {$condition}
        ORDER BY AR.REQUESTED_DATE DESC";

        $statement = $this->adapter->query($sql);
        $result = $statement->execute();
        return $result;
    }

}
