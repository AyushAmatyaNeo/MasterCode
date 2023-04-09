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
use SelfService\Model\TRAVELFILES;
use Setup\Model\HrEmployees;
use Zend\Http\Header\TE;
use ZF\DevelopmentMode\Help;

class NewTravelRequestRepository extends HrisRepository implements RepositoryInterface {

    protected $tableGateway;
    protected $adapter;
 
    public function __construct(AdapterInterface $adapter) {
        $this->adapter = $adapter;
        $this->tableGateway = new TableGateway(TravelRequest::TABLE_NAME, $adapter);
        $this->tableEmployeeGateway = new TableGateway(HrEmployees::TABLE_NAME, $adapter);
        $this->fileGateway = new TableGateway(TRAVELFILES::TABLE_NAME,$adapter);
    } 
    public function getFilteredRecords(array $search) { #passes value from view
        $sql = new Sql($this->adapter);
        $employeeId = $search['employeeId'];

        if($search['year']){
            $startdate = '01-JAN-'.$search['year'];
            $enddate = '30-DEC-'.$search['year'];
        }
       

        $select = $sql->select();
        $select->columns([
            new Expression("INITCAP(TO_CHAR(TR.FROM_DATE, 'DD-MON-YYYY')) AS FROM_DATE_AD"),
            new Expression("BS_DATE(TR.FROM_DATE) AS FROM_DATE_BS"),
            new Expression("INITCAP(TO_CHAR(TR.TO_DATE, 'DD-MON-YYYY')) AS TO_DATE_AD"),
            new Expression("BS_DATE(TR.TO_DATE) AS TO_DATE_BS"),
            new Expression("TR.STATUS AS STATUS"),
            new Expression("TR.HARDCOPY_SIGNED_FLAG AS HARDCOPY_SIGNED_FLAG"),
            new Expression("travel_status_desc(TR.STATUS) AS STATUS_DETAIL"),
            new Expression("TR.DESTINATION AS DESTINATION"),
            new Expression("TR.DEPARTURE AS DEPARTURE"),
            new Expression("INITCAP(TO_CHAR(TR.REQUESTED_DATE, 'DD-MON-YYYY')) AS REQUESTED_DATE_AD"),
            new Expression("BS_DATE(TR.REQUESTED_DATE) AS REQUESTED_DATE_BS"),
            new Expression("INITCAP(TO_CHAR(TR.APPROVED_DATE, 'DD-MON-YYYY')) AS APPROVED_DATE"),
            new Expression("INITCAP(TO_CHAR(TR.RECOMMENDED_DATE, 'DD-MON-YYYY')) AS RECOMMENDED_DATE"),
            new Expression("TR.REQUESTED_AMOUNT AS REQUESTED_AMOUNT"),
            new Expression("TR.TRAVEL_ID AS TRAVEL_ID"),
            new Expression("TR.TRAVEL_CODE AS TRAVEL_CODE"),
            new Expression("TR.PURPOSE AS PURPOSE"),
            new Expression("TR.APPROVED_BY AS APPROVED_BY"),
            new Expression("TR.TRANSPORT_TYPE AS TRANSPORT_TYPE"),
            new Expression("(CASE WHEN TR.TRANSPORT_TYPE = 'AP' THEN 'Aeroplane' WHEN TR.TRANSPORT_TYPE = 'OV' THEN 'Office Vehicles' WHEN TR.TRANSPORT_TYPE = 'TI' THEN 'Taxi' WHEN TR.TRANSPORT_TYPE = 'BS' THEN 'BUS' WHEN TR.TRANSPORT_TYPE = 'VV' THEN 'Own Vehicle' ELSE 'Others' END) AS TRANSPORT_TYPE_DETAIL"),
            new Expression("TR.EMPLOYEE_ID AS EMPLOYEE_ID"),
            new Expression("TR.RECOMMENDED_BY AS RECOMMENDED_BY"),
            new Expression("TR.APPROVED_BY AS APPROVED_BY"),
            new Expression("TR.APPROVED_REMARKS AS APPROVED_REMARKS"),
            new Expression("TR.RECOMMENDED_REMARKS AS RECOMMENDED_REMARKS"),
            new Expression("TR.REMARKS AS REMARKS"),
            new Expression("TR.REQUESTED_TYPE AS REQUESTED_TYPE"),
            new Expression("TR.CURRENCY_NAME AS CURRENCY"),
            new Expression("TR.TRAVEL_TYPE AS TRAVEL_TYPE"),
            new Expression("(CASE WHEN LOWER(TR.REQUESTED_TYPE) = 'ad' AND TRAVEL_TYPE = 'LTR' THEN 'Local Travel Request' WHEN LOWER(TR.REQUESTED_TYPE) = 'ia' THEN 'Intenational Travel Request' WHEN LOWER(TR.REQUESTED_TYPE) = 'ad' AND TRAVEL_TYPE = 'ITR' THEN 'Intenational Travel Advance Request' ELSE 'Expense' END) AS REQUESTED_TYPE"),
            new Expression("(CASE WHEN TR.STATUS in ('RQ','SV') THEN 'Y' ELSE 'N' END) AS ALLOW_EDIT"),
            new Expression("(CASE WHEN TR.STATUS IN ('RQ','RC','SV') THEN 'Y' ELSE 'N' END) AS ALLOW_DELETE"),
            new Expression("(CASE WHEN (TR.STATUS = 'AP' AND LOWER(TR.REQUESTED_TYPE) = 'ad' AND (SELECT COUNT(*) FROM HRIS_EMPLOYEE_TRAVEL_REQUEST WHERE REFERENCE_TRAVEL_ID =TR.TRAVEL_ID AND STATUS not in ('C','R') ) =0 ) THEN 'Y' ELSE 'N' END) AS ALLOW_EXPENSE_APPLY"),

            new Expression("(CASE WHEN (TR.STATUS = 'AP' AND LOWER(TR.REQUESTED_TYPE) = 'ia' AND (SELECT COUNT(*) FROM HRIS_EMPLOYEE_TRAVEL_REQUEST WHERE REFERENCE_TRAVEL_ID =TR.TRAVEL_ID AND STATUS not in ('C','R') ) =0 )  THEN 'Y' ELSE 'N' END) AS ALLOW_ADVANCE_ITR"),
        ], true);

        $select->from(['TR' => TravelRequest::TABLE_NAME])
            ->join(['E' => 'HRIS_EMPLOYEES'], 'E.EMPLOYEE_ID=TR.EMPLOYEE_ID', ["FULL_NAME" => new Expression("INITCAP(E.FULL_NAME)")], "left")
            ->join(['E2' => "HRIS_EMPLOYEES"], "E2.EMPLOYEE_ID=TR.RECOMMENDED_BY", ['RECOMMENDED_BY_NAME' => new Expression("INITCAP(E2.FULL_NAME)")], "left")
            ->join(['E3' => "HRIS_EMPLOYEES"], "E3.EMPLOYEE_ID=TR.APPROVED_BY", ['APPROVED_BY_NAME' => new Expression("INITCAP(E3.FULL_NAME)")], "left")
            ->join(['RA' => "HRIS_RECOMMENDER_APPROVER"], "RA.EMPLOYEE_ID=TR.EMPLOYEE_ID", ['RECOMMENDER_ID' => 'RECOMMEND_BY', 'APPROVER_ID' => 'APPROVED_BY'], "left")
            ->join(['RECM' => "HRIS_EMPLOYEES"], "RECM.EMPLOYEE_ID=RA.RECOMMEND_BY", ['RECOMMENDER_NAME' => new Expression("INITCAP(RECM.FULL_NAME)")], "left")
            ->join(['APRV' => "HRIS_EMPLOYEES"], "APRV.EMPLOYEE_ID=RA.APPROVED_BY", ['APPROVER_NAME' => new Expression("INITCAP(APRV.FULL_NAME)")], "left");

        $select->where([
            "E.EMPLOYEE_ID  = {$employeeId}",
            "TR.STATUS  != 'C' ",
           //"LOWER(TR.REQUESTED_TYPE) = 'ad'"
        ]);
        // if($search['travelType']){
        //     $select->where([
		// 	"TR.TRAVEL_TYPE = '{$search['travelType']}'",
        //     ]);
        // }
        // if($search['year']){
            
        //     $select->where([
		// 	"TR.REQUESTED_DATE BETWEEN '{$startdate}' AND '{$enddate}'",
        //     ]);
        // }
        if($search['year']){
            
            $select->where([
			"TR.from_date BETWEEN '{$startdate}' AND '{$enddate}'",
            ]);
        }
        if($search['year']){
            
            $select->where([
			"TR.to_date BETWEEN '{$startdate}' AND '{$enddate}'",
            ]);
        }

        if ($search['statusId'] != -1) {
            $select->where([
                "TR.STATUS" => $search['statusId'],
            ]);
        }
        if ($search['statusId'] != 'C') {
            $select->where([
                " (trunc(sysdate-tr.requested_date)) < (
                    CASE
                        WHEN tr.status = 'C' THEN 20
                        ELSE 365
                    END
                )"
            ]);
        }

