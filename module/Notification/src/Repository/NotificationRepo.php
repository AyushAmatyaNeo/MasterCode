<?php

namespace Notification\Repository;

use Application\Helper\EntityHelper;
use Application\Helper\Helper;
use Application\Model\Model;
use Application\Repository\RepositoryInterface;
use Notification\Model\Notification;
use Setup\Model\EmployeeFile;
use Setup\Model\HrEmployees;
use Zend\Db\Adapter\AdapterInterface;
use Zend\Db\Sql\Select;
use Zend\Db\Sql\Sql;
use Application\Repository\HrisRepository;
use Zend\Db\TableGateway\TableGateway;

class NotificationRepo extends HrisRepository implements RepositoryInterface {

    protected $tableGateway;
    protected $adapter;

    public function __construct(AdapterInterface $adapter) {
        $this->adapter = $adapter;
        $this->tableGateway = new TableGateway(Notification::TABLE_NAME, $adapter);
    }

    public function add(Model $model) {
        return $this->tableGateway->insert($model->getArrayCopyForDB());
    }

    public function delete($id) {
        
    }

    public function edit(Model $model, $id) {
        return $this->tableGateway->update($model->getArrayCopyForDB(), [Notification::MESSAGE_ID => $id]);
    }

    public function editByEmployeeId(Model $model, $id) {
        return $this->tableGateway->update($model->getArrayCopyForDB(), [Notification::MESSAGE_TO => $id]);
    }

    public function fetchAll() {
        
    }

    public function fetchAllBy($where) {
        $sql = new Sql($this->adapter);
        $select = $sql->select();
        $select->columns([
            Helper::columnExpression(Notification::MESSAGE_ID, "N"),
            Helper::columnExpression(Notification::MESSAGE_TITLE, "N"),
            Helper::columnExpression(Notification::MESSAGE_DESC, "N"),
            Helper::datetimeExpression(Notification::MESSAGE_DATETIME, "N"),
            Helper::columnExpression(Notification::MESSAGE_FROM, "N"),
            Helper::columnExpression(Notification::MESSAGE_TO, "N"),
            Helper::columnExpression(Notification::STATUS, "N"),
                ], TRUE);
        $select->from(['N' => Notification::TABLE_NAME]);
        $select->where($where);
        $select->order(["N." . Notification::MESSAGE_DATETIME => Select::ORDER_DESCENDING]);
        $statement = $sql->prepareStatementForSqlObject($select);
        $result = $statement->execute();
        return $result;
    }

    public function fetchAllWithEmpDet($where) {
        $sql = new Sql($this->adapter);
        $select = $sql->select();
        $select->columns([
            Helper::columnExpression(Notification::MESSAGE_ID, "N"),
            Helper::columnExpression(Notification::MESSAGE_TITLE, "N"),
            Helper::columnExpression(Notification::MESSAGE_DESC, "N"),
            Helper::datetimeExpression(Notification::MESSAGE_DATETIME, "N"),
            Helper::columnExpression(Notification::MESSAGE_FROM, "N"),
            Helper::columnExpression(Notification::MESSAGE_TO, "N"),
            Helper::columnExpression(Notification::STATUS, "N"),
                ], TRUE);
        $select->from(['N' => Notification::TABLE_NAME])
                ->join(['E' => HrEmployees::TABLE_NAME], "N." . Notification::MESSAGE_FROM . "= " . "E." . HrEmployees::EMPLOYEE_ID, [HrEmployees::FIRST_NAME, HrEmployees::MIDDLE_NAME, HrEmployees::LAST_NAME], Select::JOIN_LEFT)
                ->join(['F' => EmployeeFile::TABLE_NAME], "E." . HrEmployees::PROFILE_PICTURE_ID . " = " . "F." . EmployeeFile::FILE_CODE, [EmployeeFile::FILE_PATH], Select::JOIN_LEFT);
        $select->where($where);
        $select->order(["N." . Notification::MESSAGE_DATETIME => Select::ORDER_DESCENDING]);
        $statement = $sql->prepareStatementForSqlObject($select);
        $result = $statement->execute();
        return $result;
    }

    public function fetchById($id) {
        return $this->tableGateway->select([Notification::MESSAGE_ID => $id])->current();
    }
    
    public function fetchAllEmployeeEmail($postData) {
        $whereCondition = EntityHelper::getSearchConditonBounded($postData['company'], $postData['branch'], $postData['department'], $postData['position'], $postData['designation'], $postData['serviceType'], $postData['serviceEventType'], $postData['employeeType'], $postData['employee']);

        $boundedParameter = [];
        $boundedParameter=array_merge($boundedParameter, $whereCondition['parameter']);

        $sql = 'SELECT E.FULL_NAME,E.EMAIL_OFFICIAL FROM HRIS_EMPLOYEES E where E.EMAIL_OFFICIAL IS NOT NULL ';
        $sql.=$whereCondition['sql'];
        return $this->rawQuery($sql, $boundedParameter);
        // $statement = $this->adapter->query($sql);
        // $result = $statement->execute();
        // return Helper::extractDbData($result);
    }

}
