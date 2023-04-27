<?php
namespace Payroll\Repository;

use Application\Helper\EntityHelper;
use Application\Model\Model;
use Application\Repository\HrisRepository;
use Payroll\Model\SalarySheetDetail;
use Zend\Db\Adapter\AdapterInterface;

class SalarySheetDetailRepo extends HrisRepository {

    public function __construct(AdapterInterface $adapter, $tableName = null) {
        if ($tableName == null) {
            $tableName = SalarySheetDetail::TABLE_NAME;
        }
        parent::__construct($adapter, $tableName);
    }

    public function add(Model $model) {
        return $this->tableGateway->insert($model->getArrayCopyForDB());
    }

    public function delete($id) {
        return $this->tableGateway->delete([SalarySheetDetail::SHEET_NO => $id]);
    }
    public function deleteBy($by) {
        return $this->tableGateway->delete($by);
    }

    public function fetchById($id) {
        return $this->tableGateway->select($id);
    }

    public function fetchSalarySheetDetail($sheetId) {
        $in = $this->fetchPayIdsAsArray();
        $sql = "SELECT P.*,E.FULL_NAME AS EMPLOYEE_NAME,E.EMPLOYEE_CODE,B.BRANCH_NAME,PO.POSITION_NAME,E.ID_ACCOUNT_NO
                FROM
                  (SELECT *
                  FROM
                    (SELECT SHEET_NO,
                      EMPLOYEE_ID,
                      PAY_ID,
                      VAL
                    FROM HRIS_SALARY_SHEET_DETAIL
                    WHERE SHEET_NO                =:sheetId
                    ) PIVOT (MAX(VAL) FOR PAY_ID IN ({$in}))
                  ) P
                JOIN HRIS_EMPLOYEES E
                ON (P.EMPLOYEE_ID=E.EMPLOYEE_ID) 
                LEFT JOIN HRIS_BRANCHES B ON (B.BRANCH_ID=E.BRANCH_ID)
                LEFT JOIN HRIS_POSITIONS PO ON (PO.POSITION_ID=E.POSITION_ID)";

        $boundedParameter = [];
        $boundedParameter['sheetId'] = $sheetId;

        return $this->rawQuery($sql, $boundedParameter);
    }

    public function fetchSalarySheetEmp($monthId, $employeeId) {
        $in = $this->fetchPayIdsAsArray();
        $sql = "SELECT P.*,E.FULL_NAME AS EMPLOYEE_NAME
                FROM
                  (SELECT *
                  FROM
                    (SELECT EMPLOYEE_ID,
                      PAY_ID,
                      VAL
                    FROM HRIS_SALARY_SHEET_DETAIL
                    WHERE SHEET_NO                =(SELECT SHEET_NO FROM HRIS_SALARY_SHEET WHERE MONTH_ID =:monthId)
                    AND EMPLOYEE_ID               =:employeeId
                    ) PIVOT (MAX(VAL) FOR PAY_ID IN ({$in}))
                  ) P
                JOIN HRIS_EMPLOYEES E
                ON (P.EMPLOYEE_ID=E.EMPLOYEE_ID)";

        $boundedParameter = [];
        $boundedParameter['monthId'] = $monthId;
        $boundedParameter['employeeId'] = $employeeId;
        return $this->rawQuery($sql, $boundedParameter);
        // return EntityHelper::rawQueryResult($this->adapter, $sql);
    }

    public function fetchPayIdsAsArray() {
        $rawList = EntityHelper::rawQueryResult($this->adapter, "SELECT PAY_ID FROM HRIS_PAY_SETUP WHERE STATUS ='E'");
        $dbArray = "";
        foreach ($rawList as $key => $row) {
            if ($key == sizeof($rawList)) {
                $dbArray .= "{$row['PAY_ID']} AS P_{$row['PAY_ID']}";
            } else {
                $dbArray .= "{$row['PAY_ID']} AS P_{$row['PAY_ID']},";
            }
        }
        return $dbArray;
    }

    public function fetchPrevSumPayValue($employeeId, $fiscalYearId, $fiscalYearMonthNo) {
        $sql = "SELECT SSD.PAY_ID,
                  SUM(SSD.VAL) AS PREV_SUM_VAL
                FROM HRIS_SALARY_SHEET_DETAIL SSD
                JOIN HRIS_SALARY_SHEET SS
                ON (SSD.SHEET_NO =SS.SHEET_NO)
                JOIN HRIS_MONTH_CODE MC
                ON (SS.MONTH_ID             =MC.MONTH_ID)
                WHERE MC.FISCAL_YEAR_ID     =:fiscalYearId
                AND MC.FISCAL_YEAR_MONTH_NO <:fiscalYearMonthNo
                AND SSD.EMPLOYEE_ID         =:employeeId
                GROUP BY SSD.PAY_ID";
        
        $boundedParameter = [];
        $boundedParameter['fiscalYearMonthNo'] = $fiscalYearMonthNo;
        $boundedParameter['fiscalYearId'] = $fiscalYearId;
        $boundedParameter['employeeId'] = $employeeId;
        return $this->rawQuery($sql, $boundedParameter);
    }

