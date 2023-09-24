<?php
/**
 * Created by PhpStorm.
 * User: root
 * Date: 11/13/16
 * Time: 3:14 PM
 */
namespace Setup\Repository;

use Application\Model\Model;
use Application\Repository\RepositoryInterface;
use Zend\Db\Adapter\AdapterInterface;
use Setup\Model\EmployeeSalaryHistory;
use Zend\Db\TableGateway\TableGateway;
use Zend\Db\Sql\Select;
use Zend\Db\Sql\Sql;
use Zend\Db\Sql\Expression;
use Zend\Authentication\AuthenticationService;
use Application\Helper\Helper;

class EmployeeSalaryHistoryRepository implements RepositoryInterface {

    private $tableGateway;
    private $adapter;
    private $loggedIdEmployeeId;

    public function __construct(AdapterInterface $adapter)
    {
        $this->adapter = $adapter;
        $this->tableGateway = new TableGateway(EmployeeSalaryHistory::TABLE_NAME,$adapter);
        $auth = new AuthenticationService();
        $this->loggedIdEmployeeId = $auth->getStorage()->read()['employee_id'];
    }

    public function add(Model $model)
    {
        $this->tableGateway->insert($model->getArrayCopyForDB());
    }


    public function edit(Model $model, $id)
    {
        $this->tableGateway->update($model->getArrayCopyForDB(),[EmployeeSalaryHistory::ID=>$id]);

    }

    public function fetchAll()
    {
        return $this->tableGateway->select([EmployeeSalaryHistory::STATUS=>'E']);
    }

    public function fetchById($id)
    {
        $result = $this->tableGateway->select([EmployeeSalaryHistory::ID=>$id]);
        return $result->current();
    }

    public function fetchByEmployeeId($employeeId){
        return $this->tableGateway->select(function(Select $select)use($employeeId){
                $select->where([EmployeeSalaryHistory::EMPLOYEE_ID=>$employeeId,EmployeeSalaryHistory::STATUS=>'E']);
                $select->order(EmployeeSalaryHistory::ID);//IS_ENABLE DESC, FROM_DATE DESC, PAY_ID
            });
    }

    public function delete($id)
    {
        $modifiedDt = Helper::getcurrentExpressionDate();
        $employeeID = $this->loggedIdEmployeeId;
        $this->tableGateway->update([EmployeeSalaryHistory::STATUS=>'D', EmployeeSalaryHistory::MODIFIED_BY=>$employeeID, EmployeeSalaryHistory::MODIFIED_DATE=>$modifiedDt],[EmployeeSalaryHistory::ID=>$id]);
    }
    public function getByEmpId($employeeId){
        $sql = new Sql($this->adapter);
        $select = $sql->select();
        $select->columns([
            new Expression("INITCAP(TO_CHAR(SH.FROM_DATE, 'DD-MON-YYYY')) AS FROM_DATE"),
            new Expression("INITCAP(TO_CHAR(SH.TO_DATE, 'DD-MON-YYYY')) AS TO_DATE"), 
            new Expression("SH.ID AS ID"), 
            new Expression("SH.PAY_ID AS PAY_ID"),
            new Expression("SH.AMOUNT AS AMOUNT"),
            new Expression("SH.IS_DEFAULT_HEAD AS IS_DEFAULT_HEAD"),
            new Expression("SH.IS_ENABLE AS IS_ENABLE"),
            new Expression("SH.EFFECT_BY_WORKING_DAY AS EFFECT_BY_WORKING_DAY")],TRUE);
        $select->from(['SH'=>EmployeeSalaryHistory::TABLE_NAME]); 
        $select->where(["SH." . EmployeeSalaryHistory::EMPLOYEE_ID => $employeeId]);
        $select->where(["SH." . EmployeeSalaryHistory::STATUS . "='E'"]);
        $select->order("IS_ENABLE DESC, FROM_DATE DESC, PAY_ID");
        $statement = $sql->prepareStatementForSqlObject($select);
        $result = $statement->execute();
        return $result;
    }

    public function callSalaryHistoryCorrectionProc($employeeId){
        $sql="BEGIN
                HRIS_SALARY_HISTORY_CORRECTION($employeeId);      
            END;";
        $statement = $this->adapter->query($sql);
        $statement->execute();
    }
}