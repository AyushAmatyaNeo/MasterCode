<?php
namespace SelfService\Repository;

use Application\Helper\EntityHelper;
use Application\Model\Model;
use Application\Repository\RepositoryInterface;
use SelfService\Model\TravelRequest;
use Zend\Db\Adapter\AdapterInterface;
use Zend\Db\Sql\Expression; 
use Zend\Db\Sql\Sql;
use Application\Helper\Helper;
use Zend\Db\TableGateway\TableGateway;
use Application\Repository\HrisRepository;

class TravelRequestRepository extends HrisRepository implements RepositoryInterface {

    protected $tableGateway;
    protected $adapter;
 
    public function __construct(AdapterInterface $adapter) {
        $this->adapter = $adapter;
        $this->tableGateway = new TableGateway(TravelRequest::TABLE_NAME, $adapter);
    } 


  
    
    public function linkTravelWithFiles(){
        if(!empty($_POST['fileUploadList'])){
            $filesList = $_POST['fileUploadList'];
            $filesList = implode(',', $filesList);

            $sql = "UPDATE HRIS_TRAVEL_FILES SET TRAVEL_ID = (SELECT MAX(TRAVEL_ID) FROM HRIS_EMPLOYEE_TRAVEL_REQUEST) 
                    WHERE FILE_ID IN($filesList)";
            $statement = $this->adapter->query($sql);
            $statement->execute();
        }
    } 

    public function fetchAttachmentsById($id){
      $sql = "SELECT * FROM HRIS_TRAVEL_FILES WHERE TRAVEL_ID = $id";
      $result = EntityHelper::rawQueryResult($this->adapter, $sql);
      return Helper::extractDbData($result);
    }
    
    public function travelCategory($empId)
    {
        $sql="SELECT
        t.level_no
    FROM
        hris_positions       p
        LEFT JOIN hris_employees       e ON p.position_id = e.position_id
        LEFT JOIN hris_travel_category t ON p.level_no = t.level_no
    WHERE
        e.employee_id = $empId";
       $statement=$this->adapter->query($sql);
       $result=$statement->execute();
       
       return $result ->current();
    }
    public function TravelRecordById($id){
        $sql="SELECT
        t.level_no,
        t.advance_amount
    FROM
        hris_positions       p
        LEFT JOIN hris_employees       e ON p.position_id = e.position_id
        LEFT JOIN hris_travel_category t ON p.level_no = t.level_no
    WHERE
        employee_id = $id";
        $statement=$this->adapter->query($sql);
        $result=$statement->execute();
        return $result->current();
    }


   

   
    public function add(Model $model) {
        $addData=$model->getArrayCopyForDB();
        // echo '<pre>';print_r($addData);die;
        $this->tableGateway->insert($addData);
        
        if ($addData['STATUS']=='AP' && date('Y-m-d', strtotime($model->fromDate)) <= date('Y-m-d')) {

            $sql = "BEGIN 
            HRIS_REATTENDANCE(:fromDate, :employeeId, :toDate);
               END; ";

            $boundedParameter = [];
            $boundedParameter['fromDate'] = $model->fromDate;
            $boundedParameter['employeeId'] = $model->employeeId;
            $boundedParameter['toDate'] = $model->toDate;

            $this->rawQuery($sql, $boundedParameter);
        }
        
    }

