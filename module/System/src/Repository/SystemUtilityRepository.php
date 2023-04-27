<?php

namespace System\Repository;

use Application\Helper\EntityHelper;
use Application\Helper\Helper;
use Application\Model\Model;
use Application\Repository\RepositoryInterface;
use Zend\Db\Adapter\AdapterInterface;
use Application\Repository\HrisRepository;

class SystemUtilityRepository extends HrisRepository implements RepositoryInterface {

    protected $adapter;

    public function __construct(AdapterInterface $adapter) {
        $this->adapter = $adapter;
    }

    public function add(Model $model) {
        
    }

    public function delete($id) {
        
    }

    public function edit(Model $model, $id) {
        
    }

    public function fetchAll() {
        
    }

    public function fetchById($id) {
        
    }

    public function filterRecords($branchId, $departmentId, $designationId, $positionId, $employeeType, $serviceTypeId, $companyId = null, $genderId = null, $serviceEventTypeId = null) {
        $condition = EntityHelper::getSearchConditon($companyId, $branchId, $departmentId, $positionId, $designationId, $serviceTypeId, $serviceEventTypeId, $employeeType, null, $genderId);
	
       // $boundedParameter = [];
       // $boundedParameter=array_merge($boundedParameter, $condition['parameter']);

        $sql = "SELECT E.EMPLOYEE_ID                                                AS EMPLOYEE_ID,
              E.COMPANY_ID                                                      AS COMPANY_ID,
              E.EMPLOYEE_CODE                                                   AS EMPLOYEE_CODE,
              INITCAP(E.FIRST_NAME)                                             AS FIRST_NAME,
              INITCAP(E.MIDDLE_NAME)                                            AS MIDDLE_NAME,
              INITCAP(E.LAST_NAME)                                              AS LAST_NAME,
              E.NAME_NEPALI                                                     AS NAME_NEPALI,
              E.GENDER_ID                                                       AS GENDER_ID,
              INITCAP(TO_CHAR(E.BIRTH_DATE, 'DD-MON-YYYY'))                     AS BIRTH_DATE,
              E.BLOOD_GROUP_ID                                                  AS BLOOD_GROUP_ID,
              E.RELIGION_ID                                                     AS RELIGION_ID,
              E.SOCIAL_ACTIVITY                                                 AS SOCIAL_ACTIVITY,
              E.TELEPHONE_NO                                                    AS TELEPHONE_NO,
              E.MOBILE_NO                                                       AS MOBILE_NO,
              E.EXTENSION_NO                                                    AS EXTENSION_NO,
              E.EMAIL_OFFICIAL                                                  AS EMAIL_OFFICIAL,
              E.EMAIL_PERSONAL                                                  AS EMAIL_PERSONAL,
              E.SOCIAL_NETWORK                                                  AS SOCIAL_NETWORK,
              E.EMRG_CONTACT_NAME                                               AS EMRG_CONTACT_NAME,
              E.EMERG_CONTACT_NO                                                AS EMERG_CONTACT_NO,
              E.EMERG_CONTACT_ADDRESS                                           AS EMERG_CONTACT_ADDRESS,
              E.EMERG_CONTACT_RELATIONSHIP                                      AS EMERG_CONTACT_RELATIONSHIP,
              E.ADDR_PERM_HOUSE_NO                                              AS ADDR_PERM_HOUSE_NO,
              E.ADDR_PERM_WARD_NO                                               AS ADDR_PERM_WARD_NO,
              E.ADDR_PERM_STREET_ADDRESS                                        AS ADDR_PERM_STREET_ADDRESS,
              E.ADDR_PERM_COUNTRY_ID                                            AS ADDR_PERM_COUNTRY_ID,
              E.ADDR_PERM_VDC_MUNICIPALITY_ID                                   AS ADDR_PERM_VDC_MUNICIPALITY_ID,
              E.ADDR_TEMP_HOUSE_NO                                              AS ADDR_TEMP_HOUSE_NO,
              E.ADDR_TEMP_WARD_NO                                               AS ADDR_TEMP_WARD_NO,
              E.ADDR_TEMP_STREET_ADDRESS                                        AS ADDR_TEMP_STREET_ADDRESS,
              E.ADDR_TEMP_COUNTRY_ID                                            AS ADDR_TEMP_COUNTRY_ID,
              E.ADDR_TEMP_VDC_MUNICIPALITY_ID                                   AS ADDR_TEMP_VDC_MUNICIPALITY_ID,
              E.FAM_FATHER_NAME                                                 AS FAM_FATHER_NAME,
              E.FAM_FATHER_OCCUPATION                                           AS FAM_FATHER_OCCUPATION,
              E.FAM_MOTHER_NAME                                                 AS FAM_MOTHER_NAME,
              E.FAM_MOTHER_OCCUPATION                                           AS FAM_MOTHER_OCCUPATION,
              E.FAM_GRAND_FATHER_NAME                                           AS FAM_GRAND_FATHER_NAME,
              E.FAM_GRAND_MOTHER_NAME                                           AS FAM_GRAND_MOTHER_NAME,
              E.MARITAL_STATUS                                                  AS MARITAL_STATUS,
              E.FAM_SPOUSE_NAME                                                 AS FAM_SPOUSE_NAME,
              E.FAM_SPOUSE_OCCUPATION                                           AS FAM_SPOUSE_OCCUPATION,
              INITCAP(TO_CHAR(E.FAM_SPOUSE_BIRTH_DATE, 'DD-MON-YYYY'))          AS FAM_SPOUSE_BIRTH_DATE,
              INITCAP(TO_CHAR(E.FAM_SPOUSE_WEDDING_ANNIVERSARY, 'DD-MON-YYYY')) AS FAM_SPOUSE_WEDDING_ANNIVERSARY,
              E.ID_CARD_NO                                                      AS ID_CARD_NO,
              E.ID_LBRF                                                         AS ID_LBRF,
              E.ID_BAR_CODE                                                     AS ID_BAR_CODE,
              E.ID_PROVIDENT_FUND_NO                                            AS ID_PROVIDENT_FUND_NO,
              E.ID_DRIVING_LICENCE_NO                                           AS ID_DRIVING_LICENCE_NO,
              E.ID_DRIVING_LICENCE_TYPE                                         AS ID_DRIVING_LICENCE_TYPE,
              INITCAP(TO_CHAR(E.ID_DRIVING_LICENCE_EXPIRY, 'DD-MON-YYYY'))      AS ID_DRIVING_LICENCE_EXPIRY,
              E.ID_THUMB_ID                                                     AS ID_THUMB_ID,
              E.ID_PAN_NO                                                       AS ID_PAN_NO,
              E.ID_ACCOUNT_NO                                                   AS ID_ACCOUNT_NO,
              E.ID_RETIREMENT_NO                                                AS ID_RETIREMENT_NO,
              E.ID_CITIZENSHIP_NO                                               AS ID_CITIZENSHIP_NO,
              INITCAP(TO_CHAR(E.ID_CITIZENSHIP_ISSUE_DATE, 'DD-MON-YYYY'))      AS ID_CITIZENSHIP_ISSUE_DATE,
              E.ID_CITIZENSHIP_ISSUE_PLACE                                      AS ID_CITIZENSHIP_ISSUE_PLACE,
              E.ID_PASSPORT_NO                                                  AS ID_PASSPORT_NO,
              INITCAP(TO_CHAR(E.ID_PASSPORT_EXPIRY, 'DD-MON-YYYY'))             AS ID_PASSPORT_EXPIRY,
              INITCAP(TO_CHAR(E.JOIN_DATE, 'DD-MON-YYYY'))                      AS JOIN_DATE,
              E.SALARY                                                          AS SALARY,
              E.SALARY_PF                                                       AS SALARY_PF,
              E.REMARKS                                                         AS REMARKS,
              E.STATUS                                                          AS STATUS,
              E.CREATED_DT                                                      AS CREATED_DT,
              E.SERVICE_EVENT_TYPE_ID                                           AS SERVICE_EVENT_TYPE_ID,
              E.SERVICE_TYPE_ID                                                 AS SERVICE_TYPE_ID,
              E.POSITION_ID                                                     AS POSITION_ID,
              E.DESIGNATION_ID                                                  AS DESIGNATION_ID,
              E.DEPARTMENT_ID                                                   AS DEPARTMENT_ID,
              E.BRANCH_ID                                                       AS BRANCH_ID,
              E.APP_SERVICE_EVENT_TYPE_ID                                       AS APP_SERVICE_EVENT_TYPE_ID,
              E.APP_SERVICE_TYPE_ID                                             AS APP_SERVICE_TYPE_ID,
              E.APP_POSITION_ID                                                 AS APP_POSITION_ID,
              E.APP_DESIGNATION_ID                                              AS APP_DESIGNATION_ID,
              E.APP_DEPARTMENT_ID                                               AS APP_DEPARTMENT_ID,
              E.APP_BRANCH_ID                                                   AS APP_BRANCH_ID,
              E.COUNTRY_ID                                                      AS COUNTRY_ID,
              E.PROFILE_PICTURE_ID                                              AS PROFILE_PICTURE_ID,
              E.RETIRED_FLAG                                                    AS RETIRED_FLAG,
              E.EMPLOYEE_TYPE                                                   AS EMPLOYEE_TYPE,
              E.CREATED_BY                                                      AS CREATED_BY,
              E.MODIFIED_BY                                                     AS MODIFIED_BY,
              E.MODIFIED_DT                                                     AS MODIFIED_DT,
              INITCAP(E.FULL_NAME)                                              AS FULL_NAME,
              E.IS_HR                                                           AS IS_HR,
              E.ADDR_TEMP_ZONE_ID                                               AS ADDR_TEMP_ZONE_ID,
              E.ADDR_TEMP_DISTRICT_ID                                           AS ADDR_TEMP_DISTRICT_ID,
              E.ADDR_PERM_ZONE_ID                                               AS ADDR_PERM_ZONE_ID,
              E.ADDR_PERM_DISTRICT_ID                                           AS ADDR_PERM_DISTRICT_ID,
              E.LOCATION_ID                                                     AS LOCATION_ID,
              E.FUNCTIONAL_TYPE_ID                                              AS FUNCTIONAL_TYPE_ID,
              E.FUNCTIONAL_LEVEL_ID                                             AS FUNCTIONAL_LEVEL_ID,
              B.BRANCH_NAME                                                     AS BRANCH_NAME,
              D.DEPARTMENT_NAME                                                 AS DEPARTMENT_NAME,
              DES.DESIGNATION_TITLE                                             AS DESIGNATION_TITLE,
              P.POSITION_NAME                                                   AS POSITION_NAME,
              P.LEVEL_NO                                                        AS LEVEL_NO,
              C.COMPANY_NAME                                                    AS COMPANY_NAME,
              INITCAP(G.GENDER_NAME)                                            AS GENDER_NAME,
              BG.BLOOD_GROUP_CODE                                               AS BLOOD_GROUP_CODE,
              RG.RELIGION_NAME                                                  AS RELIGION_NAME,
              INITCAP(CN.COUNTRY_NAME)                                          AS COUNTRY_NAME,
              INITCAP(VM.VDC_MUNICIPALITY_NAME)                                 AS VDC_MUNICIPALITY_NAME,
              VM1.VDC_MUNICIPALITY_NAME                                         AS VDC_MUNICIPALITY_NAME_TEMP,
              D1.DEPARTMENT_NAME                                                AS APP_DEPARTMENT_NAME,
              DES1.DESIGNATION_TITLE                                            AS APP_DESIGNATION_TITLE,
              P1.POSITION_NAME                                                  AS APP_POSITION_NAME,
              P1.LEVEL_NO                                                       AS APP_LEVEL_NO,
              INITCAP(S1.SERVICE_TYPE_NAME)                                     AS APP_SERVICE_TYPE_NAME,
              INITCAP(SE1.SERVICE_EVENT_TYPE_NAME)                              AS APP_SERVICE_EVENT_TYPE_NAME,
              LOC.LOCATION_EDESC                                                AS LOCATION_EDESC,
              FUNT.FUNCTIONAL_TYPE_EDESC                                        AS FUNCTIONAL_TYPE_EDESC,
              FUNT.FUNCTIONAL_TYPE_CODE                                         AS FUNCTIONAL_TYPE_CODE,
              FUNL.FUNCTIONAL_LEVEL_EDESC                                       AS FUNCTIONAL_LEVEL_EDESC,
              FUNL.FUNCTIONAL_LEVEL_NO                                          AS FUNCTIONAL_LEVEL_NO
            FROM HRIS_EMPLOYEES E
            LEFT JOIN HRIS_BRANCHES B
            ON E.BRANCH_ID=B.BRANCH_ID
            LEFT JOIN HRIS_DEPARTMENTS D
            ON E.DEPARTMENT_ID=D.DEPARTMENT_ID
            LEFT JOIN HRIS_DESIGNATIONS DES
            ON E.DESIGNATION_ID=DES.DESIGNATION_ID
            LEFT JOIN HRIS_POSITIONS P
            ON E.POSITION_ID=P.POSITION_ID
            LEFT JOIN HRIS_COMPANY C
            ON E.COMPANY_ID=C.COMPANY_ID
            LEFT JOIN HRIS_GENDERS G
            ON E.GENDER_ID=G.GENDER_ID
            LEFT JOIN HRIS_BLOOD_GROUPS BG
            ON E.BLOOD_GROUP_ID=BG.BLOOD_GROUP_ID
            LEFT JOIN HRIS_RELIGIONS RG
            ON E.RELIGION_ID=RG.RELIGION_ID
            LEFT JOIN HRIS_COUNTRIES CN
            ON E.COUNTRY_ID=CN.COUNTRY_ID
            LEFT JOIN HRIS_VDC_MUNICIPALITIES VM
            ON E.ADDR_PERM_VDC_MUNICIPALITY_ID=VM.VDC_MUNICIPALITY_ID
            LEFT JOIN HRIS_VDC_MUNICIPALITIES VM1
            ON E.ADDR_TEMP_VDC_MUNICIPALITY_ID=VM1.VDC_MUNICIPALITY_ID
            LEFT JOIN HRIS_DEPARTMENTS D1
            ON E.APP_DEPARTMENT_ID=D1.DEPARTMENT_ID
            LEFT JOIN HRIS_DESIGNATIONS DES1
            ON E.APP_DESIGNATION_ID=DES1.DESIGNATION_ID
            LEFT JOIN HRIS_POSITIONS P1
            ON E.APP_POSITION_ID=P1.POSITION_ID
            LEFT JOIN HRIS_SERVICE_TYPES S1
            ON E.APP_SERVICE_TYPE_ID=S1.SERVICE_TYPE_ID
            LEFT JOIN HRIS_SERVICE_EVENT_TYPES SE1
            ON E.APP_SERVICE_EVENT_TYPE_ID=SE1.SERVICE_EVENT_TYPE_ID
            LEFT JOIN HRIS_LOCATIONS LOC
            ON E.LOCATION_ID=LOC.LOCATION_ID
            LEFT JOIN HRIS_FUNCTIONAL_TYPES FUNT
            ON E.FUNCTIONAL_TYPE_ID=FUNT.FUNCTIONAL_TYPE_ID
            LEFT JOIN HRIS_FUNCTIONAL_LEVELS FUNL
            ON E.FUNCTIONAL_LEVEL_ID=FUNL.FUNCTIONAL_LEVEL_ID
            WHERE E.STATUS          ='E'
            {$condition}
            ORDER BY E.FIRST_NAME ASC";
        
        return $this->rawQuery($sql);
        // $statement = $this->adapter->query($sql);
        // $result = $statement->execute();

        // return Helper::extractDbData($result);
    }

