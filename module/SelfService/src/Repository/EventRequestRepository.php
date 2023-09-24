<?php

namespace SelfService\Repository;

use Application\Helper\Helper;
use Application\Helper\EntityHelper;
use Application\Model\Model;
use Application\Repository\RepositoryInterface;
use SelfService\Model\EventRequest;
use Zend\Db\Adapter\AdapterInterface;
use Zend\Db\Sql\Expression;
use Zend\Db\Sql\Select;
use Zend\Db\Sql\Sql;
use Zend\Db\TableGateway\TableGateway;

class EventRequestRepository implements RepositoryInterface
{

  private $tableGateway;
  private $adapter;

  public function __construct(AdapterInterface $adapter)
  {
    $this->adapter = $adapter;
    $this->tableGateway = new TableGateway(EventRequest::TABLE_NAME, $adapter);
  }

  public function add(Model $model)
  {
    $this->tableGateway->insert($model->getArrayCopyForDB());
    $new = $model->getArrayCopyForDB();
    if ($model->status == 'AP') {
      EntityHelper::rawQueryResult($this->adapter, "
                BEGIN
                    HRIS_REATTENDANCE({$new['START_DATE']->getExpression()},{$new['EMPLOYEE_ID']},{$new['END_DATE']->getExpression()});
                END;
                ");
    }
  }

  public function delete($id)
  {
    $currentDate = Helper::getcurrentExpressionDate();
    $this->tableGateway->update([EventRequest::STATUS => 'C', EventRequest::MODIFIED_DATE => $currentDate], [EventRequest::REQUEST_ID => $id]);
    $rewardSql = "
                DECLARE
                  V_EVENT_ID HRIS_EMPLOYEE_EVENT_REQUEST.EVENT_ID%TYPE;
                  V_START_DATE HRIS_EMPLOYEE_EVENT_REQUEST.START_DATE%TYPE;
                  V_END_DATE HRIS_EMPLOYEE_EVENT_REQUEST.END_DATE%TYPE;
                  V_EMPLOYEE_ID HRIS_EMPLOYEE_EVENT_REQUEST.EMPLOYEE_ID%TYPE;
                  V_STATUS HRIS_EMPLOYEE_EVENT_REQUEST.STATUS%TYPE;
                  V_REQUEST_ID HRIS_EMPLOYEE_EVENT_REQUEST.REQUEST_ID%TYPE:= {$id};
                  V_ASSIGNED CHAR(1 BYTE)                                    :=NULL;
                  V_DURATION HRIS_EMPLOYEE_EVENT_REQUEST.DURATION%TYPE;
                BEGIN
                  SELECT EVENT_ID,
                    TRUNC( START_DATE ),
                    TRUNC( END_DATE ),
                    EMPLOYEE_ID,
                    STATUS,
                    DURATION
                  INTO V_EVENT_ID,
                    V_START_DATE,
                    V_END_DATE,
                    V_EMPLOYEE_ID,
                    V_STATUS,
                    V_DURATION
                  FROM HRIS_EMPLOYEE_EVENT_REQUEST
                  WHERE REQUEST_ID =V_REQUEST_ID;
                  --
                  
                    IF V_EVENT_ID IS NOT NULL THEN
                    SELECT (
                      CASE
                        WHEN COUNT(*)>0
                        THEN 'Y'
                        ELSE 'N'
                      END)
                    INTO V_ASSIGNED
                    FROM HRIS_EMPLOYEE_EVENT_ASSIGN
                    WHERE EVENT_ID = V_EVENT_ID
                    AND EMPLOYEE_ID   =V_EMPLOYEE_ID;
                    IF V_ASSIGNED    ='N' AND V_STATUS='AP' THEN
                      INSERT
                      INTO HRIS_EMPLOYEE_EVENT_ASSIGN
                        (
                          EVENT_ID,
                          EMPLOYEE_ID,
                          STATUS,
                          CREATED_DATE,
                          CREATED_BY
                        )
                        VALUES
                        (
                          V_EVENT_ID,
                          V_EMPLOYEE_ID,
                          'E',
                          TRUNC(SYSDATE),
                          V_EMPLOYEE_ID
                        );
                        
                        


                    END IF;
                    -- TO DELETE IF ASSIGNED
                    IF(V_ASSIGNED ='Y' AND V_STATUS='C')
                    THEN
                    DELETE FROM HRIS_EMPLOYEE_EVENT_ASSIGN WHERE 
                    EVENT_ID=V_EVENT_ID AND EMPLOYEE_ID=V_EMPLOYEE_ID;
                    END IF;
                    IF V_STATUS IN ('AP','C') THEN
                        HRIS_REATTENDANCE(V_START_DATE,V_EMPLOYEE_ID,V_END_DATE);
                    END IF;
                    END IF; 
                EXCEPTION
                WHEN NO_DATA_FOUND THEN
                  DBMS_OUTPUT.PUT('NO DATA FOUND FOR ID =>'|| V_REQUEST_ID);
                END;
            ";
    // echo '<pre>';print_r($rewardSql);die;
    EntityHelper::rawQueryResult($this->adapter, $rewardSql);
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
      new Expression("TR.REQUEST_ID AS REQUEST_ID"),
      new Expression("TR.EMPLOYEE_ID AS EMPLOYEE_ID"),
      new Expression("INITCAP(TO_CHAR(TR.REQUESTED_DATE, 'DD-MON-YYYY')) AS REQUESTED_DATE"),
      new Expression("INITCAP(TO_CHAR(TR.REQUESTED_DATE, 'DD-MON-YYYY')) AS REQUESTED_DATE_AD"),
      new Expression("BS_DATE(TO_CHAR(TR.REQUESTED_DATE, 'DD-MON-YYYY')) AS REQUESTED_DATE_BS"),
      new Expression("INITCAP(TO_CHAR(TR.START_DATE, 'DD-MON-YYYY')) AS START_DATE"),
      new Expression("INITCAP(TO_CHAR(TR.START_DATE, 'DD-MON-YYYY')) AS START_DATE_AD"),
      new Expression("BS_DATE(TO_CHAR(TR.START_DATE, 'DD-MON-YYYY')) AS START_DATE_BS"),
      new Expression("INITCAP(TO_CHAR(TR.END_DATE, 'DD-MON-YYYY')) AS END_DATE"),
      new Expression("INITCAP(TO_CHAR(TR.END_DATE, 'DD-MON-YYYY')) AS END_DATE_AD"),
      new Expression("BS_DATE(TO_CHAR(TR.END_DATE, 'DD-MON-YYYY')) AS END_DATE_BS"),
      new Expression("TR.EVENT_ID AS EVENT_ID"),
      new Expression("TR.TITLE AS TITLE"),
      new Expression("TR.DESCRIPTION AS DESCRIPTION"),
      new Expression("TR.DURATION AS DURATION"),
      new Expression("TR.EVENT_TYPE AS EVENT_TYPE"),
      new Expression("(CASE WHEN TR.EVENT_TYPE = 'CC' THEN 'Company Contribution' ELSE 'Personal' END) AS EVENT_TYPE_DETAIL"),
      new Expression("TR.REMARKS AS REMARKS"),
      new Expression("TR.STATUS AS STATUS"),
      new Expression("LEAVE_STATUS_DESC(TR.STATUS) AS STATUS_DETAIL"),
      new Expression("TR.RECOMMENDED_BY AS RECOMMENDED_BY"),
      new Expression("INITCAP(TO_CHAR(TR.RECOMMENDED_DATE, 'DD-MON-YYYY')) AS RECOMMENDED_DATE"),
      new Expression("TR.RECOMMENDED_REMARKS AS RECOMMENDED_REMARKS"),
      new Expression("TR.APPROVED_BY AS APPROVED_BY"),
      new Expression("INITCAP(TO_CHAR(TR.APPROVED_DATE, 'DD-MON-YYYY')) AS APPROVED_DATE"),
      new Expression("TR.APPROVED_REMARKS AS APPROVED_REMARKS"),
      new Expression("INITCAP(TO_CHAR(TR.MODIFIED_DATE, 'DD-MON-YYYY')) AS MODIFIED_DATE"),
      new Expression("(CASE WHEN TR.STATUS = 'RQ' THEN 'Y' ELSE 'N' END) AS ALLOW_EDIT"),
      new Expression("(CASE WHEN TR.STATUS IN ('RQ','RC','AP') THEN 'Y' ELSE 'N' END) AS ALLOW_DELETE"),
    ], true);

