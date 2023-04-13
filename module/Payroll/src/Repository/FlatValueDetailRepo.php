<?php

namespace Payroll\Repository;

use Application\Helper\EntityHelper;
use Application\Model\Model;
use Application\Repository\RepositoryInterface;
use Payroll\Model\FlatValueDetail;
use Zend\Db\Adapter\AdapterInterface;
use Zend\Db\TableGateway\TableGateway;
use Application\Repository\HrisRepository;

class FlatValueDetailRepo extends HrisRepository implements RepositoryInterface {

    protected $adapter;
    protected $gateway;

    public function __construct(AdapterInterface $adapter) {
        $this->adapter = $adapter;
        $this->gateway = new TableGateway(FlatValueDetail::TABLE_NAME, $adapter);
    }

    public function add(Model $model) {
        $this->gateway->insert($model->getArrayCopyForDB());
    }

    public function edit(Model $model, $id) {
        
    }

    public function fetchAll() {
        
    }

    public function delete($id) {
        
    }

    public function fetchById($id) {
        $sql = "
                SELECT F.FLAT_VALUE
                FROM HRIS_FLAT_VALUE_DETAIL F,
                  (SELECT * FROM HRIS_MONTH_CODE WHERE MONTH_ID=:monthId
                  ) Y
                WHERE F. EMPLOYEE_ID = :employeeId
                AND F.FISCAL_YEAR_ID = F.FISCAL_YEAR_ID
                AND F.FLAT_ID        = :flatId";

        $boundedParameter = [];
        $boundedParameter['monthId'] = $id['MONTH_ID'];
        $boundedParameter['employeeId'] = $id['EMPLOYEE_ID'];
        $boundedParameter['flatId'] = $id['FLAT_ID'];
        return $this->rawQuery($sql, $boundedParameter)[0];

        // $statement = $this->adapter->query($sql);
        // $rawResult = $statement->execute();
        // return $rawResult->current();
    }

    public function getFlatValuesDetailById($flatValueId, $fiscalYearId, $emp, $monthId = null) {
        $searchCondition = EntityHelper::getSearchConditonBounded($emp['companyId'], $emp['branchId'], $emp['departmentId'], $emp['positionId'], $emp['designationId'], $emp['serviceTypeId'], $emp['serviceEventTypeId'], $emp['employeeTypeId'], $emp['employeeId'], $emp['genderId'], $emp['locationId']);

        $boundedParameter = [];
        $boundedParameter=array_merge($boundedParameter, $searchCondition['parameter']);
        
        $empQuery = "SELECT E.EMPLOYEE_ID FROM HRIS_EMPLOYEES E WHERE 1=1 {$searchCondition['sql']} ";

        $sql = "SELECT  FVD.*,EE.EMPLOYEE_CODE FROM HRIS_FLAT_VALUE_DETAIL FVD
                LEFT JOIN HRIS_EMPLOYEES EE on (EE.EMPLOYEE_ID=FVD.EMPLOYEE_ID)  
                WHERE FVD.FLAT_ID = :flatValueId AND FVD.FISCAL_YEAR_ID = :fiscalYearId AND FVD.EMPLOYEE_ID IN ({$empQuery})
                ";

      $boundedParameter['fiscalYearId'] = $fiscalYearId;
      $boundedParameter['flatValueId'] = $flatValueId;


      return $this->rawQuery($sql, $boundedParameter);
        // $statement = $this->adapter->query($sql);
        // return $statement->execute();
    }

    public function postFlatValuesDetail($data) {

        $boundedParameter = [];
        $boundedParameter['flatId'] = $data['flatId'];
        $boundedParameter['employeeId'] = $data['employeeId'];
        $boundedParameter['flatValue'] = $data['flatValue'];
        $boundedParameter['fiscalYearId'] = $data['fiscalYearId'];

        $sql = "
                DECLARE
                  V_FLAT_ID HRIS_FLAT_VALUE_DETAIL.FLAT_ID%TYPE := :flatId;
                  V_EMPLOYEE_ID HRIS_FLAT_VALUE_DETAIL.EMPLOYEE_ID%TYPE := :employeeId;
                  V_FLAT_VALUE HRIS_FLAT_VALUE_DETAIL.FLAT_VALUE%TYPE := :flatValue;
                  V_FISCAL_YEAR_ID HRIS_FLAT_VALUE_DETAIL.FISCAL_YEAR_ID%TYPE := :fiscalYearId;
                  V_OLD_FLAT_VALUE HRIS_FLAT_VALUE_DETAIL.FLAT_VALUE%TYPE;
                BEGIN
                  SELECT FLAT_VALUE
                  INTO V_OLD_FLAT_VALUE
                  FROM HRIS_FLAT_VALUE_DETAIL
                  WHERE FLAT_ID       = V_FLAT_ID
                  AND EMPLOYEE_ID    = V_EMPLOYEE_ID
                  AND FISCAL_YEAR_ID = V_FISCAL_YEAR_ID;
                  
                  UPDATE HRIS_FLAT_VALUE_DETAIL
                  SET FLAT_VALUE      = V_FLAT_VALUE
                  WHERE FLAT_ID       = V_FLAT_ID
                  AND EMPLOYEE_ID    = V_EMPLOYEE_ID
                  AND FISCAL_YEAR_ID = V_FISCAL_YEAR_ID;
                  
                EXCEPTION
                WHEN NO_DATA_FOUND THEN
                  INSERT
                  INTO HRIS_FLAT_VALUE_DETAIL
                    (
                      FLAT_ID,
                      EMPLOYEE_ID,
                      FISCAL_YEAR_ID,
                      FLAT_VALUE,
                      CREATED_DT
                    )
                    VALUES
                    (
                      V_FLAT_ID,
                      V_EMPLOYEE_ID,
                      V_FISCAL_YEAR_ID,
                      V_FLAT_VALUE,
                      TRUNC(SYSDATE)
                    );
                END;
";
        $statement = $this->adapter->query($sql);
        return $statement->execute($boundedParameter);
    }