    public function fetchEmployeePaySlip($monthId, $employeeId,$salaryTypeId=1) {
        $sql = "SELECT 
                ts.sheet_no,
                ts.employee_id,
                ts.pay_id,
                CASE
                WHEN ts.val = 0 THEN '0.00'
                ELSE to_char(ts.val, '99,99,999.99')
                END AS val,
                  P.PAY_TYPE_FLAG,
                  P.PAY_EDESC
                FROM HRIS_SALARY_SHEET_DETAIL TS
                LEFT JOIN HRIS_PAY_SETUP P
                ON (TS.PAY_ID         =P.PAY_ID)
                WHERE P.INCLUDE_IN_SALARY='Y' AND TS.VAL !=0
                AND TS.SHEET_NO       IN
                  (SELECT SHEET_NO FROM HRIS_SALARY_SHEET WHERE MONTH_ID =:monthId 
                      AND SALARY_TYPE_ID=:salaryTypeId  AND APPROVED='Y'
                  )
                AND EMPLOYEE_ID =:employeeId ORDER BY P.PRIORITY_INDEX";
        // echo '<pre>';print_r($sql);die;
        $boundedParameter = [];
        $boundedParameter['monthId'] = $monthId;
        $boundedParameter['salaryTypeId'] = $salaryTypeId;
        $boundedParameter['employeeId'] = $employeeId;
        return $this->rawQuery($sql, $boundedParameter);
    }

    public function fetchEmployeeLoanAmt($monthId,$employeeId,$ruleId) {
        $sql="select 
        case when
        sum(AMOUNT) is not null 
        then sum(AMOUNT)
        else 0
        end
        as AMT
        from Hris_Loan_Payment_Detail pd
        left join hris_employee_loan_request lr on (pd.Loan_Request_Id=lr.loan_request_id)
        left join hris_loan_master_setup lms  on (lms.LOAN_ID=lr.LOAN_ID)
        join HRIS_PAY_SETUP ps on (lms.PAY_ID_AMT=ps.PAY_ID AND PS.PAY_ID=:ruleId)
        join hris_month_code mc on (Mc.From_Date=trunc(Pd.From_Date,'month') and Mc.To_Date=Pd.To_Date)
        where 
        lr.loan_status='OPEN'
        and Lr.Employee_Id=:employeeId
        and mc.month_id=:monthId";
        $boundedParameter = [];
        $boundedParameter['monthId'] = $monthId;
        $boundedParameter['ruleId'] = $ruleId;
        $boundedParameter['employeeId'] = $employeeId;
        $resultList = $this->rawQuery($sql, $boundedParameter);
        return ($resultList[0]['AMT'])?$resultList[0]['AMT']:0;
        
    }
    public function fetchEmployeeLoanIntrestAmt($monthId,$employeeId,$ruleId) {
        $sql="select 
        case when
        sum(INTEREST_AMOUNT) is not null 
        then sum(INTEREST_AMOUNT)
        else 0
        end
        as AMT
        from Hris_Loan_Payment_Detail pd
        left join hris_employee_loan_request lr on (pd.Loan_Request_Id=lr.loan_request_id)
        left join hris_loan_master_setup lms  on (lms.LOAN_ID=lr.LOAN_ID)
        join HRIS_PAY_SETUP ps on (lms.PAY_ID_INT=ps.PAY_ID AND PS.PAY_ID={$ruleId})
        join hris_month_code mc on (Mc.From_Date=trunc(Pd.From_Date,'month') and Mc.To_Date=Pd.To_Date)
        where 
        lr.loan_status='OPEN'
        and Lr.Employee_Id={$employeeId}
        and mc.month_id={$monthId}";
        $boundedParameter = [];
        $boundedParameter['monthId'] = $monthId;
        $boundedParameter['ruleId'] = $ruleId;
        $boundedParameter['employeeId'] = $employeeId;
        $resultList = $this->rawQuery($sql, $boundedParameter);
        
        return ($resultList[0]['AMT'])?$resultList[0]['AMT']:0;
        
    }
    