    $select->from(['TR' => EventRequest::TABLE_NAME])
      ->join(['E' => 'HRIS_EMPLOYEES'], 'E.EMPLOYEE_ID=TR.EMPLOYEE_ID', ["FULL_NAME" => new Expression("INITCAP(E.FULL_NAME)")], "left")
      ->join(['E2' => "HRIS_EMPLOYEES"], "E2.EMPLOYEE_ID=TR.RECOMMENDED_BY", ['RECOMMENDED_BY_NAME' => new Expression("INITCAP(E2.FULL_NAME)")], "left")
      ->join(['E3' => "HRIS_EMPLOYEES"], "E3.EMPLOYEE_ID=TR.APPROVED_BY", ['APPROVED_BY_NAME' => new Expression("INITCAP(E3.FULL_NAME)")], "left")
      ->join(['RA' => "HRIS_RECOMMENDER_APPROVER"], "RA.EMPLOYEE_ID=TR.EMPLOYEE_ID", ['RECOMMENDER_ID' => 'RECOMMEND_BY', 'APPROVER_ID' => 'APPROVED_BY'], "left")
      ->join(['RECM' => "HRIS_EMPLOYEES"], "RECM.EMPLOYEE_ID=RA.RECOMMEND_BY", ['RECOMMENDER_NAME' => new Expression("INITCAP(RECM.FULL_NAME)")], "left")
      ->join(['APRV' => "HRIS_EMPLOYEES"], "APRV.EMPLOYEE_ID=RA.APPROVED_BY", ['APPROVER_NAME' => new Expression("INITCAP(APRV.FULL_NAME)")], "left");

