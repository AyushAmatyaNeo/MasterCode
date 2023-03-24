<?php

namespace PrintLayout\Repository;

use Application\Helper\Helper;
use Application\Model\Model;
use Application\Controller\HrisController;
use Application\Repository\RepositoryInterface;
use Zend\Db\Adapter\AdapterInterface;
use Zend\Db\TableGateway\TableGateway;
use ZendDeveloperTools\ReportInterface;
use PrintLayout\Model\PrintLayoutTemplate;

class PrintRepository extends HrisController{
    protected $tableGateway;
    protected $adapter;

    public function __construct(AdapterInterface $adapter) {
        $this->adapter = $adapter;
        $this->tableGateway = new TableGateway(PrintLayoutTemplate::TABLE_NAME, $adapter);
    }



    public function fetchById($id){
        return $this->tableGateway->select([PrintLayoutTemplate::PR_ID => $id])->current();
        // $sql = "select PR_ID, PR_CODE,SUBJECT, BODY, CC from HRIS_PRINT_REPORT_MASTER where PR_ID = $id";
        // $statement = $this->adapter->query($sql);
        // $result = $statement->execute();
        // return Helper::extractDbData($result);
    }

    public function getByCode($id){
        $sql = "select PR_ID, PR_CODE,SUBJECT, BODY, CC from HRIS_PRINT_REPORT_MASTER where PR_CODE = '$id'";
        $statement = $this->adapter->query($sql);
        $result = $statement->execute();
        return Helper::extractDbData($result);
    }

    public function getServiceEventVariables($id){
        $sql = "SELECT TO_CHAR(H.START_DATE, 'YYYY-MM-DD') as START_DATE ,
        TO_CHAR(H.END_DATE, 'YYYY-MM-DD') as END_DATE,
        SE.SERVICE_EVENT_TYPE_NAME,
        C.COMPANY_NAME,
        B.BRANCH_NAME,
        DEP.DEPARTMENT_NAME,
        DES.DESIGNATION_TITLE,
        P.POSITION_NAME,
        ST.SERVICE_TYPE_NAME,
        H.TO_SALARY,
        H.EVENT_DATE
      FROM HRIS_JOB_HISTORY H 
      JOIN HRIS_SERVICE_EVENT_TYPES SE ON (H.SERVICE_EVENT_TYPE_ID = SE.SERVICE_EVENT_TYPE_ID)
      JOIN HRIS_COMPANY C ON (H.TO_COMPANY_ID = C.COMPANY_ID)
      JOIN HRIS_BRANCHES B ON (H.TO_BRANCH_ID = B.BRANCH_ID)
      JOIN HRIS_DEPARTMENTS DEP ON (H.TO_DEPARTMENT_ID = DEP.DEPARTMENT_ID)
      JOIN HRIS_DESIGNATIONS DES ON (H.TO_DESIGNATION_ID = DES.DESIGNATION_ID)
      JOIN HRIS_POSITIONS P ON (H.TO_POSITION_ID = P.POSITION_ID)
      JOIN HRIS_SERVICE_TYPES ST ON (H.TO_SERVICE_TYPE_ID = ST.SERVICE_TYPE_ID)
      WHERE H.JOB_HISTORY_ID = $id";
        $statement = $this->adapter->query($sql);
        $result = $statement->execute(); 
        return Helper::extractDbData($result);

    }
}