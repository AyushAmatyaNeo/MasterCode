<?php

/**
 * Created by PhpStorm.
 * User: ukesh
 * Date: 9/9/16
 * Time: 10:53 AM 
 */

namespace LeaveManagement\Repository;

use Application\Helper\EntityHelper;
use Application\Helper\Helper;
use Application\Model\Model;
use Application\Repository\RepositoryInterface;
use LeaveManagement\Model\LeaveApply;
use Zend\Db\Adapter\AdapterInterface;
use Zend\Db\Sql\Select;
use Zend\Db\TableGateway\TableGateway;
use Setup\Repository\BranchRepository;
use Setup\Model\Logs;

class LeaveApplyRepository implements RepositoryInterface
{

    private $tableGateway;
    private $adapter;
    private $logTable;

    public function __construct(AdapterInterface $adapter)
    {
        $this->tableGateway = new TableGateway(LeaveApply::TABLE_NAME, $adapter);
        $this->logTable = new TableGateway(Logs::TABLE_NAME, $adapter);
        $this->adapter = $adapter;
    }

    public function pushFileLink($data)
    {
        $fileName = $data['fileName'];
        $fileInDir = $data['filePath'];
        $sql = "INSERT INTO HRIS_LEAVE_FILES(FILE_ID, FILE_NAME, FILE_IN_DIR_NAME, LEAVE_ID) VALUES((SELECT MAX(FILE_ID)+1 FROM HRIS_LEAVE_FILES), '$fileName', '$fileInDir', null)";
        $statement = $this->adapter->query($sql);
        $statement->execute();
        $sql = "SELECT * FROM HRIS_LEAVE_FILES WHERE FILE_ID IN (SELECT MAX(FILE_ID) AS FILE_ID FROM HRIS_LEAVE_FILES)";
        $statement = $this->adapter->query($sql);
        return Helper::extractDbData($statement->execute());
    }

    public function linkLeaveWithFiles()
    {
        if (!empty($_POST['fileUploadList'])) {
            $filesList = $_POST['fileUploadList'];
            $filesList = implode(',', $filesList);

            $sql = "UPDATE HRIS_LEAVE_FILES SET LEAVE_ID = (SELECT MAX(ID) FROM HRIS_EMPLOYEE_LEAVE_REQUEST) 
                    WHERE FILE_ID IN($filesList)";
            $statement = $this->adapter->query($sql);
            $statement->execute();
        }
    }

    public function add(Model $model)
    {
        $new = $model->getArrayCopyForDB();
        $this->tableGateway->insert($model->getArrayCopyForDB());
        $this->linkLeaveWithFiles();
        if ($model->status == 'AP') {
            EntityHelper::rawQueryResult($this->adapter, "
                BEGIN
                    HRIS_REATTENDANCE({$new['START_DATE']->getExpression()},{$new['EMPLOYEE_ID']},{$new['END_DATE']->getExpression()});
                END;
                ");
        }
        $branch = new BranchRepository($this->adapter);
        $logs = new Logs();
        $logs->module = 'Leave Apply Admin';
        $logs->operation = 'I';
        $logs->createdBy = $new['CREATED_BY'];
        $logs->createdDesc = 'leave id - ' . $new['LEAVE_ID'] . ',' . 'Employee id-' . $new['EMPLOYEE_ID'];
        $logs->tableDesc = 'HRIS_EMPLOYEE_LEAVE_REQUEST';
        $branch->insertLogs($logs);
    }

    public function edit(Model $model, $id)
    {
        // TODO: Implement edit() method.
    }

    public function fetchAll()
    {
        // TODO: Implement fetchAll() method.
    }

    public function fetchById($id)
    {
        return $this->tableGateway->select(function (Select $select) use ($id) {
            $select->columns(Helper::convertColumnDateFormat($this->adapter, new LeaveApply(), [
                'startDate', 'endDate'
            ]), false);
            $select->where([LeaveApply::ID => $id]);
        })->current();
    }

    public function delete($id)
    {
        // TODO: Implement delete() method.
    }
}