    public function fetchSalarySheetByGroupSheet($monthId,$groupId,$sheetNo,$salaryTypeId,$companyId) {
        
        
           $sheetString = $sheetNo;
        //    echo '<pre>';print_r($sheetNo );die;
        if ($sheetNo == -1) {
//            echo is_array($groupId);
            if (is_array($groupId)) {

                $valuesinCSV = "";
                for ($i = 0; $i < sizeof($groupId); $i++) {
                    $value = $groupId[$i];
//                $value = isString ? "'{$group[$i]}'" : $group[$i];
                    if ($i + 1 == sizeof($groupId)) {
                        $valuesinCSV .= "{$value}";
                    } else {
                        $valuesinCSV .= "{$value},";
                    }
                }

                $sheetString = "select sheet_no from HRIS_SALARY_SHEET where month_id={$monthId} and salary_type_id={$salaryTypeId} and group_id in ($valuesinCSV)";
            }else{
                $sheetString = "select sheet_no from HRIS_SALARY_SHEET where month_id={$monthId} and salary_type_id={$salaryTypeId}";
            }
        }

        $in = $this->fetchPayIdsAsArray();
        if ($companyId>0){
            $sql = "SELECT P.*,E.FULL_NAME AS EMPLOYEE_NAME,E.EMPLOYEE_CODE,B.BRANCH_NAME,PO.POSITION_NAME,E.ID_ACCOUNT_NO
            FROM
              (SELECT *
              FROM
                (SELECT SSED.SHEET_NO,
                  SSED.EMPLOYEE_ID,
                  SSD.PAY_ID,
                  SSD.VAL
                FROM HRIS_SALARY_SHEET_DETAIL SSD
                RIGHT JOIN HRIS_SALARY_SHEET_EMP_DETAIL SSED ON (SSD.SHEET_NO=SSED.SHEET_NO AND SSD.EMPLOYEE_ID=SSED.EMPLOYEE_ID)
                WHERE SSED.SHEET_NO in ({$sheetString}) and SSED.company_id=$companyId
                ) PIVOT (MAX(VAL) FOR PAY_ID IN ({$in}))
              ) P
            JOIN HRIS_EMPLOYEES E
            ON (P.EMPLOYEE_ID=E.EMPLOYEE_ID) 
            LEFT JOIN HRIS_BRANCHES B ON (B.BRANCH_ID=E.BRANCH_ID)
            LEFT JOIN HRIS_POSITIONS PO ON (PO.POSITION_ID=E.POSITION_ID)";
        }else{
            $sql = "SELECT P.*,E.FULL_NAME AS EMPLOYEE_NAME,E.EMPLOYEE_CODE,B.BRANCH_NAME,PO.POSITION_NAME,E.ID_ACCOUNT_NO
                FROM
                  (SELECT *
                  FROM
                    (SELECT SSED.SHEET_NO,
                      SSED.EMPLOYEE_ID,
                      SSD.PAY_ID,
                      SSD.VAL
                    FROM HRIS_SALARY_SHEET_DETAIL SSD
                    RIGHT JOIN HRIS_SALARY_SHEET_EMP_DETAIL SSED ON (SSD.SHEET_NO=SSED.SHEET_NO AND SSD.EMPLOYEE_ID=SSED.EMPLOYEE_ID)
                    WHERE SSED.SHEET_NO in ({$sheetString}) 
                    ) PIVOT (MAX(VAL) FOR PAY_ID IN ({$in}))
                  ) P
                JOIN HRIS_EMPLOYEES E
                ON (P.EMPLOYEE_ID=E.EMPLOYEE_ID) 
                LEFT JOIN HRIS_BRANCHES B ON (B.BRANCH_ID=E.BRANCH_ID)
                LEFT JOIN HRIS_POSITIONS PO ON (PO.POSITION_ID=E.POSITION_ID)";
        }
        
// echo '<pre>';print_r($sql);die;
        return EntityHelper::rawQueryResult($this->adapter, $sql);
    }
    
    public function fetchEmployeePreviousSum($monthId,$employeeId,$ruleId) {
                $sql="select 
        nvl(sum(val),0) as value
        from 
        (
        select 
        Ssd.val,
        Mc.Fiscal_Year_Id,ssed.* 
        from 
        Hris_Salary_Sheet_Emp_Detail  ssed
        join Hris_Month_Code mc on (mc.month_id=ssed.month_id AND EMPLOYEE_ID=:employeeId)
        join Hris_Salary_Sheet_Detail ssd on (ssed.sheet_no=ssd.sheet_no and ssed.employee_id=ssd.employee_id and pay_id=:ruleId)
        where 
        ssed.month_id<:monthId 
        and Mc.Fiscal_Year_Id = (select fiscal_year_id from Hris_Month_Code where Month_Id=:monthId)
        )";
        $boundedParameter = [];
        $boundedParameter['monthId'] = $monthId;
        $boundedParameter['ruleId'] = $ruleId;
        $boundedParameter['employeeId'] = $employeeId;
        $resultList = $this->rawQuery($sql, $boundedParameter);
        return $resultList[0]['VALUE'];
    }
    
