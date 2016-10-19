<?php

namespace Payroll\Repository;


use Application\Model\Model;
use Application\Repository\RepositoryInterface;
use Payroll\Model\FlatValueDetail;
use Payroll\Model\MonthlyValueDetail;
use Setup\Model\Branch;
use Setup\Model\Department;
use Setup\Model\Designation;
use Setup\Model\HrEmployees;
use Zend\Db\Adapter\AdapterInterface;
use Zend\Db\Sql\Select;
use Zend\Db\Sql\Sql;
use Zend\Db\TableGateway\TableGateway;

class FlatValueDetailRepo implements RepositoryInterface
{
    private $adapter;
    private $gateway;

    public function __construct(AdapterInterface $adapter)
    {
        $this->adapter = $adapter;
        $this->gateway = new TableGateway(FlatValueDetail::TABLE_NAME, $adapter);
    }

    public function add(Model $model)
    {
        $this->gateway->insert($model->getArrayCopyForDB());
    }

    public function edit(Model $model, $id)
    {
        $this->gateway->update($model->getArrayCopyForDB(), [FlatValueDetail::EMPLOYEE_ID => $id[0], FlatValueDetail::FLAT_ID => $id[1]]);
    }

    public function fetchAll()
    {

    }

    public function filter($branchId, $departmentId, $designationId, $id)
    {
        $sql = new Sql($this->adapter);
        $select = $sql->select();

        $select->columns(["EMPLOYEE_ID", "FIRST_NAME", "MIDDLE_NAME", "LAST_NAME"], true);
        $select->from(['E' => "HR_EMPLOYEES"])
            ->join(['M' => FlatValueDetail::TABLE_NAME], 'M.' . FlatValueDetail::EMPLOYEE_ID . '=E.EMPLOYEE_ID', [FlatValueDetail::FLAT_ID, FlatValueDetail::FLAT_VALUE], Select::JOIN_LEFT);
        if ($branchId != -1) {
            $select->where(["E." . Branch::BRANCH_ID . "=$branchId"]);
        }
        if ($departmentId != -1) {
            $select->where(["E." . Department::DEPARTMENT_ID . "=$departmentId"]);
        }
        if ($designationId != -1) {
            $select->where(["E." . Designation::DESIGNATION_ID . "=$designationId"]);
        }
        $select->where("M." . FlatValueDetail::FLAT_ID . "=" . $id);
        $select->order("E.EMPLOYEE_ID ASC");

        $statement = $sql->prepareStatementForSqlObject($select);
        $result = $statement->execute();
        return $result;
    }


    public function fetchEmployees($branchId, $departmentId, $designationId)
    {
        $sql = new Sql($this->adapter);
        $select = $sql->select();

        $select->columns(["EMPLOYEE_ID", "FIRST_NAME", "MIDDLE_NAME", "LAST_NAME"], true);
        $select->from(['E' => "HR_EMPLOYEES"]);
        if ($branchId != -1) {
            $select->where(["E." . Branch::BRANCH_ID . "=$branchId"]);
        }
        if ($departmentId != -1) {
            $select->where(["E." . Department::DEPARTMENT_ID . "=$departmentId"]);
        }
        if ($designationId != -1) {
            $select->where(["E." . Designation::DESIGNATION_ID . "=$designationId"]);
        }
        $select->order("E.EMPLOYEE_ID ASC");

        $statement = $sql->prepareStatementForSqlObject($select);
        $result = $statement->execute();
        return $result;
    }

    public function fetchById($id)
    {
        return $this->gateway->select([FlatValueDetail::FLAT_ID => $id[1], FlatValueDetail::EMPLOYEE_ID => $id[0]])->current();
    }

    public function delete($id)
    {
        // TODO: Implement delete() method.
    }
}