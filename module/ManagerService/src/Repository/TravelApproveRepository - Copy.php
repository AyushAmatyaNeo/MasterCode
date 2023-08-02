<?php
namespace ManagerService\Repository;

use Application\Helper\EntityHelper;
use Application\Model\Model;
use Application\Repository\RepositoryInterface;
use Exception;
use SelfService\Model\TravelRequest;
use Zend\Db\Adapter\AdapterInterface;
use Zend\Db\Sql\Expression;
use Zend\Db\Sql\Sql;
use Zend\Db\TableGateway\TableGateway;
use Application\Helper\Helper;
use Application\Repository\HrisRepository;

class TravelApproveRepository extends HrisRepository implements RepositoryInterface {

    protected $tableGateway;
    protected $adapter;
 
    public function __construct(AdapterInterface $adapter) {
        $this->adapter = $adapter;
        $this->tableGateway = new TableGateway(TravelRequest::TABLE_NAME, $adapter);
    }

    public function add(Model $model) {
        
    }

    public function delete($id) {
        
    }

    public function getAllWidStatus($id, $status) {
        
    }

    public function edit(Model $model, $id) {
        $temp = $model->getArrayCopyForDB();
        $this->tableGateway->update($temp, [TravelRequest::TRAVEL_ID => $id]);
        IF ($model->status == 'AP') {
          $boundedParameter = [];
          $boundedParameter['id'] = $id;
            try {
                $this->rawQuery("
                BEGIN
                    hris_travel_leave_reward(:id);
                END;", $boundedParameter);
            } catch (Exception $e) {
                return $e->getMessage();
            }
        }

        $link = $model->status == 'AP' ? 'Y' : 'N';
        if ($link == 'Y') {
          $boundedParameter = [];
          $boundedParameter['id'] = $id;
          $boundedParameter['link'] = $link;
            try {
                $this->rawQuery("
                BEGIN
                    HRIS_TRAVEL_REQUEST_PROC(:id,:link);
                END;", $boundedParameter);
            } catch (Exception $e) {
                return $e->getMessage();
            }
        }
    }

    public function fetchAll() {
        
    }
  
    /*
    public function fetchAttachmentsById($id){
      $sql = "SELECT * FROM HRIS_TRAVEL_FILES WHERE TRAVEL_ID = $id";
      $result = EntityHelper::rawQueryResult($this->adapter, $sql);
      return Helper::extractDbData($result);
    }*/

    public function fetchById($id) {
        $sql = new Sql($this->adapter);
        $select = $sql->select();
        $select->columns([
            new Expression("TR.EMPLOYEE_ID AS EMPLOYEE_ID"),
            new Expression("TR.TRAVEL_ID AS TRAVEL_ID"),
            new Expression("TR.TRAVEL_CODE AS TRAVEL_CODE"),
            new Expression("TR.DEPARTURE AS DEPARTURE"),
            new Expression("TR.DESTINATION AS DESTINATION"),
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
            ], true);

        $select->from(['TR' => TravelRequest::TABLE_NAME])
            ->join(['TS' => "HRIS_TRAVEL_SUBSTITUTE"], "TR.TRAVEL_ID=TS.TRAVEL_ID", [
                'SUB_EMPLOYEE_ID' => 'EMPLOYEE_ID',
                'SUB_APPROVED_DATE' => new Expression("INITCAP(TO_CHAR(TS.APPROVED_DATE, 'DD-MON-YYYY'))"),
                'SUB_REMARKS' => "REMARKS",
                'SUB_APPROVED_FLAG' => "APPROVED_FLAG",
                'SUB_APPROVED_FLAG_DETAIL' => new Expression("(CASE WHEN APPROVED_FLAG = 'Y' THEN 'Approved' WHEN APPROVED_FLAG = 'N' THEN 'Rejected' ELSE 'Pending' END)")
                ], "left")
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
        $result = $statement->execute();
        return $result->current();
    }

    public function getAllFiltered($search) {
        $condition = "";
        $boundedParameter = [];
        if (isset($search['fromDate']) && $search['fromDate'] != null) {
          $boundedParameter['fromDate'] = $search['fromDate'];
            $condition .= " AND TR.FROM_DATE>=TO_DATE(:fromDate','DD-MM-YYYY') ";
        }
        if (isset($search['toDate']) && $search['toDate'] != null) {
          $boundedParameter['toDate'] = $search['toDate'];
            $condition .= " AND TR.TO_DATE<=TO_DATE(:toDate','DD-MM-YYYY') ";
        }
        $boundedParameter['employeeId'] = $search['employeeId'];

        if (isset($search['status']) && $search['status'] != null && $search['status'] != -1) {
            if (gettype($search['status']) === 'array') {
                $csv = "";
                for ($i = 0; $i < sizeof($search['status']); $i++) {
                    if ($i == 0) {
                        $csv = ":status".$i;
                        $boundedParameter["status".$i] = $search['status'][$i];
                    } else {
                        $csv .= ",:status".$i;
                        $boundedParameter["status".$i] = $search['status'][$i];
                    }
                }
                $condition .= "AND TR.STATUS IN ({$csv})";
            } else {
                $condition .= "AND TR.STATUS IN (:status)";
                $boundedParameter['status'] = $search['status'];
            }
        }

        $sql = "SELECT TR.TRAVEL_ID                        AS TRAVEL_ID,
                  TR.TRAVEL_CODE                           AS TRAVEL_CODE,
                  TR.EMPLOYEE_ID                           AS EMPLOYEE_ID,
                  E.EMPLOYEE_CODE                             AS EMPLOYEE_CODE,
                  E.FULL_NAME                              AS EMPLOYEE_NAME,
                  TO_CHAR(TR.REQUESTED_DATE,'DD-MON-YYYY') AS REQUESTED_DATE_AD,
                  BS_DATE(TR.REQUESTED_DATE)               AS REQUESTED_DATE_BS,
                  TO_CHAR(TR.FROM_DATE,'DD-MON-YYYY')      AS FROM_DATE_AD,
                  BS_DATE(TR.FROM_DATE)                    AS FROM_DATE_BS,
                  TO_CHAR(TR.TO_DATE,'DD-MON-YYYY')        AS TO_DATE_AD,
                  BS_DATE(TR.TO_DATE)                      AS TO_DATE_BS,
                  TR.DESTINATION                           AS DESTINATION,
                  TR.PURPOSE                               AS PURPOSE,
                  TR.REQUESTED_TYPE                        AS REQUESTED_TYPE,
                  (
                  CASE
                    WHEN TR.REQUESTED_TYPE = 'ad'
                    THEN 'Advance'
                    ELSE 'Expense'
                  END)                                                            AS REQUESTED_TYPE_DETAIL,
                  NVL(TR.REQUESTED_AMOUNT,0)                                      AS REQUESTED_AMOUNT,
                  TR.TRANSPORT_TYPE                                               AS TRANSPORT_TYPE,
                  INITCAP(HRIS_GET_FULL_FORM(TR.TRANSPORT_TYPE,'TRANSPORT_TYPE')) AS TRANSPORT_TYPE_DETAIL,
                  TO_CHAR(TR.DEPARTURE_DATE)                                      AS DEPARTURE_DATE_AD,
                  BS_DATE(TR.DEPARTURE_DATE)                                      AS DEPARTURE_DATE_BS,
                  TO_CHAR(TR.RETURNED_DATE)                                       AS RETURNED_DATE_AD,
                  BS_DATE(TR.RETURNED_DATE)                                       AS RETURNED_DATE_BS,
                  TR.REMARKS                                                      AS REMARKS,
                  TR.STATUS                                                       AS STATUS,
                  LEAVE_STATUS_DESC(TR.STATUS)                                    AS STATUS_DETAIL,
                  TR.RECOMMENDED_BY                                               AS RECOMMENDED_BY,
                  RE.FULL_NAME                                                    AS RECOMMENDED_BY_NAME,
                  TO_CHAR(TR.RECOMMENDED_DATE)                                    AS RECOMMENDED_DATE_AD,
                  BS_DATE(TR.RECOMMENDED_DATE)                                    AS RECOMMENDED_DATE_BS,
                  TR.RECOMMENDED_REMARKS                                          AS RECOMMENDED_REMARKS,
                  TR.APPROVED_BY                                                  AS APPROVED_BY,
                  AE.FULL_NAME                                                    AS APPROVED_BY_NAME,
                  TO_CHAR(TR.APPROVED_DATE)                                       AS APPROVED_DATE_AD,
                  BS_DATE(TR.APPROVED_DATE)                                       AS APPROVED_DATE_BS,
                  TR.APPROVED_REMARKS                                             AS APPROVED_REMARKS,
                  RAR.EMPLOYEE_ID                                                 AS RECOMMENDER_ID,
                  RAR.FULL_NAME                                                   AS RECOMMENDER_NAME,
                  RAA.EMPLOYEE_ID                                                 AS APPROVER_ID,
                  RAA.FULL_NAME                                                   AS APPROVER_NAME,
                  REC_APP_ROLE(U.EMPLOYEE_ID,RA.RECOMMEND_BY,RA.APPROVED_BY)      AS ROLE,
                  REC_APP_ROLE_NAME(U.EMPLOYEE_ID,RA.RECOMMEND_BY,RA.APPROVED_BY) AS YOUR_ROLE
                FROM HRIS_EMPLOYEE_TRAVEL_REQUEST TR
                LEFT JOIN HRIS_TRAVEL_SUBSTITUTE TS
                ON TR.TRAVEL_ID = TS.TRAVEL_ID
                LEFT JOIN HRIS_EMPLOYEES E
                ON (E.EMPLOYEE_ID =TR.EMPLOYEE_ID)
                LEFT JOIN HRIS_EMPLOYEES RE
                ON(RE.EMPLOYEE_ID =TR.RECOMMENDED_BY)
                LEFT JOIN HRIS_EMPLOYEES AE
                ON (AE.EMPLOYEE_ID =TR.APPROVED_BY)
                LEFT JOIN HRIS_RECOMMENDER_APPROVER RA
                ON (RA.EMPLOYEE_ID=TR.EMPLOYEE_ID)
                LEFT JOIN HRIS_EMPLOYEES RAR
                ON (RA.RECOMMEND_BY=RAR.EMPLOYEE_ID)
                LEFT JOIN HRIS_EMPLOYEES RAA
                ON(RA.APPROVED_BY=RAA.EMPLOYEE_ID)
                LEFT JOIN HRIS_ALTERNATE_R_A ALR
                ON(ALR.R_A_FLAG='R' AND ALR.EMPLOYEE_ID=TR.EMPLOYEE_ID AND ALR.R_A_ID=:employeeId)
                LEFT JOIN HRIS_ALTERNATE_R_A ALA
                ON(ALA.R_A_FLAG='A' AND ALA.EMPLOYEE_ID=TR.EMPLOYEE_ID AND ALA.R_A_ID=:employeeId)
                LEFT JOIN HRIS_EMPLOYEES U
                ON(U.EMPLOYEE_ID      = RA.RECOMMEND_BY
                OR U.EMPLOYEE_ID      =RA.APPROVED_BY
                OR U.EMPLOYEE_ID = ALR.R_A_ID
                OR U.EMPLOYEE_ID = ALA.R_A_ID )
                WHERE 1               =1
                AND (TS.APPROVED_FLAG =
                  CASE
                    WHEN TS.EMPLOYEE_ID IS NOT NULL
                    THEN ('Y')
                  END
                OR TS.EMPLOYEE_ID IS NULL)
                AND U.EMPLOYEE_ID  =:employeeId {$condition}";
        return $this->rawQuery($sql, $boundedParameter);
    }

    public function getPendingList($employeeId) {
        $sql = "SELECT TR.TRAVEL_ID                        AS TRAVEL_ID,
                  TR.TRAVEL_CODE                           AS TRAVEL_CODE,
                  TR.EMPLOYEE_ID                           AS EMPLOYEE_ID,
                  E.FULL_NAME                              AS EMPLOYEE_NAME,
                  E.EMPLOYEE_CODE                             AS EMPLOYEE_CODE,
                  TO_CHAR(TR.REQUESTED_DATE,'DD-MON-YYYY') AS REQUESTED_DATE_AD,
                  BS_DATE(TR.REQUESTED_DATE)               AS REQUESTED_DATE_BS,
                  TO_CHAR(TR.FROM_DATE,'DD-MON-YYYY')      AS FROM_DATE_AD,
                  BS_DATE(TR.FROM_DATE)                    AS FROM_DATE_BS,
                  TO_CHAR(TR.TO_DATE,'DD-MON-YYYY')        AS TO_DATE_AD,
                  BS_DATE(TR.TO_DATE)                      AS TO_DATE_BS,
                  TR.DESTINATION                           AS DESTINATION,
                  TR.PURPOSE                               AS PURPOSE,
                  TR.REQUESTED_TYPE                        AS REQUESTED_TYPE,
                  (
                  CASE
                    WHEN TR.REQUESTED_TYPE = 'ad'
                    THEN 'Advance'
                    ELSE 'Expense'
                  END)                                                            AS REQUESTED_TYPE_DETAIL,
                  NVL(TR.REQUESTED_AMOUNT,0)                                      AS REQUESTED_AMOUNT,
                  TR.TRANSPORT_TYPE                                               AS TRANSPORT_TYPE,
                  INITCAP(HRIS_GET_FULL_FORM(TR.TRANSPORT_TYPE,'TRANSPORT_TYPE')) AS TRANSPORT_TYPE_DETAIL,
                  TO_CHAR(TR.DEPARTURE_DATE)                                      AS DEPARTURE_DATE_AD,
                  BS_DATE(TR.DEPARTURE_DATE)                                      AS DEPARTURE_DATE_BS,
                  TO_CHAR(TR.RETURNED_DATE)                                       AS RETURNED_DATE_AD,
                  BS_DATE(TR.RETURNED_DATE)                                       AS RETURNED_DATE_BS,
                  TR.REMARKS                                                      AS REMARKS,
                  TR.STATUS                                                       AS STATUS,
                  LEAVE_STATUS_DESC(TR.STATUS)                                    AS STATUS_DETAIL,
                  TR.RECOMMENDED_BY                                               AS RECOMMENDED_BY,
                  RE.FULL_NAME                                                    AS RECOMMENDED_BY_NAME,
                  TO_CHAR(TR.RECOMMENDED_DATE)                                    AS RECOMMENDED_DATE_AD,
                  BS_DATE(TR.RECOMMENDED_DATE)                                    AS RECOMMENDED_DATE_BS,
                  TR.RECOMMENDED_REMARKS                                          AS RECOMMENDED_REMARKS,
                  TR.APPROVED_BY                                                  AS APPROVED_BY,
                  AE.FULL_NAME                                                    AS APPROVED_BY_NAME,
                  TO_CHAR(TR.APPROVED_DATE)                                       AS APPROVED_DATE_AD,
                  BS_DATE(TR.APPROVED_DATE)                                       AS APPROVED_DATE_BS,
                  TR.APPROVED_REMARKS                                             AS APPROVED_REMARKS,
                  RAR.EMPLOYEE_ID                                                 AS RECOMMENDER_ID,
                  RAR.FULL_NAME                                                   AS RECOMMENDER_NAME,
                  RAA.EMPLOYEE_ID                                                 AS APPROVER_ID,
                  RAA.FULL_NAME                                                   AS APPROVER_NAME,
                  REC_APP_ROLE(U.EMPLOYEE_ID,
                  CASE WHEN ALR.R_A_ID IS NOT NULL THEN ALR.R_A_ID ELSE RA.RECOMMEND_BY END,
                  CASE WHEN ALA.R_A_ID IS NOT NULL THEN ALA.R_A_ID ELSE RA.APPROVED_BY END
                  )      AS ROLE,
                  REC_APP_ROLE_NAME(U.EMPLOYEE_ID,
                  CASE WHEN ALR.R_A_ID IS NOT NULL THEN ALR.R_A_ID ELSE RA.RECOMMEND_BY END,
                  CASE WHEN ALA.R_A_ID IS NOT NULL THEN ALA.R_A_ID ELSE RA.APPROVED_BY END
                  ) AS YOUR_ROLE,
                  CASE WHEN ( ALR.R_A_ID IS NOT NULL OR ALA.R_A_ID  IS NOT NULL ) THEN 'SECONDARY' ELSE 'PRIMARY' END AS PRI_SEC
                FROM HRIS_EMPLOYEE_TRAVEL_REQUEST TR
                LEFT JOIN HRIS_TRAVEL_SUBSTITUTE TS
                ON TR.TRAVEL_ID = TS.TRAVEL_ID
                LEFT JOIN HRIS_EMPLOYEES E
                ON (E.EMPLOYEE_ID =TR.EMPLOYEE_ID)
                LEFT JOIN HRIS_EMPLOYEES RE
                ON(RE.EMPLOYEE_ID =TR.RECOMMENDED_BY)
                LEFT JOIN HRIS_EMPLOYEES AE
                ON (AE.EMPLOYEE_ID =TR.APPROVED_BY)
                LEFT JOIN HRIS_RECOMMENDER_APPROVER RA
                ON (RA.EMPLOYEE_ID=TR.EMPLOYEE_ID)
                LEFT JOIN HRIS_EMPLOYEES RAR
                ON (RA.RECOMMEND_BY=RAR.EMPLOYEE_ID)
                LEFT JOIN HRIS_EMPLOYEES RAA
                ON(RA.APPROVED_BY=RAA.EMPLOYEE_ID)
                LEFT JOIN HRIS_ALTERNATE_R_A ALR
                ON(ALR.R_A_FLAG='R' AND ALR.EMPLOYEE_ID=TR.EMPLOYEE_ID AND ALR.R_A_ID=:employeeId)
                LEFT JOIN HRIS_ALTERNATE_R_A ALA
                ON(ALA.R_A_FLAG='A' AND ALA.EMPLOYEE_ID=TR.EMPLOYEE_ID AND ALA.R_A_ID=:employeeId)
                LEFT JOIN HRIS_EMPLOYEES U
                ON(U.EMPLOYEE_ID      = RA.RECOMMEND_BY
                OR U.EMPLOYEE_ID      =RA.APPROVED_BY
                OR
                U.EMPLOYEE_ID = ALR.R_A_ID
                OR
                U.EMPLOYEE_ID = ALA.R_A_ID )
                WHERE 1               =1
                AND E.STATUS          ='E'
                AND E.RETIRED_FLAG    ='N'
                AND ((
                ((RA.RECOMMEND_BY = U.EMPLOYEE_ID) OR (ALR.R_A_ID = U.EMPLOYEE_ID))
                AND TR.STATUS         ='RQ')
                OR 
                (((RA.APPROVED_BY    = U.EMPLOYEE_ID)  OR (ALA.R_A_ID = U.EMPLOYEE_ID))
                AND TR.STATUS         ='RC') )
                AND U.EMPLOYEE_ID     =:employeeId
                AND (TS.APPROVED_FLAG =
                  CASE
                    WHEN TS.EMPLOYEE_ID IS NOT NULL
                    THEN ('Y')
                  END
                OR TS.EMPLOYEE_ID IS NULL)";

        $boundedParameter = [];
        $boundedParameter['employeeId'] = $employeeId;
        return $this->rawQuery($sql, $boundedParameter);
    }
}