    public function runQuery($query) {
        $sql = $query;
        $result = EntityHelper::rawQueryResult($this->adapter, $sql);
        return Helper::extractDbData($result);
    }

    public function recalYearlyLeave($employeeId,$leaveId){
        $sql=" 
        BEGIN 
        hris_recalculate_leave({$employeeId},{$leaveId});
        commit;
        END;
        ";
       // echo '<pre>';print_r($sql);die;
        $statement =$this->adapter->query($sql);
        $result = $statement->execute();
        return $result->current();
 
    } 

    public function checkMonthlyFlag($leaveId){
        $sql="SELECT IS_MONTHLY FROM HRIS_LEAVE_MASTER_SETUP WHERE LEAVE_ID=$leaveId";
        $statement=$this->adapter->query($sql);
        $result=$statement->execute();
        return $result->current();
    }

    public function recalMonthlyLeave($employeeId,$leaveId){
        $sql=" 
        BEGIN 
        hris_recalc_monthly_leaves({$employeeId},{$leaveId});
        commit;
        END;
        ";

        $statement =$this->adapter->query($sql);
        $result = $statement->execute();
        return $result->current(); 
    }

    public function codeExists($code, $tableName, $columnName, $id = null, $idName = null){
        $sql = "select count(*) as count from {$tableName} where lower({$columnName}) = '{$code}'";
  
        if($id){
            $sql = $sql . " and {$idName} <> {$id}";
        }
  
        $statement = $this->adapter->query($sql);
        return $statement->execute()->current();
    }

}
