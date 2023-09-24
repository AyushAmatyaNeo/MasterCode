<?php

namespace Advance\Repository;

use Advance\Model\AdvanceRequestModel;
use Advance\Model\AdvanceSetupModel;
use Application\Helper\Helper;
use Application\Model\Model;
use Application\Repository\RepositoryInterface;
use Zend\Db\Adapter\AdapterInterface;
use Zend\Db\Sql\Expression;
use Zend\Db\Sql\Sql;
use Zend\Db\TableGateway\TableGateway;

class AdvanceRequestRepository implements RepositoryInterface
{

    private $adapter;
    private $tableGateway;

    public function __construct(AdapterInterface $adapter)
    {
        $this->tableGateway = new TableGateway(AdvanceRequestModel::TABLE_NAME, $adapter);
        $this->adapter = $adapter;
    }

    public function add(Model $model)
    {
        $this->tableGateway->insert($model->getArrayCopyForDB());
    }

    public function delete($id)
    {
        $this->tableGateway->update([AdvanceRequestModel::STATUS => 'C'], [AdvanceRequestModel::ADVANCE_REQUEST_ID => $id]);
    }

    public function edit(Model $model, $id)
    {
    }

    public function fetchAll()
    {
    }

    public function fetchById($id)
    {
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
            new Expression("(CASE WHEN AR.DEDUCTION_RATE IS NOT NULL THEN AR.DEDUCTION_RATE ELSE A.DEDUCTION_RATE END ) AS DEDUCTION_RATE"),
            new Expression("AR.DEDUCTION_IN AS DEDUCTION_IN"),
            new Expression("AR.DEDUCTION_TYPE AS DEDUCTION_TYPE"),
            new Expression("AR.OVERRIDE_RECOMMENDER_ID AS OVERRIDE_RECOMMENDER_ID"),
            new Expression("AR.OVERRIDE_APPROVER_ID AS OVERRIDE_APPROVER_ID"),
            new Expression("AR.RECOMMENDED_BY AS RECOMMENDED_BY"),
            new Expression("AR.APPROVED_BY AS APPROVED_BY"),
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

    public function getfilterRecords($data)
    {
        $employeeId = $data['employeeId'];

        $sql = new Sql($this->adapter);
        $select = $sql->select();
        $select->columns([
            new Expression("AR.ADVANCE_REQUEST_ID AS ADVANCE_REQUEST_ID"),
            new Expression("INITCAP(TO_CHAR(AR.REQUESTED_DATE, 'DD-MON-YYYY')) AS REQUESTED_DATE"),
            new Expression("INITCAP(TO_CHAR(AR.REQUESTED_DATE, 'DD-MON-YYYY')) AS REQUESTED_DATE_AD"),
            new Expression("BS_DATE(TO_CHAR(AR.REQUESTED_DATE, 'DD-MON-YYYY')) AS REQUESTED_DATE_BS"),
            new Expression("INITCAP(TO_CHAR(AR.DATE_OF_ADVANCE, 'DD-MON-YYYY')) AS DATE_OF_ADVANCE"),
            new Expression("INITCAP(TO_CHAR(AR.DATE_OF_ADVANCE, 'DD-MON-YYYY')) AS DATE_OF_ADVANCE_AD"),
            new Expression("BS_DATE(TO_CHAR(AR.DATE_OF_ADVANCE, 'DD-MON-YYYY')) AS DATE_OF_ADVANCE_BS"),
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
            new Expression("(CASE WHEN AR.DEDUCTION_TYPE = 'M' THEN 'MONTH' ELSE 'SALARY' END) AS DEDUCTION_TYPE_NAME"),
            new Expression("(CASE WHEN AR.STATUS = 'RQ' THEN 'Y' ELSE 'N' END) AS ALLOW_EDIT"),
            new Expression("(CASE WHEN AR.STATUS IN ('RQ','RC') THEN 'Y' ELSE 'N' END) AS ALLOW_DELETE"),
        ], true);

        $select->from(['AR' => AdvanceRequestModel::TABLE_NAME])
            ->join(['A' => AdvanceSetupModel::TABLE_NAME], "A.ADVANCE_ID=AR.ADVANCE_ID", ['ADVANCE_CODE', 'ADVANCE_ENAME' => new Expression("INITCAP(A.ADVANCE_ENAME)")])
            ->join(['E' => 'HRIS_EMPLOYEES'], 'AR.EMPLOYEE_ID=E.EMPLOYEE_ID', ["FULL_NAME" => new Expression("INITCAP(E.FULL_NAME)")], "left")
            ->join(['E2' => "HRIS_EMPLOYEES"], "E2.EMPLOYEE_ID=AR.RECOMMENDED_BY", ['RECOMMENDED_BY_NAME' => new Expression("INITCAP(E2.FULL_NAME)")], "left")
            ->join(['E3' => "HRIS_EMPLOYEES"], "E3.EMPLOYEE_ID=AR.APPROVED_BY", ['APPROVED_BY_NAME' => new Expression("INITCAP(E3.FULL_NAME)")], "left")
            ->join(['RA' => "HRIS_RECOMMENDER_APPROVER"], "RA.EMPLOYEE_ID=AR.EMPLOYEE_ID", ['RECOMMENDER_ID' => 'RECOMMEND_BY', 'APPROVER_ID' => 'APPROVED_BY'], "left")
            ->join(['RECM' => "HRIS_EMPLOYEES"], "RECM.EMPLOYEE_ID=RA.RECOMMEND_BY", ['RECOMMENDER_NAME' => new Expression("INITCAP(RECM.FULL_NAME)")], "left")
            ->join(['APRV' => "HRIS_EMPLOYEES"], "APRV.EMPLOYEE_ID=RA.APPROVED_BY", ['APPROVER_NAME' => new Expression("INITCAP(APRV.FULL_NAME)")], "left")
            ->join(['OVR' => "HRIS_EMPLOYEES"], "OVR.EMPLOYEE_ID=AR.OVERRIDE_RECOMMENDER_ID", ['OV_RECOMMENDER_NAME' => new Expression("INITCAP(RECM.FULL_NAME)")], "left")
            ->join(['OVA' => "HRIS_EMPLOYEES"], "OVA.EMPLOYEE_ID=AR.OVERRIDE_APPROVER_ID", ['OV_APPROVER_NAME' => new Expression("INITCAP(APRV.FULL_NAME)")], "left");

        $select->where([
            "E.EMPLOYEE_ID=" . $employeeId
        ]);



        $select->order("AR.REQUESTED_DATE DESC");
        $statement = $sql->prepareStatementForSqlObject($select);
        //        
        //        echo $statement->getSql()
        //        die();
        $result = $statement->execute();
        return $result;
    }

    public function fetchAvailableAdvacenList($employee_id)
    {
        $sql = "SELECT 
                A.*
                FROM HRIS_EMPLOYEES E
                LEFT JOIN HRIS_SERVICE_TYPES ST ON(E.SERVICE_TYPE_ID=ST.SERVICE_TYPE_ID)
                LEFT JOIN HRIS_ADVANCE_MASTER_SETUP A ON (  (CASE A.ALLOWED_TO 
                        WHEN 'PER' THEN 'PERMANENT'
                        WHEN 'PRO' THEN 'PROBATION'
                        WHEN 'CON' THEN 'CONTRACT'
                        END=ST.TYPE) OR ALLOWED_TO='ALL')
                WHERE E.EMPLOYEE_ID={$employee_id}";

        $statement = $this->adapter->query($sql);
        $result = $statement->execute();
        return Helper::extractDbData($result);
    }

    public function getSalaryHistory($employee_id)
    {
        $sql = "SELECT amount as salary FROM hris_employee_salary_history WHERE EMPLOYEE_ID=$employee_id and pay_id in (select pay_id from hris_pay_setup where pay_code='BS')and is_enable='Y' and status='E'";
        $statement = $this->adapter->query($sql);
        $result = $statement->execute();
        return $result->current();
    }

    public function getPayrollBasicSalary($employee_id)
    {
        $sql = "select flat_value from hris_flat_value_detail where flat_id=(select flat_id from hris_flat_value_setup where flat_code='BS' and fiscal_year_id=(select fiscal_year_id from hris_fiscal_years where trunc(sysdate) between start_date and end_date)) and EMPLOYEE_ID=$employee_id  ";
        $statement = $this->adapter->query($sql);
        $result = $statement->execute();
        return $result->current();
    }
}