        // if ($search['fromDate'] != null) {
        //     $fromDate = $search['fromDate'];
        //     $select->where([
        //         "TR.FROM_DATE>=TO_DATE('{$fromDate}','DD-MON-YYYY')"
        //     ]);
        // }

        // if ($search['toDate'] != null) {
        //     $toDate = $search['toDate'];
        //     $select->where([
        //         "TR.TO_DATE>=TO_DATE('{$toDate}','DD-MON-YYYY')"
        //     ]);
        // }

        if (isset($search['requestedType'])) {
            $select->where([
                "LOWER(TR.REQUESTED_TYPE) in ('{$search['requestedType']}') "
            ]);
        }
        if ($search['travelType']) {
            $select->where([
                "TR.TRAVEL_TYPE in ('{$search['travelType']}') "
            ]);
        }
        $select->order("TR.FROM_DATE DESC");
        $statement = $sql->prepareStatementForSqlObject($select);
        // echo '<pre>';print_r($statement);die;

        $result = $statement->execute();
        // echo '<pre>';print_r($statement);die;
        return $result;
    }
    public function linkTravelWithFiles($id = null)
    {
        if (!empty($_POST['fileUploadList'])) {
            if ($id == null) {
                $filesList = $_POST['fileUploadList'];
                $filesList = implode(',', $filesList);
                $sql = "UPDATE hris_travel_files SET TRAVEL_ID = (SELECT reference_travel_id FROM HRIS_EMPLOYEE_TRAVEL_REQUEST where travel_id = (select max(travel_id) from HRIS_EMPLOYEE_TRAVEL_REQUEST))
                        WHERE FILE_ID IN($filesList)";
                $statement = $this->adapter->query($sql);
                $statement->execute();
            } else {
                $filesList = $_POST['fileUploadList'];
                $filesList = implode(',', $filesList);
                $sql = "UPDATE hris_travel_files SET TRAVEL_ID = $id
                        WHERE FILE_ID IN($filesList)";
                $statement = $this->adapter->query($sql);
                $statement->execute();
            }
        }
        $sql="delete from hris_travel_files where travel_id is null";
        $statement = $this->adapter->query($sql);
        $statement->execute();
    }

    public function fetchAttachmentsById($id)
    {
        $sql="select * from HRIS_TRAVEL_FILES where travel_id=$id";
        $result=EntityHelper::rawQueryResult($this->adapter,$sql);
        return Helper::extractDbData($result);
    }

    public function add(Model $model) {
        $addData=$model->getArrayCopyForDB();
        // echo '<pre>'; print_r($addData); die;
        $this->tableGateway->insert($addData);
        // var_dump('dgdv');die;

        if ($addData['STATUS']=='AP' && date('Y-m-d', strtotime($model->fromDate)) <= date('Y-m-d')) {
            //THE FOLLOWING CODE WAS DONE IN THE URGENCY FOR MAKING THE DATE COMPATIBLE WITH SAP HANA
            $sql = "CALL 
            HRIS_REATTENDANCE((select to_char(from_date,'yyyy-mm-dd') from HRIS_EMPLOYEE_TRAVEL_REQUEST where travel_id = $model->travelId), $model->employeeId,(select to_char(to_date,'yyyy-mm-dd') from HRIS_EMPLOYEE_TRAVEL_REQUEST where travel_id = $model->travelId));";
        //            $boundedParameter = [];
        //            $boundedParameter['fromDate'] = $model->fromDate;
        //            $boundedParameter['employeeId'] = $model->employeeId;
        //            $boundedParameter['toDate'] = $model->toDate;

            $this->rawQuery($sql);
        }
        //$this->linkTravelWithFiles();
        $this->linkTravelWithFiles();
    }
    public function addFiles($data)
    {
        $this->fileGateway->insert($data);
    }

    public function delete($id) {
        $this->tableGateway->update([TravelRequest::STATUS => 'C'], [TravelRequest::TRAVEL_ID => $id]);
    }
 
    public function edit(Model $model, $id) {
        // echo("<pre>");print_r($model);die;
        $array = $model->getArrayCopyForDB();
        // var_dump($array); die;
        $this->tableGateway->update($array, [TravelRequest::TRAVEL_ID => $id]);
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
            new Expression("(CASE WHEN LOWER(TR.REQUESTED_TYPE) = 'ad' THEN 'Advance' ELSE 'Expense' END) AS REQUESTED_TYPE_DETAIL"),
            new Expression("INITCAP(TO_CHAR(TR.DEPARTURE_DATE, 'DD-MON-YYYY')) AS DEPARTURE_DATE"),
            new Expression("INITCAP(TO_CHAR(TR.RETURNED_DATE, 'DD-MON-YYYY')) AS RETURNED_DATE"),
            new Expression("INITCAP(TO_CHAR(TR.FROM_DATE, 'DD-MON-YYYY')) AS FROM_DATE"),
            new Expression("BS_DATE(TR.FROM_DATE) AS FROM_DATE_BS"),
            new Expression("INITCAP(TO_CHAR(TR.TO_DATE, 'DD-MON-YYYY')) AS TO_DATE"),
            new Expression("BS_DATE(TR.TO_DATE) AS TO_DATE_BS"),
            new Expression("trunc(tr.TO_DATE-tr.FROM_DATE + 1) AS DURATION"),
            new Expression("INITCAP(TO_CHAR(TR.REQUESTED_DATE, 'DD-MON-YYYY')) AS REQUESTED_DATE"),
            new Expression("TR.REMARKS AS REMARKS"),
            new Expression("TR.STATUS AS STATUS"),
            new Expression("travel_status_desc(TR.STATUS) AS STATUS_DETAIL"),
            new Expression("TR.RECOMMENDED_BY AS RECOMMENDED_BY"),
            new Expression("INITCAP(TO_CHAR(TR.RECOMMENDED_DATE, 'DD-MON-YYYY')) AS RECOMMENDED_DATE"),
            new Expression("TR.RECOMMENDED_REMARKS AS RECOMMENDED_REMARKS"),
            new Expression("TR.APPROVED_BY AS APPROVED_BY"),
            new Expression("INITCAP(TO_CHAR(TR.APPROVED_DATE, 'DD-MON-YYYY')) AS APPROVED_DATE"),
            new Expression("TR.APPROVED_REMARKS AS APPROVED_REMARKS"),
            new Expression("TR.REFERENCE_TRAVEL_ID AS REFERENCE_TRAVEL_ID"),
            new Expression("TR.TRAVEL_TYPE AS TRAVEL_TYPE"),
            new Expression("TR.CURRENCY_NAME AS CURRENCY"),
            new Expression("TE.EXPENSE_DATE AS EXPENSE_REQUESTED_DATE"),
            ], true);

        $select->from(['TR' => TravelRequest::TABLE_NAME])
            ->join(['TS' => "HRIS_TRAVEL_SUBSTITUTE"], "TR.TRAVEL_ID=TS.TRAVEL_ID", [
                'SUB_EMPLOYEE_ID' => 'EMPLOYEE_ID',
                'SUB_APPROVED_DATE' => new Expression("INITCAP(TO_CHAR(TS.APPROVED_DATE, 'DD-MON-YYYY'))"),
                'SUB_REMARKS' => "REMARKS",
                'SUB_APPROVED_FLAG' => "APPROVED_FLAG",
                'SUB_APPROVED_FLAG_DETAIL' => new Expression("(CASE WHEN APPROVED_FLAG = 'Y' THEN 'Approved' WHEN APPROVED_FLAG = 'N' THEN 'Rejected' ELSE 'Pending' END)")
                ], "left")
            ->join (['TE' =>'HRIS_TRAVEL_EXPENSE'], 'TE.TRAVEL_ID=TR.TRAVEL_ID',["EXPENSE_DATE" =>new Expression("INITCAP(TO_CHAR(TE.EXPENSE_DATE, 'DD-MON-YYYY'))")],"left")
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
                // echo '<pre>'; print_r($statement); die;
        $result = $statement->execute();
        return $result->current();
    }

    public function getEmployeeData($id)
    {
        $sql =  "SELECT HRIS_EMPLOYEES.FULL_NAME,HRIS_BRANCHES.BRANCH_NAME,HRIS_DEPARTMENTS.DEPARTMENT_NAME,HRIS_DESIGNATIONS.DESIGNATION_TITLE,HRIS_EMPLOYEES.EMPLOYEE_ID,HRIS_RECOMMENDER_APPROVER.RECOMMEND_BY,HRIS_RECOMMENDER_APPROVER.APPROVED_BY,HRIS_USERS.USER_NAME, ( SELECT FULL_NAME FROM HRIS_EMPLOYEES WHERE EMPLOYEE_ID = HRIS_RECOMMENDER_APPROVER.APPROVED_BY) AS APPROVER_NAME, ( SELECT FULL_NAME FROM HRIS_EMPLOYEES WHERE EMPLOYEE_ID = HRIS_RECOMMENDER_APPROVER.RECOMMEND_BY) AS RECOMMENDER_NAME FROM HRIS_EMPLOYEES 
        LEFT JOIN HRIS_BRANCHES ON HRIS_EMPLOYEES.BRANCH_ID = HRIS_BRANCHES.BRANCH_ID 
        LEFT JOIN HRIS_USERS ON HRIS_EMPLOYEES.EMPLOYEE_ID = HRIS_USERS.EMPLOYEE_ID
        LEFT JOIN HRIS_DEPARTMENTS ON HRIS_EMPLOYEES.DEPARTMENT_ID = HRIS_DEPARTMENTS.DEPARTMENT_ID 
        LEFT JOIN HRIS_DESIGNATIONS ON HRIS_EMPLOYEES.DESIGNATION_ID = HRIS_DESIGNATIONS.DESIGNATION_ID
        LEFT JOIN HRIS_RECOMMENDER_APPROVER ON HRIS_EMPLOYEES.EMPLOYEE_ID = HRIS_RECOMMENDER_APPROVER.EMPLOYEE_ID
        WHERE HRIS_EMPLOYEES.EMPLOYEE_ID = {$id} ";
        $result =  $this->rawQuery($sql);
        // var_dump($result); die;
        return $result[0];
    }
    public function checkAllowEdit($id){
        $sql = "SELECT (CASE WHEN STATUS = 'RQ' THEN 'Y' ELSE 'N' END)"
                . " AS ALLOW_EDIT FROM HRIS_EMPLOYEE_TRAVEL_REQUEST WHERE "
                . "TRAVEL_ID = {$id}";

        // $boundedParameter = [];
        // $boundedParameter['id'] = $id;
        return $this->rawQuery($sql)[0]["ALLOW_EDIT"];
    }
    public function getClassIdFromEmpId($id){
        $sql = "select pcm.class_id from hris_position_class_map pcm
        left join hris_employees he on (pcm.position_id = he.position_id)
        where he.employee_id = {$id}";
        return $this->rawQuery($sql)[0]["CLASS_ID"];
    }
    public function getRateFromConfigId($configId){
        $sql = "select rate from hris_class_travel_config where config_id = {$configId}";

        //print_r($sql);die;
        return $this->rawQuery($sql)[0]["RATE"];
    }
    public function getCongifId($travelType, $mot, $classId){
        if($travelType == "DOMESTIC"){
            $sql = "select config_id from hris_class_travel_config where
        travel_type = '{$travelType}' and domestic_type = '{$mot}' and class_id = {$classId}";
        }else{
            $sql = "select config_id from hris_class_travel_config where
        travel_type = '{$travelType}' and international_type = '{$mot}' and class_id = {$classId}";
        }
        // print_r($this->rawQuery($sql));die;   
        return $this->rawQuery($sql)[0]["CONFIG_ID"];
    }
    public function getTotalExpenseAmount($travelId){
        // $sql = "select sum(total * exchange_rate) as NRP_TOTAL from hris_travel_expense where travel_id = {$travelId} and status = 'E'";
        // return $this->rawQuery($sql)[0]["NRP_TOTAL"];
        $sql = "select nvl(sum(amount),0) as NRP_TOTAL from hris_travel_expense where travel_id = {$travelId} and status = 'E'";
        // var_dump($this->rawQuery($sql)[0]["NRP_TOTAL"]); die;

        return $this->rawQuery($sql)[0]["NRP_TOTAL"];
    }

    public function fetchFilesById($id)
    {

        $sql = "select * from HRIS_TRAVEL_FILES where travel_id = {$id}";
        $result =  $this->rawQuery($sql);
        // var_dump($id); die;
        return $result;
    }

    public function getLTravel($id)
    {
        $sql = "SELECT * 
        FROM   hris_employee_travel_request 
        WHERE  TRAVEL_ID NOT IN 
        (
		SELECT reference_travel_id FROM hris_employee_travel_request
         where requested_type = 'ep' and requested_date > '01-JAN-2022'
		 and employee_id = {$id} AND reference_travel_id IS NOT null AND STATUS NOT IN ('R','C'))
        and requested_type='ad' and employee_id = {$id} and travel_type = 'LTR'
         and status= 'AP' and requested_date > '01-JAN-2022'";
        $result =  $this->rawQuery($sql);
        // var_dump($result); die;
		$a = [];
        foreach ($result as $key => $value) {
            $a[] = $value['travel_id'];
        }
		
        
        return $result;
    }
    public function getITravel($id)
    {
     $sql = "SELECT * 
     FROM   hris_employee_travel_request 
      WHERE  TRAVEL_ID NOT IN 
      (SELECT reference_travel_id FROM 
      hris_employee_travel_request
       where requested_type = 'ep' 
       and requested_date > '01-JAN-2022' 
       and employee_id = {$id} AND reference_travel_id IS NOT null  AND STATUS NOT IN ('R','C'))
      and requested_type='ad' and 
      employee_id = {$id} and 
      travel_type = 'ITR'
       and status= 'AP'";
        $result =  $this->rawQuery($sql);
        // var_dump($result); die;
        return $result;
    }
    public function getLTraveldetailsId($id)
    {
        $sql = "select * from HRIS_EMPLOYEE_TRAVEL_REQUEST where travel_id = '{$id}'";
        $result =  $this->rawQuery($sql);
        // var_dump($result); die;
        return $result[0];
    }

    public function getLTraveldetailsIdInternational($id)
    {
        $sql = "select * from HRIS_EMPLOYEE_TRAVEL_REQUEST where travel_id = '{$id}'";
        $result =  $this->rawQuery($sql);
        // var_dump($result); die;
        return $result[0];
    }

    public function getPreferenceData()
    {
        $sql="select * from hris_preferences";
        $result =  $this->rawQuery($sql);
        // echo '<pre>';print_r($sql);die;
        return $result;
    }
}

