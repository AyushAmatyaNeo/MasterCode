<?php

namespace Setup\Repository;

use Application\Helper\EntityHelper;
use Application\Helper\Helper;
use Application\Model\Model;
use Application\Repository\RepositoryInterface;
use Setup\Model\Company;
use Setup\Model\Institute;
use Setup\Model\Training;
use Zend\Db\Adapter\AdapterInterface;
use Zend\Db\Sql\Expression;
use Zend\Db\Sql\Sql;
use Zend\Db\TableGateway\TableGateway;

class TrainingRepository implements RepositoryInterface {

    private $tableGateway;
    private $adapter;

    public function __construct(AdapterInterface $adapter) {
        $this->tableGateway = new TableGateway(Training::TABLE_NAME, $adapter);
        $this->adapter = $adapter;
    }

    public function add(Model $model) {
        $this->tableGateway->insert($model->getArrayCopyForDB());
    }

    public function edit(Model $model, $id) {
        $temp = $model->getArrayCopyForDB();
        if (!$temp['INSTITUTE_ID']) {
            $temp['INSTITUTE_ID'] = null;
        }
        if (!$temp['COMPANY_ID']) {
            $temp['COMPANY_ID'] = null;
        }
        $this->tableGateway->update($temp, [Training::TRAINING_ID => $id]);
    }

    public function fetchAll() {
        $sql = new Sql($this->adapter);
        $select = $sql->select();
        $customCols = ["BS_DATE(TO_CHAR(T.START_DATE, 'DD-MON-YYYY')) AS START_DATE_BS",
            "BS_DATE(TO_CHAR(T.END_DATE, 'DD-MON-YYYY')) AS END_DATE_BS",
            "TO_CHAR(T.START_DATE, 'DD-MON-YYYY') AS START_DATE_AD",
            "TO_CHAR(T.END_DATE, 'DD-MON-YYYY') AS END_DATE_AD"];
        $select->columns(EntityHelper::getColumnNameArrayWithOracleFns(
                        Training::class, [
                    Training::TRAINING_NAME
                        ], NULL, NULL, NULL, NULL, 'T', FALSE, FALSE, NULL, $customCols)
                , false);


        $select->from(['T' => Training::TABLE_NAME]);
        $select->join(['I' => Institute::TABLE_NAME], "T." . Training::INSTITUTE_ID . "=I." . Institute::INSTITUTE_ID, [Institute::INSTITUTE_NAME => new Expression('(I.' . Institute::INSTITUTE_NAME . ')')], 'left');
        $select->join(['C' => Company::TABLE_NAME], "T." . Training::COMPANY_ID . "=C." . Company::COMPANY_ID, [Company::COMPANY_NAME => new Expression('(C.' . Company::COMPANY_NAME . ')')], 'left');
        $select->where(["T.STATUS='E'"]);
        $select->order("T." . Training::TRAINING_NAME . " ASC");
        $statement = $sql->prepareStatementForSqlObject($select);
        $result = $statement->execute();
        $arrayList = [];
        foreach ($result as $row) {
            if ($row['TRAINING_TYPE'] == 'CP') {
                $row['TRAINING_TYPE'] = 'Company Personal';
            } else if ($row['TRAINING_TYPE'] == 'CC') {
                $row['TRAINING_TYPE'] = 'Company Contribution';
            } else {
                $row['TRAINING_TYPE'] = '';
            }
            array_push($arrayList, $row);
        }
        return $arrayList;
    }

    public function fetchById($id) {
        $sql = new Sql($this->adapter);
        $select = $sql->select();
        $select->columns(EntityHelper::getColumnNameArrayWithOracleFns(
                        Training::class, [
                    Training::TRAINING_NAME
                        ], [
                    Training::START_DATE,
                    Training::END_DATE
                        ], NULL, NULL, NULL, 'T')
                , false);

        $select->from(['T' => Training::TABLE_NAME]);
        $select->join(['I' => Institute::TABLE_NAME], "T." . Training::INSTITUTE_ID . "=I." . Institute::INSTITUTE_ID, [Institute::INSTITUTE_NAME => new Expression('(I.' . Institute::INSTITUTE_NAME . ')')], 'left');
        $select->where(["T.TRAINING_ID" => $id]);
        $statement = $sql->prepareStatementForSqlObject($select);
        $result = $statement->execute();
        return $result->current();
    }

    //select only those training list that were not exist in training assign table
    public function selectAll($employeeId) {
        $today = Helper::getcurrentExpressionDate();
        $sql = new Sql($this->adapter);
        $select = $sql->select();
        $select->columns(EntityHelper::getColumnNameArrayWithOracleFns(
                        Training::class, [
                    Training::TRAINING_NAME
                        ], [
                    Training::START_DATE,
                    Training::END_DATE
                        ], NULL, NULL, NULL, 'T')
                , false);
        $select->from(['T' => Training::TABLE_NAME]);
        $select->join(['I' => Institute::TABLE_NAME], "T." . Training::INSTITUTE_ID . "=I." . Institute::INSTITUTE_ID, [Institute::INSTITUTE_NAME => new Expression('(I.' . Institute::INSTITUTE_NAME . ')')], 'left');

        $select->where([
            "T.STATUS='E'",
            // "T.TRAINING_ID IN (SELECT TRAINING_ID FROM HRIS_EMPLOYEE_TRAINING_ASSIGN WHERE STATUS='E' AND EMPLOYEE_ID=$employeeId)"
//            "T.END_DATE<=".$today->getExpression()
        ]);

        $select->order("T.START_DATE DESC");
        $statement = $sql->prepareStatementForSqlObject($select);
		// print_r($statement->getSql()); die();
        $result = $statement->execute();
        return $result;
    }

    public function delete($id) {
        $this->tableGateway->update([Training::STATUS => 'D'], [Training::TRAINING_ID => $id]);
    }

}
