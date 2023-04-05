<?php

namespace Payroll\Repository;

use Application\Helper\Helper;
use Application\Model\Model;
use Application\Repository\HrisRepository;
use Payroll\Model\Rules;
use Traversable;
use Zend\Db\Adapter\AdapterInterface;
use Zend\Db\Sql\Select;

class RulesRepository extends HrisRepository
{

  public function __construct(AdapterInterface $adapter, $tableName = null)
  {
    if ($tableName == null) {
      $tableName = Rules::TABLE_NAME;
    }
    parent::__construct($adapter, $tableName);
  }

  public function add(Model $model)
  {
    return $this->tableGateway->insert($model->getArrayCopyForDB());
  }

  public function edit(Model $model, $id)
  {
    $this->tableGateway->update($model->getArrayCopyForDB(), [Rules::PAY_ID => $id]);
  }

  public function fetchAll(): Traversable
  {
    $query = "SELECT PAY_ID,
                  PAY_CODE,
                  PAY_EDESC,
                  REPLACE(PAY_EDESC, ' ', '_') AS PAY_EDESC_WITH_UNDERSCORE,
                  PAY_TYPE_FLAG,
                  (
                  CASE
                    WHEN PAY_TYPE_FLAG ='A'
                    THEN 'Additon'
                    WHEN PAY_TYPE_FLAG='D'
                    THEN 'Deduction'
                    WHEN PAY_TYPE_FLAG='V'
                    THEN 'View'
                    ELSE 'Tax'
                  END) AS PAY_TYPE,
                  PRIORITY_INDEX,
                  INCLUDE_IN_TAX,
                  (
                  CASE
                    WHEN INCLUDE_IN_TAX = 'Y'
                    THEN 'Yes'
                    ELSE 'No'
                  END ) AS INCLUDE_IN_TAX_DETAIL,
                  INCLUDE_IN_SALARY,
                  (
                  CASE
                    WHEN INCLUDE_IN_SALARY = 'Y'
                    THEN 'Yes'
                    ELSE 'No'
                  END ) AS INCLUDE_IN_SALARY_DETAIL,
                  INCLUDE_PAST_VALUE,
                  (
                  CASE
                    WHEN INCLUDE_PAST_VALUE = 'Y'
                    THEN 'Yes'
                    ELSE 'No'
                  END ) AS INCLUDE_PAST_VALUE_DETAIL,
                  INCLUDE_FUTURE_VALUE,
                  (
                  CASE
                    WHEN INCLUDE_FUTURE_VALUE = 'Y'
                    THEN 'Yes'
                    ELSE 'No'
                  END ) AS INCLUDE_FUTURE_VALUE_DETAIL,
                  DEDUCTION_LIMIT_FLAG,
                  (
                  CASE
                    WHEN DEDUCTION_LIMIT_FLAG = 'Y'
                    THEN 'Yes'
                    ELSE 'No'
                  END ) AS DEDUCTION_LIMIT_FLAG_DETAIL,
                  FORMULA,
                  REMARKS,
                  STATUS
                FROM HRIS_PAY_SETUP
                WHERE STATUS ='E' ORDER BY PRIORITY_INDEX";

    $statement = $this->adapter->query($query);
    return $statement->execute();
  }

  public function fetchById($id)
  {
    return $this->tableGateway->select(function (Select $select) use ($id) {
      $select->where([Rules::STATUS => 'E', Rules::PAY_ID => $id]);
    })->current();
  }

  public function delete($id)
  {
    $rule = new Rules();
    $rule->modifiedDt = Helper::getcurrentExpressionDate();
    $rule->status = 'D';
    $this->tableGateway->update($rule->getArrayCopyForDB(), [Rules::PAY_ID => $id]);
  }

