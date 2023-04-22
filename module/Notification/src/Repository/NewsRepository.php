<?php
 
namespace Notification\Repository;

use Application\Helper\EntityHelper;
use Application\Helper\Helper;
use Application\Model\Model;
use Application\Repository\RepositoryInterface;
use Notification\Model\NewsModel;
use Notification\Model\NewsTypeModel;
use Setup\Model\Company;
use Setup\Model\Designation;
use Zend\Db\Adapter\AdapterInterface;
use Zend\Db\Sql\Select;
use Zend\Db\Sql\Sql;
use Application\Repository\HrisRepository;
use Zend\Db\TableGateway\TableGateway;

class NewsRepository extends HrisRepository implements RepositoryInterface {

    protected $tableGateway;
    protected $adapter;

    public function __construct(AdapterInterface $adapter) {
        $this->adapter = $adapter;
        $this->tableGateway = new TableGateway(NewsModel::TABLE_NAME, $adapter);
    }

    public function add(Model $model) {
        $this->tableGateway->insert($model->getArrayCopyForDB());
    }

    public function delete($id) {
        $this->tableGateway->update([NewsModel::STATUS => 'D'], [NewsModel::NEWS_ID => $id]);
    }

    public function edit(Model $model, $id) {
        $data = $model->getArrayCopyForDB();
        unset($data[NewsModel::CREATED_BY]);
        unset($data[NewsModel::CREATED_DT]);
        unset($data[NewsModel::STATUS]);
        $this->tableGateway->update($data, [NewsModel::NEWS_ID => $id]);
    }

    public function fetchAll() {

        $sql = new Sql($this->adapter);
        $select = $sql->select();
        $select->from(['N' => NewsModel::TABLE_NAME]);
        $select->join(['NT' => NewsTypeModel::TABLE_NAME], "NT." . NewsTypeModel::NEWS_TYPE_ID . "=N." . NewsModel::NEWS_TYPE, array('NEWS_TYPE_ID', 'NEWS_TYPE_DESC'), 'LEFT');
        $select->where(["N." . NewsModel::STATUS => 'E']);
        $select->order(["N." . NewsModel::NEWS_DATE => Select::ORDER_DESCENDING]);

        $statement = $sql->prepareStatementForSqlObject($select);
        $result = $statement->execute();
        // echo '<pre>';print_r($statement);die;
        return $result;
    }

    public function fetchById($id) {
        $sql = new Sql($this->adapter);
        $select = $sql->select();
        $select->from(['N' => NewsModel::TABLE_NAME]);
        $select->where(["N." . NewsModel::NEWS_ID => $id]);
        $select->columns(Helper::convertColumnDateFormat($this->adapter, new NewsModel(), ['newsDate', 'newsExpiryDate'], NULL, 'N'), false);

        $statement = $sql->prepareStatementForSqlObject($select);
        $result = $statement->execute();

        return $result->current();
    }

    public function fetchAllDesignationAndCompany() {
        $sql = new Sql($this->adapter);
        $select = $sql->select();

        $select->columns([Designation::DESIGNATION_ID, Designation::DESIGNATION_TITLE]);
        $select->from(['D' => Designation::TABLE_NAME]);

        $select->join(['C' => Company::TABLE_NAME], "C." . Company::COMPANY_ID . "=D." . Designation::COMPANY_ID, array('COMPANY_ID', 'COMPANY_NAME'), 'inner');

        $select->where(["C." . Company::STATUS => EntityHelper::STATUS_ENABLED]);
        $select->where(["D." . Designation::STATUS => EntityHelper::STATUS_ENABLED]);
        $select->order(["D." . Designation::DESIGNATION_TITLE => Select::ORDER_ASCENDING]);

        $statement = $sql->prepareStatementForSqlObject($select);

        $result = $statement->execute();
        $list = Helper::extractDbData($result);
        $designationList = [];
        foreach ($list as $val) {
            $newKey = $val[Company::COMPANY_ID];
            $designationList[$newKey][] = $val;
        }
        return $designationList;
    }

    public function fetchForEmployee($employeeId, $newsDate) {
        $sql = "SELECT N.*
        FROM HRIS_NEWS N,
          (SELECT COMPANY_ID,   
            BRANCH_ID,
            DEPARTMENT_ID,
            DESIGNATION_ID
          FROM HRIS_EMPLOYEES
          WHERE EMPLOYEE_ID=:employeeId
          ) E
        WHERE (N.COMPANY_ID=E.COMPANY_ID
        OR N.BRANCH_ID     =E.BRANCH_ID
        OR N.DEPARTMENT_ID =E.DEPARTMENT_ID
        OR N.DESIGNATION_ID=E.DESIGNATION_ID)
        AND (N.STATUS      ='E') 
        AND N.NEWS_DATE=:newsDate";

        $boundedParameter = [];
        $boundedParameter['newsDate'] = $newsDate;
        $boundedParameter['employeeId'] = $employeeId;

        return $this->rawQuery($sql, $boundedParameter);
    }

