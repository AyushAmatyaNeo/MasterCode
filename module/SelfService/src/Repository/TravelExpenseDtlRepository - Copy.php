<?php

namespace SelfService\Repository;

use Zend\Db\Sql\Expression;
use Zend\Db\Sql\Select;
use Zend\Db\Sql\Sql;
use Zend\Db\Adapter\AdapterInterface;
use Zend\Db\TableGateway\TableGateway;
use SelfService\Model\TravelExpenseDetail;
use SelfService\Model\TravelRequest;
use Application\Model\Model;
use Application\Repository\RepositoryInterface;
use Zend\Authentication\AuthenticationService;
use Application\Helper\Helper;
use Setup\Model\TravelExpenseClass;

class TravelExpenseDtlRepository implements RepositoryInterface {

    private $tableGateway;
    private $adapter;
    private $loggedInEmployee;

    public function __construct(AdapterInterface $adapter) {
        $this->tableGateway = new TableGateway(TravelExpenseDetail::TABLE_NAME, $adapter);
        $this->adapter = $adapter;
        $auth = new AuthenticationService();
        $this->loggedInEmployee = $auth->getStorage()->read()['employee_id'];
    }

    public function add(Model $model) {
        $this->tableGateway->insert($model->getArrayCopyForDB());
    }

    public function delete($id) {
        $modifiedDt = Helper::getcurrentExpressionDate();
        $employeeID = $this->loggedInEmployee;
        $this->tableGateway->update([TravelExpenseDetail::STATUS => 'D', TravelExpenseDetail::MODIFIED_BY => $employeeID, TravelExpenseDetail::MODIFIED_DATE => $modifiedDt], [TravelExpenseDetail::ID => $id]);
    }

    public function edit(Model $model, $id) {
        $tempData = $model->getArrayCopyForDB();
        $this->tableGateway->update($model->getArrayCopyForDB(), [TravelExpenseDetail::ID => $id]);
    }

    public function fetchAll() {
        
    }

    public function fetchByTravelId($travelId) {
        $sql = new Sql($this->adapter);
        $select = $sql->select();
        $select->columns([
            new Expression("TR.ID AS ID"),
            new Expression("TR.TRAVEL_ID AS TRAVEL_ID"),
            new Expression("TO_CHAR(TR.DEPARTURE_DATE,'DD-MON-YYYY') AS DEPARTURE_DATE"),
            new Expression("TO_CHAR(TR.DEPARTURE_TIME,'HH:MI AM') AS DEPARTURE_TIME"),
            new Expression("TR.DEPARTURE_PLACE AS DEPARTURE_PLACE"),
            new Expression("TO_CHAR(TR.DESTINATION_DATE,'DD-MON-YYYY') AS DESTINATION_DATE"),
            new Expression("TO_CHAR(TR.DESTINATION_TIME,'HH:MI AM') AS DESTINATION_TIME"),
            new Expression("TR.DESTINATION_PLACE AS DESTINATION_PLACE"),
            new Expression("TR.TRANSPORT_TYPE AS TRANSPORT_TYPE"),
            new Expression("(CASE WHEN TR.TRANSPORT_TYPE = 'AP' THEN 'Aeroplane' WHEN TR.TRANSPORT_TYPE = 'OV' THEN 'Office Vehicles' WHEN TR.TRANSPORT_TYPE = 'TI' THEN 'Taxi' WHEN TR.TRANSPORT_TYPE = 'BS' THEN 'Bus' WHEN TR.TRANSPORT_TYPE = 'OF' THEN 'On Foot' END) AS TRANSPORT_TYPE_DETAIL"),
            new Expression("TR.FARE AS FARE"),
            new Expression("TR.ALLOWANCE AS ALLOWANCE"),
            new Expression("TR.LOCAL_CONVEYENCE AS LOCAL_CONVEYENCE"),
            new Expression("TR.MISC_EXPENSES AS MISC_EXPENSES"),
            new Expression("TR.TOTAL_AMOUNT AS TOTAL_AMOUNT"),
          //  new Expression("HTEC.CATEGORY_NAME AS CATEGORY"),
            new Expression("TR.REMARKS AS REMARKS"),
            
                ], false);
        $select->from(['TR' => TravelExpenseDetail::TABLE_NAME])
                ->join(['T' => TravelRequest::TABLE_NAME], "T." . TravelRequest::TRAVEL_ID . "=TR." . TravelExpenseDetail::TRAVEL_ID, [TravelRequest::TRAVEL_CODE])
               ->join(['HTEC' => TravelExpenseClass::TABLE_NAME], "HTEC." . TravelExpenseClass::ID . "=TR." . TravelExpenseDetail::CATEGORY_ID,[TravelExpenseClass::CATEGORY_NAME]);
                

        $select->where([
            "TR.TRAVEL_ID" => $travelId,
            "TR.STATUS" => 'E'
        ]);

        $select->order("TR.ID");
        $statement = $sql->prepareStatementForSqlObject($select);
       
        $result = $statement->execute();
      // echo '<pre>'; print_r($result);die;
        return $result;
    }

    public function fetchById($id) {
        
    }

}
