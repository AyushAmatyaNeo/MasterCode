<?php

namespace SelfService\Repository;

use Application\Helper\EntityHelper;
use Application\Model\Model;
use Application\Repository\RepositoryInterface;
use SelfService\Model\TravelRequest;


use SelfService\Model\TravelExpenses; 
use Zend\Db\Adapter\AdapterInterface;
use Zend\Db\Sql\Expression;
use Zend\Db\Sql\Select;
use Zend\Db\Sql\Sql;
use Zend\Db\TableGateway\TableGateway;

 

use Application\Helper\Helper;

use Application\Repository\HrisRepository;

class TravelExpensesRepository extends HrisRepository implements RepositoryInterface {

    public function __construct(AdapterInterface $adapter) {
        $this->tableGateway = new TableGateway(TravelExpenses::TABLE_NAME, $adapter);
        $this->adapter = $adapter;
    }

    function add(Model $model) {
        $addData=$model->getArrayCopyForDB();
        $this->tableGateway->insert($addData);
        // var_dump('here'); die;
    }

    function edit(Model $model, $id) {
        // TODO: Implement edit() method.
    }

    function delete($id) {
        // TODO: Implement delete() method.
    }

    function fetchAll() {
        
    }

    

    function fetchById($id) {
        $boundedParams = [];

        $sql = "select * from hris_travel_expense where travel_id = :id";
        $boundedParams['id'] = $id;

        return $this->rawQuery($sql, $boundedParams);
    }

    function fetchDomesticById($id){
        $boundedParams = [];

        $sql = "select 
        hte.TRAVEL_EXPENSE_ID, 
        to_char(hte.DEPARTURE_DT,'DD-MON-YYYY') as DEPARTURE_DT,
        hte.DEPARTURE_PLACE,
        hte.ARRAIVAL_PLACE,
        to_char(hte.ARRAIVAL_DT,'DD-MON-YYYY') as ARRAIVAL_DT,
        hte.TRAVEL_ID,
        hte.CONFIG_ID,
        hte.AMOUNT,
        hte.OTHER_EXPENSE,
        hte.TOTAL,
        hte.EXCHANGE_RATE,
        hte.EXPENSE_DATE,
        hte.REMARKS,
        hte.STATUS,
        ctc.domestic_Type, round(hte.amount/ctc.rate,2) as unit,  (hte.arraival_dt - hte.departure_dt) +1 as noOfDays from hris_travel_expense hte 
        left join hris_class_travel_config ctc on (ctc.config_id = hte.config_id)
        where hte.travel_id = {$id} and hte.status='E' and hte.config_id in (select config_id from hris_class_travel_config where travel_type='DOMESTIC')";
        $boundedParams['id'] = $id;
        // print_r($sql);print_r($boundedParams);die;
        return $this->rawQuery($sql, $boundedParams);
    }

    function fetchInternationalById($id){
        $boundedParams = [];

        $sql = "select 
        hte.TRAVEL_EXPENSE_ID, 
        to_char(hte.DEPARTURE_DT,'DD-MON-YYYY') as DEPARTURE_DT,
        hte.DEPARTURE_PLACE,
        hte.ARRAIVAL_PLACE,
        to_char(hte.ARRAIVAL_DT,'DD-MON-YYYY') as ARRAIVAL_DT,
        hte.TRAVEL_ID,
        hte.CONFIG_ID,
        hte.AMOUNT,
        hte.OTHER_EXPENSE,
        hte.TOTAL,
        hte.EXCHANGE_RATE,
        hte.EXPENSE_DATE,
        hte.REMARKS,
        hte.STATUS, ctc.international_Type, round(hte.amount/ctc.rate,2) as unit, (hte.arraival_dt - hte.departure_dt) +1 as noOfDays from hris_travel_expense hte 
        left join hris_class_travel_config ctc on (ctc.config_id = hte.config_id)
        where hte.travel_id = {$id} and hte.status='E' and hte.config_id in (select config_id from hris_class_travel_config where travel_type='INTERNATIONAL')";
        $boundedParams['id'] = $id;
        // print_r($sql);print_r($boundedParams);die;
        return $this->rawQuery($sql, $boundedParams);
    }

    public function getLinkedId($id){
        $sql = "SELECT reference_travel_id FROM HRIS_EMPLOYEE_TRAVEL_REQUEST where travel_id = {$id}";
        return $this->rawQuery($sql)[0]["REFERENCE_TRAVEL_ID"];
    }

    public function deletePreviousData($linkedId){
        $sql ="update hris_travel_expense set status = 'D' where travel_id ={$linkedId};";
        return $this->rawQuery($sql);
    }
}
