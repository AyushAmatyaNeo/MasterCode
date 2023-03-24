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

class PrintLayoutRepo extends HrisController{
    protected $tableGateway;
    protected $adapter;

    public function __construct(AdapterInterface $adapter) {
        $this->adapter = $adapter;
        $this->tableGateway = new TableGateway(PrintLayoutTemplate::TABLE_NAME, $adapter);
    }

    public function add(Model $model) {
        return $this->tableGateway->insert($model->getArrayCopyForDB());
    }
    public function getReportsTable() {
        $sql = "select ROWNUM as SN, pr.PR_ID, pr.PR_CODE, PR.SUBJECT, PR.CC from HRIS_PRINT_REPORT_MASTER pr where pr.status='E'";
        $statement = $this->adapter->query($sql);
        $result = $statement->execute(); 
        return Helper::extractDbData($result);
    }

    public function delete($id)
    {
        $this->tableGateway->update([PrintLayoutTemplate::STATUS => 'D'], [PrintLayoutTemplate::PR_ID => $id]);
        $sql = "update HRIS_PRINT_REPORT_MASTER set status='D' where pr_id = $id";
        $statement = $this->adapter->query($sql);
        $statement->execute();

    }

    public function edit(Model $model, $id) {
        // print_r($model->getArrayCopyForDB());
        // print_r($id);die;
        return $this->tableGateway->update($model->getArrayCopyForDB(), [PrintLayoutTemplate::PR_ID => $id]);
    }

    public function fetchAll() {
        $templates = $this->tableGateway->select();
        return Helper::extractDbData($templates, TRUE, PrintLayoutTemplate::PR_ID);
    }

    public function fetchById($id){
        return $this->tableGateway->select([PrintLayoutTemplate::PR_ID => $id])->current();
        
    }

}