    public function fetchEmployeePreviousMonthAmount($monthId,$employeeId,$ruleId) {
                $sql="select 
        nvl(sum(val),0) as value
        from 
        (
        select 
        case when cm.Fiscal_Year_Month_no=1 then 0 else Ssd.val end as val,
        Mc.Fiscal_Year_Id,ssed.* 
        from 
        Hris_Salary_Sheet_Emp_Detail  ssed
        join Hris_Month_Code mc on (mc.month_id=ssed.month_id AND EMPLOYEE_ID=:employeeId)
        join Hris_Salary_Sheet_Detail ssd on (ssed.sheet_no=ssd.sheet_no and ssed.employee_id=ssd.employee_id and pay_id=:ruleId)
         join (select * from Hris_Month_Code where Month_Id=:monthId) cm on (1=1) 
        where 
        ssed.month_id=(:monthId -1 )  
        and Mc.Fiscal_Year_Id = (select fiscal_year_id from Hris_Month_Code where Month_Id=:monthId)
        )";
        $boundedParameter = [];
        $boundedParameter['monthId'] = $monthId;
        $boundedParameter['ruleId'] = $ruleId;
        $boundedParameter['employeeId'] = $employeeId;
        $resultList = $this->rawQuery($sql, $boundedParameter);
        return $resultList[0]['VALUE'];
    }
    
    public function fetchEmployeeGrade($monthId,$employeeId){
        $sql="select 
                    aa.*
                    ,case when (new_Grade=0  and aa.MONTH_CHECK=0 )
                    then 
                    aa.month_days
                    when aa.MONTH_CHECK=2 then 0
                    else
                    aa.month_days - (aa.to_date - aa.grade_date) - 1
                    end as cur_Grade_days
                    ,case when new_Grade=0 
                    then 
                    0
                    when aa.MONTH_CHECK=2 then 
                    aa.month_days
                    else
                    (aa.to_date - aa.grade_date) + 1
                    end as new_Grade_days
                    from 

                    (select 
                    eg.employee_code,eg.OPENING_GRADE,eg.additional_grade,eg.grade_value,eg.grade_date
                    ,mc.FROM_DATE,mc.TO_DATE
                    ,eg.OPENING_GRADE+eg.additional_grade as cur_grade
                    ,case when
                    (eg.grade_date between mc.from_date and mc.to_date ) or  ( mc.from_date > eg.grade_date )
                    then
                    eg.OPENING_GRADE+eg.additional_grade +eg.GRADE_VALUE
                    else
                    0
                    end as new_Grade,
                    (mc.to_date-mc.from_date +1) as month_days,
                     case 
                    when eg.grade_date between mc.from_date and mc.to_date  THEN 1
                    when mc.from_date > eg.grade_date then 2
                    ELSE
                    0
                    end as MONTH_CHECK
                    from HR_EMPLOYEE_GRADE_INFO eg
                    left join HRIS_MONTH_CODE mc on (mc.month_id=:monthId)
                    where employee_code=:employeeId) aa";
        $boundedParameter = [];
        $boundedParameter['monthId'] = $monthId;
        $boundedParameter['employeeId'] = $employeeId;
        $resultList = $this->rawQuery($sql, $boundedParameter);
        if(!empty($resultList)){
        return $resultList[0];
        }else{
            return $resultList;
        } 
    }
    
    
     public function fetchEmployeeGratuityPercentage($monthId,$employeeId){
        $sql="SELECT 
 E.GRATUITY_DATE,MC.TO_DATE 
, E.GRATUITY_DATE  + interval '10' year as ten_yrs
, E.GRATUITY_DATE  + interval '15' year as fifteen_yrs
, E.GRATUITY_DATE  + interval '20' year as twenty_yrs 
,
case 
when MC.TO_DATE  >  ( E.GRATUITY_DATE  + interval '20' year)  then 16.67
when MC.TO_DATE  > ( E.GRATUITY_DATE  + interval '15' year)  then 14.58
when MC.TO_DATE  > ( E.GRATUITY_DATE  + interval '10' year)  then 12.50
when ( MC.TO_DATE  >=  E.GRATUITY_DATE ) then 8.33
else
0
end as GRATUTITY_PERCENT
 FROM HRIS_EMPLOYEES E
 LEFT JOIN ( SELECT * FROM  HRIS_MONTH_CODE WHERE MONTH_ID=:monthId) MC ON (1=1)
 WHERE EMPLOYEE_ID=:employeeId";
        $boundedParameter = [];
        $boundedParameter['monthId'] = $monthId;
        $boundedParameter['employeeId'] = $employeeId;
        $resultList = $this->rawQuery($sql, $boundedParameter);
        return $resultList[0]['GRATUTITY_PERCENT'];
    }
    
    

}