    $select->where(["TR.REQUEST_ID" => $id]);
    $select->order(["TR.START_DATE" => Select::ORDER_DESCENDING]);
    $statement = $sql->prepareStatementForSqlObject($select);
    $result = $statement->execute();
    return $result->current();
  }

  public function getAllByEmployeeId($employeeId): array
  {
    $sql = new Sql($this->adapter);
    $select = $sql->select();
    $select->columns([
      new Expression("TR.REQUEST_ID AS REQUEST_ID"),
      new Expression("TR.EMPLOYEE_ID AS EMPLOYEE_ID"),
      new Expression("INITCAP(TO_CHAR(TR.REQUESTED_DATE, 'DD-MON-YYYY')) AS REQUESTED_DATE_AD"),
      new Expression("BS_DATE(TO_CHAR(TR.REQUESTED_DATE, 'DD-MON-YYYY')) AS REQUESTED_DATE_BS"),
      new Expression("INITCAP(TO_CHAR(TR.START_DATE, 'DD-MON-YYYY')) AS START_DATE_AD"),
      new Expression("BS_DATE(TO_CHAR(TR.START_DATE, 'DD-MON-YYYY')) AS START_DATE_BS"),
      new Expression("INITCAP(TO_CHAR(TR.END_DATE, 'DD-MON-YYYY')) AS END_DATE_AD"),
      new Expression("BS_DATE(TO_CHAR(TR.END_DATE, 'DD-MON-YYYY')) AS END_DATE_BS"),
      new Expression("TR.EVENT_ID AS EVENT_ID"),
      new Expression("TR.TITLE AS TITLE"),
      new Expression("TR.DESCRIPTION AS DESCRIPTION"),
      new Expression("TR.DURATION AS DURATION"),
      new Expression("TR.EVENT_TYPE AS EVENT_TYPE"),
      new Expression("(CASE WHEN TR.EVENT_TYPE = 'CC' THEN 'Company Contribution' ELSE 'Personal' END) AS EVENT_TYPE_DETAIL"),
      new Expression("TR.REMARKS AS REMARKS"),
      new Expression("TR.STATUS AS STATUS"),
      new Expression("LEAVE_STATUS_DESC(TR.STATUS) AS STATUS_DETAIL"),
      new Expression("TR.RECOMMENDED_BY AS RECOMMENDED_BY"),
      new Expression("INITCAP(TO_CHAR(TR.RECOMMENDED_DATE, 'DD-MON-YYYY')) AS RECOMMENDED_DATE"),
      new Expression("TR.RECOMMENDED_REMARKS AS RECOMMENDED_REMARKS"),
      new Expression("TR.APPROVED_BY AS APPROVED_BY"),
      new Expression("INITCAP(TO_CHAR(TR.APPROVED_DATE, 'DD-MON-YYYY')) AS APPROVED_DATE"),
      new Expression("TR.APPROVED_REMARKS AS APPROVED_REMARKS"),
      new Expression("INITCAP(TO_CHAR(TR.MODIFIED_DATE, 'DD-MON-YYYY')) AS MODIFIED_DATE"),
      new Expression("(CASE WHEN TR.STATUS = 'RQ' THEN 'Y' ELSE 'N' END) AS ALLOW_EDIT"),
      new Expression("(CASE WHEN TR.STATUS IN ('RQ','RC','AP') THEN 'Y' ELSE 'N' END) AS ALLOW_DELETE"),
    ], true);

    $select->from(['TR' => EventRequest::TABLE_NAME])
      ->join(['E' => 'HRIS_EMPLOYEES'], 'E.EMPLOYEE_ID=TR.EMPLOYEE_ID', ["FULL_NAME" => new Expression("INITCAP(E.FULL_NAME)")], "left")
      ->join(['E2' => "HRIS_EMPLOYEES"], "E2.EMPLOYEE_ID=TR.RECOMMENDED_BY", ['RECOMMENDED_BY_NAME' => new Expression("INITCAP(E2.FULL_NAME)")], "left")
      ->join(['E3' => "HRIS_EMPLOYEES"], "E3.EMPLOYEE_ID=TR.APPROVED_BY", ['APPROVED_BY_NAME' => new Expression("INITCAP(E3.FULL_NAME)")], "left")
      ->join(['RA' => "HRIS_RECOMMENDER_APPROVER"], "RA.EMPLOYEE_ID=TR.EMPLOYEE_ID", ['RECOMMENDER_ID' => 'RECOMMEND_BY', 'APPROVER_ID' => 'APPROVED_BY'], "left")
      ->join(['RECM' => "HRIS_EMPLOYEES"], "RECM.EMPLOYEE_ID=RA.RECOMMEND_BY", ['RECOMMENDER_NAME' => new Expression("INITCAP(RECM.FULL_NAME)")], "left")
      ->join(['APRV' => "HRIS_EMPLOYEES"], "APRV.EMPLOYEE_ID=RA.APPROVED_BY", ['APPROVER_NAME' => new Expression("INITCAP(APRV.FULL_NAME)")], "left");
    $select->where([
      "E.EMPLOYEE_ID" => $employeeId
    ]);
    $select->order("TR.START_DATE DESC");
    $statement = $sql->prepareStatementForSqlObject($select);
    // echo '<pre>';print_r($statement);die;
    $result = $statement->execute();
    return iterator_to_array($result, false);
  }
}