  public function fetchReferencingRules($payId = null): array
  {

    if ($payId == null) {
      $sql = "
                SELECT P.PAY_ID,
                  INITCAP(P.PAY_EDESC) AS PAY_EDESC,
                  INITCAP(P.PAY_LDESC) AS PAY_LDESC
                FROM HRIS_PAY_SETUP P";
      return $this->rawQuery($sql);
    } else {
      $sql = "
                SELECT P.PAY_ID,
                  INITCAP(P.PAY_EDESC) AS PAY_EDESC,
                  INITCAP(P.PAY_LDESC) AS PAY_LDESC
                FROM HRIS_PAY_SETUP P,
                  (SELECT PRIORITY_INDEX FROM HRIS_PAY_SETUP WHERE PAY_ID=:payId
                  ) PS
                WHERE P.PRIORITY_INDEX < PS.PRIORITY_INDEX";
      $boundedParameter = [];
      $boundedParameter['payId'] = $payId;
      return $this->rawQuery($sql, $boundedParameter);
    }
  }

  public function fetchSSRules(): array
  {
    $sql = "SELECT PAY_ID,'H_'||PAY_ID AS PAY_ID_COL,PAY_EDESC
                FROM HRIS_PAY_SETUP
                WHERE --INCLUDE_IN_SALARY='Y'
              --  AND PAY_TYPE_FLAG     IN ('A','D')   AND
               STATUS ='E'
                ORDER BY PRIORITY_INDEX";
    return $this->rawQuery($sql);
  }


  public function fetchAllTypeWise($sheetNo): Traversable
  {
    $query = "SELECT PS.PAY_ID,
                  PS.PAY_CODE,
                  PS.PAY_EDESC,
                  REPLACE(UPPER(PAY_EDESC), ' ', '_') AS PAY_EDESC_WITH_UNDERSCORE,
                  PS.PAY_TYPE_FLAG,
                  (
                  CASE
                    WHEN PS.PAY_TYPE_FLAG ='A'
                    THEN 'Additon'
                    WHEN PS.PAY_TYPE_FLAG='D'
                    THEN 'Deduction'
                    WHEN PS.PAY_TYPE_FLAG='V'
                    THEN 'View'
                    ELSE 'Tax'
                  END) AS PAY_TYPE,
                  PS.PRIORITY_INDEX,
                  PS.INCLUDE_IN_TAX,
                  (
                  CASE
                    WHEN PS.INCLUDE_IN_TAX = 'Y'
                    THEN 'Yes'
                    ELSE 'No'
                  END ) AS INCLUDE_IN_TAX_DETAIL,
                  PS.INCLUDE_IN_SALARY,
                  (
                  CASE
                    WHEN PS.INCLUDE_IN_SALARY = 'Y'
                    THEN 'Yes'
                    ELSE 'No'
                  END ) AS INCLUDE_IN_SALARY_DETAIL,
                  PS.INCLUDE_PAST_VALUE,
                  (
                  CASE
                    WHEN PS.INCLUDE_PAST_VALUE = 'Y'
                    THEN 'Yes'
                    ELSE 'No'
                  END ) AS INCLUDE_PAST_VALUE_DETAIL,
                  PS.INCLUDE_FUTURE_VALUE,
                  (
                  CASE
                    WHEN PS.INCLUDE_FUTURE_VALUE = 'Y'
                    THEN 'Yes'
                    ELSE 'No'
                  END ) AS INCLUDE_FUTURE_VALUE_DETAIL,
                  PS.DEDUCTION_LIMIT_FLAG,
                  (
                  CASE
                    WHEN PS.DEDUCTION_LIMIT_FLAG = 'Y'
                    THEN 'Yes'
                    ELSE 'No'
                  END ) AS DEDUCTION_LIMIT_FLAG_DETAIL,
                  PS.FORMULA,
                  PS.REMARKS,
                  PS.STATUS
                  ,SS.SALARY_TYPE_ID
                  ,PSS.FORMULA AS TYPE_FORMULA
                  ,PSS.FLAG AS TYPE_FLAG
                FROM HRIS_PAY_SETUP PS
                left join (select SALARY_TYPE_ID from Hris_Salary_Sheet where sheet_no=:sheetNo) SS on (1=1)
                LEFT JOIN HRIS_PAY_SETUP_SPECIAL PSS ON (PSS.SALARY_TYPE_ID=SS.SALARY_TYPE_ID AND PS.PAY_ID=PSS.PAY_ID)
                WHERE PS.STATUS ='E' ORDER BY PRIORITY_INDEX";

    $boundedParameter = [];
    $boundedParameter['sheetNo'] = $sheetNo;
    // echo '<pre>';
    // print_r($query);
    // die;
    $statement = $this->adapter->query($query);
    return $statement->execute($boundedParameter);
  }