    public function getBulkFlatValuesDetailById($pivotString, $fiscalYearId, $emp) {

        $searchCondition = EntityHelper::getSearchConditonBounded($emp['companyId'], $emp['branchId'], $emp['departmentId'], $emp['positionId'], $emp['designationId'], $emp['serviceTypeId'], $emp['serviceEventTypeId'], $emp['employeeTypeId'], $emp['employeeId'], $emp['genderId'], $emp['locationId']);
        
        $boundedParameter = [];
        $boundedParameter=array_merge($boundedParameter, $searchCondition['parameter']);

        $empQuery = "SELECT E.EMPLOYEE_ID FROM HRIS_EMPLOYEES E WHERE 1=1 {$searchCondition['sql']}";
        $sql = "
        SELECT * FROM (
        SELECT  e.employee_id,
    fvd.flat_value,
    fvd.flat_id,
    e.employee_code,
    e.SENIORITY_LEVEL,
    e.full_name FROM HRIS_FLAT_VALUE_DETAIL FVD
    RIGHT JOIN HRIS_EMPLOYEES E on (E.EMPLOYEE_ID=FVD.EMPLOYEE_ID AND FVD.FISCAL_YEAR_ID = :fiscalYearId)  WHERE e.status='E' {$searchCondition['sql']}
  ) PIVOT(MAX(FLAT_VALUE) FOR FLAT_ID IN ($pivotString)) order by SENIORITY_LEVEL asc";

        $boundedParameter['fiscalYearId'] = $fiscalYearId;
        return $this->rawQuery($sql, $boundedParameter);
        // $statement = $this->adapter->query($sql);
        // return $statement->execute();
    }

    public function getColumns($flat_id){
      $flat_ids = ':F_' . implode(',:F_', $flat_id);

      $boundedParameter = [];
      for($i = 0; $i < count($flat_id); $i++){
        $boundedParameter['F_'.$flat_id[$i]] = $flat_id[$i];
      }

      $sql = "select flat_id, flat_edesc, 'F_'||flat_id as title from hris_flat_value_setup where flat_id in ($flat_ids)";

      return $this->rawQuery($sql, $boundedParameter);
      // $statement = $this->adapter->query($sql);
      // return $statement->execute();
    }

    public function postBulkFlatValuesDetail($data, $fiscalYearId) {
        $flatId = $data['flatId'];
        $employeeId = $data['employeeId'];
        $fiscalYearId = $fiscalYearId;
        if($data['value'] == null || $data['value'] == ''){
          $sql = "DELETE FROM HRIS_FLAT_VALUE_DETAIL
                  WHERE FLAT_ID       = $flatId
                  AND EMPLOYEE_ID    = $employeeId
                  AND FISCAL_YEAR_ID = $fiscalYearId";
        }
        else{
          $value = $data['value'];
          $sql = "
                DECLARE
                  V_FLAT_ID HRIS_FLAT_VALUE_DETAIL.FLAT_ID%TYPE := $flatId;
                  V_EMPLOYEE_ID HRIS_FLAT_VALUE_DETAIL.EMPLOYEE_ID%TYPE := $employeeId;
                  V_FLAT_VALUE HRIS_FLAT_VALUE_DETAIL.FLAT_VALUE%TYPE := $value;
                  V_FISCAL_YEAR_ID HRIS_FLAT_VALUE_DETAIL.FISCAL_YEAR_ID%TYPE := $fiscalYearId;
                  V_OLD_FLAT_VALUE HRIS_FLAT_VALUE_DETAIL.FLAT_VALUE%TYPE;
                BEGIN
                  SELECT FLAT_VALUE
                  INTO V_OLD_FLAT_VALUE
                  FROM HRIS_FLAT_VALUE_DETAIL
                  WHERE FLAT_ID       = V_FLAT_ID
                  AND EMPLOYEE_ID    = V_EMPLOYEE_ID
                  AND FISCAL_YEAR_ID = V_FISCAL_YEAR_ID;
                  
                  UPDATE HRIS_FLAT_VALUE_DETAIL
                  SET FLAT_VALUE      = V_FLAT_VALUE
                  WHERE FLAT_ID       = V_FLAT_ID
                  AND EMPLOYEE_ID    = V_EMPLOYEE_ID
                  AND FISCAL_YEAR_ID = V_FISCAL_YEAR_ID;
                  
                EXCEPTION
                WHEN NO_DATA_FOUND THEN
                  INSERT
                  INTO HRIS_FLAT_VALUE_DETAIL
                    (
                      FLAT_ID,
                      EMPLOYEE_ID,
                      FISCAL_YEAR_ID,
                      FLAT_VALUE,
                      CREATED_DT
                    )
                    VALUES
                    (
                      V_FLAT_ID,
                      V_EMPLOYEE_ID,
                      V_FISCAL_YEAR_ID,
                      V_FLAT_VALUE,
                      TRUNC(SYSDATE)
                    );
                END;
            ";
        }
        $statement = $this->adapter->query($sql);
        return $statement->execute();
    }