    public function allNewsTypeWise($typeId, $employeeId) {
        $sql = "SELECT AA.*,
                  BB.FILE_PATH,
                  CC.FILE_NAME
                FROM
                  (SELECT N.NEWS_ID,
                    N.NEWS_DATE,
                    N.NEWS_TYPE,
                    N.NEWS_TITLE,
                    N.NEWS_EDESC,
                    N.NEWS_EXPIRY_DT,
                    N.STATUS
                  FROM HRIS_NEWS N
                  WHERE N.STATUS     ='E'
                  AND N.NEWS_TYPE    =:typeId
                  AND :employeeId IN
                    (SELECT NE.EMPLOYEE_ID FROM HRIS_NEWS_EMPLOYEE NE WHERE NE.NEWS_ID=N.NEWS_ID
                    )
                  ) AA
                LEFT JOIN
                  (SELECT NEWS_ID,
                    LISTAGG(FILE_PATH, ',') WITHIN GROUP (
                  ORDER BY FILE_PATH) AS FILE_PATH
                  FROM HRIS_NEWS_FILE
                  GROUP BY NEWS_ID
                  ) BB
                ON (BB.NEWS_ID=AA.NEWS_ID)
                LEFT JOIN
                  (SELECT NEWS_ID,
                    LISTAGG(FILE_NAME, ',') WITHIN GROUP (
                  ORDER BY FILE_NAME) AS FILE_NAME
                  FROM HRIS_NEWS_FILE
                  GROUP BY NEWS_ID
                  ) CC
                ON (CC.NEWS_ID     =AA.NEWS_ID)
                WHERE STATUS       ='E'
                AND NEWS_DATE     <=TRUNC(SYSDATE)
                AND NEWS_EXPIRY_DT>=TRUNC(SYSDATE)
                ORDER BY NEWS_DATE DESC";

        $boundedParameter = [];
        $boundedParameter['typeId'] = $typeId;
        $boundedParameter['employeeId'] = $employeeId;

        return $this->rawQuery($sql, $boundedParameter);        

        // $statement = $this->adapter->query($sql);
        // $result = $statement->execute();
        // return Helper::extractDbData($result);
    }

    public function newsAssign(int $newsId, array $assignedTo) {
        $newsAssignGateway = new TableGateway("HRIS_NEWS_TO", $this->adapter);
        $newsAssignGateway->delete(["NEWS_ID" => $newsId]);
        foreach ($assignedTo as $item) {
            $item['NEWS_ID'] = $newsId;
            $newsAssignGateway->insert($item);
        }
        $boundedParameter = [];
        $boundedParameter['newsId'] = $newsId;
        EntityHelper::rawQueryResult($this->adapter, "BEGIN HRIS_NEWS_TO_PROC(:newsId); END;",$boundedParameter);
    }

    public function getAssignedToList(int $newsId) {
        $newsAssignGateway = new TableGateway("HRIS_NEWS_TO", $this->adapter);
        $newsToList = $newsAssignGateway->select(['NEWS_ID' => $newsId]);

        $return = [
            'companyId' => [],
            'branchId' => [],
            'departmentId' => [],
            'designationId' => [],
            'positionId' => [],
            'serviceTypeId' => [],
            'serviceEventTypeId' => [],
            'employeeType' => [],
            'employeeId' => [],
            'genderId' => [],
        ];


        foreach ($newsToList as $newsTo) {
            if ($newsTo['COMPANY_ID'] != NULL) {
                array_push($return['companyId'], $newsTo['COMPANY_ID']);
            }
            if ($newsTo['BRANCH_ID'] != NULL) {
                array_push($return['branchId'], $newsTo['BRANCH_ID']);
            }
            if ($newsTo['DEPARTMENT_ID'] != NULL) {
                array_push($return['departmentId'], $newsTo['DEPARTMENT_ID']);
            }
            if ($newsTo['DESIGNATION_ID'] != NULL) {
                array_push($return['designationId'], $newsTo['DESIGNATION_ID']);
            }
            if ($newsTo['POSITION_ID'] != NULL) {
                array_push($return['positionId'], $newsTo['POSITION_ID']);
            }
            if ($newsTo['SERVICE_TYPE_ID'] != NULL) {
                array_push($return['serviceTypeId'], $newsTo['SERVICE_TYPE_ID']);
            }
            if ($newsTo['SERVICE_EVENT_TYPE_ID'] != NULL) {
                array_push($return['serviceEventTypeId'], $newsTo['SERVICE_EVENT_TYPE_ID']);
            }
            if ($newsTo['EMPLOYEE_TYPE'] != NULL) {
                array_push($return['employeeType'], $newsTo['EMPLOYEE_TYPE']);
            }
            if ($newsTo['EMPLOYEE_ID'] != NULL) {
                array_push($return['employeeId'], $newsTo['EMPLOYEE_ID']);
            }
            if ($newsTo['GENDER_ID'] != NULL) {
                array_push($return['genderId'], $newsTo['GENDER_ID']);
            }
        }
        return $return;
    }

    public function updateNewsFileUploads($newsId, $fileList) {
        $id = '';
        $noOfUploads = sizeof($fileList);
        ;
        $counter = 1;
        foreach ($fileList as $data) {
            $id .= ($counter == $noOfUploads) ? $data : $data . ',';
            $counter++;
        }
        $boundedParameter = [];
        $boundedParameter['newsId'] = $newsId;
        $boundedParameter['id'] = $id;
        $updateSql = ($noOfUploads == 0) ? "" : "UPDATE HRIS_NEWS_FILE SET NEWS_ID=:newsId WHERE NEWS_FILE_ID IN (:id);";

        $sql = "BEGIN
            UPDATE HRIS_NEWS_FILE SET NEWS_ID=NULL WHERE NEWS_ID=:newsId;
            {$updateSql}
            END;";
        $statement = $this->adapter->query($sql);
        $result = $statement->execute($boundedParameter);
    }

}
