<?php
namespace Appraisal\Repository;

use Application\Repository\RepositoryInterface;
use Zend\Db\TableGateway\TableGateway;
use Zend\Db\Adapter\AdapterInterface;
use Zend\Db\Sql\Sql;
use Zend\Db\Sql\Select;
use Zend\Db\Sql\Expression;
use Application\Model\Model;
use Setup\Model\HrEmployees;
use Appraisal\Model\AppraisalAnswer;
use Appraisal\Model\Heading;
use Appraisal\Model\Question;
use Appraisal\Model\StageQuestion; 
use Appraisal\Model\Stage;

class AppraisalAnswerRepository implements RepositoryInterface{
    private $adapter;
    private $tableGateway;
    public function __construct(AdapterInterface $adapter) {
        $this->adapter = $adapter;
        $this->tableGateway = new TableGateway(AppraisalAnswer::TABLE_NAME,$adapter);
    }

    public function add(Model $model) {
        $this->tableGateway->insert($model->getArrayCopyForDB());
    }

    public function delete($id) {
        
    }

    public function edit(Model $model, $id) {
        $this->tableGateway->update($model->getArrayCopyForDB(),[AppraisalAnswer::ANSWER_ID=>$id]);
    }

    public function fetchAll() {
        
    }

    public function fetchById($id) {
        
    }
    public function fetchByAllDtl($appraisalId,$questionId,$employeeId,$userId){
        $result = $this->tableGateway->select([AppraisalAnswer::APPRAISAL_ID=>$appraisalId, AppraisalAnswer::QUESTION_ID=>$questionId,AppraisalAnswer::EMPLOYEE_ID=>$employeeId,AppraisalAnswer::USER_ID=>$userId]);
        return $result->current();
    }
    public function getByAppIdEmpIdUserId($headingId,$appraisalId,$employeeId,$userId,$orderCondition=null){
        $sql = new Sql($this->adapter);
        $select = $sql->select();
        $select->from(['APS' => AppraisalAnswer::TABLE_NAME])
                ->join(['Q' => Question::TABLE_NAME], "Q.". Question::QUESTION_ID.'=APS.'.AppraisalAnswer::QUESTION_ID, ["QUESTION_EDESC"=>new Expression("INITCAP(Q.QUESTION_EDESC)"),"ANSWER_TYPE"], "left")
                ->join(['H' => Heading::TABLE_NAME], "H.".Heading::HEADING_ID.'=Q.'.Question::HEADING_ID, ["HEADING_EDESC"=>new Expression("INITCAP(H.HEADING_EDESC)")], "left")
                ->join(['S' => Stage::TABLE_NAME], "S.".Stage::STAGE_ID.'=APS.'.AppraisalAnswer::STAGE_ID, ["STAGE_EDESC"=>new Expression("INITCAP(S.STAGE_EDESC)")], "left");

        $select->where([
            "APS.".AppraisalAnswer::APPRAISAL_ID=>$appraisalId,
            "APS.".AppraisalAnswer::EMPLOYEE_ID=>$employeeId,
            "APS.".AppraisalAnswer::USER_ID=>$userId,
            "H.".Heading::HEADING_ID =>$headingId]);
        if($orderCondition!=null){
            $select->where(["S.ORDER_NO".$orderCondition]);
        }
        $select->order("Q.ORDER_NO");
        $statement = $sql->prepareStatementForSqlObject($select);
        $result = $statement->execute();
        return $result;
    }
    public function fetchByEmpAppraisalId($employeeId,$appraisalId){
        $result = $this->tableGateway->select([AppraisalAnswer::APPRAISAL_ID=>$appraisalId, AppraisalAnswer::EMPLOYEE_ID=>$employeeId]);
        return $result->current(); 
    }
}