    public function getPositionWiseFlatValue($pivotString, $fiscalYearId, $position_id) {
        $position_ids = ':F_' . implode(',:F_', $position_id);
        $boundedParameter = [];
        for($i = 0; $i < count($position_id); $i++){
          $boundedParameter['F_'.$position_id[$i]] = $position_id[$i];
        }
        $boundedParameter['fiscalYearId'] = $fiscalYearId;
        $sql = "SELECT * FROM (
        SELECT
            pfv.assigned_value,
            pfv.flat_id,
            p.position_id,
            p.position_name,
            p.level_no
        FROM
            hris_position_flat_value   pfv
            RIGHT JOIN hris_positions           p 
            ON ( p.position_id = pfv.position_id AND pfv.fiscal_year_id = :fiscalYearId )
            WHERE P.POSITION_ID IN ($position_ids)
    ) PIVOT (
        MAX ( assigned_value )
        FOR flat_id
        IN ($pivotString)) order by level_no asc";

        return $this->rawQuery($sql, $boundedParameter);
        // $statement = $this->adapter->query($sql);
        // return $statement->execute();
    }

    public function setPositionWiseFlatValue($data, $fiscalYearId) {
        $flatId = $data['flatId'];
        $positionId = $data['positionId'];
        $fiscalYearId = $fiscalYearId; 
          if($data['value'] == null || $data['value'] == ''){
            $sql = "DELETE FROM HRIS_POSITION_FLAT_VALUE
                    WHERE FLAT_ID       = $flatId
                    AND POSITION_ID    = $positionId
                    AND FISCAL_YEAR_ID = $fiscalYearId";
          }
          else{
            $value = $data['value']; 
            $sql = "
                  DECLARE
                    V_FLAT_ID HRIS_POSITION_FLAT_VALUE.FLAT_ID%TYPE := $flatId;
                    V_POSITION_ID HRIS_POSITION_FLAT_VALUE.POSITION_ID%TYPE := $positionId;
                    V_FLAT_VALUE HRIS_POSITION_FLAT_VALUE.ASSIGNED_VALUE%TYPE := $value;
                    V_FISCAL_YEAR_ID HRIS_POSITION_FLAT_VALUE.FISCAL_YEAR_ID%TYPE := $fiscalYearId;
                    V_OLD_FLAT_VALUE HRIS_POSITION_FLAT_VALUE.ASSIGNED_VALUE%TYPE;
                  BEGIN
                    SELECT ASSIGNED_VALUE
                    INTO V_OLD_FLAT_VALUE
                    FROM HRIS_POSITION_FLAT_VALUE
                    WHERE FLAT_ID       = V_FLAT_ID
                    AND POSITION_ID    = V_POSITION_ID
                    AND FISCAL_YEAR_ID = V_FISCAL_YEAR_ID;
                    
                    UPDATE HRIS_POSITION_FLAT_VALUE
                    SET ASSIGNED_VALUE      = V_FLAT_VALUE
                    WHERE FLAT_ID       = V_FLAT_ID
                    AND POSITION_ID    = V_POSITION_ID
                    AND FISCAL_YEAR_ID = V_FISCAL_YEAR_ID;
                    
                  EXCEPTION
                  WHEN NO_DATA_FOUND THEN
                    INSERT
                    INTO HRIS_POSITION_FLAT_VALUE
                      (
                        FLAT_ID,
                        POSITION_ID,
                        FISCAL_YEAR_ID,
                        ASSIGNED_VALUE
                      )
                      VALUES
                      (
                        V_FLAT_ID,
                        V_POSITION_ID,
                        V_FISCAL_YEAR_ID,
                        V_FLAT_VALUE
                      );
                  END;";
          } 
        $statement = $this->adapter->query($sql);
        return $statement->execute();
    }
}
