<?php

namespace System\Repository;

use Application\Model\Model;
use Application\Repository\RepositoryInterface;
use System\Model\UserSetup;
use Traversable;
use Zend\Db\Adapter\AdapterInterface;
use Zend\Db\Sql\Expression as Expression2;
use Zend\Db\Sql\Predicate\Expression;
use Zend\Db\Sql\Select;
use Zend\Db\Sql\Sql;
use Zend\Db\TableGateway\TableGateway;

class UserSetupRepository implements RepositoryInterface {

    private $adapter;
    private $tableGateway;

    public function __construct(AdapterInterface $adapter) {
        $this->tableGateway = new TableGateway(UserSetup::TABLE_NAME, $adapter);
        $this->adapter = $adapter;
    }

    public function add(Model $model) {
        $this->tableGateway->insert($model->getArrayCopyForDB());
    }

    public function edit(Model $model, $id) {
        $this->tableGateway->update($model->getArrayCopyForDB(), [UserSetup::USER_ID => $id]);
    }

    public function fetchAll(): Traversable {
        $sql = new Sql($this->adapter);
        $select = $sql->select();
        $select->columns([
            new Expression("STATUS_DESC(US.STATUS) AS STATUS"),
            new Expression("US.USER_ID AS USER_ID"),
            new Expression("US.USER_NAME AS USER_NAME"),
            new Expression("FN_DECRYPT_PASSWORD(US.PASSWORD) AS PASSWORD"),
            new Expression("US.EMPLOYEE_ID AS EMPLOYEE_ID"),
            new Expression("US.ROLE_ID AS ROLE_ID"),
                ], true);

        $select->from(['US' => UserSetup::TABLE_NAME])
                ->join(['R' => 'HRIS_ROLES'], "R.ROLE_ID=US.ROLE_ID", ['ROLE_NAME'])
                ->join(['E' => "HRIS_EMPLOYEES"], "E.EMPLOYEE_ID=US.EMPLOYEE_ID", ['FULL_NAME' => new Expression("INITCAP(E.FULL_NAME)")], Select::JOIN_LEFT)
                ->join(['C' => "HRIS_COMPANY"], "C.COMPANY_ID=E.COMPANY_ID", ['COMPANY_NAME' => new Expression("(C.COMPANY_NAME)")], Select::JOIN_LEFT);

        $select->order(['C.COMPANY_NAME' => Select::ORDER_ASCENDING, 'R.ROLE_NAME' => Select::ORDER_ASCENDING]);
        $statement = $sql->prepareStatementForSqlObject($select);
        return $statement->execute();
    }

    public function fetchFiltered($filter = null): array {
        $sql = new Sql($this->adapter);
        $select = $sql->select();
        $select->columns([
            new Expression("STATUS_DESC(US.STATUS) AS STATUS"),
            new Expression("US.USER_ID AS USER_ID"),
            new Expression("US.USER_NAME AS USER_NAME"),
            new Expression("FN_DECRYPT_PASSWORD(US.PASSWORD) AS PASSWORD"),
            new Expression("US.EMPLOYEE_ID AS EMPLOYEE_ID"),
            new Expression("US.ROLE_ID AS ROLE_ID"),
                ], true);

        $select->from(['US' => UserSetup::TABLE_NAME])
                ->join(['R' => 'HRIS_ROLES'], "R.ROLE_ID=US.ROLE_ID", ['ROLE_NAME'])
                ->join(['E' => "HRIS_EMPLOYEES"], "E.EMPLOYEE_ID=US.EMPLOYEE_ID", ['FULL_NAME' => new Expression("INITCAP(E.FULL_NAME)"),'EMPLOYEE_CODE'=>'EMPLOYEE_CODE'], Select::JOIN_LEFT)
                ->join(['C' => "HRIS_COMPANY"], "C.COMPANY_ID=E.COMPANY_ID", ['COMPANY_NAME' => new Expression("(C.COMPANY_NAME)")], Select::JOIN_LEFT);

        $select->where(["US.STATUS" =>"E" ]);
        if (isset($filter['companyId']) && $filter['companyId'] != -1) {
            $select->where(["E.COMPANY_ID" => $filter['companyId']]);
        }
        if (isset($filter['branchId']) && $filter['branchId'] != -1) {
            $select->where(["E.BRANCH_ID" => $filter['branchId']]);
        }
        if (isset($filter['departmentId']) && $filter['departmentId'] != -1) {
            $select->where(["E.DEPARTMENT_ID" => $filter['departmentId']]);
        }
        if (isset($filter['designationId']) && $filter['designationId'] != -1) {
            $select->where(["E.DESIGNATION_ID" => $filter['designationId']]);
        }
        if (isset($filter['positionId']) && $filter['positionId'] != -1) {
            $select->where(["E.POSITION_ID" => $filter['positionId']]);
        }
        if (isset($filter['serviceTypeId']) && $filter['serviceTypeId'] != -1) {
            $select->where(["E.SERVICE_TYPE_ID" => $filter['serviceTypeId']]);
        }
        if (isset($filter['employeeType']) && $filter['employeeType'] != -1) {
            $select->where(["E.EMPLOYEE_TYPE" => "'{$filter['employeeType']}'"]);
        }
        if (isset($filter['employeeId']) && $filter['employeeId'] != -1) {
            $select->where(["E.EMPLOYEE_ID" => $filter['employeeId']]);
        }

        $select->order(['C.COMPANY_NAME' => Select::ORDER_ASCENDING, 'R.ROLE_NAME' => Select::ORDER_ASCENDING]);
        $statement = $sql->prepareStatementForSqlObject($select);
        $result = $statement->execute();
        return iterator_to_array($result, false);
    }