  public function fetchPreviousSumVal($employeeId, $monthId)
  {
    $boundedParameter = [];
    $boundedParameter['employeeId'] = $employeeId;
    $boundedParameter['monthId'] = $monthId;
    $query = "select 
 '[PS:'||REPLACE(UPPER(ps.PAY_EDESC), ' ', '_')||']' as PAY_EDESC,
  sd.pay_id,
 case when 
 sd.value is not null
 then sd.value
 else 0 
 end
 as value 
 from (select 
        ssd.pay_id,
        nvl(sum(ssd.val),0) as value
        from 
        Hris_Salary_Sheet_Emp_Detail  ssed
        join Hris_Month_Code mc on (mc.month_id=ssed.month_id and 
        mc.FISCAL_YEAR_ID=(select FISCAL_YEAR_ID from Hris_Month_Code where MONTH_ID=:monthId) 
        AND EMPLOYEE_ID=:employeeId)
        join Hris_Salary_Sheet_Detail ssd on (ssed.sheet_no=ssd.sheet_no and ssed.employee_id=ssd.employee_id )
        where 
        ssed.month_id<=:monthId 
        group by ssd.pay_id
          ) sd
          right join HRIS_PAY_SETUP ps on ( sd.PAY_ID=ps.PAY_ID)";
    $statement = $this->adapter->query($query);
    return $statement->execute($boundedParameter);
  }

  public function fetchMaxPayVal($employeeId, $monthId)
  {
    $boundedParameter = [];
    $boundedParameter['employeeId'] = $employeeId;
    $boundedParameter['monthId'] = $monthId;
    $query = "select 
 '[MS:'||REPLACE(UPPER(ps.PAY_EDESC), ' ', '_')||']' as PAY_EDESC,
  sd.pay_id,
 case when 
 sd.value is not null
 then sd.value
 else 0 
 end
 as value 
 from (select 
        ssd.pay_id,
        nvl(max(ssd.val),0) as value
        from 
        Hris_Salary_Sheet_Emp_Detail  ssed
        join Hris_Month_Code mc on (mc.month_id=ssed.month_id and 
        mc.FISCAL_YEAR_ID=(select FISCAL_YEAR_ID from Hris_Month_Code where MONTH_ID=:monthId) 
        AND EMPLOYEE_ID=:employeeId)
        join Hris_Salary_Sheet_Detail ssd on (ssed.sheet_no=ssd.sheet_no and ssed.employee_id=ssd.employee_id )
        where 
        ssed.month_id=:monthId 
        group by ssd.pay_id
          ) sd
          right join HRIS_PAY_SETUP ps on ( sd.PAY_ID=ps.PAY_ID)";
    $statement = $this->adapter->query($query);
    return $statement->execute($boundedParameter);
  }

  public function getCompanyWise($companyId)
  {
    $sql = "select distinct group_id from hris_employees where company_id = {$companyId} and group_id is not null and status='E'";
    // echo '<pre>';print_r($sql);die;
    return $this->rawQuery($sql);
  }

  public function getCompanyWiseGroup($companyId)
  {
    $sql = "select group_name,group_id from HRIS_SALARY_SHEET_GROUP where group_id in (select  distinct 
      group_id from hris_employees where company_id =$companyId and group_id is not null and status='E')
      ";
    // echo '<pre>';print_r($sql);die;
    return $this->rawQuery($sql);
  }

  public function getCompany($id)
  {
    // echo '<pre>';print_r($empId);die;
    $sql = "SELECT
      company_name,
      company_id
  FROM
   hris_company 
  WHERE
      company_id = $id";
    return $this->rawQuery($sql);
  }

  public function getCompanyId($id)
  {
    // echo '<pre>';print_r($empId);die;
    $sql = "SELECT
      hc.company_name,
      hc.company_id,
      hc.company_code
  FROM
   hris_company  hc 
   left join hris_employees he on (hc.company_id=he.company_id)
  WHERE
      he.employee_id = $id";
    return $this->rawQuery($sql);
  }
}