    public function delete($id) {
        $this->tableGateway->update([TravelRequest::STATUS => 'C'], [TravelRequest::TRAVEL_ID => $id]);
        EntityHelper::rawQueryResult($this->adapter, "
                DECLARE
                  V_FROM_DATE HRIS_EMPLOYEE_TRAVEL_REQUEST.FROM_DATE%TYPE;
                  V_TO_DATE HRIS_EMPLOYEE_TRAVEL_REQUEST.TO_DATE%TYPE;
                  V_EMPLOYEE_ID HRIS_EMPLOYEE_TRAVEL_REQUEST.EMPLOYEE_ID%TYPE;
                  V_STATUS HRIS_EMPLOYEE_TRAVEL_REQUEST.STATUS%TYPE;
                  V_TRAVEL_ID HRIS_EMPLOYEE_TRAVEL_REQUEST.TRAVEL_ID%TYPE:= {$id};
                BEGIN
                BEGIN
                HRIS_TRAVEL_CANCELLATION(V_TRAVEL_ID);
                END;
                  SELECT FROM_DATE ,
                    TO_DATE,
                    EMPLOYEE_ID,
                    STATUS
                  INTO V_FROM_DATE,
                    V_TO_DATE,
                    V_EMPLOYEE_ID,
                    V_STATUS
                  FROM HRIS_EMPLOYEE_TRAVEL_REQUEST
                  WHERE TRAVEL_ID =V_TRAVEL_ID;
                  --
                  IF V_STATUS IN ('AP','C') AND V_FROM_DATE < TRUNC(SYSDATE) THEN
                    HRIS_REATTENDANCE(V_FROM_DATE,V_EMPLOYEE_ID,V_TO_DATE);
                  END IF;
                EXCEPTION
                WHEN NO_DATA_FOUND THEN
                  DBMS_OUTPUT.PUT('NO DATA FOUND FOR ID =>'|| V_TRAVEL_ID);
                END;
");
    }
 
    public function edit(Model $model, $id) {
        $array = $model->getArrayCopyForDB();
        unset($array['EMPLOYEE_ID']);
        $this->tableGateway->update($array, [TravelRequest::TRAVEL_ID => $id]);

    }
    
    public function editHrTravel(Model $model, $id) {
        $sql="UPDATE hris_employee_travel_request
        SET
            employee_id = $model->employeeId,
            from_date =  $model->fromDate,
            to_date =  $model->toDate,
            destination =  $model->destination,
            departure =  $model->departure,
            purpose =  $model->purpose,
            requested_amount =  $model->requestedAmount,
            requested_type =  $model->requestedType,
            remarks =$model->remarks,
            recommended_remarks =  $model->recommendedRemarks,
            approved_remarks =  $model->approvedRemarks,
            transport_type =  $model->transportType,
            travel_category_id =  $model->travelCategory
        WHERE
            travel_id = $id";
            $statement = $this->adapter->query($sql);
            // echo '<pre>';print_r($statement);die;
            $result = $statement->execute();
            return $result->current();
    }

    public function fetchAll() {
        
    }

    public function fetchById($id) {
        $sql = new Sql($this->adapter);
        $select = $sql->select();
        $select->columns([
            new Expression("TR.EMPLOYEE_ID AS EMPLOYEE_ID"),
            new Expression("TR.TRAVEL_ID AS TRAVEL_ID"),
            new Expression("TR.TRAVEL_CODE AS TRAVEL_CODE"),
            new Expression("TR.DESTINATION AS DESTINATION"),
            new Expression("TR.DEPARTURE AS DEPARTURE"),
            new Expression("TR.HARDCOPY_SIGNED_FLAG AS HARDCOPY_SIGNED_FLAG"),
            new Expression("TR.REQUESTED_AMOUNT AS REQUESTED_AMOUNT"),
            new Expression("TR.PURPOSE AS PURPOSE"),
            new Expression("TR.TRANSPORT_TYPE AS TRANSPORT_TYPE"),
            new Expression("INITCAP(HRIS_GET_FULL_FORM(TR.TRANSPORT_TYPE,'TRANSPORT_TYPE')) AS TRANSPORT_TYPE_DETAIL"),
            new Expression("TR.REQUESTED_TYPE AS REQUESTED_TYPE"),
            new Expression("(CASE WHEN LOWER(TR.REQUESTED_TYPE) = 'ap' THEN 'Advance' ELSE 'Expense' END) AS REQUESTED_TYPE_DETAIL"),
            new Expression("INITCAP(TO_CHAR(TR.DEPARTURE_DATE, 'DD-MON-YYYY')) AS DEPARTURE_DATE"),
            new Expression("INITCAP(TO_CHAR(TR.RETURNED_DATE, 'DD-MON-YYYY')) AS RETURNED_DATE"),
            new Expression("INITCAP(TO_CHAR(TR.FROM_DATE, 'DD-MON-YYYY')) AS FROM_DATE"),
            new Expression("INITCAP(TO_CHAR(TR.TO_DATE, 'DD-MON-YYYY')) AS TO_DATE"),
            new Expression("((TR.TO_DATE)-TRUNC(TR.FROM_DATE))+1 AS DURATION"),
            new Expression("INITCAP(TO_CHAR(TR.REQUESTED_DATE, 'DD-MON-YYYY')) AS REQUESTED_DATE"),
            new Expression("TR.REMARKS AS REMARKS"),
            new Expression("TR.STATUS AS STATUS"),
            new Expression("LEAVE_STATUS_DESC(TR.STATUS) AS STATUS_DETAIL"),
            new Expression("TR.RECOMMENDED_BY AS RECOMMENDED_BY"),
            new Expression("INITCAP(TO_CHAR(TR.RECOMMENDED_DATE, 'DD-MON-YYYY')) AS RECOMMENDED_DATE"),
            new Expression("TR.RECOMMENDED_REMARKS AS RECOMMENDED_REMARKS"),
            new Expression("TR.APPROVED_BY AS APPROVED_BY"),
            new Expression("INITCAP(TO_CHAR(TR.APPROVED_DATE, 'DD-MON-YYYY')) AS APPROVED_DATE"),
            new Expression("TR.APPROVED_REMARKS AS APPROVED_REMARKS"),
            new Expression("TR.REFERENCE_TRAVEL_ID AS REFERENCE_TRAVEL_ID"),
            new Expression("TR.ITNARY_ID AS ITNARY_ID"),
            new Expression("TC.LEVEL_NO AS CATEGORY_NAME"),
            new Expression("TC.ID AS ID"),
            NEW Expression("TC.DAILY_ALLOWANCE_AMOUNT AS DAILY_ALLOWANCE"),
            NEW Expression("TC.DAILY_ALLOWANCE_AMOUNT / 2  AS DAILY_ALLOWANCE_RETURN"),
            NEW Expression("TC.DAILY_ALLOWANCE_AMOUNT * 0.25 AS TWENTY_FIVE_PERCENT"),
            NEW Expression("TC.DAILY_ALLOWANCE_AMOUNT * 0.50 AS FIFTY_PERCENT"),
            NEW Expression("TC.DAILY_ALLOWANCE_AMOUNT * 0.75 AS SEVENTY_FIVE"),
            NEW Expression("TC.DAILY_ALLOWANCE_AMOUNT  AS HUNDRED_PERCENT"),
            NEW Expression("TC.ADVANCE_AMOUNT AS ADVANCE_AMOUNT"),

            ], true);

        $select->from(['TR' => TravelRequest::TABLE_NAME])
            ->join(['TS' => "HRIS_TRAVEL_SUBSTITUTE"], "TR.TRAVEL_ID=TS.TRAVEL_ID", [
                'SUB_EMPLOYEE_ID' => 'EMPLOYEE_ID',
                'SUB_APPROVED_DATE' => new Expression("INITCAP(TO_CHAR(TS.APPROVED_DATE, 'DD-MON-YYYY'))"),
                'SUB_REMARKS' => "REMARKS",
                'SUB_APPROVED_FLAG' => "APPROVED_FLAG",
                'SUB_APPROVED_FLAG_DETAIL' => new Expression("(CASE WHEN APPROVED_FLAG = 'Y' THEN 'Approved' WHEN APPROVED_FLAG = 'N' THEN 'Rejected' ELSE 'Pending' END)")
                ], "left")
            ->join(['TC' => 'HRIS_TRAVEL_CATEGORY'], 'TC.LEVEL_NO=TR.TRAVEL_CATEGORY_ID',["LEVEL_NO" => new Expression("INITCAP(TC.LEVEL_NO)")], "left")
            ->join(['TSE' => 'HRIS_EMPLOYEES'], 'TS.EMPLOYEE_ID=TSE.EMPLOYEE_ID', ["SUB_EMPLOYEE_NAME" => new Expression("INITCAP(TSE.FULL_NAME)")], "left")
            ->join(['TSED' => 'HRIS_DESIGNATIONS'], 'TSE.DESIGNATION_ID=TSED.DESIGNATION_ID', ["SUB_DESIGNATION_TITLE" => "DESIGNATION_TITLE"], "left")
            ->join(['E' => 'HRIS_EMPLOYEES'], 'E.EMPLOYEE_ID=TR.EMPLOYEE_ID', ["FULL_NAME" => new Expression("INITCAP(E.FULL_NAME)")], "left")
            ->join(['ED' => 'HRIS_DESIGNATIONS'], 'E.DESIGNATION_ID=ED.DESIGNATION_ID', ["DESIGNATION_TITLE" => "DESIGNATION_TITLE"], "left")
            ->join(['EC' => 'HRIS_COMPANY'], 'E.COMPANY_ID=EC.COMPANY_ID', ["COMPANY_NAME" => "COMPANY_NAME"], "left")
            ->join(['ECF' => 'HRIS_EMPLOYEE_FILE'], 'EC.LOGO=ECF.FILE_CODE', ["COMPANY_FILE_PATH" => "FILE_PATH"], "left")
            ->join(['E2' => "HRIS_EMPLOYEES"], "E2.EMPLOYEE_ID=TR.RECOMMENDED_BY", ['RECOMMENDED_BY_NAME' => new Expression("INITCAP(E2.FULL_NAME)")], "left")
            ->join(['E3' => "HRIS_EMPLOYEES"], "E3.EMPLOYEE_ID=TR.APPROVED_BY", ['APPROVED_BY_NAME' => new Expression("INITCAP(E3.FULL_NAME)")], "left")
            ->join(['RA' => "HRIS_RECOMMENDER_APPROVER"], "RA.EMPLOYEE_ID=TR.EMPLOYEE_ID", ['RECOMMENDER_ID' => 'RECOMMEND_BY', 'APPROVER_ID' => 'APPROVED_BY'], "left")
            ->join(['RECM' => "HRIS_EMPLOYEES"], "RECM.EMPLOYEE_ID=RA.RECOMMEND_BY", ['RECOMMENDER_NAME' => new Expression("INITCAP(RECM.FULL_NAME)")], "left")
            ->join(['APRV' => "HRIS_EMPLOYEES"], "APRV.EMPLOYEE_ID=RA.APPROVED_BY", ['APPROVER_NAME' => new Expression("INITCAP(APRV.FULL_NAME)")], "left");
        $select->where(["TR.TRAVEL_ID" => $id]);
        $select->order("TR.REQUESTED_DATE DESC");
        $statement = $sql->prepareStatementForSqlObject($select);
    //    echo '<pre>';print_r($statement);die;
        $result = $statement->execute();
        return $result->current();
    }

    

    public function getTravelRecords($id){
        $sql="
        select advance_amount from hris_travel_category where status='E'
         and id=$id";
        $statement = $this->adapter->query($sql);
        $result=$statement->execute();
        return $result->current();
    }
    

    public function getFilteredRecords(array $search) {
        $sql = new Sql($this->adapter);
        $select = $sql->select();
        $select->columns([
            new Expression("INITCAP(TO_CHAR(TR.FROM_DATE, 'DD-MON-YYYY')) AS FROM_DATE_AD"),
            new Expression("BS_DATE(TO_CHAR(TR.FROM_DATE, 'DD-MON-YYYY')) AS FROM_DATE_BS"),
            new Expression("INITCAP(TO_CHAR(TR.TO_DATE, 'DD-MON-YYYY')) AS TO_DATE_AD"),
            new Expression("BS_DATE(TO_CHAR(TR.TO_DATE, 'DD-MON-YYYY')) AS TO_DATE_BS"),
            new Expression("TR.STATUS AS STATUS"),
            new Expression("TR.HARDCOPY_SIGNED_FLAG AS HARDCOPY_SIGNED_FLAG"),
            new Expression("LEAVE_STATUS_DESC(TR.STATUS) AS STATUS_DETAIL"),
            new Expression("TR.DESTINATION AS DESTINATION"),
            new Expression("TR.DEPARTURE AS DEPARTURE"),
            new Expression("INITCAP(TO_CHAR(TR.REQUESTED_DATE, 'DD-MON-YYYY')) AS REQUESTED_DATE_AD"),
            new Expression("BS_DATE(TO_CHAR(TR.REQUESTED_DATE, 'DD-MON-YYYY')) AS REQUESTED_DATE_BS"),
            new Expression("INITCAP(TO_CHAR(TR.APPROVED_DATE, 'DD-MON-YYYY')) AS APPROVED_DATE"),
            new Expression("INITCAP(TO_CHAR(TR.RECOMMENDED_DATE, 'DD-MON-YYYY')) AS RECOMMENDED_DATE"),
            new Expression("TR.REQUESTED_AMOUNT AS REQUESTED_AMOUNT"),
            new Expression("TR.TRAVEL_ID AS TRAVEL_ID"),
            new Expression("TR.TRAVEL_CODE AS TRAVEL_CODE"),
            new Expression("TR.PURPOSE AS PURPOSE"),
            new Expression("TR.TRANSPORT_TYPE AS TRANSPORT_TYPE"),
            new Expression("INITCAP(HRIS_GET_FULL_FORM(TR.TRANSPORT_TYPE,'TRANSPORT_TYPE')) AS TRANSPORT_TYPE_DETAIL"),
            new Expression("TR.EMPLOYEE_ID AS EMPLOYEE_ID"),
            new Expression("TR.RECOMMENDED_BY AS RECOMMENDED_BY"),
            new Expression("TR.APPROVED_BY AS APPROVED_BY"),
            new Expression("TR.APPROVED_REMARKS AS APPROVED_REMARKS"),
            new Expression("TR.RECOMMENDED_REMARKS AS RECOMMENDED_REMARKS"),
            new Expression("TR.REMARKS AS REMARKS"),
            NEW Expression("TC.LEVEL_NO AS CATEGORY_NAME"),
            new Expression("TR.REQUESTED_TYPE AS REQUESTED_TYPE"),
            new Expression("(CASE WHEN LOWER(TR.REQUESTED_TYPE) = 'ad' THEN 'Advance' ELSE 'Expense' END) AS REQUESTED_TYPE"),
            new Expression("(CASE WHEN TR.STATUS = 'RQ' THEN 'Y' ELSE 'N' END) AS ALLOW_EDIT"),
            new Expression("(CASE WHEN TR.STATUS IN ('RQ','RC','AP') THEN 'Y' ELSE 'N' END) AS ALLOW_DELETE"),
            new Expression("(CASE WHEN (TR.STATUS = 'AP' AND (SELECT COUNT(*) FROM HRIS_EMPLOYEE_TRAVEL_REQUEST WHERE REFERENCE_TRAVEL_ID =TR.TRAVEL_ID AND STATUS not in ('C','R') ) =0 ) THEN 'Y' ELSE 'N' END) AS ALLOW_EXPENSE_APPLY"),
            ], true);

        $select->from(['TR' => TravelRequest::TABLE_NAME])
            ->join(['TC'=>'HRIS_TRAVEL_CATEGORY'],'TR.TRAVEL_CATEGORY_ID=TC.LEVEL_NO',["LEVEL_NO"=>new Expression("INITCAP(TC.LEVEL_NO)")],"left")
            ->join(['E' => 'HRIS_EMPLOYEES'], 'E.EMPLOYEE_ID=TR.EMPLOYEE_ID', ["FULL_NAME" => new Expression("INITCAP(E.FULL_NAME)")], "left")
            ->join(['E2' => "HRIS_EMPLOYEES"], "E2.EMPLOYEE_ID=TR.RECOMMENDED_BY", ['RECOMMENDED_BY_NAME' => new Expression("INITCAP(E2.FULL_NAME)")], "left")
            ->join(['E3' => "HRIS_EMPLOYEES"], "E3.EMPLOYEE_ID=TR.APPROVED_BY", ['APPROVED_BY_NAME' => new Expression("INITCAP(E3.FULL_NAME)")], "left")
            ->join(['RA' => "HRIS_RECOMMENDER_APPROVER"], "RA.EMPLOYEE_ID=TR.EMPLOYEE_ID", ['RECOMMENDER_ID' => 'RECOMMEND_BY', 'APPROVER_ID' => 'APPROVED_BY'], "left")
            ->join(['RECM' => "HRIS_EMPLOYEES"], "RECM.EMPLOYEE_ID=RA.RECOMMEND_BY", ['RECOMMENDER_NAME' => new Expression("INITCAP(RECM.FULL_NAME)")], "left")
            ->join(['APRV' => "HRIS_EMPLOYEES"], "APRV.EMPLOYEE_ID=RA.APPROVED_BY", ['APPROVER_NAME' => new Expression("INITCAP(APRV.FULL_NAME)")], "left");

        $select->where([
            "E.EMPLOYEE_ID" => $search['employeeId']
        ]);

        $boundedParameter = [];

        if ($search['statusId'] != -1) {
            $select->where([
                "TR.STATUS" => $search['statusId']
            ]);
        }
        if ($search['statusId'] != 'C') {
            $select->where([
                "(TRUNC(SYSDATE)- TR.REQUESTED_DATE) < (
                      CASE
                        WHEN TR.STATUS = 'C'
                        THEN 20
                        ELSE 365
                      END)"
            ]);
        }

        if ($search['fromDate'] != null) {
            $boundedParameter['fromDate'] = $search['fromDate'];
            $select->where([
                "TR.FROM_DATE>=TO_DATE(:fromDate,'DD-MM-YYYY')"
            ]);
        }

        if ($search['toDate'] != null) {
            $boundedParameter['toDate'] = $search['toDate'];
            $select->where([
                "TR.TO_DATE<=TO_DATE(:toDate,'DD-MM-YYYY')"
            ]);
        }

        if (isset($search['requestedType'])) {
            $select->where([
                "LOWER(TR.REQUESTED_TYPE)" => $search['requestedType']
            ]);
        }
        $select->order("TR.REQUESTED_DATE DESC");
        $statement = $sql->prepareStatementForSqlObject($select);
        // echo '<pre>';print_r($statement);die;

        $result = $statement->execute($boundedParameter);
        return $result;
    }
    
    public function checkAllowEdit($id){
        $sql = "SELECT (CASE WHEN STATUS = 'RQ' THEN 'Y' ELSE 'N' END)"
                . " AS ALLOW_EDIT FROM HRIS_EMPLOYEE_TRAVEL_REQUEST WHERE "
                . "TRAVEL_ID = :id";

        $boundedParameter = [];
        $boundedParameter['id'] = $id;
        return $this->rawQuery($sql, $boundedParameter)[0]["ALLOW_EDIT"];
    }
}