    //to get the employee list for select option
    public function getEmployeeList($employeeId = null) {
        $boundedParameter = [];

        $sql = "SELECT EMPLOYEE_CODE||'-'||FULL_NAME  AS FULL_NAME,EMPLOYEE_ID FROM HRIS_EMPLOYEES WHERE STATUS='E' AND RETIRED_FLAG='N' AND EMPLOYEE_ID NOT IN (SELECT EMPLOYEE_ID FROM HRIS_USERS WHERE STATUS='E'AND EMPLOYEE_ID IS NOT NULL)";

        if ($employeeId != null) {
            $boundedParameter['employeeId']=$employeeId;
            $sql = " 
        SELECT EMPLOYEE_CODE||'-'||FULL_NAME  AS FULL_NAME,EMPLOYEE_ID FROM HRIS_EMPLOYEES WHERE STATUS='E' AND EMPLOYEE_ID IN (:employeeId)";
        }

        $statement = $this->adapter->query($sql);
        $resultset = $statement->execute($boundedParameter);

        $entitiesArray = array();
        foreach ($resultset as $result) {
            $entitiesArray[$result['EMPLOYEE_ID']] = $result['FULL_NAME'];
        }
        return $entitiesArray;
    }

    public function fetchById($id) {
        $sql = new Sql($this->adapter);
        $select = $sql->select();
        $select->columns([
            new Expression("STATUS AS STATUS"),
            new Expression("USER_ID AS USER_ID"),
            new Expression("USER_NAME AS USER_NAME"),
            new Expression("FN_DECRYPT_PASSWORD(PASSWORD) AS PASSWORD"),
            new Expression("EMPLOYEE_ID AS EMPLOYEE_ID"),
            new Expression("ROLE_ID AS ROLE_ID"),
            new Expression("IS_LOCKED AS IS_LOCKED"),
                ], true);

        $select->from(UserSetup::TABLE_NAME);
        $select->where([
            UserSetup::USER_ID => $id
        ]);

        $statement = $sql->prepareStatementForSqlObject($select);
        $result = $statement->execute();
        return $result->current();
    }

    public function fetchByUsername($username) {
        $sql = new Sql($this->adapter);
        $select = $sql->select();
        $select->columns([
            new Expression("US.STATUS AS STATUS"),
            new Expression("US.USER_ID AS USER_ID"),
            new Expression("US.USER_NAME AS USER_NAME"),
            new Expression("US.PASSWORD AS PASSWORD"),
            new Expression("US.EMPLOYEE_ID AS EMPLOYEE_ID"),
            new Expression("US.ROLE_ID AS ROLE_ID"),
                ], true);

        $select->from(['US' => UserSetup::TABLE_NAME])
                ->join(['E' => "HRIS_EMPLOYEES"], "E.EMPLOYEE_ID=US.EMPLOYEE_ID", ['FIRST_NAME' => new Expression("INITCAP(E.FIRST_NAME)"), 'MIDDLE_NAME' => new Expression("INITCAP(E.MIDDLE_NAME)"), 'LAST_NAME' => new Expression("INITCAP(E.LAST_NAME)"), 'EMAIL_OFFICIAL'])
                ->join(['R' => 'HRIS_ROLES'], "R.ROLE_ID=US.ROLE_ID", ['ROLE_NAME']);

        $select->where([
            "US.STATUS='E'",
            "E.STATUS='E'"
        ]);
        $select->where(["US.USER_NAME"=>$username]);

        $statement = $sql->prepareStatementForSqlObject($select);
        $result = $statement->execute();

        return $result->current();
    }

    public function delete($id) {
        $this->tableGateway->update([UserSetup::STATUS => "D"], [UserSetup::USER_ID => $id]);
    }

    public function updateByEmpId($employeeId, $password) {
        $this->tableGateway->update([UserSetup::PASSWORD => $password,UserSetup::FIRST_TIME=>'N',UserSetup::MODIFIED_DT=> new Expression2('TRUNC(SYSDATE)')], [UserSetup::EMPLOYEE_ID => $employeeId]);
    }

    public function getUserByEmployeeId($employeeId) {
        $sql = new Sql($this->adapter);
        $select = $sql->select();
        $select->columns([
            new Expression("STATUS AS STATUS"),
            new Expression("USER_ID AS USER_ID"),
            new Expression("USER_NAME AS USER_NAME"),
            new Expression("FN_DECRYPT_PASSWORD(PASSWORD) AS PASSWORD"),
            new Expression("EMPLOYEE_ID AS EMPLOYEE_ID"),
            new Expression("ROLE_ID AS ROLE_ID"),
                ], true);

        $select->from(UserSetup::TABLE_NAME);
        $select->where([
            UserSetup::EMPLOYEE_ID => $employeeId
        ]);

        $statement = $sql->prepareStatementForSqlObject($select);
        $result = $statement->execute();
        return $result->current();
    }

    public function checkUserNameAvailability($userName,$userId) {
        $boundedParameter = [];
        $boundedParameter['userName']=$userName;
        $sql = "SELECT * FROM HRIS_USERS WHERE LOWER(USER_NAME)=LOWER(:userName) ";
        
        if($userId){
            $boundedParameter['userId']=$userId;
        $sql .= "AND USER_ID!=:userId";
        }
        // echo '<pre>';print_r($sql);die;
        $statement = $this->adapter->query($sql);
        $result = $statement->execute($boundedParameter);
        return $result->current();
    }

}
